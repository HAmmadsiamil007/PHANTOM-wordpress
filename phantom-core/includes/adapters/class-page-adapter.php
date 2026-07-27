<?php
declare(strict_types=1);

namespace PhantomCore\Adapters;

use PhantomCore\Contracts\AdapterInterface;

defined('ABSPATH') || exit;

class Page_Adapter implements AdapterInterface {

  public function normalize($input = null): array {
    $page = $input;
    if (is_numeric($page)) {
      $page = get_post((int) $page);
    }
    if (is_null($page)) {
      $page = get_post();
    }
    if (!$page || !($page instanceof \WP_Post) || 'page' !== $page->post_type || 'publish' !== $page->post_status) {
      return $this->empty();
    }

    $id = $page->ID;

    return [
      'id' => $id,
      'title' => $page->post_title,
      'slug' => $page->post_name,
      'url' => get_permalink($id),
      'content' => get_the_content(null, false, $page),
      'excerpt' => get_the_excerpt($page),
      'date' => get_the_date('', $page),
      'image' => get_the_post_thumbnail_url($id, 'full') ?: '',
      'template' => get_page_template_slug($id),
      'parent_id' => wp_get_post_parent_id($id),
      'menu_order' => $page->menu_order,
    ];
  }

  public function normalize_collection(array $pages): array {
    return array_map([$this, 'normalize'], $pages);
  }

  private function empty(): array {
    return [
      'id' => 0, 'title' => '', 'slug' => '', 'url' => '#',
      'content' => '', 'excerpt' => '', 'date' => '',
      'image' => '', 'template' => '', 'parent_id' => 0, 'menu_order' => 0,
    ];
  }
}