<?php
declare(strict_types=1);

namespace PhantomCore\Feature;

defined('ABSPATH') || exit;

class Feature_Registry {
    private static ?self $instance = null;
    private array $features = [];
    private bool $loaded = false;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function load(): void {
        if ($this->loaded) return;

        $definitions = require PHANTOM_CORE_PATH . 'includes/Feature/data/features.php';
        foreach ($definitions as $id => $data) {
            $this->features[$id] = Feature::from_array($data);
        }

        $this->loaded = true;
    }

    public function register(Feature $feature): void {
        $this->load();
        $this->features[$feature->id] = $feature;
    }

    public function register_from_array(array $data): void {
        $this->register(Feature::from_array($data));
    }

    public function get(string $id): ?Feature {
        $this->load();
        return $this->features[$id] ?? null;
    }

    public function has(string $id): bool {
        $this->load();
        return isset($this->features[$id]);
    }

    public function enabled(string $id): bool {
        $feature = $this->get($id);
        return null !== $feature && $feature->enabled();
    }

    public function set_enabled(string $id, bool $enabled): bool {
        $feature = $this->get($id);
        if (null === $feature) return false;
        return $feature->set_enabled($enabled);
    }

    public function reset(string $id): bool {
        $feature = $this->get($id);
        if (null === $feature) return false;
        return $feature->reset();
    }

    public function get_all(): array {
        $this->load();
        return $this->features;
    }

    public function get_by_category(string $category): array {
        $this->load();
        return array_filter($this->features, fn(Feature $f) => $f->category === $category);
    }

    public function get_categories(): array {
        $this->load();
        $cats = [];
        foreach ($this->features as $feature) {
            $cats[$feature->category] = true;
        }
        return array_keys($cats);
    }

    public function count(): int {
        $this->load();
        return count($this->features);
    }

    public function flush_cache(): void {
        $this->features = [];
        $this->loaded = false;
    }

    public function deregister(string $id): bool {
        $this->load();
        if (!isset($this->features[$id])) return false;
        unset($this->features[$id]);
        return true;
    }
}
