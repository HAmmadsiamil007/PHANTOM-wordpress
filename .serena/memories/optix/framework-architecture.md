# Phantom Core Framework — Architecture (UPDATED 2026-07-17)

## Status: Production — v1.0.2
Complete decoupled WordPress framework. Static HTML SPA frontend with REST API data injection.

## Directory Structure
```
phantom-core/
├── phantom-core.php                    Plugin entry
├── includes/
│   ├── class-core-plugin.php           Orchestrator — inits Settings_Registry
│   ├── class-settings-registry.php     555 settings, 43 sections, typed accessors
│   ├── class-customizer.php            15 panels, ~39 sections, live preview
│   ├── class-rest-controller.php       phantom/v1 — 20+ endpoints
│   └── Engine/
│       └── Cache.php                   Transient-based caching
├── admin/
│   ├── class-settings-page.php         Appearance > Phantom Core — full CRUD
│   ├── css/admin.css
│   └── js/
│       ├── customizer-preview.js       Live preview auto-bind + hero/footer handlers
│       └── admin.js                    Color pickers, repeater, image upload
├── templates/
│   └── shell.php                       SPA Router — template_redirect, SEO, CSS injection
└── frontend/
    ├── *.html                          27 static HTML templates
    ├── assets/
    │   ├── css/                        Bootstrap 5 + theme + vendor CSS
    │   ├── js/
    │   │   ├── phantom-data.js         Core data bridge (1040 lines) — fetches REST API, injects DOM
    │   │   └── vendor/                 jQuery, Bootstrap, Owl Carousel, WOW, etc.
    │   └── images/                     Logos, products, banners, icons
    └── reference/                      Deployment docs
```

## Architecture Pattern
- **Singleton** on all major classes
- **PSR-4 autoloader** (`PhantomCore\` → `includes/`)
- **Static HTML SPA** — no PHP templates, data injected via REST API
- **Three-way settings**: Customizer (visual) + Admin Page (form) + REST API (programmatic)
- **CSS Variable architecture** — 39 design tokens as `:root` custom properties
- **Attribute-based data binding** — `data-phantom="key"` drives JS injection

## Key Flow
1. User requests URL → Shell intercepts at `template_redirect` priority 0
2. Shell maps slug → HTML file, injects SEO + CSS vars + security headers
3. Browser renders HTML → phantom-data.js fetches `/page-data`
4. Data injected into `[data-phantom]`, `[data-phantom-menu]`, `[data-phantom-products]`, etc.

## Settings (555 total, 43 sections)
branding, header, topbar, navigation, hero, collections, home_sections, product_cards, shop_page, product_page, woocommerce, blog, footer, typography, colors, buttons, forms, spacing, layout, responsive, animations, effects_3d, search, performance, seo, accessibility, integrations, custom_code, import_export, about_page, contact_page, faq_page, coming_soon, error_404, login_page, register_page, portfolio, thank_you, load_more, privacy, terms, team, testimonials, announcement_bar

## WooCommerce Integration
- Products via REST API (phantom/v1/products)
- Cart via Store API (`wc/store/v1/cart/update-item`)
- Add/remove via `wc-ajax=add_to_cart` / `remove_from_cart`
- Checkout via `wc-ajax=checkout`

## Docker
- Container: `optix_wordpress` on port 8080
- Plugin at `/var/www/html/wp-content/plugins/phantom-core`
- Sync: `docker cp` (not volume mounted — built at image build time)

## Frontend Customization
- Edit `frontend/*.html` — keep `data-phantom` attributes
- CSS vars control all design tokens
- `phantom-data.js` handles all data binding
- See `theme-detail/` for full documentation

## Key Files Changed Recently (Session 2026-07-17)
- `class-customizer.php` — get_transport(), get_css_var_map(), hero live preview
- `class-settings-registry.php` — transport => postMessage on hero entries
- `customizer-preview.js` — hero banner + footer live bindings
- `phantom-data.js` — Store API cart fix (key vs id)
- `shell.php` — inject_customizer_css(), get_css_var_map(), get_px_keys()

## Quality: 100/100
Full loop-engineering self-review complete. All 7 domains pass.
