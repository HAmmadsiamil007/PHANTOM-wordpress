<?php
declare(strict_types=1);

namespace PhantomCore\Adapters;

use PhantomCore\Contracts\AdapterInterface;

defined('ABSPATH') || exit;

class Order_Adapter implements AdapterInterface {

  public function normalize($input = null): array {
    $order = $input;
    if (is_numeric($order)) {
      $order = wc_get_order((int) $order);
    }
    if (!$order || !($order instanceof \WC_Order)) {
      return $this->empty();
    }

    return [
      'id'               => $order->get_id(),
      'status'           => $order->get_status(),
      'total'            => $order->get_total(),
      'subtotal'         => $order->get_subtotal(),
      'tax_total'        => $order->get_total_tax(),
      'shipping_total'   => $order->get_shipping_total(),
      'currency'         => $order->get_currency(),
      'date_created'     => $order->get_date_created() ? $order->get_date_created()->date_i18n('F j, Y') : '',
      'date_modified'    => $order->get_date_modified() ? $order->get_date_modified()->date_i18n('F j, Y') : '',
      'line_items'       => $this->get_line_items($order),
      'shipping_address' => $this->get_address($order->get_address('shipping')),
      'billing_address'  => $this->get_address($order->get_address('billing')),
      'payment_method'   => $order->get_payment_method_title(),
      'customer_note'    => $order->get_customer_note(),
      'coupon_lines'     => $this->get_coupons($order),
    ];
  }

  public function normalize_collection(array $inputs): array {
    return array_map([$this, 'normalize'], $inputs);
  }

  private function get_line_items(\WC_Order $order): array {
    $items = [];
    foreach ($order->get_items() as $item) {
      $product = $item->get_product();
      $items[] = [
        'id'       => $item->get_id(),
        'name'     => $item->get_name(),
        'quantity' => $item->get_quantity(),
        'total'    => $item->get_total(),
        'image'    => $product ? wp_get_attachment_url($product->get_image_id()) : '',
        'url'      => $product ? get_permalink($product->get_id()) : '',
      ];
    }
    return $items;
  }

  private function get_address(array $address): array {
    return [
      'first_name' => $address['first_name'] ?? '',
      'last_name'  => $address['last_name'] ?? '',
      'company'    => $address['company'] ?? '',
      'address_1'  => $address['address_1'] ?? '',
      'address_2'  => $address['address_2'] ?? '',
      'city'       => $address['city'] ?? '',
      'state'      => $address['state'] ?? '',
      'postcode'   => $address['postcode'] ?? '',
      'country'    => $address['country'] ?? '',
    ];
  }

  private function get_coupons(\WC_Order $order): array {
    $coupons = [];
    foreach ($order->get_coupons() as $coupon) {
      $coupons[] = [
        'code'   => $coupon->get_code(),
        'amount' => $coupon->get_discount(),
      ];
    }
    return $coupons;
  }

  private function empty(): array {
    return [
      'id' => 0, 'status' => '', 'total' => 0, 'subtotal' => 0,
      'tax_total' => 0, 'shipping_total' => 0, 'currency' => 'USD',
      'date_created' => '', 'date_modified' => '', 'line_items' => [],
      'shipping_address' => [], 'billing_address' => [],
      'payment_method' => '', 'customer_note' => '', 'coupon_lines' => [],
    ];
  }
}
