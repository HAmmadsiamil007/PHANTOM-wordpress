<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PhantomCore\Packs\Frontend_Pack;
use PhantomCore\Packs\Frontend_Pack_Registry;

class Frontend_Pack_Registry_Install_Test extends TestCase {
    private Frontend_Pack_Registry $registry;
    private string $base;

    protected function setUp(): void {
        $this->registry = Frontend_Pack_Registry::get_instance();
        $this->base = sys_get_temp_dir() . '/phantom-packs-' . uniqid('', true);
        @mkdir($this->base, 0777, true);
        unset($GLOBALS['_phantom_options']['phantom_template_pack']);
    }

    protected function tearDown(): void {
        $this->remove_tree($this->base);
    }

    private function make_zip(string $zip_path, array $files): void {
        if (!class_exists('ZipArchive')) {
            $this->markTestSkipped('ZipArchive extension not available.');
        }
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE));
        foreach ($files as $name => $content) {
            $zip->addFromString($name, $content);
        }
        $zip->close();
    }

    private function valid_manifest(string $slug = 'custom-pack'): string {
        return json_encode([
            'slug' => $slug,
            'name' => 'Custom Pack',
            'version' => '1.0.0',
            'description' => 'Test pack',
            'author' => 'Tests',
            'settings' => ['custom_key_xyz' => 'abc'],
            'templates' => ['override_count' => 2, 'base' => 'frontend/packs/custom-pack/html/'],
            'assets' => ['css' => [], 'js' => []],
        ], JSON_PRETTY_PRINT) ?: '{}';
    }

    // ── validate_slug ─────────────────────────────────────────────────────

    public function test_validate_slug_accepts_valid(): void {
        $this->assertNull($this->registry->validate_slug('dark'));
        $this->assertNull($this->registry->validate_slug('my-pack'));
        $this->assertNull($this->registry->validate_slug('a-b-c'));
        $this->assertNull($this->registry->validate_slug(str_repeat('a', 32)));
    }

    public function test_validate_slug_rejects_invalid(): void {
        $this->assertSame('invalid_slug', $this->registry->validate_slug(''));
        $this->assertSame('invalid_slug', $this->registry->validate_slug('Dark'));
        $this->assertSame('invalid_slug', $this->registry->validate_slug('a'));
        $this->assertSame('invalid_slug', $this->registry->validate_slug('my pack'));
        $this->assertSame('invalid_slug', $this->registry->validate_slug('my_pack'));
        $this->assertSame('invalid_slug', $this->registry->validate_slug(str_repeat('a', 33)));
    }

    // ── install_zip guards ────────────────────────────────────────────────

    public function test_install_missing_file_returns_zip_failed(): void {
        $result = $this->registry->install_zip($this->base . '/nope.zip', $this->base);
        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('zip_failed', $result->get_error_code());
    }

    public function test_install_non_zip_file_returns_zip_invalid(): void {
        $path = $this->base . '/fake.zip';
        file_put_contents($path, 'this is not a zip');
        $result = $this->registry->install_zip($path, $this->base);
        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('zip_invalid', $result->get_error_code());
    }

    public function test_install_zip_without_manifest_returns_manifest_missing(): void {
        $path = $this->base . '/nomanifest.zip';
        $this->make_zip($path, ['readme.txt' => 'no manifest here']);
        $result = $this->registry->install_zip($path, $this->base);
        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('manifest_missing', $result->get_error_code());
    }

    public function test_install_invalid_manifest_json_returns_manifest_invalid(): void {
        $path = $this->base . '/badmanifest.zip';
        $this->make_zip($path, ['manifest.json' => '{broken']);
        $result = $this->registry->install_zip($path, $this->base);
        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('manifest_invalid', $result->get_error_code());
    }

    public function test_install_zip_with_traversal_entry_is_rejected(): void {
        $path = $this->base . '/evil.zip';
        $this->make_zip($path, [
            'manifest.json' => $this->valid_manifest(),
            'evil/../evil.txt' => 'pwned',
        ]);
        $result = $this->registry->install_zip($path, $this->base);
        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('zip_invalid', $result->get_error_code());
        $this->assertFileDoesNotExist($this->base . '/evil.txt');
        $this->assertFalse($this->registry->has('custom-pack'));
    }

    public function test_install_zip_with_absolute_entry_is_rejected(): void {
        $path = $this->base . '/evil2.zip';
        $this->make_zip($path, [
            'manifest.json' => $this->valid_manifest(),
            '/etc/evil.txt' => 'pwned',
        ]);
        $result = $this->registry->install_zip($path, $this->base);
        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('zip_invalid', $result->get_error_code());
    }

    public function test_install_invalid_slug_in_manifest(): void {
        $path = $this->base . '/badslug.zip';
        $manifest = json_decode($this->valid_manifest(), true);
        $manifest['slug'] = 'BAD SLUG!';
        $this->make_zip($path, ['manifest.json' => json_encode($manifest)]);
        $result = $this->registry->install_zip($path, $this->base);
        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('invalid_slug', $result->get_error_code());
    }

    public function test_install_root_manifest_without_slug_is_invalid(): void {
        $path = $this->base . '/noslug.zip';
        $manifest = json_decode($this->valid_manifest(), true);
        unset($manifest['slug']);
        $this->make_zip($path, ['manifest.json' => json_encode($manifest)]);
        $result = $this->registry->install_zip($path, $this->base);
        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('invalid_slug', $result->get_error_code());
    }

    public function test_install_duplicate_returns_pack_exists(): void {
        $path = $this->base . '/dup.zip';
        $this->make_zip($path, ['manifest.json' => $this->valid_manifest()]);
        $first = $this->registry->install_zip($path, $this->base);
        $this->assertInstanceOf(Frontend_Pack::class, $first);
        $second = $this->registry->install_zip($path, $this->base);
        $this->assertInstanceOf(WP_Error::class, $second);
        $this->assertSame('pack_exists', $second->get_error_code());
    }

    // ── install success paths ─────────────────────────────────────────────

    public function test_install_root_manifest_pack(): void {
        $path = $this->base . '/good.zip';
        $this->make_zip($path, [
            'manifest.json' => $this->valid_manifest(),
            'html/index.html' => '<div>custom</div>',
        ]);
        $pack = $this->registry->install_zip($path, $this->base);
        $this->assertInstanceOf(Frontend_Pack::class, $pack);
        $this->assertSame('custom-pack', $pack->slug);
        $this->assertSame('Custom Pack', $pack->name);
        $this->assertTrue($this->registry->has('custom-pack'));
        $this->assertFileExists($this->base . '/custom-pack/manifest.json');
        $this->assertFileExists($this->base . '/custom-pack/html/index.html');
        $this->assertFileDoesNotExist($this->base . '/custom-pack/html/manifest.json');
    }

    public function test_install_nested_single_dir_pack(): void {
        $path = $this->base . '/nested.zip';
        $this->make_zip($path, [
            'my-pack/manifest.json' => $this->valid_manifest('my-pack'),
            'my-pack/html/index.html' => '<div>nested</div>',
        ]);
        $pack = $this->registry->install_zip($path, $this->base);
        $this->assertInstanceOf(Frontend_Pack::class, $pack);
        $this->assertSame('my-pack', $pack->slug);
        $this->assertTrue($this->registry->has('my-pack'));
        $this->assertFileExists($this->base . '/my-pack/html/index.html');
    }

    public function test_install_manifest_slug_overrides_nested_dir_name(): void {
        $path = $this->base . '/renamed.zip';
        $manifest = json_decode($this->valid_manifest('ignored-dir-name'), true);
        $manifest['slug'] = 'renamed-pack';
        $this->make_zip($path, [
            'ignored-dir-name/manifest.json' => json_encode($manifest),
            'ignored-dir-name/html/index.html' => '<div>renamed</div>',
        ]);
        $pack = $this->registry->install_zip($path, $this->base);
        $this->assertInstanceOf(Frontend_Pack::class, $pack);
        $this->assertSame('renamed-pack', $pack->slug);
        $this->assertTrue($this->registry->has('renamed-pack'));
        $this->assertFalse($this->registry->has('ignored-dir-name'));
    }

    public function test_install_nested_with_extra_root_file_is_sanitized(): void {
        $path = $this->base . '/messy.zip';
        $this->make_zip($path, [
            'pack-a/manifest.json' => $this->valid_manifest('pack-a'),
            'pack-a/html/index.html' => '<div>a</div>',
            'stray-notes.txt' => 'should not leak into pack',
        ]);
        $pack = $this->registry->install_zip($path, $this->base);
        $this->assertInstanceOf(Frontend_Pack::class, $pack);
        $this->assertFileDoesNotExist($this->base . '/pack-a/stray-notes.txt');
    }

    // ── uninstall ─────────────────────────────────────────────────────────

    public function test_uninstall_missing_pack(): void {
        $result = $this->registry->uninstall('ghost-pack', false, $this->base);
        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('pack_missing', $result->get_error_code());
    }

    public function test_uninstall_active_pack_requires_force(): void {
        $path = $this->base . '/active.zip';
        $this->make_zip($path, ['manifest.json' => $this->valid_manifest('active-pack')]);
        $this->registry->install_zip($path, $this->base);
        update_option('phantom_template_pack', 'active-pack');
        $result = $this->registry->uninstall('active-pack', false, $this->base);
        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('pack_active', $result->get_error_code());
        $this->assertTrue($this->registry->has('active-pack'));
    }

    public function test_uninstall_builtin_requires_force(): void {
        $path = $this->base . '/dark.zip';
        $this->make_zip($path, ['manifest.json' => $this->valid_manifest('dark')]);
        $this->registry->install_zip($path, $this->base);
        $this->assertTrue($this->registry->get('dark')->builtin);
        $result = $this->registry->uninstall('dark', false, $this->base);
        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('builtin', $result->get_error_code());
    }

    public function test_force_uninstall_builtin(): void {
        $path = $this->base . '/dark.zip';
        $this->make_zip($path, ['manifest.json' => $this->valid_manifest('dark')]);
        $this->registry->install_zip($path, $this->base);
        $result = $this->registry->uninstall('dark', true, $this->base);
        $this->assertTrue($result);
        $this->assertFalse($this->registry->has('dark'));
        $this->assertDirectoryDoesNotExist($this->base . '/dark');
    }

    public function test_force_uninstall_active_resets_option(): void {
        $path = $this->base . '/active2.zip';
        $this->make_zip($path, ['manifest.json' => $this->valid_manifest('active-pack')]);
        $this->registry->install_zip($path, $this->base);
        update_option('phantom_template_pack', 'active-pack');
        $result = $this->registry->uninstall('active-pack', true, $this->base);
        $this->assertTrue($result);
        $this->assertSame('default', get_option('phantom_template_pack', 'default'));
    }

    public function test_uninstall_custom_pack(): void {
        $path = $this->base . '/custom.zip';
        $this->make_zip($path, ['manifest.json' => $this->valid_manifest('custom-pack')]);
        $this->registry->install_zip($path, $this->base);
        $result = $this->registry->uninstall('custom-pack', false, $this->base);
        $this->assertTrue($result);
        $this->assertFalse($this->registry->has('custom-pack'));
    }

    // ── activate + apply_pack_settings ────────────────────────────────────

    public function test_activate_unknown_pack(): void {
        $result = $this->registry->activate('ghost-pack', $this->base);
        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('pack_missing', $result->get_error_code());
    }

    public function test_activate_sets_option_and_applies_settings(): void {
        $path = $this->base . '/custom.zip';
        $this->make_zip($path, ['manifest.json' => $this->valid_manifest('custom-pack')]);
        $this->registry->install_zip($path, $this->base);

        $result = $this->registry->activate('custom-pack', $this->base);
        $this->assertTrue($result);
        $this->assertSame('custom-pack', get_option('phantom_template_pack', ''));
        $this->assertSame('abc', get_option('phantom_custom_key_xyz', ''));
    }

    public function test_apply_pack_settings_uses_token_option_key(): void {
        $path = $this->base . '/token.zip';
        $manifest = json_decode($this->valid_manifest('token-pack'), true);
        $manifest['settings'] = ['color.primary' => '#123456'];
        $this->make_zip($path, ['manifest.json' => json_encode($manifest)]);
        $this->registry->install_zip($path, $this->base);

        $applied = $this->registry->apply_pack_settings('token-pack');
        $this->assertSame(1, $applied);
        $this->assertSame('#123456', get_option('phantom_color_primary', ''));
    }

    public function test_apply_pack_settings_returns_count(): void {
        $path = $this->base . '/multi.zip';
        $manifest = json_decode($this->valid_manifest('multi-pack'), true);
        $manifest['settings'] = [
            'custom_key_xyz' => 'abc',
            'another_key' => 'def',
        ];
        $this->make_zip($path, ['manifest.json' => json_encode($manifest)]);
        $this->registry->install_zip($path, $this->base);

        $applied = $this->registry->apply_pack_settings('multi-pack');
        $this->assertSame(2, $applied);
        $this->assertSame('abc', get_option('phantom_custom_key_xyz', ''));
        $this->assertSame('def', get_option('phantom_another_key', ''));
    }

    public function test_apply_pack_settings_unknown_pack_returns_zero(): void {
        $this->assertSame(0, $this->registry->apply_pack_settings('ghost-pack'));
    }

    public function test_apply_pack_settings_empty_settings_returns_zero(): void {
        $path = $this->base . '/empty.zip';
        $manifest = json_decode($this->valid_manifest('empty-pack'), true);
        $manifest['settings'] = [];
        $this->make_zip($path, ['manifest.json' => json_encode($manifest)]);
        $this->registry->install_zip($path, $this->base);
        $this->assertSame(0, $this->registry->apply_pack_settings('empty-pack'));
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
