# Task 4 Report — Fix Price HTML Entity Bug

**Status:** DONE

## What Changed

**File:** `phantom-core/templates/shell.php`
**Lines:** 892–902 (was 892–899)

### Before (lines 892–899)
```php
$price_html = $product->get_price_html();
if ( $product->is_on_sale() ) {
    $regular_price = $product->get_regular_price();
    $sale_price = $product->get_sale_price();
    if ( $regular_price && $sale_price ) {
        $price_html = '<span class="price-old">' . wc_price( $regular_price ) . '</span> ' . wc_price( $sale_price );
    }
}
```

### After (lines 892–902)
```php
if ( $product->is_on_sale() ) {
    $regular_price = $product->get_regular_price();
    $sale_price = $product->get_sale_price();
    if ( $regular_price && $sale_price ) {
        $price_html = '<span class="price-old">' . wp_strip_all_tags( wc_price( $regular_price ) ) . '</span> ' . wp_strip_all_tags( wc_price( $sale_price ) );
    } else {
        $price_html = wp_strip_all_tags( $product->get_price_html() );
    }
} else {
    $price_html = wp_strip_all_tags( $product->get_price_html() );
}
```

## Verification

- [x] PHP syntax check: `php -l` — **No syntax errors**
- [x] sprintf placeholders: 6 `%s` at lines 907–911, 6 arguments at lines 915–920 — **matches**
- [x] All three price paths (on-sale with both prices, on-sale fallback, non-sale) wrap output with `wp_strip_all_tags()`
- [x] No other methods or code paths affected

## Concerns

None. The change is surgical and self-contained within `render_product_card_html()`.

## Self-Review

- The `else` branches were added to handle cases where `is_on_sale()` is true but either `regular_price` or `sale_price` is empty — previously those fell through with the raw `get_price_html()`. Now they're also sanitized.
- `wp_strip_all_tags()` strips HTML tags but preserves text content, so `&#36;` → `$` is handled by `wc_price()` itself. The real fix here is preventing double-encoding when the output is later escaped by `esc_html()` in a sprintf context.
