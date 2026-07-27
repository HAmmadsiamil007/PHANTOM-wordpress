<?php
defined( 'ABSPATH' ) || exit;
/**
 * Search Results Template
 *
 * @package Phantom_Theme
 */

get_header(); ?>

<div class="container search-page">
	<header class="search-header">
		<h1 class="search-title">
			<?php
			printf(
				esc_html__( 'Search Results for: %s', 'phantom-theme' ),
				'<span>' . esc_html( get_search_query() ) . '</span>'
			);
			?>
		</h1>
	</header>

	<div class="row">
		<div class="col-lg-8 col-md-12">
			<?php if ( have_posts() ) : ?>
				<div class="search-results">
					<?php while ( have_posts() ) : the_post(); ?>
						<article id="post-<?php the_ID(); ?>" <?php post_class( 'search-result' ); ?>>
							<?php if ( has_post_thumbnail() ) : ?>
								<div class="post-thumbnail">
									<a href="<?php echo esc_url( get_permalink() ); ?>">
										<?php the_post_thumbnail( 'thumbnail' ); ?>
									</a>
								</div>
							<?php endif; ?>
							<div class="post-content">
								<h2 class="post-title">
									<a href="<?php echo esc_url( get_permalink() ); ?>"><?php echo esc_html( get_the_title() ); ?></a>
								</h2>
								<div class="post-excerpt">
									<?php the_excerpt(); ?>
								</div>
								<div class="post-meta">
									<span class="post-date"><?php echo esc_html( get_the_date() ); ?></span>
									<span class="post-type"><?php echo esc_html( get_post_type() ); ?></span>
								</div>
							</div>
						</article>
					<?php endwhile; ?>
				</div>
				<?php phantom_theme_pagination(); ?>
			<?php else : ?>
				<p><?php esc_html_e( 'No results found. Try a different search term.', 'phantom-theme' ); ?></p>
				<?php get_search_form(); ?>
			<?php endif; ?>
		</div>
		<div class="col-lg-4 col-md-12">
			<?php get_sidebar(); ?>
		</div>
	</div>
</div>

<?php get_footer();
