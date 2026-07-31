# Phantom Visual Customizer — Design Spec

- **Date**: 2026-07-31
- **Status**: Approved (all 12 architectural adjustments locked)
- **Version**: 2.0.0
- **Entry point**: `http://localhost:8080/wp-admin/customize.php` (Appearance → Customize)

## 1. Context & Goal

Replace the current production Customizer (10 panels, 29 sections, 292 controls) with a
single-editor, component-driven, click-to-edit visual system modeled on Shopify / Framer /
Webflow. The native WordPress Customizer becomes the **one and only** customization
interface. The separate "Visual Customizer" admin page and the Design Studio admin page
are removed; their engines are migrated into the Customizer.

The backend architecture is untouched:

```
Component Registry
        ↓
Settings Registry
        ↓
Theme State Engine
        ↓
CSS Variable Generator
        ↓
Frontend
```

Only the entry point and the editing surface change.

## 2. Non-Negotiable Requirements (locked 2026-07-31)

1. **Customizer ONLY** — the final product exists only inside Appearance → Customize.
   No Visual Customizer admin page, no Design Studio, no Design Dashboard, no separate
   editor, no duplicate editor anywhere.
2. **The preview is the editor** — every edit starts by clicking an element in the live
   preview. Nothing is selected from long lists. The sidebar changes automatically to
   show only the controls for the selected component.
3. **No giant color lists** — the hundreds of individual color controls are gone.
   Workflow: click the Color tool → click an element → choose property → pick color.
   One workflow, not hundreds of settings.
4. **Same system for typography** — click the Typography tool → click a heading →
   inspector opens with Font / Size / Weight / Alignment / Spacing / Color. Nothing else.
5. **Inline text editing** — every editable text is editable directly in the preview
   (Shop → Shopping). The system distinguishes data sources and never overwrites the
   wrong one:
   - Theme text → Settings Registry (`phantom_*`)
   - Page title → WordPress Page
   - Blog title → Post
   - Product title → WooCommerce Product
   - Menu → Menu API
   - Widget → Widget API
6. **Asset editing** — one universal workflow: click asset → upload/replace → done;
   remove → default asset automatically returns. Applies to Logo, Mobile Logo, Sticky
   Logo, Hero Desktop, Hero Mobile, Favicon, Placeholder, Author Avatar, Category Banner.
7. **Everything is component-based** — nothing hardcoded. Every editable item belongs
   to a registered component (Hero, Header, Footer, Product Card, Product Grid, Blog
   Card, Navigation, Button, Search, Sidebar…). Each component owns colors, typography,
   spacing, assets, responsive, animations.
8. **No duplicate storage** — Settings Registry → Theme State Engine → CSS Variable
   Generator → Frontend remains the single pipeline. No second settings system, no
   duplicate options.
9. **Live preview rules** — changes are instant, no page refresh, **no PHP request per
   color change**, CSS variables only. Publish writes to storage.
10. **Publish pipeline** — the WordPress Publish button is the only publish action:
    (1) save Settings Registry, (2) create History snapshot, (3) regenerate CSS,
    (4) generate versioned CSS file, (5) clear cache, (6) reload preview.
11. **Frontend independence** — the backend never depends on one frontend. The same
    Phantom Core supports Ecommerce, Blog, Portfolio, Agency, SaaS, Restaurant, Fashion,
    Real Estate, Landing Pages. Changing the frontend requires only new HTML, new
    component templates, new component definitions — never backend rewrites. Component
    resolution (aliases) is data-driven, not hardcoded to the AETHER templates.
12. **Phase A acceptance** (see §10).

## 3. Architecture

### 3.1 Integration model

- **Preview side** (`customize_preview_init`): enqueue the existing
  `admin/js/visual-customizer/selection-engine.js` + `admin/css/visual-customizer.css`
  into the Customizer's live preview iframe. Activation gate changes from
  `$_GET['vc_preview']` to `is_customizer_preview()` (keep `vc_preview` as a
  secondary gate for testability). Hover → outline; click → `vc-element-selected`
  postMessage to the parent.
