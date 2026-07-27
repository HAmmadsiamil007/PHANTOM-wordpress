# Phantom OS Phase 2 — Three-Engine Split

**Date:** 2026-07-27
**Status:** Draft
**Depends on:** Phase 1 (Service Container + Event System)
**Leads into:** Phase 3 (Demo Manager)

---

## 1. Objective

Split the monolithic `Render_Engine` into three focused engines (Data, View, Asset) plus a thin coordinator. Each engine has a single responsibility, is independently testable, and communicates through constructor injection via the Container.

## 2. Current State

| Component | Lines | Responsibility |
|-----------|-------|---------------|
| `Render_Engine` | 233 | Does 10+ sequential operations: template loading, SEO, assets, WC content, customizer CSS, bridge data, auth nonces, plugin hooks, security headers, URL resolution |
| `Template_Loader` | 123 | Template resolution + pack system — already separate ✅ |
| `SEO_Engine` | 340 | SEO meta injection — already separate ✅ |
| `Asset_Loader` | 190 | CSS/JS/fonts/images/CDN injection — separate but not Engine-shaped |
| `Security_Headers` | 31 | CSP + security headers — standalone class |
| `WooCommerce_Injector` | 426 | WC content — left intact for Phase 5 (Plugin Bridges) |

## 3. Target Architecture

```
Render_Engine (coordinator, < 100 lines)
 ├── Data_Engine (NEW)   — resolved product/post/category state
 ├── View_Engine (NEW)   — template loading + HTML manipulation pipeline
 ├── SEO_Engine (existing) — unchanged
 ├── Asset_Engine (refactored) — CSS/JS/fonts/headers (Security_Headers merged in)
 └── WooCommerce_Injector (existing, untouched)
```

### 3.1 Data_Engine (`includes/Engine/Data_Engine.php`)

**Responsibility:** Holds resolved state for the current request. Future: WP/WC query methods.

```
get_resolved_product_id(): ?int
get_resolved_post_id(): ?int
get_category_slug(): ?string
with_product_id(int): self
with_post_id(int): self
with_category(string): self
```

**Constructor:** No dependencies (standalone).

**Testability:** Fully testable — no WP/WC calls in initial version. Set state, read state, verify.

### 3.2 View_Engine (`includes/Engine/View_Engine.php`)

**Responsibility:** Loads HTML templates, applies all HTML transformations, orchestrates sub-injectors.

```
__construct(Template_Loader, SEO_Engine, Asset_Engine, EventDispatcher)
resolve(string): string                    — delegates to Template_Loader
load(string): string                       — delegates to Template_Loader
prepare_html(string): string               — dark mode, skip link, loading state, ARIA roles
inject_seo(string, string): string         — delegates to SEO_Engine
inject_assets(string, string, bool): string — delegates to Asset_Engine
inject_customizer_css(string): string
inject_bridge_data(string, Data_Engine): string
inject_auth_nonces(string): string
inject_plugin_hooks(string): string
inject_customizer_preview(string): string
```

**Constructor deps:** Template_Loader, SEO_Engine, Asset_Engine, EventDispatcher.

**Methods moved from Render_Engine:**
- `inject_customizer_css()` — unchanged
- `inject_bridge()` — renamed to `inject_bridge_data()`, now receives Data_Engine for state
- `inject_auth_nonces()` — unchanged
- The three `do_action` blocks → `inject_plugin_hooks()`
- Customizer preview block → `inject_customizer_preview()`

### 3.3 Asset_Engine (`includes/Engine/Asset_Engine.php` — extends Asset_Loader)

**Responsibility:** All CSS/JS/fonts/images/CDN/security header injection.

```
__construct(Security_Headers)
inject_all(string, string, bool): string   — same contract as Asset_Loader
send_security_headers(bool): void          — delegates to Security_Headers::send()
```

Security_Headers (in `includes/Engine/Security_Headers.php`) is kept as a focused class. Asset_Engine takes it as constructor dependency and exposes `send_security_headers()` as its public API. Asset_Loader code is copied into Asset_Engine; Asset_Loader.php is deleted.

### 3.4 Render_Engine (rewritten — < 100 lines)

