<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PhantomCore\Packs\Pack_Rest;

class Pack_Rest_Test extends TestCase {
    private Pack_Rest $rest;

    protected function setUp(): void {
        $this->rest = Pack_Rest::get_instance();
        $GLOBALS['_phantom_rest_routes'] = [];
        unset($GLOBALS['_phantom_options']['phantom_template_pack']);
    }

    public function test_route_specs_contains_four_routes(): void {
        $specs = Pack_Rest::get_route_specs();
        $this->assertCount(4, $specs);

        $by_route = [];
        foreach ($specs as $spec) {
            $by_route[$spec['method'] . ' ' . $spec['route']] = $spec;
        }
        $this->assertArrayHasKey('GET /packs', $by_route);
        $this->assertArrayHasKey('POST /packs/activate', $by_route);
        $this->assertArrayHasKey('POST /packs/install', $by_route);
        $this->assertArrayHasKey('POST /packs/uninstall', $by_route);
    }

    public function test_spec_callbacks_and_permissions(): void {
        $specs = Pack_Rest::get_route_specs();
        $get = $specs[0];
        $activate = $specs[1];
        $install = $specs[2];
        $uninstall = $specs[3];

        $this->assertSame('get_packs', $get['callback']);
        $this->assertSame('public_read', $get['permission']);

        $this->assertSame('activate', $activate['callback']);
        $this->assertSame('edit_theme_options_cap', $activate['permission']);
        $this->assertTrue($activate['args']['slug']['required']);

        $this->assertSame('install', $install['callback']);
        $this->assertSame('manage_options_cap', $install['permission']);

        $this->assertSame('uninstall', $uninstall['callback']);
        $this->assertSame('manage_options_cap', $uninstall['permission']);
        $this->assertTrue($uninstall['args']['slug']['required']);
        $this->assertFalse($uninstall['args']['force']['default']);
    }

    public function test_register_routes_registers_all_four(): void {
        $this->rest->register_routes();
        $routes = $GLOBALS['_phantom_rest_routes'];
        $this->assertCount(4, $routes);

        $paths = [];
        foreach ($routes as $entry) {
            $this->assertSame('phantom/v1', $entry['namespace']);
            $paths[] = strtoupper($entry['args']['methods']) . ' ' . $entry['route'];
        }
        sort($paths);
        $this->assertSame(
            ['GET /packs', 'POST /packs/activate', 'POST /packs/install', 'POST /packs/uninstall'],
            $paths
        );
    }

    public function test_register_routes_skips_existing_route(): void {
        $GLOBALS['_phantom_rest_routes'][] = [
            'namespace' => 'phantom/v1',
            'route' => '/packs',
            'args' => ['methods' => 'GET'],
        ];
        $this->rest->register_routes();
        $this->assertCount(4, $GLOBALS['_phantom_rest_routes']);
    }

    public function test_get_packs_returns_superset_shape(): void {
        $response = $this->rest->get_packs();
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertSame('default', $data['active']);

        $packs = $data['packs'];
        $this->assertIsArray($packs);
        $slugs = array_column($packs, 'slug');
        $this->assertContains('dark', $slugs);
        $this->assertContains('minimal', $slugs);
        $this->assertContains('bold', $slugs);

        foreach ($packs as $pack) {
            $this->assertArrayHasKey('name', $pack);
            $this->assertArrayHasKey('version', $pack);
            $this->assertArrayHasKey('settings', $pack);
            $this->assertArrayHasKey('builtin', $pack);
            $this->assertArrayHasKey('active', $pack);
        }
        $dark = $packs[array_search('dark', $slugs, true)];
        $this->assertTrue($dark['builtin']);
        $this->assertFalse($dark['active']);
    }

    public function test_get_packs_active_flag(): void {
        update_option('phantom_template_pack', 'dark');
        $data = $this->rest->get_packs()->get_data();
        $this->assertSame('dark', $data['active']);
        $dark = $data['packs'][array_search('dark', array_column($data['packs'], 'slug'), true)];
        $this->assertTrue($dark['active']);
    }

    public function test_activate_unknown_pack_returns_wp_error(): void {
        $request = new WP_REST_Request('POST');
        $request->set_param('slug', 'ghost-pack');
        $result = $this->rest->activate($request);
        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('pack_missing', $result->get_error_code());
    }

    public function test_uninstall_unknown_pack_returns_wp_error(): void {
        $request = new WP_REST_Request('POST');
        $request->set_param('slug', 'ghost-pack');
        $request->set_param('force', false);
        $result = $this->rest->uninstall($request);
        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('pack_missing', $result->get_error_code());
    }

    public function test_install_without_file_returns_upload_error(): void {
        $_FILES = [];
        $request = new WP_REST_Request('POST');
        $result = $this->rest->install($request);
        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('upload_error', $result->get_error_code());
    }

    public function test_install_with_failed_upload_returns_upload_error(): void {
        $_FILES = ['file' => ['error' => UPLOAD_ERR_INI_SIZE, 'tmp_name' => '']];
        $request = new WP_REST_Request('POST');
        $result = $this->rest->install($request);
        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('upload_error', $result->get_error_code());
    }

    public function test_install_with_non_zip_file_returns_zip_invalid(): void {
        $tmp = sys_get_temp_dir() . '/notzip-' . uniqid() . '.zip';
        file_put_contents($tmp, 'plain text');
        try {
            $_FILES = ['file' => ['error' => UPLOAD_ERR_OK, 'tmp_name' => $tmp, 'name' => 'x.zip', 'size' => 10]];
            $request = new WP_REST_Request('POST');
            $result = $this->rest->install($request);
            $this->assertInstanceOf(WP_Error::class, $result);
            $this->assertSame('zip_invalid', $result->get_error_code());
        } finally {
            @unlink($tmp);
        }
    }

    private function remove_tree(string $dir): void {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir . '/' . $entry;
            if (is_dir($path)) {
                $this->remove_tree($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}
