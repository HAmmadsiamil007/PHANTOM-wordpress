<?php
declare(strict_types=1);

namespace PhantomCore\Compatibility;

use PhantomCore\Bridges\Plugin_Bridge;

defined('ABSPATH') || exit;

class Yoast_Bridge extends Plugin_Bridge {
  protected array $supported_hooks = ['wpseo_head'];

  public function __construct() {
    $this->id = 'yoast';
    $this->label = 'Yoast SEO';
  }

  public function is_active(): bool {
    return defined('WPSEO_VERSION');
  }

  public function init(): void {
    if (!$this->is_active()) {
      return;
    }
    add_action('phantom_core/seo/head', [$this, 'inject_yoast_meta']);
    add_filter('phantom_core/rest/seo_data', [$this, 'add_yoast_data'], 10, 2);
  }

  public function inject_yoast_meta(): void {
    if (function_exists('wpseo_frontend_head_init')) {
      wpseo_frontend_head_init();
    }
    if (class_exists('WPSEO_Frontend')) {
      $frontend = \WPSEO_Frontend::get_instance();
      $frontend->head();
    }
  }

  public function add_yoast_data(array $data, int $post_id): array {
    if (class_exists('WPSEO_Meta')) {
      $data['title'] = \WPSEO_Meta::get_value('title', $post_id);
      $data['description'] = \WPSEO_Meta::get_value('metadesc', $post_id);
      $data['canonical'] = \WPSEO_Meta::get_value('canonical', $post_id);
      $data['noindex'] = \WPSEO_Meta::get_value('meta-robots-noindex', $post_id);
    }
    return $data;
  }
}
