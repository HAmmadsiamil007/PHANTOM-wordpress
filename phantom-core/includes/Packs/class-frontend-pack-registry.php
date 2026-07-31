<?php
declare(strict_types=1);

namespace PhantomCore\Packs;

defined('ABSPATH') || exit;

class Frontend_Pack_Registry {
    private const BUILTIN = ['dark', 'minimal', 'bold'];

    private static ?self $instance = null;

    private array $packs = [];

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
        return $this->packs[$slug] ?? null;
    }

    public function get_all(): array {
        return $this->packs;
    }

    public function has(string $slug): bool {
        return isset($this->packs[$slug]);
    }

    public function count(): int {
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
        $names = [];
        foreach ($this->packs as $slug => $pack) {
            $names[$slug] = $pack->name;
        }
        return $names;
    }

    public function get_pack_list(): array {
        $list = [];
        foreach ($this->packs as $slug => $pack) {
            $list[$slug] = $pack->to_array();
        }
        return $list;
    }
}
