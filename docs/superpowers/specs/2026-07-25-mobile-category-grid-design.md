# Mobile Category Grid — Single Column Stack

## Problem
The "Find Your Fit" category grid on mobile (≤ 768px) uses named grid areas (`"large top-mid top-right" / "large bottom-left accent"`) that define 3 columns. The ≤ 768px breakpoint sets `grid-template-columns: 1fr` but does NOT reset `grid-template-areas`, causing the browser to render broken overflow layout with cards spilling outside the container.

## Design
Single-column vertical stack. All 4 cards render full-width, one per row, with 16:9 aspect ratio.

### Layout
```
┌──────────────────────────┐
│     Accessories          │  ← grid-area: large
├──────────────────────────┤
│      Clothing            │  ← grid-area: top-mid
├──────────────────────────┤
│       Shoes              │  ← grid-area: bottom-left
├──────────────────────────┤
│   ★ Toys (accent)        │  ← grid-area: accent (gold bg)
└──────────────────────────┘
```

### Specification
- **Breakpoint**: `@media (max-width: 768px)` — matches the existing Tablet Portrait breakpoint
- **Grid**: `grid-template-columns: 1fr; grid-template-areas: "large" "top-mid" "bottom-left" "accent";`
- **Aspect ratio**: All cards `16:9`
- **Gap**: 20px (existing)
- **Min-height**: Remove `min-height: 500px` on mobile (let cards size naturally)
- **Content**: Bottom-aligned text (existing), left-aligned
- **Toys accent**: Gold gradient background (`--accent` class) preserved, card appears last

### Visual details
- Dark gradient overlay (existing) on each card image
- Category name, item count, "Shop Now" CTA as existing
- Card content padding: 16px (≤ 576px), 20px (768px)
- No `--large` aspect ratio distinction on mobile — all cards equal height

## Files to modify

### 1. `frontend/assets/css/responsive.css` — ≤ 768px breakpoint
Add `grid-template-areas` and remove `min-height`:

```css
.category-grid {
    grid-template-columns: 1fr;
    grid-template-areas:
        "large"
        "top-mid"
        "bottom-left"
        "accent";
    min-height: auto;
}
```

### 2. `frontend/assets/css/style.css` — desktop baseline (already correct)
No change needed. The desktop named-grid-area layout remains:
```css
grid-template-columns: 1.2fr 1fr 1fr;
grid-template-rows: 1fr 1fr;
grid-template-areas:
    "large top-mid top-right"
    "large bottom-left accent";
```
