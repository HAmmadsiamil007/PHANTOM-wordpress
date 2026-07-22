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
	}

	public function define_panels(): array {
		return array(
			'phantom_branding'      => array(
				'title'    => __( 'Branding', 'phantom-core' ),
				'sections' => array( 'branding' ),
				'priority' => 10,
			),
			'phantom_header'        => array(
				'title'    => __( 'Header & Navigation', 'phantom-core' ),
				'sections' => array( 'header', 'topbar', 'navigation', 'announcement_bar' ),
				'priority' => 20,
			),
			'phantom_hero'          => array(
				'title'    => __( 'Hero & Home', 'phantom-core' ),
				'sections' => array( 'hero', 'home_sections', 'collections' ),
				'priority' => 30,
			),
			'phantom_products'      => array(
				'title'    => __( 'Products & Shop', 'phantom-core' ),
				'sections' => array( 'product_cards', 'shop_page', 'product_page' ),
				'priority' => 40,
			),
			'phantom_woocommerce'   => array(
				'title'    => __( 'WooCommerce', 'phantom-core' ),
				'sections' => array( 'woocommerce' ),
				'priority' => 50,
			),
			'phantom_blog'          => array(
				'title'    => __( 'Blog', 'phantom-core' ),
				'sections' => array( 'blog' ),
				'priority' => 60,
			),
			'phantom_footer'        => array(
				'title'    => __( 'Footer', 'phantom-core' ),
				'sections' => array( 'footer' ),
				'priority' => 70,
			),
			'phantom_typography'    => array(
				'title'    => __( 'Typography & Fonts', 'phantom-core' ),
				'sections' => array( 'typography' ),
				'priority' => 80,
			),
			'phantom_colors'        => array(
				'title'    => __( 'Colors & Buttons', 'phantom-core' ),
				'sections' => array( 'colors', 'buttons', 'forms', 'spacing' ),
				'priority' => 90,
			),
			'phantom_layout'        => array(
				'title'    => __( 'Layout & Effects', 'phantom-core' ),
				'sections' => array( 'layout', 'responsive', 'animations', 'effects_3d' ),
				'priority' => 100,
			),
			'phantom_search'        => array(
				'title'    => __( 'Search', 'phantom-core' ),
				'sections' => array( 'search' ),
				'priority' => 110,
			),
			'phantom_performance'   => array(
				'title'    => __( 'Performance & SEO', 'phantom-core' ),
				'sections' => array( 'performance', 'seo' ),
				'priority' => 120,
			),
			'phantom_accessibility' => array(
				'title'    => __( 'Accessibility', 'phantom-core' ),
				'sections' => array( 'accessibility' ),
				'priority' => 130,
			),
			'phantom_advanced'      => array(
				'title'    => __( 'Advanced', 'phantom-core' ),
				'sections' => array( 'integrations', 'custom_code', 'import_export' ),
				'priority' => 140,
			),
			'phantom_pages'         => array(
				'title'    => __( 'Pages', 'phantom-core' ),
				'sections' => array(
					'about_page', 'contact_page', 'faq_page', 'coming_soon',
					'error_404', 'login_page', 'register_page', 'portfolio',
					'thank_you', 'load_more', 'privacy', 'terms', 'team', 'testimonials',
				),
				'priority' => 150,
			),
		);
	}

	public function register( WP_Customize_Manager $wp_customize ): void {
		Control_Base::register_all( $wp_customize );
		$section_priority = 0;

		foreach ( $this->panels as $panel_id => $panel ) {
			$wp_customize->add_panel( $panel_id, array(
				'title'    => $panel['title'],
				'priority' => $panel['priority'],
			) );

			foreach ( $panel['sections'] as $section_slug ) {
				$section_priority += 5;
				$section_id = 'phantom_section_' . $section_slug;
				$section_label = $this->get_section_label( $section_slug );

				$wp_customize->add_section( $section_id, array(
					'title'    => $section_label,
					'panel'    => $panel_id,
					'priority' => $section_priority,
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
						$default = wp_json_encode( $default, JSON_UNESCAPED_SLASHES );
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
	}

	private function add_control( WP_Customize_Manager $wp_customize, string $key, array $entry, string $section_id, string $setting_id, int $priority ): void {
		if ( ! empty( $entry['divider'] ) ) {
			$this->divider_controls[ $setting_id ] = $entry['divider'];
		}

		$type = $entry['type'] ?? 'string';
		$label = $entry['label'] ?? $key;
		$description = $entry['desc'] ?? '';
		$custom_types = array( 'ast-color', 'ast-toggle', 'ast-radio-image', 'ast-responsive-slider', 'ast-responsive-spacing', 'ast-typography', 'ast-gradient', 'ast-select', 'ast-color-group', 'ast-background', 'ast-border' );

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
		global $wpdb;
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( 'phantom_' ) . '%'
			)
		);
		$prefix     = 'phantom_';
		$options    = get_option( 'phantom_options', array() );
		$changed    = false;
		$known_keys = array_keys( Settings_Registry::get_instance()->get_entries() );
		foreach ( $rows as $row ) {
			$key = substr( $row->option_name, strlen( $prefix ) );
			if ( 'options' === $key || ! in_array( $key, $known_keys, true ) ) {
				continue;
			}
			$value = maybe_unserialize( $row->option_value );
			if ( ! isset( $options[ $key ] ) || $options[ $key ] !== $value ) {
				$options[ $key ] = $value;
				$changed = true;
			}
		}
		if ( $changed ) {
			update_option( 'phantom_options', $options );
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
				if ( in_array( $key, Settings_Registry::get_px_keys(), true ) ) {
					$val = is_numeric( $val ) ? $val . 'px' : $val;
				}
				$css .= $var . ':' . esc_attr( $val ) . ';';
			}
		}
		if ( '' === $css ) {
			return '';
		}
		return '<style id="phantom-customizer-css">:root{' . $css . '}</style>';
	}
}
