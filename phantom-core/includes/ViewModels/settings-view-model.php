<?php
declare(strict_types=1);

namespace PhantomCore\ViewModels;

use PhantomCore\Contracts\ViewModelInterface;

defined('ABSPATH') || exit;

/**
 * Settings_ViewModel transforms settings data into a typed view-model object.
 * Bridge between adapters and template renderers.
 */
final class Settings_ViewModel implements ViewModelInterface {
	public array $all;
	public array $sections;

	/**
	 * Create from raw settings data array.
	 */
	public static function from_adapter_output(array $data): self {
		$vm = new self();
		$vm->all = (array) ($data['all'] ?? []);
		$vm->sections = (array) ($data['sections'] ?? []);
		return $vm;
	}

	/**
	 * Get a single setting value by key.
	 */
	public function get(string $key, $default = null) {
		return $this->all[$key] ?? $default;
	}

	/**
	 * Get all settings in a section.
	 */
	public function get_section(string $section): array {
		return $this->sections[$section] ?? [];
	}

	/**
	 * Convert to array for template rendering.
	 */
	public function to_array(): array {
		return [
			'all' => $this->all,
			'sections' => $this->sections,
		];
	}
}
