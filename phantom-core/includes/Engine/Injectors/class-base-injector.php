<?php
declare(strict_types=1);

namespace PhantomCore\Engine\Injectors;

use PhantomCore\Components\Component_Registry;
use PhantomCore\Engine\Render_Engine;

defined('ABSPATH') || exit;

abstract class Base_Injector {
  protected Render_Engine $engine;

  public function __construct(Render_Engine $engine) {
    $this->engine = $engine;
  }

  abstract public function inject(string $html): string;

  protected function get_renderer(string $key): ?object {
    $component = Component_Registry::get_instance()->get($key);
    return $component ? $component->instance() : null;
  }

  protected function replace_section(string $html, string $class, string $replacement): string {
    return preg_replace(
      '/<' . $class . '[^>]*>.*?<\/' . $class . '>\s*/s',
      $replacement,
      $html,
      1
    );
  }
}
