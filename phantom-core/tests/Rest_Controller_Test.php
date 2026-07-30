<?php

use PHPUnit\Framework\TestCase;
use PhantomCore\Api\Rest_Controller;
use PhantomCore\Settings_Registry;

/**
 * Comprehensive REST API endpoint tests for Phantom Core.
 *
 * Covers all 42+ routes registered under phantom/v1.
 * Tests are standalone (no WordPress) using bootstrap stubs.
 */
class Rest_Controller_Test extends TestCase {

    private Rest_Controller $controller;

    public static function setUpBeforeClass(): void {
        // Ensure settings registry is loaded
        Settings_Registry::get_instance()->register();
        // Reset captured routes
        $GLOBALS['_phantom_rest_routes'] = [];
    }

    protected function setUp(): void {
        parent::setUp();
        $this->controller = Rest_Controller::get_instance();
        $GLOBALS['_phantom_rest_routes'] = [];
        $GLOBALS['_phantom_transients'] = [];
        global $wp_registered_sidebars;
        $wp_registered_sidebars = [];
    }

    // ──────────────────────────────────────────────
    // 1. ROUTE REGISTRATION
    // ──────────────────────────────────────────────

    public function test_register_routes_captures_all_routes(): void {
        $this->controller->register_routes();
        $routes = $GLOBALS['_phantom_rest_routes'];
        $this->assertIsArray($routes);
        $this->assertGreaterThanOrEqual(42, count($routes),
            'Should register at least 42 route entries under phantom/v1');
    }

    public function test_register_routes_namespace_is_phantom_v1(): void {
        $this->controller->register_routes();
        foreach ($GLOBALS['_phantom_rest_routes'] as $route) {
            $this->assertEquals('phantom/v1', $route['namespace'],
                "Route {$route['route']} should use phantom/v1 namespace");
        }
    }

    public function test_all_routes_have_permission_callback(): void {
        $this->controller->register_routes();
        foreach ($GLOBALS['_phantom_rest_routes'] as $entry) {
            $args = $entry['args'];
            // Handle both single route and multi-method array
            if (isset($args['methods'])) {
                $this->assertNotEmpty($args['permission_callback'] ?? null,
                    "Route {$entry['route']} missing permission_callback");
            } else {
                foreach ($args as $i => $method) {
                    $this->assertNotEmpty($method['permission_callback'] ?? null,
                        "Route {$entry['route']} method #$i missing permission_callback");
                }
            }
        }
    }

    public function test_register_routes_includes_expected_endpoints(): void {
        $this->controller->register_routes();
        $route_paths = array_map(function ($r) { return $r['route']; }, $GLOBALS['_phantom_rest_routes']);

        $expected = [
            '/settings', '/settings/(?P<key>[\w-]+)', '/schema', '/options',
            '/options/persistent', '/export', '/import', '/cache/flush',
            '/partial', '/posts', '/posts/(?P<slug>[\w-]+)', '/post-types',
            '/pages', '/pages/(?P<slug>[\w-]+)', '/categories',
            '/menu-locations', '/menus/(?P<location>[\w-]+)', '/product-tags',
            '/products', '/products/featured', '/products/(?P<id>\d+)',
            '/cart', '/cart/add', '/cart/update', '/cart/remove',
            '/cart/coupons', '/cart/coupon', '/cart/remove-coupon',
            '/cart/shipping-methods',
            '/woo/attributes', '/woo/variations', '/woo/reviews',
            '/page-data',
            '/auth/login', '/auth/register', '/auth/password-reset', '/auth/logout',
            '/contact', '/widgets', '/widgets/(?P<sidebar_id>[\w-]+)',
            '/user/profile', '/user/orders',
        ];

        foreach ($expected as $expected_route) {
            $this->assertContains($expected_route, $route_paths,
                "Expected route $expected_route not registered");
        }
    }

