# Phantom Core — Architecture

## Core Concept

Phantom Core is a **decoupled WordPress framework**. There is NO standard `wp-content/themes/` directory. The plugin IS the theme. WordPress is used only as a backend CMS — its template hierarchy is completely bypassed.

```
                    ┌──────────────────────────────────────────────────┐
                    │              WordPress Backend CMS               │
                    │  Users · Posts · Pages · Media · Comments · Roles│
                    │  Options API · Menus · Widgets · Permalinks      │
                    └──────────────────────┬───────────────────────────┘
                                           │
                    ┌──────────────────────▼───────────────────────────┐
                    │              Phantom Core Plugin                 │
                    │                                                  │
                    │  ┌────────────────┐ ┌────────────────────────┐  │
                    │  │ Settings_Reg   │ │ Rest_Controller        │  │
                    │  │ 612 settings   │ │ 49 routes phantom/v1   │  │
                    │  │ 46 sections    │ │ (42 unique paths)      │  │
                    │  │ Options API    │ │ Settings CRUD + Auth   │  │
                    │  └───────┬────────┘ │ Products/Cart/Orders   │  │
                    │          │          │ Posts/Pages/Menus      │  │
                    │          │          │ Post-Types/Menu-Locs   │  │
                    │          │          └───────────┬────────────┘  │
                    │          │                      │              │
                    │  ┌───────▼──────────────────────▼──────────┐  │
                    │  │           Shell SPA Router              │  │
                    │  │  template_redirect (priority 1)         │  │
                    │  │  URL → slug → HTML file                 │  │
                    │  │  43 routes · SEO meta · 136 CSS vars    │  │
                    │  │  Security headers · phantomData JS      │  │
                    │  └───────────────────┬──────────────────────┘  │
                    │                      │                         │
                    │  ┌───────────────────▼──────────────────────┐  │
                    │  │    Customizer (16 panels, 46 sections)   │  │
                    │  │    + Custom CSS Engine (9 modules)       │  │
                    │  │    + 11 Custom Controls (3 used)        │  │
                    │  │    + Global Color Palette (4 presets)    │  │
                    │  │    + Font System (Google + system + local)│  │
                    │  └──────────────────────────────────────────┘  │
                    │                                                  │
                    │  ┌──────────────────────────────────────────┐  │
                    │  │  Architecture Subsystems (Phase B-E)     │  │
                    │  │  ┌──────┐ ┌──────┐ ┌─────┐ ┌─────────┐ │  │
                    │  │  │Data  │ │Layout│ │Hook │ │Bridges   │ │  │
                    │  │  │Layer │ │Reg   │ │Reg  │ │(WooComm) │ │  │
                    │  │  └──────┘ └──────┘ └─────┘ └─────────┘ │  │
                    │  │  ┌──────────┐ ┌──────┐ ┌────────────┐ │  │
                    │  │  │Capability│ │Asset │ │Public API  │ │  │
                    │  │  │Manager   │ │Reg   │ │(6 facades) │ │  │
                    │  │  └──────────┘ └──────┘ └────────────┘ │  │
                    │  │  ┌──────────────────┐ ┌──────────────┐ │  │
                    │  │  │Template_Manifest │ │Splitting_Br │ │  │
                    │  │  │Component_Metadata│ │(CDN+CSS)    │ │  │
                    │  │  └──────────────────┘ └──────────────┘ │  │
                    │  │  ┌──────────────────────────────────┐  │  │
                    │  │  │  Container_Config: 38 services   │  │  │
                    │  │  └──────────────────────────────────┘  │  │
                    │  └──────────────────────────────────────────┘  │
                    └──────────────────────┬──────────────────────────┘
                                           │
                    ┌──────────────────────▼──────────────────────────┐
                    │               Frontend SPA                      │
                    │                                                  │
                    │  22 Static HTML Templates (frontend/html/)       │
                    │  ┌──────────────────────────────────────────┐   │
                    │  │ index · shop · product-detail · cart     │   │
                    │  │ checkout · blog · single-blog · about    │   │
                    │  │ contact · faq · team · testimonials      │   │
                    │  │ login · join-now · coming-soon · 404     │   │
                    │  │ privacy · terms · cookie · thank-you     │   │
                    │  │ account · wishlist                         │   │
                    │  └──────────────────────────────────────────┘   │
                    │                                                  │
                    │  phantom-data.js (REST API bridge, 38+ fns)      │
                    │  phantom-bridge.js (helper utilities)           │
                    │  Swup.js (SPA page transitions)                 │
                    │  jQuery (AJAX, DOM manipulation)                │
                    │  10 CSS files (Bootstrap + theme + a11y)        │
                    └──────────────────────────────────────────────────┘
```

