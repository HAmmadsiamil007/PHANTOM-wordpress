<?php
declare(strict_types=1);

namespace PhantomCore\Engine;

defined('ABSPATH') || exit;

class SEO_Engine {

  private int $resolved_product_id = 0;
  private int $resolved_post_id = 0;

  public function with_product_id(int $id): self {
    $this->resolved_product_id = $id;
    return $this;
  }

  public function with_post_id(int $id): self {
    $this->resolved_post_id = $id;
    return $this;
  }

  public function inject(string $html, string $slug): string {
    $site_name = get_bloginfo('name');
    $site_desc = get_bloginfo('description');
    $home_url  = home_url('/');
    $current_url = home_url(add_query_arg([]));

    $title = $this->build_title($slug, $site_name, $site_desc);
    $site_desc = $this->override_description($slug, $site_desc);
    $image_url = $this->get_social_image($slug);

    $title_tag = sprintf('<title>%s</title>', esc_html($title));
    $html = preg_replace('/<title>[^<]*<\/title>/i', $title_tag, $html, 1);

    $meta = $this->build_meta_tags($title, $site_desc, $current_url, $image_url, $slug);
    $meta .= $this->build_preload_links($slug);
    $meta .= $this->build_json_ld($slug, $site_name, $home_url, $current_url);
    $base_tag = sprintf('<base href="%s" />', esc_url($home_url));

    $html = str_replace('<head>', "<head>\n" . $base_tag . "\n" . $meta, $html);
    return $html;
  }

  private function build_title(string $slug, string $site_name, string $site_desc): string {
    $titles = [
      ''               => $site_name . ' – ' . $site_desc,
      'about'          => 'About – ' . $site_name,
      'blog'           => 'Journal – ' . $site_name,
      'cart'           => 'Your Cart – ' . $site_name,
      'checkout'       => 'Checkout – ' . $site_name,
      'contact'        => 'Contact – ' . $site_name,
      'coming-soon'    => 'Coming Soon – ' . $site_name,
      'cookie-policy'  => 'Cookie Policy – ' . $site_name,
      'faq'            => 'FAQ – ' . $site_name,
      'join-now'       => 'Join the Void – ' . $site_name,
      'login'          => 'Sign In – ' . $site_name,
      'my-account'     => 'My Account – ' . $site_name,
      'account'        => 'My Account – ' . $site_name,
      'post'           => '{post_title} – ' . $site_name,
      'privacy-policy' => 'Privacy Policy – ' . $site_name,
      'product'        => '{product_name} – ' . $site_name,
      'product-detail' => '{product_name} – ' . $site_name,
      'register'       => 'Join the Void – ' . $site_name,
      'shop'           => 'Collection – ' . $site_name,
      'single-blog'    => '{post_title} – ' . $site_name,
      'team'           => 'Our Team – ' . $site_name,
      'term-of-use'    => 'Terms of Use – ' . $site_name,
      'testimonials'   => 'Testimonials – ' . $site_name,
      'thank-you'      => 'Thank You – ' . $site_name,
      'wishlist'       => 'Wishlist – ' . $site_name,
    ];

    $title = $titles[$slug] ?? $site_name;

    if (strpos($title, '{product_name}') !== false && $this->resolved_product_id) {
      $product = wc_get_product($this->resolved_product_id);
      if ($product) {
        $title = str_replace('{product_name}', $product->get_name(), $title);
      }
    }

    if (strpos($title, '{post_title}') !== false && $this->resolved_post_id) {
      $post = get_post($this->resolved_post_id);
      if ($post) {
        $title = str_replace('{post_title}', $post->post_title, $title);
      }
    }

    return $title;
  }

  private function override_description(string $slug, string $default): string {
    if (in_array($slug, ['blog', 'post', 'single-blog'], true) && $this->resolved_post_id) {
      $post = get_post($this->resolved_post_id);
      if ($post) {
        $excerpt = $post->post_excerpt ?: wp_trim_words($post->post_content, 30, '...');
        if ($excerpt) return $excerpt;
      }
    }
    return $default;
  }

