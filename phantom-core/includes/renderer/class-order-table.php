<?php
declare(strict_types=1);

namespace PhantomCore\Renderer;

defined('ABSPATH') || exit;

class Order_Table extends Component_Renderer {

  private string $template;

  public function __construct() {
    $this->template = $this->load_template('order-table') ?: $this->default_template();
  }

  public function render(array $data): string {
    $status_class = 'order-status--' . sanitize_html_class($data['status']);

    return $this->inject($this->template, [
      'title' => esc_html($data['title']),
      'status_class' => $status_class,
      'status' => esc_html($data['status']),
      'date' => esc_html($data['date']),
      'total' => wp_kses_post($data['total']),
      'table_rows' => $data['table_rows'] ?? '',
      'subtotal' => wp_kses_post($data['subtotal']),
      'shipping' => wp_kses_post($data['shipping']),
    ]);
  }

  private function default_template(): string {
    return '<div class="order-table">
      <div class="order-table-header">
        <h3 class="order-table-title">{{TITLE}}</h3>
        <span class="order-table-status {{STATUS_CLASS}}">{{STATUS}}</span>
      </div>
      <div class="order-table-meta">
        <div class="order-table-date"><span class="order-table-label">Order Date</span><span class="order-table-value">{{DATE}}</span></div>
        <div class="order-table-total"><span class="order-table-label">Total</span><span class="order-table-value">{{TOTAL}}</span></div>
      </div>
      <table class="order-table-items">
        <thead><tr><th>Product</th><th>Price</th><th>Quantity</th><th>Subtotal</th></tr></thead>
        <tbody>{{TABLE_ROWS}}</tbody>
      </table>
      <div class="order-table-totals">
        <div class="order-table-total-row"><span>Subtotal</span><span>{{SUBTOTAL}}</span></div>
        <div class="order-table-total-row"><span>Shipping</span><span>{{SHIPPING}}</span></div>
        <div class="order-table-total-row order-table-total-row--grand"><span>Total</span><span>{{TOTAL}}</span></div>
      </div>
    </div>';
  }
}
