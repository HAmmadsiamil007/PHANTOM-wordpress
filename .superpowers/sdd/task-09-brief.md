# T0.9 — Remove dead code files

**Goal:** Delete dead PHP and JS files that were superseded in Phase 0 refactoring.

## Files to Delete

### PHP (4 files)
1. `phantom-core/includes/adapters/class-post-adapter.php`
2. `phantom-core/includes/adapters/class-settings-adapter.php`
3. `phantom-core/includes/renderer/class-navigation.php`
4. `phantom-core/includes/renderer/class-blog-card.php`

### JS (3 files)
5. `phantom-core/frontend/assets/js/adapters/post-adapter.js`
6. `phantom-core/frontend/assets/js/renderer/navigation.js`
7. `phantom-core/frontend/assets/js/renderer/blog-card.js`

## Files to Modify

### `phantom-core/build.js`
READ the existing file first. Remove these entries from the manifest array:
- `'adapters/post-adapter.js'`
- `'renderer/blog-card.js'`
- `'renderer/navigation.js'`

## Verification
```bash
# Verify files no longer exist
Test-Path "phantom-core/includes/adapters/class-post-adapter.php" -PathType Leaf
Test-Path "phantom-core/includes/adapters/class-settings-adapter.php" -PathType Leaf
Test-Path "phantom-core/includes/renderer/class-navigation.php" -PathType Leaf
Test-Path "phantom-core/includes/renderer/class-blog-card.php" -PathType Leaf
Test-Path "phantom-core/frontend/assets/js/adapters/post-adapter.js" -PathType Leaf
Test-Path "phantom-core/frontend/assets/js/renderer/navigation.js" -PathType Leaf
Test-Path "phantom-core/frontend/assets/js/renderer/blog-card.js" -PathType Leaf
```
All should return False.

```bash
# Run the build
cd phantom-core && node build.js
```
Should succeed.

## Commit
```bash
cd C:\Users\hamma\Downloads\wordpress
git rm phantom-core/includes/adapters/class-post-adapter.php phantom-core/includes/adapters/class-settings-adapter.php phantom-core/includes/renderer/class-navigation.php phantom-core/includes/renderer/class-blog-card.php
git rm phantom-core/frontend/assets/js/adapters/post-adapter.js phantom-core/frontend/assets/js/renderer/navigation.js phantom-core/frontend/assets/js/renderer/blog-card.js
git add phantom-core/build.js
git commit -m "feat(phase0): remove dead code files (4 PHP, 3 JS, update build manifest)"
```
