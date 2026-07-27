<?php
declare(strict_types=1);

namespace PhantomCore\Public;

use PhantomCore\Hook\Hook_Registry;
use PhantomCore\Feature\Feature_Registry;
use PhantomCore\Registry\Asset_Registry;

defined('ABSPATH') || exit;

class Developer_API {
  private static ?Developer_API $instance = null;
  private ?Hook_Registry $hooks = null;
  private ?Feature_Registry $features = null;
  private ?Asset_Registry $assets = null;

  private function __construct() {}

  public static function get_instance(): self {
    if (null === self::$instance) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  private function hooks(): Hook_Registry {
    if (null === $this->hooks) {
      $this->hooks = Hook_Registry::get_instance();
    }
    return $this->hooks;
  }

  private function features(): Feature_Registry {
    if (null === $this->features) {
      $this->features = Feature_Registry::get_instance();
    }
    return $this->features;
  }

  private function assets(): Asset_Registry {
    if (null === $this->assets) {
      $this->assets = Asset_Registry::get_instance();
    }
    return $this->assets;
  }

  public function get_hooks(): array {
    return $this->hooks()->get_all();
  }

  public function get_features(): array {
    return $this->features()->get_all();
  }

  public function get_registered_assets(): array {
    return $this->assets()->get_all();
  }

  public function get_cache_stats(): array {
    return [
      'time' => time(),
      'memory' => memory_get_usage(true),
      'peak_memory' => memory_get_peak_usage(true),
    ];
  }

  public function is_dev_mode(): bool {
    return defined('WP_DEBUG') && WP_DEBUG;
  }
}
