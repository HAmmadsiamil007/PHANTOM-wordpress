<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PhantomCore\Components\ComponentInstance;
use PhantomCore\Components\Media_Asset_Registry;
use PhantomCore\Components\MediaAsset;
use PhantomCore\Inspector\Inspector_Factory;

class Inspector_Assets_Test extends TestCase {
    private Inspector_Factory $factory;

    protected function setUp(): void {
        $this->factory = Inspector_Factory::get_instance();
    }

    private function clear_asset_registry(): void {
        $registry = Media_Asset_Registry::get_instance();
        $ref = new ReflectionClass($registry);
        foreach (['assets', 'defaults_registered'] as $prop) {
            $property = $ref->getProperty($prop);
            $property->setAccessible(true);
            $property->setValue($registry, 'assets' === $prop ? [] : true);
        }
    }

    public function test_no_assets_panel_when_registry_empty(): void {
        $this->clear_asset_registry();

        $output = $this->factory->render_panels('hero');

        $this->assertStringNotContainsString('vc-panel-assets', $output);
        $this->assertStringNotContainsString('vc-asset-row', $output);
        $this->assertStringNotContainsString('vc-btn-upload', $output);
    }

    public function test_assets_panel_rendered_when_assets_registered(): void {
        $registry = Media_Asset_Registry::get_instance();
        $registry->register(new MediaAsset('t7-logo', 'Test Logo', 'image', 'http://default/test-logo.svg'));
        $registry->register(new MediaAsset('t7-hero', 'Test Hero', 'image', 'http://default/test-hero.jpg'));

        $output = $this->factory->render_panels('hero');

        $this->assertStringContainsString('class="vc-panel vc-panel-assets"', $output);
        $this->assertStringContainsString('data-panel="assets"', $output);
        $this->assertStringContainsString('vc-panel-title">Assets', $output);
        $this->assertStringContainsString('vc-panel-body', $output);
        $this->assertStringContainsString('vc-asset-row', $output);
        $this->assertStringContainsString('vc-btn-upload', $output);
        $this->assertStringContainsString('vc-btn-reset', $output);
        $this->assertStringContainsString('data-asset="t7-logo"', $output);
        $this->assertStringContainsString('data-asset="t7-hero"', $output);
    }

    public function test_assets_panel_renders_after_component_tabs(): void {
        $registry = Media_Asset_Registry::get_instance();
        $registry->register(new MediaAsset('t7-logo', 'Test Logo', 'image', 'http://default/test-logo.svg'));

        $output = $this->factory->render_panels('hero');

        $first_panel = strpos($output, 'data-panel="');
        $assets_panel = strpos($output, 'vc-panel-assets');
        $this->assertNotFalse($first_panel);
        $this->assertNotFalse($assets_panel);
        $this->assertLessThan($assets_panel, $first_panel);
    }

    public function test_asset_row_contains_buttons_inside_row(): void {
        $registry = Media_Asset_Registry::get_instance();
        $registry->register(new MediaAsset('t7-hero', 'Test Hero', 'image', 'http://default/test-hero.jpg'));

        $output = $this->factory->render_panels('hero');

        $start = strpos($output, '<div class="vc-asset-row" data-asset="t7-hero">');
        $this->assertNotFalse($start);
        $end = strpos($output, '</div></div>', $start);
        $block = substr($output, $start, $end - $start);

        $this->assertStringContainsString('vc-asset-preview', $block);
        $this->assertStringContainsString('vc-btn-upload', $block);
        $this->assertStringContainsString('vc-btn-reset', $block);
        $this->assertSame(3, substr_count($block, 'data-asset="t7-hero"'));
    }

    public function test_asset_preview_uses_default_url(): void {
        $registry = Media_Asset_Registry::get_instance();
        $registry->register(new MediaAsset('t7-banner', 'Banner', 'image', 'http://default/banner.jpg'));

        $output = $this->factory->render_panels('hero');

        $this->assertStringContainsString('class="vc-asset-preview" src="http://default/banner.jpg"', $output);
        $this->assertStringContainsString('data-default="http://default/banner.jpg"', $output);
    }

    public function test_asset_preview_span_when_no_url(): void {
        $registry = Media_Asset_Registry::get_instance();
        $registry->register(new MediaAsset('t7-empty', 'Empty', 'image', ''));

        $output = $this->factory->render_panels('hero');

        $this->assertStringContainsString('<span class="vc-asset-preview">Default</span>', $output);
    }

    public function test_non_image_assets_excluded(): void {
        $registry = Media_Asset_Registry::get_instance();
        $registry->register(new MediaAsset('t7-video', 'Video', 'video', 'http://default/v.mp4'));

        $output = $this->factory->render_panels('hero');

        $this->assertStringNotContainsString('data-asset="t7-video"', $output);
    }

    public function test_assets_panel_with_instance(): void {
        $registry = Media_Asset_Registry::get_instance();
        $registry->register(new MediaAsset('t7-logo', 'Test Logo', 'image', 'http://default/test-logo.svg'));
        $instance = new ComponentInstance('hero-test', 'hero');

        $output = $this->factory->render_panels('hero', $instance);

        $this->assertStringContainsString('vc-panel-assets', $output);
        $this->assertStringContainsString('data-asset="t7-logo"', $output);
    }
}
