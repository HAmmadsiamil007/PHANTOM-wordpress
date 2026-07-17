# Phantom Core — Architecture

## Overview

Phantom Core is a **decoupled WordPress framework** that replaces WordPress's traditional PHP template hierarchy with a **static HTML SPA architecture**. The frontend is pure static HTML files; all dynamic data is injected client-side via a custom REST API.

```
┌─────────────────────────────────────────────────────────────────┐
│                     WordPress Core                              │
│  (Users, Posts, Pages, Media, Comments, Roles, Options API)     │
└──────────────────────────┬──────────────────────────────────────┘
                           │
┌──────────────────────────▼──────────────────────────────────────┐
│                    Phantom Core Plugin                          │
│                                                                │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────┐  │
│  │Settings      │  │Customizer    │  │REST API              │  │
│  │Registry      │◄─┤15 panels     │  │phantom/v1            │  │
│  │555 settings  │  │39 sections   │  │20+ endpoints         │  │
│  │43 sections   │  │live preview  │  │CRUD + public         │  │
│  └──────┬───────┘  └──────────────┘  └──────────┬───────────┘  │
│         │                                        │              │
│  ┌──────▼────────────────────────────────────────▼───────────┐  │
│  │                   Shell (SPA Router)                       │  │
│  │  template_redirect → map URL → static HTML → inject data  │  │
│  └────────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────────┘
                           │
┌──────────────────────────▼──────────────────────────────────────┐
│                    Frontend (Static HTML + JS)                   │
│                                                                │
│  frontend/*.html  ←  phantom-data.js  ←  REST API             │
│  [data-phantom] attributes bind settings to DOM                │
└──────────────────────────────────────────────────────────────────┘
```

---

## Core Components

### 1. Settings Registry (`Settings_Registry`)

**File:** `includes/class-settings-registry.php`

The master settings repository. Defines **555 settings** across **43 sections**. Every setting has:

- `key` — Unique identifier (e.g., `general_site_logo`)
- `type` — `string|bool|int|float|color|select|image|text|code|repeater|array|number|multiselect`
- `default` — Default value
- `sanitize` — Sanitization callback
- `label` — Human-readable name
- `section` — Group slug (e.g., `branding`, `header`, `hero`)
- `transport` — `postMessage` (live preview) or `refresh`
- `css_property` — Maps to CSS custom property (e.g., `--primary--color`)
- `css_selector` — CSS selector (default `:root`)
- `dependencies` — Conditional visibility rules

**Storage:** Each setting stored as `wp_option` with key `phantom_{key}`.

**Singleton.** Accessed by every other component.

---

### 2. Customizer (`Customizer`)

**File:** `includes/class-customizer.php`

Bridges Settings Registry → WordPress Customizer. Defines **15 panels**, **~39 sections**.

**Panels:**
1. `phantom_branding` — Logo, favicon, site identity
2. `phantom_header` — Header layout, topbar, navigation, announcement bar
3. `phantom_hero` — Hero banner, home sections, collections
4. `phantom_products` — Product cards, shop page, product page
5. `phantom_woocommerce` — WooCommerce settings
6. `phantom_blog` — Blog layout, single post
7. `phantom_footer` — Footer layout, widgets, copyright
8. `phantom_typography` — Fonts, sizes, weights
9. `phantom_colors` — Color scheme, buttons, forms, spacing
10. `phantom_layout` — Layout, responsive, animations, 3D effects
11. `phantom_search` — AJAX search, suggestions
12. `phantom_performance` — Performance & SEO
13. `phantom_accessibility` — Accessibility features
14. `phantom_advanced` — Integrations, custom code, import/export
15. `phantom_pages` — Static page content (about, contact, FAQ, team, etc.)

**Transport logic:**
- `color` type → `postMessage` (instant preview, no refresh)
- All others → `refresh` (unless explicitly set to `postMessage`)
- 39 CSS var settings use `postMessage` for live preview

**CSS Var Map:** 39 settings map directly to CSS custom properties. Both PHP (Shell) and JS (customizer-preview.js) manage these.

---

