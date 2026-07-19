<?php
declare(strict_types=1);

namespace PhantomCore\Customizer\Controls;

defined( 'ABSPATH' ) || exit;

class Responsive_Slider_Control extends Control_Base {

    public static function get_type(): string {
        return 'ast-responsive-slider';
    }

    public static function get_sanitize_callback(): callable {
        return function ( $value ) {
            if ( ! is_array( $value ) ) {
                return array( 'desktop' => 50, 'tablet' => 50, 'mobile' => 50 );
            }
            return array(
                'desktop' => isset( $value['desktop'] ) ? floatval( $value['desktop'] ) : 50,
                'tablet'  => isset( $value['tablet'] ) ? floatval( $value['tablet'] ) : 50,
                'mobile'  => isset( $value['mobile'] ) ? floatval( $value['mobile'] ) : 50,
            );
        };
    }

    public function enqueue(): void {
        $js_url = plugins_url( 'admin/js/custom-controls/ast-responsive-slider.js', PHANTOM_CORE_FILE );
        wp_enqueue_script( 'phantom-ast-responsive-slider', $js_url, array( 'customize-controls' ), PHANTOM_CORE_VERSION, true );
    }

    public function render_content(): void {
        $val  = $this->value();
        $val  = is_array( $val ) ? $val : array( 'desktop' => 50, 'tablet' => 50, 'mobile' => 50 );
        $min  = $this->input_attrs['min'] ?? 0;
        $max  = $this->input_attrs['max'] ?? 100;
        $step = $this->input_attrs['step'] ?? 1;
        $unit = $this->input_attrs['unit'] ?? 'px';
        $devices = array( 'desktop' => 'Desktop', 'tablet' => 'Tablet', 'mobile' => 'Mobile' );
        ?>
        <label>
            <span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
            <?php if ( $this->description ) : ?>
                <span class="description customize-control-description"><?php echo esc_html( $this->description ); ?></span>
            <?php endif; ?>
        </label>
        <div class="ast-responsive-slider-container">
            <div class="ast-device-tabs">
                <?php foreach ( $devices as $key => $name ) : ?>
                    <button type="button" class="ast-device-tab <?php echo 'desktop' === $key ? 'active' : ''; ?>" data-device="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $name ); ?></button>
                <?php endforeach; ?>
            </div>
            <?php foreach ( $devices as $key => $name ) : ?>
                <div class="ast-slider-device" data-device="<?php echo esc_attr( $key ); ?>" <?php echo 'desktop' !== $key ? 'style="display:none"' : ''; ?>>
                    <input type="range" class="ast-responsive-slider" data-device="<?php echo esc_attr( $key ); ?>"
                           min="<?php echo esc_attr( $min ); ?>" max="<?php echo esc_attr( $max ); ?>" step="<?php echo esc_attr( $step ); ?>"
                           value="<?php echo esc_attr( $val[ $key ] ); ?>" />
                    <input type="number" class="ast-responsive-slider-input" data-device="<?php echo esc_attr( $key ); ?>"
                           value="<?php echo esc_attr( $val[ $key ] ); ?>" min="<?php echo esc_attr( $min ); ?>" max="<?php echo esc_attr( $max ); ?>"
                           step="<?php echo esc_attr( $step ); ?>" />
                    <span class="ast-slider-unit"><?php echo esc_html( $unit ); ?></span>
                </div>
            <?php endforeach; ?>
            <input type="hidden" class="ast-responsive-slider-value" <?php $this->link(); ?> />
        </div>
        <style>
        .ast-device-tabs { display:flex; gap:2px; margin-bottom:8px; }
        .ast-device-tab { padding:4px 12px; border:1px solid #ddd; background:#f0f0f1; cursor:pointer; font-size:11px; border-radius:3px; }
        .ast-device-tab.active { background:#2271b1; color:#fff; border-color:#2271b1; }
        .ast-slider-device { display:flex; align-items:center; gap:8px; margin:4px 0; }
        .ast-responsive-slider { flex:1; }
        .ast-responsive-slider-input { width:60px; }
        .ast-slider-unit { font-size:11px; color:#666; min-width:20px; }
        </style>
        <?php
    }
}
