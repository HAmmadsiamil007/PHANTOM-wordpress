<?php
/**
 * Bridge system tests for Phantom Core.
 *
 * Covers Bridge_Manager singleton, Plugin_Bridge abstract base,
 * and all 8 concrete bridge implementations.
 * Tests are standalone (no WordPress plugins installed) using bootstrap stubs.
 */

use PHPUnit\Framework\TestCase;
use PhantomCore\Contracts\BridgeInterface;
use PhantomCore\Bridges\Bridge_Manager;
use PhantomCore\Bridges\Plugin_Bridge;
use PhantomCore\Bridges\WooCommerce_Bridge;
use PhantomCore\Bridges\Wishlist_Bridge;
use PhantomCore\Bridges\Mailchimp_Bridge;
use PhantomCore\Bridges\Swiper_Bridge;
use PhantomCore\Compatibility\Gutenberg_Bridge;
use PhantomCore\Compatibility\Elementor_Bridge;
use PhantomCore\Compatibility\WPML_Bridge;
use PhantomCore\Compatibility\RankMath_Bridge;
use PhantomCore\Compatibility\Yoast_Bridge;
use PhantomCore\Compatibility\CF7_Bridge;

class Bridge_Test extends TestCase {

    private Bridge_Manager $manager;

    public static function setUpBeforeClass(): void {
        require_once PHANTOM_CORE_PATH . 'includes/Registry/class-asset-registry.php';
        require_once PHANTOM_CORE_PATH . 'includes/contracts/interface-bridge.php';
        require_once PHANTOM_CORE_PATH . 'includes/Bridges/class-plugin-bridge.php';
        require_once PHANTOM_CORE_PATH . 'includes/Bridges/class-bridge-manager.php';
        require_once PHANTOM_CORE_PATH . 'includes/Bridges/class-woocommerce-bridge.php';
        require_once PHANTOM_CORE_PATH . 'includes/Bridges/class-wishlist-bridge.php';
        require_once PHANTOM_CORE_PATH . 'includes/Bridges/class-mailchimp-bridge.php';
        require_once PHANTOM_CORE_PATH . 'includes/Bridges/class-swiper-bridge.php';
        require_once PHANTOM_CORE_PATH . 'includes/Compatibility/class-gutenberg-bridge.php';
        require_once PHANTOM_CORE_PATH . 'includes/Compatibility/class-elementor-bridge.php';
        require_once PHANTOM_CORE_PATH . 'includes/Compatibility/class-wpml-bridge.php';
        require_once PHANTOM_CORE_PATH . 'includes/Compatibility/class-rankmath-bridge.php';
        require_once PHANTOM_CORE_PATH . 'includes/Compatibility/class-yoast-bridge.php';
        require_once PHANTOM_CORE_PATH . 'includes/Compatibility/class-cf7-bridge.php';
    }

    protected function setUp(): void {
        $this->manager = new Bridge_Manager();
    }

    public function test_get_instance_returns_singleton(): void {
        $a = Bridge_Manager::get_instance();
        $b = Bridge_Manager::get_instance();
        $this->assertSame($a, $b);
    }

    public function test_bridge_manager_is_bridge_manager_instance(): void {
        $this->assertInstanceOf(Bridge_Manager::class, $this->manager);
    }

    public function test_register_adds_bridge(): void {
        $bridge = $this->createMock(Plugin_Bridge::class);
        $bridge->method('get_id')->willReturn('test-bridge');
        $this->manager->register($bridge);
        $this->assertCount(1, $this->manager->get_all());
    }

    public function test_get_returns_bridge_by_id(): void {
        $bridge = $this->createMock(Plugin_Bridge::class);
        $bridge->method('get_id')->willReturn('test-bridge');
        $this->manager->register($bridge);
        $result = $this->manager->get('test-bridge');
        $this->assertNotNull($result);
        $this->assertSame('test-bridge', $result->get_id());
    }

    public function test_get_returns_null_for_unknown_id(): void {
        $this->assertNull($this->manager->get('nonexistent-bridge'));
    }

    public function test_get_all_returns_all_registered_bridges(): void {
        $bridge1 = $this->createMock(Plugin_Bridge::class);
        $bridge1->method('get_id')->willReturn('bridge-1');
        $bridge2 = $this->createMock(Plugin_Bridge::class);
        $bridge2->method('get_id')->willReturn('bridge-2');
        $this->manager->register($bridge1);
        $this->manager->register($bridge2);
        $all = $this->manager->get_all();
        $this->assertCount(2, $all);
        $this->assertArrayHasKey('bridge-1', $all);
        $this->assertArrayHasKey('bridge-2', $all);
    }

