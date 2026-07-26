# Phantom OS — Phase 1: Service Container + Event System

**Date:** 2026-07-26  
**Version:** 1.0  
**Status:** Draft  
**Parent:** `2026-07-26-phantom-os-master-plan.md`

---

## 1. Objective

Introduce a PSR-11-compatible dependency injection container with auto-wiring and a PHP EventDispatcher that bridges to JavaScript. Refactor all Engine classes to use constructor injection via the container. Replace all `new ClassName()` instantiations in Engine files with container resolution.

---

## 2. Current State

### 2.1 Engine Classes (7 files in `includes/Engine/`)

| Class | Lines | Dependencies Created Manually | Instantiated By |
|-------|-------|------------------------------|-----------------|
| `Render_Engine` | 230 | `Template_Loader`, `SEO_Engine`, `Security_Headers`, `Asset_Loader` | `Shell::init()` |
| `Template_Loader` | 123 | None | `Render_Engine` |
| `SEO_Engine` | 340 | None | `Render_Engine` |
| `Security_Headers` | 31 | None | `Render_Engine` |
| `Asset_Loader` | 190 | None | `Render_Engine` |
| `Cache` | 55 | None (singleton) | Via `get_instance()` |
| `WooCommerce_Injector` | 424 | `Product_Adapter`, `Category_Adapter`, `Hero_Adapter`, `Product_Card`, `Category_Card`, `Hero`, `Footer` | `Render_Engine::inject_woocommerce_content()` |

**Problem:** `Render_Engine::__construct()` and `WooCommerce_Injector::__construct()` hardcode `new ClassName()` calls. Swapping implementations or testing in isolation requires modifying class internals.

### 2.2 Event System (Current)

- **PHP:** Only WordPress `do_action()`/`add_action()` hooks are used for loose coupling at plugin level.
- **JS:** `w.PhantomEvents` (in `event-services.js`) provides `on()`, `emit()`, `off()` generic events plus `onSettingChange()`, `offSettingChange()`, `emitSettingChange()` for setting-specific listeners.
- **No bridge:** PHP events never reach JavaScript. JS events never trigger PHP hooks.

---

## 3. Architecture

### 3.1 Container (`includes/Engine/Container.php`)

PSR-11 compatible container with auto-wiring via PHP reflection.

```
Container
├── get(string $id): mixed        # Resolve service (auto-wire if not registered)
├── has(string $id): bool          # Check if service can be resolved
├── set(string $id, callable $factory): void  # Register factory
├── singleton(string $id, callable $factory): void  # Register shared instance factory
├── tag(string $id, string $tag): void  # Tag a service for group resolution
├── tagged(string $tag): array     # Resolve all services with a given tag
└── autoWire(string $class): object  # Reflection-based auto-wiring
```

**Auto-wiring rules:**
- Scan constructor parameter types via `ReflectionClass`
- For typed parameters that implement an interface or class known to the container, resolve recursively
- For nullable typed parameters, skip if null is the default
- For untyped/built-in parameters, throw `ContainerException` (must be registered explicitly)
- Cache resolved instances for singleton registrations; resolve fresh for `get()` without `singleton()`

**Wire-up** (`includes/Engine/class-container-config.php` or inline in `phantom-core.php`):
```php
$container = new Container();
$container->singleton(Container::class, fn() => $container);
$container->singleton(Render_Engine::class, fn($c) => new Render_Engine(
  $c->get(Template_Loader::class),
  $c->get(SEO_Engine::class),
  $c->get(Security_Headers::class),
  $c->get(Asset_Loader::class),
  $c->get(EventDispatcher::class)
));
$container->singleton(Template_Loader::class, fn() => new Template_Loader());
$container->singleton(SEO_Engine::class, fn() => new SEO_Engine());
$container->singleton(Security_Headers::class, fn() => new Security_Headers());
$container->singleton(Asset_Loader::class, fn() => new Asset_Loader());
$container->singleton(Cache::class, fn() => Cache::get_instance());
$container->singleton(EventDispatcher::class, fn($c) => new EventDispatcher($c));
```

Engine classes with no constructor params (`SEO_Engine`, `Security_Headers`, `Asset_Loader`, `Template_Loader`) should NOT be changed — they have no constructor injection. Only `Render_Engine`, `WooCommerce_Injector`, and `Shell` need refactoring to accept dependencies via constructor.

