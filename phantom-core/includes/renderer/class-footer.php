<?php
declare(strict_types=1);

namespace PhantomCore\Renderer;

defined('ABSPATH') || exit;

class Footer extends Component_Renderer {

  public function render(array $data): string {
    $copyright = $data['copyright'] ?? '&copy; ' . date('Y') . ' All rights reserved.';
    $template = '<footer class="site-footer" role="contentinfo">
      <div class="container">
        <div class="footer-widgets">{{WIDGETS}}</div>
        <div class="footer-bottom">
          <p class="footer-copyright">{{COPYRIGHT}}</p>
        </div>
      </div>
    </footer>';

    return $this->inject($template, [
      'widgets' => $data['widgets'] ?? '',
      'copyright' => wp_kses_post($copyright),
    ]);
  }
}
