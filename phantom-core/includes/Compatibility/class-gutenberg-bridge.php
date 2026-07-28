<?php
declare(strict_types=1);

namespace PhantomCore\Compatibility;

use PhantomCore\Bridges\Plugin_Bridge;

defined('ABSPATH') || exit;

class Gutenberg_Bridge extends Plugin_Bridge {
  protected array $supported_hooks = ['enqueue_block_assets', 'after_setup_theme'];

  public function __construct() {
    $this->id = 'gutenberg';
    $this->label = 'Gutenberg';
  }

  public function is_active(): bool {
    return function_exists('register_block_type');
  }

  public function init(): void {
    if (!$this->is_active()) {
      return;
    }
    add_filter('phantom_core/render/post_content', [$this, 'filter_post_content'], 10, 2);
    add_filter('render_block', [$this, 'wrap_block'], 10, 2);
  }

  public function filter_post_content(string $content, \WP_Post $post): string {
    if (has_blocks($post->post_content)) {
      return do_blocks($post->post_content);
    }
    return $content;
  }

  public function wrap_block(string $block_content, array $block): string {
    if (in_array($block['blockName'] ?? '', ['core/paragraph', 'core/heading', 'core/image'], true)) {
      return '<div class="phantom-block phantom-block-' . esc_attr(str_replace('/', '-', $block['blockName'])) . '">' . $block_content . '</div>';
    }
    return $block_content;
  }
}
