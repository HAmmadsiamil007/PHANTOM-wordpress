# T0.3 — Refactor Adapters to Implement AdapterInterface

**Goal:** Make all 4 non-dead adapters implement AdapterInterface from T0.1.

**Depends on:** T0.1 (needs AdapterInterface)

## Files to Modify

### 1. `phantom-core/includes/adapters/class-product-adapter.php`
Add `use PhantomCore\Contracts\AdapterInterface;` after namespace line.
Change `class Product_Adapter` to `class Product_Adapter implements AdapterInterface`.
Existing methods `normalize($product)` and `normalize_collection($products)` already match the interface.

### 2. `phantom-core/includes/adapters/class-category-adapter.php`
Add `use PhantomCore\Contracts\AdapterInterface;`.
Change `class Category_Adapter` to `class Category_Adapter implements AdapterInterface`.
Existing methods already match.

### 3. `phantom-core/includes/adapters/class-menu-adapter.php`
Add `use PhantomCore\Contracts\AdapterInterface;`.
Change `class Menu_Adapter` to `class Menu_Adapter implements AdapterInterface`.
Existing methods already match.

### 4. `phantom-core/includes/adapters/class-hero-adapter.php`
Add `use PhantomCore\Contracts\AdapterInterface;`.
Change `class Hero_Adapter` to `class Hero_Adapter implements AdapterInterface`.
Existing `normalize()` takes no args — this is valid PHP since the interface has `$input = null` default.
Add `normalize_collection()` if not present:
```php
public function normalize_collection(array $inputs): array {
    return array_map([$this, 'normalize'], $inputs);
}
```

## Verification
```bash
php -d error_reporting=E_ALL -l phantom-core/includes/adapters/class-product-adapter.php
php -d error_reporting=E_ALL -l phantom-core/includes/adapters/class-category-adapter.php
php -d error_reporting=E_ALL -l phantom-core/includes/adapters/class-menu-adapter.php
php -d error_reporting=E_ALL -l phantom-core/includes/adapters/class-hero-adapter.php
```
Expected: All return `No syntax errors detected`.

## Commit
```bash
cd C:\Users\hamma\Downloads\wordpress && git add phantom-core/includes/adapters/ && git commit -m "feat(phase0): refactor 4 adapters to implement AdapterInterface"
```
