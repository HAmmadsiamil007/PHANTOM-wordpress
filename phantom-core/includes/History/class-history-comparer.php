<?php
declare(strict_types=1);

namespace PhantomCore\History;

defined('ABSPATH') || exit;

/**
 * History_Comparer — diffs two snapshots to produce a change log.
 *
 * @package PhantomCore\History
 */
class History_Comparer {

    private static ?self $instance = null;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Compare two snapshots and return detailed changes.
     *
     * @param Snapshot $before The older snapshot.
     * @param Snapshot $after  The newer snapshot.
     * @return array{changes: array, added: int, modified: int, removed: int, summary: string}
     */
    public function compare(Snapshot $before, Snapshot $after): array {
        $changes  = [];
        $added    = 0;
        $modified = 0;
        $removed  = 0;

        $beforeKeys = array_keys($before->settings);
        $afterKeys  = array_keys($after->settings);

        // Added settings (in after but not in before)
        foreach ($afterKeys as $key) {
            if (!array_key_exists($key, $before->settings)) {
                $changes[] = [
                    'key'      => $key,
                    'type'     => 'added',
                    'old'      => null,
                    'new'      => $after->settings[$key],
                    'old_str'  => '—',
                    'new_str'  => $this->format_value($after->settings[$key]),
                ];
                $added++;
                continue;
            }

            $oldVal = $before->settings[$key];
            $newVal = $after->settings[$key];

            if ($oldVal !== $newVal) {
                $changes[] = [
                    'key'      => $key,
                    'type'     => 'modified',
                    'old'      => $oldVal,
                    'new'      => $newVal,
                    'old_str'  => $this->format_value($oldVal),
                    'new_str'  => $this->format_value($newVal),
                ];
                $modified++;
            }
        }

        // Removed settings (in before but not in after)
        foreach ($beforeKeys as $key) {
            if (!array_key_exists($key, $after->settings)) {
                $changes[] = [
                    'key'      => $key,
                    'type'     => 'removed',
                    'old'      => $before->settings[$key],
                    'new'      => null,
                    'old_str'  => $this->format_value($before->settings[$key]),
                    'new_str'  => '—',
                ];
                $removed++;
            }
        }

        $total = $added + $modified + $removed;

        return [
            'changes' => $changes,
            'added'   => $added,
            'modified' => $modified,
            'removed'  => $removed,
            'total'    => $total,
            'summary'  => sprintf(
                /* translators: %1$d: added count, %2$d: modified count, %3$d: removed count */
                __('%1$d added, %2$d modified, %3$d removed', 'phantom-core'),
                $added,
                $modified,
                $removed
            ),
        ];
    }

    /**
     * Compare live settings against a snapshot.
     *
     * @param Snapshot $snapshot The snapshot to compare against current state.
     * @return array{changes: array, total: int, is_dirty: bool}
     */
    public function compare_live(Snapshot $snapshot): array {
        $current = [];
        $registry = \PhantomCore\Settings_Registry::get_instance();

        // Build current state of settings that were in the snapshot
        foreach ($snapshot->settings as $key => $oldVal) {
            if ($registry->has($key)) {
                $current[$key] = $registry->get($key);
            }
        }

        $currentSnapshot = new Snapshot(['settings' => $current, 'action' => '']);
        $result = $this->compare($snapshot, $currentSnapshot);

        return [
            'changes'  => $result['changes'],
            'total'    => $result['total'],
            'is_dirty' => $result['total'] > 0,
        ];
    }

    /**
     * Format a value for human-readable display.
     */
    private function format_value(mixed $value): string {
        if (null === $value) {
            return '—';
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_array($value)) {
            return wp_json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        return (string) $value;
    }
}
