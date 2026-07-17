# Phantom Core — Frontend Guide

## Architecture

The frontend is **completely decoupled** from the WordPress backend. It consists of:

```
frontend/
├── *.html              # Static HTML templates (27 pages)
├── assets/
│   ├── css/            # Stylesheets (Bootstrap, theme CSS, vendor)
│   ├── js/             # JavaScript (phantom-data.js + vendor)
│   └── images/         # Images (logos, products, icons, backgrounds)
└── reference/          # Deployment reference docs
```

**No PHP templates. No server-side rendering.** All dynamic data is injected client-side via REST API.

---

## How Data Binding Works

### 1. Attribute-Based Binding (`[data-phantom]`)

HTML elements use `data-phantom` attributes to declare what data they need:

```html
<!-- Text content -->
<span data-phantom="site_title">Loading...</span>

<!-- Image src -->
<img data-phantom="general_site_logo" src="placeholder.png">

<!-- Link href -->
<a data-phantom="hero_button_url" href="#">Shop Now</a>

<!-- Background image -->
<div data-phantom-bg="hero_bg_image"></div>
```

`phantom-data.js` reads these attributes and injects values from the REST API.

### 2. Menu Binding (`[data-phantom-menu]`)

```html
<nav data-phantom-menu="primary"></nav>
<nav data-phantom-menu="footer"></nav>
<nav data-phantom-menu="mobile"></nav>
```

### 3. Product Binding (`[data-phantom-products]`)

```html
<div data-phantom-products="featured" data-count="4"></div>
<div data-phantom-products="all" data-count="8"></div>
<div data-phantom-products="related" data-id="PRODUCT_ID"></div>
```

### 4. Post Binding (`[data-phantom-posts]`)

```html
<div data-phantom-posts="recent" data-count="3"></div>
<div data-phantom-posts="related" data-id="POST_ID"></div>
```

### 5. Category Binding (`#category1`)

```html
<ul id="category1"></ul>
```

### 6. Cart Binding (`.shopping-cart-info`)

```html
<div class="shopping-cart-info"></div>
<span class="cart-count">0</span>
```

### 7. Single Product Binding (`[data-phantom-product]`)

Detected via URL parameter `?product_id=X` or `/product/{slug}` path.

### 8. Single Post Binding (`[data-phantom-post]`)

Detected via URL parameter `?post_id=X`.

---

## The Complete Data Flow

```
1. Browser requests /shop
2. Shell (PHP) serves frontend/shop.html with SEO + CSS vars injected
3. phantom-data.js loads on DOMContentLoaded
4. Fetches /page-data (mega-endpoint with all data)
5. Injects each section in order:
   ┌─────────────────────────────────────────────┐
   │ injectSettings() → [data-phantom] elements  │
   │ injectBanner()   → hero sections            │
   │ injectFooter()   → footer sections          │
   │ injectSEO()      → meta tags                │
   │ injectMenus()    → [data-phantom-menu]      │
   │ injectProducts() → [data-phantom-products]  │
   │ injectPosts()    → [data-phantom-posts]     │
   │ injectCart()     → .shopping-cart-info      │
   │ initWooCommerce()→ add-to-cart/quantity/remove │
   │ initCheckout()   → checkout form             │
   │ injectCategories() → #category1              │
   │ hidePreloader()  → remove loading screen     │
   └─────────────────────────────────────────────┘
```

---

## How to Edit the Frontend Without Breaking Anything

### Safe Edits (visual only)

These changes affect ONLY the visual presentation. All backend functionality remains intact:

| What to Edit | How | Backend Impact |
|-------------|-----|---------------|
| CSS styles | Edit `frontend/assets/css/style.css` | None |
| HTML layout | Edit HTML files — **keep data attributes** | None |
| Images | Replace files in `frontend/assets/images/` | None |
| Colors | Use Customizer (no file editing) | None |
| Typography | Use Customizer (no file editing) | None |
| Spacing/layout | Use Customizer CSS vars | None |

### Critical: NEVER Remove These

These attributes/signatures are required for JS data binding:

```
data-phantom="key"          ── DO NOT REMOVE
data-phantom-menu="name"    ── DO NOT REMOVE
data-phantom-products="type"── DO NOT REMOVE
data-phantom-posts="type"   ── DO NOT REMOVE
data-phantom-product        ── DO NOT REMOVE
data-phantom-post           ── DO NOT REMOVE
data-phantom-bg="key"       ── DO NOT REMOVE
.shopping-cart-info         ── DO NOT REMOVE/rename class
#category1                  ── DO NOT REMOVE/rename id
.loader-mask                ── DO NOT REMOVE (preloader)
```

---

## How to Replace the Entire Frontend

### Step 1: Create New HTML Templates

Replace files in `frontend/*.html`. You can:
- Use any HTML framework (Bootstrap, Tailwind, custom)
- Use any design
- Add any CSS/JS libraries

### Step 2: Add Data-Binding Attributes

For each dynamic element, add `data-phantom` attributes:

```html
<!-- Before (static) -->
<h1>Welcome to Our Store</h1>

<!-- After (dynamic) -->
<h1 data-phantom="hero_title">Welcome to Our Store</h1>
```