    public function test_get_active_returns_only_active_bridges(): void {
        $activeBridge = $this->createMock(Plugin_Bridge::class);
        $activeBridge->method('get_id')->willReturn('active-bridge');
        $activeBridge->method('is_active')->willReturn(true);
        $inactiveBridge = $this->createMock(Plugin_Bridge::class);
        $inactiveBridge->method('get_id')->willReturn('inactive-bridge');
        $inactiveBridge->method('is_active')->willReturn(false);
        $this->manager->register($activeBridge);
        $this->manager->register($inactiveBridge);
        $active = $this->manager->get_active();
        $this->assertCount(1, $active);
        $this->assertArrayHasKey('active-bridge', $active);
    }

    public function test_is_bridge_active_returns_true_for_active_bridge(): void {
        $bridge = $this->createMock(Plugin_Bridge::class);
        $bridge->method('get_id')->willReturn('my-bridge');
        $bridge->method('is_active')->willReturn(true);
        $this->manager->register($bridge);
        $this->assertTrue($this->manager->is_bridge_active('my-bridge'));
    }

    public function test_is_bridge_active_returns_false_for_inactive_bridge(): void {
        $bridge = $this->createMock(Plugin_Bridge::class);
        $bridge->method('get_id')->willReturn('my-bridge');
        $bridge->method('is_active')->willReturn(false);
        $this->manager->register($bridge);
        $this->assertFalse($this->manager->is_bridge_active('my-bridge'));
    }

    public function test_is_bridge_active_returns_false_for_unregistered_bridge(): void {
        $this->assertFalse($this->manager->is_bridge_active('unregistered'));
    }

    public function test_init_all_calls_init_on_active_bridges_once(): void {
        $activeBridge = $this->createMock(Plugin_Bridge::class);
        $activeBridge->method('get_id')->willReturn('active-bridge');
        $activeBridge->method('is_active')->willReturn(true);
        $activeBridge->expects($this->once())->method('init');
        $inactiveBridge = $this->createMock(Plugin_Bridge::class);
        $inactiveBridge->method('get_id')->willReturn('inactive-bridge');
        $inactiveBridge->method('is_active')->willReturn(false);
        $inactiveBridge->expects($this->never())->method('init');
        $this->manager->register($activeBridge);
        $this->manager->register($inactiveBridge);
        $this->manager->init_all();
    }

    public function test_init_all_calls_init_on_all_active_bridges(): void {
        for ($i = 1; $i <= 3; $i++) {
            $bridge = $this->createMock(Plugin_Bridge::class);
            $bridge->method('get_id')->willReturn("bridge-$i");
            $bridge->method('is_active')->willReturn(true);
            $bridge->expects($this->once())->method('init');
            $this->manager->register($bridge);
        }
        $this->manager->init_all();
    }

    public function test_register_overwrites_duplicate_id(): void {
        $bridge1 = $this->createMock(Plugin_Bridge::class);
        $bridge1->method('get_id')->willReturn('dup-bridge');
        $bridge1->method('is_active')->willReturn(false);
        $bridge2 = $this->createMock(Plugin_Bridge::class);
        $bridge2->method('get_id')->willReturn('dup-bridge');
        $bridge2->method('is_active')->willReturn(false);
        $this->manager->register($bridge1);
        $this->manager->register($bridge2);
        $this->assertCount(1, $this->manager->get_all());
        $this->assertSame($bridge2, $this->manager->get('dup-bridge'));
    }

    public function test_plugin_bridge_anonymous_class_implements_interface(): void {
        $bridge = new class extends Plugin_Bridge {
            public function __construct() {
                $this->id = 'test-id';
                $this->label = 'Test Label';
            }
            public function is_active(): bool { return true; }
            public function init(): void {}
        };
        $this->assertInstanceOf(BridgeInterface::class, $bridge);
    }

    public function test_plugin_bridge_get_id_returns_id(): void {
        $bridge = new class extends Plugin_Bridge {
            public function __construct() {
                $this->id = 'my-plugin';
                $this->label = 'My Plugin';
            }
            public function is_active(): bool { return true; }
            public function init(): void {}
        };
        $this->assertSame('my-plugin', $bridge->get_id());
    }

