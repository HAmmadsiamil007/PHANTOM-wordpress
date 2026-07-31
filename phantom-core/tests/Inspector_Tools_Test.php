<?php
declare(strict_types=1);

use PhantomCore\Inspector\Inspector_Factory;
use PHPUnit\Framework\TestCase;

/**
 * Inspector tool filtering — metadata parts render per selected tool,
 * legacy tabs remain the fallback for components without metadata.
 */
class Inspector_Tools_Test extends TestCase {

    private Inspector_Factory $factory;

    protected function setUp(): void {
        parent::setUp();
        $this->factory = Inspector_Factory::get_instance();
    }

    public function test_hero_renders_metadata_parts_by_default(): void {
        $html = $this->factory->render_panels('hero');

        $this->assertStringNotContainsString('Component not found', $html);
        $this->assertStringContainsString('data-panel="background"', $html);
        $this->assertStringContainsString('data-panel="heading"', $html);
        $this->assertStringContainsString('data-panel="button_primary"', $html);
        $this->assertStringContainsString('data-property="hero_bg_color"', $html);
        $this->assertStringContainsString('data-property="hero_title_size"', $html);
        $this->assertStringContainsString('data-property="hero_button_radius"', $html);
    }

    public function test_colors_tool_renders_only_color_parts(): void {
        $html = $this->factory->render_panels('hero', null, 'normal', 'desktop', [], 'colors');

        $this->assertStringContainsString('data-panel="background"', $html);
        $this->assertStringContainsString('data-panel="button_primary"', $html);
        $this->assertStringContainsString('data-property="hero_bg_color"', $html);

        $this->assertStringNotContainsString('data-panel="image"', $html);
        $this->assertStringNotContainsString('data-property="hero_title_size"', $html);
        $this->assertStringNotContainsString('data-property="hero_button_radius"', $html);
    }

    public function test_typography_tool_renders_only_typography_parts(): void {
        $html = $this->factory->render_panels('hero', null, 'normal', 'desktop', [], 'typography');

        $this->assertStringContainsString('data-panel="heading"', $html);
        $this->assertStringContainsString('data-property="hero_title_size"', $html);
        $this->assertStringContainsString('data-property="hero_title_font"', $html);

        $this->assertStringNotContainsString('data-panel="background"', $html);
        $this->assertStringNotContainsString('data-property="hero_bg_color"', $html);
    }

    public function test_unknown_tool_falls_back_to_full_parts(): void {
        $all = $this->factory->render_panels('hero', null, 'normal', 'desktop', [], '');
        $gibberish = $this->factory->render_panels('hero', null, 'normal', 'desktop', [], 'gibberish');

        $this->assertSame($all, $gibberish);
    }

    public function test_spacing_tool_renders_sliders(): void {
        $html = $this->factory->render_panels('hero', null, 'normal', 'desktop', [], 'spacing');

        $this->assertStringContainsString('data-property="hero_button_padding_x"', $html);
        $this->assertStringContainsString('vc-range', $html);
        $this->assertStringNotContainsString('vc-color-picker', $html);
    }

    public function test_legacy_tabs_fallback_without_metadata(): void {
        $html = $this->factory->render_panels(
            'mystery-section',
            null,
            'normal',
            'desktop',
            ['background_color', 'heading_text']
        );

        $this->assertStringNotContainsString('Component not found', $html);
        $this->assertStringContainsString('vc-panel', $html);
        $this->assertStringContainsString('mystery-section_background_color', $html);
        $this->assertStringContainsString('data-property', $html);
    }

    public function test_metadata_controls_use_property_defaults(): void {
        $html = $this->factory->render_panels('hero', null, 'normal', 'desktop', [], 'colors');

        $this->assertStringContainsString('value="#1a1a2e"', $html);
    }

    public function test_header_metadata_parts(): void {
        $html = $this->factory->render_panels('header', null, 'normal', 'desktop', [], 'colors');

        $this->assertStringContainsString('data-panel="links"', $html);
        $this->assertStringContainsString('data-property="header_link_color"', $html);
        $this->assertStringNotContainsString('data-property="header_link_size"', $html);
    }
}
