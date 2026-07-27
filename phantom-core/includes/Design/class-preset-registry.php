<?php
declare(strict_types=1);

namespace PhantomCore\Design;

use PhantomCore\Design\Providers\PresetProviderInterface;

defined('ABSPATH') || exit;

class PresetRegistry {
    private static ?self $instance = null;
    private array $providers = [];
    private ?array $mergedCache = null;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function register_provider(PresetProviderInterface $provider): void {
        $this->providers[] = $provider;
        $this->mergedCache = null;
    }

    public function get_providers(): array {
        return $this->providers;
    }

    public function get_all(): array {
        $this->ensureMerged();
        return $this->mergedCache;
    }

    public function get(string $id): ?Preset {
        // User presets first, then demo, then core
        foreach ($this->getProvidersByPriority() as $provider) {
            if ($provider->exists($id)) {
                return $provider->get_preset($id);
            }
        }
        return null;
    }

    public function has(string $id): bool {
        return null !== $this->get($id);
    }

    public function get_by_source(string $source): array {
        $result = [];
        foreach ($this->get_all() as $id => $preset) {
            if ($preset->source === $source) {
                $result[$id] = $preset;
            }
        }
        return $result;
    }

    public function count(): int {
        return count($this->get_all());
    }

    private function ensureMerged(): void {
        if (null !== $this->mergedCache) return;
        $this->mergedCache = [];
        foreach ($this->getProvidersByPriority() as $provider) {
            foreach ($provider->get_presets() as $id => $preset) {
                $this->mergedCache[$id] = $preset;
            }
        }
    }

    private function getProvidersByPriority(): array {
        $ordered = [];
        foreach ($this->providers as $p) {
            $source = $p->source();
            if ($source === 'user') {
                array_unshift($ordered, $p);
            } elseif ($source === 'demo') {
                array_splice($ordered, count($ordered), 0, [$p]);
            } else {
                $ordered[] = $p;
            }
        }
        return $ordered;
    }

    public function invalidateCache(): void {
        $this->mergedCache = null;
    }
}
