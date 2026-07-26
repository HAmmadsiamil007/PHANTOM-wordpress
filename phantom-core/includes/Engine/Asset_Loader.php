<?php
declare(strict_types=1);

namespace PhantomCore\Engine;

defined('ABSPATH') || exit;

class Asset_Loader {

  public function inject_all(string $html, string $slug, bool $is_customizer_preview): string {
    $html = $this->inject_css_by_route($html, $slug);
    $html = $this->inject_images($html);
    $html = $this->inject_google_fonts($html);
    $html = $this->inject_minified_js($html);
    $html = $this->inject_cdn_fallbacks($html);
    $html = $this->inject_lazy_loading($html);
    $html = $this->inject_woo_scripts($html);
    $html = $this->inject_a11y($html);
    $html = $this->inject_scroll_reveal($html);

    return $html;
  }

  private function inject_css_by_route(string $html, string $slug): string {
    $theme_css = PHANTOM_CORE_URL . '../phantom-theme/assets/css/';
    $v = '?v=' . PHANTOM_CORE_VERSION;

    if (in_array($slug, ['blog', 'post', 'single-blog'], true)) {
      $html = str_replace(
        '</head>',
        '<link rel="stylesheet" href="' . esc_url($theme_css . 'blog.css' . $v) . '" media="all">' . "\n" . '</head>',
        $html
      );
    }

    if (in_array($slug, ['shop', 'product', 'product-detail', 'cart', 'checkout', 'wishlist', 'account', 'my-account'], true)) {
      $html = str_replace('</head>',
        '<link rel="stylesheet" href="' . esc_url($theme_css . 'shop.css' . $v) . '" media="all">' . "\n" .
        '<link rel="stylesheet" href="' . esc_url($theme_css . 'woocommerce.css' . $v) . '" media="all">' . "\n" .
        '</head>',
        $html
      );
    }

    return $html;
  }

  private function inject_images(string $html): string {
    $logo = get_option('phantom_custom_logo', '');
    if ($logo) {
      $html = preg_replace(
        '/<img[^>]*class="[^"]*site-logo[^"]*"[^>]*src="[^"]*"[^>]*>/i',
        '<img src="' . esc_url($logo) . '" alt="' . esc_attr(get_bloginfo('name')) . '" class="site-logo">',
        $html
      );
    }

    $favicon = get_option('phantom_custom_favicon', '');
    if ($favicon) {
      $html = preg_replace(
        '/<link[^>]*rel="(icon|shortcut icon)"[^>]*>/i',
        '<link rel="icon" href="' . esc_url($favicon) . '" sizes="32x32">',
        $html
      );
    }

    return $html;
  }

  private function inject_google_fonts(string $html): string {
    $fonts = [];
    $fonts[] = get_option('phantom_typography_body_font', 'Archivo');
    $heading_font = get_option('phantom_typography_heading_font', '');
    if ($heading_font) $fonts[] = $heading_font;

    foreach (['h1', 'h2', 'h3', 'h4', 'h5', 'h6'] as $h) {
      $font = get_option('phantom_typography_' . $h . '_font', '');
      if ($font) $fonts[] = $font;
    }

    $fonts = array_unique(array_filter($fonts));
    if (empty($fonts)) return $html;

    $url = \PhantomCore\Fonts::instance()->get_enqueue_url($fonts);
    $link = '<link rel="stylesheet" id="phantom-google-fonts" href="' . esc_url($url) . '" media="all" />';

    return str_replace('</head>', $link . "\n" . '</head>', $html);
  }

  private function inject_minified_js(string $html): string {
    $js_url = PHANTOM_CORE_URL . 'frontend/assets/js/phantom-core.min.js';
    if (!file_exists(PHANTOM_CORE_PATH . 'frontend/assets/js/phantom-core.min.js')) {
      $js_url = PHANTOM_CORE_URL . 'frontend/assets/js/phantom-data.js';
    }
    $html = str_replace(
      '</body>',
      '<script src="' . esc_url($js_url) . '?v=' . PHANTOM_CORE_VERSION . '" id="phantom-core-js"></script>' . "\n" . '</body>',
      $html
    );
    return $html;
  }

  private function inject_cdn_fallbacks(string $html): string {
    return str_replace(
      '</body>',
      '<script>window.jQuery||document.write(\'<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"><\/script>\')</script>' . "\n" .
      '<script>window.bootstrap||document.write(\'<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css"><\/link>\')</script>' . "\n" .
      '</body>',
      $html
    );
  }

