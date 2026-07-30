<?php
declare(strict_types=1);

namespace PhantomCore\Customizer\Controls;

use WP_Customize_Manager;

defined( 'ABSPATH' ) || exit;

abstract class Control_Base extends \WP_Customize_Control {

    abstract public static function get_type(): string;

    abstract public static function get_sanitize_callback(): callable;

    public static function register_all( WP_Customize_Manager $wp_customize ): void {
        $base = __DIR__ . '/';
        $controls = array(
            'class-toggle-control.php',
            'class-select-control.php',
            'class-radio-image-control.php',
            'class-color-control.php',
            'class-gradient-control.php',
            'class-responsive-slider-control.php',
            'class-responsive-spacing-control.php',
            'class-typography-control.php',
            'class-color-group-control.php',
            'class-background-control.php',
            'class-border-control.php',
            'class-preset-card-control.php',
        );
        foreach ( $controls as $file ) {
            $path = $base . $file;
            if ( file_exists( $path ) ) {
                require_once $path;
            }
        }
        foreach ( array_values( self::$type_class_map ) as $class ) {
            if ( class_exists( $class ) ) {
                $wp_customize->register_control_type( $class );
            }
        }
    }

    private static array $type_class_map = array(
        'ast-color'             => Color_Control::class,
        'ast-toggle'            => Toggle_Control::class,
        'ast-radio-image'       => Radio_Image_Control::class,
        'ast-responsive-slider' => Responsive_Slider_Control::class,
        'ast-responsive-spacing'=> Responsive_Spacing_Control::class,
        'ast-typography'        => Typography_Control::class,
        'ast-gradient'          => Gradient_Control::class,
        'ast-select'            => Select_Control::class,
        'ast-color-group'       => Color_Group_Control::class,
        'ast-background'        => Background_Control::class,
        'ast-border'            => Border_Control::class,
        'ast-preset-card'       => Preset_Card_Control::class,
    );

    public static function get_class_for_type( string $type ): ?string {
        return self::$type_class_map[ $type ] ?? null;
    }

    public static function get_sanitize_for_type( string $type ): ?callable {
        $class = self::get_class_for_type( $type );
        if ( $class && class_exists( $class ) && is_subclass_of( $class, self::class ) ) {
            return $class::get_sanitize_callback();
        }
        return null;
    }

    public static function get_control_instance( \WP_Customize_Manager $wp_customize, string $type, string $id, array $args = array() ): ?\WP_Customize_Control {
        $class = self::get_class_for_type( $type );
        if ( $class && class_exists( $class ) ) {
            return new $class( $wp_customize, $id, $args );
        }
        return null;
    }

    public function to_json(): void {
        parent::to_json();
        $this->json['value'] = $this->value();
        if ( ! empty( $this->choices ) ) {
            $this->json['choices'] = $this->choices;
        }
    }

    public function add_control( \WP_Customize_Manager $wp_customize, string $setting_key, array $args ): void {
        $type     = $args['type'] ?? $this->type;
        $instance = self::get_control_instance( $wp_customize, $type, $setting_key, $args );
        if ( $instance ) {
            $wp_customize->add_control( $instance );
        }
    }
}
