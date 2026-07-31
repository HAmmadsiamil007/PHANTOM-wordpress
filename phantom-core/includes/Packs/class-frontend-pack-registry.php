<?php
declare(strict_types=1);

namespace PhantomCore\Packs;

defined('ABSPATH') || exit;

class Frontend_Pack_Registry {
    private const BUILTIN = ['dark', 'minimal', 'bold'];

    private const MAX_ZIP_SIZE = 20971520;

    private static ?self $instance = null;

    private array $packs = [];

    private ?string $scanned_base = null;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function default_base_path(): string {
        return PHANTOM_CORE_PATH . 'frontend/packs';
    }

    public function scan(?string $base_path = null): void {
        $base = $base_path ?? $this->default_base_path();
        $this->scanned_base = $base;
        $this->packs = [];

        if (!is_dir($base)) {
            return;
        }

        $active = $this->get_active_slug();

        foreach (scandir($base) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $dir = $base . '/' . $entry;
            if (!is_dir($dir)) {
                continue;
            }
            $manifest_file = $dir . '/manifest.json';
            if (!file_exists($manifest_file)) {
                continue;
            }
            $json = json_decode((string) file_get_contents($manifest_file), true);
            if (!is_array($json)) {
                continue;
            }
            $pack = Frontend_Pack::from_manifest(
                $json,
                $entry,
                $dir,
                in_array($entry, self::BUILTIN, true)
            );
            $pack->active = ($entry === $active);
            $this->packs[$entry] = $pack;
        }

        ksort($this->packs);
    }

    public function refresh(?string $base_path = null): void {
        $this->scan($base_path);
    }

    public function get(string $slug): ?Frontend_Pack {
        $this->ensure_scanned();
        return $this->packs[$slug] ?? null;
    }

    public function get_all(): array {
        $this->ensure_scanned();
        return $this->packs;
    }

    public function has(string $slug): bool {
        $this->ensure_scanned();
        return isset($this->packs[$slug]);
    }

    public function count(): int {
        $this->ensure_scanned();
        return count($this->packs);
    }

    public function get_active_slug(): string {
        $active = get_option('phantom_template_pack', 'default');
        return is_string($active) ? $active : 'default';
    }

    public function get_active(): ?Frontend_Pack {
        return $this->get($this->get_active_slug());
    }

    public function get_display_names(): array {
        $this->ensure_scanned();
        $names = [];
        foreach ($this->packs as $slug => $pack) {
            $names[$slug] = $pack->name;
        }
        return $names;
    }

    public function get_pack_list(): array {
        $this->ensure_scanned();
        $list = [];
        foreach ($this->packs as $slug => $pack) {
            $list[$slug] = $pack->to_array();
        }
        return $list;
    }

    private function ensure_scanned(): void {
        if (null === $this->scanned_base) {
            $this->scan();
        }
    }

    public function validate_slug(string $slug): ?string {
        return ('' === $slug || !preg_match('/^[a-z0-9-]{2,32}$/', $slug)) ? 'invalid_slug' : null;
    }

