<?php
declare(strict_types=1);

namespace PhantomCore\Engine;

use PhantomCore\Adapters\Product_Adapter;
use PhantomCore\Adapters\Category_Adapter;
use PhantomCore\Adapters\Hero_Adapter;
use PhantomCore\Renderer\Product_Card;
use PhantomCore\Renderer\Category_Card;
use PhantomCore\Renderer\Hero;
use PhantomCore\Renderer\Footer;

defined('ABSPATH') || exit;

class WooCommerce_Injector {

  private Render_Engine $engine;
  private EventDispatcher $events;
  private Product_Adapter $product_adapter;
  private Category_Adapter $category_adapter;
  private Hero_Adapter $hero_adapter;
  private Product_Card $product_card;
  private Category_Card $category_card;
  private Hero $hero;
  private Footer $footer;

  public function __construct(Render_Engine $engine, EventDispatcher $events) {
    $this->engine = $engine;
    $this->events = $events;
    $this->product_adapter = new Product_Adapter();
    $this->category_adapter = new Category_Adapter();
    $this->hero_adapter = new Hero_Adapter();
    $this->product_card = new Product_Card();
    $this->category_card = new Category_Card();
    $this->hero = new Hero();
    $this->footer = new Footer();
  }

  public function inject(string $html, string $slug): string {
    // Inject hero section (replace static page-hero block)
    try {
      $hero_html = $this->hero->render($this->hero_adapter->normalize());
      $html = preg_replace(
        '/<section[^>]*class="[^"]*page-hero[^"]*"[^>]*>.*?<\/section>/s',
        $hero_html,
        $html,
        1
      );
    } catch (\Throwable $e) {
      // Fall through — keep static template hero
    }

    switch (true) {
      case 'shop' === $slug:
      case strpos($slug, 'category/') === 0:
        $html = $this->inject_shop_content($html);
        break;
      case 'product' === $slug:
      case 'product-detail' === $slug:
      case strpos($slug, 'product/') === 0:
        $html = $this->inject_product_content($html);
        break;
      case 'cart' === $slug:
        $html = $this->inject_cart_content($html);
        break;
      case 'checkout' === $slug:
        $html = $this->inject_checkout_content($html);
        break;
      case '' === $slug:
      case 'index' === $slug:
        $html = $this->inject_homepage_products($html);
        $html = $this->inject_homepage_categories($html);
        break;
      case 'wishlist' === $slug:
        $html = $this->inject_wishlist_content($html);
        break;
      case 'account' === $slug:
      case 'my-account' === $slug:
        $html = $this->inject_account_content($html);
        break;
    }

    // Inject footer section (replace static footer block)
    try {
      $footer_html = $this->footer->render($this->get_footer_data());
      $html = preg_replace(
        '/<footer[^>]*class="[^"]*footer[^"]*"[^>]*>.*?<\/footer>/s',
        $footer_html,
        $html,
        1
      );
    } catch (\Throwable $e) {
      // Fall through — keep static template footer
    }

    return $html;
  }

  private function get_footer_data(): array {
    $widgets = '';
    if (function_exists('dynamic_sidebar') && is_active_sidebar('phantom-footer-widgets-1')) {
      ob_start();
      dynamic_sidebar('phantom-footer-widgets-1');
      $widgets = ob_get_clean();
    }
    return [
      'widgets' => $widgets ?: '<div class="footer-widget-placeholder"><p>Add widgets to the Footer area.</p></div>',
      'copyright' => get_option('phantom_footer_copyright', '&copy; ' . date('Y') . ' All rights reserved.'),
    ];
  }

