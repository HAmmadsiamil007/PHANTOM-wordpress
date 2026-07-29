# Site Identity Panel

Panel: `phantom_branding`
Section: `branding` (`phantom_section_branding`)

## Overview

The Site Identity panel manages the site's visual identity — logo, favicon, site title, and tagline. All settings are persisted via `wp_options` and output through the Phantom settings system.

## Settings Table

| Setting ID | Type | Default | CSS Var | Frontend Selector | Live Preview |
|---|---|---|---|---|---|
| `general_site_logo` | image | — | none | `.brand-logo img` | `src` / `backgroundImage` binding |
| `phantom_branding_favicon` | image | — | none | `link[rel="icon"]` | `href` binding |
| `blogname` | string | — | none | `.brand-logo`, `[data-phantom="site_name"]` | `textContent` binding |
| `blogdescription` | string | — | none | `.site-tagline` | `textContent` binding |
| `general_site_logo_width` | int | 150 | `--logo-width` | `.brand-logo img` | CSS var update |
| `general_site_logo_height` | int | 50 | `--logo-height` | `.brand-logo img` | CSS var update |
| `branding_logo_spacing` | int | 12 | `--logo-spacing` | `.brand-logo` | CSS var update |
| `general_dark_logo` | image | — | none | `.brand-logo img` (dark mode) | `src` binding |
| `general_favicon` | image | — | none | `link[rel="icon"]` | `href` binding |

## Code Flow

```
User uploads logo in Customizer
    → WP_Customize_Image_Control saves URL to wp_options
    → phantom_general_site_logo option updated
    → customizer-preview.js binding updates .brand-logo img src in real-time
    → On page load, Asset_Engine::inject_images() outputs <img> tag with saved URL
```

### Upload Path

1. User selects image via `WP_Customize_Image_Control`
2. Image uploaded to WordPress media library (`wp-content/uploads/YYYY/MM/`)
3. Image URL saved to `phantom_general_site_logo` option via `Settings_Registry`
4. Customizer preview JS picks up the new URL and applies it to the preview frame

### Storage

All site identity settings are stored in `wp_options` via the Phantom settings system:

```
wp_options:
  phantom_general_site_logo    → https://site.com/wp-content/uploads/.../logo.png
  phantom_blogname              → "My Site"
  phantom_blogdescription       → "My Tagline"
  phantom_general_site_logo_width → 150
  ...
```

## CSS Variable Output

When `general_site_logo_width`, `general_site_logo_height`, or `branding_logo_spacing` are set, the CSS Generation Engine outputs:

```css
:root {
  --logo-width: 150px;
  --logo-height: 50px;
  --logo-spacing: 12px;
}
```

These variables are consumed by the theme's CSS:

```css
.brand-logo img {
  max-width: var(--logo-width, 150px);
  max-height: var(--logo-height, 50px);
}
.brand-logo {
  gap: var(--logo-spacing, 12px);
}
```

## Frontend Connection

### Header Template (`header.php`)

The logo appears via the `custom_logo` theme support or the `phantom_general_site_logo` option:

```php
<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="brand-logo">
  <?php if ( $logo_url ) : ?>
    <img src="<?php echo esc_url( $logo_url ); ?>"
         alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"
         width="<?php echo absint( $logo_width ); ?>"
         height="<?php echo absint( $logo_height ); ?>">
  <?php endif; ?>
  <span data-phantom="site_name"><?php bloginfo( 'name' ); ?></span>
</a>
```

### AETHER Templates

The AETHER templates (index.html, shop.html, etc.) use `.brand-logo img` as the selector for the site logo. The `phantom-data.js` data injection system replaces static logo references with the dynamically configured image.

### Favicon

The favicon is output via `wp_site_icon()` or the `phantom_branding_favicon` option in the document `<head>`:

```html
<link rel="icon" href="https://site.com/wp-content/uploads/.../favicon.ico" sizes="32x32">
```

## Live Preview Behavior

| Setting | Binding Type | Notes |
|---|---|---|
| `general_site_logo` | `src` | Direct image URL replacement |
| `phantom_branding_favicon` | `href` | Updates `<link rel="icon">` |
| `blogname` | `textContent` | Updates all matching elements |
| `blogdescription` | `textContent` | Updates tagline text |
| `general_site_logo_width` | CSS var | Sets `--logo-width` in preview |
| `general_site_logo_height` | CSS var | Sets `--logo-height` in preview |
| `branding_logo_spacing` | CSS var | Sets `--logo-spacing` in preview |

All bindings are registered in `admin/js/customizer-preview.js` via the auto-bind system. Logo settings use the `image` type binding which handles both `src` and `backgroundImage` properties.

## Dark Mode Logo

`general_dark_logo` provides an alternative logo for dark mode. The theme uses the `prefers-color-scheme: dark` media query to swap between the primary and dark logo:

```css
@media (prefers-color-scheme: dark) {
  .brand-logo img:not([data-theme="light"]) {
    content: var(--dark-logo);
  }
}
```

## Related Files

| File | Role |
|---|---|
| `includes/class-settings-registry.php` | Registers all 9 settings |
| `includes/Settings/class-settings-loader.php` | Defines section `phantom_section_branding` |
| `admin/js/customizer-preview.js` | Live preview bindings |
| `includes/class-custom-css.php` | Outputs `--logo-width`, `--logo-height`, `--logo-spacing` |
| `templates/header.php` | Renders logo markup |
| `frontend/assets/js/phantom-data.js` | Data injection for SPA templates |
