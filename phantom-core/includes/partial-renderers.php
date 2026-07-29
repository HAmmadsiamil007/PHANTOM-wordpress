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

	// Fallback to default images if no custom image uploaded.
	$default_desktop = PHANTOM_CORE_URL . 'frontend/assets/images/banner-img1.png';
	$default_tablet  = PHANTOM_CORE_URL . 'frontend/assets/images/banner-img2.png';
	$default_mobile  = PHANTOM_CORE_URL . 'frontend/assets/images/banner-bg-img.png';
	if ( '' === $desktop || ! filter_var( $desktop, FILTER_VALIDATE_URL ) ) {
		$desktop = $default_desktop;
	}
	if ( '' === $tablet || ! filter_var( $tablet, FILTER_VALIDATE_URL ) ) {
		$tablet = $default_tablet;
	}
	if ( '' === $mobile || ! filter_var( $mobile, FILTER_VALIDATE_URL ) ) {
		$mobile = $default_mobile;
	}
	$enabled = (bool) get_option( $prefix . 'hero_enable_responsive', 1 );
	$tablet_bp = absint( get_option( $prefix . 'hero_tablet_breakpoint', 1024 ) );
	$mobile_bp = absint( get_option( $prefix . 'hero_mobile_breakpoint', 768 ) );
	$style = '--hero-image:url("' . esc_url( $desktop ) . '");--hero-image-desktop:url("' . esc_url( $desktop ) . '");';
	if ( $enabled ) {
		$style .= '@media(max-width:' . $tablet_bp . 'px){:root{--hero-image-tablet:url("' . esc_url( $tablet ) . '");--hero-image:url("' . esc_url( $tablet ) . '");}}';
		$style .= '@media(max-width:' . $mobile_bp . 'px){:root{--hero-image-mobile:url("' . esc_url( $mobile ) . '");--hero-image:url("' . esc_url( $mobile ) . '");}}';
	}
	echo '<style id="phantom-hero-partial">[data-hero-area]{' . $style . '}</style>';
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

function phantom_render_header_partial_v2(): void {
	$style = get_option( 'phantom_header_style', 'default' );
	?>
	<header id="masthead" class="site-header header-<?php echo esc_attr( $style ); ?>">
		<div class="container">
			<div class="row align-items-center">
				<div class="col-auto">
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="brand-logo" rel="home">
						<?php $logo = phantom_get_image_with_default( 'phantom_general_site_logo', 'frontend/assets/images/logo.png' ); ?>
						<img src="<?php echo esc_url( $logo ); ?>" alt="<?php bloginfo( 'name' ); ?>" height="40">
					</a>
				</div>
				<div class="col">
					<nav class="main-navigation">
						<?php wp_nav_menu( array(
							'theme_location' => 'phantom_primary',
							'container'      => false,
							'menu_class'     => 'primary-menu',
							'fallback_cb'    => false,
							'depth'          => 3,
						) ); ?>
					</nav>
				</div>
			</div>
		</div>
	</header>
	<?php
}

function phantom_render_blog_partial_v2(): void {
	$layout = get_option( 'phantom_blog_layout', 'grid' );
	$posts = get_posts( array( 'numberposts' => 6 ) );
	if ( empty( $posts ) ) {
		echo '<p>' . esc_html__( 'No posts found.', 'phantom-core' ) . '</p>';
		return;
	}
	?>
	<div class="blog-posts blog-<?php echo esc_attr( $layout ); ?>">
		<div class="row row-cols-<?php echo 'list' === $layout ? 1 : 3; ?>">
			<?php foreach ( $posts as $post ) : setup_postdata( $post ); ?>
				<div class="col mb-4">
					<article class="post-card">
						<?php if ( has_post_thumbnail( $post ) ) : ?>
							<a href="<?php the_permalink(); ?>" class="post-thumb">
								<?php echo get_the_post_thumbnail( $post, 'medium' ); ?>
							</a>
						<?php endif; ?>
						<h3><a href="<?php the_permalink(); ?>"><?php echo esc_html( get_the_title( $post ) ); ?></a></h3>
						<small><?php echo esc_html( get_the_date( '', $post ) ); ?></small>
						<p><?php echo esc_html( wp_trim_words( get_the_excerpt( $post ), 20, '...' ) ); ?></p>
					</article>
				</div>
			<?php endforeach; wp_reset_postdata(); ?>
		</div>
	</div>
	<?php
}
