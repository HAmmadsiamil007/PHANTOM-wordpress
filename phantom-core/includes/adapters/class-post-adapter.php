<?php
declare(strict_types=1);

namespace PhantomCore\Adapters;

use PhantomCore\Contracts\AdapterInterface;

defined('ABSPATH') || exit;

class Post_Adapter implements AdapterInterface {

  public function normalize($input = null): array {
    $post = $input;
    if (is_numeric($post)) {
      $post = get_post((int) $post);
    }
    if (is_null($post)) {
      $post = get_post();
    }
    if (!$post || !($post instanceof \WP_Post) || 'publish' !== $post->post_status) {
      return $this->empty();
    }

    $id = $post->ID;
    $categories = wp_get_post_categories($id, ['fields' => 'all']);
    if (is_wp_error($categories)) $categories = [];
    $tags = get_the_tags($id);
    if (is_wp_error($tags)) $tags = false;

    return [
      'id' => $id,
      'title' => $post->post_title,
      'slug' => $post->post_name,
      'url' => get_permalink($id),
      'excerpt' => get_the_excerpt($post),
      'content' => get_the_content(null, false, $post),
      'date' => get_the_date('', $post),
      'image' => get_the_post_thumbnail_url($id, 'full') ?: '',
      'image_id' => (int) get_post_thumbnail_id($id),
      'author' => get_the_author_meta('display_name', $post->post_author),
      'categories' => array_map(function($c) {
        return ['id' => $c->term_id, 'name' => $c->name, 'slug' => $c->slug];
      }, $categories),
      'tags' => $tags ? array_map(function($t) {
        return ['id' => $t->term_id, 'name' => $t->name, 'slug' => $t->slug];
      }, $tags) : [],
    ];
  }

  public function normalize_collection(array $posts): array {
    return array_map([$this, 'normalize'], $posts);
  }

  private function empty(): array {
    return [
      'id' => 0, 'title' => '', 'slug' => '', 'url' => '#',
      'excerpt' => '', 'content' => '', 'date' => '',
      'image' => '', 'image_id' => 0,
      'author' => '', 'categories' => [], 'tags' => [],
    ];
  }
}