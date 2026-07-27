<?php
declare(strict_types=1);

namespace PhantomCore\Layout;

defined('ABSPATH') || exit;

final class Layout_Manager {
  public static function init(): void {
    $registry = Layout_Registry::get_instance();

    $registry->register(new Layout(
      slug: 'full-width',
      label: 'Full Width',
      columns: 1,
      css_classes: ['container', 'row'],
      template_wrapper: 'full-width',
      options: ['content_width' => '100%'],
    ));

    $registry->register(new Layout(
      slug: 'two-column',
      label: 'Two Column',
      columns: 2,
      breakpoints: ['md' => 768],
      css_classes: ['container', 'row', 'col-md-6'],
      template_wrapper: 'two-column',
      options: ['content_width' => '50%'],
    ));

    $registry->register(new Layout(
      slug: 'three-column',
      label: 'Three Column',
      columns: 3,
      breakpoints: ['md' => 768],
      css_classes: ['container', 'row', 'col-md-4'],
      template_wrapper: 'three-column',
      options: ['content_width' => '33.33%'],
    ));

    $registry->register(new Layout(
      slug: 'four-column',
      label: 'Four Column',
      columns: 4,
      breakpoints: ['md' => 768, 'lg' => 992],
      css_classes: ['container', 'row', 'col-lg-3', 'col-md-6'],
      template_wrapper: 'four-column',
      options: ['content_width' => '25%'],
    ));

    $registry->register(new Layout(
      slug: 'six-column',
      label: 'Six Column',
      columns: 6,
      breakpoints: ['md' => 768, 'lg' => 992],
      css_classes: ['container', 'row', 'col-lg-2', 'col-md-4'],
      template_wrapper: 'six-column',
      options: ['content_width' => '16.66%'],
    ));

    $registry->register(new Layout(
      slug: 'sidebar-left',
      label: 'Sidebar Left',
      columns: 2,
      breakpoints: ['lg' => 992],
      css_classes: ['container', 'row', 'col-lg-8', 'col-lg-4'],
      template_wrapper: 'sidebar-left',
      options: ['content_width' => '66.67%', 'sidebar_width' => '33.33%', 'sidebar_position' => 'left'],
    ));

    $registry->register(new Layout(
      slug: 'sidebar-right',
      label: 'Sidebar Right',
      columns: 2,
      breakpoints: ['lg' => 992],
      css_classes: ['container', 'row', 'col-lg-8', 'col-lg-4'],
      template_wrapper: 'sidebar-right',
      options: ['content_width' => '66.67%', 'sidebar_width' => '33.33%', 'sidebar_position' => 'right'],
    ));
  }
}
