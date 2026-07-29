<?php
declare(strict_types=1);

namespace PhantomCore\Engine;

use PhantomCore\Feature\Feature_Registry;

defined('ABSPATH') || exit;

class Asset_Engine {

  private const FA_VERSION = '6.5.1';

  private Data_Engine $data;
  private Security_Headers $security;

  public function __construct(Data_Engine $data, Security_Headers $security) {
    $this->data = $data;
    $this->security = $security;
  }

  /**
   * Minimal injection for self-contained AETHER templates.
   * Only injects bridge data, auth nonces, and SPA routing JS.
   * No CSS, fonts, SEO meta, or loading state — templates have their own.
   */
  public function inject_essential_only(string $html, string $slug): string {
    $html = $this->inject_bridge($html, $slug);
    $html = $this->inject_auth_nonces($html);
    $html = $this->inject_minified_js($html);
    $this->security->send(false);
    return $html;
  }

  public function inject_all(string $html, string $slug, bool $is_customizer_preview): string {
    $html = $this->inject_css_by_route($html, $slug);
    $html = $this->inject_images($html);
    $html = $this->inject_resource_hints($html, $slug);
    $html = $this->inject_google_fonts($html);
    $html = $this->inject_font_awesome($html);
    $html = $this->inject_minified_js($html);
    $html = $this->inject_cdn_fallbacks($html);

    // Performance-gated: lazy loading
    if (Feature_Registry::get_instance()->enabled('lazy_load_images') && get_option('phantom_performance_lazy_load', '1')) {
      $html = $this->inject_lazy_loading($html);
    }

    $html = $this->inject_woo_scripts($html);
    $html = $this->inject_a11y($html);

    // Feature-gated: scroll animations
    if (Feature_Registry::get_instance()->enabled('animate_on_scroll')) {
      $html = $this->inject_scroll_reveal($html);
    }

    // Feature-gated: swiper gallery
    if (Feature_Registry::get_instance()->enabled('swiper_gallery')) {
      $html = $this->inject_swiper($html, $slug);
    }

    $html = $this->inject_performance_options($html);

    if (!$is_customizer_preview) {
      $html = $this->inject_bridge($html, $slug);
    }

    $html = $this->inject_auth_nonces($html);
    $html = $this->inject_customizer_css($html);
    $html = $this->inject_plugin_hooks($html);
    $this->security->send($is_customizer_preview);

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

  private function inject_resource_hints(string $html, string $slug): string {
    $hints = '';
    $default_domains = [
      'https://fonts.googleapis.com',
      'https://fonts.gstatic.com',
      'https://cdnjs.cloudflare.com',
      'https://unpkg.com',
    ];

    // User-defined preconnect domains
    $user_preconnect = get_option('phantom_performance_preconnect_domains', []);
    if (!empty($user_preconnect) && is_array($user_preconnect)) {
      foreach ($user_preconnect as $domain) {
        $domain = trim($domain);
        if ($domain) {
          $hints .= '<link rel="preconnect" href="' . esc_url($domain) . '" crossorigin />' . "\n";
        }
      }
    } else {
      foreach ($default_domains as $domain) {
        $hints .= '<link rel="preconnect" href="' . esc_url($domain) . '" crossorigin />' . "\n";
      }
    }

    // DNS prefetch
    if (get_option('phantom_performance_dns_prefetch', '0')) {
      $dns_domains = get_option('phantom_performance_dns_prefetch_domains', []);
      if (is_array($dns_domains)) {
        foreach ($dns_domains as $domain) {
          $domain = trim($domain);
          if ($domain) {
            $hints .= '<link rel="dns-prefetch" href="' . esc_url($domain) . '" />' . "\n";
          }
        }
      }
    }

    // Preload hero image
    if (get_option('phantom_performance_preload_hero', '1')) {
      if (in_array($slug, ['', 'index'], true)) {
        $hero_img = get_option('phantom_home_banner_img1', '');
        if ($hero_img) {
          $hints .= '<link rel="preload" href="' . esc_url($hero_img) . '" as="image" fetchpriority="high" />' . "\n";
        }
      } elseif (preg_match('/^product/', $slug) && function_exists('wc_get_product')) {
        $product_id = apply_filters('phantom_core/current_product_id', 0);
        if ($product_id) {
          $product = wc_get_product($product_id);
          if ($product) {
            $img_id = $product->get_image_id();
            if ($img_id) {
              $img_url = wp_get_attachment_url($img_id);
              if ($img_url) {
                $hints .= '<link rel="preload" href="' . esc_url($img_url) . '" as="image" fetchpriority="high" />' . "\n";
              }
            }
          }
        }
      }
    }

    // Preload Google Fonts stylesheet
    if (get_option('phantom_performance_preload_fonts', '1')) {
      $fonts_url = $this->get_google_fonts_url();
      if ($fonts_url) {
        $hints .= '<link rel="preload" as="style" href="' . esc_url($fonts_url) . '" />' . "\n";
      }
    }

    if ($hints) {
      return str_replace('</head>', $hints . '</head>', $html);
    }

    return $html;
  }

  private function get_google_fonts_url(): string {
    $fonts = [];
    $fonts[] = get_option('phantom_typography_body_font', 'Archivo');
    $heading_font = get_option('phantom_typography_heading_font', '');
    if ($heading_font) $fonts[] = $heading_font;

    foreach (['h1', 'h2', 'h3', 'h4', 'h5', 'h6'] as $h) {
      $font = get_option('phantom_typography_' . $h . '_font', '');
      if ($font) $fonts[] = $font;
    }

    $fonts = array_unique(array_filter($fonts));
    if (empty($fonts)) return '';

    return \PhantomCore\Fonts::instance()->get_enqueue_url($fonts);
  }

  private function inject_google_fonts(string $html): string {
    $url = $this->get_google_fonts_url();
    if (!$url) return $html;

    $font_display = get_option('phantom_performance_font_display', 'swap');
    if ($font_display && $font_display !== 'swap') {
      $url = add_query_arg('display', $font_display, $url);
    }

    $link = '<link rel="stylesheet" id="phantom-google-fonts" href="' . esc_url($url) . '" media="all" />';
    return str_replace('</head>', $link . "\n" . '</head>', $html);
  }

  private function inject_font_awesome(string $html): string {
    if (strpos($html, 'font-awesome') !== false) {
      return $html;
    }
    $fa_url = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/' . self::FA_VERSION . '/css/all.min.css';
    $link = '<link rel="stylesheet" id="phantom-font-awesome" href="' . esc_url($fa_url) . '" media="all" />';
    $fallback = '<script>(function(){var l=document.getElementById("phantom-font-awesome");if(!l||!l.sheet){var d=document.createElement("link");d.rel="stylesheet";d.href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@' . self::FA_VERSION . '/css/all.min.css";document.head.appendChild(d)}})();</script>';
    return str_replace('</head>', $link . "\n" . $fallback . "\n" . '</head>', $html);
  }

  private function inject_minified_js(string $html): string {
    $injector_url = PHANTOM_CORE_URL . 'frontend/assets/js/phantom-injector.js';
    $html = str_replace(
      '</body>',
      '<script src="' . esc_url($injector_url) . '?v=' . PHANTOM_CORE_VERSION . '"></script>' . "\n" . '</body>',
      $html
    );
    return $html;
  }

  private function inject_cdn_fallbacks(string $html): string {
    $fallback = '<script>
(function(){
  if(!window.jQuery){
    var s=document.createElement("script");
    s.src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js";
    document.head.appendChild(s);
  }
  if(!window.bootstrap){
    var l=document.createElement("link");
    l.rel="stylesheet";
    l.href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css";
    document.head.appendChild(l);
  }
})();
</script>';
    return str_replace('</body>', $fallback . "\n" . '</body>', $html);
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

  private function inject_swiper(string $html, string $slug): string {
    if (!class_exists('\PhantomCore\Bridges\Swiper_Bridge')) return $html;
    $bridge = \PhantomCore\Bridges\Swiper_Bridge::get_instance();
    if (!$bridge->is_active()) return $html;

    $is_product = is_product() || preg_match('/^product\//', $slug);
    $has_hero = (bool) get_option('phantom_home_banner_img1', '');
    if (!$is_product && !$has_hero) return $html;

    $effects = get_option('phantom_animations_swiper_effects', 'fade');
    $autoplay = get_option('phantom_animations_swiper_autoplay', '1');
    $autoplay_speed = get_option('phantom_animations_swiper_autoplay_speed', '3000');
    $loop = get_option('phantom_animations_swiper_loop', '1');
    $pagination = get_option('phantom_animations_swiper_pagination', '1');
    $navigation = get_option('phantom_animations_swiper_navigation', '1');

    $config = [
      'effects' => $effects,
      'autoplay' => '1' === $autoplay,
      'autoplaySpeed' => absint($autoplay_speed),
      'loop' => '1' === $loop,
      'pagination' => '1' === $pagination,
      'navigation' => '1' === $navigation,
    ];

    $html = str_replace(
      '</head>',
      '<script id="phantom-swiper-init">window.PhantomSwiperConfig=' . wp_json_encode($config) . ';</script>' . "\n" . '</head>',
      $html
    );

    return $html;
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

  private function inject_performance_options(string $html): string {
    // Defer non-critical JS
    if (get_option('phantom_performance_defer_js', '0')) {
      $html = preg_replace(
        '/<script\s+src=(["\'])([^"\']+)\1([^>]*)>/i',
        '<script defer src=$1$2$1$3>',
        $html
      );
    }

    return $html;
  }

  private function inject_bridge(string $html, string $slug): string {
    $data = $this->data->get_bridge_data();
    $json = wp_json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $script = '<script id="phantom-bridge-data" type="application/json">' . $json . '</script>';
    $script .= '<script id="phantom-bridge-js">'
      . 'window.PhantomData=' . $json . ';'
      . 'window.PhantomData.api_nonce=document.getElementById("phantom-bridge-data")'
      . '?document.getElementById("phantom-bridge-data").textContent:JSON.stringify(window.PhantomData);'
      . 'try{window.PhantomData=JSON.parse(document.getElementById("phantom-bridge-data").textContent)}catch(e){}'
      . '</script>';

    return str_replace('</head>', $script . "\n" . '</head>', $html);
  }

  private function inject_auth_nonces(string $html): string {
    $nonces = $this->data->get_auth_nonces();
    $json = wp_json_encode($nonces);
    $script = '<script id="phantom-nonces">window.PhantomNonces=' . $json . ';</script>';
    return str_replace('</body>', $script . '</body>', $html);
  }

  private function inject_customizer_css(string $html): string {
    $all_css = $this->data->get_customizer_css();
    if ('' === $all_css) return $html;
    return str_replace('</head>', $all_css . '</head>', $html);
  }

  private function inject_plugin_hooks(string $html): string {
    ob_start();
    do_action('phantom_before_head_close');
    $head_hook = ob_get_clean();
    if ($head_hook) {
      $html = str_replace('</head>', $head_hook . '</head>', $html);
    }

    ob_start();
    do_action('phantom_before_body_close');
    $body_hook = ob_get_clean();
    if ($body_hook) {
      $html = str_replace('</body>', $body_hook . '</body>', $html);
    }

    return $html;
  }

}
