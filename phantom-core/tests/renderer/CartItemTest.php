<?php
declare(strict_types=1);

use PhantomCore\Renderer\CartItem;

class CartItemTest extends Component_Renderer_Test_Base {

  public function test_render_returns_html(): void {
    $component = new CartItem();
    $html = $component->render(['title' => 'Cart Item']);
    $this->assertStringContainsString('cart-item', $html);
    $this->assertStringContainsString('Cart Item', $html);
  }

  public function test_render_collection_returns_multiple(): void {
    $component = new CartItem();
    $html = $component->render_collection([
      ['title' => 'One'],
      ['title' => 'Two'],
    ]);
    $this->assertEquals(2, substr_count($html, 'cart-item'));
  }
}
