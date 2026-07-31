<?php
declare(strict_types=1);

namespace PhantomCore\Engine;

use PhantomCore\Registry\Template_Registry;
use PhantomCore\Packs\Frontend_Pack_Registry;

defined('ABSPATH') || exit;

class Template_Loader {

  private string $pack = 'default';

  public function set_pack(string $pack): self {
    $this->pack = $pack;
    return $this;
  }

  public function get_pack(): string {
    return $this->pack;
  }

  public function pack_exists(string $pack): bool {
    return Frontend_Pack_Registry::get_instance()->has($pack);
  }

  public function get_pack_manifest(string $pack = ''): ?array {
    if ($pack === '') $pack = $this->pack;
    $pack_obj = Frontend_Pack_Registry::get_instance()->get($pack);
    return $pack_obj ? $pack_obj->to_manifest() : null;
  }

  public function get_pack_asset_urls(string $pack = ''): array {
    $pack_obj = Frontend_Pack_Registry::get_instance()->get($pack ?: $this->pack);
    if (null === $pack_obj) {
      return ['css' => [], 'js' => []];
    }
    return ['css' => $pack_obj->get_css_urls(), 'js' => $pack_obj->get_js_urls()];
  }

  public function resolve(string $slug): string {
    if (preg_match('/^product\/(.+)$/', $slug)) {
      return 'product-detail.html';
    }
    if (preg_match('/^blog\/(.+)$/', $slug)) {
      return 'single-blog.html';
    }
    if (preg_match('/^category\/(.+)$/', $slug)) {
      return 'shop.html';
    }
    if (preg_match('/^tag\/(.+)$/', $slug)) {
      return '404.html';
    }
    if (preg_match('/^search\/(.+)$/', $slug)) {
      return 'search.html';
    }
    if (preg_match('/^order\/(.+)$/', $slug)) {
      return 'order-detail.html';
    }
    if (preg_match('/^author\/(.+)$/', $slug)) {
      return '404.html';
    }

    // Use Template_Registry which handles direct routes + patterns
    $registry = Template_Registry::get_instance();
    $registry->register_defaults();
    $registry_file = $registry->resolve($slug);
    if ($registry_file !== '404.html') {
      return $registry_file;
    }

    $routes = [
      ''                => 'index.html',
      'index'           => 'index.html',
      'index.html'      => 'index.html',
      'shop'            => 'shop.html',
      'product'         => 'product-detail.html',
      'product-detail'  => 'product-detail.html',
      'about'           => 'about.html',
      'blog'            => 'blog.html',
      'post'            => 'single-blog.html',
      'single-blog'     => 'single-blog.html',
      'contact'         => 'contact.html',
      'cart'            => 'cart.html',
      'checkout'        => 'checkout.html',
      'my-account'      => 'account.html',
      'account'         => 'account.html',
      'orders'          => 'orders.html',
      'order-detail'    => 'order-detail.html',
      'search'          => 'search.html',
      'coming-soon'     => 'coming-soon.html',
      'faq'             => 'faq.html',
      'team'            => 'team.html',
      'testimonials'    => 'testimonials.html',
      'join-now'        => 'join-now.html',
      'login'           => 'login.html',
      'register'        => 'join-now.html',
      'thank-you'       => 'thank-you.html',
      'wishlist'        => 'wishlist.html',
      'privacy-policy'  => 'privacy-policy.html',
      'term-of-use'     => 'term-of-use.html',
      'cookie-policy'   => 'cookie-policy.html',
      '404'             => '404.html',
    ];

    return $routes[$slug]
      ?? $routes[preg_replace('/\.html$/', '', $slug)]
      ?? '404.html';
  }

  public function load(string $template): string {
    $path = $this->resolve_path($template);
    $content = file_get_contents($path);
    return $content !== false ? $content : '';
  }

  public function resolve_path(string $template): string {
    // Always use default AETHER template for homepage — pack overrides break the AETHER design
    if ($template === 'index.html' && $this->pack !== 'default') {
      $default_path = PHANTOM_CORE_PATH . 'frontend/html/' . $template;
      if (file_exists($default_path)) {
        return $default_path;
      }
    }

    if ($this->pack !== 'default') {
      $pack_path = PHANTOM_CORE_PATH . 'frontend/packs/' . $this->pack . '/html/' . $template;
      if (file_exists($pack_path)) {
        return $pack_path;
      }
    }

    $path = PHANTOM_CORE_PATH . 'frontend/html/' . $template;
    if (!file_exists($path)) {
      $path = PHANTOM_CORE_PATH . 'frontend/html/404.html';
    }
    return $path;
  }

  public function get_packs(): array {
    $packs = ['default' => 'Default'];
    foreach (Frontend_Pack_Registry::get_instance()->get_display_names() as $slug => $name) {
      $packs[$slug] = $name;
    }
    return $packs;
  }

  public function get_supported_templates(): array {
    $registry = Template_Registry::get_instance();
    $files = $registry->get_supported_templates();
    if (empty($files)) {
      $files = array_values(array_unique([
        'index.html','shop.html','product-detail.html','about.html','blog.html',
        'single-blog.html','contact.html','cart.html','checkout.html','account.html',
        'orders.html','order-detail.html','search.html',
        'coming-soon.html','faq.html','team.html','testimonials.html','join-now.html',
        'login.html','thank-you.html','wishlist.html','privacy-policy.html',
        'term-of-use.html','cookie-policy.html','404.html',
      ]));
    }
    return $files;
  }
}
