# Phantom OS Framework — Architecture Master Plan

**Date:** 2026-07-26
**Version:** 2.0 → 3.0 (Phantom OS)
**Status:** Draft
**Author:** AI Agent + Human Partner

---

## 1. Domain Glossary

| Term | Definition |
|------|-----------|
| **Phantom OS** | The framework layer — never changes. Contains Service Container, Event System, Registries, Engines, Bridges. Analogous to an operating system. |
| **Demo** | A self-contained visual skin consisting of HTML templates, CSS, JS, images, fonts, icons, and a `manifest.json`. Fully swappable without touching PHP. Analogous to a child theme. |
| **Template Pack** | The filesystem storage for a Demo. Lives at `frontend/templates/{pack}/`. Contains `html/`, `css/`, `js/`, `assets/` subdirectories. |
| **Engine** | A service class in `includes/Engine/` with a single responsibility: Data, View, or Asset. |
| **Data Engine** | Queries WordPress/WooCommerce, normalizes data through Adapters, returns plain arrays. Never produces HTML. |
| **View Engine** | Loads HTML templates, invokes Component Renderers, injects data into HTML via string replacement. Never queries the database. |
| **Asset Engine** | Manages CSS, JS, fonts, images, CDN fallbacks, lazy loading, versioning, CSP headers. Never produces page content. |
| **Service Container** | PSR-11-like DI container with auto-wiring. Manages service definitions, lazy instantiation, shared instances. |
| **Event Dispatcher** | Pub/sub system wrapping WordPress `do_action`/`apply_filters`. Services emit events; listeners react. |
| **Data Adapter** | Normalizes raw WP/WC data (objects, IDs) into consistent associative arrays. Implements `AdapterInterface`. One per domain entity. |
| **Component Renderer** | Converts data arrays into HTML strings via template loading + placeholder injection. Implements `RendererInterface`. One per UI component. |
| **Component Contract** | Formal array shape contract between an Adapter and a Renderer. Documented in `ViewModel` classes (typed properties). |
| **Design Token** | A named CSS custom property with a semantic value. Scoped by category (spacing, typography, color, radius, shadow). |
| **Theme Preset** | A full snapshot of all settings + Design Token overrides + active configuration. Restorable in one click. |
| **Plugin Bridge** | An adapter class that isolates third-party plugin integration (WooCommerce, Elementor, WPML, RankMath) behind a unified interface. |
| **Animation Registry** | A central registry of named animation definitions (CSS keyframes + JS scroll/intersection triggers). |
| **Demo Manager** | Admin UI for listing, previewing, installing, activating, and deleting Demos. Supports ZIP upload + one-click switch. |

---

## 2. Current State Assessment

### 2.1 What's Production-Grade (Keep As-Is)

| Component | Files | LoC | Health |
|-----------|-------|-----|--------|
| Settings Registry | `class-settings-registry.php` | 5,709 | ✅ 564 settings, 44 sections |
| Customizer | `class-customizer.php` + 13 controls | ~3,000 | ✅ 15 panels, 96 CSS vars |
| REST API | `class-rest-controller.php` | ~1,500 | ✅ 43 routes |
| CSS Generation | `includes/custom-css/` (9 files) | ~2,000 | ✅ 9 modules |
| Theme Setup | `class-core-plugin.php`, `functions.php` | ~500 | ✅ Menus, widgets, sidebars |

### 2.2 What's Built But Needs Work

| Component | Status | Issue |
|-----------|--------|-------|
| Engine Layer | ⚠️ 7 files, good structure | Render_Engine does 21 sequential operations. No DI. Cache isolated. |
| Data Adapters | ⚠️ 6 files, 330 lines total | No interfaces. Inconsistent signatures. Post_Adapter + Settings_Adapter dead. |
| Component Renderers | ⚠️ 7 files, 256 lines total | Hero/Navigation/Footer/Blog_Card dead. Components dir doesn't exist. `inject()` base method never called. |
| Template Loader | ✅ Working | Pack system works. Fashion pack exists. No admin UI. |
| JS Modules | ⚠️ 14 files, 700 lines built | PhantomInjector undefined. phantom-bridge.js is separate arch. No minification. |
| Template Packs | ⚠️ Fashion partial | 5/22 templates. No manifest.json. No demo screenshots. |

