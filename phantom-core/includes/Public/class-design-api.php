<?php declare(strict_types=1);

namespace PhantomCore\Public;

use PhantomCore\Design\DesignSystemManager;

defined('ABSPATH') || exit;

class Design_API {
    private static ?Design_API $instance = null;

    private ?DesignSystemManager $dsm = null;

    private function __construct() {}

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function manager(): DesignSystemManager {
        if (null === $this->dsm) {
            $this->dsm = DesignSystemManager::get_instance();
        }
        return $this->dsm;
    }

    public function get_token(string $name): ?string {
        $value = $this->manager()->token($name);
        $result = is_string($value) ? $value : null;
        return apply_filters('phantom_core/design/get_token', $result, $name);
    }

    public function get_tokens(?string $category = null): array {
        $categories = null !== $category ? [$category] : null;
        $raw = $this->manager()->tokens($categories);
        if (null !== $category && isset($raw[$category])) {
            $result = $raw[$category];
        } elseif (null === $category) {
            $result = $raw;
        } else {
            $result = [];
        }
        return apply_filters('phantom_core/design/get_tokens', $result, $category);
    }

    public function get_css_var(string $name): ?string {
        $result = $this->manager()->cssVar($name);
        return apply_filters('phantom_core/design/get_css_var', $result, $name);
    }

    public function get_all_css_vars(): array {
        $result = $this->manager()->allCssVars();
        return apply_filters('phantom_core/design/get_all_css_vars', $result);
    }

    public function get_current_preset(): ?string {
        $preset = $this->manager()->currentPreset();
        $result = null !== $preset ? ($preset['id'] ?? $preset['slug'] ?? null) : null;
        return apply_filters('phantom_core/design/get_current_preset', $result);
    }

    public function get_available_presets(): array {
        $presets = $this->manager()->availablePresets();
        $result = array_map(function ($p) {
            return is_array($p) ? $p : (method_exists($p, 'to_array') ? $p->to_array() : []);
        }, $presets);
        return apply_filters('phantom_core/design/get_available_presets', $result);
    }

    public function apply_preset(string $slug): bool {
        $result = $this->manager()->applyPreset($slug);
        return apply_filters('phantom_core/design/apply_preset', $result, $slug);
    }

    public function get_palette(): array {
        $tokens = $this->get_tokens('color');
        $result = [];
        foreach ($tokens as $name => $token) {
            if (is_array($token)) {
                $result[$name] = $token['value'] ?? $token['default'] ?? '';
            }
        }
        return apply_filters('phantom_core/design/get_palette', $result);
    }

    public function get_typography(): array {
        $tokens = $this->get_tokens('typography');
        $result = [];
        foreach ($tokens as $name => $token) {
            if (is_array($token)) {
                $result[$name] = $token['value'] ?? $token['default'] ?? '';
            }
        }
        return apply_filters('phantom_core/design/get_typography', $result);
    }
}
