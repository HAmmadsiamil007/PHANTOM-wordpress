# Phantom Core — Complete Frontend Replacement Guide

> How to completely replace the HTML/CSS/JS frontend with any framework
> (React, Vue, Next.js, Svelte, static HTML, etc.) WITHOUT touching PHP.

---

## Architecture: How It Connects

```
PHP Backend (NEVER change this)
  │
  ├─ Shell.php: Routes URL → Loads HTML template
  │     ├─ Matches URL to internal route
  │     ├─ Injects 136 CSS vars as <style>
  │     ├─ Injects SEO meta tags
  │     ├─ Injects phantomData JS config object
  │     ├─ Injects phantom-data.js
  │     └─ Outputs HTML + exit
  │
  ├─ rest-controller.php: phantom/v1 API
  │     └─ 49 routes (42 unique paths): settings, menus, products, posts, cart, auth
  │
  └─ class-customizer.php: CSS var generation
        └─ 136 CSS vars injected into every page

Frontend (100% replaceable)
  │
  ├─ frontend/html/*.html — 22 page templates
  ├─ frontend/assets/js/phantom-data.js — Core data bridge
  └─ frontend/assets/css/ — CSS files
```

### 12 Critical Connection Points (Must Not Break)

| # | Connection | What It Does | If Broken |
|---|-----------|-------------|-----------|
| 1 | **Route slug** → Shell.php maps URL to HTML file | `/shop` → `frontend/shop.html` | 404 on page |
| 2 | **CSS var names** → Injected as `<style id="phantom-customizer-css">` (136 vars) | Changes colors/fonts/layout via Customizer | Customizer has no effect |
| 3 | **`data-phantom` attributes** → JS injects content | Text, images, links from settings | Static fallback content shows |
| 4 | **Template strings** → `ProductCard.template` / `CategoryCard.template` in phantom-data.js | If changing card HTML, update template string, not JS logic | Cards silently diverge from template |
| 5 | **CSS class names** → phantom-data.js queries by class | Cart badge, menu toggle, search | Feature silently breaks |
| 6 | **`PhantomData` JS object** → Injected by Shell, consumed by JS | Contains REST URL, nonce, settings | Everything breaks |
| 7 | **REST API URL** → Fetch target for all dynamic data | `PhantomServices.Api.baseUrl + '/phantom/v1/...'` | No data loads |
| 8 | **`#swup` container** → Swup.js replaces content on navigation | SPA page transitions | Full page reload on every click |
| 9 | **`#contactpage` form** → Checkout form data collection | Shipping, payment, order submission | Checkout breaks |
| 10 | **Settings Registry** → `define_entries()` defines all ~612 settings | Every `data-phantom` attribute's data source | Missing keys show fallback text |
| 11 | **Layout Registry** → 7 default layouts control structure | Page template selection and layout variants | Layout options unavailable |
| 12 | **Plugin Bridges** → Bridge_Manager with WooCommerce bridge | WC product/cart/checkout integration | WooCommerce features fail |

---

## Step-by-Step: Replace HTML Only (Easiest)

### Step 1: Create New HTML Templates
Replace files in `frontend/*.html`. Use any HTML framework.

### Step 2: Add Data-Binding Attributes
For each dynamic element, add `data-phantom` attributes:

```html
<!-- BEFORE (static text) -->
<h1>Welcome to Our Store</h1>

<!-- AFTER (dynamic — injected from settings) -->
<h1 data-phantom="hero_title">Welcome to Our Store</h1>
```

### Step 3: Keep Required Markup
```html
<!DOCTYPE html>
<html lang="en">
<head>
  <!-- Shell.php will inject here: CSS vars, SEO meta, phantomData JS, scripts -->
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Page Title <!-- replaced by Shell SEO --></title>
  <link rel="stylesheet" href="frontend/assets/css/style.css">
</head>
<body>
  <main id="swup"><!-- REQUIRED for SPA transitions -->
    <!-- Your page content -->
    <h1 data-phantom="hero_title">Default Title</h1>
    <nav data-phantom-menu="primary"><!-- falls back to hardcoded nav --></nav>
    <div data-phantom-products="featured" data-count="4"><!-- fallback --></div>
  </main>
</body>
</html>
```

### Step 4: Include phantom-data.js
```html
<script src="/wp-content/plugins/phantom-core/frontend/assets/js/phantom-data.js"></script>
<!-- OR via the injected phantomData.plugin_url -->
```

