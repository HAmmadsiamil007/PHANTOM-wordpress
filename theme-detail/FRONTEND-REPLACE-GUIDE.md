# Frontend Replacement Guide

> How to completely replace the HTML/CSS/JS frontend without breaking the WordPress REST API connections, CSS variable system, and SPA routing.

---

## 1. Architecture: How It Connects

```
PHP Backend (WordPress)
  │
  ├─ Shell.php: Routes URL → Loads HTML template
  │     - Matches $_SERVER['REQUEST_URI'] to internal route
  │     - Injects CSS variables as <style>
  │     - Injects SEO meta tags
  │     - Injects phantomData JS object
  │     - Injects phantom-data.js
  │
  ├─ rest-controller.php: phantom/v1 API
  │     - Endpoints: settings, menus, products, posts, pages, cart, hero
  │
  └─ class-customizer.php: CSS var generation
        - 63 CSS vars injected into Swup header

Frontend (Static HTML → SPA via Swup)
  │
  ├─ frontend/*.html — 27 page templates
  ├─ frontend/assets/css/ — CSS files
  ├─ frontend/assets/js/phantom-data.js — Core data bridge
  └─ frontend/assets/js/vendor/ — Swup, Bootstrap, etc.
```

### Critical Connection Points

1. **Route slug** → Matched URL → Loaded HTML file
2. **CSS variable names** → Injected as `<style>` → Used in CSS
3. **`data-phantom` attributes** → JS finds elements → Injects content
4. **CSS class names in JS** → phantom-data.js queries by specific classes
5. **phantomData JS object** → Injected in `<script>` → Consumed by phantom-data.js
6. **REST API URL (`rest_url`)** → Fetch target

---

## 2. Route System (Shell.php:120-240)

The Shell maps URL paths to HTML template files:

```php
private $routes = [
    '/'            => 'frontend/index.html',
    '/shop'        => 'frontend/shop.html',
    '/product/{slug}' => 'frontend/product-detail.html',
    '/cart'        => 'frontend/cart.html',
    '/checkout'    => 'frontend/checkout.html',
    '/blog'        => 'frontend/blog.html',
    '/blog/{slug}' => 'frontend/single-blog.html',
    '/login'       => 'frontend/login.html',
    '/contact'     => 'frontend/contact.html',
    '/about'       => 'frontend/about.html',
    '/faq'         => 'frontend/faq.html',
    '/coming-soon' => 'frontend/coming-soon.html',
    '/404'         => 'frontend/404.html',
    '/thank-you'   => 'frontend/thank-you.html',
    // ... 15 more routes
];
```

### To Change Route Slugs

Edit `Shell.php` lines ~120-160:
```php
// Change '/shop' to '/products'
private $routes = [
    '/products'    => 'frontend/shop.html',
    // ... rest
];
```

### To Add New Pages

1. Create the HTML file: `frontend/my-new-page.html`
2. Add the route: `'/my-page' => 'frontend/my-new-page.html'`
3. Add the `[data-phantom]` attributes for dynamic content
4. Add SEO title/description in `Shell.php::get_meta_tags()`

### SPA Navigation

Swup handles page transitions. Links must:
- Use `<a href="/shop">Shop</a>` (not `#` or external URLs)
- NOT have `data-swup-preload` unless needed
- Be within Swup's content area (default: `#swup`)

---

## 3. CSS Variable System

### Source of Truth

CSS variables are generated in two places (must be kept in sync):

| Location | Lines | Purpose |
|----------|-------|---------|
| `class-customizer.php::get_css_var_map()` | ~260-310 | Defines var→setting mapping |
| `Shell.php::inject_css_variables()` | ~330-398 | Injects vars into `<head>` |

### Complete Variable List (63 vars)

