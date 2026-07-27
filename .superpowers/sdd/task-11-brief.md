# T0.11 — Clean up shell.php + Render_Engine imports

**Goal:** Remove unused imports from `shell.php` — these classes are now handled by WooCommerce_Injector, not by Shell directly.

## Files to Modify

### `phantom-core/templates/shell.php`

READ the existing file first. Currently has these imports (lines 13-21):
```php
use PhantomCore\Adapters\Product_Adapter;
use PhantomCore\Adapters\Category_Adapter;
use PhantomCore\Adapters\Menu_Adapter;
use PhantomCore\Adapters\Hero_Adapter;
use PhantomCore\Renderer\Product_Card;
use PhantomCore\Renderer\Category_Card;
use PhantomCore\Renderer\Navigation;
use PhantomCore\Renderer\Hero;
use PhantomCore\Engine\Render_Engine;
```

None of these are used directly in the Shell class body except `Render_Engine` (used in `init()` at line 38). The adapter and renderer classes are instantiated inside WooCommerce_Injector, not Shell.

Remove all imports except:
```php
use PhantomCore\Engine\Render_Engine;
```

### `phantom-core/includes/Engine/Render_Engine.php`
CHECK the file first — it may not have unused imports. If all imports are used, leave it alone.

## Verification
```bash
php -l phantom-core/templates/shell.php
```
Expected: `No syntax errors detected`

## Commit
```bash
cd C:\Users\hamma\Downloads\wordpress
git add phantom-core/templates/shell.php
git commit -m "chore(phase0): remove unused imports from shell.php"
```
