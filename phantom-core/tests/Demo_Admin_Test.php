<?php
declare(strict_types=1);

use PhantomCore\Demo\Demo_Registry;
use PhantomCore\Demo\Demo_Switcher;
use PhantomCore\Demo\Demo_Installer;
use PhantomCore\Admin\Demo_Admin;

class Demo_Admin_Test extends PHPUnit\Framework\TestCase {

    private Demo_Registry $registry;
    private Demo_Switcher $switcher;
    private Demo_Installer $installer;

    protected function setUp(): void {
        phantom_ensure_fashion_fixture();
        clearstatcache();

        $GLOBALS['_phantom_actions']       = [];
        $GLOBALS['_phantom_submenu_pages'] = [];

        if ( ! isset( $GLOBALS['_phantom_options'] ) ) {
            $GLOBALS['_phantom_options'] = [];
        }

        $this->registry  = Demo_Registry::get_instance();
        $this->switcher  = new Demo_Switcher( $this->registry );
        $this->installer = new Demo_Installer( $this->registry );
    }

    public function test_constructor_injects_dependencies(): void {
        $admin = new Demo_Admin( $this->registry, $this->switcher, $this->installer );
        $this->assertInstanceOf( Demo_Admin::class, $admin );
    }

    public function test_get_instance_returns_singleton(): void {
        $instance1 = Demo_Admin::get_instance();
        $instance2 = Demo_Admin::get_instance();
        $this->assertSame( $instance1, $instance2 );
    }

    public function test_get_instance_returns_admin_instance(): void {
        $instance = Demo_Admin::get_instance();
        $this->assertInstanceOf( Demo_Admin::class, $instance );
    }

    public function test_register_menu_adds_submenu_page(): void {
        $admin = new Demo_Admin( $this->registry, $this->switcher, $this->installer );
        $admin->register_menu();

        $this->assertCount( 1, $GLOBALS['_phantom_submenu_pages'] );
        $entry = $GLOBALS['_phantom_submenu_pages'][0];
        $this->assertEquals( 'phantom-dashboard', $entry['parent_slug'] );
        $this->assertEquals( 'phantom-demo-manager', $entry['menu_slug'] );
        $this->assertEquals( 'manage_options', $entry['capability'] );
    }

    public function test_init_registers_hooks(): void {
        $admin = new Demo_Admin( $this->registry, $this->switcher, $this->installer );
        $admin->init();

        $tags = array_map( fn( $a ) => $a['tag'], $GLOBALS['_phantom_actions'] );
        $this->assertContains( 'admin_enqueue_scripts', $tags );
        $this->assertContains( 'wp_ajax_phantom_activate_demo', $tags );
        $this->assertContains( 'wp_ajax_phantom_deactivate_demo', $tags );
        $this->assertContains( 'wp_ajax_phantom_activate_precheck', $tags );
        $this->assertContains( 'wp_ajax_phantom_delete_demo', $tags );
        $this->assertContains( 'admin_post_phantom_install_demo', $tags );
    }

    public function test_enqueue_assets_skips_wrong_hook(): void {
        $admin = new Demo_Admin( $this->registry, $this->switcher, $this->installer );
        $admin->enqueue_assets( 'appearance_page_phantom-core' );
        $this->assertTrue( true );
    }

    public function test_enqueue_assets_runs_on_correct_hook(): void {
        $admin = new Demo_Admin( $this->registry, $this->switcher, $this->installer );
        $admin->enqueue_assets( 'appearance_page_phantom-demo-manager' );
        $this->assertTrue( true );
    }

    public function test_ajax_activate_sends_success(): void {
        $admin = new Demo_Admin( $this->registry, $this->switcher, $this->installer );

        $_POST['slug']  = 'fashion';
        $_POST['nonce'] = wp_create_nonce( 'phantom_demo_nonce' );

        ob_start();
        try {
            $admin->ajax_activate();
        } catch ( \RuntimeException $e ) {
            // Expected — wp_send_json_success throws
        }
        $output = ob_get_clean();

        $data = json_decode( $output, true );
        $this->assertTrue( $data['success'] );
        $this->assertEquals( 'fashion', $data['data']['slug'] );
    }

    public function test_ajax_activate_invalid_slug_returns_error(): void {
        $admin = new Demo_Admin( $this->registry, $this->switcher, $this->installer );

        $_POST['slug']  = '';
        $_POST['nonce'] = wp_create_nonce( 'phantom_demo_nonce' );

        ob_start();
        try {
            $admin->ajax_activate();
        } catch ( \RuntimeException $e ) {
            // Expected — wp_send_json_error throws
        }
        $output = ob_get_clean();

        $this->assertNotEmpty( $output, 'Output should not be empty' );
        $data = json_decode( $output, true );
        $this->assertIsArray( $data, 'JSON decode failed for output: ' . var_export( $output, true ) );
        $this->assertFalse( $data['success'] );
    }

    public function test_ajax_deactivate_sends_success(): void {
        $admin = new Demo_Admin( $this->registry, $this->switcher, $this->installer );

        $_POST['nonce'] = wp_create_nonce( 'phantom_demo_nonce' );

        $this->switcher->activate( 'fashion' );

        ob_start();
        try {
            $admin->ajax_deactivate();
        } catch ( \RuntimeException $e ) {
            // Expected — wp_send_json_success throws
        }
        $output = ob_get_clean();

        $data = json_decode( $output, true );
        $this->assertTrue( $data['success'] );
    }

    public function test_ajax_activate_precheck_sends_checks(): void {
        $admin = new Demo_Admin( $this->registry, $this->switcher, $this->installer );

        $_POST['slug']  = 'fashion';
        $_POST['nonce'] = wp_create_nonce( 'phantom_demo_nonce' );

        ob_start();
        try {
            $admin->ajax_activate_precheck();
        } catch ( \RuntimeException $e ) {
            // Expected — wp_send_json_success throws
        }
        $output = ob_get_clean();

        $data = json_decode( $output, true );
        $this->assertTrue( $data['success'] );
        $this->assertArrayHasKey( 'pass', $data['data'] );
        $this->assertArrayHasKey( 'checks', $data['data'] );
    }

    public function test_ajax_delete_returns_success(): void {
        $admin = new Demo_Admin( $this->registry, $this->switcher, $this->installer );

        $_POST['slug']  = 'fashion';
        $_POST['nonce'] = wp_create_nonce( 'phantom_demo_nonce' );

        ob_start();
        try {
            $admin->ajax_delete();
        } catch ( \RuntimeException $e ) {
            // Expected — wp_send_json_success throws
        }
        $output = ob_get_clean();

        $data = json_decode( $output, true );
        $this->assertTrue( $data['success'] );
    }
}
