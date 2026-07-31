<?php
declare(strict_types=1);

namespace PhantomCore\Components;

defined('ABSPATH') || exit;

class Property {
    public string $name;
    public string $label;
    public string $category;
    public string $type;
    public string $token;
    public string $css_var;
    public mixed $default;
    public array $options;
    public bool $responsive;
    public array $states;
    public ?string $unit;
    public ?array $range;

    public function __construct(
        string $name,
        string $label = '',
        string $category = 'colors',
        string $type = 'color',
        string $token = '',
        string $css_var = '',
        mixed $default = null,
        array $options = [],
        bool $responsive = false,
        array $states = ['normal'],
        ?string $unit = null,
        ?array $range = null
    ) {
        $this->name = $name;
        $this->label = $label ?: ucwords(str_replace(['_', '.'], ' ', $name));
        $this->category = $category;
        $this->type = $type;
        $this->token = $token;
        $this->css_var = $css_var;
        $this->default = $default;
        $this->options = $options;
        $this->responsive = $responsive;
        $this->states = $states;
        $this->unit = $unit;
        $this->range = $range;
    }

    public function to_array(): array {
        return [
            'name' => $this->name,
            'label' => $this->label,
            'category' => $this->category,
            'type' => $this->type,
            'token' => $this->token,
            'css_var' => $this->css_var,
            'default' => $this->default,
            'options' => $this->options,
            'responsive' => $this->responsive,
            'states' => $this->states,
            'unit' => $this->unit,
            'range' => $this->range,
        ];
    }

    public static function from_array(array $data): self {
        return new self(
            name: $data['name'] ?? '',
            label: $data['label'] ?? '',
            category: $data['category'] ?? 'colors',
            type: $data['type'] ?? 'color',
            token: $data['token'] ?? '',
            css_var: $data['css_var'] ?? '',
            default: $data['default'] ?? null,
            options: $data['options'] ?? [],
            responsive: (bool)($data['responsive'] ?? false),
            states: $data['states'] ?? ['normal'],
            unit: $data['unit'] ?? null,
            range: $data['range'] ?? null
        );
    }
}

class Property_Registry {
    private static ?self $instance = null;
    private array $properties = [];
    private bool $defaults_registered = false;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function register(Property $property): void {
        $this->properties[$property->name] = $property;
    }

