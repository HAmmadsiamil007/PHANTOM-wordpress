<?php
declare(strict_types=1);

namespace PhantomTheme;

defined('ABSPATH') || exit;

add_action('after_setup_theme', function (): void {
    add_theme_support('post-thumbnails');
    add_theme_support('woocommerce');
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script']);
    add_theme_support('custom-logo', ['height' => 60, 'width' => 200, 'flex-height' => true, 'flex-width' => true]);
    add_theme_support('title-tag');
});