### 2.3 Dead Code Inventory

| File | Lines | Reason Dead |
|------|-------|-------------|
| `Post_Adapter` | 55 | Never instantiated anywhere |
| `Settings_Adapter` | 23 | Never instantiated anywhere |
| `Hero::render()` | 37 | Instantiated in WooCommerce_Injector but `render()` never called |
| `Navigation` | 38 | Imported in shell.php, never instantiated |
| `Footer` | 17 | Never instantiated. `{{WIDGETS}}` never replaced. |
| `Blog_Card` | 42 | Never instantiated (not even in tests) |
| `PhantomInjector` | 3 refs | Referenced in phantom-core.js, doesn't exist |
| `Component_Renderer::inject()` | 24 | Method defined in base class but never called by any subclass |

### 2.4 Technical Debt

| Issue | Severity |
|-------|----------|
| PhantomInjector undefined — API data fetched then silently discarded | High |
| phantom-bridge.js vs modular JS — two competing architectures | High |
| No minification despite `.min.js` filename | Medium |
| Component templates directory `frontend/html/components/` doesn't exist | Medium |
| Renderer base class `inject()` method never called by subclasses | Low |
| No localization (`__()`) in any renderer | Low |
| No `apply_filters` hooks on rendered HTML | Low |

---

## 3. Target Architecture: Phantom OS

