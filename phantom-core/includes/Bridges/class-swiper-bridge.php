<?php
declare(strict_types=1);
namespace PhantomCore\Bridges;
use PhantomCore\Registry\Asset_Registry;
defined('ABSPATH') || exit;

class Swiper_Bridge extends Plugin_Bridge {
  private static ?self $instance = null;
  private bool $enqueued = false;

  public static function get_instance(): self {
    if (null === self::$instance) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  public function __construct() {
    $this->id = 'swiper';
    $this->label = 'Swiper';
  }

  public function is_active(): bool {
    $registry = Asset_Registry::get_instance();
    return $registry->has('swiper-css') && $registry->has('swiper-js');
  }

  public function get_supported_hooks(): array {
    return ['woocommerce_init', 'wp_enqueue_scripts'];
  }

  public function init(): void {
    if (!$this->is_active()) {
      return;
    }
    add_action('wp_enqueue_scripts', [$this, 'enqueue'], 25);
  }

  public function enqueue(): void {
    if ($this->enqueued) {
      return;
    }
    $this->enqueued = true;

    if (!is_product() && !$this->has_hero_slider()) {
      return;
    }

    wp_enqueue_style('swiper-css');
    wp_enqueue_script('swiper-js');

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

    wp_add_inline_script('swiper-js', 'window.PhantomSwiperConfig=' . wp_json_encode($config) . ';', 'before');
  }

  private function has_hero_slider(): bool {
    $hero_img = get_option('phantom_home_banner_img1', '');
    if (empty($hero_img)) {
      return false;
    }
    return true;
  }
}