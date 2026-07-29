<?php
/**
 * Typography CSS Module
 *
 * @package Phantom_Core
 */

defined( 'ABSPATH' ) || exit;

add_filter(
	'phantom_dynamic_css',
	function ( string $css ): string {
		$map    = \PhantomCore\Settings_Registry::get_css_var_map();
		$output = '';

		$keys = array(
			'typography_body_font', 'typography_body_weight', 'typography_body_style',
			'typography_base_size', 'typography_line_height', 'typography_body_spacing',
			'typography_heading_font', 'typography_heading_weight',
			'typography_heading_case', 'typography_heading_spacing',
			'typography_h1_size', 'typography_h1_height',
			'typography_h2_size', 'typography_h2_height',
			'typography_h3_size', 'typography_h3_height',
			'typography_h4_size', 'typography_h4_height',
			'typography_h5_size', 'typography_h5_height',
			'typography_h6_size', 'typography_h6_height',
			'menu_font_size',
		);

		$px_keys = \PhantomCore\Settings_Registry::get_px_keys();

		foreach ( $keys as $k ) {
			if ( ! isset( $map[ $k ] ) ) {
				continue;
			}

			$val = \Phantom_Custom_CSS::get_legacy_option( $k );
			if ( '' !== $val ) {
				$val_display = $val;
				if ( in_array( $k, $px_keys, true ) && is_numeric( $val ) ) {
					$val_display .= 'px';
				}
				$output .= "\t" . $map[ $k ] . ': ' . wp_strip_all_tags( $val_display ) . ';' . "\n";
			}
		}

		$headings = array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' );
		foreach ( $headings as $h ) {
			$prefix = 'typography_' . $h . '_';

			$font = \Phantom_Custom_CSS::get_legacy_option( $prefix . 'font' );
			if ( '' === $font ) {
				$font = \Phantom_Custom_CSS::get_legacy_option( 'typography_heading_font' );
			}
			if ( '' === $font ) {
				$font = \Phantom_Custom_CSS::get_legacy_option( 'typography_body_font', 'Archivo' );
			}
			if ( isset( $map[ $prefix . 'font' ] ) ) {
				$output .= "\t" . $map[ $prefix . 'font' ] . ': ' . wp_strip_all_tags( $font ) . ";\n";
			}

			$weight = \Phantom_Custom_CSS::get_legacy_option( $prefix . 'weight' );
			if ( '' === $weight ) {
				$weight = \Phantom_Custom_CSS::get_legacy_option( 'typography_heading_weight', '500' );
			}
			if ( isset( $map[ $prefix . 'weight' ] ) ) {
				$output .= "\t" . $map[ $prefix . 'weight' ] . ': ' . esc_attr( $weight ) . ";\n";
			}

			if ( isset( $map[ $prefix . 'style' ] ) ) {
				$style = \Phantom_Custom_CSS::get_legacy_option( $prefix . 'style', 'normal' );
				$output .= "\t" . $map[ $prefix . 'style' ] . ': ' . esc_attr( $style ) . ";\n";
			}

			$spacing = \Phantom_Custom_CSS::get_legacy_option( $prefix . 'spacing' );
			if ( '' === $spacing ) {
				$spacing = \Phantom_Custom_CSS::get_legacy_option( 'typography_heading_spacing', 0 );
			}
			if ( isset( $map[ $prefix . 'spacing' ] ) ) {
				$output .= "\t" . $map[ $prefix . 'spacing' ] . ': ' . esc_attr( floatval( $spacing ) ) . "px;\n";
			}

			if ( isset( $map[ $prefix . 'case' ] ) ) {
				$case = \Phantom_Custom_CSS::get_legacy_option( $prefix . 'case' );
				if ( '' === $case ) {
					$case = \Phantom_Custom_CSS::get_legacy_option( 'typography_heading_case', 'none' );
				}
				$output .= "\t" . $map[ $prefix . 'case' ] . ': ' . esc_attr( $case ) . ";\n";
			}
		}

		if ( '' !== $output ) {
			$css .= ':root {' . "\n" . $output . '}' . "\n";
		}

		return $css;
	},
	20
);
