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
			'header_bg', 'header_text_color', 'header_padding', 'header_padding_y', 'header_padding_x',
			'header_height', 'header_fullwidth', 'header_transparent',
			'topbar_bg', 'topbar_text_color',
			'menu_font_size', 'menu_font_weight',
			'submenu_bg', 'submenu_text_color', 'submenu_width',
			'sticky_header',
		);

		$px_keys = array( 'header_padding', 'header_padding_y', 'header_padding_x', 'header_height', 'submenu_width', 'menu_font_size' );

		$map    = \PhantomCore\Settings_Registry::get_css_var_map();
		$output = '';

		foreach ( $keys as $k ) {
			if ( ! isset( $map[ $k ] ) ) {
				continue;
			}
			$val = get_option( 'phantom_' . $k, '' );
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
