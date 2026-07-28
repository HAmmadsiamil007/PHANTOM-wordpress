<?php
declare(strict_types=1);

namespace PhantomCore\Renderer;

defined('ABSPATH') || exit;

class Order_Card extends Component_Renderer {

  private string $template;

  public function __construct() {
    $this->template = $this->load_template('order-card') ?: $this->default_template();
  }

  public function render(array $data): string {
    $status_class = 'order-status--' . sanitize_html_class($data['status']);

    return $this->inject($this->template, [
      'url' => esc_url($data['url']),
      'order_number' => esc_html($data['order_number']),
      'status_class' => $status_class,
      'status' => esc_html($data['status']),
      'date' => esc_html($data['date']),
      'total' => wp_kses_post($data['total']),
      'item_count' => (int) $data['item_count'],
      'view_text' => esc_html($data['view_text'] ?? 'View Order'),
    ]);
  }

  private function default_template(): string {
    return '<a href="{{URL}}" class="order-card">
      <div class="order-card-header">
        <span class="order-card-number">{{ORDER_NUMBER}}</span>
        <span class="order-card-status {{STATUS_CLASS}}">{{STATUS}}</span>
      </div>
      <div class="order-card-body">
        <div class="order-card-date">{{DATE}}</div>
        <div class="order-card-total">{{TOTAL}}</div>
        <div class="order-card-count">{{ITEM_COUNT}} items</div>
      </div>
      <div class="order-card-footer">
        <span class="order-card-view">{{VIEW_TEXT}} <i class="fas fa-arrow-right"></i></span>
      </div>
    </a>';
  }
}
