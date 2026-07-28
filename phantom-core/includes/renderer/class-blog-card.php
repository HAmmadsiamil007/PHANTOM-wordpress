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
    return $this->inject($this->template, [
      'url' => esc_url($data['url'] ?? ''),
      'image' => esc_url($data['image'] ?? ''),
      'category' => esc_html($data['category'] ?? ''),
      'date' => esc_html($data['date'] ?? ''),
      'title' => esc_html($data['title'] ?? ''),
      'excerpt' => esc_html($data['excerpt'] ?? ''),
      'author' => esc_html($data['author'] ?? ''),
      'read_more_text' => esc_html($data['read_more'] ?? 'Read More'),
    ]);
  }

  private function default_template(): string {
    return '<a href="{{URL}}" class="blog-card" data-tilt data-reveal-item>
      <div class="blog-card-image" data-image-zoom>
        <img loading="lazy" src="{{IMAGE}}" alt="{{TITLE}}">
        <span class="blog-category">{{CATEGORY}}</span>
      </div>
      <div class="blog-card-content">
        <span class="blog-date">{{DATE}}</span>
        <h3 class="blog-card-title">{{TITLE}}</h3>
        <p class="blog-card-excerpt">{{EXCERPT}}</p>
        <span class="blog-read-more">{{READ_MORE_TEXT}} <i class="fas fa-arrow-right"></i></span>
      </div>
    </a>';
  }
}