---

## How the Backend Connects to the Frontend

There are exactly **3 data channels** between the PHP backend and the HTML/JS frontend:

### Channel 1: Server-Side Injection (Shell.php)

On every page load, `Shell::handle_request()` injects data into the HTML **before** serving it:

```
Shell reads WordPress database
    │
    ├──→ wp_options (all phantom_{key} settings)
    │       │
    │       ▼
    │   <style id="phantom-customizer-css">
    │   :root {
    │     --primary--color: #ff0000;
    │     --button-bg: #333;
    │     ... 136 CSS custom properties
    │   }
    │   </style>
    │
    ├──→ site info (name, description, URL)
    │       │
    │       ▼
    │   <script>window.phantomData = {
    │     rest_url: "https://.../wp-json/",
    │     plugin_url: "https://.../phantom-core/",
    │     nonce: "abc123...",
    │     site_name: "My Store",
    │     is_logged_in: true/false,
    │     user_name: "John",
    │     user_email: "..." // only for edit_theme_options users
    │   };</script>
    │
    ├──→ SEO metadata
    │       │
    │       ▼
    │   <title>Page Title</title>
    │   <meta name="description">...
    │   <meta property="og:...">...
    │   <script type="application/ld+json">...</script>
    │
    └──→ Security headers
            │
            ▼
        Content-Security-Policy
        X-Frame-Options: SAMEORIGIN
        X-Content-Type-Options: nosniff
        Referrer-Policy: strict-origin-when-cross-origin
        Permissions-Policy: ...
```

**What the frontend MUST have for this to work:**
- `<head>` with `<meta charset>` — Shell injects CSS vars and SEO **before** `</head>`
- `<body>` with JS script tags — Shell appends scripts before `</body>`
- `window.phantomData` — Read by phantom-data.js for REST URL, nonce, site identity

### Channel 2: Client-Side REST API (phantom-data.js)

After the page loads, `phantom-data.js` fetches all dynamic data from the REST API:

```
Browser renders HTML with [data-phantom] attributes
    │
    ▼
DOMContentLoaded → phantom-data.js runs
    │
    ▼
fetch(GET /wp-json/phantom/v1/page-data)  ← single mega-endpoint, cached 1hr
    │
    ▼
JSON response contains:
{
  "settings": { all 612 setting values },
  "menus": { primary, secondary, footer, mobile, categories },
  "products": { featured, recent, ... },
  "posts": { ... },
  "categories": [ ... ],
  "cart": { items, total, count }
}
    │
    ▼
injectSettings()  → [data-phantom="key"] → textContent/src/href
injectBanner()    → hero sections
injectFooter()    → footer content
injectSEO()       → <title>, <meta>
injectMenus()     → [data-phantom-menu="location"] → builds <nav>
injectProducts()  → [data-phantom-products="type"] → product cards
injectPosts()     → [data-phantom-posts="type"] → blog cards
injectCart()      → .shopping-cart-info → cart dropdown
initWooCommerce() → bind add-to-cart, quantity, remove events
initCheckout()    → bind checkout form submission
initAuthForms()   → bind login/register/reset forms
initMyAccount()   → fetch /user/orders, render order history
initSearch()      → bind live search with suggestions
hidePreloader()   → remove .loader-mask
```

**What the frontend MUST have for this to work:**
- `data-phantom="setting_key"` on HTML elements that need dynamic content
- `data-phantom-menu="location"` on navigation containers
- `data-phantom-products="type"` on product grid containers
- `data-phantom-posts="type"` on blog post containers
- `<script src="phantom-data.js">` loaded on every page
- jQuery loaded before phantom-data.js

### Channel 3: CSS Custom Properties (Design Tokens)

136 CSS custom properties bridge backend settings to frontend styling:

```
Settings_Registry → CSS Var Map → Shell injects as <style> → Frontend uses var()
```

