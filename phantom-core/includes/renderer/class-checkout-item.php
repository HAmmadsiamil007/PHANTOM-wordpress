<?php
declare(strict_types=1);

namespace PhantomCore\Renderer;

defined('ABSPATH') || exit;

class Checkout_Item extends Component_Renderer {

  private string $template;

  public function __construct() {
    $this->template = $this->load_template('checkout-item') ?: $this->default_template();
  }

  public function render(array $data): string {
    $meta = !empty($data['meta']) ? esc_html($data['meta']) : '';

    return $this->inject($this->template, [
      'image' => esc_url($data['image_url']),
      'title' => esc_html($data['title']),
      'meta' => $meta,
      'price' => wp_kses_post($data['price']),
      'subtotal' => wp_kses_post($data['subtotal']),
    ]);
  }

  private function default_template(): string {
    return '<div class="checkout-item">
      <div class="checkout-item-image"><img loading="lazy" src="{{IMAGE}}" alt="{{TITLE}}"></div>
      <div class="checkout-item-details">
        <h4 class="checkout-item-name">{{TITLE}}</h4>
        <span class="checkout-item-meta">{{META}}</span>
        <span class="checkout-item-price">{{PRICE}}</span>
      </div>
      <div class="checkout-item-total">
        <span class="checkout-item-subtotal">{{SUBTOTAL}}</span>
      </div>
    </div>';
  }
}
