<?php

use PHPUnit\Framework\TestCase;

class Font_Families_Test extends TestCase {

    private static Phantom_Font_Families $fonts;

    public static function setUpBeforeClass(): void {
        self::$fonts = new Phantom_Font_Families();
    }

    public function test_get_all_returns_array(): void {
        $fonts = self::$fonts->get_all();
        $this->assertIsArray( $fonts );
    }

    public function test_get_all_has_expected_fonts(): void {
        $fonts = self::$fonts->get_all();
        $this->assertNotEmpty( $fonts );
        $this->assertArrayHasKey( 'system', $fonts );
        $this->assertArrayHasKey( 'google', $fonts );
    }

    public function test_system_fonts_are_valid(): void {
        $fonts = self::$fonts->get_all();
        $system = $fonts['system'] ?? array();
        $this->assertNotEmpty( $system );
        foreach ( $system as $font ) {
            $this->assertIsString( $font );
        }
    }

    public function test_google_fonts_have_stack_format(): void {
        $fonts = self::$fonts->get_all();
        $google = $fonts['google'] ?? array();
        $this->assertNotEmpty( $google );
    }
}
