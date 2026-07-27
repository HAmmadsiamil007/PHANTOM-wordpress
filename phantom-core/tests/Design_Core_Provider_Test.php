<?php
use PHPUnit\Framework\TestCase;
use PhantomCore\Design\Providers\CoreProvider;

class Design_Core_Provider_Test extends TestCase {
    private CoreProvider $provider;

    protected function setUp(): void {
        $this->provider = new CoreProvider();
    }

    public function test_source_returns_core(): void {
        $this->assertSame('core', $this->provider->source());
    }

    public function test_get_presets_returns_array(): void {
        $presets = $this->provider->get_presets();
        $this->assertIsArray($presets);
        $this->assertGreaterThanOrEqual(7, count($presets));
    }

    public function test_get_preset_returns_known_preset(): void {
        $preset = $this->provider->get_preset('core:light');
        $this->assertNotNull($preset);
        $this->assertSame('Light', $preset->name);
    }

    public function test_get_preset_returns_null_for_unknown(): void {
        $this->assertNull($this->provider->get_preset('nonexistent'));
    }

    public function test_exists_returns_true_for_known(): void {
        $this->assertTrue($this->provider->exists('core:dark'));
    }

    public function test_exists_returns_false_for_unknown(): void {
        $this->assertFalse($this->provider->exists('core:unknown'));
    }

    public function test_all_foundation_presets_present(): void {
        $presets = $this->provider->get_presets();
        $expected = ['core:light', 'core:dark', 'core:minimal', 'core:modern', 'core:luxury', 'core:classic', 'core:glass'];
        foreach ($expected as $id) {
            $this->assertArrayHasKey($id, $presets, "Missing preset: $id");
        }
    }
}
