<?php
declare(strict_types=1);

use PhantomCore\Design\Component_Metadata;
use PhantomCore\Design\Property_Registry;
use PHPUnit\Framework\TestCase;

/**
 * Component_Metadata — B.0 metadata engine (parts + derived tools).
 * Every component self-describes; tools are derived, never hard-coded.
 */
class Component_Metadata_Test extends TestCase {

    private Component_Metadata $metadata;

    protected function setUp(): void {
        parent::setUp();
        $this->metadata = Component_Metadata::get_instance();
    }

    public function test_known_components_have_metadata(): void {
        foreach (['hero', 'header', 'footer', 'products', 'blog', 'navigation'] as $id) {
            $this->assertTrue($this->metadata->has($id), "{$id} should have metadata");
        }
    }

    public function test_unknown_component_has_no_metadata(): void {
        $this->assertFalse($this->metadata->has('mystery-element'));
        $this->assertEmpty($this->metadata->get_parts('mystery-element'));
    }

    public function test_hero_parts_resolve_properties_and_storage_keys(): void {
        $parts = $this->metadata->get_parts('hero');

        $this->assertArrayHasKey('background', $parts);
        $this->assertArrayHasKey('heading', $parts);
        $this->assertArrayHasKey('button_primary', $parts);
        $this->assertSame('Primary Button', $parts['button_primary']['label']);

        $bg = $parts['background']['properties'][0];
        $this->assertSame('background-color', $bg['property']);
        $this->assertSame('hero_bg_color', $bg['key']);
        $this->assertSame('colors', $bg['def']['tool']);
    }

    public function test_all_parts_resolve_to_registered_properties(): void {
        $registry = Property_Registry::get_instance();
        foreach ($this->metadata->get_raw() as $id => $data) {
            foreach ($data['parts'] as $part_id => $part) {
                foreach ($part['properties'] as $entry) {
                    $prop = $entry['property'];
                    $this->assertTrue(
                        $registry->has($prop),
                        "{$id}.{$part_id} references unknown property {$prop}"
                    );
                }
            }
        }
    }

    public function test_tools_derived_from_properties_in_order(): void {
        $tools = $this->metadata->get_tools('hero');

        $this->assertSame(['colors', 'typography', 'spacing', 'assets'], array_column($tools, 'tool'));
        $this->assertSame('Colors', $tools[0]['label']);
        $this->assertTrue($tools[0]['implemented']);
        $this->assertFalse($tools[2]['implemented']);
    }

    public function test_footer_tools_exclude_assets(): void {
        $this->assertSame(['colors', 'typography', 'spacing'], array_column($this->metadata->get_tools('footer'), 'tool'));
    }

    public function test_tools_empty_for_component_without_metadata(): void {
        $this->assertEmpty($this->metadata->get_tools('mystery-element'));
    }

    public function test_get_parts_for_tool_filters_to_tool_only(): void {
        $parts = $this->metadata->get_parts_for_tool('hero', 'typography');

        $this->assertArrayHasKey('heading', $parts);
        $this->assertArrayNotHasKey('background', $parts);
        $this->assertArrayNotHasKey('image', $parts);

        foreach ($parts as $part) {
            foreach ($part['properties'] as $entry) {
                $this->assertSame('typography', $entry['def']['tool']);
            }
        }
    }

    public function test_empty_tool_returns_all_parts(): void {
        $all = $this->metadata->get_parts_for_tool('hero', '');
        $this->assertSame($this->metadata->get_parts('hero'), $all);
    }

    public function test_custom_part_label_override(): void {
        $parts = $this->metadata->get_parts('products');
        $this->assertSame('Add to Cart Button', $parts['button']['label']);
        $this->assertSame('Product Title', $parts['title']['label']);
    }

    public function test_assets_tool_exposed_via_image_properties(): void {
        $tools = $this->metadata->get_tools('hero');
        $this->assertContains('assets', array_column($tools, 'tool'));

        $products = $this->metadata->get_tools('products');
        $this->assertNotContains('assets', array_column($products, 'tool'));
    }
}
