# T0.5 — Refactor Renderers to Implement RendererInterface + Use inject()

**Goal:** Make all 5 renderers use the base `inject()` method instead of manual HTML building or str_replace.

**Depends on:** T0.1 (RendererInterface), T0.4 (component templates exist)

## Files

### 1. `class-component-renderer.php` — abstract base
Add `implements RendererInterface` and fix `inject()` to be case-insensitive.

Current `inject()` uses `$data[$m[1]]` which is case-sensitive. Change to lowercase both sides so `{{URL}}` matches `$data['url']`:

```php
<?php
declare(strict_types=1);

namespace PhantomCore\Renderer;

use PhantomCore\Contracts\RendererInterface;

defined('ABSPATH') || exit;

abstract class Component_Renderer implements RendererInterface {

  abstract public function render(array $data): string;

  public function render_collection(array $data_set): string {
    $output = '';
    foreach ($data_set as $data) {
      $output .= $this->render($data);
    }
    return $output;
  }

  protected function load_template(string $name): string {
    $path = PHANTOM_CORE_PATH . 'frontend/html/components/' . $name . '.html';
    if (!file_exists($path)) return '';
    return (string) file_get_contents($path);
  }

  protected function inject(string $template, array $data): string {
    return preg_replace_callback('/\{\{(\w+)\}\}/', function($m) use ($data) {
      $key = strtolower($m[1]);
      return isset($data[$key]) ? $data[$key] : $m[0];
    }, $template);
  }
}
```

### 2. `class-product-card.php`
Change `render()` to use `$this->inject()` instead of `str_replace`:

```php
public function render(array $data): string {
    $badge = '';
    if (!empty($data['on_sale'])) {
      $badge = '<span class="product-badge badge-sale">Sale</span>';
    } elseif (!empty($data['is_featured'])) {
      $badge = '<span class="product-badge badge-new">New</span>';
    }

    $rating = '';
    if (!empty($data['rating'])) {
      $full = floor((float) $data['rating']);
      $stars = '';
      for ($i = 0; $i < 5; $i++) {
        $stars .= $i < $full ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
      }
      $rating = '<div class="product-rating">' . $stars . '<span>(' . (int) $data['reviews_count'] . ')</span></div>';
    }

    $categories = '';
    if (!empty($data['categories'])) {
      $cats = array_slice($data['categories'], 0, 2);
      $categories = '<div class="product-tagline">' . esc_html(implode(', ', array_column($cats, 'name'))) . '</div>';
    }

    $price = esc_html($data['price']);
    if (!empty($data['on_sale'])) {
      $price = '<span class="price-sale">' . esc_html($data['sale_price']) . '</span>' .
               '<span class="price-original">' . esc_html($data['regular_price']) . '</span>';
    }

    $atc = '<a href="' . esc_url($data['url']) . '" class="btn btn-sm btn-primary" data-magnetic="0.12">View Details</a>';

    return $this->inject($this->template, [
      'badge' => $badge,
      'url' => esc_url($data['url']),
      'image' => esc_url($data['image']),
      'name' => esc_attr($data['name']),
      'rating' => $rating,
      'categories' => $categories,
      'price' => $price,
      'atc_button' => $atc,
    ]);
  }
```

