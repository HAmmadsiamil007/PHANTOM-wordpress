# Phantom Core Framework — Agent Instructions

## Project State
- **Version**: 1.5.0
- **Plugin**: `phantom-core` — decoupled WordPress framework with static HTML SPA architecture
- **Settings**: 555 across 43 sections
- **Customizer**: 15 panels, 25+ sections, 13 custom control types
- **REST API**: 20+ endpoints under `phantom/v1`
- **HTML Templates**: 27 static templates in `frontend/`
- **JS Files**: 22 frontend + 11 customizer control files
- **CSS Modules**: 10 modular CSS generation files
- **WooCommerce**: Full integration with Store API, template overrides
- **Docker**: WordPress on port 8080, MySQL 8.0 on port 3307
- **Latest audit**: Pro-level — XSS fixes, CSS engine hardening, cache hardening, security

## Architecture
```
WordPress ─── WooCommerce
     │
Phantom Core Plugin
  ├── Settings Registry (555 settings)
  ├── Customizer (15 panels, 13 custom controls)
  ├── Admin Settings Page (tabbed UI)
  ├── REST API (phantom/v1 — 20+ endpoints)
  ├── CSS Generation Engine (10 modules)
  ├── Global Color Palette (4 presets, dark mode)
  ├── Font System (Google + system + local)
  └── Shell SPA Router (template_redirect → HTML)
       │
  Frontend (swappable)
  ├── 27 static HTML templates
  ├── PhantomBridge.js (REST API bridge)
  └── phantom-data.js (data injection)
```

## Key Files
| File | Purpose |
|------|---------|
| `phantom-core.php` | Plugin bootstrap, autoloader, constants |
| `includes/class-settings-registry.php` | 555 settings, 43 sections |
| `includes/class-customizer.php` | Customizer integration |
| `includes/class-rest-controller.php` | REST API (20+ endpoints) |
| `includes/class-custom-css.php` | CSS Generation Engine |
| `includes/class-phantom-global-palette.php` | 9-color palette system |
| `includes/class-phantom-font-families.php` | System + Google Fonts |
| `includes/class-phantom-webfont-loader.php` | Local font enqueue |
| `includes/custom-controls/` | 13 custom Customizer controls |
| `includes/custom-css/` | 10 CSS module files |
| `admin/js/customizer-preview.js` | Live preview bindings |
| `admin/js/customizer-conditionals.js` | Conditional display logic |
| `frontend/assets/js/phantom-bridge.js` | REST API bridge |
| `templates/shell.php` | SPA Router |

## Known Issues
1. Tests directory is empty — no PHPUnit suite yet
2. Docker COPY commands reference legacy optix-core/optix-main (update needed)
3. MySQL auth requires db_data volume reset on password change
4. REST API loopback fails in Docker (expected — no loopback interface)

## Development Workflow
```bash
# Push local changes to Docker
docker cp phantom-core optix_wordpress:/var/www/html/wp-content/plugins/phantom-core

# Pull from Docker
docker cp optix_wordpress:/var/www/html/wp-content/plugins/phantom-core ./phantom-core
```
