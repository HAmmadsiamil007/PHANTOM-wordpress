# Hero Panel

Panel: `phantom_hero`
Sections: `hero`, `home_sections`, `collections`

## Overview

The Hero panel controls the homepage hero banner, home page section toggles, and collection/category display settings. The hero section includes a responsive image system with `<picture>` elements for desktop, tablet, and mobile views. 67 total settings across 3 sections.

## Hero Section (`phantom_section_hero`)

### Settings Table (22 settings)

| Setting ID | Type | Default | CSS Var | Frontend Selector | Live Preview |
|---|---|---|---|---|---|
| `home_banner_heading` | text | — | none | `.hero-headline .hero-headline-accent` | `textContent` |
| `home_banner_title` | text | — | none | `h1.hero-headline` | `textContent` + line breaks |
| `home_banner_description` | text | — | none | `p.hero-subline` | `textContent` + line breaks |
| `home_banner_btn_text` | text | — | none | `.hero-cta-group .btn-primary` | `textContent` |
| `home_banner_btn_url` | text | — | none | `.hero-cta-group .btn-primary` | `href` |
| `home_banner_img1` | image | — | none | `.swiper-slide.hero-slide:first-child .hero-slide-bg img` | `src` |
| `home_banner_img2` | image | — | none | `.swiper-slide.hero-slide:last-child .hero-slide-bg img` | `src` |
| `phantom_hero_banner_image` | image | — | `--hero-image` | `[data-hero-area] img.hero-image` | `src` + `backgroundImage` |
| `phantom_hero_image_tablet` | image | — | none | `[data-hero-area] picture source[data-device="tablet"]` | `srcset` |
| `phantom_hero_image_mobile` | image | — | none | `[data-hero-area] picture source[data-device="mobile"]` | `srcset` |
| `phantom_hero_loading` | select | lazy | none | `img.hero-image` | `loading` attr |
| `phantom_hero_fit` | select | cover | `--hero-object-fit` | `img.hero-image` | CSS var |
| `phantom_hero_position` | select | center | `--hero-object-position`, `--hero-bg-position` | `img.hero-image` | CSS var |
| `phantom_hero_overlay_opacity` | number | 0.5 | `--hero-overlay-opacity` | `.hero-overlay` | CSS var |
| `phantom_hero_tablet_breakpoint` | number | 1024 | `--hero-tablet-bp` | — | CSS var |
| `phantom_hero_mobile_breakpoint` | number | 768 | `--hero-mobile-bp` | — | CSS var |
| `home_banner_enable` | ast-toggle | true | none | `.hero-section` | display toggle |
| `home_banner_height` | int | 600 | none | `.hero-section` | height (px) |
| `home_banner_overlay_color` | ast-color | — | none | `.hero-overlay` | backgroundColor |
| `home_banner_text_color` | ast-color | — | none | `.hero-headline`, `.hero-subline` | color |
| `home_banner_spacing` | int | 0 | none | `.hero-section` | margin-bottom (px) |

### Responsive Hero System

The hero uses a `<picture>` element with 3 source elements:

```html
<div data-hero-area class="hero-area">
  <picture>
    <!-- Mobile source -->
    <source media="(max-width: 768px)"
            srcset="mobile-hero.jpg"
            data-device="mobile">
    <!-- Tablet source -->
    <source media="(max-width: 1024px)"
            srcset="tablet-hero.jpg"
            data-device="tablet">
    <!-- Desktop (default) -->
    <img src="desktop-hero.jpg"
         class="hero-image"
         alt="Hero"
         loading="lazy"
         style="object-fit: cover; object-position: center;">
  </picture>
  <div class="hero-overlay" style="opacity: 0.5;"></div>
</div>
```

### CSS Variable Output

```css
:root {
  --hero-image: url('https://site.com/wp-content/uploads/hero-desktop.jpg');
  --hero-object-fit: cover;
  --hero-object-position: center center;
  --hero-bg-position: center center;
  --hero-overlay-opacity: 0.5;
  --hero-tablet-bp: 1024px;
  --hero-mobile-bp: 768px;
}

/* Tablet override */
@media (max-width: 1024px) {
  :root {
    --hero-image: url('https://site.com/wp-content/uploads/hero-tablet.jpg');
  }
}

/* Mobile override */
@media (max-width: 768px) {
  :root {
    --hero-image: url('https://site.com/wp-content/uploads/hero-mobile.jpg');
  }
}
```

### Fallback Behavior

When tablet or mobile images are not set, the system falls back to the desktop image:

