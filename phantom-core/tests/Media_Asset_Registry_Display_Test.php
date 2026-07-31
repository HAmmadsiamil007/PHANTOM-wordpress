<?php
declare(strict_types=1);

use PhantomCore\Components\Media_Asset_Registry;
use PHPUnit\Framework\TestCase;

/**
 * Visual Assets (spec §4.2) — 10 display assets in order, defaults,
 * responsive flags, and URL fallback behavior.
 */
class Media_Asset_Registry_Display_Test extends TestCase {

    private Media_Asset_Registry $registry;

    protected function setUp(): void {
        parent::setUp();
        \PhantomCore\Settings_Registry::get_instance()->register();
        $this->registry = Media_Asset_Registry::get_instance();
        $ref = new ReflectionClass($this->registry);
        foreach (['assets', 'defaults_registered'] as $prop) {
            $property = $ref->getProperty($prop);
            $property->setAccessible(true);
            $property->setValue($this->registry, 'assets' === $prop ? [] : false);
        }
        $this->registry->register_defaults();
        $GLOBALS['_phantom_options']['phantom_assets'] = [];
    }

    public function test_display_assets_exactly_ten_in_order(): void {
        $display = $this->registry->get_display_assets();
        $this->assertSame(
            ['logo', 'mobile_logo', 'sticky_logo', 'hero_desktop', 'hero_mobile',
             'favicon', 'product_placeholder', 'blog_placeholder', 'category_banner', 'author_avatar'],
            array_keys($display)
        );
        $this->assertCount(10, $display);
    }

    public function test_display_assets_have_default_urls(): void {
        foreach (array_keys($this->registry->get_display_assets()) as $key) {
            $asset = $this->registry->get($key);
            $this->assertNotNull($asset, "Missing asset: $key");
            $this->assertStringContainsString(
                'frontend/assets/images/',
                $asset->default,
                "$key default URL missing"
            );
        }
    }

    public function test_hero_desktop_responsive_with_sizes(): void {
        $hero = $this->registry->get('hero_desktop');
        $this->assertTrue($hero->responsive);
        $this->assertSame(['full', 'large', 'medium'], $hero->sizes);
    }

    public function test_hero_mobile_and_category_banner_responsive(): void {
        $this->assertTrue($this->registry->get('hero_mobile')->responsive);
        $this->assertTrue($this->registry->get('category_banner')->responsive);
        $this->assertFalse($this->registry->get('logo')->responsive);
    }

    public function test_get_url_falls_back_to_default(): void {
        $url = $this->registry->get_url('logo');
        $this->assertStringContainsString('frontend/assets/images/logo.png', $url);
    }

    public function test_get_url_unknown_key_returns_empty(): void {
        $this->assertSame('', $this->registry->get_url('does-not-exist'));
    }

    public function test_registry_holds_extended_set(): void {
        $this->assertGreaterThanOrEqual(16, $this->registry->count());
        $this->assertTrue($this->registry->has('logo-light'));
        $this->assertTrue($this->registry->has('404-image'));
    }
}
