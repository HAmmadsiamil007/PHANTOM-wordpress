<?php
declare(strict_types=1);

namespace PhantomCore\Compatibility;

use PhantomCore\Bridges\Plugin_Bridge;

defined('ABSPATH') || exit;

class CF7_Bridge extends Plugin_Bridge {
  protected array $supported_hooks = ['wpcf7_init', 'wpcf7_after_save'];

  public function __construct() {
    $this->id = 'cf7';
    $this->label = 'Contact Form 7';
  }

  public function is_active(): bool {
    return defined('WPCF7_VERSION');
  }

  public function init(): void {
    if (!$this->is_active()) {
      return;
    }
    add_filter('phantom_core/render/post_content', [$this, 'render_cf7_shortcodes'], 10, 1);
    add_action('wp_enqueue_scripts', [$this, 'enqueue_cf7_assets'], 20);
  }

  public function render_cf7_shortcodes(string $content): string {
    if (function_exists('wpcf7_contact_form_tag_func')) {
      $content = do_shortcode($content);
    }
    return $content;
  }

  public function enqueue_cf7_assets(): void {
    if (function_exists('wpcf7_enqueue_scripts')) {
      wpcf7_enqueue_scripts();
    }
    if (function_exists('wpcf7_enqueue_styles')) {
      wpcf7_enqueue_styles();
    }
  }
}
