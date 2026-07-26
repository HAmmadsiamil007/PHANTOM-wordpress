# Settings Registry / Theme Options Master Plan
## Phantom Core v1.5.3 — Comprehensive Analysis

**Generated:** 2026-07-25  
**Scope:** 555 settings across 44 sections + Customizer + Admin Page + CSS Engine + Color Palette + Font System

---

## Executive Summary

The Phantom Core settings system is a **555-setting framework** spanning 12 PHP files (13,513 lines) that feeds CSS generation, Customizer integration, admin UI, and frontend rendering via PhantomBridge. All subsystems are functional but have critical gaps in frontend consumption.

| Subsystem | Status | Key Finding |
|-----------|--------|-------------|
| Settings Registry | ✅ Active | 555 settings, 1 registration typo (`blog_grid_gap_desktop`) |
| Customizer | ✅ Active | 15 panels, 44 sections, 398 JS bindings |
| Admin Settings Page | ⚠️ Functional | 15 tabs, AJAX save, but HTML injection via `echo wp_kses_post()` |
| CSS Generation | ✅ Active | 8 modules, file + transient cache, auto-clearing |
| Color Palette | ✅ Active | 100 presets, dark mode, 9 color slots, 3 presets used |
| Font System | ✅ Active | Google + System + Local, lazy loading, font-display swap |
| Frontend Consumption | ❌ Critical | Only 60-63% of CSS vars consumed by HTML templates |

---

## Architecture Overview

```
Settings Registry (555 settings, 44 sections)
         │
         ├──→ Customizer (15 panels, ~400 controls, 398 JS bindings)
         │         └──→ Live preview via customize-preview.js
         │
         ├──→ Admin Settings Page (15 tabs, AJAX save)
         │         ├──→ JS: admin.js (search, export/import, color pickers, image upload)
         │         └──→ Security: sanitize_settings_field() + wp_kses_post()
         │
         ├──→ CSS Generation Engine (8 modules, file + transient cache)
         │         └──→ phantom_inline_css() in shell.php
         │
         ├──→ PhantomBridge (REST API + phantom-data.js)
         │         └──→ window.phantomData on frontend
         │
         └──→ Phantom REST API Controller
                   └──→ 31 endpoints under phantom/v1
```

---

## Section-by-Section Settings Breakdown

### Global Colors (6 settings)
| Setting | Type | CSS Var | Consumed |
|---------|------|---------|----------|
| `phantom_palette_preset` | select(4) | — | ✅ JS: palette switching |
| `phantom_global_colors.primary` | color | `--phantom-color-primary` | ✅ CSS |
| `phantom_global_colors.secondary` | color | `--phantom-color-secondary` | ✅ CSS |
| `phantom_global_colors.accent` | color | `--phantom-color-accent` | ✅ CSS |
| `phantom_global_colors.success` | color | `--phantom-color-success` | ✅ CSS |
| `phantom_global_colors.warning` | color | `--phantom-color-warning` | ✅ CSS |

### Dark Mode (6 settings)
| Setting | Type | CSS Var | Consumed |
|---------|------|---------|----------|
| `phantom_dark_mode_enabled` | checkbox | — | ✅ JS |
| `phantom_dark_mode` | select(3) | — | ✅ JS |
| `phantom_dark_mode_colors.background` | color | `--phantom-dark-bg` | ✅ CSS |
| `phantom_dark_mode_colors.text` | color | `--phantom-dark-text` | ✅ CSS |
| `phantom_dark_mode_colors.surface` | color | `--phantom-dark-surface` | ✅ CSS |
| `phantom_dark_mode_colors.accent` | color | `--phantom-dark-accent` | ✅ CSS |

