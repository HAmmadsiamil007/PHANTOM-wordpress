# T0.2 — Create ViewModel Classes

**Goal:** Create 3 ViewModel classes that document the array shapes adapters return.

**Depends on:** T0.1 (uses ViewModelInterface from namespace PhantomCore\Contracts)

**Directory:** `phantom-core/includes/ViewModels/`

## Files to Create

### 1. `product-view-model.php`
```php
<?php
namespace PhantomCore\ViewModels;

use PhantomCore\Contracts\ViewModelInterface;

final class Product_ViewModel implements ViewModelInterface {
    public int $id;
    public string $title;
    public string $slug;
    public string $permalink;
    public string $description;
    public string $short_description;
    public string $price;
    public string $regular_price;
    public string $sale_price;
    public string $currency;
    public string $image;
    public array $gallery;
    public string $sku;
    public string $stock_status;
    public bool $in_stock;
    public string $type;
    public string $add_to_cart_text;
    public string $add_to_cart_url;
    public array $categories;
    public array $tags;
    public array $attributes;
    public array $variations;
    public float $rating;
    public int $review_count;
    public string $badge;
}
```

### 2. `category-view-model.php`
```php
<?php
namespace PhantomCore\ViewModels;

use PhantomCore\Contracts\ViewModelInterface;

final class Category_ViewModel implements ViewModelInterface {
    public int $id;
    public string $name;
    public string $slug;
    public string $permalink;
    public string $description;
    public string $image;
    public int $count;
}
```

### 3. `post-view-model.php`
```php
<?php
namespace PhantomCore\ViewModels;

use PhantomCore\Contracts\ViewModelInterface;

final class Post_ViewModel implements ViewModelInterface {
    public int $id;
    public string $title;
    public string $slug;
    public string $permalink;
    public string $excerpt;
    public string $content;
    public string $date;
    public string $image;
    public string $author;
    public array $categories;
    public array $tags;
}
```

## Rules
- All classes are `final`
- All classes implement `ViewModelInterface`
- All properties typed with PHP 7.4+ typed properties
- Namespace `PhantomCore\ViewModels`

## Verification
```bash
php -d error_reporting=E_ALL -l phantom-core/includes/ViewModels/product-view-model.php
php -d error_reporting=E_ALL -l phantom-core/includes/ViewModels/category-view-model.php
php -d error_reporting=E_ALL -l phantom-core/includes/ViewModels/post-view-model.php
```
Expected: Each returns `No syntax errors detected`.

## Commit
```bash
cd C:\Users\hamma\Downloads\wordpress && git add phantom-core/includes/ViewModels/ && git commit -m "feat(phase0): create ViewModel classes documenting adapter array shapes"
```
