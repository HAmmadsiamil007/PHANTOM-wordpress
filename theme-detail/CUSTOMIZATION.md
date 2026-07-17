# Phantom Core — Customization Guide

## Total User-Accessible Controls

| System | Controls | Status |
|--------|---------|--------|
| WordPress Core (existing) | 100+ | ✅ All use WP native |
| WooCommerce (existing) | 250+ | ✅ All use WC native |
| Theme Settings (Phantom) | **555 settings** | ✅ 44 sections, fully configurable |
| Customizer Panels | 14 panels, 49 sections | ✅ Mapped from registry |
| **Total** | **800–900 controls** | ⚠️ ~70% of ideal "1,000-1,500+" target |

### Missing Controls (what would add to reach 1,000+)

Adding these would bring Phantom Core to the professional multipurpose tier:

- **Branding**: Retina/dark/mobile logos, SVG upload (10 settings)
- **Header**: Wishlist, compare, notification icons, transparent mode (12 settings)
- **Mega Menu**: Per-item config, columns, icons (15 settings)
- **Product Page**: Video, 360°, sticky ATC, upsells/cross-sells (12 settings)
- **Blog**: Masonry, author bio, reading time (8 settings)
- **Animations**: Page loader type, scroll reveal, GSAP, Swiper, hover (25 settings)
- **Accessibility**: Keyboard nav, skip links, ARIA, focus states (10 settings)
- **Performance**: Minification, image optimization, font loading (10 settings)
- **SEO**: Breadcrumbs schema, sitemap, meta templates (8 settings)
- **Integrations**: GA4 injection, Maps API, Meta Pixel, newsletter (8 settings)
- **Import/Export**: Reset, presets, backup/restore (8 settings)
- **3D Effects**: Tilt intensity, perspective, performance (6 settings)
- **WooCommerce**: Product attributes, variations, reviews APIs (10 settings)

**Total possible additions: ~140 settings** — bringing the total to ~695+ phantom settings (still short of 700+ spec, but within range).

---

## Three Ways to Customize

### 1. WordPress Customizer (Visual)

**URL:** `/wp-admin/customize.php`

**14 Panels (49 sections):**

| Panel | Sections | Live Preview |
|-------|----------|-------------|
| Branding | Site Identity, Logo, Favicon | CSS vars |
| Header & Navigation | Header, Top Bar, Navigation, Announcement Bar | CSS vars + hero/logo/footer |
| Hero & Home | Hero, Home Sections, Collections | Hero text/images |
| Products & Shop | Product Cards, Shop Page, Product Page | CSS vars |
| WooCommerce | Cart, Checkout, My Account | Refresh |
| Blog | Archive, Single Post | Refresh |
| Footer | Layout, Widgets, Copyright | CSS vars + footer text |
| Typography | Fonts, Sizes, Weights | CSS vars |
| Colors & Buttons | Colors, Buttons, Forms, Spacing | CSS vars (postMessage) |
| Layout & Effects | Layout, Responsive, Animations, 3D | CSS vars |
| Search | AJAX, Suggestions | Refresh |
| Performance & SEO | Performance, SEO | Refresh |
| Accessibility | Contrast, Keyboard, ARIA | Body classes |
| Advanced | Integrations, Custom Code, Import/Export | Refresh |

**Live preview coverage:** 
- ✅ All `color` type settings → automatic `postMessage`
- ✅ 7 hero settings → explicit `postMessage`
- ❌ Everything else → requires page refresh
- ✅ ~42 CSS var changes update instantly (via color postMessage)

---

### 2. Admin Settings Page (Form)

**URL:** `/wp-admin/themes.php?page=phantom-core-settings`

**15 Tabs** matching Customizer panels. Full CRUD with:

- **Text inputs** — Strings, URLs, emails
- **Textareas** — Long text, HTML content
- **Checkboxes** — Boolean toggles
- **Number inputs** — Integers, floats with min/max/step
- **Color pickers** — WordPress native color picker
- **Select dropdowns** — Single choice
- **Multi-select** — Multiple choices
- **Image upload** — WordPress media library integration
- **Code editors** — Syntax-highlighted CSS/JS/HTML/JSON
- **Repeater fields** — Dynamic add/remove rows with sub-fields (bool, select, color, text, image)
- **Dependency logic** — Fields show/hide based on other field values

**Verification:** Nonce + `manage_options` capability check.

---

### 3. REST API (Programmatic)

**Base URL:** `/wp-json/phantom/v1`

**Authentication:** `manage_options` capability for write operations.

**Endpoints:**

```bash
# Get all settings
GET /wp-json/phantom/v1/settings

# Get settings filtered by section
GET /wp-json/phantom/v1/settings?section=colors

# Update a single setting
PUT /wp-json/phantom/v1/settings/primary_color
{ "value": "#ff0000" }

# Bulk update
POST /wp-json/phantom/v1/settings
{ "settings": { "primary_color": "#ff0000", "header_sticky": true } }

# Export all settings
POST /wp-json/phantom/v1/export

# Import settings
POST /wp-json/phantom/v1/import
{ "data": { ... } }

# Get setting schema
GET /wp-json/phantom/v1/schema
```

---

## CSS Variable Architecture

Design tokens are exposed as CSS custom properties on `:root`. This is the bridge between settings and frontend styling.

