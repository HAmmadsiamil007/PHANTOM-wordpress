# Homepage Categories Redesign — Design Spec

**Date:** 2026-07-26
**Status:** Approved
**Scope:** Homepage "Find Your Fit" section → Customizer-controlled category section + product heading removal

## Problem

1. The "Find Your Fit" category section on the homepage renders an empty grid — `inject_homepage_categories()` in `shell.php` silently fails
2. Customizer settings for categories (`home_categories` repeater, `home_categories_heading`, etc.) exist but are bypassed by server-side code
3. Product cards display `<h3 class="product-name">` headings that should be removed
4. Section headings ("Find Your Fit", "Most Loved") should be removed from the homepage
5. HTML entity bug: `&#36;` showing instead of `$` in some product prices

## Design Decisions

### 1. Remove Section Headings

**Files:** `frontend/html/index.html`

- Remove `<div class="section-header">` block from the categories section (lines 325-328)
- Remove `<div class="section-header">` block from the bestsellers section (lines 351-355)
- Keep review and FAQ section headings (they're content sections, not product sections)

### 2. Remove Product Name Headings (h3)

**Files:** `frontend/html/index.html`, `templates/shell.php`

- Remove `<h3 class="product-name">` from static product cards in `index.html`
- Remove `h3.product-name` from `render_product_card_html()` in `shell.php:908`
- Keep the category label (`Women`/`Men`), star rating, price, and CTA button

### 3. Rewrite Category Section to Use Customizer Repeater

**Files:** `templates/shell.php`

Rewrite `inject_homepage_categories()` (lines 1458-1533) to:

1. Read `get_option('phantom_settings')` and extract the `home_categories` repeater value
2. The repeater contains an array of category IDs (set via Customizer → Hero & Home → Collections)
3. For each selected category ID:
   - Fetch the `WP_Term` object via `get_term($id, 'product_cat')`
   - Get category image from `get_term_meta($id, 'thumbnail_id', true)` → `wp_get_attachment_image_url()`
   - Get product count from `$term->count`
   - Build link to `/category/{slug}`
4. Render category cards matching the existing template structure
5. **Fallback:** If repeater is empty, auto-pull all top-level non-empty product categories (current behavior, but fixed)

**Customizer settings used:**
| Setting | Purpose |
|---------|---------|
| `home_categories` | Repeater: array of category IDs to display |
| `home_categories_heading` | Label text above the section title |
| `home_categories_title` | Section title text |
| `collections_layout` | Grid layout type |
| `collections_columns` | Number of columns (default: 3) |

### 4. Fix Price HTML Entity Bug

**File:** `templates/shell.php`

In `render_product_card_html()` (line 892-899), the `$price_html` from `$product->get_price_html()` may contain encoded entities. Use `wp_strip_all_tags()` on the output, or format prices inline with `wc_price()` to ensure proper `$` rendering.

## Testing

1. Open Docker at `http://localhost:8080`
2. Verify: No section headings on homepage (categories, bestsellers sections)
3. Verify: No product name (h3) on any product card
4. Verify: Category section shows WooCommerce categories from Customizer repeater
5. Verify: Category section falls back to auto-pull when repeater is empty
6. Verify: Prices display `$` correctly (no `&#36;` entities)
7. Verify: Shop page (`/shop`) still shows product names (only homepage removes them)
