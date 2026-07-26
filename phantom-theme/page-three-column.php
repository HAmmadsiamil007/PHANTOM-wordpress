<?php
/**
 * Template Name: Three Column
 *
 * @package Phantom_Theme
 */

get_header(); ?>

<div class="container three-column-page">
	<div class="row">
		<div class="col-lg-4 col-md-4 col-sm-12">
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
