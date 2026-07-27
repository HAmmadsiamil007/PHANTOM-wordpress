<?php
declare(strict_types=1);

namespace PhantomCore\Engine;

defined('ABSPATH') || exit;

class View_Engine {

  private SEO_Engine $seo;

  public function __construct(SEO_Engine $seo) {
    $this->seo = $seo;
  }

  public function inject_all(string $html, string $slug, ?int $product_id = null, ?int $post_id = null): string {
    $html = $this->inject_dark_mode($html);
    $html = $this->inject_skip_link($html);
    $html = $this->inject_loading_state($html);
    $html = $this->inject_aria_roles($html);
    $html = $this->inject_loading_css($html);

    $this->seo->with_product_id($product_id ?? 0)->with_post_id($post_id ?? 0);
    $html = $this->seo->inject($html, $slug);

    return $html;
  }

  private function inject_dark_mode(string $html): string {
    if (!empty($_COOKIE['phantom_dark_mode']) && '1' === $_COOKIE['phantom_dark_mode']) {
      $html = preg_replace('/<body(\s[^>]*)?>/', '<body$1 data-phantom-dark-mode="true">', $html, 1);
    }
    return $html;
  }

  private function inject_skip_link(string $html): string {
    return preg_replace(
      '/<body(\s[^>]*)?>/',
      '<body$1>' . "\n" . '<a class="skip-link screen-reader-text" href="#main">Skip to main content</a>',
      $html,
      1
    );
  }

  private function inject_loading_state(string $html): string {
    $loading = '<div id="phantom-loading" role="status" aria-hidden="true" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:var(--bg-color,#fff);z-index:9999;align-items:center;justify-content:center;transition:opacity .3s"><div style="width:40px;height:40px;border:3px solid var(--border-color,#e5e7eb);border-top-color:var(--accent-color,#6366f1);border-radius:50%;animation:phantom-spin .8s linear infinite"></div></div>';
    return preg_replace('/<body[^>]*>/', '$0' . "\n" . $loading, $html, 1);
  }

  private function inject_aria_roles(string $html): string {
    $html = preg_replace('/<header([^>]*)>/i', '<header$1 role="banner">', $html, 1);
    if (!preg_match('/<main[^>]*role=/i', $html)) {
      $html = preg_replace('/<main([^>]*)>/i', '<main$1 role="main">', $html, 1);
    }
    $html = preg_replace('/<footer([^>]*)>(?!.*role=)/i', '<footer$1 role="contentinfo">', $html, 1);
    return $html;
  }

  private function inject_loading_css(string $html): string {
    return str_replace('</head>', '<style id="phantom-loading-css">@keyframes phantom-spin{to{transform:rotate(360deg)}}</style></head>', $html);
  }

}
