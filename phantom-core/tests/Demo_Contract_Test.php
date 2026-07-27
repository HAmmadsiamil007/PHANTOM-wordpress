<?php
use PHPUnit\Framework\TestCase;
use PhantomCore\Demo\Demo_Contract;

class Demo_Contract_Test extends TestCase {

    public function test_from_array_parses_valid_data(): void {
        $data = [
            'name' => 'Test Demo',
            'slug' => 'test',
            'version' => '1.0.0',
            'description' => 'A test demo',
            'author' => 'Phantom',
            'templates' => ['index', 'shop'],
            'tags' => ['test'],
        ];
        $demo = Demo_Contract::from_array($data, 'test');

        $this->assertSame('Test Demo', $demo->name);
        $this->assertSame('test', $demo->slug);
        $this->assertSame('1.0.0', $demo->version);
        $this->assertSame('A test demo', $demo->description);
        $this->assertSame('Phantom', $demo->author);
        $this->assertSame(['index', 'shop'], $demo->templates);
        $this->assertSame(['test'], $demo->tags);
        $this->assertTrue($demo->is_compatible);
        $this->assertEmpty($demo->errors);
    }

    public function test_from_array_missing_name_adds_error(): void {
        $data = ['version' => '1.0.0'];
        $demo = Demo_Contract::from_array($data, 'no-name');

        $this->assertNotEmpty($demo->errors);
        $this->assertStringContainsString('name', $demo->errors[0]);
    }

    public function test_from_array_missing_version_adds_error(): void {
        $data = ['name' => 'No Version'];
        $demo = Demo_Contract::from_array($data, 'no-version');

        $this->assertNotEmpty($demo->errors);
        $this->assertStringContainsString('version', $demo->errors[0]);
    }

    public function test_from_array_defaults_for_optional_fields(): void {
        $demo = Demo_Contract::from_array(['name' => 'Minimal', 'version' => '1.0'], 'minimal');

        $this->assertSame('Minimal', $demo->name);
        $this->assertSame('minimal', $demo->slug);
        $this->assertSame('1.0', $demo->version);
        $this->assertSame('', $demo->description);
        $this->assertSame('', $demo->author);
        $this->assertSame([], $demo->templates);
        $this->assertSame([], $demo->tags);
    }

    public function test_check_compatibility_passes_for_compatible_requirements(): void {
        $demo = Demo_Contract::from_array([
            'name' => 'Compatible',
            'version' => '1.0',
            'requires' => [
                'php' => '7.4',
                'wordpress' => '5.0',
                'phantom_core' => '1.0.0',
            ],
        ], 'compat');

        $this->assertTrue($demo->is_compatible);
        $this->assertEmpty($demo->errors);
    }

    public function test_check_compatibility_fails_for_incompatible_php(): void {
        $demo = Demo_Contract::from_array([
            'name' => 'Old PHP Required',
            'version' => '1.0',
            'requires' => ['php' => '99.0'],
        ], 'old-php');

        $this->assertFalse($demo->is_compatible);
        $this->assertNotEmpty($demo->errors);
    }

    public function test_validate_templates_returns_missing(): void {
        $demo = Demo_Contract::from_array([
            'name' => 'Template Check',
            'version' => '1.0',
            'templates' => ['non-existent-template-xyz'],
        ], 'template-check');

        $missing = $demo->validate_templates();
        $this->assertNotEmpty($missing);
        $this->assertContains('non-existent-template-xyz', $missing);
    }

    public function test_from_array_parses_preset_field(): void {
        $demo = Demo_Contract::from_array([
            'name' => 'With Preset',
            'version' => '1.0',
            'preset' => 'modern-business',
        ], 'with-preset');
        $this->assertSame('modern-business', $demo->preset);
    }

    public function test_from_array_default_preset_is_empty(): void {
        $demo = Demo_Contract::from_array([
            'name' => 'No Preset',
            'version' => '1.0',
        ], 'no-preset');
        $this->assertSame('', $demo->preset);
    }

    public function test_has_screenshot_false_when_no_file(): void {
        $demo = Demo_Contract::from_array([
            'name' => 'No Screenshot',
            'version' => '1.0',
        ], 'no-screenshot-demo');

        $this->assertFalse($demo->has_screenshot);
    }
}
