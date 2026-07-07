# Phase 6 — Design Token Foundation Systems

> **Status:** Design doc
> **Target:** PHANTOM v2.2.0

## Goal

Build 7 missing foundation systems into the PHANTOM theme: Spacing Scale, Shadow System, Z-Index Layers, Elevation System, Border System, Opacity Scale, and Illustration System. All changes are additive and backward-compatible — zero breakage.

## Architecture

Follow the exact pattern established by Phases 3–5:

```
assets/ph-design-tokens.css.liquid   ← NEW: all CSS custom properties
snippets/ph-illustrations.liquid      ← NEW: SVG illustration components
layout/theme.liquid                   ← MODIFY: include new CSS
config/settings_schema.json           ← MODIFY: add design token controls
```

No existing CSS classes, variables, or Liquid logic are modified. Only additions.

## Files

### CREATE: `assets/ph-design-tokens.css.liquid`

Single file containing all 7 token systems as CSS custom properties on `:root`.

Dark mode overrides via `@media (prefers-color-scheme: dark)` at the bottom.
RTL-ready via CSS logical properties (`margin-inline`, `padding-inline`, `gap`).

### CREATE: `snippets/ph-illustrations.liquid`

SVG illustration snippets rendering via a `{%- render 'ph-illustrations', name: 'empty-cart' -%}` interface.

### MODIFY: `layout/theme.liquid`

Add stylesheet tag for the new file (alongside existing asset includes):
```liquid
{{ 'ph-design-tokens.css.liquid' | asset_url | stylesheet_tag }}
```

### MODIFY: `config/settings_schema.json`

Add design token controls to the existing `ph_motion` block (or a new adjacent block):
- `shadow_opacity` — range 0–100, default 15, controls shadow darkness
- `shadow_color` — color picker, default `#000000`, shadow tint
- `border_radius_default` — range 0–24px, default 4px
- `elevation_intensity` — select: subtle / normal / pronounced, multiplies shadow offset

## Token Specifications

### 1. Spacing Scale (`--ph-space-*`)

12 steps following a 4px base unit:

| Token | Value | Typical Use |
|-------|-------|-------------|
| `--ph-space-1` | 4px | Icons, compact gaps |
| `--ph-space-2` | 8px | Button padding, tight gaps |
| `--ph-space-3` | 12px | Input padding, block spacing |
| `--ph-space-4` | 16px | Card padding, grid gap |
| `--ph-space-5` | 24px | Section inner padding |
| `--ph-space-6` | 32px | Section margins |
| `--ph-space-7` | 40px | Hero padding |
| `--ph-space-8` | 48px | Large section gaps |
| `--ph-space-9` | 64px | Page section spacing |
| `--ph-space-10` | 80px | Wide layout gaps |
| `--ph-space-11` | 96px | Maximum content padding |
| `--ph-space-12` | 120px | Hero section padding |

Semantic aliases:
```
--ph-gap-xs: var(--ph-space-1)
--ph-gap-sm: var(--ph-space-2)
--ph-gap-md: var(--ph-space-3)
--ph-gap-lg: var(--ph-space-4)
--ph-gap-xl: var(--ph-space-5)
```

### 2. Shadow System (`--ph-shadow-*`)

5 elevation levels using configurable shadow color:

| Token | Values |
|-------|--------|
| `--ph-shadow-sm` | `0 1px 2px rgba(var(--ph-shadow-color-rgb), var(--ph-shadow-alpha-sm))` |
| `--ph-shadow-md` | `0 4px 6px rgba(var(--ph-shadow-color-rgb), var(--ph-shadow-alpha-md))` |
| `--ph-shadow-lg` | `0 10px 25px rgba(var(--ph-shadow-color-rgb), var(--ph-shadow-alpha-lg))` |
| `--ph-shadow-xl` | `0 20px 50px rgba(var(--ph-shadow-color-rgb), var(--ph-shadow-alpha-xl))` |
| `--ph-shadow-2xl` | `0 30px 80px rgba(var(--ph-shadow-color-rgb), var(--ph-shadow-alpha-2xl))` |

Alpha values derived from the `shadow_opacity` theme setting:
```
--ph-shadow-alpha-sm: calc(var(--ph-shadow-opacity) * 0.33)
--ph-shadow-alpha-md: calc(var(--ph-shadow-opacity) * 0.47)
--ph-shadow-alpha-lg: calc(var(--ph-shadow-opacity) * 0.67)
--ph-shadow-alpha-xl: calc(var(--ph-shadow-opacity) * 0.80)
--ph-shadow-alpha-2xl: calc(var(--ph-shadow-opacity) * 1.00)
```

Dark mode: shadows invert to light-on-dark (`rgba(255,255,255, ...)`).

### 3. Z-Index Layers (`--ph-z-*`)

