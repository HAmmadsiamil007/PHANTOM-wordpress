<?php
use PHPUnit\Framework\TestCase;
use PhantomCore\Design\ThemeDNAEngine;

class Design_Theme_DNA_Engine_Test extends TestCase {
    private ThemeDNAEngine $engine;

    protected function setUp(): void {
        $this->engine = ThemeDNAEngine::get_instance();
        $this->engine->reset();
    }

    public function test_getDimensions_returns_array(): void {
        $dims = $this->engine->getDimensions();
        $this->assertIsArray($dims);
        $this->assertArrayHasKey('design_style', $dims);
        $this->assertArrayHasKey('motion_style', $dims);
        $this->assertArrayHasKey('shape_style', $dims);
        $this->assertArrayHasKey('typography_style', $dims);
        $this->assertArrayHasKey('elevation_style', $dims);
        $this->assertArrayHasKey('color_style', $dims);
    }

    public function test_getCurrent_returns_defaults(): void {
        $current = $this->engine->getCurrent();
        $this->assertSame('classic', $current['design_style']);
        $this->assertSame('sharp', $current['shape_style']);
    }

    public function test_set_updates_dimension(): void {
        $result = $this->engine->set('design_style', 'modern');
        $this->assertTrue($result);
        $current = $this->engine->getCurrent();
        $this->assertSame('modern', $current['design_style']);
    }

    public function test_set_invalid_dimension_returns_false(): void {
        $this->assertFalse($this->engine->set('nonexistent', 'value'));
    }

    public function test_set_invalid_value_returns_false(): void {
        $this->assertFalse($this->engine->set('design_style', 'invalid_value'));
    }

    public function test_applyOverrides_updates_multiple(): void {
        $this->engine->applyOverrides([
            'design_style' => 'luxury',
            'motion_style' => 'elegant',
            'color_style' => 'vibrant',
        ]);
        $current = $this->engine->getCurrent();
        $this->assertSame('luxury', $current['design_style']);
        $this->assertSame('elegant', $current['motion_style']);
        $this->assertSame('vibrant', $current['color_style']);
    }

    public function test_reset_clears_to_defaults(): void {
        $this->engine->set('design_style', 'modern');
        $this->engine->reset();
        $current = $this->engine->getCurrent();
        $this->assertSame('classic', $current['design_style']);
    }
}
