<?php
declare(strict_types=1);

namespace PhantomCore\Engine;

use PhantomCore\Adapters\Product_Adapter;
use PhantomCore\Adapters\Category_Adapter;
use PhantomCore\Adapters\Hero_Adapter;
use PhantomCore\Components\Component_Registry;
use PhantomCore\Feature\Feature_Registry;
use PhantomCore\ViewModels\Product_ViewModel;

defined('ABSPATH') || exit;

// Ensure ViewModel is loaded (autoloader edge case)
require_once PHANTOM_CORE_PATH . 'includes/ViewModels/product-view-model.php';

class WooCommerce_Injector {

  private Render_Engine $engine;
  private EventDispatcher $events;
  private Product_Adapter $product_adapter;
  private Category_Adapter $category_adapter;
  private Hero_Adapter $hero_adapter;

  public function __construct(Render_Engine $engine, EventDispatcher $events) {
    $this->engine = $engine;
    $this->events = $events;
    $this->product_adapter = new Product_Adapter();
    $this->category_adapter = new Category_Adapter();
    $this->hero_adapter = new Hero_Adapter();
  }

  public function inject(string $html, string $slug): string {
    // Inject hero section (replace static page-hero block)
    try {
      $hero_component = Component_Registry::get_instance()->get('hero');
      if ($hero_component) {
        $hero_html = $hero_component->instance()->render($this->hero_adapter->normalize());
        $html = preg_replace(
          '/<section[^>]*class=\"[^\"]*page-hero[^\"]*\"[^>]*>.*?<\/section>/s',
          $hero_html,
          $html,
          1
        );
      }
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
      $footer_component = Component_Registry::get_instance()->get('footer');
      if ($footer_component) {
        $footer_html = $footer_component->instance()->render($this->get_footer_data());
        $html = preg_replace(
          '/<footer[^>]*class=\"[^\"]*footer[^\"]*\"[^>]*>.*?<\/footer>/s',
          $footer_html,
          $html,
          1
        );
      }
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
        '/<div class="shop-grid[^\"]*"[^>]*>.*?<\/div>\s*<\/section>/s',
        '<div class="shop-grid" data-reveal-group>' . $empty . '</div></section>',
        $html,
        1
      );
    }

    $product_card = $this->get_product_card_renderer();
    if ($product_card) {
      $normalized = $this->product_adapter->normalize_collection($products);
      $viewmodels = array_map(
        function (array $data) { return Product_ViewModel::from_adapter_output($data)->to_array(); },
        $normalized
      );
      $product_cards = $product_card->render_collection($viewmodels);
    } else {
      $product_cards = $this->render_fallback_products($products);
    }

