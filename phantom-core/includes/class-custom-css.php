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
		if ( ! is_customize_preview() ) {
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
				$output .= "\t" . $selector . ' { ' . $property . ': ' . $desktop . $unit . '; }' . "\n";
			}
			if ( '' !== $tablet ) {
				$output .= '@media (max-width: ' . $breakpoints['tablet'] . 'px) {' . "\n";
				$output .= "\t" . $selector . ' { ' . $property . ': ' . $tablet . $unit . '; }' . "\n";
				$output .= '}' . "\n";
			}
			if ( '' !== $mobile ) {
				$output .= '@media (max-width: ' . $breakpoints['mobile'] . 'px) {' . "\n";
				$output .= "\t" . $selector . ' { ' . $property . ': ' . $mobile . $unit . '; }' . "\n";
				$output .= '}' . "\n";
			}
		} else {
			$scalar = $value;
			if ( '' !== $scalar ) {
				$output .= "\t" . $selector . ' { ' . $property . ': ' . $scalar . $unit . '; }' . "\n";
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
					$rules .= "\t" . $property . ': ' . $value . ";\n";
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
}
