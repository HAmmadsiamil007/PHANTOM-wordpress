<?php
declare(strict_types=1);

namespace PhantomCore\Design;

defined('ABSPATH') || exit;

class TokenResolver {
    private TokenRegistry $registry;
    private array $cache = [];
    private const MAX_INHERITANCE_DEPTH = 5;

    public function __construct() {
        $this->registry = TokenRegistry::get_instance();
    }

    public function resolve(string $name): mixed {
        if (isset($this->cache[$name])) {
            return $this->cache[$name];
        }

        $def = $this->registry->get($name);
        if (null === $def) {
            return null;
        }

        $optionKey = $def['option_key'];
        $value = get_option($optionKey, '__not_set__');

        if ('__not_set__' === $value) {
            $value = $def['default'];
        }

        $value = $this->resolveInheritance($value, 0);
        $value = $this->castValue($value, $def['type'] ?? 'string');

        $this->cache[$name] = $value;
        return $value;
    }

    public function resolveAll(?array $names = null): array {
        $tokens = $names ? array_intersect_key($this->registry->get_all(), array_flip($names)) : $this->registry->get_all();
        $result = [];
        foreach ($tokens as $name => $def) {
            $result[$name] = $this->resolve($name);
        }
        return $result;
    }

    public function resolveCategory(string $category): array {
        $tokens = $this->registry->get_by_category($category);
        $result = [];
        foreach ($tokens as $name => $def) {
            $result[$name] = $this->resolve($name);
        }
        return $result;
    }

    private function resolveInheritance(mixed $value, int $depth): mixed {
        if ($depth > self::MAX_INHERITANCE_DEPTH) {
            return $value;
        }
        if (!is_string($value)) {
            return $value;
        }
        if (preg_match('/^\{([a-z0-9._-]+)\}$/', $value, $matches)) {
            $refName = $matches[1];
            if (!$this->registry->has($refName)) {
                return $value;
            }
            $refValue = get_option($this->registry->get_option_key($refName), '__not_set__');
            if ('__not_set__' === $refValue) {
                $refValue = $this->registry->get_default($refName);
            }
            return $this->resolveInheritance($refValue, $depth + 1);
        }
        return $value;
    }

    private function castValue(mixed $value, string $type): mixed {
        if (!is_string($value)) {
            return $value;
        }
        return match ($type) {
            'number' => is_numeric($value) ? $value : (string) $value,
            default => $value,
        };
    }

    public function invalidateCache(): void {
        $this->cache = [];
    }
}
