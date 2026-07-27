<?php
declare(strict_types=1);

namespace PhantomCore\Feature;

defined('ABSPATH') || exit;

class Feature {
    public readonly string $id;
    public readonly string $category;
    public readonly string $type;
    public readonly string $label;
    public readonly string $description;
    public readonly bool $default;

    public function __construct(
        string $id,
        string $category = 'general',
        string $type = 'ast-toggle',
        string $label = '',
        string $description = '',
        bool $default = true
    ) {
        $this->id = $id;
        $this->category = $category;
        $this->type = $type;
        $this->label = $label ?: ucwords(str_replace('_', ' ', $id));
        $this->description = $description;
        $this->default = $default;
    }

    /**
     * Check if this feature is enabled.
     * Reads from database if the option exists, otherwise returns the default.
     */
    public function enabled(): bool {
        $val = get_option('phantom_feature_' . $this->id, null);
        if (null !== $val) {
            return (bool) $val;
        }
        return $this->default;
    }

    /**
     * Check if this feature has been explicitly overridden in the database.
     */
    public function is_overridden(): bool {
        return null !== get_option('phantom_feature_' . $this->id, null);
    }

    /**
     * Persist the enabled/disabled state to the database.
     */
    public function set_enabled(bool $enabled): bool {
        return update_option('phantom_feature_' . $this->id, $enabled ? 1 : 0, false);
    }

    /**
     * Delete the override, reverting to the default value.
     */
    public function reset(): bool {
        return delete_option('phantom_feature_' . $this->id);
    }

    public function to_array(): array {
        return [
            'id' => $this->id,
            'category' => $this->category,
            'type' => $this->type,
            'label' => $this->label,
            'description' => $this->description,
            'default' => $this->default,
            'enabled' => $this->enabled(),
            'overridden' => $this->is_overridden(),
        ];
    }

    public static function from_array(array $data): self {
        return new self(
            id: $data['id'] ?? '',
            category: $data['category'] ?? 'general',
            type: $data['type'] ?? 'ast-toggle',
            label: $data['label'] ?? '',
            description: $data['description'] ?? '',
            default: (bool) ($data['default'] ?? true)
        );
    }
}
