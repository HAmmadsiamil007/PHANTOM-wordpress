<?php
declare(strict_types=1);

namespace PhantomCore\Adapters;

use PhantomCore\Contracts\AdapterInterface;

defined('ABSPATH') || exit;

class Tag_Adapter implements AdapterInterface {

  public function normalize($input = null): array {
    $term = $input;
    if (is_numeric($term)) {
      $term = get_term((int) $term, 'post_tag');
    }
    if (!$term || !($term instanceof \WP_Term) || is_wp_error($term)) {
      return $this->empty();
    }

    return [
      'id'          => $term->term_id,
      'name'        => $term->name,
      'slug'        => $term->slug,
      'description' => $term->description,
      'count'       => (int) $term->count,
      'link'        => get_term_link($term),
      'term_group'  => $term->term_group,
      'posts'       => $this->get_recent_posts($term->term_id),
    ];
  }

  public function normalize_collection(array $inputs): array {
    return array_map([$this, 'normalize'], $inputs);
  }

  private function get_recent_posts(int $term_id): array {
    $posts = get_posts([
      'post_type'   => 'post',
      'numberposts' => 5,
      'tax_query'   => [
        [
          'taxonomy' => 'post_tag',
          'field'    => 'term_id',
          'terms'    => $term_id,
        ],
      ],
    ]);
    return array_map(function ($p) {
      return [
        'id'    => $p->ID,
        'title' => $p->post_title,
        'url'   => get_permalink($p->ID),
      ];
    }, $posts);
  }

  private function empty(): array {
    return [
      'id' => 0, 'name' => '', 'slug' => '', 'description' => '',
      'count' => 0, 'link' => '', 'term_group' => 0, 'posts' => [],
    ];
  }
}