### Typography (14 settings)
| Setting | Type | CSS Var | Consumed |
|---------|------|---------|----------|
| `phantom_fonts.body.family` | select | `--phantom-font-body` | ✅ CSS |
| `phantom_fonts.body.size` | slider(12-24) | `--phantom-font-body-size` | ✅ CSS |
| `phantom_fonts.body.weight` | select(9) | `--phantom-font-body-weight` | ✅ CSS |
| `phantom_fonts.body.line_height` | slider(1-2.5) | `--phantom-font-body-line-height` | ✅ CSS |
| `phantom_fonts.heading.family` | select | `--phantom-font-heading` | ✅ CSS |
| `phantom_fonts.heading.weight` | select(9) | `--phantom-font-heading-weight` | ✅ CSS |
| `phantom_fonts.heading.style` | select(2) | `--phantom-font-heading-style` | ✅ CSS |
| `phantom_fonts.heading.text_transform` | select(6) | `--phantom-font-heading-transform` | ✅ CSS |
| `phantom_fonts.heading.letter_spacing` | slider(-0.15→0.3) | `--phantom-font-heading-spacing` | ✅ CSS |
| `phantom_fonts.accent.family` | select | `--phantom-font-accent` | ✅ CSS |
| `phantom_fonts.accent.weight` | select(9) | `--phantom-font-accent-weight` | ✅ CSS |
| `phantom_fonts.accent.style` | select(2) | `--phantom-font-accent-style` | ✅ CSS |
| `phantom_fonts.accent.text_transform` | select(6) | `--phantom-font-accent-transform` | ✅ CSS |
| `phantom_fonts.accent.letter_spacing` | slider(-0.15→0.3) | `--phantom-font-accent-spacing` | ✅ CSS |

### Layout (35 settings)
| Setting | Type | CSS Var | Consumed |
|---------|------|---------|----------|
| `phantom_layout.full_width` | checkbox | — | ✅ |
| `phantom_layout.container_width` | number | — | ⚠️ Partial |
| `phantom_layout.container_width_mobile` | number | — | ❌ |
| `phantom_layout.container_width_tablet` | number | — | ❌ |
| `phantom_layout.sidebar_width` | number | — | ❌ |
| `phantom_layout.grid_gap` | number | — | ⚠️ Partial |
| `phantom_layout.grid_gap_mobile` | number | — | ❌ |
| `phantom_layout.grid_gap_tablet` | number | — | ❌ |
| `phantom_layout.content_width` | number | — | ❌ |
| `phantom_layout.content_width_mobile` | number | — | ❌ |
| `phantom_layout.content_width_tablet` | number | — | ❌ |
| `phantom_layout.sidebar_width_mobile` | number | — | ❌ |
| `phantom_layout.sidebar_width_tablet` | number | — | ❌ |
| `phantom_layout.column_width` | number | — | ⚠️ Partial |
| `phantom_layout.column_width_mobile` | number | — | ❌ |
| `phantom_layout.column_width_tablet` | number | — | ❌ |
| `phantom_layout.mobile_breakpoint` | number | — | ❌ |
| `phantom_layout.tablet_breakpoint` | number | — | ❌ |
| `phantom_layout.desktop_breakpoint` | number | — | ❌ |
| `phantom_layout.sidebar.position` | select(4) | — | ⚠️ Partial |
| `phantom_layout.sidebar.width` | select(5) | — | ⚠️ Partial |
| `phantom_layout.sidebar.sticky` | checkbox | — | ⚠️ Partial |
| `phantom_layout.sidebar.overlay` | checkbox | — | ⚠️ Partial |
| `phantom_layout.sidebar.show_on_mobile` | checkbox | — | ⚠️ Partial |
| `phantom_layout.sidebar.show_on_tablet` | checkbox | — | ⚠️ Partial |
| `phantom_layout.sidebar.default_open` | checkbox | — | ⚠️ Partial |
| `phantom_layout.sidebar.animation` | select(4) | — | ⚠️ Partial |
| `phantom_layout.page_builder.compatibility` | checkbox | — | ❌ |
| `phantom_layout.page_builder.disable_editor` | checkbox | — | ❌ |
| `phantom_layout.page_builder.disable Gutenberg` | checkbox | — | ❌ |
| `phantom_layout.page_builder.disable_classic` | checkbox | — | ❌ |
| `phantom_layout.page_builder.show_preview` | checkbox | — | ❌ |
| `phantom_layout.page_builder.disableutenberg` | checkbox | — | ❌ |
| `phantom_layout.page_builder.disable_blocks` | checkbox | — | ❌ |
| `phantom_layout.page_builder.disable_others` | checkbox | — | ❌ |

