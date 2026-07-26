<?php
namespace PhantomCore\ViewModels;

use PhantomCore\Contracts\ViewModelInterface;

final class Category_ViewModel implements ViewModelInterface {
	public int $id;
	public string $name;
	public string $slug;
	public string $permalink;
	public string $description;
	public string $image;
	public int $count;
}
