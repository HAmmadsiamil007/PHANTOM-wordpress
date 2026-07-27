<?php
/**
 * Phantom Core Theme Functions
 *
 * @package Phantom_Theme
 * @version 1.5.0
 */

defined( 'ABSPATH' ) || exit;

define( 'PHANTOM_THEME_VERSION', '1.5.0' );
define( 'PHANTOM_THEME_DIR', get_template_directory() );
define( 'PHANTOM_THEME_URL', get_template_directory_uri() );

/**
 * Load Bootstrap Nav Walker for proper Bootstrap 5 dropdown markup
 */
require_once PHANTOM_THEME_DIR . '/class-bootstrap-nav-walker.php';

/**
 * Theme Setup
 */
function phantom_theme_setup(): void {
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'custom-logo', array(
		'height'      => 80,
		'width'       => 200,
		'flex-height' => true,
		'flex-width'  => true,
	) );
	add_theme_support( 'html5', array(
		'search-form',
		'comment-form',
		'comment-list',
		'gallery',
		'caption',
		'style',
		'script',
		'navigation-widgets',
	) );
	add_theme_support( 'align-wide' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'customize-selective-refresh-widgets' );
	add_theme_support( 'wp-block-styles' );

	load_theme_textdomain( 'phantom-theme', PHANTOM_THEME_DIR . '/languages' );

	register_nav_menus( array(
		'primary' => esc_html__( 'Primary Menu', 'phantom-theme' ),
		'footer'  => esc_html__( 'Footer Menu', 'phantom-theme' ),
	) );

	if ( class_exists( 'WooCommerce' ) ) {
		add_theme_support( 'woocommerce' );
		add_theme_support( 'wc-product-gallery-zoom' );
		add_theme_support( 'wc-product-gallery-lightbox' );
		add_theme_support( 'wc-product-gallery-slider' );
	}
}
add_action( 'after_setup_theme', 'phantom_theme_setup' );

/**
 * Enqueue Styles and Scripts
 */
