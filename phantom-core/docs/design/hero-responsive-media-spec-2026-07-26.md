# Design Spec: Hero Banner Responsive Media System

## Feature Name
Responsive Hero Media

## Location
Customize → Hero Banner → Hero Media

## Motivation
Allow clients to upload device-specific hero images (desktop required, tablet optional, mobile optional) with automatic fallback. Output semantic `<picture>` elements for native responsive image support.

## Controls

| Setting ID | Type | Default | Required |
|---|---|---|---|
| `hero_banner_image` (existing) | image | '' | YES |
| `hero_image_tablet` | image | '' | NO |
| `hero_image_mobile` | image | '' | NO |
| `hero_enable_responsive` | ast-toggle | 1 | — |
| `hero_tablet_breakpoint` | number | 1024 | — |
| `hero_mobile_breakpoint` | number | 768 | — |
| `hero_loading` | ast-select | auto | — |
| `hero_fit` | ast-select | cover | — |
| `hero_position` | ast-select | center | — |
| `hero_overlay_opacity` | number | 50 | — |

## Fallback Logic
- If no tablet image → use desktop
- If no mobile image → use desktop
- If responsive disabled → use desktop only

## Frontend Rendering
```html
<picture>
  <source media="(max-width: 767px)" srcset="{mobile}">
  <source media="(max-width: 1024px)" srcset="{tablet}">
  <img src="{desktop}" loading="{loading}" class="hero-image">
</picture>
```

## CSS Generation
CSS vars for `--hero-object-fit`, `--hero-object-position`, `--hero-overlay-opacity`.

## Backward Compatibility
Existing `hero_banner_image` maps to desktop. Existing sites continue working.

## Future Extensibility
Settings architecture supports future media types: Video, Lottie, Three.js, Slider, Custom HTML.
