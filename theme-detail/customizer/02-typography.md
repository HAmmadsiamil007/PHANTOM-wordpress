# Typography Panel

Panel: `phantom_typography`
Section: `typography` (`phantom_section_typography`)

## Overview

The Typography panel controls all font-related settings across the site: body font, heading fonts, per-heading overrides, line heights, letter spacing, and font subsets. 43 settings total, all generating CSS custom properties consumed by the theme and AETHER templates.

## Settings Table

### Body Typography (6 settings)

| Setting ID | Type | Default | CSS Var | Options |
|---|---|---|---|---|
| `typography_body_font` | select | Archivo | `--font-body` | 170+ Google Fonts + 7 system fonts |
| `typography_body_weight` | select | 400 | `--font-body-weight` | 100–900 |
| `typography_body_style` | select | normal | `--font-body-style` | normal, italic, oblique |
| `typography_base_size` | int | 16 | `--font-base-size` | 12–32 px (responsive: desktop/tablet/mobile) |
| `typography_line_height` | float | 1.6 | `--font-line-height` | 1.0–3.0 |
| `typography_body_spacing` | float | 0 | `--font-body-spacing` | -2.0–5.0 px |

### Heading Typography (4 settings)

| Setting ID | Type | Default | CSS Var | Options |
|---|---|---|---|---|
| `typography_heading_font` | select | Playfair Display | `--font-heading` | 170+ Google Fonts + 7 system fonts |
| `typography_heading_weight` | select | 700 | `--font-heading-weight` | 100–900 |
| `typography_heading_case` | select | none | `--font-heading-case` | none, uppercase, lowercase, capitalize |
| `typography_heading_spacing` | float | 0 | `--font-heading-spacing` | -2.0–5.0 px |

### Per-Heading Overrides (h1–h6, 7 settings each = 42 settings)

For each heading level `h{n}` (where `{n}` = 1–6):

| Setting ID | Type | CSS Var | Description |
|---|---|---|---|
| `typography_h{n}_size` | int | `--h{n}-size` | Font size in px (responsive) |
| `typography_h{n}_height` | float | `--h{n}-height` | Line height |
| `typography_h{n}_font` | select | `--h{n}-font` | Override font family |
| `typography_h{n}_weight` | select | `--h{n}-weight` | Override weight |
| `typography_h{n}_style` | select | `--h{n}-style` | Override style |
| `typography_h{n}_spacing` | float | `--h{n}-spacing` | Override letter spacing |
| `typography_h{n}_case` | select | `--h{n}-case` | Override text transform |

### Additional Settings (1 setting)

| Setting ID | Type | CSS Var | Description |
|---|---|---|---|
| `typography_menu_font_size` | int | `--menu--font--size` | Navigation menu font size |

### Font Subset (1 setting)

| Setting ID | Type | Description |
|---|---|---|
| `font_subset` | multiselect | Google Fonts subsets to load (latin, latin-ext, cyrillic, etc.) |

## CSS Variable Output

The `typography.php` CSS module (priority 20) reads all typography settings and outputs:

```css
:root {
  /* Body */
  --font-body: 'Archivo', sans-serif;
  --font-body-weight: 400;
  --font-body-style: normal;
  --font-base-size: 16px;
  --font-line-height: 1.6;
  --font-body-spacing: 0px;

  /* Heading */
  --font-heading: 'Playfair Display', serif;
  --font-heading-weight: 700;
  --font-heading-case: none;
  --font-heading-spacing: 0px;

  /* Per-heading */
  --h1-size: 48px;
  --h1-height: 1.2;
  --h1-font: 'Playfair Display', serif;
  --h1-weight: 700;
  --h1-style: normal;
  --h1-spacing: 0px;
  --h1-case: none;

  --h2-size: 36px;
  --h2-height: 1.3;
  /* ... */

  /* Menu */
  --menu--font--size: 14px;
}

/* Responsive overrides */
@media (max-width: 1024px) {
  :root {
    --font-base-size: 15px;
    --h1-size: 36px;
    --h2-size: 28px;
  }
}

@media (max-width: 768px) {
  :root {
    --font-base-size: 14px;
    --h1-size: 28px;
    --h2-size: 24px;
  }
}
```

## Code Flow

```
User selects font in Customizer
    → setting saved to phantom_typography_body_font
    → typography.php CSS module reads via get_legacy_option()
    → outputs :root { --font-body: 'Archivo', sans-serif; }
    → Google Fonts enqueued via phantom_enqueue_google_fonts()
    → Font loaded in <head>
```

### Font Selection Flow

