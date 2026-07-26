<?php
declare(strict_types=1);

namespace PhantomCore;

defined( 'ABSPATH' ) || exit;

class Bootstrap_Walker extends \Walker_Nav_Menu {

	public function start_lvl( &$output, $depth = 0, $args = null ): void {
		$indent  = str_repeat( "\t", $depth );
		$classes = 'dropdown-menu';
		if ( 0 === $depth ) {
			$classes .= ' dropdown-menu-end';
		}
		$output .= "\n$indent<ul class=\"$classes\">\n";
	}

	public function end_lvl( &$output, $depth = 0, $args = null ): void {
		$indent = str_repeat( "\t", $depth );
		$output .= "$indent</ul>\n";
	}

	public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ): void {
		$indent = str_repeat( "\t", $depth );

		$classes   = empty( $item->classes ) ? array() : (array) $item->classes;
		$classes[] = 'nav-item';
		$classes[] = 'menu-item';

		$has_children = in_array( 'menu-item-has-children', $classes, true );
		if ( $has_children ) {
			$classes[] = 'dropdown';
		}

		$class_names = implode( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item, $args, $depth ) );
		$class_names = $class_names ? ' class="' . esc_attr( $class_names ) . '"' : '';

		$output .= $indent . '<li' . $class_names . '>';

		$atts           = array();
		$atts['title']  = ! empty( $item->attr_title ) ? $item->attr_title : '';
		$atts['target'] = ! empty( $item->target ) ? $item->target : '';
		$atts['rel']    = ! empty( $item->xfn ) ? $item->xfn : '';
		$atts['href']   = ! empty( $item->url ) ? $item->url : '';

		$atts['class'] = 'nav-link';
		if ( $has_children ) {
			$atts['class']          .= ' dropdown-toggle';
			$atts['role']            = 'button';
			$atts['data-bs-toggle']  = 'dropdown';
			$atts['aria-expanded']   = 'false';
		}
		if ( in_array( 'current-menu-item', $classes, true ) ) {
			$atts['class'] .= ' active';
			$atts['aria-current'] = 'page';
		}
		if ( $depth > 0 ) {
			$atts['class'] .= ' dropdown-item';
		}

		$atts = apply_filters( 'nav_menu_link_attributes', $atts, $item, $args, $depth );

		$attributes = '';
		foreach ( $atts as $attr => $value ) {
			if ( ! empty( $value ) ) {
				$value       = 'href' === $attr ? esc_url( $value ) : esc_attr( $value );
				$attributes .= ' ' . $attr . '="' . $value . '"';
			}
		}

		$title = apply_filters( 'the_title', $item->title, $item->ID );
		$title = apply_filters( 'nav_menu_item_title', $title, $item, $args, $depth );

		$item_output  = $args->before;
		$item_output .= '<a' . $attributes . '>';
		$item_output .= $args->link_before . $title . $args->link_after;
		$item_output .= '</a>';
		$item_output .= $args->after;

		$output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );
	}

	public function end_el( &$output, $item, $depth = 0, $args = null ): void {
		$output .= "</li>\n";
	}
}
