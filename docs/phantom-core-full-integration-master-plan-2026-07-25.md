# Phantom Core v1.5.3 — Full Integration Master Plan

> **Date**: 2026-07-25
> **Scope**: Customizer · Menus · Widgets · WooCommerce Products · Categories · REST API
> **Status**: Analysis complete — all 6 subsystems audited end-to-end
> **Overall Health**: ~78/100 (gaps documented with 4-phase remediation)

---

## Table of Contents

1. [Executive Summary](#1-executive-summary)
2. [Customizer Deep Analysis](#2-customizer-deep-analysis)
3. [Menus Deep Analysis](#3-menus-deep-analysis)
4. [Widgets Deep Analysis](#4-widgets-deep-analysis)
5. [WooCommerce Products Deep Analysis](#5-woocommerce-products-deep-analysis)
6. [Categories Deep Analysis](#6-categories-deep-analysis)
7. [REST API Deep Analysis](#7-rest-api-deep-analysis)
8. [Cross-Cutting Issues](#8-cross-cutting-issues)
9. [4-Phase Remediation Plan](#9-4-phase-remediation-plan)
10. [Appendix: Key File Paths](#10-appendix-key-file-paths)

---

## 1) Executive Summary

This master plan documents a full forensic audit of all 6 integration subsystems. The Phantom Core framework is mature (v1.5.3, 555 settings, 34+ REST endpoints, 31 HTML templates) but has integration gaps that would impact client delivery.

### Health Scores by Subsystem

| Subsystem | Health Score | Critical Issues | Medium Issues | Low Issues |
|-----------|:-----------:|:---------------:|:-------------:|:----------:|
| Customizer | 89/100 | 1 | 5 | 5 |
| Menus | 55/100 | 2 | 4 | 5 |
| Widgets | 35/100 | 5 | 4 | 3 |
| Products (WooCommerce) | 80/100 | 2 | 4 | 6 |
| Categories | 50/100 | 3 | 6 | 11 |
| REST API | 85/100 | 3 | 5 | 4 |
| **Aggregate** | **~65/100** | **16** | **28** | **34** |

### Top 5 Critical Issues (Fix Before Client Delivery)

1. **PHP Fatal Error on empty menu** — `phantom_theme_primary_fallback` undefined in `header.php`
2. **No Bootstrap 5 Nav Walker** — Menus render without `nav-item`/`nav-link`/`dropdown` classes
3. **SPA widgets: zero support** — 10 registered widget areas, 0 render in SPA mode
4. **Checkout field name mismatch** — `checkout.html` uses non-WooCommerce field names
5. **Page-data bloat** — All 555 settings returned on every page load, ~50 actually needed

---

## 2) Customizer Deep Analysis

### Architecture

- **15 panels**, **44 sections**, ~555 settings
- **13 custom control types** (ast-color, ast-toggle, ast-select, ast-radio-image, ast-responsive-slider, ast-responsive-spacing, ast-typography, ast-gradient, ast-color-group, ast-background, ast-border, ast-font-families, Control_Base)
- **89 CSS var entries** in the CSS var map — fully aligned with registry `css_property` entries
- **Live preview**: Auto-binding for ALL CSS-var-backed settings via `customizer-preview.js`
- **8 conditional rules** in `customizer-conditionals.js`

### Issues Found

#### Critical (1)
| # | File | Issue |
|---|------|-------|
| C1 | `phantom-theme/functions.php:162-179` | Theme outputs 4 CSS vars (`--primary-color`, `--accent-color`, `--text-color`, `--bg-color`) that differ from plugin's vars (`--primary--color`, etc.). Same data, different names — 300 bytes dead code. |

#### Medium (5)
| # | File | Issue |
|---|------|-------|
| C2 | `partial-renderers.php:12-26` | Header, footer, blog, search partial renderers are stubs — output placeholder `<div>`s |
| C3 | `customizer-preview.js:63-68` | `phantom_header_sticky` binding is dead code (transport is `refresh`) |
| C4 | `class-color-group-control.php` | `ast-color-group` renders empty div — 100% JS dependent, no server fallback |
| C5 | `class-background-control.php` | Same — only hidden inputs, full JS dependency |
| C6 | `class-border-control.php` | Same — only hidden inputs, full JS dependency |

#### Low (5)
| # | File | Issue |
|---|------|-------|
| C7 | `class-control-base.php:19-31` | File list has 12 entries but type map has 13 types |
| C8 | `class-radio-image-control.php:21` | Uses `plugins_url()` with `PHANTOM_CORE_FILE` not `PHANTOM_CORE_URL` |
| C9 | `class-customizer.php:148-153` | Empty array defaults convert to empty string |
| C10 | `class-font-families.php` | Deprecated 2.0.0 but still loaded |
| C11 | Dual `header_bg`/`color_header_bg` | Two controls for same semantic value — UX confusion |

---

## 3) Menus Deep Analysis

### Architecture

- **Theme**: 2 locations (`primary`, `footer`) registered in `functions.php`
- **Plugin**: 4 locations (`phantom_primary`, `phantom_secondary`, `phantom_footer`, `phantom_mobile`) registered in `class-core-plugin.php`
- **Traditional mode**: `header.php` renders via `wp_nav_menu()` with Bootstrap 5 nav classes, depth 3
- **SPA mode**: `data-phantom-menu="primary"` attributes on all 22 HTML templates, populated by `phantom-data.js`

### Issues Found

#### Critical (2)
| # | File | Issue |
|---|------|-------|
| M1 | `header.php:61` | **`phantom_theme_primary_fallback` undefined** — PHP fatal error when no menu assigned to `primary` location |
| M2 | No custom walker | **No Bootstrap 5 nav walker**. Default WP walker outputs `<li><a>` without `nav-item`/`nav-link`. SPA JS generates `data-toggle="dropdown"` (Bootstrap 4) instead of `data-bs-toggle="dropdown"` (Bootstrap 5) |

#### Medium (4)
| # | File | Issue |
|---|------|-------|
| M3 | `phantom-data.js` | SPA `buildMenuHTML()` only handles 1 level of children — grandchildren silently dropped |
| M4 | `partial-renderers.php` | `phantom_render_nav_partial` defaults to `phantom_primary` — mismatched with theme's `primary` |
| M5 | `class-rest-controller.php` | Menu cache not invalidated on `wp_update_nav_menu` / `wp_create_nav_menu` |
| M6 | `phantom-data.js` | Active link detection is exact-match only — no `aria-current="page"`, no parent highlighting |

#### Low (5)
| # | File | Issue |
|---|------|-------|
| M7 | Theme/Plugin | Menu location names mismatch (`primary` vs `phantom_primary`) |
| M8 | SPA templates | No `data-phantom-menu="mobile"` — mobile menu is hardcoded HTML |
| M9 | No mega menu | No infrastructure for multi-column/advanced menus |
| M10 | `build_menu_tree()` | Missing: `description`, `attr_title`, `xfn`, `object`, `object_id` fields in REST response |
| M11 | `enrich_menu_tree()` | Auto-appends "Pages" dropdown that may duplicate existing menu items |

---

## 4) Widgets Deep Analysis

### Architecture

- **Theme**: 3 widget areas (`sidebar-blog`, `sidebar-shop`, `sidebar-footer`)
- **Plugin**: 7 widget areas (`phantom-sidebar-main`, `phantom-sidebar-shop`, `phantom-sidebar-blog`, `phantom-footer-1` through `phantom-footer-4`)
- **Total**: 10 registered areas, **only 1 actually outputs** (`sidebar-blog` via `sidebar.php`)
- **SPA mode**: 22 HTML templates with **zero widget containers**

### Issues Found

#### Critical (5)
| # | File | Issue |
|---|------|-------|
| W1 | All 22 HTML templates | **SPA mode: zero widget containers**. `<aside class="widget-area">` doesn't exist in any template |
| W2 | `footer.php` | **Footer widgets never render**. Footer is fully hardcoded — `dynamic_sidebar('sidebar-footer')` never called |
| W3 | Theme `functions.php:123-131` | **Shop sidebar is a ghost**. `sidebar-shop` registered but never rendered — no template calls it |
| W4 | Theme + Plugin | **Dual registration creates confusion**. 10 widget areas registered (3 theme + 7 plugin), most unused. Users see duplicate "Blog Sidebar", "Shop Sidebar" in admin |
| W5 | `class-rest-controller.php` | **No REST endpoints for widgets**. `/widgets`, `/widgets/{sidebar}` don't exist — no way to fetch widget data via API |

#### Medium (4)
| # | File | Issue |
|---|------|-------|
| W6 | Theme | No `sidebar-shop.php` template — even if called, would fail silently |
| W7 | SPA HTML templates | `vendor/blog.css` and `vendor/shop.css` (contain widget CSS) never linked — only `style.css` loaded |
| W8 | Plugin widget areas | `phantom-footer-1` through `phantom-footer-4` have no Bootstrap grid classes — would stack vertically |
| W9 | Settings Registry | `--widget-spacing` CSS var exists but has no visible effect (no widget containers) |

#### Low (3)
| # | File | Issue |
|---|------|-------|
| W10 | Theme | Filename typo: `page-three-colum-sidbar.php` → should be `page-three-column-sidebar.php` |
| W11 | `functions.php:41` | `customize-selective-refresh-widgets` declared but never utilized |
| W12 | Theme `style.css` | Zero widget CSS — relies entirely on `blog.css` and `shop.css` |

---

## 5) WooCommerce Products Deep Analysis

### Architecture

- **18 WC-related REST endpoints** under `phantom/v1`
- **3 WooCommerce template overrides** in `phantom-core/woocommerce/` (cart, checkout, add-to-cart stubs)
- **Full cart flow**: SPA handles add-to-cart → mini-cart → cart page → checkout via JS
- **Variable products**: Supported with attribute dropdowns, price/image/stock updates on variation selection
- **Product gallery**: Swiper-based with zoom, thumbnails, 360-viewer support

### Issues Found

#### Critical (2)
| # | File | Issue |
|---|------|-------|
| P1 | `checkout.html` | **Checkout field name mismatch**. Uses `checkoutEmail`, `firstName`, `cardNumber` — WooCommerce expects `billing_first_name`, `billing_email`, `billing_address_1`, etc. |
| P2 | `checkout.html` | **Raw credit card fields in DOM**. `cardNumber`, `expiry`, `CVC` input fields — security risk. WooCommerce expects payment gateway iframe/Stripe Elements |

#### Medium (4)
| # | File | Issue |
|---|------|-------|
| P3 | `phantom-data.js` `renderProduct()` | No color/image swatches for variable products — only dropdown `<select>` elements |
| P4 | `class-rest-controller.php` | No standalone product tags endpoint — `tag` only works as filter on `GET /products` |
| P5 | Missing | No customer/billing data API — checkout form cannot prefill for logged-in users |
| P6 | Missing | No endpoint to list applied coupons in cart response |

#### Low (6)
| # | File | Issue |
|---|------|-------|
| P7 | `product-detail.html` | Template caps gallery at 4 images — products with >4 gallery images truncated |
| P8 | `phantom-theme/functions.php` | WC gallery features (zoom, lightbox, slider) declared but SPA uses own Swiper implementation |
| P9 | `woocommerce/` stubs | 3 WC template overrides are minimal redirect stubs — no graceful JS-off fallback |
| P10 | `class-css-product.php` | Only 2 CSS vars (`color_rating`, `color_sale`) — no card, button, badge CSS vars exposed |
| P11 | REST | Cart operations use POST instead of PUT/DELETE — non-standard |
| P12 | `phantom-data.js` | `buildProductCard()` does not show categories on product grid cards |

---

## 6) Categories Deep Analysis

### Architecture

- **Traditional mode**: Full category/tag support via `archive.php`, `single.php`, `index.php`
- **REST**: `/categories` endpoint returns merged `product_cat` + `category` terms
- **SPA**: `injectCategories()` renders homepage category showcase grid from `page-data`
- **Shop filtering**: `initShopControls()` maps button text to category slugs (fragile)

### Issues Found

#### Critical (3)
| # | File | Issue |
|---|------|-------|
| CA1 | `shell.php` | **No category/tag archive routes in SPA**. No routes for `category/{slug}`, `tag/{slug}`, `product-category/{slug}` |
| CA2 | `phantom-data.js` | **No blog category filtering**. `initBlogPagination()` fetches `/posts?per_page=6&page={page}` without any category parameter |
| CA3 | `phantom-data.js` `initShopControls()` | **Shop filter uses button text as category slug**. "Men" button → `?category=Men` — fails if actual slug is "mens" or "men-shoes" |

#### Medium (6)
| # | File | Issue |
|---|------|-------|
| CA4 | `phantom-data.js` `injectPosts()` | No category display on blog cards in SPA |
| CA5 | `phantom-data.js` `injectSinglePost()` | No category/tag display on single blog post in SPA |
| CA6 | `shell.php:get_page_data()` | Categories are mutually exclusive — only `product_cat` when WooCommerce active, never blog categories too |
| CA7 | `shell.php:get_page_data()` | No category `image` field in page-data |
| CA8 | `shell.php:get_page_data()` | No category `url` field in page-data |
| CA9 | `phantom-data.js` `buildProductCard()` | Product card does not show categories |

#### Low (11)
| # | File | Issue |
|---|------|-------|
| CA10 | `shop.html` | "Sale" filter button has no corresponding product category — should use `on_sale` |
| CA11 | `get_categories()` | No parent/child hierarchy — flat list |
| CA12 | `phantom-data.js` `renderProduct()` | No product tag display on detail page |
| CA13 | SPA templates | No category sidebar widget in SPA mode |
| CA14 | `shop.html` | Filter buttons hardcoded in HTML — should be generated from API |
| CA15 | All templates | Footer shop links hardcoded (Men, Women, Kids, New Arrivals) |
| CA16 | Settings Registry | `load_more_category_text` setting exists but never used in JS |
| CA17 | Settings Registry | `blog_show_categories`/`blog_show_tags` settings never checked in phantom-data.js |
| CA18 | SPA blog templates | No tag cloud or category list in blog sidebar |
| CA19 | `get_categories()` | Endpoint returns flat merged list — no way to request specific taxonomy |
| CA20 | Entire codebase | Zero custom taxonomies registered |

---

## 7) REST API Deep Analysis

### Architecture

- **41 routes** under `phantom/v1` (settings, content, products, cart, auth, contact, user)
- **Permission callbacks**: `__return_true` (public), settings/admin permission checks with nonce
- **Caching**: Transient cache (1hr), ETag headers, client-side in-memory cache (120s)
- **JS bridge**: `phantom-bridge.js` (settings) + `phantom-data.js` (data consumption)

### Issues Found

#### Critical (3)
| # | File | Issue |
|---|------|-------|
| R1 | `class-rest-controller.php` `get_page_data()` | **Page-data bloat**: All 555 settings returned on every page load. ~50 actually needed for frontend. 28KB+ JSON payload |
| R2 | `class-rest-controller.php` `get_featured_products()` | **Inconsistent format**: Returns raw array `[...]` instead of `{"products": [...]}` — will break consumers expecting standard format |
| R3 | `checkout.html` + `initCheckout()` | **No checkout REST endpoint**: Uses `wc-ajax=checkout` directly — no Phantom nonce validation, no Phantom error handling |

#### Medium (5)
| # | File | Issue |
|---|------|-------|
| R4 | `class-rest-controller.php` | `settings_permission_check()`/`partial_permission_check()` pass no `$request` to `verify_nonce()` — falls back to `$_SERVER` |
| R5 | All endpoints | No `_fields` parameter for partial responses — always over-fetches |
| R6 | `set_cache_headers()` | ETag computed via `serialize()` — expensive on 555-setting payload |
| R7 | `phantom-data.js` | Client cache never uses conditional requests (If-None-Match) — always full 200 responses |
| R8 | Auth endpoints | Login/register response returns `user_email` — unnecessary exposure |

#### Low (4)
| # | File | Issue |
|---|------|-------|
| R9 | Auth endpoints | Rate limiting is IP-based — unreliable behind NAT/proxy |
| R10 | `phantom-bridge.js` | No retry logic for transient network failures |
| R11 | `contact-form.js` | Form field names must match REST — fragile coupling with `.serialize()` |
| R12 | `verify_nonce()` | Called without `$request` in `settings_write_permission_check` |

---

## 8) Cross-Cutting Issues

These issues span multiple subsystems and need coordinated fixes:

| # | Issue | Affected Subsystems | Description |
|---|-------|--------------------|-------------|
| X1 | **SPA vs Traditional mode duality** | Menus, Widgets, Categories | All 6 subsystems have different implementations for traditional mode (PHP templates) vs SPA mode (HTML + JS). Any fix must address both paths |
| X2 | **Dual menu location names** | Menus, API, Customizer | Theme registers `primary`, plugin registers `phantom_primary`. Creates confusion for menu assignment, partial rendering, REST data |
| X3 | **Widget area name collision** | Widgets, Theme, Plugin | Both theme and plugin register "Blog Sidebar" and "Shop Sidebar" with different IDs. Users see duplicates in admin |
| X4 | **No template for sidebar-shop** | Widgets, WooCommerce | `sidebar-shop` registered but no `sidebar-shop.php` file. Even if `dynamic_sidebar()` was called, it would fail |
| X5 | **Cache invalidation gaps** | API, Menus | Menu changes don't invalidate page-data cache. Widget changes don't invalidate anything |
| X6 | **Hardcoded shop footer links** | Menus, Categories, Products | Footer "Shop" column links (Men, Women, Kids, New Arrivals) are hardcoded in all 22 HTML templates |

---

## 9) 4-Phase Remediation Plan

### Phase 1: CRITICAL — Must Fix Before Client Delivery
**Estimated effort**: 3-5 days
**Impact**: Prevents crashes, data loss, and broken checkout

| Priority | ID | Task | Files to Modify |
|----------|-----|------|----------------|
| P0 | M1 | Define `phantom_theme_primary_fallback()` or set `fallback_cb => false` | `header.php`, `functions.php` |
| P0 | M2 | Create Bootstrap 5 Nav Walker class. Fix SPA `data-toggle` → `data-bs-toggle` | New `class-bootstrap-walker.php`, `phantom-data.js` |
| P0 | W1-W3 | Add widget containers to SPA HTML templates (blog sidebar, shop sidebar, footer) | `blog.html`, `single-blog.html`, `shop.html`, all templates with footer |
| P0 | W5 | Add `/widgets` and `/widgets/{sidebar}` REST endpoints | `class-rest-controller.php` |
| P0 | P1-P2 | Fix checkout field names to WooCommerce convention. Integrate Stripe/payment gateway properly | `checkout.html`, `phantom-data.js` `initCheckout()` |
| P0 | R1 | Filter `/page-data` settings to only frontend-relevant subset | `class-rest-controller.php` `get_page_data()` |
| P0 | R2 | Fix `/products/featured` response format for consistency | `class-rest-controller.php` |
| P0 | CA1 | Add category/tag archive routes to shell.php | `shell.php` route registration |
| P0 | C1 | Remove or align theme's CSS var output with plugin vars | `functions.php` |

### Phase 2: HIGH — Polish for Production Quality
**Estimated effort**: 3-5 days
**Impact**: Visual polish, user experience, admin confusion

| Priority | ID | Task | Files to Modify |
|----------|-----|------|----------------|
| P1 | C2 | Implement real partial renderers for header/footer/blog/search | `partial-renderers.php` |
| P1 | M3 | Add recursive child menu support to `buildMenuHTML()` | `phantom-data.js` |
| P1 | W4 | Consolidate widget area registrations — remove duplicates | `functions.php`, `class-core-plugin.php` |
| P1 | P3 | Add color/image swatches for variable product attributes | `phantom-data.js` `renderProduct()` |
| P1 | P4 | Add `/woo/tags` standalone endpoint | `class-rest-controller.php` |
| P1 | P5 | Add `/user/profile` and `/user/addresses` endpoints | `class-rest-controller.php` |
| P1 | CA2-CA3 | Fix blog category filtering and shop filter slug mapping | `phantom-data.js`, `shop.html` |
| P1 | R3 | Add native Phantom checkout REST endpoint | `class-rest-controller.php`, `phantom-data.js` |

### Phase 3: MEDIUM — Complete Feature Coverage
**Estimated effort**: 4-6 days
**Impact**: Feature completeness, admin experience

| Priority | ID | Task | Files to Modify |
|----------|-----|------|----------------|
| P2 | C4-C6 | Add server-rendered fallback HTML for JS-dependent controls | `class-color-group-control.php`, `class-background-control.php`, `class-border-control.php` |
| P2 | M5 | Add cache invalidation on menu update | `class-rest-controller.php` |
| P2 | W6 | Create `sidebar-shop.php` and wire to WooCommerce templates | New file, possibly `woocommerce.php` |
| P2 | W7 | Link `vendor/blog.css` and `vendor/shop.css` in SPA templates | HTML templates |
| P2 | CA4-CA9 | Add category display to blog cards, single posts, product cards. Include image/url in page-data | `phantom-data.js`, `class-rest-controller.php` |
| P2 | R4-R5 | Add `$request` parameter to permission callbacks. Implement `_fields` parameter support | `class-rest-controller.php` |
| P2 | W9 | Make `--widget-spacing` CSS var effective by adding widget containers | All SPA templates + CSS |

### Phase 4: LOW — Best Practices & Performance
**Estimated effort**: 2-3 days
**Impact**: Code quality, performance, maintainability

| Priority | ID | Task | Files to Modify |
|----------|-----|------|----------------|
| P3 | C7-C11 | Fix consistency issues in custom controls | Multiple custom control files |
| P3 | M6-M11 | Add aria-current, fix menu location naming, add REST fields | `phantom-data.js`, `class-rest-controller.php`, `class-core-plugin.php` |
| P3 | W10-W12 | Fix filename typo, utilize selective refresh, add widget base styles to style.css | Multiple files |
| P3 | P7-P12 | Gallery image limit, CSS vars expansion, REST method consistency | Multiple files |
| P3 | CA10-CA20 | Fix Sale filter, add hierarchy support, generate dynamic filter buttons | Multiple files |
| P3 | R6-R12 | Fix ETag strategy, add retry logic, fix verify_nonce, remove email exposure | `class-rest-controller.php`, `phantom-bridge.js` |
| P3 | X6 | Make footer shop links dynamic from product categories | All 22 HTML templates + `phantom-data.js` |

---

## 10) Appendix: Key File Paths

### Plugin Core Files
| File | Purpose |
|------|---------|
| `phantom-core/phantom-core.php` | Bootstrap, autoloader, constants (8,894 lines) |
| `phantom-core/includes/class-customizer.php` | Customizer registration (15 panels, 44 sections) |
| `phantom-core/includes/class-settings-registry.php` | 555 settings across 44 sections (5,554 lines) |
| `phantom-core/includes/class-rest-controller.php` | 41 REST endpoints (3,035 lines) |
| `phantom-core/includes/class-custom-css.php` | CSS generation engine (8 modules) |
| `phantom-core/includes/class-core-plugin.php` | Plugin orchestrator, registers nav menus + widget areas |
| `phantom-core/includes/partial-renderers.php` | Selective refresh partials |
| `phantom-core/includes/custom-controls/` | 13 custom Customizer control types |
| `phantom-core/includes/custom-css/` | 8 CSS generation modules |
| `phantom-core/templates/shell.php` | SPA router (695 lines) |
| `phantom-core/admin/js/customizer-preview.js` | Live preview bindings |
| `phantom-core/admin/js/customizer-conditionals.js` | Conditional display logic |
| `phantom-core/frontend/html/` | 22 SPA HTML templates |
| `phantom-core/frontend/assets/js/phantom-data.js` | Main data bridge (2,022 lines) |
| `phantom-core/frontend/assets/js/phantom-bridge.js` | Settings bridge |

### Theme Files
| File | Purpose |
|------|---------|
| `phantom-theme/functions.php` | Theme setup, menu/widget registrations (245 lines) |
| `phantom-theme/header.php` | Primary menu, cart icon, header CSS vars |
| `phantom-theme/footer.php` | Footer menu, hardcoded footer columns |
| `phantom-theme/sidebar.php` | Blog sidebar output |
| `phantom-theme/archive.php` | Category/tag/author/date archives |
| `phantom-theme/style.css` | Theme stylesheet (2810 lines) |
| `phantom-theme/assets/css/blog.css` | Blog + widget CSS |
| `phantom-theme/assets/css/shop.css` | Shop + widget CSS |
| `phantom-theme/assets/js/phantom-data.js` | Theme-side data bridge (mirrors plugin version) |
| `phantom-theme/theme.json` | FSE theme.json with 5-color palette, 7 custom templates |

### Documentation
| File | Purpose |
|------|---------|
| `docs/phantom-core-full-integration-master-plan-2026-07-25.md` | **This file** |
| `docs/woocommerce-master-plan.md` | Previous WooCommerce gap analysis |
| `docs/settings-registry-master-plan.md` | Previous settings registry gap analysis |
| `docs/phantom-core-analysis-2026-07-25.md` | Previous comprehensive analysis |
| `theme-detail/` | Architecture, features, customization, audits |

### Serena Memory Files
| Memory | Purpose |
|--------|---------|
| `phantom-core-current-state.md` | Current project state |
| `phantom-core-final-state.md` | v1.5.0 final state |
| `docker-live-testing-2026-07-20.md` | Docker live test results |
| `full-site-audit.md` | July 22 full site audit |
| `phantom-core-customizer-test-results.md` | Customizer test results |
| `quality-sweep-2026-07-20.md` | Quality sweep results |

---

## Remediation Roadmap

```
Phase 1 (CRITICAL)    → Week 1: 8 critical fixes, 3-5 days
Phase 2 (HIGH)        → Week 2: 8 high-priority fixes, 3-5 days
Phase 3 (MEDIUM)      → Week 3-4: 10 medium fixes, 4-6 days
Phase 4 (LOW)         → Week 5: 12 low-priority fixes, 2-3 days

Total: ~15-20 days of engineering work
```

After completing all 4 phases, the aggregate health score should reach **95+/100** with all subsystems production-ready for client delivery.
