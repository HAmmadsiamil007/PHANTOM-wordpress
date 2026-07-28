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

  protected function through_view_model(string $class, array $data): array {
    if (class_exists($class) && is_subclass_of($class, '\\PhantomCore\\Contracts\\ViewModelInterface')) {
      $vm = $class::from_adapter_output($data);
      return $vm->to_array();
    }
    return $data;
  }

  protected function load_template(string $name): string {
    $pack = get_option('phantom_template_pack', 'default');
    if ($pack !== 'default') {
      $pack_path = PHANTOM_CORE_PATH . 'frontend/packs/' . $pack . '/html/components/' . $name . '.html';
      if (file_exists($pack_path)) return (string) file_get_contents($pack_path);
    }
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
