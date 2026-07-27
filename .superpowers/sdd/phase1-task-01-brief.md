# P1.1 — Create Container.php with PSR-11 auto-wiring

**File:** `phantom-core/includes/Engine/Container.php`

**Namespace:** `PhantomCore\Engine`

## Requirements

Create a PSR-11-compatible dependency injection container with auto-wiring via PHP reflection.

### Interfaces (defined inline in the same file)

```php
interface ContainerInterface {
    public function get(string $id): mixed;
    public function has(string $id): bool;
}

class ContainerException extends \Exception implements ContainerExceptionInterface {}
class NotFoundException extends \Exception implements NotFoundExceptionInterface {}

interface ContainerExceptionInterface extends \Psr\Container\ContainerExceptionInterface {}
interface NotFoundExceptionInterface extends \Psr\Container\NotFoundExceptionInterface {}
```

Actually, since we don't want the psr/container dependency, define our OWN interfaces:

```php
namespace PhantomCore\Engine;

interface ContainerInterface {
    public function get(string $id): mixed;
    public function has(string $id): bool;
}
```

### Container class

```php
class Container implements ContainerInterface {
    private array $factories = [];    // factory callables
    private array $singletons = [];   // singleton factory callables
    private array $instances = [];    // resolved singleton instances
    private array $tags = [];         // tag => [service_ids]

    public function get(string $id): mixed
    public function has(string $id): bool
    public function set(string $id, callable $factory): void
    public function singleton(string $id, callable $factory): void
    public function tag(string $id, string $tag): void
    public function tagged(string $tag): array
    public function autoWire(string $class): object
}
```

### Auto-wiring rules

1. If `$id` is in `$instances` (previously resolved singleton), return it
2. If `$id` has a registered factory, call it with `$this` as argument
3. If `$id` has a registered singleton factory, call it, cache the result
4. Otherwise, attempt `autoWire($id)` — but ONLY if `$id` is a class name (class_exists)
5. `autoWire()` uses `ReflectionClass` to inspect constructor parameters:
   - If no constructor, `$ref->newInstance()`
   - For each parameter:
     a. If the type is a class/interface name, call `$this->get($typeName)` (recursive resolution)
     b. If the parameter is nullable and has a null default, skip it (pass null)
     c. If the parameter has a default value, use it
     d. Otherwise throw `ContainerException`

### Exception classes

```php
class ContainerException extends \Exception implements ContainerExceptionInterface {}
class NotFoundException extends \Exception implements NotFoundExceptionInterface {}
interface ContainerExceptionInterface {}
interface NotFoundExceptionInterface {}
```

### Tagged services

- `tag($id, $tag)` — appends `$id` to `$this->tags[$tag]`
- `tagged($tag)` — resolves and returns all services with that tag as an array

### Error handling

- `get()` on unregistered service → attempt autoWire first, if not a class throw NotFoundException
- Auto-wire on interface without registration → ContainerException
- Built-in type hint without default → ContainerException
- Circular dependency → Reflection may stack overflow, catch and throw ContainerException with clear message

## File structure

```php
<?php
declare(strict_types=1);

namespace PhantomCore\Engine;

defined('ABSPATH') || exit;

// Interfaces
interface ContainerInterface { ... }
interface ContainerExceptionInterface {}
interface NotFoundExceptionInterface {}

// Exceptions
class ContainerException extends \Exception implements ContainerExceptionInterface {}
class NotFoundException extends \Exception implements NotFoundExceptionInterface {}

// Container
class Container implements ContainerInterface { ... }
```

## Verification
```bash
php -l phantom-core/includes/Engine/Container.php
```

## Commit
```
cd C:\Users\hamma\Downloads\wordpress
git add phantom-core/includes/Engine/Container.php
git commit -m "feat(phase1): create PSR-11 Container with auto-wiring"
```

Write report to `.superpowers/sdd/phase1-task-01-report.md`
