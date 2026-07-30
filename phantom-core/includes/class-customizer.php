<?php
declare(strict_types=1);

namespace PhantomCore;

use WP_Customize_Manager;
use PhantomCore\Customizer\Controls\Control_Base;

defined( 'ABSPATH' ) || exit;

class Customizer {

	private static ?Customizer $instance = null;
	private array $entries = array();
	private array $panels = array();
	private array $divider_controls = array();

	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function init(): void {
		$this->entries = Settings_Registry::get_instance()->get_entries();
		$this->panels = $this->define_panels();
		add_action( 'customize_register', array( $this, 'register' ) );
		add_action( 'customize_preview_init', array( $this, 'preview_js' ) );
		add_action( 'customize_controls_enqueue_scripts', array( $this, 'controls_js' ) );
		add_action( 'customize_save_after', array( $this, 'sync_options' ) );
		add_action( 'wp_head', array( $this, 'output_inline_css' ), 100 );
	}

	public function output_inline_css(): void {
		echo $this->get_inline_css();
	}

	public function define_panels(): array {
		// 7 global-only panels — component-level settings live in Design Studio.
		$design_studio_note = __( 'Component-level settings (header, footer, hero, products, etc.) are managed in the Design Studio.', 'phantom-core' );
		$panels = array(
			'phantom_branding'      => array(
				'title'       => 'Branding',
				'description' => $design_studio_note,
				'sections'    => array( 'branding' ),
				'priority'    => 10,
			),
			'phantom_global_colors' => array(
				'title'       => 'Global Colors',
				'description' => $design_studio_note,
				'sections'    => array( 'colors' ),
				'priority'    => 20,
			),
			'phantom_global_typography' => array(
				'title'       => 'Global Typography',
				'description' => $design_studio_note,
				'sections'    => array( 'typography' ),
				'priority'    => 30,
			),
			'phantom_presets'       => array(
				'title'       => 'Presets & Design System',
				'description' => $design_studio_note,
				'sections'    => array( 'design_tokens', 'design_system' ),
				'priority'    => 40,
			),
			'phantom_responsive'    => array(
				'title'       => 'Responsive',
				'description' => $design_studio_note,
				'sections'    => array( 'responsive' ),
				'priority'    => 50,
			),
			'phantom_performance'   => array(
				'title'       => 'Performance & SEO',
				'description' => $design_studio_note,
				'sections'    => array( 'performance', 'seo', 'template_pack' ),
				'priority'    => 60,
			),
			'phantom_integrations'  => array(
				'title'       => 'Integrations',
				'description' => $design_studio_note,
				'sections'    => array( 'integrations', 'custom_code' ),
				'priority'    => 70,
			),
		);

		// Legacy panels hidden behind PHANTOM_DEV_MODE — component-level settings
		// moved to Design Studio. Enable via wp-config.php:
		//   define('PHANTOM_DEV_MODE', true);
		if ( defined( 'PHANTOM_DEV_MODE' ) && PHANTOM_DEV_MODE ) {
			$legacy_panels = array(
				'phantom_header'        => array(
					'title'    => '[Dev] Header & Navigation',
					'sections' => array( 'header', 'topbar', 'navigation', 'announcement_bar' ),
					'priority' => 80,
				),
				'phantom_hero'          => array(
					'title'    => '[Dev] Hero & Home',
					'sections' => array( 'hero', 'home_sections', 'collections' ),
					'priority' => 90,
				),
				'phantom_products'      => array(
					'title'    => '[Dev] Products & Shop',
					'sections' => array( 'product_cards', 'shop_page', 'product_page' ),
					'priority' => 100,
				),
				'phantom_woocommerce'   => array(
					'title'    => '[Dev] WooCommerce',
					'sections' => array( 'woocommerce' ),
					'priority' => 110,
				),
				'phantom_blog'          => array(
					'title'    => '[Dev] Blog',
					'sections' => array( 'blog' ),
					'priority' => 120,
				),
				'phantom_footer'        => array(
					'title'    => '[Dev] Footer',
					'sections' => array( 'footer' ),
					'priority' => 130,
				),
				'phantom_colors'        => array(
					'title'    => '[Dev] Colors & Buttons',
					'sections' => array( 'buttons', 'forms', 'spacing' ),
					'priority' => 140,
				),
				'phantom_layout'        => array(
					'title'    => '[Dev] Layout & Effects',
					'sections' => array( 'layout', 'animations', 'effects_3d' ),
					'priority' => 150,
				),
				'phantom_search'        => array(
					'title'    => '[Dev] Search',
					'sections' => array( 'search' ),
					'priority' => 160,
				),
				'phantom_accessibility' => array(
					'title'    => '[Dev] Accessibility',
					'sections' => array( 'accessibility' ),
					'priority' => 170,
				),
				'phantom_advanced'      => array(
					'title'    => '[Dev] Advanced',
					'sections' => array( 'import_export' ),
					'priority' => 180,
				),
				'phantom_pages'         => array(
					'title'    => '[Dev] Pages',
					'sections' => array(
						'about_page', 'contact_page', 'faq_page', 'coming_soon',
						'error_404', 'login_page', 'register_page', 'portfolio',
						'thank_you', 'load_more', 'privacy', 'terms', 'team', 'testimonials',
					),
					'priority' => 190,
				),
			);
			$panels = array_merge( $panels, $legacy_panels );
		}

		return $panels;
	}

