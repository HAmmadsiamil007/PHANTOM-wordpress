<?php
use PHPUnit\Framework\TestCase;
use PhantomCore\Engine\Data_Engine;
use PhantomCore\Engine\Template_Loader;

class Data_Engine_Test extends TestCase {
    private Data_Engine $engine;

    protected function setUp(): void {
        $this->engine = new Data_Engine(new Template_Loader());
    }

    public function test_with_product_id_sets_and_returns(): void {
        $this->engine->with_product_id(42);
        $this->assertSame(42, $this->engine->get_resolved_product_id());
    }

    public function test_with_post_id_sets_and_returns(): void {
        $this->engine->with_post_id(99);
        $this->assertSame(99, $this->engine->get_resolved_post_id());
    }

    public function test_with_category_sets_and_returns(): void {
        $this->engine->with_category('t-shirts');
        $this->assertSame('t-shirts', $this->engine->get_category_slug());
    }

    public function test_default_ids_are_null(): void {
        $this->assertNull($this->engine->get_resolved_product_id());
        $this->assertNull($this->engine->get_resolved_post_id());
        $this->assertNull($this->engine->get_category_slug());
    }

    public function test_with_product_id_is_fluent(): void {
        $result = $this->engine->with_product_id(7);
        $this->assertSame($this->engine, $result);
    }

    public function test_with_post_id_is_fluent(): void {
        $result = $this->engine->with_post_id(7);
        $this->assertSame($this->engine, $result);
    }

    public function test_with_category_is_fluent(): void {
        $result = $this->engine->with_category('test');
        $this->assertSame($this->engine, $result);
    }

    public function test_get_template_loader_returns_instance(): void {
        $this->assertInstanceOf(Template_Loader::class, $this->engine->get_template_loader());
    }

    public function test_get_template_loader_is_consistent(): void {
        $this->assertSame(
            $this->engine->get_template_loader(),
            $this->engine->get_template_loader()
        );
    }

    public function test_with_methods_chain(): void {
        $this->engine
            ->with_product_id(1)
            ->with_post_id(2)
            ->with_category('books');
        $this->assertSame(1, $this->engine->get_resolved_product_id());
        $this->assertSame(2, $this->engine->get_resolved_post_id());
        $this->assertSame('books', $this->engine->get_category_slug());
    }

    public function test_bridge_data_contains_expected_keys(): void {
        $data = $this->engine->get_bridge_data();
        $this->assertArrayHasKey('rest_url', $data);
        $this->assertArrayHasKey('home_url', $data);
        $this->assertArrayHasKey('nonce', $data);
        $this->assertArrayHasKey('api_nonce', $data);
        $this->assertArrayHasKey('ajax_url', $data);
        $this->assertArrayHasKey('wc_ajax_url', $data);
        $this->assertArrayHasKey('user_logged_in', $data);
        $this->assertArrayHasKey('routes', $data);
        $this->assertArrayHasKey('theme', $data);
    }

    public function test_bridge_data_routes(): void {
        $data = $this->engine->get_bridge_data();
        $this->assertArrayHasKey('shop', $data['routes']);
        $this->assertArrayHasKey('cart', $data['routes']);
        $this->assertArrayHasKey('checkout', $data['routes']);
        $this->assertArrayHasKey('account', $data['routes']);
    }

    public function test_bridge_data_theme(): void {
        $data = $this->engine->get_bridge_data();
        $this->assertArrayHasKey('name', $data['theme']);
        $this->assertArrayHasKey('version', $data['theme']);
    }

    public function test_bridge_data_includes_product_id_when_set(): void {
        $this->engine->with_product_id(42);
        $data = $this->engine->get_bridge_data();
        $this->assertArrayHasKey('product_id', $data);
        $this->assertSame(42, $data['product_id']);
    }

    public function test_bridge_data_includes_post_id_when_set(): void {
        $this->engine->with_post_id(99);
        $data = $this->engine->get_bridge_data();
        $this->assertArrayHasKey('post_id', $data);
        $this->assertSame(99, $data['post_id']);
    }

    public function test_bridge_data_includes_category_when_set(): void {
        $this->engine->with_category('t-shirts');
        $data = $this->engine->get_bridge_data();
        $this->assertArrayHasKey('category', $data);
        $this->assertSame('t-shirts', $data['category']);
    }

    public function test_bridge_data_no_ids_by_default(): void {
        $data = $this->engine->get_bridge_data();
        $this->assertArrayNotHasKey('product_id', $data);
        $this->assertArrayNotHasKey('post_id', $data);
        $this->assertArrayNotHasKey('category', $data);
    }

    public function test_get_auth_nonces_returns_expected_keys(): void {
        $nonces = $this->engine->get_auth_nonces();
        $this->assertArrayHasKey('wp_rest', $nonces);
        $this->assertArrayHasKey('phantom_api', $nonces);
        $this->assertArrayHasKey('woocommerce_cart', $nonces);
    }

    public function test_get_customizer_css_returns_empty_string(): void {
        $this->assertSame('', $this->engine->get_customizer_css());
    }
}
