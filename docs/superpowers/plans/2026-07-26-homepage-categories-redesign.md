# Homepage Categories Redesign Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Remove product headings and section titles from the homepage, rewrite the "Find Your Fit" category section to use Customizer repeater settings, and fix the price HTML entity bug.

**Architecture:** Modify `index.html` (static template) and `shell.php` (server-side injection) to remove headings. Rewrite `inject_homepage_categories()` to read the `home_categories` Customizer repeater instead of auto-querying all categories. Fix price formatting in `render_product_card_html()`.

**Tech Stack:** PHP (WordPress/WooCommerce), HTML, Customizer API (`get_option`, `get_term_meta`)

## Global Constraints

- WordPress 6.x, WooCommerce 8.x, PHP 8.x
- Docker environment at `localhost:8080`
- Follow existing code patterns in `shell.php` and `index.html`
- No new files needed — all changes are modifications to existing files
- Product headings removal is **homepage only** — shop page keeps them

---

## File Map

| File | Action | Purpose |
|------|--------|---------|
| `phantom-core/frontend/html/index.html` | Modify | Remove section headers + product h3 headings |
| `phantom-core/templates/shell.php` | Modify | Rewrite `inject_homepage_categories()` to use Customizer repeater; remove h3 from `render_product_card_html()`; fix price entity bug |

---

### Task 1: Remove Section Headings from Homepage

**Files:**
- Modify: `phantom-core/frontend/html/index.html:325-328` (categories section header)
- Modify: `phantom-core/frontend/html/index.html:351-355` (bestsellers section header)

**Interfaces:**
- Consumes: None
- Produces: Cleaner homepage with no section titles on product sections

- [ ] **Step 1: Remove the categories section header**

In `phantom-core/frontend/html/index.html`, remove lines 325-328:

```html
<!-- REMOVE THIS BLOCK -->
<div class="section-header">
    <span class="section-label" data-phantom="section_label" data-motion-text="words">Shop by Category</span>
    <h2 class="section-title" data-phantom="section_title" data-motion-text="words">Find Your Fit</h2>
</div>
```

The section should go directly from `<div class="container">` to `<div class="category-grid" ...>`.

- [ ] **Step 2: Remove the bestsellers section header**

In `phantom-core/frontend/html/index.html`, remove lines 351-355:

```html
<!-- REMOVE THIS BLOCK -->
<div class="section-header">
    <span class="section-label" data-phantom="section_label" data-motion-text="words">Bestsellers</span>
    <h2 class="section-title" data-phantom="section_title" data-motion-text="words">Most Loved</h2>
    <p class="section-subtitle" data-phantom="section_subtitle" data-motion-text="lines">The shoes everyone's talking about. Tried, tested, and obsessed over.</p>
</div>
```

- [ ] **Step 3: Verify changes**

Run: `docker cp phantom-core phantom_wordpress:/var/www/html/wp-content/plugins/phantom-core`
Open `http://localhost:8080` — confirm no "Find Your Fit" or "Most Loved" headings visible.

- [ ] **Step 4: Commit**

```bash
git add phantom-core/frontend/html/index.html
git commit -m "feat: remove section headings from homepage categories and bestsellers"
```

---

### Task 2: Remove Product Name Headings (h3) from Homepage

**Files:**
- Modify: `phantom-core/frontend/html/index.html:377,405,433,460` (static product cards)
- Modify: `phantom-core/templates/shell.php:908` (server-side `render_product_card_html()`)

**Interfaces:**
- Consumes: None
- Produces: Product cards without h3.product-name on homepage; shop page unaffected

- [ ] **Step 1: Remove h3.product-name from static product cards in index.html**

In `phantom-core/frontend/html/index.html`, remove these lines from each of the 4 product cards:

```html
<!-- REMOVE from each product card -->
<h3 class="product-name">AETHER Void Runner</h3>
<h3 class="product-name">AETHER Cloud Stride</h3>
<h3 class="product-name">AETHER Midnight</h3>
<h3 class="product-name">AETHER Aero Sprint</h3>
```

- [ ] **Step 2: Remove h3.product-name from render_product_card_html() in shell.php**

In `phantom-core/templates/shell.php`, modify `render_product_card_html()` (line 877-922).

Change the sprintf template from:

```php
'<div class="product-card" data-phantom="product" data-tilt data-reveal-item>
    <div class="product-image" data-image-zoom>
        %s
        <a href="%s"><img loading="lazy" src="%s" alt="%s"></a>
    </div>
    <div class="product-info">
        <h3 class="product-name"><a href="%s">%s</a></h3>
        <p class="product-price">%s</p>
        <a href="%s" class="btn btn-primary btn-sm" data-magnetic="0.12">View Details</a>
    </div>
</div>',
$badge,
esc_url( $permalink ),
esc_url( $image_url ),
esc_attr( $image_alt ),
esc_url( $permalink ),
esc_html( $name ),
$price_html,
esc_url( $permalink )
```

