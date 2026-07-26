<?php
/**
 * Shop Sidebar
 *
 * @package Phantom_Theme
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
if ( ! is_active_sidebar( 'sidebar-shop' ) ) {
    return;
}
?>
<aside id="secondary" class="widget-area shop-widget-area">
    <?php dynamic_sidebar( 'sidebar-shop' ); ?>
</aside>
