<?php
declare(strict_types=1);

namespace PhantomCore\Demo;

defined('ABSPATH') || exit;

class Demo_Installer {
    private const ALLOWED_EXTENSIONS = [
        'html', 'css', 'js', 'json',
        'png', 'jpg', 'jpeg', 'gif', 'svg', 'webp',
        'woff', 'woff2', 'ttf', 'eot',
    ];

    private const MAX_ZIP_SIZE = 52428800;

    public function __construct(
        private Demo_Registry $registry
    ) {}

    public function install(string $zip_path): Demo_Result {
        if (!file_exists($zip_path)) {
            return Demo_Result::fail('ZIP file not found.', ['file_not_found' => $zip_path]);
        }

        if (filesize($zip_path) > self::MAX_ZIP_SIZE) {
            return Demo_Result::fail(
                'ZIP file exceeds maximum size of 50MB.',
                ['max_size' => self::MAX_ZIP_SIZE]
            );
        }

        $validate = $this->validate_zip($zip_path);
        if (!$validate->success) {
            return $validate;
        }

        $slug = $validate->data['slug'];

        $target = $this->get_target_path($slug);
        if (file_exists($target)) {
            return Demo_Result::fail(
                sprintf('Demo "%s" is already installed.', $slug),
                ['slug_exists' => $slug]
            );
        }

        $zip = new \ZipArchive();
        $res = $zip->open($zip_path);
        if ($res !== true) {
            return Demo_Result::fail('Failed to open ZIP archive.', ['zip_error' => $res]);
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            if ($filename === false) {
                continue;
            }

            $normalized = str_replace('\\', '/', $filename);
            if (strpos($normalized, '..') !== false) {
                $zip->close();
                return Demo_Result::fail(
                    'ZIP file contains path traversal sequences.',
                    ['path_traversal' => $normalized]
                );
            }

            if ($normalized !== 'demo.json') {
                $ext = strtolower(pathinfo($normalized, PATHINFO_EXTENSION));
                if (!in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
                    $zip->close();
                    return Demo_Result::fail(
                        sprintf('Disallowed file extension in ZIP: .%s', $ext),
                        ['disallowed_ext' => $ext, 'file' => $normalized]
                    );
                }
            }
        }

        if (!mkdir($target, 0755, true) && !is_dir($target)) {
            $zip->close();
            return Demo_Result::fail('Failed to create target directory.', ['target' => $target]);
        }

        $zip->extractTo($target);
        $zip->close();

        if (!file_exists($target . '/demo.json')) {
            $this->rmdir_recursive($target);
            return Demo_Result::fail(
                'Extraction failed: demo.json not found after extraction.',
                ['missing_manifest' => true]
            );
        }

        if (!is_dir($target . '/html')) {
            $this->rmdir_recursive($target);
            return Demo_Result::fail(
                'Extraction failed: html/ directory not found.',
                ['missing_html_dir' => true]
            );
        }

        $this->registry->refresh();
        $demo = $this->registry->get($slug);

        return Demo_Result::ok(
            sprintf('Demo "%s" installed successfully.', $validate->data['name']),
            ['slug' => $slug, 'name' => $validate->data['name'], 'demo' => $demo]
        );
    }

    public function delete(string $slug): Demo_Result {
        if ($slug === 'kids') {
            return Demo_Result::fail('Cannot delete the default demo.', ['protected_demo' => 'kids']);
        }

        $demo = $this->registry->get($slug);
        if ($demo === null) {
            return Demo_Result::fail(
                sprintf('Demo "%s" is not installed.', $slug),
                ['demo_not_found' => $slug]
            );
        }

        $active_slug = get_option('phantom_active_demo', 'kids');
        if ($slug === $active_slug) {
            return Demo_Result::fail(
                sprintf('Cannot delete active demo "%s". Deactivate it first.', $slug),
                ['demo_active' => $slug]
            );
        }

        $target = $this->get_target_path($slug);
        if (!is_dir($target)) {
            return Demo_Result::fail(
                sprintf('Demo directory not found: %s', $target),
                ['dir_not_found' => $target]
            );
        }

        $this->rmdir_recursive($target);

        $this->registry->refresh();

        return Demo_Result::ok(
            sprintf('Demo "%s" deleted successfully.', $demo->name),
            ['slug' => $slug]
        );
    }

    public function validate_zip(string $zip_path): Demo_Result {
        if (!file_exists($zip_path)) {
            return Demo_Result::fail('ZIP file not found.');
        }

        $zip = new \ZipArchive();
        $res = $zip->open($zip_path);
        if ($res !== true) {
            return Demo_Result::fail('Failed to open ZIP archive.', ['zip_error' => $res]);
        }

        $manifest_content = $zip->getFromName('demo.json');
        if ($manifest_content === false) {
            $zip->close();
            return Demo_Result::fail(
                'ZIP must contain a demo.json manifest at its root.',
                ['missing_manifest' => true]
            );
        }

        $data = json_decode($manifest_content, true);
        if (!is_array($data)) {
            $zip->close();
            return Demo_Result::fail(
                'demo.json contains invalid JSON.',
                ['invalid_json' => true]
            );
        }

        if (empty($data['slug'])) {
            $zip->close();
            return Demo_Result::fail(
                'demo.json must contain a "slug" field.',
                ['missing_slug' => true]
            );
        }

        $slug = preg_replace('/[^a-z0-9-]/', '', strtolower($data['slug']));
        if (empty($slug)) {
            $zip->close();
            return Demo_Result::fail(
                'Demo slug must contain only lowercase letters, numbers, and hyphens.',
                ['invalid_slug' => true]
            );
        }

        $zip->close();

        return Demo_Result::ok('ZIP file is valid.', [
            'slug' => $slug,
            'name' => $data['name'] ?? ucwords(str_replace('-', ' ', $slug)),
        ]);
    }

    public function get_target_path(string $slug): string {
        return PHANTOM_CORE_PATH . 'frontend/templates/' . $slug;
    }

    private function rmdir_recursive(string $dir): void {
        if (!is_dir($dir)) {
            return;
        }
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->rmdir_recursive($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }
}
