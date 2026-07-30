<?php
declare(strict_types=1);

namespace PhantomCore\Assets;

use PhantomCore\Registry\Asset_Registry;

defined('ABSPATH') || exit;

class Asset_REST {

    private string $namespace = 'phantom/v1';

    private Asset_Manager $manager;
    private Asset_Registry $registry;

    public function __construct() {
        $this->manager  = Asset_Manager::get_instance();
        $this->registry = Asset_Registry::get_instance();
    }

    public function register_routes(): void {
        // List all asset definitions
        register_rest_route($this->namespace, '/assets', array(
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => array($this, 'get_assets'),
            'permission_callback' => array($this, 'admin_permission_check'),
        ));

        // Get single asset by handle
        register_rest_route($this->namespace, '/assets/(?P<handle>[\w-]+)', array(
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => array($this, 'get_asset'),
            'permission_callback' => array($this, 'admin_permission_check'),
            'args'                => array(
                'handle' => array(
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));

        // Upload new media asset
        register_rest_route($this->namespace, '/assets/upload', array(
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => array($this, 'upload_asset'),
            'permission_callback' => array($this, 'admin_permission_check'),
        ));

        // Replace existing media asset
        register_rest_route($this->namespace, '/assets/replace', array(
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => array($this, 'replace_asset'),
            'permission_callback' => array($this, 'admin_permission_check'),
            'args'                => array(
                'key' => array(
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));

        // Reset asset to default
        register_rest_route($this->namespace, '/assets/reset', array(
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => array($this, 'reset_asset'),
            'permission_callback' => array($this, 'admin_permission_check'),
            'args'                => array(
                'key' => array(
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));

        // Remove uploaded media asset
        register_rest_route($this->namespace, '/assets/remove', array(
            'methods'             => \WP_REST_Server::DELETABLE,
            'callback'            => array($this, 'remove_asset'),
            'permission_callback' => array($this, 'admin_permission_check'),
            'args'                => array(
                'key' => array(
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));
    }

    public function admin_permission_check(): bool {
        return current_user_can('manage_options');
    }

    public function get_assets(): \WP_REST_Response {
        $this->manager->init();
        return new \WP_REST_Response(array(
            'success' => true,
            'assets'  => $this->manager->get_all_asset_info(),
        ), 200);
    }

    public function get_asset(\WP_REST_Request $request): \WP_REST_Response {
        $this->manager->init();
        $handle = $request->get_param('handle');
        $info   = $this->manager->get_asset_info($handle);

        if (null === $info) {
            return new \WP_REST_Response(array(
                'success' => false,
                'message' => sprintf('Unknown asset: %s', $handle),
            ), 404);
        }

        return new \WP_REST_Response(array(
            'success' => true,
            'asset'   => $info,
        ), 200);
    }

    public function upload_asset(\WP_REST_Request $request): \WP_REST_Response {
        if (!function_exists('wp_handle_upload') || !function_exists('wp_check_filetype')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
        }

        $files = $request->get_file_params();
        if (empty($files['file'])) {
            return new \WP_REST_Response(array(
                'success' => false,
                'message' => __('No file provided.', 'phantom-core'),
            ), 400);
        }

        $key  = $request->get_param('key') ? sanitize_text_field($request->get_param('key')) : '';
        $attach_id = media_handle_sideload($files['file'], 0);

        if (is_wp_error($attach_id)) {
            return new \WP_REST_Response(array(
                'success' => false,
                'message' => $attach_id->get_error_message(),
            ), 500);
        }

        $url = wp_get_attachment_url($attach_id);

        if ($key) {
            $this->save_asset_option($key, $attach_id);
        }

        return new \WP_REST_Response(array(
            'success'     => true,
            'attachment_id' => $attach_id,
            'url'          => $url,
            'message'      => __('Asset uploaded successfully.', 'phantom-core'),
        ), 200);
    }

    public function replace_asset(\WP_REST_Request $request): \WP_REST_Response {
        if (!function_exists('wp_handle_upload')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
        }

        $key = $request->get_param('key');
        if (!$key) {
            return new \WP_REST_Response(array(
                'success' => false,
                'message' => __('Asset key is required.', 'phantom-core'),
            ), 400);
        }

        $files = $request->get_file_params();
        if (empty($files['file'])) {
            return new \WP_REST_Response(array(
                'success' => false,
                'message' => __('No file provided.', 'phantom-core'),
            ), 400);
        }

        $existing = $this->get_asset_option($key);
        if ($existing) {
            wp_delete_attachment((int) $existing, true);
        }

        $attach_id = media_handle_sideload($files['file'], 0);

        if (is_wp_error($attach_id)) {
            return new \WP_REST_Response(array(
                'success' => false,
                'message' => $attach_id->get_error_message(),
            ), 500);
        }

        $url = wp_get_attachment_url($attach_id);
        $this->save_asset_option($key, $attach_id);

        return new \WP_REST_Response(array(
            'success'       => true,
            'attachment_id' => $attach_id,
            'url'           => $url,
            'message'       => __('Asset replaced successfully.', 'phantom-core'),
        ), 200);
    }

    public function reset_asset(\WP_REST_Request $request): \WP_REST_Response {
        $key = $request->get_param('key');
        if (!$key) {
            return new \WP_REST_Response(array(
                'success' => false,
                'message' => __('Asset key is required.', 'phantom-core'),
            ), 400);
        }

        $existing = $this->get_asset_option($key);
        if ($existing) {
            wp_delete_attachment((int) $existing, true);
        }

        $this->save_asset_option($key, 0);

        $default_url = $this->get_default_asset_url($key);

        return new \WP_REST_Response(array(
            'success' => true,
            'url'     => $default_url,
            'message' => __('Asset reset to default.', 'phantom-core'),
        ), 200);
    }

    public function remove_asset(\WP_REST_Request $request): \WP_REST_Response {
        $key = $request->get_param('key');
        if (!$key) {
            return new \WP_REST_Response(array(
                'success' => false,
                'message' => __('Asset key is required.', 'phantom-core'),
            ), 400);
        }

        $existing = $this->get_asset_option($key);
        if ($existing) {
            wp_delete_attachment((int) $existing, true);
        }

        $this->save_asset_option($key, 0);

        return new \WP_REST_Response(array(
            'success' => true,
            'message' => __('Asset removed.', 'phantom-core'),
        ), 200);
    }

    private function get_asset_option(string $key) {
        $assets = get_option('phantom_assets', array());
        return $assets[$key] ?? 0;
    }

    private function save_asset_option(string $key, $value): void {
        $assets       = get_option('phantom_assets', array());
        $assets[$key] = $value;
        update_option('phantom_assets', $assets);
    }

    private function get_default_asset_url(string $key): string {
        $defaults = array(
            'logo'             => PHANTOM_CORE_URL . 'frontend/assets/images/logo.svg',
            'favicon'          => PHANTOM_CORE_URL . 'frontend/assets/images/favicon.ico',
            'hero_desktop'     => PHANTOM_CORE_URL . 'frontend/assets/images/hero-default.jpg',
            'product_placeholder' => PHANTOM_CORE_URL . 'frontend/assets/images/placeholder.png',
        );
        return $defaults[$key] ?? '';
    }
}
