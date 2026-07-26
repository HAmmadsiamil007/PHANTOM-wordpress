# Task 3 Report: Rewrite Category Section to Use Customizer Repeater

## Status: DONE

## What Changed
**File:** `phantom-core/templates/shell.php`

### 1. `inject_homepage_categories()` — Replaced (lines 1452-1569)
- **Old:** Auto-pulled all top-level product categories, inline image resolution logic, debug `error_log()` call
- **New:** Reads `phantom_settings['home_categories']` (Customizer repeater array of category IDs). If populated, renders ONLY those selected categories in order. If empty, falls back to auto-pulling all top-level non-empty categories (preserves existing behavior). Delegates image resolution to new helper.

### 2. `get_category_image_url()` — Added (lines 1571-1601)
- New helper method placed immediately after `inject_homepage_categories()`
- Checks `get_term_meta($cat->term_id, 'thumbnail_id', true)` first
- Falls back to first product image via `wc_get_products()`
- Falls back to `wc_placeholder_img_src('medium_large')`

### 3. Removed
- Debug `error_log('PHANTOM_CAT: ...')` line (was line 1520 in old version)

## Self-Review Findings
- ✅ Call site at line 860 (`$this->insert_homepage_categories( $html )`) — signature unchanged, compatible
- ✅ PHP lint: `No syntax errors detected`
- ✅ Method placement: `get_category_image_url` sits between `inject_homepage_categories` and `inject_wishlist_content`
- ✅ Regex pattern for `category-grid` replacement unchanged — CSS class names preserved
- ✅ Card HTML structure identical — no frontend breakage risk
- ✅ All `$cat->count` and `$cat->name` references wrapped in `esc_html()`
- ✅ All URLs wrapped in `esc_url()`
- ✅ Category IDs cast with `absint()`

## Concerns
- **None.** The implementation exactly matches the spec. The `home_categories` key in `phantom_settings` needs to be registered in the Settings Registry (Task 1/2 responsibility) for the repeater to have data, but this method handles the empty-array fallback gracefully regardless.
