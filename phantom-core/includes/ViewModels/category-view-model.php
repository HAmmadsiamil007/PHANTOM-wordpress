<?php
declare(strict_types=1);

namespace PhantomCore\ViewModels;

use PhantomCore\Contracts\ViewModelInterface;

defined('ABSPATH') || exit;

/**
 * Category_ViewModel transforms category data into a typed view-model object.
 * Bridge between adapters (raw WP_Term data) and renderers (HTML output).
 */
final class Category_ViewModel implements ViewModelInterface {
	public int $id;
	public string $name;
	public string $slug;
	public string $permalink;
	public string $description;
	public string $image;
	public int $count;

	/**
	 * Create from raw category data array.
	 */
	public static function from_adapter_output(array $data): self {
		$vm = new self();
		$vm->id = (int) ($data['id'] ?? 0);
		$vm->name = (string) ($data['name'] ?? '');
		$vm->slug = (string) ($data['slug'] ?? '');
		$vm->permalink = (string) ($data['url'] ?? '');
		$vm->description = (string) ($data['description'] ?? '');
		$vm->image = (string) ($data['image'] ?? '');
		$vm->count = (int) ($data['count'] ?? 0);
		return $vm;
	}

	/**
	 * Convert to array for template rendering.
	 */
	public function to_array(): array {
		return [
			'id' => $this->id,
			'name' => $this->name,
			'slug' => $this->slug,
			'url' => $this->permalink,
			'description' => $this->description,
			'image' => $this->image,
			'count' => $this->count,
		];
	}
}
