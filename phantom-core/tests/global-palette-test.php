<?php

use PHPUnit\Framework\TestCase;

class Global_Palette_Test extends TestCase {

    private static Phantom_Global_Palette $palette;

    public static function setUpBeforeClass(): void {
        self::$palette = Phantom_Global_Palette::instance();
    }

    public function test_get_default_presets_returns_array(): void {
        $presets = self::$palette->get_default_presets();
        $this->assertIsArray( $presets );
    }

    public function test_has_minimum_presets(): void {
        $presets = self::$palette->get_default_presets();
        $this->assertGreaterThanOrEqual( 3, count( $presets ) );
    }

    public function test_each_preset_has_nine_colors(): void {
        $presets = self::$palette->get_default_presets();
        foreach ( $presets as $name => $preset ) {
            $this->assertArrayHasKey( 'colors', $preset, "Preset '$name' missing 'colors' key" );
            $this->assertCount( 9, $preset['colors'], "Preset '$name' does not have 9 colors" );
        }
    }

    public function test_default_colors_are_valid_hex(): void {
        $presets = self::$palette->get_default_presets();
        $colors = $presets['light']['colors'] ?? array();
        $this->assertNotEmpty( $colors );
        foreach ( $colors as $label => $hex ) {
            $this->assertNotEmpty( $hex, "Color '$label' is empty" );
        }
    }
}
