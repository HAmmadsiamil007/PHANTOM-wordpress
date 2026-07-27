# Premium Frontend Template — CLI Agent Guide

> **Purpose:** Complete guide for any AI coding agent to build premium, animated, 3D-enabled, scrollable frontend templates that connect to **Phantom Core** (decoupled WordPress framework with ~612 settings, 49 REST routes (42 unique paths), 136 CSS vars).
>
> **Stack:** Phantom Core Data Bridge + Bootstrap 5 + GSAP + Three.js + Lenis + Swiper
>
> **Output:** Production-ready static HTML template dynamically driven by WordPress via REST API

---

## Render Engine Architecture

```
WordPress

WooCommerce

Settings Registry (~612 settings, 46 sections)

Theme Options (Customizer → CSS vars → Frontend)

Customizer (16 panels, 46 sections, 13 custom controls, 136 CSS vars)

Menus (data-phantom-menu → WP Nav Menus)

Widgets (sidebar/ footer widgets via REST)

Products (WooCommerce product grids via data-phantom-products)

Categories (data-phantom-categories via REST)

API (49 routes under phantom/v1, 42 unique paths)
      │
      ▼
          Render Engine
────────────────────────────────────
          Shell.php (SPA Router)
              template_redirect
              Routes URL → HTML file
              Injects: CSS vars, SEO, phantomData JS
              Security headers, auth state
                   │
                   ▼
          phantom-data.js (718 lines, 55+ methods)
              Fetches /phantom/v1/page-data
              Injects settings, menus, products, posts
              Initializes cart, checkout, auth, search
                   │
                   ▼
          Swup.js (SPA page transitions)
          PhantomBridge.js (setting read/write)
                   │
                   ▼
          Premium Frontend Templates
          ┌─────────────────────────────────────┐
          │ Bootstrap 5 (grid, components)      │
          │ GSAP + ScrollTrigger (animations)   │
          │ Three.js (3D scenes)                │
          │ Lenis (smooth scroll)               │
          │ Swiper (touch sliders)              │
          │ Dark mode (CSS vars + cookie)       │
          └─────────────────────────────────────┘
```

---

## Architecture Overview

The full pipeline, visualized:

```
WordPress Backend (stays)
┌─────────────────────────────────────────────────────────┐
│  Settings Registry ──► Customizer ──► WP_Customize      │
│       │                      │          (controls)      │
│       ▼                      ▼                           │
│  Options DB ◄────────── save_setting()                   │
│       │                                                  │
│       ▼                                                  │
│  REST Controller ──► /phantom/v1/settings                │
│       │                                                  │
│       ▼                                                  │
│  Shell/Hooks ──► phantom-data.js (wp_localize_script)   │
└───────┬─────────────────────────────────────────────────┘
        │  DATA
        ▼
Swappable Frontend
┌─────────────────────────────────────────────────────────┐
│  PhantomBridge.js  ◄── reads PhantomData / REST API    │
│       │                                                 │
│       ├──► CSS Vars (<style> injection)                 │
│       ├──► Live Editor (inline edit controls)           │
│       ├──► Bootstrap / GSAP / Three.js / Swiper         │
│       └──► Theme CSS (uses injected CSS vars)           │
└─────────────────────────────────────────────────────────┘
```

### 3 Data Channels Between Backend & Frontend

| Channel | Direction | What | Mechanism |
|---------|-----------|------|-----------|
| **1. Server Injection** | PHP → HTML | 136 CSS vars, SEO meta, phantomData JS config, security headers | Shell.php injects into `<head>` before serving |
| **2. REST API** | PHP → JSON → JS | Settings, menus, products, posts, cart, auth | phantom-data.js fetches `/phantom/v1/page-data` |
| **3. CSS Variables** | Settings → CSS → Style | All design tokens as `--custom-properties` | `:root { --primary--color: #... }` via `<style id="phantom-customizer-css">` |

---

## Section 1: The Connection Contract

For any HTML template to work with Phantom Core, these contracts must be satisfied.

### 1.1 Required HTML Structure

Every template MUST include:

```html
<!DOCTYPE html>
<html lang="en">
<head>
  <!-- Shell.php injects here: CSS vars, SEO meta, phantomData JS, scripts -->
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Page Title <!-- replaced by Shell SEO --></title>
  <link rel="stylesheet" href="assets/css/style.css">
  <!-- Shell.php will insert additional <link> and <style> before </head> -->
</head>
<body>
  <!-- Shell.php inserts skip-link, preloader, auth nonces after <body> -->

  <main id="swup"><!-- REQUIRED: Swup SPA container for page transitions -->
    <!-- ALL page content goes inside #swup -->
    <h1 data-phantom="hero_title">Default Title</h1>
    <nav data-phantom-menu="primary"><!-- fallback nav --></nav>
    <div data-phantom-products="featured" data-count="4"><!-- fallback --></div>
  </main>

  <!-- Shell.php injects scripts before </body> -->
  <script src="assets/js/vendor/jquery-3.7.1.min.js"></script>
  <script src="assets/js/vendor/bootstrap.bundle.min.js"></script>
  <!-- Shell.php injects phantom-data.js automatically -->
</body>
</html>
```

### 1.2 The `phantomData` JS Object

Injected by Shell.php into every page. Available at `window.phantomData`:

```javascript
window.phantomData = {
  rest_url: "https://example.com/wp-json/",       // REST API base
  nonce: "abc123...",                              // Phantom API nonce
  plugin_url: "https://.../phantom-core/",         // Plugin URL for assets
  site_name: "My Store",                           // WordPress site title
  is_logged_in: false,                             // User auth state
  user_name: null,                                 // Current user name
  user_email: null                                 // Only for edit_theme_options
};
```

### 1.3 `data-phantom` Attribute Reference (The Core Contract)

These attributes tell `phantom-data.js` what data to inject where:

| Attribute | Target Elements | Data Source | What It Does |
|-----------|----------------|-------------|--------------|
| `data-phantom="setting_key"` | Any element | Settings value | Sets `textContent` for text elements, `src` for `<img>`/`<source>`, `href` for `<a>` |
| `data-phantom-bg="setting_key"` | Any element | Image URL setting | Sets `background-image: url(...)` |
| `data-phantom-alt="setting_key"` | `<img>` | Text setting | Sets `alt` attribute |
| `data-phantom-menu="location"` | `<nav>` / `<ul>` | WP Menu API | Builds full nav tree with dropdowns |
| `data-phantom-products="count"` | Container `<div>` | WooCommerce REST | Renders product cards (grid) |
| `data-phantom-product` | Container `<div>` | Single product REST | Renders full product detail page |
| `data-phantom-posts="count"` | Container `<div>` | WP Posts REST | Renders blog post cards |
| `data-phantom-post` | Container `<div>` | Single post REST | Renders full blog post |
| `data-phantom-key="setting_key"` | Editable element | Settings value | Inline editor for admin users (contenteditable) |
| `data-phantom-dark-toggle` | Button | Cookie + localStorage | Toggles dark mode |
| `data-phantom-logout` | `<a>` | Auth REST | Logout handler |
| `data-phantom-reset-url` | `<a>` | Site URL | Password reset link |
| `data-phantom-categories` | Container `<div>` | Categories REST | Renders category list |

**Setting keys source:** ~612 keys in `Settings_Registry::define_entries()` under 46 sections including: `hero_title`, `hero_subtitle`, `hero_description`, `site_title`, `site_logo`, `footer_text`, `footer_description`, `copyright_text`, `banner_heading`, `banner_description`, `contact_email`, `contact_phone`, `contact_address`, `social_facebook`, `social_twitter`, `social_instagram`, `social_youtube`, `social_linkedin`, etc.

### 1.4 CSS Class Names Used by phantom-data.js

These class names are hardcoded in JS. Templates MUST use them:

| Class/ID | Used By | Purpose |
|----------|---------|---------|
| `.cart-count` | `updateCartCount()` | Cart badge number |
| `.cart-total` | `updateCartTotal()` | Cart total price display |
| `.shopping-cart-info` | `injectCart()` | Cart dropdown / slide-in panel |
| `.add-to-cart-trigger` | `addToCartHandler()` | Add-to-cart click handler |
| `.primary_btn` | `addToCartHandler()` | Alternative add-to-cart (product detail) |
| `.decrease-button` | `quantityDecrease()` | Quantity stepper down |
| `.increase-button` | `quantityIncrease()` | Quantity stepper up |
| `.remove-product` | `removeFromCart()` | Remove cart item |
| `.coupon-input` | `applyCoupon()` | Coupon code input field |
| `.apply-coupon-btn` | `applyCoupon()` | Apply coupon button |
| `.checkout-shipping-section` | `initShipping()` | Shipping method radio buttons |
| `#contactpage` | `initCheckout()` | Checkout form container |
| `#category1` | `injectCategories()` | Category list container |
| `#phantom-account-content` | `initMyAccount()` | My account order history |
| `.loader-mask` / `#preloader` | `hidePreloader()` | Loading screen |
| `.notification-popup` | `showAddToCartNotification()` | Toast notification |
| `.cart-drawer` / `.cart-overlay` | `closeCartDrawer()` | Side cart drawer |
| `.mobile-menu-toggle` / `.nav-menu` | `mobileMenuToggle()` | Hamburger menu |
| `.search-suggestions` / `.search-dropdown` | `renderSearchSuggestions()` | Live search dropdown |
| `.related-products-grid` / `.related-products-slider` | `renderRelatedProducts()` | Related products |
| `.reviews-container` | `injectReviews()` | Product reviews |
| `header` / `.header` | `stickyHeader()` | Scroll-based header styling |

### 1.5 Required Element IDs

| ID | Purpose | Required On |
|----|---------|-------------|
| `#swup` | SPA page transition container | ALL templates |
| `#contactpage` | Checkout form submission | `checkout.html` |
| `#category1` | Category list container | `shop.html`, `index.html` |
| `#phantom-account-content` | My account page content | `my-account.html` |
| `#hero-3d` | Three.js 3D scene container | Hero section (optional) |

---

## Section 2: System-by-System Integration

### 2.1 WordPress Core

