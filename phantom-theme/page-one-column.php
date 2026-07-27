<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
/**
 * Template Name: One Column
 *
 * @package Phantom_Theme
 */

get_header(); ?>

<div class="container one-column-page">
	<div class="row">
		<div class="col-12">
			<?php
			while ( have_posts() ) :
				the_post();
				the_content();
			endwhile;
			?>
		</div>
	</div>
</div>

<?php get_footer();
