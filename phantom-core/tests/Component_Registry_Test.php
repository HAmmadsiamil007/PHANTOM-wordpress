<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PhantomCore\Components\Component;
use PhantomCore\Components\Component_Registry;

class Component_Registry_Test extends TestCase {
    private Component_Registry $registry;

    protected function setUp(): void {
        $this->registry = Component_Registry::get_instance();
    }

    public function test_registry_is_singleton(): void {
        $a = Component_Registry::get_instance();
        $b = Component_Registry::get_instance();
        $this->assertSame($a, $b);
    }

    public function test_register_defaults_adds_components(): void {
        $this->registry->register_defaults();
        $this->assertTrue($this->registry->has('product_card'));
        $this->assertTrue($this->registry->has('category_card'));
        $this->assertTrue($this->registry->has('hero'));
        $this->assertTrue($this->registry->has('footer'));
    }

    public function test_get_returns_component(): void {
        $this->registry->register_defaults();
        $component = $this->registry->get('product_card');
        $this->assertInstanceOf(Component::class, $component);
    }

    public function test_get_returns_null_for_unknown(): void {
        $this->assertNull($this->registry->get('unknown_component'));
    }

    public function test_get_all_returns_all_components(): void {
        $this->registry->register_defaults();
        $all = $this->registry->get_all();
        $this->assertCount(4, $all);
    }

    public function test_register_adds_new_component(): void {
        $component = new Component('test_comp', 'Test Comp', 'custom', 'Test_Renderer');
        $this->registry->register($component);
        $this->assertTrue($this->registry->has('test_comp'));
    }

    public function test_deregister_removes_component(): void {
        $this->registry->register(new Component('removable', 'Removable', 'custom', 'Test_Renderer'));
        $this->assertTrue($this->registry->has('removable'));
        $this->registry->deregister('removable');
        $this->assertFalse($this->registry->has('removable'));
    }

    public function test_component_has_required_properties(): void {
        $component = new Component('test', 'Test Comp', 'custom', 'Test_Class');
        $this->assertSame('test', $component->name);
        $this->assertSame('Test_Class', $component->class_name);
        $this->assertSame('custom', $component->category);
    }

    public function test_component_instance_creates_object(): void {
        $component = new Component('std', 'stdClass', 'core', 'stdClass');
        $instance = $component->instance();
        $this->assertInstanceOf(\stdClass::class, $instance);
    }
}
