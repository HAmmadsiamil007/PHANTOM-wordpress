<?php
declare(strict_types=1);

use PhantomCore\Design\Property_Registry;
use PHPUnit\Framework\TestCase;

/**
 * Property_Registry — generic property definitions keyed to tools (Phase B).
 * Properties are the unit of editing; tools know nothing about components.
 */
class Property_Registry_Test extends TestCase {

    private Property_Registry $registry;

    protected function setUp(): void {
        parent::setUp();
        $this->registry = Property_Registry::get_instance();
    }

    public function test_seven_tools_registered_in_shell_order(): void {
        $tools = $this->registry->get_tools();

        $this->assertCount(7, $tools);
        $this->assertSame(
            ['colors', 'typography', 'spacing', 'assets', 'animation', 'responsive', 'content'],
            array_keys($tools)
        );
    }

    public function test_all_tools_are_implemented(): void {
        $tools = $this->registry->get_tools();
        foreach ($tools as $tool) {
            $this->assertTrue($tool['implemented'], 'Tool ' . $tool['label'] . ' should be implemented');
        }
    }

    public function test_tool_exists(): void {
        $this->assertTrue($this->registry->tool_exists('colors'));
        $this->assertTrue($this->registry->tool_exists('typography'));
        $this->assertFalse($this->registry->tool_exists('borders'));
    }

    public function test_defaults_are_registered(): void {
        $this->assertTrue($this->registry->has('background-color'));
        $this->assertTrue($this->registry->has('font-size'));
        $this->assertTrue($this->registry->has('padding-x'));
        $this->assertFalse($this->registry->has('nope-unknown'));
    }

    public function test_property_carries_tool_and_metadata(): void {
        $def = $this->registry->get('background-color');
        $this->assertSame('colors', $def['tool']);
        $this->assertSame('color', $def['type']);
        $this->assertContains('gradient', $def['types']);

        $size = $this->registry->get('font-size');
        $this->assertSame('typography', $size['tool']);
        $this->assertSame('slider', $size['type']);
        $this->assertTrue($size['responsive']);
        $this->assertSame('px', $size['unit']);
    }

    public function test_unknown_property_returns_null(): void {
        $this->assertNull($this->registry->get('does-not-exist'));
    }

    public function test_get_for_tool_filters_by_tool(): void {
        $colors = $this->registry->get_for_tool('colors');
        $this->assertNotEmpty($colors);
        foreach ($colors as $def) {
            $this->assertSame('colors', $def['tool']);
        }
        $this->assertArrayNotHasKey('font-size', $colors);
        $this->assertArrayHasKey('background-color', $colors);
    }

    public function test_get_for_tool_returns_new_tool_properties(): void {
        $content = $this->registry->get_for_tool('content');
        $this->assertNotEmpty($content);
        $this->assertArrayHasKey('subtitle-text', $content);
        $this->assertArrayHasKey('button-text', $content);
        $this->assertArrayHasKey('button-secondary-text', $content);

        $responsive = $this->registry->get_for_tool('responsive');
        $this->assertNotEmpty($responsive);
        $this->assertArrayHasKey('hide-desktop', $responsive);
        $this->assertArrayHasKey('hide-tablet', $responsive);
        $this->assertArrayHasKey('hide-mobile', $responsive);

        foreach (array_merge($content, $responsive) as $key => $def) {
            $this->assertTrue($this->registry->tool_exists($def['tool']), "{$key} tool mismatch");
        }
    }

    public function test_new_tool_properties_are_toggle_and_textarea_types(): void {
        $this->assertSame('toggle', $this->registry->get('hide-mobile')['type']);
        $this->assertSame('textarea', $this->registry->get('subtitle-text')['type']);
        $this->assertSame('text', $this->registry->get('button-text')['type']);
    }

    public function test_custom_register_is_additive(): void {
        $this->registry->register('test-glow', [
            'tool'  => 'animation',
            'label' => 'Glow',
            'type'  => 'slider',
        ]);

        $def = $this->registry->get('test-glow');
        $this->assertSame('animation', $def['tool']);
        $this->assertSame('Glow', $def['label']);
        $this->assertSame('test-glow', $def['key']);
    }

    public function test_all_registered_properties_belong_to_known_tool(): void {
        foreach ($this->registry->get_all() as $key => $def) {
            $this->assertTrue(
                $this->registry->tool_exists($def['tool']),
                "Property {$key} has unknown tool {$def['tool']}"
            );
        }
    }
}