### How it works

```
Settings_Registry → CSS Var Map → :root { --primary--color: #... }
                                        │
                    ┌────────────────────┼────────────────────┐
                    ▼                    ▼                    ▼
           Shell (PHP)           Customizer (PHP)      Customizer Preview (JS)
           inject_customizer     get_inline_css()      style.setProperty()
           _css() → <style>      → backend use         → live preview
```

### CSS Var Naming Convention

Settings keys are converted to CSS vars with `--` prefix and `--` as separator:

| Setting Key | CSS Variable |
|------------|-------------|
| `primary_color` | `--primary--color` |
| `header_bg` | `--header--bg` |
| `body_font_size` | `--body--font--size` |
| `container_width` | `--container--width` |
| `button_radius` | `--button--radius` |

### All 63 CSS Var Mappings

**Header (8):**
`--header--bg`, `--header--text--color`, `--header--padding--y`, `--header--padding--x`, `--header--border--color`, `--mobile--header--height`, `--header--banner--height`

**Navigation (2):**
`--nav--menu--height`, `--nav--submenu--width`

**Footer (5):**
`--footer--text--color`, `--footer--heading--color`, `--footer--link--color`, `--footer--border--color`, `--footer--bg--color`

**Typography (8):**
`--heading--font`, `--body--font`, `--base--font--size`, `--heading--font--weight`, `--body--font--weight`, `--body--line--height`, `--letter--spacing`, `--text--case`

**Colors (12):**
`--primary--color`, `--secondary--color`, `--accent--color`, `--text--color`, `--heading--color`, `--bg--color`, `--header--bg--color`, `--footer--bg--color`, `--link--color`, `--link--hover--color`, `--border--color`, `--sale--color`

**Buttons (8):**
`--button--bg`, `--button--text--color`, `--button--hover--bg`, `--button--hover--text`, `--button--radius`, `--button--padding--y`, `--button--padding--x`, `--button--font--size`

**Forms (2):**
`--input--radius`, `--input--height`

**Spacing (6):**
`--section--padding--y`, `--section--padding--x`, `--container--gutter`, `--content--gap`, `--element--margin`, `--widget--spacing`

**Layout (5):**
`--container--width`, `--boxed--width`, `--content--width`, `--sidebar--width`, `--column--count`

**Responsive (4):**
`--breakpoint--xl`, `--breakpoint--lg`, `--breakpoint--md`, `--breakpoint--sm`

**Announcement Bar (2):**
`--announcement--bg`, `--announcement--text--color`

**Misc (1):**
`--section--spacing`

### PX Keys (22 numeric values requiring `px` suffix)

```
header-padding-y, header-padding-x, mobile-header-height, header-banner-height,
nav-menu-height, nav-submenu-width, base-font-size, button-radius,
button-padding-y, button-padding-x, button-font-size, input-radius,
input-height, section-padding-y, section-padding-x, container-gutter,
content-gap, element-margin, widget-spacing, container-width, boxed-width,
sidebar-width
```

### ⚠️ Important: CSS Var Duplication

The CSS var maps and px key lists are duplicated across 2 files:

| File | Location | What's Duplicated |
|------|----------|-------------------|
| `class-customizer.php` | `get_css_var_map()` (~line 260) | 63 var mappings |
| `class-customizer.php` | `get_inline_css()` px injection (~line 410) | 22 px keys |
| `templates/shell.php` | `inject_css_variables()` (~line 330) | 63 var mappings + 22 px keys |

**Any change to CSS vars must be applied in BOTH files.** There is no shared source of truth.

---

## How Customization Reaches the Frontend

### PHP Path (initial page load)

```
User sets "primary_color" → update_option('phantom_primary_color', '#ff0000')
        │
        ▼
Shell::inject_customizer_css() reads ALL phantom_options from DB
        │
        ▼
Builds :root { --primary--color: #ff0000; ... } style block (63 vars)
        │
        ▼
Injected as <style id="phantom-customizer-css"> before </head>
```

### JS Path (Customizer live preview)

```
User changes "primary_color" in Customizer
        │
        ▼
wp.customize('phantom_primary_color', (val) => {
    document.documentElement.style.setProperty('--primary--color', val);
});
        │
        ▼
CSS var updates → all elements using var(--primary--color) change instantly
```

### JS Path (frontend data injection)

```
phantom-data.js fetches /page-data
        │
        ▼
injectSettings() finds [data-phantom="site_title"]
        │
        ▼
Sets textContent/sr/c/href from API response
```

---

## Disconnecting & Replacing the Frontend

The frontend is **fully decoupled** from the backend. See `FRONTEND-REPLACE-GUIDE.md` for complete instructions.

### Quick Summary

To replace the frontend:

1. **Replace HTML files** in `frontend/*.html` — keep `[data-phantom]` attributes
2. **Keep the data-binding attributes** — they drive JS injection
3. **Or re-theme via CSS** — all visual tokens are CSS vars managed by Customizer
4. **Or use custom CSS/JS** — use the Custom Code settings in Advanced panel
5. **Keep CSS class names** used by phantom-data.js (see FRONTEND-REPLACE-GUIDE.md)

The backend (Settings, Customizer, REST API, WooCommerce) remains untouched. Only the HTML templates and/or CSS change.
