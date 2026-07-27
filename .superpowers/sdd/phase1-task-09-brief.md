# P1.9 — Hook container init in phantom-core.php

**File:** `phantom-core/phantom-core.php`

## Required Changes

**Change the Shell initialization block (lines 134-138):**

FROM:
```php
$shell_path = PHANTOM_CORE_PATH . 'templates/shell.php';
if ( file_exists( $shell_path ) ) {
    require_once $shell_path;
    \PhantomCore\Shell::get_instance()->init();
}
```

TO:
```php
$shell_path = PHANTOM_CORE_PATH . 'templates/shell.php';
if ( file_exists( $shell_path ) ) {
    require_once $shell_path;
}
add_action('plugins_loaded', function(): void {
    \PhantomCore\Shell::get_instance()->init();
}, 0);
```

This moves the Shell initialization to `plugins_loaded` with priority 0 (before any other plugin init), while still requiring the file for class definition.

The container (and all its services) will be created when `plugins_loaded` fires at priority 0, ensuring all Engine services are ready before any other plugin starts up.

## Verification
```bash
php -l phantom-core/phantom-core.php
```

**IMPORTANT:** Also add a require_once for the new Engine files that the autoloader might not catch. The autoloader uses PSR-4-like namespacing for PhantomCore\Engine\* classes, mapping to `includes/Engine/*.php`. Since Container.php, EventDispatcher.php, etc are at `includes/Engine/Container.php`, the autoloader should find them. But Container_Config references Render_Engine.php which is also in the Engine directory. The autoloader should handle this.

However, to be safe and ensure proper load order, add this BEFORE the Shell require:
```php
require_once PHANTOM_CORE_PATH . 'includes/Engine/Container.php';
require_once PHANTOM_CORE_PATH . 'includes/Engine/Container_Config.php';
require_once PHANTOM_CORE_PATH . 'includes/Engine/Render_Engine.php';
require_once PHANTOM_CORE_PATH . 'includes/Engine/EventDispatcher.php';
require_once PHANTOM_CORE_PATH . 'includes/Engine/PhpEventStore.php';
require_once PHANTOM_CORE_PATH . 'includes/Engine/WooCommerce_Injector.php';
```

Actually, the autoloader already handles `PhantomCore\Engine\*` -> `includes/Engine/*.php`. And the existing require_once chain already loads everything else. So no additional requires should be needed. BUT to be safe, add explicit requires for the new Engine files right after the existing require_once chain and before the Shell require.

## Commit
```bash
cd C:\Users\hamma\Downloads\wordpress
git add phantom-core/phantom-core.php
git commit -m "feat(phase1): hook container init at plugins_loaded priority 0"
```

Write report to `.superpowers/sdd/phase1-task-09-report.md`
