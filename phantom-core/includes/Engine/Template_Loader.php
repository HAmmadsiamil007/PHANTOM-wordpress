<?php
declare(strict_types=1);

namespace PhantomCore\Engine;

use PhantomCore\Registry\Template_Registry;

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
    $dir = PHANTOM_CORE_PATH . 'frontend/packs/' . $pack;
    return is_dir($dir) && file_exists($dir . '/manifest.json');
  }

  public function get_pack_manifest(string $pack = ''): ?array {
    if ($pack === '') $pack = $this->pack;
    if ($pack === 'default') return null;
    $file = PHANTOM_CORE_PATH . 'frontend/packs/' . $pack . '/manifest.json';
    if (!file_exists($file)) return null;
    $json = json_decode((string) file_get_contents($file), true);
    return is_array($json) ? $json : null;
  }

  public function get_pack_asset_urls(string $pack = ''): array {
    $manifest = $this->get_pack_manifest($pack ?: $this->pack);
    if (!$manifest || !isset($manifest['assets'])) return ['css' => [], 'js' => []];
    $base = function_exists('content_url')
      ? content_url() . '/plugins/phantom-core/'
      : PHANTOM_CORE_URL . 'frontend/packs/';
    $css = [];
    $js = [];
    foreach ($manifest['assets']['css'] ?? [] as $rel) {
      $css[] = $base . $rel;
    }
    foreach ($manifest['assets']['js'] ?? [] as $rel) {
      $js[] = $base . $rel;
    }
    return ['css' => $css, 'js' => $js];
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

    $registry = Template_Registry::get_instance();
    if ($registry->has($slug)) {
      $template = $registry->get($slug);
      if ($template) return $template->file;
    }

    $without_ext = preg_replace('/\.html$/', '', $slug);
    if ($without_ext !== $slug && $registry->has($without_ext)) {
      $template = $registry->get($without_ext);
      if ($template) return $template->file;
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
    $dir = PHANTOM_CORE_PATH . 'frontend/packs/';
    if (is_dir($dir)) {
      foreach (scandir($dir) as $entry) {
        if ($entry !== '.' && $entry !== '..' && is_dir($dir . $entry)) {
          $packs[$entry] = ucwords(str_replace('-', ' ', $entry));
        }
      }
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
        'coming-soon.html','faq.html','team.html','testimonials.html','join-now.html',
        'login.html','thank-you.html','wishlist.html','privacy-policy.html',
        'term-of-use.html','cookie-policy.html','404.html',
      ]));
    }
    return $files;
  }
}