```php
// hero.php CSS module
$tablet_image = get_option( 'phantom_hero_image_tablet', '' );
$mobile_image = get_option( 'phantom_hero_image_mobile', '' );
$desktop_image = get_option( 'phantom_hero_banner_image', '' );

// Fallback chain: tablet → desktop, mobile → desktop
$tablet_src = ! empty( $tablet_image ) ? $tablet_image : $desktop_image;
$mobile_src = ! empty( $mobile_image ) ? $mobile_image : $desktop_image;
```

## Code Flow

```
User uploads hero image
    → phantom_hero_banner_image saved to wp_options
    → hero.php CSS module outputs --hero-image CSS var
    → <picture> element uses CSS var for background-image
    → @media queries handle responsive breakpoints
```

### Hero Renderer

The `Hero_Renderer` class (`includes/renderer/class-hero.php`) outputs the `<picture>` markup:

```php
class Hero_Renderer {
    public function render( $data ) {
        $desktop = $data['banner_image'];
        $tablet = $data['image_tablet'] ?: $desktop;
        $mobile = $data['image_mobile'] ?: $desktop;

        echo '<div data-hero-area class="hero-area">';
        echo '<picture>';
        printf(
            '<source media="(max-width: %dpx)" srcset="%s" data-device="tablet">',
            $data['tablet_breakpoint'],
            esc_url( $tablet )
        );
        printf(
            '<source media="(max-width: %dpx)" srcset="%s" data-device="mobile">',
            $data['mobile_breakpoint'],
            esc_url( $mobile )
        );
        printf(
            '<img src="%s" class="hero-image" alt="%s" loading="%s" style="object-fit:%s;object-position:%s">',
            esc_url( $desktop ),
            esc_attr( $data['title'] ),
            esc_attr( $data['loading'] ),
            esc_attr( $data['fit'] ),
            esc_attr( $data['position'] )
        );
        echo '</picture>';
        echo '</div>';
    }
}
```

### Live Preview Bindings

| Setting | Binding | Notes |
|---|---|---|
| `home_banner_heading` | `textContent` | Accent text above title |
| `home_banner_title` | `textContent` | Main headline, handles `\n` as `<br>` |
| `home_banner_description` | `textContent` | Subtitle, handles `\n` as `<br>` |
| `home_banner_btn_text` | `textContent` | CTA button label |
| `home_banner_btn_url` | `href` | CTA button link |
| `home_banner_img1` | `src` | Slide 1 image |
| `home_banner_img2` | `src` | Slide 2 image |
| `phantom_hero_banner_image` | `src` + `backgroundImage` | Desktop hero |
| `phantom_hero_image_tablet` | `srcset` | Tablet source element |
| `phantom_hero_image_mobile` | `srcset` | Mobile source element |
| `phantom_hero_loading` | `loading` attr | lazy/eager |
| `phantom_hero_fit` | CSS var | `--hero-object-fit` |
| `phantom_hero_position` | CSS var | `--hero-object-position` |
| `phantom_hero_overlay_opacity` | CSS var | `--hero-overlay-opacity` |

### Selective Refresh Partials

Three hero settings support selective refresh (no full page reload):

| Partial | Setting |
|---|---|
| `hero_banner_image` | `phantom_hero_banner_image` |
| `hero_image_tablet` | `phantom_hero_image_tablet` |
| `hero_image_mobile` | `phantom_hero_image_mobile` |

## Home Sections (`phantom_section_home_sections`)

### Settings Table (39 settings)

| Setting ID | Type | CSS Var | Description |
|---|---|---|---|
| `home_products_enable` | ast-toggle | none | Show products section |
| `home_products_title` | text | none | Products section title |
| `home_products_count` | int | none | Number of products to show |
| `home_categories_enable` | ast-toggle | none | Show categories section |
| `home_cta_enable` | ast-toggle | none | Show CTA section |
| `home_top_selling_enable` | ast-toggle | none | Show top selling |
| `home_testimonials_enable` | ast-toggle | none | Show testimonials |
| `home_instagram_enable` | ast-toggle | none | Show Instagram feed |
| `home_benefits_enable` | ast-toggle | none | Show benefits |
| `home_brands_enable` | ast-toggle | none | Show brands |
| `home_blog_enable` | ast-toggle | none | Show blog posts |
| `home_section_spacing` | int | `--home-section-spacing` | Section spacing (px) |
| Various text/repeater settings | string/repeater | — | Section content |

### Section Toggle Flow

