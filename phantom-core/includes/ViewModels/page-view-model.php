<?php
declare(strict_types=1);

namespace PhantomCore\ViewModels;

use PhantomCore\Contracts\ViewModelInterface;

/**
 * Page_ViewModel transforms page data into a typed view-model object.
 * Bridge between adapters and template renderers.
 */
final class Page_ViewModel implements ViewModelInterface {
	public int $id;
	public string $title;
	public string $slug;
	public string $permalink;
	public string $content;
	public string $excerpt;
	public string $date;
	public string $image;
	public string $template;
	public int $parent_id;
	public int $menu_order;

	/**
	 * Create from raw page data array.
	 */
	public static function from_adapter_output(array $data): self {
		$vm = new self();
		$vm->id = (int) ($data['id'] ?? 0);
		$vm->title = (string) ($data['title'] ?? '');
		$vm->slug = (string) ($data['slug'] ?? '');
		$vm->permalink = (string) ($data['url'] ?? '');
		$vm->content = (string) ($data['content'] ?? '');
		$vm->excerpt = (string) ($data['excerpt'] ?? '');
		$vm->date = (string) ($data['date'] ?? '');
		$vm->image = (string) ($data['image'] ?? '');
		$vm->template = (string) ($data['template'] ?? 'default');
		$vm->parent_id = (int) ($data['parent_id'] ?? 0);
		$vm->menu_order = (int) ($data['menu_order'] ?? 0);
		return $vm;
	}

	/**
	 * Convert to array for template rendering.
	 */
	public function to_array(): array {
		return [
			'id' => $this->id,
			'title' => $this->title,
			'slug' => $this->slug,
			'url' => $this->permalink,
			'content' => $this->content,
			'excerpt' => $this->excerpt,
			'date' => $this->date,
			'image' => $this->image,
			'template' => $this->template,
			'parent_id' => $this->parent_id,
			'menu_order' => $this->menu_order,
		];
	}
}
