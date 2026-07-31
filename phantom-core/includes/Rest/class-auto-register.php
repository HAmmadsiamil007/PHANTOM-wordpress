<?php
declare(strict_types=1);

namespace PhantomCore\Rest;

use PhantomCore\Components\Component_Registry;
use PhantomCore\Components\ComponentInstance;
use PhantomCore\Components\Property_Registry;
use PhantomCore\Components\Media_Asset_Registry;
use PhantomCore\Animation\Animation_Registry;
use PhantomCore\History\History_Manager;
use PhantomCore\Engine\Template_Loader;
use PhantomCore\Public\Design_API;
use PhantomCore\Component\Component_Tree;
use PhantomCore\Inspector\Inspector_Factory;
use PhantomCore\Lock\Lock_Manager;
use PhantomCore\Favorites\Favorites_Manager;
use PhantomCore\Search\Search_Service;

defined('ABSPATH') || exit;

class Auto_Register {
    private static ?self $instance = null;

    private array $registry_routes = [];

    private string $namespace = 'phantom/v1';

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init(): void {
        add_action('rest_api_init', [$this, 'register_all'], 20);
    }

    public function register_all(): void {
        $this->register_component_routes();
        $this->register_token_routes();
        $this->register_property_routes();
        $this->register_asset_routes();
        $this->register_animation_routes();
        $this->register_instance_routes();
        $this->register_instance_tree_routes();
        $this->register_lock_routes();
        $this->register_favorites_routes();
        $this->register_search_routes();
        $this->register_history_routes();
        $this->register_build_routes();
        $this->register_pack_routes();
        $this->register_diagnostics_routes();
    }

    public function get_routes(): array {
        return array_keys($this->registry_routes);
    }

    public function route_exists(string $route, string $method): bool {
        $key = $route . '|' . strtoupper($method);
        return isset($this->registry_routes[$key]);
    }

    private function register_route(string $route, string $method, callable $callback, callable $permission_callback, array $args = []): void {
        $method = strtoupper($method);
        $key = $route . '|' . $method;

        if (isset($this->registry_routes[$key])) {
            return;
        }

        if ($this->wp_route_exists($route, $method)) {
            return;
        }

        $this->registry_routes[$key] = true;

        register_rest_route(
            $this->namespace,
            $route,
            array(
                'methods'             => $method,
                'callback'            => $callback,
                'permission_callback' => $permission_callback,
                'args'                => $args,
            )
        );
    }

    private function wp_route_exists(string $route, string $method): bool {
        $server = rest_get_server();
        $routes = $server->get_routes();
        $full_route = '/' . $this->namespace . '/' . ltrim($route, '/');

        foreach ($routes as $pattern => $handlers) {
            if ($pattern !== $full_route) {
                continue;
            }
            foreach ($handlers as $handler) {
                $handler_methods = $handler['methods'] ?? array();
                if (is_array($handler_methods) && isset($handler_methods[$method])) {
                    return true;
                }
                if (is_string($handler_methods) && $handler_methods === $method) {
                    return true;
                }
            }
        }

        return false;
    }

