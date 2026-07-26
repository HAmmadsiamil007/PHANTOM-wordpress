<?php
/**
 * Phantom Core Shell — Thin Orchestrator
 *
 * @package PhantomCore
 * @version 2.0.0
 */

declare(strict_types=1);

namespace PhantomCore;

use PhantomCore\Adapters\Product_Adapter;
use PhantomCore\Adapters\Category_Adapter;
use PhantomCore\Adapters\Menu_Adapter;
use PhantomCore\Adapters\Hero_Adapter;
use PhantomCore\Renderer\Product_Card;
use PhantomCore\Renderer\Category_Card;
use PhantomCore\Renderer\Navigation;
use PhantomCore\Renderer\Hero;
use PhantomCore\Engine\Render_Engine;

defined('ABSPATH') || exit;

class Shell {

    private static ?Shell $instance = null;
    private Render_Engine $engine;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init(): void {
        $this->engine = new Render_Engine();

        // WooCommerce SPA shell compatibility filters
        if (class_exists('WooCommerce')) {
            add_filter('woocommerce_disable_template_redirect', '__return_true');
            add_filter('woocommerce_cart_redirect_after_add', '__return_false');
            add_filter('woocommerce_enable_ajax_add_to_cart', '__return_false');
        }

        add_action('template_redirect', [$this, 'init_wc_session'], 5);
        add_action('save_post', [$this, 'invalidate_cache_on_save'], 10, 1);
        add_action('delete_post', [$this, 'invalidate_cache_on_save'], 10, 1);
        add_action('woocommerce_delete_product', [$this, 'invalidate_cache_on_save'], 10, 1);
        add_action('template_redirect', [$this, 'handle_request'], 10);
    }

    public function init_wc_session(): void {
        if (!class_exists('WooCommerce') || isset(WC()->session)) return;

        $wc_slugs = ['shop', 'product', 'product-detail', 'cart', 'checkout', 'wishlist', 'account'];
        $request_uri = esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'] ?? '/'));
        $path = parse_url($request_uri, PHP_URL_PATH);
        $slug = trim($path ?: '', '/');
        if (in_array($slug, $wc_slugs, true) || preg_match('/^product\//', $slug)) {
            WC()->session = new \WC_Session_Handler();
            WC()->session->init();
        }
    }

    public function handle_request(): void {
        if (is_feed() || is_robots() || is_trackback()) return;
        if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'wp-cron') !== false) return;

        $request_uri = esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'] ?? '/'));
        $path = parse_url($request_uri, PHP_URL_PATH);
        if (false === $path) $path = '/';
        $slug = trim($path, '/');

        // Bypass for WP system pages
        if (
            strpos($slug, 'wp-json') === 0 || strpos($slug, 'wp-admin') === 0 ||
            strpos($slug, 'wp-login') === 0 || strpos($slug, 'xmlrpc') === 0 ||
            'robots.txt' === $slug || 'sitemap.xml' === $slug ||
            strpos($slug, 'feed/') === 0 || 'feed' === $slug ||
            strpos($slug, '.well-known/') === 0 ||
            isset($_GET['rest_route']) || isset($_GET['wc-ajax']) ||
            isset($_GET['add-to-cart']) || isset($_GET['remove_item']) ||
            isset($_GET['empty_cart']) || isset($_GET['apply_coupon']) ||
            isset($_GET['remove_coupon']) ||
            preg_match('/\.(php|css|js|png|jpg|jpeg|gif|ico|svg|webp|woff2?|txt|xml)(\/.*)?$/i', $slug)
        ) {
            status_header(200);
            return;
        }

        $is_customizer_preview = isset($_GET['customize_changeset_uuid']);

        // Disable WP frontend output (only when shell serves the page)
        if (!$is_customizer_preview) {
            remove_action('wp_head', 'wp_enqueue_scripts', 1);
            remove_action('wp_head', 'wp_print_styles', 1);
            remove_action('wp_head', 'wp_print_head_scripts', 1);
            remove_action('wp_head', 'feed_links', 2);
            remove_action('wp_head', 'rsd_link');
            remove_action('wp_head', 'wlwmanifest_link');
            remove_action('wp_head', 'wp_shortlink_wp_head');
            remove_action('wp_head', 'rest_output_link_wp_head');
            remove_action('wp_head', 'wp_generator');
            remove_action('wp_head', 'wc_generator_tag');
        }

        // Resolve product / post / category from URL
        if (preg_match('/^product\/(.+)$/', $slug, $matches)) {
            $product_slug = sanitize_title(urldecode($matches[1]));
            $query = new \WP_Query([
                'name' => $product_slug, 'post_type' => 'product',
                'posts_per_page' => 1, 'post_status' => 'publish', 'suppress_filters' => true,
            ]);
            if ($query->have_posts()) {
                $this->engine = $this->engine->with_product_id($query->posts[0]->ID);
            } elseif (function_exists('wc_get_product_id_by_slug')) {
                $id = wc_get_product_id_by_slug($product_slug);
                if ($id) $this->engine = $this->engine->with_product_id($id);
            }
        } elseif (preg_match('/^blog\/(.+)$/', $slug, $matches)) {
            $post_slug = sanitize_title(urldecode($matches[1]));
            $query = new \WP_Query([
                'name' => $post_slug, 'post_type' => 'post',
                'posts_per_page' => 1, 'post_status' => 'publish', 'suppress_filters' => true,
            ]);
            if ($query->have_posts()) {
                $this->engine = $this->engine->with_post_id($query->posts[0]->ID);
            }
        } elseif (preg_match('/^category\/(.+)$/', $slug, $matches)) {
            $this->engine = $this->engine->with_category(sanitize_title($matches[1]));
        }

        // Asset base path replacement: Replace assets/ prefix before output
        $v = '?v=' . PHANTOM_CORE_VERSION;
        $asset_base = PHANTOM_CORE_URL . 'frontend/assets';

        // Render via Engine
        $html = $this->engine->render($slug);

        // Apply asset base path substitution
        $html = preg_replace(
            '/\.?\/?assets\/(bootstrap|css|js|images)\/([a-zA-Z0-9_\-.\/]+)/i',
            $asset_base . '/$1/$2' . $v,
            $html
        );

        // Dynamic copyright year
        $html = preg_replace('/\b2025\b/', date('Y'), $html);

        echo $html;
        exit;
    }

    public function invalidate_cache_on_save(int $post_id): void {
        delete_transient('phantom_page_data');
        $upload_dir = wp_upload_dir();
        $cache_dir = $upload_dir['basedir'] . '/phantom-cache/';
        if (is_dir($cache_dir)) {
            $files = glob($cache_dir . '*.css');
            if (is_array($files)) {
                array_map('unlink', $files);
            }
        }
    }
}
