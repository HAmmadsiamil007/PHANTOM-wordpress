<?php
use PHPUnit\Framework\TestCase;
use PhantomCore\Design\PresetRegistry;
use PhantomCore\Design\Preset;
use PhantomCore\Design\Providers\CoreProvider;
use PhantomCore\Design\Providers\UserProvider;

class Design_Preset_Registry_Test extends TestCase {
    private PresetRegistry $registry;

    protected function setUp(): void {
        $this->registry = PresetRegistry::get_instance();
        $this->registry->invalidateCache();
    }

    public function test_register_provider_and_get_all(): void {
        $this->registry->register_provider(new CoreProvider());
        $all = $this->registry->get_all();
        $this->assertNotEmpty($all);
        $this->assertArrayHasKey('core:light', $all);
    }

    public function test_get_returns_preset(): void {
        $this->registry->register_provider(new CoreProvider());
        $preset = $this->registry->get('core:light');
        $this->assertNotNull($preset);
        $this->assertSame('Light', $preset->name);
    }

    public function test_get_returns_null_for_unknown(): void {
        $this->registry->register_provider(new CoreProvider());
        $this->assertNull($this->registry->get('nonexistent'));
    }

    public function test_has_returns_correctly(): void {
        $this->registry->register_provider(new CoreProvider());
        $this->assertTrue($this->registry->has('core:light'));
        $this->assertFalse($this->registry->has('nonexistent'));
    }

    public function test_get_by_source_filters_correctly(): void {
        $this->registry->register_provider(new CoreProvider());
        $corePresets = $this->registry->get_by_source('core');
        $this->assertNotEmpty($corePresets);
        foreach ($corePresets as $p) {
            $this->assertSame('core', $p->source);
        }
    }

    public function test_user_presets_override_core(): void {
        $this->registry->register_provider(new CoreProvider());
        $userProvider = new UserProvider();
        $overridePreset = Preset::from_array([
            'id' => 'core:light',
            'name' => 'Overridden Light',
            'source' => 'user',
        ]);
        $userProvider->save($overridePreset);
        $this->registry->register_provider($userProvider);
        $this->registry->invalidateCache();

        $preset = $this->registry->get('core:light');
        $this->assertNotNull($preset);
        $this->assertSame('Overridden Light', $preset->name);
    }

    public function test_count_returns_expected(): void {
        $this->registry->register_provider(new CoreProvider());
        $this->assertGreaterThanOrEqual(7, $this->registry->count());
    }
}
