# PHANTOM Motion System — Design Doc

> Designed: July 7, 2026
> Status: Draft for review (Updated after Round 1+2 review)

---

## Overview

The PHANTOM Motion System is a 3-layer enhancement that transforms the theme from a static Impulse clone into a modern, animated, premium-feeling theme. Each layer is independent and can be shipped separately.

### Why This Matters

1. **Differentiation:** Adding modern animations, page transitions, and new style presets makes PHANTOM feel like a completely different theme — visually and experientially.

2. **Anti-Detectability:** New animation CSS, new data attributes, new `data-aos` animation types, and new presets add thousands of lines of non-Impulse code. Theme detection tools look at code fingerprints — new code patterns break those fingerprints.

3. **Business Value:** "Scroll-triggered animations" and "cinematic page transitions" are selling points that premium themes charge $350+ for.

### Critical Finding — Existing AOS System

**The theme ALREADY has a scroll-triggered animation system (AOS):**
- Bundled in `assets/phantom-vendor.js` (minified, cannot safely modify)
- 55+ CSS rules in `assets/theme.css.liquid` using `.aos-animate[data-aos=...]`
- Used across 17+ sections via `data-aos` attributes
- Has a master disable toggle via `data-disable-animations` attribute
- Has existing animation types: `row-of-3`, `row-of-4`, `hero__animation`, `overflow__animation`, `background-media-text__animation`, `logo__animation`, `map-section__animation`, `image-fade-in`

**Our approach: ADDITIVE enhancement, not replacement.**
- We keep AOS engine working (too deeply embedded to remove safely)
- We add NEW animation types that our CSS handles
- Both old and new animations coexist
- Merchants choose per section via the `entrance_animation` setting

---

## The Three Layers

```
PHANTOM Motion System
├── Layer 1: Enhanced Animation Engine    
│   ├── New animation types via ph-motion.css.liquid
│   ├── Works WITH existing AOS (not against)
│   ├── Adds entrance_animation setting to 15 sections
│   ├── 7 new animation types: fade-up, fade-down, fade-left, fade-right, scale-in, zoom-in, none
│   └── Extension: not replacement
│
├── Layer 2: Skeleton Loading + Page Transitions  
│   ├── View Transitions API for SPA-like page navigation
│   ├── Skeleton placeholders for AJAX-dependent sections only
│   ├── Exclusion selectors prevent conflicts with existing JS
│   ├── Progressive enhancement — falls back to normal load
│   └── Independent of AOS — no conflict
│
└── Layer 3: Theme Presets                    
    ├── 4 new style presets added to existing 3 (total: 7)
    ├── Minimal, Editorial, Bold, Luxury
    ├── One-click switching via Theme Settings
    └── Pure JSON — no code changes, just settings_data.json entries
```

---

## Layer 1: Enhanced Animation Engine

### Architecture

```
Existing:   [AOS engine in vendor.js] → observes [data-aos] → applies .aos-animate
New:        [AOS engine in vendor.js] → observes [data-aos="ph-fade-up"] → applies .aos-animate
            [ph-motion.css.liquid]    → styles .aos-animate[data-aos="ph-fade-up"] { ... }
```

We do NOT create a new IntersectionObserver. We use AOS's existing engine but with NEW animation types that our CSS handles with modern, smooth animations.

### New Animation Types

| Value | CSS Transform | Opacity | Duration |
|-------|--------------|---------|----------|
| `ph-fade-up` | translateY(30px) → translateY(0) | 0 → 1 | 0.6s |
| `ph-fade-down` | translateY(-30px) → translateY(0) | 0 → 1 | 0.6s |
| `ph-fade-left` | translateX(-30px) → translateX(0) | 0 → 1 | 0.6s |
| `ph-fade-right` | translateX(30px) → translateX(0) | 0 → 1 | 0.6s |
| `ph-scale-in` | scale(0.95) → scale(1) | 0 → 1 | 0.5s |
| `ph-zoom-in` | scale(0.8) → scale(1) | 0 → 1 | 0.7s |

