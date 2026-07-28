<?php
declare(strict_types=1);

namespace PhantomCore\Engine;

use PhantomCore\Adapters\Hero_Adapter;
use PhantomCore\Adapters\Footer_Adapter;
use PhantomCore\Components\Component_Registry;
use PhantomCore\Engine\Injectors\Product_Injector;
use PhantomCore\Engine\Injectors\Cart_Checkout_Injector;
use PhantomCore\Engine\Injectors\Account_Injector;
use PhantomCore\Engine\Injectors\Content_Injector;
use PhantomCore\Engine\Injectors\Wishlist_Injector;

defined('ABSPATH') || exit;

class WooCommerce_Injector {

  private Render_Engine $engine;
  private EventDispatcher $events;
  private Hero_Adapter $hero_adapter;
  private Product_Injector $product_injector;
  private Cart_Checkout_Injector $cart_injector;
  private Account_Injector $account_injector;
  private Content_Injector $content_injector;
  private Wishlist_Injector $wishlist_injector;

  public function __construct(Render_Engine $engine, EventDispatcher $events) {
    $this->engine = $engine;
    $this->events = $events;
    $this->hero_adapter = new Hero_Adapter();
    $this->product_injector = new Product_Injector($engine);
    $this->cart_injector = new Cart_Checkout_Injector($engine);
    $this->account_injector = new Account_Injector($engine);
    $this->content_injector = new Content_Injector($engine);
    $this->wishlist_injector = new Wishlist_Injector($engine);
  }

  public function inject(string $html, string $slug): string {
    try {
      $hero_component = Component_Registry::get_instance()->get('hero');
      if ($hero_component) {
        $hero_html = $hero_component->instance()->render($this->hero_adapter->normalize());
        $html = $this->replace_inner_by_component($html, 'hero', $hero_html);
      }
    } catch (\Throwable $e) {}

    switch (true) {
      case 'shop' === $slug:
      case strpos($slug, 'category/') === 0:
        $html = $this->product_injector->inject_shop_content($html);
        break;
      case 'product' === $slug:
      case 'product-detail' === $slug:
      case strpos($slug, 'product/') === 0:
        $html = $this->product_injector->inject_product_content($html);
        break;
      case 'cart' === $slug:
        $html = $this->cart_injector->inject_cart_content($html);
        break;
      case 'checkout' === $slug:
        $html = $this->cart_injector->inject_checkout_content($html);
        break;
      case '' === $slug:
      case 'index' === $slug:
        $html = $this->product_injector->inject_homepage_products($html);
        $html = $this->product_injector->inject_homepage_categories($html);
        break;
      case 'wishlist' === $slug:
        $html = $this->wishlist_injector->inject_wishlist_content($html);
        break;
      case 'account' === $slug:
      case 'my-account' === $slug:
        $html = $this->account_injector->inject_account_content($html);
        break;
      case 'blog' === $slug:
      case 'post' === $slug:
        $html = $this->content_injector->inject_blog_content($html);
        break;
      case strpos($slug, 'post/') === 0:
        $html = $this->content_injector->inject_post_content($html);
        break;
      case strpos($slug, 'orders') === 0:
      case 'orders' === $slug:
        $html = $this->account_injector->inject_orders_content($html);
        break;
      case strpos($slug, 'order/') === 0:
      case 'order-detail' === $slug:
        $html = $this->account_injector->inject_order_detail_content($html);
        break;
      case 'search' === $slug:
        $html = $this->content_injector->inject_search_content($html);
        break;
    }

    try {
      $footer_component = Component_Registry::get_instance()->get('footer');
      if ($footer_component) {
        $footer_html = $footer_component->instance()->render($this->get_footer_data());
        $html = $this->replace_inner_by_component($html, 'footer', $footer_html);
      }
    } catch (\Throwable $e) {}

    return $html;
  }

  private function get_footer_data(): array {
    $footer_adapter = new Footer_Adapter();
    $footer_settings = $footer_adapter->normalize();

    $widgets = '';
    $col_classes = ['col-lg-3 col-md-6', 'col-lg-3 col-md-6', 'col-lg-3 col-md-6', 'col-lg-3 col-md-6'];
    $columns = $footer_settings['columns'];
    if ($columns >= 1 && $columns <= 4) {
      $col_classes = [
        ['col-lg-12', 'col-lg-6 col-md-6', 'col-lg-4 col-md-6', 'col-lg-3 col-md-6'],
        ['', 'col-lg-6 col-md-6', 'col-lg-4 col-md-6', 'col-lg-3 col-md-6'],
        ['', '', 'col-lg-4 col-md-6', 'col-lg-3 col-md-6'],
        ['', '', '', 'col-lg-3 col-md-6'],
      ][$columns - 1];
    }
    for ($i = 1; $i <= $columns; $i++) {
      $sidebar_id = 'phantom-footer-' . $i;
      if (function_exists('dynamic_sidebar') && is_active_sidebar($sidebar_id)) {
        ob_start();
        dynamic_sidebar($sidebar_id);
        $col_content = ob_get_clean();
        if ($col_content) {
          $widgets .= '<div class="' . esc_attr($col_classes[$i - 1]) . ' footer-column footer-column-' . $i . '">' . $col_content . '</div>';
        }
      }
    }
    return [
      'widgets' => $widgets ?: '<div class="col-12 footer-widget-placeholder"><p>Add widgets to the Footer area via Appearance > Widgets.</p></div>',
      'copyright' => $footer_settings['copyright_text'] ?: '&copy; ' . date('Y') . ' All rights reserved.',
    ];
  }

  public function get_dispatch_table(): array {
    return [
      'shop'          => ['renderer' => 'product_card', 'template' => 'shop'],
      'category'      => ['renderer' => 'product_card', 'template' => 'category'],
      'product'       => ['renderer' => 'product_card', 'template' => 'product'],
      'product-detail'=> ['renderer' => 'product_card', 'template' => 'product'],
      'cart'          => ['renderer' => 'cart_item',    'template' => 'cart'],
      'checkout'      => ['renderer' => 'checkout_form', 'template' => 'checkout'],
      'account'       => ['renderer' => 'account_detail', 'template' => 'account'],
      'my-account'    => ['renderer' => 'account_detail', 'template' => 'account'],
      'orders'        => ['renderer' => 'order_card',   'template' => 'orders'],
      'order-detail'  => ['renderer' => 'order_table',  'template' => 'order-detail'],
      'blog'          => ['renderer' => 'blog_card',    'template' => 'blog'],
      'post'          => ['renderer' => 'post_card',    'template' => 'post'],
      'search'        => ['renderer' => 'search_card',  'template' => 'search'],
      'wishlist'      => ['renderer' => null,           'template' => 'wishlist'],
      'home'          => ['renderer' => null,           'template' => 'home'],
      'index'         => ['renderer' => null,           'template' => 'home'],
    ];
  }
}
