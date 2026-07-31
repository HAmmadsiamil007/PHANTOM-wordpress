<?php
declare(strict_types=1);

namespace PhantomCore\Packs;

defined('ABSPATH') || exit;

class Pack_Rest {
    private const NAMESPACE = 'phantom/v1';

    private static ?self $instance = null;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init(): void {
        add_action('rest_api_init', [$this, 'register_routes'], 15);
    }

    public static function get_route_specs(): array {
        $slug_args = [
            'required' => true,
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ];
        return [
            [
                'route' => '/packs',
                'method' => 'GET',
                'callback' => 'get_packs',
                'permission' => 'public_read',
                'args' => [],
            ],
            [
                'route' => '/packs/activate',
                'method' => 'POST',
                'callback' => 'activate',
                'permission' => 'edit_theme_options_cap',
                'args' => ['slug' => $slug_args],
            ],
            [
                'route' => '/packs/install',
                'method' => 'POST',
                'callback' => 'install',
                'permission' => 'manage_options_cap',
                'args' => [],
            ],
            [
                'route' => '/packs/uninstall',
                'method' => 'POST',
                'callback' => 'uninstall',
                'permission' => 'manage_options_cap',
                'args' => [
                    'slug' => $slug_args,
                    'force' => [
                        'type' => 'boolean',
                        'default' => false,
                    ],
                ],
            ],
        ];
    }

    public function register_routes(): void {
        $permissions = [
            'public_read' => '__return_true',
            'edit_theme_options_cap' => fn() => current_user_can('edit_theme_options'),
            'manage_options_cap' => fn() => current_user_can('manage_options'),
        ];

        foreach (self::get_route_specs() as $spec) {
            $route = $spec['route'];
            $method = strtoupper($spec['method']);
            if ($this->wp_route_exists($route, $method)) {
                continue;
            }
            register_rest_route(
                self::NAMESPACE,
                $route,
                [
                    'methods' => $method,
                    'callback' => [$this, $spec['callback']],
                    'permission_callback' => $permissions[$spec['permission']] ?? '__return_true',
                    'args' => $spec['args'],
                ]
            );
        }
    }

    public function get_packs(): \WP_REST_Response {
        $registry = Frontend_Pack_Registry::get_instance();
        $registry->scan();
        return new \WP_REST_Response(
            [
                'success' => true,
                'packs' => array_values($registry->get_pack_list()),
                'active' => $registry->get_active_slug(),
            ],
            200
        );
    }

    public function activate(\WP_REST_Request $request) {
        $slug = (string) $request->get_param('slug');
        $result = Frontend_Pack_Registry::get_instance()->activate($slug);
        if (is_wp_error($result)) {
            return $result;
        }
        return new \WP_REST_Response(
            [
                'success' => true,
                'pack' => $slug,
                'applied' => Frontend_Pack_Registry::get_instance()->apply_pack_settings($slug),
            ],
            200
        );
    }

    public function install(\WP_REST_Request $request) {
        $file = $_FILES['file'] ?? [];
        $result = Frontend_Pack_Registry::get_instance()->install_from_upload(is_array($file) ? $file : []);
        if (is_wp_error($result)) {
            return $result;
        }
        return new \WP_REST_Response(
            [
                'success' => true,
                'pack' => $result->to_array(),
            ],
            201
        );
    }

    public function uninstall(\WP_REST_Request $request) {
        $slug = (string) $request->get_param('slug');
        $force = (bool) $request->get_param('force');
        $result = Frontend_Pack_Registry::get_instance()->uninstall($slug, $force);
        if (is_wp_error($result)) {
            return $result;
        }
        return new \WP_REST_Response(
            [
                'success' => true,
                'pack' => $slug,
                'removed' => true,
            ],
            200
        );
    }

    private function wp_route_exists(string $route, string $method): bool {
        if (function_exists('rest_get_server')) {
            try {
                $server = rest_get_server();
            } catch (\Throwable $e) {
                $server = null;
            }
            if (null !== $server) {
                $routes = $server->get_routes();
                $full = '/' . self::NAMESPACE . '/' . ltrim($route, '/');
                foreach ($routes as $pattern => $handlers) {
                    if ($pattern !== $full) {
                        continue;
                    }
                    foreach ($handlers as $handler) {
                        $methods = $handler['methods'] ?? [];
                        if (is_string($methods)) {
                            if (strtoupper($methods) === $method) {
                                return true;
                            }
                            continue;
                        }
                        if (is_array($methods) && isset($methods[$method])) {
                            return true;
                        }
                    }
                }
            }
        }

        if (isset($GLOBALS['_phantom_rest_routes']) && is_array($GLOBALS['_phantom_rest_routes'])) {
            foreach ($GLOBALS['_phantom_rest_routes'] as $entry) {
                if (($entry['route'] ?? '') !== $route) {
                    continue;
                }
                $entry_methods = $entry['args']['methods'] ?? '';
                if (strtoupper((string) $entry_methods) === strtoupper($method)) {
                    return true;
                }
            }
        }
        return false;
    }
}
