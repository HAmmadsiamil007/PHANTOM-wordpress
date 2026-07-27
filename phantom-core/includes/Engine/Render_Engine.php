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
   * 3. View injection (View_Engine)
   * 4. WooCommerce injection
   * 5. Asset injection (Asset_Engine)
   * 6. Response assembly (ResponseBuilder)
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

    // View injection
    $html = $this->view->inject_all(
      $html, $slug,
      $this->data->get_resolved_product_id(),
      $this->data->get_resolved_post_id()
    );

    // WooCommerce injection (if active)
    if (class_exists('WooCommerce')) {
      $html = (new WooCommerce_Injector($this, $this->events))->inject($html, $slug);
    }

    // Asset injection (CSS, JS, fonts, nonces, etc.)
    $html = $this->assets->inject_all($html, $slug, $is_customizer);

    // Response assembly
    $html = $this->builder->build($html, $template, $slug, $is_customizer, $this->router);

    return $html;
  }

}