| Feature | How It Works In Phantom Core |
|---------|------------------------------|
| **Routing** | `Shell.php` hooks `template_redirect` (priority 1), maps URL slug → HTML file. 43 routes. |
| **SEO** | Shell injects `<title>`, meta description, OG tags, Twitter Card, JSON-LD schema per page type |
| **Auth** | 4 REST endpoints: login, register, password-reset, logout. Nonce-based. JS handles forms. |
| **Users** | My account page fetches user orders via `/phantom/v1/user/orders` |
| **Contact form** | POST `/phantom/v1/contact` → `wp_mail()` to admin email |
| **Search** | `/phantom/v1/products` (WooCommerce) + `/phantom/v1/posts` with `?search=` param |
| **Security** | CSP, HSTS, X-Frame-Options, X-Content-Type-Options, Referrer-Policy, Permissions-Policy headers |
| **Media** | Image URLs from settings use `resolveUrl()` — supports absolute, relative, and `assets/` paths |

**Template reference for auth pages:**
- `login.html` → form with `#phantom-login-form`, fields `#login-email`, `#login-password`, `#login-remember`
- `join-now.html` → form with `#phantom-register-form`, fields `#register-name`, `#register-email`, `#register-password`
- `password-reset.html` → form with `#phantom-reset-form`, field `#reset-email`

### 2.2 Settings Registry (~612 settings)

Settings are the backbone. Every visual element starts as a setting entry.

**How templates consume settings:**
```html
<!-- Text setting → textContent -->
<h1 data-phantom="hero_title">Fallback Title</h1>

<!-- Image setting → src -->
<img data-phantom="site_logo" src="fallback-logo.png">

<!-- URL setting → href -->
<a data-phantom="hero_button_url" href="#">Shop Now</a>

<!-- Background image setting → CSS -->
<section data-phantom-bg="hero_bg_image" class="hero-section"></section>

<!-- Alt text -->
<img data-phantom="hero_image" data-phantom-alt="hero_image_alt" alt="Default">
```

**46 Setting Sections:**

| # | Section | Example Keys | Template Usage |
|---|---------|-------------|----------------|
| 1 | Branding | `site_title`, `site_logo`, `favicon` | All pages |
| 2 | Header | `header_bg`, `header_layout`, `sticky_header` | All pages |
| 3 | Topbar | `topbar_bg`, `topbar_text`, `topbar_show` | All pages |
| 4 | Navigation | `nav_menu_height`, `nav_submenu_width` | All pages |
| 5 | Hero | `hero_title`, `hero_subtitle`, `hero_bg_image` | Home page |
| 6 | Collections | `collection_1_title`, `collection_1_image` | Home page |
| 7 | Home Sections | `home_section_1_title`, `home_section_1_content` | Home page |
| 8 | Product Cards | `product_card_style`, `sale_badge_color` | Shop, product |
| 9 | Shop Page | `products_per_page`, `shop_layout` | Shop page |
| 10 | Product Page | `related_products_count`, `zoom_enabled` | Product detail |
| 11 | WooCommerce | `currency`, `tax_display`, `cart_style` | Cart, checkout |
| 12 | Blog | `posts_per_page`, `blog_layout`, `excerpt_length` | Blog pages |
| 13 | Footer | `footer_text`, `footer_bg`, `copyright_text` | All pages |
| 14 | Typography | `font_body`, `font_heading`, `h1_size`–`h6_size` | All pages |
| 15 | Colors | `primary_color`, `secondary_color`, `accent_color` | All pages |
| 16–46 | Buttons, Forms, Spacing, Layout, Responsive, Animations, SEO, Custom Code, Privacy, About, Contact, FAQ, Coming Soon, 404, Hero (responsive media), etc. | Various | Various pages |

### 2.3 CSS Variable Contract (136 Vars)

These CSS custom properties are injected into EVERY page. Templates MUST reference them for dynamic theming.

```css
/* ── COLORS (20) ── */
--primary--color: #6366f1;
--secondary--color: #8b5cf6;
--accent--color: #f59e0b;
--bg: #ffffff;
--text--color: #1e293b;
--heading--color: #0f172a;
--link: #6366f1;
--link--hover: #4f46e5;
--border--color: #e2e8f0;
--sale--color: #ef4444;
--light--bg--color: #f8fafc;
--grey--color: #94a3b8;
--success--color: #10b981;
--error--color: #ef4444;
--warning--color: #f59e0b;
--info--color: #3b82f6;
--gradient-start--color: #6366f1;
--gradient-end--color: #8b5cf6;
--featured-badge--color: #f59e0b;
--woo--rating: #f59e0b;

/* ── TYPOGRAPHY (24) ── */
--font-body: 'Inter', sans-serif;
--font-heading: 'Playfair Display', serif;
--font-base-size: 16px;
--font-body-weight: 400;
--font-body-spacing: 0px;
--font-heading-weight: 600;
--font-heading-case: none;
--font-heading-spacing: 0px;
--h1-size: 2.5rem;  --h1-height: 1.15;
--h2-size: 2rem;    --h2-height: 1.2;
--h3-size: 1.75rem; --h3-height: 1.25;
--h4-size: 1.5rem;  --h4-height: 1.3;
--h5-size: 1.25rem; --h5-height: 1.35;
--h6-size: 1rem;    --h6-height: 1.4;

/* ── HEADER (10) ── */
--header-bg: #ffffff;
--header-color: #1e293b;
--header-padding-x: 1rem;
--header-padding-y: 0.75rem;
--header-border-color: #e2e8f0;
--header-border-width: 1px;
--header-mobile-height: 60px;
--banner-height: 600px;
--header--height: 80px;
--color-header-bg: #ffffff;

/* ── BUTTONS (8) ── */
--button-bg: #6366f1;
--button-text: #ffffff;
--button-bg-hover: #4f46e5;
--button-text-hover: #ffffff;
--button-radius: 0.5rem;
--button-padding-x: 1.5rem;
--button-padding-y: 0.75rem;
--button-font-size: 1rem;

/* ── FORMS (2) ── */
--form-input-radius: 0.5rem;
--form-input-height: 48px;

/* ── NAVIGATION (3) ── */
--nav-menu-height: 60px;
--nav-submenu-width: 220px;
--menu--font--size: 0.95rem;

/* ── FOOTER (6) ── */
--footer--bg: #0f172a;
--footer--text: #e2e8f0;
--footer-heading: #f8fafc;
--footer-link: #94a3b8;
--footer-border-color: #1e293b;
--color-footer-bg: #0f172a;

/* ── LAYOUT / SPACING (15) ── */
--container-width: 1280px;
--content-width: 768px;
--sidebar-width: 320px;
--boxed-width: 1200px;
--layout-columns: 3;
--section-padding-x: 1.5rem;
--section-padding-y: 4rem;
--container-gutter: 1.5rem;
--content-gap: 2rem;
--element-margin-bottom: 1.5rem;
--widget-spacing: 2rem;
--home-section-spacing: 4rem;

/* ── RESPONSIVE BREAKPOINTS (4) ── */
--breakpoint-xl: 1200px;
--breakpoint-lg: 992px;
--breakpoint-md: 768px;
--breakpoint-sm: 576px;

/* ── TOPBAR (2) ── */
--topbar--bg: #0f172a;
--topbar--text: #e2e8f0;

/* ── ANNOUNCEMENT BAR (2) ── */
--announcement-bar-bg: #6366f1;
--announcement-bar-color: #ffffff;

/* ── HERO / RESPONSIVE MEDIA (6) ── */
--hero-image: url('');
--hero-object-fit: cover;
--hero-object-position: center;
--hero-bg-position: center;
--hero-overlay-opacity: 0.3;
--hero-min-height: 600px;

/* ── PRODUCT CARDS (10) ── */
--product-card-bg: #ffffff;
--product-card-radius: 0.5rem;
--product-card-shadow: 0 2px 8px rgba(0,0,0,0.08);
--product-card-hover-shadow: 0 8px 24px rgba(0,0,0,0.12);
--product-image-aspect: 1;
--product-card-title-size: 1rem;
--product-card-title-color: var(--heading--color);
--product-card-price-size: 1.125rem;
--product-card-price-color: var(--primary--color);
--product-card-gap: 1.5rem;

/* ── MISC / UTILITY (14) ── */
--border-radius: 0.5rem;
--border-radius-sm: 0.25rem;
--border-radius-lg: 1rem;
--border-radius-full: 9999px;
--transition-base: 300ms ease;
--transition-slow: 500ms ease;
--shadow-sm: 0 1px 3px rgba(0,0,0,0.1);
--shadow-md: 0 4px 12px rgba(0,0,0,0.1);
--shadow-lg: 0 8px 24px rgba(0,0,0,0.12);
--shadow-xl: 0 12px 36px rgba(0,0,0,0.15);
--z-dropdown: 1000;
--z-sticky: 1020;
--z-modal: 1030;
--z-tooltip: 1040;
```

**Usage in template CSS:**
```css
.button {
  background: var(--button-bg, #6366f1);
  color: var(--button-text, #ffffff);
  border-radius: var(--button-radius, 0.5rem);
  padding: var(--button-padding-y) var(--button-padding-x);
  font-size: var(--button-font-size, 1rem);
}

.section {
  padding-top: var(--section-padding-y);
  padding-bottom: var(--section-padding-y);
}

@media (max-width: var(--breakpoint-lg)) {
  .container { max-width: 100%; }
}
```

**Dark mode:** `[data-theme="dark"]` selector overrides color vars. Template CSS automatically responds.

### 2.4 Customizer (16 Panels, 13 Custom Controls)

The Customizer is the visual settings editor. Every setting registered in `Settings_Registry` automatically appears.

**13 Custom Control Types:**
| Control | PHP Class | Purpose |
|---------|-----------|---------|
| Alpha Color | `class-custom-control-alpha-color.php` | RGBA color picker |
| Dimension | `class-custom-control-dimension.php` | Width/height/spacing inputs |
| Typography | `class-custom-control-typography.php` | Font family, weight, size, spacing |
| Slider | `class-custom-control-slider.php` | Range slider |
| Toggle | `class-custom-control-toggle.php` | On/off switch |
| Radio Image | `class-custom-control-radio-image.php` | Image-based option selection |
| Select | `class-custom-control-select.php` | Enhanced select dropdown |
| Multi Select | `class-custom-control-multi-select.php` | Multi-value select |
| Repeater | `class-custom-control-repeater.php` | Repeatable field groups |
| Heading | `class-custom-control-heading.php` | Section heading/separator |
| Sortable | `class-custom-control-sortable.php` | Drag-and-drop reorder |
| Gradient | `class-custom-control-gradient.php` | Gradient color picker |
| Box Shadow | `class-custom-control-box-shadow.php` | Shadow configuration |