    public function test_plugin_bridge_get_label_returns_label(): void {
        $bridge = new class extends Plugin_Bridge {
            public function __construct() {
                $this->id = 'my-plugin';
                $this->label = 'My Plugin';
            }
            public function is_active(): bool { return true; }
            public function init(): void {}
        };
        $this->assertSame('My Plugin', $bridge->get_label());
    }

    public function test_plugin_bridge_get_supported_hooks_returns_array(): void {
        $bridge = new class extends Plugin_Bridge {
            protected array $supported_hooks = ['init', 'wp_enqueue_scripts'];
            public function __construct() {
                $this->id = 'my-plugin';
                $this->label = 'My Plugin';
            }
            public function is_active(): bool { return true; }
            public function init(): void {}
        };
        $hooks = $bridge->get_supported_hooks();
        $this->assertIsArray($hooks);
        $this->assertContains('init', $hooks);
        $this->assertContains('wp_enqueue_scripts', $hooks);
    }

    public function test_plugin_bridge_default_supported_hooks_is_empty(): void {
        $bridge = new class extends Plugin_Bridge {
            public function __construct() {
                $this->id = 'minimal';
                $this->label = 'Minimal';
            }
            public function is_active(): bool { return false; }
            public function init(): void {}
        };
        $this->assertEmpty($bridge->get_supported_hooks());
    }

    public function test_woocommerce_bridge_get_id(): void {
        $bridge = new WooCommerce_Bridge();
        $this->assertSame('woocommerce', $bridge->get_id());
    }

    public function test_woocommerce_bridge_get_label(): void {
        $bridge = new WooCommerce_Bridge();
        $this->assertSame('WooCommerce', $bridge->get_label());
    }

    public function test_woocommerce_bridge_is_active_returns_false_without_plugin(): void {
        $bridge = new WooCommerce_Bridge();
        $this->assertFalse($bridge->is_active());
    }

    public function test_woocommerce_bridge_get_supported_hooks(): void {
        $bridge = new WooCommerce_Bridge();
        $hooks = $bridge->get_supported_hooks();
        $this->assertIsArray($hooks);
        $this->assertContains('woocommerce_init', $hooks);
        $this->assertContains('woocommerce_loaded', $hooks);
    }

    public function test_wishlist_bridge_get_id(): void {
        $bridge = new Wishlist_Bridge();
        $this->assertSame('wishlist', $bridge->get_id());
    }

    public function test_wishlist_bridge_get_label(): void {
        $bridge = new Wishlist_Bridge();
        $this->assertSame('YITH WooCommerce Wishlist', $bridge->get_label());
    }

    public function test_wishlist_bridge_is_active_returns_false_without_plugin(): void {
        $bridge = new Wishlist_Bridge();
        $this->assertFalse($bridge->is_active());
    }

    public function test_wishlist_bridge_get_supported_hooks(): void {
        $bridge = new Wishlist_Bridge();
        $hooks = $bridge->get_supported_hooks();
        $this->assertIsArray($hooks);
        $this->assertContains('init', $hooks);
        $this->assertContains('wp_enqueue_scripts', $hooks);
    }

    public function test_mailchimp_bridge_get_id(): void {
        $bridge = new Mailchimp_Bridge();
        $this->assertSame('mailchimp', $bridge->get_id());
    }

    public function test_mailchimp_bridge_get_label(): void {
        $bridge = new Mailchimp_Bridge();
        $this->assertSame('Mailchimp for WP', $bridge->get_label());
    }

    public function test_mailchimp_bridge_is_active_returns_false_without_plugin(): void {
        $bridge = new Mailchimp_Bridge();
        $this->assertFalse($bridge->is_active());
    }

    public function test_mailchimp_bridge_get_supported_hooks(): void {
        $bridge = new Mailchimp_Bridge();
        $hooks = $bridge->get_supported_hooks();
        $this->assertIsArray($hooks);
        $this->assertContains('init', $hooks);
        $this->assertContains('wp_ajax_nopriv_phantom_mailchimp_subscribe', $hooks);
        $this->assertContains('wp_ajax_phantom_mailchimp_subscribe', $hooks);
    }

