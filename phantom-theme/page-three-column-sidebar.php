<?php
/**
 * Template Name: Three Column Sidebar
 *
 * @package Phantom_Theme
 */

get_header(); ?>

<div class="container three-column-sidebar-page">
	<div class="row">
		<div class="col-lg-8 col-md-12">
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
		<div class="col-lg-4 col-md-12">
			<?php get_sidebar(); ?>
		</div>
	</div>
</div>

<?php get_footer();