```css
/* Header (8) */
--phantom-header-bg, --phantom-header-text, --phantom-header-padding-y,
--phantom-header-padding-x, --phantom-header-border-color,
--phantom-mobile-header-height, --phantom-header-banner-height

/* Navigation (2) */
--phantom-menu-height, --phantom-submenu-width

/* Footer (5) */
--phantom-footer-text-color, --phantom-footer-heading-color,
--phantom-footer-link-color, --phantom-footer-border-color,
--phantom-footer-bg-color

/* Typography (8) */
--phantom-heading-font, --phantom-body-font, --phantom-base-font-size,
--phantom-heading-font-weight, --phantom-body-font-weight,
--phantom-body-line-height, --phantom-letter-spacing, --phantom-text-case

/* Colors (12) */
--phantom-primary-color, --phantom-secondary-color,
--phantom-accent-color, --phantom-text-color, --phantom-heading-color,
--phantom-bg-color, --phantom-header-bg-color, --phantom-footer-bg-color,
--phantom-link-color, --phantom-link-hover-color,
--phantom-border-color, --phantom-sale-color

/* Buttons (8) */
--phantom-button-bg, --phantom-button-text-color,
--phantom-button-hover-bg, --phantom-button-hover-text,
--phantom-button-radius, --phantom-button-padding-y,
--phantom-button-padding-x, --phantom-button-font-size

/* Forms (2) */
--phantom-input-radius, --phantom-input-height

/* Spacing (6) */
--phantom-section-padding-y, --phantom-section-padding-x,
--phantom-container-gutter, --phantom-content-gap,
--phantom-element-margin, --phantom-widget-spacing

/* Layout (5) */
--phantom-container-width, --phantom-boxed-width,
--phantom-content-width, --phantom-sidebar-width, --phantom-columns

/* Responsive (4) */
--phantom-breakpoint-xl, --phantom-breakpoint-lg,
--phantom-breakpoint-md, --phantom-breakpoint-sm

/* Announcement Bar (2) */
--phantom-announcement-bg, --phantom-announcement-text-color

/* Misc (1) */
--phantom-section-spacing
```

### PX Keys (22 numeric values)

These vars get `px` appended as unit:

```
header-padding-y, header-padding-x, mobile-header-height, header-banner-height,
menu-height, submenu-width, base-font-size, button-radius, button-padding-y,
button-padding-x, button-font-size, input-radius, input-height,
section-padding-y, section-padding-x, container-gutter, content-gap,
element-margin, widget-spacing, container-width, boxed-width, sidebar-width
```

### To Change CSS Vars

1. Edit the setting value in WordPRess Admin → Phantom → Settings, OR
2. Add a new setting in `class-settings-registry.php`
3. Add the CSS var mapping in `get_css_var_map()` in BOTH `class-customizer.php` and `Shell.php`
4. Add the px key in `get_px_keys()` in BOTH files
5. Use `var(--phantom-your-var)` in your CSS

### To Change CSS Var Names

1. Find all usages in `class-customizer.php` (2 locations: `get_css_var_map()` and `get_px_keys()`)
2. Find all usages in `Shell.php` (same 2 locations)
3. Find all usages in CSS files: `rg "phantom-.*var-name" --include="*.css"`
4. Find all usages in HTML files: `rg "phantom-.*var-name" --include="*.html"`
5. Update all consistently

**⚠️ CRITICAL: The CSS var list in customizer.php and Shell.php must be kept in sync.**

---

## 4. Data Binding: `[data-phantom]` Attributes

phantom-data.js reads settings from the REST API and injects content into HTML elements via `data-phantom` attributes.

### How It Works

```html
<!-- In your HTML template: -->
<h1 data-phantom="hero_title"></h1>
<img data-phantom-src="hero_image" alt="">

<!-- phantom-data.js runs: -->
<!-- 1. Fetches GET /wp-json/phantom/v1/settings -->
<!-- 2. Reads phantomData settings object (injected inline) -->
<!-- 3. Finds elements by [data-phantom] attribute -->
<!-- 4. Sets innerHTML or src attributes -->

<!-- Result after JS injection: -->
<h1 data-phantom="hero_title">Summer Sale 2026</h1>
<img data-phantom-src="hero_image" src="https://...summer-banner.jpg" alt="">
```

### Complete Selector Map

phantom-data.js uses these CSS selectors to find elements (lines 10-235):

