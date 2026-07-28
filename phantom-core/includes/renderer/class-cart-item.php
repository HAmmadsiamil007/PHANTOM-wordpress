<?php
declare(strict_types=1);

namespace PhantomCore\Renderer;

defined('ABSPATH') || exit;

class Cart_Item extends Component_Renderer {

  private string $template;

  public function __construct() {
    $this->template = $this->load_template('cart-item') ?: $this->default_template();
  }

  public function render(array $data): string {
    $item_key = esc_attr($data['item_key'] ?? '');
    $remove_btn = '<button class="cart-remove-btn" data-key="' . $item_key . '" aria-label="Remove item"><i class="fas fa-times"></i></button>';

    $qty_html = '<div class="cart-qty-control">
      <button class="cart-qty-btn cart-qty-minus" data-key="' . $item_key . '" aria-label="Decrease quantity">&minus;</button>
      <input type="number" class="cart-qty-input" value="' . (int) ($data['quantity'] ?? 1) . '" min="1" data-key="' . $item_key . '" readonly>
      <button class="cart-qty-btn cart-qty-plus" data-key="' . $item_key . '" aria-label="Increase quantity">+</button>
    </div>';

    return $this->inject($this->template, [
      'item_key' => $item_key,
      'remove_btn' => $remove_btn,
      'image' => esc_url($data['image_url'] ?? ''),
      'title' => esc_html($data['title'] ?? ''),
      'url' => esc_url($data['permalink'] ?? ''),
      'price' => wp_kses_post($data['price'] ?? ''),
      'quantity' => $qty_html,
      'subtotal' => wp_kses_post($data['subtotal'] ?? ''),
    ]);
  }

  private function default_template(): string {
    return '<tr class="cart-item" data-key="{{ITEM_KEY}}">
      <td class="cart-item-remove">{{REMOVE_BTN}}</td>
      <td class="cart-item-image"><img loading="lazy" src="{{IMAGE}}" alt="{{TITLE}}"></td>
      <td class="cart-item-details">
        <a href="{{URL}}" class="cart-item-name">{{TITLE}}</a>
        <span class="cart-item-price">{{PRICE}}</span>
      </td>
      <td class="cart-item-quantity">{{QUANTITY}}</td>
      <td class="cart-item-subtotal">{{SUBTOTAL}}</td>
    </tr>';
  }
}
