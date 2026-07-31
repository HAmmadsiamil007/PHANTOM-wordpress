<?php
declare(strict_types=1);

namespace PhantomCore\Design;

defined('ABSPATH') || exit;

/**
 * Component_Definition_Registry — manages all Component_Definition instances.
 *
 * Acts as the central catalog that the Design Studio Inspector queries
 * to render the correct tabs and fields for any selected component.
 *
 * @package PhantomCore\Design
 */
class Component_Definition_Registry {

    private static ?self $instance = null;

    /** @var array<string, Component_Definition> */
    private array $definitions = [];

    private bool $defaultsRegistered = false;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Register a component definition.
     */
    public function register(Component_Definition $definition): void {
        $this->definitions[$definition->id] = $definition;
    }

    /**
     * Lazily register all built-in definitions before any lookup.
     */
    private function ensure_defaults(): void {
        $this->register_defaults();
    }

    /**
     * Get a component definition by ID.
     */
    public function get(string $id): ?Component_Definition {
        $this->ensure_defaults();
        return $this->definitions[$id] ?? null;
    }

    /**
     * Get all registered definitions, optionally filtered by category.
     *
     * @param string|null $category
     * @return Component_Definition[]
     */
    public function get_all(?string $category = null): array {
        $this->ensure_defaults();
        if (null === $category) {
            return array_values($this->definitions);
        }
        return array_values(
            array_filter(
                $this->definitions,
                fn(Component_Definition $d) => $d->category === $category
            )
        );
    }

    /**
     * Get all unique category names.
     */
    public function get_categories(): array {
        $this->ensure_defaults();
        $cats = [];
        foreach ($this->definitions as $d) {
            $cats[$d->category] = true;
        }
        return array_keys($cats);
    }

    /**
     * Check if a definition exists.
     */
    public function has(string $id): bool {
        $this->ensure_defaults();
        return isset($this->definitions[$id]);
    }

    /**
     * Get count.
     */
    public function count(): int {
        $this->ensure_defaults();
        return count($this->definitions);
    }

    public function count_by_category(): array {
        $this->ensure_defaults();
        $cats = [];
        foreach ($this->definitions as $d) {
            $cat = $d->category ?? 'other';
            if (!isset($cats[$cat])) {
                $cats[$cat] = 0;
            }
            $cats[$cat]++;
        }
        return $cats;
    }

