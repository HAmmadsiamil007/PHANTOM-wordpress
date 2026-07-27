# P1.8 — Refactor Shell to use container

**File:** `phantom-core/templates/shell.php`

**Namespace:** `PhantomCore`

## Required Changes

**Add use statements:**
```php
use PhantomCore\Engine\Container;
use PhantomCore\Engine\Container_Config;
```

**Change `init()` method:**

FROM:
```php
public function init(): void {
    $this->engine = new Render_Engine();
    // ... WooCommerce filters ...
    // ... hook registrations ...
}
```

TO:
```php
public function init(?Container $container = null): void {
    $container = $container ?? new Container();
    Container_Config::configure($container);
    
    $this->engine = $container->get(Render_Engine::class);
    
    // WooCommerce SPA shell compatibility filters
    if (class_exists('WooCommerce')) {
        add_filter('woocommerce_disable_template_redirect', '__return_true');
        add_filter('woocommerce_cart_redirect_after_add', '__return_false');
        add_filter('woocommerce_enable_ajax_add_to_cart', '__return_false');
    }
    
    add_action('template_redirect', [$this, 'init_wc_session'], 5);
    add_action('save_post', [$this, 'invalidate_cache_on_save'], 10, 1);
    add_action('delete_post', [$this, 'invalidate_cache_on_save'], 10, 1);
    add_action('woocommerce_delete_product', [$this, 'invalidate_cache_on_save'], 10, 1);
    add_action('template_redirect', [$this, 'handle_request'], 10);
}
```

The `?Container $container = null` parameter allows injecting a pre-configured container for testing.

**Everything else stays the same** — all other methods remain unchanged.

## Verification
```bash
php -l phantom-core/templates/shell.php
```

## Commit
```bash
cd C:\Users\hamma\Downloads\wordpress
git add phantom-core/templates/shell.php
git commit -m "feat(phase1): refactor Shell to resolve from Container"
```

Write report to `.superpowers/sdd/phase1-task-08-report.md`
