<?php
declare(strict_types=1);
namespace PhantomCore\Bridges;
use PhantomCore\Contracts\BridgeInterface;
defined('ABSPATH') || exit;

class Bridge_Manager {
  private static ?Bridge_Manager $instance = null;
  private array $bridges = [];

  public static function get_instance(): self {
    if (null === self::$instance) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  public function register(BridgeInterface $bridge): void {
    $this->bridges[$bridge->get_id()] = $bridge;
  }

  public function get(string $id): ?BridgeInterface {
    return $this->bridges[$id] ?? null;
  }

  public function get_active(): array {
    return array_filter($this->bridges, fn($bridge) => $bridge->is_active());
  }

  public function get_all(): array {
    return $this->bridges;
  }

  public function init_all(): void {
    foreach ($this->get_active() as $bridge) {
      $bridge->init();
    }
  }

  public function is_bridge_active(string $id): bool {
    $bridge = $this->get($id);
    return null !== $bridge && $bridge->is_active();
  }
}
