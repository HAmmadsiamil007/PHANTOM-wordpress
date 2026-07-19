<?php
declare(strict_types=1);

namespace PhantomCore\Customizer\Controls;

defined( 'ABSPATH' ) || exit;

class Gradient_Control extends Control_Base {

    public static function get_type(): string {
        return 'ast-gradient';
    }

    public static function get_sanitize_callback(): callable {
        return function ( $value ) {
            if ( ! is_array( $value ) ) {
                return array( 'color1' => '#000000', 'color2' => '#ffffff', 'angle' => 0 );
            }
            $sanitize_color = function ( $c ) {
                if ( preg_match( '/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/', $c ) ) {
                    return $c;
                }
                return '#000000';
            };
            return array(
                'color1' => $sanitize_color( $value['color1'] ?? '#000000' ),
                'color2' => $sanitize_color( $value['color2'] ?? '#ffffff' ),
                'angle'  => floatval( $value['angle'] ?? 0 ),
            );
        };
    }

    public function enqueue(): void {
        $js_url = plugins_url( 'admin/js/custom-controls/ast-gradient.js', PHANTOM_CORE_FILE );
        wp_enqueue_script( 'phantom-ast-gradient', $js_url, array( 'customize-controls', 'wp-color-picker' ), PHANTOM_CORE_VERSION, true );
        wp_enqueue_style( 'wp-color-picker' );
    }

    public function render_content(): void {
        $val = $this->value();
        $val = is_array( $val ) ? $val : array( 'color1' => '#000000', 'color2' => '#ffffff', 'angle' => 0 );
        ?>
        <label>
            <span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
            <?php if ( $this->description ) : ?>
                <span class="description customize-control-description"><?php echo esc_html( $this->description ); ?></span>
            <?php endif; ?>
        </label>
        <div class="ast-gradient-container">
            <div class="ast-gradient-preview" style="background: linear-gradient(<?php echo esc_attr( $val['angle'] ); ?>deg, <?php echo esc_attr( $val['color1'] ); ?>, <?php echo esc_attr( $val['color2'] ); ?>); height:40px; border-radius:4px; margin-bottom:8px;"></div>
            <div class="ast-gradient-colors">
                <label>Color 1 <input type="text" class="ast-gradient-color-1" value="<?php echo esc_attr( $val['color1'] ); ?>" data-alpha="true" /></label>
                <label>Color 2 <input type="text" class="ast-gradient-color-2" value="<?php echo esc_attr( $val['color2'] ); ?>" data-alpha="true" /></label>
            </div>
            <div class="ast-gradient-angle-row">
                <label>Angle <input type="range" class="ast-gradient-angle" min="0" max="360" value="<?php echo esc_attr( $val['angle'] ); ?>" /> <span class="ast-gradient-angle-value"><?php echo esc_html( $val['angle'] ); ?>°</span></label>
            </div>
            <input type="hidden" class="ast-gradient-value" <?php $this->link(); ?> />
        </div>
        <style>
        .ast-gradient-colors { display:flex; gap:8px; margin:6px 0; }
        .ast-gradient-colors label { flex:1; font-size:11px; color:#666; }
        .ast-gradient-colors input { width:100%; }
        .ast-gradient-angle-row label { display:flex; align-items:center; gap:8px; font-size:11px; color:#666; }
        .ast-gradient-angle { flex:1; }
        </style>
        <?php
    }
}