**Naming convention:** `_` in setting keys becomes `--` in CSS vars:
```
primary_color          → --primary--color
header_bg              → --header-bg
typography_body_font   → --font-body
button_font_size       → --button-font-size
```

**How frontend CSS uses them:**
```css
.button {
    background: var(--button-bg, #0066cc);
    color: var(--button-text, #ffffff);
    border-radius: var(--button-radius, 4px);
    font-size: var(--button-font-size, 16px);
}
```

**What the frontend MUST have for this to work:**
- CSS files must reference `var(--name, fallback)` for customizable properties
- `<style id="phantom-customizer-css">` must be present in `<head>` (Shell injects it)
- Customizer changes take effect immediately via `postMessage` in preview

---

## The 6 Critical Connection Points (Must Not Break)

| # | Connection | Mechanism | Location | If Broken |
|---|-----------|-----------|----------|-----------|
| 1 | **Route → HTML file** | Shell::$routes maps URL slug to template | `templates/shell.php` | 404 on page |
| 2 | **Settings → CSS vars** | `get_css_var_map()` → `<style>` injection | `shell.php:inject_css_variables()` | Customizer theming breaks |
| 3 | **Settings → `[data-phantom]`** | `phantom-data.js` reads `page-data` endpoint | `phantom-data.js:injectSettings()` | Static content stays |
| 4 | **`phantomData` JS config** | Shell injects `window.phantomData` | `shell.php:inject_bridge()` | Everything breaks |
| 5 | **REST API endpoints** | `Rest_Controller::register_routes()` | `class-rest-controller.php:register_routes()` | No dynamic data |
| 6 | **CSS class selectors** | JS hardcodes class names for DOM queries | `phantom-data.js` (multiple functions) | Features silently break |

---

## Complete Request Lifecycle

```
1. Browser requests /shop
2. WordPress: template_redirect fires (priority 1)
3. Shell::handle_request():
   a. Parse URL → slug = "shop"
   b. Bypass check: wp-json? wp-admin? wp-login? → let WP handle
   c. Route table lookup: '/shop' → 'frontend/shop.html'
   d. Read template file from disk
   e. Inject SEO: <title>, <meta>, OG tags, Twitter Card, JSON-LD
   f. Inject phantomData: <script>window.phantomData = {...}</script>
   g. Inject CSS vars: <style>:root { --primary--color: #... }</style>
   h. Set security headers: CSP, X-Frame-Options, etc.
   i. Append scripts: jQuery, Swup, Bootstrap, phantom-data.js
   j. Output HTML + exit (WordPress never renders a theme)
4. Browser renders shop.html
5. phantom-data.js: DOMContentLoaded → fetches /page-data → injects content
6. Swup.js handles subsequent navigation via AJAX (no full page reload)
```

Note: The Shell handles all routes and injects CSS vars/phantomData/SEO. The phantom-theme can also render pages via its own templates (functions.php handles sidebars, widgets, menus, custom logo) when served through the traditional WordPress loop — this provides fallback compatibility.

---

## Core Components

### 1. Settings Registry (`Settings_Registry`)
**File:** `includes/class-settings-registry.php` — 5,555+ lines

The master settings repository. **612 settings across 46 sections.** Each entry has:

| Field | Type | Purpose |
|-------|------|---------|
| `key` | `string` | Unique ID (e.g., `primary_color`) |
| `type` | `string` | `string|bool|int|float|color|select|image|text|code|repeater|array|number|multiselect|json|multi_select` |
| `default` | mixed | Default value |
| `sanitize` | callback | Sanitization function |
| `label` | `string` | Human-readable name |
| `section` | `string` | Group slug (e.g., `colors`, `header`, `typography`) |
| `transport` | `string` | `postMessage` (live preview) or `refresh` |
| `css_property` | `string` | CSS custom property name (e.g., `--primary--color`) |
| `css_selector` | `string` | CSS selector (default `:root`) |
| `dependencies` | `array` | Conditional visibility rules |
| `responsive` | `bool` | Supports desktop/tablet/mobile values |

**Key insight:** Adding a setting entry here = automatically available in Customizer + Admin Page + REST API. This is the single source of truth.

### 2. REST API (`Rest_Controller`)
**File:** `includes/class-rest-controller.php` — ~2,300 lines (post-refactor)

