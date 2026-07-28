<?php
declare(strict_types=1);

use PhantomCore\Admin\Customizer_Design_Panel;
use PHPUnit\Framework\TestCase;

class Customizer_Design_Panel_Test extends TestCase {
    private Customizer_Design_Panel $panel;

    protected function setUp(): void {
        parent::setUp();
        \PhantomCore\Design\DesignSystemManager::get_instance()->init();
        $this->panel = Customizer_Design_Panel::get_instance();
    }

    public function testSingleton(): void {
        $p1 = Customizer_Design_Panel::get_instance();
        $p2 = Customizer_Design_Panel::get_instance();
        $this->assertSame($p1, $p2);
    }

    public function testRenderContainsTokens(): void {
        ob_start();
        $this->panel->render();
        $output = ob_get_clean();
        $this->assertStringContainsString('Design System', $output);
        $this->assertStringContainsString('Tokens', $output);
        $this->assertStringContainsString('Active Preset', $output);
        $this->assertStringContainsString('Available Presets', $output);
    }

    public function testRenderContainsThemeDnaTable(): void {
        ob_start();
        $this->panel->render();
        $output = ob_get_clean();
        $this->assertStringContainsString('Theme DNA Profile', $output);
        $this->assertStringContainsString('Style', $output);
        $this->assertStringContainsString('Design Style', $output);
        $this->assertStringContainsString('Motion Style', $output);
    }

    public function testRenderContainsLinkToDesignStudio(): void {
        ob_start();
        $this->panel->render();
        $output = ob_get_clean();
        $this->assertStringContainsString('Open Design Studio', $output);
        $this->assertStringContainsString('phantom-design-studio', $output);
    }
}
