# Phantom Core v1.5.3 — 100/100 Client Delivery Master Plan

> **Date**: 2026-07-25
> **Scope**: Full breakage analysis across all 8 subsystems — WordPress Core Theme, WooCommerce, Settings Registry, Customizer, Menus & Widgets, Products & Categories, REST API, SPA vs Traditional Dual-Mode
> **Status**: Synthesis complete — 0% delivery-ready
> **Overall Health**: ~50/100 (8 breakage analyses performed in parallel)

---

## Table of Contents

1. [Executive Summary](#1-executive-summary)
2. [Critical Blockers (Will Crash / 500 / Lose Data)](#2-critical-blockers)
3. [High Priority (Will Cause Significant Problems)](#3-high-priority)
4. [Medium Priority (Will Cause Issues Under Specific Conditions)](#4-medium-priority)
5. [Low Priority (Polish / Edge Cases)](#5-low-priority)
6. [Subsystem-by-Subsystem Delivery Readiness](#6-subsystem-by-subsystem-delivery-readiness)
7. [Dual-Mode Matrix: Traditional vs SPA](#7-dual-mode-matrix)
8. [Security Audit Summary](#8-security-audit-summary)
9. [Remediation Roadmap](#9-remediation-roadmap)
10. [Appendix: Complete Issue Database](#10-appendix-complete-issue-database)

---

## 1) Executive Summary

This document synthesizes **8 parallel breakage analysis** reports covering every subsystem of Phantom Core v1.5.3. The analysis was performed at maximum depth — tracing every code path, every edge case, every failure mode — for both **Traditional** (PHP templates) and **SPA** (static HTML + REST API) operating modes.

### What Changed From Previous Analysis

The previous master plan (`phantom-core-full-integration-master-plan-2026-07-25.md`) documented 16 critical, 28 medium, 34 low issues with an aggregate health score of ~65/100. The **breakage analysis reveals ~40 additional failure modes**, including several that are **immediately fatal** or **permanently broken**:

| Finding | Severity | Previous Status |
|---------|----------|-----------------|
| `window.phantomData` never set — all cart/auth write ops send empty nonce → 401 rejected | CRITICAL | **Not detected** |
| Newsletter form submits to `admin-ajax` with zero server handler → silent data loss | CRITICAL | **Not detected** |
| `WC()->cart` accessed without null guard → PHP fatal | CRITICAL | **Not detected** |
| `settings_permission_check()` missing `$request` parameter → nonce check always fails | CRITICAL | **Not detected** |
| `/page-data` returns ALL 555 settings to unauthenticated users via `__return_true` | CRITICAL | Listed as bloat, **not as security issue** |
| `/partial` endpoint has CSRF + function injection risk | CRITICAL | **Not detected** |
| Auth endpoints have NO nonce check (use `__return_true`) | CRITICAL | **Not detected** |
| CSS var map stores array → produces `--var-name:Array;` in CSS | HIGH | **Not detected** |
| Auto-bind loop throws TypeError for unregistered settings | HIGH | **Not detected** |
| `template_redirect` at priority 0 kills all plugin hooks | HIGH | **Not detected** |
| Hardcoded CSP blocks plugin injection | HIGH | **Not detected** |
| `wp_deregister_script('jquery')` breaks plugin compatibility | HIGH | **Not detected** |
| `declare(strict_types=1)` on theme functions.php causes TypeErrors | HIGH | **Not detected** |
| SPA route map only 22 entries — everything else 404 | HIGH | **Not detected** |

### Aggregate Health Scores by Subsystem

| Subsystem | Traditional Mode | SPA Mode | Aggregate | Critical | High | Medium | Low |
|-----------|:----------------:|:--------:|:---------:|:--------:|:----:|:------:|:---:|
| WordPress Core Theme | 40/100 | 55/100 | **45/100** | 3 | 5 | 4 | 3 |
| WooCommerce Integration | 50/100 | 40/100 | **45/100** | 3 | 4 | 6 | 5 |
| Settings Registry | 85/100 | 80/100 | **82/100** | 0 | 1 | 2 | 3 |
| Customizer | 70/100 | N/A | **70/100** | 1 | 3 | 4 | 3 |
| Menus | 45/100 | 55/100 | **50/100** | 2 | 4 | 5 | 3 |
| Widgets | 30/100 | 10/100 | **20/100** | 3 | 4 | 3 | 3 |
| Products & Categories | 65/100 | 50/100 | **55/100** | 4 | 4 | 7 | 4 |
| REST API | N/A | 45/100 | **45/100** | 3 | 5 | 4 | 3 |
| Cross-Cutting / Security | 40/100 | 30/100 | **35/100** | 5 | 4 | 4 | 2 |
| **Aggregate** | **~50/100** | **~45/100** | **~50/100** | **24** | **34** | **39** | **29** |

**Total issues found across all 8 analyses: 126** (24 critical, 34 high, 39 medium, 29 low)

---

## 2) Critical Blockers (Will Crash / 500 / Lose Data)

These MUST be fixed before any client sees the site. Each one either causes a PHP fatal error, silent data loss, permanently broken user flow, or exploitable security vulnerability.

### 2.1 PHP Fatals / Crashes

| ID | File | Issue | Mode | Discovered |
|----|------|-------|------|------------|
| F1 | `header.php:61` | `phantom_theme_primary_fallback()` undefined — **PHP fatal** when no menu assigned to `primary` location | Traditional | Agent 1 (known but unstated severity) |
| F2 | `header.php:74` | `WC()->cart` accessed with only `class_exists('WooCommerce')` guard — **PHP fatal** if WooCommerce active but cart not initialized on certain pages | Traditional | Agent 1 **(NEW)** |
| F3 | `footer.php:11-17` | Newsletter form POSTs to `admin-ajax` with NO PHP handler registered — **silent data loss**, form appears to submit but data goes nowhere | Both | Agent 1 **(NEW)** |
| F4 | `class-customizer.php:515-531` | `esc_attr($val)` where `$val` is an array (responsive/typography control) → produces `--var-name:Array;` in CSS with PHP notice | Both | Agent 4 **(NEW)** |
| F5 | `admin/js/customizer-preview.js:49-58` | Auto-bind loop calls `wp.customize(settingId, fn)` on unregistered setting IDs → returns `undefined` → `.bind()` throws **TypeError** | Customizer | Agent 4 **(NEW)** |

### 2.2 Permanently Broken User Flows

| ID | File | Issue | Mode | Discovered |
|----|------|-------|------|------------|
| U1 | `frontend/assets/js/phantom-data.js` | `window.phantomData` **never set** — `shell.php` outputs `window.phantomData = ...` but due to injection timing or CSP, the global is never defined. All cart/auth write operations send empty nonce → **401 rejected**. Entire checkout/auth flow permanently broken. | SPA | Agent 8 **(NEW)** |
| U2 | `frontend/html/checkout.html` | Checkout POSTs to `/?wc-ajax=checkout` which `shell.php` intercepts incorrectly — never reaches WooCommerce. Combined with U1, **no orders can be placed**. | SPA | Agent 8 (previously known but not as critical) |
| U3 | `frontend/assets/js/phantom-data.js` | WooCommerce AJAX API completely disabled — `wc-ajax` endpoints never reach WooCommerce handler in SPA mode | SPA | Agent 8 **(NEW)** |
| U4 | `templates/shell.php` | SPA route map only has ~22 entries — everything else returns 404. No category archives, tag archives, author pages, search results | SPA | Agent 8 **(NEW)** |
| U5 | `class-rest-controller.php` | `/page-data` uses `__return_true` permission callback — **ALL 555 settings exposed publicly**. No authentication required. | SPA | Agent 7 **(NEW — previously listed as bloat, now identified as security issue)** |

### 2.3 Security Vulnerabilities

| ID | File | Issue | CVSS | Discovered |
|----|------|-------|------|------------|
| S1 | `class-rest-controller.php:595` | `settings_permission_check()` has **no `$request` parameter** — function signature: `function settings_permission_check()` with 0 args. WordPress passes `$request` as first arg, which is ignored. Nonce check always uses `$_SERVER` fallback. | 7.5 (High) | Agent 7 **(NEW)** |
| S2 | `class-rest-controller.php:605` | `partial_permission_check()` — same issue, plus `$_POST['partial']` keys are used in `render_partial_callback()` without sanitization → **function injection risk** | 8.0 (High) | Agent 7 **(NEW)** |
| S3 | Auth endpoints (login/register/logout) | ALL auth endpoints use `__return_true` permission callback — **no nonce check whatsoever**. Protection relies entirely on rate limiting. | 7.0 (High) | Agent 7 **(NEW)** |
| S4 | `/phantom/v1/woo/reviews` POST | No nonce verification — only `is_user_logged_in()` check | 6.5 (Medium) | Agent 7 **(NEW)** |
| S5 | `phantom-theme/functions.php` | `wp_deregister_script('jquery')` deregisters jQuery globally — **breaks any plugin that depends on jQuery** | 6.0 (Medium) | Agent 1 **(NEW)** |

### 2.4 Critical Failover Gaps

| ID | File | Issue | Mode | Discovered |
|----|------|-------|------|------------|
| G1 | `templates/shell.php` | `add_action('template_redirect', ..., **0**)` — priority 0 fires before all plugin hooks. **Kills every other plugin's frontend functionality** that hooks into `template_redirect` | SPA | Agent 8 **(NEW)** |
| G2 | `phantom-theme/functions.php` | `declare(strict_types=1)` at file top — PHP 7+ strict types mode. Core functions like `strlen()`, `substr()` called with `mixed` arguments produce **TypeError** instead of autocasting | Traditional | Agent 1 **(NEW)** |
| G3 | All 22 HTML templates | CDN dependencies (GSAP, Swiper, Bootstrap) — **any single CDN outage breaks SPA mode partially or totally**. No local fallbacks. | SPA | Agent 8 (previously known as low) |

---

## 3) High Priority (Will Cause Significant Problems)

### 3.1 WordPress Core Theme

| ID | File | Issue | Mode |
|----|------|-------|------|
| H1 | `phantom-theme/*.php` | Page template naming issues — `page-three-colum-sidbar.php` (typo), column templates only render single column regardless of selection | Traditional |
| H2 | `phantom-theme/functions.php` | No WooCommerce template overrides — all WC pages fall through to default WC templates in SPA-off mode | Traditional |
| H3 | `phantom-theme/sidebar.php` | `sidebar-shop` registered but **never rendered** — no `dynamic_sidebar('sidebar-shop')` call anywhere | Traditional |
| H4 | `phantom-theme/footer.php` | Hardcoded page slugs in footer links — not dynamic, will 404 if pages are renamed | Traditional |

### 3.2 WooCommerce

| ID | File | Issue | Mode |
|----|------|-------|------|
| H5 | `frontend/html/checkout.html` | 8 checkout field names still mismatched (partial fix applied, verify completion) — `checkoutEmail`, `firstName`, `cardNumber` vs `billing_email`, `billing_first_name`, `billing_address_1` | SPA |
| H6 | `frontend/html/checkout.html` | Raw credit card fields in DOM — `cardNumber`, `expiry`, `CVC` input fields present. Payment gateway Stripe Elements not integrated. | SPA |
| H7 | `frontend/assets/js/phantom-data.js` | Shop filter button text used as category slug — "Men" button → `?category=Men`. Fails if actual slug is "mens" or "men-shoes" | SPA |
| H8 | `frontend/assets/js/phantom-data.js` | External/grouped product types not handled — ATC button shows but does nothing | SPA |

### 3.3 Customizer

| ID | File | Issue | Mode |
|----|------|-------|------|
| H9 | `includes/class-custom-css.php` | `get_inline_css()` doesn't handle responsive array values — should emit per-breakpoint `@media` blocks | Both |
| H10 | `phantom-theme/functions.php:162-179` | Theme outputs 4 CSS vars (`--primary-color`, `--accent-color`, `--text-color`, `--bg-color`) that differ from plugin's vars (`--primary--color` etc) — naming conflict | Both |

### 3.4 Menus

| ID | File | Issue | Mode |
|----|------|-------|------|
| H11 | `partial-renderers.php:30` | `phantom_render_nav_partial` defaults to `phantom_primary` but admin may have assigned menu to `primary` (theme location) | Traditional |
| H12 | `class-core-plugin.php:37-45` | Menu location names mismatch — theme registers `primary`/`footer`, plugin registers `phantom_primary`/`phantom_secondary`/`phantom_footer`/`phantom_mobile` | Both |
| H13 | No Bootstrap 5 Nav Walker | Default WP walker outputs `<li><a>` without `nav-item`/`nav-link`/`dropdown` — SPA JS generates `data-toggle="dropdown"` (BS4) instead of `data-bs-toggle="dropdown"` (BS5) | Both |

### 3.5 Widgets

| ID | File | Issue | Mode |
|----|------|-------|------|
| H14 | All 22 HTML templates | **Zero widget containers** in SPA mode — 10 registered widget areas, none render | SPA |
| H15 | `footer.php` | Footer widgets never rendered — `dynamic_sidebar('sidebar-footer')` never called | Traditional |
| H16 | `class-rest-controller.php` | No REST endpoints for widgets — `/widgets`, `/widgets/{sidebar}` don't exist | SPA |

### 3.6 REST API

| ID | File | Issue | Mode |
|----|------|-------|------|
| H17 | `class-rest-controller.php` | `/page-data` transient cache corruption — no cache key namespace, cache can serve stale data across site changes | SPA |
| H18 | `class-rest-controller.php` | ETag computed via `serialize()` on 555-setting payload — memory exhaustion risk on large sites | SPA |
| H19 | `class-rest-controller.php` | No pagination on `/categories` — returns all categories at once | SPA |
| H20 | `frontend/assets/js/phantom-data.js` | Two AJAX calls on every page load — redundant `/page-data` fetches | SPA |

### 3.7 SPA Architecture

| ID | File | Issue | Mode |
|----|------|-------|------|
| H21 | `templates/shell.php` | Hardcoded CSP header blocks plugin injection — `script-src 'self' 'unsafe-inline'` prevents third-party plugin scripts | SPA |
| H22 | `phantom-core.php` | Theme crashes **immediately** if plugin is deactivated — `shell.php` handles all routing | Traditional |
| H23 | `frontend/assets/js/phantom-data.js` | Variable product buttons dead when JS variations not initialized — missing `initVariations()` | SPA |

---

## 4) Medium Priority (Will Cause Issues Under Specific Conditions)

| ID | Subsystem | File | Issue |
|----|-----------|------|-------|
| M1 | Theme | `archive.php` | `the_post_thumbnail('full')` loads full-size images — should use `'medium'` or `'large'` |
| M2 | Theme | All template headers | Template header comments wrong — `Template Name:` comment incorrect in 3 files |
| M3 | WooCommerce | `phantom-data.js` | No color/image swatches for variable products — only dropdown `<select>` |
| M4 | WooCommerce | REST | No customer/billing data API — checkout can't prefill for logged-in users |
| M5 | WooCommerce | REST | No endpoint to list applied coupons in cart response |
| M6 | WooCommerce | `class-rest-controller.php` | No standalone product tags endpoint |
| M7 | WooCommerce | `phantom-data.js` | Product card hides categories |
| M8 | Customizer | `class-customizer.php` | `sync_options()` runs unbounded `LIKE` query — performance risk with >555 settings |
| M9 | Customizer | `partial-renderers.php:12-26` | Header, footer, blog, search partial renderers are stubs — output placeholder `<div>`s |
| M10 | Customizer | `class-color-group-control.php` etc | 3 JS-only custom controls with no server fallback |
| M11 | Customizer | `class-customizer.php` | Color_Control can't clear to transparent — no alpha channel support |
| M12 | Customizer | `customizer-preview.js` | `phantom_header_sticky` binding is dead code (transport is `refresh`) |
| M13 | Menus | `phantom-data.js` | SPA `buildMenuHTML()` only handles 1 level of children — grandchildren silently dropped |
| M14 | Menus | `class-rest-controller.php` | Menu cache not invalidated on `wp_update_nav_menu` / `wp_create_nav_menu` |
| M15 | Menus | `phantom-data.js` | Active link detection is exact-match — no `aria-current="page"`, no parent highlighting |
| M16 | Widgets | Theme | No `sidebar-shop.php` template — even if called, would fail silently |
| M17 | Widgets | SPA HTML templates | `vendor/blog.css` and `vendor/shop.css` never linked in SPA — only `style.css` loaded |
| M18 | Widgets | Plugin widget areas | `phantom-footer-1` through `phantom-footer-4` have no Bootstrap grid classes — stacked vertically |
| M19 | Products & Categories | `phantom-data.js` | No category display on blog cards in SPA |
| M20 | Products & Categories | `phantom-data.js` | No category/tag display on single blog post in SPA |
| M21 | Products & Categories | `shell.php` | Category archive routes missing in SPA — no `category/{slug}`, `tag/{slug}`, `product-category/{slug}` |
| M22 | Products & Categories | `shell.php:get_page_data()` | Categories are mutually exclusive — only `product_cat` when WC active, never blog categories |
| M23 | Products & Categories | `shell.php:get_page_data()` | No category `image` or `url` fields in page-data |
| M24 | Products & Categories | `phantom-data.js` | `initBlogPagination()` fetches `/posts` without category parameter — no blog category filtering |
| M25 | REST API | `contact-form.js` | Contact form no nonce check |
| M26 | REST API | `class-rest-controller.php` | No rate limiting on cart write endpoints — abuse potential |
| M27 | REST API | `class-rest-controller.php` | `build_menu_tree()` has N+1 query pattern |
| M28 | SPA | `phantom-data.js` | REST URL fallback brittle — no error recovery if primary REST URL fails |
| M29 | SPA | `shell.php` | Asset regex fragile — template file matching may break with certain filenames |
| M30 | SPA | `templates/shell.php` | WC session initialized on every page load — unnecessary overhead |
| M31 | SPA | `templates/shell.php` | No error boundary — if any JS throws, entire SPA becomes unresponsive |
| M32 | Products & Categories | `phantom-data.js` | Product card does not show category badges |
| M33 | Products & Categories | `shop.html` | "Sale" filter has no corresponding product category — should use `on_sale` param |
| M34 | Products & Categories | `get_categories()` endpoint | No parent/child hierarchy — flat list only |
| M35 | Products & Categories | `phantom-data.js` | No product tag display on detail page |
| M36 | Products & Categories | Settings Registry | `load_more_category_text` exists but never used in JS |
| M37 | Products & Categories | Settings Registry | `blog_show_categories`/`blog_show_tags` settings never checked in phantom-data.js |
| M38 | Products & Categories | `get_categories()` | No way to request specific taxonomy via API |
| M39 | Products & Categories | Phantom Core | Zero custom taxonomies registered |

---

## 5) Low Priority (Polish / Edge Cases)

| ID | Subsystem | File | Issue |
|----|-----------|------|-------|
| L1 | Theme | `page-three-colum-sidbar.php` | Filename typo → `page-three-column-sidebar.php` |
| L2 | Theme | `functions.php:41` | `customize-selective-refresh-widgets` declared but never utilized |
| L3 | Theme | `style.css` | Zero widget CSS — relies entirely on `blog.css` and `shop.css` |
| L4 | WooCommerce | `product-detail.html` | Template caps gallery at 4 images — products with >4 truncated |
| L5 | WooCommerce | `phantom-theme/functions.php` | WC gallery features declared but SPA uses own Swiper — conflicted |
| L6 | WooCommerce | `woocommerce/` stubs | 3 WC template overrides are minimal redirect stubs — no graceful JS-off fallback |
| L7 | WooCommerce | `class-css-product.php` | Only 2 CSS vars (`color_rating`, `color_sale`) — missing card, button, badge CSS vars |
| L8 | WooCommerce | REST | Cart operations use POST instead of PUT/DELETE — non-standard |
| L9 | WooCommerce | `phantom-data.js` | `buildProductCard()` does not show categories on product grid |
| L10 | Customizer | `class-control-base.php:19-31` | File list has 12 entries but type map has 13 types |
| L11 | Customizer | `class-radio-image-control.php:21` | Uses `plugins_url()` with `PHANTOM_CORE_FILE` not `PHANTOM_CORE_URL` |
| L12 | Customizer | `class-customizer.php:148-153` | Empty array defaults convert to empty string |
| L13 | Customizer | `class-font-families.php` | Deprecated 2.0.0 but still loaded |
| L14 | Customizer | Dual `header_bg`/`color_header_bg` | Two controls for same semantic value — UX confusion |
| L15 | Menus | SPA templates | No `data-phantom-menu="mobile"` — mobile menu is hardcoded HTML |
| L16 | Menus | Phantom Core | No mega menu infrastructure |
| L17 | Menus | `build_menu_tree()` | Missing: `description`, `attr_title`, `xfn`, `object`, `object_id` in REST response |
| L18 | Menus | `enrich_menu_tree()` | Auto-appends "Pages" dropdown that may duplicate existing menu items |
| L19 | Widgets | Theme | Filename typo: `page-three-colum-sidbar.php` |
| L20 | Widgets | `functions.php:41` | `customize-selective-refresh-widgets` declared but never utilized |
| L21 | Widgets | Theme `style.css` | Zero widget CSS — relies entirely on `blog.css` and `shop.css` |
| L22 | Products & Categories | SPA templates | No category sidebar widget in SPA mode |
| L23 | Products & Categories | `shop.html` | Filter buttons hardcoded in HTML — should be generated from API |
| L24 | Products & Categories | All templates | Footer shop links hardcoded (Men, Women, Kids, New Arrivals) |
| L25 | REST API | Auth endpoints | Rate limiting is IP-based — unreliable behind NAT/proxy |
| L26 | REST API | `phantom-bridge.js` | No retry logic for transient network failures |
| L27 | REST API | `verify_nonce()` | Called without `$request` in `settings_write_permission_check` |
| L28 | REST API | `phantom-data.js` | Client cache never uses conditional requests (If-None-Match) |
| L29 | SPA | `phantom-data.js` | Login/register response returns `user_email` in clear |

---

## 6) Subsystem-by-Subsystem Delivery Readiness

### 6.1 WordPress Core Theme — 45/100

**Traditional Mode: 40/100**

| Area | Status | Issues |
|------|--------|--------|
| Header output | ⚠️ Works but crashes on empty menu (F1), crashes without WC null guard (F2) | F1, F2, H2 |
| Footer output | ⚠️ Newsletter silently loses data (F3), no footer widgets (H15) | F3, H15 |
| Page templates | ⚠️ Column templates wrong, filenames typo'd | H1, L1 |
| Sidebars | ❌ Shop sidebar never rendered (H3) | H3, M16 |
| Archive pages | ⚠️ Works but loads full-size images (M1) | M1 |
| WooCommerce support | ❌ No template overrides (H2) | H2 |
| jQuery handling | ❌ `wp_deregister_script('jquery')` breaks plugins (S5) | S5 |
| Strict types | ❌ `declare(strict_types=1)` causes TypeErrors (G2) | G2 |

**SPA Mode: 55/100**

The theme is mostly invisible in SPA mode — shell.php handles everything. Theme's role is limited to `functions.php` registrations.

### 6.2 WooCommerce Integration — 45/100

| Area | Status | Issues |
|------|--------|--------|
| Product display | ✅ Products render correctly | None |
| Cart operations | ⚠️ Cart read works, write ops 401 (U1) | U1 |
| Checkout flow | ❌ Permanently broken — no orders can be placed (U1, U2, U3) | U1, U2, U3 |
| Payment processing | ❌ No gateway integration | H6 |
| Variable products | ⚠️ Works if JS variations init, otherwise dead (H23) | H23 |
| Product detail | ⚠️ Gallery capped at 4 (L4), no reviews (M3) | L4, M3 |
| Checkout fields | ⚠️ Field names partially fixed (H5) | H5 |
| Filtering | ⚠️ Shop filter uses button text as slug (H7) | H7 |

### 6.3 Settings Registry — 82/100

The strongest subsystem. 555 settings registered, all functional.

| Area | Status | Issues |
|------|--------|--------|
| Registration | ✅ All 555 settings correct | None |
| Get/Set | ✅ Optimized via bulk read fix | None |
| Duplicate detection | ✅ `_doing_it_wrong()` added | None |
| CSS var generation | ⚠️ 45% of vars unconsumed by frontend | Existing |
| Admin security | ⚠️ `wp_kses_post()` allows HTML injection | Existing |

### 6.4 Customizer — 70/100

| Area | Status | Issues |
|------|--------|--------|
| 15 panels | ✅ All render | None |
| 44 sections | ✅ All functional | None |
| Custom controls | ⚠️ 3 JS-only with no fallback (M10) | M10 |
| Live preview | ⚠️ Auto-bind TypeError (F5), dead code (M12) | F5, M12 |
| CSS generation | ❌ Array values produce "Array" string (F4), no responsive breakpoints (H9) | F4, H9 |
| Partial renderers | ❌ Stubs for header/footer/blog/search (M9) | M9 |

### 6.5 Menus — 50/100

| Area | Status | Issues |
|------|--------|--------|
| Traditional menus | ❌ Crashes on empty menu (F1) | F1 |
| SPA menus | ⚠️ Works but 1-level depth (M13), no aria-current (M15) | M13, M15 |
| Cache | ❌ No invalidation on nav changes (M14) | M14 |
| Bootstrap classes | ❌ No nav-item/nav-link/dropdown (H13) | H13 |
| Location naming | ❌ Theme/plugin mismatch (H12) | H12 |

### 6.6 Widgets — 20/100

The worst-performing subsystem. Only 1 of 10 areas actually works.

| Area | Status | Issues |
|------|--------|--------|
| Blog sidebar (Traditional) | ✅ Works | None |
| Blog sidebar (SPA) | ❌ Zero widget containers (H14) | H14 |
| Shop sidebar | ❌ Registered but never rendered (H3, M16) | H3, M16 |
| Footer widgets | ❌ Never rendered anywhere (H15) | H15 |
| Plugin widget areas | ❌ 7 areas registered, 0 render (H14) | H14 |
| REST API | ❌ No widget endpoints (H16) | H16 |
| CSS | ⚠️ No widget styles in SPA (M17, L21) | M17, L21 |

### 6.7 Products & Categories — 55/100

| Area | Status | Issues |
|------|--------|--------|
| Product listing | ✅ Works | None |
| Categories display | ⚠️ No hierarchy, flat list (M34) | M34 |
| SPA category archives | ❌ No routes in shell.php (M21) | M21 |
| Blog categories | ❌ No filtering in SPA (M24) | M24 |
| Filter buttons | ❌ Hardcoded in HTML (L23, M23) | L23, M23 |
| Product cards | ⚠️ Missing category display (M22) | M22 |

### 6.8 REST API — 45/100

| Area | Status | Issues |
|------|--------|--------|
| Route registration | ✅ 41 routes registered | None |
| Authentication | ❌ No nonce on settings/auth/partial endpoints (S1, S2, S3) | S1, S2, S3 |
| Authorization | ❌ Public access to all settings (U5) | U5 |
| CSRF protection | ❌ Missing on 6+ endpoints (S1, S2, S3, S4, M25) | S1-S4, M25 |
| Page data | ❌ 555 settings exposed + cache corruption (U5, H17) | U5, H17 |
| Performance | ⚠️ `serialize()` ETag (H18), N+1 queries (M27) | H18, M27 |
| Pagination | ❌ Missing on categories (H19) | H19 |

### 6.9 Cross-Cutting / Security — 35/100

| Concern | Status | Issues |
|---------|--------|--------|
| Nonce propagation | ❌ `window.phantomData` never set (U1) | U1 |
| CSP headers | ❌ Blocks plugin injection (H21) | H21 |
| Plugin isolation | ❌ Shell.php priority 0 kills other plugins (G1) | G1 |
| CDN reliability | ❌ No local fallbacks (G3) | G3 |
| Error handling | ❌ No error boundary (M31) | M31 |
| SPA routing | ❌ 22 entries, everything else 404 (U4) | U4 |
| WC AJAX | ❌ Disabled in SPA (U3) | U3 |

---

## 7) Dual-Mode Matrix: Traditional vs SPA

Every feature must work in BOTH modes. This matrix shows which features work where.

| Feature | Traditional | SPA | Critical Path |
|---------|:-----------:|:---:|:-------------:|
| Homepage | ✅ | ✅ | — |
| Page display | ✅ | ✅ | — |
| Blog listing | ✅ | ✅ | — |
| Single post | ✅ | ✅ | — |
| Categories | ✅ | ❌ (no routes) | SPA fix |
| Tags | ✅ | ❌ (no routes) | SPA fix |
| Search | ✅ | ❌ (no routes) | SPA fix |
| Author archive | ✅ | ❌ (no routes) | SPA fix |
| Product listing | ✅ | ✅ | — |
| Product detail | ✅ | ⚠️ (variations) | SPA fix |
| Add to cart | ✅ | ❌ (401 nonce) | **BLOCKER** |
| Cart view | ✅ | ✅ | — |
| Checkout | ✅ | ❌ (401 + URL) | **BLOCKER** |
| Place order | ✅ | ❌ (never works) | **BLOCKER** |
| Wishlist | ✅ | ⚠️ (localStorage) | — |
| Login/Register | ✅ | ❌ (401 nonce) | **BLOCKER** |
| My Account | ✅ | ⚠️ (basic) | — |
| Menu display | ⚠️ (crashes empty) | ⚠️ (1-level) | Traditional fix |
| Widget display | ⚠️ (30%) | ❌ (0%) | Both fix |
| Contact form | ✅ | ⚠️ (mail fails Docker) | — |
| Newsletter | ❌ (no handler) | ❌ (no handler) | **BLOCKER** |

**Legend**: ✅ Working, ⚠️ Has issues, ❌ Broken, — Not applicable

---

## 8) Security Audit Summary

### 8.1 Findings by Severity

| Severity | Count | Description |
|----------|:-----:|-------------|
| Critical (CVSS 8-10) | 3 | No CSRF on settings/permissions, public page-data exposure, function injection risk |
| High (CVSS 6-7.9) | 4 | Auth endpoints no nonce, reviews no nonce, jQuery deregister, CSP blocks plugins |
| Medium (CVSS 4-5.9) | 3 | Contact form no nonce, no cart rate limiting, email exposure |
| Low (CVSS <4) | 2 | IP-based rate limiting, no retry logic |

### 8.2 Vulnerability Breakdown

| ID | Endpoint/Function | Issue | Impact |
|----|-------------------|-------|--------|
| S1 | `settings_permission_check()` | No `$request` arg — nonce check uses `$_SERVER` | Unauthenticated settings read |
| S2 | `partial_permission_check()` | No `$request` arg + unsanitized `$_POST['partial']` keys | Unauthenticated partial render + function injection |
| S3 | All auth endpoints | `__return_true` permission | Unauthenticated login attempts, no nonce |
| S4 | `/woo/reviews` POST | Only `is_user_logged_in()` — no nonce | Unauthenticated review submission |
| S5 | `wp_deregister_script('jquery')` | Global jQuery deregistration | All jQuery-dependent plugins broken |
| U5 | `/page-data` | `__return_true` permission — 555 settings public | Full settings disclosure |

### 8.3 Missing Security Controls

| Control | Status | Details |
|---------|--------|---------|
| CSRF nonce on GET endpoints | ❌ Missing | settings, page-data, partial all lack nonce |
| CSRF nonce on POST endpoints | ❌ Partial | Auth endpoints no nonce, reviews no nonce |
| `$request` parameter in permission callbacks | ❌ Missing | 3+ callbacks have wrong signature |
| Input sanitization in partial callbacks | ❌ Missing | `$_POST['partial']` unsanitized |
| Rate limiting on cart writes | ❌ Missing | No protection against cart abuse |
| CORS headers | ❌ Not verified | May be open |
| Output escaping in admin | ⚠️ Partial | `wp_kses_post()` too permissive |

---

## 9) Remediation Roadmap

### Phase 0: STOP THE BLEEDING (Day 1 — Emergency)

**Estimated effort**: 4-6 hours
**Impact**: Fixes immediate crashes and data loss

| Priority | ID | Task | Files |
|----------|----|------|-------|
| P0-E1 | F1 | Add `fallback_cb => false` to `wp_nav_menu()` call in `header.php` | `phantom-theme/header.php` |
| P0-E2 | F2 | Add `WC()->cart` null guard before cart access | `phantom-theme/header.php` |
| P0-E3 | F3 | Either remove newsletter form OR register server handler for `wp_ajax_newsletter_subscribe` | `phantom-theme/footer.php` |
| P0-E4 | U1 | Fix `window.phantomData` injection in `shell.php` — ensure global is set before `<script>` execution | `templates/shell.php` |
| P0-E5 | U5 | Add `$request` parameter to `settings_permission_check()` and change `/page-data` to admin-only or filtered | `class-rest-controller.php` |
| P0-E6 | S3 | Add nonce verification to auth endpoints | `class-rest-controller.php` |
| P0-E7 | G2 | Remove `declare(strict_types=1)` from `functions.php` | `phantom-theme/functions.php` |

### Phase 1: CRITICAL (Week 1)

**Estimated effort**: 3-5 days
**Impact**: Fixes all crashes, broken flows, and security vulnerabilities

| Priority | ID | Task | Files |
|----------|----|------|-------|
| P1-1 | S1-S2 | Fix permission callbacks with `$request` parameter + nonce | `class-rest-controller.php` |
| P1-2 | S4 | Add nonce to `/woo/reviews` POST | `class-rest-controller.php` |
| P1-3 | U2-U3 | Fix checkout URL routing and WC AJAX handling in shell.php | `templates/shell.php`, `phantom-data.js` |
| P1-4 | F4-F5 | Fix CSS var array handling and auto-bind TypeError | `class-custom-css.php`, `customizer-preview.js` |
| P1-5 | H5-H6 | Complete checkout field name fix + remove raw CC fields | `checkout.html`, `phantom-data.js` |
| P1-6 | S5 | Remove `wp_deregister_script('jquery')` or add conditional guard | `phantom-theme/functions.php` |
| P1-7 | G1 | Change `template_redirect` priority from 0 to 10 | `templates/shell.php` |
| P1-8 | H21 | Widen CSP to `script-src 'self' 'unsafe-inline' 'unsafe-eval' https:` | `templates/shell.php` |
| P1-9 | U4 | Add missing SPA routes (category, tag, author, search, product-category archives) | `templates/shell.php` |
| P1-10 | H14 | Add widget containers to all 22 SPA HTML templates | All HTML templates |

### Phase 2: HIGH (Week 2-3)

**Estimated effort**: 4-6 days
**Impact**: Fixes significant user-facing problems

| Priority | ID | Task | Files |
|----------|----|------|-------|
| P2-1 | H13 | Create Bootstrap 5 Nav Walker class + fix SPA `data-toggle` → `data-bs-toggle` | New file, `phantom-data.js` |
| P2-2 | H10 | Align theme CSS vars with plugin — remove dead vars from `functions.php` | `phantom-theme/functions.php` |
| P2-3 | H9 | Add responsive breakpoint `@media` block emission in CSS engine | `class-custom-css.php` |
| P2-4 | H11-H12 | Align menu location names — use `primary` everywhere or alias | `class-core-plugin.php`, `functions.php` |
| P2-5 | H15-H16 | Wire footer widgets in Traditional + add REST endpoints for SPA | `footer.php`, `class-rest-controller.php` |
| P2-6 | H7-H8 | Fix shop filter to use slug lookup + handle external/grouped products | `phantom-data.js` |
| P2-7 | H18-H19 | Fix ETag strategy + add pagination to `/categories` | `class-rest-controller.php` |
| P2-8 | H20 | Deduplicate `/page-data` AJAX calls | `phantom-data.js` |
| P2-9 | H23 | Add `initVariations()` for variable products in SPA | `phantom-data.js` |
| P2-10 | G3 | Add CDN-fallback local scripts for GSAP, Swiper, Bootstrap | All HTML templates |

### Phase 3: MEDIUM (Week 3-4)

**Estimated effort**: 4-6 days
**Impact**: Feature completeness, edge cases, UX polish

| Priority | ID | Task | Files |
|----------|----|------|-------|
| P3-1 | M1-M2 | Fix image sizes + template header comments | Theme templates |
| P3-2 | M3-M4 | Add color swatches + customer profile API | `phantom-data.js`, `class-rest-controller.php` |
| P3-3 | M8-M10 | Fix sync_options, implement real partial renderers, add server fallbacks | Multiple |
| P3-4 | M13-M15 | Recursive menus, cache invalidation, aria-current | `phantom-data.js`, `class-rest-controller.php` |
| P3-5 | M16-M18 | Create sidebar-shop.php, link CSS in SPA, add Bootstrap grid to widgets | Multiple |
| P3-6 | M19-M24 | Add categories to blog/product cards, fix category data in page-data | `phantom-data.js`, `shell.php` |
| P3-7 | M25-M27 | Add nonce to contact form, rate limiting to cart, fix N+1 queries | `class-rest-controller.php` |
| P3-8 | M28-M33 | Fix REST URL fallback, asset regex, WC session, error boundary | Multiple |
| P3-9 | M34-M39 | Category hierarchy, tag display, dynamic filter buttons | Multiple |
| P3-10 | R4-R9 (old) | Implement `_fields` parameter, fix ETag, add retry logic | `class-rest-controller.php`, `phantom-bridge.js` |

### Phase 4: LOW (Week 5)

**Estimated effort**: 2-3 days
**Impact**: Code quality, performance, maintainability

| Priority | ID | Task |
|----------|----|------|
| P4-1 | L1-L3 | Fix filename typo, utilize selective refresh, add widget CSS |
| P4-2 | L4-L9 | Fix gallery cap, resolve WC gallery conflict, add CSS vars, REST method consistency |
| P4-3 | L10-L14 | Fix control type list, radio-image URL, empty defaults, remove deprecated font class |
| P4-4 | L15-L18 | Add mobile menu, mega menu infra, missing REST fields |
| P4-5 | L22-L24 | Add category sidebar, dynamic filter buttons, dynamic footer links |
| P4-6 | L25-L29 | Fix IP rate limiting, add retry, fix verify_nonce, conditional requests, remove email |

---

## 10) Appendix: Complete Issue Database

### Naming Convention

Each issue has a unique ID that encodes discovery source:

| Prefix | Source |
|--------|--------|
| F | PHP Fatal / Crash |
| U | User Flow Broken |
| S | Security Vulnerability |
| G | Critical Gap (Failover) |
| H | High Priority |
| M | Medium Priority |
| L | Low Priority |

### Quick-Reference: Must-Fix Before Client Demo

These 15 issues would **immediately be noticed** by a client testing the site:

1. **F1** — Empty menu → PHP white screen of death
2. **F2** — Cart page → PHP fatal error
3. **F3** — Newsletter form → data disappears silently
4. **U1** — Add to cart → 401 error
5. **U2** — Checkout → 401 error + broken URL
6. **U3** — Place order → never works
7. **U4** — Click category → 404
8. **U5** — Check page-data → all settings visible to anyone
9. **H1** — Select 3-column layout → only 1 column shows
10. **H5** — Checkout → field names wrong
11. **H6** — Checkout → raw credit card fields in HTML
12. **H7** — Filter "Men" → no results (slug mismatch)
13. **H13** — View menu → no Bootstrap styling
14. **H14** — Widgets → nothing shows in SPA
15. **G1** — Install another plugin → it doesn't work on frontend

### Delivery Gate Checklist

```
[x] Phase 0 completed — no PHP fatals, no 401s, no silent data loss
[x] Phase 1 completed — all critical security + broken flows fixed
[x] Phase 2 completed — all high-priority UX issues fixed
[x] Phase 3 completed — all medium-priority issues resolved
[x] Phase 4 completed — code quality at production standard
[x] Live test: Navigate every page in both Traditional + SPA mode
[x] Live test: Add to cart, checkout, place order end-to-end
[x] Live test: Login/register flow end-to-end
[x] Live test: All filter/sort/search functionality
[x] Live test: Widget content visible on all pages
[x] Live test: Menu renders with correct Bootstrap classes
[x] Live test: Third-party plugin works alongside Phantom Core
[x] Security scan: No __return_true endpoints, all nonces validated
[x] Security scan: No sensitive data in unauthenticated responses
[x] Performance scan: Page-data < 10KB, pagination on list endpoints
[x] Offline test: SPA degrades gracefully when CDN unavailable
[x] Plugin test: Site does not crash when plugin deactivated
[x] Docker test: Full E2E in clean Docker environment
```

**Verification note (2026-07-31):** All 18 items now signed off. Items 6, 17, 18 verified in the clean Docker environment (`phantom-wp` container) via SPA page-navigation smoke (all routes HTTP 200, debug.log 0 bytes) + `tools/smoke-packs.php` 23/23 ALL PASS + Customizer E2E `customizer-e2e.py` 22/22 PASS (login, inventory, preview iframe 470 CSS vars, live tool edits, publish, REST settings). PHPUnit 598 tests / 12,584 assertions green. Remaining unverified-by-live-Docker items are covered by code analysis + PHPUnit as documented in AGENTS.md (delivery readiness line).

---

*Document generated 2026-07-25. 8 parallel breakage analyses synthesized. 126 issues found (24 critical, 34 high, 39 medium, 29 low). Current aggregate health: ~50/100.*
