<?php
declare(strict_types=1);

namespace PhantomCore\Public;

defined('ABSPATH') || exit;

class Animation_API {
  private static ?Animation_API $instance = null;
  private array $animations = [];

  private function __construct() {}

  public static function get_instance(): self {
    if (null === self::$instance) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  public function register_animation(array $config): bool {
    if (!isset($config['slug'])) {
      return false;
    }
    $this->animations[$config['slug']] = array_merge([
      'name' => '',
      'type' => 'css',
      'duration' => 0.3,
      'easing' => 'ease',
      'enqueued' => false,
    ], $config);
    return true;
  }

  public function get_animation(string $slug): ?array {
    return $this->animations[$slug] ?? null;
  }

  public function get_all(): array {
    return $this->animations;
  }

  public function enqueue(string $slug): bool {
    if (!isset($this->animations[$slug])) {
      return false;
    }
    $this->animations[$slug]['enqueued'] = true;
    return true;
  }
}
