<?php
declare(strict_types=1);

namespace PhantomCore\Adapters;

use PhantomCore\Contracts\AdapterInterface;

defined('ABSPATH') || exit;

class Footer_Adapter implements AdapterInterface {

  public function normalize($input = null): array {
    $prefix = 'phantom_';
    $social_raw = get_option($prefix . 'footer_social_links', []);
    $social_links = [];
    if (is_array($social_raw)) {
      foreach ($social_raw as $link) {
        if (isset($link['platform'], $link['url'])) {
          $social_links[] = [
            'platform' => sanitize_text_field($link['platform']),
            'url' => esc_url_raw($link['url']),
          ];
        }
      }
    }

    return [
      'copyright_text' => get_option($prefix . 'footer_copyright', ''),
      'footer_widgets' => (bool) get_option($prefix . 'footer_widgets', true),
      'columns' => (int) get_option($prefix . 'footer_columns', 4),
      'show_social' => (bool) get_option($prefix . 'footer_social', false),
      'social_links' => $social_links,
      'back_to_top' => (bool) get_option($prefix . 'footer_back_to_top', true),
    ];
  }

  public function normalize_collection(array $inputs): array {
    return array_map([$this, 'normalize'], $inputs);
  }

  private function empty(): array {
    return [
      'copyright_text' => '', 'footer_widgets' => false, 'columns' => 4,
      'show_social' => false, 'social_links' => [], 'back_to_top' => false,
    ];
  }
}