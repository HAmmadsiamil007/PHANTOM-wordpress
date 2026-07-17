# Phantom Core — Customization Guide

## Total User-Accessible Controls: 1,000–1,500+

| System | Controls |
|--------|---------|
| WordPress Core (existing) | 100+ |
| WooCommerce (existing) | 250+ |
| Theme Settings (Phantom) | 400–700 |
| Customizer Panels | 30–50 |
| Component Settings | 300–500 |
| **Total** | **1,000–1,500+** |

---

## Three Ways to Customize

### 1. WordPress Customizer (Visual)

**URL:** `/wp-admin/customize.php`

**15 Panels:**

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
| Pages | About, Contact, FAQ, Team, Testimonials, etc. | Refresh |

**Live preview (postMessage):** All `color` type settings + 39 CSS var mappings update instantly without page refresh. Everything else requires refresh.

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

### All 39 CSS Var Mappings

```
--primary--color, --secondary--color, --accent--color
--body--bg, --body--text--color, --body--font--size
--heading--color, --heading--font--family, --heading--font--weight
--body--font--family
--link--color, --link--hover--color
--button--bg, --button--text--color, --button--hover--bg, --button--hover--text--color
--button--radius, --button--padding--x, --button--padding--y
--header--bg, --header--text--color, --header--height
--topbar--bg, --topbar--text--color
--footer--bg, --footer--text--color, --footer--heading--color
--container--width, --content--width, --sidebar--width
--section--padding--y
--input--bg, --input--border--color, --input--radius
--announcement--bg, --announcement--text--color
--hero--overlay--color, --hero--overlay--opacity
```

Numeric keys (19 total) automatically get `px` suffix: `header_height`, `container_width`, `body_font_size`, `button_radius`, `button_padding_x`, `button_padding_y`, `hero_height`, `section_padding_y`, `content_width`, `sidebar_width`, etc.

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
Builds :root { --primary--color: #ff0000; ... } style block
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

The frontend is **fully decoupled** from the backend. See `FRONTEND-GUIDE.md` for complete instructions.

### Quick Summary

To replace the frontend:

1. **Replace HTML files** in `frontend/*.html` — keep `[data-phantom]` attributes
2. **Keep the data-binding attributes** — they drive JS injection
3. **Or re-theme via CSS** — all visual tokens are CSS vars managed by Customizer
4. **Or use custom CSS/JS** — use the Custom Code settings in Advanced panel

The backend (Settings, Customizer, REST API, WooCommerce) remains untouched. Only the HTML templates and/or CSS change.