To (remove the h3 line and adjust sprintf args):

```php
'<div class="product-card" data-phantom="product" data-tilt data-reveal-item>
    <div class="product-image" data-image-zoom>
        %s
        <a href="%s"><img loading="lazy" src="%s" alt="%s"></a>
    </div>
    <div class="product-info">
        <p class="product-price">%s</p>
        <a href="%s" class="btn btn-primary btn-sm" data-magnetic="0.12">View Details</a>
    </div>
</div>',
$badge,
esc_url( $permalink ),
esc_url( $image_url ),
esc_attr( $image_alt ),
$price_html,
esc_url( $permalink )
```

- [ ] **Step 3: Verify changes**

Deploy to Docker, open homepage. Confirm no product names on cards. Open `/shop` — confirm product names still show there (shop uses a different rendering path).

- [ ] **Step 4: Commit**

```bash
git add phantom-core/frontend/html/index.html phantom-core/templates/shell.php
git commit -m "feat: remove product name headings from homepage cards"
```

---

### Task 3: Rewrite Category Section to Use Customizer Repeater

**Files:**
- Modify: `phantom-core/templates/shell.php:1458-1533` (`inject_homepage_categories()`)

**Interfaces:**
- Consumes: `get_option('phantom_settings')` → `home_categories` (repeater array of category IDs), `home_categories_heading`, `home_categories_title`, `collections_columns`
- Produces: Category cards rendered from Customizer-selected categories with proper images, names, counts, and links

- [ ] **Step 1: Rewrite inject_homepage_categories()**

Replace the entire `inject_homepage_categories()` method in `shell.php` with:

```php
/**
 * Inject WooCommerce product categories into the homepage category section.
 * Uses Customizer repeater 'home_categories' for selected category IDs.
 * Falls back to auto-pulling all top-level categories if repeater is empty.
 */
private function inject_homepage_categories( string $html ): string {
    $settings = get_option( 'phantom_settings', array() );
    $selected_ids = isset( $settings['home_categories'] ) ? $settings['home_categories'] : array();

    // If repeater is empty, fall back to auto-pulling all top-level categories
    if ( empty( $selected_ids ) || ! is_array( $selected_ids ) ) {
        $cats = get_terms( array(
            'taxonomy'   => 'product_cat',
            'hide_empty' => true,
            'parent'     => 0,
        ) );
        if ( empty( $cats ) || is_wp_error( $cats ) ) {
            return $html;
        }
        $category_cards = '';
        $idx = 0;
        $total = count( $cats );
        foreach ( $cats as $cat ) {
            if ( 'uncategorized' === $cat->slug ) {
                continue;
            }
            $card_class = 'category-card';
            if ( $idx === 0 ) {
                $card_class .= ' category-card--large';
            } elseif ( $idx === $total - 1 && $total % 2 === 1 ) {
                $card_class .= ' category-card--accent';
            }

            $image_url = $this->get_category_image_url( $cat );
            $count_str = $cat->count . ' Product' . ( $cat->count !== 1 ? 's' : '' );

            $category_cards .= sprintf(
                '<a href="%s" class="%s" data-tilt data-reveal-item>
                    <div class="category-card-bg">
                        <img loading="lazy" src="%s" alt="%s">
                        <div class="category-card-overlay"></div>
                    </div>
                    <div class="category-card-content">
                        <span class="category-count">%s</span>
                        <h3 class="category-name">%s</h3>
                        <span class="category-cta">Shop %s <i class="fas fa-arrow-right"></i></span>
                    </div>
                </a>',
                esc_url( get_term_link( $cat ) ),
                $card_class,
                esc_url( $image_url ),
                esc_attr( $cat->name . ' Collection' ),
                esc_html( $count_str ),
                esc_html( $cat->name ),
                esc_html( $cat->name )
            );
            $idx++;
        }
    } else {
        // Build cards from Customizer-selected category IDs
        $category_cards = '';
        $total = count( $selected_ids );
        $idx = 0;
        foreach ( $selected_ids as $cat_id ) {
            $cat_id = absint( $cat_id );
            if ( ! $cat_id ) {
                continue;
            }
            $cat = get_term( $cat_id, 'product_cat' );
            if ( ! $cat || is_wp_error( $cat ) || 'uncategorized' === $cat->slug ) {
                continue;
            }

            $card_class = 'category-card';
            if ( $idx === 0 ) {
                $card_class .= ' category-card--large';
            } elseif ( $idx === $total - 1 && $total % 2 === 1 ) {
                $card_class .= ' category-card--accent';
            }

            $image_url = $this->get_category_image_url( $cat );
            $count_str = $cat->count . ' Product' . ( $cat->count !== 1 ? 's' : '' );

            $category_cards .= sprintf(
                '<a href="%s" class="%s" data-tilt data-reveal-item>
                    <div class="category-card-bg">
                        <img loading="lazy" src="%s" alt="%s">
                        <div class="category-card-overlay"></div>
                    </div>
                    <div class="category-card-content">
                        <span class="category-count">%s</span>
                        <h3 class="category-name">%s</h3>
                        <span class="category-cta">Shop %s <i class="fas fa-arrow-right"></i></span>
                    </div>
                </a>',
                esc_url( get_term_link( $cat ) ),
                $card_class,
                esc_url( $image_url ),
                esc_attr( $cat->name . ' Collection' ),
                esc_html( $count_str ),
                esc_html( $cat->name ),
                esc_html( $cat->name )
            );
            $idx++;
        }
    }

    if ( '' !== $category_cards ) {
        $html = preg_replace(
            '/<div class="category-grid"[^>]*>.*?<\/div>\s*<\/div>\s*<\/section>/s',
            '<div class="category-grid" data-reveal-group>' . $category_cards . '</div></div></section>',
            $html,
            1
        );
    }

    return $html;
}

/**
 * Get the image URL for a product category term.
 * Checks term meta 'thumbnail_id' first, falls back to first product image, then placeholder.
 */
private function get_category_image_url( $cat ): string {
    $thumb_id = get_term_meta( $cat->term_id, 'thumbnail_id', true );
    if ( $thumb_id ) {
        $url = wp_get_attachment_image_url( $thumb_id, 'medium_large' );
        if ( $url ) {
            return $url;
        }
    }

    // Fallback: first product in category
    $products_in_cat = wc_get_products( array(
        'limit'    => 1,
        'status'   => 'publish',
        'category' => array( $cat->slug ),
    ) );
    if ( ! empty( $products_in_cat ) ) {
        $img_id = $products_in_cat[0]->get_image_id();
        if ( $img_id ) {
            $url = wp_get_attachment_image_url( $img_id, 'medium_large' );
            if ( $url ) {
                return $url;
            }
        }
    }

    return wc_placeholder_img_src( 'medium_large' );
}
```