  private function get_social_image(string $slug): string {
    $default = PHANTOM_CORE_URL . 'frontend/assets/images/logo.png';
    if (in_array($slug, ['post', 'single-blog'], true) && $this->resolved_post_id) {
      $thumb_id = get_post_thumbnail_id($this->resolved_post_id);
      if ($thumb_id) {
        $url = wp_get_attachment_url($thumb_id);
        if ($url) return $url;
      }
    }
    return $default;
  }

  private function build_meta_tags(string $title, string $desc, string $url, string $image, string $slug): string {
    $meta = '';
    $meta .= sprintf('<meta name="description" content="%s" />', esc_attr($desc));
    $meta .= sprintf('<link rel="canonical" href="%s" />', esc_url($url));
    $meta .= sprintf('<meta property="og:title" content="%s" />', esc_attr($title));
    $meta .= sprintf('<meta property="og:description" content="%s" />', esc_attr($desc));
    $meta .= sprintf('<meta property="og:url" content="%s" />', esc_url($url));
    $meta .= sprintf('<meta property="og:image" content="%s" />', esc_url($image));
    $meta .= '<meta property="og:type" content="website" />';
    $meta .= sprintf('<meta property="og:site_name" content="%s" />', esc_attr(get_bloginfo('name')));
    $meta .= '<meta name="twitter:card" content="summary_large_image" />';
    $meta .= sprintf('<meta name="twitter:title" content="%s" />', esc_attr($title));
    $meta .= sprintf('<meta name="twitter:description" content="%s" />', esc_attr($desc));

    if (in_array($slug, ['post', 'single-blog'], true) && $this->resolved_post_id) {
      $post = get_post($this->resolved_post_id);
      if ($post) {
        $meta .= '<meta property="og:type" content="article" />';
        $meta .= sprintf('<meta property="article:published_time" content="%s" />', esc_attr($post->post_date));
        $meta .= sprintf('<meta property="article:modified_time" content="%s" />', esc_attr($post->post_modified));
        $author = get_the_author_meta('display_name', $post->post_author);
        if ($author) {
          $meta .= sprintf('<meta property="article:author" content="%s" />', esc_attr($author));
        }
        foreach (wp_get_post_categories($this->resolved_post_id, ['fields' => 'names']) as $cat) {
          $meta .= sprintf('<meta property="article:section" content="%s" />', esc_attr($cat));
        }
        foreach (wp_get_post_tags($this->resolved_post_id, ['fields' => 'names']) as $tag) {
          $meta .= sprintf('<meta property="article:tag" content="%s" />', esc_attr($tag));
        }
      }
    }

    $locale = get_locale();
    $meta .= sprintf('<link rel="alternate" href="%s" hreflang="%s" />', esc_url($url), esc_attr($locale));

    if (in_array($slug, ['blog', 'shop'], true)) {
      $page_num = absint($_GET['page'] ?? 1);
      if ($page_num > 1) {
        $meta .= sprintf('<link rel="prev" href="%s" />', esc_url(add_query_arg('page', $page_num - 1, $url)));
      }
      $meta .= sprintf('<link rel="next" href="%s" />', esc_url(add_query_arg('page', $page_num + 1, $url)));
    }

    $meta .= '<link rel="preconnect" href="https://fonts.googleapis.com" crossorigin />';
    $meta .= '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />';
    $meta .= '<link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin />';
    $meta .= '<link rel="preconnect" href="https://unpkg.com" crossorigin />';
    $meta .= '<link rel="dns-prefetch" href="//fonts.googleapis.com" />';
    $meta .= '<link rel="dns-prefetch" href="//fonts.gstatic.com" />';
    $meta .= '<link rel="dns-prefetch" href="//cdnjs.cloudflare.com" />';
    $meta .= '<link rel="dns-prefetch" href="//unpkg.com" />';

    if (function_exists('wp_create_nonce')) {
      $meta .= sprintf('<meta name="wc-nonce" content="%s" />', esc_attr(wp_create_nonce('woocommerce-process_checkout')));
    }

    if (preg_match('/^product/', $slug) && function_exists('wc_get_product') && $this->resolved_product_id) {
      $product = wc_get_product($this->resolved_product_id);
      if ($product) {
        $meta .= '<meta property="og:type" content="product" />';
        $meta .= sprintf('<meta property="og:description" content="%s" />', esc_attr(wp_strip_all_tags($product->get_short_description())));
        $img_id = $product->get_image_id();
        $meta .= sprintf('<meta property="og:image" content="%s" />', esc_url($img_id ? wp_get_attachment_url($img_id) : ''));
        $meta .= sprintf('<meta property="product:price:amount" content="%s" />', esc_attr((string) $product->get_price()));
        $meta .= sprintf('<meta property="product:price:currency" content="%s" />', esc_attr(get_woocommerce_currency()));
        $meta .= sprintf('<meta property="product:retailer_item_id" content="%s" />', esc_attr($product->get_sku() ?: (string) $this->resolved_product_id));
        $meta .= sprintf('<meta name="twitter:description" content="%s" />', esc_attr(wp_strip_all_tags($product->get_short_description())));
      }
    }

    return $meta;
  }

