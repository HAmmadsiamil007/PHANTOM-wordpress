<?php
defined( 'ABSPATH' ) || exit;
/**
 * 404 Template
 *
 * @package Phantom_Theme
 */

get_header(); ?>

<div class="container error-404-page text-center">
	<section class="error-404 not-found">
		<h1 class="error-title">404</h1>
		<h2 class="page-title"><?php esc_html_e( 'Oops! That page can&rsquo;t be found.', 'phantom-theme' ); ?></h2>
		<p><?php esc_html_e( 'It looks like nothing was found at this location. Maybe try a search?', 'phantom-theme' ); ?></p>
		<?php get_search_form(); ?>
		<div class="mt-4">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="btn btn-primary">
				<?php esc_html_e( 'Back to Home', 'phantom-theme' ); ?>
			</a>
		</div>
	</section>
</div>

<?php get_footer();
