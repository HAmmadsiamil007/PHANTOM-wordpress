<?php
use PHPUnit\Framework\TestCase;
use PhantomCore\Engine\Asset_Engine;
use PhantomCore\Engine\Data_Engine;
use PhantomCore\Engine\Security_Headers;
use PhantomCore\Engine\Template_Loader;

class Asset_Engine_Test extends TestCase {
    private Asset_Engine $engine;

    protected function setUp(): void {
        // Enable feature flags that gate lazy-loading and scroll-reveal
        if (class_exists('\\PhantomCore\\Feature\\Feature_Registry')) {
            $registry = \PhantomCore\Feature\Feature_Registry::get_instance();
            $registry->flush_cache();
            $registry->load();
            $registry->set_enabled('lazy_load_images', true);
            $registry->set_enabled('animate_on_scroll', true);
        }
        $data = new Data_Engine(new Template_Loader());
        $security = $this->createMock(Security_Headers::class);
        $this->engine = new Asset_Engine($data, $security);
    }

    public function test_inject_all_returns_string(): void {
        $html = '<!DOCTYPE html><html><head></head><body></body></html>';
        $result = $this->engine->inject_all($html, 'home', false);
        $this->assertIsString($result);
    }

    public function test_inject_all_preserves_doctype(): void {
        $html = '<!DOCTYPE html><html><head></head><body></body></html>';
        $result = $this->engine->inject_all($html, 'home', false);
        $this->assertStringStartsWith('<!DOCTYPE html>', trim($result));
    }

    public function test_inject_all_adds_minified_js(): void {
        $html = '<!DOCTYPE html><html><head></head><body></body></html>';
        $result = $this->engine->inject_all($html, 'home', false);
        $this->assertStringContainsString('id="phantom-bridge-js"', $result);
    }

    public function test_inject_all_adds_cdn_fallbacks(): void {
        $html = '<!DOCTYPE html><html><head></head><body></body></html>';
        $result = $this->engine->inject_all($html, 'home', false);
        $this->assertStringContainsString('cdnjs.cloudflare.com', $result);
    }

    public function test_inject_all_adds_lazy_loading(): void {
        $html = '<!DOCTYPE html><html><head></head><body></body></html>';
        $result = $this->engine->inject_all($html, 'home', false);
        $this->assertStringContainsString('id="phantom-lazy-load"', $result);
    }

    public function test_inject_all_adds_a11y_script(): void {
        $html = '<!DOCTYPE html><html><head></head><body></body></html>';
        $result = $this->engine->inject_all($html, 'home', false);
        $this->assertStringContainsString('id="phantom-a11y-js"', $result);
    }

    public function test_inject_all_adds_a11y_css(): void {
        $html = '<!DOCTYPE html><html><head></head><body></body></html>';
        $result = $this->engine->inject_all($html, 'home', false);
        $this->assertStringContainsString('id="phantom-a11y-css"', $result);
    }

    public function test_inject_all_adds_scroll_reveal(): void {
        $html = '<!DOCTYPE html><html><head></head><body></body></html>';
        $result = $this->engine->inject_all($html, 'home', false);
        $this->assertStringContainsString('id="phantom-scroll-reveal"', $result);
    }

    public function test_inject_all_adds_bridge_data(): void {
        $html = '<!DOCTYPE html><html><head></head><body></body></html>';
        $result = $this->engine->inject_all($html, 'home', false);
        $this->assertStringContainsString('id="phantom-bridge-data"', $result);
        $this->assertStringContainsString('id="phantom-bridge-js"', $result);
    }

    public function test_inject_all_adds_nonces(): void {
        $html = '<!DOCTYPE html><html><head></head><body></body></html>';
        $result = $this->engine->inject_all($html, 'home', false);
        $this->assertStringContainsString('id="phantom-nonces"', $result);
    }

    public function test_inject_all_skips_bridge_in_customizer_preview(): void {
        $html = '<!DOCTYPE html><html><head></head><body></body></html>';
        $result = $this->engine->inject_all($html, 'home', true);
        $this->assertStringNotContainsString('id="phantom-bridge-data"', $result);
    }

    public function test_inject_all_adds_blog_css_for_blog_slug(): void {
        $html = '<!DOCTYPE html><html><head></head><body></body></html>';
        $result = $this->engine->inject_all($html, 'blog', false);
        $this->assertStringContainsString('blog.css', $result);
    }

    public function test_inject_all_adds_shop_css_for_shop_slug(): void {
        $html = '<!DOCTYPE html><html><head></head><body></body></html>';
        $result = $this->engine->inject_all($html, 'shop', false);
        $this->assertStringContainsString('shop.css', $result);
        $this->assertStringContainsString('woocommerce.css', $result);
    }

    public function test_inject_all_adds_shop_css_for_product_slug(): void {
        $html = '<!DOCTYPE html><html><head></head><body></body></html>';
        $result = $this->engine->inject_all($html, 'product', false);
        $this->assertStringContainsString('shop.css', $result);
    }

    public function test_inject_all_adds_customizer_css_block(): void {
        $html = '<!DOCTYPE html><html><head></head><body></body></html>';
        $result = $this->engine->inject_all($html, 'home', false);
        $this->assertStringContainsString('</head>', $result);
    }

    public function test_inject_all_adds_plugin_hooks_output(): void {
        $html = '<!DOCTYPE html><html><head></head><body></body></html>';
        $result = $this->engine->inject_all($html, 'home', false);
        $this->assertStringContainsString('</head>', $result);
        $this->assertStringContainsString('</body>', $result);
    }

    public function test_security_headers_send_called(): void {
        $data = new Data_Engine(new Template_Loader());
        $security = $this->createMock(Security_Headers::class);
        $security->expects($this->once())->method('send')->with(true);
        $engine = new Asset_Engine($data, $security);
        $engine->inject_all('<html><head></head><body></body></html>', 'home', true);
    }
}
