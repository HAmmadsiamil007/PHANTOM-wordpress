<?php
declare(strict_types=1);

namespace PhantomCore\ViewModels;

use PhantomCore\Contracts\ViewModelInterface;

defined('ABSPATH') || exit;

/**
 * Post_ViewModel transforms post data into a typed view-model object.
 * Bridge between data sources and template renderers.
 */
final class Post_ViewModel implements ViewModelInterface {
	public int $id;
	public string $title;
	public string $slug;
	public string $permalink;
	public string $excerpt;
	public string $content;
	public string $date;
	public string $image;
	public string $author;
	public array $categories;
	public array $tags;

	/**
	 * Create from raw post data array.
	 */
	public static function from_adapter_output(array $data): self {
		$vm = new self();
		$vm->id = (int) ($data['id'] ?? 0);
		$vm->title = (string) ($data['title'] ?? '');
		$vm->slug = (string) ($data['slug'] ?? '');
		$vm->permalink = (string) ($data['url'] ?? '');
		$vm->excerpt = (string) ($data['excerpt'] ?? '');
		$vm->content = (string) ($data['content'] ?? '');
		$vm->date = (string) ($data['date'] ?? '');
		$vm->image = (string) ($data['image'] ?? '');
		$vm->author = (string) ($data['author'] ?? '');
		$vm->categories = (array) ($data['categories'] ?? []);
		$vm->tags = (array) ($data['tags'] ?? []);
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
			'excerpt' => $this->excerpt,
			'content' => $this->content,
			'date' => $this->date,
			'image' => $this->image,
			'author' => $this->author,
			'categories' => $this->categories,
			'tags' => $this->tags,
		];
	}
}
