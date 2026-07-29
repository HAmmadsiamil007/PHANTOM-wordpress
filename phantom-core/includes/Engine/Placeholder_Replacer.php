<?php
declare(strict_types=1);

namespace PhantomCore\Engine;

defined('ABSPATH') || exit;

class Placeholder_Replacer {

  private Template_Loader $template_loader;

  public function __construct(Template_Loader $template_loader) {
    $this->template_loader = $template_loader;
  }

  public function replace(string $html, string $slug, bool $is_self_contained = false): string {
    $replacements = $this->build_replacements($slug, $is_self_contained);
    foreach ($replacements as $placeholder => $value) {
      $html = str_replace('{{' . $placeholder . '}}', $value, $html);
    }
    return $html;
  }

  private function build_replacements(string $slug, bool $is_self_contained = false): array {
    $pack_assets = $this->template_loader->get_pack_asset_urls();
    $css_tags = '';
    foreach ($pack_assets['css'] as $url) {
      $css_tags .= '<link rel="stylesheet" href="' . esc_url($url) . '" media="all" />' . "\n";
    }
    $js_tags = '';
    foreach ($pack_assets['js'] as $url) {
      $js_tags .= '<script src="' . esc_url($url) . '"></script>' . "\n";
    }

    // Generate AETHER base CSS links — always load even with pack active
    $v = '?v=' . PHANTOM_CORE_VERSION;
    $base = PHANTOM_CORE_URL . 'frontend/assets/css/';
    $base_css = '<link rel="stylesheet" href="' . esc_url($base . 'style.css' . $v) . '" media="all">' . "\n"
      . '<link rel="stylesheet" href="' . esc_url($base . 'motion.css' . $v) . '" media="all">' . "\n"
      . '<link rel="stylesheet" href="' . esc_url($base . 'responsive.css' . $v) . '" media="all">';

    return [
      'TITLE' => $this->get_site_title(),
      'META_DESCRIPTION' => $this->get_meta_description(),
      'HEAD_CSS' => $base_css,
      'PACK_CSS' => $css_tags,
      'PACK_JS' => $js_tags,
      'FOOTER_JS' => '',
      'HEADER' => $is_self_contained ? '' : $this->get_wp_head(),
      'FOOTER' => $is_self_contained ? '' : $this->get_wp_footer(),
      'PAGE_TITLE' => $this->get_page_title($slug),
      'PAGE_SUBTITLE' => '',
      'HERO_BADGE' => '',
      'HERO_TITLE' => $this->get_page_title($slug),
      'HERO_SUBTITLE' => '',
      'HERO_CTA' => '',
      'HERO_SECONDARY_CTA' => '',
      'FEATURES_TITLE' => '',
      'FEATURES_DESC' => '',
      'FEATURES_GRID' => '',
      'PRODUCTS_TITLE' => '',
      'PRODUCT_GRID' => '',
      'TESTIMONIALS' => '',
      'SHOP_SIDEBAR' => '',
      'SHOP_CONTROLS' => '',
      'PAGINATION' => '',
    ];
  }

  private function get_site_title(): string {
    return esc_html(get_bloginfo('name'));
  }

  private function get_meta_description(): string {
    return esc_attr(get_bloginfo('description'));
  }

  private function get_page_title(string $slug): string {
    if (is_singular() || is_page()) {
      return esc_html(get_the_title());
    }
    if (is_post_type_archive('product')) {
      return esc_html(post_type_archive_title('', false));
    }
    if (is_category() || is_tax()) {
      return esc_html(single_term_title('', false));
    }
    if (is_search()) {
      return esc_html(sprintf(__('Search Results for "%s"', 'phantom-core'), get_search_query()));
    }
    return $this->get_site_title();
  }

  private function get_wp_head(): string {
    ob_start();
    wp_head();
    return ob_get_clean();
  }

  private function get_wp_footer(): string {
    ob_start();
    wp_footer();
    return ob_get_clean();
  }

}