function phantom_theme_enqueue(): void {
	$ver = PHANTOM_THEME_VERSION;

	wp_enqueue_style( 'bootstrap', PHANTOM_THEME_URL . '/assets/bootstrap/bootstrap.min.css', array(), $ver );
	wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css', array(), '6.5.1' );
	wp_enqueue_style( 'owl-carousel', PHANTOM_THEME_URL . '/assets/css/owl.carousel.min.css', array(), $ver );
	wp_enqueue_style( 'owl-theme', PHANTOM_THEME_URL . '/assets/css/owl.theme.default.min.css', array(), $ver );
	wp_enqueue_style( 'animate', PHANTOM_THEME_URL . '/assets/css/animate.css', array(), $ver );
	wp_enqueue_style( 'phantom-style', PHANTOM_THEME_URL . '/assets/css/style.css', array( 'bootstrap' ), $ver );
	wp_enqueue_style( 'phantom-blog', PHANTOM_THEME_URL . '/assets/css/blog.css', array(), $ver );
	wp_enqueue_style( 'phantom-shop', PHANTOM_THEME_URL . '/assets/css/shop.css', array(), $ver );
	wp_enqueue_style( 'phantom-columns', PHANTOM_THEME_URL . '/assets/css/column-width.css', array(), $ver );
	wp_enqueue_style( 'phantom-responsive', PHANTOM_THEME_URL . '/assets/css/responsive.css', array(), $ver );
	wp_enqueue_style( 'phantom-a11y', PHANTOM_THEME_URL . '/assets/css/a11y.css', array(), $ver );

	if ( ! wp_script_is( 'jquery', 'registered' ) ) {
		wp_register_script( 'jquery', PHANTOM_THEME_URL . '/assets/js/jquery-3.7.1.min.js', array(), '3.7.1', false );
	}
	wp_enqueue_script( 'jquery' );

	wp_enqueue_script( 'bootstrap', PHANTOM_THEME_URL . '/assets/js/bootstrap.min.js', array( 'jquery' ), $ver, true );
	wp_enqueue_script( 'popper', PHANTOM_THEME_URL . '/assets/js/popper.min.js', array(), $ver, true );
	wp_enqueue_script( 'owl-carousel', PHANTOM_THEME_URL . '/assets/js/owl.carousel.js', array( 'jquery' ), $ver, true );
	wp_enqueue_script( 'wow', PHANTOM_THEME_URL . '/assets/js/wow.js', array( 'jquery' ), $ver, true );
	wp_add_inline_script( 'wow', 'document.addEventListener("DOMContentLoaded", function() { if (typeof WOW !== "undefined") { new WOW().init(); } });' );
	wp_enqueue_script( 'phantom-preloader', PHANTOM_THEME_URL . '/assets/js/preloader.js', array( 'jquery' ), $ver, true );
	wp_enqueue_script( 'phantom-counter', PHANTOM_THEME_URL . '/assets/js/counter.js', array( 'jquery' ), $ver, true );
	wp_enqueue_script( 'phantom-carousel', PHANTOM_THEME_URL . '/assets/js/carousel.js', array( 'jquery' ), $ver, true );
	wp_enqueue_script( 'phantom-search', PHANTOM_THEME_URL . '/assets/js/search.js', array( 'jquery' ), $ver, true );
	wp_enqueue_script( 'phantom-filter', PHANTOM_THEME_URL . '/assets/js/filter-button.js', array( 'jquery' ), $ver, true );
	wp_enqueue_script( 'phantom-loadmore', PHANTOM_THEME_URL . '/assets/js/loadmore.js', array( 'jquery' ), $ver, true );
	wp_enqueue_script( 'phantom-video-popup', PHANTOM_THEME_URL . '/assets/js/video-popup.js', array( 'jquery' ), $ver, true );
	wp_enqueue_script( 'phantom-video-section', PHANTOM_THEME_URL . '/assets/js/video-section.js', array( 'jquery' ), $ver, true );
	wp_enqueue_script( 'phantom-back-to-top', PHANTOM_THEME_URL . '/assets/js/back-to-top-button.js', array( 'jquery' ), $ver, true );
	wp_enqueue_script( 'phantom-contact-form', PHANTOM_THEME_URL . '/assets/js/contact-form.js', array( 'jquery' ), $ver, true );
	wp_enqueue_script( 'phantom-country-dropdown', PHANTOM_THEME_URL . '/assets/js/country_dropdown.js', array( 'jquery' ), $ver, true );
	wp_enqueue_script( 'phantom-dark-mode', PHANTOM_THEME_URL . '/assets/js/phantom-dark-mode.js', array(), $ver, true );

	wp_localize_script( 'phantom-contact-form', 'phantomAjax', array(
		'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		'nonce'   => wp_create_nonce( 'phantom_theme_nonce' ),
	) );

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'phantom_theme_enqueue' );

/**
 * Register Widget Areas
 */
function phantom_theme_widgets(): void {
	register_sidebar( array(
		'name'          => esc_html__( 'Blog Sidebar', 'phantom-theme' ),
		'id'            => 'sidebar-blog',
		'description'   => esc_html__( 'Add widgets for the blog sidebar.', 'phantom-theme' ),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h3 class="widget-title">',
		'after_title'   => '</h3>',
	) );

	register_sidebar( array(
		'name'          => esc_html__( 'Shop Sidebar', 'phantom-theme' ),
		'id'            => 'sidebar-shop',
		'description'   => esc_html__( 'Add widgets for the shop sidebar.', 'phantom-theme' ),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h3 class="widget-title">',
		'after_title'   => '</h3>',
	) );

	register_sidebar( array(
		'name'          => esc_html__( 'Footer Widgets', 'phantom-theme' ),
		'id'            => 'sidebar-footer',
		'description'   => esc_html__( 'Add widgets for the footer area.', 'phantom-theme' ),
		'before_widget' => '<div id="%1$s" class="col-lg-3 col-md-6 col-sm-12 widget %2$s">',
		'after_widget'  => '</div>',
		'before_title'  => '<h4 class="footer-widget-title">',
		'after_title'   => '</h4>',
	) );
}
add_action( 'widgets_init', 'phantom_theme_widgets' );

/**
 * Body class additions
 */
function phantom_theme_body_class( array $classes ): array {
	if ( is_user_logged_in() ) {
		$classes[] = 'user-logged-in';
	}
	if ( class_exists( 'WooCommerce' ) && is_woocommerce() ) {
		$classes[] = 'woocommerce-active';
	}
	return $classes;
}
add_filter( 'body_class', 'phantom_theme_body_class' );

/**
 * Customizer integration: Output dynamic CSS variables
 */
