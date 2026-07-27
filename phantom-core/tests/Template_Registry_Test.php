<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PhantomCore\Registry\Template;
use PhantomCore\Registry\Template_Registry;

class Template_Registry_Test extends TestCase {
    private Template_Registry $registry;

    protected function setUp(): void {
        $this->registry = Template_Registry::get_instance();
    }

    public function test_registry_is_singleton(): void {
        $a = Template_Registry::get_instance();
        $b = Template_Registry::get_instance();
        $this->assertSame($a, $b);
    }

    public function test_register_defaults_creates_routes(): void {
        $this->registry->register_defaults();
        $this->assertGreaterThan(20, $this->registry->count());
    }

    public function test_has_returns_true_for_registered_route(): void {
        $this->registry->register_defaults();
        $this->assertTrue($this->registry->has('shop'));
        $this->assertTrue($this->registry->has('about'));
        $this->assertTrue($this->registry->has('blog'));
    }

    public function test_has_returns_false_for_unknown_route(): void {
        $this->registry->register_defaults();
        $this->assertFalse($this->registry->has('nonexistent_page'));
    }

    public function test_get_returns_template_object(): void {
        $this->registry->register_defaults();
        $template = $this->registry->get('shop');
        $this->assertInstanceOf(Template::class, $template);
        $this->assertSame('shop', $template->slug);
        $this->assertSame('shop.html', $template->file);
    }

    public function test_resolve_returns_correct_file(): void {
        $this->registry->register_defaults();
        $this->assertSame('shop.html', $this->registry->resolve('shop'));
        $this->assertSame('index.html', $this->registry->resolve(''));
        $this->assertSame('index.html', $this->registry->resolve('index'));
        $this->assertSame('404.html', $this->registry->resolve('nonexistent'));
    }

    public function test_get_all_filters_by_category(): void {
        $this->registry->register_defaults();
        $shop = $this->registry->get_all('shop');
        foreach ($shop as $template) {
            $this->assertSame('shop', $template->category);
        }
    }

    public function test_register_pattern_adds_dynamic_route(): void {
        $this->registry->register_pattern('/^custom\/(.+)$/', 'custom.html');
        $this->assertTrue($this->registry->has('custom/test-slug'));
        $this->assertSame('custom.html', $this->registry->resolve('custom/test-slug'));
    }

    public function test_deregister_removes_route(): void {
        $this->registry->register('temp-route', 'temp.html', 'Temp', 'pages');
        $this->assertTrue($this->registry->has('temp-route'));
        $this->registry->deregister('temp-route');
        $this->assertFalse($this->registry->has('temp-route'));
    }

    public function test_template_is_404_property(): void {
        $t = new Template('not-found', '404.html', '404', 'error', 'kids', true);
        $this->assertTrue($t->is_404);
        $t2 = new Template('home', 'index.html', 'Home', 'pages', 'kids', false);
        $this->assertFalse($t2->is_404);
    }

    public function test_get_patterns_returns_array(): void {
        $this->registry->register_defaults();
        $patterns = $this->registry->get_patterns();
        $this->assertIsArray($patterns);
        $this->assertGreaterThan(0, count($patterns));
        foreach ($patterns as $p) {
            $this->assertArrayHasKey('pattern', $p);
            $this->assertArrayHasKey('template', $p);
        }
    }

    public function test_get_supported_templates_returns_files(): void {
        $this->registry->register_defaults();
        $files = $this->registry->get_supported_templates();
        $this->assertContains('index.html', $files);
        $this->assertContains('404.html', $files);
    }
}