### 3.2 EventDispatcher (`includes/Engine/EventDispatcher.php`)

```php
EventDispatcher
├── dispatch(string $event, array $payload = []): void  # Fire event
├── listen(string $event, callable $listener, int $priority = 10): void  # Register PHP listener
├── listenOnce(string $event, callable $listener): void  # One-shot listener
├── flush(string $event): void  # Remove all listeners for event
├── getListeners(string $event): array  # Return all registered listeners
└── store(): PhpEventStore  # Return captured events for JS bridge
```

**Dispatch behavior:**
1. Calls all registered PHP listeners in priority order
2. Fires `do_action("phantom_event_{$event}", $payload)` for WP hook compatibility
3. Captures event into `PhpEventStore` (queue) for JS bridge injection

**Event naming convention:** `phantom.{domain}.{action}` — e.g., `phantom.cart.added`, `phantom.cart.updated`, `phantom.settings.changed`, `phantom.auth.login`, `phantom.demo.activated`, `phantom.cache.flushed`.

### 3.3 JS Bridge

`Render_Engine::inject_bridge()` already injects `window.PhantomData` with a JSON blob. Extend it to also inject recent events from `PhpEventStore`:

```html
<script id="phantom-events-store" type="application/json">
[{"event":"phantom.cart.updated","payload":{"count":3,"total":"$120.00"},"ts":1722000000}]
</script>
```

On the JS side, `event-services.js` already has `w.PhantomEvents`. Add a method to consume this store:
```javascript
// In PhantomEvents (event-services.js)
consumeStore: function() {
  var el = document.getElementById('phantom-events-store');
  if (!el) return;
  try {
    var events = JSON.parse(el.textContent);
    for (var i = 0; i < events.length; i++) {
      this.emit(events[i].event, events[i].payload);
    }
  } catch(e) {}
}
```

This is called once on DOMContentLoaded after PhantomData is loaded.

### 3.4 Classes to Refactor for DI

