<?php
declare(strict_types=1);

namespace PhantomCore\Design\Providers;

use PhantomCore\Design\Preset;

defined('ABSPATH') || exit;

class CoreProvider implements PresetProviderInterface {
    private array $presets = [];
    private bool $loaded = false;

    public function source(): string {
        return 'core';
    }

    public function get_presets(): array {
        $this->load();
        return $this->presets;
    }

    public function get_preset(string $id): ?Preset {
        $this->load();
        return $this->presets[$id] ?? null;
    }

    public function exists(string $id): bool {
        $this->load();
        return isset($this->presets[$id]);
    }

    private function load(): void {
        if ($this->loaded) return;
        $file = PHANTOM_CORE_PATH . 'includes/Design/data/presets.php';
        if (!file_exists($file)) return;
        $rawPresets = require $file;
        foreach ($rawPresets as $id => $data) {
            $this->presets[$id] = Preset::from_array($data);
        }
        $this->loaded = true;
    }
}