  private function inject_shop_content(string $html): string {
    // Filter buttons from categories
    $categories = get_terms([
      'taxonomy' => 'product_cat',
      'hide_empty' => true,
      'parent' => 0,
    ]);

    $filter_buttons = '<button data-category="" class="filter-btn active">All</button>';
    if (!empty($categories) && !is_wp_error($categories)) {
      foreach ($categories as $cat) {
        $active = ($this->engine->get_category_slug() && $this->engine->get_category_slug() === $cat->slug) ? ' active' : '';
        $filter_buttons .= sprintf(
          '<button data-category="%s" class="filter-btn%s" data-href="%s">%s</button>',
          esc_attr($cat->slug),
          $active,
          esc_url(home_url('/category/' . $cat->slug . '/')),
          esc_html($cat->name)
        );
      }
    }

    $html = preg_replace(
      '/<div class="filter-buttons">.*?<\/div>/s',
      '<div class="filter-buttons">' . $filter_buttons . '</div>',
      $html,
      1
    );

    // Products
    $paged = absint($_GET['page'] ?? 1);
    $args = ['limit' => 12, 'page' => $paged, 'status' => 'publish'];
    if ($this->engine->get_category_slug()) {
      $args['category'] = [$this->engine->get_category_slug()];
    }

    $products = wc_get_products($args);

    if (empty($products)) {
      $empty = '<div class="shop-grid-empty"><p>No products found in this category.</p><a href="' . esc_url(home_url('/shop')) . '" class="btn btn-outline">View All Products</a></div>';
      return preg_replace(
        '/<div class="shop-grid[^"]*"[^>]*>.*?<\/div>\s*<\/section>/s',
        '<div class="shop-grid" data-reveal-group>' . $empty . '</div></section>',
        $html,
        1
      );
    }

    $product_cards = $this->product_card->render_collection(
      $this->product_adapter->normalize_collection($products)
    );

    $html = preg_replace(
      '/<div class="shop-grid[^"]*"[^>]*>.*?<\/div>\s*<\/section>/s',
      '<div class="shop-grid" data-reveal-group>' . $product_cards . '</div></section>',
      $html,
      1
    );

    // Pagination
    $total_ids = wc_get_products(array_merge($args, ['limit' => -1, 'return' => 'ids']));
    $total_pages = ceil(count($total_ids) / 12);

    if ($total_pages > 1) {
      $pagination = '<div class="shop-pagination">';
      $pagination .= sprintf(
        '<a href="%s" class="pagination-btn pagination-prev%s" aria-label="Previous page"><i class="fas fa-chevron-left"></i></a>',
        $paged > 1 ? esc_url(add_query_arg('page', $paged - 1)) : '#',
        $paged <= 1 ? ' disabled' : ''
      );
      $pagination .= '<div class="pagination-pages">';
      for ($i = 1; $i <= $total_pages; $i++) {
        $pagination .= sprintf(
          '<a href="%s" class="pagination-page%s">%d</a>',
          esc_url(add_query_arg('page', $i)),
          $i === $paged ? ' active' : '',
          $i
        );
      }
      $pagination .= '</div>';
      $pagination .= sprintf(
        '<a href="%s" class="pagination-btn pagination-next%s" aria-label="Next page"><i class="fas fa-chevron-right"></i></a>',
        $paged < $total_pages ? esc_url(add_query_arg('page', $paged + 1)) : '#',
        $paged >= $total_pages ? ' disabled' : ''
      );
      $pagination .= '</div>';

      $html = preg_replace(
        '/<div class="shop-pagination">.*?<\/div>\s*<\/section>/s',
        $pagination . '</section>',
        $html,
        1
      );
    }

    return $html;
  }

  private function inject_product_content(string $html): string {
    $product_id = $this->engine->get_resolved_product_id()
      ?? (isset($_GET['product_id']) ? (int) $_GET['product_id'] : 0);

    if (!$product_id || !function_exists('wc_get_product')) {
      return str_replace(
        '[product_content]',
        '<p class="text-error">Product not found.</p>',
        $html
      );
    }

    $product = wc_get_product($product_id);
    if (!$product) {
      return str_replace(
        '[product_content]',
        '<p class="text-error">Product not found.</p>',
        $html
      );
    }

    $data = $this->product_adapter->normalize($product);

    // Gallery
    $gallery_html = '';
    if (!empty($data['gallery'])) {
      $gallery_html = '<div class="product-gallery swiper" id="productGallery">';
      $gallery_html .= '<div class="swiper-wrapper">';
      foreach ($data['gallery'] as $img) {
        $gallery_html .= '<div class="swiper-slide"><img src="' . esc_url($img) . '" alt="' . esc_attr($data['name']) . '" loading="lazy"></div>';
      }
      $gallery_html .= '</div><div class="swiper-pagination"></div></div>';
    }

    // Price
    $price_html = esc_html($data['price']);
    if ($data['on_sale']) {
      $price_html = '<span class="price-sale">' . esc_html($data['sale_price']) . '</span> <span class="price-original">' . esc_html($data['regular_price']) . '</span>';
    }

    // Add to cart
    $atc_html = $this->render_add_to_cart($product);

    // Stock
    $stock_html = $data['in_stock']
      ? '<span class="stock in-stock">In Stock</span>'
      : '<span class="stock out-of-stock">Out of Stock</span>';

    // Rating
    $rating_html = '';
    if ($data['rating'] > 0) {
      $full = floor($data['rating']);
      $stars = '';
      for ($i = 0; $i < 5; $i++) {
        $stars .= $i < $full ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
      }
      $rating_html = '<div class="product-rating">' . $stars . ' <span>(' . (int) $data['reviews_count'] . ' reviews)</span></div>';
    }

    $search = [
      '[product_gallery]',
      '[product_title]',
      '[product_price]',
      '[product_rating]',
      '[product_stock]',
      '[product_sku]',
      '[product_short_description]',
      '[product_description]',
      '[product_add_to_cart]',
      '[product_categories]',
    ];

    $replace = [
      $gallery_html ?: '<img src="' . esc_url($data['image']) . '" alt="' . esc_attr($data['name']) . '">',
      '<h1 class="product-title">' . esc_html($data['name']) . '</h1>',
      '<div class="product-price-wrap">' . $price_html . '</div>',
      $rating_html,
      $stock_html,
      $data['sku'] ? '<span class="product-sku">SKU: ' . esc_html($data['sku']) . '</span>' : '',
      '<div class="product-short-desc">' . wp_kses_post($data['short_description']) . '</div>',
      '<div class="product-description">' . wp_kses_post($data['description']) . '</div>',
      $atc_html,
      $this->render_product_categories($data['categories']),
    ];

    return str_replace($search, $replace, $html);
  }