    public function test_swiper_bridge_get_id(): void {
        $bridge = new Swiper_Bridge();
        $this->assertSame('swiper', $bridge->get_id());
    }

    public function test_swiper_bridge_get_label(): void {
        $bridge = new Swiper_Bridge();
        $this->assertSame('Swiper', $bridge->get_label());
    }

    public function test_swiper_bridge_is_active_returns_false_without_assets(): void {
        $bridge = new Swiper_Bridge();
        $this->assertFalse($bridge->is_active());
    }

    public function test_swiper_bridge_get_supported_hooks(): void {
        $bridge = new Swiper_Bridge();
        $hooks = $bridge->get_supported_hooks();
        $this->assertIsArray($hooks);
        $this->assertContains('woocommerce_init', $hooks);
        $this->assertContains('wp_enqueue_scripts', $hooks);
    }

    public function test_gutenberg_bridge_get_id(): void {
        $bridge = new Gutenberg_Bridge();
        $this->assertSame('gutenberg', $bridge->get_id());
    }

    public function test_gutenberg_bridge_get_label(): void {
        $bridge = new Gutenberg_Bridge();
        $this->assertSame('Gutenberg', $bridge->get_label());
    }

    public function test_gutenberg_bridge_is_active_returns_false_without_plugin(): void {
        $bridge = new Gutenberg_Bridge();
        $this->assertFalse($bridge->is_active());
    }

    public function test_gutenberg_bridge_get_supported_hooks(): void {
        $bridge = new Gutenberg_Bridge();
        $hooks = $bridge->get_supported_hooks();
        $this->assertIsArray($hooks);
        $this->assertContains('enqueue_block_assets', $hooks);
        $this->assertContains('after_setup_theme', $hooks);
    }

    public function test_elementor_bridge_get_id(): void {
        $bridge = new Elementor_Bridge();
        $this->assertSame('elementor', $bridge->get_id());
    }

    public function test_elementor_bridge_get_label(): void {
        $bridge = new Elementor_Bridge();
        $this->assertSame('Elementor', $bridge->get_label());
    }

    public function test_elementor_bridge_is_active_returns_false_without_plugin(): void {
        $bridge = new Elementor_Bridge();
        $this->assertFalse($bridge->is_active());
    }

    public function test_elementor_bridge_get_supported_hooks(): void {
        $bridge = new Elementor_Bridge();
        $hooks = $bridge->get_supported_hooks();
        $this->assertIsArray($hooks);
        $this->assertContains('elementor/init', $hooks);
        $this->assertContains('elementor/widgets/register', $hooks);
    }

    public function test_wpml_bridge_get_id(): void {
        $bridge = new WPML_Bridge();
        $this->assertSame('wpml', $bridge->get_id());
    }

    public function test_wpml_bridge_get_label(): void {
        $bridge = new WPML_Bridge();
        $this->assertSame('WPML', $bridge->get_label());
    }

    public function test_wpml_bridge_is_active_returns_false_without_plugin(): void {
        $bridge = new WPML_Bridge();
        $this->assertFalse($bridge->is_active());
    }

    public function test_wpml_bridge_get_supported_hooks(): void {
        $bridge = new WPML_Bridge();
        $hooks = $bridge->get_supported_hooks();
        $this->assertIsArray($hooks);
        $this->assertContains('wpml_after_init', $hooks);
        $this->assertContains('wpml_language_switcher', $hooks);
    }

    public function test_rankmath_bridge_get_id(): void {
        $bridge = new RankMath_Bridge();
        $this->assertSame('rankmath', $bridge->get_id());
    }

    public function test_rankmath_bridge_get_label(): void {
        $bridge = new RankMath_Bridge();
        $this->assertSame('RankMath SEO', $bridge->get_label());
    }

    public function test_rankmath_bridge_is_active_returns_false_without_plugin(): void {
        $bridge = new RankMath_Bridge();
        $this->assertFalse($bridge->is_active());
    }

    public function test_rankmath_bridge_get_supported_hooks(): void {
        $bridge = new RankMath_Bridge();
        $hooks = $bridge->get_supported_hooks();
        $this->assertIsArray($hooks);
        $this->assertContains('rank_math/head', $hooks);
    }

    public function test_yoast_bridge_get_id(): void {
        $bridge = new Yoast_Bridge();
        $this->assertSame('yoast', $bridge->get_id());
    }

