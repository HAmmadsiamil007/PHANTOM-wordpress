<?php
declare(strict_types=1);

namespace PhantomCore\ViewModels;

use PhantomCore\Contracts\ViewModelInterface;
use PhantomCore\Adapters\Product_Adapter;

/**
 * Product_ViewModel transforms normalized product data into a typed
 * view-model object that template renderers consume.
 *
 * This is the bridge between Adapters (raw data) and Renderers (HTML output).
 * Adapters normalize the source → ViewModel validates/structures → Renderer outputs HTML.
 */
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

	/**
	 * Create a Product_ViewModel from raw data (array from Adapter).
	 */
	public static function from_adapter_output(array $data): self {
		$vm = new self();
		$vm->id = (int) ($data['id'] ?? 0);
		$vm->title = (string) ($data['name'] ?? '');
		$vm->slug = (string) ($data['slug'] ?? '');
		$vm->permalink = (string) ($data['url'] ?? '');
		$vm->description = (string) ($data['description'] ?? '');
		$vm->short_description = (string) ($data['short_description'] ?? '');
		$vm->price = (string) ($data['price'] ?? '');
		$vm->regular_price = (string) ($data['regular_price'] ?? '');
		$vm->sale_price = (string) ($data['sale_price'] ?? '');
		$vm->currency = (string) ($data['currency'] ?? 'USD');
		$vm->image = (string) ($data['image'] ?? '');
		$vm->gallery = (array) ($data['gallery'] ?? []);
		$vm->sku = (string) ($data['sku'] ?? '');
		$vm->stock_status = $data['in_stock'] ? 'instock' : 'outofstock';
		$vm->in_stock = (bool) ($data['in_stock'] ?? false);
		$vm->type = (string) ($data['type'] ?? 'simple');
		$vm->add_to_cart_text = $vm->in_stock ? 'Add to Cart' : 'Read More';
		$vm->add_to_cart_url = $vm->permalink;
		$vm->categories = (array) ($data['categories'] ?? []);
		$vm->tags = (array) ($data['tags'] ?? []);
		$vm->attributes = (array) ($data['variation_attributes'] ?? []);
		$vm->variations = (array) ($data['variations'] ?? []);
		$vm->rating = (float) ($data['rating'] ?? 0);
		$vm->review_count = (int) ($data['reviews_count'] ?? 0);
		$vm->badge = $data['on_sale'] ? 'Sale' : ($data['is_featured'] ? 'New' : '');
		return $vm;
	}

	/**
	 * Create a Product_ViewModel directly from a WC_Product object.
	 * Uses Product_Adapter internally.
	 */
	public static function from_wc_product(\WC_Product $product): self {
		$adapter = new Product_Adapter();
		$data = $adapter->normalize($product);
		return self::from_adapter_output($data);
	}

	/**
	 * Get a formatted price display string.
	 */
	public function formatted_price(): string {
		if ($this->sale_price && $this->sale_price !== $this->regular_price) {
			return '<span class="price-sale">' . wp_kses_post($this->sale_price) . '</span> <span class="price-original">' . wp_kses_post($this->regular_price) . '</span>';
		}
		return wp_kses_post($this->price);
	}

	/**
	 * Get rating stars HTML.
	 */
	public function rating_stars(): string {
		if ($this->rating <= 0) return '';
		$full = (int) floor($this->rating);
		$stars = '';
		for ($i = 0; $i < 5; $i++) {
			$stars .= $i < $full ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
		}
		return '<div class="product-rating">' . $stars . ' <span>(' . $this->review_count . ' reviews)</span></div>';
	}

	/**
	 * Get gallery HTML.
	 */
	public function gallery_html(): string {
		if (empty($this->gallery)) {
			return '<img src="' . esc_url($this->image) . '" alt="' . esc_attr($this->title) . '" class="product-main-image">';
		}
		$html = '<div class="product-gallery swiper" id="productGallery"><div class="swiper-wrapper">';
		foreach ($this->gallery as $img) {
			$html .= '<div class="swiper-slide"><img src="' . esc_url($img) . '" alt="' . esc_attr($this->title) . '" loading="lazy"></div>';
		}
		$html .= '</div><div class="swiper-pagination"></div></div>';
		return $html;
	}

	/**
	 * Get gallery thumbnail HTML (for thumbnails Swiper).
	 */
	public function gallery_thumbnails_html(): string {
		if (empty($this->gallery)) {
			return '';
		}
		$html = '';
		foreach ($this->gallery as $i => $img) {
			$active = $i === 0 ? ' pd-thumb-active' : '';
			$html .= '<div class="swiper-slide' . $active . '">';
			$html .= '<img loading="lazy" src="' . esc_url($img) . '" alt="' . esc_attr($this->title . ' Thumbnail ' . ($i + 1)) . '">';
			$html .= '</div>';
		}
		return $html;
	}

	/**
	 * Convert to array for template rendering.
	 */
	public function to_array(): array {
		return [
			'id' => $this->id,
			'name' => $this->title,
			'slug' => $this->slug,
			'url' => $this->permalink,
			'image' => $this->image,
			'gallery' => $this->gallery,
			'price' => $this->formatted_price(),
			'regular_price' => $this->regular_price,
			'sale_price' => $this->sale_price,
			'on_sale' => !empty($this->sale_price) && $this->sale_price !== $this->regular_price,
			'is_featured' => $this->badge === 'New',
			'in_stock' => $this->in_stock,
			'stock_status' => $this->stock_status,
			'rating' => $this->rating,
			'reviews_count' => $this->review_count,
			'sku' => $this->sku,
			'categories' => $this->categories,
			'tags' => $this->tags,
			'type' => $this->type,
			'short_description' => $this->short_description,
			'description' => $this->description,
			'variations' => $this->variations,
			'variation_attributes' => $this->attributes,
			'badge' => $this->badge,
		];
	}
}
