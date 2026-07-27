<?php
defined( 'ABSPATH' ) || exit;
?>
	</main>

	<footer id="colophon" class="site-footer">
		<div class="footer-newsletter">
			<div class="container">
				<div class="row align-items-center">
					<div class="col-lg-6 col-md-6 col-sm-12">
						<h3><?php echo esc_html( get_theme_mod( 'newsletter_heading', __( 'Subscribe to Our Newsletter', 'phantom-theme' ) ) ); ?></h3>
					</div>
					<div class="col-lg-6 col-md-6 col-sm-12">
						<form class="newsletter-form" action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" method="post">
							<div class="input-group">
								<input type="email" name="email" class="form-control" placeholder="<?php esc_attr_e( 'Enter your email', 'phantom-theme' ); ?>" required>
								<input type="hidden" name="action" value="phantom_newsletter_subscribe">
								<?php wp_nonce_field( 'phantom_newsletter', 'phantom_newsletter_nonce' ); ?>
								<button class="btn btn-primary" type="submit"><?php esc_html_e( 'Subscribe', 'phantom-theme' ); ?></button>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>

		<div class="footer-main">
			<div class="container">
				<div class="row">
					<div class="col-lg-4 col-md-6 col-sm-12">
						<div class="footer-about">
							<img src="<?php echo esc_url( PHANTOM_THEME_URL . '/assets/images/footer-logo.png' ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" class="footer-logo">
							<p><?php echo esc_html( get_theme_mod( 'footer_about_text', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore.' ) ); ?></p>
						</div>
					</div>
					<div class="col-lg-2 col-md-6 col-sm-12">
						<h4><?php esc_html_e( 'Navigation', 'phantom-theme' ); ?></h4>
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
						<h4><?php esc_html_e( 'Support', 'phantom-theme' ); ?></h4>
						<ul class="footer-support">
							<li><a href="<?php echo esc_url( home_url( '/term-of-use/' ) ); ?>"><?php esc_html_e( 'Terms of Use', 'phantom-theme' ); ?></a></li>
							<li><a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>"><?php esc_html_e( 'Privacy Policy', 'phantom-theme' ); ?></a></li>
							<li><a href="<?php echo esc_url( home_url( '/cookie-policy/' ) ); ?>"><?php esc_html_e( 'Cookie Policy', 'phantom-theme' ); ?></a></li>
							<li><a href="<?php echo esc_url( home_url( '/faq/' ) ); ?>"><?php esc_html_e( 'FAQ', 'phantom-theme' ); ?></a></li>
							<li><a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>"><?php esc_html_e( 'Contact', 'phantom-theme' ); ?></a></li>
						</ul>
					</div>
					<div class="col-lg-3 col-md-6 col-sm-12">
						<h4><?php esc_html_e( 'Contact', 'phantom-theme' ); ?></h4>
						<?php if ( is_active_sidebar( 'sidebar-footer' ) ) : ?>
							<?php dynamic_sidebar( 'sidebar-footer' ); ?>
						<?php else : ?>
						<ul class="footer-contact">
							<li><img src="<?php echo esc_url( PHANTOM_THEME_URL . '/assets/images/loc-img.png' ); ?>" alt=""> <?php echo esc_html( get_theme_mod( 'footer_address', '123 Street, New York, USA' ) ); ?></li>
							<li><img src="<?php echo esc_url( PHANTOM_THEME_URL . '/assets/images/email-img.png' ); ?>" alt=""> <?php echo esc_html( get_theme_mod( 'footer_email', 'info@phantom.test' ) ); ?></li>
							<li><img src="<?php echo esc_url( PHANTOM_THEME_URL . '/assets/images/buttondown-img.png' ); ?>" alt=""> <?php echo esc_html( get_theme_mod( 'footer_phone', '+1 234 567 890' ) ); ?></li>
						</ul>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>

		<div class="footer-bottom">
			<div class="container">
				<div class="row align-items-center">
					<div class="col-lg-6 col-md-6 col-sm-12">
						<p class="copyright">
							&copy; <?php echo esc_html( date( 'Y' ) ); ?> <?php echo esc_html( get_bloginfo( 'name' ) ); ?>. <?php esc_html_e( 'All rights reserved.', 'phantom-theme' ); ?>
						</p>
					</div>
					<div class="col-lg-6 col-md-6 col-sm-12 text-end">
						<img src="<?php echo esc_url( PHANTOM_THEME_URL . '/assets/images/payment-cards.png' ); ?>" alt="<?php esc_attr_e( 'Payment Methods', 'phantom-theme' ); ?>">
					</div>
				</div>
			</div>
		</div>
	</footer>
</div>

<?php wp_footer(); ?>
</body>
</html>
