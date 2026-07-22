<?php
/**
 * Plugin Name:       Phantom Core Framework
 * Plugin URI:        https://phantom.test
 * Description:       Core REST API layer for Phantom — settings registry, theme options, customizer, import/export, caching. Backend only — no frontend code.
 * Version:           1.5.1
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * WC requires at least: 9.0
 * WC tested up to:      9.5
 * Author:            Phantom
 * Text Domain:       phantom-core
 * Domain Path:       /languages
 *
 * @package PhantomCore
 */

declare(strict_types=1);

namespace PhantomCore;

defined( 'ABSPATH' ) || exit;

define( 'PHANTOM_CORE_VERSION', '1.5.2' );
define( 'PHANTOM_CORE_FILE', __FILE__ );
define( 'PHANTOM_CORE_PATH', plugin_dir_path( __FILE__ ) );
define( 'PHANTOM_CORE_URL', plugin_dir_url( __FILE__ ) );

add_action( 'plugins_loaded', function (): void {
	load_plugin_textdomain(
		'phantom-core',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages'
	);
}, 1 );

spl_autoload_register(
	function ( string $class ): void {
		$prefix = 'PhantomCore\\';
		$len    = strlen( $prefix );
		if ( strncmp( $prefix, $class, $len ) !== 0 ) {
			return;
		}
		$relative_class = substr( $class, $len );

		// Custom controls use includes/custom-controls/ with class-{name}.php naming
		$controls_prefix = 'Customizer\\Controls\\';
		if ( strncmp( $controls_prefix, $relative_class, strlen( $controls_prefix ) ) === 0 ) {
			$short = substr( $relative_class, strlen( $controls_prefix ) );
			$file  = PHANTOM_CORE_PATH . 'includes/custom-controls/class-' . str_replace( '_', '-', strtolower( $short ) ) . '.php';
			if ( file_exists( $file ) ) {
				require_once $file;
				return;
			}
		}

		$file = PHANTOM_CORE_PATH . 'includes/' . str_replace( '\\', '/', $relative_class ) . '.php';
		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
);

require_once PHANTOM_CORE_PATH . 'includes/class-settings-registry.php';
require_once PHANTOM_CORE_PATH . 'includes/class-core-plugin.php';
require_once PHANTOM_CORE_PATH . 'includes/class-rest-controller.php';
require_once PHANTOM_CORE_PATH . 'includes/class-customizer.php';
require_once PHANTOM_CORE_PATH . 'includes/class-custom-css.php';
require_once PHANTOM_CORE_PATH . 'includes/class-phantom-global-palette.php';
require_once PHANTOM_CORE_PATH . 'includes/class-phantom-version-compatibility.php';
require_once PHANTOM_CORE_PATH . 'includes/class-fonts.php';
require_once PHANTOM_CORE_PATH . 'includes/class-phantom-font-families.php';
require_once PHANTOM_CORE_PATH . 'includes/class-phantom-webfont-loader.php';
require_once PHANTOM_CORE_PATH . 'includes/partial-renderers.php';
require_once PHANTOM_CORE_PATH . 'includes/custom-css/colors.php';
require_once PHANTOM_CORE_PATH . 'includes/custom-css/typography.php';
require_once PHANTOM_CORE_PATH . 'includes/custom-css/header.php';
require_once PHANTOM_CORE_PATH . 'includes/custom-css/footer.php';
require_once PHANTOM_CORE_PATH . 'includes/custom-css/layout.php';
require_once PHANTOM_CORE_PATH . 'includes/custom-css/buttons.php';
require_once PHANTOM_CORE_PATH . 'includes/custom-css/product.php';
require_once PHANTOM_CORE_PATH . 'includes/custom-css/responsive.php';
require_once PHANTOM_CORE_PATH . 'admin/class-settings-page.php';

$rest_path = PHANTOM_CORE_PATH . 'includes/class-rest-controller.php';
if ( file_exists( $rest_path ) ) {
	\PhantomCore\Api\Rest_Controller::get_instance()->init();
}

$settings_page_path = PHANTOM_CORE_PATH . 'admin/class-settings-page.php';
if ( file_exists( $settings_page_path ) ) {
	\PhantomCore\Admin\Settings_Page::get_instance()->init();
}

$font_download_page_path = PHANTOM_CORE_PATH . 'admin/class-font-download-page.php';
if ( file_exists( $font_download_page_path ) ) {
	require_once $font_download_page_path;
	\Phantom_Font_Download_Page::instance()->init();
}

$cache_path = PHANTOM_CORE_PATH . 'includes/Engine/Cache.php';
if ( file_exists( $cache_path ) ) {
	require_once $cache_path;
	\PhantomCore\Engine\Cache::get_instance()->init();
}

$shell_path = PHANTOM_CORE_PATH . 'templates/shell.php';
if ( file_exists( $shell_path ) ) {
	require_once $shell_path;
	\PhantomCore\Shell::get_instance()->init();
}

add_action(
	'plugins_loaded',
	function (): void {
		Plugin::get_instance()->init();
	},
	5
);

// Declare WooCommerce compatibility
add_action(
	'after_setup_theme',
	function (): void {
		if ( class_exists( 'WooCommerce' ) ) {
			add_theme_support( 'woocommerce' );
			add_theme_support( 'wc-product-gallery-zoom' );
			add_theme_support( 'wc-product-gallery-lightbox' );
			add_theme_support( 'wc-product-gallery-slider' );
		}
		add_theme_support( 'post-thumbnails' );
	},
	10
);

add_action(
	'plugins_loaded',
	function (): void {
		\PhantomCore\Version_Compatibility::get_instance()->init();
	},
	10
);

// Initialize Customizer after plugin is loaded
add_action(
	'plugins_loaded',
	function (): void {
		\PhantomCore\Customizer::get_instance()->init();
	},
	15
);

register_activation_hook(
	__FILE__,
	function (): void {
		flush_rewrite_rules();
	}
);

register_deactivation_hook(
	__FILE__,
	function (): void {
		flush_rewrite_rules();
	}
);

register_uninstall_hook(
	__FILE__,
	'PhantomCore\\phantom_uninstall'
);

add_action( 'admin_notices', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$stored_version = get_option( 'phantom_core_version', '' );
	if ( '' !== $stored_version && version_compare( $stored_version, '1.5.0', '<' ) ) {
		echo '<div class="notice notice-info is-dismissible"><p>' . esc_html__( 'Phantom Core 1.5.0 — New features available! Check Customizer for updated controls.', 'phantom-core' ) . '</p></div>';
	}
	update_option( 'phantom_core_version', PHANTOM_CORE_VERSION );
} );