```
__construct(View_Engine, Data_Engine, Asset_Engine, WooCommerce_Injector)

render(string $slug): string {
    $is_customizer = isset($_GET['customize_changeset_uuid']);
    $html = $this->view->resolve_and_load($slug);
    if (empty($html)) { status_header(404); return ''; }
    
    $html = $this->view->prepare_html($html);
    $html = $this->wc_injector->inject($html, $slug);
    $html = $this->view->inject_seo($html, $slug);
    $html = $this->view->inject_assets($html, $slug, $is_customizer);
    $html = $this->view->inject_customizer_css($html);
    $html = $this->view->inject_bridge_data($html, $this->data);
    $html = $this->view->inject_auth_nonces($html);
    $html = $this->view->inject_plugin_hooks($html);
    
    if ($is_customizer) {
        $html = $this->view->inject_customizer_preview($html);
    }
    
    $this->assets->send_security_headers($is_customizer);
    return $html;
}
```

## 4. File Changes

| Action | File | Est. Lines |
|--------|------|-----------|
| **CREATE** | `includes/Engine/Data_Engine.php` | ~50 |
| **CREATE** | `includes/Engine/View_Engine.php` | ~180 |
| **CREATE** | `includes/Engine/Asset_Engine.php` | ~200 |
| **REWRITE** | `includes/Engine/Render_Engine.php` | ~90 (was 233) |
| **UPDATE** | `includes/Engine/Container_Config.php` | +8 |
| **UPDATE** | `phantom-core.php` | update requires |
| **UPDATE** | `templates/shell.php` | verify imports |
| **UPDATE** | `tests/bootstrap.php` | add new engine requires |
| **CREATE** | `tests/Data_Engine_Test.php` | ~30 |
| **CREATE** | `tests/View_Engine_Test.php` | ~50 |
| **CREATE** | `tests/Asset_Engine_Test.php` | ~30 |
| **DELETE** | `includes/Engine/Asset_Loader.php` | — (code moved to Asset_Engine) |

## 5. Backward Compatibility

- `Asset_Loader` → `Asset_Engine`: The `inject_all()` method keeps the same signature. Only the class name changes. Container_Config and View_Engine are updated.
- `Security_Headers::send()` → `Asset_Engine::send_security_headers()`: Same behavior, delegates to Security_Headers internally.
- Data_Engine replaces the `$resolved_product_id`, `$resolved_post_id`, `$category_slug` properties on Render_Engine. All setter/getter method signatures preserved.
- Render_Engine's `render()` output is identical — no functional changes.
- WooCommerce_Injector untouched.
- No HTML template changes.

## 6. Testing Strategy

### Data_Engine_Test
- Set product_id, read it back
- Set post_id, read it back
- Set category slug, read it back
- Chain setters (with_product_id()->with_post_id())
- Null defaults before setting

### View_Engine_Test
- Test `prepare_html()` adds skip link
- Test `inject_auth_nonces()` adds script tag
- Test `inject_customizer_css()` adds style tag (with stubs)
- Test `inject_plugin_hooks()` returns HTML unchanged (no hooks in test env)

### Asset_Engine_Test
- Test `send_security_headers()` sends Content-Type header
- Test `inject_lazy_loading()` adds script tag (private → test via inject_all)
- Test images injection replaces logo placeholder

### Integration (existing tests)
- Container_Test still passes (Container unchanged)
- EventDispatcher_Test still passes (EventDispatcher unchanged)
- All 40+ existing tests still pass

## 7. Success Criteria

- [ ] Data_Engine: get/set chained state, null defaults
- [ ] View_Engine: all HTML pipeline methods produce correct output
- [ ] Asset_Engine: inject_all() + send_security_headers() work
- [ ] Render_Engine: < 100 lines, delegates to engines
- [ ] All 40+ existing PHPUnit tests pass
- [ ] All PHP files pass `php -l` syntax check
- [ ] Container_Config correctly wires all new dependencies

## 8. Revision History

| Date | Version | Changes |
|------|---------|---------|
| 2026-07-27 | 1.0 | Initial spec based on Phase 2 master plan + code audit |