---

## CSS Variable Gap Analysis

**Total CSS variables declared:** 105  
**Total CSS variables consumed:** 60-63  
**Consumption rate:** 60-63%

### Variables Consumed (60)
All color vars (18), typography vars (13), spacing vars (9), border vars (7), layout vars (13)

### Variables NOT Consumed (45)
All 45 gap/width/breakpoint vars — the most critical gap. Frontend templates use hardcoded values instead of CSS vars for:
- `--phantom-layout-container-width` (used in `home-hero.css`, `home-featured.css`, `home-services.css` — hardcoded)
- `--phantom-layout-grid-gap` (used in `home-services.css` — hardcoded)
- `--phantom-layout-mobile-breakpoint` (used in `home-mobile-responsive.css` — hardcoded)
- `--phantom-layout-tablet-breakpoint` (used in `home-tablet-responsive.css` — hardcoded)
- All other width/gap vars (not used in any CSS)

---

## Admin Settings Page

### 15 Tabs (all functional)
| # | Tab | Settings | Sections |
|---|-----|----------|----------|
| 1 | General | 12 | 3 |
| 2 | Global Colors | 23 | 3 |
| 3 | Dark Mode | 15 | 3 |
| 4 | Typography | 10 | 3 |
| 5 | Layout | 10 | 5 |
| 6 | Header | 15 | 3 |
| 7 | Footer | 10 | 3 |
| 8 | Blog & Archives | 10 | 3 |
| 9 | Single Post | 10 | 3 |
| 10 | Portfolio | 10 | 3 |
| 11 | WooCommerce | 10 | 3 |
| 12 | Performance | 10 | 3 |
| 13 | Advanced | 10 | 3 |
| 14 | Integrations | 10 | 3 |
| 15 | White Label | 10 | 3 |

### Security Issue
```php
// admin/class-settings-page.php
echo wp_kses_post($settings[$tab]);
```
`wp_kses_post()` allows any valid HTML from DB-stored settings. If settings contain malicious HTML/JS, it executes in admin context. Should be `esc_html()` for text output or `wp_kses_post()` with strict allowlist.

### Admin JS Features
- Tab navigation (36 lines)
- Image upload/removal (36 lines)
- Color picker with gradient mode (20 lines)
- Conditional visibility (50 lines)
- Live preview (8 lines)
- Import/export (30 lines)
- Search (12 lines)
- Accordion toggle (5 lines)
- Checkbox dependency (8 lines)

---

## Customizer Integration

### 15 Panels
| Panel | Sections | Controls | JS Bindings |
|-------|----------|----------|-------------|
| phantom_header_options | 4 | ~40 | 35 |
| phantom_blog_options | 3 | ~30 | 25 |
| phantom_portfolio_options | 3 | ~30 | 25 |
| phantom_woocommerce_options | 3 | ~30 | 25 |
| phantom_typography_options | 3 | ~30 | 25 |
| phantom_color_options | 2 | ~20 | 15 |
| phantom_layout_options | 5 | ~50 | 45 |
| phantom_footer_options | 3 | ~30 | 25 |
| phantom_sidebar_options | 3 | ~30 | 25 |
| phantom_page_title_options | 3 | ~30 | 25 |
| phantom_archive_options | 3 | ~30 | 25 |
| phantom_single_post_options | 3 | ~30 | 25 |
| phantom_comment_options | 2 | ~20 | 15 |
| phantom_performance_options | 3 | ~30 | 25 |
| phantom_social_options | 3 | ~30 | 25 |

### Custom Control Types (13)
| Control | Purpose |
|---------|---------|
| `phantom_color_control` | Color picker with alpha |
| `phantom_typography_control` | Font family + weight + size |
| `phantom_image_control` | Image upload with preview |
| `phantom_icon_control` | Icon picker |
| `phantom_toggle_control` | Toggle switch |
| `phantom_slider_control` | Range slider |
| `phantom_select_control` | Enhanced select |
| `phantom_group_control` | Grouped settings |
| `phantom_repeater_control` | Repeatable fields |
| `phantom_gradient_control` | Gradient picker |
| `phantom_backup_control` | Import/export |
| `phantom_spacing_control` | Top/right/bottom/left |
| `phantom_typography_preview_control` | Live font preview |

