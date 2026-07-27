<?php
declare(strict_types=1);

namespace PhantomCore\Animation;

defined('ABSPATH') || exit;

class Splitting_Bridge {
  private static ?Splitting_Bridge $instance = null;
  private bool $enqueued = false;

  public static function get_instance(): self {
    if (self::$instance === null) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  public function enqueue(): void {
    if ($this->enqueued) {
      return;
    }
    wp_enqueue_script(
      'splitting',
      '//unpkg.com/splitting@1.1.0/dist/splitting.min.js',
      [],
      '1.1.0',
      true
    );
    wp_enqueue_style(
      'splitting',
      '//unpkg.com/splitting@1.1.0/dist/splitting.css',
      [],
      '1.1.0'
    );
    $this->enqueued = true;
  }

  public function is_enqueued(): bool {
    return $this->enqueued;
  }
}