**Template connection:** None needed — Customizer changes flow through CSS vars automatically.

### 2.5 Menus (data-phantom-menu)

Menus are defined in WordPress Admin → Appearance → Menus, then injected into templates.

```html
<!-- Primary navigation (top nav) -->
<nav data-phantom-menu="primary" class="navbar navbar-expand-lg">
  <!-- JS builds: <ul><li><a href="...">Menu Item</a>
       Sub-items become dropdown <ul> -->
</nav>

<!-- Footer navigation -->
<div data-phantom-menu="footer" class="footer-links"></div>
```

**How it renders:**
```json
// GET /phantom/v1/menus/primary → returns menu tree
[{
  "title": "Home", "url": "/", "target": "", "children": []
}, {
  "title": "Shop", "url": "/shop", "target": "",
  "children": [
    {"title": "Clothing", "url": "/product-category/clothing", "target": ""},
    {"title": "Accessories", "url": "/product-category/accessories", "target": ""}
  ]
}]
```

JS builds `<ul class="nav-menu"><li><a>Home</a></li><li class="menu-item-has-children"><a>Shop</a><ul class="sub-menu">...</ul></li></ul>`

**Required CSS classes for menu styling:**
- `.nav-menu` — top-level `<ul>`
- `.menu-item-has-children` — parent `<li>` (has dropdown)
- `.sub-menu` — dropdown `<ul>`
- `.current-menu-item` — active page `<li>`

### 2.6 Widgets

Phantom Core registers **7 widget areas** via `class-core-plugin.php`:

| Widget Area ID | Name | Location | Typical Content |
|---------------|------|----------|-----------------|
| `phantom-sidebar-main` | Main Sidebar | Blog pages | Search, recent posts, categories, tags |
| `phantom-sidebar-shop` | Shop Sidebar | Product archive pages | Product filters, price slider, categories |
| `phantom-sidebar-blog` | Blog Sidebar | Blog archive/single | Recent posts, archives, categories |
| `phantom-footer-1` | Footer Widgets 1 | Footer column 1 | About text, logo |
| `phantom-footer-2` | Footer Widgets 2 | Footer column 2 | Quick links, menu |
| `phantom-footer-3` | Footer Widgets 3 | Footer column 3 | Recent posts, contact info |
| `phantom-footer-4` | Footer Widgets 4 | Footer column 4 | Newsletter signup, social |

**How widgets work with SPA templates:**

Since Phantom Core uses static HTML (no PHP templates), `dynamic_sidebar()` cannot be called directly. Two approaches:

**Approach A (Settings-based — Recommended):** Use Phantom Core settings + `data-phantom` attributes instead of widgets for sidebar/footer content.

```html
<!-- Footer columns via settings -->
<footer class="footer-custom">
  <div class="container">
    <div class="row">
      <div class="col-lg-3">
        <h3 data-phantom="footer_heading_1">About Us</h3>
        <p data-phantom="footer_description">Company description...</p>
      </div>
      <div class="col-lg-3">
        <h3>Quick Links</h3>
        <nav data-phantom-menu="footer"></nav>
      </div>
      <div class="col-lg-3">
        <h3>Contact</h3>
        <p data-phantom="contact_address">123 Street</p>
        <p data-phantom="contact_phone">+1 234 567</p>
      </div>
      <div class="col-lg-3">
        <h3 data-phantom="footer_heading_4">Newsletter</h3>
        <!-- Newsletter form -->
      </div>
    </div>
  </div>
</footer>
```

**Approach B (REST API — Advanced):** Add widget rendering via a custom REST endpoint or extend Shell.php to inject `dynamic_sidebar()` output into the HTML template.

```php
// Example: Add to Shell.php handle_request()
ob_start();
dynamic_sidebar('phantom-sidebar-main');
$sidebar_html = ob_get_clean();
// Replace <!--sidebar--> placeholder in HTML
$html = str_replace('<!--sidebar-->', $sidebar_html, $html);
```

**CSS classes for widget styling:**
```css
.sidebar .widget { margin-bottom: var(--widget-spacing, 2rem); }
.sidebar .widget-title { font-size: var(--h4-size, 1.5rem); }
.widget-categories ul li a { color: var(--text--color); }
.sidebar .post-thumbnail-entry { border-bottom: 1px solid var(--border--color); }
```

**Settings controlling widget appearance:**
- `--sidebar-width` — Width of sidebar column (default: 320px)
- `--widget-spacing` — Space between widgets (default: 2rem)
- `blog_show_sidebar` — Toggle blog sidebar on/off
- `blog_sidebar_position` — Sidebar left or right
- `shop_enable_sidebar` — Toggle shop sidebar on/off

### 2.7 Products (WooCommerce via data-phantom-products)

```html
<!-- Featured products (homepage) -->
<div data-phantom-products="featured" data-count="4" class="product-grid">
  <!-- JS renders: <div class="product-card">...</div> × N -->
</div>

<!-- Shop page (all products with pagination) -->
<div data-phantom-products="12" data-page="1" class="product-grid" id="productGrid">
</div>

<!-- Single product detail -->
<div data-phantom-product class="product-detail-wrapper">
  <!-- JS renders full product: images, title, price, description, add-to-cart -->
</div>
```

**Product card HTML — generated via Data Adapter + Component Renderer (v2.0):**
```html
<div class="product-card" data-tilt data-reveal-item>
  <div class="product-image" data-image-zoom>
    <span class="product-badge">Sale!</span>
    <a href="/product/slug"><img loading="lazy" src="..." alt="Product Name"></a>
    <div class="product-actions">
      <button class="product-action-btn phantom-wishlist-trigger" aria-label="Wishlist"><i class="far fa-heart"></i></button>
      <button class="product-action-btn phantom-quickview-trigger" aria-label="Quick view"><i class="far fa-eye"></i></button>
    </div>
  </div>
  <div class="product-info">
    <div class="product-categories"><span class="product-category-badge">Category</span></div>
    <div class="product-rating"><i class="fas fa-star"></i><span>(5)</span></div>
    <div class="product-price-row">
      <span class="product-price"><span class="price-sale">$14.99</span><span class="price-original">$19.99</span></span>
      <a href="#" class="btn btn-sm btn-primary phantom-add-to-cart" data-product_id="123">Add to Cart</a>
    </div>
  </div>
</div>
```

**Template string source (in `phantom-data.js`):** `PRODUCT_CARD_TPL` — exact copy of the above structure with `{{PLACEHOLDER}}` tokens.

**Data adapter:** `adaptProductCard(raw, settings, showSaleBadge, saleBadgeText)` normalizes all fields. `buildProductCard()` is now 4 lines: `adaptProductCard()` + `renderTemplate()` + `innerHTML`. If the card HTML changes, update `PRODUCT_CARD_TPL`, not the adapter or builder.

**Data attributes on product cards:**
- `data-product-id` — Product ID (for add-to-cart)
- `data-product-slug` — Product slug (for URL)
- `data-product-type` — `simple` or `variable`
- `data-variation-id` — Variation ID (for variable products)

### 2.8 Categories (data-phantom-categories)

```html
<!-- Category list in sidebar or homepage -->
<div data-phantom-categories class="category-list">
  <!-- JS renders: <a href="/product-category/..." class="category-item">
       <span class="category-name">Category Name</span>
       <span class="category-count">(12)</span></a> -->
</div>

<!-- Category 1 (product_cat taxonomy) uses #category1 ID -->
<div id="category1" class="category-grid">
  <!-- JS renders category cards via adaptCategoryCard() + renderTemplate(CATEGORY_CARD_TPL):
       <a href="{{URL}}" class="category-card {{CLASSES}}" data-tilt data-reveal-item>
         <div class="category-card-bg"><img src="{{IMAGE}}"><div class="category-card-overlay"></div></div>
         <div class="category-card-content">
           <span class="category-count">{{COUNT}}</span>
           <h3 class="category-name">{{NAME}}</h3>
           <span class="category-cta">{{CTA}} <i class="fas fa-arrow-right"></i></span>
         </div>
       </a> -->
</div>
```

### 2.9 WooCommerce Cart & Checkout

**Cart display:**
```html
<!-- Cart count badge (in header/nav) -->
<span class="cart-count" data-phantom="cart_count">0</span>

<!-- Cart total -->
<span class="cart-total" data-phantom="cart_total">$0.00</span>

<!-- Cart dropdown / slide-in panel -->
<div class="shopping-cart-info">
  <!-- JS injects cart items from REST -->
</div>
```

**Add to cart button:**
```html
<button class="add-to-cart-trigger" data-product-id="123" data-product-type="simple">
  Add to Cart
</button>

<!-- Quantity stepper -->
<div class="quantity">
  <button class="decrease-button">−</button>
  <input type="number" class="qty-input" value="1" min="1">
  <button class="increase-button">+</button>
</div>

<!-- Remove from cart -->
<button class="remove-product" data-cart-item-key="abc123">×</button>
```

**Checkout form (`#contactpage`):**
```html
<form id="contactpage" class="checkout-form">
  <div class="row">
    <div class="col-md-6">
      <input type="text" name="billing_first_name" placeholder="First Name" required>
      <input type="text" name="billing_last_name" placeholder="Last Name" required>
      <input type="email" name="billing_email" placeholder="Email" required>
      <input type="tel" name="billing_phone" placeholder="Phone">
    </div>
    <div class="col-md-6">
      <input type="text" name="billing_address_1" placeholder="Address" required>
      <input type="text" name="billing_city" placeholder="City" required>
      <select name="billing_state" class="state-select"><option value="">State</option></select>
      <input type="text" name="billing_postcode" placeholder="Postcode" required>
    </div>
  </div>
  <!-- Shipping methods injected into .checkout-shipping-section -->
  <div class="checkout-shipping-section"></div>
  <!-- Order summary -->
  <div class="order-summary"></div>
  <button type="submit" class="btn btn-primary btn-lg w-100">Place Order</button>
</form>
```

