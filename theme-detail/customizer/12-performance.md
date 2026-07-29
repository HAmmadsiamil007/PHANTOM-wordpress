# Customizer Panel: Performance (`phantom_performance`)

## Overview

The Performance panel controls asset optimization (CSS/JS minification, inlining, deferral), caching, and SEO metadata generation. Performance settings directly affect how the CSS Generation Engine and Asset Engine process and deliver assets. SEO settings generate `<meta>` tags injected into `<head>` via the Asset Engine.

## Panel ID

`phantom_performance`

## Sections

### 1. Performance Settings (`phantom_section_performance`)

**14 settings** controlling CSS/JS optimization, caching, and resource loading.

#### CSS Optimization

| Setting Key | Type | Default | Description |
|---|---|---|---|
| `performance_minify_css` | `ast-toggle` | `false` | Minify generated CSS (strip comments, whitespace). |
| `performance_inline_css` | `ast-toggle` | `false` | Inline critical CSS in `<style>` tag instead of external file. |
| `cache_generated_css` | `ast-toggle` | `true` | Enable file-based CSS caching. |
| `performance_cache_generated_css` | `ast-toggle` | `true` | Alias/alternate key for CSS caching (backward compat). |

#### JS Optimization

| Setting Key | Type | Default | Description |
|---|---|---|---|
| `performance_minify_js` | `ast-toggle` | `false` | Minify plugin JS files. |
| `performance_defer_js` | `ast-toggle` | `true` | Add `defer` attribute to non-critical JS. |
| `performance_async_js` | `ast-toggle` | `false` | Add `async` attribute to scripts. |

#### Resource Loading

| Setting Key | Type | Default | Description |
|---|---|---|---|
| `performance_preload_key` | `ast-toggle` | `true` | Add `<link rel="preload">` for key resources (fonts, critical CSS). |
| `performance_dns_prefetch` | `ast-toggle` | `true` | Add `<link rel="dns-prefetch">` for external domains (Google Fonts, CDN). |
| `performance_lazy_images` | `ast-toggle` | `true` | Add `loading="lazy"` to images below the fold. |

---

## Code Flow: CSS Minification

```
performance_minify_css = true
  → Phantom_Custom_CSS::get_css() is called during wp_head
  → Checks: get_option('phantom_performance_minify_css')
  → If true: calls minify_css($raw_css)
    → preg_replace: strips CSS comments (/\* ... \*/)
    → preg_replace: collapses whitespace (multiple spaces → single space)
    → preg_replace: removes newlines
  → Minified CSS output in <style> tag (or external file if caching enabled)
```

### CSS Caching

```
cache_generated_css = true
  → Phantom_Custom_CSS generates CSS
  → Writes to wp-content/cache/phantom-{hash}.css
  → Subsequent requests serve cached file via <link> tag
  → Cache invalidated when any setting changes (hash includes all option values)
```

### JS Deferral

```
performance_defer_js = true
  → Phantom_Assets::enqueue_scripts() adds 'defer' to script tags
  → Applies to: phantom-injector.js, phantom-data.js, phantom-bridge.js
  → Excludes: jQuery (loaded normally for compatibility)
```

### Resource Preloading

```
performance_preload_key = true
  → Adds <link rel="preload" href="..." as="font"> for active Google Fonts
  → Adds <link rel="preload" href="phantom-inline.css" as="style">
```

---

### 2. SEO Settings (`phantom_section_seo`)

**8 settings** controlling meta tags, structured data, and third-party analytics.

#### Meta Tags

| Setting Key | Type | Default | Description |
|---|---|---|---|
| `seo_meta_description` | `text` | (empty) | Default meta description. Used when no page-specific description exists. |
| `seo_og_image` | `image` | (empty) | Default Open Graph image URL. Used when no page-specific OG image exists. |
| `seo_twitter_handle` | `string` | (empty) | Twitter @handle for `twitter:site` meta tag. |

#### Third-Party Integration

| Setting Key | Type | Default | Description |
|---|---|---|---|
| `seo_google_analytics` | `string` | (empty) | GA tracking ID (format: `G-XXXXXXXXXX` or `UA-XXXXXXXX-X`). |
| `seo_facebook_pixel` | `string` | (empty) | Facebook Pixel ID. |

#### SEO Features

| Setting Key | Type | Default | Description |
|---|---|---|---|
| `seo_enable_schema` | `ast-toggle` | `true` | Enable JSON-LD structured data (Organization, WebSite, BreadcrumbList). |
| `seo_enable_sitemap` | `ast-toggle` | `true` | Enable XML sitemap generation. |
| `seo_enable_breadcrumbs` | `ast-toggle` | `true` | Enable breadcrumb navigation. |

### SEO Code Flow

```
SEO_Engine::generate_meta_tags() runs on wp_head
  → Reads phantom_seo_meta_description from options
  → Outputs <meta name="description" content="...">
  → Reads phantom_seo_og_image → outputs <meta property="og:image" content="...">
  → Reads phantom_seo_twitter_handle → outputs <meta name="twitter:site" content="@handle">
  → Reads phantom_seo_google_analytics → outputs GA script tag
  → Reads phantom_seo_facebook_pixel → outputs FB pixel script tag
  → Reads phantom_seo_enable_schema → generates JSON-LD <script type="application/ld+json">
  → Reads phantom_seo_enable_breadcrumbs → renders BreadcrumbList markup
```

### JSON-LD Output Example

```json
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "Site Name",
  "url": "https://example.com",
  "logo": "https://example.com/logo.png"
}
```

---

## Frontend Integration

Performance settings affect the `<head>` output:

```html
<!-- Preloaded resources -->
<link rel="preload" href="fonts/inter.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="phantom-inline.css" as="style">

<!-- DNS prefetch -->
<link rel="dns-prefetch" href="//fonts.googleapis.com">
<link rel="dns-prefetch" href="//cdn.example.com">

<!-- CSS (minified or not, inline or external) -->
<style>/* minified CSS or <link> to cached file */</style>

<!-- JS (deferred or not) -->
<script src="phantom-injector.js" defer></script>

<!-- SEO meta tags -->
<meta name="description" content="...">
<meta property="og:image" content="...">
<meta name="twitter:site" content="@handle">
<script type="application/ld+json">{"@type":"Organization",...}</script>
```

---

## Developer Notes

- **CSS minification**: The `minify_css()` method uses regex-based stripping (not a full CSS parser). It removes comments, collapses whitespace, and strips newlines. For production-grade minification, consider using a dedicated library.
- **CSS caching**: Cache files are stored in `wp-content/cache/` with a hash filename. The hash includes all settings values, so any setting change invalidates the cache. Delete `phantom-*.css` files in the cache directory to force regeneration.
- **Async vs Defer**: `async` is for independent scripts (analytics, ads). `defer` is for scripts that need DOM order. The plugin defaults to `defer` for correctness; `async` should only be enabled for specific third-party scripts.
- **SEO meta tags**: The `SEO_Engine` class in `includes/class-seo-engine.php` handles all meta tag generation. It checks for page-specific overrides (post meta `phantom_meta_description`, `phantom_og_image`) before falling back to global settings.
- **Structured data**: Schema.org JSON-LD is generated for Organization, WebSite, and BreadcrumbList types. Product schema is generated separately by WooCommerce integration.
- **GA/FB**: Tracking scripts are only output when the corresponding setting is non-empty. No conditional loading logic — empty string = no script tag.
