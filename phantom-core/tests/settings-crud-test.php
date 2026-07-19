<?php

use PHPUnit\Framework\TestCase;

class Settings_CRUD_Test extends TestCase {

    private static \PhantomCore\Settings_Registry $registry;
    private static array $entries;

    public static function setUpBeforeClass(): void {
        self::$registry = \PhantomCore\Settings_Registry::get_instance();
        self::$registry->register();
        self::$entries = self::$registry->get_entries();
    }

    public function test_get_entries_returns_expected_sections(): void {
        $sections = array();
        foreach ( self::$entries as $entry ) {
            $sections[ $entry['section'] ?? '' ] = true;
        }
        $this->assertArrayHasKey( 'colors', $sections );
        $this->assertArrayHasKey( 'typography', $sections );
        $this->assertArrayHasKey( 'buttons', $sections );
        $this->assertArrayHasKey( 'header', $sections );
        $this->assertArrayHasKey( 'footer', $sections );
        $this->assertArrayHasKey( 'layout', $sections );
        $this->assertArrayHasKey( 'contact_page', $sections );
    }

    public function test_entries_have_valid_types(): void {
        foreach ( self::$entries as $key => $entry ) {
            $type = $entry['type'] ?? '';
            $this->assertNotEmpty( $type, "Entry '$key' missing type" );
        }
    }

    public function test_all_entries_have_sanitize_callback(): void {
        $no_sanitize = array( 'ast-toggle', 'ast-typography', 'font', 'bool', 'multiselect' );
        foreach ( self::$entries as $key => $entry ) {
            if ( in_array( $entry['type'] ?? '', $no_sanitize, true ) ) {
                continue;
            }
            $this->assertArrayHasKey( 'sanitize', $entry, "Entry '$key' of type '{$entry['type']}' missing sanitize callback" );
            $this->assertNotEmpty( $entry['sanitize'], "Entry '$key' has empty sanitize callback" );
        }
    }

    public function test_css_properties_are_consistent(): void {
        $map = \PhantomCore\Settings_Registry::get_css_var_map();
        foreach ( self::$entries as $key => $entry ) {
            if ( ! isset( $entry['css_property'] ) ) {
                continue;
            }
            $this->assertStringStartsWith( '--', $entry['css_property'], "Entry '$key' css_property must start with --" );
            $this->assertArrayHasKey( $key, $map, "Entry '$key' has css_property but no map entry" );
        }
    }

    public function test_responsive_entries_have_responsive_flag(): void {
        foreach ( self::$entries as $key => $entry ) {
            if ( ! empty( $entry['responsive'] ) ) {
                $type = $entry['type'] ?? '';
                $this->assertContains( $type, array( 'int', 'text' ), "Entry '$key' responsive type must be int or text" );
            }
        }
    }
}
