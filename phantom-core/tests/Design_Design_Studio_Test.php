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

    public function test_render_presets_tab(): void {
        $_GET['tab'] = 'presets';
        $this->expectOutputRegex('/(Design Studio|Design Presets)/');
        $this->page->render();
    }

    public function test_render_dna_tab(): void {
        $_GET['tab'] = 'dna';
        $this->expectOutputRegex('/Theme DNA/');
        $this->page->render();
    }

    public function test_render_colors_tab(): void {
        $_GET['tab'] = 'colors';
        $this->expectOutputRegex('/Colors/');
        $this->page->render();
    }

    public function test_render_tokens_tab(): void {
        $_GET['tab'] = 'tokens';
        $this->expectOutputRegex('/All Design Tokens/');
        $this->page->render();
    }

    public function test_render_css_tab(): void {
        $_GET['tab'] = 'css';
        $this->expectOutputRegex('/CSS Preview/');
        $this->page->render();
    }
}