```
┌─────────────────────────────────────────────────────────────┐
│                    PHANTOM OS FRAMEWORK                      │
│  (Never Changes — Stable Core)                             │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  ┌───────────────────────────────────────────────────────┐  │
│  │                   SERVICE CONTAINER                    │  │
│  │  PSR-11 DI Container with auto-wiring + service       │  │
│  │  providers + lazy singleton resolution                │  │
│  └───────────────────────────────────────────────────────┘  │
│                                                              │
│  ┌───────────────────────────────────────────────────────┐  │
│  │                   EVENT DISPATCHER                    │  │
│  │  Pub/sub wrapping WP hooks + JS PhantomEvents        │  │
│  └───────────────────────────────────────────────────────┘  │
│                                                              │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐          │
│  │ DATA ENGINE │  │ VIEW ENGINE │  │ASSET ENGINE │          │
│  │             │  │             │  │             │          │
│  │ Adapters    │  │ Renderers   │  │ CSS/JS      │          │
│  │ ViewModels  │  │ Templates   │  │ Fonts       │          │
│  │ WP/WC API   │  │ Sections    │  │ Images      │          │
│  │ Settings    │  │ Layouts     │  │ CDN         │          │
│  └─────────────┘  └─────────────┘  └─────────────┘          │
│                                                              │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐          │
│  │ REGISTRIES  │  │   BRIDGES   │  │   MANAGERS  │          │
│  │             │  │             │  │             │          │
│  │ Design Token│  │ WooCommerce │  │ Demo Mgr    │          │
│  │ Component   │  │ Elementor   │  │ Preset Mgr  │          │
│  │ Animation   │  │ WPML        │  │ Cache Mgr   │          │
│  │ Section     │  │ RankMath    │  │ License Mgr │          │
│  │ CSS Gen (9) │  │             │  │             │          │
│  └─────────────┘  └─────────────┘  └─────────────┘          │
│                                                              │
│  ┌───────────────────────────────────────────────────────┐  │
│  │                   CONTRACTS                            │  │
│  │  AdapterInterface  RendererInterface  BridgeInterface │  │
│  │  EventInterface    ContainerInterface  PresetInterface│  │
│  └───────────────────────────────────────────────────────┘  │
│                                                              │
├─────────────────────────────────────────────────────────────┤
│                    DEMO LAYER (Swappable)                    │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐    │
│  │ FASHION  │  │  KIDS    │  │  SHOES   │  │FURNITURE │    │
│  │ Demo     │  │  Demo    │  │  Demo    │  │  Demo    │    │
│  │          │  │          │  │          │  │          │    │
│  │ HTML     │  │ HTML     │  │ HTML     │  │ HTML     │    │
│  │ CSS      │  │ CSS      │  │ CSS      │  │ CSS      │    │
│  │ JS       │  │ JS       │  │ JS       │  │ JS       │    │
│  │ Assets   │  │ Assets   │  │ Assets   │  │ Assets   │    │
│  │ manifest │  │ manifest │  │ manifest │  │ manifest │    │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘    │
│                                                              │
│  ┌───────────────────────────────────────────────────────┐  │
│  │              ONE-CLICK DEMO SWITCH                     │  │
│  │  Settings → Admin → Demo Manager → Activate → Done   │  │
│  └───────────────────────────────────────────────────────┘  │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

**Note:** The existing CSS Generation Engine (`includes/custom-css/`, 9 files) remains as a Registry-level component. It generates CSS custom properties from settings and feeds into the Asset Engine's CSS pipeline. It is NOT merged into Asset Engine because its output is consumed by both frontend (via Asset Engine) and Customizer preview (live CSS updates via `wp_head`).

---

## 4. Architectural Decisions

### ADR-001: Service Container over Manual Instantiation

**Context:** All Engine classes currently use `new ClassName()` in constructors or `Singleton::get_instance()` statics. This makes testing impossible and coupling tight.

**Decision:** Build a PSR-11-compatible Container with:
- `set(id, definition)` — register service
- `get(id)` — resolve with auto-wiring
- `has(id)` — check registration
- Service providers for logical groups
- Singleton vs factory resolution

**Consequence:** All `new` calls in Engine classes are replaced with `Container::get()`. WooCommerce_Injector receives engine instances via constructor injection.

### ADR-002: Three Engines over One Monolithic Render_Engine

**Context:** `Render_Engine::render()` does 21 sequential operations (verified by code audit) from template loading to SEO to asset injection to WC content.

**Decision:** Split into Data Engine (WP/WC queries, normalization), View Engine (templates, renderers, HTML injection), Asset Engine (CSS/JS/fonts/headers). Render_Engine becomes a thin coordinator calling all three in sequence.

**Consequence:** Each engine becomes independently testable. Data Engine can be used by REST API endpoints. Asset Engine can be used by Customizer preview.

### ADR-003: AdapterInterface + RendererInterface + ViewModel Contracts

**Context:** Adapters have no shared contract. Renderers have abstract class but no interface. Data flows via undocumented array shapes.

**Decision:** Three contract layers:
- `AdapterInterface`: `normalize($input): array`, `normalize_collection(array $inputs): array`
- `RendererInterface`: `render(array $data): string`, `render_collection(array $data_set): string`
- `ViewModelInterface`: Marker interface implemented by ViewModel classes. Each ViewModel is a final class with PHP 7.4+ typed properties documenting the exact array shape an adapter returns and a renderer expects. ViewModels are NOT instantiated at runtime — they serve as living documentation enforced by PHP type hints.

**Consequence:** Adapters and Renderers become independently implementable and testable. Array keys are documented in one place with typed properties. ViewModels are pure documentation and static analysis aids — zero runtime overhead.

### ADR-004: Demo Manager over Static Template Switching

**Context:** Template packs exist but switching requires PHP code or database edits.

**Decision:** Admin UI page with: list all installed demos, preview screenshot, one-click activate, ZIP upload to install new, delete. Each demo has `manifest.json`.

**Consequence:** Users switch visual identity without touching code. New demos can be installed from ZIP files, enabling a marketplace model.

### ADR-005: PhantomBridge Merge into Modular JS

**Context:** Two separate JS architectures coexist — phantom-bridge.js (monolithic IIFE, own HTTP client, own events) and modular JS (14 files, PhantomEvents, PhantomInjector undefined).

**Decision:** Merge phantom-bridge.js functionality into the modular architecture. Implement PhantomInjector. Unify on single event system (PhantomEvents). Single HTTP client (PhantomServices.Api).

**Consequence:** One JS bundle instead of two. No duplicate code. PhantomInjector actually injects data into the DOM.

---

## 5. Implementation Phases

```
Phase 0 ── Foundation Hardening ─────────── 1-2 sessions
  ├── Create component templates directory
  ├── Implement PhantomInjector JS
  ├── Merge phantom-bridge.js into modular JS
  ├── Add minification to build pipeline
  ├── Create AdapterInterface + RendererInterface contracts
  └── Wire up or remove dead code

