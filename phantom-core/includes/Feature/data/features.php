<?php
declare(strict_types=1);

namespace PhantomCore\Feature;

defined('ABSPATH') || exit;

return [
    // ===== MOTION & ANIMATION =====
    'animate_on_scroll' => [
        'id' => 'animate_on_scroll',
        'category' => 'motion',
        'type' => 'ast-toggle',
        'label' => 'Scroll Animations',
        'description' => 'Enable fade-in, slide-up, and scale animations as elements enter the viewport.',
        'default' => true,
    ],
    'parallax_effects' => [
        'id' => 'parallax_effects',
        'category' => 'motion',
        'type' => 'ast-toggle',
        'label' => 'Parallax Effects',
        'description' => 'Enable parallax scrolling effects on hero sections and background images.',
        'default' => true,
    ],
    'preloader' => [
        'id' => 'preloader',
        'category' => 'motion',
        'type' => 'ast-toggle',
        'label' => 'Page Preloader',
        'description' => 'Show an animated preloader while the page loads.',
        'default' => true,
    ],
    'counter_animations' => [
        'id' => 'counter_animations',
        'category' => 'motion',
        'type' => 'ast-toggle',
        'label' => 'Counter Animations',
        'description' => 'Animate number counters on the page (stats, testimonials).',
        'default' => true,
    ],
    'gsap_effects' => [
        'id' => 'gsap_effects',
        'category' => 'motion',
        'type' => 'ast-toggle',
        'label' => 'GSAP Effects',
        'description' => 'Enable GSAP-powered animations for premium motion effects.',
        'default' => true,
    ],

    // ===== 3D & INTERACTIVE =====
    'tilt_3d_cards' => [
        'id' => 'tilt_3d_cards',
        'category' => 'effects_3d',
        'type' => 'ast-toggle',
        'label' => '3D Tilt Cards',
        'description' => 'Enable 3D tilt hover effects on product and category cards.',
        'default' => false,
    ],
    'image_zoom' => [
        'id' => 'image_zoom',
        'category' => 'effects_3d',
        'type' => 'ast-toggle',
        'label' => 'Image Zoom',
        'description' => 'Enable hover zoom on product detail images.',
        'default' => true,
    ],
    'three_js_effects' => [
        'id' => 'three_js_effects',
        'category' => 'effects_3d',
        'type' => 'ast-toggle',
        'label' => 'Three.js Effects',
        'description' => 'Enable Three.js 3D background effects. Can impact performance.',
        'default' => false,
    ],

    // ===== SHOP FEATURES =====
    'product_quick_view' => [
        'id' => 'product_quick_view',
        'category' => 'shop',
        'type' => 'ast-toggle',
        'label' => 'Quick View',
        'description' => 'Show a quick-view modal when clicking product cards.',
        'default' => true,
    ],
    'wishlist' => [
        'id' => 'wishlist',
        'category' => 'shop',
        'type' => 'ast-toggle',
        'label' => 'Wishlist',
        'description' => 'Enable wishlist functionality for logged-in users.',
        'default' => true,
    ],
    'product_compare' => [
        'id' => 'product_compare',
        'category' => 'shop',
        'type' => 'ast-toggle',
        'label' => 'Product Compare',
        'description' => 'Enable product comparison feature.',
        'default' => false,
    ],
    'ajax_add_to_cart' => [
        'id' => 'ajax_add_to_cart',
        'category' => 'shop',
        'type' => 'ast-toggle',
        'label' => 'AJAX Add to Cart',
        'description' => 'Add products to cart without page reload.',
        'default' => true,
    ],
    'shop_infinite_scroll' => [
        'id' => 'shop_infinite_scroll',
        'category' => 'shop',
        'type' => 'ast-toggle',
        'label' => 'Infinite Scroll',
        'description' => 'Auto-load more products when scrolling to the bottom.',
        'default' => false,
    ],
    'shipping_calculator' => [
        'id' => 'shipping_calculator',
        'category' => 'shop',
        'type' => 'ast-toggle',
        'label' => 'Shipping Calculator',
        'description' => 'Show shipping cost calculator on the cart page.',
        'default' => true,
    ],

    // ===== NAVIGATION =====
    'mega_menu' => [
        'id' => 'mega_menu',
        'category' => 'navigation',
        'type' => 'ast-toggle',
        'label' => 'Mega Menu',
        'description' => 'Enable mega menu with columns, images, and rich content.',
        'default' => false,
    ],
    'live_search' => [
        'id' => 'live_search',
        'category' => 'navigation',
        'type' => 'ast-toggle',
        'label' => 'Live Search',
        'description' => 'Show live search suggestions as the user types.',
        'default' => true,
    ],
    'sticky_header' => [
        'id' => 'sticky_header',
        'category' => 'navigation',
        'type' => 'ast-toggle',
        'label' => 'Sticky Header',
        'description' => 'Keep the header fixed at the top when scrolling.',
        'default' => true,
    ],
    'back_to_top' => [
        'id' => 'back_to_top',
        'category' => 'navigation',
        'type' => 'ast-toggle',
        'label' => 'Back to Top',
        'description' => 'Show a back-to-top button when scrolling down.',
        'default' => true,
    ],

    // ===== PERFORMANCE =====
    'lazy_load_images' => [
        'id' => 'lazy_load_images',
        'category' => 'performance',
        'type' => 'ast-toggle',
        'label' => 'Lazy Load Images',
        'description' => 'Defer loading of below-the-fold images to improve page speed.',
        'default' => true,
    ],
    'minify_css' => [
        'id' => 'minify_css',
        'category' => 'performance',
        'type' => 'ast-toggle',
        'label' => 'Minify CSS',
        'description' => 'Automatically minify generated CSS for smaller file sizes.',
        'default' => true,
    ],
    'minify_js' => [
        'id' => 'minify_js',
        'category' => 'performance',
        'type' => 'ast-toggle',
        'label' => 'Minify JavaScript',
        'description' => 'Serve minified JavaScript files when available.',
        'default' => true,
    ],

    // ===== ACCESSIBILITY =====
    'skip_links' => [
        'id' => 'skip_links',
        'category' => 'accessibility',
        'type' => 'ast-toggle',
        'label' => 'Skip Links',
        'description' => 'Show keyboard-accessible skip-to-content links.',
        'default' => true,
    ],
    'focus_outlines' => [
        'id' => 'focus_outlines',
        'category' => 'accessibility',
        'type' => 'ast-toggle',
        'label' => 'Focus Outlines',
        'description' => 'Show visible focus outlines for keyboard navigation.',
        'default' => true,
    ],
    'reduced_motion' => [
        'id' => 'reduced_motion',
        'category' => 'accessibility',
        'type' => 'ast-toggle',
        'label' => 'Reduced Motion',
        'description' => 'Respect prefers-reduced-motion OS setting and reduce animations.',
        'default' => true,
    ],

    // ===== BRANDING =====
    'dark_mode' => [
        'id' => 'dark_mode',
        'category' => 'branding',
        'type' => 'ast-toggle',
        'label' => 'Dark Mode',
        'description' => 'Enable dark mode toggle for users.',
        'default' => true,
    ],
    'breadcrumbs' => [
        'id' => 'breadcrumbs',
        'category' => 'branding',
        'type' => 'ast-toggle',
        'label' => 'Breadcrumbs',
        'description' => 'Show breadcrumb navigation on inner pages.',
        'default' => true,
    ],
];
