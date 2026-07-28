<?php
declare(strict_types=1);

namespace PhantomCore\Components;

defined('ABSPATH') || exit;

class Component_Metadata {

  private static ?self $instance = null;

  private array $component_templates = [];
  private array $component_assets = [];

  private function __construct() {
    $this->register_defaults();
  }

  public static function get_instance(): self {
    if (self::$instance === null) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  private function register_defaults(): void {
    $this->component_templates = [
      'product-card'  => ['product-card.php', 'product-card-vertical.php'],
      'post-card'     => ['post-card.php', 'post-card-featured.php'],
      'blog-card'     => ['blog-card.php'],
      'search-card'   => ['search-card.php'],
      'cart-item'     => ['cart-item.php'],
      'checkout-item' => ['checkout-item.php'],
      'category-card' => ['category-card.php', 'category-card-grid.php'],
      'order-card'    => ['order-card.php'],
      'comment-card'  => ['comment-card.php'],
      'account-detail'=> ['account-detail.php'],
      'hero'          => ['hero.php', 'hero-responsive.php'],
      'footer'        => ['footer.php', 'footer-minimal.php'],
      'nav-menu'      => ['nav-menu.php', 'nav-menu-mobile.php'],
      'address-card'  => ['address-card.php'],
      'address-form'  => ['address-form.php'],
      'account-form'  => ['account-form.php'],
      'checkout-form' => ['checkout-form.php'],
      'order-table'   => ['order-table.php'],
      'post-content'  => ['post-content.php'],
      'contact-form'  => ['contact-form.php'],
    ];

    $this->component_assets = [
      'product-card'  => ['product-card.css', 'product-card.js'],
      'post-card'     => ['post-card.css', 'post-card.js'],
      'blog-card'     => ['blog-card.css', 'blog-card.js'],
      'search-card'   => ['search-card.css'],
      'cart-item'     => ['cart-item.css', 'cart.js'],
      'checkout-item' => ['checkout-item.css', 'checkout.js'],
      'category-card' => ['category-card.css', 'category-card.js'],
      'order-card'    => ['order-card.css'],
      'comment-card'  => ['comment-card.css'],
      'account-detail'=> ['account-detail.css', 'account.js'],
      'hero'          => ['hero.css', 'hero.js'],
      'footer'        => ['footer.css'],
      'nav-menu'      => ['nav-menu.js'],
      'address-card'  => ['address-card.css'],
      'address-form'  => ['address-form.js'],
      'account-form'  => ['account-form.js', 'account.js'],
      'checkout-form' => ['checkout-form.js', 'checkout.js'],
      'order-table'   => ['order-table.css'],
      'post-content'  => ['post-content.css'],
      'contact-form'  => ['contact-form.js'],
    ];
  }

  public function get_templates(string $component_slug): array {
    return $this->component_templates[$component_slug] ?? [];
  }

  public function get_assets(string $component_slug): array {
    return $this->component_assets[$component_slug] ?? [];
  }

  public function has_template(string $component_slug, string $template_name): bool {
    return in_array($template_name, $this->component_templates[$component_slug] ?? [], true);
  }

  public function has_asset(string $component_slug, string $asset_name): bool {
    return in_array($asset_name, $this->component_assets[$component_slug] ?? [], true);
  }

  public function register_template(string $component_slug, string $template_name): void {
    if (!isset($this->component_templates[$component_slug])) {
      $this->component_templates[$component_slug] = [];
    }
    if (!in_array($template_name, $this->component_templates[$component_slug], true)) {
      $this->component_templates[$component_slug][] = $template_name;
    }
  }

  public function register_asset(string $component_slug, string $asset_name): void {
    if (!isset($this->component_assets[$component_slug])) {
      $this->component_assets[$component_slug] = [];
    }
    if (!in_array($asset_name, $this->component_assets[$component_slug], true)) {
      $this->component_assets[$component_slug][] = $asset_name;
    }
  }

  public function get_all_components(): array {
    return array_keys($this->component_templates);
  }
}