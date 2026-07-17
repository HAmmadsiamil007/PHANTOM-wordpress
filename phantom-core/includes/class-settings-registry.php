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

	public function get( string $key ): mixed {
		if ( ! $this->registered ) {
			$this->register();
		}
		if ( ! isset( $this->entries[ $key ] ) ) {
			return null;
		}
		$entry = $this->entries[ $key ];
		$value = get_option( 'phantom_' . $key, '__not_set__' );
		if ( '__not_set__' === $value ) {
			return $entry['default'] ?? null;
		}
		return $value;
	}

	public function set( string $key, mixed $value ): void {
		if ( ! $this->registered ) {
			$this->register();
		}
		if ( ! isset( $this->entries[ $key ] ) ) {
			return;
		}
		$entry   = $this->entries[ $key ];
		$sanitize = $entry['sanitize'] ?? null;
		if ( is_string( $sanitize ) && function_exists( $sanitize ) ) {
			$value = $sanitize( $value );
		} elseif ( is_callable( $sanitize ) ) {
			$value = $sanitize( $value );
		}
		update_option( 'phantom_' . $key, $value, false );
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
		return array_merge(
			$this->section_branding(),
			$this->section_header(),
			$this->section_topbar(),
			$this->section_navigation(),
			$this->section_hero(),
			$this->section_collections(),
			$this->section_home_sections(),
			$this->section_product_cards(),
			$this->section_shop_page(),
			$this->section_product_page(),
			$this->section_woocommerce(),
			$this->section_blog(),
			$this->section_footer(),
			$this->section_typography(),
			$this->section_colors(),
			$this->section_buttons(),
			$this->section_forms(),
			$this->section_spacing(),
			$this->section_layout(),
			$this->section_responsive(),
			$this->section_animations(),
			$this->section_effects_3d(),
			$this->section_search(),
			$this->section_performance(),
			$this->section_seo(),
			$this->section_accessibility(),
			$this->section_integrations(),
			$this->section_custom_code(),
			$this->section_import_export(),
			$this->section_about_page(),
			$this->section_contact_page(),
			$this->section_faq_page(),
			$this->section_coming_soon(),
			$this->section_error_404(),
			$this->section_login_page(),
			$this->section_register_page(),
			$this->section_portfolio(),
			$this->section_thank_you(),
			$this->section_load_more(),
			$this->section_privacy(),
			$this->section_terms(),
			$this->section_team(),
			$this->section_testimonials(),
			$this->section_announcement_bar()
		);
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

	public function get_image( string $key, string $size = 'full' ): string|int {
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

	public function get_option( string $key, mixed $default = null ): mixed {
		return $this->get( $key ) ?? $default;
	}

	public function get_entries(): array {
		if ( ! $this->registered ) {
			$this->register();
		}
		return $this->entries;
	}

	private function section_branding(): array {
		return array(
			'general_site_logo'            => array(
				'section'  => 'branding',
				'type'     => 'image',
				'default'  => '',
				'sanitize' => 'esc_url_raw',
				'label'    => __( 'Site Logo', 'phantom-core' ),
			),
			'general_preloader_enable'     => array(
				'section'  => 'branding',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Enable Preloader', 'phantom-core' ),
			),
			'general_kc_img_base'          => array(
				'section'  => 'branding',
				'type'     => 'string',
				'default'  => '/assets/kids-collection/images',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Image Base Path', 'phantom-core' ),
			),
			'branding_favicon'             => array(
				'section'  => 'branding',
				'type'     => 'image',
				'default'  => '',
				'sanitize' => 'esc_url_raw',
				'label'    => __( 'Favicon', 'phantom-core' ),
			),
			'branding_apple_touch_icon'    => array(
				'section'  => 'branding',
				'type'     => 'image',
				'default'  => '',
				'sanitize' => 'esc_url_raw',
				'label'    => __( 'Apple Touch Icon', 'phantom-core' ),
			),
			'branding_site_tagline_enable' => array(
				'section'  => 'branding',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Show Site Tagline', 'phantom-core' ),
			),
			'branding_site_tagline_text'   => array(
				'section'  => 'branding',
				'type'     => 'string',
				'default'  => 'Just another WordPress site',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Custom Tagline', 'phantom-core' ),
			),
			'branding_logo_retina'         => array(
				'section'  => 'branding',
				'type'     => 'image',
				'default'  => '',
				'sanitize' => 'esc_url_raw',
				'label'    => __( 'Retina Logo', 'phantom-core' ),
			),
			'branding_logo_dark'           => array(
				'section'  => 'branding',
				'type'     => 'image',
				'default'  => '',
				'sanitize' => 'esc_url_raw',
				'label'    => __( 'Dark Mode Logo', 'phantom-core' ),
			),
			'branding_logo_mobile'         => array(
				'section'  => 'branding',
				'type'     => 'image',
				'default'  => '',
				'sanitize' => 'esc_url_raw',
				'label'    => __( 'Mobile Logo', 'phantom-core' ),
			),
			'branding_preloader_style'     => array(
				'section'  => 'branding',
				'type'     => 'select',
				'default'  => 'spinner',
				'options'  => array(
					'spinner' => 'Spinner',
					'pulse'   => 'Pulse',
					'fade'    => 'Fade',
					'none'    => 'None',
				),
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Preloader Style', 'phantom-core' ),
			),
			'branding_browser_theme_color' => array(
				'section'  => 'branding',
				'type'     => 'color',
				'default'  => '#ffffff',
				'sanitize' => 'sanitize_hex_color',
				'label'    => __( 'Browser Theme Color', 'phantom-core' ),
			),
			'branding_logo_max_height'     => array(
				'section'  => 'branding',
				'type'     => 'int',
				'default'  => 60,
				'sanitize' => 'absint',
				'label'    => __( 'Logo Max Height', 'phantom-core' ),
			),
			'branding_logo_padding'        => array(
				'section'  => 'branding',
				'type'     => 'int',
				'default'  => 0,
				'sanitize' => 'absint',
				'label'    => __( 'Logo Padding', 'phantom-core' ),
			),
			'branding_preloader_bg'        => array(
				'section'  => 'branding',
				'type'     => 'color',
				'default'  => '#ffffff',
				'sanitize' => 'sanitize_hex_color',
				'label'    => __( 'Preloader Background', 'phantom-core' ),
			),
		);
	}

	private function section_header(): array {
		return array(
			'header_logo'                   => array(
				'section'  => 'header',
				'type'     => 'string',
				'default'  => '',
				'sanitize' => 'esc_url_raw',
				'label'    => __( 'Header Logo', 'phantom-core' ),
			),
			'header_logo_width'             => array(
				'section'  => 'header',
				'type'     => 'int',
				'default'  => 150,
				'sanitize' => 'absint',
				'label'    => __( 'Logo Width', 'phantom-core' ),
			),
			'header_sticky'                 => array(
				'section'  => 'header',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Sticky Header', 'phantom-core' ),
			),
			'header_height'                 => array(
				'section'  => 'header',
				'type'     => 'int',
				'default'  => 80,
				'sanitize' => 'absint',
				'label'    => __( 'Header Height', 'phantom-core' ),
			),
			'header_search_icon'            => array(
				'section'  => 'header',
				'type'     => 'string',
				'default'  => '/header-search.png',
				'sanitize' => 'esc_url_raw',
				'label'    => __( 'Search Icon', 'phantom-core' ),
			),
			'header_cart_icon'              => array(
				'section'  => 'header',
				'type'     => 'string',
				'default'  => '/header-cart.png',
				'sanitize' => 'esc_url_raw',
				'label'    => __( 'Cart Icon', 'phantom-core' ),
			),
			'header_login_icon'             => array(
				'section'  => 'header',
				'type'     => 'string',
				'default'  => '/header-admin.png',
				'sanitize' => 'esc_url_raw',
				'label'    => __( 'Login Icon', 'phantom-core' ),
			),
			'header_cart_count'             => array(
				'section'  => 'header',
				'type'     => 'int',
				'default'  => 0,
				'sanitize' => 'absint',
				'label'    => __( 'Cart Count', 'phantom-core' ),
			),
			'menu_font_size'                => array(
				'section'  => 'header',
				'type'     => 'int',
				'default'  => 14,
				'sanitize' => 'absint',
				'label'    => __( 'Menu Font Size', 'phantom-core' ),
			),
			'search_placeholder'            => array(
				'section'  => 'header',
				'type'     => 'string',
				'default'  => 'Search products...',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Search Placeholder', 'phantom-core' ),
			),
			'header_layout'                 => array(
				'section'  => 'header',
				'type'     => 'select',
				'default'  => 'default',
				'options'  => array(
					'default'     => 'Default',
					'centered'    => 'Centered',
					'split'       => 'Split',
					'transparent' => 'Transparent',
				),
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Header Layout', 'phantom-core' ),
			),
			'header_bg'                     => array(
				'section'      => 'header',
				'type'         => 'color',
				'default'      => '#ffffff',
				'sanitize'     => 'sanitize_hex_color',
				'label'        => __( 'Header Background', 'phantom-core' ),
				'css_property' => '--header-bg',
				'css_selector' => ':root',
			),
			'header_text_color'             => array(
				'section'      => 'header',
				'type'         => 'color',
				'default'      => '#222222',
				'sanitize'     => 'sanitize_hex_color',
				'label'        => __( 'Header Text Color', 'phantom-core' ),
				'css_property' => '--header-color',
				'css_selector' => ':root',
			),
			'header_padding_y'              => array(
				'section'      => 'header',
				'type'         => 'int',
				'default'      => 0,
				'sanitize'     => 'absint',
				'label'        => __( 'Header Padding Top/Bottom', 'phantom-core' ),
				'css_property' => '--header-padding-y',
				'css_selector' => ':root',
			),
			'header_padding_x'              => array(
				'section'      => 'header',
				'type'         => 'int',
				'default'      => 0,
				'sanitize'     => 'absint',
				'label'        => __( 'Header Padding Left/Right', 'phantom-core' ),
				'css_property' => '--header-padding-x',
				'css_selector' => ':root',
			),
			'header_border_color'           => array(
				'section'      => 'header',
				'type'         => 'color',
				'default'      => '#e5e5e5',
				'sanitize'     => 'sanitize_hex_color',
				'label'        => __( 'Header Border Color', 'phantom-core' ),
				'css_property' => '--header-border-color',
				'css_selector' => ':root',
			),
			'header_border_width'           => array(
				'section'      => 'header',
				'type'         => 'int',
				'default'      => 0,
				'sanitize'     => 'absint',
				'label'        => __( 'Header Border Width', 'phantom-core' ),
				'css_property' => '--header-border-width',
				'css_selector' => ':root',
			),
			'header_logo_alt'               => array(
				'section'  => 'header',
				'type'     => 'string',
				'default'  => '',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Logo Alt Text', 'phantom-core' ),
			),
			'header_mobile_logo'            => array(
				'section'  => 'header',
				'type'     => 'string',
				'default'  => '',
				'sanitize' => 'esc_url_raw',
				'label'    => __( 'Mobile Logo', 'phantom-core' ),
			),
			'header_mobile_height'          => array(
				'section'      => 'header',
				'type'         => 'int',
				'default'      => 60,
				'sanitize'     => 'absint',
				'label'        => __( 'Mobile Header Height', 'phantom-core' ),
				'css_property' => '--header-mobile-height',
				'css_selector' => ':root',
			),
			'header_banner_height'          => array(
				'section'      => 'header',
				'type'         => 'int',
				'default'      => 600,
				'sanitize'     => 'absint',
				'label'        => __( 'Banner Height', 'phantom-core' ),
				'css_property' => '--banner-height',
				'css_selector' => ':root',
			),
			'header_banner_overlay_color'   => array(
				'section'  => 'header',
				'type'     => 'color',
				'default'  => '#000000',
				'sanitize' => 'sanitize_hex_color',
				'label'    => __( 'Banner Overlay Color', 'phantom-core' ),
			),
			'header_banner_overlay_opacity' => array(
				'section'  => 'header',
				'type'     => 'float',
				'default'  => 0.3,
				'sanitize' => 'floatval',
				'label'    => __( 'Banner Overlay Opacity', 'phantom-core' ),
			),
			'header_transparent_logo'       => array(
				'section'  => 'header',
				'type'     => 'string',
				'default'  => '',
				'sanitize' => 'esc_url_raw',
				'label'    => __( 'Transparent Header Logo', 'phantom-core' ),
			),
		);
	}

	private function section_topbar(): array {
		return array(
			'topbar_enable'     => array(
				'section'  => 'topbar',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Enable Top Bar', 'phantom-core' ),
			),
			'topbar_sale_text'  => array(
				'section'  => 'topbar',
				'type'     => 'string',
				'default'  => 'Summer sale discount off <span class="d-inline-block">60%</span> on all of your orders!',
				'sanitize' => 'wp_kses_post',
				'label'    => __( 'Sale Text', 'phantom-core' ),
			),
			'topbar_bg'         => array(
				'section'  => 'topbar',
				'type'     => 'color',
				'default'  => '#222222',
				'sanitize' => 'sanitize_hex_color',
				'label'    => __( 'Top Bar Background', 'phantom-core' ),
			),
			'topbar_text'       => array(
				'section'  => 'topbar',
				'type'     => 'color',
				'default'  => '#ffffff',
				'sanitize' => 'sanitize_hex_color',
				'label'    => __( 'Top Bar Text Color', 'phantom-core' ),
			),
			'topbar_languages'  => array(
				'section'  => 'topbar',
				'type'     => 'repeater',
				'default'  => array(
					array(
						'lang_flag'    => 'header-flag1.png',
						'lang_code'    => 'EN',
						'lang_url'     => '/',
						'lang_country' => 'UK',
					),
					array(
						'lang_flag'    => 'header-flag2.png',
						'lang_code'    => 'USA',
						'lang_url'     => '/',
						'lang_country' => 'SE',
					),
				),
				'sanitize' => function ( $v ) {
						return $v; },
				'label'    => __( 'Language Switcher', 'phantom-core' ),
			),
			'topbar_currencies' => array(
				'section'  => 'topbar',
				'type'     => 'repeater',
				'default'  => array(
					array(
						'currency_code' => 'USD',
						'currency_url'  => '/',
					),
					array(
						'currency_code' => 'EUR',
						'currency_url'  => '/',
					),
					array(
						'currency_code' => 'GBP',
						'currency_url'  => '/',
					),
					array(
						'currency_code' => 'INR',
						'currency_url'  => '/',
					),
					array(
						'currency_code' => 'PKR',
						'currency_url'  => '/',
					),
				),
				'sanitize' => function ( $v ) {
						return $v; },
				'label'    => __( 'Currency Switcher', 'phantom-core' ),
			),
		);
	}

	private function section_navigation(): array {
		return array(
			'footer_nav'                      => array(
				'section'  => 'navigation',
				'type'     => 'repeater',
				'default'  => array(
					array(
						'label' => 'Home',
						'url'   => '/',
					),
					array(
						'label' => 'Shop',
						'url'   => '/shop/',
					),
					array(
						'label' => 'About',
						'url'   => '/about/',
					),
					array(
						'label' => 'Blog',
						'url'   => '/blog/',
					),
					array(
						'label' => 'Contact',
						'url'   => '/contact/',
					),
				),
				'sanitize' => function ( $v ) {
					return $v; },
				'label'    => __( 'Footer Navigation', 'phantom-core' ),
			),
			'footer_support'                  => array(
				'section'  => 'navigation',
				'type'     => 'repeater',
				'default'  => array(
					array(
						'label' => 'Term of use',
						'url'   => '/term-of-use/',
					),
					array(
						'label' => 'Privacy policy',
						'url'   => '/privacy-policy/',
					),
					array(
						'label' => 'Cookie policy',
						'url'   => '/cookie-policy/',
					),
					array(
						'label' => 'Latest Posts',
						'url'   => '/single-blog/',
					),
					array(
						'label' => 'Care Guide',
						'url'   => '/contact/',
					),
				),
				'sanitize' => function ( $v ) {
						return $v; },
				'label'    => __( 'Support Links', 'phantom-core' ),
			),
			'menu_layout'                     => array(
				'section'  => 'navigation',
				'type'     => 'select',
				'default'  => 'horizontal',
				'options'  => array(
					'horizontal' => 'Horizontal',
					'vertical'   => 'Vertical',
					'hamburger'  => 'Hamburger',
				),
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Menu Layout', 'phantom-core' ),
			),
			'mobile_menu_breakpoint'          => array(
				'section'  => 'navigation',
				'type'     => 'string',
				'default'  => 'md',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Mobile Menu Breakpoint', 'phantom-core' ),
			),
			'menu_animation'                  => array(
				'section'  => 'navigation',
				'type'     => 'select',
				'default'  => 'fade',
				'options'  => array(
					'fade'     => 'Fade',
					'slide'    => 'Slide',
					'slide-up' => 'Slide Up',
				),
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Menu Animation', 'phantom-core' ),
			),
			'menu_indicator_icon'             => array(
				'section'  => 'navigation',
				'type'     => 'string',
				'default'  => '',
				'sanitize' => 'esc_url_raw',
				'label'    => __( 'Dropdown Indicator Icon', 'phantom-core' ),
			),
			'nav_sticky_enable'               => array(
				'section'  => 'navigation',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Enable Sticky Nav', 'phantom-core' ),
			),
			'nav_mega_menu_columns'           => array(
				'section'  => 'navigation',
				'type'     => 'int',
				'default'  => 4,
				'sanitize' => 'absint',
				'label'    => __( 'Mega Menu Columns', 'phantom-core' ),
			),
			'nav_mobile_menu_style'           => array(
				'section'  => 'navigation',
				'type'     => 'select',
				'default'  => 'hamburger',
				'options'  => array(
					'hamburger' => 'Hamburger',
					'slide'     => 'Slide',
					'push'      => 'Push',
				),
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Mobile Menu Style', 'phantom-core' ),
			),
			'nav_dropdown_animation_duration' => array(
				'section'  => 'navigation',
				'type'     => 'string',
				'default'  => '0.3s',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Dropdown Animation Duration', 'phantom-core' ),
			),
			'nav_menu_height'                 => array(
				'section'      => 'navigation',
				'type'         => 'int',
				'default'      => 60,
				'sanitize'     => 'absint',
				'label'        => __( 'Menu Height', 'phantom-core' ),
				'css_property' => '--nav-menu-height',
				'css_selector' => ':root',
			),
			'nav_submenu_width'               => array(
				'section'      => 'navigation',
				'type'         => 'int',
				'default'      => 220,
				'sanitize'     => 'absint',
				'label'        => __( 'Submenu Width', 'phantom-core' ),
				'css_property' => '--nav-submenu-width',
				'css_selector' => ':root',
			),
			'nav_menu_align'                  => array(
				'section'  => 'navigation',
				'type'     => 'select',
				'default'  => 'left',
				'options'  => array(
					'left'   => 'Left',
					'center' => 'Center',
					'right'  => 'Right',
				),
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Menu Alignment', 'phantom-core' ),
			),
			'nav_show_search'                 => array(
				'section'  => 'navigation',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Show Search', 'phantom-core' ),
			),
			'nav_show_cart'                   => array(
				'section'  => 'navigation',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Show Cart', 'phantom-core' ),
			),
			'nav_submenu_animation'           => array(
				'section'  => 'navigation',
				'type'     => 'select',
				'default'  => 'fade',
				'options'  => array(
					'fade'  => 'Fade',
					'slide' => 'Slide',
					'scale' => 'Scale',
				),
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Submenu Animation', 'phantom-core' ),
			),
		);
	}

	private function section_hero(): array {
		return array(
			'home_banner_enable'      => array(
				'section'  => 'hero',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Enable Hero', 'phantom-core' ),
			),
			'home_banner_heading'     => array(
				'section'   => 'hero',
				'type'      => 'string',
				'default'   => 'Claudia Kids Collection',
				'sanitize'  => 'sanitize_text_field',
				'label'     => __( 'Hero Heading', 'phantom-core' ),
				'transport' => 'postMessage',
			),
			'home_banner_title'       => array(
				'section'   => 'hero',
				'type'      => 'string',
				'default'   => 'Little Treasures, <br>Big Smiles!',
				'sanitize'  => 'wp_kses_post',
				'label'     => __( 'Hero Title', 'phantom-core' ),
				'transport' => 'postMessage',
			),
			'home_banner_description' => array(
				'section'   => 'hero',
				'type'      => 'text',
				'default'   => 'Discover a world of fun and joy with our toys, clothes, and essentials that bring smiles.',
				'sanitize'  => 'sanitize_textarea_field',
				'label'     => __( 'Hero Description', 'phantom-core' ),
				'transport' => 'postMessage',
			),
			'home_banner_btn_text'    => array(
				'section'   => 'hero',
				'type'      => 'string',
				'default'   => 'Shop Now',
				'sanitize'  => 'sanitize_text_field',
				'label'     => __( 'Button Text', 'phantom-core' ),
				'transport' => 'postMessage',
			),
			'home_banner_btn_url'     => array(
				'section'   => 'hero',
				'type'      => 'string',
				'default'   => '/shop/',
				'sanitize'  => 'esc_url_raw',
				'label'     => __( 'Button URL', 'phantom-core' ),
				'transport' => 'postMessage',
			),
			'home_banner_img1'        => array(
				'section'   => 'hero',
				'type'      => 'string',
				'default'   => '/banner-img1.png',
				'sanitize'  => 'esc_url_raw',
				'label'     => __( 'Hero Image', 'phantom-core' ),
				'transport' => 'postMessage',
			),
			'home_banner_img2'        => array(
				'section'   => 'hero',
				'type'      => 'string',
				'default'   => '/banner-img2.png',
				'sanitize'  => 'esc_url_raw',
				'label'     => __( 'Hero Image 2', 'phantom-core' ),
				'transport' => 'postMessage',
			),
			'hero_overlay_enable'     => array(
				'section'  => 'hero',
				'type'     => 'bool',
				'default'  => 0,
				'sanitize' => 'absint',
				'label'    => __( 'Enable Overlay', 'phantom-core' ),
			),
			'hero_overlay_color'      => array(
				'section'      => 'hero',
				'type'         => 'color',
				'default'      => '#000000',
				'sanitize'     => 'sanitize_hex_color',
				'label'        => __( 'Overlay Color', 'phantom-core' ),
				'dependencies' => array(
					array(
						'key'   => 'hero_overlay_enable',
						'value' => true,
					),
				),
			),
		);
	}

	private function section_collections(): array {
		return array(
			'home_categories_enable'  => array(
				'section'  => 'collections',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Enable Categories', 'phantom-core' ),
			),
			'home_categories_heading' => array(
				'section'  => 'collections',
				'type'     => 'string',
				'default'  => 'magna aliqua',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Categories Heading', 'phantom-core' ),
			),
			'home_categories_title'   => array(
				'section'  => 'collections',
				'type'     => 'string',
				'default'  => 'Product Categories',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Categories Title', 'phantom-core' ),
			),
			'home_categories'         => array(
				'section'  => 'collections',
				'type'     => 'repeater',
				'default'  => array(
					array(
						'title'    => 'Kids Toys',
						'image'    => '/pc-img1.png',
						'bg_class' => 'bg-light1',
						'url'      => '/shop/',
					),
					array(
						'title'    => 'Clothes',
						'image'    => '/pc-img2.png',
						'bg_class' => 'bg-light2',
						'url'      => '/shop/',
					),
					array(
						'title'    => 'Girls',
						'image'    => '/pc-img3.png',
						'bg_class' => 'bg-light3',
						'url'      => '/shop/',
					),
					array(
						'title'    => 'Accessories',
						'image'    => '/pc-img4.png',
						'bg_class' => 'bg-light4',
						'url'      => '/shop/',
					),
					array(
						'title'    => 'New Born',
						'image'    => '/pc-img5.png',
						'bg_class' => 'bg-light5',
						'url'      => '/shop/',
					),
					array(
						'title'    => 'Boys',
						'image'    => '/pc-img6.png',
						'bg_class' => 'bg-light6',
						'url'      => '/shop/',
					),
				),
				'sanitize' => function ( $v ) {
						return $v; },
				'label'    => __( 'Category Items', 'phantom-core' ),
			),
			'collections_layout'      => array(
				'section'  => 'collections',
				'type'     => 'select',
				'default'  => 'grid',
				'options'  => array(
					'grid'     => 'Grid',
					'carousel' => 'Carousel',
				),
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Collections Layout', 'phantom-core' ),
			),
			'collections_columns'     => array(
				'section'  => 'collections',
				'type'     => 'int',
				'default'  => 3,
				'sanitize' => 'absint',
				'label'    => __( 'Collections Columns', 'phantom-core' ),
			),
		);
	}

	private function section_home_sections(): array {
		return array(
			'home_promotion_enable'          => array(
				'section'  => 'home_sections',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Enable Promotion', 'phantom-core' ),
			),
			'home_promotion_boxes'           => array(
				'section'  => 'home_sections',
				'type'     => 'repeater',
				'default'  => array(
					array(
						'tag'      => 'Trending',
						'title'    => 'Kids Collection',
						'discount' => 'Up to 50% Off',
						'image'    => '/promotion-img1.png',
						'bg_class' => 'bg-light1',
						'link'     => '/shop/',
						'btn_text' => 'Shop Now',
					),
					array(
						'tag'      => 'Latest',
						'title'    => 'Boys Collection',
						'discount' => 'Up to 30% Off',
						'image'    => '/promotion-img2.png',
						'bg_class' => 'bg-light2',
						'link'     => '/shop/',
						'btn_text' => 'Shop Now',
					),
					array(
						'tag'      => 'Hot Deals',
						'title'    => 'Buy One Get One Free',
						'discount' => '',
						'image'    => '/promotion-img3.png',
						'bg_class' => 'bg-light3',
						'link'     => '/shop/',
						'btn_text' => 'Shop Now',
					),
					array(
						'tag'      => 'New Arrivals',
						'title'    => 'Girls Collection',
						'discount' => '',
						'image'    => '/promotion-img4.png',
						'bg_class' => 'bg-light4',
						'link'     => '/shop/',
						'btn_text' => 'Shop Now',
					),
				),
				'sanitize' => function ( $v ) {
					return $v; },
				'label'    => __( 'Promotion Boxes', 'phantom-core' ),
			),
			'home_products_enable'           => array(
				'section'  => 'home_sections',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Enable Products', 'phantom-core' ),
			),
			'home_products_heading'          => array(
				'section'  => 'home_sections',
				'type'     => 'string',
				'default'  => 'Our Collection',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Products Heading', 'phantom-core' ),
			),
			'home_products_title'            => array(
				'section'  => 'home_sections',
				'type'     => 'string',
				'default'  => 'Popular Products',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Products Title', 'phantom-core' ),
			),
			'home_products_count'            => array(
				'section'  => 'home_sections',
				'type'     => 'int',
				'default'  => 8,
				'sanitize' => 'absint',
				'label'    => __( 'Products Count', 'phantom-core' ),
			),
			'home_products_btn_text'         => array(
				'section'  => 'home_sections',
				'type'     => 'string',
				'default'  => 'View All',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Products Button', 'phantom-core' ),
			),
			'home_products_fallback_img'     => array(
				'section'  => 'home_sections',
				'type'     => 'string',
				'default'  => '/product-img1.png',
				'sanitize' => 'esc_url_raw',
				'label'    => __( 'Fallback Image', 'phantom-core' ),
			),
			'home_products_price_multiplier' => array(
				'section'  => 'home_sections',
				'type'     => 'float',
				'default'  => 1.3,
				'sanitize' => 'floatval',
				'label'    => __( 'Price Multiplier', 'phantom-core' ),
			),
			'home_cta_enable'                => array(
				'section'  => 'home_sections',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Enable CTA', 'phantom-core' ),
			),
			'home_cta_title'                 => array(
				'section'  => 'home_sections',
				'type'     => 'string',
				'default'  => 'Mid Season Sale!',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'CTA Title', 'phantom-core' ),
			),
			'home_cta_subtitle'              => array(
				'section'  => 'home_sections',
				'type'     => 'string',
				'default'  => 'Get 20% Off on All New Arrivals!',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'CTA Subtitle', 'phantom-core' ),
			),
			'home_cta_btn_text'              => array(
				'section'  => 'home_sections',
				'type'     => 'string',
				'default'  => 'Get this Deal',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'CTA Button', 'phantom-core' ),
			),
			'home_cta_btn_url'               => array(
				'section'  => 'home_sections',
				'type'     => 'string',
				'default'  => '/shop/',
				'sanitize' => 'esc_url_raw',
				'label'    => __( 'CTA URL', 'phantom-core' ),
			),
			'home_top_selling_enable'        => array(
				'section'  => 'home_sections',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Enable Top Selling', 'phantom-core' ),
			),
			'home_top_selling_title'         => array(
				'section'  => 'home_sections',
				'type'     => 'string',
				'default'  => 'Top Selling Products',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Top Selling Title', 'phantom-core' ),
			),
			'home_top_selling_count'         => array(
				'section'  => 'home_sections',
				'type'     => 'int',
				'default'  => 4,
				'sanitize' => 'absint',
				'label'    => __( 'Top Selling Count', 'phantom-core' ),
			),
			'home_testimonials_enable'       => array(
				'section'  => 'home_sections',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Enable Testimonials', 'phantom-core' ),
			),
			'home_testimonials_heading'      => array(
				'section'  => 'home_sections',
				'type'     => 'string',
				'default'  => 'Testimonials',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Testimonials Heading', 'phantom-core' ),
			),
			'home_testimonials_title'        => array(
				'section'  => 'home_sections',
				'type'     => 'string',
				'default'  => 'Our Client Reviews',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Testimonials Title', 'phantom-core' ),
			),
			'home_testimonials'              => array(
				'section'  => 'home_sections',
				'type'     => 'repeater',
				'default'  => array(
					array(
						'rating' => 5,
						'text'   => '...',
						'name'   => 'Katrina Parker',
						'role'   => 'Happy Client',
						'avatar' => '/review-person1.jpg',
					),
					array(
						'rating' => 5,
						'text'   => '...',
						'name'   => 'Fergus Douchebag',
						'role'   => 'Happy Customer',
						'avatar' => '/review-person2.jpg',
					),
					array(
						'rating' => 5,
						'text'   => '...',
						'name'   => 'Erika Neurth',
						'role'   => 'Happy Customer',
						'avatar' => '/review-person3.jpg',
					),
					array(
						'rating' => 5,
						'text'   => '...',
						'name'   => 'Alina James',
						'role'   => 'Happy Client',
						'avatar' => '/review-person4.jpg',
					),
				),
				'sanitize' => function ( $v ) {
					return $v; },
				'label'    => __( 'Testimonial Items', 'phantom-core' ),
			),
			'home_instagram_enable'          => array(
				'section'  => 'home_sections',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Enable Instagram', 'phantom-core' ),
			),
			'home_instagram_heading'         => array(
				'section'  => 'home_sections',
				'type'     => 'string',
				'default'  => '@claudia instagram',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Instagram Heading', 'phantom-core' ),
			),
			'home_instagram_title'           => array(
				'section'  => 'home_sections',
				'type'     => 'string',
				'default'  => 'Find us On Instagram',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Instagram Title', 'phantom-core' ),
			),
			'home_instagram_images'          => array(
				'section'  => 'home_sections',
				'type'     => 'repeater',
				'default'  => array(
					array(
						'image' => '/follow-image1.jpg',
						'url'   => 'https://www.instagram.com/',
					),
					array(
						'image' => '/follow-image2.jpg',
						'url'   => 'https://www.instagram.com/',
					),
					array(
						'image' => '/follow-image3.jpg',
						'url'   => 'https://www.instagram.com/',
					),
					array(
						'image' => '/follow-image4.jpg',
						'url'   => 'https://www.instagram.com/',
					),
				),
				'sanitize' => function ( $v ) {
					return $v; },
				'label'    => __( 'Instagram Images', 'phantom-core' ),
			),
			'home_benefits_enable'           => array(
				'section'  => 'home_sections',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Enable Benefits', 'phantom-core' ),
			),
			'home_benefits'                  => array(
				'section'  => 'home_sections',
				'type'     => 'repeater',
				'default'  => array(
					array(
						'icon' => '/benefit-icon1.png',
						'text' => 'Free Worldwide Shipping',
					),
					array(
						'icon' => '/benefit-icon2.png',
						'text' => 'Secure Checkout Hassle Free',
					),
					array(
						'icon' => '/benefit-icon3.png',
						'text' => '24/7 Live Chat Support',
					),
					array(
						'icon' => '/benefit-icon4.png',
						'text' => '30 Days Money Back Guarantee',
					),
				),
				'sanitize' => function ( $v ) {
					return $v; },
				'label'    => __( 'Benefit Items', 'phantom-core' ),
			),
			'home_brands_enable'             => array(
				'section'  => 'home_sections',
				'type'     => 'bool',
				'default'  => 0,
				'sanitize' => 'absint',
				'label'    => __( 'Enable Brands', 'phantom-core' ),
			),
			'home_brands_title'              => array(
				'section'  => 'home_sections',
				'type'     => 'string',
				'default'  => 'Shop by Brand',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Brands Title', 'phantom-core' ),
			),
			'home_brands'                    => array(
				'section'  => 'home_sections',
				'type'     => 'repeater',
				'default'  => array(),
				'sanitize' => function ( $v ) {
					return $v; },
				'label'    => __( 'Brand Items', 'phantom-core' ),
			),
			'home_hero_layout'               => array(
				'section'  => 'home_sections',
				'type'     => 'select',
				'default'  => 'default',
				'options'  => array(
					'default'    => 'Default',
					'split'      => 'Split',
					'fullscreen' => 'Fullscreen',
					'minimalist' => 'Minimalist',
				),
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Hero Layout', 'phantom-core' ),
			),
			'home_hero_height'               => array(
				'section'  => 'home_sections',
				'type'     => 'select',
				'default'  => 'full',
				'options'  => array(
					'full' => 'Full Screen',
					'half' => 'Half Screen',
					'auto' => 'Auto',
				),
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Hero Height', 'phantom-core' ),
			),
			'home_hero_subheading'           => array(
				'section'  => 'home_sections',
				'type'     => 'string',
				'default'  => '',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Hero Subheading', 'phantom-core' ),
			),
			'home_hero_parallax'             => array(
				'section'  => 'home_sections',
				'type'     => 'bool',
				'default'  => 0,
				'sanitize' => 'absint',
				'label'    => __( 'Enable Parallax', 'phantom-core' ),
			),
			'home_hero_video'                => array(
				'section'  => 'home_sections',
				'type'     => 'string',
				'default'  => '',
				'sanitize' => 'esc_url_raw',
				'label'    => __( 'Hero Video URL', 'phantom-core' ),
			),
			'home_slider_enable'             => array(
				'section'  => 'home_sections',
				'type'     => 'bool',
				'default'  => 0,
				'sanitize' => 'absint',
				'label'    => __( 'Enable Slider', 'phantom-core' ),
			),
			'home_slider_items'              => array(
				'section'  => 'home_sections',
				'type'     => 'repeater',
				'default'  => array(),
				'sanitize' => function ( $v ) {
					return $v; },
				'label'    => __( 'Slider Items', 'phantom-core' ),
			),
			'home_features_enable'           => array(
				'section'  => 'home_sections',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Enable Features Section', 'phantom-core' ),
			),
			'home_features_heading'          => array(
				'section'  => 'home_sections',
				'type'     => 'string',
				'default'  => 'Features',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Features Heading', 'phantom-core' ),
			),
			'home_features_title'            => array(
				'section'  => 'home_sections',
				'type'     => 'string',
				'default'  => 'Why Choose Us',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Features Title', 'phantom-core' ),
			),
			'home_features_items'            => array(
				'section'  => 'home_sections',
				'type'     => 'repeater',
				'default'  => array(),
				'sanitize' => function ( $v ) {
					return $v; },
				'label'    => __( 'Features Items', 'phantom-core' ),
			),
			'home_blog_enable'               => array(
				'section'  => 'home_sections',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Enable Blog Section', 'phantom-core' ),
			),
			'home_blog_heading'              => array(
				'section'  => 'home_sections',
				'type'     => 'string',
				'default'  => 'Blog',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Blog Heading', 'phantom-core' ),
			),
			'home_blog_title'                => array(
				'section'  => 'home_sections',
				'type'     => 'string',
				'default'  => 'Latest News',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Blog Title', 'phantom-core' ),
			),
			'home_blog_count'                => array(
				'section'  => 'home_sections',
				'type'     => 'int',
				'default'  => 3,
				'sanitize' => 'absint',
				'label'    => __( 'Blog Post Count', 'phantom-core' ),
			),
			'home_section_spacing'           => array(
				'section'      => 'home_sections',
				'type'         => 'int',
				'default'      => 80,
				'sanitize'     => 'absint',
				'label'        => __( 'Section Spacing', 'phantom-core' ),
				'css_property' => '--home-section-spacing',
				'css_selector' => ':root',
			),
		);
	}

	private function section_product_cards(): array {
		return array(
			'shop_columns'         => array(
				'section'  => 'product_cards',
				'type'     => 'int',
				'default'  => 3,
				'sanitize' => 'absint',
				'label'    => __( 'Shop Columns', 'phantom-core' ),
			),
			'card_image_ratio'     => array(
				'section'  => 'product_cards',
				'type'     => 'select',
				'default'  => '1:1',
				'options'  => array(
					'1:1'      => '1:1',
					'3:4'      => '3:4',
					'4:3'      => '4:3',
					'original' => 'Original',
				),
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Image Ratio', 'phantom-core' ),
			),
			'card_hover_effect'    => array(
				'section'  => 'product_cards',
				'type'     => 'select',
				'default'  => 'zoom',
				'options'  => array(
					'none'  => 'None',
					'zoom'  => 'Zoom',
					'slide' => 'Slide',
					'fade'  => 'Fade',
				),
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Hover Effect', 'phantom-core' ),
			),
			'card_quick_view'      => array(
				'section'  => 'product_cards',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Enable Quick View', 'phantom-core' ),
			),
			'card_sale_badge'      => array(
				'section'  => 'product_cards',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Show Sale Badge', 'phantom-core' ),
			),
			'card_sale_badge_text' => array(
				'section'  => 'product_cards',
				'type'     => 'string',
				'default'  => 'Sale!',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Sale Badge Text', 'phantom-core' ),
			),
			'card_show_rating'     => array(
				'section'  => 'product_cards',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Show Rating', 'phantom-core' ),
			),
			'card_show_cart_btn'   => array(
				'section'  => 'product_cards',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Show Cart Button', 'phantom-core' ),
			),
		);
	}

	private function section_shop_page(): array {
		return array(
			'shop_title'             => array(
				'section'  => 'shop_page',
				'type'     => 'string',
				'default'  => 'Shop',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Shop Page Title', 'phantom-core' ),
			),
			'shop_products_per_page' => array(
				'section'  => 'shop_page',
				'type'     => 'int',
				'default'  => 12,
				'sanitize' => 'absint',
				'label'    => __( 'Products Per Page', 'phantom-core' ),
			),
			'shop_enable_sidebar'    => array(
				'section'  => 'shop_page',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Enable Sidebar', 'phantom-core' ),
			),
			'shop_enable'            => array(
				'section'  => 'shop_page',
				'type'     => 'bool',
				'default'  => true,
				'sanitize' => 'absint',
				'label'    => __( 'Enable Shop', 'phantom-core' ),
			),
			'shop_layout'            => array(
				'section'  => 'shop_page',
				'type'     => 'select',
				'default'  => 'sidebar-left',
				'options'  => array(
					'full-width'    => 'Full Width',
					'sidebar-left'  => 'Sidebar Left',
					'sidebar-right' => 'Sidebar Right',
				),
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Shop Layout', 'phantom-core' ),
			),
			'shop_pagination'        => array(
				'section'  => 'shop_page',
				'type'     => 'select',
				'default'  => 'numbered',
				'options'  => array(
					'numbered'        => 'Numbered',
					'load-more'       => 'Load More',
					'infinite-scroll' => 'Infinite Scroll',
				),
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Pagination Style', 'phantom-core' ),
			),
			'shop_results_count'     => array(
				'section'  => 'shop_page',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Show Results Count', 'phantom-core' ),
			),
			'shop_sorting'           => array(
				'section'  => 'shop_page',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Show Sorting Dropdown', 'phantom-core' ),
			),
			'shop_per_page'          => array(
				'section'  => 'shop_page',
				'type'     => 'number',
				'default'  => 12,
				'sanitize' => 'absint',
				'label'    => __( 'Products Per Page', 'phantom-core' ),
			),
			'show_shop_toolbar'      => array(
				'section'  => 'shop_page',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Show Shop Toolbar', 'phantom-core' ),
			),
		);
	}

	private function section_product_page(): array {
		return array(
			'product_related_title'                     => array(
				'section'  => 'product_page',
				'type'     => 'string',
				'default'  => 'Related Products',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Related Products Title', 'phantom-core' ),
			),
			'product_related_count'                     => array(
				'section'  => 'product_page',
				'type'     => 'int',
				'default'  => 12,
				'sanitize' => 'absint',
				'label'    => __( 'Related Products Count', 'phantom-core' ),
			),
			'product_related_sale_tag'                  => array(
				'section'  => 'product_page',
				'type'     => 'string',
				'default'  => 'Sale',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Sale Tag Text', 'phantom-core' ),
			),
			'product_more_title'                        => array(
				'section'  => 'product_page',
				'type'     => 'string',
				'default'  => 'More Products',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'More Products Title', 'phantom-core' ),
			),
			'product_detail_title'                      => array(
				'section'  => 'product_page',
				'type'     => 'string',
				'default'  => 'Product Details',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Detail Section Title', 'phantom-core' ),
			),
			'product_detail_name'                       => array(
				'section'  => 'product_page',
				'type'     => 'string',
				'default'  => 'Dreamy Day Pajamas',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Default Product Name', 'phantom-core' ),
			),
			'product_detail_price'                      => array(
				'section'  => 'product_page',
				'type'     => 'string',
				'default'  => '$38.00',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Default Price', 'phantom-core' ),
			),
			'product_detail_original_price'             => array(
				'section'  => 'product_page',
				'type'     => 'string',
				'default'  => '$89.00',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Original Price', 'phantom-core' ),
			),
			'product_detail_rating'                     => array(
				'section'  => 'product_page',
				'type'     => 'string',
				'default'  => '4.9/5',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Default Rating', 'phantom-core' ),
			),
			'product_detail_desc'                       => array(
				'section'  => 'product_page',
				'type'     => 'text',
				'default'  => 'Neque porro ruisquam est aui dolorem iesum ruia do sit amet consectetur, adies velit, sed num eius modi tempoa incidunt ut labore et dolore magna aute re dolor in reprehenderit in velit esse cillum eaque ipsa quae ab illo inventore veritatis.',
				'sanitize' => 'sanitize_textarea_field',
				'label'    => __( 'Default Description', 'phantom-core' ),
			),
			'product_detail_stock'                      => array(
				'section'  => 'product_page',
				'type'     => 'string',
				'default'  => 'In stock',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Stock Status', 'phantom-core' ),
			),
			'product_detail_sku_label'                  => array(
				'section'  => 'product_page',
				'type'     => 'string',
				'default'  => 'SKU:',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'SKU Label', 'phantom-core' ),
			),
			'product_detail_sku_value'                  => array(
				'section'  => 'product_page',
				'type'     => 'string',
				'default'  => 'HD_158',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'SKU Value', 'phantom-core' ),
			),
			'product_detail_categories_label'           => array(
				'section'  => 'product_page',
				'type'     => 'string',
				'default'  => 'Categories:',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Categories Label', 'phantom-core' ),
			),
			'product_detail_categories_value'           => array(
				'section'  => 'product_page',
				'type'     => 'string',
				'default'  => 'Decor, Home Decor, Furniture, Interior',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Categories Value', 'phantom-core' ),
			),
			'product_detail_tags_label'                 => array(
				'section'  => 'product_page',
				'type'     => 'string',
				'default'  => 'Tags:',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Tags Label', 'phantom-core' ),
			),
			'product_detail_tags_value'                 => array(
				'section'  => 'product_page',
				'type'     => 'string',
				'default'  => 'Pjs, Pajamas',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Tags Value', 'phantom-core' ),
			),
			'product_detail_color_label'                => array(
				'section'  => 'product_page',
				'type'     => 'string',
				'default'  => 'Color:',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Color Label', 'phantom-core' ),
			),
			'product_detail_add_to_cart'                => array(
				'section'  => 'product_page',
				'type'     => 'string',
				'default'  => 'Add to Cart',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Add to Cart Text', 'phantom-core' ),
			),
			'product_detail_wishlist'                   => array(
				'section'  => 'product_page',
				'type'     => 'string',
				'default'  => 'Add to wishlist',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Wishlist Text', 'phantom-core' ),
			),
			'product_detail_compare'                    => array(
				'section'  => 'product_page',
				'type'     => 'string',
				'default'  => 'Compare',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Compare Text', 'phantom-core' ),
			),
			'product_detail_safe_checkout'              => array(
				'section'  => 'product_page',
				'type'     => 'string',
				'default'  => 'Guaranteed Safe Checkout:',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Safe Checkout Text', 'phantom-core' ),
			),
			'product_detail_tab_description'            => array(
				'section'  => 'product_page',
				'type'     => 'string',
				'default'  => 'Description',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Tab: Description', 'phantom-core' ),
			),
			'product_detail_tab_additional'             => array(
				'section'  => 'product_page',
				'type'     => 'string',
				'default'  => 'Additional Information',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Tab: Additional', 'phantom-core' ),
			),
			'product_detail_tab_reviews'                => array(
				'section'  => 'product_page',
				'type'     => 'string',
				'default'  => 'Reviews',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Tab: Reviews', 'phantom-core' ),
			),
			'product_detail_info_features_label'        => array(
				'section'  => 'product_page',
				'type'     => 'string',
				'default'  => 'Features',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Features Label', 'phantom-core' ),
			),
			'product_detail_info_features_value'        => array(
				'section'  => 'product_page',
				'type'     => 'string',
				'default'  => 'Adjustable, Foldable, Cushioned, Storage, Reclining',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Features Value', 'phantom-core' ),
			),
			'product_detail_info_materials_label'       => array(
				'section'  => 'product_page',
				'type'     => 'string',
				'default'  => 'Materials',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Materials Label', 'phantom-core' ),
			),
			'product_detail_info_materials_value'       => array(
				'section'  => 'product_page',
				'type'     => 'string',
				'default'  => 'Wood, Metal, Plastic, Glass, Upholstery',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Materials Value', 'phantom-core' ),
			),
			'product_detail_info_types_label'           => array(
				'section'  => 'product_page',
				'type'     => 'string',
				'default'  => 'Types',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Types Label', 'phantom-core' ),
			),
			'product_detail_info_types_value'           => array(
				'section'  => 'product_page',
				'type'     => 'string',
				'default'  => 'Chairs, Tables, Sofas, Beds, Cabinets',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Types Value', 'phantom-core' ),
			),
			'product_detail_review_heading'             => array(
				'section'  => 'product_page',
				'type'     => 'string',
				'default'  => 'Review',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Review Heading', 'phantom-core' ),
			),
			'product_detail_review_title'               => array(
				'section'  => 'product_page',
				'type'     => 'string',
				'default'  => 'Post Your Review',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Review Title', 'phantom-core' ),
			),
			'product_detail_review_btn'                 => array(
				'section'  => 'product_page',
				'type'     => 'string',
				'default'  => 'Post Review',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Review Button', 'phantom-core' ),
			),
			'product_detail_review_name_placeholder'    => array(
				'section'  => 'product_page',
				'type'     => 'string',
				'default'  => 'Your Name',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Name Placeholder', 'phantom-core' ),
			),
			'product_detail_review_email_placeholder'   => array(
				'section'  => 'product_page',
				'type'     => 'string',
				'default'  => 'Your Email',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Email Placeholder', 'phantom-core' ),
			),
			'product_detail_review_comment_placeholder' => array(
				'section'  => 'product_page',
				'type'     => 'string',
				'default'  => 'Your Review',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Comment Placeholder', 'phantom-core' ),
			),
			'product_detail_review_author'              => array(
				'section'  => 'product_page',
				'type'     => 'string',
				'default'  => 'Jonathan Andrew',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Review Author', 'phantom-core' ),
			),
			'product_detail_review_role'                => array(
				'section'  => 'product_page',
				'type'     => 'string',
				'default'  => 'Happy Customer',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Review Role', 'phantom-core' ),
			),
			'product_layout'                            => array(
				'section'  => 'product_page',
				'type'     => 'select',
				'default'  => 'standard',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Product Layout', 'phantom-core' ),
				'choices'  => array( 'standard' => 'Standard', 'fullwidth' => 'Full Width', 'sticky' => 'Sticky Gallery' ),
			),
		);
	}

	private function section_woocommerce(): array {
		return array(
			'cart_title'                 => array(
				'section'  => 'woocommerce',
				'type'     => 'string',
				'default'  => 'Cart',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Cart Title', 'phantom-core' ),
			),
			'cart_heading'               => array(
				'section'  => 'woocommerce',
				'type'     => 'string',
				'default'  => 'Shopping Cart',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Cart Heading', 'phantom-core' ),
			),
			'cart_continue_text'         => array(
				'section'  => 'woocommerce',
				'type'     => 'string',
				'default'  => 'Continue Shopping',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Continue Shopping', 'phantom-core' ),
			),
			'cart_summary_title'         => array(
				'section'  => 'woocommerce',
				'type'     => 'string',
				'default'  => 'Summary',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Summary Title', 'phantom-core' ),
			),
			'cart_tax_label'             => array(
				'section'  => 'woocommerce',
				'type'     => 'string',
				'default'  => 'Estimate Tax',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Tax Label', 'phantom-core' ),
			),
			'cart_tax_description'       => array(
				'section'  => 'woocommerce',
				'type'     => 'string',
				'default'  => 'Enter your billing address to get a tax estimate.',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Tax Description', 'phantom-core' ),
			),
			'cart_country_label'         => array(
				'section'  => 'woocommerce',
				'type'     => 'string',
				'default'  => 'Country',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Country Label', 'phantom-core' ),
			),
			'cart_country_placeholder'   => array(
				'section'  => 'woocommerce',
				'type'     => 'string',
				'default'  => 'Select Country',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Country Placeholder', 'phantom-core' ),
			),
			'cart_subtotal_label'        => array(
				'section'  => 'woocommerce',
				'type'     => 'string',
				'default'  => 'Sub Total',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Subtotal Label', 'phantom-core' ),
			),
			'cart_total_label'           => array(
				'section'  => 'woocommerce',
				'type'     => 'string',
				'default'  => 'Total',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Total Label', 'phantom-core' ),
			),
			'cart_discount_label'        => array(
				'section'  => 'woocommerce',
				'type'     => 'string',
				'default'  => 'Apply Discount Code',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Discount Label', 'phantom-core' ),
			),
			'cart_discount_placeholder'  => array(
				'section'  => 'woocommerce',
				'type'     => 'string',
				'default'  => 'Enter discount code',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Discount Placeholder', 'phantom-core' ),
			),
			'cart_discount_btn'          => array(
				'section'  => 'woocommerce',
				'type'     => 'string',
				'default'  => 'Apply Discount',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Discount Button', 'phantom-core' ),
			),
			'cart_checkout_text'         => array(
				'section'  => 'woocommerce',
				'type'     => 'string',
				'default'  => 'Proceed to checkout',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Checkout Button', 'phantom-core' ),
			),
			'cart_coupon_enable'         => array(
				'section'  => 'woocommerce',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Enable Coupon', 'phantom-core' ),
			),
			'cart_cross_sell_enable'     => array(
				'section'  => 'woocommerce',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Enable Cross-sell', 'phantom-core' ),
			),
			'cart_state_label'           => array(
				'section'  => 'woocommerce',
				'type'     => 'string',
				'default'  => 'State/Province',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'State Label', 'phantom-core' ),
			),
			'cart_state_placeholder'     => array(
				'section'  => 'woocommerce',
				'type'     => 'string',
				'default'  => 'Select State',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'State Placeholder', 'phantom-core' ),
			),
			'cart_zip_label'             => array(
				'section'  => 'woocommerce',
				'type'     => 'string',
				'default'  => 'Zip/ postal code',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Zip Label', 'phantom-core' ),
			),
			'checkout_title'             => array(
				'section'  => 'woocommerce',
				'type'     => 'string',
				'default'  => 'Checkout',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Checkout Title', 'phantom-core' ),
			),
			'checkout_step_shipping'     => array(
				'section'  => 'woocommerce',
				'type'     => 'string',
				'default'  => 'Shipping',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Step: Shipping', 'phantom-core' ),
			),
			'checkout_step_payment'      => array(
				'section'  => 'woocommerce',
				'type'     => 'string',
				'default'  => 'Review & Payments',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Step: Payment', 'phantom-core' ),
			),
			'checkout_shipping_label'    => array(
				'section'  => 'woocommerce',
				'type'     => 'string',
				'default'  => 'Shipping Address:',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Shipping Label', 'phantom-core' ),
			),
			'checkout_email_label'       => array(
				'section'  => 'woocommerce',
				'type'     => 'string',
				'default'  => 'Email Address',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Email Label', 'phantom-core' ),
			),
			'checkout_next_btn'          => array(
				'section'  => 'woocommerce',
				'type'     => 'string',
				'default'  => 'Next',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Next Button', 'phantom-core' ),
			),
			'checkout_terms_text'        => array(
				'section'  => 'woocommerce',
				'type'     => 'string',
				'default'  => 'By clicking the button, you agree to the',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Terms Text', 'phantom-core' ),
			),
			'checkout_terms_link_text'   => array(
				'section'  => 'woocommerce',
				'type'     => 'string',
				'default'  => 'Terms and Conditions',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Terms Link Text', 'phantom-core' ),
			),
			'shop_catalog_mode'          => array(
				'section'  => 'woocommerce',
				'type'     => 'bool',
				'default'  => 0,
				'sanitize' => 'absint',
				'label'    => __( 'Enable Catalog Mode', 'phantom-core' ),
			),
			'shop_cart_icon_style'       => array(
				'section'  => 'woocommerce',
				'type'     => 'select',
				'default'  => 'default',
				'options'  => array(
					'default' => 'Default',
					'filled'  => 'Filled',
					'outline' => 'Outline',
				),
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Cart Icon Style', 'phantom-core' ),
			),
			'shop_wishlist_enable'       => array(
				'section'  => 'woocommerce',
				'type'     => 'bool',
				'default'  => 0,
				'sanitize' => 'absint',
				'label'    => __( 'Enable Wishlist', 'phantom-core' ),
			),
			'shop_product_image_zoom'    => array(
				'section'  => 'woocommerce',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Enable Image Zoom', 'phantom-core' ),
			),
			'shop_variant_swatches'      => array(
				'section'  => 'woocommerce',
				'type'     => 'bool',
				'default'  => 0,
				'sanitize' => 'absint',
				'label'    => __( 'Enable Variant Swatches', 'phantom-core' ),
			),
			'shop_stock_display'         => array(
				'section'  => 'woocommerce',
				'type'     => 'select',
				'default'  => 'always',
				'options'  => array(
					'always'       => 'Always',
					'low_stock'    => 'Low Stock Only',
					'out_of_stock' => 'Out of Stock Only',
				),
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Stock Display', 'phantom-core' ),
			),
			'shop_review_enable'         => array(
				'section'  => 'woocommerce',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Enable Reviews', 'phantom-core' ),
			),
			'shop_ajax_add_to_cart'      => array(
				'section'  => 'woocommerce',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'AJAX Add to Cart', 'phantom-core' ),
			),
			'shop_minicart_enable'       => array(
				'section'  => 'woocommerce',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Enable Mini Cart', 'phantom-core' ),
			),
			'shop_checkout_style'        => array(
				'section'  => 'woocommerce',
				'type'     => 'select',
				'default'  => 'multi-step',
				'options'  => array(
					'multi-step'  => 'Multi Step',
					'single-page' => 'Single Page',
				),
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Checkout Style', 'phantom-core' ),
			),
			'shop_order_tracking_enable' => array(
				'section'  => 'woocommerce',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Enable Order Tracking', 'phantom-core' ),
			),
			'shop_sale_flash_style'      => array(
				'section'  => 'woocommerce',
				'type'     => 'select',
				'default'  => 'circle',
				'options'  => array(
					'circle' => 'Circle',
					'square' => 'Square',
					'ribbon' => 'Ribbon',
				),
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Sale Flash Style', 'phantom-core' ),
			),
			'shop_show_stock_badge'      => array(
				'section'  => 'woocommerce',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Show Stock Badge', 'phantom-core' ),
			),
		);
	}

	private function section_blog(): array {
		return array(
			'blog_enable'                           => array(
				'section'  => 'blog',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Enable Blog', 'phantom-core' ),
			),
			'blog_title'                            => array(
				'section'  => 'blog',
				'type'     => 'string',
				'default'  => 'Blog',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Blog Title', 'phantom-core' ),
			),
			'blog_posts_per_page'                   => array(
				'section'  => 'blog',
				'type'     => 'int',
				'default'  => 6,
				'sanitize' => 'absint',
				'label'    => __( 'Posts Per Page', 'phantom-core' ),
			),
			'blog_tabs'                             => array(
				'section'  => 'blog',
				'type'     => 'array',
				'default'  => array( 'All', 'Advices', 'Announcements', 'News', 'Consultation', 'Development' ),
				'sanitize' => function ( $v ) {
					return $v; },
				'label'    => __( 'Blog Tabs', 'phantom-core' ),
			),
			'blog_layout'                           => array(
				'section'  => 'blog',
				'type'     => 'select',
				'default'  => 'grid',
				'options'  => array(
					'grid'    => 'Grid',
					'list'    => 'List',
					'classic' => 'Classic',
				),
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Blog Layout', 'phantom-core' ),
			),
			'blog_show_sidebar'                     => array(
				'section'  => 'blog',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Show Sidebar', 'phantom-core' ),
			),
			'blog_show_featured_image'              => array(
				'section'  => 'blog',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Show Featured Image', 'phantom-core' ),
			),
			'blog_show_author'                      => array(
				'section'  => 'blog',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Show Author', 'phantom-core' ),
			),
			'single_blog_show_related'              => array(
				'section'  => 'blog',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Show Related Posts', 'phantom-core' ),
			),
			'single_blog_related_title'             => array(
				'section'  => 'blog',
				'type'     => 'string',
				'default'  => 'Related Posts',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Related Posts Title', 'phantom-core' ),
			),
			'blog_excerpt_length'                   => array(
				'section'  => 'blog',
				'type'     => 'int',
				'default'  => 55,
				'sanitize' => 'absint',
				'label'    => __( 'Excerpt Length', 'phantom-core' ),
			),
			'blog_read_more_text'                   => array(
				'section'  => 'blog',
				'type'     => 'string',
				'default'  => 'Read More',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Read More Text', 'phantom-core' ),
			),
			'blog_show_date'                        => array(
				'section'  => 'blog',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Show Date', 'phantom-core' ),
			),
			'blog_show_categories'                  => array(
				'section'  => 'blog',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Show Categories', 'phantom-core' ),
			),
			'blog_show_tags'                        => array(
				'section'  => 'blog',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Show Tags', 'phantom-core' ),
			),
			'blog_enable_comments'                  => array(
				'section'  => 'blog',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Enable Comments', 'phantom-core' ),
			),
			'blog_show_author_box'                  => array(
				'section'  => 'blog',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Show Author Box', 'phantom-core' ),
			),
			'blog_show_related_count'               => array(
				'section'  => 'blog',
				'type'     => 'int',
				'default'  => 3,
				'sanitize' => 'absint',
				'label'    => __( 'Related Posts Count', 'phantom-core' ),
			),
			'blog_sidebar_position'                 => array(
				'section'  => 'blog',
				'type'     => 'select',
				'default'  => 'right',
				'options'  => array(
					'left'  => 'Left',
					'right' => 'Right',
				),
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Sidebar Position', 'phantom-core' ),
			),
			'blog_sticky_sidebar'                   => array(
				'section'  => 'blog',
				'type'     => 'bool',
				'default'  => 0,
				'sanitize' => 'absint',
				'label'    => __( 'Sticky Sidebar', 'phantom-core' ),
			),
			'single_blog_author_image'              => array(
				'section'  => 'blog',
				'type'     => 'string',
				'default'  => '',
				'sanitize' => 'esc_url_raw',
				'label'    => __( 'Author Image', 'phantom-core' ),
			),
			'single_blog_author_name'               => array(
				'section'  => 'blog',
				'type'     => 'string',
				'default'  => 'Billy wallson',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Author Name', 'phantom-core' ),
			),
			'single_blog_author_role'               => array(
				'section'  => 'blog',
				'type'     => 'string',
				'default'  => 'Senior Director',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Author Role', 'phantom-core' ),
			),
			'single_blog_author_bio'                => array(
				'section'  => 'blog',
				'type'     => 'text',
				'default'  => '',
				'sanitize' => 'sanitize_textarea_field',
				'label'    => __( 'Author Bio', 'phantom-core' ),
			),
			'single_blog_quote_image'               => array(
				'section'  => 'blog',
				'type'     => 'string',
				'default'  => '',
				'sanitize' => 'esc_url_raw',
				'label'    => __( 'Quote Image', 'phantom-core' ),
			),
			'single_blog_quote_text'                => array(
				'section'  => 'blog',
				'type'     => 'text',
				'default'  => '',
				'sanitize' => 'sanitize_textarea_field',
				'label'    => __( 'Quote Text', 'phantom-core' ),
			),
			'single_blog_prev_text'                 => array(
				'section'  => 'blog',
				'type'     => 'string',
				'default'  => 'Prev',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Previous Text', 'phantom-core' ),
			),
			'single_blog_next_text'                 => array(
				'section'  => 'blog',
				'type'     => 'string',
				'default'  => 'Next',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Next Text', 'phantom-core' ),
			),
			'single_blog_tags_heading'              => array(
				'section'  => 'blog',
				'type'     => 'string',
				'default'  => 'Related Tags',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Tags Heading', 'phantom-core' ),
			),
			'single_blog_social_heading'            => array(
				'section'  => 'blog',
				'type'     => 'string',
				'default'  => 'Social Share',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Social Heading', 'phantom-core' ),
			),
			'single_blog_reply_text'                => array(
				'section'  => 'blog',
				'type'     => 'string',
				'default'  => 'Reply',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Reply Text', 'phantom-core' ),
			),
			'single_blog_comment_heading'           => array(
				'section'  => 'blog',
				'type'     => 'string',
				'default'  => 'Leave a Comment',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Comment Heading', 'phantom-core' ),
			),
			'single_blog_comment_placeholder'       => array(
				'section'  => 'blog',
				'type'     => 'string',
				'default'  => 'Enter your comment here...',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Comment Placeholder', 'phantom-core' ),
			),
			'single_blog_comment_name_placeholder'  => array(
				'section'  => 'blog',
				'type'     => 'string',
				'default'  => 'Your name',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Name Placeholder', 'phantom-core' ),
			),
			'single_blog_comment_email_placeholder' => array(
				'section'  => 'blog',
				'type'     => 'string',
				'default'  => 'Your e-mail',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Email Placeholder', 'phantom-core' ),
			),
			'single_blog_comment_btn'               => array(
				'section'  => 'blog',
				'type'     => 'string',
				'default'  => 'Post Comment',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Comment Button', 'phantom-core' ),
			),
			'single_blog_search_heading'            => array(
				'section'  => 'blog',
				'type'     => 'string',
				'default'  => 'Search News',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Search Heading', 'phantom-core' ),
			),
			'single_blog_search_placeholder'        => array(
				'section'  => 'blog',
				'type'     => 'string',
				'default'  => 'Search Here...',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Search Placeholder', 'phantom-core' ),
			),
			'single_blog_categories_heading'        => array(
				'section'  => 'blog',
				'type'     => 'string',
				'default'  => 'Popular Category',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Categories Heading', 'phantom-core' ),
			),
			'single_blog_follow_heading'            => array(
				'section'  => 'blog',
				'type'     => 'string',
				'default'  => 'Follow Us',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Follow Heading', 'phantom-core' ),
			),
			'single_blog_feeds_heading'             => array(
				'section'  => 'blog',
				'type'     => 'string',
				'default'  => 'Feeds',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Feeds Heading', 'phantom-core' ),
			),
			'single_blog_tags_sidebar_heading'      => array(
				'section'  => 'blog',
				'type'     => 'string',
				'default'  => 'Tags',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Tags Sidebar Heading', 'phantom-core' ),
			),
			'blog_excerpt_more'                     => array(
				'section'  => 'blog',
				'type'     => 'string',
				'default'  => '...',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Excerpt More Text', 'phantom-core' ),
			),
			'blog_single_layout'                    => array(
				'section'  => 'blog',
				'type'     => 'select',
				'default'  => 'full',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Single Post Layout', 'phantom-core' ),
				'choices'  => array( 'full' => 'Full', 'sidebar' => 'With Sidebar' ),
			),
			'blog_archive_layout'                   => array(
				'section'  => 'blog',
				'type'     => 'select',
				'default'  => 'grid',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Archive Layout', 'phantom-core' ),
				'choices'  => array( 'grid' => 'Grid', 'list' => 'List', 'masonry' => 'Masonry' ),
			),
			'blog_wpm'                              => array(
				'section'  => 'blog',
				'type'     => 'number',
				'default'  => 200,
				'sanitize' => 'absint',
				'label'    => __( 'Words Per Minute', 'phantom-core' ),
				'desc'     => __( 'Used for estimated reading time calculation.', 'phantom-core' ),
			),
			'blog_newsletter_heading'               => array(
				'section'  => 'blog',
				'type'     => 'text',
				'default'  => 'Subscribe to Our <br> Newsletter :',
				'sanitize' => 'wp_kses_post',
				'label'    => __( 'Newsletter Heading', 'phantom-core' ),
			),
			'blog_newsletter_placeholder'           => array(
				'section'  => 'blog',
				'type'     => 'string',
				'default'  => 'Enter Your Email Address:',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Newsletter Placeholder', 'phantom-core' ),
			),
			'blog_newsletter_button'                => array(
				'section'  => 'blog',
				'type'     => 'string',
				'default'  => 'Submit',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Newsletter Button', 'phantom-core' ),
			),
		);
	}

	private function section_footer(): array {
		return array(
			'footer_logo'                 => array(
				'section'  => 'footer',
				'type'     => 'string',
				'default'  => '/wp-content/plugins/phantom-core/frontend/assets/images/footer-logo.png',
				'sanitize' => 'esc_url_raw',
				'label'    => __( 'Footer Logo', 'phantom-core' ),
			),
			'footer_about_text'           => array(
				'section'  => 'footer',
				'type'     => 'text',
				'default'  => 'Duis aute irure dolor in reprehenderit in voluptate velit cillum dolore eu fugiat nulla pariatur ccaecat cupidata proident, sunt in culpa officia deserunt mollit.',
				'sanitize' => 'sanitize_textarea_field',
				'label'    => __( 'About Text', 'phantom-core' ),
			),
			'footer_copyright'            => array(
				'section'  => 'footer',
				'type'     => 'string',
				'default'  => 'Copyright (c) %d claudia.com All rights reserved.',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Copyright Text', 'phantom-core' ),
			),
			'footer_payment_cards'        => array(
				'section'  => 'footer',
				'type'     => 'string',
				'default'  => '/wp-content/plugins/phantom-core/frontend/assets/images/payment-cards.png',
				'sanitize' => 'esc_url_raw',
				'label'    => __( 'Payment Cards Image', 'phantom-core' ),
			),
			'footer_phone'                => array(
				'section'  => 'footer',
				'type'     => 'string',
				'default'  => '+1235 211 5236',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Phone', 'phantom-core' ),
			),
			'footer_email'                => array(
				'section'  => 'footer',
				'type'     => 'string',
				'default'  => 'hello@claudia.com',
				'sanitize' => 'sanitize_email',
				'label'    => __( 'Email', 'phantom-core' ),
			),
			'footer_address'              => array(
				'section'  => 'footer',
				'type'     => 'text',
				'default'  => '121 King Street Melbourne, <br>3000, Australia',
				'sanitize' => 'wp_kses_post',
				'label'    => __( 'Address', 'phantom-core' ),
			),
			'footer_text'                 => array(
				'section'      => 'footer',
				'type'         => 'color',
				'default'      => '#999999',
				'sanitize'     => 'sanitize_hex_color',
				'label'        => __( 'Text Color', 'phantom-core' ),
				'css_property' => '--footer-text',
				'css_selector' => ':root',
			),
			'footer_heading_text'         => array(
				'section'      => 'footer',
				'type'         => 'color',
				'default'      => '#ffffff',
				'sanitize'     => 'sanitize_hex_color',
				'label'        => __( 'Heading Color', 'phantom-core' ),
				'css_property' => '--footer-heading',
				'css_selector' => ':root',
			),
			'footer_link'                 => array(
				'section'      => 'footer',
				'type'         => 'color',
				'default'      => '#cccccc',
				'sanitize'     => 'sanitize_hex_color',
				'label'        => __( 'Link Color', 'phantom-core' ),
				'css_property' => '--footer-link',
				'css_selector' => ':root',
			),
			'footer_social_links'         => array(
				'section'  => 'footer',
				'type'     => 'repeater',
				'default'  => array(
					array(
						'platform' => 'Facebook',
						'url'      => 'https://www.facebook.com/',
					),
					array(
						'platform' => 'Instagram',
						'url'      => 'https://www.instagram.com/',
					),
					array(
						'platform' => 'YouTube',
						'url'      => 'https://www.youtube.com/',
					),
				),
				'sanitize' => function ( $v ) {
						return $v; },
				'label'    => __( 'Social Links', 'phantom-core' ),
			),
			'footer_social'               => array(
				'section'  => 'footer',
				'type'     => 'repeater',
				'default'  => array(
					array(
						'platform' => 'Facebook',
						'url'      => 'https://www.facebook.com/',
					),
					array(
						'platform' => 'Instagram',
						'url'      => 'https://www.instagram.com/',
					),
					array(
						'platform' => 'YouTube',
						'url'      => 'https://www.youtube.com/',
					),
				),
				'sanitize' => function ( $v ) {
						return $v; },
				'label'    => __( 'Social (Alt)', 'phantom-core' ),
			),
			'newsletter_heading'          => array(
				'section'  => 'footer',
				'type'     => 'string',
				'default'  => 'Subscribe to Our Newsletter :',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Newsletter Heading', 'phantom-core' ),
			),
			'newsletter_placeholder'      => array(
				'section'  => 'footer',
				'type'     => 'string',
				'default'  => 'Enter Your Email Address:',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Newsletter Placeholder', 'phantom-core' ),
			),
			'newsletter_btn_text'         => array(
				'section'  => 'footer',
				'type'     => 'string',
				'default'  => 'Subscribe',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Newsletter Button', 'phantom-core' ),
			),
			'newsletter_action_url'       => array(
				'section'  => 'footer',
				'type'     => 'string',
				'default'  => '/wp-forms/subscribe',
				'sanitize' => 'esc_url_raw',
				'label'    => __( 'Newsletter Action URL', 'phantom-core' ),
			),
			'footer_enable'               => array(
				'section'  => 'footer',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Enable Footer', 'phantom-core' ),
			),
			'footer_columns'              => array(
				'section'  => 'footer',
				'type'     => 'int',
				'default'  => 4,
				'sanitize' => 'absint',
				'label'    => __( 'Footer Columns', 'phantom-core' ),
			),
			'footer_width'                => array(
				'section'  => 'footer',
				'type'     => 'select',
				'default'  => 'full',
				'options'  => array(
					'full'      => 'Full Width',
					'boxed'     => 'Boxed',
					'contained' => 'Contained',
				),
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Footer Width', 'phantom-core' ),
			),
			'footer_border_enable'        => array(
				'section'  => 'footer',
				'type'     => 'bool',
				'default'  => 0,
				'sanitize' => 'absint',
				'label'    => __( 'Enable Footer Border', 'phantom-core' ),
			),
			'footer_border_color'         => array(
				'section'      => 'footer',
				'type'         => 'color',
				'default'      => '#333333',
				'sanitize'     => 'sanitize_hex_color',
				'label'        => __( 'Footer Border Color', 'phantom-core' ),
				'css_property' => '--footer-border-color',
				'css_selector' => ':root',
			),
			'footer_bg_color'             => array(
				'section'      => 'footer',
				'type'         => 'color',
				'default'      => '#222222',
				'sanitize'     => 'sanitize_hex_color',
				'label'        => __( 'Footer Background', 'phantom-core' ),
				'css_property' => '--footer-bg',
				'css_selector' => ':root',
			),
			'footer_scroll_to_top_enable' => array(
				'section'  => 'footer',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Enable Scroll to Top', 'phantom-core' ),
			),
			'footer_scroll_to_top_color'  => array(
				'section'  => 'footer',
				'type'     => 'color',
				'default'  => '#ffffff',
				'sanitize' => 'sanitize_hex_color',
				'label'    => __( 'Scroll to Top Icon Color', 'phantom-core' ),
			),
			'footer_scroll_to_top_bg'     => array(
				'section'  => 'footer',
				'type'     => 'color',
				'default'  => '#705b53',
				'sanitize' => 'sanitize_hex_color',
				'label'    => __( 'Scroll to Top Background', 'phantom-core' ),
			),
			'footer_social_enable'        => array(
				'section'  => 'footer',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Enable Social Links', 'phantom-core' ),
			),
			'footer_newsletter_enable'    => array(
				'section'  => 'footer',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Enable Newsletter', 'phantom-core' ),
			),
			'footer_bottom_bar_enable'    => array(
				'section'  => 'footer',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Enable Bottom Bar', 'phantom-core' ),
			),
			'footer_layout'               => array(
				'section'  => 'footer',
				'type'     => 'select',
				'default'  => '3-col',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Footer Layout', 'phantom-core' ),
				'choices'  => array( '1-col' => '1 Column', '2-col' => '2 Columns', '3-col' => '3 Columns', '4-col' => '4 Columns' ),
			),
		);
	}

	private function section_typography(): array {
		return array(
			'typography_heading_font'   => array(
				'section'      => 'typography',
				'type'         => 'string',
				'default'      => 'Playfair Display',
				'sanitize'     => 'sanitize_text_field',
				'label'        => __( 'Heading Font', 'phantom-core' ),
				'css_property' => '--font-heading',
				'css_selector' => ':root',
			),
			'typography_body_font'      => array(
				'section'      => 'typography',
				'type'         => 'string',
				'default'      => 'Archivo',
				'sanitize'     => 'sanitize_text_field',
				'label'        => __( 'Body Font', 'phantom-core' ),
				'css_property' => '--font-body',
				'css_selector' => ':root',
			),
			'typography_base_size'      => array(
				'section'      => 'typography',
				'type'         => 'int',
				'default'      => 16,
				'sanitize'     => 'absint',
				'label'        => __( 'Base Font Size', 'phantom-core' ),
				'css_property' => '--font-base-size',
				'css_selector' => ':root',
			),
			'typography_heading_weight' => array(
				'section'      => 'typography',
				'type'         => 'string',
				'default'      => '700',
				'sanitize'     => 'sanitize_text_field',
				'label'        => __( 'Heading Weight', 'phantom-core' ),
				'css_property' => '--font-heading-weight',
				'css_selector' => ':root',
			),
			'typography_body_weight'    => array(
				'section'      => 'typography',
				'type'         => 'string',
				'default'      => '400',
				'sanitize'     => 'sanitize_text_field',
				'label'        => __( 'Body Weight', 'phantom-core' ),
				'css_property' => '--font-body-weight',
				'css_selector' => ':root',
			),
			'typography_line_height'    => array(
				'section'      => 'typography',
				'type'         => 'float',
				'default'      => 1.6,
				'sanitize'     => 'floatval',
				'label'        => __( 'Line Height', 'phantom-core' ),
				'css_property' => '--font-line-height',
				'css_selector' => ':root',
			),
			'typography_letter_spacing' => array(
				'section'      => 'typography',
				'type'         => 'int',
				'default'      => 0,
				'sanitize'     => 'absint',
				'label'        => __( 'Letter Spacing', 'phantom-core' ),
				'css_property' => '--font-letter-spacing',
				'css_selector' => ':root',
			),
			'typography_heading_case'   => array(
				'section'      => 'typography',
				'type'         => 'select',
				'default'      => 'none',
				'options'      => array(
					'none'       => 'None',
					'uppercase'  => 'Uppercase',
					'capitalize' => 'Capitalize',
				),
				'sanitize'     => 'sanitize_text_field',
				'label'        => __( 'Heading Text Case', 'phantom-core' ),
				'css_property' => '--font-heading-case',
				'css_selector' => ':root',
			),
		);
	}

	private function section_colors(): array {
		return array(
			'color_primary'    => array(
				'section'      => 'colors',
				'type'         => 'color',
				'default'      => '#7635d5',
				'sanitize'     => 'sanitize_hex_color',
				'label'        => __( 'Primary Color', 'phantom-core' ),
				'css_property' => '--color-primary',
				'css_selector' => ':root',
			),
			'color_secondary'  => array(
				'section'      => 'colors',
				'type'         => 'color',
				'default'      => '#ffffff',
				'sanitize'     => 'sanitize_hex_color',
				'label'        => __( 'Secondary Color', 'phantom-core' ),
				'css_property' => '--color-secondary',
				'css_selector' => ':root',
			),
			'color_accent'     => array(
				'section'      => 'colors',
				'type'         => 'color',
				'default'      => '#fcd668',
				'sanitize'     => 'sanitize_hex_color',
				'label'        => __( 'Accent Color', 'phantom-core' ),
				'css_property' => '--color-accent',
				'css_selector' => ':root',
			),
			'color_text'       => array(
				'section'      => 'colors',
				'type'         => 'color',
				'default'      => '#4e4e4e',
				'sanitize'     => 'sanitize_hex_color',
				'label'        => __( 'Text Color', 'phantom-core' ),
				'css_property' => '--color-text',
				'css_selector' => ':root',
			),
			'color_heading'    => array(
				'section'      => 'colors',
				'type'         => 'color',
				'default'      => '#3f3f3f',
				'sanitize'     => 'sanitize_hex_color',
				'label'        => __( 'Heading Color', 'phantom-core' ),
				'css_property' => '--color-heading',
				'css_selector' => ':root',
			),
			'color_background' => array(
				'section'      => 'colors',
				'type'         => 'color',
				'default'      => '#ffffff',
				'sanitize'     => 'sanitize_hex_color',
				'label'        => __( 'Background Color', 'phantom-core' ),
				'css_property' => '--color-background',
				'css_selector' => ':root',
			),
			'color_header_bg'  => array(
				'section'      => 'colors',
				'type'         => 'color',
				'default'      => '#ffffff',
				'sanitize'     => 'sanitize_hex_color',
				'label'        => __( 'Header Background', 'phantom-core' ),
				'css_property' => '--color-header-bg',
				'css_selector' => ':root',
			),
			'color_footer_bg'  => array(
				'section'      => 'colors',
				'type'         => 'color',
				'default'      => '#222222',
				'sanitize'     => 'sanitize_hex_color',
				'label'        => __( 'Footer Background', 'phantom-core' ),
				'css_property' => '--color-footer-bg',
				'css_selector' => ':root',
			),
			'color_link'       => array(
				'section'      => 'colors',
				'type'         => 'color',
				'default'      => '#705b53',
				'sanitize'     => 'sanitize_hex_color',
				'label'        => __( 'Link Color', 'phantom-core' ),
				'css_property' => '--color-link',
				'css_selector' => ':root',
			),
			'color_link_hover' => array(
				'section'      => 'colors',
				'type'         => 'color',
				'default'      => '#c19a6b',
				'sanitize'     => 'sanitize_hex_color',
				'label'        => __( 'Link Hover', 'phantom-core' ),
				'css_property' => '--color-link-hover',
				'css_selector' => ':root',
			),
			'color_border'     => array(
				'section'      => 'colors',
				'type'         => 'color',
				'default'      => '#e5e5e5',
				'sanitize'     => 'sanitize_hex_color',
				'label'        => __( 'Border Color', 'phantom-core' ),
				'css_property' => '--color-border',
				'css_selector' => ':root',
			),
			'color_sale'       => array(
				'section'      => 'colors',
				'type'         => 'color',
				'default'      => '#e74c3c',
				'sanitize'     => 'sanitize_hex_color',
				'label'        => __( 'Sale Color', 'phantom-core' ),
				'css_property' => '--color-sale',
				'css_selector' => ':root',
			),
		);
	}

	private function section_buttons(): array {
		return array(
			'button_bg'         => array(
				'section'      => 'buttons',
				'type'         => 'color',
				'default'      => '#7635d5',
				'sanitize'     => 'sanitize_hex_color',
				'label'        => __( 'Button Background', 'phantom-core' ),
				'css_property' => '--button-bg',
				'css_selector' => ':root',
			),
			'button_text'       => array(
				'section'      => 'buttons',
				'type'         => 'color',
				'default'      => '#ffffff',
				'sanitize'     => 'sanitize_hex_color',
				'label'        => __( 'Button Text', 'phantom-core' ),
				'css_property' => '--button-text',
				'css_selector' => ':root',
			),
			'button_bg_hover'   => array(
				'section'      => 'buttons',
				'type'         => 'color',
				'default'      => '#5a29a6',
				'sanitize'     => 'sanitize_hex_color',
				'label'        => __( 'Button Hover BG', 'phantom-core' ),
				'css_property' => '--button-bg-hover',
				'css_selector' => ':root',
			),
			'button_text_hover' => array(
				'section'      => 'buttons',
				'type'         => 'color',
				'default'      => '#ffffff',
				'sanitize'     => 'sanitize_hex_color',
				'label'        => __( 'Button Hover Text', 'phantom-core' ),
				'css_property' => '--button-text-hover',
				'css_selector' => ':root',
			),
			'button_radius'     => array(
				'section'      => 'buttons',
				'type'         => 'int',
				'default'      => 4,
				'sanitize'     => 'absint',
				'label'        => __( 'Button Radius', 'phantom-core' ),
				'css_property' => '--button-radius',
				'css_selector' => ':root',
			),
			'button_padding_y'  => array(
				'section'      => 'buttons',
				'type'         => 'int',
				'default'      => 12,
				'sanitize'     => 'absint',
				'label'        => __( 'Button Padding Y', 'phantom-core' ),
				'css_property' => '--button-padding-y',
				'css_selector' => ':root',
			),
			'button_padding_x'  => array(
				'section'      => 'buttons',
				'type'         => 'int',
				'default'      => 24,
				'sanitize'     => 'absint',
				'label'        => __( 'Button Padding X', 'phantom-core' ),
				'css_property' => '--button-padding-x',
				'css_selector' => ':root',
			),
			'button_font_size'  => array(
				'section'      => 'buttons',
				'type'         => 'int',
				'default'      => 14,
				'sanitize'     => 'absint',
				'label'        => __( 'Button Font Size', 'phantom-core' ),
				'css_property' => '--button-font-size',
				'css_selector' => ':root',
			),
		);
	}

	private function section_forms(): array {
		return array(
			'checkout_fname_label'            => array(
				'section'  => 'forms',
				'type'     => 'string',
				'default'  => 'First Name',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'First Name Label', 'phantom-core' ),
			),
			'checkout_lname_label'            => array(
				'section'  => 'forms',
				'type'     => 'string',
				'default'  => 'Last Name',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Last Name Label', 'phantom-core' ),
			),
			'checkout_account_note'           => array(
				'section'  => 'forms',
				'type'     => 'string',
				'default'  => 'You can create an account after checkout.',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Account Note', 'phantom-core' ),
			),
			'checkout_street_line1_label'     => array(
				'section'  => 'forms',
				'type'     => 'string',
				'default'  => 'Street Address: Line1',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Street Line 1', 'phantom-core' ),
			),
			'checkout_country_label'          => array(
				'section'  => 'forms',
				'type'     => 'string',
				'default'  => 'Country',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Country Label', 'phantom-core' ),
			),
			'checkout_country_placeholder'    => array(
				'section'  => 'forms',
				'type'     => 'string',
				'default'  => 'Select Country',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Country Placeholder', 'phantom-core' ),
			),
			'checkout_state_label'            => array(
				'section'  => 'forms',
				'type'     => 'string',
				'default'  => 'State/Province',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'State Label', 'phantom-core' ),
			),
			'checkout_state_placeholder'      => array(
				'section'  => 'forms',
				'type'     => 'string',
				'default'  => 'Select State',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'State Placeholder', 'phantom-core' ),
			),
			'checkout_city_label'             => array(
				'section'  => 'forms',
				'type'     => 'string',
				'default'  => 'City',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'City Label', 'phantom-core' ),
			),
			'checkout_city_placeholder'       => array(
				'section'  => 'forms',
				'type'     => 'string',
				'default'  => 'Select City',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'City Placeholder', 'phantom-core' ),
			),
			'checkout_zip_label'              => array(
				'section'  => 'forms',
				'type'     => 'string',
				'default'  => 'Zip/ postal code',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Zip Label', 'phantom-core' ),
			),
			'checkout_summary_title'          => array(
				'section'  => 'forms',
				'type'     => 'string',
				'default'  => 'Order Summary',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Summary Title', 'phantom-core' ),
			),
			'checkout_subtotal_label'         => array(
				'section'  => 'forms',
				'type'     => 'string',
				'default'  => 'Sub Total',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Subtotal Label', 'phantom-core' ),
			),
			'checkout_total_label'            => array(
				'section'  => 'forms',
				'type'     => 'string',
				'default'  => 'Total',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Total Label', 'phantom-core' ),
			),
			'checkout_items_label'            => array(
				'section'  => 'forms',
				'type'     => 'string',
				'default'  => 'Items in cart',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Items Label', 'phantom-core' ),
			),
			'checkout_item_name'              => array(
				'section'  => 'forms',
				'type'     => 'string',
				'default'  => 'Charm Wall Clock',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Item Name', 'phantom-core' ),
			),
			'checkout_item_qty_label'         => array(
				'section'  => 'forms',
				'type'     => 'string',
				'default'  => 'Qty:',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Qty Label', 'phantom-core' ),
			),
			'checkout_item_price'             => array(
				'section'  => 'forms',
				'type'     => 'string',
				'default'  => '$38.00',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Item Price', 'phantom-core' ),
			),
			'checkout_payment_label'          => array(
				'section'  => 'forms',
				'type'     => 'string',
				'default'  => 'Payment Method:',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Payment Label', 'phantom-core' ),
			),
			'checkout_credit_card_label'      => array(
				'section'  => 'forms',
				'type'     => 'string',
				'default'  => 'Credit card',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Credit Card Label', 'phantom-core' ),
			),
			'checkout_card_number_label'      => array(
				'section'  => 'forms',
				'type'     => 'string',
				'default'  => 'Card number',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Card Number Label', 'phantom-core' ),
			),
			'checkout_expiration_label'       => array(
				'section'  => 'forms',
				'type'     => 'string',
				'default'  => 'Expiration date',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Expiration Label', 'phantom-core' ),
			),
			'checkout_month_placeholder'      => array(
				'section'  => 'forms',
				'type'     => 'string',
				'default'  => 'Month',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Month Placeholder', 'phantom-core' ),
			),
			'checkout_year_placeholder'       => array(
				'section'  => 'forms',
				'type'     => 'string',
				'default'  => 'Year',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Year Placeholder', 'phantom-core' ),
			),
			'checkout_security_label'         => array(
				'section'  => 'forms',
				'type'     => 'string',
				'default'  => 'Security Code',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Security Label', 'phantom-core' ),
			),
			'checkout_cod_label'              => array(
				'section'  => 'forms',
				'type'     => 'string',
				'default'  => 'Cash on Delivery',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'COD Label', 'phantom-core' ),
			),
			'checkout_shipping_methods_label' => array(
				'section'  => 'forms',
				'type'     => 'string',
				'default'  => 'Shipping Methods:',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Shipping Methods Label', 'phantom-core' ),
			),
			'checkout_shipping_free_price'    => array(
				'section'  => 'forms',
				'type'     => 'string',
				'default'  => '$0.00',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Free Shipping Price', 'phantom-core' ),
			),
			'checkout_shipping_free_label'    => array(
				'section'  => 'forms',
				'type'     => 'string',
				'default'  => 'Free',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Free Shipping Label', 'phantom-core' ),
			),
			'checkout_shipping_free_name'     => array(
				'section'  => 'forms',
				'type'     => 'string',
				'default'  => 'Free Shipping',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Free Shipping Name', 'phantom-core' ),
			),
			'checkout_shipping_flat_price'    => array(
				'section'  => 'forms',
				'type'     => 'string',
				'default'  => '$5.00',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Flat Rate Price', 'phantom-core' ),
			),
			'checkout_shipping_flat_label'    => array(
				'section'  => 'forms',
				'type'     => 'string',
				'default'  => 'Fixed',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Flat Rate Label', 'phantom-core' ),
			),
			'checkout_shipping_flat_name'     => array(
				'section'  => 'forms',
				'type'     => 'string',
				'default'  => 'Flat Rate',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Flat Rate Name', 'phantom-core' ),
			),
			'checkout_company_label'          => array(
				'section'  => 'forms',
				'type'     => 'string',
				'default'  => 'Company',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Company Label', 'phantom-core' ),
			),
			'checkout_street_label'           => array(
				'section'  => 'forms',
				'type'     => 'string',
				'default'  => 'Street Address',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Street Label', 'phantom-core' ),
			),
			'checkout_phone_label'            => array(
				'section'  => 'forms',
				'type'     => 'string',
				'default'  => 'Phone Number',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Phone Label', 'phantom-core' ),
			),
			'form_input_border_radius'        => array(
				'section'      => 'forms',
				'type'         => 'int',
				'default'      => 4,
				'sanitize'     => 'absint',
				'label'        => __( 'Input Border Radius', 'phantom-core' ),
				'css_property' => '--form-input-radius',
				'css_selector' => ':root',
			),
			'form_input_height'               => array(
				'section'      => 'forms',
				'type'         => 'int',
				'default'      => 48,
				'sanitize'     => 'absint',
				'label'        => __( 'Input Height', 'phantom-core' ),
				'css_property' => '--form-input-height',
				'css_selector' => ':root',
			),
		);
	}

	private function section_spacing(): array {
		return array(
			'section_padding_y'     => array(
				'section'      => 'spacing',
				'type'         => 'int',
				'default'      => 80,
				'sanitize'     => 'absint',
				'label'        => __( 'Section Padding Y', 'phantom-core' ),
				'css_property' => '--section-padding-y',
				'css_selector' => ':root',
			),
			'section_padding_x'     => array(
				'section'      => 'spacing',
				'type'         => 'int',
				'default'      => 0,
				'sanitize'     => 'absint',
				'label'        => __( 'Section Padding X', 'phantom-core' ),
				'css_property' => '--section-padding-x',
				'css_selector' => ':root',
			),
			'container_gutter'      => array(
				'section'      => 'spacing',
				'type'         => 'int',
				'default'      => 30,
				'sanitize'     => 'absint',
				'label'        => __( 'Container Gutter', 'phantom-core' ),
				'css_property' => '--container-gutter',
				'css_selector' => ':root',
			),
			'content_gap'           => array(
				'section'      => 'spacing',
				'type'         => 'int',
				'default'      => 30,
				'sanitize'     => 'absint',
				'label'        => __( 'Content Gap', 'phantom-core' ),
				'css_property' => '--content-gap',
				'css_selector' => ':root',
			),
			'element_margin_bottom' => array(
				'section'      => 'spacing',
				'type'         => 'int',
				'default'      => 20,
				'sanitize'     => 'absint',
				'label'        => __( 'Element Margin Bottom', 'phantom-core' ),
				'css_property' => '--element-margin-bottom',
				'css_selector' => ':root',
			),
			'widget_spacing'        => array(
				'section'      => 'spacing',
				'type'         => 'int',
				'default'      => 40,
				'sanitize'     => 'absint',
				'label'        => __( 'Widget Spacing', 'phantom-core' ),
				'css_property' => '--widget-spacing',
				'css_selector' => ':root',
			),
		);
	}

	private function section_layout(): array {
		return array(
			'container_width'           => array(
				'section'      => 'layout',
				'type'         => 'int',
				'default'      => 1200,
				'sanitize'     => 'absint',
				'label'        => __( 'Container Width', 'phantom-core' ),
				'css_property' => '--container-width',
				'css_selector' => ':root',
			),
			'layout_boxed_mode'         => array(
				'section'  => 'layout',
				'type'     => 'bool',
				'default'  => 0,
				'sanitize' => 'absint',
				'label'    => __( 'Boxed Mode', 'phantom-core' ),
			),
			'layout_boxed_width'        => array(
				'section'      => 'layout',
				'type'         => 'int',
				'default'      => 1440,
				'sanitize'     => 'absint',
				'label'        => __( 'Boxed Width', 'phantom-core' ),
				'css_property' => '--boxed-width',
				'css_selector' => ':root',
			),
			'content_width'             => array(
				'section'      => 'layout',
				'type'         => 'int',
				'default'      => 800,
				'sanitize'     => 'absint',
				'label'        => __( 'Content Width', 'phantom-core' ),
				'css_property' => '--content-width',
				'css_selector' => ':root',
			),
			'sidebar_width'             => array(
				'section'      => 'layout',
				'type'         => 'int',
				'default'      => 380,
				'sanitize'     => 'absint',
				'label'        => __( 'Sidebar Width', 'phantom-core' ),
				'css_property' => '--sidebar-width',
				'css_selector' => ':root',
			),
			'layout_columns'            => array(
				'section'      => 'layout',
				'type'         => 'int',
				'default'      => 12,
				'sanitize'     => 'absint',
				'label'        => __( 'Grid Columns', 'phantom-core' ),
				'css_property' => '--layout-columns',
				'css_selector' => ':root',
			),
			'four_column_title'         => array(
				'section'  => 'layout',
				'type'     => 'string',
				'default'  => 'Four Column',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Four Column Title', 'phantom-core' ),
			),
			'three_column_title'        => array(
				'section'  => 'layout',
				'type'     => 'string',
				'default'  => 'Three Column',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Three Column Title', 'phantom-core' ),
			),
			'three_colum_sidbar_title'  => array(
				'section'  => 'layout',
				'type'     => 'string',
				'default'  => 'Three Column Sidebar',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Three Column Sidebar', 'phantom-core' ),
			),
			'two_column_title'          => array(
				'section'  => 'layout',
				'type'     => 'string',
				'default'  => 'Two Column',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Two Column Title', 'phantom-core' ),
			),
			'six_colum_full_wide_title' => array(
				'section'  => 'layout',
				'type'     => 'string',
				'default'  => 'Six Column',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Six Column', 'phantom-core' ),
			),
			'one_column_title'          => array(
				'section'  => 'layout',
				'type'     => 'string',
				'default'  => 'One Column',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'One Column Title', 'phantom-core' ),
			),
		);
	}

	private function section_responsive(): array {
		return array(
			'breakpoint_xl' => array(
				'section'      => 'responsive',
				'type'         => 'int',
				'default'      => 1200,
				'sanitize'     => 'absint',
				'label'        => __( 'XL Breakpoint', 'phantom-core' ),
				'css_property' => '--breakpoint-xl',
				'css_selector' => ':root',
			),
			'breakpoint_lg' => array(
				'section'      => 'responsive',
				'type'         => 'int',
				'default'      => 992,
				'sanitize'     => 'absint',
				'label'        => __( 'LG Breakpoint', 'phantom-core' ),
				'css_property' => '--breakpoint-lg',
				'css_selector' => ':root',
			),
			'breakpoint_md' => array(
				'section'      => 'responsive',
				'type'         => 'int',
				'default'      => 768,
				'sanitize'     => 'absint',
				'label'        => __( 'MD Breakpoint', 'phantom-core' ),
				'css_property' => '--breakpoint-md',
				'css_selector' => ':root',
			),
			'breakpoint_sm' => array(
				'section'      => 'responsive',
				'type'         => 'int',
				'default'      => 576,
				'sanitize'     => 'absint',
				'label'        => __( 'SM Breakpoint', 'phantom-core' ),
				'css_property' => '--breakpoint-sm',
				'css_selector' => ':root',
			),
		);
	}

	private function section_animations(): array {
		return array(
			'animations_enable'   => array(
				'section'  => 'animations',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Enable Animations', 'phantom-core' ),
			),
			'animations_duration' => array(
				'section'  => 'animations',
				'type'     => 'string',
				'default'  => '2s',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Animation Duration', 'phantom-core' ),
			),
			'animations_delay'    => array(
				'section'  => 'animations',
				'type'     => 'string',
				'default'  => '0.05s',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Animation Delay', 'phantom-core' ),
			),
			'animations_easing'   => array(
				'section'  => 'animations',
				'type'     => 'select',
				'default'  => 'ease-out',
				'options'  => array(
					'linear'      => 'Linear',
					'ease'        => 'Ease',
					'ease-in'     => 'Ease In',
					'ease-out'    => 'Ease Out',
					'ease-in-out' => 'Ease In Out',
				),
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Animation Easing', 'phantom-core' ),
			),
			'animations_scroll'   => array(
				'section'  => 'animations',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Scroll Animations', 'phantom-core' ),
			),
		);
	}

	private function section_effects_3d(): array {
		return array(
			'effect_3d_enable'      => array(
				'section'  => 'effects_3d',
				'type'     => 'bool',
				'default'  => 0,
				'sanitize' => 'absint',
				'label'    => __( 'Enable 3D Effects', 'phantom-core' ),
			),
			'effect_3d_perspective' => array(
				'section'  => 'effects_3d',
				'type'     => 'int',
				'default'  => 1000,
				'sanitize' => 'absint',
				'label'    => __( '3D Perspective', 'phantom-core' ),
			),
			'effect_3d_rotate_x'    => array(
				'section'  => 'effects_3d',
				'type'     => 'int',
				'default'  => 5,
				'sanitize' => 'absint',
				'label'    => __( '3D Rotate X', 'phantom-core' ),
			),
			'effect_3d_rotate_y'    => array(
				'section'  => 'effects_3d',
				'type'     => 'int',
				'default'  => 5,
				'sanitize' => 'absint',
				'label'    => __( '3D Rotate Y', 'phantom-core' ),
			),
		);
	}

	private function section_search(): array {
		return array(
			'search_ajax_enable'  => array(
				'section'  => 'search',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Enable Ajax Search', 'phantom-core' ),
			),
			'search_live_results' => array(
				'section'  => 'search',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Live Results', 'phantom-core' ),
			),
			'search_suggestions'  => array(
				'section'  => 'search',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Show Suggestions', 'phantom-core' ),
			),
			'search_max_results'    => array(
				'section'  => 'search',
				'type'     => 'int',
				'default'  => 5,
				'sanitize' => 'absint',
				'label'    => __( 'Max Results', 'phantom-core' ),
			),
			'search_post_types'     => array(
				'section'  => 'search',
				'type'     => 'multiselect',
				'default'  => array( 'post', 'product' ),
				'label'    => __( 'Search Post Types', 'phantom-core' ),
			),
			'search_per_page'       => array(
				'section'  => 'search',
				'type'     => 'number',
				'default'  => 10,
				'sanitize' => 'absint',
				'label'    => __( 'Results Per Page', 'phantom-core' ),
			),
			'search_no_results'     => array(
				'section'  => 'search',
				'type'     => 'text',
				'default'  => 'No results found. Try a different search term.',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'No Results Message', 'phantom-core' ),
			),
		);
	}

	private function section_performance(): array {
		return array(
			'performance_lazy_load'            => array(
				'section'  => 'performance',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Lazy Load Images', 'phantom-core' ),
			),
			'performance_minify_css'           => array(
				'section'  => 'performance',
				'type'     => 'bool',
				'default'  => 0,
				'sanitize' => 'absint',
				'label'    => __( 'Minify CSS', 'phantom-core' ),
			),
			'performance_minify_js'            => array(
				'section'  => 'performance',
				'type'     => 'bool',
				'default'  => 0,
				'sanitize' => 'absint',
				'label'    => __( 'Minify JS', 'phantom-core' ),
			),
			'performance_defer_js'             => array(
				'section'  => 'performance',
				'type'     => 'bool',
				'default'  => 0,
				'sanitize' => 'absint',
				'label'    => __( 'Defer Non-critical JS', 'phantom-core' ),
			),
			'performance_preload_fonts'        => array(
				'section'  => 'performance',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Preload Fonts', 'phantom-core' ),
			),
			'performance_combine_css'          => array(
				'section'  => 'performance',
				'type'     => 'bool',
				'default'  => 0,
				'sanitize' => 'absint',
				'label'    => __( 'Combine CSS Files', 'phantom-core' ),
			),
			'performance_dns_prefetch'         => array(
				'section'  => 'performance',
				'type'     => 'bool',
				'default'  => 0,
				'sanitize' => 'absint',
				'label'    => __( 'Enable DNS Prefetch', 'phantom-core' ),
			),
			'performance_dns_prefetch_domains' => array(
				'section'  => 'performance',
				'type'     => 'array',
				'default'  => array(),
				'sanitize' => function ( $v ) {
					return $v; },
				'label'    => __( 'DNS Prefetch Domains', 'phantom-core' ),
			),
			'performance_preconnect_domains'   => array(
				'section'  => 'performance',
				'type'     => 'array',
				'default'  => array(),
				'sanitize' => function ( $v ) {
					return $v; },
				'label'    => __( 'Preconnect Domains', 'phantom-core' ),
			),
			'performance_preload_hero'         => array(
				'section'  => 'performance',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Preload Hero Image', 'phantom-core' ),
			),
			'performance_font_display'         => array(
				'section'  => 'performance',
				'type'     => 'select',
				'default'  => 'swap',
				'options'  => array(
					'swap'     => 'Swap',
					'block'    => 'Block',
					'optional' => 'Optional',
					'fallback' => 'Fallback',
				),
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Font Display Strategy', 'phantom-core' ),
			),
			'performance_remove_wp_emoji'      => array(
				'section'  => 'performance',
				'type'     => 'bool',
				'default'  => 0,
				'sanitize' => 'absint',
				'label'    => __( 'Remove WP Emoji', 'phantom-core' ),
			),
			'performance_remove_wp_block_css'  => array(
				'section'  => 'performance',
				'type'     => 'bool',
				'default'  => 0,
				'sanitize' => 'absint',
				'label'    => __( 'Remove Block Library CSS', 'phantom-core' ),
			),
		);
	}

	private function section_seo(): array {
		return array(
			'seo_breadcrumbs_enable' => array(
				'section'  => 'seo',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Enable Breadcrumbs', 'phantom-core' ),
			),
			'seo_schema_enable'      => array(
				'section'  => 'seo',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Enable Schema Markup', 'phantom-core' ),
			),
			'seo_og_enable'          => array(
				'section'  => 'seo',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Enable Open Graph', 'phantom-core' ),
			),
			'seo_og_default_image'   => array(
				'section'  => 'seo',
				'type'     => 'image',
				'default'  => '',
				'sanitize' => 'esc_url_raw',
				'label'    => __( 'Default OG Image', 'phantom-core' ),
			),
			'seo_meta_description'   => array(
				'section'  => 'seo',
				'type'     => 'text',
				'default'  => '',
				'sanitize' => 'sanitize_textarea_field',
				'label'    => __( 'Meta Description', 'phantom-core' ),
				'desc'     => __( 'Used by Head_Manager for <meta name="description"> output.', 'phantom-core' ),
			),
			'seo_meta_keywords'      => array(
				'section'  => 'seo',
				'type'     => 'string',
				'default'  => '',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Meta Keywords', 'phantom-core' ),
				'desc'     => __( 'Comma-separated keywords.', 'phantom-core' ),
			),
			'seo_og_title'           => array(
				'section'  => 'seo',
				'type'     => 'string',
				'default'  => '',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'OG Title', 'phantom-core' ),
				'desc'     => __( 'Open Graph title (falls back to page title if empty).', 'phantom-core' ),
			),
			'seo_og_description'     => array(
				'section'  => 'seo',
				'type'     => 'text',
				'default'  => '',
				'sanitize' => 'sanitize_textarea_field',
				'label'    => __( 'OG Description', 'phantom-core' ),
				'desc'     => __( 'Open Graph description.', 'phantom-core' ),
			),
			'seo_og_image'           => array(
				'section'  => 'seo',
				'type'     => 'image',
				'default'  => '',
				'sanitize' => 'esc_url_raw',
				'label'    => __( 'OG Image', 'phantom-core' ),
				'desc'     => __( 'Per-page OG image override.', 'phantom-core' ),
			),
		);
	}

	private function section_accessibility(): array {
		return array(
			'a11y_keyboard_nav'  => array(
				'section'  => 'accessibility',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Enable Keyboard Nav', 'phantom-core' ),
			),
			'a11y_skip_links'    => array(
				'section'  => 'accessibility',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Enable Skip Links', 'phantom-core' ),
			),
			'a11y_focus_outline' => array(
				'section'  => 'accessibility',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Show Focus Outline', 'phantom-core' ),
			),
			'a11y_contrast_mode' => array(
				'section'  => 'accessibility',
				'type'     => 'bool',
				'default'  => 0,
				'sanitize' => 'absint',
				'label'    => __( 'High Contrast Mode', 'phantom-core' ),
			),
			'a11y_font_size'     => array(
				'section'  => 'accessibility',
				'type'     => 'number',
				'default'  => 100,
				'sanitize' => 'absint',
				'label'    => __( 'Font Size (%)', 'phantom-core' ),
				'desc'     => __( 'Base font size percentage for accessibility scaling.', 'phantom-core' ),
			),
			'a11y_high_contrast' => array(
				'section'  => 'accessibility',
				'type'     => 'bool',
				'default'  => 0,
				'sanitize' => 'absint',
				'label'    => __( 'High Contrast Mode Override', 'phantom-core' ),
				'desc'     => __( 'Engine-level high contrast flag for body class.', 'phantom-core' ),
			),
		);
	}

	private function section_integrations(): array {
		return array(
			'social_facebook'     => array(
				'section'  => 'integrations',
				'type'     => 'string',
				'default'  => 'https://www.facebook.com/',
				'sanitize' => 'esc_url_raw',
				'label'    => __( 'Facebook URL', 'phantom-core' ),
			),
			'social_instagram'    => array(
				'section'  => 'integrations',
				'type'     => 'string',
				'default'  => 'https://www.instagram.com/',
				'sanitize' => 'esc_url_raw',
				'label'    => __( 'Instagram URL', 'phantom-core' ),
			),
			'social_youtube'      => array(
				'section'  => 'integrations',
				'type'     => 'string',
				'default'  => 'https://www.youtube.com/',
				'sanitize' => 'esc_url_raw',
				'label'    => __( 'YouTube URL', 'phantom-core' ),
			),
			'social_twitter'      => array(
				'section'  => 'integrations',
				'type'     => 'string',
				'default'  => 'https://twitter.com/',
				'sanitize' => 'esc_url_raw',
				'label'    => __( 'Twitter URL', 'phantom-core' ),
			),
			'social_pinterest'    => array(
				'section'  => 'integrations',
				'type'     => 'string',
				'default'  => '',
				'sanitize' => 'esc_url_raw',
				'label'    => __( 'Pinterest URL', 'phantom-core' ),
			),
			'social_tiktok'       => array(
				'section'  => 'integrations',
				'type'     => 'string',
				'default'  => '',
				'sanitize' => 'esc_url_raw',
				'label'    => __( 'TikTok URL', 'phantom-core' ),
			),
			'cookie_enable'       => array(
				'section'  => 'integrations',
				'type'     => 'bool',
				'default'  => 0,
				'sanitize' => 'absint',
				'label'    => __( 'Enable Cookie Notice', 'phantom-core' ),
			),
			'cookie_message'      => array(
				'section'  => 'integrations',
				'type'     => 'text',
				'default'  => 'This website uses cookies to improve your experience.',
				'sanitize' => 'sanitize_textarea_field',
				'label'    => __( 'Cookie Message', 'phantom-core' ),
			),
			'cookie_accept_text'  => array(
				'section'  => 'integrations',
				'type'     => 'string',
				'default'  => 'Accept',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Accept Text', 'phantom-core' ),
			),
			'cookie_decline_text' => array(
				'section'  => 'integrations',
				'type'     => 'string',
				'default'  => 'Decline',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Decline Text', 'phantom-core' ),
			),
			'cookie_policy_link'  => array(
				'section'  => 'integrations',
				'type'     => 'string',
				'default'  => '/privacy-policy/',
				'sanitize' => 'esc_url_raw',
				'label'    => __( 'Policy Link', 'phantom-core' ),
			),
			'cookie_title'        => array(
				'section'  => 'integrations',
				'type'     => 'string',
				'default'  => 'Cookies Policy',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Cookie Title', 'phantom-core' ),
			),
			'cookie_content'      => array(
				'section'  => 'integrations',
				'type'     => 'text',
				'default'  => '',
				'sanitize' => 'wp_kses_post',
				'label'    => __( 'Cookie Content', 'phantom-core' ),
			),
			'google_analytics_id' => array(
				'section'  => 'integrations',
				'type'     => 'string',
				'default'  => '',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Google Analytics ID', 'phantom-core' ),
				'desc'     => __( 'Format: UA-XXXXX-Y or G-XXXXXXXXXX.', 'phantom-core' ),
			),
			'google_tag_manager'  => array(
				'section'  => 'integrations',
				'type'     => 'string',
				'default'  => '',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Google Tag Manager ID', 'phantom-core' ),
				'desc'     => __( 'Format: GTM-XXXXXXX.', 'phantom-core' ),
			),
			'facebook_pixel'      => array(
				'section'  => 'integrations',
				'type'     => 'string',
				'default'  => '',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Facebook Pixel ID', 'phantom-core' ),
				'desc'     => __( 'Format: 000000000000000.', 'phantom-core' ),
			),
		);
	}

	private function section_custom_code(): array {
		return array(
			'custom_css'            => array(
				'section'  => 'custom_code',
				'type'     => 'code',
				'default'  => '',
				'sanitize' => 'wp_strip_all_tags',
				'label'    => __( 'Custom CSS', 'phantom-core' ),
				'autoload' => false,
			),
			'custom_js'             => array(
				'section'  => 'custom_code',
				'type'     => 'code',
				'default'  => '',
				'sanitize' => 'wp_strip_all_tags',
				'label'    => __( 'Custom JS', 'phantom-core' ),
				'autoload' => false,
			),
			'custom_head_scripts'   => array(
				'section'  => 'custom_code',
				'type'     => 'code',
				'default'  => '',
				'sanitize' => $this->sanitize_code_passthrough(),
				'label'    => __( 'Head Scripts', 'phantom-core' ),
				'autoload' => false,
			),
			'custom_footer_scripts' => array(
				'section'  => 'custom_code',
				'type'     => 'code',
				'default'  => '',
				'sanitize' => $this->sanitize_code_passthrough(),
				'label'    => __( 'Footer Scripts', 'phantom-core' ),
				'autoload' => false,
			),
		);
	}

	private function section_import_export(): array {
		return array(
			'import_export_export_data'   => array(
				'section'  => 'import_export',
				'type'     => 'code',
				'default'  => '',
				'sanitize' => $this->sanitize_code_passthrough(),
				'label'    => __( 'Export Data', 'phantom-core' ),
				'autoload' => false,
			),
			'import_export_import_data'   => array(
				'section'  => 'import_export',
				'type'     => 'code',
				'default'  => '',
				'sanitize' => $this->sanitize_code_passthrough(),
				'label'    => __( 'Import Data', 'phantom-core' ),
				'autoload' => false,
			),
			'import_export_reset_confirm' => array(
				'section'  => 'import_export',
				'type'     => 'bool',
				'default'  => 0,
				'sanitize' => 'absint',
				'label'    => __( 'Confirm Reset', 'phantom-core' ),
			),
		);
	}

	private function section_about_page(): array {
		return array(
			'about_about_enable'      => array(
				'section'  => 'about_page',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Enable About Section', 'phantom-core' ),
			),
			'about_about_heading'     => array(
				'section'  => 'about_page',
				'type'     => 'string',
				'default'  => 'About Us',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'About Heading', 'phantom-core' ),
			),
			'about_about_title'       => array(
				'section'  => 'about_page',
				'type'     => 'string',
				'default'  => 'Unique clothes & Toys For Kids',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'About Title', 'phantom-core' ),
			),
			'about_about_text_1'      => array(
				'section'  => 'about_page',
				'type'     => 'text',
				'default'  => 'At Claudia Kids, we believe every child\'s world is full of wonder and imagination.',
				'sanitize' => 'sanitize_textarea_field',
				'label'    => __( 'About Text 1', 'phantom-core' ),
			),
			'about_about_text_2'      => array(
				'section'  => 'about_page',
				'type'     => 'text',
				'default'  => 'Our passion lies in designing and curating playful, high-quality pieces.',
				'sanitize' => 'sanitize_textarea_field',
				'label'    => __( 'About Text 2', 'phantom-core' ),
			),
			'about_about_image'       => array(
				'section'  => 'about_page',
				'type'     => 'string',
				'default'  => '/about-us-img.jpg',
				'sanitize' => 'esc_url_raw',
				'label'    => __( 'About Image', 'phantom-core' ),
			),
			'about_about_btn_text'    => array(
				'section'  => 'about_page',
				'type'     => 'string',
				'default'  => 'Read More',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'About Button', 'phantom-core' ),
			),
			'about_about_btn_url'     => array(
				'section'  => 'about_page',
				'type'     => 'string',
				'default'  => '/shop/',
				'sanitize' => 'esc_url_raw',
				'label'    => __( 'About Button URL', 'phantom-core' ),
			),
			'about_mission_enable'    => array(
				'section'  => 'about_page',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Enable Mission', 'phantom-core' ),
			),
			'about_mission_heading'   => array(
				'section'  => 'about_page',
				'type'     => 'string',
				'default'  => 'Our Mission',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Mission Heading', 'phantom-core' ),
			),
			'about_mission_title'     => array(
				'section'  => 'about_page',
				'type'     => 'string',
				'default'  => 'Start of Countless Collection.',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Mission Title', 'phantom-core' ),
			),
			'about_mission_text_1'    => array(
				'section'  => 'about_page',
				'type'     => 'text',
				'default'  => '',
				'sanitize' => 'sanitize_textarea_field',
				'label'    => __( 'Mission Text 1', 'phantom-core' ),
			),
			'about_mission_text_2'    => array(
				'section'  => 'about_page',
				'type'     => 'text',
				'default'  => '',
				'sanitize' => 'sanitize_textarea_field',
				'label'    => __( 'Mission Text 2', 'phantom-core' ),
			),
			'about_team_enable'       => array(
				'section'  => 'about_page',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Enable Team', 'phantom-core' ),
			),
			'about_team_heading'      => array(
				'section'  => 'about_page',
				'type'     => 'string',
				'default'  => 'Experts Team',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Team Heading', 'phantom-core' ),
			),
			'about_team_title'        => array(
				'section'  => 'about_page',
				'type'     => 'string',
				'default'  => 'Our Team Members',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Team Title', 'phantom-core' ),
			),
			'about_team_members'      => array(
				'section'  => 'about_page',
				'type'     => 'repeater',
				'default'  => array(
					array(
						'image'     => '/team-person1.jpg',
						'name'      => 'Marvin Joner',
						'role'      => 'Co Founder',
						'facebook'  => '#',
						'instagram' => '#',
						'youtube'   => '#',
					),
					array(
						'image'     => '/team-person2.jpg',
						'name'      => 'Patricia Woodrum',
						'role'      => 'Staff Worker',
						'facebook'  => '#',
						'instagram' => '#',
						'youtube'   => '#',
					),
					array(
						'image'     => '/team-person3.jpg',
						'name'      => 'Hannaz Stone',
						'role'      => 'Shop Worker',
						'facebook'  => '#',
						'instagram' => '#',
						'youtube'   => '#',
					),
				),
				'sanitize' => function ( $v ) {
					return $v; },
				'label'    => __( 'Team Members', 'phantom-core' ),
			),
			'about_categories_enable' => array(
				'section'  => 'about_page',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Enable Categories', 'phantom-core' ),
			),
			'about_instagram_enable'  => array(
				'section'  => 'about_page',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Enable Instagram', 'phantom-core' ),
			),
			'about_benefits_enable'   => array(
				'section'  => 'about_page',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Enable Benefits', 'phantom-core' ),
			),
		);
	}

	private function section_contact_page(): array {
		return array(
			'contact_info_heading'    => array(
				'section'  => 'contact_page',
				'type'     => 'string',
				'default'  => 'Contact Info',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Info Heading', 'phantom-core' ),
			),
			'contact_info_title'      => array(
				'section'  => 'contact_page',
				'type'     => 'string',
				'default'  => 'Our Information',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Info Title', 'phantom-core' ),
			),
			'contact_location_icon'   => array(
				'section'  => 'contact_page',
				'type'     => 'string',
				'default'  => '/loc-img.png',
				'sanitize' => 'esc_url_raw',
				'label'    => __( 'Location Icon', 'phantom-core' ),
			),
			'contact_location_text'   => array(
				'section'  => 'contact_page',
				'type'     => 'text',
				'default'  => '121 King Street, Melbourne Victoria <br>3000 Australia',
				'sanitize' => 'wp_kses_post',
				'label'    => __( 'Location Text', 'phantom-core' ),
			),
			'contact_location_title'  => array(
				'section'  => 'contact_page',
				'type'     => 'string',
				'default'  => 'Our Location',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Location Title', 'phantom-core' ),
			),
			'contact_phone_icon'      => array(
				'section'  => 'contact_page',
				'type'     => 'string',
				'default'  => '/contact-img.png',
				'sanitize' => 'esc_url_raw',
				'label'    => __( 'Phone Icon', 'phantom-core' ),
			),
			'contact_phone_numbers'   => array(
				'section'  => 'contact_page',
				'type'     => 'array',
				'default'  => array( '(+61 3 8376 6284)', '(+800 2345 6789)' ),
				'sanitize' => function ( $v ) {
					return $v; },
				'label'    => __( 'Phone Numbers', 'phantom-core' ),
			),
			'contact_phone_title'     => array(
				'section'  => 'contact_page',
				'type'     => 'string',
				'default'  => 'Phone Number',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Phone Title', 'phantom-core' ),
			),
			'contact_email_icon'      => array(
				'section'  => 'contact_page',
				'type'     => 'string',
				'default'  => '/email-img.png',
				'sanitize' => 'esc_url_raw',
				'label'    => __( 'Email Icon', 'phantom-core' ),
			),
			'contact_email_addresses' => array(
				'section'  => 'contact_page',
				'type'     => 'array',
				'default'  => array( 'info@claudia.com', 'claudia@gmail.com' ),
				'sanitize' => function ( $v ) {
					return $v; },
				'label'    => __( 'Email Addresses', 'phantom-core' ),
			),
			'contact_email_title'     => array(
				'section'  => 'contact_page',
				'type'     => 'string',
				'default'  => 'Email Us:',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Email Title', 'phantom-core' ),
			),
			'contact_form_heading'    => array(
				'section'  => 'contact_page',
				'type'     => 'string',
				'default'  => 'Get in Touch',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Form Heading', 'phantom-core' ),
			),
			'contact_form_title'      => array(
				'section'  => 'contact_page',
				'type'     => 'string',
				'default'  => 'Send Us a Message',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Form Title', 'phantom-core' ),
			),
			'contact_form_btn_text'   => array(
				'section'  => 'contact_page',
				'type'     => 'string',
				'default'  => 'Send Now',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Form Button', 'phantom-core' ),
			),
			'contact_map_embed'       => array(
				'section'  => 'contact_page',
				'type'     => 'code',
				'default'  => '',
				'sanitize' => array( $this, 'sanitize_map_embed' ),
				'label'    => __( 'Map Embed', 'phantom-core' ),
				'autoload' => false,
			),
		);
	}

	private function section_faq_page(): array {
		return array(
			'faq_enable'           => array(
				'section'  => 'faq_page',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Enable FAQ', 'phantom-core' ),
			),
			'faq_heading'          => array(
				'section'  => 'faq_page',
				'type'     => 'string',
				'default'  => 'FAQs',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'FAQ Heading', 'phantom-core' ),
			),
			'faq_title'            => array(
				'section'  => 'faq_page',
				'type'     => 'string',
				'default'  => 'Frequently Asked Questions',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'FAQ Title', 'phantom-core' ),
			),
			'faq_items'            => array(
				'section'  => 'faq_page',
				'type'     => 'array',
				'default'  => array(),
				'sanitize' => function ( $v ) {
					return $v; },
				'label'    => __( 'FAQ Items', 'phantom-core' ),
			),
			'faq_instagram_enable' => array(
				'section'  => 'faq_page',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Enable Instagram', 'phantom-core' ),
			),
			'faq_benefits_enable'  => array(
				'section'  => 'faq_page',
				'type'     => 'bool',
				'default'  => 1,
				'sanitize' => 'absint',
				'label'    => __( 'Enable Benefits', 'phantom-core' ),
			),
		);
	}

	private function section_coming_soon(): array {
		return array(
			'coming_soon_logo'        => array(
				'section'  => 'coming_soon',
				'type'     => 'string',
				'default'  => '/wp-content/plugins/phantom-core/frontend/assets/images/large-logo.png',
				'sanitize' => 'esc_url_raw',
				'label'    => __( 'Logo', 'phantom-core' ),
			),
			'coming_soon_subtitle'    => array(
				'section'  => 'coming_soon',
				'type'     => 'string',
				'default'  => 'Our Website is under construction',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Subtitle', 'phantom-core' ),
			),
			'coming_soon_title'       => array(
				'section'  => 'coming_soon',
				'type'     => 'string',
				'default'  => 'Coming Soon',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Title', 'phantom-core' ),
			),
			'coming_soon_date'        => array(
				'section'  => 'coming_soon',
				'type'     => 'string',
				'default'  => '',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Launch Date', 'phantom-core' ),
			),
			'maintenance_mode_enable' => array(
				'section'  => 'coming_soon',
				'type'     => 'bool',
				'default'  => 0,
				'sanitize' => 'absint',
				'label'    => __( 'Enable Maintenance', 'phantom-core' ),
			),
		);
	}

	private function section_error_404(): array {
		return array(
			'404_title'       => array(
				'section'  => 'error_404',
				'type'     => 'string',
				'default'  => 'We Could Not Find The Page You\'re Looking For',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( '404 Title', 'phantom-core' ),
			),
			'404_description' => array(
				'section'  => 'error_404',
				'type'     => 'text',
				'default'  => 'The link you\'re trying to access is probably broken, or the page has been removed.',
				'sanitize' => 'sanitize_textarea_field',
				'label'    => __( '404 Description', 'phantom-core' ),
			),
			'404_btn_text'    => array(
				'section'  => 'error_404',
				'type'     => 'string',
				'default'  => 'Back to Homepage',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( '404 Button', 'phantom-core' ),
			),
		);
	}

	private function section_login_page(): array {
		return array(
			'login_title'              => array(
				'section'  => 'login_page',
				'type'     => 'string',
				'default'  => 'Welcome Back !',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Login Title', 'phantom-core' ),
			),
			'login_btn_text'           => array(
				'section'  => 'login_page',
				'type'     => 'string',
				'default'  => 'Login',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Login Button', 'phantom-core' ),
			),
			'login_email_label'        => array(
				'section'  => 'login_page',
				'type'     => 'string',
				'default'  => 'Enter Your E-mail',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Email Label', 'phantom-core' ),
			),
			'login_password_label'     => array(
				'section'  => 'login_page',
				'type'     => 'string',
				'default'  => 'Enter Your Password',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Password Label', 'phantom-core' ),
			),
			'login_remember_label'     => array(
				'section'  => 'login_page',
				'type'     => 'string',
				'default'  => 'Remember me',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Remember Label', 'phantom-core' ),
			),
			'login_lost_password_text' => array(
				'section'  => 'login_page',
				'type'     => 'string',
				'default'  => 'Lost Password?',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Lost Password Text', 'phantom-core' ),
			),
			'login_lost_password_url'  => array(
				'section'  => 'login_page',
				'type'     => 'string',
				'default'  => '/contact/',
				'sanitize' => 'esc_url_raw',
				'label'    => __( 'Lost Password URL', 'phantom-core' ),
			),
			'login_join_link'          => array(
				'section'  => 'login_page',
				'type'     => 'string',
				'default'  => 'Join now, create your FREE account',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Join Link Text', 'phantom-core' ),
			),
			'login_logo'               => array(
				'section'  => 'login_page',
				'type'     => 'string',
				'default'  => '',
				'sanitize' => 'esc_url_raw',
				'label'    => __( 'Login Logo', 'phantom-core' ),
			),
		);
	}

	private function section_register_page(): array {
		return array(
			'join_title'            => array(
				'section'  => 'register_page',
				'type'     => 'string',
				'default'  => 'Create Your FREE Account',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Join Title', 'phantom-core' ),
			),
			'join_btn_text'         => array(
				'section'  => 'register_page',
				'type'     => 'string',
				'default'  => 'Register Now',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Register Button', 'phantom-core' ),
			),
			'join_name_label'       => array(
				'section'  => 'register_page',
				'type'     => 'string',
				'default'  => 'Your full name',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Name Label', 'phantom-core' ),
			),
			'join_email_label'      => array(
				'section'  => 'register_page',
				'type'     => 'string',
				'default'  => 'Your e-mail',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Email Label', 'phantom-core' ),
			),
			'join_password_label'   => array(
				'section'  => 'register_page',
				'type'     => 'string',
				'default'  => 'Enter your password',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Password Label', 'phantom-core' ),
			),
			'join_updates_label'    => array(
				'section'  => 'register_page',
				'type'     => 'string',
				'default'  => 'Inform me about new features and updates (max. twice a month)',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Updates Label', 'phantom-core' ),
			),
			'join_login_link'       => array(
				'section'  => 'register_page',
				'type'     => 'string',
				'default'  => 'Already have an account?',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Login Link', 'phantom-core' ),
			),
			'join_logo'             => array(
				'section'  => 'register_page',
				'type'     => 'string',
				'default'  => '',
				'sanitize' => 'esc_url_raw',
				'label'    => __( 'Join Logo', 'phantom-core' ),
			),
			'join_referral_label'   => array(
				'section'  => 'register_page',
				'type'     => 'string',
				'default'  => 'How did you find out about us?',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Referral Label', 'phantom-core' ),
			),
			'join_referral_default' => array(
				'section'  => 'register_page',
				'type'     => 'string',
				'default'  => 'Please, choose the first interaction you remember.',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Referral Default', 'phantom-core' ),
			),
		);
	}

	private function section_portfolio(): array {
		return array(
			'portfolio_title'             => array(
				'section'  => 'portfolio',
				'type'     => 'string',
				'default'  => 'Our Projects',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Portfolio Title', 'phantom-core' ),
			),
			'portfolio_projects_per_page' => array(
				'section'  => 'portfolio',
				'type'     => 'int',
				'default'  => 9,
				'sanitize' => 'absint',
				'label'    => __( 'Projects Per Page', 'phantom-core' ),
			),
			'portfolio_columns'           => array(
				'section'  => 'portfolio',
				'type'     => 'int',
				'default'  => 3,
				'sanitize' => 'absint',
				'label'    => __( 'Portfolio Columns', 'phantom-core' ),
			),
		);
	}

	private function section_thank_you(): array {
		return array(
			'thank_you_title'    => array(
				'section'  => 'thank_you',
				'type'     => 'string',
				'default'  => 'Thank You!',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Thank You Title', 'phantom-core' ),
			),
			'thank_you_text'     => array(
				'section'  => 'thank_you',
				'type'     => 'text',
				'default'  => 'Thank you for your order! We\'re committed to your health and well-being.',
				'sanitize' => 'sanitize_textarea_field',
				'label'    => __( 'Thank You Text', 'phantom-core' ),
			),
			'thank_you_btn_text' => array(
				'section'  => 'thank_you',
				'type'     => 'string',
				'default'  => 'Back to Home',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Button Text', 'phantom-core' ),
			),
			'thank_you_btn_url'  => array(
				'section'  => 'thank_you',
				'type'     => 'string',
				'default'  => home_url( '/' ),
				'sanitize' => 'esc_url_raw',
				'label'    => __( 'Button URL', 'phantom-core' ),
			),
			'thank_you_icon'     => array(
				'section'  => 'thank_you',
				'type'     => 'string',
				'default'  => '',
				'sanitize' => 'esc_url_raw',
				'label'    => __( 'Icon', 'phantom-core' ),
			),
		);
	}

	private function section_load_more(): array {
		return array(
			'load_more_title'         => array(
				'section'  => 'load_more',
				'type'     => 'string',
				'default'  => 'Load More',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Title', 'phantom-core' ),
			),
			'load_more_button_text'   => array(
				'section'  => 'load_more',
				'type'     => 'string',
				'default'  => 'Load More',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Button Text', 'phantom-core' ),
			),
			'load_more_by_text'       => array(
				'section'  => 'load_more',
				'type'     => 'string',
				'default'  => 'By : Admin',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'By Text', 'phantom-core' ),
			),
			'load_more_category_text' => array(
				'section'  => 'load_more',
				'type'     => 'string',
				'default'  => 'Virtual Assistant',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Category Text', 'phantom-core' ),
			),
			'load_more_date'          => array(
				'section'  => 'load_more',
				'type'     => 'string',
				'default'  => 'Dec 20,2022',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Date', 'phantom-core' ),
			),
			'load_more_read_more'     => array(
				'section'  => 'load_more',
				'type'     => 'string',
				'default'  => 'Read More',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Read More Text', 'phantom-core' ),
			),
			'load_more_video_vimeo'   => array(
				'section'  => 'load_more',
				'type'     => 'string',
				'default'  => '',
				'sanitize' => 'esc_url_raw',
				'label'    => __( 'Vimeo Embed URL', 'phantom-core' ),
			),
			'load_more_video_youtube' => array(
				'section'  => 'load_more',
				'type'     => 'string',
				'default'  => '',
				'sanitize' => 'esc_url_raw',
				'label'    => __( 'YouTube Embed URL', 'phantom-core' ),
			),
		);
	}

	private function section_privacy(): array {
		return array(
			'privacy_title'   => array(
				'section'  => 'privacy',
				'type'     => 'string',
				'default'  => 'Privacy Policy',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Title', 'phantom-core' ),
			),
			'privacy_content' => array(
				'section'  => 'privacy',
				'type'     => 'code',
				'default'  => '',
				'sanitize' => 'wp_kses_post',
				'label'    => __( 'Content', 'phantom-core' ),
				'autoload' => false,
			),
		);
	}

	private function section_terms(): array {
		return array(
			'terms_title'   => array(
				'section'  => 'terms',
				'type'     => 'string',
				'default'  => 'Term of Use',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Title', 'phantom-core' ),
			),
			'terms_content' => array(
				'section'  => 'terms',
				'type'     => 'code',
				'default'  => '',
				'sanitize' => 'wp_kses_post',
				'label'    => __( 'Content', 'phantom-core' ),
				'autoload' => false,
			),
		);
	}

	private function section_team(): array {
		return array(
			'team_title'           => array(
				'section'  => 'team',
				'type'     => 'string',
				'default'  => 'Team',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Title', 'phantom-core' ),
			),
			'team_title_inner'     => array(
				'section'  => 'team',
				'type'     => 'string',
				'default'  => 'Our Team Members',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Inner Title', 'phantom-core' ),
			),
			'team_heading'         => array(
				'section'  => 'team',
				'type'     => 'string',
				'default'  => 'Experts Team',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Heading', 'phantom-core' ),
			),
			'team_mission_heading' => array(
				'section'  => 'team',
				'type'     => 'string',
				'default'  => 'Our Mission',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Mission Heading', 'phantom-core' ),
			),
			'team_mission_title'   => array(
				'section'  => 'team',
				'type'     => 'string',
				'default'  => 'Inspiring Homes <br>Enriching Lives',
				'sanitize' => 'wp_kses_post',
				'label'    => __( 'Mission Title', 'phantom-core' ),
			),
			'team_members'         => array(
				'section'  => 'team',
				'type'     => 'array',
				'default'  => array(),
				'sanitize' => function ( $v ) {
					return $v; },
				'label'    => __( 'Members', 'phantom-core' ),
			),
		);
	}

	private function section_testimonials(): array {
		return array(
			'testimonials_title'       => array(
				'section'  => 'testimonials',
				'type'     => 'string',
				'default'  => 'Testimonials',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Title', 'phantom-core' ),
			),
			'testimonials_title_inner' => array(
				'section'  => 'testimonials',
				'type'     => 'string',
				'default'  => 'Our Client Reviews',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Inner Title', 'phantom-core' ),
			),
			'testimonials_heading'     => array(
				'section'  => 'testimonials',
				'type'     => 'string',
				'default'  => 'Testimonials',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Heading', 'phantom-core' ),
			),
		);
	}

	/**
	 * Announcement bar section entries.
	 *
	 * @return array
	 */
	private function section_announcement_bar(): array {
		return array(
			'announcement_bar_enable'     => array(
				'section'  => 'announcement_bar',
				'type'     => 'bool',
				'default'  => 0,
				'sanitize' => 'absint',
				'label'    => __( 'Enable Announcement Bar', 'phantom-core' ),
			),
			'announcement_bar_text'       => array(
				'section'  => 'announcement_bar',
				'type'     => 'string',
				'default'  => 'Free shipping on orders over $50!',
				'sanitize' => 'sanitize_text_field',
				'label'    => __( 'Announcement Text', 'phantom-core' ),
			),
			'announcement_bar_bg'         => array(
				'section'      => 'announcement_bar',
				'type'         => 'color',
				'default'      => '#000000',
				'sanitize'     => 'sanitize_hex_color',
				'label'        => __( 'Background Color', 'phantom-core' ),
				'css_property' => '--announcement-bar-bg',
				'css_selector' => ':root',
			),
			'announcement_bar_text_color' => array(
				'section'      => 'announcement_bar',
				'type'         => 'color',
				'default'      => '#ffffff',
				'sanitize'     => 'sanitize_hex_color',
				'label'        => __( 'Text Color', 'phantom-core' ),
				'css_property' => '--announcement-bar-color',
				'css_selector' => ':root',
			),
		);
	}

	/**
	 * Sanitize callback that only allows users with manage_options capability.
	 *
	 * @return \Closure
	 */
	private function sanitize_code_passthrough(): \Closure {
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
