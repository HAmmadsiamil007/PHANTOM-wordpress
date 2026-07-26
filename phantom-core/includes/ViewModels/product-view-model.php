<?php
namespace PhantomCore\ViewModels;

use PhantomCore\Contracts\ViewModelInterface;

final class Product_ViewModel implements ViewModelInterface {
	public int $id;
	public string $title;
	public string $slug;
	public string $permalink;
	public string $description;
	public string $short_description;
	public string $price;
	public string $regular_price;
	public string $sale_price;
	public string $currency;
	public string $image;
	public array $gallery;
	public string $sku;
	public string $stock_status;
	public bool $in_stock;
	public string $type;
	public string $add_to_cart_text;
	public string $add_to_cart_url;
	public array $categories;
	public array $tags;
	public array $attributes;
	public array $variations;
	public float $rating;
	public int $review_count;
	public string $badge;
}
