<?php
declare(strict_types=1);

namespace PhantomCore\Components;

use PhantomCore\Adapters\Product_Adapter;
use PhantomCore\Adapters\Category_Adapter;
use PhantomCore\Adapters\Hero_Adapter;

defined('ABSPATH') || exit;

class Component_Manager {
    private static ?self $instance = null;
    private Component_Registry $registry;
    private Product_Adapter $product_adapter;
    private Category_Adapter $category_adapter;
    private Hero_Adapter $hero_adapter;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->registry = Component_Registry::get_instance();
        $this->product_adapter = new Product_Adapter();
        $this->category_adapter = new Category_Adapter();
        $this->hero_adapter = new Hero_Adapter();
    }

    /**
     * Initialize: register default components.
     */
    public function init(): void {
        $this->registry->register_defaults();
    }

    /**
     * Render a product card with normalized data.
     */
    public function render_product_card($product): string {
        $data = $this->product_adapter->normalize($product);
        return $this->registry->render('product_card', $data);
    }

    /**
     * Render product cards collection.
     */
    public function render_product_cards(array $products): string {
        $collection = $this->product_adapter->normalize_collection($products);
        $output = '';
        foreach ($collection as $data) {
            $output .= $this->registry->render('product_card', $data);
        }
        return $output;
    }

    /**
     * Render a category card with normalized data.
     */
    public function render_category_card($term): string {
        $data = $this->category_adapter->normalize($term);
        return $this->registry->render('category_card', $data);
    }

    /**
     * Render category cards collection.
     */
    public function render_category_cards(array $terms): string {
        $collection = $this->category_adapter->normalize_collection($terms);
        $output = '';
        foreach ($collection as $data) {
            $output .= $this->registry->render('category_card', $data);
        }
        return $output;
    }

    /**
     * Render hero section from settings.
     */
    public function render_hero(): string {
        $data = $this->hero_adapter->normalize();
        return $this->registry->render('hero', $data);
    }

    /**
     * Get the underlying registry.
     */
    public function registry(): Component_Registry {
        return $this->registry;
    }

    /**
     * Get all registered component definitions as arrays.
     */
    public function get_component_definitions(?string $category = null): array {
        $components = $this->registry->get_all($category);
        return array_map(fn(Component $c) => $c->to_array(), $components);
    }
}
