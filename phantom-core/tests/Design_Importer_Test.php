<?php
declare(strict_types=1);

use PhantomCore\Design\DesignImporter;
use PhantomCore\Design\PresetRegistry;
use PHPUnit\Framework\TestCase;

class Design_Importer_Test extends TestCase {
    private DesignImporter $importer;

    protected function setUp(): void {
        parent::setUp();
        PresetRegistry::get_instance()->register_provider(
            new PhantomCore\Design\Providers\CoreProvider()
        );
        $this->importer = new DesignImporter();
    }

    protected function tearDown(): void {
        parent::tearDown();
        delete_option('phantom_active_preset');
        delete_option('phantom_theme_dna');
    }

    public function testImportValidJson(): void {
        $json = json_encode([
            'id' => 'user:test-import-1',
            'name' => 'Test Import',
            'tokens' => [
                'color_primary' => '#ff0000',
                'color_secondary' => '#00ff00',
            ],
            'dna' => [
                'style' => 'modern',
                'complexity' => 'moderate',
                'spacing' => 'comfortable',
                'formality' => 'casual',
                'color_saturation' => 'vibrant',
                'motion' => 'moderate',
            ],
        ]);
        $result = $this->importer->import($json);
        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('imported', $result['message']);
        $this->assertSame('user:test-import-1', $result['preset_id']);
    }

    public function testImportInvalidJson(): void {
        $result = $this->importer->import('not json');
        $this->assertFalse($result['success']);
        $this->assertSame('Invalid JSON', $result['message']);
    }

    public function testImportMissingRequiredFields(): void {
        $result = $this->importer->import(json_encode(['name' => 'no id']));
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Missing required', $result['message']);
    }

    public function testImportMissingTokens(): void {
        $result = $this->importer->import(json_encode(['id' => 'test']));
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Missing required', $result['message']);
    }

    public function testImportFrameworkIncompatible(): void {
        $result = $this->importer->import(json_encode([
            'id' => 'test-incompat',
            'tokens' => ['color_primary' => '#000'],
            'framework' => '>=999.0.0',
        ]));
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('requires framework', $result['message']);
    }

    public function testImportPersistsPreset(): void {
        $json = json_encode([
            'id' => 'user:persist-test',
            'name' => 'Persist Test',
            'tokens' => ['color_primary' => '#0000ff'],
            'dna' => [
                'style' => 'modern',
                'complexity' => 'moderate',
                'spacing' => 'comfortable',
                'formality' => 'casual',
                'color_saturation' => 'vibrant',
                'motion' => 'moderate',
            ],
        ]);
        $result = $this->importer->import($json);
        $this->assertTrue($result['success']);
        $preset = PresetRegistry::get_instance()->get('user:persist-test');
        $this->assertNotNull($preset);
        $this->assertSame('Persist Test', $preset->name);
    }
}
