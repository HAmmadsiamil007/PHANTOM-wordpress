# P1.6 — Refactor WooCommerce_Injector to accept EventDispatcher

**File:** `phantom-core/includes/Engine/WooCommerce_Injector.php`

**Namespace:** `PhantomCore\Engine`

## Current State
```php
private Render_Engine $engine;
private Product_Adapter $product_adapter;
// ... other properties ...

public function __construct(Render_Engine $engine) {
    $this->engine = $engine;
    $this->product_adapter = new Product_Adapter();
    // ... more `new` calls ...
}
```

## Required Changes

1. **Add property:**
```php
private EventDispatcher $events;
```

2. **Change constructor to accept EventDispatcher:**
```php
public function __construct(Render_Engine $engine, EventDispatcher $events) {
    $this->engine = $engine;
    $this->events = $events;
    $this->product_adapter = new Product_Adapter();
    $this->category_adapter = new Category_Adapter();
    $this->hero_adapter = new Hero_Adapter();
    $this->product_card = new Product_Card();
    $this->category_card = new Category_Card();
    $this->hero = new Hero();
    $this->footer = new Footer();
}
```

3. **Keep all adapter/renderer `new` calls** — those are Phase 2 concerns.

4. **Everything else stays the same** — no method signatures or logic changes.

IMPORTANT: The Render_Engine will be updated in parallel to pass `$this->events` when creating `WooCommerce_Injector`. These two changes are coordinated.

## Verification
```bash
php -l phantom-core/includes/Engine/WooCommerce_Injector.php
```

## Commit
```bash
cd C:\Users\hamma\Downloads\wordpress
git add phantom-core/includes/Engine/WooCommerce_Injector.php
git commit -m "feat(phase1): inject EventDispatcher into WooCommerce_Injector"
```

Write report to `.superpowers/sdd/phase1-task-06-report.md`
