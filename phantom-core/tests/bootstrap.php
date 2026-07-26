<?php
/**
 * PHPUnit bootstrap for Phantom Core standalone tests.
 *
 * WordPress-specific integration tests require the WP test suite.
 * These standalone tests verify pure logic without WordPress dependencies.
 */

define( 'ABSPATH', true );
define( 'PHANTOM_CORE_VERSION', '1.5.0' );
define( 'PHANTOM_CORE_PATH', dirname( __DIR__ ) . '/' );
define( 'PHANTOM_CORE_URL', 'http://example.com/wp-content/plugins/phantom-core/' );
define( 'PHANTOM_CORE_FILE', PHANTOM_CORE_PATH . 'phantom-core.php' );

// WordPress function stubs for standalone testing
if ( ! function_exists( '__' ) ) {
    function __( $text, $domain = 'default' ) { return $text; }
}
if ( ! function_exists( '_x' ) ) {
    function _x( $text, $context, $domain = 'default' ) { return $text; }
}
if ( ! function_exists( '_n' ) ) {
    function _n( $single, $plural, $number, $domain = 'default' ) { return $number > 1 ? $plural : $single; }
}
if ( ! function_exists( 'esc_html__' ) ) {
    function esc_html__( $text, $domain = 'default' ) { return $text; }
}
if ( ! function_exists( 'esc_attr__' ) ) {
    function esc_attr__( $text, $domain = 'default' ) { return $text; }
}
if ( ! function_exists( 'esc_html' ) ) {
    function esc_html( $text ) { return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' ); }
}
if ( ! function_exists( 'esc_attr' ) ) {
    function esc_attr( $text ) { return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' ); }
}
if ( ! function_exists( 'apply_filters' ) ) {
    function apply_filters( $tag, $value ) { return $value; }
}
if ( ! function_exists( 'sanitize_text_field' ) ) {
    function sanitize_text_field( $str ) { return trim( preg_replace( '/\s+/', ' ', strip_tags( $str ) ) ); }
}
if ( ! function_exists( 'wp_strip_all_tags' ) ) {
    function wp_strip_all_tags( $string, $remove_breaks = false ) {
        $string = preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', $string );
        $string = strip_tags( $string );
        if ( ! $remove_breaks ) {
            $string = preg_replace( '/[\r\n\t ]+/', ' ', $string );
        }
        return trim( $string );
    }
}
if ( ! function_exists( 'trailingslashit' ) ) {
    function trailingslashit( $string ) { return rtrim( $string, '/\\' ) . '/'; }
}
if ( ! function_exists( 'untrailingslashit' ) ) {
    function untrailingslashit( $string ) { return rtrim( $string, '/\\' ); }
}
if ( ! function_exists( 'home_url' ) ) {
    function home_url( $path = '', $scheme = null ) { return 'http://example.com' . ( $path ? '/' . ltrim( $path, '/' ) : '' ); }
}
if ( ! function_exists( 'admin_url' ) ) {
    function admin_url( $path = '', $scheme = 'admin' ) { return 'http://example.com/wp-admin/' . ltrim( $path, '/' ); }
}
if ( ! function_exists( 'get_option' ) ) {
    function get_option( $option, $default = false ) { return $default; }
}
if ( ! function_exists( 'update_option' ) ) {
    function update_option( $option, $value, $autoload = null ) { return true; }
}
if ( ! function_exists( 'plugin_dir_url' ) ) {
    function plugin_dir_url( $file ) { return 'http://example.com/wp-content/plugins/' . basename( dirname( $file ) ) . '/'; }
}
if ( ! function_exists( 'get_template_directory_uri' ) ) {
    function get_template_directory_uri() { return 'http://example.com/wp-content/themes/phantom-core'; }
}

// Load settings registry (the core data class)
require_once PHANTOM_CORE_PATH . 'includes/class-settings-registry.php';

// Load font classes
require_once PHANTOM_CORE_PATH . 'includes/class-phantom-font-families.php';

// Load global palette
require_once PHANTOM_CORE_PATH . 'includes/class-phantom-global-palette.php';

// Load Engine classes
require_once PHANTOM_CORE_PATH . 'includes/Engine/PhpEventStore.php';
require_once PHANTOM_CORE_PATH . 'includes/Engine/EventDispatcher.php';

// Load Engine Container
require_once PHANTOM_CORE_PATH . 'includes/Engine/Container.php';
