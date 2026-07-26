# Phantom Core — Comprehensive Test Plan
**Date**: 2026-07-26  
**Version**: 1.5.3  
**Docker**: WordPress 8080, MySQL 8.0 3307, container `optix_wordpress`  
**Admin**: `admin` / `admin123`  
**Auth**: `X-WP-Nonce` header with `wp_rest` nonce; `X-Phantom-Nonce`/`phantom_api` fallback

---

## 1. WordPress Core Integration

| # | Test | Method | Expected | Status |
|---|------|--------|----------|--------|
| 1.1 | Plugin active | `wp plugin list` | `phantom-core` active | |
| 1.2 | Plugin deactivate/reactivate | WP admin → Plugins | No errors | |
| 1.3 | SPA shell intercepts frontend | `GET /` → inspect HTML | Returns `templates/shell.php` content, not WP default | |
| 1.4 | SPA routes resolve | `GET /shop`, `/about`, `/blog` etc. | 200, shell with correct template | |
| 1.5 | SPA 404 route | `GET /nonexistent-page` | shell with `404.html` template | |
| 1.6 | Template redirect not interfere admin | `GET /wp-admin/` | Normal WP admin | |
| 1.7 | Template redirect not interfere REST | `GET /wp-json/` | Normal REST response | |
| 1.8 | `PHANTOM_CORE_VERSION` constant | `php -r 'echo PHANTOM_CORE_VERSION'` | `1.5.3` | |
| 1.9 | Autoloader resolves classes | Check no `require_once` fails | No fatal errors | |
| 1.10 | Flush rewrite rules on activation | Deactivate/reactivate, check permalinks | SPA routing intact | |

---

## 2. Settings Registry

| # | Test | Method | Expected | Status |
|---|------|--------|----------|--------|
| 2.1 | All 591 entries load | `Settings_Registry::get_entries()` | Returns 591 items | |
| 2.2 | All 44 sections defined | Inspect entries `section` keys | 44 unique sections | |
| 2.3 | All entries have `default` | Iterate entries | Non-null default on each | |
| 2.4 | All entries have `type` | Iterate entries | Valid type string | |
| 2.5 | `search_post_types` has sanitize | `entries['search_post_types']['sanitize']` | `'sanitize_textarea_field'` | |
| 2.6 | `get()` returns default for unset | `get('nonexistent_key')` | `null` | |
| 2.7 | `has()` works | `has('general_site_logo')` / `has('bogus')` | `true` / `false` | |
| 2.8 | `set()` persists to DB | `set('test_key', 'val')` → `get('test_key')` | `'val'` | |
| 2.9 | Sanitize callback fires on `set()` | Set XSS string → get back sanitized | Sanitized | |
| 2.10 | Double `register()` returns early | `register()` twice | No duplicate entries | |

---

## 3. REST API — Auth & Nonce

| # | Test | Method | Expected | Status |
|---|------|--------|----------|--------|
| 3.1 | GET /settings without auth | No nonce header | 401 or error | |
| 3.2 | GET /settings with X-WP-Nonce | Valid nonce | 200 + settings array | |
| 3.3 | GET /settings with X-Phantom-Nonce | Valid phantom_api nonce | 200 | |
| 3.4 | Invalid nonce rejected | Bad nonce | 401 | |
| 3.5 | Nonce expiry check | Old nonce after timeout | 401 | |
| 3.6 | POST /settings without nonce | No header | 401 | |
| 3.7 | POST /settings with valid nonce | Valid + settings payload | 200, settings saved | |

---

## 4. REST API — Settings Endpoints

| # | Test | Method | Expected | Status |
|---|------|--------|----------|--------|
| 4.1 | GET /settings returns 626 items | `X-WP-Total` header | 626 | |
| 4.2 | GET /settings paginated | `?page=1&per_page=50` | 50 items | |
| 4.3 | GET /settings page 13 | `?page=13&per_page=50` | Last page items | |
| 4.4 | GET /settings/{key} | `general_site_logo` | Returns single setting | |
| 4.5 | GET /settings/{key} not found | `nonexistent_key` | 404 | |
| 4.6 | POST /settings (bulk) | `{"settings": {"key": "val"}}` | Saved | |
| 4.7 | POST /settings (changes) | `{"changes": {"key": "val"}}` | Saved | |
| 4.8 | POST /settings with empty body | `{}` | 400 error | |
| 4.9 | POST /settings bulk 50 items | 50 keys at once | All saved | |
| 4.10 | POST /settings validate types | String in numeric field | Validation error | |