/**
 * Enqueue Google Fonts based on selected typography settings.
 */
function phantom_enqueue_google_fonts(): void {
	$fonts = array();

	$fonts[] = get_option( 'phantom_typography_body_font', 'Archivo' );

	$heading_font = get_option( 'phantom_typography_heading_font', '' );
	if ( '' !== $heading_font ) {
		$fonts[] = $heading_font;
	}

	$headings = array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' );
	foreach ( $headings as $h ) {
		$font = get_option( 'phantom_typography_' . $h . '_font', '' );
		if ( '' !== $font ) {
			$fonts[] = $font;
		}
	}

	$fonts = array_unique( array_filter( $fonts ) );

	$url = \Phantom_Font_Families::instance()->get_font_enqueue_url( $fonts );

	wp_enqueue_style(
		'phantom-google-fonts',
		$url,
		array(),
		PHANTOM_CORE_VERSION
	);
}

add_action( 'wp_enqueue_scripts', 'PhantomCore\\phantom_enqueue_google_fonts', 9 );

/**
 * Enqueue dark mode toggle script.
 */
function phantom_enqueue_dark_mode(): void {
	wp_enqueue_script(
		'phantom-dark-mode',
		PHANTOM_CORE_URL . 'frontend/assets/js/phantom-dark-mode.js',
		array(),
		PHANTOM_CORE_VERSION,
		true
	);
}

add_action( 'wp_enqueue_scripts', 'PhantomCore\\phantom_enqueue_dark_mode', 11 );

\Phantom_Webfont_Loader::instance()->init();

if ( ! function_exists( 'phantom_sanitize_subsets' ) ) {
	function phantom_sanitize_subsets( $value ): array {
		if ( ! is_array( $value ) ) {
			return array( 'latin' );
		}
		$valid = \PhantomCore\Fonts::instance()->get_subsets();
		return array_intersect( $value, $valid );
	}
}

if ( ! function_exists( 'phantom_sanitize_headings_json' ) ) {
	function phantom_sanitize_headings_json( $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}
		$allowed = array( 'size', 'line_height', 'weight', 'spacing' );
		$heads   = array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' );
		$result  = array();
		foreach ( $heads as $h ) {
			if ( isset( $value[ $h ] ) && is_array( $value[ $h ] ) ) {
				$result[ $h ] = array_intersect_key( $value[ $h ], array_flip( $allowed ) );
			}
		}
		return $result;
	}
}

// JS minification: Run `npm run build` before deployment. Custom control JS files in admin/js/custom-controls/

/**
 * Add WooCommerce template path override for SPA shell compatibility.
 */
add_filter( 'woocommerce_locate_template', function ( string $template, string $template_name, string $template_path ): string {
	$plugin_path = PHANTOM_CORE_PATH . 'woocommerce/' . $template_name;
	if ( file_exists( $plugin_path ) ) {
		return $plugin_path;
	}
	return $template;
}, 10, 3 );

/**
 * Clean up plugin data on uninstall.
 */
function phantom_uninstall(): void {
	$options = array(
		'phantom_core_settings',
		'phantom_core_version',
		'phantom_customizer_css',
		'theme_mods_phantom',
		'phantom_font_families',
		'phantom_webfonts_loader',
	);
	foreach ( $options as $option ) {
		delete_option( $option );
	}
}
