<?php
declare(strict_types=1);

namespace PhantomCore\Data;

defined('ABSPATH') || exit;

/**
 * Data_Normalizer provides static utility methods for normalizing
 * raw data into consistent formats for ViewModels and renderers.
 */
class Data_Normalizer {

	/**
	 * Normalize a date string to a consistent format.
	 */
	public static function normalize_date(string $date): string {
		if (empty($date)) {
			return '';
		}
		$timestamp = strtotime($date);
		if (false === $timestamp) {
			return '';
		}
		return gmdate(get_option('date_format', 'F j, Y'), $timestamp);
	}

	/**
	 * Ensure a URL is absolute.
	 */
	public static function normalize_url(string $url): string {
		if (empty($url)) {
			return '';
		}
		if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
			return esc_url($url);
		}
		if (str_starts_with($url, '/')) {
			return esc_url(home_url($url));
		}
		return esc_url($url);
	}

	/**
	 * Truncate text with ellipsis.
	 */
	public static function truncate_text(string $text, int $length): string {
		if (mb_strlen($text) <= $length) {
			return $text;
		}
		return rtrim(mb_substr($text, 0, $length)) . '&hellip;';
	}

	/**
	 * Sanitize HTML content for safe output.
	 */
	public static function sanitize_html(string $html): string {
		return wp_kses_post($html);
	}

	/**
	 * Extract a single field from an array of term objects.
	 */
	public static function extract_terms(array $terms, string $field): array {
		if (empty($terms)) {
			return [];
		}
		$result = [];
		foreach ($terms as $term) {
			if (is_array($term) && isset($term[$field])) {
				$result[] = $term[$field];
			} elseif (is_object($term) && isset($term->$field)) {
				$result[] = $term->$field;
			}
		}
		return $result;
	}

	/**
	 * Flatten a collection of items keyed by ID.
	 */
	public static function flatten_collection(array $items, string $key = 'id'): array {
		if (empty($items)) {
			return [];
		}
		$result = [];
		foreach ($items as $item) {
			if (is_array($item) && isset($item[$key])) {
				$result[$item[$key]] = $item;
			} elseif (is_object($item) && isset($item->$key)) {
				$result[$item->$key] = $item;
			}
		}
		return $result;
	}
}
