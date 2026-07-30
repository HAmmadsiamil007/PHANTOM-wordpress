# Phantom Core — Technical Architecture Reference

> **Version:** 2.0.0 | **Last updated:** 2026-07-29

---

## Table of Contents

1. [Plugin Bootstrap](#1-plugin-bootstrap)
2. [Theme Setup](#2-theme-setup)
3. [Autoloader](#3-autoloader)
4. [Container DI](#4-container-di)
5. [Asset Engine](#5-asset-engine)
6. [SPA Router (Shell)](#6-spa-router-shell)
7. [Template System](#7-template-system)
8. [CSS Generation Engine](#8-css-generation-engine)
9. [Feature Flags](#9-feature-flags)
10. [Bridge System](#10-bridge-system)

---

## 1. Plugin Bootstrap

**File:** `phantom-core.php`

### Constants

| Constant | Value | Purpose |
|----------|-------|---------|
| `PHANTOM_CORE_VERSION` | `2.0.0` | Plugin version for cache busting and enqueue |
| `PHANTOM_CORE_FILE` | `__FILE__` | Absolute path to main plugin file |
| `PHANTOM_CORE_PATH` | `plugin_dir_path(__FILE__)` | Trailing-slash directory path |
| `PHANTOM_CORE_URL` | `plugin_dir_url(__FILE__)` | Trailing-slash URL |
| `PHANTOM_THEME_URL` | `get_template_directory_uri()` | Theme assets URL |

### Hook Registration Order

The bootstrap registers hooks at specific priorities to enforce initialization order:

```
plugins_loaded (priority 0)  →  Shell::init()              — SPA router intercepts all frontend
plugins_loaded (priority 5)  →  Plugin::init()             — Core orchestrator: Settings_Registry, Core_Plugin
plugins_loaded (priority 15) →  Customizer::init()         — Customizer panels/sections/controls
plugins_loaded (priority 20) →  DesignSystemManager::init()— Design token system
after_setup_theme             →  WooCommerce theme supports — Product gallery zoom/lightbox/slider
wp_enqueue_scripts (priority 9)  → Google Fonts enqueue    — Web Font Loader
wp_head (priority 100)           → output_inline_css()     — CSS Generation Engine output
template_redirect (priority 5)   → Shell::init_wc_session()— WooCommerce session init
template_redirect (priority 10)  → Shell::handle_request() — SPA router: URL → HTML template
```

### Initialization Flow

```
phantom-core.php loaded by WordPress
  │
  ├── define constants (PHANTOM_CORE_VERSION, PATH, URL)
  ├── require_once autoloader
  ├── load_plugin_textdomain()
  │
  ├── plugins_loaded @ priority 0
  │   └── Shell::init()
  │       └── add_action('template_redirect', [Shell::class, 'handle_request'], 10)
  │
  ├── plugins_loaded @ priority 5
  │   └── Plugin::init()
  │       ├── Settings_Registry::register() — 612 settings loaded
  │       ├── Core_Plugin::init() — menus, widgets, theme supports
  │       └── Container_Config::init() — 53 services registered
  │
  ├── plugins_loaded @ priority 15
  │   └── Customizer::init()
  │       └── add_action('customize_register', ...)
  │
  └── plugins_loaded @ priority 20
      └── DesignSystemManager::init()
          └── Registers design tokens, presets, CSS var bindings
```

**Critical:** `Shell::init()` runs at priority 0 on `plugins_loaded` to ensure it registers `template_redirect` before any other component can modify routing. This guarantees the SPA router intercepts all frontend requests first.

---

## 2. Theme Setup

**File:** `phantom-theme/functions.php`

### Theme Supports

```php
add_theme_support('post-thumbnails');
add_theme_support('title-tag');
add_theme_support('custom-logo', [
    'height'      => 80,
    'width'       => 200,
    'flex-width'  => true,
    'flex-height' => true,
]);
add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption']);
add_theme_support('align-wide');
add_theme_support('responsive-embeds');
add_theme_support('customize-selective-refresh-widgets');
add_theme_support('wp-block-styles');
add_theme_support('woocommerce');
add_theme_support('wc-product-gallery-zoom');
add_theme_support('wc-product-gallery-lightbox');
add_theme_support('wc-product-gallery-slider');
```

### Navigation Menu Locations

6 total (2 theme + 4 plugin):

| Location | Registered By | Purpose |
|----------|---------------|---------|
| `primary` | Theme | Main navigation |
| `footer` | Theme | Footer navigation |
| `phantom_primary` | Plugin | Primary nav (SPA) |
| `phantom_secondary` | Plugin | Secondary navigation |
| `phantom_footer` | Plugin | Footer nav (SPA) |
| `phantom_mobile` | Plugin | Mobile hamburger menu |

### Widget Areas

10 total (3 theme + 7 plugin):

| Sidebar ID | Registered By | Purpose |
|------------|---------------|---------|
| `sidebar-blog` | Theme | Blog sidebar |
| `sidebar-shop` | Theme | Shop sidebar |
| `sidebar-footer` | Theme | Theme footer widgets |
| `phantom-sidebar-main` | Plugin | Main SPA sidebar |
| `phantom-sidebar-shop` | Plugin | Shop page sidebar |
| `phantom-sidebar-blog` | Plugin | Blog page sidebar |
| `phantom-footer-1` | Plugin | Footer column 1 |
| `phantom-footer-2` | Plugin | Footer column 2 |
| `phantom-footer-3` | Plugin | Footer column 3 |
| `phantom-footer-4` | Plugin | Footer column 4 |

### Custom Image Sizes

| Size Name | Dimensions | Crop | Purpose |
|-----------|------------|------|---------|
| `phantom-blog-thumb` | 400×300 | Hard | Blog archive cards |
| `phantom-shop-thumb` | 300×300 | Hard | Shop product cards |
| `phantom-featured-lg` | 1200×600 | Soft | Featured/hero images |

### Enqueued Assets

**CSS (10 files):**
`bootstrap.min.css`, `font-awesome.min.css`, `owl-carousel.min.css`, `owl-theme.min.css`, `animate.min.css`, `venobox.min.css`, ` slick.min.css`, ` meanmenu.min.css`, ` phantom-theme.css`, ` phantom-accessibility.css`

**JS (13 files):**
`jquery.min.js`, `bootstrap.bundle.min.js`, `owl-carousel.min.js`, `owl-animate.js`, `waypoints.min.js`, `counterup.min.js`, `venobox.min.js`, ` slick.min.js`, `jquery.meanmenu.js`, `isotope.pkgd.min.js`, ` imagesloaded.pkgd.min.js`, ` phantom-theme.js`, ` phantom-accessibility.js`

---

## 3. Autoloader

**File:** `phantom-core.php` (spl_autoload_register)

PSR-4-inspired autoloader with kebab-case file conversion. Maps namespace prefixes to directory paths.

### Namespace Prefixes (24)

| Prefix | Directory | Example Class → File |
|--------|-----------|---------------------|
| `PhantomCore\` | `includes/` | `Settings_Registry` → `includes/class-settings-registry.php` |
| `PhantomCore\Customizer\Controls\` | `includes/custom-controls/` | `Toggle_Control` → `includes/custom-controls/class-toggle-control.php` |
| `PhantomCore\Adapters\` | `includes/Adapters/` | `Post_Adapter` → `includes/Adapters/class-post-adapter.php` |
| `PhantomCore\ViewModels\` | `includes/ViewModels/` | `Product_ViewModel` → `includes/ViewModels/class-product-viewmodel.php` |
| `PhantomCore\Data\` | `includes/Data/` | `Data_Normalizer` → `includes/Data/class-data-normalizer.php` |
| `PhantomCore\Design\` | `includes/Design/` | `DesignSystemManager` → `includes/Design/class-design-system-manager.php` |
| `PhantomCore\Components\` | `includes/Components/` | `Component_Renderer` → `includes/Components/class-component-renderer.php` |
| `PhantomCore\Engine\` | `includes/Engine/` | `Template_Loader` → `includes/Engine/class-template-loader.php` |
| `PhantomCore\Engine\Injectors\` | `includes/Engine/Injectors/` | `WooCommerce_Injector` → `includes/Engine/Injectors/class-woocommerce-injector.php` |
| `PhantomCore\Layout\` | `includes/Layout/` | `Layout_Registry` → `includes/Layout/class-layout-registry.php` |
| `PhantomCore\Hook\` | `includes/Hook/` | `Hook_Registry` → `includes/Hook/class-hook-registry.php` |
| `PhantomCore\Bridges\` | `includes/Bridges/` | `WooCommerce_Bridge` → `includes/Bridges/class-woocommerce-bridge.php` |
| `PhantomCore\Registry\` | `includes/Registry/` | `Asset_Registry` → `includes/Registry/class-asset-registry.php` |
| `PhantomCore\Public\` | `includes/Public/` | `Design_API` → `includes/Public/class-design-api.php` |
| `PhantomCore\Settings\` | `includes/Settings/` | `Settings_Loader` → `includes/Settings/class-settings-loader.php` |
| `PhantomCore\Setup\` | `includes/Setup/` | `Demo_Content_Generator` → `includes/Setup/class-demo-content-generator.php` |
| `PhantomCore\Upgrade\` | `includes/Upgrade/` | `Upgrade_Manager` → `includes/Upgrade/class-upgrade-manager.php` |
| `PhantomCore\Renderer\` | `includes/renderer/` | `Product_Card` → `includes/renderer/class-product-card.php` |
| `PhantomCore\Api\` | `includes/Api/` | `REST_Controller` → `includes/Api/class-rest-controller.php` |
| `PhantomCore\Css\` | `includes/custom-css/` | (module files) |
| `PhantomCore\Wp\` | `includes/wp/` | (WordPress compatibility) |
| `Phantom\Theme\` | `phantom-theme/` | Theme classes |
| `Phantom\Theme\Admin\` | `phantom-theme/admin/` | Theme admin classes |
| `Phantom\Theme\Frontend\` | `phantom-theme/frontend/` | Theme frontend classes |

### Conversion Rule

```
Namespace separator (\)  →  Directory separator (/)
Underscore (_)            →  Hyphen (-)
Class prefix (class-)     →  Prepended
Suffix (class-.php)       →  Appended

Example:
  PhantomCore\Adapters\Post_Adapter
  → includes/Adapters/class-post-adapter.php
```

---

## 4. Container DI

**File:** `includes/Engine/Container_Config.php`

### Registered Services (53)

The DI container provides singleton and transient service management:

```
Core Services (singletons):
  ├── EventDispatcher
  ├── Template_Loader
  ├── Data_Engine
  ├── Asset_Engine
  ├── Render_Engine
  ├── WooCommerce_Injector
  ├── Feature_Registry
  ├── Component_Registry
  ├── Layout_Registry
  ├── Design_API
  ├── Hook_Registry
  ├── Bridge_Manager
  ├── Asset_Registry
  ├── Settings_Registry
  ├── Customizer
  ├── Custom_CSS
  ├── Global_Palette
  ├── Font_Families
  ├── Font_Loader
  ├── Capability_Manager
  ├── Component_Metadata
  ├── Template_Manifest
  ├── Data_Normalizer
  └── Layout_Manager

Adapters (transient, new instance per call):
  ├── Post_Adapter
  ├── Page_Adapter
  ├── User_Adapter
  ├── Footer_Adapter
  ├── Settings_Adapter
  ├── Product_Adapter
  ├── Menu_Adapter
  ├── Category_Adapter
  ├── Cart_Adapter
  ├── Coupon_Adapter
  ├── Order_Adapter
  ├── Comment_Adapter
  ├── Tag_Adapter
  ├── Search_Result_Adapter
  └── Hero_Adapter

ViewModels (transient):
  ├── Post_ViewModel
  ├── Page_ViewModel
  ├── User_ViewModel
  ├── Settings_ViewModel
  ├── Product_ViewModel
  ├── Category_ViewModel
  ├── Coupon_ViewModel
  ├── Order_ViewModel
  ├── Tag_ViewModel
  └── Search_Result_ViewModel

Public API Facades (transient):
  ├── Render
  ├── Component
  ├── Animation
  ├── Settings
  ├── Template
  ├── Design
  └── Developer

Managers:
  ├── Bridge_Manager
  └── Layout_Manager
```

### Usage

```php
// Get singleton
$loader = Container::get('Template_Loader');

// Get transient (new instance)
$adapter = Container::get('Post_Adapter');

// Check if registered
if (Container::has('WooCommerce_Bridge')) { ... }
```

---

## 5. Asset Engine

**File:** `includes/Engine/Asset_Engine.php`

Two injection modes for different template contexts:

### `inject_essential_only()` — Self-Contained AETHER Templates

Used when a template is fully self-contained (has its own DOCTYPE, complete HTML structure). Only injects the minimum required runtime data:

```
1. Bridge data        → window.phantomData (REST URL, nonce, site info)
2. Auth nonces        → WP nonce for AJAX requests
3. SPA routing JS     → Swup.js for client-side navigation
4. Customizer CSS     → <style id="phantom-inline-css"> with CSS vars
5. Security headers   → CSP, X-Frame-Options, etc.
```

**When to use:** Templates in `frontend/html/` that have complete `<!DOCTYPE html>`, full `<head>`, and self-contained markup. No placeholder tags.

### `inject_all()` — Pack Templates (Dynamic)

Used for templates with `{{PLACEHOLDER}}` tags that need server-side data injection:

```
1.  Route-specific CSS    → Template-matched stylesheet
2.  Images                → Hero banners, backgrounds
3.  Resource hints        → dns-prefetch, preconnect
4.  Google Fonts          → Web Font Loader output
5.  Font Awesome          → Icon font CSS
6.  SPA routing JS        → Swup.js
7.  CDN fallbacks         → Fallback if CDN fails
8.  Lazy loading          → IntersectionObserver polyfill
9.  Scroll reveal         → Scroll-triggered animations
10. Swiper gallery        → WooCommerce product gallery
11. Bridge data           → window.phantomData
12. Auth nonces           → WP nonces
13. Customizer CSS        → CSS vars <style> block
14. Plugin hooks          → Dynamic wp_head/wp_footer output
15. Security headers      → CSP, etc.
```

### Injection Decision Tree

```
Template loaded by Template_Loader::load()
  │
  ├── Is template self-contained (has DOCTYPE)?
  │   ├── YES → Asset_Engine::inject_essential_only()
  │   └── NO  → Asset_Engine::inject_all()
  │
  └── Does template have {{PLACEHOLDER}} tags?
      ├── YES → Placeholder_Replacer processes them
      └── NO  → Raw HTML served as-is
```

---

## 6. SPA Router (Shell)

**File:** `templates/shell.php` — ~700 lines

### Hook

```php
add_action('template_redirect', [Shell::class, 'handle_request'], 10);
```

### Bypass Conditions

Shell does NOT intercept these requests (WordPress handles them normally):

| Condition | Reason |
|-----------|--------|
| `is_feed()` | RSS/Atom feeds |
| `is_robots()` | robots.txt |
| `defined('DOING_CRON')` | WP-Cron requests |
| `strpos($uri, 'wp-json') !== false` | REST API |
| `strpos($uri, 'wp-admin') !== false` | Admin |
| `strpos($uri, 'wp-login') !== false` | Login |
| Static file extension (`.css`, `.js`, `.png`, etc.) | Asset requests |
| `strpos($uri, 'wc-ajax') !== false` | WooCommerce AJAX |

### URL Slug Resolution

Shell maps URL paths to template slugs:

```
/                    → "index"
/shop                → "shop"
/product/{slug}      → WP_Query('post_type=product', 'name={slug}') → product_id
/blog                → "blog"
/blog/{slug}         → WP_Query('post_type=post', 'name={slug}') → post_id
/category/{slug}     → category_slug → "shop" (filtered)
/cart                 → "cart"
/checkout            → "checkout"
/404                 → "404"
/about               → "about"
/contact             → "contact"
/faq                 → "faq"
/login               → "login"
/register            → "join-now"
/coming-soon         → "coming-soon"
/privacy             → "privacy"
/terms               → "terms"
/cookie              → "cookie"
/thank-you           → "thank-you"
/account             → "account"
/wishlist            → "wishlist"
```

### Render Pipeline

```
1. Shell::handle_request()
   │
   ├── Parse request URI
   ├── Check bypass conditions → return if any match
   │
   ├── Resolve slug from URL
   │   ├── Static slug? → use directly
   │   └── Dynamic slug? → WP_Query to resolve ID
   │
   ├── Template_Loader::resolve($slug)
   │   ├── Pack active? → frontend/packs/{pack}/html/{slug}.html
   │   └── Fallback?    → frontend/html/{slug}.html
   │
   ├── Template_Loader::load($file_path)
   │   └── Returns raw HTML contents
   │
   ├── Placeholder_Replacer::replace($html, $data)
   │   └── Replaces {{PLACEHOLDER}} tokens with dynamic data
   │
   ├── Asset_Engine::inject_all($html)
   │   └── Injects CSS, JS, meta tags, resource hints
   │
   ├── WooCommerce_Injector::inject($html)
   │   └── Injects WooCommerce-specific data (cart, products, etc.)
   │
   ├── Inject SEO metadata (<title>, <meta>, JSON-LD)
   ├── Inject window.phantomData JS config
   ├── Inject CSS variables (<style id="phantom-inline-css">)
   ├── Set security headers
   ├── Copyright year replacement: preg_replace('/\b2025\b/', date('Y'), $html)
   ├── Add skip-to-content link (accessibility)
   │
   └── echo $html; exit;
```

### Session Initialization

```php
// template_redirect priority 5 — runs BEFORE handle_request
Shell::init_wc_session() {
    if (class_exists('WooCommerce')) {
        WC()->session->init();
    }
}
```

---

## 7. Template System

**File:** `includes/Engine/class-template-loader.php`

### Template Types

| Type | Description | Has DOCTYPE? | Has Placeholders? |
|------|-------------|--------------|-------------------|
| **Self-contained (AETHER)** | Complete HTML documents | Yes | No |
| **Pack templates** | Override base templates per pack | No | Yes (`{{PLACEHOLDER}}`) |

### Path Resolution

```
Template_Loader::resolve($slug)
  │
  ├── 1. Check if pack is active
  │       get_option('phantom_template_pack') → 'dark' | 'minimal' | 'bold' | false
  │
  ├── 2. If pack active:
  │       $pack_path = PHANTOM_CORE_PATH . "frontend/packs/{$pack}/html/{$slug}.html"
  │       file_exists($pack_path) → return $pack_path
  │
  ├── 3. Fallback to base:
  │       $base_path = PHANTOM_CORE_PATH . "frontend/html/{$slug}.html"
  │       file_exists($base_path) → return $base_path
  │
  └── 4. Not found → return false (Shell outputs 404)
```

### Template Packs

3 packs, each in `frontend/packs/{pack}/`:

```
frontend/packs/
  ├── dark/
  │   ├── manifest.json          — Pack metadata (name, description, version)
  │   ├── scss/pack.scss         — SCSS overrides
  │   ├── assets/css/pack.css    — Compiled CSS
  │   ├── assets/js/pack.js      — Pack-specific JS
  │   └── html/
  │       ├── index.html
  │       ├── shop.html
  │       ├── 404.html
  │       ├── product-card.html
  │       └── blog-card.html
  ├── minimal/
  │   └── (same structure)
  └── bold/
      └── (same structure)
```

**Pack methods:**
- `pack_exists($pack)` — Check if pack directory exists
- `get_pack_manifest($pack)` — Read manifest.json
- `get_pack_asset_urls($pack)` — Get CSS/JS URLs for enqueuing
- `Component_Renderer` checks `phantom_template_pack` option for component overrides

---

## 8. CSS Generation Engine

**File:** `includes/class-custom-css.php`

### Output

Single `<style id="phantom-inline-css">` tag injected into `<head>` via `wp_head` at priority 100:

```html
<style id="phantom-inline-css">
:root {
  --primary--color: #ff0000;
  --header-bg: #1a1a1a;
  --font-body: 'Inter', sans-serif;
  --button-bg: #0066cc;
  /* ... 136+ CSS custom properties */
}
</style>
```

### CSS Modules (9)

Each module hooks `phantom_dynamic_css` filter and appends CSS rules:

| File | Priority | Purpose |
|------|----------|---------|
| `includes/custom-css/colors.php` | 10 | Color scheme CSS vars |
| `includes/custom-css/typography.php` | 20 | Typography CSS vars |
| `includes/custom-css/header.php` | 30 | Header CSS vars |
| `includes/custom-css/footer.php` | 40 | Footer CSS vars |
| `includes/custom-css/layout.php` | 50 | Layout CSS vars |
| `includes/custom-css/buttons.php` | 60 | Button CSS vars |
| `includes/custom-css/product.php` | 70 | Product card CSS vars |
| `includes/custom-css/responsive.php` | 80 | Responsive breakpoint vars |
| `includes/custom-css/hero.php` | 90 | Hero responsive media + `@media` queries |

### Filter Pipeline

```
phantom_dynamic_css filter (136+ vars)
  │
  ├── Priority 10: colors.php    → appends color vars
  ├── Priority 20: typography.php → appends font vars
  ├── Priority 30: header.php    → appends header vars
  ├── Priority 40: footer.php    → appends footer vars
  ├── Priority 50: layout.php    → appends layout vars
  ├── Priority 60: buttons.php   → appends button vars
  ├── Priority 70: product.php   → appends product vars
  ├── Priority 80: responsive.php→ appends breakpoint vars
  └── Priority 90: hero.php      → appends hero media + @media queries
  │
  ▼
Output: single CSS string → Shell injects as <style>
```

### Caching

- **Transient cache:** `phantom_dynamic_css` key, TTL 3600 seconds (1 hour)
- **Optional file cache:** Writes to `wp-content/cache/phantom-dynamic.css`
- **Cache flush:** `POST /phantom/v1/cache/flush` REST endpoint or `WP-CLI wp transient delete phantom_dynamic_css`

### Customizer Integration

Settings with `css_var` set in `Settings_Registry` are auto-mapped to CSS variables:

```
Settings_Registry entry:
  key: 'primary_color'
  css_var: '--primary--color'
  css_selector: ':root'

Shell::inject_customizer_css()
  → get_option('phantom_primary_color') → '#ff0000'
  → ":root { --primary--color: #ff0000; }"
```

The `get_css_var_map()` method dynamically expands to include design token CSS vars (298+ additional entries beyond the base 136).

---

## 9. Feature Flags

**File:** `includes/class-feature-registry.php`

### How It Works

```php
$registry = Container::get('Feature_Registry');

if ($registry->is_active('animations')) {
    // Enqueue animation scripts
}

if ($registry->is_active('woocommerce')) {
    // Show WooCommerce features
}
```

Each feature flag checks `get_option('phantom_feature_{id}')`. Disabled by default.

### Available Feature Flags

| Feature ID | Option Key | Purpose |
|------------|------------|---------|
| `animations` | `phantom_feature_animations` | Scroll-reveal and entrance animations |
| `smooth_scroll` | `phantom_feature_smooth_scroll` | Smooth scroll behavior |
| `lottie_animations` | `phantom_feature_lottie_animations` | Lottie JSON animations |
| `woocommerce` | `phantom_feature_woocommerce` | WooCommerce integration features |
| `lazy_load_images` | `phantom_feature_lazy_load_images` | Native lazy loading for images |
| `animate_on_scroll` | `phantom_feature_animate_on_scroll` | Scroll-triggered animations |
| `swiper_gallery` | `phantom_feature_swiper_gallery` | Swiper.js product gallery |
| `parallax_effects` | `phantom_feature_parallax_effects` | Parallax scrolling effects |
| `three_js_effects` | `phantom_feature_three_js_effects` | Three.js 3D effects |
| `wishlist` | `phantom_feature_wishlist` | Wishlist functionality |

### Usage Pattern

```php
// In template injection:
if ($feature_registry->is_active('swiper_gallery')) {
    $html .= Asset_Engine::enqueue_swiper_gallery();
}

// In REST controller:
if ($feature_registry->is_active('woocommerce')) {
    register_rest_route('phantom/v1', '/cart', [...]);
}
```

---

## 10. Bridge System

**Files:** `includes/Bridges/`

### Architecture

```
BridgeInterface (contract)
  │
  ├── is_active()  → bool (checks if plugin dependency is installed)
  ├── init()       → void (register hooks, filters, actions)
  └── enqueue()    → void (enqueue assets)
  │
  ├── Plugin_Bridge (abstract base)
  │   ├── Capability checks (phantom_manage_bridges)
  │   ├── Dependency guards (class_exists checks)
  │   └── Init/teardown lifecycle
  │
  └── Concrete implementations:
      ├── WooCommerce_Bridge
      ├── Swiper_Bridge
      ├── ThreeJS_Bridge
      ├── Wishlist_Bridge
      ├── Mailchimp_Bridge
      ├── Gutenberg_Bridge
      ├── Elementor_Bridge
      ├── WPML_Bridge
      ├── RankMath_Bridge
      ├── Yoast_Bridge
      └── CF7_Bridge
```

### Bridge Manager

**File:** `includes/Bridges/class-bridge-manager.php`

Singleton that manages all bridge lifecycle:

```php
$bridge_manager = Bridge_Manager::get_instance();

// Register a bridge
$bridge_manager->register('woocommerce', new WooCommerce_Bridge());

// Initialize all active bridges
$bridge_manager->init_all();  // Called on 'init' hook priority 1

// Query bridge status
$bridge_manager->is_bridge_active('woocommerce');  // true/false
$bridge_manager->get_active();  // array of active bridge names
```

### Bootstrap Integration

```php
// In phantom-core.php or Plugin::init()
add_action('init', function () {
    Bridge_Manager::init_all();
}, 1);  // Priority 1 — early initialization
```

### Concrete Bridge Details

| Bridge | Dependency Check | What It Provides |
|--------|-----------------|------------------|
| `WooCommerce_Bridge` | `class_exists('WooCommerce')` | Cart hooks, product data normalization, checkout styling, session management |
| `Swiper_Bridge` | `class_exists('Swiper')` or asset check | Product image gallery with Swiper.js |
| `ThreeJS_Bridge` | `class_exists('ThreeJS')` or CDN check | 3D product visualization, parallax backgrounds |
| `Wishlist_Bridge` | `class_exists('YITH_Wishlist')` or similar | Wishlist add/remove buttons, wishlist page data |
| `Mailchimp_Bridge` | `class_exists('MC4WP')` or API key check | Newsletter signup forms, audience sync |
| `Gutenberg_Bridge` | `class_exists('Gutenberg')` or WP 5.0+ | Block patterns, block styles registration |
| `Elementor_Bridge` | `class_exists('Elementor\Plugin')` | Widget registration, template imports |
| `WPML_Bridge` | `class_exists('SitePress')` | Multilingual routing, language switcher |
| `RankMath_Bridge` | `class_exists('RankMath')` | SEO meta integration, schema markup |
| `Yoast_Bridge` | `class_exists('WPSEO_Options')` | SEO meta integration, OpenGraph |
| `CF7_Bridge` | `class_exists('WPCF7')` | Contact form rendering, validation, submission |

### Bridge Interface Contract

```php
interface BridgeInterface {
    /**
     * Check if the bridge's dependency plugin is active.
     * @return bool
     */
    public function is_active(): bool;

    /**
     * Initialize bridge hooks and filters.
     * Called once on 'init' if is_active() returns true.
     * @return void
     */
    public function init(): void;

    /**
     * Enqueue bridge-specific assets.
     * Called on 'wp_enqueue_scripts' if is_active() returns true.
     * @return void
     */
    public function enqueue(): void;
}
```

---

## Quick Reference: Key Files

| File | Purpose | Lines |
|------|---------|-------|
| `phantom-core.php` | Plugin bootstrap, constants, autoloader | ~200 |
| `phantom-theme/functions.php` | Theme setup, menus, widgets, assets | ~400 |
| `includes/class-settings-registry.php` | 612 settings, 46 sections | 5,555+ |
| `includes/class-rest-controller.php` | 60 REST routes, phantom/v1 | ~2,300 |
| `includes/class-customizer.php` | 16 panels, 46 sections | 540 |
| `includes/class-custom-css.php` | CSS Generation Engine | ~300 |
| `includes/Engine/class-template-loader.php` | Pack-aware template resolution | ~250 |
| `includes/Engine/Asset_Engine.php` | Essential vs full injection | ~400 |
| `includes/Engine/Container_Config.php` | 53 DI services | ~600 |
| `includes/Bridges/class-bridge-manager.php` | Bridge lifecycle manager | ~200 |
| `templates/shell.php` | SPA router, render pipeline | ~700 |
| `frontend/assets/js/phantom-data.js` | REST API → DOM injection | 2,364 |
| `admin/js/customizer-preview.js` | Live preview bindings | ~400 |
| `admin/js/design-studio.js` | Design Studio toast/AJAX | ~300 |

---

## Architecture Diagram

```
                    ┌─────────────────────────────────────────────┐
                    │              WordPress Backend              │
                    │  Options API · DB · Users · Posts · WooCommerce │
                    └──────────────────┬──────────────────────────┘
                                       │
              ┌────────────────────────┼────────────────────────┐
              │                        │                        │
    ┌─────────▼──────────┐  ┌─────────▼──────────┐  ┌─────────▼──────────┐
    │   Settings Registry │  │    REST Controller  │  │    Customizer      │
    │   612 settings      │  │    60 routes        │  │    16 panels       │
    │   46 sections       │  │    phantom/v1       │  │    46 sections     │
    └─────────┬──────────┘  └─────────┬──────────┘  └─────────┬──────────┘
              │                        │                        │
              └────────────────────────┼────────────────────────┘
                                       │
              ┌────────────────────────┼────────────────────────┐
              │                        │                        │
    ┌─────────▼──────────┐  ┌─────────▼──────────┐  ┌─────────▼──────────┐
    │  CSS Generation     │  │   Container DI     │  │  Feature Registry  │
    │  9 modules          │  │   53 services      │  │  10 flags          │
    │  136+ CSS vars      │  │   singletons       │  │  option-based      │
    └─────────┬──────────┘  └─────────┬──────────┘  └─────────┬──────────┘
              │                        │                        │
              └────────────────────────┼────────────────────────┘
                                       │
                    ┌──────────────────▼──────────────────────────┐
                    │              Shell (SPA Router)             │
                    │  template_redirect priority 10             │
                    │  URL → slug → template → render pipeline   │
                    └──────────────────┬──────────────────────────┘
                                       │
              ┌────────────────────────┼────────────────────────┐
              │                        │                        │
    ┌─────────▼──────────┐  ┌─────────▼──────────┐  ┌─────────▼──────────┐
    │  Template Loader    │  │  Asset Engine      │  │  WooCommerce       │
    │  Pack-aware         │  │  essential/all     │  │  Injector          │
    │  3 packs (D/M/B)   │  │  CSS/JS/meta       │  │  Cart/products     │
    └─────────┬──────────┘  └─────────┬──────────┘  └─────────┬──────────┘
              │                        │                        │
              └────────────────────────┼────────────────────────┘
                                       │
                    ┌──────────────────▼──────────────────────────┐
                    │           Frontend HTML Templates           │
                    │  22 static files · 3 pack overrides         │
                    │  phantom-data.js · Swup.js · Bootstrap 5    │
                    └────────────────────────────────────────────┘
```
