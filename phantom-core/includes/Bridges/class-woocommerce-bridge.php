<?php
declare(strict_types=1);
namespace PhantomCore\Bridges;
defined('ABSPATH') || exit;

class WooCommerce_Bridge extends Plugin_Bridge {
  protected array $supported_hooks = ['woocommerce_init', 'woocommerce_loaded'];

  public function __construct() {
    $this->id = 'woocommerce';
    $this->label = 'WooCommerce';
  }

  public function is_active(): bool {
    return class_exists('WooCommerce');
  }

  public function init(): void {
    if (!$this->is_active()) {
      return;
    }
    add_action('woocommerce_init', [$this, 'on_woocommerce_init']);
    add_filter('woocommerce_add_to_cart_validation', [$this, 'validate_cart'], 10, 3);
  }

  public function on_woocommerce_init(): void {
    do_action('phantom_core/woocommerce/init');
  }

  public function validate_cart(bool $passed, int $product_id, int $quantity): bool {
    return $passed;
  }
}