The template file `frontend/html/components/product-card.html` has matching lowercase keys after inject() strtolower: `{{BADGE}}` → `badge`, `{{URL}}` → `url`, etc.
But the template also has placeholders like `{{PRODUCT_ID}}`, `{{PERMALINK}}`, `{{TITLE}}`, `{{ADD_TO_CART_URL}}`, `{{ADD_TO_CART_TEXT}}`, `{{CATEGORIES}}`, `{{REVIEW_COUNT}}`, `{{RATING_HTML}}` which this renderer doesn't supply. That's OK — inject() leaves unmatched placeholders intact (they'd still show `{{TITLE}}` in the rendered HTML if the template is loaded).

To make the component template match this renderer, the template `product-card.html` from T0.4 needs to use the placeholders this renderer provides: `{{BADGE}}`, `{{URL}}`, `{{IMAGE}}`, `{{NAME}}`, `{{RATING}}`, `{{CATEGORIES}}`, `{{PRICE}}`, `{{ATC_BUTTON}}`. If the loaded template doesn't have these, the renderer's `default_template()` fallback is used instead.

No change needed to the template — the renderer loads template first, falls back to default_template() if not found or empty. The default_template() already uses matching `{{BADGE}}`, `{{URL}}`, etc.

### 3. `class-category-card.php`
Change `render()` to use `$this->inject()`:

```php
public function render(array $data): string {
    return $this->inject($this->template, [
      'url' => esc_url($data['url']),
      'image' => esc_url($data['image']),
      'name' => esc_html($data['name']),
      'count' => (int) $data['count'] . ' items',
      'cta' => 'Shop ' . esc_html($data['name']),
    ]);
  }
```

### 4. `class-hero.php`
Change `render()` to use `$this->inject()` with a hero template string:

```php
public function render(array $data): string {
    $image = $data['image'];
    $image_tablet = !empty($data['enable_responsive']) && !empty($data['image_tablet']) ? $data['image_tablet'] : $image;
    $image_mobile = !empty($data['enable_responsive']) && !empty($data['image_mobile']) ? $data['image_mobile'] : $image;

    $template = '<section class="hero-section" style="--hero-overlay-opacity: {{OVERLAY_OPACITY}}">';
    if (!empty($data['enable_responsive'])) {
      $template .= '<picture>';
      if ($image_tablet !== $image) {
        $template .= '<source media="(max-width: ' . (int) $data['tablet_breakpoint'] . 'px)" srcset="{{IMAGE_TABLET}}">';
      }
      if ($image_mobile !== $image) {
        $template .= '<source media="(max-width: ' . (int) $data['mobile_breakpoint'] . 'px)" srcset="{{IMAGE_MOBILE}}">';
      }
      $template .= '<img src="{{IMAGE}}" alt="{{TITLE}}" class="hero-image" loading="{{LOADING}}">';
      $template .= '</picture>';
    } else {
      $template .= '<img src="{{IMAGE}}" alt="{{TITLE}}" class="hero-image" loading="{{LOADING}}">';
    }
    $template .= '<div class="hero-content">
      <h1 class="hero-title">{{TITLE}}</h1>
      {{SUBTITLE_HTML}}
      {{DESCRIPTION_HTML}}
      <a href="{{BTN_URL}}" class="btn btn-primary hero-cta">{{BTN_TEXT}}</a>
    </div>
    </section>';

    return $this->inject($template, [
      'image' => esc_url($image),
      'image_tablet' => esc_url($image_tablet),
      'image_mobile' => esc_url($image_mobile),
      'title' => esc_html($data['title']),
      'subtitle_html' => !empty($data['subtitle']) ? '<p class="hero-subtitle">' . esc_html($data['subtitle']) . '</p>' : '',
      'description_html' => !empty($data['description']) ? '<p class="hero-description">' . esc_html($data['description']) . '</p>' : '',
      'btn_url' => esc_url($data['btn_url']),
      'btn_text' => esc_html($data['btn_text']),
      'overlay_opacity' => esc_attr($data['overlay_opacity']),
      'loading' => esc_attr($data['loading']),
    ]);
  }
```

### 5. `class-footer.php`
Change `render()` to use `$this->inject()`:

```php
public function render(array $data): string {
    $copyright = $data['copyright'] ?? '&copy; ' . date('Y') . ' All rights reserved.';
    $template = '<footer class="site-footer" role="contentinfo">
      <div class="container">
        <div class="footer-widgets">{{WIDGETS}}</div>
        <div class="footer-bottom">
          <p class="footer-copyright">{{COPYRIGHT}}</p>
        </div>
      </div>
    </footer>';

    return $this->inject($template, [
      'widgets' => $data['widgets'] ?? '',
      'copyright' => wp_kses_post($copyright),
    ]);
  }
```

## Verification
```bash
php -d error_reporting=E_ALL -l phantom-core/includes/renderer/class-component-renderer.php
php -d error_reporting=E_ALL -l phantom-core/includes/renderer/class-product-card.php
php -d error_reporting=E_ALL -l phantom-core/includes/renderer/class-category-card.php
php -d error_reporting=E_ALL -l phantom-core/includes/renderer/class-hero.php
php -d error_reporting=E_ALL -l phantom-core/includes/renderer/class-footer.php
```

Expected: All return `No syntax errors detected`.

## Commit
```bash
cd C:\Users\hamma\Downloads\wordpress && git add phantom-core/includes/renderer/ && git commit -m "feat(phase0): refactor 5 renderers to implement RendererInterface and use inject()"
```
