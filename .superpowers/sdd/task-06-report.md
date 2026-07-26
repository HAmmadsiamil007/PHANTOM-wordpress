# T0.6 Report — PhantomInjector.js

## Status
✅ Complete

## Commits
- `54fbb97` — feat(phase0): create PhantomInjector.js with DOM injection API, wire into phantom-core.js

## Files Created
- `phantom-core/frontend/assets/js/phantom-injector.js` (157 lines)

## Files Modified
- `phantom-core/frontend/assets/js/phantom-core.js` (37 → 54 lines)

## Changes to phantom-core.js

1. **Replaced guard no-ops** (lines 27-29): Three `w.PhantomInjector && w.PhantomInjector.inject*()` guard patterns replaced with direct `window.PhantomInjector.inject*()` calls — since PhantomInjector is guaranteed loaded before phantom-core.js.

2. **Added PhantomData init block** (lines 38-53): IIFE that reads static data from `window.PhantomData` (settings, menus, products) and injects via PhantomInjector immediately at script load time, before the async API fetch in `onReady`.

## What Changed More Specifically
| Change | Location | Details |
|--------|----------|---------|
| Guard → direct call | core.js:27 | `w.PhantomInjector && w.PhantomInjector.injectSettings(data.settings)` → `window.PhantomInjector.injectSettings(data.settings)` |
| Guard → direct call | core.js:28 | Same pattern for `injectMenus` |
| Guard → direct call | core.js:29 | Same pattern for `injectProducts` |
| New IIFE | core.js:38-53 | Reads `window.PhantomData` and calls injectors for settings, menus, products (static data initialization) |