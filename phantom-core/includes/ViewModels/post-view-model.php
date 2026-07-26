<?php
namespace PhantomCore\ViewModels;

use PhantomCore\Contracts\ViewModelInterface;

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
}