1. User picks font from dropdown (170+ options)
2. `WP_Customize_Select_Control` saves font family name
3. `Settings_Registry` persists to `phantom_typography_body_font` option
4. `typography.php` CSS module reads via `get_legacy_option()`:
   ```php
   $body_font = get_legacy_option( 'typography_body_font', 'Archivo' );
   echo "--font-body: '{$body_font}', " . $fallback . ";";
   ```
5. Font-family value sanitized with `wp_strip_all_tags()` (prevents HTML entity encoding from `esc_attr`)
6. `phantom_enqueue_google_fonts()` enqueues the Google Fonts `<link>` tag
7. Font loaded asynchronously via `font-display: swap`

### Font Sanitization

Font family values are sanitized with `wp_strip_all_tags()` instead of `esc_attr()` to prevent spaces from being encoded as `&#8217;`:

```php
// Wrong — encodes apostrophes and spaces
$font = esc_attr( get_option( 'typography_body_font' ) );

// Correct — preserves readable font names
$font = wp_strip_all_tags( get_option( 'typography_body_font' ) );
```

## Font System

### Phantom_Font_Families

The `Phantom_Font_Families` class provides 170+ Google Fonts organized by category:

| Category | Count | Examples |
|---|---|---|
| Sans-Serif | ~60 | Archivo, Inter, Montserrat, Open Sans, Roboto |
| Serif | ~35 | Playfair Display, Lora, Merriweather, Source Serif Pro |
| Display | ~30 | Bebas Neue, Oswald, Righteous, Permanent Marker |
| Handwriting | ~25 | Dancing Script, Pacifico, Satisfy, Great Vibes |
| Monospace | ~15 | Fira Code, JetBrains Mono, Source Code Pro |
| System | 7 | system-ui, -apple-system, BlinkMacSystemFont, sans-serif, serif, monospace, cursive |

### Font Loading Strategy

1. Google Fonts loaded via `<link rel="preconnect">` + `<link href="...">`
2. `font-display: swap` ensures text remains visible during load
3. Subsets loaded based on `font_subset` setting (default: `latin`)
4. System fonts skip network requests entirely

## Live Preview Behavior

All typography settings use the auto-bind system in `admin/js/customizer-preview.js`:

```javascript
// Auto-bind handles all typography CSS var updates automatically
// No manual JS bindings needed — the cssVarMap includes all typography settings
const cssVarMap = {
  'typography_body_font': '--font-body',
  'typography_body_weight': '--font-body-weight',
  'typography_base_size': '--font-base-size',
  // ... all 43 typography settings mapped
};
```

The auto-bind system:
- Detects setting changes via `wp.customize()`
- Maps setting IDs to CSS variable names via `cssVarMap`
- Updates `:root` style in the preview iframe
- Handles responsive breakpoint overrides

## Frontend Connection

### Theme CSS

The theme's `style.css` and component CSS files consume the CSS variables:

```css
body {
  font-family: var(--font-body, 'Archivo', sans-serif);
  font-weight: var(--font-body-weight, 400);
  font-style: var(--font-body-style, normal);
  font-size: var(--font-base-size, 16px);
  line-height: var(--font-line-height, 1.6);
  letter-spacing: var(--font-body-spacing, 0px);
}

h1, h2, h3, h4, h5, h6 {
  font-family: var(--font-heading, 'Playfair Display', serif);
  font-weight: var(--font-heading-weight, 700);
  text-transform: var(--font-heading-case, none);
  letter-spacing: var(--font-heading-spacing, 0px);
}

h1 {
  font-size: var(--h1-size, 48px);
  line-height: var(--h1-height, 1.2);
}
```

### AETHER Templates

The AETHER static HTML templates (index.html, shop.html, etc.) use the same CSS variables. The `phantom-inline-css` stylesheet is injected via `wp_head` and applies all typography settings globally.

## Responsive Typography

Three breakpoints control font size scaling:

| Breakpoint | Body Size | H1 | H2 | H3 |
|---|---|---|---|---|
| Desktop (>1024px) | 16px | 48px | 36px | 28px |
| Tablet (768–1024px) | 15px | 36px | 28px | 24px |
| Mobile (<768px) | 14px | 28px | 24px | 20px |

Per-heading settings (`typography_h{n}_size`) use responsive values with `@media` queries.

## Related Files

| File | Role |
|---|---|
| `includes/class-settings-registry.php` | Registers all 43 typography settings |
| `includes/Settings/class-settings-loader.php` | Defines section `phantom_section_typography` |
| `includes/custom-css/typography.php` | CSS module — outputs all typography CSS vars |
| `includes/class-phantom-font-families.php` | 170+ Google Fonts + 7 system fonts |
| `includes/class-phantom-webfont-loader.php` | Local font enqueue |
| `admin/js/customizer-preview.js` | Auto-bind for all typography CSS vars |
