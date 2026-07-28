<?php
declare(strict_types=1);

namespace PhantomCore\Renderer;

defined('ABSPATH') || exit;

class Nav_Menu extends Component_Renderer {

  private string $template;

  public function __construct() {
    $this->template = $this->load_template('nav-menu') ?: $this->default_template();
  }

  public function render(array $data): string {
    return $this->inject($this->template, [
      'label' => esc_attr($data['label'] ?? 'Navigation'),
      'menu_items' => $data['menu_items'] ?? '',
    ]);
  }

  private function default_template(): string {
    return '<nav class="nav-menu" role="navigation" aria-label="{{LABEL}}"><ul class="nav-menu-list">{{MENU_ITEMS}}</ul></nav>';
  }
}