Phase 1 ── Service Container + Event System ── 2-3 sessions
  ├── Build PSR-11 Container with auto-wiring
  ├── Build EventDispatcher (PHP)
  ├── Refactor Engine classes for DI
  ├── Unify PHP events with JS PhantomEvents
  └── Write ContainerTest + EventDispatcherTest

Phase 2 ── Three-Engine Split ────────────── 2-3 sessions
  ├── Extract Data_Engine from Render_Engine
  ├── Extract View_Engine (template + renderers)
  ├── Refactor Asset_Loader into Asset_Engine
  ├── Rewrite Render_Engine as thin coordinator
  └── Write DataEngineTest + ViewEngineTest + AssetEngineTest

Phase 3 ── Demo Manager ──────────────────── 2-3 sessions
  ├── manifest.json format specification
  ├── Admin UI page (list, activate, delete)
  ├── ZIP upload + extract + validate
  ├── One-click activation with cache flush
  └── Write DemoManagerTest

Phase 4 ── Design Tokens + Presets ───────── 2-3 sessions
  ├── Token scales (spacing, typography, color, radius)
  ├── Token Registry PHP class + CSS var generation
  ├── Preset = settings + tokens snapshot
  ├── Admin UI: Save/Load/Delete presets
  ├── Ship 4 presets (Light, Dark, Luxury, Minimal)
  └── Write TokenRegistryTest + PresetManagerTest

Phase 5 ── Plugin Bridges + Animation Registry ── 2-3 sessions
  ├── PluginBridgeInterface + base class
  ├── WooCommerceBridge (extract from hardcoded)
  ├── ElementorBridge stub
  ├── WPMLBridge stub
  ├── AnimationRegistry (CSS keyframes + JS triggers)
  └── Write BridgeTest + AnimationRegistryTest

Phase 6 ── Complete Demo Packs ────────────── 3-4 sessions
  ├── Fashion: all 22 templates + full assets
  ├── Shoes: all 22 templates + assets
  ├── Furniture: all 22 templates + assets
  └── Each demo has manifest.json + screenshot
```

### Dependency Graph

```
Phase 0 ──► Phase 1 ──► Phase 2 ────► Phase 3 ────► Phase 4
                                         │
                                         ▼
                                   Phase 5 ──► Phase 6