    private function register_component_routes(): void {
        $this->register_route(
            '/components',
            'GET',
            function () {
                $registry = Component_Registry::get_instance();
                $components = $registry->get_all();
                $data = array();
                foreach ($components as $name => $component) {
                    $data[] = $component->to_array();
                }
                return new \WP_REST_Response($data, 200);
            },
            '__return_true'
        );

        $this->register_route(
            '/components/(?P<name>[\w-]+)',
            'GET',
            function (\WP_REST_Request $request) {
                $name = $request->get_param('name');
                $component = Component_Registry::get_instance()->get($name);
                if (!$component) {
                    return new \WP_Error('not_found', 'Component not found.', array('status' => 404));
                }
                return new \WP_REST_Response($component->to_array(), 200);
            },
            '__return_true',
            array(
                'name' => array(
                    'description'       => 'Component name.',
                    'type'              => 'string',
                    'required'          => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            )
        );

        $this->register_route(
            '/components/(?P<name>[\w-]+)/inspector',
            'GET',
            function (\WP_REST_Request $request) {
                $name     = $request->get_param('name');
                $state    = $request->get_param('state') ?: 'normal';
                $viewport = $request->get_param('viewport') ?: 'desktop';
                $instance_id = $request->get_param('instance');

                $editable = array();
                $raw = $request->get_param('editable');
                if (is_string($raw) && '' !== $raw) {
                    $decoded = json_decode($raw, true);
                    if (is_array($decoded)) {
                        $editable = array_filter(array_map('sanitize_key', array_map('strval', $decoded)));
                    }
                }

                $instance = null;
                if (!empty($instance_id)) {
                    $instance = ComponentInstance::get($instance_id);
                }

                $html = Inspector_Factory::get_instance()->render_panels($name, $instance, $state, $viewport, $editable);

                return new \WP_REST_Response(
                    array(
                        'success' => true,
                        'data'    => array('panels' => $html),
                    ),
                    200
                );
            },
            function () {
                return current_user_can('edit_theme_options');
            },
            array(
                'name' => array(
                    'description'       => 'Component name.',
                    'type'              => 'string',
                    'required'          => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'state' => array(
                    'type'              => 'string',
                    'default'           => 'normal',
                ),
                'viewport' => array(
                    'type'              => 'string',
                    'default'           => 'desktop',
                ),
                'instance' => array(
                    'type'              => 'string',
                ),
            )
        );
    }

    private function register_token_routes(): void {
        $this->register_route(
            '/tokens',
            'GET',
            function () {
                if (!class_exists(Design_API::class)) {
                    return new \WP_REST_Response(array('tokens' => array()), 200);
                }
                $api = Design_API::get_instance();
                $tokens = $api->get_tokens();
                return new \WP_REST_Response(
                    array(
                        'success' => true,
                        'tokens'  => is_array($tokens) ? $tokens : array(),
                    ),
                    200
                );
            },
            '__return_true'
        );

        $this->register_route(
            '/tokens/(?P<name>[\w.]+)',
            'GET',
            function (\WP_REST_Request $request) {
                $name = $request->get_param('name');
                if (!class_exists(Design_API::class)) {
                    return new \WP_Error('not_found', 'Token not found.', array('status' => 404));
                }
                $api = Design_API::get_instance();
                $value = $api->get_token($name);
                if (null === $value) {
                    return new \WP_Error('not_found', 'Token not found.', array('status' => 404));
                }
                return new \WP_REST_Response(
                    array(
                        'success' => true,
                        'name'    => $name,
                        'value'   => $value,
                    ),
                    200
                );
            },
            '__return_true',
            array(
                'name' => array(
                    'description'       => 'Token name (dot notation).',
                    'type'              => 'string',
                    'required'          => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            )
        );
    }

    private function register_property_routes(): void {
        $this->register_route(
            '/properties',
            'GET',
            function () {
                $registry = Property_Registry::get_instance();
                $properties = $registry->get_all();
                $data = array();
                foreach ($properties as $name => $property) {
                    $data[] = $property->to_array();
                }
                return new \WP_REST_Response($data, 200);
            },
            '__return_true'
        );

        $this->register_route(
            '/properties/(?P<name>[\w.]+)',
            'GET',
            function (\WP_REST_Request $request) {
                $name = $request->get_param('name');
                $property = Property_Registry::get_instance()->get($name);
                if (!$property) {
                    return new \WP_Error('not_found', 'Property not found.', array('status' => 404));
                }
                return new \WP_REST_Response($property->to_array(), 200);
            },
            '__return_true',
            array(
                'name' => array(
                    'description'       => 'Property name (dot notation).',
                    'type'              => 'string',
                    'required'          => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            )
        );
    }

    private function register_asset_routes(): void {
        $this->register_route(
            '/assets',
            'GET',
            function () {
                $registry = Media_Asset_Registry::get_instance();
                $assets = $registry->get_all();
                $data = array();
                foreach ($assets as $key => $asset) {
                    $data[] = $asset->to_array();
                }
                return new \WP_REST_Response($data, 200);
            },
            '__return_true'
        );

        $this->register_route(
            '/assets/(?P<key>[\w-]+)',
            'GET',
            function (\WP_REST_Request $request) {
                $key = $request->get_param('key');
                $asset = Media_Asset_Registry::get_instance()->get($key);
                if (!$asset) {
                    return new \WP_Error('not_found', 'Asset not found.', array('status' => 404));
                }
                return new \WP_REST_Response($asset->to_array(), 200);
            },
            '__return_true',
            array(
                'key' => array(
                    'description'       => 'Asset key.',
                    'type'              => 'string',
                    'required'          => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            )
        );
    }

    private function register_animation_routes(): void {
        $this->register_route(
            '/animations',
            'GET',
            function () {
                $registry = Animation_Registry::get_instance();
                $animations = $registry->get_all();
                $data = array();
                foreach ($animations as $id => $animation) {
                    $data[] = $animation->to_array();
                }
                return new \WP_REST_Response($data, 200);
            },
            '__return_true'
        );

        $this->register_route(
            '/animations/(?P<name>[\w-]+)',
            'GET',
            function (\WP_REST_Request $request) {
                $id = $request->get_param('name');
                $animation = Animation_Registry::get_instance()->get($id);
                if (!$animation) {
                    return new \WP_Error('not_found', 'Animation not found.', array('status' => 404));
                }
                return new \WP_REST_Response($animation->to_array(), 200);
            },
            '__return_true',
            array(
                'name' => array(
                    'description'       => 'Animation ID.',
                    'type'              => 'string',
                    'required'          => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            )
        );
    }

    private function register_instance_routes(): void {
        $this->register_route(
            '/instances',
            'GET',
            function () {
                $instances = ComponentInstance::load_all();
                $data = array();
                foreach ($instances as $id => $instance) {
                    $data[] = $instance->to_array();
                }
                return new \WP_REST_Response($data, 200);
            },
            function () {
                return current_user_can('edit_theme_options');
            }
        );

        $this->register_route(
            '/instances',
            'POST',
            function (\WP_REST_Request $request) {
                $component_name = $request->get_param('component');
                $id = $request->get_param('id') ?: uniqid('inst_', true);
                $overrides = $request->get_param('overrides') ?: array();
                $content = $request->get_param('content') ?: array();
                $assets = $request->get_param('assets') ?: array();
                $source = $request->get_param('source') ?: 'theme';
                $parent = $request->get_param('parent');

                $instance = new ComponentInstance(
                    id: $id,
                    component_name: $component_name,
                    overrides: $overrides,
                    content: $content,
                    assets: $assets,
                    source: $source,
                    parent: $parent
                );
                $instance->save();

                return new \WP_REST_Response($instance->to_array(), 201);
            },
            function () {
                return current_user_can('edit_theme_options');
            },
            array(
                'component' => array(
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'id' => array(
                    'required'          => false,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'overrides' => array(
                    'required'          => false,
                    'type'              => 'object',
                    'default'           => array(),
                ),
                'content' => array(
                    'required'          => false,
                    'type'              => 'object',
                    'default'           => array(),
                ),
                'assets' => array(
                    'required'          => false,
                    'type'              => 'object',
                    'default'           => array(),
                ),
                'source' => array(
                    'required'          => false,
                    'type'              => 'string',
                    'default'           => 'theme',
                ),
                'parent' => array(
                    'required'          => false,
                    'type'              => 'string',
                ),
            )
        );

        $this->register_route(
            '/instances/(?P<id>[\w-]+)',
            'PUT',
            function (\WP_REST_Request $request) {
                $id = $request->get_param('id');
                $instance = ComponentInstance::get($id);
                if (!$instance) {
                    return new \WP_Error('not_found', 'Instance not found.', array('status' => 404));
                }

                if ($request->has_param('overrides')) {
                    $instance->overrides = array_merge($instance->overrides, $request->get_param('overrides'));
                }
                if ($request->has_param('content')) {
                    $instance->content = array_merge($instance->content, $request->get_param('content'));
                }
                if ($request->has_param('state') && $request->has_param('value')) {
                    $instance->set_value($request->get_param('value'), $request->get_param('state'));
                }
                if ($request->has_param('viewport') && $request->has_param('value')) {
                    $instance->set_viewport_value($request->get_param('value'), $request->get_param('viewport'));
                }

                $instance->save();

                return new \WP_REST_Response($instance->to_array(), 200);
            },
            function () {
                return current_user_can('edit_theme_options');
            },
            array(
                'id' => array(
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'overrides' => array(
                    'type'              => 'object',
                ),
                'content' => array(
                    'type'              => 'object',
                ),
                'state' => array(
                    'type'              => 'string',
                ),
                'viewport' => array(
                    'type'              => 'string',
                ),
                'value' => array(
                    'type'              => 'string',
                ),
            )
        );

        $this->register_route(
            '/instances/(?P<id>[\w-]+)',
            'DELETE',
            function (\WP_REST_Request $request) {
                $id = $request->get_param('id');
                $deleted = ComponentInstance::delete($id);
                if (!$deleted) {
                    return new \WP_Error('not_found', 'Instance not found.', array('status' => 404));
                }
                return new \WP_REST_Response(array('deleted' => true, 'id' => $id), 200);
            },
            function () {
                return current_user_can('edit_theme_options');
            },
            array(
                'id' => array(
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            )
        );
    }

    private function register_history_routes(): void {
        $this->register_route(
            '/history',
            'GET',
            function () {
                $history = History_Manager::get_instance();
                return new \WP_REST_Response(
                    array(
                        'success'  => true,
                        'history'  => $history->get_timeline(),
                        'position' => $history->get_position(),
                    ),
                    200
                );
            },
            function () {
                return current_user_can('edit_theme_options');
            }
        );

        $this->register_route(
            '/history/undo',
            'POST',
            function () {
                $history = History_Manager::get_instance();
                $result  = $history->undo();
                if (!empty($result['snapshot'])) {
                    $result['entry'] = $history->to_entry(new \PhantomCore\History\Snapshot($result['snapshot']));
                }
                return new \WP_REST_Response($result, $result['success'] ? 200 : 400);
            },
            function () {
                return current_user_can('edit_theme_options');
            }
        );

        $this->register_route(
            '/history/redo',
            'POST',
            function () {
                $history = History_Manager::get_instance();
                $result  = $history->redo();
                if (!empty($result['snapshot'])) {
                    $result['entry'] = $history->to_entry(new \PhantomCore\History\Snapshot($result['snapshot']));
                }
                return new \WP_REST_Response($result, $result['success'] ? 200 : 400);
            },
            function () {
                return current_user_can('edit_theme_options');
            }
        );
    }

    private function register_instance_tree_routes(): void {
        $this->register_route(
            '/instances/tree',
            'GET',
            function () {
                return new \WP_REST_Response(
                    array('tree' => Component_Tree::get_instance()->build_from_registry()),
                    200
                );
            },
            function () {
                return current_user_can('edit_theme_options');
            }
        );
    }

    private function register_lock_routes(): void {
        $this->register_route(
            '/instances/locked',
            'GET',
            function () {
                $manager   = Lock_Manager::get_instance();
                $instances = array();
                foreach ($manager->get_locked() as $lock) {
                    $id    = $lock['instance_id'] ?? '';
                    $inst  = !empty($id) ? ComponentInstance::get($id) : null;
                    $instances[] = array(
                        'id'        => $id,
                        'component' => $inst ? $inst->component_name : '',
                        'user_name' => $lock['user_name'] ?? '',
                        'locked_at' => $lock['locked_at'] ?? '',
                    );
                }
                return new \WP_REST_Response(array('instances' => $instances), 200);
            },
            function () {
                return current_user_can('edit_theme_options');
            }
        );

        $this->register_route(
            '/instances/lock',
            'POST',
            function (\WP_REST_Request $request) {
                $manager = Lock_Manager::get_instance();
                $id      = (string) $request->get_param('id');
                if ('' === $id) {
                    return new \WP_Error('missing_id', 'Instance ID required.', array('status' => 400));
                }
                $manager->lock($id);
                return new \WP_REST_Response(array('locked' => $manager->is_locked($id)), 200);
            },
            function () {
                return current_user_can('edit_theme_options');
            },
            array(
                'id' => array(
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            )
        );

        $this->register_route(
            '/instances/unlock',
            'POST',
            function (\WP_REST_Request $request) {
                $manager = Lock_Manager::get_instance();
                $id      = (string) $request->get_param('id');
                if ('' === $id) {
                    return new \WP_Error('missing_id', 'Instance ID required.', array('status' => 400));
                }
                $manager->unlock($id);
                return new \WP_REST_Response(array('locked' => $manager->is_locked($id)), 200);
            },
            function () {
                return current_user_can('edit_theme_options');
            },
            array(
                'id' => array(
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            )
        );
    }

    private function register_favorites_routes(): void {
        $this->register_route(
            '/favorites',
            'GET',
            function () {
                return new \WP_REST_Response(
                    array('favorites' => Favorites_Manager::get_instance()->get_with_data()),
                    200
                );
            },
            function () {
                return current_user_can('edit_theme_options');
            }
        );

        $this->register_route(
            '/favorites/toggle',
            'POST',
            function (\WP_REST_Request $request) {
                $type = (string) $request->get_param('type');
                $id   = (string) $request->get_param('id');
                if ('' === $type || '' === $id) {
                    return new \WP_Error('missing_args', 'type and id are required.', array('status' => 400));
                }
                $favorite = Favorites_Manager::get_instance()->toggle($type, $id);
                return new \WP_REST_Response(
                    array('success' => true, 'favorite' => $favorite),
                    200
                );
            },
            function () {
                return current_user_can('edit_theme_options');
            },
            array(
                'type' => array(
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'id' => array(
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            )
        );
    }

    private function register_search_routes(): void {
        $this->register_route(
            '/search',
            'GET',
            function (\WP_REST_Request $request) {
                $q       = (string) $request->get_param('q');
                $grouped = Search_Service::get_instance()->search($q);
                $results = array();
                foreach ($grouped as $group) {
                    $items = $group['items'] ?? array();
                    if (empty($items)) {
                        continue;
                    }
                    foreach ($items as $item) {
                        $results[] = $item;
                    }
                }
                return new \WP_REST_Response(array('results' => $results), 200);
            },
            function () {
                return current_user_can('edit_theme_options');
            },
            array(
                'q' => array(
                    'type'              => 'string',
                    'default'           => '',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            )
        );
    }

    private function register_build_routes(): void {
        $this->register_route(
            '/build/status',
            'GET',
            function () {
                if (!class_exists(\PhantomCore\Asset\Pipeline\Pipeline::class)) {
                    return new \WP_REST_Response(
                        array('current' => false, 'version' => '', 'build' => 0, 'date' => '', 'size' => 0),
                        200
                    );
                }
                $history = \PhantomCore\Asset\Pipeline\Pipeline::get_instance()->get_build_history();
                $status  = array('current' => false, 'version' => '', 'build' => 0, 'date' => '', 'size' => 0);
                if (!empty($history)) {
                    $first = $history[0];
                    $status = array(
                        'current' => (bool) ($first['active'] ?? false),
                        'version' => $first['version'] ?? '',
                        'build'   => count($history),
                        'date'    => $first['date'] ?? '',
                        'size'    => $first['size'] ?? 0,
                    );
                }
                return new \WP_REST_Response($status, 200);
            },
            function () {
                return current_user_can('edit_theme_options');
            }
        );

        $this->register_route(
            '/build/history',
            'GET',
            function () {
                if (!class_exists(\PhantomCore\Asset\Pipeline\Pipeline::class)) {
                    return new \WP_REST_Response(array('history' => array()), 200);
                }
                return new \WP_REST_Response(
                    array('history' => \PhantomCore\Asset\Pipeline\Pipeline::get_instance()->get_build_history()),
                    200
                );
            },
            function () {
                return current_user_can('edit_theme_options');
            }
        );

        $this->register_route(
            '/publish',
            'POST',
            function (\WP_REST_Request $request) {
                $profile = $request->get_param('profile') ?: 'production';

                // Step 1: History snapshot before publish.
                $snapshot_id = null;
                if (class_exists(History_Manager::class)) {
                    try {
                        $autosave = \PhantomCore\History\History_Autosave::get_instance();
                        $settings = $autosave->capture_current_settings();
                        $manager  = History_Manager::get_instance();
                        $snapshot = $manager->create_snapshot(
                            $settings,
                            'before_publish',
                            __('Published via Visual Customizer', 'phantom-core')
                        );
                        $snapshot_id = $snapshot->id;
                    } catch (\Throwable $e) {
                        // Non-fatal: history is best-effort.
                    }
                }

                // Step 2: Commit preview values to the Settings Registry.
                if (class_exists(\PhantomCore\Design\ThemeStateEngine::class)) {
                    try {
                        \PhantomCore\Design\ThemeStateEngine::get_instance()->commit_preview();
                    } catch (\Throwable $e) {
                        // Non-fatal.
                    }
                }

                // Step 3: Regenerate CSS caches.
                if (class_exists('\Phantom_Custom_CSS')) {
                    \Phantom_Custom_CSS::flush_cache();
                }
                delete_transient('phantom_page_data_v2');

                // Step 4: Run the asset pipeline build (records build files + version).
                $version = null;
                if (class_exists(\PhantomCore\Asset\Pipeline\Pipeline::class)) {
                    try {
                        $build = \PhantomCore\Asset\Pipeline\Pipeline::get_instance()->execute('css', array('profile' => $profile));
                        $version = $build['version'] ?? null;
                    } catch (\Throwable $e) {
                        // Non-fatal: settings already committed.
                    }
                }

                return new \WP_REST_Response(
                    array(
                        'success'     => true,
                        'message'     => __('Settings published successfully.', 'phantom-core'),
                        'version'     => $version,
                        'snapshot_id' => $snapshot_id,
                    ),
                    200
                );
            },
            function () {
                return current_user_can('edit_theme_options');
            },
            array(
                'profile' => array(
                    'type'              => 'string',
                    'default'           => 'production',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            )
        );
    }

    private function register_pack_routes(): void {
        $this->register_route(
            '/packs',
            'GET',
            function () {
                $packs_dir = PHANTOM_CORE_PATH . 'frontend/packs/';
                $packs = array();

                if (!is_dir($packs_dir)) {
                    return new \WP_REST_Response(array('packs' => $packs), 200);
                }

                $dirs = scandir($packs_dir);
                foreach ($dirs as $dir) {
                    if ($dir === '.' || $dir === '..') {
                        continue;
                    }
                    $manifest_path = $packs_dir . $dir . '/manifest.json';
                    if (!file_exists($manifest_path)) {
                        continue;
                    }
                    $manifest = json_decode((string) file_get_contents($manifest_path), true);
                    if (is_array($manifest)) {
                        $manifest['slug'] = $dir;
                        $packs[] = $manifest;
                    }
                }

                return new \WP_REST_Response(array('packs' => $packs), 200);
            },
            '__return_true'
        );
    }

    private function register_diagnostics_routes(): void {
        $this->register_route(
            '/diagnostics',
            'GET',
            function () {
                $report = array(
                    'timestamp'         => current_time('mysql'),
                    'php'               => PHP_VERSION,
                    'wordpress'         => get_bloginfo('version'),
                    'phantom_version'   => PHANTOM_CORE_VERSION,
                    'memory_limit'      => ini_get('memory_limit'),
                    'max_execution_time' => ini_get('max_execution_time'),
                    'active_theme'      => wp_get_theme()->get('Name'),
                    'woocommerce_active' => class_exists('WooCommerce'),
                    'debug_mode'        => defined('WP_DEBUG') && WP_DEBUG,
                    'script_debug'      => defined('SCRIPT_DEBUG') && SCRIPT_DEBUG,
                );

                $report['component_count'] = $this->safe_count(fn() => Component_Registry::get_instance()->count());
                $report['property_count']  = $this->safe_count(fn() => Property_Registry::get_instance()->count());
                $report['animation_count'] = $this->safe_count(fn() => Animation_Registry::get_instance()->count());
                $report['asset_count']     = $this->safe_count(fn() => Media_Asset_Registry::get_instance()->count());
                $report['instance_count']  = $this->safe_count(fn() => count(ComponentInstance::load_all()));
                $report['route_count']     = count($this->registry_routes);

                if (!function_exists('get_plugins')) {
                    require_once ABSPATH . 'wp-admin/includes/plugin.php';
                }
                $active_plugins = get_option('active_plugins', array());
                $all_plugins    = get_plugins();
                $report['plugins'] = array();
                foreach ($active_plugins as $plugin_file) {
                    if (isset($all_plugins[$plugin_file])) {
                        $report['plugins'][] = $all_plugins[$plugin_file]['Name'];
                    }
                }

                return new \WP_REST_Response(
                    array(
                        'success'     => true,
                        'diagnostics' => $report,
                    ),
                    200
                );
            },
            function () {
                return current_user_can('edit_theme_options');
            }
        );
    }

    private function safe_count(callable $fn): int {
        try {
            return (int) $fn();
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
