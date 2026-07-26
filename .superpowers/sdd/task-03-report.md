# T0.3 Report — Refactor Adapters to Implement AdapterInterface

**Status:** DONE

## Changes Made

### 1. `class-product-adapter.php`
- Added `use PhantomCore\Contracts\AdapterInterface;`
- Changed `class Product_Adapter` → `class Product_Adapter implements AdapterInterface`
- Methods `normalize($product)` and `normalize_collection(array $products)` already matched interface

### 2. `class-category-adapter.php`
- Added `use PhantomCore\Contracts\AdapterInterface;`
- Changed `class Category_Adapter` → `class Category_Adapter implements AdapterInterface`
- Methods already matched interface

### 3. `class-menu-adapter.php`
- Added `use PhantomCore\Contracts\AdapterInterface;`
- Changed `class Menu_Adapter` → `class Menu_Adapter implements AdapterInterface`
- **Additional fix:** Removed `string` type hint from `normalize()` signature (`string $location` → `$location = null`) and added null guard. The interface declares `normalize($input = null): array` (no type hint), so `string $location` would cause a PHP fatal error ("Declaration must be compatible") at class load time. PHP does not allow covariant parameter narrowing.

### 4. `class-hero-adapter.php`
- Added `use PhantomCore\Contracts\AdapterInterface;`
- Changed `class Hero_Adapter` → `class Hero_Adapter implements AdapterInterface`
- Added `normalize_collection(array $inputs): array` method (was missing)
- Existing `normalize()` with no args is compatible with interface's `$input = null` default

## Verification
- All 4 files pass `php -l` syntax checks

## Commit
```
c4b05772915a9d4c5de36799629cb832b3048afc
```

## Concerns
- **None.** Menu_Adapter type hint was fixed proactively to prevent runtime error. All 4 adapters now correctly implement `AdapterInterface`.
