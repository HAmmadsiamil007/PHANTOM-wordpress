<?php
declare(strict_types=1);

namespace PhantomCore\Registry;

defined('ABSPATH') || exit;

class Asset_Registry {
  private static ?Asset_Registry $instance = null;
  private array $assets = [];
  private array $groups = [];

  public static function get_instance(): self {
    if (null === self::$instance) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  public function register(string $handle, array $attrs): void {
    $this->assets[$handle] = array_merge([
      'src'     => '',
      'deps'    => [],
      'version' => PHANTOM_CORE_VERSION,
      'type'    => 'js',
      'condition' => '',
      'lazy'    => false,
      'media'   => 'all',
      'footer'  => true,
    ], $attrs);
  }

  public function deregister(string $handle): void {
    unset($this->assets[$handle]);
  }

  public function get(string $handle): ?array {
    return $this->assets[$handle] ?? null;
  }

  public function has(string $handle): bool {
    return isset($this->assets[$handle]);
  }

  public function get_all(?string $type = null): array {
    if (null === $type) {
      return $this->assets;
    }
    return array_filter(
      $this->assets,
      fn(array $a) => ($a['type'] ?? 'js') === $type
    );
  }

  public function get_by_group(string $group): array {
    $handles = $this->groups[$group] ?? [];
    $result = [];
    foreach ($handles as $handle) {
      if (isset($this->assets[$handle])) {
        $result[$handle] = $this->assets[$handle];
      }
    }
    return $result;
  }

  public function add_to_group(string $handle, string $group): void {
    if (!isset($this->assets[$handle])) {
      return;
    }
    $this->groups[$group][] = $handle;
    $this->groups[$group] = array_unique($this->groups[$group]);
  }

  public function enqueue(string $handle): void {
    $asset = $this->assets[$handle] ?? null;
    if (null === $asset) {
      return;
    }

    if (!empty($asset['condition']) && !$this->evaluate_condition($asset['condition'])) {
      return;
    }

    $type = $asset['type'] ?? 'js';

    if ('css' === $type) {
      wp_enqueue_style(
        $handle,
        $asset['src'],
        $asset['deps'],
        $asset['version'],
        $asset['media']
      );
    } else {
      wp_enqueue_script(
        $handle,
        $asset['src'],
        $asset['deps'],
        $asset['version'],
        $asset['footer']
      );
    }
  }

  public function enqueue_group(string $group): void {
    $handles = $this->groups[$group] ?? [];
    foreach ($handles as $handle) {
      $this->enqueue($handle);
    }
  }

  private function evaluate_condition(string $condition): bool {
    if ('is_front_page' === $condition) {
      return is_front_page();
    }
    if ('is_singular' === $condition) {
      return is_singular();
    }
    if ('is_product' === $condition) {
      return function_exists('is_product') && is_product();
    }
    if ('is_shop' === $condition) {
      return function_exists('is_shop') && is_shop();
    }
    if ('is_cart' === $condition) {
      return function_exists('is_cart') && is_cart();
    }
    if ('is_checkout' === $condition) {
      return function_exists('is_checkout') && is_checkout();
    }
    if ('is_account_page' === $condition) {
      return function_exists('is_account_page') && is_account_page();
    }
    if ('is_admin' === $condition) {
      return is_admin();
    }
    return true;
  }

  public function register_defaults(): void {
    $ver = PHANTOM_CORE_VERSION;
    $url = PHANTOM_CORE_URL;

    // Bootstrap 5
    $this->register('bootstrap-css', [
      'src'    => '//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
      'type'   => 'css',
      'footer' => false,
    ]);
    $this->register('bootstrap-js', [
      'src'  => '//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
      'type' => 'js',
    ]);

    // GSAP
    $this->register('gsap', [
      'src'  => '//cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js',
      'type' => 'js',
    ]);

    // Swiper
    $this->register('swiper-css', [
      'src'    => '//cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css',
      'type'   => 'css',
      'footer' => false,
    ]);
    $this->register('swiper-js', [
      'src'  => '//cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js',
      'type' => 'js',
    ]);

    // Lenis
    $this->register('lenis', [
      'src'  => '//unpkg.com/lenis@1.1.18/dist/lenis.min.js',
      'type' => 'js',
    ]);

    // Splitting.js
    $this->register('splitting-css', [
      'src'    => '//unpkg.com/splitting@1.1.0/dist/splitting.css',
      'type'   => 'css',
      'footer' => false,
    ]);
    $this->register('splitting-js', [
      'src'  => '//unpkg.com/splitting@1.1.0/dist/splitting.min.js',
      'type' => 'js',
    ]);

    // Lottie
    $this->register('lottie', [
      'src'  => '//cdnjs.cloudflare.com/ajax/libs/lottie-web/5.12.2/lottie.min.js',
      'type' => 'js',
    ]);

    // Three.js
    $this->register('three-js', [
      'src'  => '//cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js',
      'type' => 'js',
    ]);

    // Theme CSS
    $theme_css = [
      'phantom-theme-style'      => 'frontend/assets/css/style.css',
      'phantom-theme-responsive'  => 'frontend/assets/css/responsive.css',
      'phantom-theme-motion'      => 'frontend/assets/css/motion.css',
      'phantom-theme-a11y'        => 'frontend/assets/css/a11y.css',
      'phantom-animate'           => 'frontend/assets/css/vendor/animate.css',
      'phantom-blog-css'          => 'frontend/assets/css/vendor/blog.css',
      'phantom-shop-css'          => 'frontend/assets/css/vendor/shop.css',
    ];
    foreach ($theme_css as $handle => $path) {
      $this->register($handle, [
        'src'  => $url . $path,
        'type' => 'css',
        'footer' => false,
      ]);
    }

    // Theme JS
		$theme_js = [
	       'phantom-main-js'           => 'frontend/assets/js/main.js',
	       'phantom-animations'        => 'frontend/assets/js/animations.js',
	       'phantom-effects'           => 'frontend/assets/js/effects.js',
	       'phantom-phantom-data'      => 'frontend/assets/js/phantom-data.js',
	       'phantom-contact-form'      => 'frontend/assets/js/contact-form.js',
	       'phantom-carousel'          => 'frontend/assets/js/carousel.js',
	       'phantom-counter'           => 'frontend/assets/js/counter.js',
	       'phantom-search'            => 'frontend/assets/js/search.js',
	       'phantom-lenis-scroll'      => 'frontend/assets/js/lenis-scroll.js',
	       'phantom-three-scenes'      => 'frontend/assets/js/three-scenes.js',
	       'phantom-video-popup'       => 'frontend/assets/js/video-popup.js',
	       'phantom-video-section'     => 'frontend/assets/js/video-section.js',
	       'phantom-filter-button'     => 'frontend/assets/js/filter-button.js',
	       'phantom-loadmore'          => 'frontend/assets/js/loadmore.js',
	       'phantom-back-to-top'       => 'frontend/assets/js/back-to-top-button.js',
	       'phantom-preloader'         => 'frontend/assets/js/preloader.js',
	       'phantom-dark-mode'         => 'frontend/assets/js/phantom-dark-mode.js',
	    ];
    foreach ($theme_js as $handle => $path) {
      $this->register($handle, [
        'src'  => $url . $path,
        'type' => 'js',
      ]);
    }

    // Groups
    $this->add_to_group('bootstrap-css', 'core');
    $this->add_to_group('bootstrap-js', 'core');
    $this->add_to_group('phantom-theme-style', 'core');
    $this->add_to_group('phantom-main-js', 'core');

	    $this->add_to_group('gsap', 'animation');
	    $this->add_to_group('lenis', 'animation');
	    $this->add_to_group('phantom-animations', 'animation');
	    $this->add_to_group('phantom-effects', 'animation');
	    $this->add_to_group('phantom-preloader', 'animation');
	    $this->add_to_group('phantom-dark-mode', 'animation');
	    $this->add_to_group('lottie', 'animation');

    $this->add_to_group('swiper-css', 'gallery');
    $this->add_to_group('swiper-js', 'gallery');

    $this->add_to_group('three-js', '3d');
    $this->add_to_group('phantom-three-scenes', '3d');
  }
}