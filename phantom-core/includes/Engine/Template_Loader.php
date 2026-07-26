<?php
declare(strict_types=1);

namespace PhantomCore\Engine;

defined('ABSPATH') || exit;

class Template_Loader {

  private array $routes = [];
  private string $pack = 'kids';

  public function __construct() {
    $this->routes = [
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
      'category'        => '404.html',
      'tag'             => '404.html',
      'author'          => '404.html',
      'search'          => '404.html',
    ];
  }

  public function set_pack(string $pack): self {
    $this->pack = $pack;
    return $this;
  }

  public function get_pack(): string {
    return $this->pack;
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
      return '404.html';
    }
    if (preg_match('/^author\/(.+)$/', $slug)) {
      return '404.html';
    }

    return $this->routes[$slug]
      ?? $this->routes[preg_replace('/\.html$/', '', $slug)]
      ?? '404.html';
  }

  public function load(string $template): string {
    $path = $this->resolve_path($template);
    $content = file_get_contents($path);
    return $content !== false ? $content : '';
  }

  public function resolve_path(string $template): string {
    // Check template pack directory first
    if ($this->pack !== 'kids') {
      $pack_path = PHANTOM_CORE_PATH . 'frontend/templates/' . $this->pack . '/html/' . $template;
      if (file_exists($pack_path)) {
        return $pack_path;
      }
    }

    // Fall back to default templates
    $path = PHANTOM_CORE_PATH . 'frontend/html/' . $template;
    if (!file_exists($path)) {
      $path = PHANTOM_CORE_PATH . 'frontend/html/404.html';
    }
    return $path;
  }

  public function get_packs(): array {
    $packs = ['kids' => 'Kids Collection (Default)'];
    $dir = PHANTOM_CORE_PATH . 'frontend/templates/';
    if (is_dir($dir)) {
      foreach (scandir($dir) as $entry) {
        if ($entry !== '.' && $entry !== '..' && is_dir($dir . $entry) && $entry !== 'kids') {
          $packs[$entry] = ucwords(str_replace('-', ' ', $entry));
        }
      }
    }
    return $packs;
  }

  public function get_supported_templates(): array {
    return array_values(array_unique(array_filter($this->routes)));
  }
}
