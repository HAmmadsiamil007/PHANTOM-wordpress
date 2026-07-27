<?php
declare(strict_types=1);

namespace PhantomCore\Public;

use PhantomCore\Engine\Render_Engine;
use PhantomCore\Components\Component_Registry;

defined('ABSPATH') || exit;

class Render_API {
  private static ?Render_API $instance = null;
  private ?Render_Engine $engine = null;
  private ?Component_Registry $components = null;

  private function __construct() {}

  public static function get_instance(): self {
    if (null === self::$instance) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  private function engine(): Render_Engine {
    if (null === $this->engine) {
      $this->engine = Render_Engine::get_instance();
    }
    return $this->engine;
  }

  private function components(): Component_Registry {
    if (null === $this->components) {
      $this->components = Component_Registry::get_instance();
    }
    return $this->components;
  }

  public function render(string $slug, array $data = []): string {
    return $this->engine()->render($slug);
  }

  public function render_component(string $component_slug, array $props = []): string {
    return $this->components()->render($component_slug, $props);
  }

  public function get_rendered_output(): string {
    return $this->engine()->get_rendered_output();
  }
}
