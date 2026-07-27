<?php
declare(strict_types=1);

namespace PhantomCore\Renderer;

defined('ABSPATH') || exit;

class Blog_Card extends Component_Renderer {

  private string $template;

  public function __construct() {
    $this->template = $this->load_template('blog-card') ?: $this->default_template();
  }

  public function render(array $data): string {
    $category = '';
    if (!empty($data['category'])) {
      $category = '<span class="blog-category">' . esc_html($data['category']) . '</span>';
    }

    $date = !empty($data['date']) ? esc_html($data['date']) : '';
    $excerpt = !empty($data['excerpt']) ? '<p class="blog-card-excerpt">' . esc_html($data['excerpt']) . '</p>' : '';

    return $this->inject($this->template, [
      'url' => esc_url($data['url']),
      'image' => esc_url($data['image']),
      'category' => $category,
      'date' => $date,
      'name' => esc_html($data['title']),
      'excerpt' => $excerpt,
      'read_more' => esc_html($data['read_more'] ?? 'Read More'),
    ]);
  }

  private function default_template(): string {
    return '<a href="{{URL}}" class="blog-card" data-tilt data-reveal-item>
      <div class="blog-card-image" data-image-zoom>
        <img loading="lazy" src="{{IMAGE}}" alt="{{TITLE}}">
        {{CATEGORY}}
      </div>
      <div class="blog-card-content">
        <span class="blog-date">{{DATE}}</span>
        <h3 class="blog-card-title">{{TITLE}}</h3>
        {{EXCERPT}}
        <span class="blog-read-more">{{READ_MORE}} <i class="fas fa-arrow-right"></i></span>
      </div>
    </a>';
  }
}