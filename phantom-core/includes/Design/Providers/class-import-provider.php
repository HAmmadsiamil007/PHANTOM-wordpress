<?php
declare(strict_types=1);

namespace PhantomCore\Design\Providers;

use PhantomCore\Design\Preset;

defined('ABSPATH') || exit;

class ImportProvider implements PresetProviderInterface {
    private array $presets = [];

    public function source(): string {
        return 'import';
    }

    public function addPreset(Preset $preset): void {
        $this->presets[$preset->id] = $preset;
    }

    public function get_presets(): array {
        return $this->presets;
    }

    public function get_preset(string $id): ?Preset {
        return $this->presets[$id] ?? null;
    }

    public function exists(string $id): bool {
        return isset($this->presets[$id]);
    }

    public function clear(): void {
        $this->presets = [];
    }
}