---

## 5. REST API — Partial Endpoint

| # | Test | Method | Expected | Status |
|---|------|--------|----------|--------|
| 5.1 | GET /partial?partial=header_style | Auth'd request | 200, HTML + selector | |
| 5.2 | GET /partial?partial=footer_layout | Auth'd request | 200, HTML + selector | |
| 5.3 | GET /partial?partial=blog_layout | Auth'd request | 200, HTML + selector | |
| 5.4 | GET /partial with invalid key | `bogus` | 400 error | |
| 5.5 | GET /partial without auth | No nonce | 401 | |
| 5.6 | Response includes `selector` field | Check JSON body | CSS selector for DOM targeting | |
| 5.7 | Partial HTML has expected markup | Inspect HTML | Valid, semantic HTML | |

---

## 6. REST API — WooCommerce Endpoints

| # | Test | Method | Expected | Status |
|---|------|--------|----------|--------|
| 6.1 | GET /wc/products | Auth'd | 200, product list | |
| 6.2 | GET /wc/products/{id} | Valid product ID | 200, product detail | |
| 6.3 | GET /wc/products/{id} not found | Invalid ID | 404 | |
| 6.4 | POST /wc/products | Valid product data | 201, product created | |
| 6.5 | PUT /wc/products/{id} | Update product fields | 200, updated | |
| 6.6 | DELETE /wc/products/{id} | Valid product ID | 200, deleted | |
| 6.7 | GET /wc/categories | Auth'd | 200, category list | |
| 6.8 | POST /wc/categories | Valid category data | 201, created | |
| 6.9 | Cart — GET /wc/cart | Auth'd | 200, cart contents | |
| 6.10 | Cart — POST /wc/cart/add | Product ID + qty | 200, item added | |
| 6.11 | Cart — POST /wc/cart/update | Item key + qty | 200, updated | |
| 6.12 | Cart — POST /wc/cart/remove | Item key | 200, removed | |
| 6.13 | Cart — POST /wc/cart/apply-coupon | Valid coupon | 200, applied | |
| 6.14 | Cart — POST /wc/cart/remove-coupon | Applied coupon | 200, removed | |
| 6.15 | GET /wc/cart/shipping-methods | Auth'd | 200, methods list | |
| 6.16 | GET /wc/checkout | Auth'd | 200, checkout data | |
| 6.17 | POST /wc/checkout | Valid order data | 200, order created | |
| 6.18 | WC endpoints without WooCommerce active | Deactivate WC | 400, `woocommerce_inactive` | |

---

## 7. REST API — Utility Endpoints

| # | Test | Method | Expected | Status |
|---|------|--------|----------|--------|
| 7.1 | GET /post-types | Auth'd | 200, post types list | |
| 7.2 | GET /pages | Auth'd | 200, pages list | |
| 7.3 | GET /menu-locations | Auth'd | 200, menu locations | |
| 7.4 | POST /cache/flush | Auth'd | 200, caches flushed | |
| 7.5 | POST /import | Valid settings JSON | 200, imported | |
| 7.6 | POST /import with oversized payload | >5 MB | 413 | |
| 7.7 | POST /import rate limit | Multiple rapid calls | 429 | |
| 7.8 | Rate limiting on cart endpoints | Rapid cart requests | 429 | |

---

## 8. Theme Options (Admin Page)

| # | Test | Method | Expected | Status |
|---|------|--------|----------|--------|
| 8.1 | Page loads at `Appearance → Phantom Core` | `GET /wp-admin/themes.php?page=phantom-core` | 200 | |
| 8.2 | All 15 tabs render | Check tab bar HTML | 15 tab links | |
| 8.3 | Tab navigation works | Click each tab | Content switches | |
| 8.4 | Save settings on tab | Fill + submit | Settings persist | |
| 8.5 | Color picker renders | Check for `.wp-color-picker` | WP color picker active | |
| 8.6 | Image upload works | Media library upload | URL stored | |
| 8.7 | Repeater field add/remove | Click add/remove rows | Rows added/removed | |
| 8.8 | Code editor renders | Check `.wp-editor-area` | CodeMirror active | |
| 8.9 | Nonce validation on save | Submit with bad nonce | 403 error | |
| 8.10 | Permission check | Non-admin user | 403 | |
| 8.11 | Settings persist across page reload | Save → reload → check | Values retained | |
| 8.12 | Tab state preserved after save | Save on tab 3 → tab 3 active | Correct tab active | |

