<?php
declare(strict_types=1);

namespace PhantomCore\Engine;

defined('ABSPATH') || exit;

class Render_Engine {

  private Template_Loader $template_loader;
  private SEO_Engine $seo;
  private Security_Headers $security;
  private Asset_Loader $assets;
  private ?int $resolved_product_id = null;
  private ?int $resolved_post_id = null;
  private ?string $category_slug = null;

	public function __construct() {
		$this->template_loader = new Template_Loader();
		$this->seo = new SEO_Engine();
		$this->security = new Security_Headers();
		$this->assets = new Asset_Loader();

		$pack = 'kids';
		if (class_exists('\PhantomCore\Settings_Registry')) {
			$registry = \PhantomCore\Settings_Registry::get_instance();
			if ($registry->has('template_pack')) {
				$pack = $registry->get('template_pack');
			}
		}
		$this->template_loader->set_pack($pack);
	}

  public function with_product_id(int $id): self {
    $this->resolved_product_id = $id;
    return $this;
  }

  public function with_post_id(int $id): self {
    $this->resolved_post_id = $id;
    return $this;
  }

  public function with_category(string $slug): self {
    $this->category_slug = $slug;
    return $this;
  }

  public function get_resolved_product_id(): ?int {
    return $this->resolved_product_id;
  }

  public function get_resolved_post_id(): ?int {
    return $this->resolved_post_id;
  }

  public function get_category_slug(): ?string {
    return $this->category_slug;
  }

  public function render(string $slug): string {
    $is_customizer_preview = isset($_GET['customize_changeset_uuid']);
    $template = $this->template_loader->resolve($slug);

    status_header($template === '404.html' ? 404 : 200);

    $html = $this->template_loader->load($template);
    if (empty($html)) {
      status_header(404);
      return '';
    }

    // Dark mode from cookie
    if (!empty($_COOKIE['phantom_dark_mode']) && '1' === $_COOKIE['phantom_dark_mode']) {
      $html = preg_replace('/<body(\s[^>]*)?>/', '<body$1 data-phantom-dark-mode="true">', $html, 1);
    }

    // Skip link
    $html = preg_replace(
      '/<body(\s[^>]*)?>/',
      '<body$1>' . "\n" . '<a class="skip-link screen-reader-text" href="#phantom-main-content">Skip to main content</a>',
      $html,
      1
    );

    // Loading state
    $loading = '<div id="phantom-loading" role="status" aria-hidden="true" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:var(--bg-color,#fff);z-index:9999;align-items:center;justify-content:center;transition:opacity .3s"><div style="width:40px;height:40px;border:3px solid var(--border-color,#e5e7eb);border-top-color:var(--accent-color,#6366f1);border-radius:50%;animation:phantom-spin .8s linear infinite"></div></div>';
    $html = preg_replace('/<body[^>]*>/', '$0' . "\n" . $loading, $html, 1);

    // ARIA roles
    $html = preg_replace('/<header([^>]*)>/i', '<header$1 role="banner">', $html, 1);
    if (!preg_match('/<main[^>]*role=/i', $html)) {
      $html = preg_replace('/<main([^>]*)>/i', '<main$1 role="main">', $html, 1);
    }
    $html = preg_replace('/<footer([^>]*)>(?!.*role=)/i', '<footer$1 role="contentinfo">', $html, 1);

    $html = str_replace('</head>', '<style id="phantom-loading-css">@keyframes phantom-spin{to{transform:rotate(360deg)}}</style></head>', $html);

    // Inject WC content
    if (class_exists('WooCommerce')) {
      $html = $this->inject_woocommerce_content($html, $slug, $template);
    }

    // SEO
    $this->seo->with_product_id($this->resolved_product_id ?? 0)
              ->with_post_id($this->resolved_post_id ?? 0);
    $html = $this->seo->inject($html, $slug);

    // Assets
    if (!$is_customizer_preview) {
      $html = $this->assets->inject_all($html, $slug, $is_customizer_preview);
    }

    // Customizer CSS
    $html = $this->inject_customizer_css($html);

    // Bridge data
    $html = $this->inject_bridge($html);

    // Auth nonces
    $html = $this->inject_auth_nonces($html);

    // Plugin hooks
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

    // Customizer preview
    if ($is_customizer_preview) {
      $html = preg_replace('/<script[^>]*src="[^"]*jquery(?:-\d[\w.]*)?(?:\.min)?\.js[^"]*"[^>]*><\/script>/i', '', $html);
      ob_start();
      wp_head();
      $html = str_replace('</head>', ob_get_clean() . '</head>', $html);
      ob_start();
      wp_footer();
      $html = str_replace('</body>', ob_get_clean() . '</body>', $html);
    }

    // Security headers
    $this->security->send($is_customizer_preview);

    // Plugin output hook
    ob_start();
    do_action('phantom_before_output', $template, $slug);
    $html = ob_get_clean() . $html;

    return $html;
  }

  private function inject_customizer_css(string $html): string {
    $all_css = \Phantom_Custom_CSS::instance()->render_style();
    if ('' === $all_css) return $html;
    return str_replace('</head>', $all_css . '</head>', $html);
  }

  private function inject_bridge(string $html): string {
    $data = [
      'rest_url' => rest_url('phantom/v1'),
      'home_url' => home_url('/'),
      'nonce' => wp_create_nonce('wp_rest'),
      'api_nonce' => wp_create_nonce('phantom_api'),
      'ajax_url' => admin_url('admin-ajax.php'),
      'wc_ajax_url' => class_exists('WooCommerce')
        ? \WC_AJAX::get_endpoint('%%endpoint%%') : '',
      'currency_symbol' => function_exists('get_woocommerce_currency_symbol')
        ? get_woocommerce_currency_symbol() : '$',
      'user_logged_in' => is_user_logged_in(),
      'routes' => [
        'shop' => home_url('/shop'),
        'cart' => home_url('/cart'),
        'checkout' => home_url('/checkout'),
        'account' => home_url('/account'),
      ],
      'theme' => [
        'name' => wp_get_theme()->get('Name') ?: 'Phantom Theme',
        'version' => wp_get_theme()->get('Version') ?: '1.0',
      ],
    ];

    if ($this->resolved_product_id) {
      $data['product_id'] = $this->resolved_product_id;
    }
    if ($this->resolved_post_id) {
      $data['post_id'] = $this->resolved_post_id;
    }
    if ($this->category_slug) {
      $data['category'] = $this->category_slug;
    }

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
    $nonces = [
      'wp_rest' => wp_create_nonce('wp_rest'),
      'phantom_api' => wp_create_nonce('phantom_api'),
      'woocommerce_cart' => wp_create_nonce('woocommerce-cart'),
    ];
    $json = wp_json_encode($nonces);
    $script = '<script id="phantom-nonces">window.PhantomNonces=' . $json . ';</script>';
    return str_replace('</body>', $script . '</body>', $html);
  }

  private function inject_woocommerce_content(string $html, string $slug, string $template): string {
    if (!class_exists('WooCommerce')) return $html;

    $wc_injector = new WooCommerce_Injector($this);
    return $wc_injector->inject($html, $slug);
  }

}
