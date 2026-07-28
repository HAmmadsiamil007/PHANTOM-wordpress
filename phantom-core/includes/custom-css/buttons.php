<?php
/**
 * Buttons CSS Module
 *
 * @package Phantom_Core
 */

defined( 'ABSPATH' ) || exit;

add_filter(
	'phantom_dynamic_css',
	function ( string $css ): string {
		$keys = array(
			'button_bg', 'button_text',
			'button_bg_hover', 'button_text_hover',
			'button_padding_x', 'button_padding_y',
		);

		$px_keys = array( 'button_padding_x', 'button_padding_y' );

		$map    = \PhantomCore\Settings_Registry::get_css_var_map();
		$output = '';

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
				$output .= "\t" . $map[ $k ] . ': ' . esc_attr( $val_display ) . ';' . "\n";
			}
		}

		if ( '' !== $output ) {
			$css .= ':root {' . "\n" . $output . '}' . "\n";
		}

		return $css;
	},
	60
);
