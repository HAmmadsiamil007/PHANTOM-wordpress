<?php
declare(strict_types=1);

namespace PhantomCore\Compatibility;

use PhantomCore\Bridges\Plugin_Bridge;

defined('ABSPATH') || exit;

class WPML_Bridge extends Plugin_Bridge {
  protected array $supported_hooks = ['wpml_after_init', 'wpml_language_switcher'];

  public function __construct() {
    $this->id = 'wpml';
    $this->label = 'WPML';
  }

  public function is_active(): bool {
    return function_exists('wpml_get_active_language');
  }

  public function init(): void {
    if (!$this->is_active()) {
      return;
    }
    add_filter('phantom_core/route/url', [$this, 'translate_route_url'], 10, 2);
    add_filter('phantom_core/nav/menu_items', [$this, 'translate_menu_items'], 10, 2);
    add_filter('phantom_core/rest/language_param', [$this, 'add_language_param']);
  }

  public function translate_route_url(string $url, string $route): string {
    return apply_filters('wpml_permalink', $url, apply_filters('wpml_current_language', null));
  }

  public function translate_menu_items(array $items, string $location): array {
    foreach ($items as &$item) {
      $translated_id = apply_filters('wpml_object_id', $item['object_id'], $item['object'] ?? 'page', true);
      if ($translated_id && $translated_id !== $item['object_id']) {
        $item['url'] = get_permalink($translated_id);
      }
    }
    return $items;
  }

  public function add_language_param(array $params): array {
    $lang = apply_filters('wpml_current_language', null);
    if ($lang) {
      $params['lang'] = $lang;
    }
    return $params;
  }
}
