# P1.4 — Create EventDispatcherTest.php

**File:** `phantom-core/tests/EventDispatcher_Test.php`

**Requires:** EventDispatcher.php + PhpEventStore.php already exist at `includes/Engine/`

## Test Requirements (7+ tests)

Create PHPUnit tests covering:

1. **test_dispatch_calls_listeners** — Register a listener, dispatch, assert listener was called (use a reference variable)
2. **test_dispatch_passes_payload** — Listener receives the payload array
3. **test_listenOnce_fires_once** — listenOnce listener fires only on first dispatch
4. **test_dispatch_returns_results** — dispatch() returns array of listener return values
5. **test_flush_removes_listeners** — After flush, listener is not called
6. **test_store_captures_events** — After dispatch, store has the event (use getStore()->toArray())
7. **test_priority_order** — Low-priority listener (20) fires AFTER high-priority (5), verify order in return values
8. **test_store_flush_clears** — dispatch twice, flush store, count = 0

## Test structure

```php
<?php
use PHPUnit\Framework\TestCase;
use PhantomCore\Engine\EventDispatcher;

class EventDispatcher_Test extends TestCase {
    private EventDispatcher $dispatcher;
    
    protected function setUp(): void {
        $this->dispatcher = new EventDispatcher();
    }
    
    public function test_dispatch_calls_listeners(): void {
        $called = false;
        $this->dispatcher->listen('test.event', function($payload, $event) use (&$called) {
            $called = true;
        });
        $this->dispatcher->dispatch('test.event');
        $this->assertTrue($called);
    }
    
    // ... 7+ more tests ...
}
```

## Verification
```bash
cd C:\Users\hamma\Downloads\wordpress\phantom-core
vendor\bin\phpunit tests/EventDispatcher_Test.php
```

If tests fail, fix. Run until all pass.

## Commit
```bash
cd C:\Users\hamma\Downloads\wordpress
git add phantom-core/tests/EventDispatcher_Test.php
git commit -m "feat(phase1): add EventDispatcher tests (8 scenarios)"
```

Write report to `.superpowers/sdd/phase1-task-04-report.md`

Return: pass/fail + commit hash + summary.