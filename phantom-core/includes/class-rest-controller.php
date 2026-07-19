<?php
declare(strict_types=1);

namespace PhantomCore\Api;

use PhantomCore\Settings_Registry;

defined( 'ABSPATH' ) || exit;

class Rest_Controller extends \WP_REST_Controller {

	private static ?Rest_Controller $instance = null;
	protected $namespace = 'phantom/v1';

	final public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function init(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/settings',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_settings' ),
					'permission_callback' => array( $this, 'settings_permission_check' ),
					'args'                => $this->get_settings_args(),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'update_settings' ),
					'permission_callback' => array( $this, 'settings_write_permission_check' ),
					'args'                => $this->get_bulk_update_args(),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/settings/(?P<key>[\w-]+)',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_setting' ),
					'permission_callback' => array( $this, 'settings_permission_check' ),
					'args'                => $this->get_single_args(),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_setting' ),
					'permission_callback' => array( $this, 'settings_write_permission_check' ),
					'args'                => $this->get_single_update_args(),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_setting' ),
					'permission_callback' => array( $this, 'settings_write_permission_check' ),
					'args'                => $this->get_single_args(),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/schema',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_schema' ),
					'permission_callback' => array( $this, 'settings_permission_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/options',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_options' ),
					'permission_callback' => array( $this, 'settings_permission_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/options/persistent',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_persistent_options' ),
					'permission_callback' => '__return_true',
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/export',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'export_settings' ),
					'permission_callback' => array( $this, 'admin_permission_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/import',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'import_settings' ),
					'permission_callback' => array( $this, 'admin_permission_check' ),
					'args'                => $this->get_import_args(),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/cache/flush',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'flush_cache' ),
					'permission_callback' => array( $this, 'admin_permission_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/partial',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_partial' ),
					'permission_callback' => array( $this, 'partial_permission_check' ),
					'args'                => array(
						'partial' => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/posts',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_posts' ),
					'permission_callback' => '__return_true',
					'args'                => $this->get_posts_args(),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/posts/(?P<slug>[\w-]+)',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_post_by_slug' ),
					'permission_callback' => '__return_true',
					'args'                => $this->get_single_slug_args(),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/pages/(?P<slug>[\w-]+)',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_page_by_slug' ),
					'permission_callback' => '__return_true',
					'args'                => $this->get_single_slug_args(),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/categories',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_categories' ),
					'permission_callback' => '__return_true',
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/menus/(?P<location>[\w-]+)',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_menu' ),
					'permission_callback' => '__return_true',
					'args'                => $this->get_menu_args(),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/products',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_products' ),
					'permission_callback' => '__return_true',
					'args'                => $this->get_products_args(),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_product' ),
					'permission_callback' => array( $this, 'admin_permission_check' ),
					'args'                => $this->get_create_product_args(),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/products/featured',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_featured_products' ),
					'permission_callback' => '__return_true',
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/products/(?P<id>\d+)',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_product' ),
					'permission_callback' => '__return_true',
					'args'                => $this->get_product_args(),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_product' ),
					'permission_callback' => array( $this, 'admin_permission_check' ),
					'args'                => $this->get_update_product_args(),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_product' ),
					'permission_callback' => array( $this, 'admin_permission_check' ),
					'args'                => $this->get_product_args(),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/cart',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_cart' ),
					'permission_callback' => '__return_true',
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/cart/add',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'add_to_cart_endpoint' ),
					'permission_callback' => array( $this, 'cart_write_permission_check' ),
					'args'                => array(
						'product_id'   => array(
							'required'          => true,
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
						),
						'quantity'     => array(
							'required'          => false,
							'type'              => 'integer',
							'default'           => 1,
							'sanitize_callback' => 'absint',
						),
						'variation_id' => array(
							'required'          => false,
							'type'              => 'integer',
							'default'           => 0,
							'sanitize_callback' => 'absint',
						),
						'variation'    => array(
							'required'          => false,
							'type'              => 'object',
							'default'           => array(),
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/cart/update',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'update_cart_item_endpoint' ),
					'permission_callback' => array( $this, 'cart_write_permission_check' ),
					'args'                => array(
						'key'      => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'quantity' => array(
							'required'          => true,
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/cart/remove',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'remove_cart_item_endpoint' ),
					'permission_callback' => array( $this, 'cart_write_permission_check' ),
					'args'                => array(
						'key' => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/cart/coupon',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'apply_coupon_endpoint' ),
					'permission_callback' => array( $this, 'cart_write_permission_check' ),
					'args'                => array(
						'code' => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/cart/remove-coupon',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'remove_coupon_endpoint' ),
					'permission_callback' => array( $this, 'cart_write_permission_check' ),
					'args'                => array(
						'code' => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/cart/shipping-methods',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'get_shipping_methods' ),
					'permission_callback' => array( $this, 'cart_write_permission_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/woo/attributes',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_woo_attributes' ),
					'permission_callback' => '__return_true',
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/woo/variations',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_woo_variations' ),
					'permission_callback' => '__return_true',
					'args'                => $this->get_woo_variations_args(),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/woo/reviews',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_woo_reviews' ),
					'permission_callback' => '__return_true',
					'args'                => $this->get_woo_reviews_args(),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/page-data',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_page_data' ),
					'permission_callback' => '__return_true',
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/auth/login',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'auth_login' ),
				'permission_callback' => '__return_true',
				'args'                => $this->get_auth_login_args(),
			)
		);

		register_rest_route(
			$this->namespace,
			'/auth/register',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'auth_register' ),
				'permission_callback' => '__return_true',
				'args'                => $this->get_auth_register_args(),
			)
		);

		register_rest_route(
			$this->namespace,
			'/auth/password-reset',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'auth_password_reset' ),
				'permission_callback' => '__return_true',
				'args'                => $this->get_auth_password_reset_args(),
			)
		);

		register_rest_route(
			$this->namespace,
			'/auth/logout',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'auth_logout' ),
				'permission_callback' => array( $this, 'verify_nonce' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/contact',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'handle_contact' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'fname' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'email' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_email',
						'validate_callback' => function ( $v ) { return is_email( $v ); },
					),
					'phone' => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'message' => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_textarea_field',
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/user/orders',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_user_orders' ),
				'permission_callback' => array( $this, 'verify_nonce' ),
				'args'                => array(
					'limit' => array(
						'required'          => false,
						'type'              => 'integer',
						'default'           => 10,
						'sanitize_callback' => 'absint',
					),
					'status' => array(
						'required'          => false,
						'type'              => 'string',
						'default'           => 'any',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}

	public function settings_permission_check(): bool {
		return current_user_can( 'edit_theme_options' );
	}

	public function admin_permission_check(): bool|\WP_Error {
		$nonce = $this->verify_nonce();
		if ( is_wp_error( $nonce ) ) {
			return $nonce;
		}
		return current_user_can( 'manage_options' );
	}

	public function partial_permission_check(): bool {
		return current_user_can( 'edit_theme_options' );
	}

	private function verify_nonce(): bool|\WP_Error {
		if ( ! function_exists( 'wp_verify_nonce' ) ) {
			return true;
		}
		$nonce = '';
		if ( function_exists( 'rest_get_server' ) ) {
			$server = rest_get_server();
			if ( $server ) {
				$request = $server->get_request();
				if ( $request ) {
					$nonce = $request->get_header( 'X-Phantom-Nonce' ) ?? '';
				}
			}
		}
		if ( '' === $nonce ) {
			$nonce = isset( $_SERVER['HTTP_X_PHANTOM_NONCE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_PHANTOM_NONCE'] ) ) : '';
		}
		if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'phantom_api' ) ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'Invalid or missing nonce.', 'phantom-core' ),
				array( 'status' => 401 )
			);
		}
		return true;
	}

	public function settings_write_permission_check(): bool|\WP_Error {
		$nonce = $this->verify_nonce();
		if ( is_wp_error( $nonce ) ) {
			return $nonce;
		}
		return current_user_can( 'edit_theme_options' );
	}

	public function cart_write_permission_check(): bool|\WP_Error {
		return $this->verify_nonce();
	}

	public function get_partial( \WP_REST_Request $request ): \WP_REST_Response {
		$partial_key = $request->get_param( 'partial' );
		$entries     = Settings_Registry::get_instance()->get_entries();

		if ( ! isset( $entries[ $partial_key ] ) || empty( $entries[ $partial_key ]['partial'] ) ) {
			return $this->wp_error( 'invalid_partial', __( 'Invalid partial key.', 'phantom-core' ), 400 );
		}

		$partial     = $entries[ $partial_key ]['partial'];
		$callback    = $partial['render_callback'] ?? '';
		$selector    = $partial['selector'] ?? '';

		if ( ! $callback || ! function_exists( $callback ) ) {
			return $this->wp_error( 'invalid_callback', __( 'Render callback not found.', 'phantom-core' ), 500 );
		}

		ob_start();
		call_user_func( $callback );
		$html = ob_get_clean();

		return new \WP_REST_Response(
			array(
				'html'     => $html,
				'key'      => $partial_key,
				'selector' => $selector,
			),
			200
		);
	}

	public function get_settings( \WP_REST_Request $request ): \WP_REST_Response {
		$registry = Settings_Registry::get_instance();
		$entries  = $registry->get_entries();
		$section  = $request->get_param( 'section' );
		$per_page = max( 1, min( 100, absint( $request->get_param( 'per_page' ) ?: 50 ) ) );
		$page     = max( 1, absint( $request->get_param( 'page' ) ?: 1 ) );

		if ( ! empty( $section ) ) {
			$entries = array_filter(
				$entries,
				function ( array $entry ) use ( $section ): bool {
					return ( $entry['section'] ?? '' ) === $section;
				}
			);
		}

		$total  = count( $entries );
		$offset = ( $page - 1 ) * $per_page;
		$slice  = array_slice( $entries, $offset, $per_page, true );

		$items = array();
		foreach ( $slice as $key => $entry ) {
			$items[] = $this->format_entry( $key, $entry );
		}

		$response = new \WP_REST_Response( $items, 200 );
		$response->header( 'X-WP-Total', (string) $total );
		$response->header( 'X-WP-TotalPages', (string) (int) ceil( $total / $per_page ) );
		if ( empty( $section ) ) {
			$this->set_cache_headers( $response, 600 );
		}
		return $response;
	}

	public function get_setting( \WP_REST_Request $request ): \WP_REST_Response {
		$key   = sanitize_key( $request->get_param( 'key' ) );
		$entry = $this->get_entry_or_error( $key );
		if ( is_wp_error( $entry ) ) {
			return $this->error_response( 'not_found', __( 'Setting not found.', 'phantom-core' ), 404 );
		}
		return new \WP_REST_Response( $this->format_entry( $key, $entry ), 200 );
	}

	public function update_settings( \WP_REST_Request $request ): \WP_REST_Response {
		$settings = $request->get_param( 'settings' );
		$changes  = $request->get_param( 'changes' );

		$registry = Settings_Registry::get_instance();
		$updated  = array();
		$errors   = array();

		if ( is_array( $changes ) && ! empty( $changes ) && ! is_array( $settings ) ) {
			foreach ( $changes as $index => $item ) {
				if ( ! is_array( $item ) || ! isset( $item['key'] ) ) {
					$errors[] = sprintf(
						/* translators: %d: item index */
						__( 'Item at index %d is missing a key.', 'phantom-core' ),
						$index
					);
					continue;
				}
				$key = sanitize_key( $item['key'] );
				if ( ! $registry->has( $key ) ) {
					$errors[] = sprintf(
						/* translators: %s: setting key */
						__( 'Unknown setting key: %s', 'phantom-core' ),
						$key
					);
					continue;
				}
				$value = array_key_exists( 'value', $item ) ? $item['value'] : null;
				$registry->set( $key, $value );
				$updated[ $key ] = $registry->get( $key );
			}
		} elseif ( is_array( $settings ) && ! empty( $settings ) ) {
			foreach ( $settings as $key => $value ) {
				$key = sanitize_key( (string) $key );
				if ( ! $registry->has( $key ) ) {
					$errors[] = sprintf(
						/* translators: %s: setting key */
						__( 'Unknown setting key: %s', 'phantom-core' ),
						$key
					);
					continue;
				}
				$registry->set( $key, $value );
				$updated[ $key ] = $registry->get( $key );
			}
		} else {
			return $this->wp_error( 'invalid_params', __( 'Provide a settings object or a changes array.', 'phantom-core' ), 400 );
		}

		\PhantomCore\Customizer::get_instance()->sync_options();

		delete_transient( 'phantom_page_data' );
		\Phantom_Custom_CSS::flush_cache();

		return new \WP_REST_Response(
			array(
				'updated' => $updated,
				'errors'  => $errors,
			),
			empty( $errors ) ? 200 : 207
		);
	}

	public function update_setting( \WP_REST_Request $request ): \WP_REST_Response {
		$key   = sanitize_key( $request->get_param( 'key' ) );
		$entry = $this->get_entry_or_error( $key );
		if ( is_wp_error( $entry ) ) {
			return $this->error_response( 'not_found', __( 'Setting not found.', 'phantom-core' ), 404 );
		}

		$value = $request->get_param( 'value' );
		Settings_Registry::get_instance()->set( $key, $value );

		\PhantomCore\Customizer::get_instance()->sync_options();
		delete_transient( 'phantom_page_data' );
		\Phantom_Custom_CSS::flush_cache();

		return new \WP_REST_Response( $this->format_entry( $key, $entry, true ), 200 );
	}

	public function delete_setting( \WP_REST_Request $request ): \WP_REST_Response {
		$key   = sanitize_key( $request->get_param( 'key' ) );
		$entry = $this->get_entry_or_error( $key );
		if ( is_wp_error( $entry ) ) {
			return $this->error_response( 'not_found', __( 'Setting not found.', 'phantom-core' ), 404 );
		}

		$default = $entry['default'] ?? null;
		delete_option( 'phantom_' . $key );
		Settings_Registry::get_instance()->set( $key, $default );

		\PhantomCore\Customizer::get_instance()->sync_options();
		delete_transient( 'phantom_page_data' );
		\Phantom_Custom_CSS::flush_cache();

		return new \WP_REST_Response(
			array(
				'key'     => $key,
				'default' => $default,
				'reset'   => true,
			),
			200
		);
	}

	public function error_response( string $code, string $message, int $status = 400 ): \WP_REST_Response {
		return new \WP_REST_Response(
			array(
				'code'    => $code,
				'message' => $message,
				'status'  => $status,
			),
			$status
		);
	}

	/**
	 * Return a WP_Error for REST callbacks. WordPress REST infrastructure
	 * converts WP_Error to a proper JSON error response automatically.
	 */
	private function check_rate_limit( string $action, int $max_attempts = 5, int $window = 60 ): ?\WP_Error {
		$ip      = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
		$key     = 'phantom_rate_' . $action . '_' . md5( $ip );
		$attempts = (int) get_transient( $key );

		if ( $attempts >= $max_attempts ) {
			return new \WP_Error(
				'rate_limit_exceeded',
				__( 'Too many attempts. Please try again later.', 'phantom-core' ),
				array( 'status' => 429 )
			);
		}

		set_transient( $key, $attempts + 1, $window );
		return null;
	}

	public function wp_error( string $code, string $message, int $status = 400 ): \WP_Error {
		return new \WP_Error( $code, $message, array( 'status' => $status ) );
	}

	public function get_schema(): \WP_REST_Response {
		$registry = Settings_Registry::get_instance();
		$entries  = $registry->get_entries();
		$schema   = array();
		foreach ( $entries as $key => $entry ) {
			$schema[ $key ] = $this->clean_entry( $entry );
		}
		return new \WP_REST_Response( $schema, 200 );
	}

	public function get_options(): \WP_REST_Response {
		$registry = Settings_Registry::get_instance();
		$entries  = $registry->get_entries();

		$options = array();
		foreach ( $entries as $key => $entry ) {
			$section = $entry['section'] ?? '';
			if ( in_array( $section, array( 'typography', 'colors', 'buttons', 'layout', 'spacing', 'header', 'footer' ), true ) ) {
				$options[ $key ] = $registry->get( $key );
			}
		}

		return new \WP_REST_Response( $options, 200 );
	}

	public function get_persistent_options(): \WP_REST_Response {
		$registry = Settings_Registry::get_instance();
		$entries  = $registry->get_entries();

		$options = array();
		foreach ( $entries as $key => $entry ) {
			$section = $entry['section'] ?? '';
			if ( in_array( $section, array( 'typography', 'colors', 'buttons', 'layout', 'spacing', 'header', 'footer', 'branding' ), true ) ) {
				$options[ $key ] = $registry->get( $key );
			}
		}

		return new \WP_REST_Response( $options, 200 );
	}

	public function export_settings(): \WP_REST_Response {
		$registry = Settings_Registry::get_instance();
		$entries  = $registry->get_entries();
		$settings = array();
		foreach ( $entries as $key => $entry ) {
			$settings[ $key ] = $registry->get( $key );
		}

		return new \WP_REST_Response(
			array(
				'version'  => PHANTOM_CORE_VERSION,
				'exported' => current_time( 'mysql' ),
				'settings' => $settings,
			),
			200
		);
	}

	public function import_settings( \WP_REST_Request $request ): \WP_REST_Response {
		if ( ! current_user_can( 'manage_options' ) ) {
			return $this->wp_error( 'unauthorized', __( 'You do not have permission to import settings.', 'phantom-core' ), 403 );
		}

		$body_json = $request->get_body();
		if ( strlen( $body_json ) > 5 * 1024 * 1024 ) {
			return $this->wp_error( 'payload_too_large', __( 'Payload exceeds maximum size of 5 MB.', 'phantom-core' ), 413 );
		}

		$rate_key = 'phantom_import_rate_' . get_current_user_id();
		$last     = get_transient( $rate_key );
		if ( false !== $last ) {
			return $this->wp_error(
				'rate_limited',
				__( 'Import rate limit reached. Please wait 60 seconds before trying again.', 'phantom-core' ),
				429
			);
		}

		$settings = $request->get_param( 'settings' );
		if ( ! is_array( $settings ) || empty( $settings ) ) {
			return $this->wp_error( 'invalid_settings', __( 'The settings parameter must be a non-empty object.', 'phantom-core' ), 400 );
		}

		set_transient( $rate_key, time(), 60 );

		$registry = Settings_Registry::get_instance();
		$imported = array();
		$errors   = array();

		foreach ( $settings as $key => $value ) {
			$key = sanitize_key( (string) $key );
			if ( ! $registry->has( $key ) ) {
				$errors[] = sprintf(
					/* translators: %s: setting key */
					__( 'Unknown setting key: %s', 'phantom-core' ),
					$key
				);
				continue;
			}
			$registry->set( $key, $value );
			$imported[] = $key;
		}

		$registry->flush_cache();
		delete_transient( 'phantom_page_data' );
		\Phantom_Custom_CSS::flush_cache();

		return new \WP_REST_Response(
			array(
				'imported' => $imported,
				'errors'   => $errors,
			),
			empty( $errors ) ? 200 : 207
		);
	}

	public function flush_cache(): \WP_REST_Response {
		Settings_Registry::get_instance()->flush_cache();
		delete_transient( 'phantom_page_data' );
		\Phantom_Custom_CSS::flush_cache();
		return new \WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'All Phantom caches flushed.', 'phantom-core' ),
			),
			200
		);
	}

	public function get_posts( \WP_REST_Request $request ): \WP_REST_Response {
		$per_page = max( 1, min( 100, absint( $request->get_param( 'per_page' ) ?: 10 ) ) );
		$page     = max( 1, absint( $request->get_param( 'page' ) ?: 1 ) );
		$category = sanitize_text_field( $request->get_param( 'category' ) ?? '' );

		$args = array(
			'post_type'      => 'post',
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'post_status'    => 'publish',
		);

		if ( ! empty( $category ) ) {
			$args['category_name'] = $category;
		}

		$query    = new \WP_Query( $args );
		$posts    = array();
		$registry = Settings_Registry::get_instance();

		foreach ( $query->posts as $post ) {
			$post_id   = $post->ID;
			$excerpt   = get_the_excerpt( $post );
			$read_more = $registry->get_string( 'blog_read_more_text', 'Read More' );

			$posts[] = array(
				'id'             => $post_id,
				'title'          => get_the_title( $post ),
				'slug'           => $post->post_name,
				'excerpt'        => $excerpt ?: wp_trim_words( $post->post_content, 40, '...' ),
				'content'        => apply_filters( 'the_content', $post->post_content ),
				'date'           => get_the_date( 'c', $post ),
				'author'         => get_the_author_meta( 'display_name', $post->post_author ),
				'featured_image' => get_the_post_thumbnail_url( $post_id, 'large' ) ?: '',
				'categories'     => wp_get_post_categories(
					$post_id,
					array( 'fields' => 'names' )
				),
				'read_more_text' => $read_more,
				'reading_time'   => $this->calculate_reading_time( $post->post_content ),
				'url'            => get_permalink( $post ),
			);
		}

		$response = new \WP_REST_Response(
			array(
				'posts'      => $posts,
				'total'      => $query->found_posts,
				'totalPages' => $query->max_num_pages,
				'page'       => $page,
			),
			200
		);
		$response->header( 'X-WP-Total', (string) $query->found_posts );
		$response->header( 'X-WP-TotalPages', (string) $query->max_num_pages );
		$this->set_cache_headers( $response, 600 );
		return $response;
	}

	public function get_post_by_slug( \WP_REST_Request $request ): \WP_REST_Response {
		$slug = sanitize_title( $request->get_param( 'slug' ) );
		$post = get_page_by_path( $slug, OBJECT, 'post' );

		// Fallback: try numeric ID
		if ( ! $post && is_numeric( $slug ) ) {
			$post = get_post( (int) $slug );
		}

		if ( ! $post ) {
			return $this->wp_error( 'not_found', __( 'Post not found.', 'phantom-core' ), 404 );
		}

		setup_postdata( $post );
		$post_id     = $post->ID;
		$excerpt     = get_the_excerpt( $post );
		$categories  = wp_get_post_categories( $post_id, array( 'fields' => 'all' ) );
		$tags        = wp_get_post_tags( $post_id, array( 'fields' => 'names' ) );
		$related     = $this->get_related_posts( $post_id );
		$cat_list    = array();
		foreach ( $categories as $cat ) {
			$cat_list[] = array(
				'name' => $cat->name,
				'slug' => $cat->slug,
				'url'  => get_category_link( $cat->term_id ),
			);
		}

		$data = array(
			'id'             => $post_id,
			'title'          => get_the_title( $post ),
			'slug'           => $post->post_name,
			'content'        => apply_filters( 'the_content', $post->post_content ),
			'excerpt'        => $excerpt ?: wp_trim_words( $post->post_content, 40, '...' ),
			'date'           => get_the_date( 'c', $post ),
			'modified'       => get_the_modified_date( 'c', $post ),
			'author'         => get_the_author_meta( 'display_name', $post->post_author ),
			'featured_image' => get_the_post_thumbnail_url( $post_id, 'large' ) ?: '',
			'categories'     => $cat_list,
			'tags'           => $tags,
			'reading_time'   => $this->calculate_reading_time( $post->post_content ),
			'related_posts'  => $related,
			'url'            => get_permalink( $post ),
		);
		wp_reset_postdata();

		return new \WP_REST_Response( $data, 200 );
	}

	public function get_page_by_slug( \WP_REST_Request $request ): \WP_REST_Response {
		$slug = sanitize_title( $request->get_param( 'slug' ) );
		$page = get_page_by_path( $slug, OBJECT, 'page' );

		if ( ! $page ) {
			return $this->wp_error( 'not_found', __( 'Page not found.', 'phantom-core' ), 404 );
		}

		return new \WP_REST_Response(
			array(
				'id'             => $page->ID,
				'title'          => get_the_title( $page ),
				'slug'           => $page->post_name,
				'content'        => apply_filters( 'the_content', $page->post_content ),
				'excerpt'        => get_the_excerpt( $page ),
				'date'           => get_the_date( 'c', $page ),
				'modified'       => get_the_modified_date( 'c', $page ),
				'featured_image' => get_the_post_thumbnail_url( $page->ID, 'large' ) ?: '',
				'url'            => get_permalink( $page ),
			),
			200
		);
	}

	public function get_categories(): \WP_REST_Response {
		$items = array();

		if ( class_exists( 'WooCommerce' ) ) {
			$product_cats = get_terms(
				array(
					'taxonomy'   => 'product_cat',
					'hide_empty' => true,
					'orderby'    => 'name',
					'order'      => 'ASC',
				)
			);
			if ( is_array( $product_cats ) ) {
				foreach ( $product_cats as $cat ) {
					$cat_url = get_term_link( $cat );
					$items[] = array(
						'id'       => $cat->term_id,
						'name'     => $cat->name,
						'slug'     => $cat->slug,
						'count'    => $cat->count,
						'url'      => is_wp_error( $cat_url ) ? '' : $cat_url,
						'parent'   => $cat->parent,
						'taxonomy' => 'product_cat',
					);
				}
			}
		}

		$post_cats = get_categories(
			array(
				'hide_empty' => true,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);
		foreach ( $post_cats as $cat ) {
			$items[] = array(
				'id'       => $cat->term_id,
				'name'     => $cat->name,
				'slug'     => $cat->slug,
				'count'    => $cat->count,
				'url'      => get_category_link( $cat->term_id ),
				'parent'   => $cat->parent,
				'taxonomy' => 'category',
			);
		}

		$response = new \WP_REST_Response( $items, 200 );
		$this->set_cache_headers( $response, 3600 );
		return $response;
	}

	public function get_menu( \WP_REST_Request $request ): \WP_REST_Response {
		$location = sanitize_text_field( $request->get_param( 'location' ) );
		$theme_locations = get_nav_menu_locations();

		if ( ! isset( $theme_locations[ $location ] ) ) {
			return $this->wp_error( 'not_found', __( 'Menu location not found.', 'phantom-core' ), 404 );
		}

		$menu_id = $theme_locations[ $location ];
		$items   = wp_get_nav_menu_items( $menu_id );

		if ( ! $items ) {
			return new \WP_REST_Response( array(), 200 );
		}

		$tree = $this->build_menu_tree( $items );
		$tree = $this->enrich_menu_tree( $tree );

		return new \WP_REST_Response(
			array(
				'location' => $location,
				'menu_id'  => $menu_id,
				'items'    => $tree,
			),
			200
		);
	}

	public function get_products( \WP_REST_Request $request ): \WP_REST_Response {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return $this->wp_error( 'woocommerce_inactive', __( 'WooCommerce is not active.', 'phantom-core' ), 400 );
		}

		$per_page    = max( 1, min( 100, absint( $request->get_param( 'per_page' ) ?: 12 ) ) );
		$page        = max( 1, absint( $request->get_param( 'page' ) ?: 1 ) );
		$category    = sanitize_text_field( $request->get_param( 'category' ) ?? '' );
		$search      = sanitize_text_field( $request->get_param( 'search' ) ?? '' );
		$orderby     = sanitize_text_field( $request->get_param( 'orderby' ) ?? 'date' );
		$order       = strtoupper( sanitize_text_field( $request->get_param( 'order' ) ?? 'DESC' ) );
		$min_price   = $request->has_param( 'min_price' ) ? floatval( $request->get_param( 'min_price' ) ) : null;
		$max_price   = $request->has_param( 'max_price' ) ? floatval( $request->get_param( 'max_price' ) ) : null;
		$on_sale     = rest_sanitize_boolean( $request->get_param( 'on_sale' ) ?? false );
		$stock_status = sanitize_text_field( $request->get_param( 'stock_status' ) ?? '' );
		$featured    = rest_sanitize_boolean( $request->get_param( 'featured' ) ?? false );
		$tag         = sanitize_text_field( $request->get_param( 'tag' ) ?? '' );

		$valid_orderby = array( 'date', 'id', 'title', 'name', 'price', 'popularity', 'rating', 'rand', 'menu_order' );
		if ( ! in_array( $orderby, $valid_orderby, true ) ) {
			$orderby = 'date';
		}
		if ( ! in_array( $order, array( 'ASC', 'DESC' ), true ) ) {
			$order = 'DESC';
		}

		$args = array(
			'post_type'      => 'product',
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'post_status'    => 'publish',
		);

		$orderby_map = array(
			'price'       => array( 'meta_key' => '_price', 'orderby' => 'meta_value_num' ),
			'popularity'  => array( 'meta_key' => 'total_sales', 'orderby' => 'meta_value_num' ),
			'rating'      => array( 'meta_key' => '_wc_average_rating', 'orderby' => 'meta_value_num' ),
		);
		if ( isset( $orderby_map[ $orderby ] ) ) {
			$args = array_merge( $args, $orderby_map[ $orderby ] );
		} else {
			$args['orderby'] = $orderby;
		}
		$args['order'] = $order;

		if ( ! empty( $category ) ) {
			$args['product_cat'] = $category;
		}

		if ( ! empty( $search ) ) {
			$args['s'] = $search;
		}

		if ( $featured ) {
			$args['tax_query'][] = array(
				'taxonomy' => 'product_visibility',
				'field'    => 'name',
				'terms'    => 'featured',
			);
		}

		if ( ! empty( $tag ) ) {
			$args['product_tag'] = $tag;
		}

		if ( ! empty( $stock_status ) ) {
			$args['meta_query'][] = array(
				'key'   => '_stock_status',
				'value' => $stock_status,
			);
		}

		if ( $on_sale ) {
			$args['meta_query'][] = array(
				'relation' => 'OR',
				array(
					'key'     => '_sale_price',
					'value'   => 0,
					'compare' => '>',
					'type'    => 'NUMERIC',
				),
				array(
					'key'     => '_min_variation_sale_price',
					'value'   => 0,
					'compare' => '>',
					'type'    => 'NUMERIC',
				),
			);
		}

		// Price meta query needs special handling when combined with existing meta_query
		if ( null !== $min_price || null !== $max_price ) {
			$price_query = array( 'key' => '_price', 'type' => 'NUMERIC' );
			if ( null !== $min_price && null !== $max_price ) {
				$price_query['value'] = array( $min_price, $max_price );
				$price_query['compare'] = 'BETWEEN';
			} elseif ( null !== $min_price ) {
				$price_query['value'] = $min_price;
				$price_query['compare'] = '>=';
			} elseif ( null !== $max_price ) {
				$price_query['value'] = $max_price;
				$price_query['compare'] = '<=';
			}
			$args['meta_query'][] = $price_query;
		}

		$query    = new \WP_Query( $args );
		$products = array();

		while ( $query->have_posts() ) {
			$query->the_post();
			$product_id = get_the_ID();
			$product    = wc_get_product( $product_id );

			if ( ! $product ) {
				continue;
			}

			$products[] = $this->format_product( $product );
		}
		wp_reset_postdata();

		$response = new \WP_REST_Response(
			array(
				'products'   => $products,
				'total'      => $query->found_posts,
				'totalPages' => $query->max_num_pages,
				'page'       => $page,
			),
			200
		);
		$response->header( 'X-WP-Total', (string) $query->found_posts );
		$response->header( 'X-WP-TotalPages', (string) $query->max_num_pages );
		$this->set_cache_headers( $response, 600 );
		return $response;
	}

	public function get_featured_products(): \WP_REST_Response {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return $this->wp_error( 'woocommerce_inactive', __( 'WooCommerce is not active.', 'phantom-core' ), 400 );
		}

		$args = array(
			'post_type'      => 'product',
			'posts_per_page' => 8,
			'post_status'    => 'publish',
			'tax_query'      => array(
				array(
					'taxonomy' => 'product_visibility',
					'field'    => 'name',
					'terms'    => 'featured',
				),
			),
		);

		$query    = new \WP_Query( $args );
		$products = array();

		while ( $query->have_posts() ) {
			$query->the_post();
			$product = wc_get_product( get_the_ID() );
			if ( $product ) {
				$products[] = $this->format_product( $product );
			}
		}
		wp_reset_postdata();

		$response = new \WP_REST_Response( $products, 200 );
		$this->set_cache_headers( $response, 600 );
		return $response;
	}

	public function create_product( \WP_REST_Request $request ): \WP_REST_Response {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return $this->wp_error( 'woocommerce_inactive', __( 'WooCommerce is not active.', 'phantom-core' ), 400 );
		}
		$name        = sanitize_text_field( $request->get_param( 'name' ) );
		$description = wp_kses_post( $request->get_param( 'description' ) );
		$short_desc  = wp_kses_post( $request->get_param( 'short_description' ) );
		$price       = wc_format_decimal( $request->get_param( 'regular_price' ) );
		$sale_price  = wc_format_decimal( $request->get_param( 'sale_price' ) );
		$sku         = sanitize_text_field( $request->get_param( 'sku' ) );
		$stock_qty   = $request->has_param( 'stock_quantity' ) ? absint( $request->get_param( 'stock_quantity' ) ) : null;
		$is_featured = rest_sanitize_boolean( $request->get_param( 'is_featured' ) ?? false );
		$type        = in_array( $request->get_param( 'type' ), array( 'simple', 'variable', 'grouped', 'external' ), true ) ? $request->get_param( 'type' ) : 'simple';

		if ( empty( $name ) ) {
			return $this->wp_error( 'missing_name', __( 'Product name is required.', 'phantom-core' ), 400 );
		}

		$product = wc_get_product_object( $type );
		$product->set_name( $name );
		$product->set_status( 'publish' );
		$product->set_catalog_visibility( 'visible' );
		$product->set_description( $description );
		$product->set_short_description( $short_desc );

		if ( in_array( $type, array( 'simple', 'external' ), true ) ) {
			$product->set_regular_price( $price );
			if ( $sale_price ) {
				$product->set_sale_price( $sale_price );
			}
		}
		if ( $sku ) {
			$product->set_sku( $sku );
		}
		if ( null !== $stock_qty && in_array( $type, array( 'simple', 'external' ), true ) ) {
			$product->set_manage_stock( true );
			$product->set_stock_quantity( $stock_qty );
		}
		$product->set_featured( $is_featured );

		if ( $type === 'external' ) {
			$product_url = esc_url_raw( $request->get_param( 'product_url' ) );
			if ( $product_url ) {
				$product->set_product_url( $product_url );
			}
		}

		$product_id = $product->save();

		if ( ! $product_id ) {
			return $this->wp_error( 'create_failed', __( 'Failed to create product.', 'phantom-core' ), 500 );
		}

		if ( ! empty( $request->get_param( 'categories' ) ) ) {
			$cat_ids = array_map( 'absint', (array) $request->get_param( 'categories' ) );
			wp_set_object_terms( $product_id, $cat_ids, 'product_cat' );
		}

		$video_url = esc_url_raw( $request->get_param( 'video_url' ) );
		if ( $video_url ) {
			update_post_meta( $product_id, '_product_video', $video_url );
		}

		$images_360 = $request->get_param( 'images_360' );
		if ( ! empty( $images_360 ) && is_array( $images_360 ) ) {
			update_post_meta( $product_id, '_product_360_images', implode( ',', array_map( 'esc_url_raw', $images_360 ) ) );
		}

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return $this->wp_error( 'create_failed', __( 'Product created but could not be retrieved.', 'phantom-core' ), 500 );
		}
		return new \WP_REST_Response( $this->format_product( $product, true ), 201 );
	}

	public function update_product( \WP_REST_Request $request ): \WP_REST_Response {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return $this->wp_error( 'woocommerce_inactive', __( 'WooCommerce is not active.', 'phantom-core' ), 400 );
		}
		$id      = absint( $request->get_param( 'id' ) );
		$product = wc_get_product( $id );
		if ( ! $product ) {
			return $this->wp_error( 'not_found', __( 'Product not found.', 'phantom-core' ), 404 );
		}

		if ( $request->has_param( 'name' ) ) {
			$product->set_name( sanitize_text_field( $request->get_param( 'name' ) ) );
		}
		if ( $request->has_param( 'description' ) ) {
			$product->set_description( wp_kses_post( $request->get_param( 'description' ) ) );
		}
		if ( $request->has_param( 'short_description' ) ) {
			$product->set_short_description( wp_kses_post( $request->get_param( 'short_description' ) ) );
		}
		if ( $request->has_param( 'regular_price' ) ) {
			$product->set_regular_price( wc_format_decimal( $request->get_param( 'regular_price' ) ) );
		}
		if ( $request->has_param( 'sale_price' ) ) {
			$product->set_sale_price( wc_format_decimal( $request->get_param( 'sale_price' ) ) );
		}
		if ( $request->has_param( 'sku' ) ) {
			$product->set_sku( sanitize_text_field( $request->get_param( 'sku' ) ) );
		}
		if ( $request->has_param( 'stock_quantity' ) ) {
			$product->set_manage_stock( true );
			$product->set_stock_quantity( absint( $request->get_param( 'stock_quantity' ) ) );
		}
		if ( $request->has_param( 'is_featured' ) ) {
			$product->set_featured( rest_sanitize_boolean( $request->get_param( 'is_featured' ) ) );
		}
		if ( $request->has_param( 'video_url' ) ) {
			update_post_meta( $id, '_product_video', esc_url_raw( $request->get_param( 'video_url' ) ) );
		}
		if ( $request->has_param( 'images_360' ) ) {
			$images_360 = $request->get_param( 'images_360' );
			if ( is_array( $images_360 ) ) {
				update_post_meta( $id, '_product_360_images', implode( ',', array_map( 'esc_url_raw', $images_360 ) ) );
			}
		}
		if ( $request->has_param( 'categories' ) ) {
			$cat_ids = array_map( 'absint', (array) $request->get_param( 'categories' ) );
			wp_set_object_terms( $id, $cat_ids, 'product_cat' );
		}

		$saved = $product->save();
		if ( ! $saved ) {
			return $this->wp_error( 'update_failed', __( 'Failed to update product.', 'phantom-core' ), 500 );
		}
		$product = wc_get_product( $id );
		return new \WP_REST_Response( $this->format_product( $product, true ), 200 );
	}

	public function delete_product( \WP_REST_Request $request ): \WP_REST_Response {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return $this->wp_error( 'woocommerce_inactive', __( 'WooCommerce is not active.', 'phantom-core' ), 400 );
		}
		$id      = absint( $request->get_param( 'id' ) );
		$product = wc_get_product( $id );
		if ( ! $product ) {
			return $this->wp_error( 'not_found', __( 'Product not found.', 'phantom-core' ), 404 );
		}
		$deleted = $product->delete( true );
		if ( ! $deleted ) {
			return $this->wp_error( 'delete_failed', __( 'Failed to delete product.', 'phantom-core' ), 500 );
		}
		return new \WP_REST_Response( array( 'deleted' => true, 'id' => $id ), 200 );
	}

	public function get_product( \WP_REST_Request $request ): \WP_REST_Response {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return $this->wp_error( 'woocommerce_inactive', __( 'WooCommerce is not active.', 'phantom-core' ), 400 );
		}

		$id      = absint( $request->get_param( 'id' ) );
		$product = wc_get_product( $id );

		if ( ! $product ) {
			return $this->wp_error( 'not_found', __( 'Product not found.', 'phantom-core' ), 404 );
		}

		$data = $this->format_product( $product, true );

		$data['related_products'] = array();
		$related_ids              = wc_get_related_products( $id, 4 );
		foreach ( $related_ids as $rid ) {
			$rp = wc_get_product( $rid );
			if ( $rp ) {
				$data['related_products'][] = $this->format_product( $rp );
			}
		}

		return new \WP_REST_Response( $data, 200 );
	}

	public function add_to_cart_endpoint( \WP_REST_Request $request ): \WP_REST_Response {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return $this->wp_error( 'woocommerce_inactive', __( 'WooCommerce is not active.', 'phantom-core' ), 400 );
		}
		try {
			$product_id    = $request->get_param( 'product_id' );
			$quantity      = $request->get_param( 'quantity' );
			$variation_id  = $request->get_param( 'variation_id' );
			$variation     = $request->get_param( 'variation' );
			$cart_item_key = WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, (array) $variation );
			if ( false === $cart_item_key ) {
				return $this->wp_error( 'add_to_cart_failed', __( 'Could not add item to cart.', 'phantom-core' ), 400 );
			}
			return new \WP_REST_Response( $this->get_cart_data(), 200 );
		} catch ( \Throwable $e ) {
			return $this->wp_error( 'server_error', __( 'Internal server error.', 'phantom-core' ), 500 );
		}
	}

	public function update_cart_item_endpoint( \WP_REST_Request $request ): \WP_REST_Response {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return $this->wp_error( 'woocommerce_inactive', __( 'WooCommerce is not active.', 'phantom-core' ), 400 );
		}
		try {
			$key      = $request->get_param( 'key' );
			$quantity = $request->get_param( 'quantity' );
			if ( null === WC()->cart->get_cart_item( $key ) ) {
				return $this->wp_error( 'invalid_key', __( 'Cart item not found.', 'phantom-core' ), 400 );
			}
			WC()->cart->set_quantity( $key, $quantity );
			return new \WP_REST_Response( $this->get_cart_data(), 200 );
		} catch ( \Throwable $e ) {
			return $this->wp_error( 'server_error', __( 'Internal server error.', 'phantom-core' ), 500 );
		}
	}

	public function remove_cart_item_endpoint( \WP_REST_Request $request ): \WP_REST_Response {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return $this->wp_error( 'woocommerce_inactive', __( 'WooCommerce is not active.', 'phantom-core' ), 400 );
		}
		try {
			$key = $request->get_param( 'key' );
			if ( null === WC()->cart->get_cart_item( $key ) ) {
				return $this->wp_error( 'invalid_key', __( 'Cart item not found.', 'phantom-core' ), 400 );
			}
			WC()->cart->remove_cart_item( $key );
			return new \WP_REST_Response( $this->get_cart_data(), 200 );
		} catch ( \Throwable $e ) {
			return $this->wp_error( 'server_error', __( 'Internal server error.', 'phantom-core' ), 500 );
		}
	}

	public function apply_coupon_endpoint( \WP_REST_Request $request ): \WP_REST_Response {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return $this->wp_error( 'woocommerce_inactive', __( 'WooCommerce is not active.', 'phantom-core' ), 400 );
		}
		try {
			$code   = $request->get_param( 'code' );
			$result = WC()->cart->apply_coupon( $code );
			if ( is_wp_error( $result ) ) {
				return $this->wp_error( 'coupon_error', $result->get_error_message(), 400 );
			}
			return new \WP_REST_Response( $this->get_cart_data(), 200 );
		} catch ( \Throwable $e ) {
			return $this->wp_error( 'server_error', __( 'Internal server error.', 'phantom-core' ), 500 );
		}
	}

	public function remove_coupon_endpoint( \WP_REST_Request $request ): \WP_REST_Response {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return $this->wp_error( 'woocommerce_inactive', __( 'WooCommerce is not active.', 'phantom-core' ), 400 );
		}
		try {
			$code = $request->get_param( 'code' );
			WC()->cart->remove_coupon( $code );
			return new \WP_REST_Response( $this->get_cart_data(), 200 );
		} catch ( \Throwable $e ) {
			return $this->wp_error( 'server_error', __( 'Internal server error.', 'phantom-core' ), 500 );
		}
	}

	public function get_shipping_methods(): \WP_REST_Response {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return $this->wp_error( 'woocommerce_inactive', __( 'WooCommerce is not active.', 'phantom-core' ), 400 );
		}
		try {
			WC()->cart->calculate_shipping();
			$packages = WC()->shipping()->calculate_shipping( WC()->cart->get_shipping_packages() );
			$methods  = array();
			$selected = '';
			foreach ( $packages as $package ) {
				if ( empty( $package['rates'] ) ) {
					continue;
				}
				foreach ( $package['rates'] as $rate ) {
					$methods[] = array(
						'id'    => $rate->get_id(),
						'label' => $rate->get_label(),
						'cost'  => $rate->get_cost(),
						'tax'   => $rate->get_shipping_tax(),
					);
				}
				$chosen = WC()->session->get( 'chosen_shipping_methods', array() );
				if ( isset( $chosen[ $package['index'] ] ) ) {
					$selected = $chosen[ $package['index'] ];
				} elseif ( ! empty( $package['rates'] ) ) {
					$rate_ids = array_keys( $package['rates'] );
					$selected = $rate_ids[0];
				}
			}
			return new \WP_REST_Response(
				array(
					'success'  => true,
					'methods'  => $methods,
					'selected' => $selected,
				),
				200
			);
		} catch ( \Throwable $e ) {
			return $this->wp_error( 'server_error', __( 'Could not retrieve shipping methods.', 'phantom-core' ), 500 );
		}
	}

	public function get_cart(): \WP_REST_Response {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return $this->wp_error( 'woocommerce_inactive', __( 'WooCommerce is not active.', 'phantom-core' ), 400 );
		}
		try {
			return new \WP_REST_Response( $this->get_cart_data(), 200 );
		} catch ( \Throwable $e ) {
			return new \WP_REST_Response( array( 'items' => array(), 'total' => '', 'totalItems' => 0, 'currency' => '' ), 200 );
		}
	}

	public function get_page_data(): \WP_REST_Response {
		try {
			$cached = get_transient( 'phantom_page_data' );
			if ( false !== $cached ) {
				$cached['cart'] = $this->get_cart_data();
				return new \WP_REST_Response( $cached, 200 );
			}

			$registry = Settings_Registry::get_instance();
			$settings = array();
			foreach ( $registry->get_entries() as $key => $entry ) {
				$settings[ $key ] = $registry->get( $key );
			}

			$menus     = array();
			$locations = get_nav_menu_locations();
			foreach ( $locations as $location => $menu_id ) {
				$menu_items = wp_get_nav_menu_items( $menu_id );
				if ( $menu_items ) {
				$tree = $this->build_menu_tree( $menu_items );
				$tree = $this->enrich_menu_tree( $tree );
				$menus[ $location ] = array(
					'location' => $location,
					'menu_id'  => $menu_id,
					'items'    => $tree,
				);
				}
			}

			$data = array(
				'settings' => $settings,
				'menus'    => $menus,
			);

		$data['pagination'] = array();

		$product_count = $registry->get_int( 'home_products_count', 8 );
		if ( class_exists( 'WooCommerce' ) ) {
			$products = wc_get_products(
				array(
					'limit'  => $product_count,
					'status' => 'publish',
				)
			);
			$data['products'] = array();
			foreach ( $products as $product ) {
				$data['products'][] = $this->format_product( $product );
			}
			$product_total = wp_count_posts( 'product' );
			$data['pagination']['totalProducts'] = (int) ( $product_total->publish ?? 0 );
		}

		$post_count = $registry->get_int( 'home_blog_count', 3 );
		$posts      = get_posts(
			array(
				'post_type'      => 'post',
				'posts_per_page' => $post_count,
				'post_status'    => 'publish',
			)
		);
		$data['posts'] = array();
		foreach ( $posts as $post ) {
			$data['posts'][] = array(
				'id'             => $post->ID,
				'title'          => get_the_title( $post ),
				'slug'           => $post->post_name,
				'excerpt'        => get_the_excerpt( $post ) ?: wp_trim_words( $post->post_content, 40, '...' ),
				'date'           => get_the_date( 'c', $post ),
				'featured_image' => get_the_post_thumbnail_url( $post->ID, 'large' ) ?: '',
				'url'            => get_permalink( $post ),
			);
		}
		$data['pagination']['totalPosts'] = (int) wp_count_posts( 'post' )->publish;

		$data['categories'] = array();
		if ( class_exists( 'WooCommerce' ) ) {
			$cats = get_terms(
				array(
					'taxonomy'   => 'product_cat',
					'hide_empty' => true,
				)
			);
			foreach ( $cats as $cat ) {
				$data['categories'][] = array(
					'name' => $cat->name,
					'slug' => $cat->slug,
					'count' => $cat->count,
				);
			}
		} else {
			$cats = get_categories( array( 'hide_empty' => true ) );
			foreach ( $cats as $cat ) {
				$data['categories'][] = array(
					'name' => $cat->name,
					'slug' => $cat->slug,
					'count' => $cat->count,
				);
			}
		}

		$cache_data = $data;
		set_transient( 'phantom_page_data', $cache_data, HOUR_IN_SECONDS );
		$data['cart'] = $this->get_cart_data();

		return new \WP_REST_Response( $data, 200 );
		} catch ( \Throwable $e ) {
			defined( 'WP_DEBUG' ) && WP_DEBUG && error_log( 'Phantom Core get_page_data error: ' . $e->getMessage() );
			return new \WP_REST_Response(
				array( 'code' => 'page_data_error', 'message' => __( 'An unexpected error occurred.', 'phantom-core' ), 'status' => 500 ),
				500
			);
		}
	}

	private function set_cache_headers( \WP_REST_Response $response, int $max_age = 3600 ): void {
		$etag = md5( serialize( $response->get_data() ) );
		$response->header( 'Cache-Control', 'public, max-age=' . $max_age . ', must-revalidate' );
		$response->header( 'ETag', '"' . $etag . '"' );
	}

	private function format_entry( string $key, array $entry, bool $fresh = false ): array {
		$registry = Settings_Registry::get_instance();
		if ( $fresh ) {
			$registry->flush_cache();
		}
		return array(
			'key'     => $key,
			'value'   => $registry->get( $key ),
			'default' => $entry['default'] ?? null,
			'type'    => $entry['type'] ?? 'string',
			'section' => $entry['section'] ?? '',
			'label'   => $entry['label'] ?? '',
		);
	}

	private function get_cart_data(): array {
		if ( ! class_exists( 'WooCommerce' ) || null === WC()->cart ) {
			return array( 'items' => array(), 'total' => '', 'totalItems' => 0, 'currency' => '' );
		}
		$cart     = WC()->cart;
		$items    = array();
		$currency = get_woocommerce_currency_symbol();
		foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
			$product  = $cart_item['data'];
			$items[]  = array(
				'key'     => $cart_item_key,
				'id'      => $product->get_id(),
				'name'    => $product->get_name(),
				'price'   => wc_price( $product->get_price() ),
				'qty'     => $cart_item['quantity'],
				'subtotal'=> wc_price( $cart_item['line_subtotal'] ),
				'total'   => wc_price( $cart_item['line_total'] ),
				'image'   => wp_get_attachment_image_url( $product->get_image_id(), 'thumbnail' ) ?: '',
				'url'     => $product->get_permalink(),
			);
		}
		return array(
			'items'          => $items,
			'subtotal'       => wc_price( $cart->get_subtotal() ),
			'total'          => wc_price( $cart->get_total() ),
			'shipping_total' => wc_price( (float) $cart->get_shipping_total() + (float) $cart->get_shipping_tax() ),
			'totalItems'     => $cart->get_cart_contents_count(),
			'currency'       => $currency,
		);
	}

	private function clean_entry( array $entry ): array {
		$allowed = array( 'section', 'type', 'default', 'label' );
		$clean   = array();
		foreach ( $allowed as $field ) {
			if ( array_key_exists( $field, $entry ) ) {
				$clean[ $field ] = $entry[ $field ];
			}
		}
		return $clean;
	}

	private function get_entry_or_error( string $key ): array|\WP_Error {
		$registry = Settings_Registry::get_instance();
		$entry    = $registry->get_schema( $key );
		if ( null === $entry ) {
			return new \WP_Error(
				'not_found',
				sprintf(
					/* translators: %s: setting key */
					__( 'Setting "%s" not found in registry.', 'phantom-core' ),
					$key
				),
				array( 'status' => 404 )
			);
		}
		return $entry;
	}

	private function calculate_reading_time( string $content ): string {
		$words      = str_word_count( wp_strip_all_tags( $content ) );
		$minutes    = (int) max( 1, ceil( $words / 200 ) );
		return sprintf(
			/* translators: %d: number of minutes */
			_n( '%d min read', '%d min read', $minutes, 'phantom-core' ),
			$minutes
		);
	}

	private function get_related_posts( int $post_id ): array {
		$categories = wp_get_post_categories( $post_id, array( 'fields' => 'ids' ) );
		if ( empty( $categories ) ) {
			return array();
		}

		$related = get_posts(
			array(
				'post_type'      => 'post',
				'posts_per_page' => 3,
				'post_status'    => 'publish',
				'post__not_in'   => array( $post_id ),
				'category__in'   => $categories,
				'orderby'        => 'rand',
			)
		);

		$items = array();
		foreach ( $related as $post ) {
			$items[] = array(
				'title'          => get_the_title( $post ),
				'slug'           => $post->post_name,
				'excerpt'        => get_the_excerpt( $post ) ?: wp_trim_words( $post->post_content, 20, '...' ),
				'featured_image' => get_the_post_thumbnail_url( $post->ID, 'medium' ) ?: '',
				'date'           => get_the_date( 'c', $post ),
				'url'            => get_permalink( $post ),
			);
		}

		return $items;
	}

	private function build_menu_tree( array $items ): array {
		$tree     = array();
		$sorted   = array();
		$home_url = home_url( '/' );

		foreach ( $items as $item ) {
			// Normalize Home menu URL (/?p=1 → /)
			$url = $item->url;
			if ( $url && ! empty( $url ) ) {
				$parsed = wp_parse_url( $url );
				$url_host   = $parsed['host'] ?? '';
				$url_path   = $parsed['path'] ?? '';
				$url_query  = $parsed['query'] ?? '';
				$home_host  = wp_parse_url( $home_url, PHP_URL_HOST );
				$home_path  = wp_parse_url( $home_url, PHP_URL_PATH );

				// Case 1: URL is already the home URL (possibly with trailing slash mismatch)
				if ( rtrim( $url, '?' ) === rtrim( $home_url, '/' ) || rtrim( $url, '?' ) . '/' === $home_url ) {
					$url = $home_url;
				}
				// Case 2: Query-based URL (/?p=X or /?page_id=X) pointing to front page
				elseif ( $url_query && $url_host === $home_host && ( $url_path === $home_path || $url_path === rtrim( $home_path, '/' ) ) ) {
					$post_id = 0;
					if ( preg_match( '/^p=(\d+)$/', $url_query, $m ) ) {
						$post_id = (int) $m[1];
					} elseif ( preg_match( '/^page_id=(\d+)$/', $url_query, $m ) ) {
						$post_id = (int) $m[1];
					}
					if ( $post_id ) {
						$front_page    = (int) get_option( 'page_on_front' );
						$posts_page    = (int) get_option( 'page_for_posts' );
						$show_on_front = get_option( 'show_on_front', 'posts' );
						// Normalize if: (a) static front page, (b) posts page, (c) first post on default setup
						if ( $post_id === $front_page || $post_id === $posts_page ) {
							$url = $home_url;
						} elseif ( 'posts' === $show_on_front && $post_id === 1 ) {
							$url = $home_url;
						}
					}
				}
				// Case 3: Clean permalink pointing to a post on this site when front page shows posts
				elseif ( $url_host === $home_host ) {
					$show_on_front = get_option( 'show_on_front', 'posts' );
					$resolved_id   = url_to_postid( $url );
					if ( 'posts' === $show_on_front && $resolved_id ) {
						$url = $home_url;
					} else {
						$front_page    = (int) get_option( 'page_on_front' );
						$posts_page    = (int) get_option( 'page_for_posts' );
						$candidate_ids = array_filter( array( $front_page, $posts_page ) );
						foreach ( $candidate_ids as $candidate_id ) {
							$candidate_url = untrailingslashit( get_permalink( $candidate_id ) );
							if ( $candidate_url && untrailingslashit( $url ) === $candidate_url ) {
								$url = $home_url;
								break;
							}
						}
					}
				}
			}

			$sorted[ $item->ID ] = array(
				'id'       => $item->ID,
				'title'    => $item->title,
				'url'      => $url,
				'parent'   => (int) $item->menu_item_parent,
				'classes'  => implode( ' ', array_filter( $item->classes ?? array() ) ),
				'target'   => $item->target,
				'children' => array(),
			);
		}

		foreach ( $sorted as $id => &$node ) {
			if ( $node['parent'] && isset( $sorted[ $node['parent'] ] ) ) {
				$sorted[ $node['parent'] ]['children'][] = &$node;
			} else {
				$tree[] = &$node;
			}
		}
		unset( $node );

		return $tree;
	}

	/**
	 * Auto-enrich menu tree with important page routes missing from the WordPress menu.
	 * Groups extra pages under a "Pages" dropdown parent.
	 */
	private function enrich_menu_tree( array $tree ): array {
		$existing_urls = array();
		foreach ( $tree as $node ) {
			$existing_urls[ $node['url'] ] = true;
		}
		$next_id = 999;
		$extra_children = array();
		$wc_active = class_exists( 'WooCommerce' );
		$page_routes = array(
			array( 'title' => __( 'FAQ', 'phantom-core' ),          'url' => home_url( '/faq/' ) ),
			array( 'title' => __( 'Team', 'phantom-core' ),         'url' => home_url( '/team/' ) ),
			array( 'title' => __( 'Testimonials', 'phantom-core' ), 'url' => home_url( '/testimonials/' ) ),
		);
		if ( $wc_active ) {
			$page_routes = array_merge(
				$page_routes,
				array(
					array( 'title' => __( 'Cart', 'phantom-core' ),      'url' => wc_get_cart_url() ),
					array( 'title' => __( 'Checkout', 'phantom-core' ),  'url' => wc_get_checkout_url() ),
					array( 'title' => __( 'My Account', 'phantom-core' ),'url' => wc_get_page_permalink( 'myaccount' ) ),
				)
			);
		}
		foreach ( $page_routes as $pr ) {
			if ( ! isset( $existing_urls[ $pr['url'] ] ) ) {
				$cid = $next_id++;
				$extra_children[] = array(
					'id'       => $cid,
					'title'    => $pr['title'],
					'url'      => $pr['url'],
					'parent'   => 0,
					'classes'  => '',
					'target'   => '',
					'children' => array(),
				);
			}
		}
		if ( ! empty( $extra_children ) ) {
			$pages_parent_id = $next_id++;
			$tree[] = array(
				'id'       => $pages_parent_id,
				'title'    => __( 'Pages', 'phantom-core' ),
				'url'      => '#',
				'parent'   => 0,
				'classes'  => '',
				'target'   => '',
				'children' => $extra_children,
			);
		}
		return $tree;
	}

	private function format_product( $product, bool $full = false ): array {
		$image_id = $product->get_image_id();
		$gallery  = array();
		if ( $full ) {
			$gallery_ids = $product->get_gallery_image_ids();
			foreach ( $gallery_ids as $gid ) {
				$gallery[] = wp_get_attachment_image_url( $gid, 'large' ) ?: '';
			}
		}

		$categories_raw = wp_get_post_terms( $product->get_id(), 'product_cat' );
		$categories     = array();
		if ( is_array( $categories_raw ) ) {
			foreach ( $categories_raw as $cat ) {
				$categories[] = array(
					'id'          => $cat->term_id,
					'name'        => $cat->name,
					'slug'        => $cat->slug,
					'description' => $cat->description,
					'image'       => function_exists( 'get_term_meta' ) ? wp_get_attachment_image_url( get_term_meta( $cat->term_id, 'thumbnail_id', true ), 'large' ) ?: '' : '',
				);
			}
		}

		$data = array(
			'id'             => $product->get_id(),
			'name'           => $product->get_name(),
			'slug'           => $product->get_slug(),
			'price'          => $product->get_price(),
			'price_html'     => $product->get_price_html(),
			'regular_price'  => $product->get_regular_price(),
			'sale_price'     => $product->get_sale_price(),
			'on_sale'        => $product->is_on_sale(),
			'is_featured'    => $product->is_featured(),
			'in_stock'       => $product->is_in_stock(),
			'stock_status'   => $product->get_stock_status(),
			'stock_quantity' => $product->get_stock_quantity(),
			'backorders'     => $product->get_backorders(),
			'rating'         => $product->get_average_rating(),
			'review_count'   => $product->get_review_count(),
			'image'          => wp_get_attachment_image_url( $image_id, 'large' ) ?: wc_placeholder_img_src(),
			'gallery'        => $gallery,
			'url'            => $product->get_permalink(),
			'type'           => $product->get_type(),
			'sku'            => $product->get_sku(),
			'categories'     => $categories,
		);

		if ( $full ) {
			$data['cross_sell_ids'] = $product->get_cross_sell_ids();
			$data['up_sell_ids']    = $product->get_upsell_ids();
		}

		if ( $full ) {
			$data['description']      = $product->get_description();
			$data['short_description'] = $product->get_short_description();
			$data['attributes']        = $product->get_attributes();
			$data['weight']            = $product->get_weight();
			$data['dimensions']        = wc_format_dimensions( $product->get_dimensions( false ) );
			$data['video_url']         = get_post_meta( $product->get_id(), '_product_video', true ) ?: '';
			$raw_360                   = get_post_meta( $product->get_id(), '_product_360_images', true );
			$data['images_360']        = ! empty( $raw_360 ) ? array_map( 'trim', explode( ',', $raw_360 ) ) : array();
		}

		if ( $full && $product->is_type( 'variable' ) ) {
			$variations = $product->get_available_variations();
			$var_data   = array();
			foreach ( $variations as $v ) {
				$var_data[] = array(
					'id'            => $v['variation_id'],
					'attributes'    => $v['attributes'],
					'price'         => $v['display_price'],
					'price_html'    => $v['price_html'],
					'regular_price' => $v['display_regular_price'],
					'sale_price'    => $v['display_price'] !== $v['display_regular_price'] ? $v['display_price'] : '',
					'sku'           => $v['sku'],
					'in_stock'      => $v['is_in_stock'],
					'image'         => $v['image']['url'] ?? '',
					'weight'        => $v['weight'],
					'dimensions'    => $v['dimensions'],
					'description'   => $v['variation_description'] ?? '',
				);
			}
			$data['variations'] = $var_data;

			$attrs       = $product->get_attributes();
			$attr_data   = array();
			foreach ( $attrs as $attr_name => $attr ) {
				if ( ! $attr->get_variation() ) {
					continue;
				}
				$terms  = wc_get_product_terms( $product->get_id(), $attr_name, array( 'fields' => 'all' ) );
				$opts   = array();
				foreach ( $terms as $term ) {
					$opts[] = array(
						'slug' => $term->slug,
						'name' => $term->name,
					);
				}
				$attr_data[] = array(
					'name'    => wc_attribute_label( $attr_name ),
					'taxonomy' => $attr_name,
					'options' => $opts,
				);
			}
			$data['variation_attributes'] = $attr_data;
		}

		return $data;
	}

	private function get_settings_args(): array {
		return array(
			'section' => array(
				'description'       => __( 'Filter by section name.', 'phantom-core' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			),
			'per_page' => array(
				'description'       => __( 'Number of items per page.', 'phantom-core' ),
				'type'              => 'integer',
				'minimum'           => 1,
				'maximum'           => 100,
				'default'           => 50,
				'sanitize_callback' => 'absint',
			),
			'page' => array(
				'description'       => __( 'Page number.', 'phantom-core' ),
				'type'              => 'integer',
				'minimum'           => 1,
				'default'           => 1,
				'sanitize_callback' => 'absint',
			),
		);
	}

	private function get_single_args(): array {
		return array(
			'key' => array(
				'description'       => __( 'Setting key.', 'phantom-core' ),
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_key',
			),
		);
	}

	private function get_single_update_args(): array {
		return array(
			'key' => array(
				'description'       => __( 'Setting key.', 'phantom-core' ),
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_key',
			),
			'value' => array(
				'description' => __( 'Setting value.', 'phantom-core' ),
				'required'    => true,
			),
		);
	}

	private function get_bulk_update_args(): array {
		return array(
			'settings' => array(
				'description' => __( 'Object of key-value pairs to update.', 'phantom-core' ),
				'type'        => 'object',
				'required'    => false,
			),
			'changes' => array(
				'description' => __( 'Array of {key, value} objects (backward compat for removed /settings/batch).', 'phantom-core' ),
				'type'        => 'array',
				'required'    => false,
				'items'       => array(
					'type'       => 'object',
					'properties' => array(
						'key'   => array( 'type' => 'string' ),
						'value' => array(),
					),
				),
			),
		);
	}

	private function get_import_args(): array {
		return array(
			'settings' => array(
				'description' => __( 'Object of key-value pairs to import.', 'phantom-core' ),
				'type'        => 'object',
				'required'    => true,
			),
		);
	}

	private function get_posts_args(): array {
		return array(
			'per_page' => array(
				'description'       => __( 'Number of items per page.', 'phantom-core' ),
				'type'              => 'integer',
				'minimum'           => 1,
				'maximum'           => 100,
				'sanitize_callback' => 'absint',
				'default'           => 10,
			),
			'page' => array(
				'description'       => __( 'Page number.', 'phantom-core' ),
				'type'              => 'integer',
				'minimum'           => 1,
				'sanitize_callback' => 'absint',
				'default'           => 1,
			),
			'category' => array(
				'description'       => __( 'Filter by category slug.', 'phantom-core' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			),
		);
	}

	private function get_products_args(): array {
		return array(
			'per_page' => array(
				'description'       => __( 'Number of products per page.', 'phantom-core' ),
				'type'              => 'integer',
				'minimum'           => 1,
				'maximum'           => 100,
				'sanitize_callback' => 'absint',
				'default'           => 12,
			),
			'page' => array(
				'description'       => __( 'Page number.', 'phantom-core' ),
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'default'           => 1,
			),
			'category' => array(
				'description'       => __( 'Filter by product category slug.', 'phantom-core' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			),
			'search' => array(
				'description'       => __( 'Search products by keyword.', 'phantom-core' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			),
			'orderby' => array(
				'description'       => __( 'Sort products by field.', 'phantom-core' ),
				'type'              => 'string',
				'enum'              => array( 'price', 'date', 'rating', 'popularity', 'title', 'rand' ),
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => 'date',
			),
			'order' => array(
				'description'       => __( 'Sort order.', 'phantom-core' ),
				'type'              => 'string',
				'enum'              => array( 'ASC', 'DESC' ),
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => 'DESC',
			),
			'min_price' => array(
				'description'       => __( 'Minimum price filter.', 'phantom-core' ),
				'type'              => 'number',
				'sanitize_callback' => 'floatval',
				'default'           => 0,
			),
			'max_price' => array(
				'description'       => __( 'Maximum price filter.', 'phantom-core' ),
				'type'              => 'number',
				'sanitize_callback' => 'floatval',
				'default'           => 0,
			),
			'on_sale' => array(
				'description'       => __( 'Filter products on sale.', 'phantom-core' ),
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
				'default'           => false,
			),
			'stock_status' => array(
				'description'       => __( 'Filter by stock status.', 'phantom-core' ),
				'type'              => 'string',
				'enum'              => array( 'instock', 'outofstock', 'onbackorder' ),
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			),
			'featured' => array(
				'description'       => __( 'Filter featured products only.', 'phantom-core' ),
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
				'default'           => false,
			),
			'tag' => array(
				'description'       => __( 'Filter by product tag slug.', 'phantom-core' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			),
		);
	}

	private function get_single_slug_args(): array {
		return array(
			'slug' => array(
				'description'       => __( 'Post or page slug.', 'phantom-core' ),
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_title',
			),
		);
	}

	private function get_menu_args(): array {
		return array(
			'location' => array(
				'description'       => __( 'Menu location slug.', 'phantom-core' ),
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
			),
		);
	}

	private function get_create_product_args(): array {
		return array(
			'name'             => array(
				'description'       => __( 'Product name.', 'phantom-core' ),
				'type'              => 'string',
				'required'          => false,
				'sanitize_callback' => 'sanitize_text_field',
			),
			'description'      => array(
				'description'       => __( 'Product description.', 'phantom-core' ),
				'type'              => 'string',
				'required'          => false,
				'sanitize_callback' => 'wp_kses_post',
			),
			'short_description' => array(
				'description'       => __( 'Product short description.', 'phantom-core' ),
				'type'              => 'string',
				'required'          => false,
				'sanitize_callback' => 'wp_kses_post',
			),
			'regular_price'    => array(
				'description'       => __( 'Product regular price.', 'phantom-core' ),
				'type'              => 'string',
				'required'          => false,
				'sanitize_callback' => 'wc_format_decimal',
			),
			'sale_price'       => array(
				'description'       => __( 'Product sale price.', 'phantom-core' ),
				'type'              => 'string',
				'required'          => false,
				'sanitize_callback' => 'wc_format_decimal',
			),
			'sku'              => array(
				'description'       => __( 'Product SKU.', 'phantom-core' ),
				'type'              => 'string',
				'required'          => false,
				'sanitize_callback' => 'sanitize_text_field',
			),
			'stock_quantity'   => array(
				'description'       => __( 'Product stock quantity.', 'phantom-core' ),
				'type'              => 'integer',
				'required'          => false,
				'sanitize_callback' => 'absint',
			),
			'is_featured'      => array(
				'description'       => __( 'Whether the product is featured.', 'phantom-core' ),
				'type'              => 'boolean',
				'required'          => false,
			),
			'type'             => array(
				'description'       => __( 'Product type (simple, variable, grouped, external).', 'phantom-core' ),
				'type'              => 'string',
				'required'          => false,
				'default'           => 'simple',
				'enum'              => array( 'simple', 'variable', 'grouped', 'external' ),
			),
			'video_url'        => array(
				'description'       => __( 'Product video URL (YouTube, Vimeo, or direct MP4).', 'phantom-core' ),
				'type'              => 'string',
				'required'          => false,
				'sanitize_callback' => 'esc_url_raw',
			),
			'images_360'       => array(
				'description'       => __( '360° spin image URLs.', 'phantom-core' ),
				'type'              => 'array',
				'required'          => false,
				'items'             => array( 'type' => 'string' ),
			),
			'categories'       => array(
				'description'       => __( 'Product category IDs.', 'phantom-core' ),
				'type'              => 'array',
				'required'          => false,
				'items'             => array( 'type' => 'integer' ),
			),
		);
	}

	private function get_update_product_args(): array {
		return array(
			'name'              => array(
				'description'       => __( 'Product name.', 'phantom-core' ),
				'type'              => 'string',
				'required'          => false,
				'sanitize_callback' => 'sanitize_text_field',
			),
			'description'       => array(
				'description'       => __( 'Product description.', 'phantom-core' ),
				'type'              => 'string',
				'required'          => false,
				'sanitize_callback' => 'wp_kses_post',
			),
			'short_description' => array(
				'description'       => __( 'Product short description.', 'phantom-core' ),
				'type'              => 'string',
				'required'          => false,
				'sanitize_callback' => 'wp_kses_post',
			),
			'regular_price'     => array(
				'description'       => __( 'Product regular price.', 'phantom-core' ),
				'type'              => 'string',
				'required'          => false,
				'sanitize_callback' => 'wc_format_decimal',
			),
			'sale_price'        => array(
				'description'       => __( 'Product sale price.', 'phantom-core' ),
				'type'              => 'string',
				'required'          => false,
				'sanitize_callback' => 'wc_format_decimal',
			),
			'sku'               => array(
				'description'       => __( 'Product SKU.', 'phantom-core' ),
				'type'              => 'string',
				'required'          => false,
				'sanitize_callback' => 'sanitize_text_field',
			),
			'stock_quantity'    => array(
				'description'       => __( 'Product stock quantity.', 'phantom-core' ),
				'type'              => 'integer',
				'required'          => false,
				'sanitize_callback' => 'absint',
			),
			'is_featured'       => array(
				'description'       => __( 'Whether the product is featured.', 'phantom-core' ),
				'type'              => 'boolean',
				'required'          => false,
			),
			'video_url'         => array(
				'description'       => __( 'Product video URL (YouTube, Vimeo, or direct MP4).', 'phantom-core' ),
				'type'              => 'string',
				'required'          => false,
				'sanitize_callback' => 'esc_url_raw',
			),
			'images_360'        => array(
				'description'       => __( '360° spin image URLs.', 'phantom-core' ),
				'type'              => 'array',
				'required'          => false,
				'items'             => array( 'type' => 'string' ),
			),
			'categories'        => array(
				'description'       => __( 'Product category IDs.', 'phantom-core' ),
				'type'              => 'array',
				'required'          => false,
				'items'             => array( 'type' => 'integer' ),
			),
		);
	}

	private function get_product_args(): array {
		return array(
			'id' => array(
				'description'       => __( 'Product ID.', 'phantom-core' ),
				'type'              => 'integer',
				'required'          => true,
				'sanitize_callback' => 'absint',
			),
		);
	}

	public function get_woo_attributes(): \WP_REST_Response {
		if ( ! function_exists( 'wc_get_attribute_taxonomies' ) ) {
			return $this->wp_error( 'woocommerce_inactive', __( 'WooCommerce is not active.', 'phantom-core' ), 400 );
		}

		$attributes = wc_get_attribute_taxonomies();
		$data       = array();

		foreach ( $attributes as $attribute ) {
			$data[] = array(
				'id'           => $attribute->attribute_id,
				'name'         => $attribute->attribute_label,
				'slug'         => $attribute->attribute_name,
				'type'         => $attribute->attribute_type,
				'order_by'     => $attribute->attribute_orderby,
				'has_archives' => (bool) $attribute->attribute_public,
			);
		}

		return new \WP_REST_Response( $data, 200 );
	}

	public function get_woo_variations( \WP_REST_Request $request ): \WP_REST_Response {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return $this->wp_error( 'woocommerce_inactive', __( 'WooCommerce is not active.', 'phantom-core' ), 400 );
		}

		$product_id = absint( $request->get_param( 'product_id' ) );
		if ( ! $product_id ) {
			return $this->wp_error( 'missing_product_id', __( 'Product ID is required.', 'phantom-core' ), 400 );
		}

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return $this->wp_error( 'not_found', __( 'Product not found.', 'phantom-core' ), 404 );
		}

		if ( ! $product->is_type( 'variable' ) ) {
			return new \WP_REST_Response( array(), 200 );
		}

		$variations = $product->get_available_variations();
		$data       = array();

		foreach ( $variations as $variation ) {
			$data[] = array(
				'id'                => $variation['variation_id'],
				'attributes'        => $variation['attributes'],
				'price'             => $variation['display_price'],
				'price_html'        => $variation['price_html'],
				'regular_price'     => $variation['display_regular_price'],
				'sale_price'        => $variation['display_price'] !== $variation['display_regular_price'] ? $variation['display_price'] : '',
				'sku'               => $variation['sku'],
				'in_stock'          => $variation['is_in_stock'],
				'image'             => $variation['image']['url'] ?? '',
				'weight'            => $variation['weight'],
				'dimensions'        => $variation['dimensions'],
				'description'       => $variation['variation_description'],
			);
		}

		return new \WP_REST_Response( $data, 200 );
	}

	public function get_woo_reviews( \WP_REST_Request $request ): \WP_REST_Response {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return $this->wp_error( 'woocommerce_inactive', __( 'WooCommerce is not active.', 'phantom-core' ), 400 );
		}

		$product_id = absint( $request->get_param( 'product_id' ) );
		if ( ! $product_id ) {
			return $this->wp_error( 'missing_product_id', __( 'A valid product_id is required.', 'phantom-core' ), 400 );
		}

		$per_page = max( 1, min( 100, absint( $request->get_param( 'per_page' ) ?: 10 ) ) );
		$page     = max( 1, absint( $request->get_param( 'page' ) ?: 1 ) );

		$comment_query = new \WP_Comment_Query(
			array(
				'type'      => 'review',
				'status'    => 'approve',
				'post_id'   => $product_id,
				'number'    => $per_page,
				'offset'    => ( $page - 1 ) * $per_page,
			)
		);
		$comments = $comment_query->comments;
		$data     = array();
		$total    = (int) ( wp_count_comments( $product_id )->approved ?? 0 );

		foreach ( $comments as $comment ) {
			$review = array(
				'id'          => $comment->comment_ID,
				'product_id'  => $comment->comment_post_ID,
				'author'      => $comment->comment_author,
				'rating'      => get_comment_meta( $comment->comment_ID, 'rating', true ) ?: 0,
				'title'       => get_comment_meta( $comment->comment_ID, 'title', true ) ?: '',
				'content'     => $comment->comment_content,
				'date'        => $comment->comment_date,
			);
			if ( function_exists( 'wc_review_is_from_verified_owner' ) ) {
				$review['verified'] = wc_review_is_from_verified_owner( $comment->comment_ID );
			}
			$data[] = $review;
		}

		$response = new \WP_REST_Response( $data, 200 );
		$response->header( 'X-WP-Total', (string) $total );
		$response->header( 'X-WP-TotalPages', (string) (int) ceil( $total / $per_page ) );
		$this->set_cache_headers( $response, 600 );
		return $response;
	}

	private function get_woo_variations_args(): array {
		return array(
			'product_id' => array(
				'description'       => __( 'Product ID to get variations for.', 'phantom-core' ),
				'type'              => 'integer',
				'required'          => true,
				'sanitize_callback' => 'absint',
			),
		);
	}

	private function get_woo_reviews_args(): array {
		return array(
			'product_id' => array(
				'description'       => __( 'Filter by product ID.', 'phantom-core' ),
				'type'              => 'integer',
				'required'          => false,
				'sanitize_callback' => 'absint',
			),
			'per_page' => array(
				'description'       => __( 'Number of reviews per page.', 'phantom-core' ),
				'type'              => 'integer',
				'minimum'           => 1,
				'maximum'           => 100,
				'default'           => 10,
				'sanitize_callback' => 'absint',
			),
			'page' => array(
				'description'       => __( 'Page number.', 'phantom-core' ),
				'type'              => 'integer',
				'minimum'           => 1,
				'default'           => 1,
				'sanitize_callback' => 'absint',
			),
		);
	}

	public function auth_login( \WP_REST_Request $request ): \WP_REST_Response {
		$rate_error = $this->check_rate_limit( 'login' );
		if ( $rate_error ) {
			return $this->wp_error( 'rate_limit_exceeded', __( 'Too many login attempts. Please try again later.', 'phantom-core' ), 429 );
		}

		$email    = sanitize_email( $request->get_param( 'email' ) );
		$password = $request->get_param( 'password' );

		if ( empty( $email ) || empty( $password ) ) {
			return $this->wp_error( 'missing_fields', __( 'Email and password are required.', 'phantom-core' ), 400 );
		}

		$user = wp_authenticate( $email, $password );
		if ( is_wp_error( $user ) ) {
			return $this->wp_error( 'auth_failed', __( 'Invalid email or password.', 'phantom-core' ), 401 );
		}

		wp_set_current_user( $user->ID );
		wp_set_auth_cookie( $user->ID );

		return new \WP_REST_Response(
			array(
				'success'     => true,
				'user_id'     => $user->ID,
				'user_name'   => $user->display_name,
				'user_email'  => $user->user_email,
				'api_nonce'   => wp_create_nonce( 'phantom_api' ),
			),
			200
		);
	}

	public function auth_register( \WP_REST_Request $request ): \WP_REST_Response {
		$rate_error = $this->check_rate_limit( 'register' );
		if ( $rate_error ) {
			return $this->wp_error( 'rate_limit_exceeded', __( 'Too many registration attempts. Please try again later.', 'phantom-core' ), 429 );
		}

		$name     = sanitize_text_field( $request->get_param( 'name' ) );
		$email    = sanitize_email( $request->get_param( 'email' ) );
		$password = $request->get_param( 'password' );

		if ( empty( $name ) || empty( $email ) || empty( $password ) ) {
			return $this->wp_error( 'missing_fields', __( 'Name, email, and password are required.', 'phantom-core' ), 400 );
		}

		if ( ! is_email( $email ) ) {
			return $this->wp_error( 'invalid_email', __( 'Please provide a valid email address.', 'phantom-core' ), 400 );
		}

		if ( email_exists( $email ) ) {
			return $this->wp_error( 'email_exists', __( 'An account with this email already exists.', 'phantom-core' ), 409 );
		}

		if ( strlen( $password ) < 6 ) {
			return $this->wp_error( 'weak_password', __( 'Password must be at least 6 characters.', 'phantom-core' ), 400 );
		}

		$user_id = wp_insert_user(
			array(
				'user_login' => $email,
				'user_email' => $email,
				'display_name' => $name,
				'user_pass'  => $password,
			)
		);

		if ( is_wp_error( $user_id ) ) {
			return $this->wp_error( 'registration_failed', __( 'Could not create account. Please try again.', 'phantom-core' ), 500 );
		}

		wp_set_current_user( $user_id );
		wp_set_auth_cookie( $user_id );

		return new \WP_REST_Response(
			array(
				'success'     => true,
				'user_id'     => $user_id,
				'user_name'   => $name,
				'user_email'  => $email,
				'api_nonce'   => wp_create_nonce( 'phantom_api' ),
			),
			201
		);
	}

	public function auth_password_reset( \WP_REST_Request $request ): \WP_REST_Response {
		$rate_error = $this->check_rate_limit( 'password_reset' );
		if ( $rate_error ) {
			return $this->wp_error( 'rate_limit_exceeded', __( 'Too many reset attempts. Please try again later.', 'phantom-core' ), 429 );
		}

		$email = sanitize_email( $request->get_param( 'email' ) );

		if ( empty( $email ) || ! is_email( $email ) ) {
			return $this->wp_error( 'invalid_email', __( 'Please provide a valid email address.', 'phantom-core' ), 400 );
		}

		$user = get_user_by( 'email', $email );
		if ( ! $user ) {
			return new \WP_REST_Response(
				array(
					'success' => true,
					'message' => __( 'If an account exists, a password reset link has been sent.', 'phantom-core' ),
				),
				200
			);
		}

		$key = get_password_reset_key( $user );
		if ( is_wp_error( $key ) ) {
			return $this->wp_error( 'reset_key_failed', __( 'Could not generate reset key.', 'phantom-core' ), 500 );
		}

		$reset_url = network_site_url( "wp-login.php?action=rp&key=$key&login=" . rawurlencode( $user->user_login ) );
		$subject   = __( 'Password Reset Request', 'phantom-core' );
		$message   = sprintf( __( 'Click the following link to reset your password: %s', 'phantom-core' ), $reset_url );
		wp_mail( $email, $subject, $message );

		return new \WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'If an account exists, a password reset link has been sent.', 'phantom-core' ),
			),
			200
		);
	}

	public function auth_logout(): \WP_REST_Response {
		wp_logout();
		return new \WP_REST_Response(
			array(
				'success'   => true,
				'message'   => __( 'Logged out successfully.', 'phantom-core' ),
				'api_nonce' => wp_create_nonce( 'phantom_api' ),
			),
			200
		);
	}

	public function handle_contact( \WP_REST_Request $request ): \WP_REST_Response {
		$fname   = sanitize_text_field( $request->get_param( 'fname' ) );
		$email   = sanitize_email( $request->get_param( 'email' ) );
		$phone   = sanitize_text_field( $request->get_param( 'phone' ) );
		$message = sanitize_textarea_field( $request->get_param( 'message' ) );

		if ( empty( $fname ) || empty( $email ) ) {
			return $this->wp_error( 'missing_fields', __( 'Name and email are required.', 'phantom-core' ), 400 );
		}

		$admin_email = get_option( 'admin_email' );
		$subject     = sprintf( __( '[Phantom Core] Contact form submission from %s', 'phantom-core' ), $fname );
		$body        = sprintf(
			"Name: %s\nEmail: %s\nPhone: %s\n\nMessage:\n%s",
			$fname,
			$email,
			$phone ?: '—',
			$message ?: '—'
		);
		$headers     = array(
			'Content-Type: text/plain; charset=UTF-8',
			'Reply-To: ' . $email,
		);

		$sent = wp_mail( $admin_email, $subject, $body, $headers );

		if ( ! $sent ) {
			return $this->wp_error( 'mail_failed', __( 'Could not send message. Please try again.', 'phantom-core' ), 500 );
		}

		return new \WP_REST_Response(
			array(
				'status'  => 'Success',
				'msg'     => __( 'Your message has been sent. Thank you!', 'phantom-core' ),
			),
			200
		);
	}

	public function get_user_orders( \WP_REST_Request $request ): \WP_REST_Response {
		if ( ! is_user_logged_in() ) {
			return new \WP_REST_Response( array( 'success' => false, 'message' => __( 'Not authenticated.', 'phantom-core' ) ), 401 );
		}
		$orders = array();
		if ( class_exists( 'WooCommerce' ) ) {
			$user_id = get_current_user_id();
			$limit   = $request->get_param( 'limit' );
			$status  = $request->get_param( 'status' );
			$args    = array(
				'customer' => $user_id,
				'limit'    => $limit,
				'return'   => 'objects',
			);
			if ( 'any' !== $status ) {
				$args['status'] = $status;
			}
			$wc_orders = wc_get_orders( $args );
			foreach ( $wc_orders as $order ) {
				$orders[] = array(
					'id'        => $order->get_id(),
					'number'    => $order->get_order_number(),
					'date'      => $order->get_date_created() ? $order->get_date_created()->date_i18n( 'F j, Y' ) : '',
					'status'    => wc_get_order_status_name( $order->get_status() ),
					'total'     => $order->get_formatted_order_total(),
					'items'     => count( $order->get_items() ),
					'payment'   => $order->get_payment_method_title(),
				);
			}
		}
		return new \WP_REST_Response(
			array( 'success' => true, 'orders' => $orders ),
			200
		);
	}

	private function get_auth_login_args(): array {
		return array(
			'email' => array(
				'description'       => __( 'User email address.', 'phantom-core' ),
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_email',
			),
			'password' => array(
				'description'       => __( 'User password.', 'phantom-core' ),
				'type'              => 'string',
				'required'          => true,
			),
		);
	}

	private function get_auth_register_args(): array {
		return array(
			'name' => array(
				'description'       => __( 'Display name.', 'phantom-core' ),
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
			),
			'email' => array(
				'description'       => __( 'User email address.', 'phantom-core' ),
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_email',
			),
			'password' => array(
				'description'       => __( 'Password (min 6 characters).', 'phantom-core' ),
				'type'              => 'string',
				'required'          => true,
			),
		);
	}

	private function get_auth_password_reset_args(): array {
		return array(
			'email' => array(
				'description'       => __( 'User email address.', 'phantom-core' ),
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_email',
			),
		);
	}
}