Namespace `phantom/v1`. **49 registered routes (42 unique paths)** — all use `register_rest_route`:

| Endpoint | Method | Auth | Purpose |
|----------|--------|------|---------|
| `/settings` | GET/POST | admin | List/update all settings |
| `/settings/{key}` | GET/PUT/DELETE | admin | Single setting CRUD |
| `/settings/batch` | POST | admin | Batch update settings |
| `/schema` | GET | admin | Setting schemas with defaults |
| `/options` | GET | admin | Filtered design options |
| `/export` | POST | admin | Export all settings as JSON |
| `/import` | POST | admin | Import settings from JSON |
| `/cache/flush` | POST | admin | Flush transients |
| `/partial` | GET | public | Selective refresh partial |
| `/posts` | GET | public | Blog posts (paginated, filterable) |
| `/posts/{slug}` | GET | public | Single post by slug |
| `/pages` | GET | public | Paginated list of published pages |
| `/pages/{slug}` | GET | public | Single page by slug |
| `/post-types` | GET | public | List all public post types |
| `/categories` | GET | public | Product + post categories |
| `/menus/{location}` | GET | public | Menu tree by location |
| `/menu-locations` | GET | public | List registered nav menu locations |
| `/products` | GET/POST | public/admin | Products list/create |
| `/products/featured` | GET | public | Featured products |
| `/products/{id}` | GET/PUT/DELETE | public/admin | Single product CRUD |
| `/cart` | GET | public | Cart contents (with `wc_load_cart` fix) |
| `/cart/coupons` | GET | public | Cart coupons |
| `/cart/shipping-methods` | GET | public | Calculate shipping |
| `/cart/{item_key}` | DELETE | public | Remove cart item |
| `/contact` | POST | public | Contact form handler (wp_mail) |
| `/user/orders` | GET | logged-in | Current user's orders |
| `/user/profile` | GET | logged-in | Current user's profile |
| `/auth/login` | POST | public | User login |
| `/auth/register` | POST | public | User registration |
| `/auth/password-reset` | POST | public | Password reset |
| `/auth/logout` | POST | logged-in | User logout |
| `/page-data` | GET | public | **Mega-endpoint** — all data in one call |
| `/woo/attributes` | GET | public | Product attributes |
| `/woo/variations` | GET | public | Product variations |
| `/woo/reviews` | GET/POST | public/logged-in | Product reviews |

**Return types:** All endpoint methods return `WP_REST_Response|WP_Error` union types. Permission callbacks properly verify nonce + capability.

### 3. Shell (SPA Router)
**File:** `templates/shell.php` — ~700 lines

The frontend rendering engine. Hooks `template_redirect` at priority 1 to intercept ALL frontend requests.

**What Shell injects into every page:**
1. **SEO metadata** — `<title>`, meta description, OG tags, Twitter Card, JSON-LD schema
2. **CSS variables** — `<style id="phantom-customizer-css">` with 136 design tokens
3. **phantomData** — `window.phantomData` JS config object with REST URL, nonce, settings
4. **Security headers** — CSP, X-Frame-Options, X-Content-Type-Options, Referrer-Policy, Permissions-Policy
5. **Scripts** — jQuery, Swup, Bootstrap, vendor JS, phantom-data.js (auto minified)
6. **Copyright year** — Dynamic via `preg_replace('/\b2025\b/', date('Y'), $html)`
7. **Skip-to-content link** — Accessibility link before all page content
8. **Minified JS** — Auto-serves `.min.js` when present via `inject_minified_js()`

**Important:** Shell uses `remove_action('wp_head', 'wp_enqueue_scripts', 1)` to block WordPress from enqueuing default scripts. The SPA architecture means no WordPress theme functions run.

### 4. Customizer
**File:** `includes/class-customizer.php` — 540 lines

Bridges Settings Registry → WordPress Customizer. **16 panels, 46 sections.**

**16 Panels:**
1. Branding — Logo, favicon, site identity
2. Header — Layout, topbar, navigation, announcement bar
3. Hero — Banner, home sections, collections
4. Products — Cards, shop page, product page
5. WooCommerce — Cart, checkout, my account
6. Blog — Archive, single post
7. Footer — Layout, widgets, copyright
8. Typography — Fonts, sizes, weights
9. Colors — Scheme, buttons, forms, spacing
10. Layout — Container, responsive, animations, 3D
11. Search — AJAX, suggestions
12. Performance & SEO — Cache, preload, meta
13. Accessibility — Contrast, focus, font size
14. Advanced — Integrations, custom code, import/export
15. Announcement Bar — Enable, text, colors
16. **Responsive Hero Media** — Desktop/tablet/mobile images, breakpoints, loading, fit, overlay

