# Colors Panel

Panel: `phantom_colors`
Sections: `colors`, `buttons`, `forms`, `spacing`

## Overview

The Colors panel is the largest Customizer panel, controlling the entire color system — brand colors, UI colors, WooCommerce product colors, button styles, form inputs, and spacing values. 60+ settings across 4 sections, all generating CSS custom properties.

## Colors Section (`phantom_section_colors`)

### Color Settings Table (28 ast-color controls)

| Setting ID | CSS Var | Default | Description |
|---|---|---|---|
| `color_primary` | `--primary--color` | `#C8956C` | Primary brand color (gold) |
| `color_secondary` | `--secondary--color` | — | Secondary brand color |
| `color_accent` | `--accent--color` | — | Accent/highlight color |
| `color_background` | `--bg` | `#09090B` | Page background |
| `color_text` | `--text--color` | `#A8B5C0` | Body text color |
| `color_heading` | `--heading--color` | `#FFFFFF` | Heading text color |
| `color_link` | `--link` | — | Link color |
| `color_link_hover` | `--link--hover` | — | Link hover color |
| `color_border` | `--border--color` | — | Border color |
| `color_sale` | `--sale--color` | — | Sale badge color |
| `color_light_bg` | `--light--bg--color` | — | Light background variant |
| `color_grey` | `--grey--color` | — | Grey/muted color |
| `color_success` | `--success--color` | — | Success state color |
| `color_error` | `--error--color` | — | Error state color |
| `color_warning` | `--warning--color` | — | Warning state color |
| `color_info` | `--info--color` | — | Info state color |
| `color_gradient_start` | `--gradient-start--color` | — | Gradient start |
| `color_gradient_end` | `--gradient-end--color` | — | Gradient end |
| `color_featured_badge` | `--featured-badge--color` | — | Featured badge |
| `color_rating` | `--woo--rating` | — | WooCommerce star rating |
| `color_header_bg` | `--color-header-bg` | — | Header background |
| `color_footer_bg` | `--color-footer-bg` | — | Footer background |
| `color_card_bg` | `--product-card-bg` | — | Product card background |
| `color_card_text` | `--product-card-text` | — | Product card text |
| `color_card_border` | `--product-card-border` | — | Product card border |
| `color_button_bg` | `--product-button-bg` | — | Product button background |
| `color_button_text` | `--product-button-text` | — | Product button text |
| `color_button_hover_bg` | `--product-button-hover-bg` | — | Product button hover |
| `color_badge_sale_bg` | `--product-badge-sale-bg` | — | Sale badge background |
| `color_badge_sale_text` | `--product-badge-sale-text` | — | Sale badge text |
| `color_badge_new_bg` | `--product-badge-new-bg` | — | New badge background |
| `color_badge_new_text` | `--product-badge-new-text` | — | New badge text |

## Buttons Section (`phantom_section_buttons`)

| Setting ID | Type | CSS Var | Default | Description |
|---|---|---|---|---|
| `button_bg` | ast-color | `--button-bg` | — | Button background |
| `button_text` | ast-color | `--button-text` | — | Button text color |
| `button_bg_hover` | ast-color | `--button-bg-hover` | — | Button hover background |
| `button_text_hover` | ast-color | `--button-text-hover` | — | Button hover text |
| `button_radius` | int | `--button-radius` | — | Border radius (px) |
| `button_padding_x` | int | `--button-padding-x` | — | Horizontal padding (px) |
| `button_padding_y` | int | `--button-padding-y` | — | Vertical padding (px) |
| `button_font_size` | int | `--button-font-size` | — | Font size (px) |

## Forms Section (`phantom_section_forms`)

| Setting ID | Type | CSS Var | Description |
|---|---|---|---|
| `form_input_border_radius` | ast-color | `--form-input-radius` | Input border radius (px) |
| `form_input_height` | ast-color | `--form-input-height` | Input height (px) |
| Various checkout form field settings | string | — | Checkout field styling |

## Spacing Section (`phantom_section_spacing`)

| Setting ID | Type | CSS Var | Description |
|---|---|---|---|
| `section_padding_x` | int | `--section-padding-x` | Section horizontal padding (px) |
| `section_padding_y` | int | `--section-padding-y` | Section vertical padding (px) |
| `container_gutter` | int | `--container-gutter` | Container gutter (px) |
| `content_gap` | int | `--content-gap` | Content gap (px) |
| `element_margin_bottom` | int | `--element-margin-bottom` | Element bottom margin (px) |
| `widget_spacing` | int | `--widget-spacing` | Widget spacing (px) |

## CSS Variable Output

The `colors.php` CSS module (priority 10) reads all color settings and outputs:

```css
:root {
  /* Brand */
  --primary--color: #C8956C;
  --secondary--color: #1a1a2e;
  --accent--color: #e94560;

  /* UI */
  --bg: #09090B;
  --text--color: #A8B5C0;
  --heading--color: #FFFFFF;
  --link: #C8956C;
  --link--hover: #d4a57a;
  --border--color: #2a2a2e;

  /* State */
  --success--color: #10b981;
  --error--color: #ef4444;
  --warning--color: #f59e0b;
  --info--color: #3b82f6;

  /* WooCommerce */
  --product-card-bg: rgba(9,9,11,0.6);
  --product-card-text: #A8B5C0;
  --product-card-border: #2a2a2e;
  --product-button-bg: #C8956C;
  --product-button-text: #FFFFFF;
  --product-button-hover-bg: #d4a57a;
  --product-badge-sale-bg: #ef4444;
  --product-badge-sale-text: #FFFFFF;
  --product-badge-new-bg: #10b981;
  --product-badge-new-text: #FFFFFF;

  /* Buttons */
  --button-bg: #C8956C;
  --button-text: #FFFFFF;
  --button-bg-hover: #d4a57a;
  --button-text-hover: #FFFFFF;
  --button-radius: 4px;
  --button-padding-x: 24px;
  --button-padding-y: 12px;
  --button-font-size: 14px;

  /* Spacing */
  --section-padding-x: 80px;
  --section-padding-y: 80px;
  --container-gutter: 24px;
  --content-gap: 32px;
  --element-margin-bottom: 24px;
  --widget-spacing: 16px;
}
```