**Cart endpoints (JS handles these):**
| Action | Function | HTTP |
|--------|----------|------|
| Add to cart | `wc-ajax=add_to_cart` | POST (WC native) |
| Update qty | Store API `update-item` | POST |
| Remove item | `wc-ajax=remove_from_cart` | POST |
| Apply coupon | `POST /phantom/v1/cart/coupon` | POST |
| Remove coupon | `POST /phantom/v1/cart/remove-coupon` | POST |
| Get shipping | `GET /phantom/v1/cart/shipping-methods` | GET |
| Checkout | `wc-ajax=checkout` | POST (WC native) |

### 2.10 REST API (49 Routes Under `phantom/v1`, 42 Unique Paths)

| # | Endpoint | Methods | Permission | Purpose |
|---|----------|---------|------------|---------|
| 1 | `/settings` | GET, POST | edit_theme_options | List/update all settings |
| 2 | `/settings/{key}` | GET, PUT, DELETE | edit_theme_options | Single setting CRUD |
| 3 | `/schema` | GET | edit_theme_options | Setting schemas |
| 4 | `/options` | GET | edit_theme_options | Design options (typography, colors, etc.) |
| 5 | `/options/persistent` | GET | public | Branding, logo, site info |
| 6 | `/export` | GET | manage_options | Export all settings JSON |
| 7 | `/import` | POST | manage_options | Import settings JSON |
| 8 | `/cache/flush` | POST | manage_options | Flush Phantom caches |
| 9 | `/partial` | GET | edit_theme_options | Selective refresh |
| 10 | `/posts` | GET | public | Blog posts w/ pagination |
| 11 | `/posts/{slug}` | GET | public | Single post |
| 12 | `/pages` | GET | public | List all published pages |
| 13 | `/pages/{slug}` | GET | public | Single page |
| 14 | `/categories` | GET | public | Post + product categories |
| 15 | `/menus/{location}` | GET | public | Menu tree by location |
| 16 | `/menu-locations` | GET | public | List all registered nav menu locations |
| 17 | `/post-types` | GET | public | List all public post types |
| 18 | `/product-tags` | GET | public | Product tags |
| 19 | `/products` | GET, POST | public/admin | Products list / create |
| 20 | `/products/featured` | GET | public | Featured products |
| 21 | `/products/{id}` | GET, PUT, DELETE | public/admin | Single product CRUD |
| 22 | `/cart` | GET | public | Cart contents |
| 23 | `/cart/add` | POST | nonce | Add to cart |
| 24 | `/cart/update` | POST | nonce | Update cart qty |
| 25 | `/cart/remove` | POST | nonce | Remove cart item |
| 26 | `/cart/coupon` | POST | nonce | Apply coupon |
| 27 | `/cart/remove-coupon` | POST | nonce | Remove coupon |
| 28 | `/cart/coupons` | GET | public | List applied coupons |
| 29 | `/cart/shipping-methods` | GET | public | Calculate shipping |
| 30 | `/woo/attributes` | GET | public | Product attributes |
| 31 | `/woo/variations` | GET | public | Product variations |
| 32 | `/woo/reviews` | GET, POST | public/logged-in | Product reviews |
| 33 | `/page-data` | GET | public | **Mega-endpoint** — all data in 1 call |
| 34 | `/auth/login` | POST | public | User login |
| 35 | `/auth/register` | POST | public | User registration |
| 36 | `/auth/password-reset` | POST | public | Password reset |
| 37 | `/auth/logout` | POST | nonce | User logout |
| 38 | `/contact` | POST | public | Contact form → email |
| 39 | `/user/orders` | GET | nonce + logged-in | Order history |
| 40 | `/user/profile` | GET, POST | nonce + logged-in | User profile read/update |
| 41 | `/widgets` | GET | public | List all widget areas |
| 42 | `/widgets/{sidebar_id}` | GET, POST | public/edit_theme_options | Widget area contents |

### 2.11 New Architecture (Phase B–E: 100/100 Alignment)

Since v1.5.3, Phantom Core has expanded beyond its original settings/customizer/REST scope into a full decoupled framework:

| Layer | Components | Purpose |
|-------|-----------|---------|
| **Data Layer** | 9 Adapters (Post, Page, User, Footer, Settings, Product, Category, Menu, Widget), 6 ViewModels, `Data_Normalizer`, `Data_Provider` | Normalizes all WordPress/WooCommerce data into consistent schemas |
| **Layout Registry** | `Layout`, `Layout_Registry`, `Layout_Manager` with 7 default layouts | Controls page structure and template selection |
| **Design API** | `Design_API` facade over `DesignSystemManager` with 10 filterable methods | Unified design token access |
| **Hook Registry** | `Hook_Registry` tracks, registers, and dispatches hooks with introspection | Decoupled event-driven architecture |
| **Plugin Bridges** | `BridgeInterface`, `Plugin_Bridge` abstract, `WooCommerce_Bridge`, `Bridge_Manager` | Standardized third-party plugin integration |
| **Asset Registry** | `Asset_Registry` with 25+ pre-registered assets | Centralized asset enqueue management |
| **Capability Manager** | 8 custom `phantom_` capabilities | Fine-grained permissions |
| **Public API Facades** | Render, Component, Animation, Settings, Template, Developer facades | 6 entry points for external code |
| **Container Config** | 38 registered services | DI container for all framework services |
| **All 9 Registries** | Settings, Customizer, Layout, Hook, Asset, Template, Component, Animation, Feature | Complete registry system |

**Files:** `includes/Data/`, `includes/Layout/`, `includes/Design/`, `includes/Hook/`, `includes/Bridge/`, `includes/Registry/`, `includes/Public/`, `includes/Capabilities/`, `includes/Engine/Container_Config.php`

**Impact on frontend:** The frontend remains unchanged — all new architecture is backend-only. The REST API, `data-phantom` attributes, CSS vars, and `PhantomServices.*` JS modules continue to work identically. The new layers ensure data consistency, extensibility, and maintainability without altering the frontend contract.

**How templates call the API:**
```javascript
// Via phantom-data.js (automatic on every page):
fetch(window.phantomData.rest_url + 'phantom/v1/page-data')
  .then(r => r.json())
  .then(data => { /* injects settings, menus, products, posts, cart */ });

// Custom calls:
fetch(window.phantomData.rest_url + 'phantom/v1/products/featured')
  .then(r => r.json())
  .then(products => { /* render custom product display */ });
```

---

## Section 3: Premium Template Building

### 3.1 Tech Stack

| Library | CDN | Purpose |
|---------|-----|---------|
| **Bootstrap 5** | `cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css` | Layout grid, responsive |
| **jQuery** | `code.jquery.com/jquery-3.7.1.min.js` | DOM manipulation (required by phantom-data.js) |
| **GSAP** | `cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js` | High-performance animations |
| **GSAP ScrollTrigger** | Same CDN `/ScrollTrigger.min.js` | Scroll-based animations |
| **Three.js** | `cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js` | 3D scenes, WebGL |
| **Lenis** | `unpkg.com/lenis@1.1.18/dist/lenis.min.js` | Smooth scrolling |
| **Swiper** | `cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css` + `.js` | Touch sliders |
| **Font Awesome** | `cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css` | Icons |

### 3.2 Design System (CSS Custom Properties)

Always use the Phantom Core CSS vars (see Section 2.3). For premium-only tokens, extend with:

```css
:root {
  /* Premium Extensions (NOT in Phantom Core — define these in your theme.css) */
  --premium-font-display: 'Playfair Display', serif;
  --color-gradient-1: linear-gradient(135deg, var(--primary--color), var(--secondary--color));
  --shadow-glow: 0 0 30px rgba(99, 102, 241, 0.3);
  --transition-premium: 500ms cubic-bezier(0.22, 1, 0.36, 1);
  --glass-bg: rgba(255, 255, 255, 0.05);
  --glass-border: rgba(255, 255, 255, 0.1);
  --glass-blur: 20px;
}
```

### 3.3 Animation System (GSAP + Lenis)

```javascript
// Lenis smooth scroll
const lenis = new Lenis({
  duration: 1.2,
  easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)),
  smoothWheel: true,
});
lenis.on('scroll', ScrollTrigger.update);
gsap.ticker.add((time) => lenis.raf(time * 1000));

// Fade-in-up reveal (use on any element)
gsap.utils.toArray('.reveal-up').forEach(el => {
  gsap.from(el, {
    scrollTrigger: { trigger: el, start: 'top 85%' },
    y: 60, opacity: 0, duration: 1, ease: 'power3.out',
  });
});

// Stagger grid reveal
gsap.from('.product-card', {
  scrollTrigger: { trigger: '.product-grid', start: 'top 80%' },
  y: 80, opacity: 0, duration: 0.8, stagger: 0.1, ease: 'power3.out',
});

// Counter animation
function animateCounter(el, target) {
  gsap.fromTo(el, { textContent: 0 }, {
    textContent: target, duration: 2, ease: 'power2.out',
    snap: { textContent: 1 },
    scrollTrigger: { trigger: el, start: 'top 85%' },
  });
}
```

### 3.4 3D Integration (Three.js)

```javascript
class ThreeScene {
  constructor(containerId) {
    this.container = document.getElementById(containerId);
    if (!this.container) return;
    this.scene = new THREE.Scene();
    this.camera = new THREE.PerspectiveCamera(75,
      this.container.clientWidth / this.container.clientHeight, 0.1, 1000);
    this.renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true });
    this.renderer.setSize(this.container.clientWidth, this.container.clientHeight);
    this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    this.container.appendChild(this.renderer.domElement);
    this.setupLights();
    this.createObjects();
    this.animate();
    window.addEventListener('resize', () => this.onResize());
  }

  setupLights() {
    const ambient = new THREE.AmbientLight(0x404040);
    const directional = new THREE.DirectionalLight(0xffffff, 1);
    directional.position.set(5, 5, 5);
    this.scene.add(ambient, directional);
  }

  createObjects() {
    const geometry = new THREE.IcosahedronGeometry(1, 0);
    const material = new THREE.MeshPhysicalMaterial({
      color: getComputedStyle(document.documentElement)
        .getPropertyValue('--primary--color').trim() || 0x6366f1,
      metalness: 0.2, roughness: 0.1,
      transparent: true, opacity: 0.9,
    });
    this.mesh = new THREE.Mesh(geometry, material);
    this.scene.add(this.mesh);
  }

  animate() {
    requestAnimationFrame(() => this.animate());
    if (this.mesh) {
      this.mesh.rotation.x += 0.005;
      this.mesh.rotation.y += 0.01;
    }
    this.renderer.render(this.scene, this.camera);
  }

  onResize() {
    if (!this.container) return;
    this.camera.aspect = this.container.clientWidth / this.container.clientHeight;
    this.camera.updateProjectionMatrix();
    this.renderer.setSize(this.container.clientWidth, this.container.clientHeight);
  }
}

// Init 3D scene — read primary color from Phantom CSS var
const hero3d = new ThreeScene('hero-3d');
```