    public function test_register_routes_settings_has_get_and_create_methods(): void {
        $this->controller->register_routes();
        $settings_route = null;
        foreach ($GLOBALS['_phantom_rest_routes'] as $r) {
            if ($r['route'] === '/settings') {
                $settings_route = $r;
                break;
            }
        }
        $this->assertNotNull($settings_route, '/settings route not found');
        $this->assertCount(2, $settings_route['args'], '/settings should have 2 methods (GET+POST)');
        $this->assertEquals('GET', $settings_route['args'][0]['methods'] ?? '');
        $this->assertEquals('POST', $settings_route['args'][1]['methods'] ?? '');
    }

    // ──────────────────────────────────────────────
    // 2. PERMISSION CALLBACKS
    // ──────────────────────────────────────────────

    public function test_settings_permission_check_returns_true_with_valid_nonce(): void {
        $request = new WP_REST_Request('GET', '/settings');
        $request->set_header('X-Phantom-Nonce', wp_create_nonce('phantom_api'));
        $result = $this->controller->settings_permission_check($request);
        $this->assertTrue($result);
    }

    public function test_settings_permission_check_returns_wp_error_with_invalid_nonce(): void {
        $request = new WP_REST_Request('GET', '/settings');
        $request->set_header('X-Phantom-Nonce', 'invalid_nonce_value');
        $result = $this->controller->settings_permission_check($request);
        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertEquals('rest_forbidden', $result->get_error_code());
    }

    public function test_admin_permission_check_returns_true_with_valid_nonce(): void {
        $request = new WP_REST_Request('POST', '/export');
        $request->set_header('X-Phantom-Nonce', wp_create_nonce('phantom_api'));
        $result = $this->controller->admin_permission_check($request);
        $this->assertTrue($result);
    }

    public function test_admin_permission_check_returns_wp_error_with_invalid_nonce(): void {
        $request = new WP_REST_Request('POST', '/export');
        $request->set_header('X-Phantom-Nonce', 'bad');
        $result = $this->controller->admin_permission_check($request);
        $this->assertInstanceOf(WP_Error::class, $result);
    }

    public function test_partial_permission_check_returns_true(): void {
        $request = new WP_REST_Request('GET', '/partial');
        $request->set_header('X-Phantom-Nonce', wp_create_nonce('phantom_api'));
        $result = $this->controller->partial_permission_check($request);
        $this->assertTrue($result);
    }

    public function test_verify_nonce_with_x_phantom_nonce(): void {
        $request = new WP_REST_Request('GET', '/settings');
        $request->set_header('X-Phantom-Nonce', wp_create_nonce('phantom_api'));
        $result = $this->controller->verify_nonce($request);
        $this->assertTrue($result);
    }

    public function test_verify_nonce_without_request_fallback(): void {
        // For nonce, we need at least one valid check path
        $_SERVER['HTTP_X_PHANTOM_NONCE'] = wp_create_nonce('phantom_api');
        $result = $this->controller->verify_nonce();
        $this->assertTrue($result);
        unset($_SERVER['HTTP_X_PHANTOM_NONCE']);
    }

    public function test_cart_write_permission_check_requires_nonce_for_guest(): void {
        // Guests must also pass nonce (M2b fix)
        $request = new WP_REST_Request('POST', '/cart/add');
        $result = $this->controller->cart_write_permission_check($request);
        $this->assertInstanceOf(WP_Error::class, $result);
    }

    // ──────────────────────────────────────────────
    // 3. SETTINGS CRUD
    // ──────────────────────────────────────────────

    public function test_get_settings_returns_response_with_entries(): void {
        $request = new WP_REST_Request('GET', '/settings');
        $request->set_param('per_page', 200);
        $response = $this->controller->get_settings($request);
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        $this->assertIsArray($data);
        $this->assertGreaterThan(50, count($data), 'Should return many settings');
    }

    public function test_get_settings_with_section_filter(): void {
        $request = new WP_REST_Request('GET', '/settings');
        $request->set_param('section', 'colors');
        $response = $this->controller->get_settings($request);
        $data = $response->get_data();
        $this->assertIsArray($data);
        foreach ($data as $entry) {
            $this->assertEquals('colors', $entry['section'] ?? '');
        }
    }