  private function render_add_to_cart($product): string {
    if (!$product->is_in_stock()) {
      return '<p class="stock out-of-stock">Out of Stock</p>';
    }

    if ($product->is_type('variable')) {
      $attrs = [];
      foreach ($product->get_variation_attributes() as $name => $options) {
        $tax = str_replace('attribute_', '', $name);
        $label = wc_attribute_label($tax, $product);
        $attrs[] = $label;
      }
      $atc = '<form class="variations-form phantom-add-to-cart-form" data-product_id="' . $product->get_id() . '">';
      foreach ($product->get_variation_attributes() as $name => $options) {
        $tax = str_replace('attribute_', '', $name);
        $label = wc_attribute_label($tax, $product);
        $atc .= '<div class="variation-select">';
        $atc .= '<label for="' . esc_attr($tax) . '">' . esc_html($label) . ':</label>';
        $atc .= '<select name="' . esc_attr($name) . '" id="' . esc_attr($tax) . '" class="form-select">';
        $atc .= '<option value="">Choose ' . esc_html($label) . '</option>';
        foreach ($options as $opt) {
          $atc .= '<option value="' . esc_attr($opt) . '">' . esc_html(ucfirst(str_replace('-', ' ', $opt))) . '</option>';
        }
        $atc .= '</select></div>';
      }
      $atc .= '<button type="submit" class="btn btn-primary add-to-cart-btn" data-product_id="' . $product->get_id() . '">Add to Cart</button>';
      $atc .= '</form>';
      return $atc;
    }

    if ($product->is_type('simple')) {
      return '<form class="phantom-add-to-cart-form" data-product_id="' . $product->get_id() . '">
        <div class="quantity-wrap">
          <button type="button" class="qty-btn qty-minus" aria-label="Decrease quantity">-</button>
          <input type="number" class="qty-input form-control" value="1" min="1" max="999" step="1">
          <button type="button" class="qty-btn qty-plus" aria-label="Increase quantity">+</button>
        </div>
        <button type="submit" class="btn btn-primary add-to-cart-btn" data-product_id="' . $product->get_id() . '">Add to Cart</button>
      </form>';
    }

    return '<a href="' . esc_url($product->get_permalink()) . '" class="btn btn-primary">View Product</a>';
  }

  private function render_product_categories(array $categories): string {
    if (empty($categories)) return '';
    $links = array_map(function($c) {
      return '<a href="' . esc_url($c['url']) . '" class="product-cat-link">' . esc_html($c['name']) . '</a>';
    }, $categories);
    return '<div class="product-categories">' . implode(', ', $links) . '</div>';
  }

  private function inject_cart_content(string $html): string {
    return str_replace(
      '[woocommerce_cart]',
      do_shortcode('[woocommerce_cart]'),
      $html
    );
  }

  private function inject_checkout_content(string $html): string {
    return str_replace(
      '[woocommerce_checkout]',
      do_shortcode('[woocommerce_checkout]'),
      $html
    );
  }

  private function inject_account_content(string $html): string {
    return str_replace(
      '[woocommerce_my_account]',
      do_shortcode('[woocommerce_my_account]'),
      $html
    );
  }

  private function inject_homepage_products(string $html): string {
    $products = wc_get_products([
      'limit' => 8,
      'status' => 'publish',
      'orderby' => 'date',
      'order' => 'DESC',
    ]);

    if (empty($products)) return $html;

    $cards = $this->product_card->render_collection(
      $this->product_adapter->normalize_collection($products)
    );

    return preg_replace(
      '/<div class="featured-products-grid"[^>]*>.*?<\/div>\s*<\/section>/s',
      '<div class="featured-products-grid" data-reveal-group>' . $cards . '</div></section>',
      $html,
      1
    );
  }

  private function inject_homepage_categories(string $html): string {
    $categories = get_terms([
      'taxonomy' => 'product_cat',
      'hide_empty' => true,
      'parent' => 0,
      'number' => 6,
    ]);

    if (empty($categories) || is_wp_error($categories)) return $html;

    $cards = $this->category_card->render_collection(
      $this->category_adapter->normalize_collection($categories)
    );

    return preg_replace(
      '/<div class="category-grid">.*?<\/div>\s*<\/section>/s',
      '<div class="category-grid" data-reveal-group>' . $cards . '</div></section>',
      $html,
      1
    );
  }

  private function inject_wishlist_content(string $html): string {
    return str_replace(
      '[wishlist_content]',
      '<div class="wishlist-page"><p class="text-center">Your wishlist is currently empty.</p></div>',
      $html
    );
  }
}