### 3.5 Component Library

| Component | Class Prefix | Data Attributes | Phantom Integration |
|-----------|-------------|-----------------|---------------------|
| Navigation | `.navbar-custom` | `data-phantom-menu="primary"` | Menu tree from WP |
| Hero | `.hero-section` | `data-phantom="hero_title"` | Settings from Customizer |
| Product Grid | `.product-grid` | `data-phantom-products="count"` | WooCommerce products |
| Blog Grid | `.blog-grid` | `data-phantom-posts="count"` | WP posts |
| Cart Icon | `.cart-icon` | `data-phantom="cart_count"` | Cart data |
| Footer | `.footer-custom` | `data-phantom-menu="footer"` | Footer menu + settings |
| Category List | `.category-list` | `data-phantom-categories` | Categories from REST |
| Testimonial | `.testimonial-card` | (Swiper) | Static HTML (settings) |
| Counter | `.counter-item` | (GSAP) | Static HTML |
| Search Bar | `.search-form` | (JS handler) | Products + posts search |
| Auth Forms | `.auth-form` | `#phantom-login-form` etc. | REST auth endpoints |

---

## Section 4: CLI Agent Workflow

### Step-by-Step Process for AI Agents

```
USER PROMPT: "Create a premium [CATEGORY] template for [USE CASE]"
     │
     ▼
  1. UNDERSTAND ────────────────────────────────────────────────
     │  - Extract category: e-commerce, SaaS, portfolio, etc.
     │  - Check which Phantom Core integration points needed:
     │    □ Settings binding (data-phantom attributes)
     │    □ Menu injection (data-phantom-menu)
     │    □ Product grids (data-phantom-products)
     │    □ Blog posts (data-phantom-posts)
     │    □ Cart/checkout (WooCommerce)
     │    □ Auth forms (login/register/reset)
     │    □ Search (products + posts)
     │    □ Categories (shop taxonomy)
     │  - Determine animation complexity level
     │  - Check if 3D elements are needed
     │
     ▼
  2. PLAN ──────────────────────────────────────────────────────
     │  - Create project file structure inside `frontend/`
     │  - List sections: Hero, About, Services, Products, etc.
     │  - Map each section to Phantom Core data source
     │  - Plan animation timeline
     │
     ▼
  3. DESIGN SYSTEM ────────────────────────────────────────────
     │  - Reference Phantom Core CSS vars for all design tokens
     │  - Choose color palette (overrides primary/secondary/accent via Customizer)
     │  - Select font pairing (overrides via Typography settings)
     │  - Define premium-only tokens (gradients, glass effects)
     │
     ▼
  4. BUILD ─────────────────────────────────────────────────────
     │  Step 4a: HTML Structure
     │  │  - Create index.html with ALL required elements:
     │  │    □ <main id="swup"> for SPA transitions
     │  │    □ data-phantom attributes for dynamic content
     │  │    □ data-phantom-menu for navigation
     │  │    □ data-phantom-products for product grids
     │  │    □ data-phantom-posts for blog posts
     │  │    □ Cart classes: .cart-count, .shopping-cart-info
     │  │    □ Checkout form: #contactpage
     │  │
     │  Step 4b: CSS Theme
     │  │  - Reference var(--primary--color) etc. for all customizable properties
     │  │  - Style each section with premium animations
     │  │  - Add responsive breakpoints using --breakpoint-* vars
     │  │  - Dark mode via [data-theme="dark"] selector
     │  │
     │  Step 4c: JavaScript
     │  │  - main.js: Plugin init, nav toggles, Swiper
     │  │  - animations.js: GSAP + ScrollTrigger
     │  │  - three-scenes.js: 3D (if needed)
     │  │  - lenis-scroll.js: Smooth scroll
     │  │  - phantom-data.js: Already included by Shell.php
     │  │
     │  Step 4d: Images & Assets
     │  │  - Placeholder images in assets/images/
     │  │  - SVG favicon
     │  │  - Font imports
     │
     ▼
  5. VERIFY ────────────────────────────────────────────────────
     │  □ All sections render without errors
     │  □ phantomData JS object loads (check console)
     │  □ CSS vars inject correctly (check <style id="phantom-customizer-css">)
     │  □ data-phantom elements populate with settings values
     │  □ Menu populates from WordPress
     │  □ Products render from WooCommerce
     │  □ Cart count updates
     │  □ Checkout form validation works
     │  □ Responsive at 375/768/992/1440px
     │  □ Console errors: 0
     │  □ Accessibility: skip-link, aria-labels, focus-visible
     │  □ prefers-reduced-motion respected
     │  □ Dark mode toggle works
     │
     ▼
  6. DEPLOY ────────────────────────────────────────────────────
     │  - Copy template files to phantom-core/frontend/
     │  - Add route in Shell.php if new page
     │  - Push to GitHub
     │  - Live test via Docker (http://localhost:8080)
```

### Decision Matrix — What to Include

| Feature | Standard Template | Premium Template |
|---------|------------------|------------------|
| Phantom Core data binding | ✅ data-phantom attributes | ✅ All attributes |
| CSS var theming | ✅ Reference core vars | ✅ Extended premium vars |
| Bootstrap grid | ✅ | ✅ |
| GSAP animations | Optional | ✅ |
| Scroll-linked video sequence | - | ✅ (AI → EZGIF → GSAP) |
| Lenis / Locomotive scroll | Optional | ✅ Both available |
| Swiper sliders | Optional | ✅ |
| Three.js 3D | - | ✅ + ScrollTrigger |
| Lottie animations | - | ✅ |
| Splitting.js text effects | - | ✅ |
| Typed.js typing animation | - | ✅ |
| Shaders / WebGL | - | ✅ |
| Parallax | Optional | ✅ |
| Dark mode | Optional | ✅ |
| Page transitions (Swup) | ✅ | ✅ |
| 3D models (.glb) | - | Optional |
| Particle effects | - | Optional |
| Customizer integration | ✅ | ✅ (inline editor) |
| SCSS preprocessing | Optional | ✅ (via Vite) |
| Vite dev server | - | ✅ |

### Verification Checklist (Docker Live Test)

Run these checks on `http://localhost:8080` after deploying:

```
Phase 1: WordPress Core
  □ Homepage loads at /
  □ SEO meta tags: <title>, description, OG tags present
  □ CSS vars injected: <style id="phantom-customizer-css"> exists
  □ phantomData JS object: window.phantomData defined
  □ No console errors
  □ Preloader shows then hides

Phase 2: WooCommerce
  □ Shop page renders product grid
  □ Product detail page loads (click a product)
  □ Add to cart works (check .cart-count updates)
  □ Cart page shows items
  □ Checkout form loads (#contactpage)
  □ Quantity stepper works
  □ Remove from cart works

Phase 3: Settings
  □ data-phantom elements populate with text
  □ data-phantom-bg elements show background images
  □ Logo image loads
  □ Footer text shows site copyright

Phase 4: Customizer / CSS Vars
  □ Customizer changes reflect on frontend (change primary color)
  □ Typography changes reflect
  □ Layout changes (container width) reflect

Phase 5: Menus
  □ Navigation menu shows correct items
  □ Dropdown menus work (if sub-items exist)
  □ Footer menu shows

Phase 6: Categories
  □ Category list renders on shop page
  □ Category links point to correct URLs

Phase 7: Premium Features
  □ GSAP animations play on scroll
  □ Lenis / Locomotive smooth scroll works
  □ Swiper carousel works
  □ Three.js 3D scene renders (if used)
  □ Dark mode toggle works
  □ Mobile responsive
  □ Touch interactions work on mobile
  □ Splitting.js text effects render correctly
  □ Typed.js typing animation plays
  □ Lottie animation plays (if used)

Phase 8: AI 3D Scroll Sequence (if used)
  □ prompt.md created in project root
  □ Scroll-sequence canvas renders
  □ Frames preload with loading indicator
  □ GSAP ScrollTrigger pins the canvas
  □ Scrub scroll advances frames smoothly
  □ First frame shows immediately (before all frames load)
  □ Responsive: canvas resizes with window
  □ Phantom Core hero content overlays correctly
  □ Fallback text visible while loading
  □ Lighthouse Performance ≥ 95 (preloaded frames)
```

---

## Section 5: Skills, MCPs & Ultimate Frontend Stack

### 🚀 Ultimate Frontend Stack (TemplateMonster/ThemeForest Grade)

This is the full toolkit for building award-quality HTML templates. Install the dev tools globally:

```bash
npm install -g vite                              # Build tool & dev server
npm install -g sass                              # SCSS compilation
npm install -g @playwright/test                  # Browser testing
npm install -g lighthouse                        # Performance audit
npm install -g live-server                       # Dev server fallback
npm install -g terser                            # JS minification
npm install -g clean-css-cli                     # CSS minification
```

### Must-Use Skills (in priority order)

