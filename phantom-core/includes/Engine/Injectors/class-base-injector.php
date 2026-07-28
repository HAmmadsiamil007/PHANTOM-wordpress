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

  /**
   * Migration Layer v1 — inner-content replacement by data-component attribute.
   *
   * Finds a container element with data-component="{$key}" and replaces only its
   * child content, preserving the container element, all its classes, data-*
   * attributes, and JS animation hooks (data-reveal, data-tilt, etc.).
   *
   * Future: Replace with Component_Renderer pipeline that renders directly into
   * template slots via the Component Registry, eliminating HTML manipulation.
   */
  protected function replace_inner_by_component(string $html, string $key, string $content): string {
    $attr = 'data-component="' . $key . '"';

    if (!preg_match('/data-component="' . preg_quote($key, '/') . '"/', $html, $m, PREG_OFFSET_CAPTURE)) {
      return $html;
    }

    $attr_pos = $m[0][1];

    $open_pos = $attr_pos;
    while ($open_pos > 0 && $html[$open_pos] !== '<') {
      $open_pos--;
    }
    if ($html[$open_pos] !== '<') {
      return $html;
    }

    $tag_end = strpos($html, '>', $open_pos);
    if ($tag_end === false) {
      return $html;
    }

    preg_match('/^<(\w+)/', substr($html, $open_pos, $tag_end - $open_pos + 1), $tn);
    $tag = $tn[1] ?? 'div';
    $close_tag = "</$tag>";
    $content_start = $tag_end + 1;

    $depth = 1;
    $pos = $content_start;
    $len = strlen($html);

    while ($depth > 0 && $pos < $len) {
      $next_open = strpos($html, "<$tag", $pos);
      $next_close = strpos($html, $close_tag, $pos);

      if ($next_close === false) {
        break;
      }

      if ($next_open !== false && $next_open < $next_close) {
        $char_after = $next_open + strlen("<$tag");
        if ($char_after < $len) {
          $ch = $html[$char_after];
          if ($ch === '>' || $ch === ' ' || $ch === "\n" || $ch === "\t" || $ch === '/' || $ch === "\r") {
            $depth++;
            $pos = $next_open + 1;
            continue;
          }
        }
        $pos = $next_open + 1;
      } else {
        $depth--;
        if ($depth === 0) {
          return substr($html, 0, $content_start) . "\n" . $content . "\n" . substr($html, $next_close);
        }
        $pos = $next_close + strlen($close_tag);
      }
    }

    return $html;
  }

  /**
   * @deprecated Migration Layer v1 — use replace_inner_by_component() instead.
   * Replaces entire element matched by class regex. Destroys data-* attributes
   * and JS hooks on the container. Kept for backward compatibility during
   * migration only.
   */
  protected function replace_section(string $html, string $class, string $replacement): string {
    return preg_replace(
      '/<' . $class . '[^>]*>.*?<\/' . $class . '>\s*/s',
      $replacement,
      $html,
      1
    );
  }
}
