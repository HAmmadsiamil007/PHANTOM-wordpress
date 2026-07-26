<?php
declare(strict_types=1);

namespace PhantomCore\Adapters;

use PhantomCore\Contracts\AdapterInterface;

defined('ABSPATH') || exit;

class Product_Adapter implements AdapterInterface {

  public function normalize($product): array {
    if (is_numeric($product)) {
      $product = wc_get_product((int) $product);
    }
    if (!$product || !($product instanceof \WC_Product)) {
      return $this->empty();
    }

    $id = $product->get_id();
    $image_id = $product->get_image_id();

    $data = [
      'id'    => $id,
      'name'  => $product->get_name(),
      'slug'  => $product->get_slug(),
      'url'   => get_permalink($id),
      'image' => $image_id ? wp_get_attachment_url($image_id) : wc_placeholder_img_src(),
      'image_alt' => $product->get_title(),
      'gallery' => $this->get_gallery($product),
      'price' => wc_price($product->get_price()),
      'regular_price' => wc_price($product->get_regular_price()),
      'sale_price' => $product->is_on_sale() ? wc_price($product->get_sale_price()) : '',
      'on_sale' => $product->is_on_sale(),
      'is_featured' => $product->is_featured(),
      'in_stock' => $product->is_in_stock(),
      'rating' => $product->get_average_rating(),
      'reviews_count' => $product->get_review_count(),
      'sku' => $product->get_sku(),
      'categories' => $this->get_categories($id),
      'tags' => $this->get_tags($id),
      'type' => $product->get_type(),
      'short_description' => $product->get_short_description(),
      'description' => $product->get_description(),
    ];

    if ($product->is_type('variable')) {
      $data['variations'] = $this->get_variations($product);
      $data['attributes'] = $this->get_attributes($product);
    }

    return $data;
  }

  public function normalize_collection(array $products): array {
    return array_map([$this, 'normalize'], $products);
  }

  private function get_gallery(\WC_Product $product): array {
    $ids = $product->get_gallery_image_ids();
    return array_map('wp_get_attachment_url', $ids ?: []);
  }

  private function get_categories(int $product_id): array {
    $terms = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'all']);
    if (is_wp_error($terms)) return [];
    return array_map(function($t) {
      return ['id' => $t->term_id, 'name' => $t->name, 'slug' => $t->slug, 'url' => get_term_link($t)];
    }, $terms);
  }

  private function get_tags(int $product_id): array {
    $terms = wp_get_post_terms($product_id, 'product_tag', ['fields' => 'all']);
    if (is_wp_error($terms)) return [];
    return array_map(function($t) {
      return ['id' => $t->term_id, 'name' => $t->name, 'slug' => $t->slug];
    }, $terms);
  }

  private function get_variations(\WC_Product_Variable $product): array {
    $variations = [];
    foreach ($product->get_available_variations() as $v) {
      $variations[] = [
        'id' => $v['variation_id'],
        'price' => wc_price($v['display_price']),
        'regular_price' => wc_price($v['display_regular_price']),
        'sale_price' => $v['display_price'] !== $v['display_regular_price'] ? wc_price($v['display_price']) : '',
        'image' => $v['image']['url'] ?? '',
        'in_stock' => $v['is_in_stock'],
        'sku' => $v['sku'] ?? '',
        'attributes' => $v['attributes'],
      ];
    }
    return $variations;
  }

  private function get_attributes(\WC_Product_Variable $product): array {
    $attrs = [];
    foreach ($product->get_variation_attributes() as $name => $options) {
      $tax = str_replace('attribute_', '', $name);
      $attrs[] = [
        'name' => wc_attribute_label($tax, $product),
        'taxonomy' => $tax,
        'options' => array_map(function($opt) {
          return ['slug' => $opt, 'name' => ucfirst(str_replace('-', ' ', $opt))];
        }, $options),
      ];
    }
    return $attrs;
  }

  private function empty(): array {
    return [
      'id' => 0, 'name' => '', 'slug' => '', 'url' => '#',
      'image' => '', 'image_alt' => '', 'gallery' => [],
      'price' => '', 'regular_price' => '', 'sale_price' => '',
      'on_sale' => false, 'is_featured' => false, 'in_stock' => false,
      'rating' => 0, 'reviews_count' => 0, 'sku' => '',
      'categories' => [], 'tags' => [], 'type' => 'simple',
      'short_description' => '', 'description' => '',
    ];
  }
}
