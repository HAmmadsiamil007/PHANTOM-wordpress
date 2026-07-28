<?php
/**
 * Product / WooCommerce CSS Module
 *
 * @package Phantom_Core
 */

defined( 'ABSPATH' ) || exit;

add_filter(
	'phantom_dynamic_css',
	function ( string $css ): string {
		$keys = array(
			'color_rating',
			'color_sale',
			'color_card_bg',
			'color_card_text',
			'color_card_border',
			'color_button_bg',
			'color_button_text',
			'color_button_hover_bg',
			'color_badge_sale_bg',
			'color_badge_sale_text',
			'color_badge_new_bg',
			'color_badge_new_text',
		);

		$map    = \PhantomCore\Settings_Registry::get_css_var_map();
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
	80
);
