# Phantom Core — Frontend Development Guide

## Architecture

The frontend is completely decoupled from WordPress. It consists of:

```
frontend/
├── html/*.html          # 22 static HTML page templates
├── assets/
│   ├── css/            # 10 CSS files (Bootstrap, theme, a11y, vendor)
│   ├── js/             # 22 JS files (phantom-data.js + vendor libs)
│   └── images/         # Static images (logos, products, icons)
```

**No PHP templates. No server-side rendering.** All dynamic data injected client-side via REST API (49 routes, 42 unique paths).

---

## How the Backend Connects to the Frontend (3 Channels)

### Channel 1: Server Injection (Shell.php → HTML)
On every request, `Shell::handle_request()` injects into the HTML:
```
<style id="phantom-customizer-css">  ← 136 CSS vars from settings
<script>window.phantomData = {...}</script>  ← REST URL, nonce, site info
<title>, <meta>  ← SEO metadata
Security Headers  ← CSP, X-Frame-Options, etc.
```

### Channel 2: REST API (PHP → JSON → JS)
`phantom-data.js` fetches `/wp-json/phantom/v1/page-data` on every page:
```javascript
window.phantomData.rest_url + 'phantom/v1/page-data'
// Returns { settings, menus, products, posts, categories, cart }
// Then injects into [data-phantom] elements
```

### Channel 3: CSS Vars (Settings → CSS → Styling)
Backend settings become CSS custom properties:
```
primary_color → --primary--color (136 vars total)
Frontend CSS: background: var(--primary--color, #default);
```

---

## How Data Binding Works

### The Core Concept

Your HTML declares what data it needs via `data-phantom` attributes. `phantom-data.js` reads these attributes and injects values from the REST API.

```html
<!-- Text content: sets textContent -->
<span data-phantom="site_title">Loading...</span>

<!-- Image src: sets src attribute -->
<img data-phantom="site_logo" src="placeholder.png">

<!-- Link href: sets href attribute -->
<a data-phantom="hero_button_url" href="#">Shop Now</a>

<!-- Background image: sets CSS background-image -->
<div data-phantom-bg="hero_bg_image"></div>

<!-- Alt text for images -->
<img data-phantom-alt="hero_image_alt" alt="Default">
```

### Complete Data Flow

```
1. Browser requests /shop
2. Shell (PHP) serves frontend/shop.html with SEO + CSS vars + phantomData injected
3. phantom-data.js runs on DOMContentLoaded
4. Fetches /wp-json/phantom/v1/page-data (mega-endpoint, cached 1hr)
5. Injects each section in order:
   ┌───────────────────────────────────────────────────┐
   │ fetch(/cart) → updateCartCount()                  │
   │ fetch(/page-data) → {settings, menus, products}   │
   │   injectSettings()  → [data-phantom="key"]        │
   │   injectBanner()    → hero sections               │
   │   injectFooter()    → footer sections              │
   │   injectSEO()       → meta tags                   │
   │   injectMenus()     → [data-phantom-menu="name"]  │
   │   injectProducts()  → [data-phantom-products]     │
   │   injectPosts()     → [data-phantom-posts]        │
   │   injectCategories()-> #category1                 │
   │   injectCart()      → .shopping-cart-info         │
   │   initWooCommerce() → add-to-cart, quantity       │
   │   initCheckout()    → checkout form submission    │
   │   initAuthForms()   → login/register/reset        │
   │   initMyAccount()   → user orders                 │
   │   initBlogPagination() → blog page nav            │
   │   initShopControls() → pagination, sorting        │
   │   hidePreloader()   → remove loading screen       │
   └───────────────────────────────────────────────────┘
6. Swup.js handles subsequent navigation via AJAX
```

### How phantom-data.js Gets the Server Data

The Shell injects a `window.phantomData` object into every page:

```html
<script>
window.phantomData = {
    rest_url: "https://example.com/wp-json/",
    nonce: "abc123...",
    plugin_url: "https://example.com/wp-content/plugins/phantom-core",
    site_name: "My Store",
    is_logged_in: false,
    user_name: null,
    user_email: null  // Only for edit_theme_options users
};
</script>
```

Then `phantom-data.js` does `fetch(phantomData.rest_url + 'phantom/v1/page-data')` to get ALL page data in one call (cached for 1 hour via transient).

---

## Data Adapter + Component Renderer Pattern (v2.0)

Since v2.0, phantom-data.js uses a **Data Adapter + Component Renderer** architecture instead of `document.createElement` for building product and category cards. This ensures the JS output always matches the frontend HTML templates.

