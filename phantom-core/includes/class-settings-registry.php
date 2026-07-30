<?php
declare(strict_types=1);

namespace PhantomCore;

defined( 'ABSPATH' ) || exit;

class Settings_Registry {
	private static ?self $instance = null;

	private array $entries = array();

	private bool $registered = false;

	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function register(): void {
		if ( $this->registered ) {
			return;
		}
		$this->entries = $this->define_entries();
		$this->registered = true;
	}

	public function has( string $key ): bool {
		if ( ! $this->registered ) {
			$this->register();
		}
		return isset( $this->entries[ $key ] );
	}

	public function get( string $key ) {
		if ( ! $this->registered ) {
			$this->register();
		}
		if ( ! isset( $this->entries[ $key ] ) ) {
			return null;
		}
		$entry = $this->entries[ $key ];
		$bulk  = get_option( 'phantom_options', array() );
		if ( array_key_exists( $key, $bulk ) ) {
			return $bulk[ $key ];
		}
		$value = get_option( 'phantom_' . $key, '__not_set__' );
		if ( '__not_set__' === $value ) {
			return $entry['default'] ?? null;
		}
		return $value;
	}

	public function set( string $key, $value ): bool {
		if ( ! $this->registered ) {
			$this->register();
		}
		if ( ! isset( $this->entries[ $key ] ) ) {
			return false;
		}
		$entry   = $this->entries[ $key ];
		$sanitize = $entry['sanitize'] ?? null;
		if ( is_string( $sanitize ) && function_exists( $sanitize ) ) {
			$value = $sanitize( $value );
		} elseif ( is_callable( $sanitize ) ) {
			$value = $sanitize( $value );
		}
		return update_option( 'phantom_' . $key, $value, false );
	}

	public function get_schema( string $key ): ?array {
		if ( ! $this->registered ) {
			$this->register();
		}
		return $this->entries[ $key ] ?? null;
	}

	public function flush_cache(): void {
		\PhantomCore\Engine\Cache::get_instance()->flush();
	}


	protected function define_entries(): array {
		// Loader is the single source of truth.
		$sections = array();
		if ( class_exists( '\PhantomCore\Settings\Settings_Loader' ) ) {
			$loader   = new \PhantomCore\Settings\Settings_Loader();
			$sections = $loader->get_all_sections();
		}
		if ( empty( $sections ) ) {
			return array();
		}

		// Dedup check — warn if duplicate keys found across sections.
		// design_tokens section is intentionally allowed to override other sections.
		$keys_seen = array();
		$merged    = array();
		foreach ( $sections as $name => $section_entries ) {
			foreach ( $section_entries as $key => $entry ) {
				if ( 'design_tokens' === $name && isset( $keys_seen[ $key ] ) ) {
					continue;
				}
				if ( isset( $keys_seen[ $key ] ) ) {
					_doing_it_wrong(
						__METHOD__,
						sprintf(
							'Duplicate setting key "%1$s" in section "%2$s" (first defined in "%3$s"). Later entry overrides earlier one.',
							esc_html( $key ),
							esc_html( $name ),
							esc_html( $keys_seen[ $key ] )
						),
						'1.0.0'
					);
				}
				$keys_seen[ $key ] = $name;
				$merged[ $key ]    = $entry;
			}
		}
		return $merged;
	}

	public function get_string( string $key, string $default = '' ): string {
		$val = $this->get( $key );
		return is_string( $val ) ? $val : (string) ( $val ?? $default );
	}

	public function get_int( string $key, int $default = 0 ): int {
		return (int) ( $this->get( $key ) ?? $default );
	}

	public function get_bool( string $key, bool $default = false ): bool {
		return (bool) ( $this->get( $key ) ?? $default );
	}

	public function get_float( string $key, float $default = 0.0 ): float {
		return (float) ( $this->get( $key ) ?? $default );
	}

	public function get_image( string $key, string $size = 'full' ) {
		$val = $this->get( $key );
		if ( is_numeric( $val ) ) {
			$src = wp_get_attachment_image_url( (int) $val, $size );
			return $src ? $src : '';
		}
		return is_string( $val ) ? $val : '';
	}

