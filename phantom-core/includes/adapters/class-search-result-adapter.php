<?php
declare(strict_types=1);

namespace PhantomCore\Adapters;

use PhantomCore\Contracts\AdapterInterface;

defined('ABSPATH') || exit;

class SearchResult_Adapter implements AdapterInterface {

  public function normalize($input = null): array {
    if ($input instanceof \WC_Product) {
      return $this->from_product($input);
    }
    if ($input instanceof \WP_Post) {
      return $this->from_post($input);
    }
    if (is_numeric($input)) {
      $post = get_post((int) $input);
      if ($post) {
        return $this->from_post($post);
      }
    }
    return $this->empty();
  }

  public function normalize_collection(array $inputs): array {
    return array_map([$this, 'normalize'], $inputs);
  }

  private function from_post(\WP_Post $post): array {
    $type = 'product' === $post->post_type ? 'product' : ('page' === $post->post_type ? 'page' : 'post');
    return [
      'type'      => $type,
      'id'        => $post->ID,
      'title'     => $post->post_title,
      'excerpt'   => get_the_excerpt($post),
      'permalink' => get_permalink($post->ID),
      'image_url' => get_the_post_thumbnail_url($post->ID, 'thumbnail') ?: '',
      'price'     => '',
      'date'      => mysql2date('F j, Y', $post->post_date),
      'score'     => 0,
    ];
  }

  private function from_product(\WC_Product $product): array {
    return [
      'type'      => 'product',
      'id'        => $product->get_id(),
      'title'     => $product->get_name(),
      'excerpt'   => $product->get_short_description(),
      'permalink' => get_permalink($product->get_id()),
      'image_url' => $product->get_image_id() ? wp_get_attachment_url($product->get_image_id()) : '',
      'price'     => wc_price($product->get_price()),
      'date'      => $product->get_date_created() ? $product->get_date_created()->date_i18n('F j, Y') : '',
      'score'     => 0,
    ];
  }

  private function empty(): array {
    return [
      'type' => '', 'id' => 0, 'title' => '', 'excerpt' => '',
      'permalink' => '', 'image_url' => '', 'price' => '', 'date' => '',
      'score' => 0,
    ];
  }
}
