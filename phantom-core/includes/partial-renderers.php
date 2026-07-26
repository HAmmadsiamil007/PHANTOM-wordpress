<?php

defined( 'ABSPATH' ) || exit;

function phantom_render_header_partial(): void {
	?>
	<header id="masthead" class="site-header">
		<div class="site-branding">
			<?php if ( has_custom_logo() ) : ?>
				<?php the_custom_logo(); ?>
			<?php else : ?>
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a>
			<?php endif; ?>
		</div>
		<nav class="site-navigation main-navigation">
			<?php
			wp_nav_menu( array(
				'theme_location' => 'primary',
				'container'      => false,
				'menu_class'     => 'primary-menu',
				'fallback_cb'    => false,
				'depth'          => 3,
			) );
			?>
		</nav>
	</header>
	<?php
}

function phantom_render_footer_partial(): void {
	?>
	<footer id="colophon" class="site-footer">
		<div class="footer-main">
			<div class="container">
				<div class="row">
					<div class="col-lg-4 col-md-6 col-sm-12">
						<img src="<?php echo esc_url( PHANTOM_THEME_URL . '/assets/images/footer-logo.png' ); ?>" alt="<?php bloginfo( 'name' ); ?>" class="footer-logo">
						<p><?php echo esc_html( get_option( 'phantom_footer_about_text', 'Duis aute irure dolor in reprehenderit in voluptate velit cillum dolore eu fugiat nulla pariatur ccaecat cupidata proident, sunt in culpa officia deserunt mollit.' ) ); ?></p>
					</div>
					<div class="col-lg-2 col-md-6 col-sm-12">
						<h4><?php esc_html_e( 'Navigation', 'phantom-core' ); ?></h4>
						<?php
						wp_nav_menu( array(
							'theme_location' => 'footer',
							'container'      => false,
							'menu_class'     => 'footer-menu',
							'fallback_cb'    => false,
							'depth'          => 1,
						) );
						?>
					</div>
					<div class="col-lg-3 col-md-6 col-sm-12">
						<h4><?php esc_html_e( 'Contact', 'phantom-core' ); ?></h4>
						<ul class="footer-contact">
							<li><?php echo wp_kses_post( get_option( 'phantom_footer_address', '121 King Street Melbourne, <br>3000, Australia' ) ); ?></li>
							<li><?php echo esc_html( get_option( 'phantom_footer_email', 'hello@aethershoes.com' ) ); ?></li>
							<li><?php echo esc_html( get_option( 'phantom_footer_phone', '+1235 211 5236' ) ); ?></li>
						</ul>
					</div>
				</div>
			</div>
		</div>
		<div class="footer-bottom">
			<div class="container">
				<p class="copyright">&copy; <?php echo esc_html( date( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>. <?php esc_html_e( 'All rights reserved.', 'phantom-core' ); ?></p>
			</div>
		</div>
	</footer>
	<?php
}

function phantom_render_blog_partial(): void {
	$posts = get_posts( array( 'numberposts' => 5 ) );
	if ( empty( $posts ) ) {
		echo '<p>' . esc_html__( 'No posts found.', 'phantom-core' ) . '</p>';
		return;
	}
	?>
	<ul class="phantom-partial-posts">
		<?php foreach ( $posts as $post ) : setup_postdata( $post ); ?>
			<li>
				<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
				<small><?php echo esc_html( get_the_date( '', $post ) ); ?></small>
			</li>
		<?php endforeach; wp_reset_postdata(); ?>
	</ul>
	<?php
}

function phantom_render_search_partial(): void {
	?>
	<form role="search" method="get" class="search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
		<label>
			<span class="screen-reader-text"><?php esc_html_e( 'Search for:', 'phantom-core' ); ?></span>
			<input type="search" class="search-field" placeholder="<?php esc_attr_e( 'Search', 'phantom-core' ); ?>" name="s">
		</label>
		<button type="submit" class="search-submit"><?php esc_html_e( 'Search', 'phantom-core' ); ?></button>
	</form>
	<?php
}

function phantom_render_hero_media_partial(): void {
	$prefix  = 'phantom_';
	$desktop = get_option( $prefix . 'hero_banner_image', '' );
	$tablet  = get_option( $prefix . 'hero_image_tablet', '' );
	$mobile  = get_option( $prefix . 'hero_image_mobile', '' );
	$enabled = (bool) get_option( $prefix . 'hero_enable_responsive', 1 );
	$tablet_bp = absint( get_option( $prefix . 'hero_tablet_breakpoint', 1024 ) );
	$mobile_bp = absint( get_option( $prefix . 'hero_mobile_breakpoint', 768 ) );
	$style = '';
	if ( '' !== $desktop ) {
		$style .= '--hero-image:url("' . esc_url( $desktop ) . '");--hero-image-desktop:url("' . esc_url( $desktop ) . '");';
	}
	if ( $enabled && '' !== $tablet ) {
		$style .= '@media(max-width:' . $tablet_bp . 'px){:root{--hero-image-tablet:url("' . esc_url( $tablet ) . '");--hero-image:url("' . esc_url( $tablet ) . '");}}';
	}
	if ( $enabled && '' !== $mobile ) {
		$style .= '@media(max-width:' . $mobile_bp . 'px){:root{--hero-image-mobile:url("' . esc_url( $mobile ) . '");--hero-image:url("' . esc_url( $mobile ) . '");}}';
	}
	if ( '' !== $style ) {
		echo '<style id="phantom-hero-partial">[data-hero-area]{' . $style . '}</style>';
	}
}

function phantom_render_nav_partial(): void {
	$theme_location = get_option( 'phantom_menu_location', 'phantom_primary' );
	if ( ! has_nav_menu( $theme_location ) ) {
		$theme_location = 'primary';
	}
	wp_nav_menu( array(
		'theme_location' => $theme_location,
		'container'      => 'nav',
		'container_class' => 'site-navigation main-navigation',
		'menu_class'     => 'primary-menu',
		'fallback_cb'    => false,
	) );
}