    public function register_defaults(): void {
        if ($this->defaults_registered) {
            return;
        }

        // ─── Color Properties ───
        $this->register(new Property('background', 'Background', 'colors', 'color', '', '', '#000000'));
        $this->register(new Property('text.color', 'Text Color', 'colors', 'color', '', '', '#000000'));
        $this->register(new Property('border.color', 'Border Color', 'colors', 'color', '', '', '#e0e0e0'));
        $this->register(new Property('hover.bg', 'Hover Background', 'colors', 'color', '', '', '', states: ['normal', 'hover']));
        $this->register(new Property('hover.text', 'Hover Text Color', 'colors', 'color', '', '', '', states: ['normal', 'hover']));
        $this->register(new Property('overlay', 'Overlay Color', 'colors', 'color', '', '', 'rgba(0,0,0,0.3)'));
        $this->register(new Property('shadow.color', 'Shadow Color', 'colors', 'color', '', '', 'rgba(0,0,0,0.1)'));

        // ─── Typography Properties ───
        $this->register(new Property('font.family', 'Font Family', 'typography', 'font_picker', '', '', 'Inter'));
        $this->register(new Property('font.size', 'Font Size', 'typography', 'range', '', '', '16', responsive: true, unit: 'px', range: ['min' => 8, 'max' => 120, 'step' => 1]));
        $this->register(new Property('font.weight', 'Font Weight', 'typography', 'select', '', '', '400', options: [
            '100' => 'Thin', '200' => 'Extra Light', '300' => 'Light',
            '400' => 'Regular', '500' => 'Medium', '600' => 'Semi Bold',
            '700' => 'Bold', '800' => 'Extra Bold', '900' => 'Black',
        ]));
        $this->register(new Property('line.height', 'Line Height', 'typography', 'range', '', '', '1.5', unit: '', range: ['min' => 0.5, 'max' => 3, 'step' => 0.1]));
        $this->register(new Property('letter.spacing', 'Letter Spacing', 'typography', 'range', '', '', '0', unit: 'em', range: ['min' => -0.1, 'max' => 0.5, 'step' => 0.01]));
        $this->register(new Property('text.transform', 'Text Transform', 'typography', 'select', '', '', 'none', options: [
            'none' => 'None', 'uppercase' => 'UPPERCASE', 'lowercase' => 'lowercase', 'capitalize' => 'Capitalize',
        ]));
        $this->register(new Property('text.decoration', 'Text Decoration', 'typography', 'select', '', '', 'none', options: [
            'none' => 'None', 'underline' => 'Underline', 'line-through' => 'Line Through',
        ]));

        // ─── Spacing Properties ───
        $this->register(new Property('padding.top', 'Padding Top', 'spacing', 'range', '', '', '0', responsive: true, unit: 'px', range: ['min' => 0, 'max' => 200, 'step' => 1]));
        $this->register(new Property('padding.right', 'Padding Right', 'spacing', 'range', '', '', '0', responsive: true, unit: 'px', range: ['min' => 0, 'max' => 200, 'step' => 1]));
        $this->register(new Property('padding.bottom', 'Padding Bottom', 'spacing', 'range', '', '', '0', responsive: true, unit: 'px', range: ['min' => 0, 'max' => 200, 'step' => 1]));
        $this->register(new Property('padding.left', 'Padding Left', 'spacing', 'range', '', '', '0', responsive: true, unit: 'px', range: ['min' => 0, 'max' => 200, 'step' => 1]));
        $this->register(new Property('margin.top', 'Margin Top', 'spacing', 'range', '', '', '0', responsive: true, unit: 'px', range: ['min' => 0, 'max' => 200, 'step' => 1]));
        $this->register(new Property('margin.bottom', 'Margin Bottom', 'spacing', 'range', '', '', '0', responsive: true, unit: 'px', range: ['min' => 0, 'max' => 200, 'step' => 1]));

        // ─── Layout Properties ───
        $this->register(new Property('container.width', 'Container Width', 'layout', 'range', '', '', '1320', unit: 'px', range: ['min' => 320, 'max' => 1920, 'step' => 10]));
        $this->register(new Property('columns', 'Columns', 'layout', 'select', '', '', '4', options: [
            '1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6',
        ]));
        $this->register(new Property('alignment', 'Alignment', 'layout', 'select', '', '', 'left', options: [
            'left' => 'Left', 'center' => 'Center', 'right' => 'Right',
        ]));
        $this->register(new Property('gap', 'Gap', 'layout', 'range', '', '', '24', unit: 'px', range: ['min' => 0, 'max' => 100, 'step' => 1]));

        // ─── Effects Properties ───
        $this->register(new Property('shadow', 'Box Shadow', 'effects', 'select', '', '', 'none', options: [
            'none' => 'None',
            'sm' => 'Small',
            'md' => 'Medium',
            'lg' => 'Large',
            'xl' => 'Extra Large',
        ]));
        $this->register(new Property('opacity', 'Opacity', 'effects', 'range', '', '', '1', unit: '', range: ['min' => 0, 'max' => 1, 'step' => 0.05]));
        $this->register(new Property('border.radius', 'Border Radius', 'effects', 'range', '', '', '0', unit: 'px', range: ['min' => 0, 'max' => 50, 'step' => 1]));

        $this->defaults_registered = true;
    }

    public function get(string $name): ?Property {
        return $this->properties[$name] ?? null;
    }

    public function has(string $name): bool {
        return isset($this->properties[$name]);
    }

    public function get_all(?string $category = null): array {
        if (null === $category) {
            return $this->properties;
        }
        return array_filter(
            $this->properties,
            fn(Property $p) => $p->category === $category
        );
    }

    public function get_categories(): array {
        $cats = [];
        foreach ($this->properties as $p) {
            $cats[$p->category] = true;
        }
        return array_keys($cats);
    }

    public function get_properties_for_component(string $component_name): array {
        $registry = Component_Registry::get_instance();
        $component = $registry->get($component_name);
        if (null === $component) {
            return [];
        }
        return $component->properties ?? [];
    }

    public function count(): int {
        return count($this->properties);
    }
}