	public function get_color( string $key, string $default = '#000000' ): string {
		$val = $this->get( $key );
		if ( ! is_string( $val ) ) {
			return $default;
		}
		$sanitized = sanitize_hex_color( $val );
		return $sanitized ? $sanitized : $default;
	}

	public function get_array( string $key, array $default = array() ): array {
		$val = $this->get( $key );
		if ( is_array( $val ) ) {
			return $val;
		}
		if ( is_string( $val ) ) {
			$decoded = json_decode( $val, true );
			return is_array( $decoded ) ? $decoded : $default;
		}
		return $default;
	}

	public function get_option( string $key, $default = null ) {
		return $this->get( $key ) ?? $default;
	}

	public function get_defaults(): array {
		if ( ! $this->registered ) {
			$this->register();
		}
		$defaults = array();
		foreach ( $this->entries as $key => $entry ) {
			$defaults[ $key ] = $entry['default'] ?? '';
		}
		return $defaults;
	}

	public function get_entries(): array {
		if ( ! $this->registered ) {
			$this->register();
		}
		return $this->entries;
	}

	/**
	 * Get the shared map of setting keys to CSS custom property names.
	 *
	 * Single source of truth for the Customizer inline CSS and Shell
	 * SPA CSS injection. Every entry here becomes a `--var-name` that
	 * can be referenced in frontend CSS via `var(--var-name)`.
	 *
	 * @return array<string, string> Setting key => CSS variable name (with -- prefix).
	 */
	public static function get_css_var_map(): array {
		$map = array(
			'container_width'              => '--container-width',
			'content_width'                => '--content-width',
			'sidebar_width'                => '--sidebar-width',
			'typography_body_font'         => '--font-body',
			'typography_body_weight'       => '--font-body-weight',
			'typography_body_style'        => '--font-body-style',
			'typography_base_size'         => '--font-base-size',
			'typography_line_height'       => '--font-line-height',
			'typography_body_spacing'      => '--font-body-spacing',
			'typography_heading_font'      => '--font-heading',
			'typography_heading_weight'    => '--font-heading-weight',
			'typography_heading_case'      => '--font-heading-case',
			'typography_heading_spacing'   => '--font-heading-spacing',
			'typography_h1_size'           => '--h1-size',
			'typography_h1_height'         => '--h1-height',
			'typography_h1_font'           => '--h1-font',
			'typography_h1_weight'         => '--h1-weight',
			'typography_h1_style'          => '--h1-style',
			'typography_h1_spacing'        => '--h1-spacing',
			'typography_h1_case'           => '--h1-case',
			'typography_h2_size'           => '--h2-size',
			'typography_h2_height'         => '--h2-height',
			'typography_h2_font'           => '--h2-font',
			'typography_h2_weight'         => '--h2-weight',
			'typography_h2_style'          => '--h2-style',
			'typography_h2_spacing'        => '--h2-spacing',
			'typography_h2_case'           => '--h2-case',
			'typography_h3_size'           => '--h3-size',
			'typography_h3_height'         => '--h3-height',
			'typography_h3_font'           => '--h3-font',
			'typography_h3_weight'         => '--h3-weight',
			'typography_h3_style'          => '--h3-style',
			'typography_h3_spacing'        => '--h3-spacing',
			'typography_h3_case'           => '--h3-case',
			'typography_h4_size'           => '--h4-size',
			'typography_h4_height'         => '--h4-height',
			'typography_h4_font'           => '--h4-font',
			'typography_h4_weight'         => '--h4-weight',
			'typography_h4_style'          => '--h4-style',
			'typography_h4_spacing'        => '--h4-spacing',
			'typography_h4_case'           => '--h4-case',
			'typography_h5_size'           => '--h5-size',
			'typography_h5_height'         => '--h5-height',
			'typography_h5_font'           => '--h5-font',
			'typography_h5_weight'         => '--h5-weight',
			'typography_h5_style'          => '--h5-style',
			'typography_h5_spacing'        => '--h5-spacing',
			'typography_h5_case'           => '--h5-case',
			'typography_h6_size'           => '--h6-size',
			'typography_h6_height'         => '--h6-height',
			'typography_h6_font'           => '--h6-font',
			'typography_h6_weight'         => '--h6-weight',
			'typography_h6_style'          => '--h6-style',
			'typography_h6_spacing'        => '--h6-spacing',
			'typography_h6_case'           => '--h6-case',
			'color_primary'                => '--primary--color',
			'color_secondary'              => '--secondary--color',
			'color_accent'                 => '--accent--color',
			'color_background'             => '--bg',
			'color_text'                   => '--text--color',
			'color_heading'                => '--heading--color',
			'color_link'                   => '--link',
			'color_link_hover'             => '--link--hover',
			'form_input_border_radius'     => '--form-input-radius',
			'form_input_height'            => '--form-input-height',
			'button_padding_x'             => '--button-padding-x',
			'button_padding_y'             => '--button-padding-y',
			'header_bg'                    => '--header-bg',
			'header_text_color'            => '--header-color',
			'header_padding_x'             => '--header-padding-x',
			'header_padding_y'             => '--header-padding-y',
			'header_border_color'          => '--header-border-color',
			'header_border_width'          => '--header-border-width',
			'header_mobile_height'         => '--header-mobile-height',
			'header_banner_height'         => '--banner-height',
			'nav_menu_height'              => '--nav-menu-height',
			'nav_submenu_width'            => '--nav-submenu-width',
			'footer_bg_color'              => '--footer--bg',
			'footer_text'                  => '--footer--text',
			'footer_heading_text'          => '--footer-heading',
			'footer_link'                  => '--footer-link',
			'footer_border_color'          => '--footer-border-color',
			'home_section_spacing'         => '--home-section-spacing',
			'container_gutter'             => '--container-gutter',
			'content_gap'                  => '--content-gap',
			'element_margin_bottom'        => '--element-margin-bottom',
			'widget_spacing'               => '--widget-spacing',
			'header_height'                => '--header--height',
			'breakpoint_xl'               => '--breakpoint-xl',
			'breakpoint_lg'               => '--breakpoint-lg',
			'breakpoint_md'               => '--breakpoint-md',
			'breakpoint_sm'               => '--breakpoint-sm',
			'section_padding_x'            => '--section-padding-x',
			'section_padding_y'            => '--section-padding-y',
			'topbar_bg'                    => '--topbar--bg',
			'topbar_text'                  => '--topbar--text',
			'menu_font_size'               => '--menu--font--size',
			'button_radius'                => '--button-radius',
			'button_bg'                    => '--button-bg',
			'button_text'                  => '--button-text',
			'button_bg_hover'              => '--button-bg-hover',
			'button_text_hover'            => '--button-text-hover',
			'color_rating'                => '--woo--rating',
			'layout_boxed_width'           => '--boxed-width',
			'layout_columns'               => '--layout-columns',
			'announcement_bar_bg'          => '--announcement-bar-bg',
			'announcement_bar_text_color'  => '--announcement-bar-color',
			'color_header_bg'              => '--color-header-bg',
			'color_footer_bg'              => '--color-footer-bg',
			'color_border'                 => '--border--color',
			'color_sale'                   => '--sale--color',
			'color_light_bg'              => '--light--bg--color',
			'color_grey'                   => '--grey--color',
			'color_success'               => '--success--color',
			'color_error'                 => '--error--color',
			'color_warning'               => '--warning--color',
			'color_info'                  => '--info--color',
			'color_gradient_start'        => '--gradient-start--color',
			'color_gradient_end'          => '--gradient-end--color',
			'color_featured_badge'        => '--featured-badge--color',
		'hero_fit'                     => '--hero-object-fit',
		'hero_position'                => '--hero-object-position',
		'hero_overlay_opacity'         => '--hero-overlay-opacity',
		'hero_tablet_breakpoint'       => '--hero-tablet-bp',
		'hero_mobile_breakpoint'       => '--hero-mobile-bp',
		'button_font_size'             => '--button-font-size',
			'color_card_bg'                => '--product-card-bg',
			'color_card_text'              => '--product-card-text',
			'color_card_border'            => '--product-card-border',
			'color_button_bg'              => '--product-button-bg',
			'color_button_text'            => '--product-button-text',
			'color_button_hover_bg'        => '--product-button-hover-bg',
			'color_badge_sale_bg'          => '--product-badge-sale-bg',
			'color_badge_sale_text'        => '--product-badge-sale-text',
			'color_badge_new_bg'           => '--product-badge-new-bg',
			'color_badge_new_text'         => '--product-badge-new-text',
		);
		$instance = self::$instance;
		if ( $instance ) {
			$map = $instance->include_token_css_var_map( $map );
		}
		return $map;
	}

