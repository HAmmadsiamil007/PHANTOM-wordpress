<?php
declare(strict_types=1);

require_once __DIR__ . '/ComponentRendererTestBase.php';
require_once dirname(__DIR__, 2) . '/includes/contracts/interface-renderer.php';
require_once dirname(__DIR__, 2) . '/includes/renderer/class-component-renderer.php';
require_once dirname(__DIR__, 2) . '/includes/renderer/class-cart-item.php';

use PhantomCore\Renderer\Cart_Item;

class Cart_Item_Test extends Component_Renderer_Test_Base {

  public function test_render_returns_html(): void {
    $component = new Cart_Item();
    $html = $component->render(['title' => 'Cart Item']);
    $this->assertStringContainsString('cart-item', $html);
    $this->assertStringContainsString('Cart Item', $html);
  }

  public function test_render_collection_returns_multiple(): void {
    $component = new Cart_Item();
    $html = $component->render_collection([
      ['title' => 'One'],
      ['title' => 'Two'],
    ]);
    $this->assertEquals(2, substr_count($html, '<tr class="cart-item"'));
  }
}
