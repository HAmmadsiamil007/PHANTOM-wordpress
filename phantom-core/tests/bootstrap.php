<?php
/**
 * PHPUnit bootstrap for Phantom Core standalone tests.
 *
 * WordPress-specific integration tests require the WP test suite.
 * These standalone tests verify pure logic without WordPress dependencies.
 */

define( 'ABSPATH', true );
define( 'PHANTOM_CORE_VERSION', '2.0.0' );
$core_path = dirname( __DIR__ ) . '/';
// Ensure consistent directory separators on Windows
$core_path = str_replace( '\\', '/', $core_path );
define( 'PHANTOM_CORE_PATH', $core_path );
define( 'PHANTOM_CORE_URL', 'http://example.com/wp-content/plugins/phantom-core/' );
define( 'PHANTOM_CORE_FILE', PHANTOM_CORE_PATH . 'phantom-core.php' );

// WordPress function stubs for standalone testing
if ( ! function_exists( '__' ) ) {
    function __( $text, $domain = 'default' ) { return $text; }
}
if ( ! function_exists( '_x' ) ) {
    function _x( $text, $context, $domain = 'default' ) { return $text; }
}
if ( ! function_exists( '_n' ) ) {
    function _n( $single, $plural, $number, $domain = 'default' ) { return $number > 1 ? $plural : $single; }
}
if ( ! function_exists( 'esc_html__' ) ) {
    function esc_html__( $text, $domain = 'default' ) { return $text; }
}
if ( ! function_exists( 'esc_html_e' ) ) {
    function esc_html_e( $text, $domain = 'default' ) { echo $text; }
}
if ( ! function_exists( 'esc_textarea' ) ) {
    function esc_textarea( $text ) { return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' ); }
}
if ( ! function_exists( 'esc_attr_e' ) ) {
    function esc_attr_e( $text, $domain = 'default' ) { echo $text; }
}
if ( ! function_exists( 'esc_attr__' ) ) {
    function esc_attr__( $text, $domain = 'default' ) { return $text; }
}
if ( ! function_exists( 'esc_html' ) ) {
    function esc_html( $text ) { return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' ); }
}
if ( ! function_exists( 'esc_url' ) ) {
    function esc_url( $url, $protocols = null, $context = '' ) { return str_replace( [ ' ', '"', "'", '<', '>' ], [ '%20', '%22', '%27', '%3C', '%3E' ], $url ); }
}
if ( ! function_exists( 'esc_attr' ) ) {
    function esc_attr( $text ) { return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' ); }
}
if ( ! function_exists( 'remove_all_filters' ) ) {
    function remove_all_filters( $hook_name = '', $priority = false ) { return true; }
}
if ( ! function_exists( 'add_menu_page' ) ) {
    $GLOBALS['_phantom_menu_pages'] = [];
    function add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function = '', $icon_url = '', $position = null ) {
        $GLOBALS['_phantom_menu_pages'][] = compact( 'page_title', 'menu_title', 'capability', 'menu_slug' );
        return $menu_slug . '_hook';
    }
}
if ( ! function_exists( 'esc_js' ) ) {
    function esc_js( $text ) { return str_replace( [ "'", '"', '<', '>', '&' ], [ "\'", '\"', '\x3C', '\x3E', '\x26' ], $text ); }
}
if ( ! function_exists( 'add_filter' ) ) {
    $GLOBALS['_phantom_filters'] = [];
    function add_filter( $tag, $callback, $priority = 10, $accepted_args = 1 ) {
        $GLOBALS['_phantom_filters'][] = [ 'tag' => $tag, 'callback' => $callback, 'priority' => $priority ];
    }
}
if ( ! function_exists( 'has_filter' ) ) {
    function has_filter( $tag, $callback = false ) {
        if ( ! isset( $GLOBALS['_phantom_filters'] ) ) return false;
        foreach ( $GLOBALS['_phantom_filters'] as $f ) {
            if ( $f['tag'] === $tag ) return $f['priority'];
        }
        return false;
    }
}
if ( ! function_exists( 'apply_filters' ) ) {
    function apply_filters( $tag, $value ) { return $value; }
}
if ( ! function_exists( 'sanitize_text_field' ) ) {
    function sanitize_text_field( $str ) { return trim( preg_replace( '/\s+/', ' ', strip_tags( $str ?? '' ) ) ); }
}
if ( ! function_exists( 'wp_strip_all_tags' ) ) {
    function wp_strip_all_tags( $string, $remove_breaks = false ) {
        $string = preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', $string );
        $string = strip_tags( $string );
        if ( ! $remove_breaks ) {
            $string = preg_replace( '/[\r\n\t ]+/', ' ', $string );
        }
        return trim( $string );
    }
}
if ( ! function_exists( 'trailingslashit' ) ) {
    function trailingslashit( $string ) { return rtrim( $string, '/\\' ) . '/'; }
}
if ( ! function_exists( 'untrailingslashit' ) ) {
    function untrailingslashit( $string ) { return rtrim( $string, '/\\' ); }
}
if ( ! function_exists( 'home_url' ) ) {
    function home_url( $path = '', $scheme = null ) { return 'http://example.com' . ( $path ? '/' . ltrim( $path, '/' ) : '' ); }
}
if ( ! function_exists( 'admin_url' ) ) {
    function admin_url( $path = '', $scheme = 'admin' ) { return 'http://example.com/wp-admin/' . ltrim( $path, '/' ); }
}
if ( ! function_exists( 'get_option' ) ) {
    $GLOBALS['_phantom_options'] = [];
    function get_option( $option, $default = false ) {
        return array_key_exists( $option, $GLOBALS['_phantom_options'] ) ? $GLOBALS['_phantom_options'][ $option ] : $default;
    }
}
if ( ! function_exists( 'update_option' ) ) {
    function update_option( $option, $value, $autoload = null ) {
        $GLOBALS['_phantom_options'][ $option ] = $value;
        return true;
    }
}
if ( ! function_exists( 'plugin_dir_url' ) ) {
    function plugin_dir_url( $file ) { return 'http://example.com/wp-content/plugins/' . basename( dirname( $file ) ) . '/'; }
}
if ( ! function_exists( 'get_template_directory_uri' ) ) {
    function get_template_directory_uri() { return 'http://example.com/wp-content/themes/phantom-core'; }
}
if ( ! function_exists( 'wp_create_nonce' ) ) {
    function wp_create_nonce( $action = -1 ) { return md5( $action . '|secret' ); }
}
if ( ! function_exists( 'rest_url' ) ) {
    function rest_url( $path = '' ) { return 'http://example.com/wp-json/' . ltrim( $path, '/' ); }
}
if ( ! function_exists( 'is_user_logged_in' ) ) {
    function is_user_logged_in() { return false; }
}
if ( ! function_exists( 'wp_get_theme' ) ) {
    function wp_get_theme() {
        return new class() {
            public function get( $key ) { return $key === 'Name' ? 'Phantom Theme' : '1.0'; }
        };
    }
}
if ( ! function_exists( 'flush_rewrite_rules' ) ) {
    function flush_rewrite_rules( $hard = true ) {}
}
if ( ! function_exists( 'do_action' ) ) {
    function do_action( $tag, ...$args ) {}
}
if ( ! function_exists( 'add_action' ) ) {
    $GLOBALS['_phantom_actions'] = [];
    function add_action( $tag, $callback, $priority = 10, $accepted_args = 1 ) {
        $GLOBALS['_phantom_actions'][] = [ 'tag' => $tag, 'callback' => $callback, 'priority' => $priority ];
    }
}
if ( ! function_exists( 'add_submenu_page' ) ) {
    $GLOBALS['_phantom_submenu_pages'] = [];
    function add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function = '' ) {
        $GLOBALS['_phantom_submenu_pages'][] = compact( 'parent_slug', 'page_title', 'menu_title', 'capability', 'menu_slug' );
        return $menu_slug . '_hook';
    }
}
if ( ! function_exists( 'wp_enqueue_style' ) ) {
    function wp_enqueue_style( $handle, $src = '', $deps = [], $ver = false, $media = 'all' ) {}
}
if ( ! function_exists( 'wp_enqueue_script' ) ) {
    function wp_enqueue_script( $handle, $src = '', $deps = [], $ver = false, $in_footer = false ) {}
}
if ( ! function_exists( 'wp_localize_script' ) ) {
    function wp_localize_script( $handle, $object_name, $l10n ) {}
}
if ( ! function_exists( 'current_user_can' ) ) {
    function current_user_can( $capability, ...$args ) { return true; }
}
if ( ! function_exists( 'check_admin_referer' ) ) {
    function check_admin_referer( $action = -1, $query_arg = '_wpnonce' ) {}
}
if ( ! function_exists( 'wp_send_json_success' ) ) {
    function wp_send_json_success( $data = null, $status_code = null ) { echo wp_json_encode( [ 'success' => true, 'data' => $data ] ); throw new \RuntimeException( 'wp_send_json_success' ); }
}
if ( ! function_exists( 'wp_send_json_error' ) ) {
    function wp_send_json_error( $data = null, $status_code = null ) { echo wp_json_encode( [ 'success' => false, 'data' => $data ] ); throw new \RuntimeException( 'wp_send_json_error' ); }
}
if ( ! function_exists( 'sanitize_key' ) ) {
    function sanitize_key( $key ) { return strtolower( preg_replace( '/[^a-z0-9_-]/', '', $key ) ); }
}
if ( ! function_exists( 'wp_verify_nonce' ) ) {
    function wp_verify_nonce( $nonce, $action = -1 ) { return $nonce === md5( $action . '|secret' ); }
}
if ( ! function_exists( 'wp_die' ) ) {
    function wp_die( $message = '', $title = '', $args = [] ) { throw new \RuntimeException( $message ); }
}
if ( ! function_exists( 'wp_safe_redirect' ) ) {
    function wp_safe_redirect( $location, $status = 302 ) { throw new \RuntimeException( 'redirect:' . $location ); }
}
if ( ! function_exists( 'wp_get_referer' ) ) {
    function wp_get_referer() { return 'http://example.com/wp-admin/admin.php?page=phantom-demo-manager'; }
}
if ( ! function_exists( 'submit_button' ) ) {
    function submit_button( $text = '', $type = 'primary', $name = 'submit', $wrap = true, $other_attributes = null ) { echo '<input type="submit" />'; }
}
if ( ! function_exists( 'wp_nonce_field' ) ) {
    function wp_nonce_field( $action = -1, $name = '_wpnonce', $referer = true, $echo = true ) { echo '<input type="hidden" />'; }
}
if ( ! function_exists( 'is_admin' ) ) {
    function is_admin() { return true; }
}
if ( ! function_exists( 'get_bloginfo' ) ) {
    function get_bloginfo( $show = '', $filter = 'raw' ) {
        $values = [ 'name' => 'Phantom Test', 'description' => 'A test site', 'url' => 'http://example.com', 'version' => '6.7' ];
        return $values[ $show ] ?? 'Phantom Test';
    }
}
if ( ! function_exists( 'status_header' ) ) {
    function status_header( $code ) {}
}
if ( ! function_exists( 'wp_json_encode' ) ) {
    function wp_json_encode( $data, $options = 0, $depth = 512 ) { return json_encode( $data, $options, $depth ); }
}
if ( ! function_exists( 'add_query_arg' ) ) {
    function add_query_arg( ...$args ) { return 'http://example.com/?' . http_build_query( is_array( $args[0] ) ? $args[0] : [ $args[0] => $args[1] ] ); }
}
if ( ! function_exists( 'remove_query_arg' ) ) {
    function remove_query_arg( $keys, $url = '' ) { return $url ?: 'http://example.com/'; }
}
if ( ! function_exists( 'get_theme_mod' ) ) {
    $GLOBALS['_phantom_theme_mods'] = [];
    function get_theme_mod( $name, $default = false ) {
        return array_key_exists( $name, $GLOBALS['_phantom_theme_mods'] ) ? $GLOBALS['_phantom_theme_mods'][ $name ] : $default;
    }
}
if ( ! function_exists( 'set_theme_mod' ) ) {
    function set_theme_mod( $name, $value ) {
        $GLOBALS['_phantom_theme_mods'][ $name ] = $value;
        return true;
    }
}
if ( ! function_exists( 'get_permalink' ) ) {
    function get_permalink( $post = 0 ) { return 'http://example.com/?p=' . ( is_object( $post ) ? $post->ID : $post ); }
}
if ( ! function_exists( 'get_post' ) ) {
    function get_post( $post = null ) { return null; }
}
if ( ! function_exists( 'get_locale' ) ) {
    function get_locale() { return 'en_US'; }
}
if ( ! function_exists( 'get_the_author_meta' ) ) {
    function get_the_author_meta( $field = '', $user_id = 0 ) { return 'admin'; }
}
if ( ! function_exists( 'wp_get_post_categories' ) ) {
    function wp_get_post_categories( $post_id, $args = [] ) { return []; }
}
if ( ! function_exists( 'wp_get_post_tags' ) ) {
    function wp_get_post_tags( $post_id, $args = [] ) { return []; }
}
if ( ! function_exists( 'get_woocommerce_currency' ) ) {
    function get_woocommerce_currency() { return 'USD'; }
}
if ( ! function_exists( 'get_woocommerce_currency_symbol' ) ) {
    function get_woocommerce_currency_symbol( $currency = '' ) { return '$'; }
}
if ( ! function_exists( 'wc_get_product' ) ) {
    function wc_get_product( $product_id ) { return null; }
}
if ( ! function_exists( 'wc_get_page_id' ) ) {
    function wc_get_page_id( $page ) { return 0; }
}
if ( ! function_exists( 'get_term' ) ) {
    function get_term( $term, $taxonomy = '' ) { return null; }
}
if ( ! function_exists( 'get_term_link' ) ) {
    function get_term_link( $term, $taxonomy = '' ) { return 'http://example.com/category/'; }
}
if ( ! function_exists( 'wp_get_attachment_url' ) ) {
    function wp_get_attachment_url( $attachment_id ) { return 'http://example.com/wp-content/uploads/image.jpg'; }
}
if ( ! function_exists( 'get_post_thumbnail_id' ) ) {
    function get_post_thumbnail_id( $post = null ) { return 0; }
}
if ( ! function_exists( 'get_comments' ) ) {
    function get_comments( $args = [] ) { return []; }
}
if ( ! function_exists( 'get_comment_meta' ) ) {
    function get_comment_meta( $comment_id, $key = '', $single = false ) { return $single ? '' : []; }
}
if ( ! function_exists( '_doing_it_wrong' ) ) {
    function _doing_it_wrong( $function, $message, $version ) {
        // Silently ignore in tests
    }
}
if ( ! function_exists( 'delete_option' ) ) {
    function delete_option( $option ) {
        if ( array_key_exists( $option, $GLOBALS['_phantom_options'] ) ) {
            unset( $GLOBALS['_phantom_options'][ $option ] );
            return true;
        }
        return false;
    }
}
if ( ! function_exists( 'wp_kses_post' ) ) {
    function wp_kses_post( $data ) { return $data; }
}
if ( ! function_exists( 'is_active_sidebar' ) ) {
    function is_active_sidebar( $index ) { return false; }
}
if ( ! function_exists( 'dynamic_sidebar' ) ) {
    function dynamic_sidebar( $index ) { return false; }
}

// --- WordPress REST API stubs for standalone testing ---
if ( ! class_exists( 'WP_Error' ) ) {
    class WP_Error {
        private $code = '';
        private $message = '';
        private $data = [];
        public function __construct( $code = '', $message = '', $data = [] ) {
            $this->code    = $code;
            $this->message = $message;
            $this->data    = $data;
        }
        public function get_error_code() { return $this->code; }
        public function get_error_message() { return $this->message; }
        public function get_error_data( $code = '' ) { return $this->data; }
    }
}
if ( ! class_exists( 'WP_REST_Response' ) ) {
    class WP_REST_Response {
        private $data;
        private $status;
        private $headers = [];
        public function __construct( $data = null, $status = 200 ) {
            $this->data   = $data;
            $this->status = $status;
        }
        public function get_data() { return $this->data; }
        public function get_status() { return $this->status; }
        public function header( $name, $value ) { $this->headers[ $name ] = $value; }
        public function get_headers() { return $this->headers; }
    }
}
if ( ! class_exists( 'WP_REST_Request' ) ) {
    class WP_REST_Request {
        private $params = [];
        private $headers = [];
        private $body = '';
        private $method = 'GET';
        public function __construct( $method = 'GET', $route = '' ) { $this->method = $method; }
        public function set_param( $key, $value ) { $this->params[ $key ] = $value; }
        public function get_param( $key ) { return $this->params[ $key ] ?? null; }
        public function has_param( $key ) { return array_key_exists( $key, $this->params ); }
        public function set_header( $key, $value ) { $this->headers[ $key ] = $value; }
        public function get_header( $key ) { return $this->headers[ $key ] ?? ''; }
        public function set_body( $body ) { $this->body = $body; }
        public function get_body() { return $this->body; }
        public function get_method() { return $this->method; }
    }
}
if ( ! class_exists( 'WP_REST_Server' ) ) {
    class WP_REST_Server {
        const READABLE   = 'GET';
        const CREATABLE  = 'POST';
        const EDITABLE   = 'PUT';
        const DELETABLE  = 'DELETE';
        const ALLMETHODS = 'GET, POST, PUT, PATCH, DELETE';
    }
}
if ( ! class_exists( 'WP_REST_Controller' ) ) {
    class WP_REST_Controller {
        protected $namespace;
        public function __construct() {}
    }
}

// Stub register_rest_route to collect routes for testing
if ( ! function_exists( 'register_rest_route' ) ) {
    $GLOBALS['_phantom_rest_routes'] = [];
    function register_rest_route( $namespace, $route, $args, $override = false ) {
        $GLOBALS['_phantom_rest_routes'][] = [
            'namespace' => $namespace,
            'route'     => $route,
            'args'      => $args,
        ];
    }
}

// Stub additional WP functions used by Rest_Controller
if ( ! function_exists( 'absint' ) ) {
    function absint( $maybeint ) { return abs( (int) $maybeint ); }
}
if ( ! function_exists( 'wp_unslash' ) ) {
    function wp_unslash( $value ) { return stripslashes( $value ); }
}
if ( ! function_exists( 'is_wp_error' ) ) {
    function is_wp_error( $thing ) { return $thing instanceof WP_Error; }
}
if ( ! function_exists( 'sanitize_title' ) ) {
    function sanitize_title( $title ) { return strtolower( str_replace( [ ' ', '_' ], '-', trim( $title ) ) ); }
}
if ( ! function_exists( 'sanitize_email' ) ) {
    function sanitize_email( $email ) { return filter_var( $email, FILTER_VALIDATE_EMAIL ) ? $email : ''; }
}
if ( ! function_exists( 'sanitize_textarea_field' ) ) {
    function sanitize_textarea_field( $str ) { return sanitize_text_field( $str ); }
}
if ( ! function_exists( 'get_current_user_id' ) ) {
    function get_current_user_id() { return 0; }
}
if ( ! function_exists( 'is_email' ) ) {
    function is_email( $email ) { return (bool) filter_var( $email, FILTER_VALIDATE_EMAIL ); }
}
if ( ! function_exists( 'get_the_title' ) ) {
    function get_the_title( $post = 0 ) { return 'Test Post Title'; }
}
if ( ! function_exists( 'get_the_excerpt' ) ) {
    function get_the_excerpt( $post = null ) { return 'Test excerpt.'; }
}
if ( ! function_exists( 'get_the_date' ) ) {
    function get_the_date( $format = '', $post = null ) { return date( $format ?: 'c' ); }
}
if ( ! function_exists( 'get_the_modified_date' ) ) {
    function get_the_modified_date( $format = '', $post = null ) { return date( $format ?: 'c' ); }
}
if ( ! function_exists( 'get_the_ID' ) ) {
    function get_the_ID() { return 1; }
}
if ( ! function_exists( 'wp_trim_words' ) ) {
    function wp_trim_words( $text, $num_words = 55, $more = '...' ) { return substr( $text, 0, $num_words ) . $more; }
}
if ( ! function_exists( 'get_the_post_thumbnail_url' ) ) {
    function get_the_post_thumbnail_url( $post = null, $size = 'post-thumbnail' ) { return ''; }
}
if ( ! function_exists( 'get_post_types' ) ) {
    function get_post_types( $args = [], $output = 'names', $operator = 'and' ) {
        return [
            'post'       => (object) [ 'name' => 'post', 'label' => 'Posts', 'description' => '', 'hierarchical' => false, 'rest_base' => 'posts' ],
            'page'       => (object) [ 'name' => 'page', 'label' => 'Pages', 'description' => '', 'hierarchical' => true, 'rest_base' => 'pages' ],
            'attachment' => (object) [ 'name' => 'attachment', 'label' => 'Media', 'description' => '', 'hierarchical' => false, 'rest_base' => 'media' ],
            'product'    => (object) [ 'name' => 'product', 'label' => 'Products', 'description' => '', 'hierarchical' => false, 'rest_base' => 'products' ],
        ];
    }
}
if ( ! function_exists( 'get_registered_nav_menus' ) ) {
    function get_registered_nav_menus() {
        return [
            'primary'   => 'Primary Menu',
            'footer'    => 'Footer Menu',
            'phantom_primary'   => 'Phantom Primary',
            'phantom_secondary' => 'Phantom Secondary',
            'phantom_footer'    => 'Phantom Footer',
            'phantom_mobile'    => 'Phantom Mobile',
        ];
    }
}
if ( ! function_exists( 'get_nav_menu_locations' ) ) {
    function get_nav_menu_locations() {
        return [ 'primary' => 2, 'phantom_primary' => 3 ];
    }
}
if ( ! function_exists( 'get_terms' ) ) {
    function get_terms( $args = [] ) { return []; }
}
if ( ! function_exists( 'get_categories' ) ) {
    function get_categories( $args = [] ) { return []; }
}
if ( ! function_exists( 'wp_get_nav_menu_items' ) ) {
    function wp_get_nav_menu_items( $menu_id ) { return false; }
}
if ( ! function_exists( 'get_category_link' ) ) {
    function get_category_link( $term_id ) { return 'http://example.com/category/'; }
}
if ( ! function_exists( 'get_page_by_path' ) ) {
    function get_page_by_path( $slug, $output = OBJECT, $post_type = 'page' ) { return null; }
}
if ( ! function_exists( 'get_term_meta' ) ) {
    function get_term_meta( $term_id, $key = '', $single = false ) { return $single ? '' : []; }
}
if ( ! function_exists( 'setup_postdata' ) ) {
    function setup_postdata( $post ) {}
}
if ( ! function_exists( 'wp_reset_postdata' ) ) {
    function wp_reset_postdata() {}
}
if ( ! function_exists( 'is_multisite' ) ) {
    function is_multisite() { return false; }
}
if ( ! function_exists( 'is_customize_preview' ) ) {
    function is_customize_preview() { return false; }
}
if ( ! function_exists( 'wp_cache_flush_group' ) ) {
    function wp_cache_flush_group( $group ) { return true; }
}

// Stub $wpdb for Cache::flush() and other DB operations
if ( ! isset( $GLOBALS['wpdb'] ) ) {
    $GLOBALS['wpdb'] = new class() {
        public $options = 'wp_options';
        public $sitemeta = 'wp_sitemeta';
        public function esc_like( $str ) { return str_replace( array( '%', '_' ), array( '\\%', '\\_' ), $str ); }
        public function prepare( $query, ...$args ) {
            foreach ( $args as $arg ) {
                $query = preg_replace( '/\%[sd]/', is_string( $arg ) ? "'" . addslashes( $arg ) . "'" : (int) $arg, $query, 1 );
            }
            return $query;
        }
        public function query( $sql ) { return true; }
    };
}

// Stub WP_Query
if ( ! class_exists( 'WP_Query' ) ) {
    class WP_Query {
        public $posts = [];
        public $found_posts = 0;
        public $max_num_pages = 0;
        public function __construct( $args = [] ) {}
        public function have_posts() { return false; }
        public function the_post() {}
    }
}
if ( ! function_exists( 'rest_sanitize_boolean' ) ) {
    function rest_sanitize_boolean( $value ) { return filter_var( $value, FILTER_VALIDATE_BOOLEAN ); }
}
if ( ! function_exists( 'get_transient' ) ) {
    $GLOBALS['_phantom_transients'] = [];
    function get_transient( $key ) {
        $key = 'phantom_' . md5( $key );
        if ( isset( $GLOBALS['_phantom_transients'][ $key ] ) ) {
            $value = $GLOBALS['_phantom_transients'][ $key ];
            if ( $value['exp'] < time() ) {
                unset( $GLOBALS['_phantom_transients'][ $key ] );
                return false;
            }
            return $value['data'];
        }
        return false;
    }
    function set_transient( $key, $value, $expiration = 0 ) {
        $key = 'phantom_' . md5( $key );
        $GLOBALS['_phantom_transients'][ $key ] = [ 'data' => $value, 'exp' => time() + $expiration ];
        return true;
    }
    function delete_transient( $key ) {
        $key = 'phantom_' . md5( $key );
        unset( $GLOBALS['_phantom_transients'][ $key ] );
        return true;
    }
}
if ( ! function_exists( 'current_time' ) ) {
    function current_time( $type, $gmt = 0 ) {
        if ( 'mysql' === $type ) return date( 'Y-m-d H:i:s' );
        if ( 'timestamp' === $type ) return time();
        return time();
    }
}
if ( ! function_exists( 'wp_logout' ) ) {
    function wp_logout() {}
}
if ( ! function_exists( 'wp_upload_dir' ) ) {
    function wp_upload_dir( $time = null ) {
        return [
            'path'    => sys_get_temp_dir() . '/phantom-uploads',
            'url'     => 'http://example.com/wp-content/uploads',
            'subdir'  => '',
            'basedir' => sys_get_temp_dir() . '/phantom-uploads',
            'baseurl' => 'http://example.com/wp-content/uploads',
            'error'   => false,
        ];
    }
}



if ( ! function_exists( 'wp_count_comments' ) ) {
    function wp_count_comments( $post_id = 0 ) { return (object) [ 'approved' => 0, 'moderated' => 0, 'spam' => 0, 'trash' => 0, 'total_comments' => 0 ]; }
}
if ( ! class_exists( 'WP_Comment_Query' ) ) {
    class WP_Comment_Query {
        public $comments = [];
        public function __construct( $args = [] ) {}
        public function have_comments() { return false; }
    }
}

// Load settings registry (the core data class) + modular loader
require_once PHANTOM_CORE_PATH . 'includes/settings/class-settings-loader.php';
require_once PHANTOM_CORE_PATH . 'includes/class-settings-registry.php';

// Load font classes
require_once PHANTOM_CORE_PATH . 'includes/class-phantom-font-families.php';
require_once PHANTOM_CORE_PATH . 'includes/class-fonts.php';

// Load global palette
require_once PHANTOM_CORE_PATH . 'includes/class-phantom-global-palette.php';

// Load Engine classes
require_once PHANTOM_CORE_PATH . 'includes/Engine/PhpEventStore.php';
require_once PHANTOM_CORE_PATH . 'includes/Engine/EventDispatcher.php';
require_once PHANTOM_CORE_PATH . 'includes/Engine/Cache.php';

require_once PHANTOM_CORE_PATH . 'includes/Engine/Template_Loader.php';
require_once PHANTOM_CORE_PATH . 'includes/Engine/SEO_Engine.php';
require_once PHANTOM_CORE_PATH . 'includes/Engine/Security_Headers.php';
require_once PHANTOM_CORE_PATH . 'includes/Engine/Data_Engine.php';
require_once PHANTOM_CORE_PATH . 'includes/Engine/View_Engine.php';
require_once PHANTOM_CORE_PATH . 'includes/Engine/Asset_Engine.php';

require_once PHANTOM_CORE_PATH . 'includes/Engine/RequestRouter.php';
require_once PHANTOM_CORE_PATH . 'includes/Engine/ResponseBuilder.php';
require_once PHANTOM_CORE_PATH . 'includes/Engine/Render_Engine.php';
require_once PHANTOM_CORE_PATH . 'includes/Engine/Placeholder_Replacer.php';
require_once PHANTOM_CORE_PATH . 'includes/Engine/Container_Config.php';

// Load Engine Container
require_once PHANTOM_CORE_PATH . 'includes/Engine/Container.php';

// Load Demo classes
require_once PHANTOM_CORE_PATH . 'includes/Demo/class-demo-result.php';
require_once PHANTOM_CORE_PATH . 'includes/Demo/class-demo-contract.php';
require_once PHANTOM_CORE_PATH . 'includes/Demo/class-demo-registry.php';
require_once PHANTOM_CORE_PATH . 'includes/Demo/class-demo-loader.php';
require_once PHANTOM_CORE_PATH . 'includes/Demo/class-demo-switcher.php';
require_once PHANTOM_CORE_PATH . 'includes/Demo/class-demo-installer.php';
require_once PHANTOM_CORE_PATH . 'admin/class-demo-admin.php';

// Load Phase 4 Design System classes
require_once PHANTOM_CORE_PATH . 'includes/Design/data/token-definitions.php';
require_once PHANTOM_CORE_PATH . 'includes/Design/class-token-registry.php';
require_once PHANTOM_CORE_PATH . 'includes/Design/class-token-resolver.php';
require_once PHANTOM_CORE_PATH . 'includes/Design/class-token-validator.php';
require_once PHANTOM_CORE_PATH . 'includes/Design/class-compiled-token-set.php';
require_once PHANTOM_CORE_PATH . 'includes/Design/class-token-compiler.php';
require_once PHANTOM_CORE_PATH . 'includes/Design/class-css-variable-generator.php';
require_once PHANTOM_CORE_PATH . 'includes/Design/class-preset.php';
require_once PHANTOM_CORE_PATH . 'includes/Design/class-preset-registry.php';
require_once PHANTOM_CORE_PATH . 'includes/Design/class-preset-manager.php';
require_once PHANTOM_CORE_PATH . 'includes/Design/class-theme-dna-engine.php';
require_once PHANTOM_CORE_PATH . 'includes/Design/class-design-system-manager.php';
require_once PHANTOM_CORE_PATH . 'includes/Design/Providers/class-preset-provider-interface.php';
require_once PHANTOM_CORE_PATH . 'includes/Design/Providers/class-core-provider.php';
require_once PHANTOM_CORE_PATH . 'includes/Design/Providers/class-demo-provider.php';
require_once PHANTOM_CORE_PATH . 'includes/Design/Providers/class-user-provider.php';

// Load Phase 4C Admin page classes
require_once PHANTOM_CORE_PATH . 'admin/class-phantom-admin.php';
require_once PHANTOM_CORE_PATH . 'admin/class-dashboard-page.php';
require_once PHANTOM_CORE_PATH . 'admin/class-design-studio-page.php';
require_once PHANTOM_CORE_PATH . 'admin/class-component-library-page.php';
require_once PHANTOM_CORE_PATH . 'admin/class-template-manager-page.php';
require_once PHANTOM_CORE_PATH . 'admin/class-animation-studio-page.php';
require_once PHANTOM_CORE_PATH . 'admin/class-asset-manager-page.php';
require_once PHANTOM_CORE_PATH . 'admin/class-performance-page.php';
require_once PHANTOM_CORE_PATH . 'admin/class-seo-page.php';
require_once PHANTOM_CORE_PATH . 'admin/class-import-export-page.php';
require_once PHANTOM_CORE_PATH . 'admin/class-backup-restore-page.php';
require_once PHANTOM_CORE_PATH . 'admin/class-developer-page.php';
require_once PHANTOM_CORE_PATH . 'admin/class-system-page.php';
require_once PHANTOM_CORE_PATH . 'admin/class-customizer-design-panel.php';

// Load Phase 4D Design infrastructure
require_once PHANTOM_CORE_PATH . 'includes/Design/class-design-exporter.php';
require_once PHANTOM_CORE_PATH . 'includes/Design/class-design-importer.php';
require_once PHANTOM_CORE_PATH . 'includes/Design/Providers/class-import-provider.php';

// Load Phase 5 Feature Flag classes
require_once PHANTOM_CORE_PATH . 'includes/Feature/class-feature.php';
require_once PHANTOM_CORE_PATH . 'includes/Feature/class-feature-registry.php';
require_once PHANTOM_CORE_PATH . 'includes/Feature/class-feature-manager.php';
require_once PHANTOM_CORE_PATH . 'includes/Feature/data/features.php';

// Load Phase 5A Animation classes
require_once PHANTOM_CORE_PATH . 'includes/Animation/class-animation.php';
require_once PHANTOM_CORE_PATH . 'includes/Animation/class-animation-registry.php';
require_once PHANTOM_CORE_PATH . 'includes/Animation/class-gsap-bridge.php';
require_once PHANTOM_CORE_PATH . 'includes/Animation/class-scroll-reveal.php';
require_once PHANTOM_CORE_PATH . 'includes/Animation/class-parallax.php';

// Load Phase 5D Component + Template Registry classes
require_once PHANTOM_CORE_PATH . 'includes/Components/class-component.php';
require_once PHANTOM_CORE_PATH . 'includes/Components/class-component-registry.php';
require_once PHANTOM_CORE_PATH . 'includes/Components/class-component-manager.php';
require_once PHANTOM_CORE_PATH . 'includes/Registry/class-template.php';
require_once PHANTOM_CORE_PATH . 'includes/Registry/class-template-registry.php';

// Load Phase 5E Upgrade Manager
require_once PHANTOM_CORE_PATH . 'includes/Upgrade/class-upgrade-manager.php';

// Load Customizer (needed by Rest_Controller)
require_once PHANTOM_CORE_PATH . 'includes/class-customizer.php';
require_once PHANTOM_CORE_PATH . 'includes/class-preset-compatibility-bridge.php';
require_once PHANTOM_CORE_PATH . 'includes/class-custom-css.php';

// Load REST Controller
require_once PHANTOM_CORE_PATH . 'includes/class-rest-controller.php';

// Load Settings Page + Font Download Page
require_once PHANTOM_CORE_PATH . 'admin/class-settings-page.php';
require_once PHANTOM_CORE_PATH . 'admin/class-font-download-page.php';

/**
 * Ensure fashion demo fixture exists on disk.
 * Created via PHP so it's visible within the test process.
 */
function phantom_ensure_fashion_fixture(): void {
    $dir = PHANTOM_CORE_PATH . 'frontend/templates/fashion/';
    if ( is_dir( $dir . 'html' ) && file_exists( $dir . 'demo.json' ) ) {
        return;
    }
    @mkdir( $dir . 'html', 0777, true );
    @mkdir( $dir . 'css', 0777, true );
    @mkdir( $dir . 'js', 0777, true );
    file_put_contents(
        $dir . 'demo.json',
        '{"name":"Fashion Store","slug":"fashion","version":"1.0.0","description":"A modern fashion e-commerce demo","author":"Phantom Core","requires":{"php":"7.4","wordpress":"6.4","woocommerce":"9.0","phantom_core":"1.5.0"},"templates":["index","shop","product-detail","blog","contact"],"tags":["fashion","ecommerce","modern"]}'
    );
    file_put_contents( $dir . 'html/index.html', '<div>index</div>' );
    file_put_contents( $dir . 'html/shop.html', '<div>shop</div>' );
    file_put_contents( $dir . 'html/product-detail.html', '<div>product-detail</div>' );
    file_put_contents( $dir . 'html/blog.html', '<div>blog</div>' );
    file_put_contents( $dir . 'html/contact.html', '<div>contact</div>' );
    file_put_contents( $dir . 'css/demo.css', '/* Fashion demo */' );
    file_put_contents( $dir . 'js/demo.js', '// Fashion demo' );
    clearstatcache();
}

// Load Template Packs (PhantomCore\Packs) classes
require_once PHANTOM_CORE_PATH . 'includes/Packs/class-frontend-pack.php';

require_once PHANTOM_CORE_PATH . 'includes/Packs/class-frontend-pack-registry.php';

require_once PHANTOM_CORE_PATH . 'includes/Packs/class-pack-rest.php';

