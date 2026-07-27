<?php
declare(strict_types=1);

use PhantomCore\Design\Preset;
use PhantomCore\Design\Providers\ImportProvider;
use PHPUnit\Framework\TestCase;

class Import_Provider_Test extends TestCase {
    private ImportProvider $provider;

    protected function setUp(): void {
        parent::setUp();
        $this->provider = new ImportProvider();
    }

    public function testSource(): void {
        $this->assertSame('import', $this->provider->source());
    }

    public function testInitiallyEmpty(): void {
        $this->assertSame([], $this->provider->get_presets());
    }

    public function testAddAndGetPreset(): void {
        $preset = Preset::from_array([
            'id' => 'import:test',
            'name' => 'Import Test',
            'tokens' => ['color_primary' => '#000'],
        ]);
        $this->provider->addPreset($preset);
        $this->assertTrue($this->provider->exists('import:test'));
        $this->assertSame($preset, $this->provider->get_preset('import:test'));
    }

    public function testGetPresetReturnsNullForMissing(): void {
        $this->assertNull($this->provider->get_preset('nonexistent'));
    }

    public function testExistsReturnsFalseForMissing(): void {
        $this->assertFalse($this->provider->exists('nonexistent'));
    }

    public function testGetPresetsReturnsAll(): void {
        $p1 = Preset::from_array(['id' => 'import:a', 'name' => 'A', 'tokens' => []]);
        $p2 = Preset::from_array(['id' => 'import:b', 'name' => 'B', 'tokens' => []]);
        $this->provider->addPreset($p1);
        $this->provider->addPreset($p2);
        $all = $this->provider->get_presets();
        $this->assertCount(2, $all);
        $this->assertArrayHasKey('import:a', $all);
        $this->assertArrayHasKey('import:b', $all);
    }

    public function testClearRemovesAll(): void {
        $preset = Preset::from_array([
            'id' => 'import:clear',
            'name' => 'Clear',
            'tokens' => [],
        ]);
        $this->provider->addPreset($preset);
        $this->assertCount(1, $this->provider->get_presets());
        $this->provider->clear();
        $this->assertCount(0, $this->provider->get_presets());
    }

    public function testMultipleAddSameIdOverwrites(): void {
        $p1 = Preset::from_array(['id' => 'import:dup', 'name' => 'First', 'tokens' => []]);
        $p2 = Preset::from_array(['id' => 'import:dup', 'name' => 'Second', 'tokens' => []]);
        $this->provider->addPreset($p1);
        $this->provider->addPreset($p2);
        $this->assertSame('Second', $this->provider->get_preset('import:dup')->name);
    }
}
