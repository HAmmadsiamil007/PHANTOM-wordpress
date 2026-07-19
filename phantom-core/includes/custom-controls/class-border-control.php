<?php
/**
 * Phantom Core — Border Control
 *
 * Width (top/right/bottom/left) + color + radius with linked/unlinked toggles.
 *
 * @package Phantom_Core
 */

namespace PhantomCore\Customizer\Controls;

defined( 'ABSPATH' ) || exit;

class Border_Control extends Control_Base {

	public string $type = 'ast-border';

	public static function get_type(): string {
		return 'ast-border';
	}

	public static function get_sanitize_callback(): callable {
		return function ( $value ) {
			if ( is_array( $value ) ) {
				$radius = $value['radius'] ?? 0;
				if ( is_string( $radius ) && '' !== $radius ) {
					$radius = preg_match( '/^-?\d+(?:\.\d+)?(?:px|%|em|rem)?$/', $radius ) ? $radius : '0';
				} else {
					$radius = absint( $radius );
				}
				$parsed = array(
					'top'    => absint( $value['top'] ?? 0 ),
					'right'  => absint( $value['right'] ?? 0 ),
					'bottom' => absint( $value['bottom'] ?? 0 ),
					'left'   => absint( $value['left'] ?? 0 ),
					'color'  => sanitize_text_field( $value['color'] ?? '' ),
					'radius' => $radius,
					'linked' => ! empty( $value['linked'] ),
				);
				return $parsed;
			}
			return array();
		};
	}

	public function enqueue(): void {
		wp_enqueue_script(
			'ast-border',
			PHANTOM_CORE_URL . 'admin/js/custom-controls/ast-border.js',
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
			'top'    => 0,
			'right'  => 0,
			'bottom' => 0,
			'left'   => 0,
			'color'  => '',
			'radius' => 0,
			'linked' => true,
		);
		?>
		<div class="ast-border-wrapper">
			<?php if ( $label ) : ?>
				<span class="customize-control-title"><?php echo esc_html( $label ); ?></span>
			<?php endif; ?>
			<?php if ( $desc ) : ?>
				<span class="description customize-control-description"><?php echo esc_html( $desc ); ?></span>
			<?php endif; ?>
			<div class="ast-border-fields" data-border-id="<?php echo esc_attr( $this->id ); ?>">
				<input type="hidden" class="ast-border-top" value="<?php echo esc_attr( (string) $parsed['top'] ); ?>" />
				<input type="hidden" class="ast-border-right" value="<?php echo esc_attr( (string) $parsed['right'] ); ?>" />
				<input type="hidden" class="ast-border-bottom" value="<?php echo esc_attr( (string) $parsed['bottom'] ); ?>" />
				<input type="hidden" class="ast-border-left" value="<?php echo esc_attr( (string) $parsed['left'] ); ?>" />
				<input type="hidden" class="ast-border-color" value="<?php echo esc_attr( $parsed['color'] ); ?>" />
				<input type="hidden" class="ast-border-radius" value="<?php echo esc_attr( (string) $parsed['radius'] ); ?>" />
				<input type="hidden" class="ast-border-linked" value="<?php echo esc_attr( $parsed['linked'] ? '1' : '0' ); ?>" />
			</div>
		</div>
		<?php
	}
}
