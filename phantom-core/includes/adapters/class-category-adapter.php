<?php
declare(strict_types=1);

namespace PhantomCore\Adapters;

use PhantomCore\Contracts\AdapterInterface;

defined('ABSPATH') || exit;

class Category_Adapter implements AdapterInterface {

  public function normalize($term): array {
    if (is_numeric($term)) $term = get_term((int) $term, 'product_cat');
    if (!$term || is_wp_error($term)) return $this->empty();

    $thumb_id = get_term_meta($term->term_id, 'thumbnail_id', true);

    return [
      'id' => $term->term_id,
      'name' => $term->name,
      'slug' => $term->slug,
      'url' => get_term_link($term),
      'image' => $thumb_id ? wp_get_attachment_url($thumb_id) : '',
      'count' => (int) $term->count,
      'description' => $term->description,
    ];
  }

  public function normalize_collection(array $terms): array {
    return array_map([$this, 'normalize'], $terms);
  }

  private function empty(): array {
    return ['id' => 0, 'name' => '', 'slug' => '', 'url' => '#', 'image' => '', 'count' => 0, 'description' => ''];
  }
}
