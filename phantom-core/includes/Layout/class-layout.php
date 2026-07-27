<?php
declare(strict_types=1);

namespace PhantomCore\Layout;

defined('ABSPATH') || exit;

class Layout {
  public string $slug;
  public string $label;
  public int $columns;
  public array $breakpoints;
  public array $css_classes;
  public string $template_wrapper;
  public array $options;

  public function __construct(
    string $slug,
    string $label,
    int $columns = 1,
    array $breakpoints = [],
    array $css_classes = [],
    string $template_wrapper = '',
    array $options = []
  ) {
    $this->slug = $slug;
    $this->label = $label;
    $this->columns = $columns;
    $this->breakpoints = $breakpoints;
    $this->css_classes = $css_classes;
    $this->template_wrapper = $template_wrapper;
    $this->options = $options;
  }

  public function to_array(): array {
    return [
      'slug' => $this->slug,
      'label' => $this->label,
      'columns' => $this->columns,
      'breakpoints' => $this->breakpoints,
      'css_classes' => $this->css_classes,
      'template_wrapper' => $this->template_wrapper,
      'options' => $this->options,
    ];
  }
}