## AETHER Mapping Layer

The `Data_Engine.php` provides a mapping layer between AETHER template CSS variables and Phantom CSS variables:

| AETHER Var | Maps To | Default |
|---|---|---|
| `--gold` | `var(--primary--color)` | `#C8956C` |
| `--chrome` | `var(--text--color)` | `#A8B5C0` |
| `--white` | `var(--heading--color)` | `#FFFFFF` |
| `--void` | `var(--bg)` | `#09090B` |
| `--surface` | `var(--color-header-bg)` | `#141416` |

This mapping allows AETHER templates to use semantic names (`--gold`, `--void`) while the actual values come from the Phantom color system.

## Color Palette System

`Phantom_Global_Palette` provides a separate 9-color palette system:

| Palette CSS Var | Description |
|---|---|
| `--phantom-color-0` | Palette color 0 |
| `--phantom-color-1` | Palette color 1 |
| `--phantom-color-2` | Palette color 2 |
| `--phantom-color-3` | Palette color 3 |
| `--phantom-color-4` | Palette color 4 |
| `--phantom-color-5` | Palette color 5 |
| `--phantom-color-6` | Palette color 6 |
| `--phantom-color-7` | Palette color 7 |
| `--phantom-color-8` | Palette color 8 |

### Presets (4)

| Preset | Description |
|---|---|
| `light` | Light color scheme |
| `dark` | Dark color scheme (default) |
| `vibrant` | High-saturation colors |
| `pastel` | Soft, muted colors |

### Dark Mode

Automatic `@media (prefers-color-scheme: dark)` CSS override:

```css
@media (prefers-color-scheme: dark) {
  :root {
    --bg: #09090B;
    --text--color: #A8B5C0;
    --heading--color: #FFFFFF;
    /* ... dark mode overrides */
  }
}
```

## Code Flow

```
User picks color in Customizer
    → ast-color control saves hex value
    → colors.php module (priority 10) reads via get_legacy_option()
    → outputs :root { --primary--color: #C8956C; }
    → AETHER mapping layer maps --gold to var(--primary--color)
```

### Color Control Flow

1. User opens color picker (`ast-color` control)
2. `WP_Customize_Color_Control` saves hex value (e.g., `#C8956C`)
3. `Settings_Registry` persists to `phantom_color_primary` option
4. `colors.php` CSS module reads via `get_legacy_option()`:
   ```php
   $primary = get_legacy_option( 'color_primary', '#C8956C' );
   echo "--primary--color: {$primary};";
   ```
5. CSS variable injected into `<style id="phantom-inline-css">` in `<head>`
6. All elements using `var(--primary--color)` update instantly

### Live Preview

All color settings use the auto-bind system:

```javascript
// Color settings auto-bind to CSS variables
// ast-color controls trigger CSS var updates in preview iframe
const cssVarMap = {
  'color_primary': '--primary--color',
  'color_secondary': '--secondary--color',
  // ... all 28+ color settings mapped
};
```

## Frontend Connection

### Theme CSS

```css
body {
  background-color: var(--bg, #09090B);
  color: var(--text--color, #A8B5C0);
}

h1, h2, h3, h4, h5, h6 {
  color: var(--heading--color, #FFFFFF);
}

a {
  color: var(--link, #C8956C);
}
a:hover {
  color: var(--link--hover, #d4a57a);
}

/* WooCommerce product cards */
.product-card {
  background: var(--product-card-bg, rgba(9,9,11,0.6));
  color: var(--product-card-text, #A8B5C0);
  border: 1px solid var(--product-card-border, #2a2a2e);
}
```

### AETHER Templates

The AETHER static HTML templates consume both direct CSS variables and the mapped variables:

```css
/* AETHER uses --gold, --void, etc. */
.hero { background: var(--void); }
.hero h1 { color: var(--white); }
.hero p { color: var(--chrome); }
.cta-button { background: var(--gold); }
```

### Product Cards

All product card components (in `includes/renderer/class-product-card.php`) use the product-specific CSS variables for consistent styling across shop, homepage, and search results.

## Related Files

| File | Role |
|---|---|
| `includes/class-settings-registry.php` | Registers all 60+ color/spacing/button settings |
| `includes/Settings/class-settings-loader.php` | Defines sections `phantom_section_colors`, `_buttons`, `_forms`, `_spacing` |
| `includes/custom-css/colors.php` | CSS module — outputs all color CSS vars |
| `includes/class-phantom-global-palette.php` | 9-color palette system, 4 presets, dark mode |
| `includes/Engine/Data_Engine.php` | AETHER mapping layer (`--gold` → `--primary--color`) |
| `admin/js/customizer-preview.js` | Auto-bind for all color CSS vars |
| `includes/renderer/class-product-card.php` | Product card uses product CSS vars |
