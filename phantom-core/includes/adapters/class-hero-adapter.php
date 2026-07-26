<?php
declare(strict_types=1);

namespace PhantomCore\Adapters;

use PhantomCore\Contracts\AdapterInterface;

defined('ABSPATH') || exit;

class Hero_Adapter implements AdapterInterface {

  public function normalize(): array {
    $prefix = 'phantom_';
    return [
      'title' => get_option($prefix . 'home_banner_title', 'Summer Collection'),
      'subtitle' => get_option($prefix . 'home_banner_subtitle', ''),
      'description' => get_option($prefix . 'home_banner_desc', ''),
      'btn_text' => get_option($prefix . 'home_banner_btn_text', 'Shop Now'),
      'btn_url' => get_option($prefix . 'home_banner_btn_url', '/shop'),
      'image' => get_option($prefix . 'home_banner_img1', ''),
      'image_tablet' => get_option($prefix . 'hero_image_tablet', ''),
      'image_mobile' => get_option($prefix . 'hero_image_mobile', ''),
      'overlay_opacity' => get_option($prefix . 'hero_overlay_opacity', 0.3),
      'enable_responsive' => (bool) get_option($prefix . 'hero_enable_responsive', false),
      'tablet_breakpoint' => get_option($prefix . 'hero_tablet_breakpoint', 1024),
      'mobile_breakpoint' => get_option($prefix . 'hero_mobile_breakpoint', 768),
      'fit' => get_option($prefix . 'hero_fit', 'cover'),
      'position' => get_option($prefix . 'hero_position', 'center'),
      'loading' => get_option($prefix . 'hero_loading', 'lazy'),
    ];
  }

  public function normalize_collection(array $inputs): array {
    return array_map([$this, 'normalize'], $inputs);
  }
}
