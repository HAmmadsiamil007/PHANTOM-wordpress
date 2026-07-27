<?php
declare(strict_types=1);

namespace PhantomCore\Public;

use PhantomCore\Settings_Registry;

defined('ABSPATH') || exit;

class Settings_API {
  private static ?Settings_API $instance = null;
  private ?Settings_Registry $registry = null;

  private function __construct() {}

  public static function get_instance(): self {
    if (null === self::$instance) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  private function registry(): Settings_Registry {
    if (null === $this->registry) {
      $this->registry = Settings_Registry::get_instance();
    }
    return $this->registry;
  }

  public function get(string $key, mixed $default = null): mixed {
    $value = $this->registry()->get($key);
    return null !== $value ? $value : $default;
  }

  public function set(string $key, mixed $value): bool {
    return $this->registry()->set($key, $value);
  }

  public function get_section(string $section): array {
    return $this->registry()->get_by_section($section);
  }

  public function get_all(): array {
    return $this->registry()->get_all();
  }
}
