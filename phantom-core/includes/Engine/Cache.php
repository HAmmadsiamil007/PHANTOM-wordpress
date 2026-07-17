<?php
declare(strict_types=1);

namespace PhantomCore\Engine;

defined( 'ABSPATH' ) || exit;

final class Cache {

	private static ?self $instance = null;
	private string $prefix = 'phantom_cache_';

	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function init(): void {
	}

	public function set( string $key, $value, int $ttl = 3600 ): bool {
		return set_transient( $this->prefix . $key, $value, $ttl );
	}

	public function get( string $key ) {
		$value = get_transient( $this->prefix . $key );
		return false !== $value ? $value : false;
	}

	public function delete( string $key ): bool {
		return delete_transient( $this->prefix . $key );
	}

	public function flush(): void {
		wp_cache_flush();
	}
}