Note: Prefix `ph-` avoids collision with any existing AOS animation types.

### CSS (in ph-motion.css.liquid)

```css
[data-aos="ph-fade-up"] {
  opacity: 0;
  transform: translateY(30px);
  transition: opacity 0.6s ease, transform 0.6s ease;
}
[data-aos="ph-fade-up"].aos-animate {
  opacity: 1;
  transform: translateY(0);
}

[data-aos="ph-fade-down"] {
  opacity: 0;
  transform: translateY(-30px);
  transition: opacity 0.6s ease, transform 0.6s ease;
}
[data-aos="ph-fade-down"].aos-animate {
  opacity: 1;
  transform: translateY(0);
}

[data-aos="ph-fade-left"] {
  opacity: 0;
  transform: translateX(-30px);
  transition: opacity 0.6s ease, transform 0.6s ease;
}
[data-aos="ph-fade-left"].aos-animate {
  opacity: 1;
  transform: translateX(0);
}

[data-aos="ph-fade-right"] {
  opacity: 0;
  transform: translateX(30px);
  transition: opacity 0.6s ease, transform 0.6s ease;
}
[data-aos="ph-fade-right"].aos-animate {
  opacity: 1;
  transform: translateX(0);
}

[data-aos="ph-scale-in"] {
  opacity: 0;
  transform: scale(0.95);
  transition: opacity 0.5s ease, transform 0.5s ease;
}
[data-aos="ph-scale-in"].aos-animate {
  opacity: 1;
  transform: scale(1);
}

[data-aos="ph-zoom-in"] {
  opacity: 0;
  transform: scale(0.8);
  transition: opacity 0.7s ease, transform 0.7s ease;
}
[data-aos="ph-zoom-in"].aos-animate {
  opacity: 1;
  transform: scale(1);
}

@media (prefers-reduced-motion: reduce) {
  [data-aos^="ph-"] {
    opacity: 1 !important;
    transform: none !important;
    transition: none !important;
  }
}
```

### Schema Setting (added to each target section)

```json
{
  "type": "header",
  "content": "t:settings_schema.ph_motion.header"
},
{
  "type": "select",
  "id": "entrance_animation",
  "label": "t:settings_schema.ph_motion.entrance_animation",
  "default": "existing",
  "options": [
    { "value": "existing", "label": "t:settings_schema.ph_motion.animation_options.existing" },
    { "value": "ph-fade-up", "label": "t:settings_schema.ph_motion.animation_options.ph_fade_up" },
    { "value": "ph-fade-down", "label": "t:settings_schema.ph_motion.animation_options.ph_fade_down" },
    { "value": "ph-fade-left", "label": "t:settings_schema.ph_motion.animation_options.ph_fade_left" },
    { "value": "ph-fade-right", "label": "t:settings_schema.ph_motion.animation_options.ph_fade_right" },
    { "value": "ph-scale-in", "label": "t:settings_schema.ph_motion.animation_options.ph_scale_in" },
    { "value": "ph-zoom-in", "label": "t:settings_schema.ph_motion.animation_options.ph_zoom_in" }
  ]
}
```

Default is `"existing"` — sections keep their current `data-aos` behavior with zero change. Merchants opt in to new animations.

### Section Template Change

Each target section's wrapper div gets:
```liquid
data-aos="{% if section.settings.entrance_animation != 'existing' %}{{ section.settings.entrance_animation }}{% endif %}"
```

If `entrance_animation` is `"existing"`, the existing `data-aos` attribute in the section template continues to work. If a new animation is selected, it overrides.

### Sections to Modify (15 total)

