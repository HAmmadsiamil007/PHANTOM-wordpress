<?php
declare(strict_types=1);

namespace PhantomCore\Customizer\Controls;

defined( 'ABSPATH' ) || exit;

class Typography_Control extends Control_Base {

    public static function get_type(): string {
        return 'ast-typography';
    }

    public static function get_sanitize_callback(): callable {
        return function ( $value ) {
            $default = array(
                'family'         => '',
                'weight'         => '400',
                'style'          => 'normal',
                'transform'      => 'none',
                'line_height'    => '1.5',
                'letter_spacing' => '0',
                'size'           => array( 'desktop' => 16, 'tablet' => 16, 'mobile' => 16 ),
            );
            if ( ! is_array( $value ) ) {
                return $default;
            }
            $value['family']         = sanitize_text_field( $value['family'] ?? '' );
            $value['weight']         = sanitize_text_field( $value['weight'] ?? '400' );
            $value['style']          = sanitize_text_field( $value['style'] ?? 'normal' );
            $value['transform']      = sanitize_text_field( $value['transform'] ?? 'none' );
            $value['line_height']    = sanitize_text_field( $value['line_height'] ?? '1.5' );
            $value['letter_spacing'] = sanitize_text_field( $value['letter_spacing'] ?? '0' );
            if ( isset( $value['size'] ) && is_array( $value['size'] ) ) {
                foreach ( array( 'desktop', 'tablet', 'mobile' ) as $d ) {
                    $value['size'][ $d ] = isset( $value['size'][ $d ] ) ? floatval( $value['size'][ $d ] ) : 16;
                }
            } else {
                $value['size'] = array( 'desktop' => 16, 'tablet' => 16, 'mobile' => 16 );
            }
            return $value;
        };
    }

    public function enqueue(): void {
        $js_url = plugins_url( 'admin/js/custom-controls/ast-typography.js', PHANTOM_CORE_FILE );
        wp_enqueue_script( 'phantom-ast-typography', $js_url, array( 'customize-controls' ), PHANTOM_CORE_VERSION, true );
        wp_localize_script( 'phantom-ast-typography', 'PhantomFonts', Font_Families::get_all() );
    }

    public function render_content(): void {
        $val = $this->value();
        $val = is_array( $val ) ? $val : array(
            'family' => '', 'weight' => '400', 'style' => 'normal', 'transform' => 'none',
            'size' => array( 'desktop' => 16, 'tablet' => 16, 'mobile' => 16 ),
            'line_height' => '1.5', 'letter_spacing' => '0',
        );
        $weights    = array( '100', '200', '300', '400', '500', '600', '700', '800', '900' );
        $styles     = array( 'normal' => 'Normal', 'italic' => 'Italic' );
        $transforms = array( 'none' => 'None', 'uppercase' => 'UPPERCASE', 'lowercase' => 'lowercase', 'capitalize' => 'Capitalize' );
        $devices    = array( 'desktop' => 'Desktop', 'tablet' => 'Tablet', 'mobile' => 'Mobile' );
        ?>
        <label>
            <span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
            <?php if ( $this->description ) : ?>
                <span class="description customize-control-description"><?php echo esc_html( $this->description ); ?></span>
            <?php endif; ?>
        </label>
        <div class="ast-typography-container">
            <div class="ast-typography-row">
                <select class="ast-typography-family">
                    <option value=""><?php esc_html_e( 'Default', 'phantom-core' ); ?></option>
                    <optgroup label="System Fonts">
                        <?php foreach ( Font_Families::get_system_fonts() as $name => $stack ) : ?>
                            <option value="<?php echo esc_attr( $name ); ?>" <?php selected( $val['family'], $name ); ?>><?php echo esc_html( $name ); ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                    <optgroup label="Google Fonts">
                        <?php foreach ( Font_Families::get_google_fonts() as $name => $data ) : ?>
                            <option value="<?php echo esc_attr( $name ); ?>" <?php selected( $val['family'], $name ); ?>><?php echo esc_html( $name ); ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                </select>
            </div>
            <div class="ast-typography-row" style="display:flex;gap:4px;">
                <select class="ast-typography-weight" style="flex:1;">
                    <?php foreach ( $weights as $w ) : ?>
                        <option value="<?php echo esc_attr( $w ); ?>" <?php selected( $val['weight'], $w ); ?>><?php echo esc_html( $w ); ?></option>
                    <?php endforeach; ?>
                </select>
                <select class="ast-typography-style" style="flex:1;">
                    <?php foreach ( $styles as $k => $v ) : ?>
                        <option value="<?php echo esc_attr( $k ); ?>" <?php selected( $val['style'], $k ); ?>><?php echo esc_html( $v ); ?></option>
                    <?php endforeach; ?>
                </select>
                <select class="ast-typography-transform" style="flex:1;">
                    <?php foreach ( $transforms as $k => $v ) : ?>
                        <option value="<?php echo esc_attr( $k ); ?>" <?php selected( $val['transform'], $k ); ?>><?php echo esc_html( $v ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="ast-typography-row">
                <div class="ast-device-tabs">
                    <?php foreach ( $devices as $key => $name ) : ?>
                        <button type="button" class="ast-device-tab <?php echo 'desktop' === $key ? 'active' : ''; ?>" data-device="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $name ); ?></button>
                    <?php endforeach; ?>
                </div>
                <?php foreach ( $devices as $key => $name ) : ?>
                    <div class="ast-typography-size-device" data-device="<?php echo esc_attr( $key ); ?>" <?php echo 'desktop' !== $key ? 'style="display:none"' : ''; ?>>
                        <input type="number" class="ast-typography-size" data-device="<?php echo esc_attr( $key ); ?>"
                               value="<?php echo esc_attr( (string) ( $val['size'][ $key ] ?? 16 ) ); ?>" min="0" step="1" /> px
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="ast-typography-row" style="display:flex;gap:8px;">
                <label style="flex:1;"><?php esc_html_e( 'Line Height', 'phantom-core' ); ?> <input type="text" class="ast-typography-line-height" value="<?php echo esc_attr( $val['line_height'] ); ?>" /></label>
                <label style="flex:1;"><?php esc_html_e( 'Letter Spacing', 'phantom-core' ); ?> <input type="text" class="ast-typography-letter-spacing" value="<?php echo esc_attr( $val['letter_spacing'] ); ?>" /> px</label>
            </div>
            <input type="hidden" class="ast-typography-value" <?php $this->link(); ?> />
        </div>
        <?php
    }
}
