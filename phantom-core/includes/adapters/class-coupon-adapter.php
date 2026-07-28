<?php
declare(strict_types=1);

namespace PhantomCore\Adapters;

use PhantomCore\Contracts\AdapterInterface;

defined('ABSPATH') || exit;

class Coupon_Adapter implements AdapterInterface {

  public function normalize($input = null): array {
    $coupon = $input;
    if (is_numeric($coupon)) {
      $coupon = new \WC_Coupon((int) $coupon);
    } elseif (is_string($coupon)) {
      $coupon = new \WC_Coupon($coupon);
    }
    if (!$coupon || !($coupon instanceof \WC_Coupon) || !$coupon->get_id()) {
      return $this->empty();
    }

    return [
      'id'                => $coupon->get_id(),
      'code'              => $coupon->get_code(),
      'description'       => $coupon->get_description(),
      'discount_type'     => $coupon->get_discount_type(),
      'amount'            => $coupon->get_amount(),
      'minimum_amount'    => $coupon->get_minimum_amount(),
      'maximum_amount'    => $coupon->get_maximum_amount(),
      'expiry_date'       => $coupon->get_date_expires() ? $coupon->get_date_expires()->date_i18n('F j, Y') : '',
      'product_ids'       => $coupon->get_product_ids(),
      'excluded_product_ids' => $coupon->get_excluded_product_ids(),
      'usage_limit'       => $coupon->get_usage_limit(),
      'usage_count'       => $coupon->get_usage_count(),
      'free_shipping'     => $coupon->get_free_shipping(),
      'individual_use'    => $coupon->get_individual_use(),
    ];
  }

  public function normalize_collection(array $inputs): array {
    return array_map([$this, 'normalize'], $inputs);
  }

  private function empty(): array {
    return [
      'id' => 0, 'code' => '', 'description' => '', 'discount_type' => 'fixed_cart',
      'amount' => 0, 'minimum_amount' => '', 'maximum_amount' => '',
      'expiry_date' => '', 'product_ids' => [], 'excluded_product_ids' => [],
      'usage_limit' => 0, 'usage_count' => 0, 'free_shipping' => false,
      'individual_use' => false,
    ];
  }
}
