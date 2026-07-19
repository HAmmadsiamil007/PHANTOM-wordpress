<?php
/**
 * Settings Registry tests — pure logic, no WordPress dependencies.
 */

use PHPUnit\Framework\TestCase;

class Settings_Registry_Test extends TestCase {

    private static array $entries;

    public static function setUpBeforeClass(): void {
        $registry = \PhantomCore\Settings_Registry::get_instance();
        $registry->register();
        self::$entries = $registry->get_entries();
    }

    public function test_entries_are_not_empty(): void {
        $this->assertNotEmpty( self::$entries );
    }

    public function test_entries_count_matches_expected(): void {
        $this->assertGreaterThanOrEqual( 555, count( self::$entries ) );
    }

    public function test_css_var_map_returns_array(): void {
        $map = \PhantomCore\Settings_Registry::get_css_var_map();
        $this->assertIsArray( $map );
    }

    public function test_css_var_map_has_expected_keys(): void {
        $map = \PhantomCore\Settings_Registry::get_css_var_map();
        $this->assertArrayHasKey( 'color_primary', $map );
        $this->assertArrayHasKey( 'container_width', $map );
        $this->assertArrayHasKey( 'typography_body_font', $map );
    }

    public function test_css_var_map_values_start_with_double_dash(): void {
        $map = \PhantomCore\Settings_Registry::get_css_var_map();
        foreach ( $map as $value ) {
            $this->assertStringStartsWith( '--', $value );
        }
    }

    public function test_css_var_map_key_exists_in_entries(): void {
        $map = \PhantomCore\Settings_Registry::get_css_var_map();
        foreach ( array_keys( $map ) as $key ) {
            $this->assertArrayHasKey( $key, self::$entries, "CSS var map key '$key' not found in entries" );
        }
    }

    public function test_px_keys_are_subset_of_entries(): void {
        $px_keys = \PhantomCore\Settings_Registry::get_px_keys();
        foreach ( $px_keys as $key ) {
            $this->assertArrayHasKey( $key, self::$entries, "px_key '$key' not found in entries" );
        }
    }

    public function test_every_entry_has_required_fields(): void {
        foreach ( self::$entries as $key => $entry ) {
            $this->assertArrayHasKey( 'section', $entry, "Entry '$key' missing 'section'" );
            $this->assertArrayHasKey( 'type', $entry, "Entry '$key' missing 'type'" );
            $this->assertArrayHasKey( 'default', $entry, "Entry '$key' missing 'default'" );
            $this->assertArrayHasKey( 'label', $entry, "Entry '$key' missing 'label'" );
        }
    }

    public function test_all_color_settings_have_valid_hex_defaults(): void {
        foreach ( self::$entries as $key => $entry ) {
            if ( ! isset( $entry['section'] ) || 'colors' !== $entry['section'] ) {
                continue;
            }
            if ( 'ast-color' !== ( $entry['type'] ?? '' ) ) {
                continue;
            }
            $default = $entry['default'] ?? '';
            $this->assertMatchesRegularExpression( '/^#[0-9a-fA-F]{6}$/', $default, "Color '$key' default '$default' is not valid hex" );
        }
    }

    public function test_all_color_css_properties_match_map(): void {
        $map = \PhantomCore\Settings_Registry::get_css_var_map();
        foreach ( self::$entries as $key => $entry ) {
            if ( ! isset( $entry['css_property'] ) ) {
                continue;
            }
            $map_val = $map[ $key ] ?? null;
            if ( null !== $map_val ) {
                $this->assertEquals(
                    $map_val,
                    $entry['css_property'],
                    "Entry '$key' css_property '{$entry['css_property']}' does not match map value '$map_val'"
                );
            }
        }
    }
}
