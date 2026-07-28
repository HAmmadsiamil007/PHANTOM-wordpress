<?php
/**
 * Phantom Core — CSS Generation Engine
 *
 * Filter-based modular CSS output, responsive CSS helpers,
 * and breakpoint management.
 *
 * @package Phantom_Core
 */

defined( 'ABSPATH' ) || exit;

class Phantom_Custom_CSS {

	const SITE_OPTION_PREFIX = 'phantom_';
	const CACHE_KEY = 'phantom_dynamic_css';
	const CACHE_TTL = 3600;

	private static ?Phantom_Custom_CSS $instance = null;

	public static function instance(): Phantom_Custom_CSS {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function get_css(): string {
		if ( ! is_customize_preview() ) {
			$cached = get_transient( self::CACHE_KEY );
			if ( false !== $cached ) {
				return $cached;
			}
		}

		$use_file_cache = (bool) get_option( 'phantom_cache_generated_css', false );

		if ( $use_file_cache && ! is_customize_preview() ) {
			$upload_dir = wp_upload_dir();
			$file_path  = $upload_dir['basedir'] . '/phantom-cache/dynamic.css';
			if ( file_exists( $file_path ) ) {
				$cached = file_get_contents( $file_path );
				if ( false !== $cached ) {
					return $cached;
				}
			}
		}

		$css = '';
		$css = apply_filters( 'phantom_dynamic_css', $css );
		if ( ! is_customize_preview() && get_option( 'phantom_performance_minify_css', '0' ) ) {
			$css = self::minify_css( $css );
		}

		if ( $use_file_cache && ! is_customize_preview() ) {
			$upload_dir = wp_upload_dir();
			$cache_dir  = $upload_dir['basedir'] . '/phantom-cache';
			if ( ! is_dir( $cache_dir ) ) {
				wp_mkdir_p( $cache_dir );
			}
			file_put_contents( $cache_dir . '/dynamic.css', $css );
		}

		set_transient( self::CACHE_KEY, $css, self::CACHE_TTL );
		return $css;
	}

	public static function flush_cache(): void {
		delete_transient( self::CACHE_KEY );
		$upload_dir = wp_upload_dir();
		$file_path  = $upload_dir['basedir'] . '/phantom-cache/dynamic.css';
		if ( file_exists( $file_path ) ) {
			@unlink( $file_path );
		}
	}

	public function render_style(): string {
		$css = $this->get_css();
		if ( empty( $css ) ) {
			return '';
		}
		return '<style id="phantom-inline-css">' . "\n" . wp_strip_all_tags( $css ) . "\n" . '</style>';
	}

	public static function get_breakpoints(): array {
		$defaults = array(
			'tablet' => 768,
			'mobile' => 544,
		);
		return apply_filters( 'phantom_breakpoints', $defaults );
	}

	public static function responsive_css( string $setting_key, string $property, string $selector, string $unit = 'px' ): string {
		$breakpoints = self::get_breakpoints();
		$prefix      = self::SITE_OPTION_PREFIX;
		$value       = get_option( $prefix . $setting_key );
		$output      = '';

		if ( is_array( $value ) ) {
			$desktop = $value['desktop'] ?? '';
			$tablet  = $value['tablet'] ?? '';
			$mobile  = $value['mobile'] ?? '';

			if ( '' !== $desktop ) {
				$output .= "\t" . $selector . ' { ' . $property . ': ' . esc_attr( $desktop ) . $unit . '; }' . "\n";
			}
			if ( '' !== $tablet ) {
				$output .= '@media (max-width: ' . $breakpoints['tablet'] . 'px) {' . "\n";
				$output .= "\t" . $selector . ' { ' . $property . ': ' . esc_attr( $tablet ) . $unit . '; }' . "\n";
				$output .= '}' . "\n";
			}
			if ( '' !== $mobile ) {
				$output .= '@media (max-width: ' . $breakpoints['mobile'] . 'px) {' . "\n";
				$output .= "\t" . $selector . ' { ' . $property . ': ' . esc_attr( $mobile ) . $unit . '; }' . "\n";
				$output .= '}' . "\n";
			}
		} else {
			$scalar = $value;
			if ( '' !== $scalar ) {
				$output .= "\t" . $selector . ' { ' . $property . ': ' . esc_attr( $scalar ) . $unit . '; }' . "\n";
			}
		}

		return $output;
	}

	public static function minify_css( string $css ): string {
		$css = preg_replace( '#/\*.*?\*/#s', '', $css );
		$css = preg_replace( '/\s*\{\s*/', '{', $css );
		$css = preg_replace( '/\s*\}\s*/', '}', $css );
		$css = preg_replace( '/\s*:\s*/', ':', $css );
		$css = preg_replace( '/\s*;\s*/', ';', $css );
		$css = preg_replace( '/\s*,\s*/', ',', $css );
		$css = preg_replace( '/\s*>\s*/', '>', $css );
		$css = preg_replace( '/\s+/', ' ', $css );
		return trim( $css );
	}

	public static function parse_css( array $css_array, ?int $min_breakpoint = null, ?int $max_breakpoint = null ): string {
		$css = '';
		foreach ( $css_array as $selector => $properties ) {
			$rules = '';
			foreach ( $properties as $property => $value ) {
				if ( '' !== $value ) {
					$css_val = is_array( $value ) ? implode( ' ', $value ) : $value;
					$rules  .= "\t" . $property . ': ' . esc_attr( $css_val ) . ";\n";
				}
			}
			if ( '' !== $rules ) {
				$css .= $selector . " {\n" . $rules . "}\n";
			}
		}
		if ( null !== $min_breakpoint && null !== $max_breakpoint ) {
			$css = '@media (min-width: ' . $min_breakpoint . 'px) and (max-width: ' . $max_breakpoint . 'px) {' . "\n" . $css . '}';
		} elseif ( null !== $min_breakpoint ) {
			$css = '@media (min-width: ' . $min_breakpoint . 'px) {' . "\n" . $css . '}';
		} elseif ( null !== $max_breakpoint ) {
			$css = '@media (max-width: ' . $max_breakpoint . 'px) {' . "\n" . $css . '}';
		}
		return $css;
	}

	private const LEGACY_TO_TOKEN_MAP = [
		'color_primary'       => 'color.primary',
		'color_secondary'     => 'color.secondary',
		'color_accent'        => 'color.accent',
		'color_background'    => 'color.background',
		'color_text'          => 'color.text.primary',
		'color_heading'       => 'color.text.secondary',
		'color_link'          => 'color.link',
		'color_link_hover'    => 'color.link.hover',
		'color_border'        => 'color.border',
		'header_bg'           => 'color.header.bg',
		'header_text_color'   => 'color.header.text',
		'footer_bg_color'     => 'color.footer.bg',
		'footer_text'         => 'color.footer.text',
		'topbar_bg'           => 'color.topbar.bg',
		'topbar_text'         => 'color.topbar.text',
		'button_bg'           => 'color.button.bg',
		'button_text'         => 'color.button.text',
		'button_bg_hover'     => 'color.button.hover.bg',
		'button_text_hover'   => 'color.button.hover.text',
		'color_rating'        => 'color.rating',
		'color_sale'          => 'color.sale',
	];

	private static array $legacy_token_resolved = [];

	public static function get_css_var_map(): array {
		return self::LEGACY_TO_TOKEN_MAP;
	}

	public static function get_legacy_option( string $legacy_key, $default = '' ) {
		if ( isset( self::$legacy_token_resolved[ $legacy_key ] ) ) {
			return self::$legacy_token_resolved[ $legacy_key ];
		}
		$token_path = self::LEGACY_TO_TOKEN_MAP[ $legacy_key ] ?? null;
		if ( null === $token_path ) {
			self::$legacy_token_resolved[ $legacy_key ] = $default;
			return $default;
		}
		$dsm = \PhantomCore\Design\DesignSystemManager::get_instance();
		$value = $dsm->token( $token_path );
		if ( null === $value || '' === $value ) {
			$value = get_option( 'phantom_' . $legacy_key, $default );
		}
		self::$legacy_token_resolved[ $legacy_key ] = $value;
		return $value;
	}

	public static function clear_legacy_token_cache(): void {
		self::$legacy_token_resolved = [];
	}
}
