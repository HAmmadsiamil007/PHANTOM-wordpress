<?php
use PHPUnit\Framework\TestCase;
use PhantomCore\Admin\DesignStudioPage;
use PhantomCore\Design\DesignSystemManager;

class Design_Design_Studio_Test extends TestCase {
    private DesignStudioPage $page;

    protected function setUp(): void {
        $this->page = DesignStudioPage::get_instance();
        DesignSystemManager::get_instance()->init();
    }

    public function test_singleton(): void {
        $this->assertSame($this->page, DesignStudioPage::get_instance());
    }

    public function test_render_outputs_design_studio_shell(): void {
        $this->expectOutputRegex('/(PHANTOM Design Studio|Design Studio)/');
        $this->page->render();
    }

    public function test_render_includes_toolbar(): void {
        $this->expectOutputRegex('/phantom-ds-toolbar/');
        $this->page->render();
    }

    public function test_render_includes_navigator(): void {
        $this->expectOutputRegex('/phantom-ds-navigator/');
        $this->page->render();
    }

    public function test_render_includes_inspector(): void {
        $this->expectOutputRegex('/phantom-ds-inspector/');
        $this->page->render();
    }

    public function test_render_includes_canvas_iframe(): void {
        $this->expectOutputRegex('/phantom-ds-iframe/');
        $this->page->render();
    }
}
