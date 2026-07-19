<?php
/**
 * Phantom Core — Color Group Control
 *
 * Groups related colors (normal/hover) into expandable parent.
 *
 * @package Phantom_Core
 */

namespace PhantomCore\Customizer\Controls;

defined( 'ABSPATH' ) || exit;

class Color_Group_Control extends Control_Base {

	public string $type = 'ast-color-group';

	public static function get_type(): string {
		return 'ast-color-group';
	}

	public static function get_sanitize_callback(): callable {
		return function ( $value ) {
			if ( is_array( $value ) ) {
				$sanitized = array();
				foreach ( $value as $k => $v ) {
					$sanitized[ sanitize_key( $k ) ] = preg_match( '/^(#[a-fA-F0-9]{3,6}|rgba?\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*(?:,\s*(?:0|1|0?\.\d+)\s*)?\))?$/', (string) $v ) ? $v : '';
				}
				return $sanitized;
			}
			return array();
		};
	}

	public function enqueue(): void {
		wp_enqueue_script(
			'ast-color-group',
			PHANTOM_CORE_URL . 'admin/js/custom-controls/ast-color-group.js',
			array( 'jquery', 'customize-controls' ),
			PHANTOM_CORE_VERSION,
			true
		);
	}

	public function render_content(): void {
		$label = $this->label ?? '';
		$desc  = $this->description ?? '';
		?>
		<div class="ast-color-group-wrapper">
			<?php if ( $label ) : ?>
				<span class="customize-control-title"><?php echo esc_html( $label ); ?></span>
			<?php endif; ?>
			<?php if ( $desc ) : ?>
				<span class="description customize-control-description"><?php echo esc_html( $desc ); ?></span>
			<?php endif; ?>
			<div class="ast-color-group-items" data-group-id="<?php echo esc_attr( $this->id ); ?>"></div>
		</div>
		<?php
	}
}