### Step 5: WHAT NOT TO CHANGE
```
❌ id="swup"              — Swup SPA container (breaks navigation)
❌ data-phantom="*"        — Data bridge (breaks content injection)
❌ data-phantom-menu="*"   — Menu injection
❌ data-phantom-products="*" — Product injection
❌ data-phantom-posts="*"  — Post injection
❌ data-phantom-bg="*"     — Background images
❌ .cart-count             — Cart badge
❌ .shopping-cart-info     — Cart panel
❌ #contactpage            — Checkout form
❌ #category1              — Category list
❌ .add-to-cart-trigger    — Add to cart
❌ .decrease-button        — Quantity down
❌ .increase-button        — Quantity up
❌ .remove-product         — Remove from cart
❌ CSS var names (--primary--color, etc.)
❌ phantomData global variable name
❌ REST URL pattern (/wp-json/phantom/v1/*)
❌ Route slugs without updating Shell.php
❌ The header/footer structure for Swup
```

---

## Step-by-Step: Replace with React/Vue/Next.js

### Option A: Embed in Shell (Keep SPA Router)
1. Build your React app in `frontend/react-app/`
2. The shell still serves `frontend/index.html` with the React mount point
3. React app reads `window.phantomData` for settings and REST URL
4. React app calls `phantomData.rest_url + 'phantom/v1/...'` for data
5. Shell still handles SEO, CSS vars, security headers

```html
<!-- frontend/index.html — React mount -->
<div id="root"></div>
<script src="frontend/react-app/build/main.js"></script>
```

### Option B: Replace Shell Entirely (Full SPA)
1. Build a standalone app (Next.js, Nuxt, etc.)
2. Shell.php only serves the app shell (or bypass it entirely)
3. App fetches `/wp-json/phantom/v1/settings` on startup
4. App applies CSS vars via `document.documentElement.style.setProperty()`
5. App handles all routing client-side
6. Use WordPress for REST API only (headless CMS)

### Option C: Mixed (Existing Pages + New App)
1. Keep Shell serving 22 static templates for existing pages
2. Add a single route in Shell that serves your new app
3. New app runs independently alongside static templates

---

## Step-by-Step: Route Changes

### To Change a Route Slug
Edit `Shell.php` lines ~120-160:
```php
// Change '/shop' to '/products'
private $routes = [
    '/products'    => 'frontend/shop.html',
    // ... rest unchanged
];
```

### To Add a New Page
1. Create `frontend/my-new-page.html`
2. Add route: `'/my-page' => 'frontend/my-new-page.html'`
3. Add SEO title in `Shell::get_meta_tags()`
4. Add `data-phantom` attributes for dynamic content

### Dynamic Routes (already supported)
```
/product/{slug}    → product-detail.html  (slug = product slug)
/blog/{slug}       → single-blog.html     (slug = post slug)
```

---

## Step-by-Step: Replace CSS

### Safe to Change
- ✅ All style rules in `frontend/assets/css/*.css`
- ✅ Layout, spacing, colors — reference CSS vars
- ✅ Responsive breakpoints — use `var(--*breakpoint*)` or override
- ✅ Component styles, animations, transitions
- ✅ Font imports, typography defaults

### Must Keep
- ❌ Must use `var(--primary--color)` etc. for dynamic theming
- ❌ Must keep class names used by phantom-data.js (see full list on FRONTEND-GUIDE.md)
- ❌ Must keep Swup transition hooks if using SPA navigation

```css
/* ✅ DO: Reference CSS vars for customizable properties */
.button {
    background: var(--primary--color, #0066cc);
    border-radius: var(--border--radius, 4px);
    font-size: var(--btn--font--size, 16px);
}

/* ❌ DON'T: Hardcode values that should be theme settings */
.button {
    background: #0066cc;    /* BAD — should use var(--primary--color) */
    border-radius: 4px;     /* BAD — should use var(--border--radius) */
}
```

---

## Troubleshooting: If Frontend Becomes Disconnected

### Symptom: Content not loading
**Fix:** Check `data-phantom` attributes exist in HTML
```html
<!-- Missing: -->
<h1>Welcome</h1>

<!-- Fixed: -->
<h1 data-phantom="hero_title">Welcome</h1>
```

### Symptom: Empty REST API response
**Fix:** Check `phantomData.rest_url` in browser console
```javascript
console.log(window.phantomData.rest_url);
// Should be: "https://yoursite.com/wp-json/"
```

### Symptom: Full page reload on navigation (SPA broken)
**Fix:** Ensure `<main id="swup">` exists in all templates

### Symptom: CSS var values not applied
**Fix:** Check `<style id="phantom-customizer-css">` is injected in `<head>`

### Symptom: Cart/AJAX broken
**Fix:** Ensure jQuery is loaded before `phantom-data.js`