  private function inject_lazy_loading(string $html): string {
    return str_replace(
      '</body>',
      '<script id="phantom-lazy-load">' .
      'document.addEventListener("DOMContentLoaded",function(){' .
      'var obs=new IntersectionObserver(function(e){e.forEach(function(e){' .
      'if(e.isIntersecting){var i=e.target;i.dataset.src&&(i.src=i.dataset.src,delete i.dataset.src);' .
      'i.dataset.srcset&&(i.srcset=i.dataset.srcset,delete i.dataset.srcset);obs.unobserve(i)}})});' .
      'document.querySelectorAll("img[data-src]").forEach(function(i){obs.observe(i)})});' .
      '</script>' . "\n" . '</body>',
      $html
    );
  }

  private function inject_woo_scripts(string $html): string {
    if (!class_exists('WooCommerce')) return $html;
    $nonce = wp_create_nonce('wc_store_api');
    return str_replace(
      '</head>',
      '<meta name="woocommerce-store-api-nonce" content="' . esc_attr($nonce) . '" />' . "\n" . '</head>',
      $html
    );
  }

  private function inject_a11y(string $html): string {
    $css = '<style id="phantom-a11y-css">'
      . '.skip-link{position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden;z-index:100000}'
      . '.skip-link:focus{position:fixed;left:16px;top:16px;width:auto;height:auto;padding:12px 24px;background:#7635d5;color:#fff;font-size:16px;text-decoration:none;border-radius:4px;box-shadow:0 0 0 4px rgba(118,53,213,0.3);z-index:100000;outline:2px solid #fff;outline-offset:2px}'
      . ':focus-visible{outline:2px solid #7635d5;outline-offset:2px}'
      . '</style>';
    $html = str_replace('</head>', $css . '</head>', $html);

    $a11y_css_url = PHANTOM_CORE_URL . 'frontend/assets/css/a11y.css?v=' . PHANTOM_CORE_VERSION;
    $html = str_replace(
      '</head>',
      '<link rel="stylesheet" id="phantom-a11y-css" href="' . esc_url($a11y_css_url) . '" media="all" />' . "\n" . '</head>',
      $html
    );

    $js = '<script id="phantom-a11y-js">(function(){'
      . 'function setAriaCurrent(){'
      . 'var path=window.location.pathname;'
      . 'document.querySelectorAll(".nav-link,.mobile-nav-link,.footer-menu a,.primary-menu a").forEach(function(l){'
      . 'var h=l.getAttribute("href");'
      . 'if(h&&(h===path||h===path.replace(/\/$/,"")||(h.indexOf("#")!==-1&&path===h.split("#")[0]))){l.setAttribute("aria-current","page")}'
      . 'else if(l.getAttribute("aria-current")==="page"){l.removeAttribute("aria-current")}'
      . '})}'
      . 'document.addEventListener("DOMContentLoaded",function(){'
      . 'setAriaCurrent();'
      . 'var mainEl=document.getElementById("main-content")||document.querySelector("main");'
      . 'if(mainEl&&!mainEl.hasAttribute("tabindex")){mainEl.setAttribute("tabindex","-1")}'
      . '});'
      . 'var _pdObs=new MutationObserver(function(){setAriaCurrent()});'
      . '_pdObs.observe(document.body,{childList:true,subtree:true});'
      . '})();</script>';
    return str_replace('</body>', $js . '</body>', $html);
  }

  private function inject_scroll_reveal(string $html): string {
    $js = '<script id="phantom-scroll-reveal">(function(){'
      . 'if(window._phantomRevealInited)return;window._phantomRevealInited=true;'
      . 'var style=document.createElement("style");'
      . 'style.textContent=".pr-reveal{opacity:0;transform:translateY(24px);transition:opacity .6s ease,transform .6s ease}.pr-reveal.pr-visible{opacity:1;transform:translateY(0)}";'
      . 'document.head.appendChild(style);'
      . 'function observe(){'
      . 'var els=document.querySelectorAll(".pr-reveal:not(.pr-visible)");'
      . 'if(!els.length&&window.IntersectionObserver){'
      . 'els=document.querySelectorAll("[data-reveal]:not(.pr-visible),[data-aos]:not(.pr-visible)");'
      . 'els.forEach(function(e){e.classList.add("pr-reveal")})}'
      . 'var obs=new IntersectionObserver(function(entries){'
      . 'entries.forEach(function(e){if(e.isIntersecting){e.target.classList.add("pr-visible");obs.unobserve(e.target)}})'
      . '},{threshold:.1});'
      . 'els.forEach(function(e){obs.observe(e)})}'
      . 'document.addEventListener("DOMContentLoaded",observe);'
      . '})();</script>';
    return str_replace('</body>', $js . '</body>', $html);
  }
}
