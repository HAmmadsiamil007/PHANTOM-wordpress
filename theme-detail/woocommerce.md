# Phantom Core — WooCommerce Integration (v2.0.0)

## Overview

Phantom Core integrates WooCommerce through a **dual-path rendering architecture**: server-side injection for initial page load (via `WooCommerce_Injector` and PHP adapters/viewmodels/renderers) and client-side REST API calls for dynamic interactions (via `phantom-data.js`). WooCommerce data flows through a normalized pipeline:

```
WooCommerce Plugin → WC_Product/WC_Cart/WC_Order → Data Adapters → ViewModels → Renderers → WooCommerce_Injector → Static HTML Templates → Browser
                                                                                                        ↓
                                                                                                 REST API endpoints → phantom-data.js (client-side)
```

The plugin never modifies WooCommerce's database schema. All product, cart, order, and coupon data is read through WooCommerce's native APIs (`WC_Product`, `WC_Cart`, `WC_Order`, `WC_Coupon`) and normalized through the adapter layer before presentation.

---

## REST API Endpoints

**Namespace:** `phantom/v1`

### Products

| Endpoint | Method | Auth | Description | Key Args |
|----------|--------|------|-------------|----------|
| `/products` | GET | public | Paginated product list | `per_page`, `page`, `category`, `search`, `orderby` (date/id/title/name/price/popularity/rating/rand), `order`, `min_price`, `max_price`, `on_sale`, `stock_status`, `featured`, `tag` |
| `/products` | POST | admin | Create product | Full WC_Product fields |
| `/products/featured` | GET | public | Featured products (max 8) | — |
| `/products/{id}` | GET | public | Single product with related data | — |
| `/products/{id}` | PUT | admin | Update product | Full WC_Product fields |
| `/products/{id}` | DELETE | admin | Delete product | — |
| `/product-tags` | GET | public | All product_tag terms | — |

**`GET /products` Response:**
```json
{
  "products": [ /* Product_Adapter normalized arrays */ ],
  "total": 120,
  "totalPages": 10,
  "page": 1
}
```

**`GET /products/{id}` Response:**
```json
{
  "id": 42,
  "name": "...",
  "related_products": [101, 102, 103],
  "variations": [ /* variation objects */ ],
  "variation_attributes": [
    { "name": "Color", "taxonomy": "pa_color", "options": [{"slug":"red","name":"Red"}] }
  ],
  "cross_sell_ids": [201, 202],
  "up_sell_ids": [301, 302],
  "weight": "1.5",
  "dimensions": {"length":"30","width":"20","height":"10"},
  "video_url": "https://youtube.com/watch?v=...",
  "images_360": [ /* 360° image URLs */ ]
}
```

### Cart

| Endpoint | Method | Auth | Description | Key Args |
|----------|--------|------|-------------|----------|
| `/cart` | GET | public | Current cart contents | — |
| `/cart/add` | POST | public | Add item to cart | `product_id` (required), `quantity`, `variation_id`, `variation` |
| `/cart/update` | POST | public | Update item quantity | `key`, `quantity` |
| `/cart/remove` | POST | public | Remove item | `key` |
| `/cart/coupons` | GET | public | Applied coupons | — |
| `/cart/coupon` | POST | public | Apply coupon | `code` |
| `/cart/remove-coupon` | POST | public | Remove coupon | `code` |
| `/cart/shipping-methods` | GET | public | Shipping rates | — |

**`GET /cart` Response:**
```json
{
  "items": [
    {
      "key": "cart_item_key_abc123",
      "id": 42,
      "name": "Product Name",
      "price": 150.00,
      "qty": 2,
      "subtotal": "300,00 €",
      "total": "300,00 €",
      "image": "https://...",
      "url": "/product/product-name/"
    }
  ],
  "subtotal": "300,00 €",
  "total": "300,00 €",
  "shipping_total": "10,00 €",
  "totalItems": 2,
  "currency": "EUR"
}
```

**`POST /cart/add` Request:**
```json
{
  "product_id": 42,
  "quantity": 1,
  "variation_id": 101,
  "variation": { "attribute_color": "red" }
}
```

**`GET /cart/shipping-methods` Response:**
```json
{
  "methods": [
    { "id": "flat_rate:1", "label": "Flat Rate", "cost": "10,00 €", "tax": "0,00 €" }
  ],
  "selected": "flat_rate:1"
}
```

### WooCommerce Extended

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/woo/attributes` | GET | public | All WC product attributes |
| `/woo/variations` | GET | public | Variations for a product (args: `product_id`) |
| `/woo/reviews` | GET | public | Product reviews |
| `/woo/reviews` | POST | logged-in + nonce | Submit review |

### User/Account

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/user/profile` | GET | verify_nonce | Current user profile |
| `/user/orders` | GET | verify_nonce | WC orders (args: `limit` default 10, `status` default `'any'`) |