    public function install_zip(string $zip_path, ?string $base_path = null): Frontend_Pack|\WP_Error {
        $base = $base_path ?? $this->default_base_path();

        if (!is_file($zip_path)) {
            return new \WP_Error('zip_failed', 'Zip file not found.');
        }
        if (filesize($zip_path) > self::MAX_ZIP_SIZE) {
            return new \WP_Error('zip_invalid', 'Zip file exceeds the 20 MB limit.');
        }
        $magic = @file_get_contents($zip_path, false, null, 0, 2);
        if ('PK' !== $magic) {
            return new \WP_Error('zip_invalid', 'File is not a valid zip archive.');
        }
        if (!class_exists('ZipArchive')) {
            return new \WP_Error('zip_failed', 'ZipArchive extension is not available.');
        }

        $zip = new \ZipArchive();
        if (true !== $zip->open($zip_path)) {
            return new \WP_Error('zip_failed', 'Unable to open zip archive.');
        }

        $manifest_entry = null;
        $top_dir = null;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string) $zip->getNameIndex($i);
            if (!$this->is_safe_zip_entry($name)) {
                $zip->close();
                return new \WP_Error('zip_invalid', 'Zip contains unsafe path entries.');
            }
            if ('manifest.json' === $name) {
                $manifest_entry = $name;
            } elseif (str_ends_with($name, '/manifest.json')) {
                $dir_part = substr($name, 0, -strlen('/manifest.json'));
                if (!str_contains($dir_part, '/')) {
                    $manifest_entry = $name;
                    $top_dir = $dir_part;
                }
            }
        }

        if (null === $manifest_entry) {
            $zip->close();
            return new \WP_Error('manifest_missing', 'No manifest.json found in zip.');
        }

        $manifest_raw = $zip->getFromName($manifest_entry);
        if (false === $manifest_raw) {
            $zip->close();
            return new \WP_Error('manifest_missing', 'Unable to read manifest.json.');
        }
        $manifest = json_decode($manifest_raw, true);
        if (!is_array($manifest)) {
            $zip->close();
            return new \WP_Error('manifest_invalid', 'manifest.json is not valid JSON.');
        }

        $slug = is_string($manifest['slug'] ?? null) ? $manifest['slug'] : ($top_dir ?? '');
        $slug = strtolower(trim($slug));
        if (null !== $this->validate_slug($slug)) {
            $zip->close();
            return new \WP_Error('invalid_slug', 'Pack slug must be 2-32 lowercase letters, digits or hyphens.');
        }

        $this->scan($base);
        if ($this->has($slug)) {
            $zip->close();
            return new \WP_Error('pack_exists', sprintf('Pack "%s" is already installed.', $slug));
        }
        if (!is_writable($base)) {
            $zip->close();
            return new \WP_Error('io_error', 'Packs directory is not writable.');
        }

        $tmp_dir = sys_get_temp_dir() . '/phantom-pack-' . uniqid('', true);
        if (!@mkdir($tmp_dir, 0777, true) && !is_dir($tmp_dir)) {
            $zip->close();
            return new \WP_Error('io_error', 'Unable to create temporary directory.');
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string) $zip->getNameIndex($i);
            if (str_ends_with($name, '/')) {
                continue;
            }
            $rel = $name;
            if (null !== $top_dir) {
                if (str_starts_with($name, $top_dir . '/')) {
                    $rel = substr($name, strlen($top_dir) + 1);
                } else {
                    continue;
                }
            }
            $target = $tmp_dir . '/' . $rel;
            $parent = dirname($target);
            if (!is_dir($parent) && !@mkdir($parent, 0777, true)) {
                $zip->close();
                $this->remove_tree($tmp_dir);
                return new \WP_Error('io_error', 'Unable to create extraction directory.');
            }
            $content = $zip->getFromIndex($i);
            if (false === $content || false === @file_put_contents($target, $content)) {
                $zip->close();
                $this->remove_tree($tmp_dir);
                return new \WP_Error('io_error', 'Unable to extract zip entry.');
            }
        }
        $zip->close();

        $dest = $base . '/' . $slug;
        if (!$this->copy_tree($tmp_dir, $dest)) {
            $this->remove_tree($tmp_dir);
            return new \WP_Error('io_error', 'Unable to copy pack into packs directory.');
        }
        $this->remove_tree($tmp_dir);

        $this->refresh($base);
        $this->flush_css_cache();
        return $this->get($slug);
    }

    public function install_from_upload(array $file, ?string $base_path = null): Frontend_Pack|\WP_Error {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return new \WP_Error('upload_error', 'File upload failed or no file provided.');
        }
        $tmp = $file['tmp_name'] ?? '';
        if ('' === $tmp || !is_file($tmp)) {
            return new \WP_Error('upload_error', 'Uploaded file is missing.');
        }
        return $this->install_zip($tmp, $base_path);
    }

    public function uninstall(string $slug, bool $force = false, ?string $base_path = null): true|\WP_Error {
        $base = $base_path ?? $this->default_base_path();
        $this->scan($base);

        $pack = $this->get($slug);
        if (null === $pack) {
            return new \WP_Error('pack_missing', sprintf('Pack "%s" is not installed.', $slug));
        }
        if (!$force && $slug === $this->get_active_slug()) {
            return new \WP_Error('pack_active', 'Cannot uninstall the active pack.');
        }
        if (!$force && $pack->builtin) {
            return new \WP_Error('builtin', 'Cannot uninstall a builtin pack.');
        }
        if (!$this->remove_tree($pack->path)) {
            return new \WP_Error('io_error', 'Unable to remove pack directory.');
        }

        $this->refresh($base);
        if ($slug === $this->get_active_slug()) {
            update_option('phantom_template_pack', 'default');
        }
        return true;
    }

    public function activate(string $slug, ?string $base_path = null): true|\WP_Error {
        $base = $base_path ?? $this->default_base_path();
        $this->scan($base);

        if (!$this->has($slug)) {
            return new \WP_Error('pack_missing', sprintf('Pack "%s" is not installed.', $slug));
        }

        update_option('phantom_template_pack', $slug);
        if (function_exists('flush_rewrite_rules')) {
            flush_rewrite_rules();
        }
        $this->apply_pack_settings($slug);
        $this->flush_css_cache();
        return true;
    }

    public function apply_pack_settings(string $slug): int {
        $pack = $this->get($slug);
        if (null === $pack || empty($pack->settings)) {
            return 0;
        }

        $applied = 0;
        foreach ($pack->settings as $key => $value) {
            $saved = false;
            if (class_exists(\PhantomCore\Design\TokenRegistry::class) && \PhantomCore\Design\TokenRegistry::get_instance()->has($key)) {
                $resolver = new \PhantomCore\Design\TokenResolver();
                $saved = $resolver->save($key, $value);
            } elseif (class_exists(\PhantomCore\Settings_Registry::class) && \PhantomCore\Settings_Registry::get_instance()->has($key)) {
                $saved = \PhantomCore\Settings_Registry::get_instance()->set($key, $value);
            }
            if (!$saved) {
                $saved = update_option('phantom_' . $key, $value, false);
            }
            if ($saved) {
                $applied++;
            }
        }
        return $applied;
    }

    private function is_safe_zip_entry(string $name): bool {
        if ('' === $name || '.' === $name || str_starts_with($name, './')) {
            return false;
        }
        if (str_contains($name, '..') || str_starts_with($name, '/') || str_starts_with($name, '\\')) {
            return false;
        }
        return !preg_match('/^[a-zA-Z]:/', $name);
    }

    private function copy_tree(string $src, string $dest): bool {
        if (!is_dir($src)) {
            return false;
        }
        if (!@mkdir($dest, 0777, true) && !is_dir($dest)) {
            return false;
        }
        foreach (scandir($src) as $entry) {
            if ('.' === $entry || '..' === $entry) {
                continue;
            }
            $s = $src . '/' . $entry;
            $d = $dest . '/' . $entry;
            if (is_dir($s)) {
                if (!$this->copy_tree($s, $d)) {
                    return false;
                }
            } elseif (!@copy($s, $d)) {
                return false;
            }
        }
        return true;
    }

    private function remove_tree(string $dir): bool {
        if (!is_dir($dir)) {
            return true;
        }
        foreach (scandir($dir) as $entry) {
            if ('.' === $entry || '..' === $entry) {
                continue;
            }
            $path = $dir . '/' . $entry;
            if (is_dir($path)) {
                if (!$this->remove_tree($path)) {
                    return false;
                }
            } elseif (!@unlink($path)) {
                return false;
            }
        }
        return @rmdir($dir);
    }

    private function flush_css_cache(): void {
        if (class_exists('\Phantom_Custom_CSS') && method_exists('\Phantom_Custom_CSS', 'flush_cache') && function_exists('wp_upload_dir')) {
            \Phantom_Custom_CSS::flush_cache();
        }
        if (function_exists('delete_transient')) {
            delete_transient('phantom_page_data_v2');
        }
    }
}
