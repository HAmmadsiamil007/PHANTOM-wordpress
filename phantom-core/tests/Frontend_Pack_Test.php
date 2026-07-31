<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PhantomCore\Packs\Frontend_Pack;

class Frontend_Pack_Test extends TestCase {
    private array $manifest;

    protected function setUp(): void {
        $this->manifest = [
            'name' => 'Dark',
            'version' => '1.0.0',
            'description' => 'Dark-themed premium template pack',
            'author' => 'Phantom Core',
            'settings' => [
                'primary_color' => '#6C63FF',
                'dark_mode' => true,
                'font_heading' => 'Inter',
                'font_body' => 'Inter',
            ],
            'templates' => [
                'override_count' => 12,
                'base' => 'frontend/packs/dark/html/',
            ],
            'assets' => [
                'css' => ['frontend/packs/dark/assets/css/pack.css'],
                'js' => ['frontend/packs/dark/assets/js/pack.js'],
            ],
        ];
    }

    public function test_from_manifest_maps_all_fields(): void {
        $pack = Frontend_Pack::from_manifest($this->manifest, 'dark', '/packs/dark', true);
        $this->assertSame('dark', $pack->slug);
        $this->assertSame('Dark', $pack->name);
        $this->assertSame('1.0.0', $pack->version);
        $this->assertSame('Dark-themed premium template pack', $pack->description);
        $this->assertSame('Phantom Core', $pack->author);
        $this->assertSame('#6C63FF', $pack->settings['primary_color']);
        $this->assertTrue($pack->settings['dark_mode']);
        $this->assertSame(12, $pack->templates['override_count']);
        $this->assertCount(1, $pack->assets['css']);
        $this->assertSame('/packs/dark', $pack->path);
        $this->assertTrue($pack->builtin);
        $this->assertFalse($pack->active);
    }

    public function test_from_manifest_defaults(): void {
        $pack = Frontend_Pack::from_manifest([], 'my-cool-pack', '/tmp/x', false);
        $this->assertSame('My Cool Pack', $pack->name);
        $this->assertSame('', $pack->version);
        $this->assertSame('', $pack->description);
        $this->assertSame('', $pack->author);
        $this->assertSame([], $pack->settings);
        $this->assertSame([], $pack->templates);
        $this->assertSame([], $pack->assets);
        $this->assertFalse($pack->builtin);
        $this->assertFalse($pack->active);
    }

    public function test_to_manifest_preserves_original_shape(): void {
        $pack = Frontend_Pack::from_manifest($this->manifest, 'dark', '/packs/dark', true);
        $manifest = $pack->to_manifest();
        $this->assertSame(
            ['name', 'version', 'description', 'author', 'settings', 'templates', 'assets'],
            array_keys($manifest)
        );
        $this->assertSame($this->manifest['name'], $manifest['name']);
        $this->assertSame($this->manifest['settings'], $manifest['settings']);
        $this->assertSame($this->manifest['assets'], $manifest['assets']);
    }

    public function test_to_array_roundtrip(): void {
        $pack = Frontend_Pack::from_manifest($this->manifest, 'dark', '/packs/dark', true);
        $pack->active = true;
        $data = $pack->to_array();
        $this->assertSame('dark', $data['slug']);
        $this->assertSame('Dark', $data['name']);
        $this->assertSame('/packs/dark', $data['path']);
        $this->assertTrue($data['builtin']);
        $this->assertTrue($data['active']);
        $this->assertSame('#6C63FF', $data['settings']['primary_color']);
    }

    public function test_get_css_urls_uses_full_relative_path_without_double_prefix(): void {
        $pack = Frontend_Pack::from_manifest($this->manifest, 'dark', '/packs/dark', true);
        $urls = $pack->get_css_urls();
        $this->assertCount(1, $urls);
        $expected = 'http://example.com/wp-content/plugins/phantom-core/frontend/packs/dark/assets/css/pack.css';
        $this->assertSame($expected, $urls[0]);
        $this->assertStringNotContainsString('frontend/packs/frontend/packs', $urls[0]);
    }

    public function test_get_js_urls(): void {
        $pack = Frontend_Pack::from_manifest($this->manifest, 'dark', '/packs/dark', true);
        $urls = $pack->get_js_urls();
        $this->assertCount(1, $urls);
        $this->assertSame(
            'http://example.com/wp-content/plugins/phantom-core/frontend/packs/dark/assets/js/pack.js',
            $urls[0]
        );
    }

    public function test_empty_assets_returns_empty_lists(): void {
        $manifest = $this->manifest;
        unset($manifest['assets']);
        $pack = Frontend_Pack::from_manifest($manifest, 'dark', '/packs/dark', true);
        $this->assertSame([], $pack->get_css_urls());
        $this->assertSame([], $pack->get_js_urls());
    }

    public function test_get_asset_urls_ignore_non_string_entries(): void {
        $manifest = $this->manifest;
        $manifest['assets'] = ['css' => ['a.css', 42, null], 'js' => ['b.js']];
        $pack = Frontend_Pack::from_manifest($manifest, 'dark', '/packs/dark', true);
        $css = $pack->get_css_urls();
        $this->assertCount(1, $css);
        $this->assertStringEndsWith('a.css', $css[0]);
    }
}