### Public

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/page-data` | GET | public | Public settings + `currency_symbol` from WC |

---

## Data Adapters

**Location:** `includes/adapters/`

Adapters normalize WooCommerce objects into flat, predictable arrays suitable for ViewModels and REST responses. They are stateless — each call accepts input and returns an array.

### Product_Adapter

**Input:** `WC_Product` or product ID  
**Output:** Normalized array

| Field | Type | Description |
|-------|------|-------------|
| `id` | int | Product ID |
| `name` | string | Product title |
| `slug` | string | Product slug |
| `url` | string | Permalink |
| `image` | string | Main image URL or placeholder |
| `image_alt` | string | Alt text for main image |
| `gallery` | array | Array of image URLs |
| `price` | string | `wc_price()` HTML output |
| `regular_price` | string | `wc_price()` HTML output |
| `sale_price` | string | `wc_price()` HTML output |
| `on_sale` | bool | Currently on sale |
| `is_featured` | bool | Is featured product |
| `in_stock` | bool | Stock status |
| `rating` | float | Average rating (0-5) |
| `reviews_count` | int | Total reviews |
| `sku` | string | SKU |
| `categories` | array | `[{id, name, slug, url}]` |
| `tags` | array | `[{id, name, slug}]` |
| `type` | string | simple, variable, grouped, external |
| `short_description` | string | Sanitized HTML |
| `description` | string | Sanitized HTML |

**Variable product extensions:**

| Field | Type | Description |
|-------|------|-------------|
| `variations` | array | `[{id, price, regular_price, sale_price, image, in_stock, sku, attributes}]` |
| `attributes` | array | `[{name, taxonomy, options: [{slug, name}]}]` |

### Cart_Adapter

**Input:** None (reads `WC()->cart`)  
**Output:** Normalized array

| Field | Type | Description |
|-------|------|-------------|
| `items` | array | `[{key, product_id, variation_id, quantity, price, subtotal, total, subtotal_tax, tax, product: {Product_Adapter output}}]` |
| `items_count` | int | Total items |
| `subtotal` | string | `wc_price()` HTML |
| `subtotal_tax` | string | `wc_price()` HTML |
| `total` | string | `wc_price()` HTML |
| `total_formatted` | string | Formatted total |
| `total_tax` | string | `wc_price()` HTML |
| `shipping_total` | string | `wc_price()` HTML |
| `shipping_tax` | string | `wc_price()` HTML |
| `needs_shipping` | bool | Requires shipping |
| `needs_payment` | bool | Requires payment |
| `coupons` | array | `[{code, amount, discount, description}]` |
| `is_empty` | bool | Cart is empty |
| `currency` | string | Currency code |
| `currency_symbol` | string | Currency symbol |

### Order_Adapter

**Input:** `WC_Order` or order ID  
**Output:** Normalized array

| Field | Type | Description |
|-------|------|-------------|
| `id` | int | Order ID |
| `status` | string | Order status (e.g., `wc-completed`) |
| `total` | string | `wc_price()` HTML |
| `subtotal` | string | `wc_price()` HTML |
| `tax_total` | string | `wc_price()` HTML |
| `shipping_total` | string | `wc_price()` HTML |
| `currency` | string | Currency code |
| `date_created` | string | Formatted date |
| `date_modified` | string | Formatted date |
| `line_items` | array | `[{id, name, quantity, total, image, url}]` |
| `shipping_address` | object | Full shipping address |
| `billing_address` | object | Full billing address |
| `payment_method` | string | Payment method title |
| `customer_note` | string | Customer note |
| `coupon_lines` | array | Applied coupon codes |

### Coupon_Adapter

**Input:** `WC_Coupon`, coupon ID, or code string  
**Output:** Normalized array

| Field | Type | Description |
|-------|------|-------------|
| `id` | int | Coupon ID |
| `code` | string | Coupon code |
| `description` | string | Description |
| `discount_type` | string | `percent`, `fixed_cart`, `fixed_product` |
| `amount` | string | Discount amount |
| `minimum_amount` | string | Minimum spend |
| `maximum_amount` | string | Maximum spend |
| `expiry_date` | string | Expiry date |
| `product_ids` | array | Product IDs (empty = all) |
| `excluded_product_ids` | array | Excluded product IDs |
| `usage_limit` | int | Usage limit (null = unlimited) |
| `usage_count` | int | Times used |
| `free_shipping` | bool | Grants free shipping |
| `individual_use` | bool | Cannot combine with other coupons |

### Category_Adapter

**Input:** `WP_Term` (product_cat) or term ID  
**Output:** Normalized array

| Field | Type | Description |
|-------|------|-------------|
| `id` | int | Term ID |
| `name` | string | Category name |
| `slug` | string | Slug |
| `url` | string | Permalink |
| `image` | string | Thumbnail URL |
| `count` | int | Product count |
| `description` | string | Category description |

---

## ViewModels

**Location:** `includes/ViewModels/`

ViewModels consume adapter output and provide presentation-ready properties and formatting methods. They handle HTML generation for UI components.

### Product_ViewModel

**Construction:**
```php
// From adapter output (array)
$vm = new Product_ViewModel( $adapter_output );