### Architecture

```
WooCommerce REST API  ──→  adaptProductCard()  ──→  {NAME, URL, IMAGE, PRICE, ...}
                              │
                              ▼
                         renderTemplate(PRODUCT_CARD_TPL, data)
                              │
                              ▼
                         innerHTML / insertAdjacentHTML
```

### Template Strings (Source of Truth)

Two template strings live at the top of `phantom-data.js`:

**`PRODUCT_CARD_TPL`** — Exact HTML structure for product cards:
```html
<div class="product-card" data-tilt data-reveal-item>
  <div class="product-image" data-image-zoom>
    {{BADGE}}
    <a href="{{URL}}"><img loading="lazy" src="{{IMAGE}}" alt="{{NAME}}"></a>
    {{ACTIONS}}
  </div>
  <div class="product-info">
    {{CATEGORIES}}
    {{RATING}}
    {{TAGLINE}}
    <div class="product-price-row">
      <span class="product-price">{{PRICE}}</span>
      {{ATC_BUTTON}}
    </div>
  </div>
</div>
```

**`CATEGORY_CARD_TPL`** — Exact HTML structure for category cards:
```html
<a href="{{URL}}" class="{{CLASSES}}" data-tilt data-reveal-item>
  <div class="category-card-bg">
    <img loading="lazy" src="{{IMAGE}}" alt="{{ALT}}">
    <div class="category-card-overlay"></div>
  </div>
  <div class="category-card-content">
    <span class="category-count">{{COUNT}}</span>
    <h3 class="category-name">{{NAME}}</h3>
    <span class="category-cta">{{CTA}} <i class="fas fa-arrow-right"></i></span>
  </div>
</a>
```

### Key Functions

| Function | Purpose |
|----------|---------|
| `renderTemplate(tpl, data)` | 3-line `{{KEY}}` → value replacement. No dependencies, no DOM. |
| `adaptProductCard(raw, settings, showSaleBadge, saleBadgeText)` | Normalizes WooCommerce product data — handles badges, catalog mode, wishlist/quickview, categories, rating stars, price (sale/regular/HTML), ATC button type. |
| `adaptCategoryCard(raw, idx, total)` | Normalizes category data — large card for first item, accent card for odd-last. |

### How to Update Card Design

If you change the HTML card structure in templates, update the corresponding template string in `phantom-data.js`. Do NOT modify `adaptProductCard()` or `buildProductCard()` for HTML changes — only if the data fields change.

---

## How to Edit the Frontend Without Breaking Anything

### Safe Edits (visual only — zero backend impact)

| What to Edit | How | Backend Impact |
|-------------|-----|---------------|
| CSS styles | Edit `frontend/assets/css/style.css` | None |
| HTML layout | Edit HTML files — keep `data-phantom` attributes | None |
| Images | Replace files in `frontend/assets/images/` | None |
| Colors | Use Customizer (no file editing needed) | None |
| Typography | Use Customizer (no file editing needed) | None |
| Spacing/layout | Use Customizer CSS vars | None |
| Add CSS libraries | Include new `<link>` tags in HTML | None |
| Add JS libraries | Include new `<script>` tags in HTML | None |
| Replace text | Edit HTML directly or add `data-phantom="setting_key"` | None |
| Reorder sections | Move HTML blocks in template | None |

### CRITICAL: NEVER Remove These

These are the connection points between HTML templates and the JS data bridge. Removing them breaks the feature:

```
[data-phantom="key"]           — DO NOT REMOVE (settings injection)
[data-phantom-menu="name"]     — DO NOT REMOVE (menu injection)
[data-phantom-products="type"] — DO NOT REMOVE (product grids)
[data-phantom-posts="type"]    — DO NOT REMOVE (blog posts)
[data-phantom-product]         — DO NOT REMOVE (single product page)
[data-phantom-post]            — DO NOT REMOVE (single post page)
[data-phantom-bg="key"]        — DO NOT REMOVE (background images)
data-phantom-alt="key"         — DO NOT REMOVE (image alt text)
.shopping-cart-info            — DO NOT REMOVE (cart display)
#category1                     — DO NOT REMOVE (categories list)
.loader-mask / #preloader      — DO NOT REMOVE (page loader)
#contactpage                   — DO NOT REMOVE (checkout form)
.cart-count                    — DO NOT REMOVE (cart badge)
#phantom-account-content       — DO NOT REMOVE (my-account page)
```

