<?php
declare(strict_types=1);

namespace PhantomCore\Engine;

defined( 'ABSPATH' ) || exit;

final class Cache {

	private static ?self $instance = null;
	private string $prefix = 'phantom_cache_';
	private array $groups = [];

	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function init(): void {
		add_action( 'wp_loaded', [ $this, 'schedule_auto_cleanup' ] );
		add_action( 'phantom_cache_cleanup', [ $this, 'auto_cleanup' ] );
		add_action( 'wp_loaded', [ $this, 'init_image_optimization' ] );
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

	public function set_group( string $group, array $items, int $ttl = 3600 ): bool {
		$this->groups[ $group ] = [ 'items' => $items, 'ttl' => $ttl ];
		return $this->set( $group, serialize( $items ), $ttl );
	}

	public function get_group( string $group ) {
		$cached = $this->get( $group );
		if ( false !== $cached ) {
			return unserialize( $cached );
		}
		if ( isset( $this->groups[ $group ] ) ) {
			return $this->groups[ $group ]['items'];
		}
		return false;
	}

	public function delete_group( string $group ): bool {
		unset( $this->groups[ $group ] );
		return $this->delete( $group );
	}

	public function flush_group( string $group ): bool {
		return $this->delete_group( $group );
	}

	public function get_stats(): array {
		global $wpdb;
		$prefix = $wpdb->esc_like( '_transient_' . $this->prefix );
		$count = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s",
			$prefix . '%'
		) );
		return [
			'total_keys' => (int) $count,
			'prefix'     => $this->prefix,
			'groups'     => array_keys( $this->groups ),
			'uptime'     => time() - strtotime( WP_START_TIMESTAMP ?? 'now' ),
		];
	}

	public function schedule_auto_cleanup(): void {
		if ( ! wp_next_scheduled( 'phantom_cache_cleanup' ) ) {
			wp_schedule_event( time(), 'daily', 'phantom_cache_cleanup' );
		}
	}

	public function auto_cleanup(): void {
		global $wpdb;
		$now = time();
		$prefix = $wpdb->esc_like( '_transient_timeout_' . $this->prefix );
		$expired = $wpdb->get_col( $wpdb->prepare(
			"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value < %d",
			$prefix . '%',
			$now
		) );
		foreach ( $expired as $timeout_key ) {
			$transient_key = str_replace( '_transient_timeout_', '_transient_', $timeout_key );
			delete_option( str_replace( $wpdb->prefix, '', $transient_key ) );
			delete_option( $timeout_key );
		}
		do_action( 'phantom_cache_autocleaned', count( $expired ) );
	}

	public function init_image_optimization(): void {
		add_filter( 'wp_calculate_image_srcset', [ $this, 'optimize_srcset' ], 10, 5 );
		add_filter( 'wp_get_attachment_image_attributes', [ $this, 'add_lazy_loading' ], 10, 2 );
		add_filter( 'the_content', [ $this, 'process_content_images' ], 9 );
	}

	public function optimize_srcset( ?array $sources, int $size_id, string $size, string $image_meta, int $attachment_id ): ?array {
		if ( ! $sources ) {
			return null;
		}
		$quality = (int) get_option( 'phantom_image_quality', 80 );
		foreach ( $sources as $key => $source ) {
			$sources[ $key ]['url'] = $this->rewrite_image_url( $source['url'], $quality );
		}
		return $sources;
	}

	public function add_lazy_loading( array $attr, $attachment ): array {
		if ( ! isset( $attr['loading'] ) ) {
			$attr['loading'] = 'lazy';
		}
		return $attr;
	}

	public function process_content_images( string $content ): string {
		if ( ! class_exists( 'DOMDocument' ) ) {
			return $content;
		}
		$dom = new \DOMDocument();
		libxml_use_internal_errors( true );
		$dom->loadHTML( '<body>' . $content . '</body>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		libxml_clear_errors();
		$images = $dom->getElementsByTagName( 'img' );
		foreach ( $images as $img ) {
			if ( ! $img->hasAttribute( 'loading' ) ) {
				$img->setAttribute( 'loading', 'lazy' );
			}
			if ( ! $img->hasAttribute( 'decoding' ) ) {
				$img->setAttribute( 'decoding', 'async' );
			}
		}
		$body = $dom->getElementsByTagName( 'body' )->item( 0 );
		return $body ? $dom->saveHTML( $body ) : $content;
	}

	private function rewrite_image_url( string $url, int $quality ): string {
		$ext = pathinfo( $url, PATHINFO_EXTENSION );
		if ( in_array( strtolower( $ext ), [ 'jpg', 'jpeg', 'png', 'webp' ], true ) ) {
			$separator = strpos( $url, '?' ) !== false ? '&' : '?';
			return $url . $separator . 'quality=' . $quality;
		}
		return $url;
	}

	public function generate_critical_css(): string {
		$critical_css = '';
		$critical_css .= $this->get_above_the_fold_css();
		$critical_css .= $this->get_inline_style_css();
		return $critical_css;
	}

	private function get_above_the_fold_css(): string {
		$css = '';
		$hero_bg = get_option( 'phantom_hero_background_image', '' );
		if ( $hero_bg ) {
			$css .= '.hero-section{background-image:url(' . esc_url( $hero_bg ) . ');background-size:cover;background-position:center;}' . "\n";
		}
		return $css;
	}

	private function get_inline_style_css(): string {
		$css = '';
		$primary = get_option( 'phantom_primary_color', '#0073aa' );
		if ( $primary ) {
			$css .= ':root{--phantom-primary:' . esc_attr( $primary ) . ';}' . "\n";
		}
		return $css;
	}

	public function optimize_database(): array {
		global $wpdb;
		$results = [];
		$tables = [
			'revision'    => "DELETE FROM {$wpdb->posts} WHERE post_type = 'revision'",
			'spam_comment'=> "DELETE FROM {$wpdb->comments} WHERE comment_approved = 'spam'",
			'trash_post'  => "DELETE FROM {$wpdb->posts} WHERE post_status = 'trash'",
			'auto_draft'  => "DELETE FROM {$wpdb->posts} WHERE post_status = 'auto-draft'",
			'orphan_meta' => "DELETE pm FROM {$wpdb->postmeta} pm LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE p.ID IS NULL",
		];
		foreach ( $tables as $name => $query ) {
			$count = $wpdb->query( $query );
			$results[ $name ] = (int) $count;
		}
		$results['options_cleanup'] = (int) $wpdb->query(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_%' AND option_value < " . time()
		);
		do_action( 'phantom_db_optimized', $results );
		return $results;
	}
}