// From WC_Product directly
$vm = Product_ViewModel::from_wc_product( $wc_product );
```

**Properties:**

| Property | Type | Description |
|----------|------|-------------|
| `id` | int | Product ID |
| `title` | string | Product name |
| `slug` | string | URL slug |
| `permalink` | string | Full product URL |
| `description` | string | Full description HTML |
| `short_description` | string | Short description HTML |
| `price` | string | Formatted price HTML |
| `regular_price` | string | Regular price HTML |
| `sale_price` | string | Sale price HTML |
| `currency` | string | Currency code |
| `image` | string | Main image URL |
| `gallery` | array | Image URLs |
| `sku` | string | SKU |
| `stock_status` | string | In stock / Out of stock |
| `in_stock` | bool | Stock boolean |
| `type` | string | Product type |
| `add_to_cart_text` | string | Button label |
| `add_to_cart_url` | string | Add-to-cart URL |
| `categories` | array | Category data |
| `tags` | array | Tag data |
| `attributes` | array | Product attributes |
| `variations` | array | Variation data |
| `rating` | float | Average rating |
| `review_count` | int | Review count |
| `badge` | string | Sale / New badge HTML |

**Methods:**

| Method | Returns | Description |
|--------|---------|-------------|
| `formatted_price()` | string | `<span class="price-sale">...</span> <span class="price-original">...</span>` |
| `rating_stars()` | string | FontAwesome star icons + review count |
| `gallery_html()` | string | Swiper gallery with `pd-gallery-swiper` class |
| `gallery_thumbnails_html()` | string | Swiper thumbnails with `pd-gallery-thumbs-swiper` class |

**`formatted_price()` output example:**
```html
<span class="price-sale"><span class="woocommerce-Price-amount amount"><bdi>150,00&nbsp;<span class="woocommerce-Price-currencySymbol">&euro;</span></bdi></span></span>
<span class="price-original"><span class="woocommerce-Price-amount amount"><bdi>199,00&nbsp;<span class="woocommerce-Price-currencySymbol">&euro;</span></bdi></span></span>
```

**`gallery_html()` output example:**
```html
<div class="pd-gallery-swiper swiper">
  <div class="swiper-wrapper">
    <div class="swiper-slide"><img src="..." /></div>
    <div class="swiper-slide"><img src="..." /></div>
  </div>