	public function register( WP_Customize_Manager $wp_customize ): void {
		Control_Base::register_all( $wp_customize );
		$section_priority = 0;

		foreach ( $this->panels as $panel_id => $panel ) {
			$panel_args = array(
				'title'    => __( $panel['title'], 'phantom-core' ),
				'priority' => $panel['priority'],
			);
			if ( ! empty( $panel['description'] ) ) {
				$panel_args['description'] = $panel['description'];
			}
			$wp_customize->add_panel( $panel_id, $panel_args );

			foreach ( $panel['sections'] as $section_slug ) {
				$section_priority += 5;
				$section_id = 'phantom_section_' . $section_slug;
				$section_label = $this->get_section_label( $section_slug );

			$description = '';
			if ($section_slug === 'design_tokens') {
				$design_studio_url = admin_url('admin.php?page=phantom-design-studio');
				$description = sprintf(
					'<a href="%s" target="_blank">%s</a>',
					esc_url($design_studio_url),
					__('Open Design Studio &rarr;', 'phantom-core')
				);
			}
			if ($section_slug === 'design_system') {
				$token_count = 0;
				$preset_count = 0;
				$current_preset = 'None';
				if (class_exists('\PhantomCore\Design\DesignSystemManager')) {
					$dsm = \PhantomCore\Design\DesignSystemManager::get_instance();
					$compileResult = $dsm->compile();
					$token_count = count($compileResult->tokens);
					$presets = $dsm->availablePresets();
					$preset_count = count($presets);
					$current = $dsm->currentPreset();
					$current_preset = esc_html($current['name'] ?? 'None');
				}
				$design_system_url = admin_url('admin.php?page=phantom-design-system');
				$design_studio_url = admin_url('admin.php?page=phantom-design-studio');
				$description = sprintf(
					'<div style="padding:8px 0;">' .
					'<p><strong>%s</strong> %s &middot; <strong>%s</strong> %s &middot; <strong>%s</strong> %s</p>' .
					'<p><a href="%s" class="button button-secondary" target="_blank">%s</a> <a href="%s" class="button button-primary" target="_blank">%s</a></p>' .
					'</div>',
					__('Tokens:', 'phantom-core'), (string)$token_count,
					__('Active Preset:', 'phantom-core'), $current_preset,
					__('Available Presets:', 'phantom-core'), (string)$preset_count,
					esc_url($design_system_url), __('View Full Design System &rarr;', 'phantom-core'),
					esc_url($design_studio_url), __('Open Design Studio &rarr;', 'phantom-core')
				);
			}
			$wp_customize->add_section( $section_id, array(
				'title'       => $section_label,
				'description' => $description,
				'panel'       => $panel_id,
				'priority'    => $section_priority,
			) );

				$control_priority = 0;
				foreach ( $this->entries as $key => $entry ) {
					if ( ( $entry['section'] ?? '' ) !== $section_slug ) {
						continue;
					}
					$control_priority += 1;

					$setting_id = 'phantom_' . $key;
					$default = $entry['default'] ?? '';
					if ( is_array( $default ) ) {
						$default = wp_json_encode( $default, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
						if ( false === $default || '[]' === $default ) {
							$default = '';
						}
					}
					$wp_customize->add_setting( $setting_id, array(
						'default'           => $default,
						'type'              => 'option',
						'sanitize_callback' => $this->get_sanitize_callback( $entry ),
						'transport'         => $this->get_transport( $key, $entry ),
						'capability'        => 'edit_theme_options',
					) );

					$this->add_control( $wp_customize, $key, $entry, $section_id, $setting_id, $control_priority );
				}
			}
		}

		$this->register_partials( $wp_customize );
	}

	public function register_partials( WP_Customize_Manager $wp_customize ): void {
		foreach ( $this->entries as $key => $entry ) {
			if ( empty( $entry['partial'] ) || ! is_array( $entry['partial'] ) ) {
				continue;
			}
			$partial = $entry['partial'];
			$setting_id = 'phantom_' . $key;
			$wp_customize->selective_refresh->add_partial( 'phantom_partial_' . $key, array(
				'selector'            => $partial['selector'],
				'settings'            => array( $setting_id ),
				'render_callback'     => $partial['render_callback'] ?? '__return_empty_string',
				'container_inclusive' => false,
			) );
		}
	}

	private function add_control( WP_Customize_Manager $wp_customize, string $key, array $entry, string $section_id, string $setting_id, int $priority ): void {
		if ( ! empty( $entry['divider'] ) ) {
			$this->divider_controls[ $setting_id ] = $entry['divider'];
		}

		$type = $entry['type'] ?? 'string';
		$label = $entry['label'] ?? $key;
		$description = $entry['desc'] ?? '';
		$custom_types = array( 'ast-color', 'ast-toggle', 'ast-radio-image', 'ast-responsive-slider', 'ast-responsive-spacing', 'ast-typography', 'ast-gradient', 'ast-select', 'ast-color-group', 'ast-background', 'ast-border', 'ast-preset-card' );

		if ( in_array( $type, $custom_types, true ) ) {
			$class = Control_Base::get_class_for_type( $type );
			if ( $class ) {
				$input_attrs = $entry['input_attrs'] ?? array();
				if ( isset( $entry['min'] ) )  $input_attrs['min']  = $entry['min'];
				if ( isset( $entry['max'] ) )  $input_attrs['max']  = $entry['max'];
				if ( isset( $entry['step'] ) ) $input_attrs['step'] = $entry['step'];
				if ( isset( $entry['unit'] ) ) $input_attrs['unit'] = $entry['unit'];
				if ( isset( $entry['dependencies'] ) ) $input_attrs['data-dependencies'] = $entry['dependencies'];
				$wp_customize->add_control( new $class(
					$wp_customize,
					$setting_id,
					array(
						'label'       => $label,
						'description' => $description,
						'section'     => $section_id,
						'settings'    => $setting_id,
						'priority'    => $priority,
						'choices'     => $entry['options'] ?? $entry['choices'] ?? array(),
						'input_attrs' => $input_attrs,
					)
				) );
				return;
			}
		}

		switch ( $type ) {
			case 'color':
				$wp_customize->add_control(
					new \WP_Customize_Color_Control(
						$wp_customize,
						$setting_id,
						array(
							'label'       => $label,
							'description' => $description,
							'section'     => $section_id,
							'settings'    => $setting_id,
							'priority'    => $priority,
						)
					)
				);
				break;

			case 'bool':
				$wp_customize->add_control( $setting_id, array(
					'type'        => 'checkbox',
					'label'       => $label,
					'description' => $description,
					'section'     => $section_id,
					'priority'    => $priority,
				) );
				break;

			case 'select':
				$wp_customize->add_control( $setting_id, array(
					'type'        => 'select',
					'label'       => $label,
					'description' => $description,
					'section'     => $section_id,
					'choices'     => $entry['options'] ?? $entry['choices'] ?? array(),
					'priority'    => $priority,
				) );
				break;

			case 'image':
				$wp_customize->add_control( new \WP_Customize_Image_Control( $wp_customize, $setting_id, array(
					'label'       => $label,
					'description' => $description,
					'section'     => $section_id,
					'settings'    => $setting_id,
					'priority'    => $priority,
				) ) );
				break;

			case 'textarea':
			case 'text':
				$wp_customize->add_control( $setting_id, array(
					'type'        => 'textarea',
					'label'       => $label,
					'description' => $description,
					'section'     => $section_id,
					'input_attrs' => array( 'rows' => $entry['rows'] ?? 4 ),
					'priority'    => $priority,
				) );
				break;

			case 'code':
				$wp_customize->add_control( new \WP_Customize_Code_Editor_Control( $wp_customize, $setting_id, array(
					'label'       => $label,
					'description' => $description,
					'section'     => $section_id,
					'settings'    => $setting_id,
					'priority'    => $priority,
					'code_type'   => $entry['code_type'] ?? 'text/html',
				) ) );
				break;

			case 'repeater':
			case 'array':
			case 'multiselect':
			case 'multi_select':
			case 'json':
				$wp_customize->add_control( $setting_id, array(
					'type'        => 'textarea',
					'label'       => $label,
					'description' => $description . ' ' . __( 'Enter one value per line.', 'phantom-core' ),
					'section'     => $section_id,
					'input_attrs' => array( 'rows' => $entry['rows'] ?? 5 ),
					'priority'    => $priority,
				) );
				break;

			case 'number':
			case 'int':
			case 'float':
				$attrs = array();
				if ( isset( $entry['min'] ) ) $attrs['min'] = $entry['min'];
				if ( isset( $entry['max'] ) ) $attrs['max'] = $entry['max'];
				if ( isset( $entry['step'] ) ) $attrs['step'] = $entry['step'];
				if ( 'float' === $type && ! isset( $entry['step'] ) ) $attrs['step'] = '0.01';
				$wp_customize->add_control( $setting_id, array(
					'type'        => 'number',
					'label'       => $label,
					'description' => $description,
					'section'     => $section_id,
					'input_attrs' => $attrs,
					'priority'    => $priority,
				) );
				break;

			default:
				$wp_customize->add_control( $setting_id, array(
					'type'        => 'string' === $type ? 'text' : $type,
					'label'       => $label,
					'description' => $description,
					'section'     => $section_id,
					'priority'    => $priority,
				) );
				break;
		}
	}

	public function preview_js(): void {
		wp_enqueue_script(
			'phantom-customizer-preview',
			PHANTOM_CORE_URL . 'admin/js/customizer-preview.js',
			array( 'jquery', 'customize-preview' ),
			PHANTOM_CORE_VERSION,
			true
		);
		$css_var_map    = $this->get_css_var_map();
		$all_px_keys    = Settings_Registry::get_px_keys();
		$px_keys        = array();
		$responsive_keys = array();
		foreach ( array_keys( $css_var_map ) as $key ) {
			if ( in_array( $key, $all_px_keys, true ) ) {
				$px_keys[] = $key;
			}
		}
		foreach ( $this->entries as $ekey => $entry ) {
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
					'heroDesktop' => PHANTOM_CORE_URL . 'frontend/assets/images/banner-img1.png',
					'heroTablet'  => PHANTOM_CORE_URL . 'frontend/assets/images/banner-img2.png',
					'heroMobile'  => PHANTOM_CORE_URL . 'frontend/assets/images/banner-bg-img.png',
					'logo'        => PHANTOM_CORE_URL . 'frontend/assets/images/logo.png',
					'favicon'     => PHANTOM_CORE_URL . 'frontend/assets/images/favicon/favicon.ico',
				),
			)
		);

