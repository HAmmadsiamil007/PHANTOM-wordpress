<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
/**
 * Template Name: Four Columns
 *
 * @package Phantom_Theme
 */

get_header(); ?>

<div class="container four-column-page">
	<div class="row">
		<div class="col-lg-3 col-md-6 col-sm-12">
			<?php
			while ( have_posts() ) :
				the_post();
				the_content();
			endwhile;
			?>
		</div>
		<div class="col-lg-3 col-md-6 col-sm-12">
			<h3><?php esc_html_e( 'Column 2', 'phantom-theme' ); ?></h3>
			<p><?php esc_html_e( 'Content for column 2 goes here.', 'phantom-theme' ); ?></p>
		</div>
		<div class="col-lg-3 col-md-6 col-sm-12">
			<h3><?php esc_html_e( 'Column 3', 'phantom-theme' ); ?></h3>
			<p><?php esc_html_e( 'Content for column 3 goes here.', 'phantom-theme' ); ?></p>
		</div>
		<div class="col-lg-3 col-md-6 col-sm-12">
			<h3><?php esc_html_e( 'Column 4', 'phantom-theme' ); ?></h3>
			<p><?php esc_html_e( 'Content for column 4 goes here.', 'phantom-theme' ); ?></p>
		</div>
	</div>
</div>

<?php get_footer();
