# Customizer Panel: Layout (`phantom_layout`)

## Overview

The Layout panel controls page structure, responsive breakpoints, scroll animations, and 3D effects. Unlike the Blog panel (class-toggled), Layout settings generate CSS custom properties that the entire AETHER template system consumes. This is the structural foundation — container width, content width, sidebar positioning, and responsive breakpoints all flow from these variables.

## Panel ID

`phantom_layout`

## Sections

### 1. Layout Settings (`phantom_section_layout`)

**12 settings** controlling page width, columns, gutters, and structural options.

#### CSS Variable Mapping

| Setting Key | Type | Default | CSS Variable | Unit |
|---|---|---|---|---|
| `container_width` | `int` | `1200` | `--container-width` | `px` |
| `content_width` | `int` | `800` | `--content-width` | `px` |
| `sidebar_width` | `int` | `300` | `--sidebar-width` | `px` |
| `layout_boxed_width` | `int` | `1100` | `--boxed-width` | `px` |
| `layout_columns` | `int` | `12` | `--layout-columns` | `px` (grid column count) |
| `container_gutter` | `int` | `30` | `--container-gutter` | `px` |
| `content_gap` | `int` | `24` | `--content-gap` | `px` |

#### Select Settings

| Setting Key | Type | Default | Options |
|---|---|---|---|
| `layout_style` | `ast-select` | `full_width` | `full_width`, `boxed`, `framed` |
| `layout_sidebar_position` | `ast-select` | `right` | `left`, `right`, `none` |
| `layout_page_title_style` | `ast-select` | `standard` | `standard`, `hero`, `breadcrumbs`, `hidden` |

#### Responsive Overrides

Each `container_width` setting supports responsive values via `Desktop`, `Tablet`, `Mobile` variants in the Customizer (handled by `ast-select` with breakpoint suffixes).

---

### 2. Responsive Settings (`phantom_section_responsive`)

**4 settings** defining the responsive breakpoint thresholds.

| Setting Key | Type | Default | CSS Variable | Description |
|---|---|---|---|---|
| `breakpoint_xl` | `int` | `1200` | `--breakpoint-xl` | Extra large breakpoint (px) |
| `breakpoint_lg` | `int` | `992` | `--breakpoint-lg` | Large breakpoint (px) |
| `breakpoint_md` | `int` | `768` | `--breakpoint-md` | Medium breakpoint (px) |
| `breakpoint_sm` | `int` | `576` | `--breakpoint-sm` | Small breakpoint (px) |

#### CSS Output

```css
:root {
  --breakpoint-xl: 1200px;
  --breakpoint-lg: 992px;
  --breakpoint-md: 768px;
  --breakpoint-sm: 576px;
}
```

These variables are consumed by `@media` queries in the generated CSS:
```css
@media (max-width: var(--breakpoint-md)) {
  /* tablet styles */
}
```

**Note**: Standard CSS `@media` does not support `var()` in query expressions. The CSS Generation Engine (`includes/class-custom-css.php`) resolves variables at generation time and outputs literal `px` values in `@media` rules.

---

### 3. Animations Section (`phantom_section_animations`)

**5 settings** controlling scroll-triggered animations.

| Setting Key | Type | Default | Description |
|---|---|---|---|
| `animate_on_scroll` | `ast-toggle` | `false` | Enable scroll-triggered animations. |
| `animation_library` | `ast-select` | `wow` | Animation library: `wow` (WOW.js), `gsap` (GSAP ScrollTrigger), `none`. |
| `animation_duration` | `string` | `0.8s` | Default animation duration (CSS time value). |
| `animation_delay` | `string` | `0s` | Default animation delay (CSS time value). |
| `animations_enabled` | `ast-toggle` | `true` | Master switch — when false, all animations disabled regardless of individual settings. |

#### Code Flow

