<?php
/**
 * Phantom Core Shell
 *
 * @package PhantomCore
 * @version 1.5.0
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

        // WooCommerce SPA shell compatibility filters
        if ( class_exists( 'WooCommerce' ) ) {
            add_filter( 'woocommerce_disable_template_redirect', '__return_true' );
            add_filter( 'woocommerce_cart_redirect_after_add', '__return_false' );
            add_filter( 'woocommerce_enable_ajax_add_to_cart', '__return_false' );
            add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_woo_assets' ), 20 );
        }

        // Cache invalidation on content changes
        add_action( 'save_post', array( $this, 'invalidate_cache_on_save' ), 10, 1 );
        add_action( 'delete_post', array( $this, 'invalidate_cache_on_save' ), 10, 1 );
        add_action( 'woocommerce_delete_product', array( $this, 'invalidate_cache_on_save' ), 10, 1 );

        add_action( 'template_redirect', array( $this, 'handle_request' ), 0 );
    }

    public function handle_request(): void {
        // Early bypass for WordPress special pages and cron
        if ( is_feed() || is_robots() || is_trackback() ) {
            return;
        }
        if ( isset( $_SERVER['REQUEST_URI'] ) && false !== strpos( $_SERVER['REQUEST_URI'], 'wp-cron' ) ) {
            return;
        }

        $request_uri = esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ?? '/' ) );
        $path = parse_url( $request_uri, PHP_URL_PATH );
        if ( false === $path ) {
            $path = '/';
        }
        $slug = trim( $path, '/' );

		// Bypass: Let WordPress REST API, admin, system files, static assets, and WooCommerce URL actions pass through
		if (
			strpos( $slug, 'wp-json' ) === 0 ||
			strpos( $slug, 'wp-admin' ) === 0 ||
			strpos( $slug, 'wp-login' ) === 0 ||
			strpos( $slug, 'xmlrpc' ) === 0 ||
			'robots.txt' === $slug ||
			'sitemap.xml' === $slug ||
			0 === strpos( $slug, 'feed/' ) ||
			'feed' === $slug ||
			0 === strpos( $slug, '.well-known/' ) ||
			isset( $_GET['rest_route'] ) ||
			isset( $_GET['wc-ajax'] ) ||
			isset( $_GET['add-to-cart'] ) ||
			isset( $_GET['remove_item'] ) ||
			isset( $_GET['empty_cart'] ) ||
			isset( $_GET['apply_coupon'] ) ||
			isset( $_GET['remove_coupon'] ) ||
			preg_match( '/\.(php|css|js|png|jpg|jpeg|gif|ico|svg|webp|woff2?|txt|xml)(\/.*)?$/', $slug )
		) {
            status_header( 200 );
            return;
        }

        // Check if this is a Customizer preview request
        $is_customizer_preview = isset( $_GET['customize_changeset_uuid'] );

        // Disable ALL WordPress frontend output (only when shell serves the page, NOT in Customizer preview)
        if ( ! $is_customizer_preview ) {
            remove_action( 'wp_head', 'wp_enqueue_scripts', 1 );
            remove_action( 'wp_head', 'wp_print_styles', 1 );
            remove_action( 'wp_head', 'wp_print_head_scripts', 1 );
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
            $product_slug = sanitize_title( $matches[1] );
            $product_query = new \WP_Query( array(
                'name'             => $product_slug,
                'post_type'        => 'product',
                'posts_per_page'   => 1,
                'post_status'      => 'publish',
                'suppress_filters' => true,
            ) );
            $product_data = $product_query->have_posts() ? $product_query->posts[0] : null;
            if ( ! $product_data && function_exists( 'wc_get_product_id_by_slug' ) ) {
                $product_id_by_slug = wc_get_product_id_by_slug( $product_slug );
                if ( $product_id_by_slug ) {
                    $_GET['product_id'] = $product_id_by_slug;
                }
            }
            if ( $product_data ) {
                $_GET['product_id'] = $product_data->ID;
            }
        }
        // Handle post detail pages
        elseif ( preg_match( '/^blog\/(.+)$/', $slug, $matches ) ) {
            $template = 'single-blog.html';
            $post_slug = sanitize_title( $matches[1] );
            $post_query = new \WP_Query( array(
                'name'             => $post_slug,
                'post_type'        => 'post',
                'posts_per_page'   => 1,
                'post_status'      => 'publish',
                'suppress_filters' => true,
            ) );
            $post = $post_query->have_posts() ? $post_query->posts[0] : null;
            if ( $post ) {
                $_GET['post_id'] = $post->ID;
            }
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

        // Inject dark mode body attribute from cookie
        if ( isset( $_COOKIE['phantom_dark_mode'] ) && '1' === $_COOKIE['phantom_dark_mode'] ) {
            $html = preg_replace( '/<body(\s[^>]*)?>/', '<body$1 data-phantom-dark-mode="true">', $html, 1 );
        }

        // Inject loading state for SPA transitions
        $loading_html = '<div id="phantom-loading" role="status" aria-hidden="true" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:var(--bg-color,#fff);z-index:9999;align-items:center;justify-content:center;transition:opacity .3s"><div style="width:40px;height:40px;border:3px solid var(--border-color,#e5e7eb);border-top-color:var(--accent-color,#6366f1);border-radius:50%;animation:phantom-spin .8s linear infinite"></div></div>';
        $html = preg_replace( '/<body[^>]*>/', '$0' . "\n" . $loading_html, $html, 1 );
        $html = str_replace( '</head>', '<style id="phantom-loading-css">@keyframes phantom-spin{to{transform:rotate(360deg)}}</style></head>', $html );

        // Server-side SEO injection
        $html = $this->inject_seo( $html, $slug, $template );

		// Inject Google Fonts link tag (skipped in Customizer preview where wp_head() handles it)
		if ( ! $is_customizer_preview ) {
			$html = $this->inject_google_fonts( $html );
		}

		// Inject custom images (logo, hero banner, favicon)
		$html = $this->inject_images( $html );

		// Inject Customizer CSS variables for initial page render
		$html = $this->inject_customizer_css( $html );

		// Inject frontend editor for admin users
		$html = $this->inject_editor( $html );

		// Inject PhantomBridge data + script
		$html = $this->inject_bridge( $html );

		// Plugin compatibility hooks — plugins can inject content before </head> and </body>
		ob_start();
		do_action( 'phantom_before_head_close' );
		$head_hook = ob_get_clean();
		if ( '' !== $head_hook ) {
			$html = str_replace( '</head>', $head_hook . '</head>', $html );
		}

		ob_start();
		do_action( 'phantom_before_body_close' );
		$body_hook = ob_get_clean();
		if ( '' !== $body_hook ) {
			$html = str_replace( '</body>', $body_hook . '</body>', $html );
		}

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
        // Canonical URL — use actual WordPress permalink
        $meta .= sprintf( '<link rel="canonical" href="%s" />', esc_url( $current_url ) );
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
        // Hreflang — use site language
        $locale = get_locale();
        $meta .= sprintf( '<link rel="alternate" href="%s" hreflang="%s" />', esc_url( $current_url ), esc_attr( $locale ) );

        // JSON-LD structured data
        $json_ld_graph = array(
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
        );

        // Page-type-specific schema
        if ( preg_match( '/^product/', $slug ) && function_exists( 'wc_get_product' ) ) {
            $product_id = isset( $_GET['product_id'] ) ? (int) $_GET['product_id'] : 0;
            if ( $product_id ) {
                $product = wc_get_product( $product_id );
                if ( $product ) {
                    $product_url = $product->get_permalink();
                    $image_id    = $product->get_image_id();
                    $image_url   = $image_id ? wp_get_attachment_url( $image_id ) : '';

                    $schema_offers = array(
                        '@type'         => 'Offer',
                        'price'         => (string) $product->get_price(),
                        'priceCurrency' => get_woocommerce_currency(),
                        'availability'  => $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                        'url'           => $product_url,
                    );

                    $product_schema = array(
                        '@type'       => 'Product',
                        '@id'         => $product_url . '#product',
                        'name'        => $product->get_name(),
                        'description' => $product->get_description() ?: $product->get_short_description(),
                        'url'         => $product_url,
                        'sku'         => $product->get_sku(),
                        'offers'      => $schema_offers,
                    );

                    if ( $image_url ) {
                        $product_schema['image'] = $image_url;
                    }

                    // Add brand if set
                    $brand = $product->get_attribute( 'brand' );
                    if ( $brand ) {
                        $product_schema['brand'] = array(
                            '@type' => 'Brand',
                            'name'  => $brand,
                        );
                    }

                    // Add mpn for products without SKU
                    if ( ! $product->get_sku() ) {
                        $product_schema['mpn'] = (string) $product_id;
                    }

                    // Add review + aggregateRating if WC reviews enabled
                    if ( $product->get_review_count() > 0 ) {
                        $product_schema['aggregateRating'] = array(
                            '@type'       => 'AggregateRating',
                            'ratingValue' => (string) $product->get_average_rating(),
                            'reviewCount' => (string) $product->get_review_count(),
                        );
                        $reviews = get_comments( array(
                            'post_id' => $product_id,
                            'status'  => 'approve',
                            'type'    => 'review',
                            'number'  => 3,
                        ) );
                        if ( ! empty( $reviews ) ) {
                            foreach ( $reviews as $review ) {
                                $rating = get_comment_meta( $review->comment_ID, 'rating', true );
                                $product_schema['review'][] = array(
                                    '@type'       => 'Review',
                                    'reviewRating' => array(
                                        '@type'       => 'Rating',
                                        'ratingValue' => (string) $rating,
                                        'bestRating'  => '5',
                                    ),
                                    'author'      => array(
                                        '@type' => 'Person',
                                        'name'  => $review->comment_author,
                                    ),
                                    'reviewBody'  => wp_strip_all_tags( $review->comment_content ),
                                );
                            }
                        }
                    }

                    $json_ld_graph[] = $product_schema;

                    // Product OG meta tags
                    $meta .= sprintf( '<meta property="og:type" content="product" />' . "\n" );
                    $meta .= sprintf( '<meta property="og:description" content="%s" />' . "\n", esc_attr( wp_strip_all_tags( $product->get_short_description() ) ) );
                    $meta .= sprintf( '<meta property="og:image" content="%s" />' . "\n", esc_url( $image_url ) );
                    $meta .= sprintf( '<meta property="product:price:amount" content="%s" />' . "\n", esc_attr( (string) $product->get_price() ) );
                    $meta .= sprintf( '<meta property="product:price:currency" content="%s" />' . "\n", esc_attr( get_woocommerce_currency() ) );
                    $meta .= sprintf( '<meta property="product:retailer_item_id" content="%s" />' . "\n", esc_attr( $product->get_sku() ?: (string) $product_id ) );
                    $meta .= sprintf( '<meta name="twitter:description" content="%s" />' . "\n", esc_attr( wp_strip_all_tags( $product->get_short_description() ) ) );
                }
            }
        } elseif ( preg_match( '/^(blog|post|single-blog)/', $slug ) ) {
            $post_id = isset( $_GET['post_id'] ) ? (int) $_GET['post_id'] : 0;
            if ( $post_id ) {
                $post = get_post( $post_id );
                if ( $post ) {
                    $json_ld_graph[] = array(
                        '@type'       => 'BlogPosting',
                        'headline'    => $post->post_title,
                        'datePublished' => $post->post_date,
                        'dateModified'  => $post->post_modified,
                        'author'      => array(
                            '@type' => 'Person',
                            'name'  => get_the_author_meta( 'display_name', $post->post_author ),
                        ),
                    );
                }
            }
        }

        $json_ld = json_encode( array(
            '@context' => 'https://schema.org',
            '@graph'   => $json_ld_graph,
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
        $map = $this->get_css_var_map();
        $css = '';
        foreach ( $map as $key => $var ) {
            $val = null;
            if ( isset( $options[ $key ] ) && '' !== $options[ $key ] ) {
                $val = $options[ $key ];
            } else {
                $individual = get_option( 'phantom_' . $key, null );
                if ( null !== $individual && '' !== $individual ) {
                    $val = $individual;
                }
            }
            if ( null !== $val ) {
                if ( in_array( $key, $this->get_px_keys(), true ) && is_numeric( $val ) ) {
                    $val .= 'px';
                }
                $css .= $var . ':' . esc_attr( $val ) . ';';
            }
        }
		if ( '' === $css ) {
			return $html;
		}
		$css = $this->minify_css( $css );
		$extra_css = \Phantom_Custom_CSS::instance()->render_style();
		return str_replace( '</head>', '<style id="phantom-customizer-css">:root{' . $css . '}</style>' . $extra_css . '</head>', $html );
    }

	private function inject_images( string $html ): string {
		$options = get_option( 'phantom_options', array() );

		$logo = $options['general_site_logo'] ?? '';
		if ( '' !== $logo ) {
			$html = str_replace( 'assets/images/logo.png', esc_url( $logo ), $html );
		}

		$footer_logo = $options['footer_logo'] ?? '';
		if ( '' !== $footer_logo ) {
			$html = str_replace( 'assets/images/footer-logo.png', esc_url( $footer_logo ), $html );
		}

		$banner_img = $options['hero_banner_image'] ?? '';
		if ( '' !== $banner_img ) {
			$html = str_replace( 'assets/images/banner-img1.png', esc_url( $banner_img ), $html );
		}

		$favicon = $options['branding_favicon'] ?? '';
		if ( '' !== $favicon ) {
			$favicon_url = esc_url( $favicon );
			$html = preg_replace(
				'/<link[^>]+rel="(?:apple-touch-icon|icon)"[^>]+href="[^"]+"/i',
				'<link rel="icon" type="image/x-icon" href="' . $favicon_url . '" sizes="32x32"',
				$html,
				1
			);
		}

		if ( false === strpos( $html, 'rel="icon"' ) && false === strpos( $html, "rel='icon'" ) ) {
			$default_favicon = PHANTOM_CORE_URL . 'frontend/assets/images/favicon.svg';
			$html = str_replace(
				'</head>',
				'<link rel="icon" type="image/svg+xml" href="' . esc_url( $default_favicon ) . '" sizes="any">' . "\n" . '</head>',
				$html
			);
		}

		$img2 = $options['home_banner_img1'] ?? '';
		if ( '' !== $img2 ) {
			$html = str_replace( 'assets/images/banner-img2.png', esc_url( $img2 ), $html );
		}

		return $html;
	}

	private function inject_editor( string $html ): string {
		if ( ! is_user_logged_in() || ! current_user_can( 'edit_theme_options' ) ) {
			return $html;
		}

		$ver = PHANTOM_CORE_VERSION;

		// Add data-phantom-key attributes to known editable elements
		$html = preg_replace(
			'/(class="d-inline-block primary-text text-uppercase banner-span")/',
			'$1 data-phantom-key="home_banner_heading"',
			$html,
			1
		);
		$html = preg_replace(
			'/(<h1\s+class="font-size92")>/',
			'$1 data-phantom-key="home_banner_title">',
			$html,
			1
		);
		$html = preg_replace(
			'/(<p>)(Discover a world of fun and joy)/',
			'<p data-phantom-key="home_banner_description">$2',
			$html,
			1
		);
		$html = preg_replace(
			'/class="d-inline-block text-size-14(.*?footer-about-text)"/',
			'class="d-inline-block text-size-14$1" data-phantom-key="footer_about_text"',
			$html,
			1
		);
		$html = preg_replace(
			'/class="copyright(.*?)content(.*?)p"/',
			'class="copyright$1content$2p" data-phantom-key="footer_copyright"',
			$html,
			1
		);

		// Editor CSS
		$css_url = PHANTOM_CORE_URL . 'frontend/assets/css/phantom-editor.css?v=' . $ver;
		$editor_css = '<link rel="stylesheet" id="phantom-editor-css" href="' . esc_url( $css_url ) . '" media="all" />';

		// Add body class for JS detection — append to existing class or add new
		if ( preg_match( '/<body\s+class="([^"]*)"/', $html ) ) {
			$html = preg_replace( '/(<body\s+class=")([^"]*)(")/', '$1$2 phantom-editor-enabled$3', $html, 1 );
		} else {
			$html = preg_replace( '/<body(\s[^>]*)?>/', '<body class="phantom-editor-enabled"$1>', $html, 1 );
		}

		// REST API nonce for PUT/DELETE requests
		$nonce = wp_create_nonce( 'wp_rest' );
		$nonce_tag = '<meta name="wp-rest-nonce" content="' . esc_attr( $nonce ) . '" />';

		$assets = $nonce_tag . "\n" . $editor_css;
		$html = str_replace( '</head>', $assets . '</head>', $html );

		return $html;
	}

	private function inject_bridge( string $html ): string {
		$entries = Settings_Registry::get_instance()->get_entries();
		$css_map = Settings_Registry::get_instance()->get_css_var_map();

		$data = array();
		$prefix = 'phantom_';
		foreach ( $entries as $key => $entry ) {
			$option_key = $prefix . $key;
			$data[ $key ] = get_option( $option_key, $entry['default'] ?? '' );
		}
		$data['_cssVarMap'] = $css_map;
		$data['can_edit']  = is_user_logged_in() && current_user_can( 'edit_theme_options' );

		$json = wp_json_encode( $data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT );

		$bridge_url = PHANTOM_CORE_URL . 'frontend/assets/js/phantom-bridge.js?v=' . PHANTOM_CORE_VERSION;
		$bridge_script = '<script id="phantom-bridge-data" type="application/json">' . $json . '</script>';
		$bridge_script .= "\n" . '<script src="' . esc_url( $bridge_url ) . '" id="phantom-bridge-js" onload="(function(){var d;try{d=JSON.parse(document.getElementById(\'phantom-bridge-data\').textContent)}catch(e){};PhantomBridge.init({data:d||{}})})()"></script>';

		$html = str_replace( '</body>', $bridge_script . '</body>', $html );
		return $html;
	}

	private function inject_google_fonts( string $html ): string {
		$options     = get_option( 'phantom_options', array() );
		$body_font   = $options['typography_body_font'] ?? 'Archivo';
		$heading_font = $options['typography_heading_font'] ?? 'Playfair Display';

		$fonts = array();
		if ( $body_font ) {
			$fonts[] = rawurlencode( $body_font ) . ':wght@100;200;300;400;500;600;700;800;900';
		}
		if ( $heading_font ) {
			$fonts[] = rawurlencode( $heading_font ) . ':wght@100;200;300;400;500;600;700;800;900';
		}

		if ( empty( $fonts ) ) {
			return $html;
		}

		$family = implode( '&family=', $fonts );
		$url    = 'https://fonts.googleapis.com/css2?family=' . $family . '&display=swap';
		$link   = sprintf(
			'<link rel="stylesheet" id="phantom-google-fonts-css" href="%s" media="all" />',
			esc_url( $url )
		);

		return str_replace( '</head>', $link . '</head>', $html );
	}

	private function get_css_var_map(): array {
        return Settings_Registry::get_css_var_map();
    }

	private function get_px_keys(): array {
        return Settings_Registry::get_px_keys();
    }

	/**
	 * Minify CSS by stripping comments, whitespace, and newlines.
	 */
	private function minify_css( string $css ): string {
		$css = preg_replace( '/\/\*.*?\*\//s', '', $css );
		$css = preg_replace( '/\s*([{}:;,])\s*/', '$1', $css );
		$css = preg_replace( '/\s{2,}/', ' ', $css );
		return trim( $css );
	}

	/**
	 * Enqueue WooCommerce variation script on product detail pages.
	 */
	public function enqueue_woo_assets(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}
		$is_product = is_singular( 'product' );
		if ( $is_product ) {
			wp_enqueue_script( 'wc-add-to-cart-variation' );
		}
	}

	/**
	 * Invalidate REST API cache when content is saved or deleted.
	 */
	public function invalidate_cache_on_save( int $post_id ): void {
		delete_transient( 'phantom_page_data' );
		$cache_dir = WP_CONTENT_DIR . '/cache/phantom/';
		if ( is_dir( $cache_dir ) ) {
			$files = glob( $cache_dir . '*.css' );
			if ( is_array( $files ) ) {
				array_map( 'unlink', $files );
			}
		}
	}
}
