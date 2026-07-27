<?php
declare(strict_types=1);

defined('ABSPATH') || exit;

class Bootstrap_Nav_Walker extends Walker_Nav_Menu {

  public function start_lvl(&$output, $depth = 0, $args = null): void {
    $indent = str_repeat("\t", $depth);
    $classes = ['dropdown-menu'];
    if (0 === $depth) {
      $classes[] = 'dropdown-menu-end';
    }
    $class_names = implode(' ', $classes);
    $output .= "\n$indent<ul class=\"$class_names\">\n";
  }

  public function start_el(&$output, $item, $depth = 0, $args = null, $id = 0): void {
    $indent = ($depth) ? str_repeat("\t", $depth) : '';

    $classes = empty($item->classes) ? [] : (array) $item->classes;
    $classes[] = 'menu-item-' . $item->ID;

    $has_children = in_array('menu-item-has-children', $classes, true);

    if ($has_children) {
      $classes[] = 'nav-item';
      $classes[] = 'dropdown';
    } else {
      $classes[] = 'nav-item';
    }

    $class_names = implode(' ', apply_filters('nav_menu_css_class', array_filter($classes), $item, $args, $depth));
    $class_names = $class_names ? ' class="' . esc_attr($class_names) . '"' : '';

    $id = apply_filters('nav_menu_item_id', 'menu-item-' . $item->ID, $item, $args, $depth);
    $id = $id ? ' id="' . esc_attr($id) . '"' : '';

    $output .= $indent . '<li' . $id . $class_names . '>';

    $atts = [];
    $atts['title'] = !empty($item->attr_title) ? $item->attr_title : '';
    $atts['target'] = !empty($item->target) ? $item->target : '';
    $atts['rel'] = !empty($item->xfn) ? $item->xfn : '';
    $atts['href'] = !empty($item->url) ? $item->url : '';

    if ($has_children) {
      $atts['class'] = 'nav-link dropdown-toggle';
      $atts['data-bs-toggle'] = 'dropdown';
      $atts['aria-expanded'] = 'false';
      $atts['role'] = 'button';
    } else {
      $atts['class'] = 'nav-link';
    }

    if (0 === $depth) {
      $atts['class'] .= ' nav-link-' . sanitize_html_class($item->post_name);
    }

    $atts = apply_filters('nav_menu_link_attributes', $atts, $item, $args, $depth);

    $attributes = '';
    foreach ($atts as $attr => $value) {
      if (!empty($value)) {
        $value = ('href' === $attr) ? esc_url($value) : esc_attr($value);
        $attributes .= ' ' . $attr . '="' . $value . '"';
      }
    }

    $title = apply_filters('the_title', $item->title, $item->ID);
    $title = apply_filters('nav_menu_item_title', $title, $item, $args, $depth);

    $item_output = $args->before;
    $item_output .= '<a' . $attributes . '>';
    $item_output .= $args->link_before . $title . $args->link_after;

    if ($has_children) {
      $item_output .= ' <span class="dropdown-toggle-icon"><i class="fas fa-chevron-down"></i></span>';
    }

    $item_output .= '</a>';
    $item_output .= $args->after;

    $output .= apply_filters('walker_nav_menu_start_el', $item_output, $item, $depth, $args);
  }

  public function end_el(&$output, $item, $depth = 0, $args = null): void {
    $output .= "</li>\n";
  }

  public function end_lvl(&$output, $depth = 0, $args = null): void {
    $indent = str_repeat("\t", $depth);
    $output .= "$indent</ul>\n";
  }
}
