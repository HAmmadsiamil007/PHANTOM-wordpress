# Phantom Core Customizer System — Architecture Overview

> Developer documentation for the Phantom Core Customizer integration.
> Covers panels, sections, settings registration, CSS variable generation,
> live preview, and the Design System overlay.

---

## Table of Contents

1. [System Architecture](#1-system-architecture)
2. [Data Flow](#2-data-flow)
3. [File Map](#3-file-map)
4. [Panels and Sections](#4-panels-and-sections)
5. [Settings Registration Pipeline](#5-settings-registration-pipeline)
6. [Settings Storage](#6-settings-storage)
7. [CSS Variable Map](#7-css-variable-map)
8. [Inline CSS Generation](#8-inline-css-generation)
9. [Transport and Live Preview](#9-transport-and-live-preview)
10. [Selective Refresh Partials](#10-selective-refresh-partials)
11. [Custom Controls](#11-custom-controls)
12. [Conditional Visibility](#12-conditional-visibility)
13. [CSS Generation Engine (Modules)](#13-css-generation-engine-modules)
14. [Design System Layer](#14-design-system-layer)
15. [REST API Endpoints](#15-rest-api-endpoints)
16. [Admin Settings Page](#16-admin-settings-page)
17. [AETHER Variable Mappings](#17-aether-variable-mappings)

---

## 1. System Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                      Settings_Loader                         │
│  (defines ~612 settings across 46 section methods)          │
└──────────────────────┬──────────────────────────────────────┘
                       │ get_all_sections()
                       ▼
┌─────────────────────────────────────────────────────────────┐
│                      Settings_Registry                       │
│  (merges, deduplicates, provides get/set/get_css_var_map)   │
│  Single source of truth for all setting metadata            │
└───────────┬────────────────────┬────────────────────────────┘
            │ get_entries()      │ get_css_var_map()
            ▼                    ▼
┌─────────────────────┐  ┌──────────────────────────────────┐
│     Customizer       │  │   Customizer (CSS var binding)    │
│  register() →        │  │   preview_js() → localize        │
│  WP_Customize_Manager│  │   PhantomCustomizer.cssVarMap     │
└─────────┬───────────┘  └──────────────────────────────────┘
          │
          ├── register_partials()  → selective refresh
          ├── preview_js()         → customizer-preview.js
          ├── controls_js()        → customizer-conditionals.js
          ├── sync_options()       → phantom_options bulk array
          ├── get_inline_css()     → <style> :root CSS
          └── output_inline_css()  → wp_head @ priority 100
```

The system has three entry points that consume the same data:

1. **WordPress Customizer** — native `customize_register` integration
2. **Admin Settings Page** — standalone tabbed UI (15 tabs)
3. **Design Studio** — REST API-driven token editor and preset manager

All three read from `Settings_Registry`, which delegates to `Settings_Loader` as
the single source of truth for setting definitions.

---

## 2. Data Flow

### Settings Definition → Customizer Registration

```
Settings_Loader::get_all_sections()
    └→ array of section_name => [key => entry]
        ↓
Settings_Registry::define_entries()
    └→ merges all sections, deduplicates (warns on collision)
        ↓
Settings_Registry::get_entries()
    └→ flat array: key => entry (each entry has section, type, default, label, etc.)
        ↓
Customizer::init()
    ├→ reads entries from Settings_Registry
    ├→ maps sections to panels via define_panels()
    └→ register() iterates entries, creates WP_Customize settings + controls
```

### Settings Save → Storage Sync

```
User saves Customizer
    ↓
WordPress saves individual options: phantom_{setting_key}
    ↓
customize_save_after hook fires
    ↓
Customizer::sync_options()
    ├→ reads all phantom_{key} options
    ├→ merges into phantom_options bulk array
    └→ update_option('phantom_options', $merged)
```

### Settings Read → CSS Output

```
get_inline_css()
    ├→ reads phantom_options (bulk)
    ├→ falls back to individual phantom_{key} options
    ├→ iterates CSS var map
    ├→ for each key: outputs --var:value;
    ├→ responsive values: desktop + @media tablet + @media mobile
    └→ returns <style id="phantom-customizer-css">:root{...}</style>

output_inline_css()
    └→ hooked to wp_head at priority 100
```

---

## 3. File Map

| File | Purpose |
|------|---------|
| `includes/Settings/class-settings-loader.php` | Defines all ~612 settings across 46 section methods (5526 lines) |
| `includes/class-settings-registry.php` | Merges entries, provides `get()` / `set()` / `get_css_var_map()` (443 lines) |
| `includes/class-customizer.php` | WP Customizer integration: panels, sections, controls, preview, CSS (586 lines) |
| `includes/partial-renderers.php` | Selective refresh render callbacks (199 lines) |
| `includes/custom-controls/class-control-base.php` | Abstract base for 12 custom control types |
| `includes/custom-controls/class-color-control.php` | `ast-color` — color picker with palette |
| `includes/custom-controls/class-toggle-control.php` | `ast-toggle` — ON/OFF switch |
| `includes/custom-controls/class-select-control.php` | `ast-select` — dropdown selector |
| `includes/custom-controls/class-responsive-slider-control.php` | `ast-responsive-slider` — responsive number |
| `includes/custom-controls/class-responsive-spacing-control.php` | `ast-responsive-spacing` — responsive spacing |
| `includes/custom-controls/class-typography-control.php` | `ast-typography` — font family/size/weight |
| `includes/custom-controls/class-radio-image-control.php` | `ast-radio-image` — image-based radio |
| `includes/custom-controls/class-gradient-control.php` | `ast-gradient` — gradient picker |
| `includes/custom-controls/class-color-group-control.php` | `ast-color-group` — multiple colors |
| `includes/custom-controls/class-background-control.php` | `ast-background` — background picker |
| `includes/custom-controls/class-border-control.php` | `ast-border` — border control |
| `admin/js/customizer-preview.js` | Preview JS: auto-bind CSS vars + manual bindings (390 lines) |
| `admin/js/customizer-conditionals.js` | Controls JS: dependency-based visibility (82 lines) |
| `admin/js/design-studio.js` | Design Studio AJAX: toast system, token editors, preset apply (336 lines) |
| `admin/class-settings-page.php` | Admin tabbed settings UI (794 lines) |
| `admin/class-design-studio-page.php` | Design Studio admin page with 9 tabs (178 lines) |
| `includes/class-rest-controller.php` | REST API: 6 design endpoints + partials (3617 lines) |
| `includes/custom-css/colors.php` | CSS module: colors (priority 10) |
| `includes/custom-css/typography.php` | CSS module: typography (priority 20) |
| `includes/custom-css/header.php` | CSS module: header (priority 30) |
| `includes/custom-css/footer.php` | CSS module: footer (priority 40) |
| `includes/custom-css/layout.php` | CSS module: layout (priority 50) |
| `includes/custom-css/buttons.php` | CSS module: buttons (priority 60) |
| `includes/custom-css/product.php` | CSS module: product cards (priority 70) |
| `includes/custom-css/responsive.php` | CSS module: breakpoints (priority 80) |
| `includes/custom-css/hero.php` | CSS module: hero media (priority 90) |
| `includes/Design/class-design-system-manager.php` | Design System orchestrator (127 lines) |
| `includes/Design/class-token-registry.php` | Token definitions loader |
| `includes/Design/class-token-resolver.php` | Token value resolution (core → demo → user) |
| `includes/Design/class-token-compiler.php` | Compiles resolved tokens into CSS var set |
| `includes/Design/class-css-variable-generator.php` | Generates CSS from compiled tokens |
| `includes/Design/class-preset-registry.php` | Manages preset providers |
| `includes/Design/class-preset-manager.php` | Applies presets (sets option values) |
| `includes/Design/class-theme-dna-engine.php` | Theme DNA personality dimensions |
| `includes/Design/data/token-definitions.php` | Raw token definitions (168 tokens) |

---

## 4. Panels and Sections

The Customizer has **16 panels** containing **46 sections**. Each panel maps to
a group of related sections. Panels are defined in `Customizer::define_panels()`.

### Panel Priority Order

| # | Panel ID | Title | Priority | Sections |
|---|----------|-------|----------|----------|
| 1 | `phantom_design` | Design System | 5 | `design_tokens` |
| 2 | `phantom_branding` | Branding | 10 | `branding` |
| 3 | `phantom_header` | Header & Navigation | 20 | `header`, `topbar`, `navigation`, `announcement_bar` |
| 4 | `phantom_hero` | Hero & Home | 30 | `hero`, `home_sections`, `collections` |
| 5 | `phantom_products` | Products & Shop | 40 | `product_cards`, `shop_page`, `product_page` |
| 6 | `phantom_woocommerce` | WooCommerce | 50 | `woocommerce` |
| 7 | `phantom_blog` | Blog | 60 | `blog` |
| 8 | `phantom_footer` | Footer | 70 | `footer` |
| 9 | `phantom_typography` | Typography & Fonts | 80 | `typography` |
| 10 | `phantom_colors` | Colors & Buttons | 90 | `colors`, `buttons`, `forms`, `spacing` |
| 11 | `phantom_layout` | Layout & Effects | 100 | `layout`, `responsive`, `animations`, `effects_3d` |
| 12 | `phantom_search` | Search | 110 | `search` |
| 13 | `phantom_performance` | Performance & SEO | 120 | `performance`, `seo` |
| 14 | `phantom_accessibility` | Accessibility | 130 | `accessibility` |
| 15 | `phantom_advanced` | Advanced | 140 | `integrations`, `custom_code`, `import_export` |
| 16 | `phantom_pages` | Pages | 150 | `about_page`, `contact_page`, `faq_page`, `coming_soon`, `error_404`, `login_page`, `register_page`, `portfolio`, `thank_you`, `load_more`, `privacy`, `terms`, `team`, `testimonials` |

Section IDs in the Customizer follow the pattern `phantom_section_{slug}`.
The `design_tokens` section gets a special description with a link to Design Studio.

### Section Label Mapping

Labels are resolved in `Customizer::get_section_label()` (`class-customizer.php:431-479`).
Falls back to `ucfirst(str_replace('_', ' ', $slug))` if no explicit label is defined.

---

## 5. Settings Registration Pipeline

### Step 1: Settings_Loader defines sections

Each `section_*()` method returns an associative array:

```php
// includes/Settings/class-settings-loader.php
private function section_branding(): array {
    return array(
        'general_site_logo' => array(
            'section'   => 'branding',      // maps to Customizer section
            'type'      => 'image',          // control type
            'default'   => '',               // default value
            'sanitize'  => 'esc_url_raw',    // sanitize callback
            'label'     => 'Site Logo',
        ),
        // ...more settings
    );
}
```

`get_all_sections()` aggregates all 46+ section methods into a single array.

### Step 2: Settings_Registry merges and deduplicates

```php
// includes/class-settings-registry.php:85-118
protected function define_entries(): array {
    $sections = $loader->get_all_sections();
    foreach ($sections as $name => $section_entries) {
        foreach ($section_entries as $key => $entry) {
            if (isset($keys_seen[$key]) && 'design_tokens' !== $name) {
                _doing_it_wrong(/* ... */);
            }
            $keys_seen[$key] = $name;
            $merged[$key] = $entry;
        }
    }
    return $merged;
}
```

The `design_tokens` section is exempt from duplicate warnings because it
dynamically generates entries from token definitions and may intentionally
override entries from other sections.

### Step 3: Customizer::register() creates WP_Customize settings

For each entry, the Customizer creates:
- A **setting**: `phantom_{key}` with `type => 'option'`
- A **control**: mapped by type to standard WP or custom control class
- Transport: `postMessage` if key exists in CSS var map, else `refresh`

```php
// includes/class-customizer.php:160-183
$wp_customize->add_setting($setting_id, array(
    'default'           => $default,
    'type'              => 'option',
    'sanitize_callback' => $this->get_sanitize_callback($entry),
    'transport'         => $this->get_transport($key, $entry),
    'capability'        => 'edit_theme_options',
));
$this->add_control($wp_customize, $key, $entry, $section_id, $setting_id, $control_priority);
```

### Entry Schema

| Key | Type | Required | Description |
|-----|------|----------|-------------|
| `section` | `string` | Yes | Section slug (maps to Customizer section) |
| `type` | `string` | Yes | Control type (`ast-color`, `ast-toggle`, `text`, `bool`, etc.) |
| `default` | `mixed` | Yes | Default value |
| `label` | `string` | Yes | Human-readable label |
| `sanitize` | `callable\|string` | No | Sanitize callback (default: `sanitize_text_field`) |
| `options` | `array` | No | Choices for select/radio controls |
| `desc` | `string` | No | Description text |
| `css_var` | `string` | No | CSS variable name (auto-set for design tokens) |
| `transport` | `string` | No | `'postMessage'` or `'refresh'` (auto-detected) |
| `responsive` | `bool` | No | Whether value is `{desktop, tablet, mobile}` object |
| `partial` | `array` | No | Selective refresh config: `{selector, render_callback}` |
| `divider` | `array` | No | Divider display config |
| `dependencies` | `array` | No | Conditional visibility rules |
| `min`/`max`/`step` | `numeric` | No | Numeric constraints |
| `rows` | `int` | No | Textarea rows (default: 4-5) |
| `code_type` | `string` | No | Code editor language (default: `text/html`) |

---

## 6. Settings Storage

Phantom Core uses a dual storage strategy:

### Individual Options (Customizer native)

Each setting is stored as a separate `wp_options` row:
- **Key**: `phantom_{setting_key}`
- **Value**: serialized or scalar
- **Type**: `option` (not `theme_mod`)

### Bulk Storage (Shell SPA + inline CSS)

All settings are also mirrored into a single array option:
- **Key**: `phantom_options`
- **Value**: `array` — `['setting_key' => value, ...]`

### Sync Mechanism

`Customizer::sync_options()` runs on `customize_save_after`:

```php
// includes/class-customizer.php:522-538
public function sync_options(): void {
    $options = get_option('phantom_options', array());
    foreach (array_keys($entries) as $key) {
        $value = get_option('phantom_' . $key, null);
        if (null !== $value && $options[$key] !== $value) {
            $options[$key] = $value;
            $changed = true;
        }
    }
    if ($changed) {
        update_option('phantom_options', $options, false);
    }
}
```

### Read Order

When reading a setting value (in `Settings_Registry::get()`):

1. Check `phantom_options[$key]` (bulk array)
2. Fall back to `phantom_{key}` (individual option)
3. Fall back to schema `default`

This ensures the Customizer and the Shell SPA always see the same values.

---

## 7. CSS Variable Map

The CSS variable map is the bridge between setting keys and CSS custom properties.
It is defined as a static array in `Settings_Registry::get_css_var_map()` and
dynamically expanded by the Design System token layer.

**File**: `includes/class-settings-registry.php:199-352`

### Static Map (~137 entries)

#### Layout (7)
| Setting Key | CSS Variable |
|-------------|--------------|
| `container_width` | `--container-width` |
| `content_width` | `--content-width` |
| `sidebar_width` | `--sidebar-width` |
| `layout_boxed_width` | `--boxed-width` |
| `layout_columns` | `--layout-columns` |
| `container_gutter` | `--container-gutter` |
| `content_gap` | `--content-gap` |

#### Typography — Body (6)
| Setting Key | CSS Variable |
|-------------|--------------|
| `typography_body_font` | `--font-body` |
| `typography_body_weight` | `--font-body-weight` |
| `typography_body_style` | `--font-body-style` |
| `typography_base_size` | `--font-base-size` |
| `typography_line_height` | `--font-line-height` |
| `typography_body_spacing` | `--font-body-spacing` |

#### Typography — Heading (4)
| Setting Key | CSS Variable |
|-------------|--------------|
| `typography_heading_font` | `--font-heading` |
| `typography_heading_weight` | `--font-heading-weight` |
| `typography_heading_case` | `--font-heading-case` |
| `typography_heading_spacing` | `--font-heading-spacing` |

#### Typography — H1–H6 (42)
Each heading level (h1–h6) has 7 properties:
- `{level}_size` → `--h{n}-size`
- `{level}_height` → `--h{n}-height`
- `{level}_font` → `--h{n}-font`
- `{level}_weight` → `--h{n}-weight`
- `{level}_style` → `--h{n}-style`
- `{level}_spacing` → `--h{n}-spacing`
- `{level}_case` → `--h{n}-case`

#### Colors (21)
| Setting Key | CSS Variable |
|-------------|--------------|
| `color_primary` | `--primary--color` |
| `color_secondary` | `--secondary--color` |
| `color_accent` | `--accent--color` |
| `color_background` | `--bg` |
| `color_text` | `--text--color` |
| `color_heading` | `--heading--color` |
| `color_link` | `--link` |
| `color_link_hover` | `--link--hover` |
| `color_border` | `--border--color` |
| `color_sale` | `--sale--color` |
| `color_light_bg` | `--light--bg--color` |
| `color_grey` | `--grey--color` |
| `color_success` | `--success--color` |
| `color_error` | `--error--color` |
| `color_warning` | `--warning--color` |
| `color_info` | `--info--color` |
| `color_gradient_start` | `--gradient-start--color` |
| `color_gradient_end` | `--gradient-end--color` |
| `color_featured_badge` | `--featured-badge--color` |
| `color_rating` | `--woo--rating` |
| `color_header_bg` | `--color-header-bg` |
| `color_footer_bg` | `--color-footer-bg` |

#### Product Card Colors (10)
| Setting Key | CSS Variable |
|-------------|--------------|
| `color_card_bg` | `--product-card-bg` |
| `color_card_text` | `--product-card-text` |
| `color_card_border` | `--product-card-border` |
| `color_button_bg` | `--product-button-bg` |
| `color_button_text` | `--product-button-text` |
| `color_button_hover_bg` | `--product-button-hover-bg` |
| `color_badge_sale_bg` | `--product-badge-sale-bg` |
| `color_badge_sale_text` | `--product-badge-sale-text` |
| `color_badge_new_bg` | `--product-badge-new-bg` |
| `color_badge_new_text` | `--product-badge-new-text` |

#### Buttons (8)
| Setting Key | CSS Variable |
|-------------|--------------|
| `button_bg` | `--button-bg` |
| `button_text` | `--button-text` |
| `button_bg_hover` | `--button-bg-hover` |
| `button_text_hover` | `--button-text-hover` |
| `button_radius` | `--button-radius` |
| `button_padding_x` | `--button-padding-x` |
| `button_padding_y` | `--button-padding-y` |
| `button_font_size` | `--button-font-size` |

#### Forms (2)
| Setting Key | CSS Variable |
|-------------|--------------|
| `form_input_border_radius` | `--form-input-radius` |
| `form_input_height` | `--form-input-height` |

#### Header (9)
| Setting Key | CSS Variable |
|-------------|--------------|
| `header_bg` | `--header-bg` |
| `header_text_color` | `--header-color` |
| `header_padding_x` | `--header-padding-x` |
| `header_padding_y` | `--header-padding-y` |
| `header_border_color` | `--header-border-color` |
| `header_border_width` | `--header-border-width` |
| `header_mobile_height` | `--header-mobile-height` |
| `header_banner_height` | `--banner-height` |
| `header_height` | `--header--height` |

#### Navigation (2)
| Setting Key | CSS Variable |
|-------------|--------------|
| `nav_menu_height` | `--nav-menu-height` |
| `nav_submenu_width` | `--nav-submenu-width` |

#### Footer (5)
| Setting Key | CSS Variable |
|-------------|--------------|
| `footer_bg_color` | `--footer--bg` |
| `footer_text` | `--footer--text` |
| `footer_heading_text` | `--footer-heading` |
| `footer_link` | `--footer-link` |
| `footer_border_color` | `--footer-border-color` |

#### Spacing (5)
| Setting Key | CSS Variable |
|-------------|--------------|
| `home_section_spacing` | `--home-section-spacing` |
| `element_margin_bottom` | `--element-margin-bottom` |
| `widget_spacing` | `--widget-spacing` |
| `section_padding_x` | `--section-padding-x` |
| `section_padding_y` | `--section-padding-y` |

#### Responsive (4)
| Setting Key | CSS Variable |
|-------------|--------------|
| `breakpoint_xl` | `--breakpoint-xl` |
| `breakpoint_lg` | `--breakpoint-lg` |
| `breakpoint_md` | `--breakpoint-md` |
| `breakpoint_sm` | `--breakpoint-sm` |

#### Topbar (2)
| Setting Key | CSS Variable |
|-------------|--------------|
| `topbar_bg` | `--topbar--bg` |
| `topbar_text` | `--topbar--text` |

#### Menu (1)
| Setting Key | CSS Variable |
|-------------|--------------|
| `menu_font_size` | `--menu--font--size` |

#### Announcement Bar (2)
| Setting Key | CSS Variable |
|-------------|--------------|
| `announcement_bar_bg` | `--announcement-bar-bg` |
| `announcement_bar_text_color` | `--announcement-bar-color` |

#### Hero Responsive (5)
| Setting Key | CSS Variable |
|-------------|--------------|
| `hero_fit` | `--hero-object-fit` |
| `hero_position` | `--hero-object-position` |
| `hero_overlay_opacity` | `--hero-overlay-opacity` |
| `hero_tablet_breakpoint` | `--hero-tablet-bp` |
| `hero_mobile_breakpoint` | `--hero-mobile-bp` |

### Dynamic Expansion (Design Tokens)

The static map is expanded at runtime by `Settings_Registry::include_token_css_var_map()`:

```php
// includes/class-settings-registry.php:345-352
public function include_token_css_var_map(array $map): array {
    foreach ($this->entries as $key => $entry) {
        if (!empty($entry['css_var']) && !isset($map[$key])) {
            $map[$key] = $entry['css_var'];
        }
    }
    return $map;
}
```

This adds any setting entry that has a `css_var` key — currently ~168 design
token entries from `includes/Design/data/token-definitions.php`. The combined
map totals **304+ CSS custom properties**.

### PX Keys

Settings whose numeric values should get a `px` suffix are listed in
`Settings_Registry::get_px_keys()` (`class-settings-registry.php:360-383`).
This includes all sizing, spacing, typography, and breakpoint values.

---

## 8. Inline CSS Generation

`Customizer::get_inline_css()` generates a `<style>` tag on every page load.

**File**: `includes/class-customizer.php:541-585`

### Algorithm

```php
public function get_inline_css(): string {
    $options = get_option('phantom_options', array());  // 1. Read bulk
    $map     = $this->get_css_var_map();               // 2. Get var map
    $css     = '';

    foreach ($map as $key => $var) {
        // 3. Resolve value: bulk → individual → null
        $val = $options[$key] ?? get_option("phantom_{$key}", null);

        if (null !== $val) {
            if (is_array($val)) {
                // 4a. Responsive: desktop + @media tablet + @media mobile
                $desktop = $val['desktop'] ?? '';
                $css .= "{$var}:{$desktop};";
                // ...tablet and mobile @media blocks
            } else {
                // 4b. Scalar: append px if in px_keys
                $css .= "{$var}:{$val};";
            }
        }
    }

    return '<style id="phantom-customizer-css">:root{' . $css . '}</style>';
}
```

### Responsive Output Example

For a responsive setting like `container_width` with
`{desktop: 1200, tablet: 992, mobile: 100%}`:

```css
:root {
    --container-width: 1200px;
}
@media (max-width: 768px) {
    :root { --container-width: 992px; }
}
@media (max-width: 544px) {
    :root { --container-width: 100%; }
}
```

### Hook

```php
// includes/class-customizer.php:32
add_action('wp_head', array($this, 'output_inline_css'), 100);
```

---

## 9. Transport and Live Preview

### Transport Resolution

```php
// includes/class-customizer.php:501-510
private function get_transport(string $key, array $entry): string {
    if (isset($entry['transport'])) {
        return $entry['transport'];              // explicit override
    }
    $map = Settings_Registry::get_css_var_map();
    if (isset($map[$key])) {
        return 'postMessage';                     // CSS var → live preview
    }
    return 'refresh';                             // non-CSS → full refresh
}
```

Any setting with a CSS variable mapping automatically gets `postMessage` transport.

### Auto-Bind System (customizer-preview.js)

**File**: `admin/js/customizer-preview.js:42-61`

The auto-bind system iterates `PhantomCustomizer.cssVarKeys` and creates
a `wp.customize(settingId).bind()` handler for each:

```javascript
// auto-bind CSS variables from PHP mapping
PhantomCustomizer.cssVarKeys.forEach(function (settingKey) {
    var settingId = 'phantom_' + settingKey;
    var cssVar = PhantomCustomizer.cssVarMap[settingKey];
    var needsPx = PhantomCustomizer.cssVarPxKeys.indexOf(settingKey) !== -1;
    var isResponsive = PhantomCustomizer.responsiveKeys.indexOf(settingKey) !== -1;

    wp.customize(settingId).bind(function (newval) {
        if (isResponsive) {
            updateResponsiveCss(settingKey, cssVar, newval);
            return;
        }
        if (needsPx && /^\d+(\.\d+)?$/.test(newval)) newval += 'px';
        document.documentElement.style.setProperty(cssVar, newval);
    });
});
```

This means **all 304+ CSS variable bindings are created automatically**
without any manual JS per-setting code.

### Localized Data

```php
// includes/class-customizer.php:380-390
wp_localize_script('phantom-customizer-preview', 'PhantomCustomizer', [
    'cssVarMap'      => $css_var_map,       // {setting_key: '--var-name'}
    'cssVarKeys'     => array_keys($css_var_map),  // ['setting_key', ...]
    'cssVarPxKeys'   => $px_keys,           // keys needing px suffix
    'responsiveKeys' => $responsive_keys,   // keys with responsive values
    'restUrl'        => rest_url(),
]);
```

### Manual Bindings

Some settings need DOM manipulation beyond CSS variable updates. These are
defined manually in `customizer-preview.js:63-389`:

| Setting | Target | Action |
|---------|--------|--------|
| `blogname` | `.brand-logo`, `[data-phantom="site_name"]` | `textContent` |
| `phantom_home_banner_heading` | `.hero-headline .hero-headline-accent` | `textContent` |
| `phantom_home_banner_title` | `h1.hero-headline` | `textContent` + `<br>` |
| `phantom_home_banner_description` | `p.hero-subline` | `textContent` + `<br>` |
| `phantom_home_banner_btn_text` | `.hero-cta-group .btn-primary` | `textContent` |
| `phantom_home_banner_btn_url` | `.hero-cta-group .btn-primary` | `href` |
| `phantom_home_banner_img1` | `.swiper-slide.hero-slide:first-child img` | `src` |
| `phantom_home_banner_img2` | `.swiper-slide.hero-slide:last-child img` | `src` |
| `phantom_general_site_logo` | `.brand-logo img` | `src` / `backgroundImage` |
| `phantom_footer_logo` | `.footer-logo img` | `src` / `backgroundImage` |
| `phantom_branding_favicon` | `link[rel="icon"]` | `href` |
| `phantom_hero_banner_image` | `[data-hero-area] img.hero-image` | `src` + `backgroundImage` |
| `phantom_hero_image_tablet` | `picture source[data-device="tablet"]` | `srcset` |
| `phantom_hero_image_mobile` | `picture source[data-device="mobile"]` | `srcset` |
| `phantom_hero_loading` | `[data-hero-area] img.hero-image` | `loading` attr |
| `phantom_hero_fit` | CSS var `--hero-object-fit` | `setProperty` |
| `phantom_hero_position` | CSS var `--hero-object-position` + `--hero-bg-position` | `setProperty` |
| `phantom_hero_overlay_opacity` | CSS var `--hero-overlay-opacity` | `setProperty` |
| `phantom_header_style` | `document.body` | add/remove `header-{style}` class |
| `phantom_blog_layout` | `.blog-posts`, `.blog-grid` | add/remove `blog-{layout}` class |
| `phantom_footer_columns` | `footer .row` | add/remove `row-cols-{n}` class |

### Responsive Value Format

Responsive settings store values as:
```json
{"desktop": "1200", "tablet": "992", "mobile": "100%"}
```

The `updateResponsiveCss()` function generates CSS rules with `@media` queries
at breakpoints `{tablet: 768px, mobile: 544px}`.

---

## 10. Selective Refresh Partials

Partials allow WordPress to re-render only the changed DOM fragment instead of
doing a full page refresh for non-CSS-var settings.

**Registration**: `Customizer::register_partials()` (`class-customizer.php:190-204`)

### Active Partials

| Setting Key | Selector | Render Callback | REST Endpoint |
|-------------|----------|-----------------|---------------|
| `header_style` | `header.header` | `phantom_render_header_partial_v2` | `/phantom/v1/partial?partial=header_style` |
| `menu_location` | `nav.main-nav` | `phantom_render_nav_partial` | `/phantom/v1/partial?partial=menu_location` |
| `blog_layout` | `div.blog-grid` | `phantom_render_blog_partial` | `/phantom/v1/partial?partial=blog_layout` |
| `footer_layout` | `footer.footer` | `phantom_render_footer_partial` | `/phantom/v1/partial?partial=footer_layout` |
| `hero_banner_image` | `[data-hero-area]` | `phantom_render_hero_media_partial` | `/phantom/v1/partial?partial=hero_banner_image` |
| `hero_image_tablet` | `[data-hero-area]` | `phantom_render_hero_media_partial` | `/phantom/v1/partial?partial=hero_image_tablet` |
| `hero_image_mobile` | `[data-hero-area]` | `phantom_render_hero_media_partial` | `/phantom/v1/partial?partial=hero_image_mobile` |
| `product_grid_layout` | `div.shop-grid` | `phantom_render_search_partial` | `/phantom/v1/partial?partial=product_grid_layout` |

### Partial Delivery

Partials are rendered server-side and delivered via REST API. The
`customizer-preview.js:352-388` handler fetches the new HTML and replaces
the matched DOM element's `innerHTML`.

---

## 11. Custom Controls

Phantom Core defines **12 custom control types** via the `Control_Base` abstract
class and its subclasses in `includes/custom-controls/`.

**File**: `includes/custom-controls/class-control-base.php`

### Type-to-Class Map

```php
private static array $type_class_map = [
    'ast-color'              => Color_Control::class,
    'ast-toggle'             => Toggle_Control::class,
    'ast-radio-image'        => Radio_Image_Control::class,
    'ast-responsive-slider'  => Responsive_Slider_Control::class,
    'ast-responsive-spacing' => Responsive_Spacing_Control::class,
    'ast-typography'         => Typography_Control::class,
    'ast-gradient'           => Gradient_Control::class,
    'ast-select'             => Select_Control::class,
    'ast-color-group'        => Color_Group_Control::class,
    'ast-background'         => Background_Control::class,
    'ast-border'             => Border_Control::class,
];
```

### Usage Counts (actively used in Settings Registry)

| Control Type | Uses | Description |
|-------------|------|-------------|
| `ast-toggle` | 103 | ON/OFF toggle switch |
| `ast-color` | 56 | Color picker with palette and alpha |
| `ast-select` | 37 | Dropdown selector with options array |
| `ast-responsive-slider` | — | Number slider with responsive breakpoints |
| `ast-responsive-spacing` | — | Spacing (top/right/bottom/left) with responsive |
| `ast-typography` | — | Font family, size, weight, line-height |
| `ast-radio-image` | — | Image-based radio buttons |
| `ast-gradient` | — | Two-color gradient picker |
| `ast-color-group` | — | Multiple named colors |
| `ast-background` | — | Background image/color picker |
| `ast-border` | — | Border width/style/color |

### Standard WP Controls

Types not in the custom map fall through to standard WordPress controls:

| Type | WP Control |
|------|------------|
| `color` | `WP_Customize_Color_Control` |
| `bool` | checkbox |
| `select` | select dropdown |
| `image` | `WP_Customize_Image_Control` |
| `text` / `textarea` | textarea |
| `code` | `WP_Customize_Code_Editor_Control` |
| `number` / `int` / `float` | number input |
| `repeater` / `array` / `multiselect` / `json` | textarea (one per line) |

### Custom Control Registration

```php
// includes/custom-controls/class-control-base.php:16-41
public static function register_all(WP_Customize_Manager $wp_customize): void {
    // Require each control file
    foreach ($controls as $file) {
        require_once $base . $file;
    }
    // Register each control type with WP_Customize_Manager
    foreach (array_values(self::$type_class_map) as $class) {
        $wp_customize->register_control_type($class);
    }
}
```

---

## 12. Conditional Visibility

Controls can declare dependencies on other settings, causing them to show/hide
based on the current value of the dependency.

**File**: `admin/js/customizer-conditionals.js`

### Dependency Format

```php
'entry' => [
    'type'    => 'ast-toggle',
    'label'   => 'Enable Feature X',
    'dependencies' => [
        ['key' => 'some_setting', 'value' => 'enabled', 'operator' => '==='],
    ],
]
```

### Supported Operators

| Operator | Meaning |
|----------|---------|
| `===` | Strict equality (default) |
| `!==` | Strict inequality |
| `in` | Value is in array |
| `==` | Loose equality |

### How It Works

1. On `customize-bind('ready')`, the JS iterates all controls
2. For each control with `data-dependencies`, it binds to the dependency settings
3. When any dependency value changes, `updateVisibility()` is called
4. If all dependencies pass, the control is shown; otherwise hidden
5. `container.toggle(visible)` controls visibility

---

## 13. CSS Generation Engine (Modules)

In addition to the inline CSS system, Phantom Core has a filter-based CSS
generation engine using the `phantom_dynamic_css` filter. Each module adds
CSS rules at a specific priority.

**Directory**: `includes/custom-css/`

| Module | Priority | CSS Rules |
|--------|----------|-----------|
| `colors.php` | 10 | 21 color variables as `:root { --var: val; }` |
| `typography.php` | 20 | Body + heading font properties, h1-h6 overrides |
| `header.php` | 30 | Header, topbar, navigation, announcement bar |
| `footer.php` | 40 | Footer bg, text, heading, link, border |
| `layout.php` | 50 | Container width, gutter, content gap, columns |
| `buttons.php` | 60 | Button bg, text, hover, radius, padding, font-size |
| `product.php` | 70 | Product card bg/text/border, button, badges |
| `responsive.php` | 80 | Breakpoint overrides via `@media` queries |
| `hero.php` | 90 | Hero image, object-fit, position, overlay, responsive |

### Module Pattern

Each module follows the same pattern:

```php
// includes/custom-css/colors.php
add_filter('phantom_dynamic_css', function (string $css): string {
    $map = Settings_Registry::get_css_var_map();
    $output = '';
    foreach ($keys as $k) {
        $val = \Phantom_Custom_CSS::get_legacy_option($k);
        if ('' !== $val) {
            $output .= "\t" . $map[$k] . ': ' . esc_attr($val) . ";\n";
        }
    }
    if ('' !== $output) {
        $css .= ':root {' . "\n" . $output . '}' . "\n";
    }
    return $css;
}, 10);
```

### Design System CSS Layer

The `DesignSystemManager` hooks into the same filter at priority 2:

```php
// includes/Design/class-design-system-manager.php:48
add_filter('phantom_dynamic_css', $this->cssGenerator->getOutputHook(), 2);
```

The `CSSVariableGenerator` compiles tokens into three output sections:
1. `:root` block with all CSS variables
2. Component-scoped blocks (`.phantom-{component}`)
3. Responsive overrides (`@media` queries)

---

## 14. Design System Layer

The Design System is a layered token architecture that sits above the
Customizer settings. It provides:

- **168 design tokens** with typed definitions (color, number, font_family, etc.)
- **Preset system** — pre-built design themes (Dark, Minimal, Bold, Core, Demo)
- **Theme DNA** — design personality dimensions (warmth, contrast, formality, etc.)
- **CSS variable generation** from resolved tokens
- **REST API** for programmatic access

### Components

| Class | File | Purpose |
|-------|------|---------|
| `DesignSystemManager` | `includes/Design/class-design-system-manager.php` | Facade — orchestrates all sub-components |
| `TokenRegistry` | `includes/Design/class-token-registry.php` | Loads token definitions from `data/token-definitions.php` |
| `TokenResolver` | `includes/Design/class-token-resolver.php` | Resolves token values through provider chain |
| `TokenCompiler` | `includes/Design/class-token-compiler.php` | Compiles resolved tokens into `CompiledTokenSet` |
| `TokenValidator` | `includes/Design/class-token-validator.php` | Validates token values against type constraints |
| `CSSVariableGenerator` | `includes/Design/class-css-variable-generator.php` | Generates CSS from compiled token set |
| `PresetRegistry` | `includes/Design/class-preset-registry.php` | Manages preset providers (Core, Demo, User) |
| `PresetManager` | `includes/Design/class-preset-manager.php` | Applies presets by setting option values |
| `ThemeDNAEngine` | `includes/Design/class-theme-dna-engine.php` | Theme DNA personality dimensions |
| `DesignImporter` | `includes/Design/class-design-importer.php` | Imports design configs |
| `DesignExporter` | `includes/Design/class-design-exporter.php` | Exports design configs |

### Provider Chain

Token values are resolved through a provider chain:
1. `UserProvider` — user-overridden values
2. `DemoProvider` — demo/sample values
3. `CoreProvider` — default core values

### Design Tokens in Settings_Loader

The `section_design_tokens()` method auto-generates Customizer entries from
token definitions:

```php
// includes/Settings/class-settings-loader.php:5466-5511
private function section_design_tokens(): array {
    $tokenDefs = require $tokenFile;
    foreach ($tokenDefs as $name => $def) {
        $entry = [
            'default'  => $def['default'] ?? '',
            'type'     => $def['type'] ?? 'text',
            'section'  => 'design_tokens',
            'label'    => $def['description'] ?? ucwords($name),
            'sanitize' => /* based on type */,
        ];
        if ($dsm) {
            $entry['css_var'] = $dsm->cssVar($name);
            $entry['transport'] = 'postMessage';
        }
        $settings[$settingKey] = $entry;
    }
    return $settings;
}
```

Each token entry gets `css_var` and `transport: 'postMessage'` automatically,
which means token settings get live preview via the auto-bind system with
zero additional JS code.

---

## 15. REST API Endpoints

**File**: `includes/class-rest-controller.php:723-802` (routes), `3420-3517` (callbacks)

### Design Token Endpoints

| Method | Endpoint | Permission | Description |
|--------|----------|------------|-------------|
| `GET` | `/phantom/v1/design/tokens` | Public | All tokens (optional `?category=` filter) |
| `GET` | `/phantom/v1/design/tokens/{name}` | Public | Single token value (dot notation) |
| `PUT/PATCH` | `/phantom/v1/design/tokens/{name}` | `edit_theme_options` | Update token value |
| `GET` | `/phantom/v1/design/presets` | Public | All available presets |
| `GET` | `/phantom/v1/design/presets/{id}` | Public | Single preset details |
| `POST` | `/phantom/v1/design/presets/apply` | `edit_theme_options` | Apply a preset |
| `GET` | `/phantom/v1/design/css` | Public | Generated CSS string |

### Partial Endpoints

| Method | Endpoint | Permission | Description |
|--------|----------|------------|-------------|
| `GET` | `/phantom/v1/partial?partial={key}` | `edit_theme_options` | Render partial HTML |

### Nonce Authentication

The REST controller accepts two nonce headers:
1. `X-Phantom-Nonce` / `phantom_api` (custom)
2. `X-WP-Nonce` / `wp_rest` (WordPress standard, fallback)

```php
// includes/class-rest-controller.php:829-849
public function verify_nonce($request = null) {
    // Try X-Phantom-Nonce first
    // Fall back to X-WP-Nonce
    // Both checked via wp_verify_nonce()
}
```

---

## 16. Admin Settings Page

**File**: `admin/class-settings-page.php`

The admin settings page provides an alternative to the Customizer with the
same setting definitions.

### Tab Structure (15 tabs)

| Tab | Sections | Key Settings |
|-----|----------|-------------|
| General | `branding` | Site logo, favicon, site name |
| Header | `header`, `topbar`, `navigation`, `announcement_bar` | Header style, bg color, padding, border |
| Hero | `hero`, `home_sections` | Hero image, responsive media, CTA buttons |
| Products | `product_cards`, `shop_page`, `product_page` | Card layout, badges, grid columns |
| WooCommerce | `woocommerce` | WC-specific settings |
| Blog | `blog` | Blog layout, excerpt length |
| Footer | `footer` | Footer columns, bg color, links |
| Typography | `typography` | Body font, heading font, sizes, weights |
| Colors | `colors`, `buttons`, `forms` | Primary/secondary/accent colors, button styles |
| Layout | `layout`, `responsive` | Container width, breakpoints, spacing |
| Effects | `animations`, `effects_3d` | Animation toggles, 3D effects |
| Search | `search` | Search form style, results layout |
| Performance | `performance`, `seo` | Lazy loading, minification, meta tags |
| Accessibility | `accessibility` | Focus styles, contrast, ARIA |
| Advanced | `integrations`, `custom_code`, `import_export` | API keys, custom CSS/JS, import/export |

### Save Behavior

The admin page saves to the same `phantom_{key}` options and bulk
`phantom_options` array, ensuring consistency with the Customizer.

---

## 17. AETHER Variable Mappings

The frontend SPA (`phantom-data.js` / `Data_Engine.php`) maps AETHER design
tokens to Phantom Core CSS variables:

| AETHER Token | Phantom CSS Variable | Fallback |
|-------------|---------------------|----------|
| `--gold` | `var(--primary--color)` | `#C8956C` |
| `--chrome` | `var(--text--color)` | `#A8B5C0` |
| `--white` | `var(--heading--color)` | `#FFFFFF` |
| `--void` | `var(--bg)` | `#09090B` |
| `--surface` | `var(--color-header-bg)` | `#141416` |

This allows frontend templates to use AETHER semantic names while the actual
values are controlled through the Customizer / Design System.

---

## Appendix: Key Constants

| Constant | Value | Used By |
|----------|-------|---------|
| `PHANTOM_CORE_URL` | Plugin URL | Script/style enqueue |
| `PHANTOM_CORE_PATH` | Plugin file path | File includes |
| `PHANTOM_CORE_VERSION` | Plugin version | Cache busting |

## Appendix: Hook Summary

| Hook | Priority | Callback | Purpose |
|------|----------|----------|---------|
| `customize_register` | — | `Customizer::register()` | Register panels/sections/controls |
| `customize_preview_init` | — | `Customizer::preview_js()` | Enqueue preview JS + localize data |
| `customize_controls_enqueue_scripts` | — | `Customizer::controls_js()` | Enqueue controls JS + conditionals |
| `customize_save_after` | — | `Customizer::sync_options()` | Sync individual → bulk options |
| `wp_head` | 100 | `Customizer::output_inline_css()` | Inject `:root` CSS variables |
| `phantom_dynamic_css` | 2-90 | CSS modules | Generate CSS from settings |
| `admin_menu` | — | `Settings_Page::add_admin_menu()` | Register admin page |
