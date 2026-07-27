<?php
defined( 'ABSPATH' ) || exit;
/**
 * Main template file — fallback for all requests
 *
 * @package Phantom_Theme
 */

get_header();

if ( is_home() && ! is_front_page() ) : ?>
	<div class="container blog-page">
		<div class="row">
			<div class="col-lg-8 col-md-12">
				<?php if ( have_posts() ) : ?>
					<div class="blog-posts">
						<?php while ( have_posts() ) : the_post(); ?>
							<article id="post-<?php the_ID(); ?>" <?php post_class( 'blog-post' ); ?>>
								<?php if ( has_post_thumbnail() ) : ?>
									<div class="post-thumbnail">
										<a href="<?php echo esc_url( get_permalink() ); ?>">
											<?php the_post_thumbnail( 'large' ); ?>
										</a>
									</div>
								<?php endif; ?>
								<div class="post-content">
									<h2 class="post-title">
										<a href="<?php echo esc_url( get_permalink() ); ?>"><?php echo esc_html( get_the_title() ); ?></a>
									</h2>
									<div class="post-meta">
										<span class="post-date"><?php echo esc_html( get_the_date() ); ?></span>
										<span class="post-author"><?php echo esc_html( get_the_author() ); ?></span>
										<span class="post-categories"><?php the_category( ', ' ); ?></span>
									</div>
									<div class="post-excerpt">
										<?php the_excerpt(); ?>
									</div>
									<a href="<?php echo esc_url( get_permalink() ); ?>" class="read-more"><?php esc_html_e( 'Read More', 'phantom-theme' ); ?></a>
								</div>
							</article>
						<?php endwhile; ?>
					</div>
					<?php phantom_theme_pagination(); ?>
				<?php else : ?>
					<p><?php esc_html_e( 'No posts found.', 'phantom-theme' ); ?></p>
				<?php endif; ?>
			</div>
			<div class="col-lg-4 col-md-12">
				<?php get_sidebar(); ?>
			</div>
		</div>
	</div>
<?php else : ?>
	<div class="container default-page">
		<?php
		while ( have_posts() ) :
			the_post();
			the_content();
		endwhile;
		?>
	</div>
<?php endif;

get_footer();
