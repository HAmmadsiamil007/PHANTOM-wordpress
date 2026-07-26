<?php
/**
 * Single Post Template
 *
 * @package Phantom_Theme
 */

get_header(); ?>

<div class="container single-post-page">
	<div class="row">
		<div class="col-lg-8 col-md-12">
			<?php
			while ( have_posts() ) :
				the_post();
				?>
				<article id="post-<?php the_ID(); ?>" <?php post_class( 'single-post' ); ?>>
					<?php if ( has_post_thumbnail() ) : ?>
						<div class="post-featured-image">
							<?php the_post_thumbnail( 'full' ); ?>
						</div>
					<?php endif; ?>

					<h1 class="post-title"><?php the_title(); ?></h1>

					<div class="post-meta">
						<span class="post-date"><?php echo get_the_date(); ?></span>
						<span class="post-author"><?php echo esc_html( get_the_author() ); ?></span>
						<span class="post-categories"><?php the_category( ', ' ); ?></span>
						<?php if ( has_tag() ) : ?>
							<span class="post-tags"><?php the_tags( '', ', ' ); ?></span>
						<?php endif; ?>
					</div>

					<div class="post-content">
						<?php the_content(); ?>
					</div>

					<?php
					wp_link_pages( array(
						'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'phantom-theme' ),
						'after'  => '</div>',
					) );
					?>

					<div class="post-navigation">
						<?php
						the_post_navigation( array(
							'prev_text' => '&laquo; %title',
							'next_text' => '%title &raquo;',
						) );
						?>
					</div>

					<?php
					if ( comments_open() || get_comments_number() ) :
						comments_template();
					endif;
					?>
				</article>
			<?php endwhile; ?>
		</div>
		<div class="col-lg-4 col-md-12">
			<?php get_sidebar(); ?>
		</div>
	</div>
</div>

<?php get_footer();
