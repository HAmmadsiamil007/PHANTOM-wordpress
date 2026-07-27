# P1.3 — Create EventDispatcher.php + PhpEventStore.php

**Files:**
- `phantom-core/includes/Engine/EventDispatcher.php`
- `phantom-core/includes/Engine/PhpEventStore.php`

**Namespace:** `PhantomCore\Engine`

## EventDispatcher Requirements

The EventDispatcher provides pub/sub event management with a capture store.

```php
class EventDispatcher {
    private array $listeners = [];  // event => [priority => [listeners]]
    private PhpEventStore $store;
    
    public function __construct(?PhpEventStore $store = null)  // create store if not provided
    
    public function dispatch(string $event, array $payload = []): array
    public function listen(string $event, callable $listener, int $priority = 10): void
    public function listenOnce(string $event, callable $listener): void
    public function flush(string $event): void
    public function getListeners(?string $event = null): array
    public function getStore(): PhpEventStore
}
```

### dispatch() behavior:
1. Check if any listeners registered for this event
2. Sort listeners by priority (ascending — lower number = higher priority)
3. Call each listener with ($payload, $event)
4. Collect return values into array
5. Capture event to store via `$this->store->capture($event, $payload)`
6. Fire WordPress action: `do_action("phantom_event_{$event}", $payload)`
7. Return array of listener return values

### listenOnce():
- Register a listener wrapped in a closure that removes itself after first invocation
- Use spl_object_id to track and remove later

### getListeners($event):
- If $event is null, return ALL listeners grouped by event
- If $event is provided, return all listeners for that event (flat array, highest priority first)

### flush($event):
- Remove ALL listeners for the given event
- Return the removed listener count

## PhpEventStore Requirements

Simple sequential event capture store.

```php
class PhpEventStore {
    private array $events = [];
    
    public function capture(string $event, array $payload = []): void    // append to $this->events
    public function flush(): array   // return and clear all events
    public function count(): int
    public function toArray(): array // return current events without clearing
    public function clear(): void    // clear all events without returning
}
```

Each stored event record:
```php
[
    'event'   => $event,
    'payload' => $payload,
    'time'    => microtime(true),
]
```

## File structure

```php
<?php
declare(strict_types=1);

namespace PhantomCore\Engine;

defined('ABSPATH') || exit;

class PhpEventStore { ... }
```

```php
<?php
declare(strict_types=1);

namespace PhantomCore\Engine;

defined('ABSPATH') || exit;

class EventDispatcher { ... }
```

## Key implementation notes

- EventDispatcher dispatch() should always capture to store, even if no listeners
- `do_action()` calls should use `function_exists('do_action')` guard for standalone testing
- `listenOnce` wrapper should properly remove itself after first call
- `dispatch()` returns array of collected results
- Listeners receive payload AND event name for context

## Stub for standalone testing

In `dispatch()`, wrap `do_action` calls:
```php
if (function_exists('do_action')) {
    do_action("phantom_event_{$event}", $payload);
}
```

## Verification
```bash
php -l phantom-core/includes/Engine/EventDispatcher.php
php -l phantom-core/includes/Engine/PhpEventStore.php
```

## Commit
```bash
cd C:\Users\hamma\Downloads\wordpress
git add phantom-core/includes/Engine/EventDispatcher.php
git add phantom-core/includes/Engine/PhpEventStore.php
git commit -m "feat(phase1): create EventDispatcher + PhpEventStore"
```

Write report to `.superpowers/sdd/phase1-task-03-report.md`
