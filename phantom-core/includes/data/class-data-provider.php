<?php
declare(strict_types=1);

namespace PhantomCore\Data;

defined('ABSPATH') || exit;

/**
 * Data_Provider is an abstract base class for data providers.
 * Provides transient-based caching and enforces a consistent
 * interface for data retrieval.
 */
abstract class Data_Provider {

	/**
	 * Get a single item by identifier.
	 */
	abstract public function get(string $identifier): array;

	/**
	 * Find items matching criteria.
	 */
	abstract public function find(array $criteria): array;

	/**
	 * Get all items.
	 */
	abstract public function all(): array;

	/**
	 * Build a cache key for this provider.
	 */
	protected function cache_key(string $key): string {
		$class = static::class;
		return 'phantom_' . md5($class . '_' . $key);
	}

	/**
	 * Get cached data if available.
	 */
	protected function cache_get(string $key) {
		return get_transient($this->cache_key($key));
	}

	/**
	 * Set cached data with TTL.
	 */
	protected function cache_set(string $key, $data, int $ttl = 3600): void {
		set_transient($this->cache_key($key), $data, $ttl);
	}
}