### 3. REST API (`Rest_Controller`)

**File:** `includes/class-rest-controller.php`

Namespace `phantom/v1`. **20+ endpoints**:

| Endpoint | Method | Auth | Purpose |
|----------|--------|------|---------|
| `/settings` | GET/POST | admin | List/update all settings |
| `/settings/{key}` | GET/PUT/DELETE | admin | Single setting CRUD |
| `/schema` | GET | admin | Setting schemas |
| `/options` | GET | admin | Filtered design options |
| `/export` | POST | admin | Export all settings |
| `/import` | POST | admin | Import settings |
| `/cache/flush` | POST | admin | Flush caches |
| `/posts` | GET | public | Blog posts (paginated) |
| `/posts/{slug}` | GET | public | Single post |
| `/pages/{slug}` | GET | public | Single page |
| `/categories` | GET | public | Product + post categories |
| `/menus/{location}` | GET | public | Menu tree |
| `/products` | GET/POST | public/admin | Products |
| `/products/featured` | GET | public | Featured products |
| `/products/{id}` | GET/PUT/DELETE | public/admin | Single product |
| `/cart` | GET | public | Cart contents |
| `/page-data` | GET | public | **Mega-endpoint** — settings + menus + products + posts + categories + cart |

**WooCommerce:** All product/cart endpoints guarded by `class_exists('WooCommerce')`.

---

### 4. Shell (SPA Router)

**File:** `templates/shell.php`

The frontend rendering engine. Hooks `template_redirect` at priority 0 to intercept **all** frontend requests.

**Flow:**
1. Parse URL → slug (e.g., `/shop` → `shop`)
2. Bypass for: `wp-json`, `wp-admin`, `wp-login`, static files (CSS/JS/images)
3. Map slug → HTML template from route table
4. Inject SEO meta (title, description, OG, Twitter, JSON-LD, base tag, WC nonce)
5. Inject Customizer CSS (`:root { --primary--color: #... }`)
6. Set security headers (CSP, X-Frame-Options, etc.)
7. Output HTML and `exit`

**Route table:** 40+ routes mapping slugs to `frontend/*.html` files.

**Dynamic routes:**
- `/product/{slug}` → `product-detail.html`
- `/blog/{slug}` → `single-blog.html`

---

### 5. Admin Settings Page (`Settings_Page`)

**File:** `admin/class-settings-page.php`

Full CRUD UI under **Appearance > Phantom Core**. 15 tabs with all field types:

- Text, textarea, number, checkbox, select, multiselect
- Color picker, image upload, code editor
- Repeater fields with sub-fields (bool, select, color, text, image)
- Dependency (conditional) logic via `data-dependencies` attributes
- Nonce + capability verification

---

### 6. Frontend JavaScript (`phantom-data.js`)

**File:** `frontend/assets/js/phantom-data.js`

The core frontend data bridge. **1040 lines**. Runs on every page.

**Init sequence:**
1. Fetch cart count
2. Fetch `/page-data` (mega-endpoint)
3. Inject settings into `[data-phantom]` elements
4. Inject banner (hero section)
5. Inject footer (logo, about, copyright, contacts, social)
6. Inject SEO meta tags
7. Inject menus into `[data-phantom-menu]` elements
8. Inject products into `[data-phantom-products]`
9. Inject posts into `[data-phantom-posts]`
10. Inject categories into `#category1`
11. Inject single post (via `?post_id` or `[data-phantom-post]`)
12. Inject single product (via `?product_id` or `[data-phantom-product]`)
13. Inject cart into `.shopping-cart-info`
14. Initialize WooCommerce (add-to-cart, quantity, remove)
15. Initialize checkout form
16. Hide preloader

**WooCommerce integration:**
- Add to cart: `wc-ajax=add_to_cart`
- Remove from cart: `wc-ajax=remove_from_cart`
- Quantity update: Store API `POST /wc/store/v1/cart/update-item`
- Checkout: `wc-ajax=checkout`

---

### 7. Customizer Live Preview JS

**File:** `admin/js/customizer-preview.js`

