<?php
declare(strict_types=1);

namespace PhantomCore\Engine\Injectors;

use PhantomCore\Adapters\Cart_Adapter;

defined('ABSPATH') || exit;

class Cart_Checkout_Injector extends Base_Injector {

  public function inject(string $html): string {
    return $html;
  }

  public function inject_cart_content(string $html): string {
    if (!function_exists('WC') || !WC()->cart) {
      return str_replace('[woocommerce_cart]', '', $html);
    }

    $cart_adapter = new Cart_Adapter();
    $normalized = $cart_adapter->normalize();
    $item_renderer = $this->get_renderer('cart_item');

    $items_html = '';
    if ($item_renderer && !empty($normalized['items'])) {
      foreach ($normalized['items'] as $item) {
        $product = $item['product'] ?? [];
        $items_html .= $item_renderer->render([
          'item_key'  => $item['key'],
          'image_url' => $product['image'] ?? wc_placeholder_img_src(),
          'title'     => $product['name'] ?? '',
          'permalink' => $product['url'] ?? '',
          'price'     => $item['price'],
          'quantity'  => $item['quantity'],
          'subtotal'  => $item['subtotal'],
        ]);
      }
    }

    if (empty($items_html)) {
      $cart_html = '<div class="cart-empty"><p class="text-center">Your cart is empty.</p><a href="' . esc_url(home_url('/shop')) . '" class="btn btn-primary">Browse Products</a></div>';
      return str_replace('[woocommerce_cart]', $cart_html, $html);
    }

    $cart_html = '<div class="woocommerce"><form class="woocommerce-cart-form" method="post">';
    $cart_html .= '<table class="shop_table shop_table_responsive cart"><thead><tr>
      <th class="product-remove">&nbsp;</th>
      <th class="product-thumbnail">&nbsp;</th>
      <th class="product-name">Product</th>
      <th class="product-price">Price</th>
      <th class="product-quantity">Quantity</th>
      <th class="product-subtotal">Subtotal</th>
    </tr></thead><tbody>' . $items_html . '</tbody></table></form>';
    $cart_html .= $this->render_cart_totals();
    $cart_html .= '</div>';

    return str_replace('[woocommerce_cart]', $cart_html, $html);
  }

  public function inject_checkout_content(string $html): string {
    if (!function_exists('WC') || !WC()->checkout()) {
      return str_replace('[woocommerce_checkout]', '', $html);
    }

    $checkout = WC()->checkout();
    $form_renderer = $this->get_renderer('checkout_form');

    ob_start();
    foreach ($checkout->get_checkout_fields('billing') as $key => $field) {
      woocommerce_form_field($key, $field, $checkout->get_value($key));
    }
    $billing_fields = ob_get_clean();

    ob_start();
    foreach ($checkout->get_checkout_fields('shipping') as $key => $field) {
      woocommerce_form_field($key, $field, $checkout->get_value($key));
    }
    $shipping_fields = ob_get_clean();

    ob_start();
    try {
      WC()->payment_gateways();
      wc_get_template('checkout/payment.php');
    } catch (\Throwable $e) {
      if (defined('WP_DEBUG') && WP_DEBUG) {
        echo '<div class="checkout-error"><p>Payment gateway error: ' . esc_html($e->getMessage()) . '</p></div>';
      }
    }
    $payment_html = ob_get_clean();

    $data = [
      'billing_heading'  => 'Billing Details',
      'billing_fields'   => $billing_fields,
      'shipping_heading' => 'Shipping Details',
      'shipping_fields'  => $shipping_fields,
      'payment_heading'  => 'Payment Method',
      'payment_methods'  => $payment_html,
      'place_order_btn'  => '<button type="submit" class="btn btn-primary checkout-place-order" name="woocommerce_checkout_place_order" id="place_order">Place Order</button>',
    ];

    $form_html = $form_renderer ? $form_renderer->render($data) : '';
    $full_html = '<form name="checkout" method="post" class="checkout woocommerce-checkout" action="' . esc_url(home_url('/checkout')) . '" enctype="multipart/form-data">';
    $full_html .= $form_html;
    $full_html .= '</form>';

    return str_replace('[woocommerce_checkout]', $full_html, $html);
  }

  private function render_cart_totals(): string {
    if (!function_exists('WC') || !WC()->cart) {
      return '';
    }

    $cart_adapter = new Cart_Adapter();
    $normalized = $cart_adapter->normalize();
    $html = '<div class="cart-collaterals"><div class="cart_totals">';
    $html .= '<h3 class="cart-totals-heading">Cart Totals</h3>';
    $html .= '<div class="cart-totals-table">';
    $html .= '<div class="cart-total-row"><span class="cart-total-label">Subtotal</span><span class="cart-total-value">' . $normalized['subtotal'] . '</span></div>';

    if ($normalized['needs_shipping']) {
      $html .= '<div class="cart-total-row"><span class="cart-total-label">Shipping</span><span class="cart-total-value">' . $normalized['shipping_total'] . '</span></div>';
    }

    if (wc_tax_enabled()) {
      $html .= '<div class="cart-total-row"><span class="cart-total-label">Tax</span><span class="cart-total-value">' . $normalized['total_tax'] . '</span></div>';
    }

    $html .= '<div class="cart-total-row cart-total-row--grand"><span class="cart-total-label">Total</span><span class="cart-total-value">' . $normalized['total_formatted'] . '</span></div>';
    $html .= '</div>';
    $html .= '<div class="cart-actions">';
    $html .= '<a href="' . esc_url(home_url('/checkout')) . '" class="btn btn-primary checkout-button">Proceed to Checkout</a>';
    $html .= '</div></div></div>';

    return $html;
  }
}
