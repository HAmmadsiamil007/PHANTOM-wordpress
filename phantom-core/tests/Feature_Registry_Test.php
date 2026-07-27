<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PhantomCore\Feature\Feature;
use PhantomCore\Feature\Feature_Registry;

class Feature_Registry_Test extends TestCase {
    private Feature_Registry $registry;

    protected function setUp(): void {
        $this->registry = Feature_Registry::get_instance();
        $this->registry->flush_cache();
        $this->registry->load();
    }

    public function test_registry_is_singleton(): void {
        $a = Feature_Registry::get_instance();
        $b = Feature_Registry::get_instance();
        $this->assertSame($a, $b);
    }

    public function test_load_creates_features(): void {
        $this->assertGreaterThan(0, $this->registry->count());
    }

    public function test_has_returns_true_for_registered_feature(): void {
        $this->assertTrue($this->registry->has('animate_on_scroll'));
    }

    public function test_has_returns_false_for_unknown_feature(): void {
        $this->assertFalse($this->registry->has('nonexistent_feature'));
    }

    public function test_get_returns_feature_object(): void {
        $feature = $this->registry->get('animate_on_scroll');
        $this->assertInstanceOf(Feature::class, $feature);
        $this->assertSame('animate_on_scroll', $feature->id);
    }

    public function test_get_returns_null_for_unknown(): void {
        $this->assertNull($this->registry->get('unknown'));
    }

    public function test_get_all_returns_all_features(): void {
        $all = $this->registry->get_all();
        $this->assertIsArray($all);
        $this->assertGreaterThan(20, count($all));
    }

    public function test_get_by_category_filters_correctly(): void {
        $motion = $this->registry->get_by_category('motion');
        $this->assertNotEmpty($motion, 'Expected at least one feature in motion category');
        foreach ($motion as $feature) {
            $this->assertSame('motion', $feature->category);
        }
    }

    public function test_get_categories_returns_unique_categories(): void {
        $categories = $this->registry->get_categories();
        $this->assertContains('motion', $categories);
        $this->assertContains('shop', $categories);
        $this->assertContains('performance', $categories);
    }

    public function test_enabled_returns_bool(): void {
        $result = $this->registry->enabled('animations');
        $this->assertIsBool($result);
    }

    public function test_enabled_returns_false_for_unknown(): void {
        $this->assertFalse($this->registry->enabled('nonexistent'));
    }

    public function test_set_enabled_persists_to_option(): void {
        $test_id = 'wishlist';
        $original = $this->registry->enabled($test_id);
        $this->registry->set_enabled($test_id, !$original);
        $this->assertNotEquals($original, $this->registry->enabled($test_id));
        $this->registry->set_enabled($test_id, $original); // restore
    }

    public function test_register_adds_new_feature(): void {
        $count_before = $this->registry->count();
        $feature = new Feature('test_feature', 'testing', 'ast-toggle', 'Test Feature', 'A test feature', true);
        $this->registry->register($feature);
        $this->assertTrue($this->registry->has('test_feature'));
        $this->assertEquals($count_before + 1, $this->registry->count());
    }

    public function test_deregister_removes_feature(): void {
        $feature = new Feature('temp_feature', 'testing', 'ast-toggle', 'Temp Feature', 'Temporary', false);
        $this->registry->register($feature);
        $this->assertTrue($this->registry->has('temp_feature'));
        $this->registry->deregister('temp_feature');
        $this->assertFalse($this->registry->has('temp_feature'));
    }

    public function test_reset_clears_override(): void {
        $test_id = 'animate_on_scroll';
        $this->registry->set_enabled($test_id, false);
        $this->registry->reset($test_id);
        $feature = $this->registry->get($test_id);
        $this->assertNotNull($feature);
    }
}
