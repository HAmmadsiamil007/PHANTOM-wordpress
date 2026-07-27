<?php
declare(strict_types=1);

namespace PhantomCore\Public;

use PhantomCore\Registry\Template_Registry;
use PhantomCore\Layout\Layout_Registry;

defined('ABSPATH') || exit;

class Template_API {
  private static ?Template_API $instance = null;
  private ?Template_Registry $templates = null;
  private ?Layout_Registry $layouts = null;

  private function __construct() {}

  public static function get_instance(): self {
    if (null === self::$instance) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  private function templates(): Template_Registry {
    if (null === $this->templates) {
      $this->templates = Template_Registry::get_instance();
    }
    return $this->templates;
  }

  private function layouts(): Layout_Registry {
    if (null === $this->layouts) {
      $this->layouts = Layout_Registry::get_instance();
    }
    return $this->layouts;
  }

  public function get_template(string $slug): ?array {
    $template = $this->templates()->get($slug);
    if (null === $template) {
      return null;
    }
    return [
      'slug' => $template->slug,
      'file' => $template->file,
      'label' => $template->label,
      'category' => $template->category,
      'pack' => $template->pack,
    ];
  }

  public function get_templates(?string $category = null): array {
    $routes = $this->templates()->get_all($category);
    $result = [];
    foreach ($routes as $slug => $template) {
      $result[$slug] = [
        'slug' => $template->slug,
        'file' => $template->file,
        'label' => $template->label,
        'category' => $template->category,
      ];
    }
    return $result;
  }

  public function get_layout(string $slug): ?array {
    $layout = $this->layouts()->get($slug);
    if (null === $layout) {
      return null;
    }
    return [
      'slug' => $layout->slug,
      'name' => $layout->name,
      'columns' => $layout->columns,
    ];
  }

  public function get_available_layouts(): array {
    $layouts = $this->layouts()->get_all();
    $result = [];
    foreach ($layouts as $slug => $layout) {
      $result[$slug] = [
        'slug' => $layout->slug,
        'name' => $layout->name,
        'columns' => $layout->columns,
      ];
    }
    return $result;
  }
}
