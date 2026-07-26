<?php
declare(strict_types=1);

use PhantomCore\Renderer\Product_Card;

class ProductCardTest extends \WP_UnitTestCase {

  public function test_render_returns_product_card_html(): void {
    $card = new Product_Card();
    $data = [
      'name' => 'Test Shoe',
      'url' => '/product/test-shoe',
      'image' => 'https://example.com/shoe.jpg',
      'price' => '$99.00',
      'on_sale' => false,
      'rating' => 4.5,
      'reviews_count' => 10,
    ];
    $html = $card->render($data);
    $this->assertStringContainsString('product-card', $html);
    $this->assertStringContainsString('Test Shoe', $html);
    $this->assertStringContainsString('$99.00', $html);
  }

  public function test_render_collection_returns_multiple_cards(): void {
    $card = new Product_Card();
    $html = $card->render_collection([
      ['name' => 'A', 'url' => '#', 'image' => '', 'price' => '$10'],
      ['name' => 'B', 'url' => '#', 'image' => '', 'price' => '$20'],
    ]);
    $this->assertStringContainsString('product-card', $html);
    $this->assertEquals(2, substr_count($html, 'product-card'));
  }

  public function test_render_with_sale_badge(): void {
    $card = new Product_Card();
    $data = [
      'name' => 'Sale Item', 'url' => '#', 'image' => '', 'price' => '$50',
      'on_sale' => true, 'sale_price' => '$40', 'regular_price' => '$50',
    ];
    $html = $card->render($data);
    $this->assertStringContainsString('badge-sale', $html);
    $this->assertStringContainsString('price-sale', $html);
  }

  public function test_render_with_featured_badge(): void {
    $card = new Product_Card();
    $data = [
      'name' => 'Featured', 'url' => '#', 'image' => '', 'price' => '$30',
      'on_sale' => false, 'is_featured' => true,
    ];
    $html = $card->render($data);
    $this->assertStringContainsString('badge-new', $html);
  }
}
