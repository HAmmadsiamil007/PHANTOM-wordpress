<?php
declare(strict_types=1);
namespace PhantomCore\Bridges;
use PhantomCore\Contracts\BridgeInterface;
defined('ABSPATH') || exit;

abstract class Plugin_Bridge implements BridgeInterface {
  protected string $id;
  protected string $label;
  protected array $supported_hooks = [];

  public function get_id(): string {
    return $this->id;
  }

  public function get_label(): string {
    return $this->label;
  }

  public function get_supported_hooks(): array {
    return $this->supported_hooks;
  }

  abstract public function is_active(): bool;

  abstract public function init(): void;
}
