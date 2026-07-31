<?php
declare(strict_types=1);

namespace PhantomCore\Customizer\Controls;

defined( 'ABSPATH' ) || exit;

/**
 * Visual_Toggle_Control — "Start Editing" toggle for click-to-edit mode.
 *
 * No setting is attached; the state lives in customizer-visual-editor.js and
 * is pushed to the selection engine inside the preview iframe.
 */
class Visual_Toggle_Control extends Control_Base {

	public $type = 'ast-visual-toggle';

	public static function get_type(): string {
		return 'ast-visual-toggle';
	}

	public static function get_sanitize_callback(): callable {
		return static function ( $value ) {
			return $value ? '1' : '0';
		};
	}

	public function render_content(): void {
		$label = $this->label ? $this->label : __( 'Start Editing', 'phantom-core' );
		$desc  = $this->description ? $this->description : __( 'Click any element in the preview to select it and open its settings here.', 'phantom-core' );
		?>
		<label class="phantom-visual-toggle">
			<input type="checkbox" id="phantom-live-preview-edit" />
			<span class="phantom-toggle-switch" aria-hidden="true"></span>
			<span class="phantom-toggle-text"><?php echo esc_html( $label ); ?></span>
		</label>
		<?php if ( $desc ) : ?>
			<p class="description"><?php echo esc_html( $desc ); ?></p>
		<?php endif; ?>
		<?php
	}

	public function content_template(): void {
		?>
		<label class="phantom-visual-toggle">
			<input type="checkbox" id="phantom-live-preview-edit" />
			<span class="phantom-toggle-switch" aria-hidden="true"></span>
			<span class="phantom-toggle-text"><# if ( data.label ) { #>{{ data.label }}<# } else { #>Start Editing<# } #></span>
		</label>
		<# if ( data.description ) { #>
			<p class="description">{{ data.description }}</p>
		<# } else { #>
			<p class="description">Click any element in the preview to select it and open its settings here.</p>
		<# } #>
		<?php
	}
}
