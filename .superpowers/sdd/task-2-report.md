# Task 2 — Remove Product Name Headings (h3) from Homepage

## Status: DONE

## Changes

### Part A: index.html (4 edits)
**File:** `phantom-core/frontend/html/index.html`

Removed `<h3 class="product-name">` from all 4 static product cards in the bestsellers section:

| Product | Line removed |
|---------|-------------|
| AETHER Void Runner | ~366 |
| AETHER Cloud Stride | ~394 |
| AETHER Midnight | ~422 |
| AETHER Aero Sprint | ~449 |

Each card now goes directly from `product-rating` div to `product-tagline` p element. No structural change — the `product-info` div, rating, tagline, price row, and CTA button all remain intact.

### Part B: shell.php (2 edits)
**File:** `phantom-core/templates/shell.php`, method `render_product_card_html()` (~line 877)

1. **Template string** (line 908): Removed `<h3 class="product-name"><a href="%s">%s</a></h3>` from the sprintf template.
2. **Arguments** (lines 917-918): Removed `esc_url( $permalink )` and `esc_html( $name )` arguments.

**Before:** 8 sprintf placeholders, 8 arguments  
**After:** 6 sprintf placeholders, 6 arguments

## Verification
- ✅ 6 `%s` placeholders match 6 arguments in sprintf
- ✅ Zero `product-name` h3 elements remain in `index.html` (grep confirmed)
- ✅ `render_product_card_html()` affects ALL pages: homepage, shop, category pages
- ✅ No other files modified

## Concerns
- The product name is removed from ALL server-side rendered product cards (shop, category, homepage). If any downstream code references `.product-name` or `h3.product-name` for JS behavior, that will silently fail. This is expected per the task spec.
- Static HTML in `shop.html`, `product-detail.html`, and `wishlist.html` still have `h3.product-name` elements — those are out of scope for this task.
