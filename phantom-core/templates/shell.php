<?php
/**
 * Phantom Core Shell
 *
 * @package PhantomCore
 * @version 1.0.2
 */

declare(strict_types=1);

namespace PhantomCore;

defined( 'ABSPATH' ) || exit;

class Shell {

    private static ?Shell $instance = null;
    private array $routes = array();

    public static function get_instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init(): void {
        $this->routes = array(
            ''              => 'index.html',
            'shop'          => 'shop.html',
            'product'       => 'product-detail.html',
            'product-detail' => 'product-detail.html',
            'about'         => 'about.html',
            'blog'          => 'blog.html',
            'post'          => 'single-blog.html',
            'single-blog'   => 'single-blog.html',
            'contact'       => 'contact.html',
            'cart'          => 'cart.html',
            'checkout'      => 'checkout.html',
            'my-account'    => 'login.html',
            'coming-soon'   => 'coming-soon.html',
            'faq'           => 'faq.html',
            'team'          => 'team.html',
            'testimonials'  => 'testimonials.html',
            'join-now'      => 'join-now.html',
            'thank-you'     => 'thank-you.html',
            'privacy-policy' => 'privacy-policy.html',
            'term-of-use'   => 'term-of-use.html',
            'cookie-policy' => 'cookie-policy.html',
            // Aliases for .html reference fallback
            'login'              => 'login.html',
            'register'           => 'login.html',
            // 'services' => 'services.html',  // file does not exist
            'one-column'         => 'one-column.html',
            'two-column'         => 'two-column.html',
            'three-column'       => 'three-column.html',
            'four-column'        => 'four-column.html',
            'three-colum-sidbar' => 'three-colum-sidbar.html',
            'six-colum-full-wide' => 'six-colum-full-wide.html',
            'load-more'          => 'load-more.html',
        );

        add_action( 'template_redirect', array( $this, 'handle_request' ), 0 );
    }