---

## 9. Customizer

| # | Test | Method | Expected | Status |
|---|------|--------|----------|--------|
| 9.1 | Customizer loads | `GET /wp-admin/customize.php` | 200, no errors | |
| 9.2 | All 15 panels render | Inspect panel list | 15 panel headings | |
| 9.3 | All 44 sections render | Expand each panel | 44 sections total | |
| 9.4 | ast-color controls work | Open branding → pick color | Control renders, value changes | |
| 9.5 | ast-toggle controls work | Toggle switch | State toggles | |
| 9.6 | ast-select controls work | Dropdown selection | Value changes | |
| 9.7 | Live preview — CSS var updates | Change color → preview | `document.documentElement.style.setProperty` fires | |
| 9.8 | Live preview — partial refresh | Change header style → preview | Partial HTML updates | |
| 9.9 | Save/publish persists | Save → reload customizer | Values retained | |
| 9.10 | CSS vars on frontend after save | View frontend | CSS vars present in `<style id="phantom-inline-css">` | |
| 9.11 | 8 unused control types don't break | Register all types | No PHP errors | |
| 9.12 | Selective refresh partials registered | Check Customizer API | `add_partial()` hooks present | |

---

## 10. Menus

| # | Test | Method | Expected | Status |
|---|------|--------|----------|--------|
| 10.1 | All 6 nav locations registered | `Appearance → Menus → Manage Locations` | 6 locations visible | |
| 10.2 | Primary menu displays on frontend | `GET /` | Menu items render in `<nav.main-nav>` | |
| 10.3 | Footer menu displays | `GET /` | Menu items in `<footer.footer>` | |
| 10.4 | Mobile menu functionality | Viewport < 768px | Hamburger/mobile menu shows | |
| 10.5 | Menu item links work | Click menu link | SPA navigation works | |
| 10.6 | Phantom-specific locations registered | Check `register_nav_menus` output | `phantom_primary`, `phantom_secondary`, `phantom_footer`, `phantom_mobile` | |
| 10.7 | Theme-compat locations registered | Check | `primary`, `footer` | |
| 10.8 | Custom menu items via REST API | POST /menu-locations | Menu assigned | |

---

## 11. Widgets

| # | Test | Method | Expected | Status |
|---|------|--------|----------|--------|
| 11.1 | All 10 widget areas registered | `Appearance → Widgets` | 10 areas visible | |
| 11.2 | Phantom areas: sidebar-main | Check widgets page | `phantom-sidebar-main` present | |
| 11.3 | Phantom areas: sidebar-shop | Check widgets page | `phantom-sidebar-shop` present | |
| 11.4 | Phantom areas: sidebar-blog | Check widgets page | `phantom-sidebar-blog` present | |
| 11.5 | Phantom areas: footer 1-4 | Check widgets page | `phantom-footer-1` through `phantom-footer-4` present | |
| 11.6 | Default widgets populated | Check each sidebar | Default widgets active | |
| 11.7 | Sidebar renders on frontend blog | `GET /blog` | Sidebar visible | |
| 11.8 | Footer widgets render | `GET /` | 4 footer columns with widgets | |
| 11.9 | Widget content is dynamic | Add custom widget text → frontend | Text displays | |

---

## 12. CSS Generation Engine

| # | Test | Method | Expected | Status |
|---|------|--------|----------|--------|
| 12.1 | `phantom-inline-css` present on frontend | View source | `<style id="phantom-inline-css">` | |
| 12.2 | Colors CSS vars output | Check CSS content | `--phantom-color-0` through `--phantom-color-8` | |
| 12.3 | Typography CSS vars output | Check CSS content | Font family/size vars | |
| 12.4 | Header CSS vars output | Check CSS content | Header height/spacing vars | |
| 12.5 | Footer CSS vars output | Check CSS content | Footer background/text vars | |
| 12.6 | Layout CSS vars output | Check CSS content | Container width/gap vars | |
| 12.7 | Buttons CSS vars output | Check CSS content | Button radius/padding vars | |
| 12.8 | Product CSS vars output | Check CSS content | Product card vars | |
| 12.9 | Responsive CSS vars output | Check CSS content | Breakpoint vars | |
| 12.10 | CSS changes reflect after Customizer save | Save color → check frontend | New color value in CSS | |
| 12.11 | Dynamic CSS filter `phantom_dynamic_css` fires | Check filter runs | Palette CSS + dark mode CSS appended | |

---

## 13. Global Color Palette