    $html = preg_replace(
      '/<div class="shop-grid[^\"]*"[^>]*>.*?<\/div>\s*<\/section>/s',
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

      $html = preg_replace_callback(
        '/<div class="shop-pagination">.*?<\/div>\s*/s',
        function($m) use ($pagination) {
          $depth = 0;
          $len = strlen($m[0]);
          for ($i = 0; $i < $len; $i++) {
            if (substr($m[0], $i, 4) === '<div') { $depth++; $i += 3; }
            elseif (substr($m[0], $i, 6) === '</div>') {
              $depth--;
              if ($depth === 0) {
                $matched = substr($m[0], 0, $i + 6);
                $after = substr($m[0], $i + 6);
                return $pagination . $after;
              }
              $i += 5;
            }
          }
          return $pagination . "\n";
        },
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

    $data_raw = $this->product_adapter->normalize($product);
    $vm = Product_ViewModel::from_adapter_output($data_raw);
    $data = $vm->to_array();

    // Gallery
    $gallery_html = $vm->gallery_html();

    // Price (use ViewModel's formatted price)
    $price_html = $vm->formatted_price();

    // Add to cart
    $atc_html = $this->render_add_to_cart($product);

    // Stock
    $stock_html = $data['in_stock']
      ? '<span class="stock in-stock">In Stock</span>'
      : '<span class="stock out-of-stock">Out of Stock</span>';

    // Rating (use ViewModel's rating stars)
    $rating_html = $vm->rating_stars();

    $gallery_thumbs = $vm->gallery_thumbnails_html();

    $search = [
      '[product_gallery]',
      '[product_gallery_thumbs]',
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
      $gallery_html ? $gallery_html : '<img src="' . esc_url($data['image']) . '" alt="' . esc_attr($data['name']) . '">',
      $gallery_thumbs ? $gallery_thumbs : '',
      esc_html($data['name']),
      $price_html,
      $rating_html,
      $stock_html,
      $data['sku'] ? 'SKU: ' . esc_html($data['sku']) : '',
      '<p class="pd-description-txt">' . wp_kses_post($data['short_description']) . '</p>',
      '<div class="pd-description-full">' . wp_kses_post($data['description']) . '</div>',
      $atc_html,
      $this->render_product_categories($data['categories']),
    ];

    return str_replace($search, $replace, $html);
  }

  private function render_add_to_cart($product): string {
    if (!$product->is_in_stock()) {
      return '<p class="pd-stock-out">Out of Stock</p>';
    }

    if ($product->is_type('variable')) {
      $atc = '<form class="pd-variations-form" data-product_id="' . $product->get_id() . '">';
      foreach ($product->get_variation_attributes() as $name => $options) {
        $tax = str_replace('attribute_', '', $name);
        $label = wc_attribute_label($tax, $product);
        $atc .= '<div class="pd-option-group">';
        $atc .= '<div class="pd-option-header">';
        $atc .= '<label class="pd-option-label">' . esc_html($label) . '</label>';
        $atc .= '</div>';
        $atc .= '<select name="' . esc_attr($name) . '" class="pd-variation-select">';
        $atc .= '<option value="">Choose ' . esc_html($label) . '</option>';
        foreach ($options as $opt) {
          $atc .= '<option value="' . esc_attr($opt) . '">' . esc_html(ucfirst(str_replace('-', ' ', $opt))) . '</option>';
        }
        $atc .= '</select></div>';
      }
      $atc .= '<div class="pd-actions">';
      $atc .= '<button type="submit" class="btn btn-primary pd-add-to-cart" data-magnetic="0.12" data-product_id="' . $product->get_id() . '"><i class="fas fa-shopping-bag"></i> Add to Cart</button>';
      $atc .= '</div>';
      $atc .= '</form>';
      return $atc;
    }

    if ($product->is_type('simple')) {
      return '<form class="pd-simple-form" data-product_id="' . $product->get_id() . '">
        <div class="pd-option-group">
          <label class="pd-option-label">Quantity</label>
          <div class="pd-qty">
            <button type="button" class="pd-qty-btn pd-qty-minus" aria-label="Decrease quantity">&minus;</button>
            <span class="pd-qty-value">1</span>
            <button type="button" class="pd-qty-btn pd-qty-plus" aria-label="Increase quantity">+</button>
          </div>
        </div>
        <div class="pd-actions">
          <button type="submit" class="btn btn-primary pd-add-to-cart" data-magnetic="0.12" data-product_id="' . $product->get_id() . '"><i class="fas fa-shopping-bag"></i> Add to Cart</button>
          <button type="button" class="pd-wishlist-btn" aria-label="Add to wishlist"><i class="far fa-heart"></i></button>
        </div>
      </form>';
    }

    return '<a href="' . esc_url($product->get_permalink()) . '" class="btn btn-primary pd-view-product">View Product</a>';
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

    $product_card = $this->get_product_card_renderer();
    if ($product_card) {
      $cards = $product_card->render_collection(
        $this->prepare_product_data($products)
      );
    } else {
      $cards = $this->render_fallback_products($products);
    }

    return preg_replace(
      '/<div class="products-grid"[^>]*>.*?<\/div>\s*<\/section>/s',
      '<div class="products-grid" data-reveal-group>' . $cards . '</div></section>',
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

    $category_card = $this->get_category_card_renderer();
    if ($category_card) {
      $cards = $category_card->render_collection(
        $this->category_adapter->normalize_collection($categories)
      );
    } else {
      $cards = $this->render_fallback_categories($categories);
    }

    return preg_replace(
      '/<div class="category-grid"[^>]*>.*?<\/div>\s*<\/section>/s',
      '<div class="category-grid" data-reveal-group>' . $cards . '</div></section>',
      $html,
      1
    );
  }

  private function inject_wishlist_content(string $html): string {
    // Feature flag: only show wishlist if enabled
    $wishlist_enabled = Feature_Registry::get_instance()->enabled('wishlist');
    if (!$wishlist_enabled) {
      return str_replace(
        '[wishlist_content]',
        '<div class="wishlist-page"><p class="text-center">Wishlist feature is currently disabled.</p></div>',
        $html
      );
    }
    return str_replace(
      '[wishlist_content]',
      '<div class="wishlist-page"><p class="text-center">Your wishlist is currently empty.</p><a href="' . esc_url(home_url('/shop')) . '" class="btn btn-primary">Browse Products</a></div>',
      $html
    );
  }

  private function get_product_card_renderer(): ?object {
    $component = Component_Registry::get_instance()->get('product_card');
    return $component ? $component->instance() : null;
  }

  private function get_category_card_renderer(): ?object {
    $component = Component_Registry::get_instance()->get('category_card');
    return $component ? $component->instance() : null;
  }

  private function prepare_product_data(array $products): array {
    $normalized = $this->product_adapter->normalize_collection($products);
    return array_map(
      function (array $data) { return Product_ViewModel::from_adapter_output($data)->to_array(); },
      $normalized
    );
  }

  private function render_fallback_products(array $products): string {
    $html = '';
    foreach ($products as $product) {
      $data = $this->product_adapter->normalize($product);
      $html .= '<div class="product-card">';
      $html .= '<a href="' . esc_url($data['url']) . '"><img src="' . esc_url($data['image']) . '" alt="' . esc_attr($data['name']) . '" loading="lazy"></a>';
      $html .= '<h3>' . esc_html($data['name']) . '</h3>';
      $html .= '<span class="product-price">' . wp_kses_post($data['price']) . '</span>';
      $html .= '</div>';
    }
    return $html;
  }

  private function render_fallback_categories(array $categories): string {
    $html = '';
    foreach ($categories as $cat) {
      $image_id = get_term_meta($cat->term_id, 'thumbnail_id', true);
      $image = $image_id ? wp_get_attachment_url($image_id) : '';
      $html .= '<a href="' . esc_url(get_term_link($cat)) . '" class="category-card">';
      $html .= '<h3>' . esc_html($cat->name) . '</h3>';
      $html .= '<span>' . esc_html((string) $cat->count) . ' items</span>';
      $html .= '</a>';
    }
    return $html;
  }
}
