<?php
declare(strict_types=1);

namespace PhantomCore\ViewModels;

use PhantomCore\Contracts\ViewModelInterface;

defined('ABSPATH') || exit;

final class Order_ViewModel implements ViewModelInterface {

  public int $id;
  public string $status;
  public float $total;
  public float $subtotal;
  public float $tax_total;
  public float $shipping_total;
  public string $currency;
  public string $date_created;
  public string $date_modified;
  public array $line_items;
  public array $shipping_address;
  public array $billing_address;
  public string $payment_method;
  public string $customer_note;
  public array $coupon_lines;

  public static function from_adapter_output(array $data): self {
    $vm = new self();
    $vm->id = (int) ($data['id'] ?? 0);
    $vm->status = (string) ($data['status'] ?? '');
    $vm->total = (float) ($data['total'] ?? 0);
    $vm->subtotal = (float) ($data['subtotal'] ?? 0);
    $vm->tax_total = (float) ($data['tax_total'] ?? 0);
    $vm->shipping_total = (float) ($data['shipping_total'] ?? 0);
    $vm->currency = (string) ($data['currency'] ?? 'USD');
    $vm->date_created = (string) ($data['date_created'] ?? '');
    $vm->date_modified = (string) ($data['date_modified'] ?? '');
    $vm->line_items = (array) ($data['line_items'] ?? []);
    $vm->shipping_address = (array) ($data['shipping_address'] ?? []);
    $vm->billing_address = (array) ($data['billing_address'] ?? []);
    $vm->payment_method = (string) ($data['payment_method'] ?? '');
    $vm->customer_note = (string) ($data['customer_note'] ?? '');
    $vm->coupon_lines = (array) ($data['coupon_lines'] ?? []);
    return $vm;
  }

  public function formatted_status(): string {
    $labels = [
      'pending'    => 'Pending Payment',
      'processing' => 'Processing',
      'on-hold'    => 'On Hold',
      'completed'  => 'Completed',
      'cancelled'  => 'Cancelled',
      'refunded'   => 'Refunded',
      'failed'     => 'Failed',
    ];
    return $labels[$this->status] ?? ucfirst($this->status);
  }

  public function formatted_total(): string {
    return html_entity_decode(wc_price($this->total, ['currency' => $this->currency]));
  }

  public function formatted_subtotal(): string {
    return html_entity_decode(wc_price($this->subtotal, ['currency' => $this->currency]));
  }

  public function to_array(): array {
    return [
      'id' => $this->id,
      'status' => $this->status,
      'status_label' => $this->formatted_status(),
      'total' => $this->formatted_total(),
      'subtotal' => $this->formatted_subtotal(),
      'tax_total' => $this->tax_total,
      'shipping_total' => $this->shipping_total,
      'currency' => $this->currency,
      'date_created' => $this->date_created,
      'date_modified' => $this->date_modified,
      'line_items' => $this->line_items,
      'shipping_address' => $this->shipping_address,
      'billing_address' => $this->billing_address,
      'payment_method' => $this->payment_method,
      'customer_note' => $this->customer_note,
      'coupon_lines' => $this->coupon_lines,
    ];
  }
}