```

Phase 0 must be first (foundational). Phase 1 must follow Phase 0. Phase 2 depends on Phase 1. Phases 3-6 have a relaxed dependency: Phase 4 depends on Phase 3, Phase 5 depends on Phase 3, Phase 6 depends on Phase 4 + Phase 5. Phases 4 and 5 can be developed in parallel by separate agents after Phase 3 completes.

---

## 6. Phase 0 Detailed Specification

### 6.1 Objective

Harden the foundation: fix dead code, create contracts, make component templates work, fix JS architecture, and add proper build pipeline. Zero new features — only fix what's broken.

### 6.2 Dead Code Decision Rules

Each dead code item is evaluated with a clear decision:

| Item | Decision | Rationale |
|------|----------|-----------|
| `Post_Adapter` | Remove | Never instantiated. Post data can be normalized inline or via a future data engine. |
| `Settings_Adapter` | Remove | Never instantiated. Settings already accessed directly via Settings_Registry. |
| `Hero::render()` | Wire up | Already instantiated in WooCommerce_Injector. Just need to call `$this->hero->render()` in the hero section. |
| `Navigation` | Remove | 38 lines, never instantiated. Nav is rendered by JS frontend. Remove import from shell.php. |
| `Footer` | Wire up | Small class (17 lines). Wire into WooCommerce_Injector to replace `{{WIDGETS}}` placeholder. |
| `Blog_Card` | Remove | 42 lines, never instantiated anywhere. Can be recreated when Phase 6 blog templates are built. |
| `Component_Renderer::inject()` | Fix and use | Refactor regex to support multi-word `{{placeholders}}`. Refactor all subclasses to call `$this->inject()` instead of `str_replace`. |

### 6.3 PhantomInjector API Specification

```javascript
// phantom-injector.js — single responsibility: inject data into DOM
window.PhantomInjector = {
    // Replace {{PLACEHOLDERS}} in element content with data values
    injectContent(element: HTMLElement, data: Record<string, string>): void,
    // Replace {{PLACEHOLDERS}} in element attributes (e.g. src, href)
    injectAttributes(element: HTMLElement, data: Record<string, string>): void,
    // Render a component template into a container with data
    renderComponent(container: HTMLElement, template: string, data: Record<string, string>): void,
    // Bulk inject settings data into DOM
    injectSettings(settings: Record<string, string>): void,
    // Bulk inject menu data into navigation containers
    injectMenus(menus: Array<MenuData>): void,
    // Bulk inject product data into product containers
    injectProducts(products: Array<ProductData>): void,
};
```

### 6.4 phantom-bridge.js Merge Specification

The merge eliminates duplicate HTTP client and event infrastructure:

1. **HTTP client**: Move `phantomFetch()` (retry-wrapped fetch) into `PhantomServices.Api` — add retry support to the existing `PhantomServices.Api` class in `services/api-services.js`
2. **Events**: The `_listeners`/`onSettingChange`/`_emit` pattern in phantom-bridge.js is replaced by existing `PhantomEvents` — ensure `PhantomEvents` supports a `onSettingChange` event type
3. **REST endpoints**: Move `setSetting()`, `saveChanges()` logic into `PhantomServices.Api` as `Api.saveSetting(key, value)` and `Api.saveSettings(data)`
4. **Nonce**: The `X-Phantom-Nonce` header handling moves into the API service's request interceptor
5. **Delete**: Remove `phantom-bridge.js` after all functionality is migrated

Verification: `grep -c "phantomFetch\|_listeners\|onSettingChange\|_emit\|setSetting\|saveChanges"` returns zero after cleanup.

### 6.5 Component Template Format Specification

Component templates live in `frontend/html/components/{component-name}.html`. Format:

```html
<!-- Component template uses {{UPPER_SNAKE_CASE}} placeholders -->
<div class="product-card" data-product-id="{{PRODUCT_ID}}">
    <img src="{{IMAGE_URL}}" alt="{{TITLE}}" loading="lazy">
    <h3>{{TITLE}}</h3>
    <p class="price">{{PRICE}}</p>
    <p class="description">{{DESCRIPTION}}</p>
    <a href="{{PERMALINK}}" class="btn btn-primary">{{CTA_TEXT}}</a>