| Section | Existing data-aos | New fallback |
|---------|------------------|--------------|
| `slideshow.liquid` | (no data-aos on wrapper) | ph-fade-up |
| `hero-video.liquid` | `data-aos="hero__animation"` | ph-scale-in |
| `background-image-text.liquid` | `data-aos="background-media-text__animation"` | ph-fade-up |
| `background-video-text.liquid` | `data-aos="background-media-text__animation"` | ph-fade-up |
| `featured-collection.liquid` | `data-aos="row-of-{{ per_row }}"` | ph-fade-up |
| `featured-collections.liquid` | (no data-aos) | ph-fade-up |
| `featured-product.liquid` | (check template) | ph-scale-in |
| `blog-posts.liquid` | `data-aos="row-of-3"` | ph-fade-up |
| `testimonials.liquid` | `data-aos>` + `data-aos="row-of-N"` | ph-fade-up |
| `text-and-image.liquid` | `data-aos>` (two elements) | ph-fade-left / ph-fade-right |
| `media-text.liquid` | (check template) | ph-fade-up |
| `text-columns.liquid` | `data-aos="row-of-3"` | ph-fade-up |
| `text-with-icons.liquid` | (no data-aos) | ph-fade-up |
| `promo-grid.liquid` | (no data-aos) | ph-scale-in |
| `rich-text.liquid` | (no data-aos) | ph-fade-up |

### Global Motion Settings (in settings_schema.json)

New tab in Theme Settings:
```json
{
  "name": "t:settings_schema.ph_motion.name",
  "settings": [
    {
      "type": "paragraph",
      "content": "t:settings_schema.ph_motion.description"
    },
    {
      "type": "checkbox",
      "id": "ph_motion_enable",
      "label": "t:settings_schema.ph_motion.settings.enable",
      "default": true
    }
  ]
}
```

---

## Layer 2: Page Transitions + Skeleton Loading

### Page Transitions with View Transitions API

File: `assets/ph-transitions.js`

```javascript
(function() {
  if (!document.startViewTransition) return;
  const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (prefersReduced) return;

  // Skip selectors — prevents conflicts with existing theme JS
  const skipSelectors = [
    '[data-no-transition]', '[data-drawer-trigger]', '[data-quick-shop]',
    '.flickity-page-dots a', '.flickity-prev-next-button',
    'a[href^="#"]', 'a[href*="javascript"]', 'a[target="_blank"]',
    'a[download]', '.drawer a', '.modal a', '.popup a',
    '.ajax-cart a', '.js-drawer-open-cart', '.js-drawer-open-nav',
    '.predictive-search__result a', '.site-nav__dropdown a',
    '.slideshow__slide a', '[data-section-type] a[href*="cart"]',
    'a[href*="account"]', 'a[href*="/collections/"] .grid-product__link'
  ];

  document.addEventListener('click', (e) => {
    const link = e.target.closest('a[href]');
    if (!link) return;
    if (link.host !== location.host) return;
    if (skipSelectors.some(sel => link.closest(sel) || link.matches(sel))) return;

    e.preventDefault();
    document.startViewTransition(() => {
      location.href = link.href;
    });
  });
})();
```

CSS in `assets/ph-transitions.css.liquid`:
```css
::view-transition-old(root) {
  animation: 0.25s ease-out both ph-fade-out;
}
::view-transition-new(root) {
  animation: 0.25s ease-in both ph-fade-in;
}
@keyframes ph-fade-out {
  to { opacity: 0; }
}
@keyframes ph-fade-in {
  from { opacity: 0; }
  to { opacity: 1; }
}
@media (prefers-reduced-motion: reduce) {
  ::view-transition-old(root),
  ::view-transition-new(root) {
    animation: none;
  }
}
```

Loaded in `theme.liquid`:
```liquid
{{ 'ph-transitions.css.liquid' | asset_url | stylesheet_tag }}
{{ 'ph-transitions.js' | asset_url | script_tag }}
```

### Skeleton Loading — Scope & Use Cases

Skeletons are for TWO specific scenarios only:

**Use Case 1: View Transitions (page-to-page navigation)**
During the View Transition, the new page's content isn't painted yet. Show a lightweight skeleton overlay with the PHANTOM logo + shimmer for visual continuity.

