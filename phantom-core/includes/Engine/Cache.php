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
		global $wpdb;
		$prefix = $wpdb->esc_like( '_transient_' ) . '%';
		$timeout_prefix = $wpdb->esc_like( '_transient_timeout_' ) . '%';
		$like = $wpdb->esc_like( '_transient_' . $this->prefix ) . '%';

		$wpdb->query( 'START TRANSACTION' );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s AND option_name LIKE %s", $prefix, $like ) );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s AND option_name LIKE %s", $timeout_prefix, $wpdb->esc_like( '_transient_timeout_' . $this->prefix ) . '%' ) );
		if ( is_multisite() ) {
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE %s AND meta_key LIKE %s", $prefix, $like ) );
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE %s AND meta_key LIKE %s", $timeout_prefix, $wpdb->esc_like( '_site_transient_timeout_' . $this->prefix ) . '%' ) );
		}
		$wpdb->query( 'COMMIT' );

		wp_cache_flush_group( 'transient' );

		do_action( 'phantom_cache_flushed' );
	}
}
