<?php
declare(strict_types=1);
namespace PhantomCore\Bridges;
defined('ABSPATH') || exit;

class Wishlist_Bridge extends Plugin_Bridge {
  protected array $supported_hooks = ['init', 'wp_enqueue_scripts'];

  public function __construct() {
    $this->id = 'wishlist';
    $this->label = 'YITH WooCommerce Wishlist';
  }

  public function is_active(): bool {
    return class_exists('YITH_WCWL') || class_exists('YITH_WCWL_Wishlist');
  }

  public function init(): void {
    if (!$this->is_active()) {
      return;
    }
    add_filter('phantom_core/wishlist/count', [$this, 'get_wishlist_count']);
    add_filter('phantom_core/wishlist/items', [$this, 'get_wishlist_items']);
    add_action('phantom_before_body_close', [$this, 'inject_wishlist_data']);
  }

  public function get_wishlist_count(): int {
    if (!function_exists('yith_wcwl_count_products')) {
      return 0;
    }
    return (int) yith_wcwl_count_products();
  }

  public function get_wishlist_items(): array {
    if (!function_exists('yith_wcwl_get_wishlist_items')) {
      return [];
    }
    try {
      $items = yith_wcwl_get_wishlist_items();
      if (!is_array($items)) {
        return [];
      }
      return array_map(function ($item) {
        return [
          'id' => $item['prod_id'] ?? 0,
          'name' => $item['product_name'] ?? '',
          'url' => $item['product_url'] ?? '',
          'image' => $item['product_image'] ?? '',
          'price' => $item['product_price'] ?? '',
        ];
      }, $items);
    } catch (\Throwable $e) {
      return [];
    }
  }

  public function inject_wishlist_data(): void {
    $count = $this->get_wishlist_count();
    echo '<script id="phantom-wishlist-data">window.PhantomWishlistCount=' . (int) $count . ';</script>';
  }
}
