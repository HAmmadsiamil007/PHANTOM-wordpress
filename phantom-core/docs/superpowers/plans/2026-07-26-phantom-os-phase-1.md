# Phantom OS Phase 1 — Service Container + Event System

**Spec:** `docs/superpowers/specs/2026-07-26-phantom-os-phase-1.md`

**Goal:** PSR-11 DI container with auto-wiring, EventDispatcher with JS bridge, all Engine classes using constructor injection.

**Dependency chain:** P1.1 → P1.2 → P1.3 → P1.4 → P1.5 → P1.6 → P1.7 → P1.8 → P1.9 → P1.10 → P1.11 (sequential)

## Tasks

### P1.1 — Container.php
`includes/Engine/Container.php` — PSR-11 container interface + auto-wiring implementation.
- `get($id)`, `has($id)` — PSR-11 interface
- `set($id, callable $factory)` — register factory
- `singleton($id, callable $factory)` — shared instance
- `tag($id, $tag)` / `tagged($tag)` — service tagging
- `autoWire($class)` — ReflectionClass-based dependency resolution
- Exceptions: `ContainerException`, `NotFoundException` implementing PSR-11 interfaces

### P1.2 — ContainerTest.php
`tests/ContainerTest.php` — 8+ tests: resolve shared, resolve factory, auto-wire simple, auto-wire with deps, has false for unregistered, throws for unresolvable, tagged resolution, singleton caches.

### P1.3 — EventDispatcher.php + PhpEventStore.php
`includes/Engine/EventDispatcher.php` — dispatch, listen, listenOnce, flush, getListeners, store.
`includes/Engine/PhpEventStore.php` — capture, flush, toArray.

### P1.4 — EventDispatcherTest.php
7+ tests: dispatch calls listeners, payload passed, listenOnce fires once, dispatch fires WP hook, flush removes, store captures, priority order.

### P1.5 — Render_Engine refactor
Change constructor to accept `Template_Loader, SEO_Engine, Security_Headers, Asset_Loader, EventDispatcher`. Remove all `new ClassName()` calls. Remove Settings_Registry + set_pack from constructor (pack moves to container config).

### P1.6 — WooCommerce_Injector refactor
Accept `EventDispatcher` in constructor. Keep existing adapter/renderer `new` calls (Phase 2 concern).

### P1.7 — container-config.php
`includes/Engine/class-container-config.php` — Container factory definitions for all 7 Engine classes. Template_Loader pack resolution. Tag registration.

### P1.8 — Shell refactor
`templates/shell.php` — `init()` creates container, resolves `Render_Engine` from it.

### P1.9 — phantom-core.php hook
Register container init at `plugins_loaded`, priority 0. Bootstrap the config.

### P1.10 — event-services.js
Add `consumeStore()` to `w.PhantomEvents`. Called on DOMContentLoaded.

### P1.11 — Final verification + audit
PHP lint all files, JS build, run tests, full self-review audit (aim 100/100).
