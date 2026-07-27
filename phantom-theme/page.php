<?php
defined( 'ABSPATH' ) || exit;
/**
 * Default Page Template
 *
 * @package Phantom_Theme
 */

get_header(); ?>

<div class="container default-page">
	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
			<h1 class="page-title"><?php echo esc_html( get_the_title() ); ?></h1>
			<div class="page-content">
				<?php the_content(); ?>
			</div>
			<?php
			wp_link_pages( array(
				'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'phantom-theme' ),
				'after'  => '</div>',
			) );
			if ( comments_open() || get_comments_number() ) :
				comments_template();
			endif;
			?>
		</article>
	<?php endwhile; ?>
</div>

<?php get_footer();