function phantom_theme_customizer_css(): void {
	$options = get_option( 'phantom_options', array() );
	$primary = $options['color_primary'] ?? get_option( 'phantom_color_primary', '#7635d5' );
	$accent  = $options['color_accent'] ?? get_option( 'phantom_color_accent', '#fcd668' );
	$text    = $options['color_text'] ?? get_option( 'phantom_color_text', '#4e4e4e' );
	$bg      = $options['color_background'] ?? get_option( 'phantom_color_background', '#ffffff' );
	?>
	<style id="phantom-theme-vars">
		:root {
			--primary-color: <?php echo esc_attr( $primary ); ?>;
			--accent-color: <?php echo esc_attr( $accent ); ?>;
			--text-color: <?php echo esc_attr( $text ); ?>;
			--bg-color: <?php echo esc_attr( $bg ); ?>;
			--phantom-color-primary: <?php echo esc_attr( $primary ); ?>;
			--phantom-color-accent: <?php echo esc_attr( $accent ); ?>;
			--phantom-color-text: <?php echo esc_attr( $text ); ?>;
			--phantom-color-background: <?php echo esc_attr( $bg ); ?>;
		}
	</style>
	<?php
}
add_action( 'wp_head', 'phantom_theme_customizer_css', 5 );

/**
 * WooCommerce support: Ensure proper template loading
 */
if ( class_exists( 'WooCommerce' ) ) {
	add_filter( 'woocommerce_show_page_title', '__return_false' );
}

/**
 * Add async/defer to scripts
 */
function phantom_theme_script_loader_tag( string $tag, string $handle ): string {
	$async = array( 'phantom-preloader', 'phantom-counter' );
	$defer = array( 'phantom-contact-form', 'phantom-back-to-top', 'phantom-search' );

	if ( in_array( $handle, $async, true ) ) {
		return str_replace( ' src', ' async src', $tag );
	}
	if ( in_array( $handle, $defer, true ) ) {
		return str_replace( ' src', ' defer src', $tag );
	}
	return $tag;
}
add_filter( 'script_loader_tag', 'phantom_theme_script_loader_tag', 10, 2 );

/**
 * Disable emoji scripts for performance
 */
function phantom_theme_disable_emoji(): void {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
}
add_action( 'init', 'phantom_theme_disable_emoji' );

/**
 * Custom image sizes
 */
function phantom_theme_image_sizes(): void {
	add_image_size( 'phantom-blog-thumb', 400, 300, true );
	add_image_size( 'phantom-shop-thumb', 300, 300, true );
	add_image_size( 'phantom-featured-lg', 1200, 600, true );
}
add_action( 'after_setup_theme', 'phantom_theme_image_sizes' );

/**
 * Pagination helper
 */
function phantom_theme_pagination(): void {
	the_posts_pagination( array(
		'mid_size'  => 2,
		'prev_text' => '&laquo;',
		'next_text' => '&raquo;',
	) );
}

/**
 * Newsletter AJAX handler
 */
function phantom_theme_newsletter_subscribe(): void {
	if ( ! isset( $_POST['phantom_newsletter_nonce'] ) || ! wp_verify_nonce( $_POST['phantom_newsletter_nonce'], 'phantom_newsletter' ) ) {
		wp_send_json_error( array( 'message' => __( 'Invalid nonce.', 'phantom-theme' ) ) );
	}

	$email = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';
	if ( ! is_email( $email ) ) {
		wp_send_json_error( array( 'message' => __( 'Invalid email address.', 'phantom-theme' ) ) );
	}

	$emails   = get_option( 'phantom_newsletter_emails', array() );
	if ( ! is_array( $emails ) ) {
		$emails = array();
	}
	if ( in_array( $email, $emails, true ) ) {
		wp_send_json_error( array( 'message' => __( 'Email already subscribed.', 'phantom-theme' ) ) );
	}

	$emails[] = $email;
	update_option( 'phantom_newsletter_emails', $emails );

	wp_send_json_success( array( 'message' => __( 'Thank you for subscribing!', 'phantom-theme' ) ) );
}
add_action( 'wp_ajax_phantom_newsletter_subscribe', 'phantom_theme_newsletter_subscribe' );
add_action( 'wp_ajax_nopriv_phantom_newsletter_subscribe', 'phantom_theme_newsletter_subscribe' );
