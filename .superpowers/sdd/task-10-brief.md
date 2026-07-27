# T0.10 — Wire Hero::render() + Footer::render() in WooCommerce_Injector

**Goal:** The WooCommerce_Injector already instantiates Hero but never calls `$this->hero->render()`. Footer doesn't exist yet. Wire both into the inject() flow so the static hero/footer sections in HTML templates are replaced with dynamic server-side rendered output.

## Files to Modify

### `phantom-core/includes/Engine/WooCommerce_Injector.php`

READ the existing file first. Changes:

1. **Add import** for Footer at top (after existing renderer imports):
```php
use PhantomCore\Renderer\Footer;
```

2. **Add Footer property** (after existing `private Hero $hero;`):
```php
  private Footer $footer;
```

3. **Add Footer construction** in constructor (after `$this->hero = new Hero();`):
```php
    $this->footer = new Footer();
```

4. **Add helper method** `get_footer_data()` — builds data array for Footer::render():
```php
  private function get_footer_data(): array {
    $widgets = '';
    if (function_exists('dynamic_sidebar') && is_active_sidebar('phantom-footer-widgets-1')) {
      ob_start();
      dynamic_sidebar('phantom-footer-widgets-1');
      $widgets = ob_get_clean();
    }
    return [
      'widgets' => $widgets ?: '<div class="footer-widget-placeholder"><p>Add widgets to the Footer area.</p></div>',
      'copyright' => get_option('phantom_footer_copyright', '&copy; ' . date('Y') . ' All rights reserved.'),
    ];
  }
```

6. **Modify `inject()` method** — replace the existing inject() method body. Use inline preg_replace with regex targeting the HTML class attribute:

Replace the CURRENT `inject()` method (lines 35-66 in the existing file starting with `public function inject(...)`) with:

```php
  public function inject(string $html, string $slug): string {
    // Inject hero section (replace static page-hero block)
    try {
      $hero_html = $this->hero->render($this->hero_adapter->normalize());
      $html = preg_replace(
        '/<section[^>]*class="[^"]*page-hero[^"]*"[^>]*>.*?<\/section>/s',
        $hero_html,
        $html,
        1
      );
    } catch (\Throwable $e) {
      // Fall through — keep static template hero
    }

    switch (true) {
      case 'shop' === $slug:
      case strpos($slug, 'category/') === 0:
        $html = $this->inject_shop_content($html);
        break;
      case 'product' === $slug:
      case 'product-detail' === $slug:
      case strpos($slug, 'product/') === 0:
        $html = $this->inject_product_content($html);
        break;
      case 'cart' === $slug:
        $html = $this->inject_cart_content($html);
        break;
      case 'checkout' === $slug:
        $html = $this->inject_checkout_content($html);
        break;
      case '' === $slug:
      case 'index' === $slug:
        $html = $this->inject_homepage_products($html);
        $html = $this->inject_homepage_categories($html);
        break;
      case 'wishlist' === $slug:
        $html = $this->inject_wishlist_content($html);
        break;
      case 'account' === $slug:
      case 'my-account' === $slug:
        $html = $this->inject_account_content($html);
        break;
    }

    // Inject footer section (replace static footer block)
    try {
      $footer_html = $this->footer->render($this->get_footer_data());
      $html = preg_replace(
        '/<footer[^>]*class="[^"]*footer[^"]*"[^>]*>.*?<\/footer>/s',
        $footer_html,
        $html,
        1
      );
    } catch (\Throwable $e) {
      // Fall through — keep static template footer
    }

    return $html;
  }
```

## Verification
```bash
php -l phantom-core/includes/Engine/WooCommerce_Injector.php
```
Expected: `No syntax errors detected`

## Commit
```bash
cd C:\Users\hamma\Downloads\wordpress
git add phantom-core/includes/Engine/WooCommerce_Injector.php
git commit -m "feat(phase0): wire Hero::render() + Footer::render() in WooCommerce_Injector"
```