</div>
```

### Order_ViewModel

**Properties:** Same fields as `Order_Adapter` output.

**Methods:**

| Method | Returns | Description |
|--------|---------|-------------|
| `formatted_status()` | string | Human-readable status label (e.g., "Completed") |
| `formatted_total()` | string | `wc_price()` HTML for order total |
| `formatted_subtotal()` | string | `wc_price()` HTML for subtotal |

### Coupon_ViewModel

**Properties:** Same fields as `Coupon_Adapter` output.

**Methods:**

| Method | Returns | Description |
|--------|---------|-------------|
| `discount_label()` | string | `"10%"` for percent type, `wc_price()` for fixed |
| `is_expired()` | bool | Whether coupon has expired |

### Category_ViewModel

**Properties:**

| Property | Type | Description |
|----------|------|-------------|
| `id` | int | Category ID |
| `name` | string | Category name |
| `slug` | string | URL slug |
| `permalink` | string | Full category URL |
| `description` | string | Category description |
| `image` | string | Thumbnail URL |
| `count` | int | Product count |

---

## Renderers

**Location:** `includes/renderer/`

Renderers consume ViewModel data and HTML templates to produce final rendered output. Each renderer reads a template file from `frontend/html/` and replaces `{{PLACEHOLDER}}` tokens.

### Product_Card

**Template:** `product-card.html`

| Placeholder | Data Source | CSS Class Context |
|-------------|-------------|-------------------|
| `{{BADGE}}` | `ViewModel->badge` | `product-badge`, `badge-sale`, `badge-new` |
| `{{URL}}` | `ViewModel->permalink` | `product-card` (href) |
| `{{IMAGE}}` | `ViewModel->image` | `product-image` |
| `{{NAME}}` | `ViewModel->title` | `product-info` |
| `{{RATING}}` | `ViewModel->rating_stars()` | `product-rating` |
| `{{CATEGORIES}}` | `ViewModel->categories` | `product-tagline` |
| `{{PRICE}}` | `ViewModel->formatted_price()` | `product-price-row`, `product-price`, `price-sale`, `price-original` |
| `{{ATC_BUTTON}}` | `ViewModel->add_to_cart_text` | `btn-primary` |

**Full CSS class list:**
`product-card`, `product-image`, `product-badge`, `badge-sale`, `badge-new`, `product-info`, `product-rating`, `product-tagline`, `product-price-row`, `product-price`, `price-sale`, `price-original`, `btn-primary`

### Cart_Item

**Template:** `cart-item.html`

| Placeholder | Data Source | CSS Class Context |
|-------------|-------------|-------------------|
| `{{ITEM_KEY}}` | `$item['key']` | `cart-item` (data-key) |
| `{{REMOVE_BTN}}` | Remove button HTML | `cart-item-remove` |
| `{{IMAGE}}` | `$item['product']['image']` | `cart-item-image` |
| `{{TITLE}}` | `$item['product']['name']` | `cart-item-name` |
| `{{URL}}` | `$item['product']['url']` | (href) |
| `{{PRICE}}` | `$item['price']` | `cart-item-price` |
| `{{QUANTITY}}` | `$item['quantity']` | `cart-qty-control`, `cart-qty-input` |
| `{{SUBTOTAL}}` | `$item['total']` | `cart-item-subtotal` |

**CSS classes:** `cart-item`, `cart-item-remove`, `cart-item-image`, `cart-item-details`, `cart-item-name`, `cart-item-price`, `cart-qty-control`, `cart-qty-btn`, `cart-qty-minus`, `cart-qty-plus`, `cart-qty-input`, `cart-item-subtotal`

### Checkout_Form

**Template:** `checkout-form.html`

| Placeholder | Data Source | CSS Class Context |
|-------------|-------------|-------------------|
| `{{BILLING_HEADING}}` | "Billing Details" | `checkout-form-heading` |
| `{{BILLING_FIELDS}}` | `WC()->checkout()->get_checkout_fields('billing')` | `checkout-form-fields` |
| `{{SHIPPING_HEADING}}` | "Shipping Details" | `checkout-form-heading` |
| `{{SHIPPING_FIELDS}}` | `WC()->checkout()->get_checkout_fields('shipping')` | `checkout-form-fields` |
| `{{PAYMENT_HEADING}}` | "Payment Method" | `checkout-form-heading` |
| `{{PAYMENT_METHODS}}` | `WC()->payment_gateways()->get_available_gateways()` | `checkout-form-payment` |
| `{{PLACE_ORDER_BTN}}` | Submit button | `checkout-place-order` |

**CSS classes:** `checkout-form`, `checkout-form-section`, `checkout-form-heading`, `checkout-form-fields`, `checkout-form-payment`, `checkout-form-actions`, `checkout-place-order`

### Checkout_Item

**Template:** `checkout-item.html`

| Placeholder | Data Source | CSS Class Context |
|-------------|-------------|-------------------|
| `{{IMAGE}}` | Line item image | `checkout-item-image` |
| `{{TITLE}}` | Line item name | `checkout-item-name` |
| `{{META}}` | Variation data | `checkout-item-meta` |
| `{{PRICE}}` | Line item price | `checkout-item-price` |
| `{{SUBTOTAL}}` | Line item total | `checkout-item-subtotal` |

**CSS classes:** `checkout-item`, `checkout-item-image`, `checkout-item-details`, `checkout-item-name`, `checkout-item-meta`, `checkout-item-price`, `checkout-item-total`, `checkout-item-subtotal`

### Order_Table

**Template:** `order-table.html`

| Placeholder | Data Source | CSS Class Context |
|-------------|-------------|-------------------|
| `{{TITLE}}` | `Order_ViewModel->id` | `order-table-title` |
| `{{STATUS_CLASS}}` | `Order_ViewModel->formatted_status()` | `order-table-status`, `order-status--{status}` |
| `{{STATUS}}` | `Order_ViewModel->formatted_status()` | (text) |
| `{{DATE}}` | `Order_ViewModel->date_created` | `order-table-date` |
| `{{TOTAL}}` | `Order_ViewModel->formatted_total()` | `order-table-total` |
| `{{TABLE_ROWS}}` | Line items HTML | `order-table-items` |
| `{{SUBTOTAL}}` | `Order_ViewModel->formatted_subtotal()` | `order-table-total-row` |
| `{{SHIPPING}}` | `Order_ViewModel->shipping_total` | `order-table-total-row` |

**CSS classes:** `order-table`, `order-table-header`, `order-table-title`, `order-table-status`, `order-table-meta`, `order-table-date`, `order-table-total`, `order-table-items`, `order-table-totals`, `order-table-total-row`, `order-table-total-row--grand`, `order-status--{status}`

### Order_Card

**Template:** `order-card.html`

| Placeholder | Data Source | CSS Class Context |
|-------------|-------------|-------------------|
| `{{URL}}` | Order detail URL | `order-card` (href) |
| `{{ORDER_NUMBER}}` | `Order_ViewModel->id` | `order-card-number` |
| `{{STATUS_CLASS}}` | `Order_ViewModel->formatted_status()` | `order-card-status`, `order-status--{status}` |
| `{{STATUS}}` | `Order_ViewModel->formatted_status()` | (text) |
| `{{DATE}}` | `Order_ViewModel->date_created` | `order-card-date` |
| `{{TOTAL}}` | `Order_ViewModel->formatted_total()` | `order-card-total` |
| `{{ITEM_COUNT}}` | `count($order->get_items())` | `order-card-count` |
| `{{VIEW_TEXT}}` | "View Order" | `order-card-view` |

**CSS classes:** `order-card`, `order-card-header`, `order-card-number`, `order-card-status`, `order-card-body`, `order-card-date`, `order-card-total`, `order-card-count`, `order-card-footer`, `order-card-view`, `order-status--{status}`

### Category_Card

**Template:** `category-card.html`

| Placeholder | Data Source | CSS Class Context |
|-------------|-------------|-------------------|
| `{{URL}}` | `Category_ViewModel->permalink` | `category-card` (href) |
| `{{IMAGE}}` | `Category_ViewModel->image` | `category-card-bg` |
| `{{NAME}}` | `Category_ViewModel->name` | `category-name` |
| `{{COUNT}}` | `Category_ViewModel->count` | `category-count` |
| `{{CTA}}` | "Shop Now" | `category-cta` |

**CSS classes:** `category-card`, `category-card-bg`, `category-card-overlay`, `category-card-content`, `category-count`, `category-name`, `category-cta`

---

## Server-Side Injection (WooCommerce_Injector)

**Files:** `includes/Injectors/class-woocommerce-injector.php`, `includes/Injectors/class-product-injector.php`, `includes/Injectors/class-cart-checkout-injector.php`, `includes/Injectors/class-account-injector.php`

The WooCommerce_Injector dispatches route-specific injection methods that replace placeholder content in static HTML templates with dynamic WooCommerce data. This happens **server-side** during the Shell router's `template_redirect` handler — before the HTML reaches the browser.

### Route Dispatch Table

| Route Pattern | Injector Method | Replaces |
|---------------|-----------------|----------|
| `shop` | `Product_Injector::inject_shop_content()` | `data-component="shop-grid"` |
| `category/*` | `Product_Injector::inject_shop_content()` | `data-component="shop-grid"` (filtered by category) |
| `product`, `product-detail`, `product/*` | `Product_Injector::inject_product_content()` | `[product_gallery]`, `[product_title]`, etc. |
| `cart` | `Cart_Checkout_Injector::inject_cart_content()` | `[woocommerce_cart]` |
| `checkout` | `Cart_Checkout_Injector::inject_checkout_content()` | `[woocommerce_checkout]` |
| `account`, `my-account` | `Account_Injector::inject_account_content()` | `[woocommerce_my_account]` |
| `orders` | `Account_Injector::inject_orders_content()` | Order grid |
| `order/*`, `order-detail` | `Account_Injector::inject_order_detail_content()` | Single order table |
| `homepage` (`index`, `''`) | `Product_Injector::inject_homepage_products()` + `inject_homepage_categories()` | `products-grid` + `category-grid` |

### Product_Injector Methods

#### `inject_shop_content()`

1. Fetches all product categories for filter buttons
2. Queries `wc_get_products()` with pagination, category filter, search, and sorting
3. For each product: `WC_Product` → `Product_Adapter` → `Product_ViewModel` → `Product_Card::render()`
4. Replaces `data-component="shop-grid"` with rendered card HTML
5. Builds server-side pagination with page links
6. Replaces filter button container with dynamic category buttons

#### `inject_product_content()`

Loads a single product and replaces **11 placeholders**:

| Placeholder | Content |
|-------------|---------|
| `[product_gallery]` | Swiper gallery via `Product_ViewModel::gallery_html()` |
| `[product_gallery_thumbs]` | Swiper thumbnails via `Product_ViewModel::gallery_thumbnails_html()` |
| `[product_title]` | Product name |
| `[product_price]` | `Product_ViewModel::formatted_price()` |
| `[product_rating]` | `Product_ViewModel::rating_stars()` |
| `[product_short_description]` | Short description |
| `[product_add_to_cart]` | Add-to-cart form (see below) |
| `[product_stock]` | Stock status text |
| `[product_sku]` | SKU |
| `[product_categories]` | Category links |
| `[product_description]` | Full description |

#### `render_add_to_cart()`

Builds add-to-cart HTML based on product type:

- **Variable products:** `<select>` dropdowns for each attribute (e.g., Color, Size) + quantity + add-to-cart button
- **Simple products:** Quantity control (minus/plus/input) + add-to-cart button
- **Out-of-stock:** "Out of stock" message (no form)

#### `inject_homepage_products()`

- Queries 6 latest published products
- Renders via `Product_ViewModel` → `Product_Card::render()`
- Replaces `products-grid` container

#### `inject_homepage_categories()`

- Fetches 6 root product categories
- Renders via `Category_ViewModel` → `Category_Card::render()`
- Replaces `category-grid` container

### Cart_Checkout_Injector Methods

#### `inject_cart_content()`

1. Calls `Cart_Adapter::normalize()` to get cart data
2. For each item: renders via `Cart_Item::render()` with template placeholders
3. Wraps items in WooCommerce-style `<table>` markup
4. Appends cart totals via `render_cart_totals()`
5. Replaces `[woocommerce_cart]` with complete cart HTML

#### `inject_checkout_content()`

1. Uses `WC()->checkout()` to render billing/shipping/payment fields
2. Fields are output as HTML form inputs with proper labels
3. Wraps in `Checkout_Form::render()` with section headings
4. Replaces `[woocommerce_checkout]` with complete checkout form

#### `render_cart_totals()`

Builds total rows:
- Subtotal
- Shipping (with method selection if available)
- Tax (if applicable)
- **Total** (grand total, bold)
- "Proceed to Checkout" button

### Account_Injector Methods

#### `inject_account_content()`

1. Renders user profile detail via `Account_Detail` renderer
2. Fetches 5 most recent orders via `wc_get_orders()`
3. Each order: `WC_Order` → `Order_Adapter` → `Order_ViewModel` → `Order_Card::render()`
4. Replaces `[woocommerce_my_account]` with profile + order grid

#### `inject_orders_content()`

- Lists up to 20 orders via `Order_Adapter` → `Order_ViewModel` → `Order_Card::render()`

#### `inject_order_detail_content()`

- Renders single order with line items via `Order_Table::render()`
- Includes billing/shipping addresses, payment method, status

---

## Frontend Templates

**Location:** `frontend/html/`

### shop.html

```html
<div data-component="shop-grid">
  <!-- Replaced by Product_Injector::inject_shop_content() -->
  <!-- Contains product card placeholders -->
</div>

<div class="shop-filters">
  <div class="filter-buttons">
    <!-- Replaced with dynamic category filter buttons -->
  </div>
</div>

<div class="shop-pagination">
  <!-- Replaced with server-side pagination -->
</div>
```

### product-detail.html

```html
<!-- 11 placeholders replaced by Product_Injector::inject_product_content() -->
<div class="product-gallery">[product_gallery]</div>
<div class="product-gallery-thumbs">[product_gallery_thumbs]</div>
<h1 class="product-title">[product_title]</h1>
<div class="product-price">[product_price]</div>
<div class="product-rating">[product_rating]</div>
<div class="product-short-desc">[product_short_description]</div>
<div class="product-add-to-cart">[product_add_to_cart]</div>
<div class="product-stock">[product_stock]</div>
<div class="product-sku">[product_sku]</div>
<div class="product-categories">[product_categories]</div>
<div class="product-description">[product_description]</div>
```

### cart.html

```html
<div class="woocommerce-cart">
  [woocommerce_cart]
  <!-- Replaced by Cart_Checkout_Injector::inject_cart_content() -->
  <!-- Includes: cart table + cart totals + proceed to checkout -->
</div>
```

### checkout.html

```html
<div class="woocommerce-checkout">
  [woocommerce_checkout]
  <!-- Replaced by Cart_Checkout_Injector::inject_checkout_content() -->
  <!-- Includes: billing fields + shipping fields + payment methods + place order -->
</div>
```

---

## JS Client (`phantom-data.js`)

**File:** `frontend/assets/js/phantom-data.js`

The client-side JavaScript handles dynamic interactions after initial page load. It uses the REST API for all data operations and maintains a 120-second cache for performance.

### PhantomServices.Api

REST client with caching:

```javascript
// Cached GET request (120s TTL)
PhantomServices.Api.get('/products', { per_page: 8 });

// POST with nonce
PhantomServices.Api.post('/cart/add', { product_id: 42, quantity: 1 });
```

### PhantomServices.Cart

Event delegation and cart operations:

```javascript
// Binds .phantom-add-to-cart click events
PhantomServices.Cart.init();

// Methods
PhantomServices.Cart.addItem(productId, quantity, variationId, variation);
PhantomServices.Cart.removeItem(key);
PhantomServices.Cart.updateQuantity(key, quantity);
```

**Event delegation:** Uses document-level click handler for `.phantom-add-to-cart` buttons, preventing double-submission and handling dynamically loaded cards.

### PhantomAdapters.ProductAdapter

Client-side normalizer for WooCommerce REST API responses:

```javascript
// Normalizes raw WC REST product data
const adapted = PhantomAdapters.ProductAdapter.normalize(rawProduct);
// Returns: { id, name, url, image, price, badge, rating, categories, ... }
```

### PhantomRenderer

Client-side HTML generation:

```javascript
// Product card from adapted data
const cardHtml = PhantomRenderer.ProductCard.render(adaptedProduct);

// Category card from adapted data
const catHtml = PhantomRenderer.CategoryCard.render(adaptedCategory);
```

Uses `{{PLACEHOLDER}}` template strings (`PRODUCT_CARD_TPL`, `CATEGORY_CARD_TPL`) that mirror the frontend HTML structure exactly.

### PhantomCore

SPA orchestrator:

```javascript
// Runs on DOMContentLoaded
PhantomCore.init();

// Skips WooCommerce injection on homepage
// (homepage uses server-side injection for static AETHER design)
if (isHomepage()) return;

// Binds: add-to-cart, quantity controls, remove, checkout, shipping, auth forms, my account
```

**Injection skip on homepage:** `PhantomCore` detects homepage routes (`index`, `''`) and skips client-side WooCommerce injection to preserve the static AETHER design. Homepage products and categories are injected server-side via `Product_Injector`.

---

## Data Flow Diagrams

### Product Listing (Shop Page)

```
1. Browser requests /shop
2. Shell::handle_request() → reads frontend/shop.html
3. WooCommerce_Injector::inject_shop_content():
   a. Product_Injector::get_categories() → WC product_cat terms
   b. wc_get_products({ per_page: 12, page: 1, category: [...] })
   c. For each WC_Product:
      Product_Adapter::normalize($product)
        → Product_ViewModel($adapter_output)
          → Product_Card::render($viewModel)
   d. Replace data-component="shop-grid" with HTML
   e. Replace filter div with category buttons
   f. Replace pagination div with page links
4. Shell injects CSS vars + phantomData + SEO
5. Browser renders shop.html with product cards
6. phantom-data.js (optional) fetches /products for AJAX pagination
```

### Product Detail Page

```
1. Browser requests /product/my-product
2. Shell::handle_request() → reads frontend/product-detail.html
3. Product_Injector::inject_product_content():
   a. wc_get_product($productId)
   b. Product_Adapter::normalize($product)
   c. Product_ViewModel($adapter_output)
   d. Replace 11 [product_*] placeholders:
      - gallery_html() → Swiper HTML
      - formatted_price() → price spans
      - rating_stars() → FontAwesome stars
      - render_add_to_cart() → form with variation selects
4. Browser renders with full product UI
5. Swiper.js initializes gallery carousel
```

### Cart Operations (Client-Side)

```
1. User clicks .phantom-add-to-cart button
2. PhantomServices.Cart.addItem(productId, quantity):
   a. POST /phantom/v1/cart/add { product_id, quantity }
   b. WooCommerce: WC()->cart->add_to_cart()
   c. Cart_Adapter::normalize() → full cart response
   d. Update cart count badge
   e. Show cart flyout / redirect
3. User clicks cart item remove:
   a. DELETE /phantom/v1/cart/{key}
   b. Re-render cart items
   c. Update totals
```

### Checkout Flow

```
1. User navigates to /checkout
2. Cart_Checkout_Injector::inject_checkout_content():
   a. WC()->checkout()->get_checkout_fields('billing')
   b. WC()->checkout()->get_checkout_fields('shipping')
   c. WC()->payment_gateways()->get_available_gateways()
   d. Checkout_Form::render() → complete form HTML
3. User fills fields, submits
4. phantom-data.js initCheckout() handles form submission:
   a. POST to WC AJAX endpoint
   b. WC processes order
   c. Redirect to /thank-you/{order_id}
```

---

## Key Integration Points

### WooCommerce Bridge

**File:** `includes/Bridges/class-woocommerce-bridge.php`

Extends `Plugin_Bridge` abstract base. Handles:

- Cart session initialization (`wc_load_cart()`)
- Product query filters
- Checkout field customization
- Payment gateway styling
- Mini-cart widget integration

**Initialization:** `Bridge_Manager::init_all()` → `WooCommerce_Bridge::init()` on `init` priority 1.

### Security

| Operation | Protection |
|-----------|------------|
| Cart add/update/remove | WC nonce verification + `wc_load_cart()` |
| Coupon apply/remove | WC nonce verification |
| Checkout submission | WC checkout nonce + rate limiting |
| Order access | `get_current_user_id()` check + `wc_customer_belong_to_order()` |
| Admin product CRUD | `manage_woocommerce` capability |
| Review submission | `logged_in` + nonce verification |

### Performance

- **Server-side injection:** Single database query per page (no AJAX for initial render)
- **Adapter caching:** Transient-based caching via `Data_Provider` base class
- **Client-side cache:** `PhantomServices.Api` caches REST responses for 120 seconds
- **Image optimization:** Product images use WooCommerce registered sizes (`woocommerce_thumbnail`, `woocommerce_gallery_thumbnail`)
- **Swiper lazy-loading:** Gallery uses Swiper's `lazy` module for deferred image loading

### Template Pack Compatibility

WooCommerce components respect template pack overrides:

- `product-card.html` can be overridden per pack (Dark, Minimal, Bold)
- `cart-item.html`, `checkout-form.html` are pack-aware via `Component_Renderer`
- `phantom_template_pack` option controls which pack's templates are loaded

---

## File Reference

| File | Lines | Purpose |
|------|-------|---------|
| `includes/Api/class-rest-controller.php` | 2300+ | REST endpoints (18 WC routes) |
| `includes/adapters/class-product-adapter.php` | ~200 | WC_Product normalization |
| `includes/adapters/class-cart-adapter.php` | ~150 | WC_Cart normalization |
| `includes/adapters/class-order-adapter.php` | ~180 | WC_Order normalization |
| `includes/adapters/class-coupon-adapter.php` | ~120 | WC_Coupon normalization |
| `includes/adapters/class-category-adapter.php` | ~80 | product_cat term normalization |
| `includes/ViewModels/class-product-viewmodel.php` | ~250 | Product presentation logic |
| `includes/ViewModels/class-order-viewmodel.php` | ~100 | Order presentation logic |
| `includes/ViewModels/class-coupon-viewmodel.php` | ~80 | Coupon presentation logic |
| `includes/ViewModels/class-category-viewmodel.php` | ~60 | Category presentation logic |
| `includes/renderer/class-product-card.php` | ~80 | Product card rendering |
| `includes/renderer/class-cart-item.php` | ~60 | Cart item rendering |
| `includes/renderer/class-checkout-form.php` | ~80 | Checkout form rendering |
| `includes/renderer/class-checkout-item.php` | ~50 | Checkout line item rendering |
| `includes/renderer/class-order-table.php` | ~90 | Order detail table rendering |
| `includes/renderer/class-order-card.php` | ~70 | Order summary card rendering |
| `includes/renderer/class-category-card.php` | ~50 | Category card rendering |
| `includes/Injectors/class-woocommerce-injector.php` | ~100 | Route dispatch |
| `includes/Injectors/class-product-injector.php` | ~300 | Product/shop/homepage injection |
| `includes/Injectors/class-cart-checkout-injector.php` | ~200 | Cart/checkout injection |
| `includes/Injectors/class-account-injector.php` | ~150 | Account/orders injection |
| `includes/Bridges/class-woocommerce-bridge.php` | ~150 | WooCommerce bridge integration |
| `frontend/html/shop.html` | ~100 | Shop page template |
| `frontend/html/product-detail.html` | ~150 | Product detail template |
| `frontend/html/cart.html` | ~80 | Cart page template |
| `frontend/html/checkout.html` | ~100 | Checkout page template |
| `frontend/html/account.html` | ~80 | Account page template |
| `frontend/assets/js/phantom-data.js` | 2364 | Client-side JS (WooCommerce functions) |

---

## Troubleshooting

### Common Issues

| Symptom | Cause | Fix |
|---------|-------|-----|
| Product prices show as escaped HTML (`&lt;span&gt;`) | `esc_html()` on HTML price strings | Use `wp_kses_post()` for price rendering |
| Cart returns 500 | Missing `wc_load_cart()` call | Ensure `wc_load_cart()` is called before cart operations |
| Cart returns 401 | Private `verify_nonce()` method | `verify_nonce()` must be `public` (permission callbacks called externally) |
| Empty shop page | `wc_get_products()` returns nothing | Check WooCommerce is active + products exist + query args |
| Gallery images not loading | Swiper not initialized | Ensure Swiper JS/CSS loaded + `pd-gallery-swiper` class present |
| Checkout form missing | `[woocommerce_checkout]` not replaced | Check `Cart_Checkout_Injector` is hooked + WooCommerce active |
| Order access denied | User doesn't own order | Check `wc_customer_belong_to_order()` validation |
| Product variations not loading | Missing variation attributes | Ensure `Product_Adapter` extracts `variation_attributes` for variable products |
