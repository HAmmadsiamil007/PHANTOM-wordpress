<?php
declare(strict_types=1);

namespace PhantomCore\Design\Providers;

use PhantomCore\Design\Preset;

defined('ABSPATH') || exit;

class UserProvider implements PresetProviderInterface {
    private array $presets = [];
    private bool $loaded = false;
    private const OPTION_KEY = 'phantom_user_presets';

    public function source(): string {
        return 'user';
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

    public function save(Preset $preset): bool {
        $presets = $this->getAllRaw();
        $presets[$preset->id] = $preset->to_array();
        $updated = update_option(self::OPTION_KEY, $presets);
        if ($updated) {
            $this->presets[$preset->id] = $preset;
        }
        return $updated;
    }

    public function delete(string $id): bool {
        $presets = $this->getAllRaw();
        if (!isset($presets[$id])) return false;
        unset($presets[$id]);
        $updated = update_option(self::OPTION_KEY, $presets);
        if ($updated) {
            unset($this->presets[$id]);
        }
        return $updated;
    }

    private function load(): void {
        if ($this->loaded) return;
        $raw = $this->getAllRaw();
        foreach ($raw as $id => $data) {
            $data['source'] = 'user';
            $this->presets[$id] = Preset::from_array($data);
        }
        $this->loaded = true;
    }

    private function getAllRaw(): array {
        $raw = get_option(self::OPTION_KEY, []);
        return is_array($raw) ? $raw : [];
    }
}