### CSS Class Names Used by phantom-data.js

These class names are hardcoded in JS. If you rename them in HTML, the JS functions won't find their targets:

| Function | Selector | Purpose |
|----------|----------|---------|
| `updateCartCount()` | `.cart-count`, `[data-phantom="cart_count"]` | Badge number |
| `updateCartTotal()` | `.cart-total`, `[data-phantom="cart_total"]` | Total price |
| `renderRelatedProducts()` | `.related-products-grid`, `.related-products-slider` | Related products |
| `showAddToCartNotification()` | `.notification-popup` | Toast message |
| `closeCartDrawer()` | `.cart-drawer`, `.cart-overlay` | Side cart |
| `renderSearchSuggestions()` | `.search-suggestions`, `.search-dropdown` | Live search |
| `mobileMenuToggle()` | `.mobile-menu-toggle`, `.nav-menu` | Hamburger menu |
| `stickyHeader()` | `header`, `.header` | Scroll behavior |
| `addToCartHandler()` | `.add-to-cart-trigger`, `.primary_btn` | Add-to-cart buttons |
| `quantityDecrease()` | `.decrease-button` | Quantity down |
| `quantityIncrease()` | `.increase-button` | Quantity up |
| `removeFromCart()` | `.remove-product` | Remove from cart |
| `initCheckout()` | `#contactpage` | Checkout form ID |
| `initMyAccount()` | `#phantom-account-content` | Account page content |

---

## How to Add New Features

### New Setting
1. Add entry in `Settings_Registry::define_entries()` — choose key, type, default, sanitize, section
2. Automatically appears in Customizer + Admin Page + REST API
3. Add `data-phantom="your_key"` to HTML template
4. If CSS var: add to `get_css_var_map()` in `class-settings-registry.php`
5. If numeric: add to `get_px_keys()`

### New Page Template
1. Create `frontend/your-page.html`
2. Add route in `Shell::$routes` array
3. Add SEO title in `Shell::get_meta_tags()` title map
4. Add `data-phantom` attributes for dynamic content

### New REST Endpoint
1. Add method to `Rest_Controller` class
2. Register in `register_routes()` with `register_rest_route()`
3. Set `permission_callback` — use `__return_true` for public, nonce methods for auth
4. Return type must be `WP_REST_Response|WP_Error`
5. Add frontend consumer in `phantom-data.js` or your own JS file

### New Frontend JS
1. Create `frontend/assets/js/my-feature.js`
2. Reference `window.phantomData` for REST URL, nonce, settings
3. Use `phantomData.rest_url + 'phantom/v1/...'` for API calls
4. Include in HTML template via `<script src="...">`

---

## WooCommerce Frontend Integration

| Feature | Method | Consumed By |
|---------|--------|-------------|
| Add to cart | `wc-ajax=add_to_cart` | `.add-to-cart-trigger`, `.primary_btn` |
| Remove from cart | `wc-ajax=remove_from_cart` | `.remove-product` |
| Update quantity | Store API `update-item` | `.decrease-button`, `.increase-button` |
| Checkout | `wc-ajax=checkout` | `#contactpage` form |
| Cart display | REST `/phantom/v1/cart` | `.shopping-cart-info`, `.cart-count` |
| Product data | REST `/phantom/v1/products` | `[data-phantom-products]` |
| Coupon | POST `/phantom/v1/cart/coupon` | `.coupon-input`, `.apply-coupon-btn` |
| Shipping | GET `/phantom/v1/cart/shipping-methods` | `.checkout-shipping-section` |
| Reviews | GET `/phantom/v1/woo/reviews` | `.reviews-container` |
| Attributes | GET `/phantom/v1/woo/attributes` | Product filters |
| Variations | GET `/phantom/v1/woo/variations` | Product options |

**Key CSS Classes for WooCommerce:**
- `.add-to-cart-trigger` — Click handler for adding products to cart
- `.primary_btn` — Alternative add-to-cart (used in product detail)
- `.decrease-button` / `.increase-button` — Quantity stepper
- `.remove-product` — Remove item button
- `#contactpage` — Form ID for checkout submission
- `.coupon-input` / `.apply-coupon-btn` — Coupon code input + button
- `.cart-count` — Cart item count badge (in header)
- `.shopping-cart-info` — Cart dropdown / slide-in panel
- `.checkout-shipping-section` — Shipping method radio buttons

---

## Security

