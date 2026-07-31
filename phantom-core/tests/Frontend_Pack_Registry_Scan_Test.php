<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PhantomCore\Packs\Frontend_Pack;
use PhantomCore\Packs\Frontend_Pack_Registry;

class Frontend_Pack_Registry_Scan_Test extends TestCase {
    private Frontend_Pack_Registry $registry;

    protected function setUp(): void {
        $this->registry = Frontend_Pack_Registry::get_instance();
        unset($GLOBALS['_phantom_options']['phantom_template_pack']);
    }

    public function test_registry_is_singleton(): void {
        $this->assertSame($this->registry, Frontend_Pack_Registry::get_instance());
    }

    public function test_scan_finds_all_bundled_packs(): void {
        $this->registry->scan();
        $this->assertTrue($this->registry->has('dark'));
        $this->assertTrue($this->registry->has('minimal'));
        $this->assertTrue($this->registry->has('bold'));
        $this->assertSame(3, $this->registry->count());
    }

    public function test_scan_parses_manifest_fields(): void {
        $this->registry->scan();
        $pack = $this->registry->get('dark');
        $this->assertInstanceOf(Frontend_Pack::class, $pack);
        $this->assertSame('Dark', $pack->name);
        $this->assertSame('1.0.0', $pack->version);
        $this->assertSame('#6C63FF', $pack->settings['primary_color']);
        $this->assertSame(12, $pack->templates['override_count']);
        $this->assertSame(['frontend/packs/dark/assets/css/pack.css'], $pack->assets['css']);
    }

    public function test_bundled_packs_marked_builtin(): void {
        $this->registry->scan();
        foreach (['dark', 'minimal', 'bold'] as $slug) {
            $this->assertTrue($this->registry->get($slug)->builtin, "{$slug} should be builtin");
        }
    }

    public function test_active_flag_reflects_option(): void {
        update_option('phantom_template_pack', 'dark');
        $this->registry->scan();
        $this->assertTrue($this->registry->get('dark')->active);
        $this->assertFalse($this->registry->get('bold')->active);
    }

    public function test_active_none_when_default_option(): void {
        $this->registry->scan();
        foreach (['dark', 'minimal', 'bold'] as $slug) {
            $this->assertFalse($this->registry->get($slug)->active);
        }
    }

    public function test_get_unknown_returns_null(): void {
        $this->registry->scan();
        $this->assertNull($this->registry->get('nope'));
        $this->assertFalse($this->registry->has('nope'));
    }

    public function test_get_active_returns_null_for_default(): void {
        $this->registry->scan();
        $this->assertNull($this->registry->get_active());
        $this->assertSame('default', $this->registry->get_active_slug());
    }

    public function test_get_active_returns_pack_when_set(): void {
        update_option('phantom_template_pack', 'minimal');
        $this->registry->scan();
        $this->assertSame('minimal', $this->registry->get_active_slug());
        $this->assertSame('Minimal', $this->registry->get_active()->name);
    }

    public function test_get_pack_list_shape(): void {
        $this->registry->scan();
        $list = $this->registry->get_pack_list();
        $this->assertArrayHasKey('dark', $list);
        $this->assertSame('dark', $list['dark']['slug']);
        $this->assertTrue($list['dark']['builtin']);
        $this->assertArrayHasKey('name', $list['dark']);
        $this->assertArrayHasKey('settings', $list['dark']);
        $this->assertArrayHasKey('assets', $list['dark']);
        $this->assertArrayHasKey('active', $list['dark']);
    }

    public function test_get_display_names(): void {
        $this->registry->scan();
        $names = $this->registry->get_display_names();
        $this->assertSame('Dark', $names['dark']);
        $this->assertSame('Minimal', $names['minimal']);
        $this->assertSame('Bold', $names['bold']);
    }

    public function test_scan_missing_base_yields_empty(): void {
        $this->registry->scan(sys_get_temp_dir() . '/phantom-no-such-packs-dir-' . uniqid());
        $this->assertSame(0, $this->registry->count());
    }

    public function test_scan_skips_dirs_without_manifest(): void {
        $base = sys_get_temp_dir() . '/phantom-packs-' . uniqid();
        @mkdir($base . '/no-manifest-dir', 0777, true);
        @mkdir($base . '/good-pack', 0777, true);
        file_put_contents(
            $base . '/good-pack/manifest.json',
            '{"name":"Good","version":"1.0.0","description":"","author":""}'
        );
        file_put_contents($base . '/junk-file.txt', 'not a dir');
        try {
            $this->registry->scan($base);
            $this->assertTrue($this->registry->has('good-pack'));
            $this->assertFalse($this->registry->has('no-manifest-dir'));
            $this->assertSame(1, $this->registry->count());
        } finally {
            $this->remove_tree($base);
        }
    }

    public function test_scan_skips_invalid_json_manifests(): void {
        $base = sys_get_temp_dir() . '/phantom-packs-' . uniqid();
        @mkdir($base . '/broken-pack', 0777, true);
        file_put_contents($base . '/broken-pack/manifest.json', '{not valid json');
        try {
            $this->registry->scan($base);
            $this->assertFalse($this->registry->has('broken-pack'));
            $this->assertSame(0, $this->registry->count());
        } finally {
            $this->remove_tree($base);
        }
    }

    public function test_custom_pack_not_marked_builtin(): void {
        $base = sys_get_temp_dir() . '/phantom-packs-' . uniqid();
        @mkdir($base . '/custom-pack', 0777, true);
        file_put_contents(
            $base . '/custom-pack/manifest.json',
            '{"name":"Custom","version":"1.0.0","description":"","author":""}'
        );
        try {
            $this->registry->scan($base);
            $this->assertTrue($this->registry->has('custom-pack'));
            $this->assertFalse($this->registry->get('custom-pack')->builtin);
        } finally {
            $this->remove_tree($base);
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