- **Sidebar side** (`customize_controls_enqueue_scripts`): one new bridge file,
  `admin/js/customizer-visual-editor.js` (~400 lines). It:
  - listens for `vc-element-selected` (and `vc-element-locked`, `vc-engine-ready`),
  - fetches `GET /phantom/v1/components/{name}/inspector` (existing
    `Inspector_Factory` renderer — reused unchanged),
  - injects the panel HTML into a **Visual Inspector** container inside the Customizer
    sidebar,
  - binds the existing control classes (`.vc-color-picker`, `.vc-range`, `.vc-select`,
    `.vc-text-input`, `.vc-btn-upload`, `.vc-btn-reset`),
  - applies changes in parallel: postMessage `vc-apply-css` (instant preview, existing
    engine behavior) **and** `wp.customize('phantom_<key>').set(value)` (native setting →
    native `customizer-preview.js` pipeline → native dirty state).
- **No custom save button anywhere.** The native Customizer Publish button is the only
  publish path. The old `POST /settings` + `POST /publish` VC flow is replaced by the
  native changeset save + `customize_save_after` server-side build (§3.3).

### 3.2 Component resolution (fixes the known gap)

Frontend `data-component` names (e.g. `products-grid`, `product-card`, `blog-grid`) do
not match `Component_Definition_Registry` ids (e.g. `products`, `product_card`, `blog`),
so most clicks today produce "Component not found". Fix, data-driven:

1. **Alias map** — `includes/Design/data/component-aliases.php`: frontend name →
   definition id, loaded by `Component_Definition_Registry` (new `resolve()` method,
   also used by `Inspector_Factory`). Aliases are data, not code — a new frontend only
   adds a new data file + definitions.
2. **Generic fallback definition** — if neither the name nor an alias resolves, build
   a runtime definition (content / background / typography / spacing tabs) from the
   element's `data-editable` fields (already present in the static HTML). **No click
   ever fails.**
3. Definitions enriched for the edit targets: Hero (background/overlay/heading/
   description/button), Product Card (background/border/shadow/price/sale badge),
   Product Grid (columns/gap), Blog Card (image/title/date/read-more), Footer
   (background/heading/links/copyright/icons), Navigation (links/colors), Button
   (bg/text/hover), Search (field/button), Sidebar (widgets/bg/border).

### 3.3 Publish pipeline (native Publish button)

`customize_save_after` hook (extends existing `sync_options()`):

1. Save Settings Registry (existing per-setting option sync into `phantom_options`).
2. Create History snapshot (`History_Manager` — existing server code, previously called
   by the VC page).
3. Commit Theme State Engine snapshot (`ThemeStateEngine::commit_preview()` — existing).
4. Regenerate CSS + generate versioned CSS file (existing asset Pipeline build, the
   server-side half of the old `POST /publish` — re-entered, not a new system).
5. Clear caches (`Settings_Registry::flush_cache()` + pipeline cache flush).
6. Preview reloads via native Customizer behavior (dirty settings → save → preview).

### 3.4 Inline text editing (fixes the dead path)

`admin/js/visual-customizer/inline-editor.js` currently runs on the admin page (wrong
document) and its `vc-inline-content-changed` message is unhandled. Fix:

- Enqueue it **inside the preview iframe** (with the selection engine) — that is where
  the DOM lives.
- The new bridge handles `vc-inline-content-changed`: the payload carries the element's
  data-source (`theme | page | post | product | menu | widget`) + entity id + field +
  new content. Routing:
  - `theme` → `POST /phantom/v1/settings` (existing endpoint, `phantom_*` keys).
  - `page` / `post` → `POST /wp/v2/{post_type}/{id}` with `edit_theme_options`/edit-cap
    checks (core REST, no new code).
  - `product` → `POST /wc/v3/products/{id}`-equivalent (`/wp/v2/product` with edit caps)
    — only the exact edited field, never bulk overwrite.
  - `menu` → `wp_rest` nav-menus endpoint (`/wp/v2/menu-items`), update title only.
  - `widget` → widget update via `wp_ajax_widgets`-equivalent REST (phase B+ detail).
  - Every write checks capabilities and sanitizes; failures surface a notice in the
    Customizer, never silently overwrite the wrong source.

## 4. Customizer structure (the sidebar users see)

```
Site Identity            (core — logo, mobile logo, sticky logo, favicon via core + asset defaults)
Menus                    (core — normal WP menus)
Widgets                  (core)
Homepage                 (front page + hero visibility + featured sections — ~5 controls)
Blog                     (layout, sidebar, posts per page — ~4 controls)
WooCommerce              (shop layout, per-row, archive sidebar — ~5 controls)
Additional CSS           (core)
─────────────────────────────────────
PHANTOM
├─ Visual Colors         (5 global colors: Primary, Secondary, Accent, Success, Error)
├─ Visual Typography     (global font family / base size / heading weight / line height)
├─ Visual Spacing        (container width, base spacing scale, border radius)
├─ Visual Assets         (10 asset rows with upload / replace / remove / reset + default fallback)
├─ Presets               (7 visual cards — Phase C)
├─ Responsive            (device-aware editing — Phase C)
└─ Live Preview          (Start Editing toggle, undo/redo, hint text)
```

