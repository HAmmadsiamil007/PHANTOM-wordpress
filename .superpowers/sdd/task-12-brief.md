# T0.12 — Final verification

**Goal:** Run comprehensive syntax/build checks on all Phase 0 changes. Write a verification report.

## Verification Checklist

### 1. PHP syntax check — all modified/new PHP files
```bash
Get-ChildItem -Recurse -Filter "*.php" -Path "C:\Users\hamma\Downloads\wordpress\phantom-core\includes\contracts","C:\Users\hamma\Downloads\wordpress\phantom-core\includes\ViewModels","C:\Users\hamma\Downloads\wordpress\phantom-core\includes\adapters","C:\Users\hamma\Downloads\wordpress\phantom-core\includes\renderer","C:\Users\hamma\Downloads\wordpress\phantom-core\includes\Engine","C:\Users\hamma\Downloads\wordpress\phantom-core\templates" | ForEach-Object { php -l $_.FullName }
```
All should say "No syntax errors detected".

### 2. JS build
```bash
cd C:\Users\hamma\Downloads\wordpress\phantom-core && node build.js
```
Should produce all 3 output files without errors.

### 3. File structure verification
Confirm these files exist:
- `phantom-core/includes/contracts/interface-adapter.php`
- `phantom-core/includes/contracts/interface-renderer.php`
- `phantom-core/includes/contracts/interface-viewmodel.php`
- `phantom-core/includes/ViewModels/class-product-viewmodel.php`
- `phantom-core/includes/ViewModels/class-category-viewmodel.php`
- `phantom-core/includes/ViewModels/class-post-viewmodel.php`
- `phantom-core/includes/adapters/class-product-adapter.php`
- `phantom-core/includes/adapters/class-category-adapter.php`
- `phantom-core/includes/adapters/class-menu-adapter.php`
- `phantom-core/includes/adapters/class-hero-adapter.php`
- `phantom-core/includes/renderer/class-component-renderer.php`
- `phantom-core/includes/renderer/class-product-card.php`
- `phantom-core/includes/renderer/class-category-card.php`
- `phantom-core/includes/renderer/class-hero.php`
- `phantom-core/includes/renderer/class-footer.php`
- `phantom-core/frontend/html/components/product-card.html`
- `phantom-core/frontend/html/components/category-card.html`
- `phantom-core/frontend/html/components/blog-card.html`
- `phantom-core/frontend/assets/js/phantom-injector.js`
- `phantom-core/frontend/assets/js/services/event-services.js`
- `phantom-core/frontend/assets/js/services/api-service.js`

Confirm these files are GONE:
- `phantom-core/includes/adapters/class-post-adapter.php`
- `phantom-core/includes/adapters/class-settings-adapter.php`
- `phantom-core/includes/renderer/class-navigation.php`
- `phantom-core/includes/renderer/class-blog-card.php`
- `phantom-core/frontend/assets/js/phantom-bridge.js`
- `phantom-core/frontend/assets/js/phantom-bridge.min.js`
- `phantom-core/frontend/assets/js/adapters/post-adapter.js`
- `phantom-core/frontend/assets/js/renderer/navigation.js`
- `phantom-core/frontend/assets/js/renderer/blog-card.js`

### 4. Write verification report
Write to `.superpowers/sdd/task-12-report.md` with:
- Commit log summary
- PHP lint results summary (pass/fail count)
- JS build result
- File structure status (all present, none missing)
- Any warnings or issues

## Commit (if any fixes are needed; otherwise no commit)
```bash
cd C:\Users\hamma\Downloads\wordpress
git add .
git commit -m "chore(phase0): final verification fixes"
```
Only commit if there were actual fixes needed.
