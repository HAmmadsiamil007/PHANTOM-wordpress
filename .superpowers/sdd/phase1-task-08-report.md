# P1.8 — Refactor Shell to use Container

**Status:** ✅ Complete  
**Commit:** `3885227`  
**File:** `phantom-core/templates/shell.php`

## Changes Made

1. Added use statements for `Container` and `Container_Config` (line 13-14)
2. Changed `init()` signature from `public function init(): void` to `public function init(?Container $container = null): void`
3. `Render_Engine` now resolved via container: `$container->get(Render_Engine::class)` instead of `new Render_Engine()`
4. Container auto-created when none injected: `$container = $container ?? new Container()` + `Container_Config::configure($container)`
5. All other methods (`handle_request`, `init_wc_session`, `invalidate_cache_on_save`) unchanged

## Verification

- `php -l` — No syntax errors detected
- Commit hash: `3885227`
