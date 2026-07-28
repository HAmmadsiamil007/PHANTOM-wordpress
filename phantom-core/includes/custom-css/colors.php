<?php
/**
 * Colors / Buttons / Forms CSS Module
 *
 * @package Phantom_Core
 */

defined( 'ABSPATH' ) || exit;

add_filter(
	'phantom_dynamic_css',
	function ( string $css ): string {
		$keys = array(
			'color_primary', 'color_secondary', 'color_accent',
			'color_background', 'color_text', 'color_heading',
			'color_link', 'color_link_hover', 'color_border',
			'header_bg', 'header_text_color',
			'footer_bg_color', 'footer_text',
			'topbar_bg', 'topbar_text',
			'button_bg', 'button_text', 'button_bg_hover', 'button_text_hover',
			'color_rating', 'color_sale',
		);

		$map = \PhantomCore\Settings_Registry::get_css_var_map();
		$output = '';

		foreach ( $keys as $k ) {
			if ( ! isset( $map[ $k ] ) ) {
				continue;
			}
			$val = \PhantomCore\Engine\Phantom_Custom_CSS::get_legacy_option( $k );
			if ( '' !== $val ) {
				$output .= "\t" . $map[ $k ] . ': ' . esc_attr( $val ) . ';' . "\n";
			}
		}

		if ( '' !== $output ) {
			$css .= ':root {' . "\n" . $output . '}' . "\n";
		}

		return $css;
	},
	10
);
