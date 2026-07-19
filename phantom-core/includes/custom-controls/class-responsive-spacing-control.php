<?php
declare(strict_types=1);

namespace PhantomCore\Customizer\Controls;

defined( 'ABSPATH' ) || exit;

class Responsive_Spacing_Control extends Control_Base {

    public static function get_type(): string {
        return 'ast-responsive-spacing';
    }

    public static function get_sanitize_callback(): callable {
        return function ( $value ) {
            if ( ! is_array( $value ) ) {
                return array( 'desktop' => array( 'top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0 ) );
            }
            $default_dir = array( 'top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0 );
            $result      = array();
            foreach ( array( 'desktop', 'tablet', 'mobile' ) as $device ) {
                $result[ $device ] = isset( $value[ $device ] ) && is_array( $value[ $device ] )
                    ? array_merge( $default_dir, array_map( 'floatval', $value[ $device ] ) )
                    : $default_dir;
            }
            return $result;
        };
    }

    public function enqueue(): void {
        $js_url = plugins_url( 'admin/js/custom-controls/ast-responsive-spacing.js', PHANTOM_CORE_FILE );
        wp_enqueue_script( 'phantom-ast-responsive-spacing', $js_url, array( 'customize-controls' ), PHANTOM_CORE_VERSION, true );
    }

    public function render_content(): void {
        $val = $this->value();
        $val = is_array( $val ) ? $val : array( 'desktop' => array( 'top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0 ) );
        $directions = array( 'top' => 'Top', 'right' => 'Right', 'bottom' => 'Bottom', 'left' => 'Left' );
        $devices    = array( 'desktop' => 'Desktop', 'tablet' => 'Tablet', 'mobile' => 'Mobile' );
        $unit = $this->input_attrs['unit'] ?? 'px';
        ?>
        <label>
            <span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
            <?php if ( $this->description ) : ?>
                <span class="description customize-control-description"><?php echo esc_html( $this->description ); ?></span>
            <?php endif; ?>
        </label>
        <div class="ast-responsive-spacing-container">
            <div class="ast-device-tabs">
                <?php foreach ( $devices as $key => $name ) : ?>
                    <button type="button" class="ast-device-tab <?php echo 'desktop' === $key ? 'active' : ''; ?>" data-device="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $name ); ?></button>
                <?php endforeach; ?>
                <button type="button" class="ast-spacing-linked" title="Link values">🔗</button>
            </div>
            <?php foreach ( $devices as $key => $name ) : ?>
                <div class="ast-spacing-device" data-device="<?php echo esc_attr( $key ); ?>" <?php echo 'desktop' !== $key ? 'style="display:none"' : ''; ?>>
                    <?php foreach ( $directions as $dir_key => $dir_name ) : ?>
                        <label class="ast-spacing-direction">
                            <span><?php echo esc_html( $dir_name ); ?></span>
                            <input type="number" class="ast-spacing-input" data-device="<?php echo esc_attr( $key ); ?>" data-direction="<?php echo esc_attr( $dir_key ); ?>"
                                   value="<?php echo esc_attr( (string) ( $val[ $key ][ $dir_key ] ?? 0 ) ); ?>" min="0" step="1" />
                            <span class="ast-spacing-unit"><?php echo esc_html( $unit ); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
            <input type="hidden" class="ast-responsive-spacing-value" <?php $this->link(); ?> />
        </div>
        <style>
        .ast-spacing-device { display:flex; gap:4px; margin:6px 0; }
        .ast-spacing-direction { display:flex; flex-direction:column; align-items:center; flex:1; }
        .ast-spacing-direction span { font-size:9px; text-transform:uppercase; color:#888; }
        .ast-spacing-input { width:100%; text-align:center; }
        .ast-spacing-unit { font-size:10px; color:#666; }
        .ast-spacing-linked { margin-left:auto; cursor:pointer; padding:2px 8px; font-size:14px; background:none; border:1px solid #ddd; border-radius:3px; }
        .ast-spacing-linked.linked { opacity:0.5; }
        </style>
        <?php
    }
}