```
animate_on_scroll = true
  → Phantom_Assets::enqueue_animation_library() loads wow.js or GSAP
  → Elements with data-wow-duration / data-wow-delay attributes animate on scroll
  → CSS classes: fadeIn, slideUp, slideLeft, slideRight, zoomIn, etc.
```

**Performance**: When `animate_on_scroll` is disabled, no animation JS or CSS is loaded (zero overhead).

---

### 4. 3D Effects Section (`phantom_section_effects_3d`)

**4 settings** controlling 3D tilt/parallax effects on elements.

| Setting Key | Type | Default | Description |
|---|---|---|---|
| `effect_3d_enable` | `ast-toggle` | `false` | Enable 3D tilt effects. |
| `effect_3d_intensity` | `int` | `15` | Effect intensity (1–50). Higher = more tilt. |
| `effect_3d_perspective` | `int` | `1000` | CSS `perspective` value in px. |
| `effect_3d_tilt_limit` | `int` | `30` | Maximum tilt angle in degrees. |

#### CSS Output

```css
[data-3d-effect] {
  perspective: 1000px;
  transform-style: preserve-3d;
}
[data-3d-effect]:hover {
  transform: rotateX(var(--3d-tilt-x, 0deg)) rotateY(var(--3d-tilt-y, 0deg));
  transition: transform 0.3s ease-out;
}
```

#### JS Flow

```
effect_3d_enable = true
  → phantom-injector.js initializes 3D tilt handler
  → mousemove on [data-3d-effect] elements calculates tilt angles
  → Applies inline transform with intensity scaling
  → tilt_limit caps maximum angle
```

---

## Code Flow: Settings → CSS Generation

```
User changes container_width to 1400
  → save: phantom_container_width = 1400
  → Customizer postMessage sends new value
  → customizer-preview.js updates :root CSS variable live
  → On save: layout.php CSS module (priority 50) generates:
      :root {
        --container-width: 1400px;
        --content-width: 800px;
        --sidebar-width: 300px;
        --container-gutter: 30px;
        --content-gap: 24px;
      }
  → All AETHER templates consume these vars:
      .site-container { max-width: var(--container-width); margin: 0 auto; }
      .content-area { max-width: var(--content-width); }
      .sidebar { width: var(--sidebar-width); }
```

### CSS Generation Engine Integration

Layout settings are processed by `includes/custom-css/layout.php` at **priority 50** (runs before typography at 100, after base reset at 10). The module:

1. Reads all `phantom_*` layout options from `get_option()`
2. Maps each to its CSS variable
3. Outputs `:root { ... }` block with all resolved values
4. For responsive variants, outputs `@media` queries with literal px values (variables resolved at generation time)

### Frontend Templates

All AETHER templates consume layout CSS vars:

| Template | Layout Usage |
|---|---|
| `frontend/html/index.html` | `max-width: var(--container-width)` on `.site-container` |
| `frontend/html/shop.html` | Grid columns use `--layout-columns`, gap uses `--content-gap` |
| `frontend/html/blog.html` | Sidebar positioning via `--sidebar-width` |
| `frontend/html/404.html` | Centered layout with `--content-width` |

---

## Developer Notes

- **CSS var priority**: Layout vars are generated at priority 50, which means they're available for all downstream CSS modules (typography, colors, components) to reference.
- **Breakpoint resolution**: The CSS Generation Engine resolves `var(--breakpoint-md)` to literal `768px` in `@media` rules at compile time. This is necessary because native CSS does not support custom properties in media query expressions.
- **Animation loading**: When `animate_on_scroll` is `false`, the plugin does not enqueue WOW.js or GSAP — zero JS/CSS overhead for sites that don't use animations.
- **3D effects**: The `effect_3d_*` settings only apply to elements with a `data-3d-effect` attribute. The plugin does not auto-apply 3D effects — templates must opt-in via this attribute.
- **Boxed layout**: When `layout_style` is `boxed`, the site container gets a max-width plus `margin: 0 auto` and a subtle box-shadow. The `--boxed-width` variable controls this width.
