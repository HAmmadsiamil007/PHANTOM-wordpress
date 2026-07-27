# P1.2 — Create ContainerTest.php

**File:** `phantom-core/tests/Container_Test.php` (note: suffix is `_Test.php`)

**Namespace:** none (root namespace, like Settings_Registry_Test)

**Requires:** Container.php already exists at `includes/Engine/Container.php`

## Test Requirements

Create PHPUnit tests covering ALL of these scenarios (8+ tests):

1. **test_get_returns_singleton_instance** — Register singleton factory, call get() twice, assert same object
2. **test_get_returns_new_instance_from_factory** — Register factory via set(), call get() twice, assert different objects
3. **test_has_returns_false_for_unregistered** — has('non_existent') returns false
4. **test_auto_wire_simple_class** — Auto-wire a class with no constructor params
5. **test_auto_wire_with_dependencies** — Auto-wire a class that depends on another class
6. **test_auto_wire_with_nullable_param** — Class with nullable typed param (should resolve to null)
7. **test_tagged_services** — Register 2 services, tag them, tagged() returns both resolved
8. **test_auto_wire_reuses_singletons** — A depends on B (singleton), autoWire(A) returns both, B is same instance
9. **test_circular_dependency_throws** — Register mutual dependency, get() should throw ContainerException

## Helper classes (define at bottom of test file)

```php
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
```

## Test structure

```php
<?php
use PHPUnit\Framework\TestCase;
use PhantomCore\Engine\Container;
// ... helper classes ...

class Container_Test extends TestCase {
    private Container $container;
    
    protected function setUp(): void {
        $this->container = new Container();
    }
    
    // ... test methods ...
}
```

## Verification
```bash
cd C:\Users\hamma\Downloads\wordpress\phantom-core
vendor\bin\phpunit tests/Container_Test.php
```

## Commit
```bash
cd C:\Users\hamma\Downloads\wordpress
git add phantom-core/tests/Container_Test.php
git commit -m "feat(phase1): add Container tests (9 scenarios)"
```

Write report to `.superpowers/sdd/phase1-task-02-report.md`