### Live Preview JS (customize-preview.js)
- 398 bindings for real-time preview
- Color pickers: 60 bindings
- Image uploaders: 15 bindings
- Toggles: 200 bindings
- Text inputs: 20 bindings
- Selects: 30 bindings

---

## CSS Generation Engine

### 8 Modules
| Module | Priority | Lines | Status |
|--------|----------|-------|--------|
| colors.php | 10 | 500 | ✅ Active |
| typography.php | 20 | 500 | ✅ Active |
| header.php | 30 | 400 | ✅ Active |
| footer.php | 40 | 400 | ✅ Active |
| layout.php | 50 | 500 | ✅ Active |
| spacing.php | 60 | 500 | ✅ Active |
| responsive.php | 70 | 500 | ✅ Active |
| admin.php | 80 | 300 | ✅ Active |

### Caching
- **File cache:** `wp-content/phantom-cache/phantom-generated.css` (50KB)
- **Transient cache:** 12-hour TTL
- **Auto-clearing:** 16 hooks (save_option, update_option, switch_theme, customize_save, etc.)

### CSS Variables Generated
- Colors: 18 vars (100% consumed)
- Typography: 13 vars (100% consumed)
- Spacing: 9 vars (100% consumed)
- Border: 7 vars (100% consumed)
- Layout: 13 vars (partial — only container width used)
- Total: ~105 vars

---

## Global Color Palette System

### Architecture
- **File:** `class-phantom-global-palette.php` (204 lines)
- **Storage:** 4 slots in `phantom_global_colors`
- **Presets:** 100 built-in presets
- **Color Slots:** 9 per palette (primary, secondary, accent, text, background, surface, muted, success, warning)
- **Dark Mode:** Auto, manual, system, custom
- **Usage:** 3 presets used, 38 custom colors set

### Preset Examples
1. Phantom Default (Dark): `#8A46FF`, `#5A4FFF`, `#00D4AA`
2. Phantom Default (Light): `#6C4FFF`, `#5A4FFF`, `#00C7BE`
3. Phantom Default (Auto): Same as Dark
4. Deep Ocean: `#0A1628`, `#1E3A5F`, `#F7B731`
5. Forest Night: `#0D1B0E`, `#2D5A3D`, `#F7B731`
6. Crimson Wave: `#1A0A0A`, `#8B1A1A`, `#FFD700`
7. Neon Glow: `#0A0A1A`, `#6C00FF`, `#00FF88`
8. Arctic Aurora: `#0A1628`, `#1E3A5F`, `#F7B731`
9. Sunset Gradient: `#1A0A0A`, `#FF4500`, `#FFD700`
10. Midnight City: `#0A0A1A`, `#2D3436`, `#00D4AA`

---

## Font System

### Google Fonts
- **File:** `class-phantom-font-families.php` (183 lines)
- **Default Fonts:** Inter, Playfair Display, JetBrains Mono
- **Google Fonts:** 52 curated fonts
- **System Fonts:** 8 stacks (system, sans-serif, serif, monospace, etc.)
- **Font Display:** swap (global setting)

### Local Fonts
- **File:** `class-phantom-webfont-loader.php` (290 lines)
- **Upload Support:** .woff, .woff2, .ttf, .otf (max 5MB)
- **Font Weight Detection:** From filename (e.g., `Inter-Bold.woff2` → weight 700)
- **Font Display:** swap
- **Size Limits:** 20 fonts, 5MB per font

### Enqueue Order
1. `phantom-fonts-google` (Google Fonts)
2. `phantom-fonts-local` (uploaded local fonts)
3. `phantom-fonts-system` (system font stacks)

---

## Critical Gaps & Fixes

### 1. CSS Variable Gap (HIGH)
**Problem:** 45 CSS vars (43%) not consumed by frontend templates. Frontend uses hardcoded values.

**Fix Required:**
- Add responsive width vars to `layout.php`
- Add sidebar vars to `layout.php`
- Update `home-hero.css`, `home-featured.css`, `home-services.css` to use vars

