<?php
declare(strict_types=1);

namespace PhantomCore;

use PhantomCore\Customizer\Controls\Asset_Grid_Control;
use PhantomCore\Customizer\Controls\Control_Base;
use PhantomCore\Customizer\Controls\Visual_Inspector_Control;
use PhantomCore\Customizer\Controls\Visual_Toggle_Control;

defined( 'ABSPATH' ) || exit;

/**
 * Customizer — the single theme editor (Phase A).
 *
 * The native WordPress Customizer (Appearance → Customize) is the one and
 * only editing surface. This class registers a small curated section list
 * plus the PHANTOM panel (Visual Colors, Typography, Spacing, Assets,
 * Live Preview, Element Inspector). Click-to-edit is handled by the
 * selection engine inside the preview iframe and customizer-visual-editor.js
 * in the sidebar (see docs/superpowers/specs/2026-07-31-visual-customizer-design.md).
 */
class Customizer {

	private static ?self $instance = null;

	private ?Settings_Registry $registry = null;

	/** @var array<string, array{title: string, keys: string[]}> */
	private array $curated_sections = array();

	/** @var array<string, array{title: string, keys: string[]}> */
	private array $visual_sections = array();

	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->registry = Settings_Registry::get_instance();
	}

	public function init(): void {
		add_action( 'customize_register', array( $this, 'register' ) );
		add_action( 'customize_preview_init', array( $this, 'preview_js' ) );
		add_action( 'customize_controls_enqueue_scripts', array( $this, 'controls_js' ) );
		add_action( 'customize_save_after', array( $this, 'sync_options' ) );
		add_action( 'wp_head', array( $this, 'output_inline_css' ), 100 );
	}

	/**
	 * Curated top-level sections (keys verified against the Settings Registry).
	 */
	private function curated_sections(): array {
		if ( empty( $this->curated_sections ) ) {
			$this->curated_sections = array(
				'phantom_section_homepage' => array(
					'title' => __( 'Homepage', 'phantom-core' ),
					'keys'  => array( 'home_banner_enable', 'home_banner_heading', 'home_banner_title', 'home_banner_description', 'home_banner_btn_text' ),
				),
				'phantom_section_blog'     => array(
					'title' => __( 'Blog', 'phantom-core' ),
					'keys'  => array( 'blog_layout', 'blog_sidebar_position', 'blog_show_sidebar', 'blog_posts_per_page' ),
				),
				'phantom_section_woocommerce' => array(
					'title' => __( 'WooCommerce', 'phantom-core' ),
					'keys'  => array( 'shop_catalog_mode', 'shop_minicart_enable', 'shop_ajax_add_to_cart', 'shop_wishlist_enable', 'shop_checkout_style' ),
				),
			);
		}
		return $this->curated_sections;
	}

	/**
	 * PHANTOM panel sections.
	 */
	private function visual_sections(): array {
		if ( empty( $this->visual_sections ) ) {
			$this->visual_sections = array(
				'phantom_section_colors'     => array(
					'title' => __( 'Visual Colors', 'phantom-core' ),
					'keys'  => array( 'color_primary', 'color_secondary', 'color_accent', 'color_success', 'color_error' ),
				),
				'phantom_section_typography' => array(
					'title' => __( 'Typography', 'phantom-core' ),
					'keys'  => array( 'typography_heading_font', 'typography_body_font', 'typography_base_size', 'typography_heading_weight' ),
				),
				'phantom_section_spacing'    => array(
					'title' => __( 'Spacing', 'phantom-core' ),
					'keys'  => array( 'container_width', 'content_width', 'sidebar_width' ),
				),
			);
		}
		return $this->visual_sections;
	}

	/**
	 * @param \WP_Customize_Manager $wp_customize
	 */
	public function register( $wp_customize ): void {
		if ( class_exists( Control_Base::class ) ) {
			Control_Base::register_all( $wp_customize );
		}

		$priority = 10;
		foreach ( $this->curated_sections() as $id => $cfg ) {
			$this->register_section( $wp_customize, $id, $cfg['title'], $cfg['keys'], '', $priority );
			$priority += 10;
		}

		$wp_customize->add_panel(
			'phantom_visual',
			array(
				'title'       => __( 'PHANTOM', 'phantom-core' ),
				'description' => __( 'Visual editing for the PHANTOM theme. Enable Start Editing, then click any element in the preview to edit it.', 'phantom-core' ),
				'priority'    => 150,
			)
		);

		$priority = 10;
		foreach ( $this->visual_sections() as $id => $cfg ) {
			$this->register_section( $wp_customize, $id, $cfg['title'], $cfg['keys'], 'phantom_visual', $priority );
			$priority += 10;
		}

		$wp_customize->add_section(
			'phantom_section_assets',
			array(
				'title'       => __( 'Visual Assets', 'phantom-core' ),
				'panel'       => 'phantom_visual',
				'priority'    => 40,
				'description' => __( 'Upload replacements for site media. Reset restores the default asset.', 'phantom-core' ),
			)
		);
		$wp_customize->add_control(
			new Asset_Grid_Control(
				$wp_customize,
				'phantom_visual_assets',
				array(
					'section'  => 'phantom_section_assets',
					'settings' => array(),
				)
			)
		);

		$wp_customize->add_section(
			'phantom_section_live_preview',
			array(
				'title'    => __( 'Live Preview', 'phantom-core' ),
				'panel'    => 'phantom_visual',
				'priority' => 60,
			)
		);
		$wp_customize->add_control(
			new Visual_Toggle_Control(
				$wp_customize,
				'phantom_live_preview_edit',
				array(
					'section'  => 'phantom_section_live_preview',
					'settings' => array(),
				)
			)
		);

		$wp_customize->add_section(
			'phantom_section_inspector',
			array(
				'title'    => __( 'Element Inspector', 'phantom-core' ),
				'panel'    => 'phantom_visual',
				'priority' => 70,
			)
		);
		$wp_customize->add_control(
			new Visual_Inspector_Control(
				$wp_customize,
				'phantom_visual_inspector',
				array(
					'section'  => 'phantom_section_inspector',
					'settings' => array(),
				)
			)
		);
	}

	/**
	 * Register one section plus controls for every curated setting key.
	 *
	 * @param \WP_Customize_Manager $wp_customize
	 * @param string                $id         Section ID.
	 * @param string                $title      Section title.
	 * @param string[]              $keys       Setting keys (must exist in the Settings Registry).
	 * @param string                $panel      Optional panel ID.
	 * @param int                   $priority   Section priority.
	 */
	private function register_section( $wp_customize, string $id, string $title, array $keys, string $panel = '', int $priority = 10 ): void {
		$args = array(
			'title'    => $title,
			'priority' => $priority,
		);
		if ( $panel ) {
			$args['panel'] = $panel;
		}
		$wp_customize->add_section( $id, $args );

		$entries = $this->registry->get_entries();
		$prio    = 10;

		foreach ( $keys as $key ) {
			$prio += 10;
			$entry = $entries[ $key ] ?? null;
			if ( ! is_array( $entry ) ) {
				continue;
			}

			$setting_id = 'phantom_' . $key;
			$type       = $entry['type'] ?? 'text';
			$transport  = $entry['transport'] ?? 'refresh';

			$wp_customize->add_setting(
				$setting_id,
				array(
					'default'           => $entry['default'] ?? '',
					'type'              => 'option',
					'sanitize_callback' => $entry['sanitize'] ?? 'sanitize_text_field',
					'transport'         => $transport,
				)
			);

			$label = $entry['label'] ?? ucwords( str_replace( '_', ' ', $key ) );
			$cargs = array(
				'section'  => $id,
				'label'    => $label,
				'priority' => $prio,
			);

			if ( in_array( $type, array( 'text', 'string', 'textarea' ), true ) ) {
				$cargs['type'] = ( 'textarea' === $type ) ? 'textarea' : 'text';
			} else {
				$cargs['type'] = $type;
			}

			if ( in_array( $type, array( 'ast-select', 'select' ), true ) ) {
				$cargs['choices'] = $entry['choices'] ?? $entry['options'] ?? array();
			}

			$wp_customize->add_control( $setting_id, $cargs );
		}
	}

	public function preview_js(): void {
		$ver = defined( 'PHANTOM_CORE_VERSION' ) ? PHANTOM_CORE_VERSION : '2.0.0';
		wp_enqueue_script(
			'phantom-customizer-preview',
			PHANTOM_CORE_URL . 'admin/js/customizer-preview.js',
			array( 'jquery', 'customize-preview' ),
			$ver,
			true
		);

		$css_var_map     = Settings_Registry::get_css_var_map();
		$all_px_keys     = Settings_Registry::get_px_keys();
		$px_keys         = array();
		$responsive_keys = array();
		foreach ( array_keys( $css_var_map ) as $key ) {
			if ( in_array( $key, $all_px_keys, true ) ) {
				$px_keys[] = $key;
			}
		}
		foreach ( $this->registry->get_entries() as $ekey => $entry ) {
			if ( ! empty( $entry['responsive'] ) && isset( $css_var_map[ $ekey ] ) ) {
				$responsive_keys[] = $ekey;
			}
		}
		wp_localize_script(
			'phantom-customizer-preview',
			'PhantomCustomizer',
			array(
				'cssVarMap'      => $css_var_map,
				'cssVarKeys'     => array_keys( $css_var_map ),
				'cssVarPxKeys'   => $px_keys,
				'responsiveKeys' => $responsive_keys,
				'restUrl'        => rest_url(),
				'defaultImages'  => array(
					'heroDesktop' => PHANTOM_CORE_URL . 'frontend/assets/images/banner-bg-img.png',
					'heroTablet'  => PHANTOM_CORE_URL . 'frontend/assets/images/banner-bg-img.png',
					'heroMobile'  => PHANTOM_CORE_URL . 'frontend/assets/images/banner-bg-img.png',
					'logo'        => PHANTOM_CORE_URL . 'frontend/assets/images/logo.png',
					'favicon'     => PHANTOM_CORE_URL . 'frontend/assets/images/favicon/favicon.ico',
				),
			)
		);
	}

	public function controls_js(): void {
		$ver = defined( 'PHANTOM_CORE_VERSION' ) ? PHANTOM_CORE_VERSION : '2.0.0';

		wp_enqueue_media();
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style(
			'phantom-customizer-visual',
			PHANTOM_CORE_URL . 'admin/css/customizer-visual-editor.css',
			array(),
			$ver
		);

		wp_enqueue_script(
			'phantom-customizer-conditionals',
			PHANTOM_CORE_URL . 'admin/js/customizer-conditionals.js',
			array( 'customize-controls' ),
			$ver,
			true
		);

		wp_enqueue_script(
			'phantom-customizer-visual',
			PHANTOM_CORE_URL . 'admin/js/customizer-visual-editor.js',
			array( 'customize-controls', 'wp-color-picker', 'jquery' ),
			$ver,
			true
		);

		wp_localize_script(
			'phantom-customizer-visual',
			'PhantomVisualEditor',
			array(
				'restUrl'     => esc_url_raw( rest_url( 'phantom/v1' ) ),
				'nonce'       => wp_create_nonce( 'phantom_api' ),
				'wpRestNonce' => wp_create_nonce( 'wp_rest' ),
				'varMap'      => Settings_Registry::get_css_var_map(),
			)
		);
	}

	/**
	 * Safety-net inline CSS (the CSS Generation Engine is the primary path).
	 * Kept at wp_head priority 100 as in previous releases.
	 */
	public function get_inline_css(): string {
		$css = '';
		foreach ( Settings_Registry::get_css_var_map() as $key => $var ) {
			$value = $this->registry->get( $key );
			if ( null !== $value && '' !== $value && is_scalar( $value ) ) {
				$css .= $var . ':' . $value . ';';
			}
		}
		return $css ? ':root{' . $css . '}' : '';
	}

	public function output_inline_css(): void {
		if ( is_customize_preview() ) {
			return;
		}
		$css = $this->get_inline_css();
		if ( $css ) {
			echo '<style id="phantom-inline-css">' . "\n" . $css . "\n" . '</style>';
		}
	}

	/**
	 * Post-save pipeline: history snapshot, design state commit, cache flush,
	 * asset pipeline build. Mirrors the /publish REST endpoint (best-effort).
	 */
	public function sync_options(): void {
		if ( class_exists( '\PhantomCore\History\History_Manager' ) ) {
			try {
				$autosave = \PhantomCore\History\History_Autosave::get_instance();
				$settings = $autosave->capture_current_settings();
				\PhantomCore\History\History_Manager::get_instance()->create_snapshot(
					$settings,
					'customizer_save',
					__( 'Saved via Customizer', 'phantom-core' )
				);
			} catch ( \Throwable $e ) {
				// Non-fatal: history is best-effort.
			}
		}

		if ( class_exists( '\PhantomCore\Design\ThemeStateEngine' ) ) {
			try {
				\PhantomCore\Design\ThemeStateEngine::get_instance()->commit_preview();
			} catch ( \Throwable $e ) {
				// Non-fatal.
			}
		}

		if ( class_exists( '\Phantom_Custom_CSS' ) ) {
			\Phantom_Custom_CSS::flush_cache();
		}
		delete_transient( 'phantom_page_data_v2' );

		if ( class_exists( '\PhantomCore\Asset\Pipeline\Pipeline' ) ) {
			try {
				\PhantomCore\Asset\Pipeline\Pipeline::get_instance()->execute( 'css', array( 'profile' => 'production' ) );
			} catch ( \Throwable $e ) {
				// Non-fatal: settings already committed.
			}
		}
	}
}