**11 Custom Control Types Available:**
- `ast-toggle` (103 instances) — Custom toggle switch
- `ast-color` (56 instances) — Color picker
- `ast-select` (37 instances) — Custom dropdown
- 8 additional types available but unused in current section configs:
  `ast-color-group`, `ast-gradient`, `ast-border`, `ast-background`, `ast-typography`, `ast-radio-image`, `ast-responsive-slider`, `ast-responsive-spacing`
- Plus base `Control_Base` and static `Font_Families` helper

### 5. Admin Settings Page (`Settings_Page`)
**File:** `admin/class-settings-page.php` — 753 lines

Full CRUD UI at `/wp-admin/themes.php?page=phantom-core-settings`. 15 tabs. All field types: text, textarea, number, checkbox, select, multiselect, color picker, image upload, code editor, repeater fields with sub-fields. Supports dependency (conditional) logic. Import/Export buttons.

### 6. Frontend JavaScript (`phantom-data.js`)
**File:** `frontend/assets/js/phantom-data.js` — 2,364 lines, 38+ functions

The bridge between REST API and HTML templates. Runs on every page.

**Data Adapter + Component Renderer Pattern (v2.0):**
Instead of building DOM nodes with `document.createElement`, phantom-data.js now uses:
- **Template strings** — `PRODUCT_CARD_TPL`, `CATEGORY_CARD_TPL` are exact copies of the frontend HTML card structure. If the design changes, update the template string, not JS DOM logic.
- **`renderTemplate(tpl, data)`** — A 3-line mustache-style `replace()` that fills `{{PLACEHOLDER}}` tokens → zero dependencies, zero DOM
- **`adaptProductCard(raw, settings, ...)`** — Normalizes WooCommerce product data (badges, actions, categories, rating, price, ATC button) into a flat `{NAME, URL, IMAGE, PRICE, ...}` object
- **`adaptCategoryCard(raw, idx, total)`** — Normalizes category data into `{NAME, URL, IMAGE, COUNT, CTA, ...}`

This guarantees product/category cards always match the frontend HTML — the template string IS the source of truth.

**Injection order:**
```
DOMContentLoaded
  ├── fetch(/cart) → updateCartCount()
  ├── fetch(/page-data) → returns {settings, menus, products, posts, categories}
  │   ├── injectSettings()    → [data-phantom="key"] elements
  │   ├── injectBanner()      → hero/banner sections
  │   ├── injectFooter()      → footer sections
  │   ├── injectSEO()         → meta tags
  │   ├── injectMenus()       → [data-phantom-menu="location"]
  │   ├── injectProducts()    → [data-phantom-products="type"]
  │   ├── injectPosts()       → [data-phantom-posts="type"]
  │   ├── injectCategories()  → #category1 (uses adaptCategoryCard + renderTemplate + CATEGORY_CARD_TPL)
  │   ├── injectSinglePost()  → [data-phantom-post]
  │   ├── injectSingleProduct()→ [data-phantom-product]
  │   ├── injectCart()        → .shopping-cart-info
  │   ├── initWooCommerce()   → add-to-cart, quantity, remove
  │   ├── initCheckout()      → checkout form
  │   ├── initShipping()      → shipping methods
  │   ├── initShopControls()  → pagination, sorting
  │   ├── initBlogPagination()-> blog page nav
  │   ├── initAuthForms()     → login/register/reset
  │   ├── initLogout()        → logout handler
  │   ├── initMyAccount()     → /my-account page
  │   ├── initQuickViewEvents()→ product quick view
  │   ├── initImageZoom()     → product image zoom
  │   ├── initWishlistEvents()-> wishlist toggle
  │   └── hidePreloader()     → remove loading screen
  └── Swup.js handles navigation → no full page reloads
```

### 7. CSS Variable Architecture
**File:** `includes/class-settings-registry.php:get_css_var_map()` — **136 entries**