**Use Case 2: AJAX-Dependent Sections**
- `product-recommendations.liquid` — fetches via Shopify API
- `recently-viewed.liquid` — builds from localStorage + API
- Cart drawer content — loads via `/cart.js`

These sections have a real "loading" gap. Skeletons fill that gap.

### Skeleton Files

| Snippet | Use Case |
|---------|----------|
| `snippets/ph-skeleton-hero.liquid` | View Transition — hero placeholder |
| `snippets/ph-skeleton-grid.liquid` | View Transition + AJAX — product grid placeholder |
| `snippets/ph-skeleton-card.liquid` | AJAX — single product card loading state |
| `snippets/ph-skeleton-cart-item.liquid` | AJAX — cart drawer line item loading state |

Each skeleton renders a shimmer animation:
```html
<div class="ph-skeleton" aria-hidden="true">
  <div class="ph-skeleton__shimmer"></div>
  <div class="ph-skeleton__block" style="height: 60vh; border-radius: 0;"></div>
</div>
```

CSS shimmer:
```css
.ph-skeleton {
  position: relative;
  overflow: hidden;
  background: rgba(128,128,128,0.1);
  border-radius: 4px;
}
.ph-skeleton__shimmer {
  position: absolute;
  inset: 0;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent);
  animation: phShimmer 1.5s infinite;
}
@keyframes phShimmer {
  0% { transform: translateX(-100%); }
  100% { transform: translateX(100%); }
}
@media (prefers-reduced-motion: reduce) {
  .ph-skeleton__shimmer { animation: none; }
}
```

---

## Layer 3: Theme Presets

### Preset Configuration

Add 4 new presets to `settings_data.json` (existing: Default, Dune, Terrain):

| Preset | Vibe | Colors | Fonts |
|--------|------|--------|-------|
| **PHANTOM Minimal** | Clean, modern | White bg, charcoal text, single accent | Sans-serif headings, sans-serif body |
| **PHANTOM Editorial** | Warm, premium | Ivory bg, burgundy accents, warm gray | Serif headings, serif body |
| **PHANTOM Bold** | Dark, high-impact | Black bg, white text, neon accent | Heavy sans-serif headings, sans body |
| **PHANTOM Luxury** | Opulent, elegant | Deep navy, gold accents, cream text | Elegant serif headings, light body |

Each preset sets ALL color, font, layout, and feature options. Switching works via Theme Settings → Presets dropdown (standard Shopify 2.0 behavior using the existing `presets` object in settings_data.json).

### Settings Schema Tab

```json
{
  "name": "t:settings_schema.ph_presets.name",
  "settings": [
    {
      "type": "paragraph",
      "content": "t:settings_schema.ph_presets.description"
    }
  ]
}
```

---

## Risk Assessment & Mitigations

| Risk | Layer | Severity | Mitigation |
|------|-------|----------|------------|
| View Transitions click handler intercepts AJAX links | 2 | **HIGH** | Exclusion selectors for cart, drawers, modals, sliders, predictive search, flickity |
| New animation types conflict with existing AOS CSS | 1 | **MEDIUM** | `ph-` prefix prevents collision; default is `"existing"` — zero change until merchant opts in |
| Disabling animations setting ignores new types | 1 | **LOW** | ph-motion.css.liquid respects `[data-disable-animations=true]` selector |
| View Transitions breaks focus management | 2 | **MEDIUM** | Use `document.startViewTransition().finished.then(() => focusFirstElement())` |
| Skeleton showing when content already loaded | 2 | **LOW** | Only in AJAX sections + View Transitions, not server-rendered sections |
| Preset switching resets merchant customizations | 3 | **LOW** | Standard Shopify behavior — presets are starting points, merchant customizes after |
| `theme.css.liquid` modifications cause breakage | ALL | **HIGH** | We DO NOT modify theme.css.liquid. All new CSS is in separate files. |
| `phantom-vendor.js` modifications cause breakage | ALL | **HIGH** | We DO NOT modify phantom-vendor.js. AOS stays as-is. |