**Render_Engine** — change constructor to accept dependencies:
```php
public function __construct(
    Template_Loader $template_loader,
    SEO_Engine $seo,
    Security_Headers $security,
    Asset_Loader $assets,
    EventDispatcher $events
)
```
Remove all `new ClassName()` calls from constructor body. Remove Settings_Registry dependency (it's a static singleton, can stay as-is).

**WooCommerce_Injector** — same pattern (but adapters/renderers are not in the container — they're created directly in the constructor for now, or passed in):
```php
public function __construct(
    Render_Engine $engine,
    Product_Adapter $product_adapter = null,
    Category_Adapter $category_adapter = null,
    ...
)
```
For simplicity in Phase 1, keep the adapter/renderer `new` calls inside WooCommerce_Injector — those will be containerized in Phase 2 when the three-engine split extracts Data_Engine. The critical change is accepting `Render_Engine` via constructor (already done) and `EventDispatcher`.

**Shell** — change `init()` to use container and set template pack:
```php
public function init(): void {
    $container = $this->get_container();
    $this->engine = $container->get(Render_Engine::class);
    // Template pack is set during container config, not here
    // ... WP hooks remain
}
```

**Template_Loader pack resolution** — currently done in `Render_Engine::__construct()` via `Settings_Registry::get_instance()->get('template_pack')`. Move this into `class-container-config.php` so the pack is set when the container wires Template_Loader:
```php
$container->singleton(Template_Loader::class, function($c) {
    $loader = new Template_Loader();
    $pack = 'kids';
    if (class_exists('\PhantomCore\Settings_Registry')) {
        $registry = \PhantomCore\Settings_Registry::get_instance();
        if ($registry->has('template_pack')) {
            $pack = $registry->get('template_pack');
        }
    }
    $loader->set_pack($pack);
    return $loader;
});
```

`get_container()` creates the singleton container on first call (at `plugins_loaded`, priority 0).

---

## 4. Files

### 4.1 New Files

| File | Purpose |
|------|---------|
| `includes/Engine/Container.php` | PSR-11 container with auto-wiring |
| `includes/Engine/EventDispatcher.php` | PHP event dispatcher |
| `includes/Engine/class-container-config.php` | Container wiring configuration (factory definitions) |
| `includes/Engine/PhpEventStore.php` | Event capture queue for JS bridge |
| `tests/ContainerTest.php` | Unit tests for Container |
| `tests/EventDispatcherTest.php` | Unit tests for EventDispatcher |

### 4.2 Modified Files

| File | Change |
|------|--------|
| `includes/Engine/Render_Engine.php` | Constructor injection for all deps; remove `new ClassName()` |
| `includes/Engine/WooCommerce_Injector.php` | Accept EventDispatcher; rest unchanged |
| `templates/shell.php` | Create container in `init()`, resolve engines from it |
| `phantom-core.php` | Hook `container_init()` at `plugins_loaded`, priority 0 |
| `frontend/assets/js/services/event-services.js` | Add `consumeStore()` method |
| `includes/Engine/Asset_Loader.php` | No change needed (no constructor) |

---

## 5. Error Handling

- **Container resolution failure:** Throw `ContainerException` (extends `\Psr\Container\ContainerExceptionInterface`). The caller in `Render_Engine::render()` catches and falls through gracefully.
- **Event dispatch with no listeners:** Silent no-op — no error, no warning.
- **JS store JSON malformed:** Catch in `consumeStore()`, log to console.warn, no crash.
- **Missing `PhpEventStore`:** If the store is empty, inject empty JSON array `[]`.

---

## 6. Testing Strategy

### Unit Tests

**ContainerTest:**
- `test_resolve_shared_service` — singleton returns same instance
- `test_resolve_factory_service` — `get()` without singleton returns new instance each time
- `test_auto_wire_simple` — class with no constructor resolves
- `test_auto_wire_with_dependencies` — class with typed constructor params resolves recursively
- `test_has_returns_false_for_unregistered` — `has('NonExistent')` returns false
- `test_resolve_throws_for_unresolvable` — built-in type hint in constructor throws
- `test_tagged_returns_all_tagged_services`
- `test_singleton_caches_instance`

**EventDispatcherTest:**
- `test_dispatch_calls_registered_listeners`
- `test_dispatch_passes_payload_to_listeners`
- `test_listen_once_fires_only_once`
- `test_dispatch_fires_wp_hook`
- `test_flush_removes_all_listeners`
- `test_store_captures_events`
- `test_priority_order_listeners`

### How to run:
```bash
cd C:\Users\hamma\Downloads\wordpress\phantom-core
phpunit tests/ContainerTest.php
phpunit tests/EventDispatcherTest.php
```

---

## 7. Success Criteria

- [ ] Container resolves services with auto-wiring (reflection-based)
- [ ] Container supports singleton (shared) and factory instances
- [ ] Container throws `ContainerException` for unresolvable dependencies
- [ ] Tagged service resolution works (`tag()` + `tagged()`)
- [ ] EventDispatcher emits events to registered PHP listeners
- [ ] EventDispatcher fires `do_action("phantom_event_{$event}")` bridge
- [ ] `PhpEventStore` captures dispatched events
- [ ] JS `consumeStore()` processes injected event store JSON
- [ ] `Render_Engine` accepts all deps via constructor injection (no `new` inside)
- [ ] `WooCommerce_Injector` accepts `EventDispatcher` via constructor
- [ ] `Shell::init()` resolves `Render_Engine` from container
- [ ] Container config is registered at `plugins_loaded`, priority 0
- [ ] All existing PHP syntax checks pass (22 files)
- [ ] All existing JS build succeeds (node build.js)
- [ ] ContainerTest: 8+ tests passing
- [ ] EventDispatcherTest: 7+ tests passing

---

## 8. Implementation Order

1. **Container.php** — core auto-wiring logic + PSR-11 interfaces
2. **ContainerTest.php** — test all 8 container behaviors
3. **EventDispatcher.php + PhpEventStore.php** — dispatch, listen, capture
4. **EventDispatcherTest.php** — test all 7 dispatcher behaviors
5. **Render_Engine refactor** — constructor injection, remove `new`
6. **WooCommerce_Injector refactor** — accept EventDispatcher
7. **container-config.php** — factory definitions for all services
8. **shell.php refactor** — init container, resolve from it
9. **phantom-core.php** — hook container init at plugins_loaded
10. **event-services.js** — add consumeStore()
11. **Final verification** — lint + build + test + commit

---

## 9. Revision History

| Date | Version | Changes |
|------|---------|---------|
| 2026-07-26 | 1.0 | Initial spec from master plan Phase 1 |
