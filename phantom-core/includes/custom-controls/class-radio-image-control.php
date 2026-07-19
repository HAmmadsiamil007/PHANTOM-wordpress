<?php
declare(strict_types=1);

namespace PhantomCore\Customizer\Controls;

defined( 'ABSPATH' ) || exit;

class Radio_Image_Control extends Control_Base {

    public static function get_type(): string {
        return 'ast-radio-image';
    }

    public static function get_sanitize_callback(): callable {
        return 'sanitize_text_field';
    }

    public function enqueue(): void {
        $js_url = plugins_url( 'admin/js/custom-controls/ast-radio-image.js', PHANTOM_CORE_FILE );
        wp_enqueue_script( 'phantom-ast-radio-image', $js_url, array( 'customize-controls' ), PHANTOM_CORE_VERSION, true );
    }

    public function render_content(): void {
        if ( empty( $this->choices ) ) {
            return;
        }
        ?>
        <label>
            <span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
            <?php if ( $this->description ) : ?>
                <span class="description customize-control-description"><?php echo esc_html( $this->description ); ?></span>
            <?php endif; ?>
        </label>
        <div class="ast-radio-image-container">
            <?php foreach ( $this->choices as $value => $image_url ) : ?>
                <label class="ast-radio-image-item <?php echo selected( $this->value(), $value, false ) ? 'active' : ''; ?>">
                    <input type="radio" name="<?php echo esc_attr( $this->id ); ?>"
                           value="<?php echo esc_attr( $value ); ?>"
                           class="ast-radio-image-input"
                           <?php $this->link(); ?>
                           <?php checked( $this->value(), $value ); ?> />
                    <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $value ); ?>" />
                </label>
            <?php endforeach; ?>
        </div>
        <style>
        .ast-radio-image-container { display:flex; flex-wrap:wrap; gap:6px; margin:6px 0; }
        .ast-radio-image-item { cursor:pointer; border:3px solid #e0e0e0; border-radius:4px; overflow:hidden; transition:border-color 0.2s; }
        .ast-radio-image-item.active { border-color:#2271b1; }
        .ast-radio-image-item img { display:block; width:48px; height:48px; object-fit:cover; margin:0; }
        .ast-radio-image-input { display:none; }
        </style>
        <?php
    }
}
