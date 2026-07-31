<?php
declare(strict_types=1);

namespace PhantomCore\History;

defined('ABSPATH') || exit;

/**
 * History_Rest — registers all REST API routes for the History & Versioning system.
 *
 * @package PhantomCore\History
 */
class History_Rest {

    private static ?self $instance = null;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize REST routes.
     */
    public function init(): void {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Register all history endpoints under phantom/v1.
     */
    public function register_routes(): void {
        $namespace = 'phantom/v1';

        // GET /history — list all snapshots
        register_rest_route($namespace, '/history', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'get_history'],
            'permission_callback' => [$this, 'admin_check'],
        ]);

        // POST /history/snapshot — create a snapshot
        register_rest_route($namespace, '/history/snapshot', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'create_snapshot'],
            'permission_callback' => [$this, 'admin_check'],
            'args'                => [
                'description' => [
                    'required'          => false,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'action' => [
                    'required'          => false,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);

        // POST /history/undo — undo
        register_rest_route($namespace, '/history/undo', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'undo'],
            'permission_callback' => [$this, 'admin_check'],
        ]);

        // POST /history/redo — redo
        register_rest_route($namespace, '/history/redo', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'redo'],
            'permission_callback' => [$this, 'admin_check'],
        ]);

        // POST /history/clear — wipe all history
        register_rest_route($namespace, '/history/clear', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'clear_history'],
            'permission_callback' => [$this, 'admin_check'],
        ]);

        // POST /history/restore/{id} — restore a specific snapshot
        register_rest_route($namespace, '/history/restore/(?P<id>[a-f0-9-]+)', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'restore_snapshot'],
            'permission_callback' => [$this, 'admin_check'],
            'args'                => [
                'id' => [
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);

        // DELETE /history/{id} — delete a snapshot
        register_rest_route($namespace, '/history/(?P<id>[a-f0-9-]+)', [
            'methods'             => \WP_REST_Server::DELETABLE,
            'callback'            => [$this, 'delete_snapshot'],
            'permission_callback' => [$this, 'admin_check'],
            'args'                => [
                'id' => [
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);

        // GET /history/compare — diff two snapshots
        register_rest_route($namespace, '/history/compare', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'compare_snapshots'],
            'permission_callback' => [$this, 'admin_check'],
            'args'                => [
                'before' => [
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'after' => [
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);

        // POST /history/versions — create a named version
        register_rest_route($namespace, '/history/versions', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'create_version'],
            'permission_callback' => [$this, 'admin_check'],
            'args'                => [
                'name' => [
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'description' => [
                    'required'          => false,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);

        // GET /history/versions — list all versions
        register_rest_route($namespace, '/history/versions', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'get_versions'],
            'permission_callback' => [$this, 'admin_check'],
        ]);

        // DELETE /history/versions/{id} — delete a version
        register_rest_route($namespace, '/history/versions/(?P<id>[a-f0-9-]+)', [
            'methods'             => \WP_REST_Server::DELETABLE,
            'callback'            => [$this, 'delete_version'],
            'permission_callback' => [$this, 'admin_check'],
            'args'                => [
                'id' => [
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);

        // GET /history/status — full status
        register_rest_route($namespace, '/history/status', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'get_status'],
            'permission_callback' => [$this, 'admin_check'],
        ]);
    }

    /**
     * Permission check: manage_options + nonce.
     * Matches the pattern used in Rest_Controller (verify_nonce).
     */
    public function admin_check($request = null): bool {
        if (!function_exists('wp_verify_nonce')) {
            return true;
        }
        if (!current_user_can('manage_options')) {
            return false;
        }
        // Verify nonce if request is provided
        if ($request instanceof \WP_REST_Request) {
            $nonce = $request->get_header('X-WP-Nonce');
            if ($nonce && !wp_verify_nonce($nonce, 'wp_rest')) {
                return false;
            }
        }
        return true;
    }

    // ── Callbacks ────────────────────────────────────

    public function get_history(): \WP_REST_Response {
        $manager = History_Manager::get_instance();
        return new \WP_REST_Response([
            'success' => true,
            'history' => $manager->get_timeline(),
            'position' => $manager->get_position(),
        ], 200);
    }

    public function create_snapshot(\WP_REST_Request $request): \WP_REST_Response {
        $manager  = History_Manager::get_instance();
        $autosave = History_Autosave::get_instance();
        $settings = $autosave->capture_current_settings();
        $action   = $request->get_param('action') ?: 'manual';
        $desc     = $request->get_param('description') ?: '';

        $snapshot = $manager->create_snapshot($settings, $action, $desc);

        return new \WP_REST_Response([
            'success'  => true,
            'snapshot' => $snapshot->to_array(),
            'position' => $manager->get_position(),
        ], 200);
    }

    public function undo(): \WP_REST_Response {
        $manager = History_Manager::get_instance();
        $result  = $manager->undo();

        if (!empty($result['snapshot'])) {
            $result['entry'] = $manager->to_entry(new \PhantomCore\History\Snapshot($result['snapshot']));
        }

        return new \WP_REST_Response($result, $result['success'] ? 200 : 400);
    }

    public function redo(): \WP_REST_Response {
        $manager = History_Manager::get_instance();
        $result  = $manager->redo();

        if (!empty($result['snapshot'])) {
            $result['entry'] = $manager->to_entry(new \PhantomCore\History\Snapshot($result['snapshot']));
        }

        return new \WP_REST_Response($result, $result['success'] ? 200 : 400);
    }

    public function clear_history(): \WP_REST_Response {
        $manager = History_Manager::get_instance();
        $manager->clear();

        return new \WP_REST_Response([
            'success'  => true,
            'message'  => __('History cleared.', 'phantom-core'),
            'position' => $manager->get_position(),
        ], 200);
    }

    public function restore_snapshot(\WP_REST_Request $request): \WP_REST_Response {
        $id        = $request->get_param('id');
        $manager   = History_Manager::get_instance();
        $storage   = History_Storage::get_instance();
        $data      = $storage->load();

        if (!isset($data['snapshots'][$id])) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => __('Snapshot not found.', 'phantom-core'),
            ], 404);
        }

        $snapshot = new \PhantomCore\History\Snapshot($data['snapshots'][$id]);

        // Save current state first (manually, to avoid create_snapshot side effects)
        $autosave = History_Autosave::get_instance();
        $current  = $autosave->capture_current_settings();
        $beforeSnapshot = new \PhantomCore\History\Snapshot([
            'settings'    => $current,
            'action'      => 'before_restore',
            'description' => __('State before restore', 'phantom-core'),
        ]);
        $data['snapshots'][$beforeSnapshot->id] = $beforeSnapshot->to_array();
        $data['undo_stack'][] = $beforeSnapshot->id;
        $data['redo_stack'] = [];
        $data['position'] = count($data['undo_stack']);
        $data['current'] = $beforeSnapshot->id;
        $storage->save($data);

        $count = $snapshot->apply();

        // Save again after restore so undo can revert
        $dataCopy = $storage->load();
        $dataCopy['current'] = $id;
        $dataCopy['redo_stack'][] = $id;
        $storage->save($dataCopy);

        // Regenerate CSS
        \Phantom_Custom_CSS::flush_cache();
        delete_transient('phantom_page_data_v2');

        return new \WP_REST_Response([
            'success'  => true,
            'snapshot' => $snapshot->to_array(),
            'count'    => $count,
            'position' => $manager->get_position(),
            'message'  => sprintf(
                /* translators: %d: number of settings restored */
                __('Snapshot restored — %d settings applied.', 'phantom-core'),
                $count
            ),
        ], 200);
    }

    public function delete_snapshot(\WP_REST_Request $request): \WP_REST_Response {
        $id      = $request->get_param('id');
        $manager = History_Manager::get_instance();
        $deleted = $manager->delete_snapshot($id);

        if (!$deleted) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => __('Snapshot not found.', 'phantom-core'),
            ], 404);
        }

        return new \WP_REST_Response([
            'success'  => true,
            'message'  => __('Snapshot deleted.', 'phantom-core'),
            'position' => $manager->get_position(),
        ], 200);
    }

    public function compare_snapshots(\WP_REST_Request $request): \WP_REST_Response {
        $beforeId = $request->get_param('before');
        $afterId  = $request->get_param('after');
        $storage  = History_Storage::get_instance();
        $data     = $storage->load();

        if (!isset($data['snapshots'][$beforeId])) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => __('Before snapshot not found.', 'phantom-core'),
            ], 404);
        }
        if (!isset($data['snapshots'][$afterId])) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => __('After snapshot not found.', 'phantom-core'),
            ], 404);
        }

        $before  = new Snapshot($data['snapshots'][$beforeId]);
        $after   = new Snapshot($data['snapshots'][$afterId]);
        $comparer = History_Comparer::get_instance();
        $diff     = $comparer->compare($before, $after);

        return new \WP_REST_Response([
            'success' => true,
            'before'  => $before->to_array(),
            'after'   => $after->to_array(),
            'diff'    => $diff,
        ], 200);
    }

    public function create_version(\WP_REST_Request $request): \WP_REST_Response {
        $name        = $request->get_param('name');
        $description = $request->get_param('description') ?: '';
        $versions    = Version_Manager::get_instance();
        $snapshot    = $versions->create_version($name, $description);

        if (null === $snapshot) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => __('Version name is required.', 'phantom-core'),
            ], 400);
        }

        return new \WP_REST_Response([
            'success'  => true,
            'snapshot' => $snapshot->to_array(),
            'message'  => sprintf(
                /* translators: %s: version name */
                __('Version "%s" created.', 'phantom-core'),
                $name
            ),
        ], 200);
    }

    public function get_versions(): \WP_REST_Response {
        $versions = Version_Manager::get_instance();
        $items    = array_map(
            fn(Snapshot $v) => $v->to_array(),
            $versions->get_versions()
        );

        return new \WP_REST_Response([
            'success'  => true,
            'versions' => $items,
            'total'    => count($items),
        ], 200);
    }

    public function delete_version(\WP_REST_Request $request): \WP_REST_Response {
        $id       = $request->get_param('id');
        $versions = Version_Manager::get_instance();
        $deleted  = $versions->delete_version($id);

        if (!$deleted) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => __('Version not found.', 'phantom-core'),
            ], 404);
        }

        return new \WP_REST_Response([
            'success' => true,
            'message' => __('Version deleted.', 'phantom-core'),
        ], 200);
    }

    public function get_status(): \WP_REST_Response {
        $manager = History_Manager::get_instance();
        return new \WP_REST_Response([
            'success' => true,
            'status'  => $manager->get_status(),
        ], 200);
    }
}
