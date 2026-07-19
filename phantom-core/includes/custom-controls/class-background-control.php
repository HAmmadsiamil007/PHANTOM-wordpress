<?php
/**
 * Phantom Core — Background Control
 *
 * Color picker + image upload + position + repeat + size + overlay + gradient.
 *
 * @package Phantom_Core
 */

namespace PhantomCore\Customizer\Controls;

defined( 'ABSPATH' ) || exit;

class Background_Control extends Control_Base {

	public string $type = 'ast-background';

	public static function get_type(): string {
		return 'ast-background';
	}

	public static function get_sanitize_callback(): callable {
		return function ( $value ) {
			if ( is_array( $value ) ) {
				$color = $value['color'] ?? '';
				if ( '' !== $color && ! preg_match( '/^(#[a-fA-F0-9]{3,6}|rgba?\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*(?:,\s*(?:0|1|0?\.\d+)\s*)?\))?$/', $color ) ) {
					$color = '';
				}
				$overlay = $value['overlay_color'] ?? '';
				if ( '' !== $overlay && ! preg_match( '/^(#[a-fA-F0-9]{3,6}|rgba?\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*(?:,\s*(?:0|1|0?\.\d+)\s*)?\))?$/', $overlay ) ) {
					$overlay = '';
				}
				$parsed = array(
					'color'           => $color,
					'image'           => esc_url_raw( $value['image'] ?? '' ),
					'position'        => sanitize_text_field( $value['position'] ?? 'center center' ),
					'repeat'          => sanitize_text_field( $value['repeat'] ?? 'no-repeat' ),
					'size'            => sanitize_text_field( $value['size'] ?? 'cover' ),
					'attachment'      => sanitize_text_field( $value['attachment'] ?? 'scroll' ),
					'overlay_color'   => $overlay,
					'overlay_opacity' => floatval( $value['overlay_opacity'] ?? 0.5 ),
				);
				return $parsed;
			}
			return array();
		};
	}

	public function enqueue(): void {
		wp_enqueue_script(
			'ast-background',
			PHANTOM_CORE_URL . 'admin/js/custom-controls/ast-background.js',
			array( 'jquery', 'customize-controls' ),
			PHANTOM_CORE_VERSION,
			true
		);
	}

	public function render_content(): void {
		$label = $this->label ?? '';
		$desc  = $this->description ?? '';
		$value = $this->value();
		$parsed = is_array( $value ) ? $value : array(
			'color'           => '',
			'image'           => '',
			'position'        => 'center center',
			'repeat'          => 'no-repeat',
			'size'            => 'cover',
			'attachment'      => 'scroll',
			'overlay_color'   => '',
			'overlay_opacity' => 0.5,
		);
		?>
		<div class="ast-background-wrapper">
			<?php if ( $label ) : ?>
				<span class="customize-control-title"><?php echo esc_html( $label ); ?></span>
			<?php endif; ?>
			<?php if ( $desc ) : ?>
				<span class="description customize-control-description"><?php echo esc_html( $desc ); ?></span>
			<?php endif; ?>
			<div class="ast-background-fields" data-bg-id="<?php echo esc_attr( $this->id ); ?>">
				<input type="hidden" class="ast-bg-color" value="<?php echo esc_attr( $parsed['color'] ); ?>" />
				<input type="hidden" class="ast-bg-image" value="<?php echo esc_attr( $parsed['image'] ); ?>" />
				<input type="hidden" class="ast-bg-position" value="<?php echo esc_attr( $parsed['position'] ); ?>" />
				<input type="hidden" class="ast-bg-repeat" value="<?php echo esc_attr( $parsed['repeat'] ); ?>" />
				<input type="hidden" class="ast-bg-size" value="<?php echo esc_attr( $parsed['size'] ); ?>" />
				<input type="hidden" class="ast-bg-attachment" value="<?php echo esc_attr( $parsed['attachment'] ); ?>" />
				<input type="hidden" class="ast-bg-overlay-color" value="<?php echo esc_attr( $parsed['overlay_color'] ); ?>" />
				<input type="hidden" class="ast-bg-overlay-opacity" value="<?php echo esc_attr( (string) $parsed['overlay_opacity'] ); ?>" />
			</div>
		</div>
		<?php
	}
}
