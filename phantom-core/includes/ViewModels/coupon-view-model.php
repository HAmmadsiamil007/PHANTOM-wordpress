<?php
declare(strict_types=1);

namespace PhantomCore\ViewModels;

use PhantomCore\Contracts\ViewModelInterface;

defined('ABSPATH') || exit;

final class Coupon_ViewModel implements ViewModelInterface {

  public int $id;
  public string $code;
  public string $description;
  public string $discount_type;
  public float $amount;
  public string $minimum_amount;
  public string $maximum_amount;
  public string $expiry_date;
  public array $product_ids;
  public array $excluded_product_ids;
  public int $usage_limit;
  public int $usage_count;
  public bool $free_shipping;
  public bool $individual_use;

  public static function from_adapter_output(array $data): self {
    $vm = new self();
    $vm->id = (int) ($data['id'] ?? 0);
    $vm->code = (string) ($data['code'] ?? '');
    $vm->description = (string) ($data['description'] ?? '');
    $vm->discount_type = (string) ($data['discount_type'] ?? 'fixed_cart');
    $vm->amount = (float) ($data['amount'] ?? 0);
    $vm->minimum_amount = (string) ($data['minimum_amount'] ?? '');
    $vm->maximum_amount = (string) ($data['maximum_amount'] ?? '');
    $vm->expiry_date = (string) ($data['expiry_date'] ?? '');
    $vm->product_ids = (array) ($data['product_ids'] ?? []);
    $vm->excluded_product_ids = (array) ($data['excluded_product_ids'] ?? []);
    $vm->usage_limit = (int) ($data['usage_limit'] ?? 0);
    $vm->usage_count = (int) ($data['usage_count'] ?? 0);
    $vm->free_shipping = (bool) ($data['free_shipping'] ?? false);
    $vm->individual_use = (bool) ($data['individual_use'] ?? false);
    return $vm;
  }

  public function discount_label(): string {
    $labels = [
      'percent'       => '%',
      'fixed_cart'    => wc_get_price_decimal_separator(),
      'fixed_product' => wc_get_price_decimal_separator(),
    ];
    $symbol = $labels[$this->discount_type] ?? '';
    if ($this->discount_type === 'percent') {
      return $this->amount . '%';
    }
    return wc_price($this->amount);
  }

  public function is_expired(): bool {
    if (empty($this->expiry_date)) return false;
    return strtotime($this->expiry_date) < time();
  }

  public function to_array(): array {
    return [
      'id' => $this->id,
      'code' => $this->code,
      'description' => $this->description,
      'discount_type' => $this->discount_type,
      'amount' => $this->amount,
      'discount_label' => $this->discount_label(),
      'minimum_amount' => $this->minimum_amount,
      'maximum_amount' => $this->maximum_amount,
      'expiry_date' => $this->expiry_date,
      'is_expired' => $this->is_expired(),
      'product_ids' => $this->product_ids,
      'excluded_product_ids' => $this->excluded_product_ids,
      'usage_limit' => $this->usage_limit,
      'usage_count' => $this->usage_count,
      'free_shipping' => $this->free_shipping,
      'individual_use' => $this->individual_use,
    ];
  }
}
