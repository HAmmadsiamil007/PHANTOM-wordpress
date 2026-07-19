<?php
declare(strict_types=1);

namespace PhantomCore\Customizer\Controls;

defined( 'ABSPATH' ) || exit;

class Toggle_Control extends Control_Base {

    public static function get_type(): string {
        return 'ast-toggle';
    }

    public static function get_sanitize_callback(): callable {
        return function ( $value ) {
            return in_array( $value, array( '1', 'on', 'yes', true, 1 ), true ) ? '1' : '0';
        };
    }

    public function enqueue(): void {
        wp_enqueue_script( 'phantom-ast-toggle', PHANTOM_CORE_URL . 'admin/js/custom-controls/ast-toggle.js', array( 'customize-controls' ), PHANTOM_CORE_VERSION, true );
    }

    public function render_content(): void {
        ?>
        <label>
            <span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
            <?php if ( $this->description ) : ?>
                <span class="description customize-control-description"><?php echo esc_html( $this->description ); ?></span>
            <?php endif; ?>
            <div class="ast-toggle-container">
                <input type="checkbox" id="ast-toggle-<?php echo esc_attr( $this->id ); ?>"
                       class="ast-toggle-input" value="1"
                       <?php checked( '1', $this->value() ); ?>
                       <?php $this->link(); ?> />
                <label class="ast-toggle-label" for="ast-toggle-<?php echo esc_attr( $this->id ); ?>">
                    <span class="ast-toggle-switch"></span>
                </label>
                <span class="ast-toggle-status"><?php echo esc_html( $this->value() ? 'ON' : 'OFF' ); ?></span>
            </div>
        </label>
        <style>
        .ast-toggle-container { display:flex; align-items:center; gap:10px; padding:6px 0; }
        .ast-toggle-input { display:none; }
        .ast-toggle-label { cursor:pointer; width:44px; height:24px; background:#ccc; border-radius:12px; position:relative; transition:background 0.2s; }
        .ast-toggle-input:checked + .ast-toggle-label { background:#2271b1; }
        .ast-toggle-switch { position:absolute; top:2px; left:2px; width:20px; height:20px; background:#fff; border-radius:50%; transition:transform 0.2s; box-shadow:0 1px 3px rgba(0,0,0,0.2); }
        .ast-toggle-input:checked + .ast-toggle-label .ast-toggle-switch { transform:translateX(20px); }
        .ast-toggle-status { font-size:11px; text-transform:uppercase; color:#666; letter-spacing:1px; font-weight:600; }
        </style>
        <?php
    }
}