---

## Technical Standards

### CSS
- All new CSS in separate files: `ph-motion.css.liquid`, `ph-transitions.css.liquid`
- DO NOT modify `theme.css.liquid` (13K+ lines, high risk)
- Use `ph-` prefix on all new CSS classes and `data-aos` values
- Respect `[data-disable-animations=true]` via CSS cascade
- `prefers-reduced-motion` — disable ALL animations
- Use `will-change: transform, opacity` on animated elements

### JavaScript
- `ph-transitions.js` uses IIFE (not module) — must run early
- Exclusion selectors list for View Transitions
- No jQuery dependency
- `requestAnimationFrame` for smooth transitions
- Skeleton elements have `aria-hidden="true"`
- View Transitions click handler debounced (prevent double-fire)

### Locales
- Keys under `settings_schema.ph_motion.*` for animation settings
- Keys under `settings_schema.ph_presets.*` for preset settings
- 5 languages: en.default, de, es, fr, it
- Existing schema locale files already have the pattern — add new keys

### Accessibility
- `prefers-reduced-motion` — ALL animations must respect this
- Skeleton loaders must have `aria-hidden="true"`
- View Transitions must restore focus to active element
- `data-aos` elements with `ph-*` types inherit existing AOS accessibility

---

## Files to Create/Modify

| File | Action | Layer |
|------|--------|-------|
| `assets/ph-motion.css.liquid` | Create | 1 |
| `assets/ph-transitions.js` | Create | 2 |
| `assets/ph-transitions.css.liquid` | Create | 2 |
| `snippets/ph-skeleton-hero.liquid` | Create | 2 |
| `snippets/ph-skeleton-grid.liquid` | Create | 2 |
| `snippets/ph-skeleton-card.liquid` | Create | 2 |
| `snippets/ph-skeleton-cart-item.liquid` | Create | 2 |
| `layout/theme.liquid` | Modify | 2 |
| `config/settings_schema.json` | Modify | 1, 3 |
| `config/settings_data.json` | Modify | 3 |
| `locales/en.default.schema.json` | Modify | 1, 3 |
| `locales/de.schema.json` (+ es, fr, it) | Modify | 1, 3 |
| `sections/slideshow.liquid` | Modify | 1 |
| `sections/hero-video.liquid` | Modify | 1 |
| `sections/background-image-text.liquid` | Modify | 1 |
| `sections/background-video-text.liquid` | Modify | 1 |
| `sections/featured-collection.liquid` | Modify | 1 |
| `sections/featured-collections.liquid` | Modify | 1 |
| `sections/featured-product.liquid` | Modify | 1 |
| `sections/blog-posts.liquid` | Modify | 1 |
| `sections/testimonials.liquid` | Modify | 1 |
| `sections/text-and-image.liquid` | Modify | 1 |
| `sections/media-text.liquid` | Modify | 1 |
| `sections/text-columns.liquid` | Modify | 1 |
| `sections/text-with-icons.liquid` | Modify | 1 |
| `sections/promo-grid.liquid` | Modify | 1 |
| `sections/rich-text.liquid` | Modify | 1 |

---

## Implementation Order

1. **Layer 1 — CSS + Schema:** Create `ph-motion.css.liquid`. Add `entrance_animation` setting to 15 sections. Add global motion tab to `settings_schema.json`. Load CSS in `theme.liquid`. Test on dev store.
2. **Layer 2 — Page Transitions:** Create `ph-transitions.js` + `ph-transitions.css.liquid`. Add to `theme.liquid`. Test navigation — verify no AJAX conflicts.
3. **Layer 2 — Skeletons:** Create 4 skeleton snippets. Add `<template>` integration to AJAX sections. Test loading flow.
4. **Layer 3 — Presets:** Add 4 presets to `settings_data.json`. Add presets tab to `settings_schema.json`.
5. **Locale updates:** Add all translation keys across 5 languages.
6. **Commit & push.**

---

*End of design document (updated after Round 1+2 review)*
