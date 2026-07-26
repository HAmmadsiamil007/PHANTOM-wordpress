<?php
declare(strict_types=1);

use PhantomCore\Adapters\Product_Adapter;

class ProductAdapterTest extends \WP_UnitTestCase {

  private Product_Adapter $adapter;

  public function setUp(): void {
    parent::setUp();
    $this->adapter = new Product_Adapter();
  }

  public function test_normalize_returns_empty_array_for_invalid_input(): void {
    $result = $this->adapter->normalize(0);
    $this->assertEquals(0, $result['id']);
    $this->assertEquals('', $result['name']);
  }

  public function test_normalize_returns_expected_keys(): void {
    $product = $this->factory->product->create_and_get(['name' => 'Test Product']);
    $result = $this->adapter->normalize($product);
    $this->assertArrayHasKey('name', $result);
    $this->assertArrayHasKey('price', $result);
    $this->assertArrayHasKey('url', $result);
    $this->assertArrayHasKey('image', $result);
    $this->assertEquals('Test Product', $result['name']);
  }

  public function test_normalize_collection(): void {
    $products = $this->factory->product->create_many(3);
    $result = $this->adapter->normalize_collection($products);
    $this->assertCount(3, $result);
  }

  public function test_normalize_includes_gallery(): void {
    $product = $this->factory->product->create_and_get();
    $result = $this->adapter->normalize($product);
    $this->assertIsArray($result['gallery']);
    $this->assertIsArray($result['categories']);
    $this->assertIsArray($result['tags']);
  }

  public function test_normalize_includes_rating(): void {
    $product = $this->factory->product->create_and_get();
    $result = $this->adapter->normalize($product);
    $this->assertIsNumeric($result['rating']);
    $this->assertIsInt($result['reviews_count']);
  }
}
