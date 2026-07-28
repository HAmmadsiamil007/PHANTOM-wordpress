<?php
declare(strict_types=1);

namespace PhantomCore\Renderer;

defined('ABSPATH') || exit;

class Hero extends Component_Renderer {

  public function __construct() {
    $this->template = $this->load_template('hero') ?: $this->default_template();
  }

  public function render(array $data): string {
    $image = $data['image'] ?? '';
    $image_tablet = !empty($data['enable_responsive']) && !empty($data['image_tablet']) ? $data['image_tablet'] : $image;
    $image_mobile = !empty($data['enable_responsive']) && !empty($data['image_mobile']) ? $data['image_mobile'] : $image;

    return $this->inject($this->template, [
      'image' => esc_url($image),
      'image_tablet' => esc_url($image_tablet),
      'image_mobile' => esc_url($image_mobile),
      'tablet_bp' => (string) ($data['tablet_breakpoint'] ?? 1024),
      'mobile_bp' => (string) ($data['mobile_breakpoint'] ?? 768),
      'title' => esc_html($data['title'] ?? ''),
      'subtitle_html' => !empty($data['subtitle']) ? '<p class="hero-subtitle">' . esc_html($data['subtitle']) . '</p>' : '',
      'description_html' => !empty($data['description']) ? '<p class="hero-description">' . esc_html($data['description']) . '</p>' : '',
      'btn_url' => esc_url($data['btn_url'] ?? ''),
      'btn_text' => esc_html($data['btn_text'] ?? ''),
      'overlay_opacity' => esc_attr($data['overlay_opacity'] ?? '0.5'),
      'loading' => esc_attr($data['loading'] ?? 'lazy'),
    ]);
  }

  private function default_template(): string {
    return '<section class="hero-section" style="--hero-overlay-opacity: {{OVERLAY_OPACITY}}">
      <picture>
        <source media="(max-width: {{TABLET_BP}}px)" srcset="{{IMAGE_TABLET}}">
        <source media="(max-width: {{MOBILE_BP}}px)" srcset="{{IMAGE_MOBILE}}">
        <img src="{{IMAGE}}" alt="{{TITLE}}" class="hero-image" loading="{{LOADING}}">
      </picture>
      <div class="hero-content">
        <h1 class="hero-title">{{TITLE}}</h1>
        {{SUBTITLE_HTML}}
        {{DESCRIPTION_HTML}}
        <a href="{{BTN_URL}}" class="btn btn-primary hero-cta">{{BTN_TEXT}}</a>
      </div>
    </section>';
  }
}