    public function test_yoast_bridge_get_label(): void {
        $bridge = new Yoast_Bridge();
        $this->assertSame('Yoast SEO', $bridge->get_label());
    }

    public function test_yoast_bridge_is_active_returns_false_without_plugin(): void {
        $bridge = new Yoast_Bridge();
        $this->assertFalse($bridge->is_active());
    }

    public function test_yoast_bridge_get_supported_hooks(): void {
        $bridge = new Yoast_Bridge();
        $hooks = $bridge->get_supported_hooks();
        $this->assertIsArray($hooks);
        $this->assertContains('wpseo_head', $hooks);
    }

    public function test_cf7_bridge_get_id(): void {
        $bridge = new CF7_Bridge();
        $this->assertSame('cf7', $bridge->get_id());
    }

    public function test_cf7_bridge_get_label(): void {
        $bridge = new CF7_Bridge();
        $this->assertSame('Contact Form 7', $bridge->get_label());
    }

    public function test_cf7_bridge_is_active_returns_false_without_plugin(): void {
        $bridge = new CF7_Bridge();
        $this->assertFalse($bridge->is_active());
    }

    public function test_cf7_bridge_get_supported_hooks(): void {
        $bridge = new CF7_Bridge();
        $hooks = $bridge->get_supported_hooks();
        $this->assertIsArray($hooks);
        $this->assertContains('wpcf7_init', $hooks);
        $this->assertContains('wpcf7_after_save', $hooks);
    }

    public function test_manager_with_all_bridges_registers_correctly(): void {
        $bridges = [
            new WooCommerce_Bridge(),
            new Wishlist_Bridge(),
            new Mailchimp_Bridge(),
            new Swiper_Bridge(),
            new Gutenberg_Bridge(),
            new Elementor_Bridge(),
            new WPML_Bridge(),
            new RankMath_Bridge(),
            new Yoast_Bridge(),
            new CF7_Bridge(),
        ];
        foreach ($bridges as $bridge) {
            $this->manager->register($bridge);
        }
        $all = $this->manager->get_all();
        $this->assertCount(10, $all);
        $this->assertArrayHasKey('woocommerce', $all);
        $this->assertArrayHasKey('wishlist', $all);
        $this->assertArrayHasKey('mailchimp', $all);
        $this->assertArrayHasKey('swiper', $all);
        $this->assertArrayHasKey('gutenberg', $all);
        $this->assertArrayHasKey('elementor', $all);
        $this->assertArrayHasKey('wpml', $all);
        $this->assertArrayHasKey('rankmath', $all);
        $this->assertArrayHasKey('yoast', $all);
        $this->assertArrayHasKey('cf7', $all);
    }

    public function test_manager_get_active_with_no_active_bridges_returns_empty(): void {
        $bridges = [
            new WooCommerce_Bridge(),
            new Wishlist_Bridge(),
            new Mailchimp_Bridge(),
        ];
        foreach ($bridges as $bridge) {
            $this->manager->register($bridge);
        }
        $this->assertEmpty($this->manager->get_active());
    }

    public function test_bridge_interface_contract(): void {
        $bridge = new WooCommerce_Bridge();
        $this->assertInstanceOf(BridgeInterface::class, $bridge);
        $this->assertIsString($bridge->get_id());
        $this->assertIsString($bridge->get_label());
        $this->assertIsBool($bridge->is_active());
        $this->assertIsArray($bridge->get_supported_hooks());
    }

    public function test_all_bridges_return_non_empty_id_and_label(): void {
        $bridges = [
            new WooCommerce_Bridge(),
            new Wishlist_Bridge(),
            new Mailchimp_Bridge(),
            new Swiper_Bridge(),
            new Gutenberg_Bridge(),
            new Elementor_Bridge(),
            new WPML_Bridge(),
            new RankMath_Bridge(),
            new Yoast_Bridge(),
            new CF7_Bridge(),
        ];
        foreach ($bridges as $bridge) {
            $this->assertNotEmpty($bridge->get_id(), get_class($bridge) . ' should have a non-empty id');
            $this->assertNotEmpty($bridge->get_label(), get_class($bridge) . ' should have a non-empty label');
            $this->assertIsBool($bridge->is_active(), get_class($bridge) . '::is_active() should return bool');
            $this->assertIsArray($bridge->get_supported_hooks(), get_class($bridge) . '::get_supported_hooks() should return array');
        }
    }
}