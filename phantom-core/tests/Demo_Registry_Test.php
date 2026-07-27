<?php
use PHPUnit\Framework\TestCase;
use PhantomCore\Demo\Demo_Registry;

class Demo_Registry_Test extends TestCase {
    private Demo_Registry $registry;

    protected function setUp(): void {
        phantom_ensure_fashion_fixture();
        clearstatcache();
        $this->registry = new Demo_Registry();
    }

    public function test_get_all_returns_installed_demos(): void {
        $demos = $this->registry->get_all();
        $this->assertIsArray($demos);
    }

    public function test_get_returns_demo_for_existing_slug(): void {
        $demo = $this->registry->get('fashion');
        $this->assertNotNull($demo);
        $this->assertSame('Fashion Store', $demo->name);
    }

    public function test_get_returns_null_for_nonexistent_slug(): void {
        $demo = $this->registry->get('nonexistent-demo-xyz');
        $this->assertNull($demo);
    }

    public function test_has_returns_true_for_existing_demo(): void {
        $this->assertTrue($this->registry->has('fashion'));
    }

    public function test_has_returns_false_for_nonexistent_demo(): void {
        $this->assertFalse($this->registry->has('nonexistent-demo-xyz'));
    }

    public function test_get_active_returns_demo_object(): void {
        $active = $this->registry->get_active();
        $this->assertInstanceOf(
            \PhantomCore\Demo\Demo_Contract::class,
            $active
        );
        $this->assertNotEmpty($active->name);
    }

    public function test_refresh_rebuilds_cache(): void {
        $before = $this->registry->count();
        $this->registry->refresh();
        $after = $this->registry->count();
        $this->assertSame($before, $after);
    }

    public function test_count_returns_non_negative_integer(): void {
        $count = $this->registry->count();
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }
}