  private function build_preload_links(string $slug): string {
    $links = '';
    if (preg_match('/^(index|)$/', $slug)) {
      $hero_img = get_option('phantom_home_banner_img1', '');
      if ($hero_img) {
        $links .= '<link rel="preload" href="' . esc_url($hero_img) . '" as="image" fetchpriority="high" />';
      }
    } elseif (preg_match('/^product/', $slug) && $this->resolved_product_id && function_exists('wc_get_product')) {
      $product = wc_get_product($this->resolved_product_id);
      if ($product) {
        $img_id = $product->get_image_id();
        if ($img_id) {
          $img_url = wp_get_attachment_url($img_id);
          if ($img_url) {
            $links .= '<link rel="preload" href="' . esc_url($img_url) . '" as="image" fetchpriority="high" />';
          }
        }
      }
    }
    return $links;
  }

  private function build_json_ld(string $slug, string $site_name, string $home_url, string $current_url): string {
    $graph = [
      [
        '@type' => 'Organization',
        'name'  => $site_name,
        'url'   => $home_url,
      ],
      [
        '@type' => 'WebSite',
        'name'  => $site_name,
        'url'   => $home_url,
        'potentialAction' => [
          '@type'       => 'SearchAction',
          'target'      => ['@type' => 'EntryPoint', 'urlTemplate' => $home_url . '?s={search_term_string}'],
          'query-input' => 'required name=search_term_string',
        ],
      ],
    ];

    $breadcrumbs = [];
    if (preg_match('/^product/', $slug) && function_exists('wc_get_product') && $this->resolved_product_id) {
      $product = wc_get_product($this->resolved_product_id);
      if ($product) {
        $cats = $product->get_category_ids();
        if (!empty($cats)) {
          $term = get_term($cats[0]);
          if ($term && !is_wp_error($term)) {
            $breadcrumbs[] = ['@type' => 'ListItem', 'position' => 1, 'name' => 'Shop', 'item' => get_permalink(wc_get_page_id('shop'))];
            $breadcrumbs[] = ['@type' => 'ListItem', 'position' => 2, 'name' => $term->name, 'item' => get_term_link($term)];
            $breadcrumbs[] = ['@type' => 'ListItem', 'position' => 3, 'name' => $product->get_name(), 'item' => $product->get_permalink()];
          }
        }
        if (empty($breadcrumbs)) {
          $breadcrumbs[] = ['@type' => 'ListItem', 'position' => 1, 'name' => 'Shop', 'item' => get_permalink(wc_get_page_id('shop'))];
        }
      }
    } elseif (in_array($slug, ['shop', 'cart', 'checkout', 'wishlist', 'my-account', 'account'], true)) {
      $labels = ['shop' => 'Collection', 'cart' => 'Cart', 'checkout' => 'Checkout', 'wishlist' => 'Wishlist', 'my-account' => 'My Account', 'account' => 'My Account'];
      $breadcrumbs[] = ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => $home_url];
      $breadcrumbs[] = ['@type' => 'ListItem', 'position' => 2, 'name' => $labels[$slug] ?? $slug, 'item' => $current_url];
    } elseif (in_array($slug, ['blog', 'post', 'single-blog'], true) && $this->resolved_post_id) {
      $post = get_post($this->resolved_post_id);
      $breadcrumbs[] = ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => $home_url];
      $breadcrumbs[] = ['@type' => 'ListItem', 'position' => 2, 'name' => 'Blog', 'item' => home_url('/blog')];
      if ($post) {
        $breadcrumbs[] = ['@type' => 'ListItem', 'position' => 3, 'name' => $post->post_title, 'item' => get_permalink($post)];
      }
    } elseif ($slug && '404' !== $slug) {
      $labels = [
        'about' => 'About', 'contact' => 'Contact', 'coming-soon' => 'Coming Soon',
        'faq' => 'FAQ', 'team' => 'Our Team', 'testimonials' => 'Testimonials',
        'join-now' => 'Join Now', 'login' => 'Sign In', 'register' => 'Join Now',
        'thank-you' => 'Thank You', 'privacy-policy' => 'Privacy Policy',
        'term-of-use' => 'Terms of Use', 'cookie-policy' => 'Cookie Policy',
      ];
      $breadcrumbs[] = ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => $home_url];
      $breadcrumbs[] = ['@type' => 'ListItem', 'position' => 2, 'name' => $labels[$slug] ?? ucwords(str_replace('-', ' ', $slug)), 'item' => $current_url];
    }

    if (!empty($breadcrumbs)) {
      $graph[] = [
        '@type' => 'BreadcrumbList',
        '@id'   => $current_url . '#breadcrumb',
        'itemListElement' => $breadcrumbs,
      ];
    }

    if (preg_match('/^product/', $slug) && function_exists('wc_get_product') && $this->resolved_product_id) {
      $product = wc_get_product($this->resolved_product_id);
      if ($product) {
        $product_url = $product->get_permalink();
        $image_id = $product->get_image_id();
        $schema = [
          '@type' => 'Product',
          '@id'   => $product_url . '#product',
          'name'  => $product->get_name(),
          'description' => $product->get_description() ?: $product->get_short_description(),
          'url'   => $product_url,
          'sku'   => $product->get_sku(),
          'offers' => [
            '@type' => 'Offer',
            'price' => (string) $product->get_price(),
            'priceCurrency' => get_woocommerce_currency(),
            'availability' => $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            'url'  => $product_url,
          ],
        ];
        if ($image_id) $schema['image'] = wp_get_attachment_url($image_id);
        if (!$product->get_sku()) $schema['mpn'] = (string) $this->resolved_product_id;
        if ($product->get_review_count() > 0) {
          $schema['aggregateRating'] = [
            '@type' => 'AggregateRating',
            'ratingValue' => (string) $product->get_average_rating(),
            'reviewCount' => (string) $product->get_review_count(),
          ];
          $reviews = get_comments(['post_id' => $this->resolved_product_id, 'status' => 'approve', 'type' => 'review', 'number' => 3]);
          foreach ($reviews as $review) {
            $rating = get_comment_meta($review->comment_ID, 'rating', true);
            $schema['review'][] = [
              '@type' => 'Review',
              'reviewRating' => ['@type' => 'Rating', 'ratingValue' => (string) $rating, 'bestRating' => '5'],
              'author' => ['@type' => 'Person', 'name' => $review->comment_author],
              'reviewBody' => wp_strip_all_tags($review->comment_content),
            ];
          }
        }
        $graph[] = $schema;
      }
    } elseif (preg_match('/^(blog|post|single-blog)/', $slug) && $this->resolved_post_id) {
      $post = get_post($this->resolved_post_id);
      if ($post) {
        $graph[] = [
          '@type' => 'BlogPosting',
          'headline' => $post->post_title,
          'datePublished' => $post->post_date,
          'dateModified' => $post->post_modified,
          'author' => ['@type' => 'Person', 'name' => get_the_author_meta('display_name', $post->post_author)],
        ];
      }
    }

    $json_ld = json_encode([
      '@context' => 'https://schema.org',
      '@graph'   => $graph,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);

    return sprintf('<script type="application/ld+json">%s</script>', $json_ld);
  }
}