| Skill | Phase | Purpose |
|-------|-------|---------|
| `wordpress-pro` | Integration | WordPress/WooCommerce hooks, REST API, settings |
| `brainstorming` | Before any work | Requirements, design exploration |
| `frontend-design` | Design | Aesthetic direction, typography, visual choices |
| `design-systems` | Design | Bold aesthetic, avoiding generic "AI slop" |
| `ui-ux-pro-max` | Design | Design systems, color palettes, font pairings, UX rules |
| `impeccable` | Polish | UX review, visual hierarchy, accessibility, anti-pattern audit |
| `mobile-responsiveness` | Build | Mobile-first, breakpoints, touch interactions |
| `fixing-accessibility` | Verify | ARIA, keyboard nav, focus management, contrast |
| `web-design-guidelines` | Review | UI code review against Web Interface Guidelines |
| `writing-guidelines` | Review | Docs/prose review against Writing Guidelines |
| `design-motion-principles` | Animation | Purposeful motion, micro-interactions (Emil Kowalski / Jhey Tompkins) |
| `review-animations` | Animation Review | Animation code review against craft bar |
| `gsap-skill` | Animation | GSAP-specific best practices |
| `color-expert` | Design | Color theory, palette generation, accessibility |
| `gradient` | Design | Gradient generation, color stops, blending |
| `brand-guidelines` | Design | Brand colors, typography, visual formatting |
| `theme-factory` | Styling | Apply pre-set themes with colors/fonts |
| `ui-styling` | Build | Beautiful, accessible UI with Tailwind/shadcn/ui |
| `design-taste-frontend` | Build | Anti-slop frontend, real design systems, audit-first |
| `taste-design` | Design | Semantic design system, premium standards |
| `seo-audit` | Verify | SEO compliance, meta tags, structured data |
| `deploy-to-vercel` | Deploy | Deploy to Vercel |
| `playwright-cli` | Test | Browser testing, screenshot verification |
| `open-design-mcp` | Design | AI-powered UI design generation via Open Design daemon |

### MCP Servers

| MCP Server | Purpose |
|-----------|---------|
| `@anthropic/mcp-playwright` | Browser testing, screenshot verification |
| `@anthropic/mcp-git` | Git operations, commit, push |
| `@modelcontextprotocol/server-web-fetch` | Fetch URLs, library docs |
| `context7` | Library documentation (React, GSAP, Three.js) |
| `serena` | Code understanding, symbol search, file navigation, refactoring |
| `open-design-mcp` | AI-powered design generation (prototypes, decks, templates) |
| `@anthropic/mcp-server-filesystem` | File operations, directory management |

---

## Section 6: Key Libraries

```html
<!-- ANIMATIONS -->
GSAP:           https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js
ScrollTrigger:  https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js
Lenis:          https://unpkg.com/lenis@1.1.18/dist/lenis.min.js
Locomotive Scroll: https://cdn.jsdelivr.net/npm/locomotive-scroll@4.1.4/dist/locomotive-scroll.min.js
AOS:            https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js
Typed.js:       https://cdnjs.cloudflare.com/ajax/libs/typed.js/2.1.0/typed.umd.js
Splitting.js:   https://unpkg.com/splitting@1.0.6/dist/splitting.min.js

<!-- 3D -->
Three.js:       https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js
OrbitControls:  https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/controls/OrbitControls.js
GLTFLoader:     https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/loaders/GLTFLoader.js
Lottie:         https://cdnjs.cloudflare.com/ajax/libs/lottie-web/5.12.2/lottie.min.js

<!-- SLIDERS -->
Swiper CSS:     https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css
Swiper JS:      https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js

<!-- ICONS -->
Font Awesome:   https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css
Bootstrap Icons: https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css

<!-- UTILITIES -->
Chart.js:       https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js
Isotope:        https://unpkg.com/isotope-layout@3.0.6/dist/isotope.pkgd.min.js
MagnificPopup:  https://cdnjs.cloudflare.com/ajax/libs/magnific-popup.js/1.1.0/jquery.magnific-popup.min.js
jQuery Validate: https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.21.0/jquery.validate.min.js
Lodash:         https://cdnjs.cloudflare.com/ajax/libs/lodash.js/4.17.21/lodash.min.js

<!-- WEBGL / SHADERS -->
Particles.js:   https://cdnjs.cloudflare.com/ajax/libs/particles.js/2.0.0/particles.min.js
Three.js Shaders: Built-in via Three.js r128 (ShaderMaterial, RawShaderMaterial)
```

### Build Tools (install globally)

```bash
# Dev server & build
npm install -g vite                    # Vite dev server + build (fast, modern)
npm install -g sass                    # SCSS → CSS compilation
npm install -g live-server             # Lightweight dev server fallback

# Minification
npm install -g terser                  # JS minification
npm install -g clean-css-cli           # CSS minification

# Testing & audit
npm install -g lighthouse              # Performance/accessibility/SEO audit
npm install -g @playwright/test        # Browser testing framework
```

---

## Section 7: CLI Prompt Template

When the user requests a template, ask or extract:

```
TEMPLATE REQUEST FORM
─────────────────────
Category:        [e-commerce | saas | portfolio | agency | ...]
Pages:           [home | shop | product | cart | checkout | blog | about | contact | ...]
3D Required:     [yes | no | optional]
Dark Mode:       [yes | no]
Brand Colors:    [hex codes or leave blank for Phantom defaults]
Fonts:           [preferences or leave blank for Phantom typography settings]

PHANTOM CORE INTEGRATION REQUIRED:
  □ Settings binding (data-phantom attributes)
  □ Menu injection (data-phantom-menu)
  □ Product grids (data-phantom-products)
  □ Blog posts (data-phantom-posts)
  □ Cart + Checkout (WooCommerce)
  □ Auth forms (login/register/reset)
  □ Search
  □ Categories

3D APPROACH:
  □ Scroll-linked image sequence (AI video → EZGIF frames → GSAP canvas)
  □ Three.js real-time 3D (Three.js + ScrollTrigger)
  □ Lottie animation (Lottie-web)
  □ Shaders/WebGL (Three.js ShaderMaterial)
  □ 360 product viewer (Three.js OrbitControls)
```

Then follow the workflow in Section 4 to plan, design, build, verify, and deploy.

If 3D is selected, the CLI will:
1. Create `prompt.md` with start/end frame prompts (see Section 9.2)
2. Generate Three.js hero template (see Section 9.6)
3. Add scroll-sequence.js with GSAP/ScrollTrigger
4. Wire Phantom Core data-phantom attributes on top

---

---

## Section 8: Lighthouse 100/100 — Performance & QA

Achieving Lighthouse 100/100 across all 6 categories (Performance, Accessibility, Best Practices, SEO, PWA, AU) requires specific patterns. This section covers the exact checklist tailored for Phantom Core templates.

### 8.1 Performance (100)

```html
<!-- ✅ CRITICAL: Preload above-the-fold assets -->
<link rel="preload" href="assets/css/critical.css" as="style">
<link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" as="style">
<link rel="preload" href="assets/images/hero.webp" as="image" fetchpriority="high">

<!-- ✅ Defer non-critical CSS -->
<link rel="preload" href="assets/css/bootstrap.min.css" as="style" onload="this.rel='stylesheet'">
<noscript><link rel="stylesheet" href="assets/css/bootstrap.min.css"></noscript>

<!-- ✅ Defer all non-critical JS -->
<script src="assets/js/vendor/gsap.min.js" defer></script>
<script src="assets/js/animations.js" defer></script>
```

| Test | Target | Phantom Core Specific |
|------|--------|-----------------------|
| First Contentful Paint (FCP) | < 1.8s | CSS vars injection adds ~50ms — inline critical CSS above-the-fold |
| Largest Contentful Paint (LCP) | < 2.5s | Preload hero image with `fetchpriority="high"`, use WebP |
| Total Blocking Time (TBT) | < 50ms | Defer GSAP/Three.js — they block the main thread |
| Cumulative Layout Shift (CLS) | < 0.1 | Set explicit width/height on ALL images, including `data-phantom` ones |
| Speed Index | < 3.0s | Inline `phantom-customizer-css` is already optimal (server-injected) |
| JavaScript execution time | < 2s | `phantom-data.js` runs on DOMContentLoaded — keeps it lean |

**CLS prevention for dynamic content:**
```css
/* Images injected via data-phantom need aspect-ratio containers */
.product-card .product-image {
  aspect-ratio: 1 / 1;
  width: 100%;
  height: auto;
  background: var(--light--bg--color, #f8fafc); /* placeholder while image loads */
}

.hero-image-wrapper {
  aspect-ratio: 16 / 9;
  width: 100%;
  max-height: 600px;
  object-fit: cover;
}
```

**Image optimization checklist:**
- [ ] All images served as WebP with JPEG fallback
- [ ] `loading="lazy"` on below-the-fold images
- [ ] `fetchpriority="high"` on LCP image (hero)
- [ ] Explicit `width` and `height` attributes on all `<img>` tags
- [ ] `aspect-ratio` CSS for dynamic containers
- [ ] Placeholder background color matching dominant image color

### 8.2 Accessibility (100)

| Requirement | Implementation |
|-------------|---------------|
| Skip link | `<a href="#main-content" class="skip-link">Skip to main content</a>` (Shell.php injects this) |
| ARIA labels | All icon-only buttons: `<button aria-label="Search"><i class="fas fa-search"></i></button>` |
| Form labels | Every `<input>` has a `<label>` or `aria-label` |
| Focus visible | `:focus-visible` outline on all interactive elements |
| Reduced motion | `@media (prefers-reduced-motion: reduce)` disables all GSAP/animations |
| Color contrast | All text/background combos must pass 4.5:1 ratio against CSS var values |
| Heading hierarchy | Single `<h1>` per page, sequential `<h2>`→`<h3>`→`<h4>` |
| Landmarks | `<header>`, `<main id="swup">`, `<footer>`, `<nav>` elements |
| Alt text | ALL `<img>` tags have `alt` — including `data-phantom` ones with `data-phantom-alt` |
| Focus trap | Modals and cart drawers trap focus while open |

```html
<!-- ✅ Accessible product card (matches PRODUCT_CARD_TPL in phantom-data.js) -->
<article class="product-card" data-tilt data-reveal-item aria-label="Product: Blue T-Shirt, $29.99">
  <div class="product-image" data-image-zoom>
    <img data-phantom="product_image_123"
         data-phantom-alt="product_name_123"
         alt="Blue T-Shirt"
         width="300" height="300"
         loading="lazy">
    <button class="add-to-cart-trigger"
            data-product-id="123"
            aria-label="Add Blue T-Shirt to cart">
      Add to Cart
    </button>
  </div>
  <div class="product-info">
    <div class="product-rating" aria-label="4 out of 5 stars">★★★★☆</div>
    <div class="product-price-row">
      <span class="product-price" aria-label="Price: $29.99">
        <span data-phantom="product_price_123">$29.99</span>
      </span>
    </div>
  </div>
</article>
```

