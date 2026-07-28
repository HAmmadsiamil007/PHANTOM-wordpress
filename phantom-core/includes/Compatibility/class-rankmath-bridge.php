<?php
declare(strict_types=1);

namespace PhantomCore\Compatibility;

use PhantomCore\Bridges\Plugin_Bridge;

defined('ABSPATH') || exit;

class RankMath_Bridge extends Plugin_Bridge {
  protected array $supported_hooks = ['rank_math/head'];

  public function __construct() {
    $this->id = 'rankmath';
    $this->label = 'RankMath SEO';
  }

  public function is_active(): bool {
    return class_exists('RankMath\\Helper');
  }

  public function init(): void {
    if (!$this->is_active()) {
      return;
    }
    add_action('phantom_core/seo/head', [$this, 'inject_meta_tags']);
    add_filter('phantom_core/rest/seo_data', [$this, 'add_seo_data'], 10, 2);
  }

  public function inject_meta_tags(): void {
    if (function_exists('rank_math_the_title')) {
      rank_math_the_title();
    }
    if (function_exists('rank_math_the_description')) {
      rank_math_the_description();
    }
    if (function_exists('rank_math_the_keywords')) {
      rank_math_the_keywords();
    }
  }

  public function add_seo_data(array $data, int $post_id): array {
    if (class_exists('RankMath\\Post')) {
      $rm_post = \RankMath\Post::get($post_id);
      $data['focus_keyword'] = $rm_post->get_meta('focus_keyword');
      $data['robots'] = $rm_post->get_meta('robots');
      $data['canonical_url'] = $rm_post->get_meta('canonical_url');
    }
    return $data;
  }
}
