<?php
declare(strict_types=1);

namespace PhantomCore\History;

defined('ABSPATH') || exit;

/**
 * History_Manager — orchestrator for the History & Versioning system.
 *
 * Manages snapshots, undo/redo stacks, and coordinates sub-systems.
 *
 * @package PhantomCore\History
 */
class History_Manager {

    private static ?self $instance = null;

    private History_Storage $storage;
    private History_Comparer $comparer;
    private History_Autosave $autosave;
    private Version_Manager $versions;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->storage  = History_Storage::get_instance();
        $this->comparer = History_Comparer::get_instance();
        $this->autosave = History_Autosave::get_instance();
        $this->versions = Version_Manager::get_instance();
    }

    /**
     * Initialize the history system.
     */
    public function init(): void {
        // Auto-save hooked to a custom action so JS can trigger it
        add_action('phantom_history_auto_save', function () {
            $this->autosave->auto_save();
        });
    }

    // ── Snapshot Management ──────────────────────────

    /**
     * Create a new snapshot.
     *
     * @param array  $settings    The settings to capture.
     * @param string $action      The action that triggered this snapshot.
     * @param string $description Human-readable description.
     * @param array  $changed     Keys that changed (can be empty = auto-detect).
     * @return Snapshot
     */
    public function create_snapshot(array $settings, string $action = 'manual', string $description = '', array $changed = []): Snapshot {
        $storage = $this->storage;
        $data    = $storage->load();

        // Detect changed keys if not provided
        if (empty($changed) && !empty($data['current'])) {
            $lastId = $data['current'];
            if (isset($data['snapshots'][$lastId])) {
                $lastSnapshot = new Snapshot($data['snapshots'][$lastId]);
                $diff = $this->comparer->compare_live($lastSnapshot);
                $changed = array_map(
                    fn(array $c) => $c['key'],
                    $diff['changes']
                );
            }
        }

        // Detect changed keys from previous snapshot in undo stack
        if (empty($changed) && !empty($data['undo_stack'])) {
            $lastUndoId = end($data['undo_stack']);
            if ($lastUndoId && isset($data['snapshots'][$lastUndoId])) {
                $lastSnapshot = new Snapshot($data['snapshots'][$lastUndoId]);
                $diff = $this->comparer->compare_live($lastSnapshot);
                $changed = array_map(
                    fn(array $c) => $c['key'],
                    $diff['changes']
                );
            }
        }

        $snapshot = new Snapshot([
            'settings'    => $settings,
            'action'      => $action,
            'description' => $description,
            'changed'     => $changed,
        ]);

        // Store
        $data['snapshots'][$snapshot->id] = $snapshot->to_array();
        $data['current'] = $snapshot->id;

        // Push to undo stack
        $data['undo_stack'][] = $snapshot->id;

        // Clear redo stack on new action
        $data['redo_stack'] = [];

        // Update position
        $data['position'] = count($data['undo_stack']);

        $storage->save($data);

        // Enforce limits
        $storage->enforce_limit();

        return $snapshot;
    }

    // ── Undo / Redo ──────────────────────────────────

    /**
     * Undo — restore the previous snapshot.
     *
     * @return array{success: bool, snapshot: ?array, position: array, message: string}
     */
    public function undo(): array {
        $storage = $this->storage;
        $data    = $storage->load();

        if (empty($data['undo_stack'])) {
            return [
                'success'  => false,
                'snapshot' => null,
                'position' => $this->get_position(),
                'message'  => __('Nothing to undo.', 'phantom-core'),
            ];
        }

        // Pop last from undo stack
        $snapshotId = array_pop($data['undo_stack']);

        if (!isset($data['snapshots'][$snapshotId])) {
            return [
                'success'  => false,
                'snapshot' => null,
                'position' => $this->get_position(),
                'message'  => __('Snapshot not found.', 'phantom-core'),
            ];
        }

        $snapshot = new Snapshot($data['snapshots'][$snapshotId]);

        // Save current state to redo stack before restoring
        // NOTE: Must NOT call create_snapshot() here — it would push to undo_stack
        // and clear redo_stack. Manually create the intermediate snapshot.
        $currentSettings = $this->autosave->capture_current_settings();
        $currentSnapshot = new Snapshot([
            'settings'    => $currentSettings,
            'action'      => 'auto',
            'description' => __('State before undo', 'phantom-core'),
        ]);
        $data['snapshots'][$currentSnapshot->id] = $currentSnapshot->to_array();
        $data['redo_stack'][] = $currentSnapshot->id;

        // Restore the undo snapshot
        $count = $snapshot->apply();

        // Update current position
        $data['current'] = $snapshotId;
        $data['position'] = count($data['undo_stack']);
        $storage->save($data);

        // Regenerate CSS
        \Phantom_Custom_CSS::flush_cache();
        delete_transient('phantom_page_data_v2');

        return [
            'success'  => true,
            'snapshot' => $snapshot->to_array(),
            'position' => $this->get_position(),
            'count'    => $count,
            'message'  => sprintf(
                /* translators: %d: number of settings restored */
                __('Undo — %d settings restored.', 'phantom-core'),
                $count
            ),
        ];
    }

    /**
     * Redo — restore the next snapshot.
     *
     * @return array{success: bool, snapshot: ?array, position: array, message: string}
     */
    public function redo(): array {
        $storage = $this->storage;
        $data    = $storage->load();

        if (empty($data['redo_stack'])) {
            return [
                'success'  => false,
                'snapshot' => null,
                'position' => $this->get_position(),
                'message'  => __('Nothing to redo.', 'phantom-core'),
            ];
        }

        $snapshotId = array_pop($data['redo_stack']);

        if (!isset($data['snapshots'][$snapshotId])) {
            return [
                'success'  => false,
                'snapshot' => null,
                'position' => $this->get_position(),
                'message'  => __('Snapshot not found.', 'phantom-core'),
            ];
        }

        $snapshot = new Snapshot($data['snapshots'][$snapshotId]);

        // Save current state before restoring (manually, to avoid create_snapshot side effects)
        $currentSettings = $this->autosave->capture_current_settings();
        $currentSnapshot = new Snapshot([
            'settings'    => $currentSettings,
            'action'      => 'auto',
            'description' => __('State before redo', 'phantom-core'),
        ]);
        $data['snapshots'][$currentSnapshot->id] = $currentSnapshot->to_array();
        $data['undo_stack'][] = $currentSnapshot->id;

        $count = $snapshot->apply();

        // Push redo snapshot back to undo stack
        $data['undo_stack'][] = $snapshotId;
        $data['current'] = $snapshotId;
        $data['position'] = count($data['undo_stack']);
        $storage->save($data);

        // Regenerate CSS
        \Phantom_Custom_CSS::flush_cache();
        delete_transient('phantom_page_data_v2');

        return [
            'success'  => true,
            'snapshot' => $snapshot->to_array(),
            'position' => $this->get_position(),
            'count'    => $count,
            'message'  => sprintf(
                /* translators: %d: number of settings restored */
                __('Redo — %d settings restored.', 'phantom-core'),
                $count
            ),
        ];
    }

    /**
     * Push a snapshot ID to the undo stack manually.
     */
    public function push_to_undo(string $snapshotId): void {
        $storage = $this->storage;
        $data    = $storage->load();
        if (isset($data['snapshots'][$snapshotId])) {
            $data['undo_stack'][] = $snapshotId;
            $data['position'] = count($data['undo_stack']);
            $data['redo_stack'] = [];
            $storage->save($data);
        }
    }

    // ── Listing ──────────────────────────────────────

    /**
     * List all snapshots.
     *
     * @param int $limit Maximum number to return.
     * @return array{snapshots: array, total: int}
     */
    public function list_snapshots(int $limit = 50): array {
        $storage   = $this->storage;
        $data      = $storage->load();
        $snapshots = [];

        foreach ($data['snapshots'] as $id => $snapData) {
            $snapData['id'] = $id;
            $snapshots[] = (new Snapshot($snapData))->to_array();
        }

        // Sort by time descending
        usort($snapshots, fn(array $a, array $b) => $b['time'] - $a['time']);

        return [
            'snapshots' => array_slice($snapshots, 0, $limit),
            'total'     => count($data['snapshots']),
        ];
    }

    /**
     * Get current undo/redo position.
     *
     * @return array{undo_count: int, redo_count: int, position: int, current: ?string}
     */
    public function get_position(): array {
        $data = $this->storage->load();
        return [
            'undo_count' => count($data['undo_stack']),
            'redo_count' => count($data['redo_stack']),
            'position'   => $data['position'],
            'current'    => $data['current'],
        ];
    }

    /**
     * Get full history status.
     *
     * @return array{snapshots: array, position: array, auto_save: array, versions: array, storage: array}
     */
    public function get_status(): array {
        return [
            'snapshots' => $this->list_snapshots(10),
            'position'  => $this->get_position(),
            'auto_save' => $this->autosave->get_status(),
            'versions'  => array_map(
                fn(Snapshot $v) => $v->to_array(),
                $this->versions->get_versions()
            ),
            'storage'   => [
                'total_entries' => $this->storage->total_entries(),
                'size'          => $this->storage->get_storage_size(),
            ],
        ];
    }

    /**
     * Delete a specific snapshot.
     */
    public function delete_snapshot(string $snapshotId): bool {
        $storage = $this->storage;
        $data    = $storage->load();

        if (!isset($data['snapshots'][$snapshotId])) {
            return false;
        }

        unset($data['snapshots'][$snapshotId]);
        $data['undo_stack'] = array_values(array_filter(
            $data['undo_stack'],
            fn(string $id) => $id !== $snapshotId
        ));
        $data['redo_stack'] = array_values(array_filter(
            $data['redo_stack'],
            fn(string $id) => $id !== $snapshotId
        ));

        $storage->save($data);
        return true;
    }
}
