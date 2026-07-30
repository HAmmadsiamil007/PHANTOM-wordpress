<?php
declare(strict_types=1);

namespace PhantomCore\Design;

defined('ABSPATH') || exit;

/**
 * ResolvedValue — value object returned by ThemeStateEngine::resolve().
 *
 * Carries the final resolved value plus metadata about how it was resolved.
 *
 * @package PhantomCore\Design
 */
class ResolvedValue {

    /** The final resolved value */
    public mixed $value;

    /** Source of the value: 'developer' | 'preview' | 'user' | 'demo' | 'preset' | 'default' */
    public string $source;

    /** Device context ('desktop' | 'tablet' | 'mobile') */
    public string $device;

    /** Whether dark mode was applied */
    public bool $dark_mode;

    /** Original value before any cascade transformations */
    public mixed $original;

    /**
     * @param mixed       $value   Final resolved value.
     * @param string      $source  Source identifier.
     * @param array       $context Context with device, dark_mode, etc.
     * @param mixed|null  $original Original value (defaults to $value).
     */
    public function __construct(
        mixed $value,
        string $source = 'default',
        array $context = [],
        mixed $original = null
    ) {
        $this->value    = $value;
        $this->source   = in_array($source, [
            'developer', 'preview', 'user', 'demo', 'preset', 'default',
        ], true) ? $source : 'default';
        $this->device   = $context['device'] ?? 'desktop';
        $this->dark_mode = !empty($context['dark_mode']);
        $this->original  = $original ?? $value;
    }

    /**
     * Whether the value came from a temporary/preview source (not persisted).
     */
    public function is_preview(): bool {
        return 'preview' === $this->source;
    }

    /**
     * Whether the value is a persisted, published setting.
     */
    public function is_persisted(): bool {
        return in_array($this->source, ['user', 'demo', 'preset', 'default'], true);
    }

    /**
     * Cast the value to a string.
     */
    public function __toString(): string {
        if (null === $this->value) {
            return '';
        }
        if (is_bool($this->value)) {
            return $this->value ? 'true' : 'false';
        }
        if (is_array($this->value)) {
            return wp_json_encode($this->value);
        }
        return (string) $this->value;
    }

    /**
     * Export as an array for REST API responses.
     */
    public function to_array(): array {
        return [
            'value'    => $this->value,
            'source'   => $this->source,
            'device'   => $this->device,
            'darkMode' => $this->dark_mode,
        ];
    }
}