| Setting Key | Attribute | Method | CSS Selector |
|-------------|-----------|--------|-------------|
| `site_logo` | `data-phantom="site_logo"` | `src` | `[data-phantom="site_logo"]` + `[data-phantom-alt="logo"]` |
| `site_title` | `data-phantom="site_title"` | `text` | `[data-phantom="site_title"]`, `.logo-text` |
| `favicon` | `data-phantom="favicon"` | `src` | `[data-phantom="favicon"]`, `[data-phantom="favicon_url"]` |
| `preloader_logo` | `data-phantom="preloader_logo"` | `src` | `[data-phantom="preloader_logo"]` |
| `preloader_enable` | `data-phantom="preloader_enable"` | `display` | `#preloader`, `.preloader` |
| `header_sticky` | `data-phantom="header_sticky"` | `class` | `header`, `.header` |
| `header_phone` | `data-phantom="header_phone"` | `text` | `.header-phone` |
| `header_email` | `data-phantom="header_email"` | `text` | `.header-email` |
| `header_btn_text` | `data-phantom="header_btn_text"` | `text` | `.header-btn` |
| `header_btn_url` | `data-phantom="header_btn_url"` | `href` | `.header-btn` |
| `topbar_show` | `data-phantom="topbar_show"` | `display` | `.topbar` |
| `announcement_bar_text` | `data-phantom="announcement_text"` | `text` | `.announcement-bar-text` |
| `hero_*` (10+) | `data-phantom="hero_*"` | text/src | `.hero-*`, `[data-phantom="hero_*"]` |
| `home_banner_img1/2` | `data-phantom="home_banner_img1"` | `src` | `.home-banner img`, `.banner-img-1` |
| `banner_heading/title/desc/btn` | `data-phantom="banner_*"` | `text` | `[data-phantom="banner_*"]` |
| `home_categories_*` | `data-phantom="categories_*"` | `text/src` | `.category-card` |
| `products` (WC) | `data-phantom="products"` | `html` | `[data-phantom="products"]`, `.product-grid` |
| `categories` (WC) | `data-phantom="categories"` | `html` | `[data-phantom="categories"]` |
| `blog_posts` | `data-phantom="blog_posts"` | `html` | `[data-phantom="blog_posts"]` |
| `testimonials_*` | `data-phantom="testimonials"` | `html` | `[data-phantom="testimonials"]` |
| `instagram_*` | `data-phantom="instagram_*"` | `html` | `[data-phantom="instagram_*"]` |
| `benefits_*` | `data-phantom="benefits"` | `html` | `[data-phantom="benefits"]` |
| `brands_*` | `data-phantom="brands"` | `html` | `[data-phantom="brands"]` |
| `promotion_*` | `data-phantom="promotion"` | `html` | `[data-phantom="promotion"]` |
| `footer_*` | `data-phantom="footer_*"` | `text/html` | `[data-phantom="footer_*"]` |
| `social_links` | `data-phantom="social_links"` | `html` | `[data-phantom="social_links"]` |
| `footer_payment_icons` | `data-phantom="payment_icons"` | `html` | `[data-phantom="payment_icons"]` |
| `search_suggestions` | `data-phantom="search_suggestions"` | `html` | `[data-phantom="search_suggestions"]` |
| `menu_items` | `data-phantom="menu_items"` | `html` | `[data-phantom="menu_items"]` |
| `cart_items` | `data-phantom="cart_items"` | `html` | `[data-phantom="cart_items"]` |
| `cart_count` | `data-phantom="cart_count"` | `text` | `[data-phantom="cart_count"]` |
| `cart_total` | `data-phantom="cart_total"` | `text` | `[data-phantom="cart_total"]` |
| `breadcrumbs` | `data-phantom="breadcrumbs"` | `html` | `[data-phantom="breadcrumbs"]` |
| `current_page_title` | `data-phantom="page_title"` | `text` | `[data-phantom="page_title"]` |

### Also Queries by Class Name

phantom-data.js also hardcodes class names for certain operations:

| Function | Selectors | Purpose |
|----------|-----------|---------|
| `updateCartCount()` | `.cart-count`, `[data-phantom="cart_count"]` | Badge number |
| `updateCartTotal()` | `.cart-total`, `[data-phantom="cart_total"]` | Total price |
| `renderRelatedProducts()` | `.related-products-grid`, `.related-products-slider` | Sibling products |
| `showAddToCartNotification()` | `.notification-popup` | Toast message |
| `closeCartDrawer()` | `.cart-drawer`, `.cart-overlay` | Side cart |
| `renderSearchSuggestions()` | `.search-suggestions`, `.search-dropdown` | Live search |
| `mobileMenuToggle()` | `.mobile-menu-toggle`, `.nav-menu` | Hamburger menu |
| `stickyHeader()` | `header`, `.header` | Scroll behavior |