</div>
```

Rules:
- Placeholders are `{{UPPER_SNAKE_CASE}}` (multi-word, e.g. `{{PRODUCT_ID}}`, `{{IMAGE_URL}}`)
- Templates contain zero logic — pure HTML
- All URLs are absolute (no relative paths in templates)
- All images include `loading="lazy"` attribute
- All links include descriptive text

### 6.6 Files to Create

| File | Purpose |
|------|---------|
| `includes/contracts/interface-adapter.php` | `AdapterInterface` with `normalize()` + `normalize_collection()` |
| `includes/contracts/interface-renderer.php` | `RendererInterface` with `render()` + `render_collection()` |
| `includes/contracts/interface-view-model.php` | `ViewModelInterface` for typed data shape documentation |
| `includes/ViewModels/product-view-model.php` | Typed class documenting Product_Adapter output shape |
| `includes/ViewModels/category-view-model.php` | Typed class documenting Category_Adapter output shape |
| `includes/ViewModels/post-view-model.php` | Typed class documenting Post_Adapter output shape |
| `frontend/html/components/product-card.html` | Component template for Product_Card |
| `frontend/html/components/category-card.html` | Component template for Category_Card |
| `frontend/html/components/blog-card.html` | Component template for Blog_Card |
| `frontend/assets/js/phantom-injector.js` | `PhantomInjector` implementation (was undefined) |

### 6.7 Files to Modify

| File | Change |
|------|--------|
| All 4 remaining adapters (after removing Post_Adapter + Settings_Adapter) | Implement `AdapterInterface`. Add missing `normalize_collection()` + `empty()`. Make signatures consistent. |
| All 5 remaining renderers (after Blog_Card removal) | Implement `RendererInterface`. Use base `inject()` instead of `str_replace`. |
| `class-component-renderer.php` | Fix `inject()` regex to support multi-word `{{PLACEHOLDERS}}`. Add `apply_filters` hook. |
| `class-hero.php` | No change needed — already instantiated. Will be wired in WooCommerce_Injector. |
| `class-navigation.php` | Remove file (dead code, nav handled by JS frontend). |
| `class-footer.php` | No change needed — will be wired in WooCommerce_Injector. |
| `class-blog-card.php` | Remove file (dead code, can be recreated in Phase 6). |
| `class-post-adapter.php` | Remove file (dead code). |
| `class-settings-adapter.php` | Remove file (dead code, Settings_Registry used directly). |
| `phantom-core.js` | Replace `PhantomInjector &&` no-ops with actual calls. |
| `build.js` | Add terser minification + source maps. Remove `.min.js` misleading name. |
| `phantom-bridge.js` | Merge functionality into modular JS (HTTP client, CSS vars, REST calls). Then remove file. |
| `shell.php` | Remove unused imports (Menu_Adapter, Navigation, etc.) |
| `Render_Engine.php` | Remove dead injection code paths. |
| `WooCommerce_Injector.php` | Wire `$this->hero->render()` into hero section. Wire `$this->footer->render()`. |

### 6.8 Success Criteria (Phase 0 Verifiable Checklist)

- [ ] All 4 remaining adapters pass `instanceof AdapterInterface`
- [ ] All 5 remaining renderers pass `instanceof RendererInterface`
- [ ] `component-renderer.php` base class `inject()` regex matches `{{MULTI_WORD_KEYS}}`
- [ ] `frontend/html/components/` exists with 3+ `*.html` component template files
- [ ] `PhantomInjector` exists on `window` and `PhantomCore.onReady()` calls actually inject
- [ ] `phantom-bridge.js` functionality merged — grep for `phantomFetch|_listeners|onSettingChange|_emit|setSetting|saveChanges` returns zero
- [ ] `build.js` produces minified output (`phantom-core.min.js` < 70% of `phantom-data.js`)
- [ ] `phantom-core.min.js` is actually minified (verify with `head -c 20`: starts with minified code)
- [ ] 5 dead files removed: `Post_Adapter`, `Settings_Adapter`, `Navigation`, `Blog_Card`, `phantom-bridge.js`
- [ ] Hero::render() and Footer::render() are wired and produce output in production flow
- [ ] `shell.php` has no `use` imports referencing removed classes
- [ ] All PHP files pass `php -l` syntax check
- [ ] All existing unit tests pass

### 6.9 Testing Strategy

**Framework**: PHPUnit (WordPress Core test suite conventions). Tests live in `tests/` directory.

**Running tests**:
```bash
# PHP syntax check (all files)
php -d error_reporting=E_ALL -l path/to/file.php

# PHPUnit (if configured)
cd /var/www/html/wp-content/plugins/phantom-core && phpunit

# JS lint (placeholder — configure when minification is added)
npx eslint frontend/assets/js/

