<?php
use PHPUnit\Framework\TestCase;
use PhantomCore\Demo\Demo_Registry;
use PhantomCore\Demo\Demo_Loader;
use PhantomCore\Engine\Template_Loader;

class Demo_Loader_Test extends TestCase {
    private Demo_Loader $loader;

    protected function setUp(): void {
        $registry = new Demo_Registry();
        $this->loader = new Demo_Loader(new Template_Loader(), $registry);
    }

    public function test_get_active_template_path_returns_string(): void {
        $path = $this->loader->get_active_template_path();
        $this->assertIsString($path);
        $this->assertNotEmpty($path);
    }

    public function test_get_active_asset_url_returns_string(): void {
        $url = $this->loader->get_active_asset_url();
        $this->assertIsString($url);
        $this->assertNotEmpty($url);
    }

    public function test_get_screenshot_url_returns_null_for_nonexistent(): void {
        $url = $this->loader->get_screenshot_url('nonexistent-demo-xyz');
        $this->assertNull($url);
    }

    public function test_get_screenshot_url_for_fashion_returns_null(): void {
        $url = $this->loader->get_screenshot_url('fashion');
        $this->assertNull($url);
    }

    public function test_get_active_css_files_returns_array(): void {
        $files = $this->loader->get_active_css_files();
        $this->assertIsArray($files);
    }

    public function test_get_active_js_files_returns_array(): void {
        $files = $this->loader->get_active_js_files();
        $this->assertIsArray($files);
    }

    public function test_has_template_returns_false_for_nonexistent(): void {
        $result = $this->loader->has_template('nonexistent-file-xyz.html');
        $this->assertFalse($result);
    }
}
