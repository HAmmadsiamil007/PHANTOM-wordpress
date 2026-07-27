# P1.2 Report — Container_Test.php

**Status:** ✅ PASS

**Commit:** `b2c4d52`

**Files:**
- `phantom-core/tests/Container_Test.php` (new) — 9 test methods, 14 assertions
- `phantom-core/includes/Engine/Container.php` (modified) — nullable param fix in `autoWire()`

**Test Results: 9/9 pass**

| # | Test | Result |
|---|------|--------|
| 1 | `test_get_returns_singleton_instance` | ✅ Singleton factory returns same instance |
| 2 | `test_get_returns_new_instance_from_factory` | ✅ Factory returns different instances |
| 3 | `test_has_returns_false_for_unregistered` | ✅ `has('non_existent')` returns false |
| 4 | `test_auto_wire_simple_class` | ✅ No-constructor class resolved |
| 5 | `test_auto_wire_with_dependencies` | ✅ Class with typed dependency resolved |
| 6 | `test_auto_wire_with_nullable_param` | ✅ Nullable param resolves to null |
| 7 | `test_tagged_services` | ✅ 2 tagged services returned as array |
| 8 | `test_auto_wire_reuses_singletons` | ✅ Singleton dependency shared across auto-wired instances |
| 9 | `test_circular_dependency_throws` | ✅ Circular dependency throws `ContainerException` |

**Fix applied:** `Container::autoWire()` — moved `allowsNull()` check before `$this->get()` resolution attempt so nullable typed params resolve to `null` instead of being eagerly resolved.