```
User toggles section on/off
    → ast-toggle saves boolean
    → home.php template checks get_option('home_products_enable')
    → If true: render section HTML
    → If false: skip section entirely
```

### Section Spacing CSS

```css
:root {
  --home-section-spacing: 80px;
}

.home-section {
  padding-top: var(--home-section-spacing, 80px);
  padding-bottom: var(--home-section-spacing, 80px);
}
```

## Collections (`phantom_section_collections`)

### Settings Table (6 settings)

| Setting ID | Type | CSS Var | Description |
|---|---|---|---|
| `home_categories_title` | text | none | Section title |
| `home_categories_subtitle` | text | none | Section subtitle |
| `home_categories_enable` | ast-toggle | none | Enable collections |
| `collections_style` | ast-select | none | Card style (grid/list/masonry) |
| `collections_count` | int | none | Number to show |
| `collections_repeater` | repeater | none | Custom collection items |

### Collections Repeater

The `collections_repeater` setting allows custom collection items with:
- Category image
- Category name
- Category URL
- Custom order

## Frontend Connection

### Homepage Template

The homepage uses the hero renderer and section toggles:

```php
<?php if ( get_option( 'home_banner_enable', true ) ) : ?>
  <?php
  $hero = new Hero_Renderer();
  $hero->render( array(
    'banner_image' => get_option( 'phantom_hero_banner_image' ),
    'image_tablet'  => get_option( 'phantom_hero_image_tablet' ),
    'image_mobile'  => get_option( 'phantom_hero_image_mobile' ),
    'title'         => get_option( 'home_banner_title' ),
    'description'   => get_option( 'home_banner_description' ),
    // ...
  ));
  ?>
<?php endif; ?>
```

### AETHER Templates

The AETHER `index.html` has static hero markup that gets replaced by `WooCommerce_Injector` on the homepage. The injector reads the same `phantom_hero_*` options and renders dynamic hero content.

### WooCommerce Injection

The `WooCommerce_Injector` skips homepage injection to preserve the static AETHER hero design:

```php
// WooCommerce_Injector.php
if ( is_front_page() ) {
    return; // Preserve AETHER hero
}
```

## CSS Generation Engine Integration

The `hero.php` CSS module (in `includes/custom-css/hero.php`) outputs all hero-related CSS variables:

```php
// hero.php
function hero_css_output() {
    $desktop = get_option( 'phantom_hero_banner_image', '' );
    $tablet = get_option( 'phantom_hero_image_tablet', '' );
    $mobile = get_option( 'phantom_hero_image_mobile', '' );
    $fit = get_option( 'phantom_hero_fit', 'cover' );
    $position = get_option( 'phantom_hero_position', 'center' );
    $overlay = get_option( 'phantom_hero_overlay_opacity', '0.5' );

    echo ':root {';
    if ( $desktop ) {
        echo '--hero-image: url(' . esc_url( $desktop ) . ');';
    }
    echo '--hero-object-fit: ' . esc_attr( $fit ) . ';';
    echo '--hero-object-position: ' . esc_attr( $position ) . ';';
    echo '--hero-bg-position: ' . esc_attr( $position ) . ';';
    echo '--hero-overlay-opacity: ' . esc_attr( $overlay ) . ';';
    echo '}';

    // Responsive overrides
    if ( $tablet ) {
        echo '@media (max-width: 1024px) { :root { --hero-image: url(' . esc_url( $tablet ) . '); } }';
    }
    if ( $mobile ) {
        echo '@media (max-width: 768px) { :root { --hero-image: url(' . esc_url( $mobile ) . '); } }';
    }
}
```

## Related Files

| File | Role |
|---|---|
| `includes/class-settings-registry.php` | Registers all 67 hero/home/collection settings |
| `includes/Settings/class-settings-loader.php` | Defines sections `phantom_section_hero`, `_home_sections`, `_collections` |
| `includes/custom-css/hero.php` | CSS module — outputs hero CSS vars with @media queries |
| `includes/renderer/class-hero.php` | Hero renderer — outputs `<picture>` markup |
| `includes/Engine/Data_Engine.php` | AETHER mapping layer for hero vars |
| `admin/js/customizer-preview.js` | Live preview bindings for hero settings |
| `includes/partial-renderers.php` | Selective refresh partials for hero images |
| `templates/home.php` | Homepage template — hero + section toggles |
| `frontend/assets/js/phantom-injector.js` | WooCommerce homepage injection guard |
| `frontend/html/index.html` | AETHER static hero markup |
