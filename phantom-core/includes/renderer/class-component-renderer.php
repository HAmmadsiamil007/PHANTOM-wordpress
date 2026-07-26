<?php
declare(strict_types=1);

namespace PhantomCore\Renderer;

use PhantomCore\Contracts\RendererInterface;

defined('ABSPATH') || exit;

abstract class Component_Renderer implements RendererInterface {

  abstract public function render(array $data): string;

  public function render_collection(array $data_set): string {
    $output = '';
    foreach ($data_set as $data) {
      $output .= $this->render($data);
    }
    return $output;
  }

  protected function load_template(string $name): string {
    $path = PHANTOM_CORE_PATH . 'frontend/html/components/' . $name . '.html';
    if (!file_exists($path)) return '';
    return (string) file_get_contents($path);
  }

  protected function inject(string $template, array $data): string {
    return preg_replace_callback('/\{\{(\w+)\}\}/', function($m) use ($data) {
      $key = strtolower($m[1]);
      return isset($data[$key]) ? $data[$key] : $m[0];
    }, $template);
  }
}
