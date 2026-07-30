<?php
declare(strict_types=1);

namespace PhantomCore\History;

defined('ABSPATH') || exit;

/**
 * History_Storage — persists the entire history tree as a single wp_options entry.
 *
 * Stores: snapshots array, undo stack, redo stack, current position, and metadata.
 * Single option = easy to backup, export, cache, and migrate.
 *
 * @package PhantomCore\History
 */
class History_Storage {

    private const OPTION_KEY = 'phantom_history';

    private static ?self $instance = null;
    private ?array $cache = null;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Load the full history tree from the database.
     *
     * @return array{snapshots: array, undo_stack: array, redo_stack: array, current: ?string, position: int, max_snapshots: int}
     */
    public function load(): array {
        if (null !== $this->cache) {
            return $this->cache;
        }

        $default = [
            'snapshots'    => [],
            'undo_stack'   => [],
            'redo_stack'   => [],
            'current'      => null,
            'position'     => 0,
            'max_snapshots' => 50,
        ];

        $data = get_option(self::OPTION_KEY, $default);
        if (!is_array($data)) {
            $data = $default;
        }

        // Ensure all keys exist
        foreach ($default as $key => $val) {
            if (!array_key_exists($key, $data)) {
                $data[$key] = $val;
            }
        }

        $this->cache = $data;
        return $data;
    }

    /**
     * Save the full history tree.
     */
    public function save(array $data): void {
        $this->cache = $data;
        update_option(self::OPTION_KEY, $data, false);
    }

    /**
     * Clear everything.
     */
    public function clear(): void {
        $this->cache = null;
        delete_option(self::OPTION_KEY);
    }

    /**
     * Get the number of snapshots stored.
     */
    public function count(): int {
        $data = $this->load();
        return count($data['snapshots']);
    }

    /**
     * Get total history size across all stacks.
     */
    public function total_entries(): int {
        $data = $this->load();
        return count($data['snapshots']) + count($data['undo_stack']) + count($data['redo_stack']);
    }

    /**
     * Get estimated storage size.
     */
    public function get_storage_size(): string {
        $data = $this->load();
        $json = wp_json_encode($data);
        return size_format(strlen($json)) ?: '0 B';
    }

    /**
     * Enforce max_snapshots limit — trim oldest snapshots.
     */
    public function enforce_limit(): int {
        $data = $this->load();
        $max  = $data['max_snapshots'];
        $snapshots = &$data['snapshots'];
        $trimmed = 0;

        while (count($snapshots) > $max) {
            $oldest_id = array_key_first($snapshots);
            if (null === $oldest_id) break;

            // Remove from main snapshots
            unset($snapshots[$oldest_id]);

            // Remove from undo/redo stacks
            $data['undo_stack'] = array_values(array_filter(
                $data['undo_stack'],
                fn(string $id) => $id !== $oldest_id
            ));
            $data['redo_stack'] = array_values(array_filter(
                $data['redo_stack'],
                fn(string $id) => $id !== $oldest_id
            ));

            $trimmed++;
        }

        if ($trimmed > 0) {
            $this->save($data);
        }

        return $trimmed;
    }
}