| # | Test | Method | Expected | Status |
|---|------|--------|----------|--------|
| 13.1 | 4 presets available | `Phantom_Global_Palette::get_default_presets()` | `light`, `dark`, `vibrant`, `pastel` | |
| 13.2 | 9 colors per preset | Check each preset | 9 color keys | |
| 13.3 | `Light Default` active | Default state | Active on fresh install | |
| 13.4 | Switch to `Dark Mode` | Save palette option | Dark colors on frontend | |
| 13.5 | Switch to `Vibrant` | Save palette option | Vibrant colors on frontend | |
| 13.6 | Switch to `Pastel` | Save palette option | Pastel colors on frontend | |
| 13.7 | Color overrides via REST API | POST override → GET palette | Override applied | |
| 13.8 | Gutenberg palette registered | Check editor color palette | 9 editor colors | |
| 13.9 | Dark mode CSS via media query | `prefers-color-scheme: dark` | Dark CSS vars applied | |
| 13.10 | Palette CSS inlined on frontend | View source | Palette CSS in `phantom-inline-css` | |

---

## 14. Font System

| # | Test | Method | Expected | Status |
|---|------|--------|----------|--------|
| 14.1 | Google Fonts enqueued | View page source | `<link>` for Google Fonts | |
| 14.2 | System font fallback configured | Check CSS | `font-family` fallback chain | |
| 14.3 | Font download page loads | `GET /wp-admin/admin.php?page=phantom-font-download` | 200 | |
| 14.4 | Font download works | Submit font family | Font CSS downloaded | |
| 14.5 | Local font enqueued after download | Check enqueued styles | Local font stylesheet | |
| 14.6 | `Phantom_Font_Families` class loads | Check class exists | No fatal errors | |
| 14.7 | Font families registration order | Load order: Font_Families before Fonts | No dependency error | |

---

## 15. SPA Frontend

| # | Test | Method | Expected | Status |
|---|------|--------|----------|--------|
| 15.1 | All 22 HTML templates serve | Check `frontend/html/` | 22 `.html` files | |
| 15.2 | Index page renders hero | `GET /` | Hero section visible | |
| 15.3 | Shop page renders | `GET /shop` | Product grid visible | |
| 15.4 | Product detail page renders | `GET /product` | Product detail layout | |
| 15.5 | Blog archive renders | `GET /blog` | Blog grid/list | |
| 15.6 | Single blog renders | `GET /post` | Blog post layout | |
| 15.7 | About page renders | `GET /about` | About content | |
| 15.8 | Contact page renders | `GET /contact` | Contact form | |
| 15.9 | Cart page renders | `GET /cart` | Cart UI | |
| 15.10 | Checkout page renders | `GET /checkout` | Checkout UI | |
| 15.11 | Account page renders | `GET /my-account` | Account UI | |
| 15.12 | FAQ page renders | `GET /faq` | FAQ accordion | |
| 15.13 | Team page renders | `GET /team` | Team grid | |
| 15.14 | Testimonials page renders | `GET /testimonials` | Testimonials carousel | |
| 15.15 | Coming Soon page renders | `GET /coming-soon` | Coming soon layout | |
| 15.16 | Thank You page renders | `GET /thank-you` | Thank you content | |
| 15.17 | Wishlist page renders | `GET /wishlist` | Wishlist UI | |
| 15.18 | Privacy Policy page renders | `GET /privacy-policy` | Privacy content | |
| 15.19 | Terms of Use page renders | `GET /term-of-use` | Terms content | |
| 15.20 | Cookie Policy page renders | `GET /cookie-policy` | Cookie content | |
| 15.21 | 404 page renders | `GET /nonexistent` | 404 layout | |
| 15.22 | Login page renders | `GET /login` | Login form | |
| 15.23 | Register page renders | `GET /register` | Registration form | |
| 15.24 | PhantomBridge.js loads | Check `<script>` tag | Bridge JS loaded | |
| 15.25 | Phantom-data.js loads | Check `<script>` tag | Data JS loaded | |
| 15.26 | SPA navigation between pages | Click links | URL changes, content updates | |
| 15.27 | Browser back/forward work | Navigate → back → forward | Correct history states | |

---

## 16. WooCommerce — Frontend

