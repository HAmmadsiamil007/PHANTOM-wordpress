<?php
use PHPUnit\Framework\TestCase;

class ContainerTest_Simple {
    public string $value = 'simple';
}

class ContainerTest_Dependent {
    public ContainerTest_Simple $dep;
    public function __construct(ContainerTest_Simple $dep) { $this->dep = $dep; }
}

class ContainerTest_Nullable {
    public ?ContainerTest_Simple $dep;
    public function __construct(?ContainerTest_Simple $dep = null) { $this->dep = $dep; }
}

class ContainerTest_A {
    public ContainerTest_B $b;
    public function __construct(ContainerTest_B $b) { $this->b = $b; }
}

class ContainerTest_B {
    public ContainerTest_A $a;
    public function __construct(ContainerTest_A $a) { $this->a = $a; }
}

class Container_Test extends TestCase {
    private PhantomCore\Engine\Container $container;

    protected function setUp(): void {
        $this->container = new PhantomCore\Engine\Container();
    }

    public function test_get_returns_singleton_instance(): void {
        $this->container->singleton('singleton_test', function () {
            return new \stdClass();
        });
        $a = $this->container->get('singleton_test');
        $b = $this->container->get('singleton_test');
        $this->assertSame($a, $b);
    }

    public function test_get_returns_new_instance_from_factory(): void {
        $this->container->set('factory_test', function () {
            return new \stdClass();
        });
        $a = $this->container->get('factory_test');
        $b = $this->container->get('factory_test');
        $this->assertNotSame($a, $b);
    }

    public function test_has_returns_false_for_unregistered(): void {
        $this->assertFalse($this->container->has('non_existent'));
    }

    public function test_auto_wire_simple_class(): void {
        $obj = $this->container->autoWire(ContainerTest_Simple::class);
        $this->assertInstanceOf(ContainerTest_Simple::class, $obj);
        $this->assertSame('simple', $obj->value);
    }

    public function test_auto_wire_with_dependencies(): void {
        $obj = $this->container->autoWire(ContainerTest_Dependent::class);
        $this->assertInstanceOf(ContainerTest_Dependent::class, $obj);
        $this->assertInstanceOf(ContainerTest_Simple::class, $obj->dep);
    }

    public function test_auto_wire_with_nullable_param(): void {
        $obj = $this->container->autoWire(ContainerTest_Nullable::class);
        $this->assertInstanceOf(ContainerTest_Nullable::class, $obj);
        $this->assertNull($obj->dep);
    }

    public function test_tagged_services(): void {
        $this->container->set('svc_a', function () { return 'alpha'; });
        $this->container->set('svc_b', function () { return 'beta'; });
        $this->container->tag('svc_a', 'group1');
        $this->container->tag('svc_b', 'group1');
        $result = $this->container->tagged('group1');
        $this->assertCount(2, $result);
        $this->assertContains('alpha', $result);
        $this->assertContains('beta', $result);
    }

    public function test_auto_wire_reuses_singletons(): void {
        $this->container->singleton(ContainerTest_Simple::class, function () {
            return new ContainerTest_Simple();
        });
        $a = $this->container->autoWire(ContainerTest_Dependent::class);
        $b = $this->container->autoWire(ContainerTest_Dependent::class);
        $this->assertSame($a->dep, $b->dep);
    }

    public function test_circular_dependency_throws(): void {
        $this->expectException(PhantomCore\Engine\ContainerException::class);
        $this->container->autoWire(ContainerTest_A::class);
    }
}