---

## 5. Replacing Templates (Step by Step)

### Option A: Keep the Same Routes, Swap HTML

1. Create your new HTML file (e.g., `frontend/shop.html`)
2. Keep `id="swup"` as the container element
3. Keep `data-phantom` attributes where you want dynamic content
4. Keep CSS class names used by phantom-data.js
5. Keep vendor includes (Bootstrap, Swup, etc.) OR update phantom-data.js to not depend on them
6. Verify the page loads at `/shop`

### Option B: Change Routes AND HTML

1. Add/change route in `Shell.php` `$routes` array
2. Create your HTML file at the new path
3. Add meta tags for SEO in `Shell.php::get_meta_tags()`
4. Follow the rules below

### Template Structure Rules

Every frontend HTML file MUST have:

```html
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Page Title</title>

  <!--
    Shell.php will inject:
    - <style id="phantom-css-vars"> with all CSS variables
    - <meta> tags for SEO
    - <script> with phantomData JSON
    - <script> tags for phantom-data.js, vendor JS
  -->

  <!-- Your CSS -->
  <link rel="stylesheet" href="frontend/assets/css/main.css">
</head>
<body>
  <!-- Header template -->

  <main id="swup"><!-- Swup container — REQUIRED for SPA -->
    <!-- Your page content -->
  </main>

  <!-- Footer template -->
</body>
</html>
```

### What NOT to Change

- ❌ `id="swup"` — Swup uses this for page transitions
- ❌ `data-phantom` attributes — data bridge uses these
- ❌ Class names used by phantom-data.js (listed above)
- ❌ CSS variable names (`--phantom-*`)
- ❌ `phantomData` global variable name
- ❌ REST API URL pattern (`/wp-json/phantom/v1/*`)
- ❌ Route slugs without updating Shell.php
- ❌ The `header` and `footer` element structure for Swup transitions
- ❌ `type="module"` on phantom-data.js script tag (if using ES module)

---

## 6. Replacing CSS

### Safe to Change

- ✅ All style rules in `frontend/assets/css/`
- ✅ Layout, spacing, colors in CSS (the variables remain the source of truth)
- ✅ Responsive breakpoints (use `var(--phantom-breakpoint-*)` or override)
- ✅ Component styles, animations, transitions
- ✅ Font imports, typography defaults

### Must Keep

- ❌ Must keep `var(--phantom-*)` usage if you want dynamic theming
- ❌ Must keep CSS class names used by phantom-data.js
- ❌ Must keep Swup transition hooks if used
- ❌ Must keep `.cart-count`, `.cart-total`, etc. if cart JS is used

### CSS Variable Architecture

Your CSS should use the CSS vars for dynamic customization:

```css
/* ✅ DO: Use CSS vars for customizable properties */
.button {
  background: var(--phantom-primary-color);
  border-radius: var(--phantom-button-radius);
  font-size: var(--phantom-button-font-size);
  padding: var(--phantom-button-padding-y) var(--phantom-button-padding-x);
}

/* ✅ DO: Fall back to CSS vars */
.container {
  max-width: var(--phantom-container-width, 1200px);
}

/* ❌ DON'T: Hardcode values that could be theme settings */
.button {
  background: #0066cc;    /* BAD — should use var(--phantom-primary-color) */
  border-radius: 4px;     /* BAD — should use var(--phantom-button-radius) */
}
```

---

## 7. Replacing JavaScript

### phantom-data.js — The Core Bridge

This file is the critical connection between WordPress and your HTML. Functions:

| Function | Purpose | Dependencies |
|----------|---------|-------------|
| `loadSettings()` | Fetch all phantom settings | `phantomData.rest_url` |
| `renderLogo()` | Injects logo | `window.phantomData` |
| `renderHero()` | Injects hero content | jQuery, `phantomData` |
| `renderProductGrid()` | Renders products | jQuery, WooCommerce |
| `renderCategories()` | Renders category cards | — |
| `updateCartCount()` | Cart badge | jQuery |
| `addToCart()` | AJAX add to cart | jQuery, WC ajax |
| `applyTheme()` | Applies CSS vars | `phantomData.settings` |
| `initSearch()` | Live search | — |
| `initMobileMenu()` | Mobile menu toggle | — |
| `stickyHeader()` | Scroll behavior | — |

### To Replace phantom-data.js

1. Read the full file to understand all connections
2. Create a new data bridge that:
   - Fetches the same REST API endpoints
   - Reads the same `phantomData` object
   - Injects content into matching selectors
3. OR keep phantom-data.js and only replace the visual/CSS layer

### To Add New JS Functionality

1. Create a new file: `frontend/assets/js/my-feature.js`
2. Enqueue it after phantom-data.js in `Shell.php`
3. Use `window.phantomData.settings` to read settings
4. Use `phantomData.rest_url` for API calls

---

## 8. Adding New REST Endpoints

In `class-rest-controller.php`, add new routes:

```php
public function register_routes() {
    // Existing routes...
    
    // Add new route
    register_rest_route('phantom/v1', '/my-new-data', [
        'methods' => 'GET',
        'callback' => [$this, 'get_my_new_data'],
        'permission_callback' => '__return_true',
    ]);
}

public function get_my_new_data() {
    // Fetch data
    $data = get_option('phantom_my_option', []);
    
    // Sanitize
    return new WP_REST_Response([
        'success' => true,
        'data' => $data,
    ]);
}
```

Then in your frontend JS:
```javascript
fetch(phantomData.rest_url + 'phantom/v1/my-new-data')
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      // Use data.data
    }
  });
```

---

## 9. Complete Migration Checklist

### Phase 1: Audit (Complete First)
- [ ] Show current templates in `frontend/`
- [ ] List all `data-phantom` attributes and their types
- [ ] Map all REST API endpoints
- [ ] Note all CSS var usages
- [ ] Identify any hardcoded content vs. dynamic content

### Phase 2: Build New Templates
- [ ] Create new HTML files with correct `data-phantom` attributes
- [ ] Ensure `id="swup"` on main content area
- [ ] Keep required CSS class names
- [ ] Test each template at its URL

### Phase 3: Replace CSS
- [ ] New CSS files referencing `var(--phantom-*)` vars
- [ ] Keep responsive breakpoint vars
- [ ] Keep class names used by phantom-data.js
- [ ] Test theming via Customizer → CSS vars update correctly

### Phase 4: Verify Critical Features
- [ ] Logo changes via Customizer
- [ ] Color changes via Customizer
- [ ] Navigation menu updates
- [ ] Product grid renders
- [ ] Cart count updates
- [ ] Checkout flow works
- [ ] Mobile menu works
- [ ] Search works
- [ ] SEO meta tags inject correctly
- [ ] Page transitions (Swup) work

### Phase 5: Polish
- [ ] Performance: lazy load, minify, cache
- [ ] Accessibility: keyboard nav, focus states, ARIA
- [ ] Replace inline `<style>` injection with external CSS
- [ ] Replace Swup with a lighter SPA if needed

---

## 10. Common Pitfalls

| Pitfall | Symptom | Fix |
|---------|---------|-----|
| Missing `data-phantom` attr | Content not loaded | Add `data-phantom="setting_key"` |
| Wrong REST API URL | Empty data, console error | Check `phantomData.rest_url` in `<script>` |
| Swup container missing | Full page reload on nav | Add `id="swup"` element |
| CSS var name mismatch | Fallback var visible | Sync Shell.php + customizer.php var lists |
| Class name mismatch | JS function does nothing | Check phantom-data.js selector list |
| PX key missing | CSS var without `px` suffix | Add to `get_px_keys()` in Shell.php |
| Route slug mismatch | 404 in SPA | Check Shell.php `$routes` array |
| Missing jQuery | Cart/AJAX broken | phantom-data.js requires jQuery |
| Missing Swup scripts | Page transition broken | Include Swup CSS + JS |
| File path wrong | 404 on template | Path is relative to plugin root |
