<?php
declare(strict_types=1);

use PhantomCore\Design\DesignExporter;
use PhantomCore\Design\PresetRegistry;
use PhantomCore\Design\TokenRegistry;
use PhantomCore\Design\ThemeDNAEngine;
use PhantomCore\Design\Providers\CoreProvider;
use PHPUnit\Framework\TestCase;

class Design_Exporter_Test extends TestCase {
    private DesignExporter $exporter;

    protected function setUp(): void {
        parent::setUp();
        // Ensure function exists in test context
        if (!function_exists('wp_json_encode')) {
            function wp_json_encode($data, $options = 0, $depth = 512) {
                return json_encode($data, $options, $depth);
            }
        }
        TokenRegistry::get_instance()->load();
        PresetRegistry::get_instance()->register_provider(
            new CoreProvider()
        );
        update_option('phantom_theme_dna', [
            'design_style' => 'modern',
            'motion_style' => 'dynamic',
            'shape_style' => 'rounded',
            'typography_style' => 'sans',
            'elevation_style' => 'soft',
            'color_style' => 'vibrant',
        ]);
        $this->exporter = new DesignExporter();
    }

    protected function tearDown(): void {
        parent::tearDown();
        delete_option('phantom_theme_dna');
        delete_option('phantom_active_preset');
    }

    public function testExportCurrentReturnsJson(): void {
        $json = $this->exporter->exportCurrent();
        $this->assertJson($json);
        $data = json_decode($json, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('tokens', $data);
        $this->assertArrayHasKey('dna', $data);
        $this->assertArrayHasKey('metadata', $data);
        $this->assertSame('user', $data['source']);
        $this->assertArrayHasKey('exported_at', $data['metadata']);
    }

    public function testExportCurrentTokensAreResolved(): void {
        $json = $this->exporter->exportCurrent();
        $data = json_decode($json, true);
        $tokens = $data['tokens'];
        $this->assertNotEmpty($tokens);
        foreach ($tokens as $name => $value) {
            $this->assertIsString($name);
            $this->assertNotNull($value);
        }
    }

    public function testExportCurrentIncludesDnaDimensions(): void {
        $json = $this->exporter->exportCurrent();
        $data = json_decode($json, true);
        $dna = $data['dna'];
        $this->assertArrayHasKey('design_style', $dna);
        $this->assertArrayHasKey('motion_style', $dna);
        $this->assertArrayHasKey('shape_style', $dna);
        $this->assertArrayHasKey('typography_style', $dna);
        $this->assertArrayHasKey('elevation_style', $dna);
        $this->assertArrayHasKey('color_style', $dna);
        $this->assertSame('modern', $dna['design_style']);
    }

    public function testExportPresetReturnsJsonForKnownPreset(): void {
        $json = $this->exporter->exportPreset('core:light');
        $this->assertJson($json);
        $data = json_decode($json, true);
        $this->assertArrayHasKey('id', $data);
        $this->assertSame('core:light', $data['id']);
    }

    public function testExportPresetReturnsEmptyForUnknown(): void {
        $json = $this->exporter->exportPreset('non_existent_preset');
        $this->assertSame('{}', $json);
    }

    public function testExportCurrentHasFrameworkConstraint(): void {
        $json = $this->exporter->exportCurrent();
        $data = json_decode($json, true);
        $this->assertSame(PHANTOM_CORE_VERSION, $data['metadata']['exported_version']);
    }

    public function testExportCurrentIdFormat(): void {
        $json = $this->exporter->exportCurrent();
        $data = json_decode($json, true);
        $this->assertStringStartsWith('user:exported-', $data['id']);
    }

    public function testExportGetTokensValueType(): void {
        $json = $this->exporter->exportCurrent();
        $data = json_decode($json, true);
        foreach ($data['tokens'] as $value) {
            $this->assertTrue(is_string($value) || is_numeric($value) || is_bool($value) || is_array($value));
        }
    }
}
