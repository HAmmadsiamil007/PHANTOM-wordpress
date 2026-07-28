<?php
declare(strict_types=1);

namespace PhantomCore\Engine\Injectors;

use PhantomCore\Adapters\Order_Adapter;
use PhantomCore\Adapters\User_Adapter;
use PhantomCore\ViewModels\Order_ViewModel;

defined('ABSPATH') || exit;

class Account_Injector extends Base_Injector {

  public function inject(string $html): string {
    return $html;
  }

  public function inject_account_content(string $html): string {
    if (!function_exists('WC') || !is_user_logged_in()) {
      return str_replace(
        '[woocommerce_my_account]',
        '<div class="account-login"><p class="text-center">Please <a href="' . esc_url(wp_login_url(get_permalink())) . '">log in</a> to view your account.</p></div>',
        $html
      );
    }

    $user = wp_get_current_user();
    $user_adapter = new User_Adapter();
    $user_data = $user_adapter->normalize($user);
    $detail_renderer = $this->get_renderer('account_detail');

    $account_html = '';
    if ($detail_renderer) {
      $account_html .= $detail_renderer->render([
        'email'         => $user_data['email'],
        'display_name'  => $user_data['display_name'],
        'greeting'      => 'Welcome back, ' . $user_data['display_name'],
        'first_name'    => $user->first_name ?? '',
        'last_name'     => $user->last_name ?? '',
        'phone'         => get_user_meta($user_data['id'], 'billing_phone', true) ?: '',
        'edit_url'      => home_url('/account'),
      ]);
    }

    $account_html .= '<div class="account-orders-section"><h3>Recent Orders</h3>';
    $customer_orders = wc_get_orders([
      'customer' => $user_data['id'], 'limit' => 5, 'orderby' => 'date', 'order' => 'DESC',
    ]);

    if (!empty($customer_orders)) {
      $order_card = $this->get_renderer('order_card');
      if ($order_card) {
        $order_adapter = new Order_Adapter();
        $account_html .= '<div class="orders-grid">';
        foreach ($customer_orders as $order) {
          $normalized = $order_adapter->normalize($order);
          $vm = Order_ViewModel::from_adapter_output($normalized);
          $data = $vm->to_array();
          $data['order_number'] = '#' . $data['id'];
          $data['url'] = home_url('/order/' . $data['id'] . '/');
          $data['view_text'] = 'View Order';
          $account_html .= $order_card->render($data);
        }
        $account_html .= '</div>';
      }
    } else {
      $account_html .= '<p class="text-center">No orders yet.</p>';
    }

    $account_html .= '</div>';
    return str_replace('[woocommerce_my_account]', $account_html, $html);
  }

  public function inject_orders_content(string $html): string {
    if (!is_user_logged_in()) {
      return str_replace('[orders_content]', '<p class="text-center">Please log in to view your orders.</p>', $html);
    }

    $user = wp_get_current_user();
    $customer_orders = wc_get_orders([
      'customer' => $user->ID, 'limit' => 20, 'orderby' => 'date', 'order' => 'DESC',
    ]);

    $order_card = $this->get_renderer('order_card');
    $order_adapter = new Order_Adapter();

    if (empty($customer_orders)) {
      $content = '<div class="orders-empty"><p class="text-center">No orders found.</p><a href="' . esc_url(home_url('/shop')) . '" class="btn btn-primary">Start Shopping</a></div>';
    } elseif ($order_card) {
      $content = '<div class="orders-grid">';
      foreach ($customer_orders as $order) {
        $normalized = $order_adapter->normalize($order);
        $vm = Order_ViewModel::from_adapter_output($normalized);
        $data = $vm->to_array();
        $data['order_number'] = '#' . $data['id'];
        $data['url'] = home_url('/order/' . $data['id'] . '/');
        $data['view_text'] = 'View Order';
        $data['item_count'] = count($normalized['line_items']);
        $content .= $order_card->render($data);
      }
      $content .= '</div>';
    } else {
      $content = '<p class="text-center">Orders list unavailable.</p>';
    }

    return $this->replace_inner_by_component($html, 'orders-grid', $content);
  }

  public function inject_order_detail_content(string $html): string {
    if (!is_user_logged_in()) {
      return str_replace('[order_detail_content]', '<p class="text-center">Please log in to view order details.</p>', $html);
    }

    $order_id = (int) ($_GET['order_id'] ?? 0);
    if (!$order_id) {
      $segments = explode('/', trim($_SERVER['REQUEST_URI'] ?? '', '/'));
      foreach ($segments as $i => $s) {
        if ($s === 'order' && isset($segments[$i + 1]) && is_numeric($segments[$i + 1])) {
          $order_id = (int) $segments[$i + 1];
          break;
        }
      }
    }

    if (!$order_id) {
      return str_replace('[order_detail_content]', '<p class="text-center">Order not found.</p>', $html);
    }

    $order_adapter = new Order_Adapter();
    $normalized = $order_adapter->normalize($order_id);

    if (empty($normalized['id'])) {
      return str_replace('[order_detail_content]', '<p class="text-center">Order not found.</p>', $html);
    }

    $vm = Order_ViewModel::from_adapter_output($normalized);
    $order_table = $this->get_renderer('order_table');

    if (!$order_table) {
      return str_replace('[order_detail_content]', '<p class="text-center">Order detail unavailable.</p>', $html);
    }

    $rows = '';
    foreach ($normalized['line_items'] as $item) {
      $rows .= '<tr>';
      $rows .= '<td>' . ($item['url'] ? '<a href="' . esc_url($item['url']) . '">' . esc_html($item['name']) . '</a>' : esc_html($item['name'])) . '</td>';
      $rows .= '<td>' . wc_price($item['total'] / max(1, $item['quantity'])) . '</td>';
      $rows .= '<td>' . (int) $item['quantity'] . '</td>';
      $rows .= '<td>' . wc_price($item['total']) . '</td>';
      $rows .= '</tr>';
    }

    $data = [
      'title'      => 'Order #' . $normalized['id'],
      'status'     => $vm->formatted_status(),
      'date'       => $normalized['date_created'],
      'total'      => $vm->formatted_total(),
      'table_rows' => $rows,
      'subtotal'   => $vm->formatted_subtotal(),
      'shipping'   => wc_price($normalized['shipping_total']),
    ];

    $content = $order_table->render($data);
    return $this->replace_inner_by_component($html, 'order-detail', $content);
  }
}
