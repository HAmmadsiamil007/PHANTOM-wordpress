<?php
declare(strict_types=1);

namespace PhantomCore\History;

defined('ABSPATH') || exit;

/**
 * Version_Manager — creates, lists, and restores named versions and drafts.
 *
 * @package PhantomCore\History
 */
class Version_Manager {

    private static ?self $instance = null;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Create a named version snapshot.
     *
     * @param string $name        Version name (e.g., "Client Final", "Summer Sale").
     * @param string $description Optional description.
     * @return Snapshot|null
     */
    public function create_version(string $name, string $description = ''): ?Snapshot {
        if (empty(trim($name))) {
            return null;
        }

        $manager = History_Manager::get_instance();
        $autosave = History_Autosave::get_instance();
        $settings = $autosave->capture_current_settings();

        $snapshot = $manager->create_snapshot(
            $settings,
            'version',
            $description ?: sprintf(
                /* translators: %s: version name */
                __('Named version: %s', 'phantom-core'),
                $name
            ),
            []
        );

        // Set version name on the snapshot
        $storage = History_Storage::get_instance();
        $data    = $storage->load();
        if (isset($data['snapshots'][$snapshot->id])) {
            $data['snapshots'][$snapshot->id]['version_name'] = $name;
            $storage->save($data);
        }

        return $snapshot;
    }

    /**
     * Create a draft snapshot (auto-save with draft label).
     *
     * @return Snapshot|null
     */
    public function create_draft(): ?Snapshot {
        $autosave = History_Autosave::get_instance();
        return $autosave->auto_save(__('Draft checkpoint', 'phantom-core'));
    }

    /**
     * Get all named versions.
     *
     * @return Snapshot[]
     */
    public function get_versions(): array {
        $storage   = History_Storage::get_instance();
        $data      = $storage->load();
        $versions  = [];

        foreach ($data['snapshots'] as $id => $snapData) {
            if (!empty($snapData['version_name'])) {
                $versions[] = new Snapshot($snapData);
            }
        }

        // Sort by time descending
        usort($versions, function (Snapshot $a, Snapshot $b) {
            return $b->time - $a->time;
        });

        return $versions;
    }

    /**
     * Restore a named version.
     *
     * @param string $versionId Snapshot ID.
     * @return array{success: bool, count: int, message: string}
     */
    public function restore_version(string $versionId): array {
        $storage = History_Storage::get_instance();
        $data    = $storage->load();

        if (!isset($data['snapshots'][$versionId])) {
            return [
                'success' => false,
                'count'   => 0,
                'message' => __('Version not found.', 'phantom-core'),
            ];
        }

        $snapshot = new Snapshot($data['snapshots'][$versionId]);
        $count    = $snapshot->apply();

        // Save to undo stack before restoring
        $historyManager = History_Manager::get_instance();
        $historyManager->push_to_undo($versionId);

        // Regenerate CSS
        \Phantom_Custom_CSS::flush_cache();
        delete_transient('phantom_page_data_v2');

        return [
            'success' => true,
            'count'   => $count,
            'message' => sprintf(
                /* translators: %d: number of settings restored */
                __('Version restored — %d settings applied.', 'phantom-core'),
                $count
            ),
        ];
    }

    /**
     * Delete a named version.
     */
    public function delete_version(string $versionId): bool {
        $storage = History_Storage::get_instance();
        $data    = $storage->load();

        if (!isset($data['snapshots'][$versionId])) {
            return false;
        }

        unset($data['snapshots'][$versionId]);
        $data['undo_stack'] = array_values(array_filter(
            $data['undo_stack'],
            fn(string $id) => $id !== $versionId
        ));
        $data['redo_stack'] = array_values(array_filter(
            $data['redo_stack'],
            fn(string $id) => $id !== $versionId
        ));

        $storage->save($data);
        return true;
    }
}