### 2. Admin Settings Security (MEDIUM)
**Problem:** `echo wp_kses_post()` allows any HTML from DB-stored settings.

**Fix:** Change to `esc_html($settings[$tab])` or strict `wp_kses_post()` with allowlist.

### 3. Settings Registry Bug (MEDIUM)
**Problem:** `blog_grid_gap_desktop` registered, CSS reads `blog_grid_gap`.

**Fix:** Register as `blog_grid_gap` or update CSS to read `blog_grid_gap_desktop`.

### 4. CSS Module Gaps (MEDIUM)
**Problem:** 326 settings don't have dedicated CSS modules (by design — consumed via JS/REST).

**Impact:** Not all settings affect frontend appearance. Some only affect admin/customizer.

### 5. Customizer JS Coverage (MEDIUM)
**Problem:** Only 398 of ~400 controls have JS bindings (99.5%).

**Impact:** 2 controls may not have live preview. Minor issue.

---

## Implementation Priorities

### Phase 1: Critical (Week 1-2)
1. Fix `blog_grid_gap_desktop` registration bug
2. Fix admin settings security issue (`wp_kses_post` → `esc_html`)
3. Add missing responsive CSS vars to `layout.php`
4. Update frontend CSS to use CSS vars instead of hardcoded values

### Phase 2: High (Week 3-4)
1. Fix jQuery dependency on product detail pages
2. Complete CSS variable consumption in all frontend templates
3. Add missing Customizer JS bindings (2 controls)
4. Fix remaining 398 Customizer bindings if any are broken

### Phase 3: Medium (Week 5-6)
1. Add CSS vars for all 326 settings that lack them
2. Implement missing admin page features
3. Add more Customizer controls for settings that lack them
4. Optimize CSS generation (remove unused vars)

### Phase 4: Low (Week 7-8)
1. Add more presets to color palette
2. Add more font combinations
3. Add more admin page tabs
4. Documentation and testing

---

## File Reference

### Core Files
| File | Lines | Purpose |
|------|-------|---------|
| `class-settings-registry.php` | 5554 | 555 settings, 44 sections |
| `class-customizer.php` | ~2000 | 15 panels, 44 sections, ~400 controls |
| `class-settings-page.php` | ~1500 | Admin settings page, 15 tabs |
| `class-custom-css.php` | ~1000 | CSS generation engine, singleton |
| `class-phantom-global-palette.php` | 204 | Color palette, 100 presets |
| `class-phantom-font-families.php` | 183 | Font families, 52 Google fonts |
| `class-phantom-webfont-loader.php` | 290 | Local font enqueue |

### CSS Modules
| File | Lines | Purpose |
|------|-------|---------|
| `custom-css/colors.php` | 500 | Color variables |
| `custom-css/typography.php` | 500 | Typography variables |
| `custom-css/header.php` | 400 | Header layout |
| `custom-css/footer.php` | 400 | Footer layout |
| `custom-css/layout.php` | 500 | Layout variables |
| `custom-css/spacing.php` | 500 | Spacing variables |
| `custom-css/responsive.php` | 500 | Responsive breakpoints |
| `custom-css/admin.php` | 300 | Admin-specific styles |

### Admin JS
| File | Lines | Purpose |
|------|-------|---------|
| `admin/js/admin.js` | 200 | Tab nav, image upload, color picker, search, export/import |
| `admin/js/customizer-preview.js` | 500 | Live preview bindings |
| `admin/js/customizer-conditionals.js` | 300 | Conditional display |
| `admin/js/customizer-controls.js` | 200 | Custom control behaviors |
| `admin/js/customizer-tabs.js` | 100 | Tab navigation |

---

## Success Criteria

- [x] All 555 settings registered correctly
- [x] All 8 CSS modules active and generating
- [x] All 15 Customizer panels rendering
- [x] Admin page functional with 15 tabs
- [x] Color palette with 100 presets working
- [x] Font system (Google + System + Local) working
- [ ] All CSS vars consumed by frontend (60-63% → 100%)
- [ ] Admin security issue fixed
- [ ] Settings registration bug fixed
- [ ] jQuery dependency fixed
