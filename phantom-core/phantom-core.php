<?php
/**
 * Plugin Name:       Phantom Core Framework
 * Plugin URI:        https://phantom.test
 * Description:       Core REST API layer for Phantom — settings registry, theme options, customizer, import/export, caching. Backend only — no frontend code.
 * Version:           2.0.0
 * Requires at least: 6.4
 * Requires PHP:      8.0
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

if ( version_compare( PHP_VERSION, '8.0', '<' ) ) {
	if ( is_admin() ) {
		add_action( 'admin_notices', function () {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Phantom Core requires PHP 8.0 or later. Please upgrade your PHP version.', 'phantom-core' ) . '</p></div>';
		} );
	}
	return; // Stop loading the plugin
}

define( 'PHANTOM_CORE_VERSION', '2.0.0' );
define( 'PHANTOM_CORE_FILE', __FILE__ );
define( 'PHANTOM_CORE_PATH', plugin_dir_path( __FILE__ ) );
define( 'PHANTOM_CORE_URL', plugin_dir_url( __FILE__ ) );
define( 'PHANTOM_THEME_URL', PHANTOM_CORE_URL . '../phantom-theme/' );
if ( ! defined( 'PHANTOM_DEV_MODE' ) ) {
	define( 'PHANTOM_DEV_MODE', false ); /** Set to true in wp-config.php to show legacy Customizer panels */
}

