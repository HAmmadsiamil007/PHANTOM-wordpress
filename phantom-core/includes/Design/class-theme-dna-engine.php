<?php
declare(strict_types=1);

namespace PhantomCore\Design;

defined('ABSPATH') || exit;

class ThemeDNAEngine {
    private static ?self $instance = null;
    private const OPTION_KEY = 'phantom_theme_dna';

    private array $dimensions = [
        'design_style' => ['classic', 'modern', 'minimal', 'luxury'],
        'motion_style' => ['subtle', 'dynamic', 'elegant', 'smooth', 'playful'],
        'shape_style' => ['sharp', 'rounded', 'soft', 'mixed'],
        'typography_style' => ['sans', 'serif', 'mixed', 'display'],
        'elevation_style' => ['flat', 'soft', 'floating', 'glass', 'layered'],
        'color_style' => ['neutral', 'vibrant', 'monochrome', 'pastel', 'bold'],
    ];

    private array $defaults = [
        'design_style' => 'classic',
        'motion_style' => 'subtle',
        'shape_style' => 'sharp',
        'typography_style' => 'sans',
        'elevation_style' => 'soft',
        'color_style' => 'neutral',
    ];

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getDimensions(): array {
        return $this->dimensions;
    }

    public function getCurrent(): array {
        $stored = get_option(self::OPTION_KEY, []);
        if (!is_array($stored)) $stored = [];
        return array_merge($this->defaults, $stored);
    }

    public function set(string $dimension, string $value): bool {
        if (!isset($this->dimensions[$dimension])) return false;
        if (!in_array($value, $this->dimensions[$dimension], true)) return false;

        $current = $this->getCurrent();
        $current[$dimension] = $value;
        return update_option(self::OPTION_KEY, $current, false);
    }

    public function applyOverrides(array $overrides): bool {
        $current = $this->getCurrent();
        foreach ($overrides as $dimension => $value) {
            if (!isset($this->dimensions[$dimension])) continue;
            if (!in_array($value, $this->dimensions[$dimension], true)) continue;
            $current[$dimension] = $value;
        }
        return update_option(self::OPTION_KEY, $current, false);
    }

    public function reset(): bool {
        return delete_option(self::OPTION_KEY);
    }
}
