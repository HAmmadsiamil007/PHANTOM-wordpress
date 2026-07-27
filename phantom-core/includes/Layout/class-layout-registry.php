<?php
declare(strict_types=1);

namespace PhantomCore\Layout;

defined('ABSPATH') || exit;

final class Layout_Registry {
  private static ?self $instance = null;
  private array $layouts = [];

  public static function get_instance(): self {
    if (null === self::$instance) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  public function register(Layout $layout): void {
    $this->layouts[$layout->slug] = $layout;
  }

  public function get(string $slug): ?Layout {
    return $this->layouts[$slug] ?? null;
  }

  public function has(string $slug): bool {
    return isset($this->layouts[$slug]);
  }

  public function get_all(): array {
    return $this->layouts;
  }

  public function get_by_columns(int $columns): array {
    return array_filter(
      $this->layouts,
      fn(Layout $layout): bool => $layout->columns === $columns
    );
  }
}