    public function handle_request(): void {
        $request_uri = esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ?? '/' ) );
        $path = parse_url( $request_uri, PHP_URL_PATH );
        if ( false === $path ) {
            $path = '/';
        }
        $slug = trim( $path, '/' );

        // Bypass: Let WordPress REST API, admin, wp-content pass through
        if (
            strpos( $slug, 'wp-json' ) === 0 ||
            strpos( $slug, 'wp-admin' ) === 0 ||
            strpos( $slug, 'wp-login' ) === 0 ||
            strpos( $slug, 'xmlrpc' ) === 0 ||
            isset( $_GET['rest_route'] ) ||
            isset( $_GET['wc-ajax'] ) ||
            preg_match( '/\.(php|css|js|png|jpg|jpeg|gif|ico|svg|webp|woff2?)(\/.*)?$/', $slug )
        ) {
            status_header( 200 );
            return;
        }

        // Check if this is a Customizer preview request
        $is_customizer_preview = isset( $_GET['customize_changeset_uuid'] );

        // Disable ALL WordPress frontend output (only when shell serves the page, NOT in Customizer preview)
        if ( ! $is_customizer_preview ) {
            remove_action( 'wp_head', 'wp_enqueue_scripts', 8 );
            remove_action( 'wp_head', 'wp_print_styles', 8 );
            remove_action( 'wp_head', 'wp_print_head_scripts', 9 );
            remove_action( 'wp_head', 'feed_links', 2 );
            remove_action( 'wp_head', 'rsd_link' );
            remove_action( 'wp_head', 'wlwmanifest_link' );
            remove_action( 'wp_head', 'wp_shortlink_wp_head' );
            remove_action( 'wp_head', 'rest_output_link_wp_head' );
            remove_action( 'wp_head', 'wp_generator' );
            remove_action( 'wp_head', 'wc_generator_tag' );
        }

        // Handle product detail pages
        if ( preg_match( '/^product\/(.+)$/', $slug, $matches ) ) {
            $template = 'product-detail.html';
        }
        // Handle post detail pages
        elseif ( preg_match( '/^blog\/(.+)$/', $slug, $matches ) ) {
            $template = 'single-blog.html';
        }
        // Normal route — try exact slug, then strip .html suffix
        else {
            $template = $this->routes[ $slug ]
                ?? $this->routes[ preg_replace( '/\.html$/', '', $slug ) ]
                ?? null;
        }

        // Default to 200 OK — WordPress may default to 404 on template_redirect
        status_header( 200 );

        // If no match, 404
        if ( ! $template ) {
            $template = '404.html';
            status_header( 404 );
        }

        // Full path to HTML file
        $html_file = PHANTOM_CORE_PATH . 'frontend/' . $template;

        // If file missing, 404
        if ( ! file_exists( $html_file ) ) {
            $html_file = PHANTOM_CORE_PATH . 'frontend/404.html';
            status_header( 404 );
        }

        // Read HTML
        $html = file_get_contents( $html_file );

        // Server-side SEO injection
        $html = $this->inject_seo( $html, $slug, $template );

        // Inject Customizer CSS variables for initial page render
        $html = $this->inject_customizer_css( $html );

        // In Customizer preview, inject WordPress scripts into Phantom Core HTML
        if ( $is_customizer_preview ) {
            ob_start();
            wp_head();
            $wp_head_output = ob_get_clean();
            $html = str_replace( '</head>', $wp_head_output . '</head>', $html );

            ob_start();
            wp_footer();
            $wp_footer_output = ob_get_clean();
            $html = str_replace( '</body>', $wp_footer_output . '</body>', $html );
        }

        // Security headers
        header( 'Content-Type: text/html; charset=UTF-8' );
        if ( ! $is_customizer_preview ) {
            header( "Content-Security-Policy: default-src 'self' https: data:; script-src 'self' 'unsafe-inline' 'unsafe-eval' https:; style-src 'self' 'unsafe-inline' https:; img-src 'self' https: data:; font-src 'self' https:; connect-src 'self' https:; frame-src 'self' https:;" );
        } else {
            // Relaxed CSP for Customizer preview (needs to load WordPress admin scripts + data: fonts)
            header( "Content-Security-Policy: default-src 'self' https: data:; script-src 'self' 'unsafe-inline' 'unsafe-eval' https: http:; style-src 'self' 'unsafe-inline' https:; img-src 'self' https: data:; font-src 'self' https: data:; connect-src 'self' https: http:; frame-src 'self' https:;" );
        }
        header( 'X-Content-Type-Options: nosniff' );
        header( 'X-Frame-Options: SAMEORIGIN' );
        header( 'Referrer-Policy: strict-origin-when-cross-origin' );
        echo $html;
        exit;
    }

    private function inject_seo( string $html, string $slug, string $template ): string {
        // Step 1: Replace asset base paths FIRST so SEO meta is not double-processed
        $v = '?v=' . PHANTOM_CORE_VERSION;
        $asset_base = PHANTOM_CORE_URL . 'frontend/assets';
        $html = preg_replace( '/\.?\/?assets\/(bootstrap|css|js|images)\/([^\s"\'<>?]+)/', $asset_base . '/$1/$2' . $v, $html );

        // Step 2: Get WordPress data
        $site_name = get_bloginfo( 'name' );
        $site_desc = get_bloginfo( 'description' );
        $home_url  = home_url( '/' );
        $current_url = home_url( add_query_arg( array() ) );

        // Build page title
        $page_titles = array(
            ''               => $site_name . ' – ' . $site_desc,
            'about'          => 'About Us – ' . $site_name,
            'blog'           => 'Blog – ' . $site_name,
            'cart'           => 'Shopping Cart – ' . $site_name,
            'checkout'       => 'Checkout – ' . $site_name,
            'contact'        => 'Contact – ' . $site_name,
            'coming-soon'    => 'Coming Soon – ' . $site_name,
            'cookie-policy'  => 'Cookie Policy – ' . $site_name,
            'faq'            => 'Frequently Asked Questions – ' . $site_name,
            'join-now'       => 'Join Now – ' . $site_name,
            'login'          => 'Login – ' . $site_name,
            'my-account'     => 'My Account – ' . $site_name,
            'post'           => '{post_title} – ' . $site_name,
            'privacy-policy' => 'Privacy Policy – ' . $site_name,
            'product'        => '{product_name} – ' . $site_name,
            'product-detail' => '{product_name} – ' . $site_name,
            'register'       => 'Register – ' . $site_name,
            'shop'           => 'Shop – ' . $site_name,
            'single-blog'    => '{post_title} – ' . $site_name,
            'team'           => 'Our Team – ' . $site_name,
            'term-of-use'    => 'Terms of Use – ' . $site_name,
            'testimonials'   => 'Testimonials – ' . $site_name,
            'thank-you'      => 'Thank You – ' . $site_name,
            'one-column'         => 'Blog – ' . $site_name,
            'two-column'         => 'Blog – ' . $site_name,
            'three-column'       => 'Blog – ' . $site_name,
            'four-column'        => 'Blog – ' . $site_name,
            'three-colum-sidbar' => 'Blog – ' . $site_name,
            'six-colum-full-wide' => 'Blog – ' . $site_name,
            'load-more'          => 'Blog – ' . $site_name,
        );

        $title = $page_titles[ $slug ] ?? $site_name;

        // Replace product_name placeholder
        if ( strpos( $title, '{product_name}' ) !== false ) {
            $product_id = isset( $_GET['product_id'] ) ? (int) $_GET['product_id'] : 0;
            if ( $product_id && function_exists( 'wc_get_product' ) ) {
                $product = wc_get_product( $product_id );
                if ( $product ) {
                    $title = str_replace( '{product_name}', $product->get_name(), $title );
                }
            }
        }

        // Replace post_title placeholder
        if ( strpos( $title, '{post_title}' ) !== false ) {
            $post_id = isset( $_GET['post_id'] ) ? (int) $_GET['post_id'] : 0;
            if ( $post_id ) {
                $post = get_post( $post_id );
                if ( $post ) {
                    $title = str_replace( '{post_title}', $post->post_title, $title );
                }
            }
        }

        // Get featured image for social
        $image_url = PHANTOM_CORE_URL . 'frontend/assets/images/logo.png';

        // Build meta tags
        $title_tag = sprintf(
            '<title>%s</title>',
            esc_html( $title )
        );
        // Replace existing title tag to avoid duplicates
        $html = preg_replace( '/<title>[^<]*<\/title>/i', $title_tag, $html, 1 );
        // If no existing title was replaced, prepend after <head>
        $meta = '';
        $meta .= sprintf(
            '<meta name="description" content="%s" />',
            esc_attr( $site_desc )
        );
        // Open Graph
        $meta .= sprintf( '<meta property="og:title" content="%s" />', esc_attr( $title ) );
        $meta .= sprintf( '<meta property="og:description" content="%s" />', esc_attr( $site_desc ) );
        $meta .= sprintf( '<meta property="og:url" content="%s" />', esc_url( $current_url ) );
        $meta .= sprintf( '<meta property="og:image" content="%s" />', esc_url( $image_url ) );
        $meta .= '<meta property="og:type" content="website" />';
        $meta .= sprintf( '<meta property="og:site_name" content="%s" />', esc_attr( $site_name ) );
        // Twitter Card
        $meta .= '<meta name="twitter:card" content="summary_large_image" />';
        $meta .= sprintf( '<meta name="twitter:title" content="%s" />', esc_attr( $title ) );
        $meta .= sprintf( '<meta name="twitter:description" content="%s" />', esc_attr( $site_desc ) );

        // JSON-LD structured data
        $json_ld = json_encode( array(
            '@context' => 'https://schema.org',
            '@graph'   => array(
                array(
                    '@type' => 'Organization',
                    'name'  => $site_name,
                    'url'   => $home_url,
                ),
                array(
                    '@type'       => 'WebSite',
                    'name'        => $site_name,
                    'url'         => $home_url,
                    'potentialAction' => array(
                        '@type'       => 'SearchAction',
                        'target'      => array(
                            '@type'       => 'EntryPoint',
                            'urlTemplate' => $home_url . '?s={search_term_string}',
                        ),
                        'query-input' => 'required name=search_term_string',
                    ),
                ),
            ),
        ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP );

        $meta .= sprintf( '<script type="application/ld+json">%s</script>', $json_ld );

        // WooCommerce nonces for AJAX cart/checkout
        if ( function_exists( 'wp_create_nonce' ) ) {
            $wc_nonce = wp_create_nonce( 'wc_store_api' );
            $meta .= sprintf( '<meta name="wc-nonce" content="%s" />', esc_attr( $wc_nonce ) );
        }

        // Inject base tag for proper relative URL resolution
        $base_tag = sprintf( '<base href="%s" />', esc_url( $home_url ) );
        $meta = $base_tag . "\n" . $meta;

        // Inject meta tags after <head>
        $html = str_replace( '<head>', "<head>\n{$meta}", $html );

        return $html;
    }

    /**
     * Inject Customizer CSS variables inline for initial page render.
     */
    private function inject_customizer_css( string $html ): string {
        $options = get_option( 'phantom_options', array() );
        if ( empty( $options ) ) {
            return $html;
        }
        $map = $this->get_css_var_map();
        $css = '';
        foreach ( $map as $key => $var ) {
            if ( isset( $options[ $key ] ) && '' !== $options[ $key ] ) {
                $val = $options[ $key ];
                if ( in_array( $key, $this->get_px_keys(), true ) && is_numeric( $val ) ) {
                    $val .= 'px';
                }
                $css .= $var . ':' . esc_attr( $val ) . ';';
            }
        }
        if ( '' === $css ) {
            return $html;
        }
        return str_replace( '</head>', '<style id="phantom-customizer-css">:root{' . $css . '}</style></head>', $html );
    }

    private function get_css_var_map(): array {
        return array(
            'color_primary'              => '--primary--color',
            'color_secondary'            => '--secondary--color',
            'color_accent'               => '--accent--color',
            'color_text'                 => '--text--color',
            'color_heading'              => '--heading--color',
            'color_header_bg'            => '--header--bg',
            'color_footer_bg'            => '--footer--bg',
            'color_border'               => '--border--color',
            'color_sale'                 => '--sale--color',
            'color_link'                 => '--link--color',
            'color_link_hover'           => '--link--hover--color',
            'color_background'           => '--color-background',
            'typography_heading_font'    => '--heading--font',
            'typography_body_font'       => '--body--font',
            'typography_base_size'       => '--base--font--size',
            'typography_line_height'     => '--line--height',
            'typography_heading_weight'  => '--font--weight',
            'typography_body_weight'     => '--font--weight',
            'button_bg'                  => '--button--bg',
            'button_text'                => '--button--text--color',
            'button_bg_hover'            => '--button--hover--bg',
            'button_text_hover'          => '--button--text-hover',
            'button_radius'              => '--btn--border--radius',
            'button_padding_y'           => '--btn--padding-y',
            'button_padding_x'           => '--btn--padding-x',
            'button_font_size'           => '--btn--font-size',
            'container_width'            => '--container--width',
            'container_gutter'           => '--container--gutter',
            'content_width'              => '--content--width',
            'content_gap'                => '--content--gap',
            'sidebar_width'              => '--sidebar--width',
            'widget_spacing'             => '--widget--spacing',
            'announcement_bar_bg'        => '--announcement-bar-bg',
            'announcement_bar_text_color' => '--announcement-bar-color',
            'header_height'              => '--header--height',
            'section_padding_y'          => '--section--padding-y',
            'section_padding_x'          => '--section--padding-x',
            'form_input_radius'          => '--form-input-radius',
            'form_input_height'          => '--form-input-height',
            'breakpoint_xl'              => '--breakpoint-xl',
            'breakpoint_lg'              => '--breakpoint-lg',
            'breakpoint_md'              => '--breakpoint-md',
            'breakpoint_sm'              => '--breakpoint-sm',
        );
    }

    private function get_px_keys(): array {
        return array(
            'typography_base_size', 'header_height',
            'container_width', 'container_gutter', 'content_width', 'content_gap',
            'sidebar_width', 'widget_spacing',
            'button_radius', 'button_padding_y', 'button_padding_x', 'button_font_size',
            'section_padding_y', 'section_padding_x',
            'form_input_radius', 'form_input_height',
            'breakpoint_xl', 'breakpoint_lg', 'breakpoint_md', 'breakpoint_sm',
        );
    }
}
