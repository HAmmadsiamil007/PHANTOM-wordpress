# P1.7 — Create Container_Config.php with factory definitions

**File:** `phantom-core/includes/Engine/Container_Config.php`

**Namespace:** `PhantomCore\Engine`

## Requirements

Create a config class that registers all Engine services into the Container.

```php
<?php
declare(strict_types=1);

namespace PhantomCore\Engine;

defined('ABSPATH') || exit;

class Container_Config {
    public static function configure(Container $container): void
}
```

## Services to register

```php
// 1. EventDispatcher — singleton
$container->singleton(EventDispatcher::class, function($c) {
    return new EventDispatcher();
});

// 2. Render_Engine — singleton with pack resolution
$container->singleton(Render_Engine::class, function($c) {
    $pack = 'kids';
    if (class_exists('\PhantomCore\Settings_Registry')) {
        $registry = \PhantomCore\Settings_Registry::get_instance();
        if ($registry->has('template_pack')) {
            $pack = $registry->get('template_pack');
        }
    }
    $engine = new Render_Engine(
        $c->get(Template_Loader::class),
        $c->get(SEO_Engine::class),
        $c->get(Security_Headers::class),
        $c->get(Asset_Loader::class),
        $c->get(EventDispatcher::class)
    );
    $engine->get_template_loader()->set_pack($pack);
    return $engine;
});

// 3. WooCommerce_Injector — factory (not singleton — created per-request)
$container->set(WooCommerce_Injector::class, function($c) {
    return new WooCommerce_Injector(
        $c->get(Render_Engine::class),
        $c->get(EventDispatcher::class)
    );
});
```

**Important:** Template_Loader, SEO_Engine, Security_Headers, Asset_Loader do NOT need explicit factories — auto-wiring handles them (no constructor params).

## Verification
```bash
php -l phantom-core/includes/Engine/Container_Config.php
```

## Commit
```bash
cd C:\Users\hamma\Downloads\wordpress
git add phantom-core/includes/Engine/Container_Config.php
git commit -m "feat(phase1): create Container_Config with service definitions"
```

Write report to `.superpowers/sdd/phase1-task-07-report.md`