```css
/* Reduced motion — disables all GSAP/Three.js */
@media (prefers-reduced-motion: reduce) {
  *, *::before, *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
    scroll-behavior: auto !important;
  }
  .reveal-up { opacity: 1 !important; transform: none !important; }
  .parallax-bg { transform: none !important; }
  [data-lenis-prevent] { overflow: visible !important; }
  .threejs-container { display: none !important; }
}
```

### 8.3 Best Practices (100)

- [ ] HTTPS enforced (via Docker proxy or deployment platform)
- [ ] No `console.log` in production JS
- [ ] All external resources loaded over HTTPS
- [ ] Correct image aspect ratios (no layout shift)
- [ ] No deprecated APIs (GSAP 3.x, Three.js r128 are current)
- [ ] `doctype` present at top of HTML
- [ ] Error boundaries on all REST calls (phantom-data.js includes `.catch()`)
- [ ] CSP headers prevent XSS (Shell.php injects them)
- [ ] Nonce verification on all POST operations
- [ ] jQuery version 3.7.1+ (no known CVEs)

### 8.4 SEO (100)

Shell.php handles these automatically:
- [x] `<title>` tag per page (from `Shell::get_meta_tags()`)
- [x] `<meta name="description">`
- [x] Open Graph tags (`og:title`, `og:description`, `og:image`, `og:url`)
- [x] Twitter Card tags
- [x] JSON-LD structured data (Product, Organization, BlogPosting)
- [x] Semantic HTML5 elements (`<header>`, `<main>`, `<footer>`, `<article>`, `<nav>`)
- [x] Responsive meta tag (`<meta name="viewport">`)
- [x] Canonical URLs
- [x] `hreflang` tags for multilingual

Template author must ensure:
- [ ] Single `<h1>` per page
- [ ] Descriptive link text (not "click here")
- [ ] Image `alt` attributes on ALL images
- [ ] Mobile-friendly viewport meta
- [ ] Sitemap submitted (WordPress SEO plugin handles this)

### 8.5 Full Lighthouse Audit Checklist

Run on `http://localhost:8080` after Docker deploy:

```
PERFORMANCE
  □ 0 console errors (open DevTools Console)
  □ No render-blocking resources above fold
  □ Properly sized images (WebP, responsive srcset)
  □ Deferred non-critical JS (GSAP, Three.js, Swiper)
  □ Preloaded critical fonts
  □ Minified CSS + JS (use .min.js variants)
  □ Lighthouse Performance ≥ 95 (target 100)

ACCESSIBILITY
  □ Skip link visible on focus
  □ All images have alt text
  □ All form inputs have labels
  □ Color contrast ≥ 4.5:1 for all text
  □ ARIA landmarks present
  □ Focus order is logical
  □ prefers-reduced-motion respected
  □ Lighthouse Accessibility = 100

BEST PRACTICES
  □ HTTPS
  □ No mixed content
  □ No deprecated APIs
  □ Correct image aspect ratios
  □ No console errors on any page
  □ Lighthouse Best Practices ≥ 95 (target 100)

SEO
  □ <title> is unique per page
  ✅ meta description present
  □ h1 element present and unique
  □ Link text is descriptive
  □ Mobile friendly
  □ Lighthouse SEO = 100

PWA (if applicable)
  □ manifest.json present
  □ Service worker registered
  □ Offline fallback page
  □ Lighthouse PWA check passes

ADVANCED CHECKS (Phantom Core specific)
  □ phantomData JS loads without errors
  □ CSS vars inject in <head> before body renders
  □ data-phantom attributes populate with REST data
  □ Cart badge updates correctly
  □ Product images have aspect-ratio set
  □ Swup SPA transitions have no flash of unstyled content
  □ Dark mode toggle does not cause layout shift
  □ Three.js scene does not block main thread (> 50ms)
```

### 8.6 Docker Verification Command

```bash
# Run Lighthouse via Playwright from project root
npx playwright open http://localhost:8080
# Or use Lighthouse CLI:
# npm install -g lighthouse
# lighthouse http://localhost:8080 --view --preset=desktop
```

**Page-by-page verification sequence:**
```
1. http://localhost:8080/           → Home (index.html)
2. http://localhost:8080/shop/      → Shop (products grid)
3. http://localhost:8080/product/nike-air-max/ → Product detail
4. http://localhost:8080/cart/      → Cart page
5. http://localhost:8080/checkout/  → Checkout form
6. http://localhost:8080/blog/      → Blog archive
7. http://localhost:8080/about/     → About page
8. http://localhost:8080/contact/   → Contact form
```

Every page must pass:
- ✅ Console errors: 0
- ✅ Network errors (404/500): 0
- ✅ All `data-phantom` elements populated
- ✅ CSS vars applied correctly
- ✅ Phantom Core connection verified

---

## Section 9: AI 3D Video & Scroll Sequence Pipeline

When the user requests 3D elements (hero banner, product showcase, background), use this AI-powered pipeline to generate cinema-quality scroll-linked animations WITHOUT writing 3D code.

### 9.1 Workflow Overview

```
USER: "Make a 3D hero banner for [product]"
      │
      ▼
  Step 1: CLI creates prompt.md with start/end frame prompts
      │  └── Generated from Cindy Zhu's 5k scroll animation guide
      │
      ▼
  Step 2: User generates frames in Google Flow
      │  └── labs.google/flow → Frames to Video
      │
      ▼
  Step 3: User converts to image sequence via EZGIF
      │  └── ezgif.com/video-to-jpg → Download frames as ZIP
      │
      ▼
  Step 4: CLI builds scroll-linked animation
      │  └── GSAP + ScrollTrigger + Canvas = Apple-style scroll sequence
      │
      ▼
  Step 5: CLI integrates into Phantom Core template
      │  └── Hero banner with data-phantom attributes + CSS vars
      │
      ▼
  Step 6: Deploy & verify
```

### 9.2 The prompt.md File (Generated by CLI)

When 3D is requested, the CLI creates `prompt.md` in the project root:

```markdown
# 3D Video Frame Prompts

Generate these in Google Flow (labs.google/flow → Frames to Video)

## Start Frame (assembled)
High-end studio product photograph of [PRODUCT NAME], fully assembled
and intact, floating dead center against a seamless matte light-grey
studio background. Straight-on view, perfectly level camera, soft even
studio lighting, one subtle soft shadow directly beneath the product,
photorealistic, ultra sharp, high detail. Keep the product small enough
in frame that there is generous empty space on every side. No text,
no watermark, no hands, no props, no brand logos. Landscape 16:9.

## End Frame (exploded view)
High-end studio product photograph of the same [PRODUCT NAME] as a clean
exploded view: [LIST 4-6 PARTS] separated and floating in an organized,
evenly spaced arrangement along one axis, like a technical teardown render.
Same straight-on view, same perfectly level camera, same soft even studio
lighting, same seamless matte light-grey studio background, one subtle
soft shadow beneath. The core body stays in the exact center at the same
size, with the parts spreading outward around it. Photorealistic, ultra
sharp, high detail. No text, no watermark, no hands, no props, no brand
logos. Landscape 16:9.

## Motion Prompt (for Frames to Video)
The product slowly comes apart into an exploded view: the casing separates
and the internal parts spread outward smoothly and evenly. Camera locked
in place, background unchanged.

## EZGIF Settings
1. Go to https://ezgif.com/video-to-jpg
2. Upload the generated video
3. Set to 30fps
4. Click "Video to JPG (image sequence)"
5. Click "Download frames as ZIP"
6. Unzip into: assets/frames/

## After placing frames in assets/frames/
Run the build command and the template will wire the scroll animation.
```

### 9.3 Scroll-Linked Image Sequence (Build Prompt)

After frames are placed in `assets/frames/`, use this build prompt:

```javascript
// main.js — Scroll-linked image sequence (Apple AirPods style)
// Place in frontend/assets/js/scroll-sequence.js

(function($) {
  'use strict';

  const frameContainer = document.getElementById('scroll-sequence');
  if (!frameContainer) return;

  const canvas = document.createElement('canvas');
  const ctx = canvas.getContext('2d');
  frameContainer.appendChild(canvas);

  const frameCount = 120; // Detect from assets/frames/ folder
  const images = [];
  let loaded = 0;
  let currentFrame = 0;

  // Preload all frames
  for (let i = 1; i <= frameCount; i++) {
    const img = new Image();
    img.src = `assets/frames/frame-${String(i).padStart(4, '0')}.jpg`;
    img.onload = () => {
      loaded++;
      if (loaded === 1) drawFrame(1); // Show first frame immediately
    };
    images.push(img);
  }

  function drawFrame(index) {
    const img = images[index - 1];
    if (!img || !img.complete) return;
    canvas.width = img.naturalWidth;
    canvas.height = img.naturalHeight;
    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
  }

  // GSAP ScrollTrigger pin + scrub
  ScrollTrigger.create({
    trigger: frameContainer,
    start: 'top top',
    end: `+=${frameCount * 8}px`, // ~4 seconds at 30fps
    pin: true,
    scrub: 1,
    onUpdate: (self) => {
      const frame = Math.round(self.progress * (frameCount - 1)) + 1;
      if (frame !== currentFrame) {
        currentFrame = frame;
        drawFrame(frame);
      }
    },
  });

  // Responsive resize
  window.addEventListener('resize', () => {
    const rect = frameContainer.getBoundingClientRect();
    canvas.style.width = rect.width + 'px';
    canvas.style.height = rect.height + 'px';
  });

  // Fallback loading indicator
  if (loaded < frameCount) {
    const loader = document.createElement('div');
    loader.className = 'frame-loader';
    loader.textContent = 'Loading 3D animation...';
    frameContainer.appendChild(loader);
    const check = setInterval(() => {
      if (loaded === frameCount) {
        loader.remove();
        clearInterval(check);
      }
    }, 500);
  }

})(jQuery);
```

### 9.4 Full Hero Banner Template (with Scroll Sequence + Phantom Core)