	public function include_token_css_var_map( array $map ): array {
		foreach ( $this->entries as $key => $entry ) {
			if ( ! empty( $entry['css_var'] ) && ! isset( $map[ $key ] ) ) {
				$map[ $key ] = $entry['css_var'];
			}
		}
		return $map;
	}

	/**
	 * Get the list of setting keys whose values should be suffixed with 'px'
	 * when output as CSS custom properties.
	 *
	 * @return array<int, string> Setting keys that require px suffixes.
	 */
	public static function get_px_keys(): array {
		return array(
			'button_padding_x', 'button_padding_y',
			'button_radius',
			'header_padding_x', 'header_padding_y',
			'header_border_width', 'header_mobile_height',
			'header_height', 'container_width', 'content_width',
			'sidebar_width',
			'breakpoint_xl', 'breakpoint_lg', 'breakpoint_md', 'breakpoint_sm',
			'section_padding_x', 'section_padding_y', 'menu_font_size', 'button_font_size',
			'widget_spacing', 'container_gutter', 'content_gap',
			'element_margin_bottom', 'home_section_spacing',
			'typography_base_size', 'typography_body_spacing', 'typography_heading_spacing',
			'typography_h1_size', 'typography_h1_height',
			'typography_h1_spacing', 'typography_h2_spacing', 'typography_h3_spacing',
			'typography_h4_spacing', 'typography_h5_spacing', 'typography_h6_spacing',
			'typography_h2_size', 'typography_h2_height', 'typography_h3_size',
			'typography_h3_height', 'typography_h4_size', 'typography_h4_height',
			'typography_h5_size', 'typography_h5_height', 'typography_h6_size',
			'typography_h6_height',
			'form_input_height', 'layout_columns',
			'nav_menu_height', 'nav_submenu_width',
		);
	}


