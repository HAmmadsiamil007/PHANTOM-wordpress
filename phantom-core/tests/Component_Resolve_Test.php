<?php
declare(strict_types=1);

use PhantomCore\Design\Component_Definition;
use PhantomCore\Design\Component_Definition_Registry;
use PhantomCore\Inspector\Inspector_Factory;
use PHPUnit\Framework\TestCase;

/**
 * Component resolution (spec §3.2) — alias map + generic fallback.
 * No click ever fails: exact id → alias → runtime generic definition.
 */
class Component_Resolve_Test extends TestCase {

    private Component_Definition_Registry $registry;

    protected function setUp(): void {
        parent::setUp();
        $this->registry = Component_Definition_Registry::get_instance();
    }

    public function test_alias_data_file_exists_and_maps_frontend_names(): void {
        $aliases = $this->registry->get_aliases();
        $this->assertNotEmpty($aliases);
        $this->assertSame('products', $aliases['products-grid'] ?? null);
        $this->assertSame('products', $aliases['product-card'] ?? null);
        $this->assertSame('blog', $aliases['blog-grid'] ?? null);
        $this->assertSame('navigation', $aliases['nav-menu'] ?? null);
        $this->assertSame('hero', $aliases['hero-banner'] ?? null);
        $this->assertSame('header', $aliases['site-header'] ?? null);
    }

    public function test_resolve_exact_id_returns_definition(): void {
        $definition = $this->registry->resolve('hero');
        $this->assertInstanceOf(Component_Definition::class, $definition);
        $this->assertSame('hero', $definition->id);
    }

    public function test_resolve_alias_returns_target_definition(): void {
        $definition = $this->registry->resolve('products-grid');
        $this->assertInstanceOf(Component_Definition::class, $definition);
        $this->assertSame('products', $definition->id);

        $this->assertSame('blog', $this->registry->resolve('blog-posts')->id);
        $this->assertSame('hero', $this->registry->resolve('hero-section')->id);
        $this->assertSame('navigation', $this->registry->resolve('navbar')->id);
    }

    public function test_resolve_unknown_with_editable_builds_generic(): void {
        $definition = $this->registry->resolve('fancy-widget', ['background_color', 'heading_text']);

        $this->assertInstanceOf(Component_Definition::class, $definition);
        $this->assertSame('fancy-widget', $definition->id);
        $this->assertSame('general', $definition->category);
        $this->assertNotEmpty($definition->tabs);

        $fields = $definition->tabs[0]['fields'] ?? [];
        $keys = array_column($fields, 'key');
        $this->assertContains('fancy-widget_background_color', $keys);
        $this->assertContains('fancy-widget_heading_text', $keys);
    }

    public function test_resolve_unknown_without_editable_still_never_null(): void {
        $definition = $this->registry->resolve('mystery-element');
        $this->assertInstanceOf(Component_Definition::class, $definition);
        $this->assertSame('mystery-element', $definition->id);
        $this->assertCount(2, $definition->tabs[0]['fields'] ?? []);
    }

    public function test_generic_field_typing_by_name(): void {
        $definition = $this->registry->resolve('widget-x', ['background_color', 'font_size', 'padding_top', 'border_radius', 'title']);

        $byKey = [];
        foreach ($definition->tabs[0]['fields'] as $field) {
            $byKey[$field['key']] = $field;
        }

        $this->assertSame('color', $byKey['widget-x_background_color']['type']);
        $this->assertSame('slider', $byKey['widget-x_font_size']['type']);
        $this->assertSame('slider', $byKey['widget-x_padding_top']['type']);
        $this->assertSame('slider', $byKey['widget-x_border_radius']['type']);
        $this->assertSame('text', $byKey['widget-x_title']['type']);
    }

    public function test_inspector_renders_generic_instead_of_not_found(): void {
        $html = Inspector_Factory::get_instance()->render_panels(
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

    public function test_inspector_alias_resolution_renders_definition(): void {
        $html = Inspector_Factory::get_instance()->render_panels('products-grid');

        $this->assertStringNotContainsString('Component not found', $html);
        $this->assertStringContainsString('Products', $html);
    }
}
