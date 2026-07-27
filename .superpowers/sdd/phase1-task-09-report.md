# P1.9 Report — Hook container init in phantom-core.php

**Status:** ✅ Complete

**Commit:** `d589d4d`

## Changes Made

### 1. Added explicit requires for Engine files
Inserted 6 `require_once` calls for the new Engine directory files between `hero.php` and `admin/class-settings-page.php`:

- `includes/Engine/Container.php`
- `includes/Engine/Container_Config.php`
- `includes/Engine/Render_Engine.php`
- `includes/Engine/EventDispatcher.php`
- `includes/Engine/PhpEventStore.php`
- `includes/Engine/WooCommerce_Injector.php`

### 2. Deferred Shell initialization to `plugins_loaded`
Changed the Shell init block from eager inline initialization to a `plugins_loaded` hook at priority 0:

- The `require_once` for `templates/shell.php` still happens at file load (class definition must be available)
- `Shell::get_instance()->init()` now fires at `plugins_loaded` priority 0 — before all other plugin init hooks (priority 5, 10, 15)

## Verification
- `php -l phantom-core/phantom-core.php` → **No syntax errors detected**
- `git add` + `git commit` → `d589d4d`

## Rationale
Moving Shell init to `plugins_loaded` ensures the Service Container and all Engine services are fully registered before other plugin components initialize. Priority 0 guarantees Shell is first among the `plugins_loaded` callbacks.