136 CSS custom properties as design tokens. Injected as `<style id="phantom-customizer-css">` on every page.

**Categories (136 vars total):**
- Colors (18): primary, secondary, accent, bg, text, heading, link, border, sale, rating, header/footer bg, gradient, badge, semantic colors
- Typography (24): h1-h6 sizes/heights, body/heading fonts, weights, spacing, case
- Header (10): bg, text, padding, border, height, mobile height, banner height
- Navigation (3): menu height, submenu width, font size
- Footer (5): bg, text, heading, link, border
- Buttons (7): bg, text, hover states, radius, padding, font size
- Forms (2): input radius, height
- Layout (5): container, content, sidebar, boxed, columns
- Spacing (6): section padding, gutter, gap, margin, widget spacing
- Responsive (4): breakpoint xl/lg/md/sm
- Topbar (2): bg, text
- Announcement (2): bg, text color
- Hero (11): image, object-fit, object-position, bg-position, overlay-opacity, image-url, tablet-image, mobile-image, tablet-breakpoint, mobile-breakpoint, loading
- Products (5): card bg, sale badge bg, featured badge bg, image radius, card gap
- Preloader (3): bg, color, enable
- General/Container (4): container width, content width, sidebar width, boxed width
- Gradients (2): start color, end color
- Misc (24+): custom-css, woo button bg/text, gap/column-gap/row-gap, section pad x/y, field outline, letter spacing, text case, body line height, etc.

### 8. Custom CSS Engine (9 modules)
**Files:** `includes/custom-css/`

Each module hooks `phantom_dynamic_css` filter:

| File | Priority | Description |
|------|----------|-------------|
| `colors.php` | 10 | Color scheme CSS vars |
| `typography.php` | 20 | Typography CSS vars |
| `header.php` | 30 | Header CSS vars |
| `footer.php` | 40 | Footer CSS vars |
| `layout.php` | 50 | Layout CSS vars |
| `buttons.php` | 60 | Button CSS vars |
| `product.php` | 80 | Product card CSS vars |
| `hero.php` | 90 | Responsive hero media CSS vars + `@media` queries |
| `responsive.php` | 100 | Responsive breakpoint vars |

### 9. Custom Customizer Controls (11 types available)
**Files:** `includes/custom-controls/`

All controls registered via `$type_class_map` in `Control_Base`. Types registered with `$wp_customize->register_control_type()`.

| Control | Type String | Instances | Purpose |
|---------|-------------|-----------|---------|
| `Control_Base` | — | — | Abstract base class |
| `Color_Control` | `ast-color` | 56 | Color picker |
| `Color_Group_Control` | `ast-color-group` | 0 | Grouped color pickers |
| `Gradient_Control` | `ast-gradient` | 0 | Gradient picker |
| `Border_Control` | `ast-border` | 0 | Border width/style/color |
| `Background_Control` | `ast-background` | 0 | Background color/image/repeat |
| `Typography_Control` | `ast-typography` | 0 | Font family/size/weight |
| `Select_Control` | `ast-select` | 37 | Custom dropdown |
| `Toggle_Control` | `ast-toggle` | 103 | Custom toggle switch |
| `Radio_Image_Control` | `ast-radio-image` | 0 | Image-based radio buttons |
| `Responsive_Slider_Control` | `ast-responsive-slider` | 0 | Device-responsive slider |
| `Responsive_Spacing_Control` | `ast-responsive-spacing` | 0 | Device-responsive spacing |
| `Font_Families` | — | — | Static font helper |

---

## Architecture Subsystems (Phase B–E, 100/100 alignment)

### Data Layer
**Files:** `includes/Data/`, `includes/ViewModels/`

9 Adapters + 6 ViewModels implementing `AdapterInterface`:

| Adapter | ViewModel | Purpose |
|---------|-----------|---------|
| `Post_Adapter` | `Post_ViewModel` | Normalize WP_Post → structured data |
| `Page_Adapter` | `Page_ViewModel` | Normalize WP page → structured data |
| `User_Adapter` | `User_ViewModel` | Normalize WP_User → structured data |
| `Footer_Adapter` | — | Footer widget settings normalization |
| `Settings_Adapter` | `Settings_ViewModel` | Settings normalization for REST + frontend |
| `Product_Adapter` | — | WooCommerce product normalization (used by REST controller) |
| `Menu_Adapter` | — | Menu tree normalization |
| `Category_Adapter` | — | Category data normalization |
| `Cart_Adapter` | — | Cart data normalization |

