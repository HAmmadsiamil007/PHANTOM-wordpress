<?php
declare(strict_types=1);
namespace PhantomCore\Contracts;
defined('ABSPATH') || exit;

interface BridgeInterface {
  public function get_id(): string;
  public function get_label(): string;
  public function is_active(): bool;
  public function get_supported_hooks(): array;
}