	/**
	 * Sanitize callback that only allows users with manage_options capability.
	 *
	 * @return \Closure
	 */
	public static function sanitize_code_passthrough(): \Closure {
		return function ( $v ) {
			if ( ! current_user_can( 'manage_options' ) ) {
				return '';
			}
			return $v;
		};
	}

	/**
	 * Sanitize map embed HTML (iframe).
	 *
	 * @param  string $value Raw embed HTML.
	 * @return string
	 */
	public function sanitize_map_embed( $value ): string {
		if ( empty( $value ) ) {
			return '';
		}
		$allowed = array(
			'iframe' => array(
				'src'             => true,
				'width'           => true,
				'height'          => true,
				'style'           => true,
				'allowfullscreen' => true,
				'loading'         => true,
				'title'           => true,
			),
		);
		return wp_kses( $value, $allowed );
	}

	/**
	 * Recursively sanitize array items.
	 *
	 * @param  array $values Array to sanitize.
	 * @return array
	 */
	public static function sanitize_array_items( array $values ): array {
		$result = array();
		foreach ( $values as $key => $value ) {
			if ( is_array( $value ) ) {
				$result[ $key ] = self::sanitize_array_items( $value );
			} elseif ( is_string( $value ) ) {
				$result[ $key ] = sanitize_text_field( $value );
			} else {
				$result[ $key ] = $value;
			}
		}
		return $result;
	}
}
