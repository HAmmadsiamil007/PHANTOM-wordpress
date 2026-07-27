<?php
declare(strict_types=1);

namespace PhantomCore\Engine;

defined('ABSPATH') || exit;

class Data_Engine {

  private Template_Loader $template_loader;
  private ?int $resolved_product_id = null;
  private ?int $resolved_post_id = null;
  private ?string $category_slug = null;

  public function __construct(Template_Loader $template_loader) {
    $this->template_loader = $template_loader;
  }

  public function with_product_id(int $id): self {
    $this->resolved_product_id = $id;
    return $this;
  }

  public function with_post_id(int $id): self {
    $this->resolved_post_id = $id;
    return $this;
  }

  public function with_category(string $slug): self {
    $this->category_slug = $slug;
    return $this;
  }

  public function get_resolved_product_id(): ?int {
    return $this->resolved_product_id;
  }

  public function get_resolved_post_id(): ?int {
    return $this->resolved_post_id;
  }

  public function get_category_slug(): ?string {
    return $this->category_slug;
  }

  public function get_template_loader(): Template_Loader {
    return $this->template_loader;
  }

  public function get_bridge_data(): array {
    $data = [
      'rest_url' => rest_url('phantom/v1'),
      'home_url' => home_url('/'),
      'nonce' => wp_create_nonce('wp_rest'),
      'api_nonce' => wp_create_nonce('phantom_api'),
      'ajax_url' => admin_url('admin-ajax.php'),
      'wc_ajax_url' => class_exists('WooCommerce')
        ? \WC_AJAX::get_endpoint('%%endpoint%%') : '',
      'currency_symbol' => function_exists('get_woocommerce_currency_symbol')
        ? get_woocommerce_currency_symbol() : '$',
      'user_logged_in' => is_user_logged_in(),
      'routes' => [
        'shop' => home_url('/shop'),
        'cart' => home_url('/cart'),
        'checkout' => home_url('/checkout'),
        'account' => home_url('/account'),
      ],
      'theme' => [
        'name' => wp_get_theme()->get('Name') ?: 'Phantom Theme',
        'version' => wp_get_theme()->get('Version') ?: '1.0',
      ],
    ];

    if ($this->resolved_product_id) {
      $data['product_id'] = $this->resolved_product_id;
    }
    if ($this->resolved_post_id) {
      $data['post_id'] = $this->resolved_post_id;
    }
    if ($this->category_slug) {
      $data['category'] = $this->category_slug;
    }

    return $data;
  }

  public function get_auth_nonces(): array {
    return [
      'wp_rest' => wp_create_nonce('wp_rest'),
      'phantom_api' => wp_create_nonce('phantom_api'),
      'woocommerce_cart' => wp_create_nonce('woocommerce-cart'),
    ];
  }

  public function get_customizer_css(): string {
    if (!class_exists('\Phantom_Custom_CSS')) return '';
    return \Phantom_Custom_CSS::instance()->render_style();
  }

}