# Verify minification
ls -lh frontend/assets/js/phantom-core.min.js
# Expect: phantom-data.js (concatenated, ~100KB) → phantom-core.min.js (minified, < 35KB)
```

**What to test for Phase 0**:
- PHP syntax: All 68+ files pass `php -l`
- AdapterInterface: Each adapter implements the interface — write 1 assertion per adapter
- RendererInterface: Each renderer implements the interface — write 1 assertion per adapter
- JS: `PhantomInjector` is defined on `window` after script loads
- Build: `phantom-core.min.js` is < 70% of `phantom-data.js` file size

### 6.10 Backward Compatibility

Phase 0 is purely additive + dead code removal. No existing functionality changes:
- Settings Registry, Customizer, REST API, CSS Generation Engine — **untouched**
- `Render_Engine` — imports cleaned, behavior unchanged
- Admin settings page — untouched
- Frontend templates — templates directory gets `components/` subdir, existing templates unchanged
- `phantom-data.js` — build script modified, output format unchanged
- WooCommerce rendering — `Hero::render()` and `Footer::render()` now produce content where they previously produced nothing (empty string). This is additive, not breaking.

**Rollback plan**: For each changed file, a git stash or branch commit exists. If a change breaks existing behavior, `git checkout -- file.php` restores the original.

### 6.11 Time Estimates

| Task | Est. Time | Dependencies |
|------|-----------|--------------|
| T0.1 — Create contracts + 3 interfaces | 15 min | None |
| T0.2 — Create ViewModels (3 typed classes) | 20 min | T0.1 (needs ViewModelInterface) |
| T0.3 — Refactor 4 adapters to implement AdapterInterface | 25 min | T0.1 (needs AdapterInterface) |
| T0.4 — Create 3 component template HTML files | 20 min | None |
| T0.5 — Refactor 5 renderers to implement RendererInterface + use inject() | 30 min | T0.1 + T0.4 |
| T0.6 — Create PhantomInjector.js + wire into phantom-core.js | 30 min | None |
| T0.7 — Merge phantom-bridge.js into modular JS | 45 min | T0.6 (uses PhantomInjector) |
| T0.8 — Fix build.js with terser + source maps | 15 min | T0.7 (after all JS changes) |
| T0.9 — Remove dead code files (Post_Adapter, Settings_Adapter, Navigation, Blog_Card) | 10 min | T0.3 + T0.5 |
| T0.10 — Wire Hero::render() + Footer::render() in WooCommerce_Injector | 15 min | T0.9 |
| T0.11 — Clean up shell.php + Render_Engine imports | 10 min | T0.9 |
| T0.12 — Syntax checks + tests + verify output | 20 min | All above |
| **Total** | **~4.5 hours** | |

---

## 7. Success Criteria (All Phases)

### Phase 0: Foundation
- [ ] Zero PHP syntax errors: `find . -name '*.php' -exec php -l {} \; 2>&1 | grep -v 'No syntax errors'` returns empty
- [ ] All 4 adapters pass `instanceof AdapterInterface` assertion
- [ ] All 5 renderers pass `instanceof RendererInterface` assertion and call `$this->inject()` instead of `str_replace`
- [ ] `frontend/html/components/` exists with 3+ `.html` files containing `{{UPPER_SNAKE_CASE}}` placeholders
- [ ] `phantom-core.min.js` file size < 70% of `phantom-data.js` after build
- [ ] `window.PhantomInjector` is defined and each method exists: `injectContent`, `injectSettings`, `injectMenus`, `injectProducts`
- [ ] `phantom-bridge.js` file deleted — grep for `phantomFetch`, `_listeners`, `onSettingChange`, `_emit`, `setSetting`, `saveChanges` returns zero
- [ ] 5 files removed: `class-post-adapter.php`, `class-settings-adapter.php`, `class-navigation.php`, `class-blog-card.php`, `phantom-bridge.js`
- [ ] WooCommerce_Injector calls `$this->hero->render()` and output appears on frontend hero section
- [ ] `shell.php` has no `use` imports for removed classes
- [ ] All existing unit tests pass: `phpunit` returns 0 failures

### Phase 1: Container + Events
- [ ] Container resolves services with auto-wiring
- [ ] Container supports shared (singleton) + factory instances
- [ ] EventDispatcher emits events via WordPress hooks
- [ ] All Engine classes use constructor injection via Container
- [ ] PHP events + JS PhantomEvents have matching names
- [ ] Unit tests cover Container, EventDispatcher

### Phase 2: Three Engines
- [ ] Data_Engine handles all WP/WC queries + normalization
- [ ] View_Engine handles all template loading + component rendering
- [ ] Asset_Engine handles all CSS/JS/fonts/headers
- [ ] Render_Engine is < 100 lines (thin coordinator)
- [ ] Each engine independently testable
- [ ] Unit tests cover each engine

### Phase 3: Demo Manager
- [ ] Admin UI lists all installed demos with preview
- [ ] One-click demo activation switches template_pack + flushes cache
- [ ] ZIP upload → extract → validate → install flow works
- [ ] `manifest.json` format is validated on install
- [ ] Deactivate + delete demo works

### Phase 4: Tokens + Presets
- [ ] Token scales defined for spacing, typography, color, radius
- [ ] Token Registry generates CSS custom properties
- [ ] Preset = full settings + tokens snapshot
- [ ] Admin UI: Save/Load/Delete presets
- [ ] 4 shipped presets produce visibly different sites

### Phase 5: Bridges + Animation
- [ ] PluginBridgeInterface defined with register/get_data/assets
- [ ] WooCommerceBridge replaces hardcoded WC in WooCommerce_Injector
- [ ] ElementorBridge, WPMLBridge, RankMathBridge stubs exist
- [ ] AnimationRegistry stores named animations
- [ ] CSS keyframes + JS triggers generated from registry

### Phase 6: Complete Demos

**Template inventory (22 templates per demo)**:

| # | Template | Route | Description |
|---|----------|-------|-------------|
| 1 | `index.html` | `/` | Homepage with hero, featured products, categories |
| 2 | `shop.html` | `/shop` | Product grid with filtering |
| 3 | `product-detail.html` | `/product/{slug}` | Single product with gallery, add-to-cart |
| 4 | `cart.html` | `/cart` | Cart with quantity controls |
| 5 | `checkout.html` | `/checkout` | WooCommerce checkout shortcode |
| 6 | `account.html` | `/account` | My Account page |
| 7 | `blog.html` | `/blog` | Blog post archive |
| 8 | `single.html` | `/blog/{slug}` | Single blog post |
| 9 | `about.html` | `/about` | About page |
| 10 | `contact.html` | `/contact` | Contact form |
| 11 | `faq.html` | `/faq` | FAQ accordion page |
| 12 | `404.html` | `404` | Custom 404 page |
| 13 | `search.html` | `/search` | Search results |
| 14 | `category.html` | `/category/{slug}` | Product category page |
| 15 | `wishlist.html` | `/wishlist` | Wishlist (WooCommerce) |
| 16 | `lookbook.html` | `/lookbook` | Visual lookbook grid |
| 17 | `sale.html` | `/sale` | On-sale products |
| 18 | `brand.html` | `/brand/{slug}` | Brand/designer page |
| 19 | `size-guide.html` | `/size-guide` | Sizing information |
| 20 | `shipping.html` | `/shipping` | Shipping information |
| 21 | `privacy.html` | `/privacy` | Privacy policy |
| 22 | `terms.html` | `/terms` | Terms of service |

- [ ] Fashion: 22/22 templates complete with full CSS, JS, and image assets
- [ ] Shoes: 22/22 templates complete with full CSS, JS, and image assets
- [ ] Furniture: 22/22 templates complete with full CSS, JS, and image assets
- [ ] Each demo has `manifest.json` (name, version, description, screenshot, author) + preview screenshot
- [ ] Switching demos changes visual identity across all 22 routes

---

## 8. Revision History

| Date | Version | Changes |
|------|---------|---------|
| 2026-07-26 | 1.0 | Initial master plan based on codebase audit |
| 2026-07-26 | 1.1 | Loop-engineering self-review: corrected line count inaccuracies (Navigation 110→38, Footer 40→17, Blog_Card 80→42, renderer total 520→256, ops 18→21), added Dead Code Decision Rules (6.2), PhantomInjector API spec (6.3), bridge merge spec (6.4), component template spec (6.5), Testing Strategy (6.9), Backward Compatibility (6.10), Time Estimates (6.11), Phase 6 template inventory, CSS Gen Engine in architecture, verifiable success criteria, resolved parallelization contradiction |
