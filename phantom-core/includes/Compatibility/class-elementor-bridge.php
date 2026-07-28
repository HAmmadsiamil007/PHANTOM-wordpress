<?php
declare(strict_types=1);

namespace PhantomCore\Compatibility;

use PhantomCore\Bridges\Plugin_Bridge;

defined('ABSPATH') || exit;

class Elementor_Bridge extends Plugin_Bridge {
  protected array $supported_hooks = ['elementor/init', 'elementor/widgets/register'];

  public function __construct() {
    $this->id = 'elementor';
    $this->label = 'Elementor';
  }

  public function is_active(): bool {
    return defined('ELEMENTOR_VERSION');
  }

  public function init(): void {
    if (!$this->is_active()) {
      return;
    }
    add_filter('phantom_core/render/page_content', [$this, 'render_elementor_page'], 10, 2);
    add_action('wp_enqueue_scripts', [$this, 'manage_elementor_assets'], 5);
  }

  public function render_elementor_page(string $content, int $page_id): string {
    if (!$this->is_elementor_page($page_id)) {
      return $content;
    }
    if (class_exists('\Elementor\Plugin')) {
      return \Elementor\Plugin::$instance->frontend->get_builder_content_for_display($page_id);
    }
    return $content;
  }

  public function manage_elementor_assets(): void {
    if (\Elementor\Plugin::$instance->preview->is_preview_mode()) {
      \Elementor\Plugin::$instance->frontend->enqueue_styles();
    }
  }

  private function is_elementor_page(int $page_id): bool {
    return get_post_meta($page_id, '_elementor_edit_mode', true) === 'builder';
  }
}
