<?php
declare(strict_types=1);

namespace PhantomCore\Components;

use PhantomCore\Renderer\Component_Renderer;

defined('ABSPATH') || exit;

class Component_Registry {
    private static ?self $instance = null;
    private array $components = [];

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Register a component.
     */
    public function register(Component $component): void {
        $this->components[$component->name] = $component;
    }

    /**
     * Register a component from an array definition.
     */
    public function register_from_array(array $data): void {
        $this->register(Component::from_array($data));
    }

    /**
     * Register a component class automatically.
     *
     * @param string $name       Component identifier (e.g. 'product_card').
     * @param string $class_name Full PHP class name (e.g. 'PhantomCore\\Renderer\\Product_Card').
     * @param string $category   Component category (e.g. 'shop', 'content').
     */
    public function register_class(string $name, string $class_name, string $category = 'general'): void {
        $label = ucwords(str_replace('_', ' ', $name));
        $this->register(new Component($name, $label, $category, $class_name));
    }

    /**
     * Get a registered component by name.
     */
    public function get(string $name): ?Component {
        return $this->components[$name] ?? null;
    }

    /**
     * Check if a component is registered.
     */
    public function has(string $name): bool {
        return isset($this->components[$name]);
    }

    /**
     * Get all registered components, optionally filtered by category.
     */
    public function get_all(?string $category = null): array {
        if (null === $category) {
            return $this->components;
        }
        return array_filter(
            $this->components,
            fn(Component $c) => $c->category === $category
        );
    }

    /**
     * Get all unique category names from registered components.
     */
    public function get_categories(): array {
        $cats = [];
        foreach ($this->components as $c) {
            $cats[$c->category] = true;
        }
        return array_keys($cats);
    }

    /**
     * Instantiate and render a component by name.
     */
    public function render(string $name, array $data = []): string {
        $component = $this->get($name);
        if (null === $component) {
            return "<!-- Component '{$name}' not registered -->";
        }
        try {
            return $component->render($data);
        } catch (\Throwable $e) {
            return "<!-- Component '{$name}' error: {$e->getMessage()} -->";
        }
    }

    /**
     * Remove a component from the registry.
     */
    public function deregister(string $name): bool {
        if (!isset($this->components[$name])) {
            return false;
        }
        unset($this->components[$name]);
        return true;
    }

    /**
     * Return count of registered components.
     */
    public function count(): int {
        return count($this->components);
    }

    /**
     * Register the built-in Phantom Core components.
     */
    public function register_defaults(): void {
        $defaults = [
            'product_card' => [
                'name' => 'product_card',
                'label' => 'Product Card',
                'category' => 'shop',
                'class_name' => 'PhantomCore\\Renderer\\Product_Card',
                'version' => '1.0.0',
                'author' => 'Phantom Core',
                'description' => 'Displays a single product with image, price, and add-to-cart button in card layout.',
                'dependencies' => ['phantom-core'],
                'required_features' => ['woocommerce'],
                'assets' => ['css' => ['shop.css'], 'js' => ['services/cart-service.js']],
                'component_settings' => ['card_quick_view', 'shop_wishlist_enable'],
            ],
            'category_card' => [
                'name' => 'category_card',
                'label' => 'Category Card',
                'category' => 'shop',
                'class_name' => 'PhantomCore\\Renderer\\Category_Card',
                'version' => '1.0.0',
                'author' => 'Phantom Core',
                'description' => 'Displays a product category with image, name, and item count.',
                'dependencies' => ['phantom-core'],
                'required_features' => ['woocommerce'],
            ],
            'hero' => [
                'name' => 'hero',
                'label' => 'Hero Banner',
                'category' => 'content',
                'class_name' => 'PhantomCore\\Renderer\\Hero',
                'version' => '1.1.0',
                'author' => 'Phantom Core',
                'description' => 'Full-width hero section with responsive image, title, subtitle, and CTA button.',
                'dependencies' => ['phantom-core'],
                'required_features' => ['animate_on_scroll'],
                'assets' => ['css' => ['motion.css'], 'js' => ['animations.js']],
                'component_settings' => ['hero_image', 'hero_title', 'hero_subtitle', 'hero_btn_text', 'hero_btn_url'],
            ],
            'footer' => [
                'name' => 'footer',
                'label' => 'Footer',
                'category' => 'layout',
                'class_name' => 'PhantomCore\\Renderer\\Footer',
                'version' => '1.0.0',
                'author' => 'Phantom Core',
                'description' => 'Site footer with widgets, menu, copyright, and social links.',
                'dependencies' => ['phantom-core'],
            ],
        ];

        foreach ($defaults as $data) {
            $this->register_from_array($data);
        }
    }
}
