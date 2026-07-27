<?php
use PHPUnit\Framework\TestCase;
use PhantomCore\Design\Providers\DemoProvider;

class Design_Demo_Provider_Test extends TestCase {
    private DemoProvider $provider;

    protected function setUp(): void {
        $this->provider = new DemoProvider();
    }

    public function test_source_returns_demo(): void {
        $this->assertSame('demo', $this->provider->source());
    }

    public function test_get_presets_returns_array(): void {
        $presets = $this->provider->get_presets();
        $this->assertIsArray($presets);
    }

    public function test_get_preset_returns_null_for_unknown(): void {
        $this->assertNull($this->provider->get_preset('nonexistent'));
    }

    public function test_exists_returns_false_for_unknown(): void {
        $this->assertFalse($this->provider->exists('nonexistent'));
    }
}