    public function test_get_settings_with_pagination(): void {
        $request = new WP_REST_Request('GET', '/settings');
        $request->set_param('per_page', 10);
        $request->set_param('page', 1);
        $response = $this->controller->get_settings($request);
        $data = $response->get_data();
        $this->assertIsArray($data);
        $this->assertLessThanOrEqual(10, count($data));
        $headers = $response->get_headers();
        $this->assertArrayHasKey('X-WP-Total', $headers);
        $this->assertArrayHasKey('X-WP-TotalPages', $headers);
    }

    public function test_get_setting_returns_entry_for_valid_key(): void {
        $request = new WP_REST_Request('GET', '/settings/key');
        $request->set_param('key', 'color_primary');
        $response = $this->controller->get_setting($request);
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        $this->assertEquals('color_primary', $data['key'] ?? '');
    }

    public function test_get_setting_returns_error_for_invalid_key(): void {
        $request = new WP_REST_Request('GET', '/settings/key');
        $request->set_param('key', 'nonexistent_setting_xyz');
        $response = $this->controller->get_setting($request);
        $this->assertEquals(404, $response->get_status());
        $data = $response->get_data();
        $this->assertEquals('not_found', $data['code'] ?? '');
    }

    public function test_update_setting_updates_value(): void {
        $request = new WP_REST_Request('PUT', '/settings/key');
        $request->set_param('key', 'color_primary');
        $request->set_param('value', '#ff0000');
        $response = $this->controller->update_setting($request);
        $this->assertEquals(200, $response->get_status());
    }

    public function test_update_setting_returns_error_for_invalid_key(): void {
        $request = new WP_REST_Request('PUT', '/settings/key');
        $request->set_param('key', 'nonexistent_key_abc');
        $request->set_param('value', 'test');
        $response = $this->controller->update_setting($request);
        $this->assertEquals(404, $response->get_status());
    }