### Symptom: Class name mismatch
**Fix:** Compare HTML class names with phantom-data.js selector list

### Symptom: 404 on page load
**Fix:** Check route exists in `Shell.php` `$routes` array

### Symptom: Data attributes ignored
**Fix:** Verify the setting key exists in `Settings_Registry::define_entries()`

### Symptom: Customizer changes not showing
**Fix:** Check CSS var names match between `customizer.php` and `shell.php`

---

## If You Need to Reconnect a Disconnected Frontend

### Complete Reconnection Checklist

1. **Verify Shell Injection**
   - [ ] `<style id="phantom-customizer-css">` present in `<head>`
   - [ ] `window.phantomData` object present (check console)
   - [ ] `phantomData.rest_url` is valid REST base URL
   - [ ] `phantomData.plugin_url` points to plugin directory

2. **Verify REST API**
   - [ ] `GET /wp-json/phantom/v1/settings` returns 200
   - [ ] `GET /wp-json/phantom/v1/page-data` returns all data
   - [ ] `GET /wp-json/phantom/v1/menus/primary` returns menu

3. **Verify phantom-data.js**
   - [ ] Script loads without errors (check console)
   - [ ] `injectSettings()` fires and finds elements
   - [ ] `injectMenus()` populates navigation
   - [ ] `injectProducts()` renders product grid
   - [ ] `injectCart()` shows cart data

4. **Verify Data Attributes in HTML**
   - [ ] `[data-phantom="site_title"]` exists and populates
   - [ ] `[data-phantom-menu="primary"]` populates with menu items
   - [ ] `[data-phantom-products="featured"]` renders products
   - [ ] `[data-phantom-posts="recent"]` renders blog posts

5. **Verify WooCommerce**
   - [ ] Add to cart click → item appears in cart
   - [ ] Quantity change → total updates
   - [ ] Remove from cart → item removed
   - [ ] Checkout form → order created

---

## Complete Migration Checklist

### Phase 1: Audit (do this first)
- [ ] Show current templates in `frontend/`
- [ ] List all `data-phantom` attributes and their types
- [ ] Map all 49 REST API routes (42 unique paths)
- [ ] Note all 136 CSS var usages in CSS files
- [ ] Identify hardcoded vs dynamic content

### Phase 2: Build New Templates
- [ ] Create new HTML files with correct `data-phantom` attributes
- [ ] Ensure `<main id="swup">` on main content area
- [ ] Keep required CSS class names
- [ ] Test each template at its URL

### Phase 3: Replace CSS
- [ ] New CSS files referencing `var(--*)` vars
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
- [ ] Replace inline `<style>` injection with external CSS if desired
- [ ] Replace Swup with lighter SPA if needed

---

## Common Pitfalls

| Pitfall | Symptom | Fix |
|---------|---------|-----|
| Missing `data-phantom` attr | Content not loaded | Add `data-phantom="setting_key"` |
| Wrong REST API URL | Empty data, console error | Check `phantomData.rest_url` in `<script>` |
| Swup container missing | Full page reload on nav | Add `<main id="swup">` element |
| CSS var name mismatch | Fallback var visible | Sync customizer.php + shell.php var lists |
| Class name mismatch | JS function does nothing | Check phantom-data.js selector list |
| PX key missing | CSS var without `px` suffix | Add to `get_px_keys()` in shell.php |
| Route slug mismatch | 404 in SPA | Check Shell.php `$routes` array |
| Missing jQuery | Cart/AJAX broken | phantom-data.js requires jQuery |
| Missing Swup scripts | Page transition broken | Include Swup CSS + JS |
| File path wrong | 404 on template | Path relative to plugin root |
| Nonce expired | Auth operations fail | Refresh page to get new nonce |
| Cache not flushed | Old settings showing | POST `/phantom/v1/cache/flush` |

---

## Quick Reference: Key Files to Keep/Break

| File | Never Delete | Can Replace |
|------|-------------|-------------|
| `frontend/*.html` | ❌ Keep `data-phantom` attrs | ✅ Replace layout |
| `frontend/assets/js/phantom-data.js` | ❌ Core data bridge | ✅ Append new helper JS |
| `frontend/assets/css/style.css` | ❌ Keep CSS var references | ✅ Replace rules |
| `includes/class-settings-registry.php` | ❌ NEVER touch | ❌ NEVER touch |
| `includes/class-rest-controller.php` | ❌ NEVER touch | ❌ NEVER touch |
| `templates/shell.php` | ❌ Only for route/SEO changes | ⚠️ Minimal changes only |
| `frontend/assets/images/` | ✅ Replace freely | ✅ Replace freely |
