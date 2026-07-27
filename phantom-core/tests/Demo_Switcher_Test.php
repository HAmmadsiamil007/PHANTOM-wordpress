<?php
use PHPUnit\Framework\TestCase;
use PhantomCore\Demo\Demo_Registry;
use PhantomCore\Demo\Demo_Switcher;

class Demo_Switcher_Test extends TestCase {
    private Demo_Switcher $switcher;

    protected function setUp(): void {
        phantom_ensure_fashion_fixture();
        clearstatcache();
        $this->switcher = new Demo_Switcher(new Demo_Registry());
    }

    public function test_get_active_slug_returns_string(): void {
        $slug = $this->switcher->get_active_slug();
        $this->assertIsString($slug);
    }

    public function test_activate_nonexistent_demo_returns_failure(): void {
        $result = $this->switcher->activate('nonexistent-demo-xyz');
        $this->assertFalse($result->success);
        $this->assertNotEmpty($result->message);
    }

    public function test_activate_fashion_returns_success(): void {
        $result = $this->switcher->activate('fashion');
        $this->assertTrue($result->success);
        $this->assertArrayHasKey('slug', $result->data);
        $this->assertSame('fashion', $result->data['slug']);
    }

    public function test_can_activate_fashion_returns_pass(): void {
        $check = $this->switcher->can_activate('fashion');
        $this->assertIsArray($check);
        $this->assertArrayHasKey('pass', $check);
        $this->assertArrayHasKey('checks', $check);
    }

    public function test_can_activate_nonexistent_returns_fail(): void {
        $check = $this->switcher->can_activate('nonexistent-demo-xyz');
        $this->assertFalse($check['pass']);
        $this->assertNotEmpty($check['checks']);
    }

    public function test_deactivate_returns_success(): void {
        $result = $this->switcher->deactivate();
        $this->assertTrue($result->success);
    }

    public function test_activate_sets_active_slug(): void {
        $this->switcher->activate('fashion');
        $this->assertSame('fashion', $this->switcher->get_active_slug());
    }
}
