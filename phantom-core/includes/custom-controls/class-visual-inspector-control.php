<?php
declare(strict_types=1);

namespace PhantomCore\Customizer\Controls;

defined( 'ABSPATH' ) || exit;

/**
 * Visual_Inspector_Control — container for the element inspector.
 *
 * The bridge (customizer-visual-editor.js) injects the inspector panels
 * (rendered by Inspector_Factory over REST) into #phantom-visual-inspector
 * whenever an element is clicked in the preview. No setting is attached.
 */
class Visual_Inspector_Control extends Control_Base {

	public $type = 'ast-visual-inspector';

	public static function get_type(): string {
		return 'ast-visual-inspector';
	}

	public static function get_sanitize_callback(): callable {
		return static function ( $value ) {
			return sanitize_text_field( (string) $value );
		};
	}

	public function render_content(): void {
		echo '<div id="phantom-visual-inspector" class="phantom-visual-inspector">';
		echo '<p class="phantom-inspector-hint">' . esc_html__( 'Enable Start Editing, then click any element in the preview. Its settings will appear here.', 'phantom-core' ) . '</p>';
		echo '</div>';
	}

	public function content_template(): void {
		?>
		<div id="phantom-visual-inspector" class="phantom-visual-inspector">
			<p class="phantom-inspector-hint">Enable Start Editing, then click any element in the preview. Its settings will appear here.</p>
		</div>
		<?php
	}
}
