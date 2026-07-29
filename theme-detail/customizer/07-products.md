# Customizer Panel: Products (`phantom_products`)

**Panel ID:** `phantom_products`
**Sections:** `product_cards`, `shop_page`, `product_page`

---

## Product Cards Section (`phantom_section_product_cards`)

**Settings (8):**

| Setting | Control | CSS Var | Notes |
|---------|---------|---------|-------|
| `shop_columns` | `int` | — | Products per row. Default: 3 |
| `card_style` | `ast-select` | — | Card visual style |
| `card_quick_view` | `ast-toggle` | — | Enable quick view |
| `card_wishlist` | `ast-toggle` | — | Enable wishlist button |
| `card_badge_sale` | `ast-toggle` | — | Show sale badge |
| `card_badge_new` | `ast-toggle` | — | Show new badge |
| `card_rating` | `ast-toggle` | — | Show rating |
| `card_price_format` | `ast-select` | — | Price display format |

### CSS Vars

| CSS Var | Purpose |
|---------|---------|
| `--product-card-bg` | Card background |
| `--product-card-text` | Card text color |
| `--product-card-border` | Card border |
| `--product-button-bg` | Button background |
| `--product-button-text` | Button text color |
| `--product-button-hover-bg` | Button hover background |
| `--product-badge-sale-bg` | Sale badge background |
| `--product-badge-sale-text` | Sale badge text |
| `--product-badge-new-bg` | New badge background |
| `--product-badge-new-text` | New badge text |

---

## Shop Page Section (`phantom_section_shop_page`)

**Settings (14):**

| Setting | Control | CSS Var | Notes |
|---------|---------|---------|-------|
| `shop_page_title` | `text` | — | Shop page heading |
| `shop_products_per_page` | `int` | — | Products per page. Default: 12 |
| `shop_orderby_default` | `ast-select` | — | Default sort order |
| `show_shop_breadcrumb` | `ast-toggle` | — | Show breadcrumb |
| `show_shop_sidebar` | `ast-toggle` | — | Show sidebar |
| `enable_quick_view` | `ast-toggle` | — | Enable quick view modal |
| `product_grid_layout` | `ast-select` | — | Grid layout style. Selective refresh partial. |
| `shop_catalog_mode` | `ast-toggle` | — | Catalog mode (no prices/cart) |
| Various filter and display toggles | — | — | |

---

## Product Page Section (`phantom_section_product_page`)

**Settings (34):**

| Setting | Control | CSS Var | Notes |
|---------|---------|---------|-------|
| `product_layout` | `ast-select` | — | Product detail layout |
| `product_related_enable` | `ast-toggle` | — | Show related products |
| `product_related_count` | `int` | — | Number of related products |
| `product_more_enable` | `ast-toggle` | — | Show "You may also like" |
| `product_detail_tabs` | `ast-toggle` | — | Show product tabs |
| `product_detail_reviews` | `ast-toggle` | — | Show reviews |
| `product_detail_share` | `ast-toggle` | — | Show share buttons |
| `product_detail_sticky` | `ast-toggle` | — | Sticky add-to-cart |
| Various product page element toggles and text settings | — | — | |

### Code Flow

```
User changes card style
  → phantom_card_style saved
  → product.php module (priority 70) reads
  → outputs CSS for .product-card selectors
  → Product_Card renderer uses classes
```

### Frontend

`shop.html` has `data-component="shop-grid"` replaced by `Product_Injector`. `product-detail.html` has `[product_*]` placeholders replaced by `Product_Injector`.
