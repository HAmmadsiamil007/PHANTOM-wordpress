<?php
declare(strict_types=1);

namespace PhantomCore\Renderer;

defined('ABSPATH') || exit;

class Category_Card extends Component_Renderer {

  private string $template;

  public function __construct() {
    $this->template = $this->load_template('category-card') ?: $this->default_template();
  }

  public function render(array $data): string {
    return $this->inject($this->template, [
      'url' => esc_url($data['url']),
      'image' => esc_url($data['image']),
      'name' => esc_html($data['name']),
      'count' => (int) $data['count'] . ' items',
      'cta' => 'Shop ' . esc_html($data['name']),
    ]);
  }

  private function default_template(): string {
    return '<a href="{{URL}}" class="category-card" data-tilt data-reveal-item>
      <div class="category-card-bg">
        <img loading="lazy" src="{{IMAGE}}" alt="{{NAME}}">
        <div class="category-card-overlay"></div>
      </div>
      <div class="category-card-content">
        <span class="category-count">{{COUNT}}</span>
        <h3 class="category-name">{{NAME}}</h3>
        <span class="category-cta">{{CTA}} <i class="fas fa-arrow-right"></i></span>
      </div>
    </a>';
  }
}
