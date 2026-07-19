<?php
/**
 * Phantom Core — Global Color Palette
 *
 * 9-color palette system with presets, dark mode, and Gutenberg integration.
 *
 * @package Phantom_Core
 */

defined( 'ABSPATH' ) || exit;

class Phantom_Global_Palette {

	const OPTION_KEY = 'phantom_global_palette';

	private static ?Phantom_Global_Palette $instance = null;

	private array $palettes = array();

	public static function instance(): Phantom_Global_Palette {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function init(): void {
		$this->palettes = $this->get_default_presets();
		add_action( 'init', array( $this, 'register_gutenberg_palette' ) );
		add_filter( 'phantom_dynamic_css', array( $this, 'output_palette_css' ), 5 );
		add_filter( 'phantom_dynamic_css', array( $this, 'output_dark_mode_css' ), 6 );
	}

	public function get_default_presets(): array {
		return array(
			'light' => array(
				'label'  => __( 'Light Default', 'phantom-core' ),
				'colors' => array(
					__( 'Brand', 'phantom-core' )       => '#705b53',
					__( 'Alternate Brand', 'phantom-core' ) => '#8a756b',
					__( 'Heading', 'phantom-core' )     => '#222222',
					__( 'Text', 'phantom-core' )        => '#555555',
					__( 'Background', 'phantom-core' )  => '#ffffff',
					__( 'Light BG', 'phantom-core' )    => '#f5f3f2',
					__( 'Border', 'phantom-core' )      => '#e5e5e5',
					__( 'Success', 'phantom-core' )     => '#2e7d32',
					__( 'Error', 'phantom-core' )       => '#d32f2f',
				),
			),
			'dark'  => array(
				'label'  => __( 'Dark Mode', 'phantom-core' ),
				'colors' => array(
					__( 'Brand', 'phantom-core' )       => '#8a756b',
					__( 'Alternate Brand', 'phantom-core' ) => '#705b53',
					__( 'Heading', 'phantom-core' )     => '#e0e0e0',
					__( 'Text', 'phantom-core' )        => '#aaaaaa',
					__( 'Background', 'phantom-core' )  => '#1a1a1a',
					__( 'Light BG', 'phantom-core' )    => '#2a2a2a',
					__( 'Border', 'phantom-core' )      => '#333333',
					__( 'Success', 'phantom-core' )     => '#4caf50',
					__( 'Error', 'phantom-core' )       => '#f44336',
				),
			),
			'vibrant' => array(
				'label'  => __( 'Vibrant', 'phantom-core' ),
				'colors' => array(
					__( 'Brand', 'phantom-core' )       => '#e63946',
					__( 'Alternate Brand', 'phantom-core' ) => '#457b9d',
					__( 'Heading', 'phantom-core' )     => '#1d3557',
					__( 'Text', 'phantom-core' )        => '#333333',
					__( 'Background', 'phantom-core' )  => '#ffffff',
					__( 'Light BG', 'phantom-core' )    => '#f1faee',
					__( 'Border', 'phantom-core' )      => '#a8dadc',
					__( 'Success', 'phantom-core' )     => '#2d6a4f',
					__( 'Error', 'phantom-core' )       => '#e63946',
				),
			),
			'pastel' => array(
				'label'  => __( 'Pastel', 'phantom-core' ),
				'colors' => array(
					__( 'Brand', 'phantom-core' )       => '#b8a9c9',
					__( 'Alternate Brand', 'phantom-core' ) => '#c9b8a9',
					__( 'Heading', 'phantom-core' )     => '#4a4a4a',
					__( 'Text', 'phantom-core' )        => '#6b6b6b',
					__( 'Background', 'phantom-core' )  => '#faf8f5',
					__( 'Light BG', 'phantom-core' )    => '#f0ece4',
					__( 'Border', 'phantom-core' )      => '#e0d8cc',
					__( 'Success', 'phantom-core' )     => '#7ebc8d',
					__( 'Error', 'phantom-core' )       => '#d4a5a5',
				),
			),
		);
	}

	public function get_current_palette(): array {
		$saved = get_option( self::OPTION_KEY, array() );
		$slug  = $saved['current'] ?? 'light';
		$palette = $this->palettes[ $slug ] ?? $this->palettes['light'];
		$colors  = $saved['overrides'] ?? array();

		$i = 0;
		$merged = array();
		foreach ( $palette['colors'] as $label => $default ) {
			$merged[ $label ] = $colors[ $i ] ?? $default;
			$i++;
		}
		return $merged;
	}

	public function get_palette_css_vars( string $suffix = '' ): string {
		$colors = $this->get_current_palette();
		$css    = '';
		$i      = 0;
		$sfx    = $suffix ? sanitize_key( $suffix ) : '';
		foreach ( $colors as $label => $hex ) {
			$css .= "\t--phantom-color-{$i}{$sfx}: {$hex};\n";
			$i++;
		}
		return $css;
	}

	public function output_palette_css( string $css ): string {
		if ( is_customize_preview() ) {
			return $css;
		}
		$palette_css = ':root {' . "\n" . $this->get_palette_css_vars() . '}' . "\n";
		return $palette_css . $css;
	}

	public function output_dark_mode_css( string $css ): string {
		$saved  = get_option( self::OPTION_KEY, array() );
		$active = $saved['current'] ?? 'light';
		if ( 'dark' === $active ) {
			return $css;
		}
		if ( empty( $this->palettes['dark'] ) ) {
			return $css;
		}
		$dark_css = '@media (prefers-color-scheme: dark) {' . "\n";
		$dark_css .= "\t" . ':root {' . "\n";
		$i = 0;
		foreach ( $this->palettes['dark']['colors'] as $label => $hex ) {
			$dark_css .= "\t\t--phantom-color-{$i}: {$hex};\n";
			$i++;
		}
		$dark_css .= "\t}" . "\n";
		$dark_css .= '}' . "\n";
		return $css . $dark_css;
	}

	public function is_dark_palette(): bool {
		$saved  = get_option( self::OPTION_KEY, array() );
		return ( $saved['current'] ?? 'light' ) === 'dark';
	}

	public function register_gutenberg_palette(): void {
		$colors = $this->get_current_palette();
		$editor_colors = array();
		$i = 0;
		foreach ( $colors as $label => $hex ) {
			$editor_colors[] = array(
				'name'  => $label,
				'slug'  => 'phantom-color-' . $i,
				'color' => $hex,
			);
			$i++;
		}
		add_theme_support( 'editor-color-palette', $editor_colors );
	}
}