		$partials = array();
		foreach ( $this->entries as $ekey => $entry ) {
			if ( ! empty( $entry['partial'] ) && is_array( $entry['partial'] ) ) {
				$partials[ $ekey ] = $entry['partial'];
			}
		}
		if ( ! empty( $partials ) ) {
			wp_localize_script(
				'phantom-customizer-preview',
				'PhantomPartials',
				$partials
			);
		}
	}

	public function controls_js(): void {
		wp_enqueue_script(
			'phantom-customizer-conditionals',
			PHANTOM_CORE_URL . 'admin/js/customizer-conditionals.js',
			array( 'jquery', 'customize-controls' ),
			PHANTOM_CORE_VERSION,
			true
		);

		if ( ! empty( $this->divider_controls ) ) {
			wp_localize_script(
				'phantom-customizer-conditionals',
				'PhantomDividerControls',
				$this->divider_controls
			);
		}

		wp_add_inline_style(
			'customize-controls',
			'.ast-top-divider { border-top: 1px solid #ddd; margin-top: 15px; padding-top: 15px; }' .
			'.ast-bottom-divider { border-bottom: 1px solid #ddd; margin-bottom: 15px; padding-bottom: 15px; }'
		);
	}

	private function get_section_label( string $slug ): string {
		$labels = array(
			'branding'          => __( 'Branding', 'phantom-core' ),
			'header'            => __( 'Header', 'phantom-core' ),
			'topbar'            => __( 'Top Bar', 'phantom-core' ),
			'navigation'        => __( 'Navigation', 'phantom-core' ),
			'announcement_bar'  => __( 'Announcement Bar', 'phantom-core' ),
			'hero'              => __( 'Hero Section', 'phantom-core' ),
			'home_sections'     => __( 'Home Sections', 'phantom-core' ),
			'collections'       => __( 'Collections', 'phantom-core' ),
			'product_cards'     => __( 'Product Cards', 'phantom-core' ),
			'shop_page'         => __( 'Shop Page', 'phantom-core' ),
			'product_page'      => __( 'Product Page', 'phantom-core' ),
			'woocommerce'       => __( 'WooCommerce', 'phantom-core' ),
			'blog'              => __( 'Blog', 'phantom-core' ),
			'footer'            => __( 'Footer', 'phantom-core' ),
			'typography'        => __( 'Typography', 'phantom-core' ),
			'colors'            => __( 'Colors', 'phantom-core' ),
			'buttons'           => __( 'Buttons', 'phantom-core' ),
			'forms'             => __( 'Forms', 'phantom-core' ),
			'spacing'           => __( 'Spacing', 'phantom-core' ),
			'layout'            => __( 'Layout', 'phantom-core' ),
			'responsive'        => __( 'Responsive', 'phantom-core' ),
			'animations'        => __( 'Animations', 'phantom-core' ),
			'effects_3d'        => __( '3D Effects', 'phantom-core' ),
			'search'            => __( 'Search', 'phantom-core' ),
			'performance'       => __( 'Performance', 'phantom-core' ),
			'seo'               => __( 'SEO', 'phantom-core' ),
			'accessibility'     => __( 'Accessibility', 'phantom-core' ),
			'integrations'      => __( 'Integrations', 'phantom-core' ),
			'custom_code'       => __( 'Custom Code', 'phantom-core' ),
			'import_export'     => __( 'Import / Export', 'phantom-core' ),
			'about_page'        => __( 'About Page', 'phantom-core' ),
			'contact_page'      => __( 'Contact Page', 'phantom-core' ),
			'faq_page'          => __( 'FAQ Page', 'phantom-core' ),
			'coming_soon'       => __( 'Coming Soon', 'phantom-core' ),
			'error_404'         => __( '404 Page', 'phantom-core' ),
			'login_page'        => __( 'Login Page', 'phantom-core' ),
			'register_page'     => __( 'Register Page', 'phantom-core' ),
			'portfolio'         => __( 'Portfolio', 'phantom-core' ),
			'thank_you'         => __( 'Thank You', 'phantom-core' ),
			'load_more'         => __( 'Load More', 'phantom-core' ),
			'privacy'           => __( 'Privacy Policy', 'phantom-core' ),
			'terms'             => __( 'Terms of Use', 'phantom-core' ),
			'team'              => __( 'Team', 'phantom-core' ),
			'testimonials'      => __( 'Testimonials', 'phantom-core' ),
			'design_tokens'     => __( 'Design Tokens', 'phantom-core' ),
			'design_system'    => __( 'Design System Overview', 'phantom-core' ),
			'template_pack'    => __( 'Template Pack', 'phantom-core' ),
		);
		return $labels[ $slug ] ?? ucfirst( str_replace( '_', ' ', $slug ) );
	}

	private function get_sanitize_callback( array $entry ): callable {
		$sanitize = $entry['sanitize'] ?? null;
		if ( is_callable( $sanitize ) ) {
			return $sanitize;
		}
		if ( is_string( $sanitize ) && function_exists( $sanitize ) ) {
			return $sanitize;
		}
		$type = $entry['type'] ?? 'string';
		if ( in_array( $type, array( 'array', 'repeater', 'multiselect', 'multi_select', 'json' ), true ) ) {
			return 'sanitize_textarea_field';
		}
		$custom_sanitize = Control_Base::get_sanitize_for_type( $type );
		if ( $custom_sanitize ) {
			return $custom_sanitize;
		}
		return 'sanitize_text_field';
	}

	private function get_transport( string $key, array $entry ): string {
		if ( isset( $entry['transport'] ) ) {
			return $entry['transport'];
		}
		$map = Settings_Registry::get_css_var_map();
		if ( isset( $map[ $key ] ) ) {
			return 'postMessage';
		}
		return 'refresh';
	}

	public function get_css_var_map(): array {
		return Settings_Registry::get_css_var_map();
	}

	/**
	 * Sync individual phantom_* options into the phantom_options array
	 * after Customizer saves. This bridges the gap between Customizer's
	 * per-setting storage and the array-based phantom_options format
	 * used by the Shell and inline CSS injection.
	 */
	public function sync_options(): void {
		$options = get_option( 'phantom_options', array() );
		$changed = false;
		$entries = Settings_Registry::get_instance()->get_entries();
		foreach ( array_keys( $entries ) as $key ) {
			$value = get_option( 'phantom_' . $key, null );
			if ( null !== $value && ( ! array_key_exists( $key, $options ) || $options[ $key ] !== $value ) ) {
				if ( is_array( $value ) && empty( $value ) ) {
					continue;
				}
				$options[ $key ] = $value;
				$changed = true;
			}
		}
		if ( $changed ) {
			update_option( 'phantom_options', $options, false );
		}
	}

	public function get_inline_css(): string {
		$options = get_option( 'phantom_options', array() );
		$map     = $this->get_css_var_map();
		$css     = '';
		foreach ( $map as $key => $var ) {
			$val = null;
			if ( isset( $options[ $key ] ) && '' !== $options[ $key ] ) {
				$val = $options[ $key ];
			} else {
				$individual = get_option( 'phantom_' . $key, null );
				if ( null !== $individual && '' !== $individual ) {
					$val = $individual;
				}
			}
			if ( null !== $val ) {
				if ( is_array( $val ) ) {
					$responsive_bps = array(
						'desktop' => '',
						'tablet'  => 768,
						'mobile'  => 544,
					);
					$desktop_val   = $val['desktop'] ?? '';
					if ( '' !== $desktop_val ) {
						$bp_val = in_array( $key, Settings_Registry::get_px_keys(), true ) && is_numeric( $desktop_val ) ? $desktop_val . 'px' : $desktop_val;
						$css   .= $var . ':' . wp_strip_all_tags( $bp_val ) . ';';
					}
					foreach ( array( 'tablet', 'mobile' ) as $bp ) {
						if ( isset( $val[ $bp ] ) && '' !== $val[ $bp ] ) {
							$bp_val = in_array( $key, Settings_Registry::get_px_keys(), true ) && is_numeric( $val[ $bp ] ) ? $val[ $bp ] . 'px' : $val[ $bp ];
							$css   .= '@media (max-width: ' . $responsive_bps[ $bp ] . 'px) {:root{' . $var . ':' . wp_strip_all_tags( $bp_val ) . ';}}';
						}
					}
				} else {
					if ( in_array( $key, Settings_Registry::get_px_keys(), true ) ) {
						$val = is_numeric( $val ) ? $val . 'px' : $val;
					}
					$css .= $var . ':' . wp_strip_all_tags( $val ) . ';';
				}
			}
		}
		if ( '' === $css ) {
			return '';
		}
		return '<style id="phantom-customizer-css">:root{' . $css . '}</style>';
	}
}
