<?php
declare(strict_types=1);

namespace PhantomCore\Renderer;

defined('ABSPATH') || exit;

class Search_Card extends Component_Renderer {

  private string $template;

  public function __construct() {
    $this->template = $this->load_template('search-card') ?: $this->default_template();
  }

  public function render(array $data): string {
    $type_icon = '';
    switch ($data['type'] ?? 'post') {
      case 'product': $type_icon = '<i class="fas fa-shopping-bag"></i>'; break;
      case 'page': $type_icon = '<i class="fas fa-file"></i>'; break;
      default: $type_icon = '<i class="fas fa-file-alt"></i>'; break;
    }

    return $this->inject($this->template, [
      'url' => esc_url($data['url']),
      'type_icon' => $type_icon,
      'title' => esc_html($data['title']),
      'excerpt' => esc_html($data['excerpt']),
      'type' => esc_html($data['type'] ?? 'post'),
      'date' => esc_html($data['date']),
    ]);
  }

  private function default_template(): string {
    return '<a href="{{URL}}" class="search-card">
      <div class="search-card-icon">{{TYPE_ICON}}</div>
      <div class="search-card-body">
        <h3 class="search-card-title">{{TITLE}}</h3>
        <p class="search-card-excerpt">{{EXCERPT}}</p>
        <span class="search-card-meta">{{TYPE}} &middot; {{DATE}}</span>
      </div>
      <div class="search-card-arrow"><i class="fas fa-arrow-right"></i></div>
    </a>';
  }
}
