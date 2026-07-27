<?php
use PHPUnit\Framework\TestCase;
use PhantomCore\Design\Preset;

class Design_Preset_Test extends TestCase {
    public function test_from_array_creates_preset(): void {
        $data = [
            'id' => 'test:preset',
            'name' => 'Test Preset',
            'source' => 'core',
            'version' => '1.0.0',
            'framework' => '>=1.5.0',
            'author' => 'Test',
            'tokens' => ['color.primary' => '#FF0000'],
            'dna' => ['design_style' => 'modern'],
            'metadata' => ['description' => 'A test preset'],
        ];
        $preset = Preset::from_array($data);
        $this->assertSame('test:preset', $preset->id);
        $this->assertSame('Test Preset', $preset->name);
        $this->assertSame('#FF0000', $preset->tokens['color.primary']);
    }

    public function test_to_array_roundtrip(): void {
        $data = [
            'id' => 'test:roundtrip',
            'name' => 'Roundtrip',
            'source' => 'user',
            'version' => '1.0.0',
            'framework' => '>=1.5.0',
            'author' => 'Test',
            'tokens' => ['space.md' => '24px'],
            'dna' => ['shape_style' => 'rounded'],
            'metadata' => [],
            'parent' => null,
        ];
        $preset = Preset::from_array($data);
        $roundtripped = $preset->to_array();
        $this->assertSame($data['id'], $roundtripped['id']);
        $this->assertSame($data['tokens'], $roundtripped['tokens']);
    }

    public function test_to_json_returns_valid_json(): void {
        $preset = Preset::from_array(['id' => 'test:json', 'name' => 'JSON Test']);
        $json = $preset->to_json();
        $this->assertJson($json);
        $decoded = json_decode($json, true);
        $this->assertSame('test:json', $decoded['id']);
    }

    public function test_merge_combines_tokens(): void {
        $parent = Preset::from_array([
            'id' => 'parent', 'name' => 'Parent',
            'tokens' => ['color.primary' => '#000', 'space.md' => '16px'],
        ]);
        $child = Preset::from_array([
            'id' => 'child', 'name' => 'Child',
            'tokens' => ['color.primary' => '#FFF'],
        ]);
        $merged = $child->merge($parent);
        $this->assertSame('#FFF', $merged->tokens['color.primary']);
        $this->assertSame('16px', $merged->tokens['space.md']);
    }

    public function test_isCompatible_checks_version(): void {
        $preset = Preset::from_array([
            'id' => 'test:compat',
            'name' => 'Compatibility',
            'framework' => '>=1.5.0',
        ]);
        $this->assertTrue($preset->isCompatible('1.5.0'));
        $this->assertTrue($preset->isCompatible('1.6.0'));
    }
}
