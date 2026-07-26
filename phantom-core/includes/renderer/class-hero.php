<?php
declare(strict_types=1);

namespace PhantomCore\Renderer;

defined('ABSPATH') || exit;

class Hero extends Component_Renderer {

  public function render(array $data): string {
    $image = $data['image'];
    $image_tablet = !empty($data['enable_responsive']) && !empty($data['image_tablet']) ? $data['image_tablet'] : $image;
    $image_mobile = !empty($data['enable_responsive']) && !empty($data['image_mobile']) ? $data['image_mobile'] : $image;

    $template = '<section class="hero-section" style="--hero-overlay-opacity: {{OVERLAY_OPACITY}}">';
    if (!empty($data['enable_responsive'])) {
      $template .= '<picture>';
      if ($image_tablet !== $image) {
        $template .= '<source media="(max-width: ' . (int) $data['tablet_breakpoint'] . 'px)" srcset="{{IMAGE_TABLET}}">';
      }
      if ($image_mobile !== $image) {
        $template .= '<source media="(max-width: ' . (int) $data['mobile_breakpoint'] . 'px)" srcset="{{IMAGE_MOBILE}}">';
      }
      $template .= '<img src="{{IMAGE}}" alt="{{TITLE}}" class="hero-image" loading="{{LOADING}}">';
      $template .= '</picture>';
    } else {
      $template .= '<img src="{{IMAGE}}" alt="{{TITLE}}" class="hero-image" loading="{{LOADING}}">';
    }
    $template .= '<div class="hero-content">
      <h1 class="hero-title">{{TITLE}}</h1>
      {{SUBTITLE_HTML}}
      {{DESCRIPTION_HTML}}
      <a href="{{BTN_URL}}" class="btn btn-primary hero-cta">{{BTN_TEXT}}</a>
    </div>
    </section>';

    return $this->inject($template, [
      'image' => esc_url($image),
      'image_tablet' => esc_url($image_tablet),
      'image_mobile' => esc_url($image_mobile),
      'title' => esc_html($data['title']),
      'subtitle_html' => !empty($data['subtitle']) ? '<p class="hero-subtitle">' . esc_html($data['subtitle']) . '</p>' : '',
      'description_html' => !empty($data['description']) ? '<p class="hero-description">' . esc_html($data['description']) . '</p>' : '',
      'btn_url' => esc_url($data['btn_url']),
      'btn_text' => esc_html($data['btn_text']),
      'overlay_opacity' => esc_attr($data['overlay_opacity']),
      'loading' => esc_attr($data['loading']),
    ]);
  }
}
