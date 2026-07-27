# P1.1 — Container.php Report

**Status:** ✅ Complete

**Commit:** `9ff61fdbe6d9033aac4a0370071639a3dc15f12b`

**File:** `phantom-core/includes/Engine/Container.php`

## What was created

Single file — `Container.php` in namespace `PhantomCore\Engine` — containing:

### Interfaces (4 total)
- `ContainerInterface` — `get(string $id): mixed`, `has(string $id): bool`
- `ContainerExceptionInterface` — marker interface
- `NotFoundExceptionInterface` — marker interface

### Exception classes (2)
- `ContainerException extends \Exception implements ContainerExceptionInterface`
- `NotFoundException extends \Exception implements NotFoundExceptionInterface`

### Container class
Implements `ContainerInterface` with these methods:

| Method | Behavior |
|--------|----------|
| `get($id)` | Resolves: instances → factories → singletons → autoWire (if class_exists). Throws `NotFoundException` if unresolvable. |
| `has($id)` | Returns true if in instances, factories, singletons, or a valid class. |
| `set($id, $factory)` | Registers a factory callable (new instance each call) |
| `singleton($id, $factory)` | Registers a shared factory (caches result) |
| `tag($id, $tag)` | Tags a service ID |
| `tagged($tag)` | Resolves and returns array of all tagged services |
| `autoWire($class)` | ReflectionClass-based auto-wiring |

### Auto-wiring features
- Recursive dependency resolution via `$this->get($typeName)` for type-hinted params
- Nullable params with null default → passed as null
- Params with default values → use the default
- Circular dependency detection via `$this->resolving[]` tracking set
- Non-instantiable (abstract/interface) classes throw `ContainerException`
- Built-in type params without defaults throw `ContainerException`

### Verification
`php -l phantom-core/includes/Engine/Container.php` → **No syntax errors detected**

## Issues
None.
