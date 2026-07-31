<?php
declare(strict_types=1);

namespace PhantomCore\Asset;

defined('ABSPATH') || exit;

class CSS_Cache_Manager {
    private static ?self $instance = null;
    private Manifest $manifest;
    private const MAX_BUILDS = 10;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->manifest = Manifest::get_instance();
    }

    public function get_active_css(): array {
        $manifest = $this->manifest->get_active();
        return $manifest['css'] ?? [];
    }

    public function get_active_version(): string {
        $manifest = $this->manifest->get_active();
        return $manifest['version'] ?? '';
    }

    public function cleanup(string $current_version): void {
        $css_dir = get_css_dir();
        $files = glob(trailingslashit($css_dir) . '*.css');

        if (!$files) {
            return;
        }

        $versions = [];
        foreach ($files as $filepath) {
            $filename = basename($filepath);
            if (preg_match('/-([a-f0-9]+)\.css$/', $filename, $m)) {
                $hash = $m[1];
                if (!isset($versions[$hash])) {
                    $versions[$hash] = [];
                }
                $versions[$hash][] = $filepath;
            }
        }

        $hashes = array_keys($versions);
        $hashes = array_filter($hashes, function ($h) use ($current_version) {
            return $h !== $current_version;
        });

        usort($hashes, function ($a, $b) use ($versions) {
            return filemtime($versions[$b][0]) - filemtime($versions[$a][0]);
        });

        $to_remove = array_slice($hashes, self::MAX_BUILDS - 1);
        foreach ($to_remove as $hash) {
            foreach ($versions[$hash] as $filepath) {
                if (file_exists($filepath)) {
                    unlink($filepath);
                }
            }
        }
    }

    public function enqueue(): void {
        if (wp_doing_ajax() || is_admin()) {
            return;
        }

        $active_css = $this->get_active_css();
        $version = $this->get_active_version();

        if (empty($active_css) || empty($version)) {
            return;
        }

        foreach ($active_css as $section => $info) {
            if (isset($info['url']) && !empty($info['file'])) {
                $filepath = trailingslashit(get_css_dir()) . $info['file'];
                if (file_exists($filepath)) {
                    wp_enqueue_style(
                        "phantom-{$section}",
                        $info['url'],
                        [],
                        $version
                    );
                }
            }
        }
    }
}
