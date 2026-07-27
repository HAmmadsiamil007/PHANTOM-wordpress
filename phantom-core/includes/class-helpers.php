<?php
declare(strict_types=1);

namespace PhantomCore;

defined('ABSPATH') || exit;

class Helpers {
  public static function get_option(string $key, $default = null): mixed {
    return get_option('phantom_' . $key, $default);
  }

  public static function update_option(string $key, $value): bool {
    return update_option('phantom_' . $key, $value);
  }

  public static function delete_option(string $key): bool {
    return delete_option('phantom_' . $key);
  }

  public static function is_woocommerce_active(): bool {
    return class_exists('WooCommerce');
  }

  public static function is_dev_mode(): bool {
    return (defined('WP_DEBUG') && WP_DEBUG) || (bool) get_option('phantom_dev_mode', false);
  }

  public static function get_current_route(): string {
    global $wp;
    $route = $wp->request ?? '';
    if (empty($route)) {
      $route = get_query_var('pagename', '');
    }
    if (empty($route)) {
      $route = get_option('page_on_front') ? 'index' : '';
    }
    return sanitize_title($route);
  }

  public static function hash_data($data): string {
    return md5(serialize($data) . wp_salt('nonce'));
  }

  public static function versioned_asset(string $path): string {
    return $path . '?v=' . PHANTOM_CORE_VERSION;
  }

  public static function array_get(array $array, string $key, $default = null): mixed {
    if (empty($key)) {
      return $default;
    }
    $keys = explode('.', $key);
    $current = $array;
    foreach ($keys as $segment) {
      if (!is_array($current) || !array_key_exists($segment, $current)) {
        return $default;
      }
      $current = $current[$segment];
    }
    return $current;
  }
}