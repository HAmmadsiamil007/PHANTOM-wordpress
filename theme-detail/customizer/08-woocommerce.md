# Customizer Panel: WooCommerce (`phantom_woocommerce`)

**Panel ID:** `phantom_woocommerce`
**Section:** `woocommerce` (`phantom_section_woocommerce`)

---

## WooCommerce Section (`phantom_section_woocommerce`)

**Settings (30):**

| Setting | Control | CSS Var | Notes |
|---------|---------|---------|-------|
| `cart_enable` | `ast-toggle` | — | Enable custom cart |
| `cart_page_title` | `string` | — | Cart page heading |
| `cart_empty_message` | `string` | — | Empty cart message |
| `cart_continue-shopping_text` | `string` | — | Continue shopping text |
| `cart_update-button_text` | `string` | — | Update cart text |
| `cart_coupon_enable` | `ast-toggle` | — | Enable coupon field |
| `cart_cross-sells_enable` | `ast-toggle` | — | Show cross-sells |
| `cart_cross-sells_count` | `int` | — | Number of cross-sells |
| `checkout_enable` | `ast-toggle` | — | Enable custom checkout |
| `checkout_page_title` | `string` | — | Checkout page heading |
| `checkout_terms_enable` | `ast-toggle` | — | Show terms checkbox |
| `checkout_terms_text` | `string` | — | Terms text |
| `checkout_privacy_text` | `string` | — | Privacy policy text |
| `checkout_login_enable` | `ast-toggle` | — | Show login form on checkout |
| `checkout_coupon_enable` | `ast-toggle` | — | Show coupon form |
| `checkout_order-notes_enable` | `ast-toggle` | — | Show order notes field |
| `checkout_different-address_enable` | `ast-toggle` | — | Allow different shipping address |
| `shop_page_enable` | `ast-toggle` | — | Enable custom shop |
| `shop_empty_message` | `string` | — | Empty shop message |
| `shop_no-results_text` | `string` | — | No results text |
| Various WooCommerce display toggles | — | — | |

### Code Flow

```
User enables custom cart
  → phantom_cart_enable saved
  → WooCommerce_Injector checks this setting
  → If enabled:
      Cart_Checkout_Injector::inject_cart_content() renders custom cart
  → If disabled:
      Falls back to WC shortcode output
```

### Frontend

| Template | Placeholder | Injector |
|----------|-------------|----------|
| `cart.html` | `[woocommerce_cart]` | `Cart_Checkout_Injector` replaces with rendered cart via `Cart_Adapter` → `Cart_Item` renderer |
| `checkout.html` | `[woocommerce_checkout]` | `Checkout_Form` renderer |
| `shop.html` | `data-component="shop-grid"` | `Product_Injector` |
| `product-detail.html` | `[product_*]` | `Product_Injector` |

### Key Classes

| Class | File | Purpose |
|-------|------|---------|
| `WooCommerce_Injector` | `includes/woocommerce/` | Checks settings, dispatches to correct injector |
| `Cart_Checkout_Injector` | `includes/woocommerce/` | Renders custom cart/checkout |
| `Cart_Adapter` | `includes/adapters/class-cart-adapter.php` | WC Cart data adapter |
| `Cart_Item` | `includes/renderer/` | Individual cart item renderer |
| `Checkout_Form` | `includes/renderer/` | Checkout form renderer |
| `Product_Injector` | `includes/woocommerce/` | Product grid/detail rendering |
