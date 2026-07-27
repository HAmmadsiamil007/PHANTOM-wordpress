<?php
declare(strict_types=1);

namespace PhantomCore\Public;

use PhantomCore\Components\Component_Registry;

defined('ABSPATH') || exit;

class Component_API {
  private static ?Component_API $instance = null;
  private ?Component_Registry $registry = null;

  private function __construct() {}

  public static function get_instance(): self {
    if (null === self::$instance) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  private function registry(): Component_Registry {
    if (null === $this->registry) {
      $this->registry = Component_Registry::get_instance();
    }
    return $this->registry;
  }

  public function register(array $config): bool {
    if (!isset($config['name'])) {
      return false;
    }
    $this->registry()->register_from_array($config);
    return $this->registry()->has($config['name']);
  }

  public function get(string $slug): ?array {
    $component = $this->registry()->get($slug);
    if (null === $component) {
      return null;
    }
    return [
      'name' => $component->name,
      'label' => $component->label,
      'category' => $component->category,
      'class_name' => $component->class_name,
      'version' => $component->version ?? '',
      'author' => $component->author ?? '',
      'description' => $component->description ?? '',
    ];
  }

  public function get_all(): array {
    $components = $this->registry()->get_all();
    $result = [];
    foreach ($components as $slug => $component) {
      $result[$slug] = [
        'name' => $component->name,
        'label' => $component->label,
        'category' => $component->category,
      ];
    }
    return $result;
  }

  public function is_registered(string $slug): bool {
    return $this->registry()->has($slug);
  }
}
