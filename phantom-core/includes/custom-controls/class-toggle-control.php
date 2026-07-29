<?php
declare(strict_types=1);

namespace PhantomCore\Customizer\Controls;

defined( 'ABSPATH' ) || exit;

class Toggle_Control extends Control_Base {

    public $type = 'ast-toggle';

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
        <?php
    }

    public function content_template(): void {
        ?>
        <label>
            <span class="customize-control-title">{{ data.label }}</span>
            <# if ( data.description ) { #>
                <span class="description customize-control-description">{{ data.description }}</span>
            <# } #>
            <div class="ast-toggle-container">
                <input type="checkbox" id="ast-toggle-{{ data.id }}"
                       class="ast-toggle-input" value="1"
                       <# if ( '1' == data.value ) { #>checked="checked"<# } #>
                       data-customize-setting-link="{{ data.settings.default }}" />
                <label class="ast-toggle-label" for="ast-toggle-{{ data.id }}">
                    <span class="ast-toggle-switch"></span>
                </label>
                <span class="ast-toggle-status"><# if ( '1' == data.value ) { #>ON<# } else { #>OFF<# } #></span>
            </div>
        </label>
        <?php
    }
}