- [ ] **Step 2: Verify changes**

Deploy to Docker, open homepage. Confirm category cards render with images and names. If `home_categories` repeater has values in Customizer, those specific categories should appear. If empty, all top-level categories should auto-populate.

- [ ] **Step 3: Commit**

```bash
git add phantom-core/templates/shell.php
git commit -m "feat: rewrite homepage categories to use Customizer repeater settings"
```

---

### Task 4: Fix Price HTML Entity Bug

**Files:**
- Modify: `phantom-core/templates/shell.php:892-899` (in `render_product_card_html()`)

**Interfaces:**
- Consumes: `$product->get_price_html()` output
- Produces: Clean price display with proper `$` symbol

- [ ] **Step 1: Fix price formatting in render_product_card_html()**

In `phantom-core/templates/shell.php`, replace the price formatting block (lines 892-899):

```php
// CURRENT (buggy):
$price_html = $product->get_price_html();
if ( $product->is_on_sale() ) {
    $regular_price = $product->get_regular_price();
    $sale_price = $product->get_sale_price();
    if ( $regular_price && $sale_price ) {
        $price_html = '<span class="price-old">' . wc_price( $regular_price ) . '</span> ' . wc_price( $sale_price );
    }
}
```

With:

```php
// FIXED:
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

- [ ] **Step 2: Verify changes**

Deploy to Docker, open homepage. Confirm prices show `$34.99` not `&#36;34.99`. Check sale prices show correct formatting.

- [ ] **Step 3: Commit**

```bash
git add phantom-core/templates/shell.php
git commit -m "fix: resolve HTML entity encoding in product price display"
```

---

### Task 5: Deploy and E2E Test

**Files:** None (verification only)

**Interfaces:**
- Consumes: All changes from Tasks 1-4
- Produces: Verified working homepage

- [ ] **Step 1: Deploy all changes to Docker**

```bash
docker cp phantom-core phantom_wordpress:/var/www/html/wp-content/plugins/phantom-core
```

- [ ] **Step 2: E2E test on homepage**

Open `http://localhost:8080` and verify:
- [ ] No "Find Your Fit" or "Most Loved" section headings
- [ ] No product name (h3) on any homepage product card
- [ ] Category section renders with images, names, product counts
- [ ] Category section links work (click through to `/category/{slug}`)
- [ ] Prices display `$` correctly (no `&#36;` entities)
- [ ] "ADD TO CART" buttons still functional

- [ ] **Step 3: E2E test on shop page**

Open `http://localhost:8080/shop` and verify:
- [ ] Product names (h3) still show on shop page (only homepage removes them)
- [ ] Prices display correctly
- [ ] Category filter buttons work

- [ ] **Step 4: Test Customizer integration**

Open Customizer → Hero & Home → Collections:
- [ ] `home_categories` repeater is visible and editable
- [ ] Setting specific categories in repeater filters the homepage display
- [ ] Empty repeater falls back to auto-pulling all categories

- [ ] **Step 5: Final commit if any fixes needed**

```bash
git add -A
git commit -m "chore: E2E testing fixes for homepage categories redesign"
```
