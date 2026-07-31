<?php
declare(strict_types=1);

namespace PhantomCore\Asset;

defined('ABSPATH') || exit;

class Manifest {
    private static ?self $instance = null;
    private const MANIFEST_FILE = 'manifest.json';

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function read(): array {
        $file = $this->get_path();
        if (!file_exists($file)) {
            return $this->get_default();
        }
        $contents = file_get_contents($file);
        if (false === $contents) {
            return $this->get_default();
        }
        $data = json_decode($contents, true);
        return is_array($data) ? $data : $this->get_default();
    }

    public function write(array $manifest): bool {
        ensure_dirs();
        $file = $this->get_path();
        return false !== file_put_contents($file, wp_json_encode($manifest, JSON_PRETTY_PRINT));
    }

    public function get_active(): array {
        return $this->read();
    }

    public function update_css_build(string $version, array $sections, string $profile): bool {
        $manifest = $this->read();
        $manifest['version'] = $version;
        $manifest['build'] = ($manifest['build'] ?? 0) + 1;
        $manifest['date'] = current_time('c');
        $manifest['profile'] = $profile;

        $css_url = get_css_url();

        foreach ($sections as $section => $content) {
            $filename = "{$section}-{$version}.css";
            $manifest['css'][$section] = [
                'file' => $filename,
                'url'  => trailingslashit($css_url) . $filename,
                'size' => strlen($content),
            ];
        }

        return $this->write($manifest);
    }

    public function get_default(): array {
        return [
            'version' => '',
            'build'   => 0,
            'date'    => '',
            'profile' => 'development',
            'css'     => [],
            'js'      => [],
            'fonts'   => [],
            'images'  => [],
        ];
    }

    public function get_path(): string {
        return get_css_dir() . '/' . self::MANIFEST_FILE;
    }
}
