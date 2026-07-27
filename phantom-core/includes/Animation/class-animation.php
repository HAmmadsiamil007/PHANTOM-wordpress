<?php
declare(strict_types=1);

namespace PhantomCore\Animation;

defined('ABSPATH') || exit;

class Animation {
    public readonly string $id;
    public readonly string $label;
    public readonly string $type;
    public readonly string $category;
    public readonly string $target;
    public array $config;
    public readonly array $defaults;
    public readonly bool $enabled_by_default;

    public function __construct(
        string $id,
        string $label,
        string $type,
        string $category,
        string $target,
        array $defaults = [],
        bool $enabled_by_default = true
    ) {
        $this->id = $id;
        $this->label = $label;
        $this->type = $type;
        $this->category = $category;
        $this->target = $target;
        $this->defaults = $defaults;
        $this->enabled_by_default = $enabled_by_default;
        $this->config = $defaults;
    }

    public function merge_config(array $overrides): void {
        $this->config = array_merge($this->defaults, $overrides);
    }

    public function to_array(): array {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'type' => $this->type,
            'category' => $this->category,
            'target' => $this->target,
            'config' => $this->config,
            'defaults' => $this->defaults,
            'enabled_by_default' => $this->enabled_by_default,
        ];
    }

    public static function from_array(array $data): self {
        return new self(
            $data['id'],
            $data['label'],
            $data['type'],
            $data['category'],
            $data['target'],
            $data['defaults'] ?? [],
            $data['enabled_by_default'] ?? true
        );
    }
}
