<?php
/**
 * Plugin Name:       Phantom Core Framework
 * Plugin URI:        https://phantom.test
 * Description:       Core REST API layer for Phantom — settings registry, theme options, customizer, import/export, caching. Backend only — no frontend code.
 * Version:           1.0.2
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Author:            Phantom
 * Text Domain:       phantom-core
 * Domain Path:       /languages
 *
 * @package PhantomCore
 */

declare(strict_types=1);

namespace PhantomCore;

defined( 'ABSPATH' ) || exit;

define( 'PHANTOM_CORE_VERSION', '1.0.2' );
define( 'PHANTOM_CORE_FILE', __FILE__ );
define( 'PHANTOM_CORE_PATH', plugin_dir_path( __FILE__ ) );
define( 'PHANTOM_CORE_URL', plugin_dir_url( __FILE__ ) );

spl_autoload_register(
	function ( string $class ): void {
		$prefix = 'PhantomCore\\';
		$len    = strlen( $prefix );
		if ( strncmp( $prefix, $class, $len ) !== 0 ) {
			return;
		}
		$relative_class = substr( $class, $len );
		$file           = PHANTOM_CORE_PATH . 'includes/' . str_replace( '\\', '/', $relative_class ) . '.php';
		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
);

require_once PHANTOM_CORE_PATH . 'includes/class-settings-registry.php';
require_once PHANTOM_CORE_PATH . 'includes/class-core-plugin.php';
require_once PHANTOM_CORE_PATH . 'includes/class-rest-controller.php';
require_once PHANTOM_CORE_PATH . 'includes/class-customizer.php';
require_once PHANTOM_CORE_PATH . 'admin/class-settings-page.php';

$rest_path = PHANTOM_CORE_PATH . 'includes/class-rest-controller.php';
if ( file_exists( $rest_path ) ) {
	\PhantomCore\Api\Rest_Controller::get_instance()->init();
}

$settings_page_path = PHANTOM_CORE_PATH . 'admin/class-settings-page.php';
if ( file_exists( $settings_page_path ) ) {
	\PhantomCore\Admin\Settings_Page::get_instance()->init();
}

$cache_path = PHANTOM_CORE_PATH . 'includes/Engine/Cache.php';
if ( file_exists( $cache_path ) ) {
	require_once $cache_path;
}
\PhantomCore\Engine\Cache::get_instance()->init();

$shell_path = PHANTOM_CORE_PATH . 'templates/shell.php';
if ( file_exists( $shell_path ) ) {
	require_once $shell_path;
	\PhantomCore\Shell::get_instance()->init();
}

add_action(
	'plugins_loaded',
	function (): void {
		load_plugin_textdomain(
			'phantom-core',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages'
		);
	},
	1
);

add_action(
	'plugins_loaded',
	function (): void {
		Plugin::get_instance()->init();
	},
	5
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

/**
 * Enqueue Google Fonts based on selected typography settings.
 */
function phantom_enqueue_google_fonts(): void {
	$options     = get_option( 'phantom_options', array() );
	$body_font   = $options['typography_body_font'] ?? 'Archivo';
	$heading_font = $options['typography_heading_font'] ?? 'Playfair Display';

	$fonts = array();
	if ( $body_font && 'Archivo' !== $body_font ) {
		$fonts[] = rawurlencode( $body_font ) . ':wght@100;200;300;400;500;600;700;800;900';
	}
	if ( $heading_font && 'Playfair Display' !== $heading_font ) {
		$fonts[] = rawurlencode( $heading_font ) . ':wght@100;200;300;400;500;600;700;800;900';
	}

	if ( empty( $fonts ) ) {
		$fonts[] = 'Archivo:wght@100;200;300;400;500;600;700;800;900';
		$fonts[] = 'Playfair+Display:wght@100;200;300;400;500;600;700;800;900';
	}

	$family = implode( '&family=', $fonts );
	$url    = 'https://fonts.googleapis.com/css2?family=' . $family . '&display=swap';

	wp_enqueue_style(
		'phantom-google-fonts',
		$url,
		array(),
		PHANTOM_CORE_VERSION
	);
}

add_action( 'wp_enqueue_scripts', 'PhantomCore\\phantom_enqueue_google_fonts', 9 );
