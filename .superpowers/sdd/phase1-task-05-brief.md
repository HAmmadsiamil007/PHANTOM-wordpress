# P1.5 — Refactor Render_Engine for constructor injection

**File:** `phantom-core/includes/Engine/Render_Engine.php`

**Namespace:** `PhantomCore\Engine`

## Current State
Constructor creates all deps via `new`:
```php
private Template_Loader $template_loader;
private SEO_Engine $seo;
private Security_Headers $security;
private Asset_Loader $assets;
private ?int $resolved_product_id = null;
private ?int $resolved_post_id = null;
private ?string $category_slug = null;

public function __construct() {
    $this->template_loader = new Template_Loader();
    $this->seo = new SEO_Engine();
    $this->security = new Security_Headers();
    $this->assets = new Asset_Loader();
    // pack resolution from Settings_Registry
}

public function render(string $slug): string {
    // ...
    $wc_injector = new WooCommerce_Injector($this);
    // ...
}
```

## Required Changes

1. **Add property:**
```php
private EventDispatcher $events;
```

2. **Change constructor to accept dependencies:**
```php
public function __construct(
    Template_Loader $template_loader,
    SEO_Engine $seo,
    Security_Headers $security,
    Asset_Loader $assets,
    EventDispatcher $events
) {
    $this->template_loader = $template_loader;
    $this->seo = $seo;
    $this->security = $security;
    $this->assets = $assets;
    $this->events = $events;
}
```

3. **Remove pack resolution from constructor** — it moves to container-config.php. Remove the block:
```php
$pack = 'kids';
if (class_exists('\PhantomCore\Settings_Registry')) {
    $registry = \PhantomCore\Settings_Registry::get_instance();
    if ($registry->has('template_pack')) {
        $pack = $registry->get('template_pack');
    }
}
$this->template_loader->set_pack($pack);
```

4. **Add getter** for Template_Loader so container-config can set the pack:
```php
public function get_template_loader(): Template_Loader {
    return $this->template_loader;
}
```

5. **Update WooCommerce_Injector creation** in `inject_woocommerce_content()`:
```php
// CHANGE THIS:
$wc_injector = new WooCommerce_Injector($this);
// TO THIS:
$wc_injector = new WooCommerce_Injector($this, $this->events);
```

6. **Everything else stays the same** — all other methods remain unchanged.

## Verification
```bash
php -l phantom-core/includes/Engine/Render_Engine.php
```

## Commit
```bash
cd C:\Users\hamma\Downloads\wordpress
git add phantom-core/includes/Engine/Render_Engine.php
git commit -m "feat(phase1): refactor Render_Engine for constructor injection"
```

Write report to `.superpowers/sdd/phase1-task-05-report.md`
