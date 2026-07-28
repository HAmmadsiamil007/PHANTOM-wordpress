<?php
declare(strict_types=1);

namespace PhantomCore\Renderer;

defined('ABSPATH') || exit;

class Checkout_Form extends Component_Renderer {

  private string $template;

  public function __construct() {
    $this->template = $this->load_template('checkout-form') ?: $this->default_template();
  }

  public function render(array $data): string {
    return $this->inject($this->template, [
      'billing_heading' => esc_html($data['billing_heading'] ?? 'Billing Details'),
      'billing_fields' => $data['billing_fields'] ?? '',
      'shipping_heading' => esc_html($data['shipping_heading'] ?? 'Shipping Details'),
      'shipping_fields' => $data['shipping_fields'] ?? '',
      'payment_heading' => esc_html($data['payment_heading'] ?? 'Payment Method'),
      'payment_methods' => $data['payment_methods'] ?? '',
      'place_order_btn' => $data['place_order_btn'] ?? '<button type="submit" class="btn btn-primary checkout-place-order">Place Order</button>',
    ]);
  }

  private function default_template(): string {
    return '<div class="checkout-form">
      <div class="checkout-form-section">
        <h3 class="checkout-form-heading">{{BILLING_HEADING}}</h3>
        <div class="checkout-form-fields">{{BILLING_FIELDS}}</div>
      </div>
      <div class="checkout-form-section">
        <h3 class="checkout-form-heading">{{SHIPPING_HEADING}}</h3>
        <div class="checkout-form-fields">{{SHIPPING_FIELDS}}</div>
      </div>
      <div class="checkout-form-section">
        <h3 class="checkout-form-heading">{{PAYMENT_HEADING}}</h3>
        <div class="checkout-form-payment">{{PAYMENT_METHODS}}</div>
      </div>
      <div class="checkout-form-actions">{{PLACE_ORDER_BTN}}</div>
    </div>';
  }
}
