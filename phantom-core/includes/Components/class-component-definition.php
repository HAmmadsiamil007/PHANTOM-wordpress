<?php
declare(strict_types=1);

namespace PhantomCore\Components;

defined('ABSPATH') || exit;

class ComponentDefinition {
    public readonly string $name;
    public readonly string $label;
    public readonly string $category;
    public readonly string $class_name;
    public readonly array $dependencies;
    public readonly bool $is_abstract;
    public readonly string $icon;

    // ★ Component Metadata
    public readonly string $version;
    public readonly string $author;
    public readonly string $description;
    public readonly array $required_features;
    public readonly array $assets;
    public readonly array $component_settings;

    // ★ Visual Customizer 2.0 Fields
    public readonly string $content_type;
    public readonly ?string $edit_url;
    public readonly array $supports;
    public readonly array $tokens;
    public readonly array $properties;
    public readonly array $style_states;
    public readonly array $instance_defaults;
    public readonly array $default_assets;
    public readonly array $inspector_schema;
    public readonly ?string $adapter;

    public function __construct(
        string $name,
        string $label = '',
        string $category = 'general',
        string $class_name = '',
        array $dependencies = [],
        bool $is_abstract = false,
        string $icon = 'dashicons-layout',

        // ★ Metadata parameters
        string $version = '1.0.0',
        string $author = 'Phantom Core',
        string $description = '',
        array $required_features = [],
        array $assets = [],
        array $component_settings = [],

        // ★ Visual Customizer 2.0 parameters
        string $content_type = 'static',
        ?string $edit_url = null,
        array $supports = [],
        array $tokens = [],
        array $properties = [],
        array $style_states = ['normal'],
        array $instance_defaults = [],
        array $default_assets = [],
        array $inspector_schema = [],
        ?string $adapter = null
    ) {
        $this->name = $name;
        $this->label = $label ?: ucwords(str_replace('_', ' ', $name));
        $this->category = $category;
        $this->class_name = $class_name;
        $this->dependencies = $dependencies;
        $this->is_abstract = $is_abstract;
        $this->icon = $icon;
        $this->version = $version;
        $this->author = $author;
        $this->description = $description ?: "{$this->label} component";
        $this->required_features = $required_features;
        $this->assets = $assets;
        $this->component_settings = $component_settings;
        $this->content_type = $content_type;
        $this->edit_url = $edit_url;
        $this->supports = $supports;
        $this->tokens = $tokens;
        $this->properties = $properties;
        $this->style_states = $style_states;
        $this->instance_defaults = $instance_defaults;
        $this->default_assets = $default_assets;
        $this->inspector_schema = $inspector_schema;
        $this->adapter = $adapter;
    }

    /**
     * Check if the component is available (all required features enabled).
     */
    public function is_available(): bool {
        if (empty($this->required_features)) {
            return true;
        }
        if (!class_exists('\\PhantomCore\\Feature\\Feature_Registry')) {
            return true;
        }
        $registry = \PhantomCore\Feature\Feature_Registry::get_instance();
        foreach ($this->required_features as $feature_id) {
            if (!$registry->enabled($feature_id)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get asset URLs for this component, filtered by type.
     */
    public function get_assets(string $type = 'css'): array {
        return $this->assets[$type] ?? [];
    }

    /**
     * Instantiate the component class.
     */
    public function instance(): object {
        if (empty($this->class_name) || !class_exists($this->class_name)) {
            throw new \RuntimeException("Component class '{$this->class_name}' not found for '{$this->name}'");
        }
        return new $this->class_name();
    }

    /**
     * Render the component with the given data.
     */
    public function render(array $data = []): string {
        $obj = $this->instance();
        if (method_exists($obj, 'render')) {
            return $obj->render($data);
        }
        if (method_exists($obj, 'render_collection')) {
            return $obj->render_collection([$data]);
        }
        return '';
    }

    public function to_array(): array {
        return [
            'name' => $this->name,
            'label' => $this->label,
            'category' => $this->category,
            'class_name' => $this->class_name,
            'dependencies' => $this->dependencies,
            'is_abstract' => $this->is_abstract,
            'icon' => $this->icon,
            'version' => $this->version,
            'author' => $this->author,
            'description' => $this->description,
            'required_features' => $this->required_features,
            'assets' => $this->assets,
            'component_settings' => $this->component_settings,
            'content_type' => $this->content_type,
            'edit_url' => $this->edit_url,
            'supports' => $this->supports,
            'tokens' => $this->tokens,
            'properties' => $this->properties,
            'style_states' => $this->style_states,
            'instance_defaults' => $this->instance_defaults,
            'default_assets' => $this->default_assets,
            'inspector_schema' => $this->inspector_schema,
            'adapter' => $this->adapter,
            'is_available' => $this->is_available(),
        ];
    }

    public static function from_array(array $data): self {
        return new self(
            name: $data['name'] ?? '',
            label: $data['label'] ?? '',
            category: $data['category'] ?? 'general',
            class_name: $data['class_name'] ?? '',
            dependencies: $data['dependencies'] ?? [],
            is_abstract: (bool) ($data['is_abstract'] ?? false),
            icon: $data['icon'] ?? 'dashicons-layout',
            version: $data['version'] ?? '1.0.0',
            author: $data['author'] ?? 'Phantom Core',
            description: $data['description'] ?? '',
            required_features: $data['required_features'] ?? [],
            assets: $data['assets'] ?? [],
            component_settings: $data['component_settings'] ?? [],
            content_type: $data['content_type'] ?? 'static',
            edit_url: $data['edit_url'] ?? null,
            supports: $data['supports'] ?? [],
            tokens: $data['tokens'] ?? [],
            properties: $data['properties'] ?? [],
            style_states: $data['style_states'] ?? ['normal'],
            instance_defaults: $data['instance_defaults'] ?? [],
            default_assets: $data['default_assets'] ?? [],
            inspector_schema: $data['inspector_schema'] ?? [],
            adapter: $data['adapter'] ?? null
        );
    }
}