| # | Test | Method | Expected | Status |
|---|------|--------|----------|--------|
| 16.1 | WooCommerce declared support | Check theme functions | `add_theme_support('woocommerce')` | |
| 16.2 | WC gallery features declared | Check | Zoom, lightbox, slider support | |
| 16.3 | Swiper gallery loads on product page | `GET /product` | Swiper JS/CSS enqueued | |
| 16.4 | Variable product display | View variable product | Variations selectable | |
| 16.5 | Add to cart from shop page | Click add to cart | Cart updated | |
| 16.6 | Product quantity update in cart | Cart page → change qty | Total recalculated | |
| 16.7 | Coupon input in cart | Enter coupon code | Discount applied | |
| 16.8 | Checkout flow | Fill checkout → submit | Order created | |
| 16.9 | Order confirmation | After checkout | Thank you page | |

---

## 17. Security

| # | Test | Method | Expected | Status |
|---|------|--------|----------|--------|
| 17.1 | CSRF nonce on admin save | Submit without nonce | 403 | |
| 17.2 | Capability check on admin page | Non-admin user | 403 | |
| 17.3 | REST API permission check | Subscriber user | 403 | |
| 17.4 | XSS — settings with script tag | Save `<script>alert(1)</script>` | Sanitized on output | |
| 17.5 | SQL injection — endpoint params | `1' OR '1'='1` | No SQL error | |
| 17.6 | Rate limiting | Rapid requests | 429 after threshold | |
| 17.7 | File upload validation | Non-image file | Rejected | |

---

## 18. PHP Error / Debug Log

| # | Test | Method | Expected | Status |
|---|------|--------|----------|--------|
| 18.1 | Frontend — no PHP errors | `GET /` | Debug log empty | |
| 18.2 | Customizer — no PHP errors | `GET /wp-admin/customize.php` | Debug log empty | |
| 18.3 | REST API — no PHP errors | `GET /wp-json/phantom/v1/settings` | Debug log empty | |
| 18.4 | Admin page — no PHP errors | `GET /wp-admin/themes.php?page=phantom-core` | Debug log empty | |
| 18.5 | All SPA templates — no errors | Navigate all 22 templates | Debug log empty | |
| 18.6 | WC endpoints — no errors | All WC REST calls | Debug log empty | |
| 18.7 | Customizer save — no errors | Save a setting | Debug log empty | |
| 18.8 | 404 route — no errors | `GET /nonexistent` | Debug log empty | |

---

## 19. Cache System

| # | Test | Method | Expected | Status |
|---|------|--------|----------|--------|
| 19.1 | Cache set/get works | `Cache::set('key', 'val')` → `Cache::get('key')` | `'val'` | |
| 19.2 | Cache TTL respected | Set with 1s TTL → wait 2s → get | `false` | |
| 19.3 | Cache delete works | Set → delete → get | `false` | |
| 19.4 | Cache flush works | Multiple entries → flush → verify empty | All deleted | |
| 19.5 | REST cache flush endpoint | POST `/cache/flush` | 200 | |

---

## 20. Performance & Assets

| # | Test | Method | Expected | Status |
|---|------|--------|----------|--------|
| 20.1 | CSS only loaded on needed pages | Check page-specific enqueues | No unused CSS | |
| 20.2 | JS only loaded on needed pages | Check page-specific enqueues | No unused JS | |
| 20.3 | Customizer assets only on customize.php | Check asset enqueue hooks | Admin-page only | |
| 20.4 | Admin page JS only on admin page | Check asset enqueue hooks | `appearance_page_phantom-core` only | |

---

## 21. Responsive Hero Media

