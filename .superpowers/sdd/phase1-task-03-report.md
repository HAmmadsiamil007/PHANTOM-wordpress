# P1.3 Report — EventDispatcher + PhpEventStore

**Status:** ✅ Complete
**Commit:** `23c3399dada5e33cca163212a90760dfb6709132`
**Date:** 2026-07-26

## Files Created
- `phantom-core/includes/Engine/EventDispatcher.php` — 111 lines
- `phantom-core/includes/Engine/PhpEventStore.php` — 56 lines

## Files Modified
- `phantom-core/tests/bootstrap.php` — added 3 lines (require_once for both Engine classes)

## Implementation Details

### PhpEventStore
- `capture($event, $payload)` — appends `['event', 'payload', 'time' => microtime(true)]`
- `flush()` — returns all events and clears
- `count()` — returns event count
- `toArray()` — peep without clearing
- `clear()` — clear without returning

### EventDispatcher
- `dispatch($event, $payload)` — sorts listeners by priority ascending, calls each with `($payload, $event)`, collects return values, captures to store unconditionally, fires `do_action("phantom_event_{$event}", $payload)` guarded by `function_exists`
- `listen($event, $listener, $priority)` — registers in nested array `[event][priority][]`
- `listenOnce($event, $listener)` — self-removing wrapper via `&$once` + `spl_object_id`
- `flush($event)` — removes all listeners, returns count removed
- `getListeners($event)` — returns flat array sorted ascending (highest priority first); `getListeners(null)` returns all grouped
- `getStore()` — returns the `PhpEventStore` instance

## Verification
- `php -l` passed for both files (no syntax errors)

## Issues
None.
