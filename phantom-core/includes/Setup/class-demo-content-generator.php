<?php
declare(strict_types=1);

namespace PhantomCore\Setup;

defined('ABSPATH') || exit;

class Demo_Content_Generator {

  public function generate_all(): array {
    $results = [];
    $results['pages'] = $this->create_pages();
    $results['products'] = $this->create_products();
    $results['posts'] = $this->create_posts();
    $results['menus'] = $this->create_menus();
    $results['widgets'] = $this->setup_widgets();
    $results['options'] = $this->set_default_options();
    $this->flush();
    return $results;
  }

  public function create_pages(): array {
    $pages = [
      'home'     => ['title' => 'Home', 'template' => 'default'],
      'shop'     => ['title' => 'Shop', 'template' => 'default'],
      'cart'     => ['title' => 'Cart', 'template' => 'default'],
      'checkout' => ['title' => 'Checkout', 'template' => 'default'],
      'about'    => ['title' => 'About', 'template' => 'default'],
      'blog'     => ['title' => 'Blog', 'template' => 'default'],
      'contact'  => ['title' => 'Contact', 'template' => 'default'],
      'faq'      => ['title' => 'FAQ', 'template' => 'default'],
    ];
    $created = [];
    foreach ($pages as $slug => $config) {
      if (get_page_by_path($slug)) continue;
      $id = wp_insert_post([
        'post_title'   => $config['title'],
        'post_name'    => $slug,
        'post_type'    => 'page',
        'post_status'  => 'publish',
        'post_content' => $this->get_page_content($slug),
      ]);
      if ($id) {
        if ($config['template'] !== 'default') {
          update_post_meta($id, '_wp_page_template', $config['template']);
        }
        $created[] = $slug;
      }
    }
    update_option('phantom_demo_pages_created', true);
    return $created;
  }

  public function create_products(int $count = 12): array {
    if (!class_exists('WooCommerce')) return [];
    $created = [];
    $categories = ['Running', 'Training', 'Lifestyle', 'Accessories'];
    $cat_ids = [];
    foreach ($categories as $cat) {
      $term = wp_insert_term($cat, 'product_cat');
      if (!is_wp_error($term)) $cat_ids[$cat] = $term['term_id'];
    }
    for ($i = 1; $i <= $count; $i++) {
      $cat = $categories[array_rand($categories)];
      $price = round(mt_rand(2900, 18900) / 100, 2);
      $sale = $i % 3 === 0 ? round($price * 0.8, 2) : '';
      $id = wp_insert_post([
        'post_title'   => $this->get_product_name($i),
        'post_type'    => 'product',
        'post_status'  => 'publish',
        'post_content' => "Premium quality product designed for peak performance. Features advanced materials and ergonomic design.",
        'meta_input'   => [
          '_price'         => $price,
          '_regular_price' => $price,
          '_sale_price'    => $sale,
          '_stock_status'  => 'instock',
          '_visibility'    => 'visible',
        ],
      ]);
      if ($id) {
        wp_set_object_terms($id, $cat, 'product_cat');
        if (isset($cat_ids[$cat])) {
          wp_set_post_terms($id, [$cat_ids[$cat]], 'product_cat');
        }
        wp_set_object_terms($id, 'simple', 'product_type');
        update_post_meta($id, '_thumbnail_id', $this->get_placeholder_image($i));
        $created[] = $id;
      }
    }
    update_option('phantom_demo_products_created', $count);
    return $created;
  }

  public function create_posts(int $count = 6): array {
    $created = [];
    $categories = ['News', 'Tutorials', 'Reviews', 'Updates'];
    foreach ($categories as $cat) {
      if (!term_exists($cat, 'category')) {
        wp_insert_term($cat, 'category');
      }
    }
    for ($i = 1; $i <= $count; $i++) {
      $cat = $categories[array_rand($categories)];
      $id = wp_insert_post([
        'post_title'   => $this->get_post_title($i),
        'post_type'    => 'post',
        'post_status'  => 'publish',
        'post_content' => $this->get_lorem_content(),
        'post_excerpt' => $this->get_lorem_excerpt(),
        'meta_input'   => ['_thumbnail_id' => $this->get_placeholder_image($i + 20)],
      ]);
      if ($id) {
        wp_set_post_categories($id, [get_cat_ID($cat)]);
        wp_set_post_tags($id, $this->get_random_tags());
        $created[] = $id;
      }
    }
    update_option('phantom_demo_posts_created', $count);
    return $created;
  }

