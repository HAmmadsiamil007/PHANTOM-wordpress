<?php
declare(strict_types=1);

namespace PhantomTheme;

defined('ABSPATH') || exit;

// Load Bootstrap 5 nav walker
require_once __DIR__ . '/class-bootstrap-nav-walker.php';

add_action('after_setup_theme', function (): void {
    add_theme_support('post-thumbnails');
    add_theme_support('woocommerce');
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script']);
    add_theme_support('custom-logo', ['height' => 60, 'width' => 200, 'flex-height' => true, 'flex-width' => true]);
    add_theme_support('title-tag');

    // Register nav menu locations
    register_nav_menus([
        'primary'          => __('Primary Menu', 'phantom-core'),
        'footer'           => __('Footer Menu', 'phantom-core'),
        'phantom_primary'   => __('Phantom Primary', 'phantom-core'),
        'phantom_secondary' => __('Phantom Secondary', 'phantom-core'),
        'phantom_footer'    => __('Phantom Footer', 'phantom-core'),
        'phantom_mobile'    => __('Phantom Mobile', 'phantom-core'),
    ]);
});