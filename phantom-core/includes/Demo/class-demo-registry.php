<?php
declare(strict_types=1);

namespace PhantomCore\Demo;

defined('ABSPATH') || exit;

class Demo_Registry {

    private static ?self $instance = null;

    public static function get_instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private ?array $cache = null;

    public function get_all(): array {
        if ($this->cache === null) {
            $this->cache = $this->scan();
        }
        return $this->cache;
    }

    public function get(string $slug): ?Demo_Contract {
        $demos = $this->get_all();
        return $demos[$slug] ?? null;
    }

    public function get_active(): Demo_Contract {
        $active_slug = get_option('phantom_active_demo', 'kids');
        $demo = $this->get($active_slug);
        if ($demo !== null) {
            return $demo;
        }
        return $this->create_virtual_demo($active_slug);
    }

    public function has(string $slug): bool {
        return $this->get($slug) !== null;
    }

    public function refresh(): void {
        $this->cache = null;
    }

    public function count(): int {
        return count($this->get_all());
    }

    private function scan(): array {
        $demos = [];
        $base = PHANTOM_CORE_PATH . 'frontend/templates/';

        if (!is_dir($base)) {
            return $demos;
        }

        $entries = scandir($base);
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            if (!is_dir($base . $entry)) {
                continue;
            }
            $manifest = $base . $entry . '/demo.json';
            if (!file_exists($manifest)) {
                continue;
            }
            $content = file_get_contents($manifest);
            if ($content === false) {
                continue;
            }
            $data = json_decode($content, true);
            if (!is_array($data)) {
                continue;
            }
            $demo = Demo_Contract::from_array($data, $entry);
            $demos[$entry] = $demo;
        }

        return $demos;
    }

    private function create_virtual_demo(string $slug): Demo_Contract {
        return new Demo_Contract(
            name: ucwords(str_replace('-', ' ', $slug)) . ' (Default)',
            slug: $slug,
            version: '1.0.0',
            description: 'Default template pack — no custom demo files.',
            is_compatible: true
        );
    }
}