### Step 3: Add Menu/Product/Post Hooks

```html
<nav data-phantom-menu="primary">
  <ul><!-- fallback menu --></ul>
</nav>

<div data-phantom-products="featured" data-count="4">
  <!-- fallback products -->
</div>

<ul id="category1">
  <li><!-- fallback categories --></li>
</ul>
```

### Step 4: Include phantom-data.js

```html
<script src="/wp-content/plugins/phantom-core/frontend/assets/js/phantom-data.js"></script>
```

### Step 5: Add WooCommerce Handlers (if needed)

- `.add-to-cart-trigger` class for add-to-cart buttons
- `.decrease-button` / `.increase-button` for quantity
- `.remove-product` for remove buttons
- `#contactpage` form for checkout

### Step 6: Test

1. Check all `[data-phantom]` elements render correct data
2. Verify menus load
3. Test WooCommerce add-to-cart/quantity/remove
4. Check Customizer live preview still works
5. Verify SEO meta tags are injected

---

## Complete Data Attribute Reference

### Settings Injection (`injectSettings`)

| Attribute | Target Elements | Data Source |
|-----------|---------------|-------------|
| `data-phantom="key"` | Any | `settings.key` from API |
| `data-phantom-bg="key"` | Block elements | `settings.key` (as background-image) |

**Special handling:**
- `<img>` and `<source>` — sets `src` attribute
- `<a>` with `href` — sets `href` attribute
- Everything else — sets `innerHTML`

### Menu Injection (`injectMenus`)

| Attribute | Menu Location |
|-----------|--------------|
| `data-phantom-menu="primary"` | Primary navigation |
| `data-phantom-menu="secondary"` | Secondary nav |
| `data-phantom-menu="footer"` | Footer menu |
| `data-phantom-menu="mobile"` | Mobile menu |
| `data-phantom-menu="categories"` | Category menu |

### Product Injection (`injectProducts`)

| Attribute | Data Source |
|-----------|-------------|
| `data-phantom-products="featured"` | Featured products |
| `data-phantom-products="all"` | All products |
| `data-phantom-products="related"` | Related products (needs `data-id`) |
| `data-phantom-products="category"` | By category (needs `data-category`) |

**Optional attributes:**
- `data-count="N"` — Number of products
- `data-id="ID"` — Reference product ID
- `data-category="slug"` — Category slug

### Post Injection (`injectPosts`)

| Attribute | Data Source |
|-----------|-------------|
| `data-phantom-posts="recent"` | Recent posts |
| `data-phantom-posts="related"` | Related posts (needs `data-id`) |
| `data-phantom-posts="category"` | By category (needs `data-category`) |

---

## Adding New Features

### New Settings

1. Add entry in `Settings_Registry::define_entries()` — choose section, type, default, sanitize
2. Setting automatically appears in Customizer + Admin Page + REST API
3. Add `data-phantom="your_key"` to HTML template

### New Page Templates

1. Create `frontend/your-page.html`
2. Add route in `Shell::$routes` array
3. Add page title in `Shell::inject_seo()` title map
4. Add `[data-phantom]` attributes for dynamic content

### New CSS Variables

1. Add setting with `css_property` and `css_selector` in Settings Registry
2. Add to `get_css_var_map()` in both Customizer and Shell
3. If numeric, add to `get_px_keys()`
4. Auto-bound in Customizer preview + PHP CSS injection

---

## WooCommerce Frontend Integration

| Feature | Method | File/Class |
|---------|--------|-----------|
| Add to cart | `wc-ajax=add_to_cart` | `phantom-data.js` → `wcAjax()` |
| Remove from cart | `wc-ajax=remove_from_cart` | `phantom-data.js` → `wcAjax()` |
| Update quantity | Store API `update-item` | `phantom-data.js` → `storeApiUpdateItem()` |
| Checkout | `wc-ajax=checkout` | `phantom-data.js` → `initCheckout()` |
| Cart display | REST `/cart` | `phantom-data.js` → `injectCart()` |
| Product data | REST `/products` | `phantom-data.js` → `injectProducts()` |

**HTML classes used by WooCommerce handlers:**
- `.add-to-cart-trigger` — Add to cart button
- `.primary_btn` — Alternative add-to-cart
- `.decrease-button` — Quantity decrease
- `.increase-button` — Quantity increase
- `.remove-product` — Remove from cart
- `#contactpage` — Checkout form
- `.cart-count` — Cart count badge
- `.shopping-cart-info` — Cart dropdown/content

---

## Security

### Input Validation (backend)
- All settings sanitized via type-specific callbacks
- Nonce verification on POST
- `manage_options` capability check
- URL sanitization (`esc_url_raw`, `wp_unslash`)

### Input Validation (frontend)
- `escapeHtml(str)` — DOM-based HTML escaping
- `sanitizeUrl(url)` — Only allows `http`, `https`, `mailto`, `tel`, relative paths
- Template content is injected via `innerHTML` (trusted — comes from own REST API)

### Output Security (backend)
- `Content-Security-Policy` header on all pages
- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: SAMEORIGIN`
- `Referrer-Policy: strict-origin-when-cross-origin`
- Customizer preview gets relaxed CSP (needs inline styles/scripts)