| Token | Value | Used By |
|-------|-------|---------|
| `--ph-z-base` | 1 | Raised elements (cards, sticky headers) |
| `--ph-z-nav` | 100 | Site navigation, toolbar |
| `--ph-z-overlay` | 300 | Overlay backgrounds |
| `--ph-z-drawer` | 400 | Slide-out drawers (cart, menu) |
| `--ph-z-modal` | 500 | Modal dialogs, lightbox |
| `--ph-z-toast` | 600 | Toast notifications, urgency bar |

### 4. Elevation System (`--ph-elevation-*`)

Semantic tokens combining shadow + z-index:

| Token | z-index | Shadow |
|-------|---------|--------|
| `--ph-elevation-ground` | `--ph-z-base` | None |
| `--ph-elevation-raised` | `--ph-z-base + 1` | `--ph-shadow-sm` |
| `--ph-elevation-overlay` | `--ph-z-overlay` | `--ph-shadow-lg` |
| `--ph-elevation-modal` | `--ph-z-modal` | `--ph-shadow-xl` |
| `--ph-elevation-toast` | `--ph-z-toast` | `--ph-shadow-2xl` |

### 5. Border System (`--ph-border-*`)

Width tokens:
```
--ph-border-width-thin: 1px
--ph-border-width-default: 2px
--ph-border-width-thick: 4px
```

Radius tokens:
```
--ph-border-radius-sm: 2px
--ph-border-radius-md: var(--ph-border-radius-default, 4px)
--ph-border-radius-lg: 8px
--ph-border-radius-full: 9999px
```

Color tokens (semantic):
```
--ph-border-color-light: rgba(var(--ph-border-color-rgb), 0.1)
--ph-border-color: rgba(var(--ph-border-color-rgb), 0.2)
--ph-border-color-strong: rgba(var(--ph-border-color-rgb), 0.4)
```

`--ph-border-color-rgb` derives from the theme's body text color.

### 6. Opacity Scale (`--ph-opacity-*`)

```
--ph-opacity-0: 0       --ph-opacity-60: 0.6
--ph-opacity-10: 0.1    --ph-opacity-70: 0.7
--ph-opacity-20: 0.2    --ph-opacity-80: 0.8
--ph-opacity-30: 0.3    --ph-opacity-90: 0.9
--ph-opacity-40: 0.4    --ph-opacity-100: 1
--ph-opacity-50: 0.5
```

### 7. Illustration System

**Interface:** `{% render 'ph-illustrations', name: 'empty-cart', class: 'my-class' %}`

Available illustrations (SVG inline, `currentColor`, ~200 bytes each):
- `empty-cart` — Shopping cart outline for empty cart state
- `empty-search` — Magnifying glass for no results
- `empty-orders` — Clipboard for no orders
- `404` — Broken link/puzzle piece
- `loading-spinner` — Animated CSS spinner (no JS)
- `hero-wave` — Decorative wave divider

All illustrations:
- Use `currentColor` for stroke/fill → inherit theme color
- Accept `class` and `size` (default 48px) parameters
- Have `aria-hidden="true"` by default
- Spinner uses CSS animation (`@keyframes`) included in `ph-design-tokens.css.liquid`

## Dark Mode

A `@media (prefers-color-scheme: dark)` section in `ph-design-tokens.css.liquid` overrides:
- Shadow colors: black → white (light-on-dark shadows)
- Border colors: softer contrast for dark backgrounds
- Elevation: adjusted shadow alpha for dark mode readability

All overrides use the same CSS custom properties — no class toggling needed.

## RTL Support

The spacing scale provides logical property aliases:
```
--ph-space-inline-start-{N}: var(--ph-space-{N})
--ph-space-inline-end-{N}: var(--ph-space-{N})
```

These can be used with CSS logical properties (`margin-inline-start`, etc.) for RTL layouts without duplicating styles.

## Backward Compatibility

- **No existing CSS vars removed or renamed**
- **No existing Liquid logic modified** (only `{% stylesheet_tag %}` added)
- **No existing section schemas changed**
- **No existing snippets changed**
- **No JavaScript added** (illustrations are CSS-animated SVGs)

The only risk is CSS specificity conflicts — mitigated by using `:root` custom properties which have the lowest priority.

## Implementation Order

1. `assets/ph-design-tokens.css.liquid` — all 7 token systems
2. `layout/theme.liquid` — include new CSS
3. `config/settings_schema.json` — token controls
4. `snippets/ph-illustrations.liquid` — SVG components
5. Verification + push

## Verification

```bash
# CSS file exists and is non-empty
Test-Path "assets/ph-design-tokens.css.liquid"

# Properly included in theme.liquid
Select-String -LiteralPath "layout/theme.liquid" -Pattern "ph-design-tokens"

# Settings present
Select-String -LiteralPath "config/settings_schema.json" -Pattern "shadow_opacity|border_radius_default"

# No existing files changed
git diff --name-only
# Should only show: assets/ph-design-tokens.css.liquid, layout/theme.liquid, config/settings_schema.json, snippets/ph-illustrations.liquid
```
