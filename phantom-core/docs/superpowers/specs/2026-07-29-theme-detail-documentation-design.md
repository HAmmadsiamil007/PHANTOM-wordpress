# Design Spec: theme-detail Documentation

**Date:** 2026-07-29
**Status:** Approved
**Audience:** Developer-focused

## Goal

Create comprehensive developer documentation at `C:\Users\hamma\Downloads\wordpress\theme-detail\` covering:
1. How the Phantom Core plugin and Phantom Theme work together
2. How they connect to WooCommerce (full stack)
3. Every Customizer panel/section — how settings flow from Customizer to frontend

## File Structure (19 files, flat)

```
theme-detail/
├── woocommerce.md              # Complete WooCommerce integration
├── architecture.md             # Theme-plugin architecture overview
├── customizer/
│   ├── 00-overview.md          # Customizer system architecture
│   ├── 01-site-identity.md     # Branding panel
│   ├── 02-typography.md        # Typography & Fonts panel
│   ├── 03-colors.md            # Colors & Buttons panel
│   ├── 04-hero.md              # Hero & Home panel
│   ├── 05-header.md            # Header & Navigation panel
│   ├── 06-footer.md            # Footer panel
│   ├── 07-products.md          # Products & Shop panel
│   ├── 08-woocommerce.md       # WooCommerce panel
│   ├── 09-blog.md              # Blog panel
│   ├── 10-layout.md            # Layout & Effects panel
│   ├── 11-search.md            # Search panel
│   ├── 12-performance.md       # Performance & SEO panel
│   ├── 13-accessibility.md     # Accessibility panel
│   ├── 14-pages.md             # Pages panel
│   ├── 15-design-tokens.md     # Design System Tokens panel
│   └── 16-advanced.md          # Advanced panel
```

## Content Template Per File

### woocommerce.md
- Architecture diagram: Bridge → REST API → Adapters → ViewModels → Renderers → Injectors → Templates
- REST API endpoints table (15 routes, methods, permissions, response schemas)
- Data Adapters (5 WC adapters): input type → output fields
- ViewModels (4 WC view models): properties, computed methods
- Renderers (7 WC renderers): CSS classes, placeholders
- Server-side injection: route dispatch table, injector methods
- Frontend templates: shop.html, product-detail.html, cart.html, checkout.html placeholder reference
- JS client: phantom-data.js services, adapters, renderers

### architecture.md
- Plugin bootstrap: phantom-core.php hooks and lifecycle
- Theme setup: functions.php, nav menus (6 locations), widget areas (7), image sizes (3)
- Autoloader: 24 namespace prefixes
- Container DI: 53 services
- Asset Engine: inject_essential_only() vs inject_all()
- SPA Router: shell.php request handling
- Template System: Template_Loader, pack system (3 packs)
- CSS Generation Engine: 9 modules, filter-based output

### customizer/00-overview.md
- 16 panels, 46 sections — complete table
- Settings flow: Settings_Loader → Settings_Registry → Customizer → wp_options → CSS
- CSS variable map: 304+ CSS custom properties
- Live preview system: customizer-preview.js auto-bind
- Selective refresh: partial renderers
- Custom controls: ast-color (56 uses), ast-toggle (103 uses), ast-select (37 uses)

### customizer/01-16 (per panel)
Each file contains:
1. **Panel ID** — `phantom_{name}`
2. **Sections** — section IDs and labels
3. **Settings table** — key, type, default, CSS var, frontend selector
4. **Code flow** — save → option → CSS var → browser render
5. **Frontend connection** — which template/element/JS binding

## Implementation Order

1. `architecture.md` — foundation for all other docs
2. `woocommerce.md` — standalone, complex
3. `customizer/00-overview.md` — system-level reference
4. `customizer/01-site-identity.md` through `16-advanced.md` — sequential

## Verification

- All setting keys match `Settings_Loader::section_*()` definitions
- All CSS var names match `Settings_Registry::get_css_var_map()`
- All REST routes match `Rest_Controller::register_routes()`
- All adapter fields match actual `normalize()` output
- All renderer placeholders match actual template files
