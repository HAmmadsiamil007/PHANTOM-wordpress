<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
/**
 * Template Name: Full Width
 *
 * @package Phantom_Theme
 */

get_header(); ?>

<div class="container-fluid full-width-page">
	<?php
	while ( have_posts() ) :
		the_post();
		the_content();
	endwhile;
	?>
</div>

<?php get_footer();