**Data Infrastructure:**
- `Data_Normalizer` — Utility for recursive array normalization, type coercion
- `Data_Provider` — Abstract base class with built-in caching (transient-based)

### Layout Registry
**Files:** `includes/Layout/`

| File | Purpose |
|------|---------|
| `class-layout.php` | Layout value object (slug, name, cols, regions, default, preview) |
| `class-layout-registry.php` | Singleton registry of available layouts |
| `class-layout-manager.php` | Layout resolution, application, and rendering |

**7 Default Layouts:** Default, Full Width, Left Sidebar, Right Sidebar, Narrow, Wide, Boxed

### Design API (Facade)
**File:** `includes/Public/class-design-api.php`

Filterable facade over `DesignSystemManager` with 10+ methods:
- `get_color($key, $variant)` — Color resolution with fallback chain
- `get_font($type)` — Font family resolution
- `get_css_var($key)` — CSS variable lookup
- `get_typography_scale()` — Typography scale from settings
- `get_spacing($level)` — Spacing scale from settings

### Hook Registry
**Files:** `includes/Hook/`

| File | Purpose |
|------|---------|
| `class-hook-registry.php` | Track, register, and dispatch hooks with introspection |
| Supports filtering by tag, priority, callback inspection |

### Plugin Bridges
**Files:** `includes/Bridges/`

| File | Purpose |
|------|---------|
| `interface-bridge.php` | `BridgeInterface` contract (init, is_active, get_info) |
| `class-plugin-bridge.php` | Abstract base with capability checks + dependency guards |
| `class-woocommerce-bridge.php` | WooCommerce integration: cart hooks, product overrides, checkout styling |
| `class-bridge-manager.php` | Singleton manager — registers bridges, calls `init_all()` on bootstrap |

### Asset Registry
**File:** `includes/Registry/class-asset-registry.php`

Pre-registers 25+ assets (CSS/JS) with handle, src, deps, version, context (frontend/admin/customizer). Used by `Container_Config` for enqueue wiring.

### Capability Manager
**File:** `includes/class-capability-manager.php`

Manages 8 `phantom_` custom capabilities:
`phantom_manage_settings`, `phantom_edit_design`, `phantom_export_data`, `phantom_import_data`, `phantom_manage_fonts`, `phantom_view_reports`, `phantom_manage_layouts`, `phantom_manage_bridges`

### Component Metadata
**File:** `includes/Components/class-component-metadata.php`

Template/asset compatibility metadata. Provides `get_compatible_templates($component)` and `get_required_assets($template)` for dynamic dependency resolution.

### Template Manifest
**File:** `includes/Manifest/class-template-manifest.php`

JSON-driven template metadata. Reads `template-manifest.json` for template groups, labels, preview images, required data-attributes, and asset dependencies.

### Splitting Bridge
**File:** `includes/Animation/class-splitting-bridge.php`

CDN + CSS enqueue for Splitting.js (text/character animation library). Manages CDN URL configuration, local fallback, and CSS enqueue for text splitting effects.

### Public API Facades (6)
**Files:** `includes/Public/`

| Facade | Purpose |
|--------|---------|
| `Render` | HTML rendering helpers (menus, breadcrumbs, pagination) |
| `Component` | UI component rendering (cards, buttons, badges) |
| `Animation` | Animation initialization data + scroll-trigger config |
| `Settings` | Public settings read access (filtered by `manage_options` guard) |
| `Template` | Template resolution (slug → path, checksum, modified time) |
| `Developer` | Debug helpers, hook introspection, var dump utilities |

### Container Config
**File:** `includes/Engine/Container_Config.php`

38 service definitions registered in the DI container:

```
Data_Normalizer (singleton)     Layout_Manager (singleton)
Settings_Registry (singleton)   Hook_Registry (singleton)
Rest_Controller                 Bridge_Manager (singleton)
Shell                           Asset_Registry (singleton)
Customizer                      Capability_Manager (singleton)
Custom_CSS (singleton)          Component_Metadata (singleton)
Global_Palette (singleton)      Template_Manifest (singleton)
Font_Families (singleton)       Splitting_Bridge (singleton)
Font_Loader (singleton)         6 Public API facades
Settings_Page                   Post_Adapter, Page_Adapter, ...
Core_Plugin                     5+ other services
```

