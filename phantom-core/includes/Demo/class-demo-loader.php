<?php
declare(strict_types=1);

namespace PhantomCore\Demo;

use PhantomCore\Engine\Template_Loader;

defined('ABSPATH') || exit;

class Demo_Loader {
    public function __construct(
        private Template_Loader $template_loader,
        private Demo_Registry $registry
    ) {}

    public function get_active_template_path(): string {
        $active = $this->registry->get_active();
        $path = PHANTOM_CORE_PATH . 'frontend/templates/' . $active->slug . '/html/';
        if (is_dir($path)) return $path;
        // Fallback to pack-based templates
        $pack = get_option('phantom_template_pack', 'default');
        if ($pack !== 'default') {
            $pack_path = PHANTOM_CORE_PATH . 'frontend/packs/' . $pack . '/html/';
            if (is_dir($pack_path)) return $pack_path;
        }
        return PHANTOM_CORE_PATH . 'frontend/html/';
    }

    public function get_active_asset_url(): string {
        $active = $this->registry->get_active();
        $path = PHANTOM_CORE_PATH . 'frontend/templates/' . $active->slug . '/';
        if (is_dir($path)) {
            return PHANTOM_CORE_URL . 'frontend/templates/' . $active->slug . '/';
        }
        return PHANTOM_CORE_URL . 'frontend/';
    }

    public function has_template(string $template_name): bool {
        $active = $this->registry->get_active();
        $path = PHANTOM_CORE_PATH . 'frontend/templates/' . $active->slug . '/html/' . $template_name;
        return file_exists($path);
    }

    public function get_active_css_files(): array {
        return $this->scan_directory('css', 'css');
    }

    public function get_active_js_files(): array {
        return $this->scan_directory('js', 'js');
    }

    public function get_screenshot_url(string $slug): ?string {
        $path = PHANTOM_CORE_PATH . 'frontend/templates/' . $slug . '/preview.jpg';
        if (file_exists($path)) {
            return PHANTOM_CORE_URL . 'frontend/templates/' . $slug . '/preview.jpg';
        }
        return null;
    }

    private function scan_directory(string $subdir, string $extension): array {
        $active = $this->registry->get_active();
        $dir = PHANTOM_CORE_PATH . 'frontend/templates/' . $active->slug . '/' . $subdir . '/';
        if (!is_dir($dir)) {
            return [];
        }
        $files = [];
        $entries = scandir($dir);
        foreach ($entries as $entry) {
            if (pathinfo($entry, PATHINFO_EXTENSION) === $extension) {
                $files[] = $entry;
            }
        }
        sort($files);
        return $files;
    }
}