| # | Test | Method | Expected | Status |
|---|------|--------|----------|--------|
| 21.1 | Desktop image setting exists | `Settings_Registry::get_entries()['hero_banner_image']` | Type `image`, default `''` | |
| 21.2 | Tablet image setting exists | `Settings_Registry::get_entries()['hero_image_tablet']` | Type `image`, default `''` | |
| 21.3 | Mobile image setting exists | `Settings_Registry::get_entries()['hero_image_mobile']` | Type `image`, default `''` | |
| 21.4 | Enable responsive toggle exists | `Settings_Registry::get_entries()['hero_enable_responsive']` | Type `ast-toggle`, default `1` | |
| 21.5 | Tablet breakpoint setting exists | `Settings_Registry::get_entries()['hero_tablet_breakpoint']` | Type `number`, default `1024` | |
| 21.6 | Mobile breakpoint setting exists | `Settings_Registry::get_entries()['hero_mobile_breakpoint']` | Type `number`, default `768` | |
| 21.7 | Image loading setting exists | `Settings_Registry::get_entries()['hero_loading']` | Type `ast-select`, default `auto` | |
| 21.8 | Hero fit setting exists | `Settings_Registry::get_entries()['hero_fit']` | Type `ast-select`, default `cover` | |
| 21.9 | Hero position setting exists | `Settings_Registry::get_entries()['hero_position']` | Type `ast-select`, default `center` | |
| 21.10 | Overlay opacity setting exists | `Settings_Registry::get_entries()['hero_overlay_opacity']` | Type `number`, default `50`, min 0, max 100 | |
| 21.11 | CSS vars in generated output | `Phantom_Custom_CSS::instance()->get_css()` | Contains `--hero-image`, `--hero-object-fit`, `--hero-overlay-opacity` | |
| 21.12 | Responsive media queries in CSS | Inspect CSS output | `@media (max-width:...` for tablet/mobile images | |
| 21.13 | Tablet image omitted from CSS when empty | Set tablet to `''` | No tablet `@media` block | |
| 21.14 | Mobile image omitted from CSS when empty | Set mobile to `''` | No mobile `@media` block | |
| 21.15 | Desktop fallback when responsive disabled | Toggle off `hero_enable_responsive` | Only desktop `--hero-image` output | |
| 21.16 | Customizer control visible | Open Customize → Hero Section | Tablet/Mobile image upload controls present | |
| 21.17 | Customizer preview updates desktop image | Change `hero_banner_image` in Customizer | Frontend `<img>` src updates live | |
| 21.18 | Customizer preview updates tablet image | Change `hero_image_tablet` in Customizer | `<source data-device="tablet">` srcset updates | |
| 21.19 | Customizer preview updates mobile image | Change `hero_image_mobile` in Customizer | `<source data-device="mobile">` srcset updates | |
| 21.20 | Customizer preview updates fit | Change `hero_fit` in Customizer | `--hero-object-fit` CSS var updates live | |
| 21.21 | Customizer preview updates position | Change `hero_position` in Customizer | `--hero-object-position` + `--hero-bg-position` update | |
| 21.22 | Customizer preview updates opacity | Change `hero_overlay_opacity` in Customizer | `--hero-overlay-opacity` updates live | |
| 21.23 | Partial refresh fires for desktop image | Change `hero_banner_image` | REST call to `/partial?partial=hero_banner_image` returns 200 | |
| 21.24 | Partial refresh fires for tablet image | Change `hero_image_tablet` | REST call to `/partial?partial=hero_image_tablet` returns 200 | |
| 21.25 | Partial refresh fires for mobile image | Change `hero_image_mobile` | REST call to `/partial?partial=hero_image_mobile` returns 200 | |
| 21.26 | `data-hero-area` attribute on hero section | Inspect `index.html` hero `<section>` | Has `data-hero-area` attribute | |
| 21.27 | Picture element wraps hero `<img>` | Inspect Swiper slides | `<picture>` > `<source>` + `<img class="hero-image">` | |
| 21.28 | `phantom-data.js` sets responsive CSS vars | Load page with tablet image set | `--hero-image-tablet` CSS var present | |
| 21.29 | REST API returns new hero settings | `GET /settings?page=9` | `hero_image_tablet`, `hero_image_mobile`, etc. present | |
| 21.30 | Debug log empty after hero settings saved | Save all hero settings | No PHP notices/warnings in debug.log | |

---

## Summary

| Section | Total Tests | Pass | Fail | Blocked | Notes |
|---------|------------|------|------|---------|-------|
| 1. WordPress Core | 10 | | | | |
| 2. Settings Registry | 10 | | | | |
| 3. REST Auth/Nonce | 7 | | | | |
| 4. REST Settings | 10 | | | | |
| 5. REST Partials | 7 | | | | |
| 6. REST WooCommerce | 18 | | | | |
| 7. REST Utility | 8 | | | | |
| 8. Theme Options | 12 | | | | |
| 9. Customizer | 12 | | | | |
| 10. Menus | 8 | | | | |
| 11. Widgets | 9 | | | | |
| 12. CSS Engine | 11 | | | | |
| 13. Color Palette | 10 | | | | |
| 14. Font System | 7 | | | | |
| 15. SPA Frontend | 27 | | | | |
| 16. WooCommerce Frontend | 9 | | | | |
| 17. Security | 7 | | | | |
| 18. Debug Log | 8 | | | | |
| 19. Cache | 5 | | | | |
| 20. Performance | 4 | | | | |
| 21. Responsive Hero Media | 30 | | | | |
| **Total** | **229** | | | | |
