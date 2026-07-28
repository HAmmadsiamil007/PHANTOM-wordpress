<?php
declare(strict_types=1);

namespace PhantomCore\Adapters;

use PhantomCore\Contracts\AdapterInterface;

defined('ABSPATH') || exit;

class Menu_Adapter implements AdapterInterface {

  public function normalize($location = null): array {
    if (is_null($location)) return ['items' => []];
    $locations = get_nav_menu_locations();
    if (!isset($locations[$location])) return ['items' => []];

    $menu_id = $locations[$location];
    $items = wp_get_nav_menu_items($menu_id);
    if (!$items) return ['items' => []];

    $tree = $this->build_tree($items);
    $result = ['items' => $tree];
    return apply_filters('phantom_core/nav/menu_items', $result, $location);
  }

  public function normalize_collection(array $locations): array {
    $result = [];
    foreach ($locations as $location) {
      $result[$location] = $this->normalize($location);
    }
    return $result;
  }

  private function build_tree(array $items, int $parent = 0): array {
    $branch = [];
    foreach ($items as $item) {
      if ((int) $item->menu_item_parent !== $parent) continue;
      $branch[] = [
        'title' => $item->title,
        'url' => $item->url,
        'target' => $item->target,
        'classes' => array_filter($item->classes ?? []),
        'children' => $this->build_tree($items, (int) $item->ID),
      ];
    }
    return $branch;
  }
}
