<?php
/**
 * Header / Topbar / Navigation CSS Module
 *
 * @package Phantom_Core
 */

defined( 'ABSPATH' ) || exit;

add_filter(
	'phantom_dynamic_css',
	function ( string $css ): string {
		$keys = array(
			'header_bg', 'header_text_color',
			'header_padding_y', 'header_padding_x',
			'header_height',
			'topbar_bg', 'topbar_text',
			'menu_font_size',
		);

		$px_keys = array( 'header_padding_y', 'header_padding_x', 'header_height', 'menu_font_size' );

		$map    = \PhantomCore\Settings_Registry::get_css_var_map();
		$output = '';

		foreach ( $keys as $k ) {
			if ( ! isset( $map[ $k ] ) ) {
				continue;
			}
			$val = \PhantomCore\Engine\Phantom_Custom_CSS::get_legacy_option( $k );
			if ( is_array( $val ) ) {
				$val = $val['desktop'] ?? '';
			}
			if ( '' !== $val ) {
				$val_display = $val;
				if ( in_array( $k, $px_keys, true ) && is_numeric( $val ) ) {
					$val_display .= 'px';
				}
				$output .= "\t" . $map[ $k ] . ': ' . esc_attr( (string) $val_display ) . ';' . "\n";
			}
		}

		if ( '' !== $output ) {
			$css .= ':root {' . "\n" . $output . '}' . "\n";
		}

		return $css;
	},
	30
);
