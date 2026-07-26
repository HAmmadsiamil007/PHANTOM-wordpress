# T0.1 — Create Contracts Directory + Interfaces

**Goal:** Create 3 interface files that define the contracts for adapters, renderers, and ViewModels.

**Where this fits:** Foundation layer. All adapters and renderers will implement these interfaces in later tasks.

**Directory:** `phantom-core/includes/contracts/`

## Files to Create

### 1. `interface-adapter.php`

```php
<?php
namespace PhantomCore\Contracts;

interface AdapterInterface {
    public function normalize($input = null): array;
    public function normalize_collection(array $inputs): array;
}
```

### 2. `interface-renderer.php`

```php
<?php
namespace PhantomCore\Contracts;

interface RendererInterface {
    public function render(array $data): string;
    public function render_collection(array $data_set): string;
}
```

### 3. `interface-view-model.php`

```php
<?php
namespace PhantomCore\Contracts;

interface ViewModelInterface {}
```

## Rules
- All files in namespace `PhantomCore\Contracts`
- No additional methods beyond those shown
- PHP opening `<?php` tag, no closing tag
- PSR-4 compliant

## Verification
```bash
php -d error_reporting=E_ALL -l phantom-core/includes/contracts/interface-adapter.php
php -d error_reporting=E_ALL -l phantom-core/includes/contracts/interface-renderer.php
php -d error_reporting=E_ALL -l phantom-core/includes/contracts/interface-view-model.php
```
Expected: Each returns `No syntax errors detected`.

## Commit
```bash
cd C:\Users\hamma\Downloads\wordpress && git add phantom-core/includes/contracts/ && git commit -m "feat(phase0): create AdapterInterface, RendererInterface, ViewModelInterface contracts"
```
