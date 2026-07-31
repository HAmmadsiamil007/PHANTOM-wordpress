<?php
declare(strict_types=1);

namespace PhantomCore\Customizer\Controls;

defined( 'ABSPATH' ) || exit;

class Array_Textarea_Control extends Control_Base {

    public $type = 'ast-array-textarea';

    public static function get_type(): string {
        return 'ast-array-textarea';
    }

    public static function get_sanitize_callback(): callable {
        return static function ( $value ) {
            if ( is_array( $value ) ) {
                return $value;
            }
            $lines = preg_split( '/[\r\n]+/', (string) $value, -1, PREG_SPLIT_NO_EMPTY );
            return array_values( array_filter( array_map( 'trim', $lines ) ) );
        };
    }

    public function get_string_value(): string {
        $value = parent::value();
        if ( is_array( $value ) ) {
            return implode( "\n", array_map( 'strval', $value ) );
        }
        return (string) $value;
    }

    public function to_json(): void {
        parent::to_json();
        $this->json['value'] = $this->get_string_value();
    }

    public function render_content(): void {
        ?>
        <label>
            <span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
            <?php if ( $this->description ) : ?>
                <span class="description customize-control-description"><?php echo esc_html( $this->description ); ?></span>
            <?php endif; ?>
            <textarea
                id="_customize-input-<?php echo esc_attr( $this->id ); ?>"
                rows="<?php echo absint( $this->input_attrs['rows'] ?? 5 ); ?>"
                <?php $this->link(); ?>
            ><?php echo esc_textarea( $this->get_string_value() ); ?></textarea>
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
            <textarea
                id="_customize-input-{{ data.id }}"
                rows="<# if ( data.input_attrs && data.input_attrs.rows ) { #>{{ data.input_attrs.rows }}<# } else { #>5<# } #>"
                data-customize-setting-link="{{ data.settings.default }}"
            >{{ data.value }}</textarea>
        </label>
        <?php
    }
}
