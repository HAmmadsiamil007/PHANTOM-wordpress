# Phantom Core Framework

A **decoupled WordPress framework** that replaces traditional PHP template hierarchy with a static HTML SPA architecture. Dynamic data is injected client-side via a custom REST API.

## Quick Stats

| Metric | Value |
|--------|-------|
| Version | 1.0.2 |
| Settings | 555 across 43 sections |
| Customizer Panels | 15 |
| REST API Endpoints | 20+ |
| HTML Templates | 27 |
| CSS Files | 7 |
| JS Files | 22 |
| Total Controls | 1,000–1,500+ |
| WooCommerce | Full integration |

## Architecture

```
WordPress Core ─── WooCommerce
       │                │
       └────────────────┘
              │
     Phantom Core Plugin
       │              │
  Settings Registry   │
  (555 settings)      │
       │              │
  ┌────┴────┬─────────┴──┐
  │         │            │
Customizer  Admin Page   REST API
(visual)    (form)       (programmatic)
  │         │            │
  └─────────┴────────────┘
              │
      Shell (SPA Router)
   template_redirect → HTML
              │
     ┌────────┴────────┐
     │                  │
  Static HTML       phantom-data.js
  Templates         (data injection)
```

## Key Concepts

- **Static HTML SPA** — No PHP templates. 27 static HTML files. All dynamic data via REST API
- **Attribute-based binding** — `data-phantom="key"` on HTML elements drives data injection
- **CSS Variable architecture** — Design tokens as CSS custom properties on `:root`
- **Three-way customization** — Customizer (visual) + Admin (form) + REST API (programmatic)
- **WooCommerce via Store API** — Modern cart/checkout integration

## Documentation

| File | Contents |
|------|----------|
| `ARCHITECTURE.md` | Complete system architecture, data flow, component relationships |
| `FEATURES.md` | Full feature list — WordPress, WooCommerce, Theme Settings |
| `CUSTOMIZATION.md` | 1,000+ controls guide — Customizer, Admin, REST API, CSS vars |
| `FRONTEND-GUIDE.md` | How to edit/replace frontend, data binding reference, WooCommerce hooks |

## Quick Start

```bash
# Theme is activated in WordPress
# Settings managed via:
# - Customizer: /wp-admin/customize.php
# - Admin: /wp-admin/themes.php?page=phantom-core-settings
# - REST API: /wp-json/phantom/v1

# To push local changes to Docker:
docker cp phantom-core optix_wordpress:/var/www/html/wp-content/plugins/phantom-core

# To pull from Docker:
docker cp optix_wordpress:/var/www/html/wp-content/plugins/phantom-core ./phantom-core
```

## Requirements

- WordPress 6.4+
- PHP 8.1+
- WooCommerce (optional, for shop features)
