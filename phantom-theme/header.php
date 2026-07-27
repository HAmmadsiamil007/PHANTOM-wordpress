<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div id="page" class="site">
	<a class="skip-link screen-reader-text" href="#primary">
		<?php esc_html_e( 'Skip to content', 'phantom-theme' ); ?>
	</a>

	<header id="masthead" class="site-header">
		<div class="topbar">
			<div class="container">
				<div class="row align-items-center">
					<div class="col-lg-6 col-md-6 col-sm-12">
						<span class="topbar-text">
							<?php echo esc_html( get_theme_mod( 'topbar_text', 'Summer sale discount off 60% on all of your orders!' ) ); ?>
						</span>
					</div>
					<div class="col-lg-6 col-md-6 col-sm-12 text-end">
						<div class="topbar-links">
							<?php if ( is_user_logged_in() ) : ?>
								<a href="<?php echo esc_url( get_edit_user_link() ); ?>"><?php esc_html_e( 'My Account', 'phantom-theme' ); ?></a>
								<a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>"><?php esc_html_e( 'Logout', 'phantom-theme' ); ?></a>
							<?php else : ?>
								<a href="<?php echo esc_url( wp_login_url() ); ?>"><?php esc_html_e( 'Login', 'phantom-theme' ); ?></a>
								<a href="<?php echo esc_url( wp_registration_url() ); ?>"><?php esc_html_e( 'Register', 'phantom-theme' ); ?></a>
							<?php endif; ?>
						</div>
					</div>
				</div>
			</div>
		</div>

		<nav class="navbar navbar-expand-lg">
			<div class="container">
				<div class="site-branding">
					<?php if ( has_custom_logo() ) : ?>
						<?php the_custom_logo(); ?>
					<?php else : ?>
						<a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
							<img src="<?php echo esc_url( PHANTOM_THEME_URL . '/assets/images/logo.png' ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" class="logo">
						</a>
					<?php endif; ?>
				</div>

				<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#primary-menu" aria-controls="primary-menu" aria-expanded="false" aria-label="<?php esc_attr_e( 'Toggle navigation', 'phantom-theme' ); ?>">
					<span class="navbar-toggler-icon"></span>
				</button>

				<div id="primary-menu" class="collapse navbar-collapse">
					<?php
					wp_nav_menu( array(
						'theme_location' => 'primary',
						'container'      => false,
						'menu_class'     => 'navbar-nav ms-auto',
						'fallback_cb'    => false,
						'depth'          => 3,
					) );
					?>
				</div>

				<div class="header-icons">
					<a href="<?php echo esc_url( home_url( '/' ) . '#search' ); ?>" class="search-toggle" aria-label="<?php esc_attr_e( 'Search', 'phantom-theme' ); ?>">
						<img src="<?php echo esc_url( PHANTOM_THEME_URL . '/assets/images/header-search.png' ); ?>" alt="<?php esc_attr_e( 'Search', 'phantom-theme' ); ?>">
					</a>
					<?php if ( class_exists( 'WooCommerce' ) ) : ?>
						<a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="cart-icon" aria-label="<?php esc_attr_e( 'Cart', 'phantom-theme' ); ?>">
							<img src="<?php echo esc_url( PHANTOM_THEME_URL . '/assets/images/header-cart.png' ); ?>" alt="<?php esc_attr_e( 'Cart', 'phantom-theme' ); ?>">
							<span class="cart-count"><?php echo esc_html( isset( WC()->cart ) && WC()->cart ? WC()->cart->get_cart_contents_count() : 0 ); ?></span>
						</a>
					<?php endif; ?>
				</div>
			</div>
		</nav>
	</header>

	<main id="primary" class="site-main">