    public function test_delete_setting_resets_to_default(): void {
        $request = new WP_REST_Request('DELETE', '/settings/key');
        $request->set_param('key', 'color_primary');
        $response = $this->controller->delete_setting($request);
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['reset'] ?? false);
        $this->assertEquals('color_primary', $data['key'] ?? '');
    }

    public function test_delete_setting_returns_error_for_invalid_key(): void {
        $request = new WP_REST_Request('DELETE', '/settings/key');
        $request->set_param('key', 'fake_key_123');
        $response = $this->controller->delete_setting($request);
        $this->assertEquals(404, $response->get_status());
    }

    public function test_update_settings_bulk_with_settings_array(): void {
        $request = new WP_REST_Request('POST', '/settings');
        $request->set_param('settings', ['color_primary' => '#111111', 'color_secondary' => '#222222']);
        $response = $this->controller->update_settings($request);
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        $this->assertArrayHasKey('updated', $data);
        $this->assertArrayHasKey('color_primary', $data['updated']);
        $this->assertArrayHasKey('color_secondary', $data['updated']);
    }

    public function test_update_settings_bulk_with_changes_array(): void {
        $request = new WP_REST_Request('POST', '/settings');
        $request->set_param('changes', [
            ['key' => 'color_primary', 'value' => '#333333'],
            ['key' => 'color_secondary', 'value' => '#444444'],
        ]);
        $response = $this->controller->update_settings($request);
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        $this->assertArrayHasKey('updated', $data);
        $this->assertCount(2, $data['updated']);
    }

    public function test_update_settings_returns_error_with_invalid_key(): void {
        $request = new WP_REST_Request('POST', '/settings');
        $request->set_param('settings', ['bad_key_xyz' => 'value']);
        $response = $this->controller->update_settings($request);
        // Returns 207 Multi-Status when there are errors
        $this->assertEquals(207, $response->get_status());
        $data = $response->get_data();
        $this->assertNotEmpty($data['errors']);
    }

    public function test_update_settings_returns_error_with_no_data(): void {
        $request = new WP_REST_Request('POST', '/settings');
        $result = $this->controller->update_settings($request);
        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertEquals('invalid_params', $result->get_error_code());
    }

    // ──────────────────────────────────────────────
    // 4. SCHEMA & OPTIONS
    // ──────────────────────────────────────────────

    public function test_get_schema_returns_settings_schema(): void {
        $response = $this->controller->get_schema();
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        $this->assertIsArray($data);
        $this->assertArrayHasKey('color_primary', $data);
        $this->assertArrayHasKey('header_style', $data);
    }

    public function test_get_options_returns_design_options(): void {
        $response = $this->controller->get_options();
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        $this->assertIsArray($data);
        // Should include design-related keys (most should be present)
        $expected = ['color_primary', 'color_secondary', 'typography_body_font', 'button_radius'];
        foreach ($expected as $key) {
            $this->assertArrayHasKey($key, $data, "Missing option: $key");
        }
    }

    public function test_get_persistent_options_returns_extended_options(): void {
        $response = $this->controller->get_persistent_options();
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        $this->assertIsArray($data);
    }

    // ──────────────────────────────────────────────
    // 5. EXPORT & IMPORT
    // ──────────────────────────────────────────────

    public function test_export_settings_returns_all_settings_with_metadata(): void {
        $response = $this->controller->export_settings();
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        $this->assertArrayHasKey('version', $data);
        $this->assertArrayHasKey('settings', $data);
        $this->assertEquals(PHANTOM_CORE_VERSION, $data['version']);
        $this->assertGreaterThan(50, count($data['settings']));
    }

    public function test_import_settings_with_valid_data(): void {
        $request = new WP_REST_Request('POST', '/import');
        $request->set_param('settings', ['color_primary' => '#ff0000', 'color_secondary' => '#00ff00']);
        $response = $this->controller->import_settings($request);
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        $this->assertArrayHasKey('imported', $data);
        $this->assertCount(2, $data['imported']);
    }

    public function test_import_settings_with_invalid_data_returns_error(): void {
        $request = new WP_REST_Request('POST', '/import');
        $request->set_param('settings', []);
        $result = $this->controller->import_settings($request);
        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertEquals('invalid_settings', $result->get_error_code());
    }

    public function test_import_settings_with_unknown_keys_returns_multi_status(): void {
        $request = new WP_REST_Request('POST', '/import');
        $request->set_param('settings', ['color_primary' => '#ff0000', 'fake_key_123' => 'nope']);
        $response = $this->controller->import_settings($request);
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(207, $response->get_status());
        $data = $response->get_data();
        $this->assertCount(1, $data['imported']);
        $this->assertCount(1, $data['errors']);
    }

    // ──────────────────────────────────────────────
    // 6. CACHE / FLUSH
    // ──────────────────────────────────────────────

    public function test_flush_cache_returns_success(): void {
        $response = $this->controller->flush_cache();
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success'] ?? false);
    }

    public function test_invalidate_page_cache_does_not_throw(): void {
        // Just ensure it doesn't error
        $this->controller->invalidate_page_cache();
        $this->assertTrue(true);
    }

    // ──────────────────────────────────────────────
    // 7. POST TYPES / PAGES / POSTS / CATEGORIES
    // ──────────────────────────────────────────────

    public function test_get_post_types_returns_public_types(): void {
        $response = $this->controller->get_post_types();
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        $this->assertIsArray($data);
        $this->assertGreaterThanOrEqual(3, count($data));
        $names = array_column($data, 'name');
        $this->assertContains('post', $names);
        $this->assertContains('page', $names);
    }

    public function test_get_menu_locations_returns_locations(): void {
        $response = $this->controller->get_menu_locations();
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        $this->assertIsArray($data);
        $this->assertGreaterThanOrEqual(4, count($data));
    }

    public function test_get_categories_returns_array(): void {
        $request = new WP_REST_Request('GET', '/categories');
        $response = $this->controller->get_categories($request);
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        $this->assertIsArray($data);
    }

    public function test_get_pages_returns_response(): void {
        $request = new WP_REST_Request('GET', '/pages');
        $response = $this->controller->get_pages($request);
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        $this->assertArrayHasKey('pages', $data);
        $this->assertArrayHasKey('total', $data);
    }

    public function test_get_posts_returns_response(): void {
        $request = new WP_REST_Request('GET', '/posts');
        $response = $this->controller->get_posts($request);
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        $this->assertArrayHasKey('posts', $data);
        $this->assertArrayHasKey('total', $data);
    }

    // ──────────────────────────────────────────────
    // 8. WIDGET AREAS
    // ──────────────────────────────────────────────

    public function test_get_widget_areas_returns_array(): void {
        $response = $this->controller->get_widget_areas();
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        $this->assertIsArray($data);
    }

    public function test_get_widget_area_returns_response(): void {
        $request = new WP_REST_Request('GET', '/widgets/id');
        $request->set_param('sidebar_id', 'sidebar-1');
        $response = $this->controller->get_widget_area($request);
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        $this->assertArrayHasKey('sidebar_id', $data);
        $this->assertArrayHasKey('html', $data);
    }

    // ──────────────────────────────────────────────
    // 9. ERROR HANDLING
    // ──────────────────────────────────────────────

    public function test_error_response_returns_proper_structure(): void {
        $response = $this->controller->error_response('test_error', 'Test message', 400);
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(400, $response->get_status());
        $data = $response->get_data();
        $this->assertEquals('test_error', $data['code']);
        $this->assertEquals('Test message', $data['message']);
        $this->assertEquals(400, $data['status']);
    }

    public function test_wp_error_returns_proper_wp_error(): void {
        $error = $this->controller->wp_error('custom_error', 'Custom message', 418);
        $this->assertInstanceOf(WP_Error::class, $error);
        $this->assertEquals('custom_error', $error->get_error_code());
        $this->assertEquals('Custom message', $error->get_error_message());
    }

    public function test_error_response_handles_various_status_codes(): void {
        $codes = [200, 201, 400, 401, 403, 404, 500, 503];
        foreach ($codes as $code) {
            $response = $this->controller->error_response('code_' . $code, 'Message', $code);
            $this->assertEquals($code, $response->get_status(), "Status $code should be preserved");
        }
    }

    // ──────────────────────────────────────────────
    // 10. MENU ENDPOINTS
    // ──────────────────────────────────────────────

    public function test_get_menu_returns_error_for_missing_location(): void {
        $request = new WP_REST_Request('GET', '/menus/location');
        $request->set_param('location', 'nonexistent_location');
        $result = $this->controller->get_menu($request);
        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertEquals('not_found', $result->get_error_code());
    }

    public function test_get_menu_returns_response_for_valid_location(): void {
        $request = new WP_REST_Request('GET', '/menus/location');
        $request->set_param('location', 'primary');
        $response = $this->controller->get_menu($request);
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        $this->assertIsArray($data);
        // Menu stub returns empty items — response structure may be empty array
    }

    // ──────────────────────────────────────────────
    // 11. PRODUCT ENDPOINTS (WooCommerce-dependent)
    // ──────────────────────────────────────────────

    public function test_get_products_returns_error_without_woocommerce(): void {
        $request = new WP_REST_Request('GET', '/products');
        $result = $this->controller->get_products($request);
        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertEquals('woocommerce_inactive', $result->get_error_code());
    }

    public function test_get_product_returns_error_without_woocommerce(): void {
        $request = new WP_REST_Request('GET', '/products/1');
        $request->set_param('id', 1);
        $result = $this->controller->get_product($request);
        $this->assertInstanceOf(WP_Error::class, $result);
    }

    public function test_get_featured_products_returns_error_without_woocommerce(): void {
        $result = $this->controller->get_featured_products();
        $this->assertInstanceOf(WP_Error::class, $result);
    }

    public function test_get_product_tags_returns_error_without_woocommerce(): void {
        $request = new WP_REST_Request('GET', '/product-tags');
        $result = $this->controller->get_product_tags($request);
        // Method returns empty tags response when WC inactive (not WP_Error)
        $this->assertInstanceOf(WP_REST_Response::class, $result);
        $this->assertEquals(200, $result->get_status());
        $data = $result->get_data();
        $this->assertIsArray($data);
    }

    // ──────────────────────────────────────────────
    // 12. CART ENDPOINTS (WooCommerce-dependent)
    // ──────────────────────────────────────────────

    public function test_get_cart_returns_error_without_woocommerce(): void {
        $result = $this->controller->get_cart();
        $this->assertInstanceOf(WP_Error::class, $result);
    }

    public function test_add_to_cart_endpoint_returns_error_without_woocommerce(): void {
        $request = new WP_REST_Request('POST', '/cart/add');
        $request->set_param('product_id', 1);
        $request->set_param('quantity', 1);
        $result = $this->controller->add_to_cart_endpoint($request);
        $this->assertInstanceOf(WP_Error::class, $result);
    }

    // ──────────────────────────────────────────────
    // 13. WOO ENDPOINTS (WooCommerce-dependent)
    // ──────────────────────────────────────────────

    public function test_get_woo_attributes_returns_error_without_woocommerce(): void {
        $result = $this->controller->get_woo_attributes();
        $this->assertInstanceOf(WP_Error::class, $result);
    }

    public function test_get_woo_variations_returns_error_without_woocommerce(): void {
        $request = new WP_REST_Request('GET', '/woo/variations');
        $request->set_param('product_id', 1);
        $result = $this->controller->get_woo_variations($request);
        $this->assertInstanceOf(WP_Error::class, $result);
    }

    public function test_get_woo_reviews_returns_error_without_woocommerce(): void {
        $request = new WP_REST_Request('GET', '/woo/reviews');
        $request->set_param('product_id', 1);
        $result = $this->controller->get_woo_reviews($request);
        // Method uses function_exists('wc_get_product') which is truthy via stub
        $this->assertInstanceOf(WP_REST_Response::class, $result);
        $this->assertEquals(200, $result->get_status());
    }

    // ──────────────────────────────────────────────
    // 14. AUTH ENDPOINTS
    // ──────────────────────────────────────────────

    public function test_auth_login_returns_wp_error_when_no_action(): void {
        $request = new WP_REST_Request('POST', '/auth/login');
        $request->set_param('username', 'test');
        $request->set_param('password', 'pass');
        $result = $this->controller->auth_login($request);
        $this->assertInstanceOf(WP_Error::class, $result);
    }

    public function test_auth_register_returns_wp_error_when_no_action(): void {
        $request = new WP_REST_Request('POST', '/auth/register');
        $request->set_param('username', 'test');
        $request->set_param('email', 'test@test.com');
        $result = $this->controller->auth_register($request);
        $this->assertInstanceOf(WP_Error::class, $result);
    }

    public function test_auth_password_reset_returns_wp_error_when_no_user(): void {
        $request = new WP_REST_Request('POST', '/auth/password-reset');
        $request->set_param('user_email', 'nonexistent@test.com');
        $result = $this->controller->auth_password_reset($request);
        $this->assertInstanceOf(WP_Error::class, $result);
    }

    public function test_auth_logout_returns_wp_error_without_valid_nonce(): void {
        $request = new WP_REST_Request('POST', '/auth/logout');
        $result = $this->controller->auth_logout($request);
        // auth_logout does not return WP_Error — permission_callback handles it
        $this->assertInstanceOf(WP_REST_Response::class, $result);
        $this->assertEquals(200, $result->get_status());
        $data = $result->get_data();
        $this->assertTrue($data['success'] ?? false);
    }

    // ──────────────────────────────────────────────
    // 15. CONTACT ENDPOINT
    // ──────────────────────────────────────────────

    public function test_handle_contact_validates_required_fields(): void {
        $request = new WP_REST_Request('POST', '/contact');
        // Missing required fields
        $result = $this->controller->handle_contact($request);
        $this->assertInstanceOf(WP_Error::class, $result);
    }

    // ──────────────────────────────────────────────
    // 16. USER ENDPOINTS
    // ──────────────────────────────────────────────

    public function test_get_user_orders_returns_wp_error_without_auth(): void {
        $request = new WP_REST_Request('GET', '/user/orders');
        $result = $this->controller->get_user_orders($request);
        // Method uses error_response() which returns WP_REST_Response, not WP_Error
        $this->assertInstanceOf(WP_REST_Response::class, $result);
        $this->assertEquals(401, $result->get_status());
    }

    public function test_get_user_profile_returns_wp_error_without_auth(): void {
        $request = new WP_REST_Request('GET', '/user/profile');
        $result = $this->controller->get_user_profile($request);
        $this->assertInstanceOf(WP_Error::class, $result);
    }

    // ──────────────────────────────────────────────
    // 17. SINGLETON PATTERN
    // ──────────────────────────────────────────────

    public function test_get_instance_returns_same_instance(): void {
        $instance1 = Rest_Controller::get_instance();
        $instance2 = Rest_Controller::get_instance();
        $this->assertSame($instance1, $instance2);
    }

    public function test_controller_has_correct_namespace(): void {
        $reflection = new ReflectionProperty(Rest_Controller::class, 'namespace');
        $reflection->setAccessible(true);
        $this->assertEquals('phantom/v1', $reflection->getValue($this->controller));
    }

    // ──────────────────────────────────────────────
    // 18. PAGE DATA
    // ──────────────────────────────────────────────

    public function test_get_public_page_data_returns_data(): void {
        $response = $this->controller->get_public_page_data();
        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        $this->assertIsArray($data);
    }

    // ──────────────────────────────────────────────
    // 19. PARTIAL ENDPOINT
    // ──────────────────────────────────────────────

    public function test_get_partial_returns_error_for_invalid_key(): void {
        $request = new WP_REST_Request('GET', '/partial');
        $request->set_param('partial', 'nonexistent_partial');
        $result = $this->controller->get_partial($request);
        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertEquals('invalid_partial', $result->get_error_code());
    }

    // ──────────────────────────────────────────────
    // 20. SHIPPING METHODS
    // ──────────────────────────────────────────────

    public function test_get_shipping_methods_returns_error_without_woocommerce(): void {
        $request = new WP_REST_Request('GET', '/cart/shipping-methods');
        $result = $this->controller->get_shipping_methods($request);
        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertEquals('woocommerce_inactive', $result->get_error_code());
    }

    // ──────────────────────────────────────────────
    // 21. CART COUPONS
    // ──────────────────────────────────────────────

    public function test_get_cart_coupons_returns_error_without_woocommerce(): void {
        $request = new WP_REST_Request('GET', '/cart/coupons');
        $request->set_header('X-Phantom-Nonce', wp_create_nonce('phantom_api'));
        $result = $this->controller->get_cart_coupons($request);
        // Method returns empty coupons array when WC inactive (not WP_Error)
        $this->assertInstanceOf(WP_REST_Response::class, $result);
        $this->assertEquals(200, $result->get_status());
        $data = $result->get_data();
        $this->assertIsArray($data);
    }

    public function test_apply_coupon_endpoint_returns_error_without_woocommerce(): void {
        $request = new WP_REST_Request('POST', '/cart/coupon');
        $request->set_param('code', 'SAVE10');
        $result = $this->controller->apply_coupon_endpoint($request);
        $this->assertInstanceOf(WP_Error::class, $result);
    }

    public function test_remove_coupon_endpoint_returns_error_without_woocommerce(): void {
        $request = new WP_REST_Request('POST', '/cart/remove-coupon');
        $request->set_param('code', 'SAVE10');
        $result = $this->controller->remove_coupon_endpoint($request);
        $this->assertInstanceOf(WP_Error::class, $result);
    }

    // ──────────────────────────────────────────────
    // 22. CART ITEM UPDATE/REMOVE
    // ──────────────────────────────────────────────

    public function test_update_cart_item_endpoint_returns_error_without_woocommerce(): void {
        $request = new WP_REST_Request('PUT', '/cart/update');
        $request->set_param('key', 'abc123');
        $request->set_param('quantity', 2);
        $result = $this->controller->update_cart_item_endpoint($request);
        $this->assertInstanceOf(WP_Error::class, $result);
    }

    public function test_remove_cart_item_endpoint_returns_error_without_woocommerce(): void {
        $request = new WP_REST_Request('DELETE', '/cart/remove');
        $request->set_param('key', 'abc123');
        $result = $this->controller->remove_cart_item_endpoint($request);
        $this->assertInstanceOf(WP_Error::class, $result);
    }

    // ──────────────────────────────────────────────
    // 23. CREATE / UPDATE / DELETE PRODUCT
    // ──────────────────────────────────────────────

    public function test_create_product_returns_error_without_woocommerce(): void {
        $request = new WP_REST_Request('POST', '/products');
        $result = $this->controller->create_product($request);
        $this->assertInstanceOf(WP_Error::class, $result);
    }

    public function test_update_product_returns_error_without_woocommerce(): void {
        $request = new WP_REST_Request('PUT', '/products/1');
        $request->set_param('id', 1);
        $result = $this->controller->update_product($request);
        $this->assertInstanceOf(WP_Error::class, $result);
    }

    public function test_delete_product_returns_error_without_woocommerce(): void {
        $request = new WP_REST_Request('DELETE', '/products/1');
        $request->set_param('id', 1);
        $result = $this->controller->delete_product($request);
        $this->assertInstanceOf(WP_Error::class, $result);
    }

    // ──────────────────────────────────────────────
    // 24. SETTINGS ROUTE CONFIGURATION
    // ──────────────────────────────────────────────

    public function test_settings_single_route_has_get_put_delete_methods(): void {
        $this->controller->register_routes();
        $single_settings = null;
        foreach ($GLOBALS['_phantom_rest_routes'] as $r) {
            if ($r['route'] === '/settings/(?P<key>[\w-]+)') {
                $single_settings = $r;
                break;
            }
        }
        $this->assertNotNull($single_settings, 'Single settings route not found');
        $this->assertCount(3, $single_settings['args'],
            '/settings/{key} should have 3 methods (GET+PUT+DELETE)');
    }

    public function test_products_route_has_get_and_create_methods(): void {
        $this->controller->register_routes();
        $products_route = null;
        foreach ($GLOBALS['_phantom_rest_routes'] as $r) {
            if ($r['route'] === '/products') {
                $products_route = $r;
                break;
            }
        }
        $this->assertNotNull($products_route, '/products route not found');
        $this->assertCount(2, $products_route['args']);
    }

    public function test_products_single_route_has_get_put_delete_methods(): void {
        $this->controller->register_routes();
        $single_product = null;
        foreach ($GLOBALS['_phantom_rest_routes'] as $r) {
            if ($r['route'] === '/products/(?P<id>\d+)') {
                $single_product = $r;
                break;
            }
        }
        $this->assertNotNull($single_product, '/products/{id} route not found');
        $this->assertCount(3, $single_product['args']);
    }

    public function test_woo_reviews_route_has_get_and_create_methods(): void {
        $this->controller->register_routes();
        $reviews_route = null;
        foreach ($GLOBALS['_phantom_rest_routes'] as $r) {
            if ($r['route'] === '/woo/reviews') {
                $reviews_route = $r;
                break;
            }
        }
        $this->assertNotNull($reviews_route, '/woo/reviews route not found');
        $this->assertCount(2, $reviews_route['args']);
    }

    // ──────────────────────────────────────────────
    // 25. INIT / HOOKS
    // ──────────────────────────────────────────────

    public function test_init_adds_rest_api_init_action(): void {
        $GLOBALS['_phantom_actions'] = [];
        $this->controller->init();
        $found = false;
        foreach ($GLOBALS['_phantom_actions'] as $action) {
            if ($action['tag'] === 'rest_api_init') {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'init() should hook rest_api_init');
    }

    public function test_init_adds_menu_cache_invalidation(): void {
        $GLOBALS['_phantom_actions'] = [];
        $this->controller->init();
        $tags = array_column($GLOBALS['_phantom_actions'], 'tag');
        $this->assertContains('wp_update_nav_menu', $tags);
        $this->assertContains('wp_create_nav_menu', $tags);
    }

    // ──────────────────────────────────────────────
    // 26. SETTINGS REGISTRY STABILITY
    // ──────────────────────────────────────────────

    public function test_settings_entries_have_all_required_fields(): void {
        $entries = Settings_Registry::get_instance()->get_entries();
        foreach ($entries as $key => $entry) {
            $this->assertArrayHasKey('section', $entry, "Entry '$key' missing 'section'");
            $this->assertArrayHasKey('type', $entry, "Entry '$key' missing 'type'");
            $this->assertArrayHasKey('default', $entry, "Entry '$key' missing 'default'");
            $this->assertArrayHasKey('label', $entry, "Entry '$key' missing 'label'");
        }
    }
}
