<?php
/**
 * Template Name: Two Column
 *
 * @package Phantom_Theme
 */

get_header(); ?>

<div class="container two-column-page">
	<div class="row">
		<div class="col-lg-6 col-md-6 col-sm-12">
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