### Helpers
**File:** `includes/class-helpers.php`

Static utility class with methods for:
- `sanitize_css_var($key)` — Convert setting key to CSS var name
- `get_image_url($id, $size)` — Safe image URL resolution
- `format_date($date, $format)` — Localized date formatting
- `truncate_text($text, $length)` — Smart text truncation
- `array_to_css_vars($array, $prefix)` — Recursive array → CSS vars

---

## Data Flow

### Settings Lifecycle
```
define_entries() in Settings_Registry (612 settings)
        │
        ├──→ Customizer::register() → WP Customizer panels/sections/controls
        ├──→ Settings_Page::init() → Admin tabs/fields CRUD
        ├──→ Rest_Controller → REST API endpoints (49 routes)
        └──→ Shell → Frontend CSS injection (136 CSS vars)

User changes setting (3 ways):
1. Admin page POST → update_option('phantom_{key}')
2. Customizer save → WP save → options table
3. REST API PUT/POST → update_option('phantom_{key}')

Frontend render (server):
get_option('phantom_{key}') → Shell::inject_customizer_css() → :root{--var:value}

Frontend render (client):
GET /phantom/v1/page-data → phantom-data.js → [data-phantom] elements
```

### Plugin Initialization Order
```
File scope (phantom-core.php):
  load_plugin_textdomain() — immediate, not hooked
  Rest_Controller::init() → rest_api_init hook
  Settings_Page::init() → admin_menu hook
  Engine\Cache::init() → registers actions
  Shell::init() → template_redirect hook (priority 1)
  Phantom_Webfont_Loader::init() → wp_enqueue_scripts hook

plugins_loaded, priority 5:  Plugin::init() → Settings_Registry::register()
plugins_loaded, priority 10: Version_Compatibility::init()
plugins_loaded, priority 15: Customizer::init() → customize_register hook
plugins_loaded, priority 20: Container_Config::init() → register DI services

wp_enqueue_scripts, priority 9:  phantom_enqueue_google_fonts()
wp_enqueue_scripts, priority 11: phantom_enqueue_dark_mode()

init, priority 1:   Bridge_Manager::init_all() → WooCommerce_Bridge::init()
init, priority 5:   Capability_Manager::register_caps()
init, priority 10:  Asset_Registry::register_assets()
init, priority 15:  Helpers::load() (autoloaded)
init, priority 20:  Layout_Registry::register_defaults()
init, priority 25:  Hook_Registry::register_default_hooks()
init, priority 30:  Component_Metadata::init()
init, priority 35:  Template_Manifest::init()
init, priority 40:  Splitting_Bridge::init()
init, priority 45:  Data_Normalizer::init()
init, priority 50:  Register Public API facades (Render, Component, Animation, Settings, Template, Developer)
```

---

## Key Principles

1. **Plugin IS the theme** — No `wp-content/themes/`. Plugin handles everything.
2. **Static HTML SPA** — 22 static HTML files. No PHP templates. Data via REST API.
3. **Three-way settings** — Customizer (visual) + Admin (form) + REST API (programmatic).
4. **CSS Variable architecture** — 136 design tokens. Change one setting → updates everywhere.
5. **Attribute-based data binding** — `[data-phantom="key"]` on HTML drives JS injection.
6. **Decoupled frontend** — 100% replaceable without touching PHP.
7. **WooCommerce via Store API** — Modern cart/checkout via wc-ajax + Store API.
8. **Security-first** — CSP headers, sanitization, URL validation, capability checks, nonces.
9. **Settings-first design** — Every visual element starts as a setting.
10. **Minified JS** — Auto-serves terser-minified JS when available.

## Performance Notes

- **Server:** ~50ms response for Shell (no DB query on cache hit).
- **Client:** Single `/page-data` call (cached 1hr transient). All data in one request.
- **CSS:** 136 vars injected inline (~4KB). 9 CSS module files combine dynamically.
- **JS:** phantom-data.js is 2,364 lines. Minified via terser ~35KB.
- **Minification:** Auto-serves `.min.js` via `inject_minified_js()` when present.
- **Architecture health:** 100/100 forensic code health + 100/100 architecture alignment.
