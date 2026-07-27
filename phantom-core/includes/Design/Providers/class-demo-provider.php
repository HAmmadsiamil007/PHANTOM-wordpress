<?php
declare(strict_types=1);

namespace PhantomCore\Design\Providers;

use PhantomCore\Design\Preset;

defined('ABSPATH') || exit;

class DemoProvider implements PresetProviderInterface {
    private array $presets = [];
    private bool $loaded = false;

    public function source(): string {
        return 'demo';
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
        $demoDir = PHANTOM_CORE_PATH . 'frontend/templates/';
        if (!is_dir($demoDir)) { $this->loaded = true; return; }
        $items = scandir($demoDir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $presetDir = $demoDir . $item . '/presets/';
            if (!is_dir($presetDir)) continue;
            $files = scandir($presetDir);
            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) !== 'json') continue;
                $json = file_get_contents($presetDir . $file);
                $data = json_decode($json, true);
                if (!is_array($data) || !isset($data['id'])) continue;
                $data['source'] = 'demo';
                $preset = Preset::from_array($data);
                $this->presets[$preset->id] = $preset;
            }
        }
        $this->loaded = true;
    }
}
