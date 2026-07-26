<?php
/**
 * Archive Template
 *
 * @package Phantom_Theme
 */

get_header(); ?>

<div class="container archive-page">
	<div class="row">
		<div class="col-lg-8 col-md-12">
			<header class="archive-header">
				<h1 class="archive-title">
					<?php
					if ( is_category() ) :
						single_cat_title();
					elseif ( is_tag() ) :
						single_tag_title();
					elseif ( is_author() ) :
						printf( esc_html__( 'Author: %s', 'phantom-theme' ), '<span>' . get_the_author() . '</span>' );
					elseif ( is_day() ) :
						printf( esc_html__( 'Day: %s', 'phantom-theme' ), '<span>' . get_the_date() . '</span>' );
					elseif ( is_month() ) :
						printf( esc_html__( 'Month: %s', 'phantom-theme' ), '<span>' . get_the_date( 'F Y' ) . '</span>' );
					elseif ( is_year() ) :
						printf( esc_html__( 'Year: %s', 'phantom-theme' ), '<span>' . get_the_date( 'Y' ) . '</span>' );
					else :
						esc_html_e( 'Archives', 'phantom-theme' );
					endif;
					?>
				</h1>
				<?php the_archive_description( '<div class="archive-description">', '</div>' ); ?>
			</header>

			<?php if ( have_posts() ) : ?>
				<div class="archive-posts">
					<?php while ( have_posts() ) : the_post(); ?>
						<article id="post-<?php the_ID(); ?>" <?php post_class( 'archive-post' ); ?>>
							<?php if ( has_post_thumbnail() ) : ?>
								<div class="post-thumbnail">
									<a href="<?php the_permalink(); ?>">
										<?php the_post_thumbnail( 'medium' ); ?>
									</a>
								</div>
							<?php endif; ?>
							<div class="post-content">
								<h2 class="post-title">
									<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
								</h2>
								<div class="post-meta">
									<span class="post-date"><?php echo get_the_date(); ?></span>
									<span class="post-author"><?php echo esc_html( get_the_author() ); ?></span>
									<span class="post-categories"><?php the_category( ', ' ); ?></span>
								</div>
								<div class="post-excerpt">
									<?php the_excerpt(); ?>
								</div>
								<a href="<?php the_permalink(); ?>" class="read-more"><?php esc_html_e( 'Read More', 'phantom-theme' ); ?></a>
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

<?php get_footer();