spl_autoload_register(
	function ( string $class ): void {
		$prefix = 'PhantomCore\\';
		$len    = strlen( $prefix );
		if ( strncmp( $prefix, $class, $len ) !== 0 ) {
			return;
		}
		$relative_class = substr( $class, $len );

		$pascal_to_kebab = function ($s) {
			$s = preg_replace( '/([a-z0-9])([A-Z])/', '$1-$2', $s );
			return preg_replace( '/([A-Z]+)([A-Z][a-z])/', '$1-$2', $s );
		};

		// Custom controls use includes/custom-controls/ with class-{name}.php naming
		$controls_prefix = 'Customizer\\Controls\\';
		if ( strncmp( $controls_prefix, $relative_class, strlen( $controls_prefix ) ) === 0 ) {
			$short = substr( $relative_class, strlen( $controls_prefix ) );
			$short = $pascal_to_kebab( $short );
			$file  = PHANTOM_CORE_PATH . 'includes/custom-controls/class-' . str_replace( '_', '-', strtolower( $short ) ) . '.php';
			if ( file_exists( $file ) ) {
				require_once $file;
				return;
			}
		}

		// Adapters use includes/adapters/ with class-{name}.php naming
		$adapters_prefix = 'Adapters\\';
		if ( strncmp( $adapters_prefix, $relative_class, strlen( $adapters_prefix ) ) === 0 ) {
			$short = substr( $relative_class, strlen( $adapters_prefix ) );
			$short = $pascal_to_kebab( $short );
			$file  = PHANTOM_CORE_PATH . 'includes/adapters/class-' . str_replace( '_', '-', strtolower( $short ) ) . '.php';
			if ( file_exists( $file ) ) {
				require_once $file;
				return;
			}
		}

		// Renderer uses includes/renderer/ with class-{name}.php naming
		$renderer_prefix = 'Renderer\\';
		if ( strncmp( $renderer_prefix, $relative_class, strlen( $renderer_prefix ) ) === 0 ) {
			$short = substr( $relative_class, strlen( $renderer_prefix ) );
			$short = $pascal_to_kebab( $short );
			$file  = PHANTOM_CORE_PATH . 'includes/renderer/class-' . str_replace( '_', '-', strtolower( $short ) ) . '.php';
			if ( file_exists( $file ) ) {
				require_once $file;
				return;
			}
		}

		// Design Providers (must come before Design\ to match sub-namespace first)
		$providers_prefix = 'Design\\Providers\\';
		if ( strncmp( $providers_prefix, $relative_class, strlen( $providers_prefix ) ) === 0 ) {
			$short = substr( $relative_class, strlen( $providers_prefix ) );
			$short = $pascal_to_kebab( $short );
			$base = str_replace( '_', '-', strtolower( $short ) );
			$file = PHANTOM_CORE_PATH . 'includes/Design/Providers/class-' . $base . '.php';
			if ( file_exists( $file ) ) {
				require_once $file;
				return;
			}
			$file = PHANTOM_CORE_PATH . 'includes/Design/Providers/interface-' . $base . '.php';
			if ( file_exists( $file ) ) {
				require_once $file;
				return;
			}
		}

		// Design System uses includes/Design/ with class-{name}.php naming
		$design_prefix = 'Design\\';
		if ( strncmp( $design_prefix, $relative_class, strlen( $design_prefix ) ) === 0 ) {
			$short = substr( $relative_class, strlen( $design_prefix ) );
			$short = $pascal_to_kebab( $short );
			$file  = PHANTOM_CORE_PATH . 'includes/Design/class-' . str_replace( '_', '-', strtolower( $short ) ) . '.php';
			if ( file_exists( $file ) ) {
				require_once $file;
				return;
			}
		}

		// Demo uses includes/Demo/ with class-{name}.php naming
		$demo_prefix = 'Demo\\';
		if ( strncmp( $demo_prefix, $relative_class, strlen( $demo_prefix ) ) === 0 ) {
			$short = substr( $relative_class, strlen( $demo_prefix ) );
			$short = $pascal_to_kebab( $short );
			$file  = PHANTOM_CORE_PATH . 'includes/Demo/class-' . str_replace( '_', '-', strtolower( $short ) ) . '.php';
			if ( file_exists( $file ) ) {
				require_once $file;
				return;
			}
		}

		// Layout uses includes/Layout/ with class-{name}.php naming
		$layout_prefix = 'Layout\\';
		if ( strncmp( $layout_prefix, $relative_class, strlen( $layout_prefix ) ) === 0 ) {
			$short = substr( $relative_class, strlen( $layout_prefix ) );
			$short = $pascal_to_kebab( $short );
			$file  = PHANTOM_CORE_PATH . 'includes/Layout/class-' . str_replace( '_', '-', strtolower( $short ) ) . '.php';
			if ( file_exists( $file ) ) {
				require_once $file;
				return;
			}
		}

		// Contracts use includes/contracts/ with interface-{name}.php naming
		$contracts_prefix = 'Contracts\\';
		if ( strncmp( $contracts_prefix, $relative_class, strlen( $contracts_prefix ) ) === 0 ) {
			$short = substr( $relative_class, strlen( $contracts_prefix ) );
			$short = preg_replace( '/Interface$/', '', $short );
			$short = $pascal_to_kebab( $short );
			$file  = PHANTOM_CORE_PATH . 'includes/contracts/interface-' . str_replace( '_', '-', strtolower( $short ) ) . '.php';
			if ( file_exists( $file ) ) {
				require_once $file;
				return;
			}
		}

		// Feature uses includes/Feature/ with class-{name}.php naming
		$feature_prefix = 'Feature\\';
		if ( strncmp( $feature_prefix, $relative_class, strlen( $feature_prefix ) ) === 0 ) {
			$short = substr( $relative_class, strlen( $feature_prefix ) );
			$short = $pascal_to_kebab( $short );
			$file  = PHANTOM_CORE_PATH . 'includes/Feature/class-' . str_replace( '_', '-', strtolower( $short ) ) . '.php';
			if ( file_exists( $file ) ) {
				require_once $file;
				return;
			}
			// Data files — only load if they look like class/interface files
			$data_file = PHANTOM_CORE_PATH . 'includes/Feature/data/' . $short . '.php';
			if ( file_exists( $data_file ) && preg_match( '/^(class|interface|trait)-/', $short ) ) {
				require_once $data_file;
				return;
			}
		}

// Component (singular) uses includes/Component/ with class-{name}.php naming
		$component_prefix = 'Component\\';
		if ( strncmp( $component_prefix, $relative_class, strlen( $component_prefix ) ) === 0 ) {
			$short = substr( $relative_class, strlen( $component_prefix ) );
			$short = $pascal_to_kebab( $short );
			$file  = PHANTOM_CORE_PATH . 'includes/Component/class-' . str_replace( '_', '-', strtolower( $short ) ) . '.php';
			if ( file_exists( $file ) ) {
				require_once $file;
				return;
			}
		}

		// Components uses includes/Components/ with class-{name}.php naming
		$components_prefix = 'Components\\';
		if ( strncmp( $components_prefix, $relative_class, strlen( $components_prefix ) ) === 0 ) {
			$short = substr( $relative_class, strlen( $components_prefix ) );
			$short = $pascal_to_kebab( $short );
			$file  = PHANTOM_CORE_PATH . 'includes/Components/class-' . str_replace( '_', '-', strtolower( $short ) ) . '.php';
			if ( file_exists( $file ) ) {
				require_once $file;
				return;
			}
		}

		// Animation uses includes/Animation/ with class-{name}.php naming
		$animation_prefix = 'Animation\\';
		if ( strncmp( $animation_prefix, $relative_class, strlen( $animation_prefix ) ) === 0 ) {
			$short = substr( $relative_class, strlen( $animation_prefix ) );
			$short = $pascal_to_kebab( $short );
			$file  = PHANTOM_CORE_PATH . 'includes/Animation/class-' . str_replace( '_', '-', strtolower( $short ) ) . '.php';
			if ( file_exists( $file ) ) {
				require_once $file;
				return;
			}
		}

		// Settings uses includes/settings/ with class-{name}.php naming
		$settings_prefix = 'Settings\\';
		if ( strncmp( $settings_prefix, $relative_class, strlen( $settings_prefix ) ) === 0 ) {
			$short = substr( $relative_class, strlen( $settings_prefix ) );
			$short = $pascal_to_kebab( $short );
			$file  = PHANTOM_CORE_PATH . 'includes/settings/class-' . str_replace( '_', '-', strtolower( $short ) ) . '.php';
			if ( file_exists( $file ) ) {
				require_once $file;
				return;
			}
		}

		// Manifest uses includes/Manifest/ with class-{name}.php naming
		$manifest_prefix = 'Manifest\\';
		if ( strncmp( $manifest_prefix, $relative_class, strlen( $manifest_prefix ) ) === 0 ) {
			$short = substr( $relative_class, strlen( $manifest_prefix ) );
			$short = $pascal_to_kebab( $short );
			$file  = PHANTOM_CORE_PATH . 'includes/Manifest/class-' . str_replace( '_', '-', strtolower( $short ) ) . '.php';
			if ( file_exists( $file ) ) {
				require_once $file;
				return;
			}
		}

		// Registry uses includes/Registry/ with class-{name}.php naming
		$registry_prefix = 'Registry\\';
		if ( strncmp( $registry_prefix, $relative_class, strlen( $registry_prefix ) ) === 0 ) {
			$short = substr( $relative_class, strlen( $registry_prefix ) );
			$short = $pascal_to_kebab( $short );
			$file  = PHANTOM_CORE_PATH . 'includes/Registry/class-' . str_replace( '_', '-', strtolower( $short ) ) . '.php';
			if ( file_exists( $file ) ) {
				require_once $file;
				return;
			}
		}

		// ViewModels use includes/ViewModels/ with {name}-view-model.php naming
		$viewmodels_prefix = 'ViewModels\\';
		if ( strncmp( $viewmodels_prefix, $relative_class, strlen( $viewmodels_prefix ) ) === 0 ) {
			$short = substr( $relative_class, strlen( $viewmodels_prefix ) );
			$short = preg_replace( '/_ViewModel$/', '', $short );
			$short = $pascal_to_kebab( $short );
			$file  = PHANTOM_CORE_PATH . 'includes/ViewModels/' . str_replace( '_', '-', strtolower( $short ) ) . '-view-model.php';
			if ( file_exists( $file ) ) {
				require_once $file;
				return;
			}
		}

		// Data namespace uses includes/data/ with class-{name}.php naming
		$data_prefix = 'Data\\';
		if ( strncmp( $data_prefix, $relative_class, strlen( $data_prefix ) ) === 0 ) {
			$short = substr( $relative_class, strlen( $data_prefix ) );
			$short = $pascal_to_kebab( $short );
			$file  = PHANTOM_CORE_PATH . 'includes/data/class-' . str_replace( '_', '-', strtolower( $short ) ) . '.php';
			if ( file_exists( $file ) ) {
				require_once $file;
				return;
			}
		}

		// Public uses includes/Public/ with class-{name}.php naming
		$public_prefix = 'Public\\';
		if ( strncmp( $public_prefix, $relative_class, strlen( $public_prefix ) ) === 0 ) {
			$short = substr( $relative_class, strlen( $public_prefix ) );
			$short = $pascal_to_kebab( $short );
			$file  = PHANTOM_CORE_PATH . 'includes/Public/class-' . str_replace( '_', '-', strtolower( $short ) ) . '.php';
			if ( file_exists( $file ) ) {
				require_once $file;
				return;
			}
		}

		// Hook uses includes/Hook/ with class-{name}.php naming
		$hook_prefix = 'Hook\\';
		if ( strncmp( $hook_prefix, $relative_class, strlen( $hook_prefix ) ) === 0 ) {
			$short = substr( $relative_class, strlen( $hook_prefix ) );
			$short = $pascal_to_kebab( $short );
			$file  = PHANTOM_CORE_PATH . 'includes/Hook/class-' . str_replace( '_', '-', strtolower( $short ) ) . '.php';
			if ( file_exists( $file ) ) {
				require_once $file;
				return;
			}
		}

		// Bridges uses includes/Bridges/ with class-{name}.php naming
		$bridges_prefix = 'Bridges\\';
		if ( strncmp( $bridges_prefix, $relative_class, strlen( $bridges_prefix ) ) === 0 ) {
			$short = substr( $relative_class, strlen( $bridges_prefix ) );
			$short = $pascal_to_kebab( $short );
			$file  = PHANTOM_CORE_PATH . 'includes/Bridges/class-' . str_replace( '_', '-', strtolower( $short ) ) . '.php';
			if ( file_exists( $file ) ) {
				require_once $file;
				return;
			}
			// Fallback: try raw lowercase in case kebab split is wrong (e.g. WooCommerce)
			$file = PHANTOM_CORE_PATH . 'includes/Bridges/class-' . str_replace( '_', '-', strtolower( substr( $relative_class, strlen( $bridges_prefix ) ) ) ) . '.php';
			if ( file_exists( $file ) ) {
				require_once $file;
				return;
			}
		}

		// Compatibility uses includes/Compatibility/ with class-{name}.php naming
		$compatibility_prefix = 'Compatibility\\';
		if ( strncmp( $compatibility_prefix, $relative_class, strlen( $compatibility_prefix ) ) === 0 ) {
			$short = substr( $relative_class, strlen( $compatibility_prefix ) );
			$short = $pascal_to_kebab( $short );
			$file  = PHANTOM_CORE_PATH . 'includes/Compatibility/class-' . str_replace( '_', '-', strtolower( $short ) ) . '.php';
			if ( file_exists( $file ) ) {
				require_once $file;
				return;
			}
			// Fallback: try raw lowercase in case kebab split is wrong (e.g. RankMath)
			$file = PHANTOM_CORE_PATH . 'includes/Compatibility/class-' . str_replace( '_', '-', strtolower( substr( $relative_class, strlen( $compatibility_prefix ) ) ) ) . '.php';
			if ( file_exists( $file ) ) {
				require_once $file;
				return;
			}
		}

		// Upgrade uses includes/Upgrade/ with class-{name}.php naming
		$upgrade_prefix = 'Upgrade\\';
		if ( strncmp( $upgrade_prefix, $relative_class, strlen( $upgrade_prefix ) ) === 0 ) {
			$short = substr( $relative_class, strlen( $upgrade_prefix ) );
			$short = $pascal_to_kebab( $short );
			$file  = PHANTOM_CORE_PATH . 'includes/Upgrade/class-' . str_replace( '_', '-', strtolower( $short ) ) . '.php';
			if ( file_exists( $file ) ) {
				require_once $file;
				return;
			}
		}

		// Injectors uses includes/Engine/Injectors/ with class-{name}.php naming
		$injectors_prefix = 'Engine\\Injectors\\';
		if ( strncmp( $injectors_prefix, $relative_class, strlen( $injectors_prefix ) ) === 0 ) {
			$short = substr( $relative_class, strlen( $injectors_prefix ) );
			$short = $pascal_to_kebab( $short );
			$file  = PHANTOM_CORE_PATH . 'includes/Engine/Injectors/class-' . str_replace( '_', '-', strtolower( $short ) ) . '.php';
			if ( file_exists( $file ) ) {
				require_once $file;
				return;
			}
		}

		// History uses includes/History/ with class-{name}.php naming
		$history_prefix = 'History\\';
		if ( strncmp( $history_prefix, $relative_class, strlen( $history_prefix ) ) === 0 ) {
			$short = substr( $relative_class, strlen( $history_prefix ) );
			$short = $pascal_to_kebab( $short );
			$file  = PHANTOM_CORE_PATH . 'includes/History/class-' . str_replace( '_', '-', strtolower( $short ) ) . '.php';
			if ( file_exists( $file ) ) {
				require_once $file;
				return;
			}
		}

		// Assets uses includes/Assets/ with class-{name}.php naming
		$assets_prefix = 'Assets\\';
		if ( strncmp( $assets_prefix, $relative_class, strlen( $assets_prefix ) ) === 0 ) {
			$short = substr( $relative_class, strlen( $assets_prefix ) );
			$short = $pascal_to_kebab( $short );
			$file  = PHANTOM_CORE_PATH . 'includes/Assets/class-' . str_replace( '_', '-', strtolower( $short ) ) . '.php';
			if ( file_exists( $file ) ) {
				require_once $file;
				return;
			}
		}

		// Setup uses includes/Setup/ with class-{name}.php naming
		$setup_prefix = 'Setup\\';
		if ( strncmp( $setup_prefix, $relative_class, strlen( $setup_prefix ) ) === 0 ) {
			$short = substr( $relative_class, strlen( $setup_prefix ) );
			$short = $pascal_to_kebab( $short );
			$file  = PHANTOM_CORE_PATH . 'includes/Setup/class-' . str_replace( '_', '-', strtolower( $short ) ) . '.php';
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
require_once PHANTOM_CORE_PATH . 'includes/class-preset-compatibility-bridge.php';
require_once PHANTOM_CORE_PATH . 'includes/class-custom-css.php';
require_once PHANTOM_CORE_PATH . 'includes/class-phantom-global-palette.php';
require_once PHANTOM_CORE_PATH . 'includes/class-phantom-version-compatibility.php';
// Must load before class-fonts.php (Fonts references Phantom_Font_Families)
require_once PHANTOM_CORE_PATH . 'includes/class-phantom-font-families.php';
require_once PHANTOM_CORE_PATH . 'includes/class-fonts.php';
require_once PHANTOM_CORE_PATH . 'includes/class-phantom-webfont-loader.php';
require_once PHANTOM_CORE_PATH . 'includes/partial-renderers.php';
require_once PHANTOM_CORE_PATH . 'includes/class-helpers.php';
require_once PHANTOM_CORE_PATH . 'includes/class-capability-manager.php';
require_once PHANTOM_CORE_PATH . 'includes/custom-css/colors.php';
require_once PHANTOM_CORE_PATH . 'includes/custom-css/typography.php';
require_once PHANTOM_CORE_PATH . 'includes/custom-css/header.php';
require_once PHANTOM_CORE_PATH . 'includes/custom-css/footer.php';
require_once PHANTOM_CORE_PATH . 'includes/custom-css/layout.php';
require_once PHANTOM_CORE_PATH . 'includes/custom-css/buttons.php';
require_once PHANTOM_CORE_PATH . 'includes/custom-css/product.php';
require_once PHANTOM_CORE_PATH . 'includes/custom-css/responsive.php';
require_once PHANTOM_CORE_PATH . 'includes/custom-css/hero.php';
// Phase 1: Service Container + Event System
require_once PHANTOM_CORE_PATH . 'includes/Engine/Container.php';
require_once PHANTOM_CORE_PATH . 'includes/Engine/Container_Config.php';
require_once PHANTOM_CORE_PATH . 'includes/Engine/RequestRouter.php';
require_once PHANTOM_CORE_PATH . 'includes/Engine/ResponseBuilder.php';
require_once PHANTOM_CORE_PATH . 'includes/Engine/Render_Engine.php';
require_once PHANTOM_CORE_PATH . 'includes/Engine/EventDispatcher.php';
require_once PHANTOM_CORE_PATH . 'includes/Engine/PhpEventStore.php';
require_once PHANTOM_CORE_PATH . 'includes/Engine/Data_Engine.php';
require_once PHANTOM_CORE_PATH . 'includes/Engine/View_Engine.php';
require_once PHANTOM_CORE_PATH . 'includes/Engine/Asset_Engine.php';
require_once PHANTOM_CORE_PATH . 'includes/Engine/WooCommerce_Injector.php';
require_once PHANTOM_CORE_PATH . 'admin/class-settings-page.php';
// Phase 5B: Feature Flags
require_once PHANTOM_CORE_PATH . 'includes/Feature/class-feature.php';
require_once PHANTOM_CORE_PATH . 'includes/Feature/class-feature-registry.php';
require_once PHANTOM_CORE_PATH . 'includes/Feature/class-feature-manager.php';
// Phase 5D: Component & Template Registries
require_once PHANTOM_CORE_PATH . 'includes/Components/class-component.php';
require_once PHANTOM_CORE_PATH . 'includes/Components/class-component-registry.php';
require_once PHANTOM_CORE_PATH . 'includes/Components/class-component-manager.php';
require_once PHANTOM_CORE_PATH . 'includes/Registry/class-template.php';
require_once PHANTOM_CORE_PATH . 'includes/Registry/class-template-registry.php';
// Phase 5.5: Theme Manifest (ChatGPT P7)
require_once PHANTOM_CORE_PATH . 'includes/Manifest/class-theme-manifest.php';
// Phase 5A: Animation Registry
require_once PHANTOM_CORE_PATH . 'includes/Animation/class-animation.php';
require_once PHANTOM_CORE_PATH . 'includes/Animation/class-animation-registry.php';
require_once PHANTOM_CORE_PATH . 'includes/Animation/class-gsap-bridge.php';
require_once PHANTOM_CORE_PATH . 'includes/Animation/class-three-bridge.php';
require_once PHANTOM_CORE_PATH . 'includes/Bridges/class-swiper-bridge.php';
require_once PHANTOM_CORE_PATH . 'includes/Animation/class-scroll-reveal.php';
require_once PHANTOM_CORE_PATH . 'includes/Animation/class-parallax.php';

// Phase 6: History & Versioning System
if ( class_exists( '\PhantomCore\History\History_Manager' ) ) {
	\PhantomCore\History\History_Manager::get_instance()->init();
	if ( is_admin() ) {
		add_action( 'rest_api_init', function () {
			\PhantomCore\History\History_Rest::get_instance()->register_routes();
		}, 15 );
	}
}

// Phase D: Plugin Bridges — register all before init_all()
$bridge_mgr = \PhantomCore\Bridges\Bridge_Manager::get_instance();
$bridge_mgr->register(new \PhantomCore\Bridges\WooCommerce_Bridge());
$bridge_mgr->register(new \PhantomCore\Bridges\Swiper_Bridge());
$bridge_mgr->register(new \PhantomCore\Animation\Three_Bridge());
$bridge_mgr->register(new \PhantomCore\Bridges\Wishlist_Bridge());
$bridge_mgr->register(new \PhantomCore\Bridges\Mailchimp_Bridge());
$bridge_mgr->register(new \PhantomCore\Compatibility\Gutenberg_Bridge());
$bridge_mgr->register(new \PhantomCore\Compatibility\Elementor_Bridge());
$bridge_mgr->register(new \PhantomCore\Compatibility\WPML_Bridge());
$bridge_mgr->register(new \PhantomCore\Compatibility\RankMath_Bridge());
$bridge_mgr->register(new \PhantomCore\Compatibility\Yoast_Bridge());
$bridge_mgr->register(new \PhantomCore\Compatibility\CF7_Bridge());
$bridge_mgr->init_all();

// Pre-register the textdomain with an empty .mo to prevent
// _load_textdomain_just_in_time notices in WP 6.7+ when __()
// is called during bootstrap (Settings_Registry entries).
$td_empty = PHANTOM_CORE_PATH . 'languages/empty.mo';
if ( file_exists( $td_empty ) ) {
	load_textdomain( 'phantom-core', $td_empty );
}
// Register textdomain properly on plugins_loaded (WP 6.7+ requires it on init).
add_action( 'plugins_loaded', function () {
	load_plugin_textdomain(
		'phantom-core',
		false,
		dirname( plugin_basename( PHANTOM_CORE_FILE ) ) . '/languages'
	);
	$td_locale = determine_locale();
	$td_mofile = PHANTOM_CORE_PATH . 'languages/phantom-core-' . $td_locale . '.mo';
	if ( file_exists( $td_mofile ) ) {
		load_textdomain( 'phantom-core', $td_mofile );
	}
}, 1 );

$rest_path = PHANTOM_CORE_PATH . 'includes/class-rest-controller.php';
if ( file_exists( $rest_path ) ) {
	\PhantomCore\Api\Rest_Controller::get_instance()->init();
}

$settings_page_path = PHANTOM_CORE_PATH . 'admin/class-settings-page.php';
if ( file_exists( $settings_page_path ) ) {
	\PhantomCore\Admin\Settings_Page::get_instance()->init();
}

// Phase 5B: Feature Flags initialization
\PhantomCore\Feature\Feature_Manager::get_instance()->init();

$font_download_page_path = PHANTOM_CORE_PATH . 'admin/class-font-download-page.php';
if ( file_exists( $font_download_page_path ) ) {
	require_once $font_download_page_path;
	\Phantom_Font_Download_Page::instance()->init();
}

$demo_admin_path = PHANTOM_CORE_PATH . 'admin/class-demo-admin.php';
if ( is_admin() && file_exists( $demo_admin_path ) ) {
	require_once $demo_admin_path;
	\PhantomCore\Admin\Demo_Admin::get_instance()->init();
}

// Phase 4C: Phantom Admin Menu (requires all page classes)
if ( is_admin() ) {
	$phantom_admin_files = [
		'admin/class-phantom-admin.php',
		'admin/class-dashboard-page.php',
		'admin/class-design-studio-page.php',
		'admin/class-component-library-page.php',
		'admin/class-template-manager-page.php',
		'admin/class-animation-studio-page.php',
		'admin/class-asset-manager-page.php',
		'admin/class-performance-page.php',
		'admin/class-seo-page.php',
		'admin/class-import-export-page.php',
		'admin/class-backup-restore-page.php',
		'admin/class-developer-page.php',
		'admin/class-system-page.php',
		'admin/class-customizer-design-panel.php',
	];
	foreach ( $phantom_admin_files as $f ) {
		$path = PHANTOM_CORE_PATH . $f;
		if ( file_exists( $path ) ) {
			require_once $path;
		}
	}
	\PhantomCore\Admin\PhantomAdmin::get_instance()->init();
	\PhantomCore\Admin\DesignStudioPage::get_instance()->init();
}

$cache_path = PHANTOM_CORE_PATH . 'includes/Engine/Cache.php';
if ( file_exists( $cache_path ) ) {
	require_once $cache_path;
	\PhantomCore\Engine\Cache::get_instance()->init();
}

$shell_path = PHANTOM_CORE_PATH . 'templates/shell.php';
if ( file_exists( $shell_path ) ) {
	require_once $shell_path;
}
add_action('plugins_loaded', function(): void {
	\PhantomCore\Shell::get_instance()->init();
}, 0);

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

// Phase 4: Design System Engine
add_action(
	'plugins_loaded',
	function (): void {
		\PhantomCore\Design\DesignSystemManager::get_instance()->init();
	},
	20
);

// Phase 5C: Backup & Restore admin-post handlers
add_action(
	'plugins_loaded',
	function (): void {
		if ( is_admin() ) {
			\PhantomCore\Admin\BackupRestorePage::get_instance()->init();
		}
	},
	10
);

// Phase 5D: Component & Template Registries initialization
add_action(
	'plugins_loaded',
	function (): void {
		\PhantomCore\Components\Component_Manager::get_instance()->init();
		\PhantomCore\Registry\Template_Registry::get_instance()->register_defaults();
	},
	25
);

register_activation_hook(
	__FILE__,
	function (): void {
		add_option( 'phantom_active_demo', 'kids' );
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

	$url = \PhantomCore\Fonts::instance()->get_enqueue_url( $fonts );

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

// Initialize Activation Wizard on admin
add_action( 'init', function (): void {
	if ( is_admin() && ! wp_doing_ajax() && ! defined( 'DOING_AJAX' ) ) {
		if ( class_exists( '\PhantomCore\Setup\Activation_Wizard' ) ) {
			new \PhantomCore\Setup\Activation_Wizard();
		}
	}
} );

/**
 * Get image URL with fallback to default plugin image.
 *
 * Returns the uploaded Customizer image if set, otherwise falls back
 * to the default image bundled with the plugin.
 *
 * @param string $option_key   The option key storing the uploaded URL.
 * @param string $default_path Relative path inside plugin (e.g. 'frontend/assets/images/logo.png').
 * @return string The image URL (uploaded or default).
 */
function phantom_get_image_with_default( string $option_key, string $default_path ): string {
	$uploaded = get_option( $option_key, '' );
	if ( '' !== $uploaded && filter_var( $uploaded, FILTER_VALIDATE_URL ) ) {
		return $uploaded;
	}
	return PHANTOM_CORE_URL . $default_path;
}

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
