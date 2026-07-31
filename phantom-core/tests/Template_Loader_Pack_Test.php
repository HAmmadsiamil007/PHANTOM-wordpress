<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PhantomCore\Engine\Template_Loader;
use PhantomCore\Packs\Frontend_Pack_Registry;

class Template_Loader_Pack_Test extends TestCase {
    private Template_Loader $loader;

    protected function setUp(): void {
        Frontend_Pack_Registry::get_instance()->scan();
        $this->loader = new Template_Loader();
    }

    public function test_pack_exists_for_bundled_packs(): void {
        $this->assertTrue($this->loader->pack_exists('dark'));
        $this->assertTrue($this->loader->pack_exists('minimal'));
        $this->assertTrue($this->loader->pack_exists('bold'));
    }

    public function test_pack_exists_false_for_default_and_unknown(): void {
        $this->assertFalse($this->loader->pack_exists('default'));
        $this->assertFalse($this->loader->pack_exists('ghost-pack'));
    }

    public function test_get_pack_manifest_returns_manifest_array(): void {
        $manifest = $this->loader->get_pack_manifest('dark');
        $this->assertIsArray($manifest);
        $this->assertSame('Dark', $manifest['name']);
        $this->assertSame('1.0.0', $manifest['version']);
        $this->assertSame('#6C63FF', $manifest['settings']['primary_color']);
        $this->assertSame(['frontend/packs/dark/assets/css/pack.css'], $manifest['assets']['css']);
    }

    public function test_get_pack_manifest_default_returns_null(): void {
        $this->assertNull($this->loader->get_pack_manifest('default'));
        $this->assertNull($this->loader->get_pack_manifest('ghost-pack'));
    }

    public function test_get_pack_manifest_uses_property_pack(): void {
        $this->loader->set_pack('dark');
        $manifest = $this->loader->get_pack_manifest();
        $this->assertSame('Dark', $manifest['name']);
    }

    public function test_get_packs_shape_preserved(): void {
        $packs = $this->loader->get_packs();
        $this->assertSame('Default', $packs['default']);
        $this->assertSame('Dark', $packs['dark']);
        $this->assertSame('Minimal', $packs['minimal']);
        $this->assertSame('Bold', $packs['bold']);
    }

    public function test_get_pack_asset_urls_resolves_correctly(): void {
        $urls = $this->loader->get_pack_asset_urls('dark');
        $this->assertCount(1, $urls['css']);
        $this->assertCount(1, $urls['js']);
        $this->assertSame(
            'http://example.com/wp-content/plugins/phantom-core/frontend/packs/dark/assets/css/pack.css',
            $urls['css'][0]
        );
        $this->assertStringNotContainsString('frontend/packs/frontend/packs', $urls['css'][0]);
    }

    public function test_get_pack_asset_urls_unknown_returns_empty(): void {
        $this->assertSame(['css' => [], 'js' => []], $this->loader->get_pack_asset_urls('ghost-pack'));
        $this->assertSame(['css' => [], 'js' => []], $this->loader->get_pack_asset_urls('default'));
    }

    public function test_resolve_path_uses_pack_overrides(): void {
        $this->loader->set_pack('dark');
        $path = $this->loader->resolve_path('shop.html');
        $this->assertStringContainsString('frontend/packs/dark/html/shop.html', str_replace('\\', '/', $path));
    }

    public function test_resolve_path_homepage_always_uses_default(): void {
        $this->loader->set_pack('dark');
        $path = $this->loader->resolve_path('index.html');
        $this->assertStringContainsString('frontend/html/index.html', str_replace('\\', '/', $path));
    }

    public function test_resolve_path_falls_back_to_default_template(): void {
        $this->loader->set_pack('dark');
        $path = $this->loader->resolve_path('does-not-exist.html');
        $this->assertStringContainsString('frontend/html/', str_replace('\\', '/', $path));
    }
}
