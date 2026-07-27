<?php
defined( 'ABSPATH' ) || exit;
/**
 * Front Page Template
 *
 * @package Phantom_Theme
 */

get_header();

if ( have_posts() ) :
	while ( have_posts() ) :
		the_post();
		the_content();
	endwhile;
endif;

get_footer();