- All 7 current global panels (Branding, Global Colors, Global Typography, Presets &
  Design System, Responsive, Performance & SEO, Integrations) are **removed** from the
  Customizer. Settings stay in `Settings_Registry` (Rule 1 — backend single source).
- All `[Dev]` legacy panels are removed entirely, including under `PHANTOM_DEV_MODE`.
- The Visual Inspector container is a section inside the PHANTOM panel; it is empty
  until a click selects a component, then shows only that component's panels
  (Requirement 2 — the frontend is the editor, the sidebar follows).

### 4.1 Visual Colors (Phase A)

5 `ast-color` controls bound to `phantom_color_primary|secondary|accent|success|error`
(all five keys already exist in `Settings_Registry::get_css_var_map()` → `--primary--color`
etc.). All other color tokens (text, heading, muted, border, background, dark-mode
variants) are **derived** by `Phantom_Global_Palette` from these 5 via a derivation map
(Phase B: full derivation; Phase A ships the 5 controls + existing palette output).
Component colors are contextual only (click → inspector), never a list.

### 4.2 Visual Assets (Phase A)

`Media_Asset_Registry::register_defaults()` extended to the 10 required assets
(existing 9 are close — rename/add):
`logo`, `mobile_logo`, `sticky_logo`, `favicon`, `hero_desktop` (responsive sizes),
`hero_mobile`, `blog_placeholder`, `product_placeholder`, `category_banner`,
`author_avatar` — each with bundled default (existing `assets/` files), resolving from
setting `asset_{key}` (numeric attachment id → URL, else raw URL, else default — existing
`get_url()` logic).

- New custom control type `ast-asset-grid` renders asset rows (current / upload /
  replace / remove / reset) — reuses `Inspector_Factory::render_asset_row` markup and
  the existing `.vc-asset-row[data-asset]` + `[data-asset]` JS contract.
- **Wire `includes/Assets/class-asset-rest.php`** (currently dead — never initialized):
  `/assets`, `/assets/upload`, `/assets/replace`, `/assets/reset`, `/assets/remove`.
  Mobile hero automatically falls back to desktop hero when empty (template `<picture>`
  logic already handles this).

### 4.3 Visual Typography / Spacing (Phase B)

Global essentials live in their sections (font family, base size, heading weight,
line height / container width, spacing scale, radius — `typography_*`,
`container_width`, `spacing_*`, `border_radius*` keys, all existing). Everything else is
component-scoped via click-to-edit inspectors (the definitions' existing typography /
spacing tabs).

## 5. Click-to-edit workflows (one model for everything)

```
User clicks element
        ↓
Preview selects component (hover outline → click → vc-element-selected)
        ↓
Sidebar changes automatically — only that component's controls
        ↓
Edit (color / typography / spacing / asset / inline text)
        ↓
Instant preview via CSS variables (postMessage vc-apply-css)
        ↓
Publish (native button) writes to storage
```

- Colors: click Color tool → click element → choose property → pick color.
- Typography: click Typography tool → click text → Font/Size/Weight/Alignment/
  Letter-spacing/Line-height/Transform.
- Spacing: click section → Padding/Margin/Container width/Border radius/Gap sliders.
- Assets: click asset → upload/replace; remove → default returns.
- Text: contentEditable inline (Shop → Shopping), committed on Enter.

## 6. Responsive (Phase C)

Reuse WordPress core's native device switcher (desktop/tablet/mobile in the Customizer
toolbar) — no custom toggle. On `customize-previewed-device-change`, the bridge forwards
the viewport to the engine; the engine + `Inspector_Factory` already support viewport
overrides via `State_Manager` (`@viewport` suffix on CSS vars, per-viewport fields).

## 7. Presets (Phase C)

7 visual cards — Luxury, Modern, Glass, Classic, Dark, Minimal, Light — using the
existing 7 core presets (`core:luxury|modern|glass|classic|dark|minimal|light` in
`includes/Design/data/presets.php`), the existing `PresetManager::apply()`, and the
existing `ast-preset-card` control type. One click restyles everything (tokens →
Settings Registry via the same pipeline). No dropdown.

## 8. Removals (Phase A)