### Backend
- All settings sanitized via type-specific callbacks
- Nonce verification on all POST operations
- `manage_options` / `edit_theme_options` capability checks
- URL sanitization via `esc_url_raw`, `wp_unslash`
- CSP headers on all frontend pages
- `user_email` only exposed to `edit_theme_options` users

### Frontend
- `escapeHtml(str)` — DOM-based HTML escaping for user-generated content
- `sanitizeUrl(url)` — Only allows `http`, `https`, `mailto`, `tel`, relative paths
- Template content injected via `innerHTML` — trust boundary: comes from own REST API
- No `eval()`, no `document.write()`, no `postMessage` in any JS file
- All `.catch()` handlers present on promises — console errors with UI fallbacks

### Security Headers (injected by Shell)
```
Content-Security-Policy: default-src 'self' ...
X-Content-Type-Options: nosniff
X-Frame-Options: SAMEORIGIN
Referrer-Policy: strict-origin-when-cross-origin
Permissions-Policy: geolocation=(), microphone=(), camera=()
```

---

## Frontend Files Reference

| File | Purpose | When to Edit |
|------|---------|-------------|
| `frontend/*.html` | Page templates | Change layout, add/remove sections |
| `frontend/assets/js/phantom-data.js` | Core data bridge (718 lines) | Change data injection logic |
| `frontend/assets/js/phantom-bridge.js` | Utility helpers | Add shared helper functions |
| `frontend/assets/js/phantom-dark-mode.js` | Dark mode toggle | Change dark mode behavior |
| `frontend/assets/css/style.css` | Theme CSS | Change visual styling |
| `frontend/assets/css/responsive.css` | Responsive rules | Change breakpoint behavior |
| `frontend/assets/css/a11y.css` | Accessibility styles | Skip link, focus, reduced motion |
| `frontend/assets/images/` | Static assets | Add/replace images |

### phantom-data.js Module Reference (718 lines, 55+ methods)

The file is organized as 11 IIFE modules under `window.Phantom*` namespaces:

| Module | Namespace | Methods | Purpose |
|--------|-----------|---------|---------|
| Event Services | `PhantomEvents` | `on()`, `off()`, `emit()`, `onSettingChange()`, `offSettingChange()`, `emitSettingChange()`, `consumeStore()` | Pub/sub event system for settings and cart changes |
| API Service | `PhantomServices.Api` | `get()`, `post()`, `invalidateCache()`, `getProducts()`, `getPageData()`, `getCart()`, `postContact()`, `fetchWithRetry()`, `getNonce()`, `setSetting()`, `saveChanges()` | REST API client with caching and retry |
| Cart Service | `PhantomServices.Cart` | `init()`, `add()`, `remove()`, `updateQuantity()`, `getCount()`, `getTotal()` | Cart CRUD with event emissions |
| Auth Service | `PhantomServices.Auth` | `login()`, `register()`, `logout()`, `resetPassword()` | Authentication endpoints |
| Product Adapter | `PhantomAdapters.ProductAdapter` | `normalize()`, `normalizeCollection()` | Normalizes raw WooCommerce product data |
| Category Adapter | `PhantomAdapters.CategoryAdapter` | `normalize()`, `normalizeCollection()` | Normalizes category data |
| Component Renderer | `PhantomRenderer.ComponentRenderer` | `renderTemplate()`, `escapeHtml()`, `sanitizeUrl()`, `getCurrencySymbol()`, `createElement()` | Low-level rendering utilities |
| Product Card | `PhantomRenderer.ProductCard` | `setTemplate()`, `render()`, `renderAll()` | Product card HTML generation via template string |
| Category Card | `PhantomRenderer.CategoryCard` | `setTemplate()`, `render()`, `renderAll()` | Category card HTML generation via template string |
| Hero Renderer | `PhantomRenderer.HeroRenderer` | `render()` | Hero/banner responsive `<picture>` rendering |
| Core Bootstrap | `PhantomCore` | `init()`, `onReady()` | Initializes all modules, fetches page-data, triggers injectors |
| Bridge Shim | `PhantomBridge` | `init()`, `getSetting()`, `setSetting()`, `saveChanges()`, `onSettingChange()`, `offSettingChange()`, `highlightElement()`, `openEditor()`, `getCssVars()` | Backward-compatible API for editing integration |

**Data injection** is handled by `PhantomInjector` (separate file) which reads `window.PhantomData` for settings, menus, and products — or fetches them via `PhantomServices.Api.getPageData()`. The old monolithic inject/inline functions have been replaced by this modular service/adapter/renderer architecture.