```html
<!-- frontend/3d-hero.html — Hero section with scroll sequence + Phantom data -->
<section class="hero-3d-section position-relative overflow-hidden" id="hero">
  <!-- Phantom Core data binding (settings text) -->
  <div class="hero-content position-absolute top-50 start-50 translate-middle text-center z-3">
    <span class="hero-badge badge mb-3" data-phantom="hero_badge">New Collection</span>
    <h1 class="hero-title display-3 fw-bold mb-3" data-phantom="hero_title">
      The Future is Here
    </h1>
    <p class="hero-desc lead mb-4" data-phantom="hero_description">
      Experience innovation like never before.
    </p>
    <a class="btn btn-primary btn-lg" data-phantom="hero_button_url" href="#">
      <span data-phantom="hero_button_text">Explore Now</span>
    </a>
  </div>

  <!-- Scroll-linked 3D sequence container -->
  <div id="scroll-sequence" class="scroll-sequence position-absolute top-0 start-0 w-100 h-100">
    <!-- Canvas injected by scroll-sequence.js -->
  </div>

  <!-- Overlay for text readability -->
  <div class="hero-overlay position-absolute top-0 start-0 w-100 h-100"></div>

  <!-- Scroll indicator -->
  <div class="scroll-indicator position-absolute bottom-0 start-50 translate-middle-x mb-4">
    <span class="scroll-text">Scroll to explore</span>
    <div class="scroll-mouse"><div class="scroll-dot"></div></div>
  </div>
</section>
```

### 9.5 3D Hero CSS (with Phantom Core CSS vars)

```css
/* frontend/assets/css/3d-hero.css */
.hero-3d-section {
  height: 100vh;
  min-height: 600px;
  background: var(--bg, #ffffff);
  color: var(--heading--color, #0f172a);
}

.scroll-sequence {
  z-index: 1;
}

.scroll-sequence canvas {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.hero-content {
  z-index: 3;
  max-width: var(--container-narrow, 768px);
  padding: var(--section-padding-x);
}

.hero-overlay {
  background: linear-gradient(
    to bottom,
    rgba(0,0,0,0) 0%,
    rgba(0,0,0,0.3) 100%
  );
  z-index: 2;
}

.hero-badge {
  background: var(--primary--color, #6366f1);
  color: var(--button-text, #ffffff);
  padding: 0.5rem 1.5rem;
  border-radius: var(--border-radius-full, 9999px);
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.frame-loader {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  color: var(--text--color, #1e293b);
  font-size: 1.125rem;
  z-index: 4;
}
```

### 9.6 Alternative: Pure Three.js 3D with GSAP ScrollTrigger

When the user wants a real-time 3D scene (not a video sequence), use Three.js directly:

```javascript
// three-scenes.js — 3D hero with GSAP scroll-driven animation
class ScrollDriven3D {
  constructor(containerId) {
    this.container = document.getElementById(containerId);
    if (!this.container) return;
    this.scene = new THREE.Scene();
    this.camera = new THREE.PerspectiveCamera(
      75, this.container.clientWidth / this.container.clientHeight, 0.1, 1000
    );
    this.renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true });
    this.renderer.setSize(this.container.clientWidth, this.container.clientHeight);
    this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    this.container.appendChild(this.renderer.domElement);
    this.setupLights();
    this.createObjects();
    this.animate();
    this.bindScroll();
    window.addEventListener('resize', () => this.onResize());
  }

  setupLights() {
    const ambient = new THREE.AmbientLight(0x404040);
    const directional = new THREE.DirectionalLight(0xffffff, 1);
    directional.position.set(5, 5, 5);
    this.scene.add(ambient, directional);
  }

  createObjects() {
    const geo = new THREE.IcosahedronGeometry(1, 1);
    const mat = new THREE.MeshPhysicalMaterial({
      color: getComputedStyle(document.documentElement)
        .getPropertyValue('--primary--color').trim() || '#6366f1',
      metalness: 0.3, roughness: 0.2,
      transparent: true, opacity: 0.9,
    });
    this.mesh = new THREE.Mesh(geo, mat);
    this.scene.add(this.mesh);

    // Wireframe overlay
    const wireMat = new THREE.MeshBasicMaterial({
      color: getComputedStyle(document.documentElement)
        .getPropertyValue('--accent--color').trim() || '#f59e0b',
      wireframe: true, transparent: true, opacity: 0.3,
    });
    this.wireframe = new THREE.Mesh(geo.clone(), wireMat);
    this.wireframe.scale.set(1.02, 1.02, 1.02);
    this.scene.add(this.wireframe);
  }

  bindScroll() {
    // GSAP drives the 3D rotation + scale based on scroll
    gsap.to(this.mesh.rotation, {
      y: Math.PI * 2,
      scrollTrigger: {
        trigger: this.container,
        start: 'top bottom',
        end: 'bottom top',
        scrub: 1.5,
      },
    });
    gsap.to(this.mesh.scale, {
      x: 1.5, y: 1.5, z: 1.5,
      scrollTrigger: {
        trigger: this.container,
        start: 'top bottom',
        end: 'center center',
        scrub: 1,
      },
    });
    if (this.wireframe) {
      gsap.to(this.wireframe.rotation, {
        y: -Math.PI * 3,
        scrollTrigger: {
          trigger: this.container,
          start: 'top bottom',
          end: 'bottom top',
          scrub: 1.5,
        },
      });
    }
  }

  animate() {
    requestAnimationFrame(() => this.animate());
    this.renderer.render(this.scene, this.camera);
  }

  onResize() {
    if (!this.container) return;
    this.camera.aspect = this.container.clientWidth / this.container.clientHeight;
    this.camera.updateProjectionMatrix();
    this.renderer.setSize(this.container.clientWidth, this.container.clientHeight);
  }
}

// Init
const hero3d = new ScrollDriven3D('hero-3d');
```

### 9.7 3D Workflow Decision Matrix

| User Wants | Method | Tools | Output |
|-----------|--------|-------|--------|
| Product hero + scroll | Scroll-linked image sequence | Google Flow → EZGIF → GSAP | Canvas with pre-rendered frames |
| Real-time 3D object | Three.js + ScrollTrigger | Three.js, GSAP | Interactive WebGL scene |
| Background particles | Three.js Points | Three.js BufferGeometry | Particle field |
| Lottie animation | Lottie-web + ScrollTrigger | Lottie, GSAP | Vector animation |
| Parallax product reveal | Locomotive/Lenis + GSAP | Lenis, ScrollTrigger | Multi-layer parallax |
| Shader effects | Three.js ShaderMaterial | Three.js, GLSL | Custom WebGL shaders |
| 360 product viewer | Three.js OrbitControls | Three.js | Drag-to-rotate product |

### 9.8 prompt.md Template (for CLI Agent)

When the user says "3D hero" or "3D website", the CLI creates this file automatically:

```markdown
# 3D Hero — Start Here

## Option A: AI Video Pipeline (recommended for photorealism)
1. Go to https://labs.google/flow
2. Use "Frames to Video: First + last"
3. Upload the start + end frames from the prompts below
4. Add motion prompt → Generate → Download
5. Go to https://ezgif.com/video-to-jpg
6. Upload video → Set 30fps → Convert → Download ZIP
7. Unzip into `frontend/assets/frames/`
8. CLI will build the scroll animation

## Option B: Three.js (for real-time 3D)
CLI will generate a Three.js scene with GSAP scroll-driven animation.
No external assets needed.

## Generated Prompts
Start frame: [INSERT PRODUCT-SPECIFIC PROMPT]
End frame: [INSERT PRODUCT-SPECIFIC PROMPT]
Motion prompt: slow smooth 360 rotation / exploded view / material transition

## EZGIF Settings
fps: 30
format: JPG
output: assets/frames/
```

### 9.9 Integrating with Open Design MCP for UI Design

Use Open Design MCP to generate the hero banner design itself:

```bash
# Open Design generates the full hero section design
od_generate_design(
  prompt: "Design a premium hero banner for a 3D product showcase.
           Full-viewport hero with canvas area for scroll-linked 3D animation,
           headline overlay, CTA button, and scroll indicator.
           Dark theme with accent color #6366f1.",
  kind: "prototype"
)
```

Then merge the generated design with the scroll-sequence JavaScript and Phantom Core `data-phantom` attributes.

---

## Appendix: Quick Reference Cards

### Card A: Phantom-Hostile vs Phantom-Friendly HTML

```html
<!-- ❌ PHANTOM-HOSTILE: Hardcoded, no data binding, no CSS vars -->
<header style="background: #fff;">
  <h1>Welcome to Our Store</h1>
  <nav><a href="/">Home</a><a href="/shop">Shop</a></nav>
</header>

<!-- ✅ PHANTOM-FRIENDLY: Data binding, CSS vars, menu injection -->
<header style="background: var(--header-bg, #fff);">
  <h1 data-phantom="hero_title">Welcome</h1>
  <nav data-phantom-menu="primary"></nav>
</header>
```

### Card B: End-to-End Data Flow

```
Customizer Change (primary_color → #ff0000)
  → Settings_Registry saves to wp_options
  → Shell.php reads option, injects --primary--color: #ff0000 into <style>
  → Browser applies var(--primary--color) to all themed elements
  → PhantomBridge reads CSS var value from computed style
  → Live preview updates in real-time

Template Change (new hero image)
  → Update hero_image setting via Customizer or Settings page
  → REST GET /phantom/v1/options/persistent returns new URL
  → phantom-data.js reads [data-phantom="hero_image"] elements
  → Sets src attribute to new image URL
```

### Card C: Troubleshooting Phantom Core Connection

| Symptom | Likely Cause | Fix |
|---------|-------------|-----|
| Content not loading | Missing `data-phantom` attribute | Add `data-phantom="setting_key"` to element |
| Console: "phantomData is not defined" | Shell.php not injecting | Check `template_redirect` hook, ensure no conflict |
| CSS vars not applied | Missing `<style id="phantom-customizer-css">` | Check `Custom_CSS::render_style()` fires |
| Menu not showing | Wrong menu location name | Use `primary`, `footer`, or registered location |
| Products empty | WC not active or no products | Install WooCommerce, add products |
| Cart not updating | jQuery not loaded | Add jQuery before phantom-data.js |
| SPA full page reload | Missing `<main id="swup">` | Add id="swup" to main content element |
| Checkout fails | Wrong form field names | Use WC field names: `billing_first_name`, etc. |
| 404 on page | Route not in Shell.php | Add route to `Shell::$routes` array |
| Customizer changes not showing | CSS var name mismatch | Sync var names between registry and CSS |
