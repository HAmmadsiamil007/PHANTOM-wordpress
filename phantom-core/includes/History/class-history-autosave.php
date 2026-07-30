<?php
declare(strict_types=1);

namespace PhantomCore\History;

defined('ABSPATH') || exit;

/**
 * History_Autosave — automatic snapshot creation with configurable interval.
 *
 * Uses a transient-based heartbeat to track "last auto-save" time.
 * The Design Studio JS will fire a heartbeat every 60 seconds.
 *
 * @package PhantomCore\History
 */
class History_Autosave {

    private const AUTOSAVE_INTERVAL_OPTION = 'phantom_autosave_interval';
    private const LAST_AUTOSAVE_TRANSIENT = 'phantom_last_autosave';

    private static ?self $instance = null;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Attempt an auto-save. Returns the created snapshot or null if skipped.
     *
     * Auto-save is skipped when:
     * - Not enough time has elapsed since last auto-save
     * - No changes detected since last snapshot
     * - The settings registry has no entries
     *
     * @param string $context Optional context description.
     * @return Snapshot|null
     */
    public function auto_save(string $context = ''): ?Snapshot {
        if (!$this->should_auto_save()) {
            return null;
        }

        $manager = History_Manager::get_instance();
        $storage = History_Storage::get_instance();
        $data    = $storage->load();

        // Don't auto-save if nothing changed since last snapshot
        $current = $this->capture_current_settings();
        $lastId  = $data['current'];
        if ($lastId && isset($data['snapshots'][$lastId])) {
            $last = new Snapshot($data['snapshots'][$lastId]);
            $comparer = History_Comparer::get_instance();
            $diff = $comparer->compare_live($last);
            if (!$diff['is_dirty']) {
                return null; // No changes, skip
            }
        }

        $description = $context ?: __('Auto-save checkpoint', 'phantom-core');

        $snapshot = $manager->create_snapshot(
            $current,
            'auto',
            $description,
            []
        );

        $this->mark_auto_saved();
        return $snapshot;
    }

    /**
     * Check if enough time has passed since last auto-save.
     */
    public function should_auto_save(): bool {
        $interval = $this->get_interval();
        $last     = get_transient(self::LAST_AUTOSAVE_TRANSIENT);

        if (false === $last) {
            return true;
        }

        return (time() - (int) $last) >= $interval;
    }

    /**
     * Mark the current time as the last auto-save.
     */
    public function mark_auto_saved(): void {
        set_transient(self::LAST_AUTOSAVE_TRANSIENT, time(), DAY_IN_SECONDS);
    }

    /**
     * Get the auto-save interval in seconds.
     */
    public function get_interval(): int {
        return (int) get_option(self::AUTOSAVE_INTERVAL_OPTION, 60);
    }

    /**
     * Set the auto-save interval.
     */
    public function set_interval(int $seconds): void {
        update_option(self::AUTOSAVE_INTERVAL_OPTION, max(10, min(3600, $seconds)));
    }

    /**
     * Capture all current settings.
     */
    public function capture_current_settings(): array {
        $registry = \PhantomCore\Settings_Registry::get_instance();
        $entries  = $registry->get_entries();
        $settings = [];

        foreach ($entries as $key => $entry) {
            $settings[$key] = $registry->get($key);
        }

        return $settings;
    }

    /**
     * Get auto-save status.
     *
     * @return array{interval: int, last_auto_save: ?int, seconds_since_last: int}
     */
    public function get_status(): array {
        $interval = $this->get_interval();
        $last     = get_transient(self::LAST_AUTOSAVE_TRANSIENT);

        return [
            'interval'           => $interval,
            'last_auto_save'     => false === $last ? null : (int) $last,
            'seconds_since_last' => false === $last ? -1 : time() - (int) $last,
            'due'                => $this->should_auto_save(),
        ];
    }

    /**
     * Clear auto-save tracking.
     */
    public function reset(): void {
        delete_transient(self::LAST_AUTOSAVE_TRANSIENT);
    }
}
