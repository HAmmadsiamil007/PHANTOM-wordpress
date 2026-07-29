<?php
declare(strict_types=1);

namespace PhantomCore\Engine;

defined('ABSPATH') || exit;

/**
 * Render_Engine — orchestrates the SPA rendering pipeline.
 *
 * ChatGPT P2: Delegates route handling to RequestRouter and output assembly
 * to ResponseBuilder. This class remains the facade that wires everything together.
 */
class Render_Engine {

  private RequestRouter $router;
  private ResponseBuilder $builder;

  public function __construct(
    private Data_Engine $data,
    private View_Engine $view,
    private Asset_Engine $assets,
    private EventDispatcher $events
  ) {
    $this->router = new RequestRouter();
    $this->builder = new ResponseBuilder();
  }

  public function with_product_id(int $id): self {
    $this->data->with_product_id($id); return $this;
  }

  public function with_post_id(int $id): self {
    $this->data->with_post_id($id); return $this;
  }

  public function with_category(string $slug): self {
    $this->data->with_category($slug); return $this;
  }

  public function get_resolved_product_id(): ?int {
    return $this->data->get_resolved_product_id();
  }

  public function get_resolved_post_id(): ?int {
    return $this->data->get_resolved_post_id();
  }

  public function get_category_slug(): ?string {
    return $this->data->get_category_slug();
  }

  public function get_template_loader(): Template_Loader {
    return $this->data->get_template_loader();
  }

  /**
   * The main rendering pipeline.
   *
   * 1. Route analysis (RequestRouter)
   * 2. Template resolution + loading
   * 3. Placeholder replacement ({{PLACEHOLDER}} tags)
   * 4. View injection (View_Engine)
   * 5. WooCommerce injection
   * 6. Asset injection (Asset_Engine)
   * 7. Response assembly (ResponseBuilder)
   */
  public function render(string $slug): string {
    $is_customizer = $this->router->is_customizer_preview();
    $template = $this->data->get_template_loader()->resolve($slug);

    $this->router->set_status_header($template);

    $html = $this->data->get_template_loader()->load($template);
    if (empty($html)) {
      status_header(404);
      return '';
    }

    // Self-contained AETHER templates have their own <!DOCTYPE html>, <title>,
    // fonts, CSS, preloader, meta tags — and NO {{PLACEHOLDER}} tags.
    // Pack templates have {{PLACEHOLDER}} tags and need wp_head()/wp_footer().
    $is_self_contained = (bool) preg_match('/<!DOCTYPE\s+html>/i', $html)
      && !preg_match('/\{\{[A-Z_]+\}\}/', $html);

    // Placeholder replacement — resolve {{PLACEHOLDER}} tags to dynamic content
    // For self-contained templates, skip wp_head()/wp_footer() calls that inject SEO/CSS
    $html = (new Placeholder_Replacer($this->data->get_template_loader()))->replace($html, $slug, $is_self_contained);

    if (!$is_self_contained) {
      // View injection (skip for self-contained templates)
      $html = $this->view->inject_all(
        $html, $slug,
        $this->data->get_resolved_product_id(),
        $this->data->get_resolved_post_id()
      );
    }

    // WooCommerce injection (if active)
    // Self-contained AETHER templates get content-only injection (shop grid,
    // product detail, cart/checkout) to preserve their designed hero/footer.
    if (class_exists('WooCommerce')) {
      $wc_slugs = ['shop', 'product', 'product-detail', 'cart', 'checkout', 'wishlist', 'account', 'my-account', 'orders', 'order-detail', 'search'];
      $is_wc_page = in_array($slug, $wc_slugs, true) || preg_match('/^(product|category|order|search)\//', $slug);
      if ($is_wc_page && function_exists('wc_load_cart')) {
        wc_load_cart();
      }
      if ($is_self_contained) {
        $html = (new WooCommerce_Injector($this, $this->events))->inject_content_only($html, $slug);
      } else {
        $html = (new WooCommerce_Injector($this, $this->events))->inject($html, $slug);
      }
    }

    // Asset injection — for self-contained templates only inject bridge, nonces, SPA JS
    if ($is_self_contained) {
      $html = $this->assets->inject_essential_only($html, $slug);
    } else {
      $html = $this->assets->inject_all($html, $slug, $is_customizer);
    }

    // Response assembly
    $html = $this->builder->build($html, $template, $slug, $is_customizer, $this->router);

    return $html;
  }

}
