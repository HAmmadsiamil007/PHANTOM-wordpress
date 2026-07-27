# P1.5 — Refactor Render_Engine for constructor injection

**Status:** ✅ Complete

## Changes applied to `phantom-core/includes/Engine/Render_Engine.php`

1. **Added `EventDispatcher $events` property** (line 14)
2. **Constructor now accepts 5 injected dependencies** (lines 19-31): `Template_Loader`, `SEO_Engine`, `Security_Headers`, `Asset_Loader`, `EventDispatcher` — removed all `new` instantiation
3. **Removed pack resolution block**: `$pack = 'kids'` + `Settings_Registry` lookup + `set_pack()` call deleted from constructor
4. **Added `get_template_loader(): Template_Loader`** getter (lines 60-62) for container-config integration
5. **Updated `WooCommerce_Injector` creation** (line 229): `new WooCommerce_Injector($this, $this->events)`
6. **All other methods unchanged** — `render()`, `inject_customizer_css()`, `inject_bridge()`, `inject_auth_nonces()`, `inject_woocommerce_content()` untouched

## Verification
- `php -l` → **No syntax errors detected**
- File: 233 lines, 18 insertions, 15 deletions

## Commit
```
df52253 feat(phase1): refactor Render_Engine for constructor injection
```