Runs in the Customizer iframe. Auto-binds CSS vars + handles DOM-specific changes:

- **Auto CSS var bind:** Iterates `PhantomCustomizer.cssVarKeys`, sets `document.documentElement.style.setProperty()`
- **Header sticky:** Toggles `.sticky-header` class
- **Hero banner:** heading, title, description, button text/URL, images
- **Logo:** site logo, footer logo
- **Footer:** about text, address, copyright

---

### 8. Cache Engine

**File:** `includes/Engine/Cache.php`

Transient-based caching with `phantom_cache_` prefix:

- `set(key, value, ttl=3600)` → `set_transient()`
- `get(key)` → `get_transient()`
- `delete(key)` → `delete_transient()`
- `flush()` → `wp_cache_flush()`

Used by REST API page-data endpoint (1-hour transient).

---

## Data Flow

### Settings Lifecycle

```
define_entries() in Settings_Registry (555 settings)
        │
        ├──→ Customizer::register() → WP Customizer panels/sections/controls
        ├──→ Settings_Page::init() → Admin tabs/fields CRUD
        ├──→ Rest_Controller → REST API endpoints
        └──→ Shell → Frontend CSS injection

User changes setting (3 ways):
1. Admin page POST → Settings_Registry::set() → update_option('phantom_{key}')
2. Customizer save → WP save → options table
3. REST API PUT/POST → Settings_Registry::set() → update_option('phantom_{key}')

Frontend render:
get_option('phantom_{key}') → Shell::inject_customizer_css() → :root{--var:value}
JS live preview: PhantomCustomizer.cssVarMap → document.documentElement.style.setProperty()
```

### Request Lifecycle

```
Browser requests /shop
        │
        ▼
WordPress: template_redirect (priority 0)
        │
        ▼
Shell::handle_request()
  ├── Parse URL → slug = "shop"
  ├── Not wp-json/wp-admin/static → proceed
  ├── Map slug → "shop.html"
  ├── Read frontend/shop.html
  ├── Inject SEO: title, meta, OG, JSON-LD, base tag, WC nonce
  ├── Inject Customizer CSS: <style id="phantom-customizer-css">:root{...}</style>
  ├── Set security headers (CSP, XFO, etc.)
  └── Output HTML + exit
        │
        ▼
Browser renders shop.html
        │
        ▼
phantom-data.js: DOMContentLoaded
  ├── GET /page-data (REST API)
  ├── injectSettings() → [data-phantom] elements
  ├── injectMenus() → [data-phantom-menu]
  ├── injectProducts() → [data-phantom-products]
  ├── injectCategories() → #category1
  ├── injectCart() → .shopping-cart-info
  ├── initWooCommerce() → event delegation
  └── hidePreloader()
```

---

## Plugin Initialization Order

```
plugins_loaded, priority 1:  load_plugin_textdomain()
plugins_loaded, priority 5:  Plugin::init() → Settings_Registry::register()
plugins_loaded, priority 15: Customizer::init() → customize_register hook

Earlier (file_exists checks in phantom-core.php):
  Rest_Controller::init() → rest_api_init hook
  Settings_Page::init() → admin_menu hook
  Engine\Cache::init()
  Shell::init() → template_redirect hook (priority 0)
```

---

## Key Architectural Patterns

1. **Singleton pattern** — All major classes use `get_instance()` with private static `$instance`
2. **PSR-4 Autoloading** — `PhantomCore\` namespace → `includes/`
3. **Static HTML SPA** — Frontend is entirely static HTML. No PHP templates. Data injected client-side via REST API
4. **Three-way settings management** — Customizer (visual) + Admin (form) + REST API (programmatic)
5. **CSS Variable architecture** — Design tokens as CSS custom properties on `:root`. Managed by both PHP and JS
6. **WooCommerce Store API** — Quantity updates use Store API; add/remove use legacy `wc-ajax`
7. **Attribute-based data binding** — `[data-phantom]` attributes on HTML elements drive JS injection
8. **Security-first** — CSP headers, XSS sanitization, URL validation, capability checks, nonce verification
