<?php

defined( 'ABSPATH' ) || exit;

add_filter(
	'phantom_dynamic_css',
	function ( string $css ): string {
		$prefix  = 'phantom_';
		$output  = '';

		$desktop = \Phantom_Custom_CSS::get_legacy_option( 'hero_banner_image' );
		$tablet  = \Phantom_Custom_CSS::get_legacy_option( 'hero_image_tablet' );
		$mobile  = \Phantom_Custom_CSS::get_legacy_option( 'hero_image_mobile' );

		// Fallback to default images if no custom image uploaded.
		$default_desktop = PHANTOM_CORE_URL . 'frontend/assets/images/banner-img1.png';
		$default_tablet  = PHANTOM_CORE_URL . 'frontend/assets/images/banner-img2.png';
		$default_mobile  = PHANTOM_CORE_URL . 'frontend/assets/images/banner-bg-img.png';
		if ( '' === $desktop || ! filter_var( $desktop, FILTER_VALIDATE_URL ) ) {
			$desktop = $default_desktop;
		}
		if ( '' === $tablet || ! filter_var( $tablet, FILTER_VALIDATE_URL ) ) {
			$tablet = $default_tablet;
		}
		if ( '' === $mobile || ! filter_var( $mobile, FILTER_VALIDATE_URL ) ) {
			$mobile = $default_mobile;
		}
		$enabled = (bool) \Phantom_Custom_CSS::get_legacy_option( 'hero_enable_responsive', 1 );
		$tablet_bp = absint( \Phantom_Custom_CSS::get_legacy_option( 'hero_tablet_breakpoint', 1024 ) );
		$mobile_bp = absint( \Phantom_Custom_CSS::get_legacy_option( 'hero_mobile_breakpoint', 768 ) );
		$fit      = \Phantom_Custom_CSS::get_legacy_option( 'hero_fit', 'cover' );
		$position = \Phantom_Custom_CSS::get_legacy_option( 'hero_position', 'center' );
		$opacity  = \Phantom_Custom_CSS::get_legacy_option( 'hero_overlay_opacity', 50 );

		$output .= "\t" . '--hero-image-desktop: url("' . esc_url( $desktop ) . '");' . "\n";
		$output .= "\t" . '--hero-image: url("' . esc_url( $desktop ) . '");' . "\n";
		if ( $enabled ) {
			$output .= '@media (max-width: ' . $tablet_bp . 'px) {' . "\n";
			$output .= "\t" . ':root { --hero-image-tablet: url("' . esc_url( $tablet ) . '"); --hero-image: url("' . esc_url( $tablet ) . '"); }' . "\n";
			$output .= '}' . "\n";
			$output .= '@media (max-width: ' . $mobile_bp . 'px) {' . "\n";
			$output .= "\t" . ':root { --hero-image-mobile: url("' . esc_url( $mobile ) . '"); --hero-image: url("' . esc_url( $mobile ) . '"); }' . "\n";
			$output .= '}' . "\n";
		}

		$output .= "\t" . '--hero-object-fit: ' . esc_attr( $fit ) . ';' . "\n";
		$output .= "\t" . '--hero-object-position: ' . esc_attr( $position ) . ';' . "\n";
		$pos_val = '50%';
		switch ( $position ) {
			case 'top':    $pos_val = '50% 0%'; break;
			case 'bottom': $pos_val = '50% 100%'; break;
			case 'left':   $pos_val = '0% 50%'; break;
			case 'right':  $pos_val = '100% 50%'; break;
		}
		$output .= "\t" . '--hero-bg-position: ' . $pos_val . ';' . "\n";
		$output .= "\t" . '--hero-overlay-opacity: ' . absint( $opacity ) . '%;' . "\n";

		// Animation tool consumption (visual editor part "animation").
		$animation = \Phantom_Custom_CSS::get_legacy_option( 'hero_animation', '' );
		if ( '' !== $animation && 'none' !== $animation ) {
			$output .= "\t" . '--hero-animation: ' . esc_attr( $animation ) . ';' . "\n";
			$delay = \Phantom_Custom_CSS::get_legacy_option( 'hero_animation_delay', '' );
			if ( '' !== $delay ) {
				$output .= "\t" . '--hero-animation-delay: ' . absint( $delay ) . 'ms;' . "\n";
			}
			$duration = \Phantom_Custom_CSS::get_legacy_option( 'hero_animation_duration', '' );
			if ( '' !== $duration ) {
				$output .= "\t" . '--hero-animation-duration: ' . absint( $duration ) . 'ms;' . "\n";
			}
		}

		if ( '' !== $output ) {
			$css .= '[data-hero-area] {' . "\n" . $output . '}' . "\n";
			$css .= ':root {' . "\n" . $output . '}' . "\n";
		}

		// Responsive tool consumption (visual editor part "responsive").
		$hide_desktop = \Phantom_Custom_CSS::get_legacy_option( 'hero_hide_desktop', '' );
		$hide_tablet  = \Phantom_Custom_CSS::get_legacy_option( 'hero_hide_tablet', '' );
		$hide_mobile  = \Phantom_Custom_CSS::get_legacy_option( 'hero_hide_mobile', '' );
		if ( $hide_desktop ) {
			$css .= '@media (max-width: 1024px) { [data-component="hero"] { display: none !important; } }' . "\n";
		}
		if ( $hide_tablet ) {
			$css .= '@media (max-width: 768px) { [data-component="hero"] { display: none !important; } }' . "\n";
		}
		if ( $hide_mobile ) {
			$css .= '@media (max-width: 576px) { [data-component="hero"] { display: none !important; } }' . "\n";
		}

		return $css;
	},
	35
);