| Item | File(s) | Fate |
|---|---|---|
| Visual Customizer admin page | `admin/class-visual-customizer-page.php` + registration in `phantom-core.php` (plugins_loaded pri 26) | **Deleted** (page + submenu). Engine JS files stay — migrated into the Customizer. |
| Design Studio admin page | `admin/class-design-studio-page.php`, tab in `admin/class-phantom-admin.php:54`, require in `phantom-core.php:575` | **Deleted** |
| Design Studio iframe injection | `templates/shell.php` `?design-studio=1` block + `admin/js/design-studio.js` + `admin/css/design-studio.css` | **Deleted** |
| Dashboard link | `admin/class-dashboard-page.php:72` "Open Design Studio" button | **Removed** |
| Customizer legacy panels | `includes/class-customizer.php` (7 global + 13 `[Dev]` panels, Design Studio links in descriptions, welcome overlay) | **Removed** |
| Design/design-studio REST routes | `includes/class-rest-controller.php` `/design-studio/*`, `/design/*` | **Keep** — Presets (Phase C) and tests depend on them. |
| Phantom Dashboard / Demo Manager / Performance / Settings admin pages | — | **Keep** — operational, not editors. |

## 9. Frontend independence

- Component resolution is data-driven (§3.2): aliases in a data file, generic fallback
  built from DOM attributes.
- Adding a frontend = new `frontend/html/*` + component definitions + optional alias
  data file. Zero backend changes.
- All CSS variables are generated by the existing pipeline — the frontend consumes
  `var(--…)`; the backend never knows frontend structure.

## 10. Phase plan & acceptance criteria

### Phase A — Customizer shell + engine migration

**Ship:** new section list (§4), selection engine in the preview, `customizer-visual-editor.js`
bridge, Visual Inspector container, component alias map + generic fallback, Visual Colors
(5 global colors), Visual Assets (10 assets + Asset_Rest wired), Live Preview section
(Start Editing toggle), publish pipeline via `customize_save_after`, removals (§8).

**Acceptance:**
- ✅ The only editor is `Appearance → Customize`.
- ✅ The separate Visual Customizer page is removed.
- ✅ Clicking any editable element in the preview selects it (hover outline → click → inspector).
- ✅ The sidebar becomes contextual — only controls for the selected component.
- ✅ Visual Colors works with click-to-select workflow, not long color lists.
- ✅ Visual Assets works with upload/remove/reset and automatic fallbacks.
- ✅ Live Preview is instant using CSS variables.
- ✅ Settings Registry, Theme State Engine, CSS Variable Generator remain the single
  source of truth.
- ✅ All existing tests + E2E pass without regressions.

### Phase B — Visual editing depth

Inspector depth for colors/typography/spacing on all components, inline text editing
with data-source routing (§3.4), 5-color derivation map in `Phantom_Global_Palette`,
asset replace flows, undo/redo in the Customizer.

### Phase C — Polish

Preset cards (§7), responsive per-device editing via core device switcher (§6),
instant CSS-variable-only preview polish, production CSS build on Publish.

## 11. Verification & test impact

- **E2E** (`customizer-e2e.py`, temp dir): updated to the new acceptance contract —
  assert approved sections exist, old panels absent, Start Editing toggle, click hero
  element in preview iframe → inspector renders in sidebar, change color → CSS var
  changes + setting dirty, native Publish saves, 0 console errors.
- **PHPUnit**: customizer inventory/design-studio tests updated for the new structure
  (suite must stay green; expected count grows).
- `php -l` on all changed files; `smoke-packs.php` ALL PASS; Docker deploy + md5
  verify; `debug.log` empty.
- Playwright manual pass of the click-to-edit flow after deploy.

## 12. Risks & mitigations

| Risk | Mitigation |
|---|---|
| Engine assumes VC page DOM (`#vc-preview-iframe` etc.) | Bridge is a new file; engine JS untouched on the iframe side; sidebar rendering driven by the bridge into the Customizer DOM. |
| Native Customizer iframe differs from raw frontend | E2E already drives the native preview iframe (530 CSS vars, customizer-preview.js works) — selection engine only adds listeners. |
| Component alias map misses names | Generic fallback guarantees no dead clicks; alias map is data (easy to extend). |
| Inline text writes wrong source | Data-source attribute on editable elements + capability checks + exact-field-only writes (§3.4). |
| Suite/E2E regressions | E2E rewritten as the acceptance contract; PHPUnit updated in the same commit as the structural change. |
