<?php
declare(strict_types=1);

namespace PhantomCore\Adapters;

use PhantomCore\Contracts\AdapterInterface;

defined('ABSPATH') || exit;

class Cart_Adapter implements AdapterInterface {

  public function normalize($input = null): array {
    if (!class_exists('WooCommerce') || !isset(WC()->cart) || empty(WC()->cart)) {
      return $this->empty();
    }

    $cart = WC()->cart;

    if ($cart->is_empty()) {
      return $this->empty();
    }

    $items = [];
    $product_adapter = new Product_Adapter();

    foreach ($cart->get_cart() as $item_key => $item) {
      $product = $item['data'] ?? null;
      $product_data = $product ? $product_adapter->normalize($product) : [];

      $items[] = [
        'key' => $item_key,
        'product_id' => $item['product_id'] ?? 0,
        'variation_id' => $item['variation_id'] ?? 0,
        'quantity' => $item['quantity'] ?? 1,
        'price' => isset($item['data']) ? wc_price($item['data']->get_price()) : '',
        'subtotal' => wc_price($item['line_subtotal'] ?? 0),
        'total' => wc_price($item['line_total'] ?? 0),
        'subtotal_tax' => wc_price($item['line_subtotal_tax'] ?? 0),
        'tax' => wc_price($item['line_tax'] ?? 0),
        'product' => $product_data,
      ];
    }

    $coupons = [];
    foreach ($cart->get_applied_coupons() as $code) {
      $coupon = new \WC_Coupon($code);
      $coupons[] = [
        'code' => $code,
        'amount' => wc_price($coupon->get_amount()),
        'discount' => wc_price($cart->get_coupon_discount_amount($code)),
        'description' => $coupon->get_description(),
      ];
    }

    return [
      'items' => $items,
      'items_count' => $cart->get_cart_contents_count(),
      'subtotal' => wc_price($cart->get_subtotal()),
      'subtotal_tax' => wc_price($cart->get_subtotal_tax()),
      'total' => wc_price($cart->get_total('edit')),
      'total_formatted' => wc_price($cart->get_total('edit')),
      'total_tax' => wc_price($cart->get_total_tax()),
      'shipping_total' => wc_price((float) $cart->get_shipping_total()),
      'shipping_tax' => wc_price((float) $cart->get_shipping_tax()),
      'needs_shipping' => $cart->needs_shipping(),
      'needs_payment' => $cart->needs_payment(),
      'coupons' => $coupons,
      'is_empty' => $cart->is_empty(),
      'currency' => get_woocommerce_currency(),
      'currency_symbol' => get_woocommerce_currency_symbol(),
    ];
  }

  public function normalize_collection(array $carts): array {
    return array_map([$this, 'normalize'], $carts);
  }

  private function empty(): array {
    return [
      'items' => [],
      'items_count' => 0,
      'subtotal' => '',
      'subtotal_tax' => '',
      'total' => '',
      'total_formatted' => '',
      'total_tax' => '',
      'shipping_total' => '',
      'shipping_tax' => '',
      'needs_shipping' => false,
      'needs_payment' => false,
      'coupons' => [],
      'is_empty' => true,
      'currency' => 'USD',
      'currency_symbol' => '$',
    ];
  }
}
