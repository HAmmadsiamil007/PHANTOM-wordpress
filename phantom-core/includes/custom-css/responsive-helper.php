<?php
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'phantom_responsive_css' ) ) {
    function phantom_responsive_css( string $setting_key, string $property, string $selector, string $unit = 'px' ): string {
        $breakpoints = apply_filters( 'phantom_breakpoints', array( 'tablet' => 768, 'mobile' => 544 ) );
        $prefix      = 'phantom_';
        $value       = get_option( $prefix . $setting_key );
        $output      = '';

		if ( is_array( $value ) ) {
			$desktop = $value['desktop'] ?? '';
			$tablet  = $value['tablet'] ?? '';
			$mobile  = $value['mobile'] ?? '';

			if ( '' !== $desktop ) {
				$output .= "\t" . esc_attr( $selector ) . ' { ' . esc_attr( $property ) . ': ' . esc_attr( $desktop ) . esc_attr( $unit ) . '; }' . "\n";
			}
			if ( '' !== $tablet ) {
				$output .= '@media (max-width: ' . esc_attr( $breakpoints['tablet'] ) . 'px) {' . "\n";
				$output .= "\t" . esc_attr( $selector ) . ' { ' . esc_attr( $property ) . ': ' . esc_attr( $tablet ) . esc_attr( $unit ) . '; }' . "\n";
				$output .= '}' . "\n";
			}
			if ( '' !== $mobile ) {
				$output .= '@media (max-width: ' . esc_attr( $breakpoints['mobile'] ) . 'px) {' . "\n";
				$output .= "\t" . esc_attr( $selector ) . ' { ' . esc_attr( $property ) . ': ' . esc_attr( $mobile ) . esc_attr( $unit ) . '; }' . "\n";
				$output .= '}' . "\n";
			}
		} else {
			$scalar = $value;
			if ( '' !== $scalar ) {
				$output .= "\t" . esc_attr( $selector ) . ' { ' . esc_attr( $property ) . ': ' . esc_attr( $scalar ) . esc_attr( $unit ) . '; }' . "\n";
			}
		}

        return $output;
    }
}
