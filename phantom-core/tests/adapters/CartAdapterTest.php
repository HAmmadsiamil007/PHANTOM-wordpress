<?php
declare(strict_types=1);

use PhantomCore\Adapters\Cart_Adapter;

class CartAdapterTest extends \WP_UnitTestCase {

  private Cart_Adapter $adapter;

  public function setUp(): void {
    parent::setUp();
    $this->adapter = new Cart_Adapter();
  }

  public function test_normalize_returns_empty_for_no_woocommerce(): void {
    $result = $this->adapter->normalize(null);
    $this->assertTrue($result['is_empty']);
    $this->assertCount(0, $result['items']);
  }

  public function test_normalize_returns_expected_keys(): void {
    $result = $this->adapter->normalize(null);
    $expected_keys = [
      'items', 'items_count', 'subtotal', 'subtotal_tax',
      'total', 'total_formatted', 'total_tax',
      'shipping_total', 'shipping_tax',
      'needs_shipping', 'needs_payment',
      'coupons', 'is_empty', 'currency', 'currency_symbol',
    ];
    foreach ($expected_keys as $key) {
      $this->assertArrayHasKey($key, $result);
    }
  }

  public function test_normalize_collection_returns_array(): void {
    $result = $this->adapter->normalize_collection([]);
    $this->assertIsArray($result);
  }

  public function test_empty_response_has_safe_defaults(): void {
    $result = $this->adapter->normalize(null);
    $this->assertSame('USD', $result['currency']);
    $this->assertSame('$', $result['currency_symbol']);
    $this->assertSame(0, $result['items_count']);
  }
}