  public function create_menus(): array {
    if (!function_exists('wp_create_nav_menu')) return [];
    $menu_name = 'Primary Menu';
    if (wp_get_nav_menu_object($menu_name)) return ['exists'];
    $menu_id = wp_create_nav_menu($menu_name);
    if (is_wp_error($menu_id)) return [];
    $items = [
      'Home'     => '/',
      'Shop'     => '/shop',
      'About'    => '/about',
      'Blog'     => '/blog',
      'Contact'  => '/contact',
    ];
    foreach ($items as $title => $url) {
      wp_update_nav_menu_item($menu_id, 0, [
        'menu-item-title'  => $title,
        'menu-item-url'    => $url,
        'menu-item-status' => 'publish',
      ]);
    }
    $locations = get_theme_mod('nav_menu_locations');
    $locations['primary'] = $menu_id;
    set_theme_mod('nav_menu_locations', $locations);
    update_option('phantom_demo_menus_created', $menu_id);
    return ['created' => $menu_id];
  }

  public function setup_widgets(): array {
    $widgets = [
      'sidebar-1' => [
        ['WP_Widget_Search', ['title' => 'Search']],
        ['WP_Widget_Recent_Posts', ['title' => 'Recent Posts', 'number' => 5]],
        ['WP_Widget_Categories', ['title' => 'Categories', 'count' => 1]],
      ],
    ];
    $results = [];
    foreach ($widgets as $area => $items) {
      $results[$area] = count($items);
    }
    update_option('phantom_demo_widgets_setup', true);
    return $results;
  }

  public function set_default_options(): array {
    $options = [
      'blogname'               => 'Phantom Store',
      'blogdescription'        => 'Premium Products for Modern Living',
      'posts_per_page'         => 6,
      'woocommerce_shop_page_display' => 'both',
      'woocommerce_cart_redirect_after_add' => 'yes',
      'phantom_template_pack'  => 'dark',
      'site_icon'              => $this->get_placeholder_image(0),
    ];
    foreach ($options as $key => $value) {
      update_option($key, $value);
    }
    return $options;
  }

  public function clear_all(): void {
    foreach (['phantom_demo_pages_created', 'phantom_demo_products_created', 'phantom_demo_posts_created', 'phantom_demo_menus_created', 'phantom_demo_widgets_setup'] as $opt) {
      delete_option($opt);
    }
    delete_option('phantom_template_pack');
  }

  private function flush(): void {
    if (function_exists('flush_rewrite_rules')) flush_rewrite_rules();
    if (function_exists('wp_cache_flush')) wp_cache_flush();
  }

  private function get_page_content(string $slug): string {
    $content = [
      'home'    => '<!-- wp:paragraph --><p>Welcome to Phantom Store. Discover our premium collection of products designed for modern living.</p><!-- /wp:paragraph -->',
      'about'   => '<!-- wp:paragraph --><p>We are passionate about delivering exceptional quality products that enhance your lifestyle.</p><!-- /wp:paragraph -->',
      'contact' => '<!-- wp:shortcode -->[contact-form-7 id="demo" title="Contact form"]<!-- /wp:shortcode -->',
      'faq'     => '<!-- wp:heading --><h2>Frequently Asked Questions</h2><!-- /wp:heading -->',
    ];
    return $content[$slug] ?? '<!-- wp:paragraph --><p></p><!-- /wp:paragraph -->';
  }

  private function get_product_name(int $i): string {
    $names = ['Quantum Runner', 'Apex Trainer', 'Nova Sneaker', 'Vortex Sandal', 'Pulse Walker', 'Drift Boot', 'Echo Slide', 'Zen Loaf', 'Flux Runner', 'Core Trainer', 'Blaze Shoe', 'Ember Boot'];
    return $names[($i - 1) % count($names)] . ' ' . chr(64 + $i);
  }

  private function get_post_title(int $i): string {
    $titles = ['Getting Started with Phantom Core', 'The Future of E-Commerce', 'Design Trends for 2026', 'Performance Optimization Guide', 'Building with Blocks', 'Community Spotlight'];
    return $titles[($i - 1) % count($titles)];
  }

  private function get_lorem_content(): string {
    return "<!-- wp:paragraph --><p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p><!-- /wp:paragraph -->";
  }

  private function get_lorem_excerpt(): string {
    return "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore.";
  }

  private function get_random_tags(): array {
    $all = ['featured', 'new', 'popular', 'trending', 'exclusive'];
    $count = mt_rand(1, 3);
    shuffle($all);
    return array_slice($all, 0, $count);
  }

  private function get_placeholder_image(int $seed): int {
    $existing = get_posts(['post_type' => 'attachment', 'posts_per_page' => 1, 'post_status' => 'any', 'fields' => 'ids']);
    return $existing[0] ?? 0;
  }
}
