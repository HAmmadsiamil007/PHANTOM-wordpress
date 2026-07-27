<?php
use PHPUnit\Framework\TestCase;
use PhantomCore\Engine\View_Engine;
use PhantomCore\Engine\SEO_Engine;

class View_Engine_Test extends TestCase {
    private View_Engine $engine;

    protected function setUp(): void {
        $this->engine = new View_Engine(new SEO_Engine());
    }

    public function test_inject_skip_link_added_to_body(): void {
        $html = '<!DOCTYPE html><html><head></head><body><main></main></body></html>';
        $result = $this->engine->inject_all($html, 'home');
        $this->assertStringContainsString('Skip to main content', $result);
        $this->assertStringContainsString('class="skip-link screen-reader-text"', $result);
        $this->assertStringContainsString('href="#phantom-main-content"', $result);
    }

    public function test_inject_loading_state_added(): void {
        $html = '<!DOCTYPE html><html><head></head><body><main></main></body></html>';
        $result = $this->engine->inject_all($html, 'home');
        $this->assertStringContainsString('id="phantom-loading"', $result);
        $this->assertStringContainsString('role="status"', $result);
        $this->assertStringContainsString('phantom-spin', $result);
    }

    public function test_inject_aria_roles_added(): void {
        $html = '<!DOCTYPE html><html><head></head><body><header>Head</header><main>Content</main><footer>Foot</footer></body></html>';
        $result = $this->engine->inject_all($html, 'home');
        $this->assertStringContainsString('<header role="banner"', $result);
        $this->assertStringContainsString('<main role="main"', $result);
        $this->assertStringContainsString('<footer role="contentinfo"', $result);
    }

    public function test_inject_loading_css_added(): void {
        $html = '<!DOCTYPE html><html><head></head><body></body></html>';
        $result = $this->engine->inject_all($html, 'home');
        $this->assertStringContainsString('id="phantom-loading-css"', $result);
        $this->assertStringContainsString('@keyframes phantom-spin', $result);
    }

    public function test_inject_all_returns_string(): void {
        $html = '<!DOCTYPE html><html><head></head><body><main></main></body></html>';
        $result = $this->engine->inject_all($html, 'home');
        $this->assertIsString($result);
    }

    public function test_inject_all_preserves_doctype(): void {
        $html = '<!DOCTYPE html><html><head></head><body><main></main></body></html>';
        $result = $this->engine->inject_all($html, 'home');
        $this->assertStringStartsWith('<!DOCTYPE html>', trim($result));
    }

    public function test_inject_all_adds_title_tag(): void {
        $html = '<!DOCTYPE html><html><head><title>Test</title></head><body></body></html>';
        $result = $this->engine->inject_all($html, 'home');
        $this->assertStringContainsString('<title>', $result);
    }

    public function test_inject_all_adds_meta_tags(): void {
        $html = '<!DOCTYPE html><html><head><title>Test</title></head><body></body></html>';
        $result = $this->engine->inject_all($html, 'home');
        $this->assertStringContainsString('<meta', $result);
    }

    public function test_inject_all_passes_product_id_to_seo(): void {
        $html = '<!DOCTYPE html><html><head><title>Test</title></head><body></body></html>';
        $result = $this->engine->inject_all($html, 'product', 42);
        $this->assertStringContainsString('Phantom Test', $result);
    }
}