    /**
     * Register all built-in component definitions for the Design Studio Inspector.
     */
    public function register_defaults(): void {
        if ($this->defaultsRegistered) {
            return;
        }
        $this->defaultsRegistered = true;

        // ── Page (navigator container) ──────────────────────
        $this->register(new Component_Definition([
            'id'             => 'page',
            'name'           => __('Page', 'phantom-core'),
            'category'       => 'pages',
            'description'    => __('Page-level container with global layout settings.', 'phantom-core'),
            'capabilities'   => [],
            'tabs'           => [
                [
                    'key'    => 'content',
                    'label'  => __('Content', 'phantom-core'),
                    'fields' => [
                        ['key' => 'page_title',       'label' => __('Page Title', 'phantom-core'),       'type' => 'text',     'default' => ''],
                        ['key' => 'page_description', 'label' => __('Meta Description', 'phantom-core'), 'type' => 'textarea', 'default' => ''],
                    ],
                ],
            ],
        ]));

        // ── Hero ────────────────────────────────────────────
        $this->register(new Component_Definition([
            'id'             => 'hero',
            'name'           => __('Hero Banner', 'phantom-core'),
            'category'       => 'sections',
            'renderer'       => 'Hero_Renderer',
            'adapter'        => 'Hero_Adapter',
            'view_model'     => 'Hero_ViewModel',
            'template'       => 'frontend/html/components/hero.html',
            'token_group'    => 'hero',
            'settings_group' => 'hero',
            'description'    => __('Main hero banner with background, title, and CTA button.', 'phantom-core'),
            'aria_role'      => 'banner',
            'variants'       => ['default', 'centered', 'split', 'fullscreen'],
            'capabilities'   => ['edit', 'reset', 'responsive', 'animate'],
            'dependencies'   => ['phantom-core'],
            'assets'         => ['hero.css', 'animations.js'],
            'seo'            => ['schema_type' => 'WPHeader'],
            'tabs'           => [
                [
                    'key'    => 'content',
                    'label'  => __('Content', 'phantom-core'),
                    'fields' => [
                        ['key' => 'hero_title',       'label' => __('Title', 'phantom-core'),       'type' => 'text',     'default' => 'Welcome'],
                        ['key' => 'hero_subtitle',    'label' => __('Subtitle', 'phantom-core'),    'type' => 'textarea', 'default' => ''],
                        ['key' => 'hero_button_text', 'label' => __('Button Text', 'phantom-core'), 'type' => 'text',     'default' => 'Shop Now'],
                        ['key' => 'hero_button_url',  'label' => __('Button URL', 'phantom-core'),  'type' => 'text',     'default' => ''],
                    ],
                ],
                [
                    'key'    => 'background',
                    'label'  => __('Background', 'phantom-core'),
                    'fields' => [
                        ['key' => 'hero_bg_color',       'label' => __('Background Color', 'phantom-core'), 'type' => 'color',  'default' => '#1a1a2e'],
                        ['key' => 'hero_bg_image',       'label' => __('Background Image', 'phantom-core'), 'type' => 'image',  'default' => ''],
                        ['key' => 'hero_overlay_color',  'label' => __('Overlay Color', 'phantom-core'),    'type' => 'color',  'default' => 'rgba(0,0,0,0.4)'],
                        ['key' => 'hero_overlay_opacity','label' => __('Overlay Opacity', 'phantom-core'), 'type' => 'slider', 'default' => '0.4', 'min' => 0, 'max' => 1, 'step' => 0.1],
                    ],
                ],
                [
                    'key'    => 'typography',
                    'label'  => __('Typography', 'phantom-core'),
                    'fields' => [
                        ['key' => 'hero_title_font',    'label' => __('Title Font', 'phantom-core'),    'type' => 'select', 'default' => 'Inter',    'options' => ['Inter', 'Playfair Display', 'Space Grotesk', 'DM Sans', 'Montserrat']],
                        ['key' => 'hero_title_size',   'label' => __('Title Size', 'phantom-core'),   'type' => 'slider', 'default' => '48', 'min' => 24, 'max' => 96, 'step' => 1, 'unit' => 'px'],
                        ['key' => 'hero_title_weight', 'label' => __('Title Weight', 'phantom-core'), 'type' => 'select', 'default' => '700', 'options' => ['300', '400', '500', '600', '700', '800', '900']],
                    ],
                ],
                [
                    'key'    => 'spacing',
                    'label'  => __('Spacing', 'phantom-core'),
                    'fields' => [
                        ['key' => 'hero_padding_y', 'label' => __('Vertical Padding', 'phantom-core'), 'type' => 'slider', 'default' => '100', 'min' => 40, 'max' => 300, 'step' => 4, 'unit' => 'px'],
                        ['key' => 'hero_height',    'label' => __('Min Height', 'phantom-core'),       'type' => 'slider', 'default' => '600', 'min' => 300, 'max' => 1080, 'step' => 10, 'unit' => 'px'],
                    ],
                ],
                [
                    'key'    => 'animation',
                    'label'  => __('Animation', 'phantom-core'),
                    'fields' => [
                        ['key' => 'hero_animation',       'label' => __('Animation Type', 'phantom-core'), 'type' => 'select', 'default' => 'fade', 'options' => ['none', 'fade', 'slide-up', 'slide-left', 'zoom', 'parallax']],
                        ['key' => 'hero_animation_delay', 'label' => __('Delay (ms)', 'phantom-core'),     'type' => 'slider', 'default' => '0', 'min' => 0, 'max' => 2000, 'step' => 100, 'unit' => 'ms'],
                    ],
                ],
                [
                    'key'    => 'responsive',
                    'label'  => __('Responsive', 'phantom-core'),
                    'fields' => [
                        ['key' => 'hero_mobile_height', 'label' => __('Mobile Height', 'phantom-core'), 'type' => 'slider', 'default' => '400', 'min' => 200, 'max' => 800, 'step' => 10, 'unit' => 'px'],
                        ['key' => 'hero_tablet_height', 'label' => __('Tablet Height', 'phantom-core'), 'type' => 'slider', 'default' => '500', 'min' => 200, 'max' => 900, 'step' => 10, 'unit' => 'px'],
                    ],
                ],
            ],
        ]));

        // ── Header ─────────────────────────────────────────
        $this->register(new Component_Definition([
            'id'             => 'header',
            'name'           => __('Header', 'phantom-core'),
            'category'       => 'layout',
            'renderer'       => 'Header_Renderer',
            'template'       => 'frontend/html/components/nav-menu.html',
            'token_group'    => 'header',
            'settings_group' => 'header',
            'description'    => __('Site header with logo, navigation, and cart icon.', 'phantom-core'),
            'aria_role'      => 'navigation',
            'variants'       => ['default', 'centered', 'transparent', 'sticky'],
            'capabilities'   => ['edit', 'reset'],
            'tabs'           => [
                [
                    'key'    => 'content',
                    'label'  => __('Content', 'phantom-core'),
                    'fields' => [
                        ['key' => 'header_style',        'label' => __('Header Style', 'phantom-core'),    'type' => 'select', 'default' => 'default', 'options' => ['default', 'centered', 'transparent', 'sticky']],
                        ['key' => 'header_sticky',       'label' => __('Sticky Header', 'phantom-core'),  'type' => 'toggle', 'default' => false],
                        ['key' => 'header_search_icon',  'label' => __('Show Search Icon', 'phantom-core'), 'type' => 'toggle', 'default' => true],
                        ['key' => 'header_cart_icon',    'label' => __('Show Cart Icon', 'phantom-core'),  'type' => 'toggle', 'default' => true],
                    ],
                ],
                [
                    'key'    => 'background',
                    'label'  => __('Background', 'phantom-core'),
                    'fields' => [
                        ['key' => 'header_bg_color',      'label' => __('Background Color', 'phantom-core'), 'type' => 'color', 'default' => '#ffffff'],
                        ['key' => 'header_bg_transparent', 'label' => __('Transparent', 'phantom-core'),       'type' => 'toggle', 'default' => false],
                    ],
                ],
                [
                    'key'    => 'typography',
                    'label'  => __('Typography', 'phantom-core'),
                    'fields' => [
                        ['key' => 'header_link_color', 'label' => __('Link Color', 'phantom-core'), 'type' => 'color', 'default' => '#1a1a2e'],
                        ['key' => 'header_link_hover', 'label' => __('Link Hover', 'phantom-core'), 'type' => 'color', 'default' => '#e94560'],
                    ],
                ],
                [
                    'key'    => 'spacing',
                    'label'  => __('Spacing', 'phantom-core'),
                    'fields' => [
                        ['key' => 'header_padding_y', 'label' => __('Vertical Padding', 'phantom-core'), 'type' => 'slider', 'default' => '16', 'min' => 8, 'max' => 48, 'step' => 2, 'unit' => 'px'],
                    ],
                ],
            ],
        ]));

        // ── Footer ─────────────────────────────────────────
        $this->register(new Component_Definition([
            'id'             => 'footer',
            'name'           => __('Footer', 'phantom-core'),
            'category'       => 'layout',
            'renderer'       => 'Footer_Renderer',
            'template'       => 'frontend/html/components/footer.html',
            'token_group'    => 'footer',
            'settings_group' => 'footer',
            'description'    => __('Site footer with widgets, menu, copyright, and social links.', 'phantom-core'),
            'aria_role'      => 'contentinfo',
            'variants'       => ['default', 'minimal', 'columns'],
            'capabilities'   => ['edit', 'reset'],
            'tabs'           => [
                [
                    'key'    => 'content',
                    'label'  => __('Content', 'phantom-core'),
                    'fields' => [
                        ['key' => 'footer_style',      'label' => __('Footer Style', 'phantom-core'),    'type' => 'select', 'default' => 'default', 'options' => ['default', 'minimal', 'columns']],
                        ['key' => 'footer_copyright',  'label' => __('Copyright Text', 'phantom-core'),  'type' => 'text',   'default' => '© 2026 All rights reserved.'],
                        ['key' => 'footer_columns',    'label' => __('Widget Columns', 'phantom-core'),  'type' => 'select', 'default' => '4', 'options' => ['1', '2', '3', '4']],
                    ],
                ],
                [
                    'key'    => 'background',
                    'label'  => __('Background', 'phantom-core'),
                    'fields' => [
                        ['key' => 'footer_bg_color', 'label' => __('Background Color', 'phantom-core'), 'type' => 'color', 'default' => '#16213e'],
                        ['key' => 'footer_text_color', 'label' => __('Text Color', 'phantom-core'),     'type' => 'color', 'default' => '#e0e0e0'],
                    ],
                ],
                [
                    'key'    => 'spacing',
                    'label'  => __('Spacing', 'phantom-core'),
                    'fields' => [
                        ['key' => 'footer_padding_y', 'label' => __('Vertical Padding', 'phantom-core'), 'type' => 'slider', 'default' => '60', 'min' => 20, 'max' => 120, 'step' => 4, 'unit' => 'px'],
                    ],
                ],
            ],
        ]));

        // ── Products Grid ──────────────────────────────────
        $this->register(new Component_Definition([
            'id'             => 'products',
            'name'           => __('Products Grid', 'phantom-core'),
            'category'       => 'shop',
            'renderer'       => 'Products_Renderer',
            'template'       => 'frontend/html/components/product-card.html',
            'token_group'    => 'products',
            'settings_group' => 'products',
            'description'    => __('Grid of product cards with image, price, and add-to-cart.', 'phantom-core'),
            'aria_role'      => 'region',
            'variants'       => ['default', 'grid', 'list', 'compact'],
            'dependencies'   => ['woocommerce'],
            'tabs'           => [
                [
                    'key'    => 'content',
                    'label'  => __('Content', 'phantom-core'),
                    'fields' => [
                        ['key' => 'products_per_page', 'label' => __('Products Per Page', 'phantom-core'), 'type' => 'slider', 'default' => '12', 'min' => 4, 'max' => 48, 'step' => 2],
                        ['key' => 'products_columns',  'label' => __('Columns', 'phantom-core'),          'type' => 'select', 'default' => '4', 'options' => ['2', '3', '4', '5', '6']],
                        ['key' => 'products_layout',   'label' => __('Layout', 'phantom-core'),           'type' => 'select', 'default' => 'grid', 'options' => ['grid', 'list', 'compact']],
                    ],
                ],
                [
                    'key'    => 'typography',
                    'label'  => __('Typography', 'phantom-core'),
                    'fields' => [
                        ['key' => 'products_title_size', 'label' => __('Title Size', 'phantom-core'), 'type' => 'slider', 'default' => '16', 'min' => 12, 'max' => 32, 'step' => 1, 'unit' => 'px'],
                        ['key' => 'products_price_size', 'label' => __('Price Size', 'phantom-core'), 'type' => 'slider', 'default' => '14', 'min' => 10, 'max' => 28, 'step' => 1, 'unit' => 'px'],
                    ],
                ],
                [
                    'key'    => 'spacing',
                    'label'  => __('Spacing', 'phantom-core'),
                    'fields' => [
                        ['key' => 'products_gap', 'label' => __('Grid Gap', 'phantom-core'), 'type' => 'slider', 'default' => '24', 'min' => 8, 'max' => 48, 'step' => 2, 'unit' => 'px'],
                    ],
                ],
                [
                    'key'    => 'animation',
                    'label'  => __('Animation', 'phantom-core'),
                    'fields' => [
                        ['key' => 'products_animation', 'label' => __('Card Animation', 'phantom-core'), 'type' => 'select', 'default' => 'fade-up', 'options' => ['none', 'fade', 'fade-up', 'zoom', 'slide-up']],
                    ],
                ],
            ],
        ]));

        // ── Blog Posts ─────────────────────────────────────
        $this->register(new Component_Definition([
            'id'             => 'blog',
            'name'           => __('Blog Posts', 'phantom-core'),
            'category'       => 'content',
            'renderer'       => 'Blog_Renderer',
            'template'       => 'frontend/html/components/blog-card.html',
            'token_group'    => 'blog',
            'settings_group' => 'blog',
            'description'    => __('Blog post grid with featured images, categories, and excerpts.', 'phantom-core'),
            'aria_role'      => 'region',
            'variants'       => ['default', 'grid', 'list'],
            'tabs'           => [
                [
                    'key'    => 'content',
                    'label'  => __('Content', 'phantom-core'),
                    'fields' => [
                        ['key' => 'blog_layout',    'label' => __('Blog Layout', 'phantom-core'),  'type' => 'select', 'default' => 'grid', 'options' => ['grid', 'list', 'masonry']],
                        ['key' => 'blog_columns',   'label' => __('Columns', 'phantom-core'),       'type' => 'select', 'default' => '3', 'options' => ['1', '2', '3', '4']],
                        ['key' => 'blog_per_page',  'label' => __('Posts Per Page', 'phantom-core'), 'type' => 'slider', 'default' => '9', 'min' => 3, 'max' => 30, 'step' => 3],
                        ['key' => 'blog_read_more_text', 'label' => __('Read More Text', 'phantom-core'), 'type' => 'text', 'default' => 'Read More'],
                    ],
                ],
                [
                    'key'    => 'typography',
                    'label'  => __('Typography', 'phantom-core'),
                    'fields' => [
                        ['key' => 'blog_title_size', 'label' => __('Title Size', 'phantom-core'), 'type' => 'slider', 'default' => '20', 'min' => 14, 'max' => 36, 'step' => 1, 'unit' => 'px'],
                    ],
                ],
                [
                    'key'    => 'spacing',
                    'label'  => __('Spacing', 'phantom-core'),
                    'fields' => [
                        ['key' => 'blog_gap', 'label' => __('Grid Gap', 'phantom-core'), 'type' => 'slider', 'default' => '24', 'min' => 8, 'max' => 48, 'step' => 2, 'unit' => 'px'],
                    ],
                ],
            ],
        ]));

        // ── Navigation ─────────────────────────────────────
        $this->register(new Component_Definition([
            'id'             => 'navigation',
            'name'           => __('Navigation Menu', 'phantom-core'),
            'category'       => 'layout',
            'renderer'       => 'Nav_Menu_Renderer',
            'template'       => 'frontend/html/components/nav-menu.html',
            'token_group'    => 'navigation',
            'settings_group' => 'navigation',
            'description'    => __('Primary navigation menu with dropdown support.', 'phantom-core'),
            'aria_role'      => 'navigation',
            'variants'       => ['default', 'dropdown', 'mega'],
            'tabs'           => [
                [
                    'key'    => 'content',
                    'label'  => __('Content', 'phantom-core'),
                    'fields' => [
                        ['key' => 'nav_style',      'label' => __('Menu Style', 'phantom-core'),    'type' => 'select', 'default' => 'default', 'options' => ['default', 'dropdown', 'mega']],
                        ['key' => 'nav_alignment',  'label' => __('Alignment', 'phantom-core'),     'type' => 'select', 'default' => 'left', 'options' => ['left', 'center', 'right']],
                        ['key' => 'nav_dropdown',   'label' => __('Enable Dropdowns', 'phantom-core'), 'type' => 'toggle', 'default' => true],
                    ],
                ],
                [
                    'key'    => 'typography',
                    'label'  => __('Typography', 'phantom-core'),
                    'fields' => [
                        ['key' => 'nav_font_size', 'label' => __('Font Size', 'phantom-core'), 'type' => 'slider', 'default' => '14', 'min' => 11, 'max' => 24, 'step' => 1, 'unit' => 'px'],
                        ['key' => 'nav_font_weight', 'label' => __('Font Weight', 'phantom-core'), 'type' => 'select', 'default' => '500', 'options' => ['300', '400', '500', '600', '700']],
                    ],
                ],
            ],
        ]));

        // ── Collections Grid ───────────────────────────────
        $this->register(new Component_Definition([
            'id'             => 'collections',
            'name'           => __('Collections Grid', 'phantom-core'),
            'category'       => 'shop',
            'renderer'       => 'Collections_Renderer',
            'template'       => 'frontend/html/components/category-card.html',
            'token_group'    => 'collections',
            'settings_group' => 'collections',
            'description'    => __('Product category grid with images, names, and item counts.', 'phantom-core'),
            'dependencies'   => ['woocommerce'],
            'variants'       => ['default', 'grid', 'carousel'],
            'tabs'           => [
                [
                    'key'    => 'content',
                    'label'  => __('Content', 'phantom-core'),
                    'fields' => [
                        ['key' => 'collections_count', 'label' => __('Number of Collections', 'phantom-core'), 'type' => 'slider', 'default' => '6', 'min' => 2, 'max' => 20, 'step' => 1],
                        ['key' => 'collections_columns', 'label' => __('Columns', 'phantom-core'),          'type' => 'select', 'default' => '3', 'options' => ['2', '3', '4']],
                        ['key' => 'collections_style', 'label' => __('Style', 'phantom-core'),               'type' => 'select', 'default' => 'grid', 'options' => ['grid', 'carousel']],
                    ],
                ],
                [
                    'key'    => 'spacing',
                    'label'  => __('Spacing', 'phantom-core'),
                    'fields' => [
                        ['key' => 'collections_gap', 'label' => __('Grid Gap', 'phantom-core'), 'type' => 'slider', 'default' => '20', 'min' => 8, 'max' => 40, 'step' => 2, 'unit' => 'px'],
                    ],
                ],
            ],
        ]));

        // ── Testimonials ───────────────────────────────────
        $this->register(new Component_Definition([
            'id'             => 'testimonials',
            'name'           => __('Testimonials', 'phantom-core'),
            'category'       => 'sections',
            'renderer'       => 'Testimonials_Renderer',
            'template'       => 'frontend/html/components/testimonial-card.html',
            'token_group'    => 'testimonials',
            'settings_group' => 'testimonials',
            'description'    => __('Customer testimonial cards with quotes, avatars, and ratings.', 'phantom-core'),
            'variants'       => ['default', 'carousel', 'grid'],
            'tabs'           => [
                [
                    'key'    => 'content',
                    'label'  => __('Content', 'phantom-core'),
                    'fields' => [
                        ['key' => 'testimonials_count', 'label' => __('Number of Testimonials', 'phantom-core'), 'type' => 'slider', 'default' => '3', 'min' => 1, 'max' => 12, 'step' => 1],
                        ['key' => 'testimonials_layout', 'label' => __('Layout', 'phantom-core'), 'type' => 'select', 'default' => 'carousel', 'options' => ['carousel', 'grid', 'single']],
                        ['key' => 'testimonials_autoplay', 'label' => __('Auto-rotate', 'phantom-core'), 'type' => 'toggle', 'default' => true],
                    ],
                ],
                [
                    'key'    => 'typography',
                    'label'  => __('Typography', 'phantom-core'),
                    'fields' => [
                        ['key' => 'testimonials_quote_size', 'label' => __('Quote Size', 'phantom-core'), 'type' => 'slider', 'default' => '18', 'min' => 12, 'max' => 32, 'step' => 1, 'unit' => 'px'],
                    ],
                ],
            ],
        ]));

        // ── Announcement Bar ───────────────────────────────
        $this->register(new Component_Definition([
            'id'             => 'announcement',
            'name'           => __('Announcement Bar', 'phantom-core'),
            'category'       => 'sections',
            'renderer'       => 'Announcement_Renderer',
            'template'       => 'frontend/html/components/announcement.html',
            'token_group'    => 'announcement',
            'settings_group' => 'announcement',
            'description'    => __('Top announcement bar with text, link, and dismiss option.', 'phantom-core'),
            'variants'       => ['default', 'dismissible'],
            'tabs'           => [
                [
                    'key'    => 'content',
                    'label'  => __('Content', 'phantom-core'),
                    'fields' => [
                        ['key' => 'announcement_text', 'label' => __('Announcement Text', 'phantom-core'), 'type' => 'text', 'default' => 'Free shipping on orders over $50!'],
                        ['key' => 'announcement_link', 'label' => __('Link URL', 'phantom-core'),          'type' => 'text', 'default' => ''],
                        ['key' => 'announcement_dismissible', 'label' => __('Dismissible', 'phantom-core'), 'type' => 'toggle', 'default' => true],
                    ],
                ],
                [
                    'key'    => 'background',
                    'label'  => __('Background', 'phantom-core'),
                    'fields' => [
                        ['key' => 'announcement_bg_color', 'label' => __('Background Color', 'phantom-core'), 'type' => 'color', 'default' => '#e94560'],
                        ['key' => 'announcement_text_color', 'label' => __('Text Color', 'phantom-core'),    'type' => 'color', 'default' => '#ffffff'],
                    ],
                ],
            ],
        ]));

        // ── Cart Icon ──────────────────────────────────────
        $this->register(new Component_Definition([
            'id'             => 'cart-icon',
            'name'           => __('Cart Icon', 'phantom-core'),
            'category'       => 'shop',
            'renderer'       => 'Cart_Icon_Renderer',
            'template'       => 'frontend/html/components/cart-icon.html',
            'token_group'    => 'cart-icon',
            'settings_group' => 'cart-icon',
            'description'    => __('Shopping cart icon with item count badge.', 'phantom-core'),
            'dependencies'   => ['woocommerce'],
            'tabs'           => [
                [
                    'key'    => 'content',
                    'label'  => __('Content', 'phantom-core'),
                    'fields' => [
                        ['key' => 'cart_icon_style', 'label' => __('Icon Style', 'phantom-core'), 'type' => 'select', 'default' => 'default', 'options' => ['default', 'outline', 'solid']],
                        ['key' => 'cart_icon_show_count', 'label' => __('Show Count', 'phantom-core'), 'type' => 'toggle', 'default' => true],
                    ],
                ],
            ],
        ]));

        // ── Logo ───────────────────────────────────────────
        $this->register(new Component_Definition([
            'id'             => 'logo',
            'name'           => __('Logo', 'phantom-core'),
            'category'       => 'layout',
            'renderer'       => 'Logo_Renderer',
            'template'       => 'frontend/html/components/logo.html',
            'token_group'    => 'logo',
            'settings_group' => 'logo',
            'description'    => __('Site logo with optional tagline.', 'phantom-core'),
            'capabilities'   => ['edit'],
            'tabs'           => [
                [
                    'key'    => 'content',
                    'label'  => __('Content', 'phantom-core'),
                    'fields' => [
                        ['key' => 'logo_type',    'label' => __('Logo Type', 'phantom-core'),  'type' => 'select', 'default' => 'image', 'options' => ['image', 'text', 'both']],
                        ['key' => 'logo_image',   'label' => __('Logo Image', 'phantom-core'), 'type' => 'image',  'default' => ''],
                        ['key' => 'logo_text',    'label' => __('Logo Text', 'phantom-core'),  'type' => 'text',   'default' => 'PHANTOM'],
                        ['key' => 'logo_tagline', 'label' => __('Tagline', 'phantom-core'),    'type' => 'text',   'default' => ''],
                    ],
                ],
                [
                    'key'    => 'spacing',
                    'label'  => __('Spacing', 'phantom-core'),
                    'fields' => [
                        ['key' => 'logo_max_width', 'label' => __('Max Width', 'phantom-core'), 'type' => 'slider', 'default' => '180', 'min' => 60, 'max' => 400, 'step' => 10, 'unit' => 'px'],
                    ],
                ],
            ],
        ]));

        // ── Blog Preview (homepage subsection) ──────────────
        $this->register(new Component_Definition([
            'id'             => 'blog-preview',
            'name'           => __('Blog Preview', 'phantom-core'),
            'category'       => 'content',
            'renderer'       => 'Blog_Renderer',
            'template'       => 'frontend/html/components/blog-card.html',
            'token_group'    => 'blog',
            'settings_group' => 'blog',
            'description'    => __('Homepage blog preview section with latest posts.', 'phantom-core'),
            'aria_role'      => 'region',
            'capabilities'   => ['edit', 'reset'],
            'tabs'           => [
                [
                    'key'    => 'content',
                    'label'  => __('Content', 'phantom-core'),
                    'fields' => [
                        ['key' => 'blog_preview_title',    'label' => __('Section Title', 'phantom-core'), 'type' => 'text',   'default' => 'Latest News'],
                        ['key' => 'blog_preview_count',    'label' => __('Posts to Show', 'phantom-core'), 'type' => 'slider', 'default' => '3', 'min' => 1, 'max' => 12, 'step' => 1],
                        ['key' => 'blog_preview_layout',   'label' => __('Layout', 'phantom-core'),        'type' => 'select', 'default' => 'grid', 'options' => ['grid', 'list']],
                    ],
                ],
                [
                    'key'    => 'typography',
                    'label'  => __('Typography', 'phantom-core'),
                    'fields' => [
                        ['key' => 'blog_preview_title_size', 'label' => __('Title Size', 'phantom-core'), 'type' => 'slider', 'default' => '28', 'min' => 18, 'max' => 48, 'step' => 2, 'unit' => 'px'],
                    ],
                ],
                [
                    'key'    => 'spacing',
                    'label'  => __('Spacing', 'phantom-core'),
                    'fields' => [
                        ['key' => 'blog_preview_padding_y', 'label' => __('Vertical Padding', 'phantom-core'), 'type' => 'slider', 'default' => '60', 'min' => 20, 'max' => 160, 'step' => 4, 'unit' => 'px'],
                    ],
                ],
            ],
        ]));

        // ── Footer Columns (footer child) ───────────────────
        $this->register(new Component_Definition([
            'id'             => 'footer-columns',
            'name'           => __('Footer Columns', 'phantom-core'),
            'category'       => 'layout',
            'renderer'       => 'Footer_Columns_Renderer',
            'token_group'    => 'footer',
            'settings_group' => 'footer',
            'description'    => __('Footer widget column section.', 'phantom-core'),
            'capabilities'   => ['edit'],
            'tabs'           => [
                [
                    'key'    => 'content',
                    'label'  => __('Content', 'phantom-core'),
                    'fields' => [
                        ['key' => 'footer_columns', 'label' => __('Number of Columns', 'phantom-core'), 'type' => 'select', 'default' => '4', 'options' => ['1', '2', '3', '4']],
                    ],
                ],
                [
                    'key'    => 'spacing',
                    'label'  => __('Spacing', 'phantom-core'),
                    'fields' => [
                        ['key' => 'footer_columns_gap', 'label' => __('Column Gap', 'phantom-core'), 'type' => 'slider', 'default' => '24', 'min' => 8, 'max' => 48, 'step' => 2, 'unit' => 'px'],
                    ],
                ],
            ],
        ]));

        // ── Copyright Bar (footer child) ────────────────────
        $this->register(new Component_Definition([
            'id'             => 'copyright',
            'name'           => __('Copyright Bar', 'phantom-core'),
            'category'       => 'layout',
            'renderer'       => 'Copyright_Renderer',
            'token_group'    => 'footer',
            'settings_group' => 'footer',
            'description'    => __('Footer copyright text and social links.', 'phantom-core'),
            'capabilities'   => ['edit'],
            'tabs'           => [
                [
                    'key'    => 'content',
                    'label'  => __('Content', 'phantom-core'),
                    'fields' => [
                        ['key' => 'footer_copyright', 'label' => __('Copyright Text', 'phantom-core'), 'type' => 'text', 'default' => '© 2026 All rights reserved.'],
                    ],
                ],
                [
                    'key'    => 'typography',
                    'label'  => __('Typography', 'phantom-core'),
                    'fields' => [
                        ['key' => 'copyright_font_size', 'label' => __('Font Size', 'phantom-core'), 'type' => 'slider', 'default' => '14', 'min' => 10, 'max' => 24, 'step' => 1, 'unit' => 'px'],
                    ],
                ],
            ],
        ]));
    }
}
