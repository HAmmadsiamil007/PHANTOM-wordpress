# Accessibility Panel — `phantom_accessibility`

## Panel Overview

| Property | Value |
|----------|-------|
| Panel ID | `phantom_accessibility` |
| Sections | 1 |
| Total Settings | 6 |
| Control Types | `ast-toggle` (5), `number` (1) |

---

## Section: `accessibility` (`phantom_section_accessibility`)

### Settings Table

| Setting Key | Control Type | Default | Description |
|-------------|-------------|---------|-------------|
| `a11y_skip_link` | `ast-toggle` | `true` | Enable skip-to-content link |
| `a11y_focus_outline` | `ast-toggle` | `true` | Enable visible focus outlines |
| `a11y_high_contrast` | `ast-toggle` | `false` | Enable high contrast mode |
| `a11y_reduce_motion` | `ast-toggle` | `true` | Respect `prefers-reduced-motion` |
| `a11y_font_size_multiplier` | `number` | `1.0` | Base font size multiplier (0.8–1.5) |
| `a11y_announce_page_changes` | `ast-toggle` | `false` | Live region announcements for SPA navigation |

### Setting Details

#### `a11y_skip_link` (ast-toggle)
When enabled, `View_Engine::inject_all()` injects a skip-to-content link as the first child element in `<body>`:

```html
<a class="skip-link" href="#main-content">Skip to content</a>
```

CSS positions the link off-screen (`position: absolute; left: -9999px`) until focused, at which point it becomes visible.

#### `a11y_focus_outline` (ast-toggle)
When enabled, injects visible focus styles:

```css
:focus-visible {
    outline: 2px solid var(--primary--color);
    outline-offset: 2px;
}
```

#### `a11y_high_contrast` (ast-toggle)
When enabled, adds `.high-contrast` class to `<body>`. Theme CSS targets this class to increase contrast ratios.

#### `a11y_reduce_motion` (ast-toggle)
When enabled, injects reduced-motion CSS:

```css
@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
        animation-duration: 0.01ms !important;
    }
}
```

#### `a11y_font_size_multiplier` (number)
Base font size multiplier. Applied as a CSS custom property `--a11y-font-scale` that multiplies all `rem` values:

```
Range: 0.8 – 1.5
Default: 1.0
Step: 0.1
```

#### `a11y_announce_page_changes` (ast-toggle)
When enabled, injects a live `aria-live` region that announces page title changes during SPA navigation.

---

## Code Flow

```
User enables skip link
    ↓
phantom_a11y_skip_link saved to option
    ↓
View_Engine::inject_all() checks setting
    ↓
Injects <a class="skip-link" href="#main-content">Skip to content</a>
as first child of <body>
    ↓
CSS positions it off-screen until focused
```

## Frontend Behavior

The skip link, focus outlines, and reduced-motion CSS are injected by **View_Engine** during the render pipeline. These are accessibility features that enhance the AETHER templates **without modifying their HTML**.

## Key Files

| File | Purpose |
|------|---------|
| `includes/Engine/View_Engine.php` | `inject_all()` method — injects a11y elements |
| `includes/Engine/Asset_Engine.php` | `inject_essential_only()` includes a11y CSS |
| `frontend/assets/css/style.css` | a11y CSS rules |
