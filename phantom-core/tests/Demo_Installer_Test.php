<?php
use PHPUnit\Framework\TestCase;
use PhantomCore\Demo\Demo_Registry;
use PhantomCore\Demo\Demo_Installer;

class Demo_Installer_Test extends TestCase {
    private Demo_Installer $installer;
    private string $temp_dir;

    protected function setUp(): void {
        $this->installer = new Demo_Installer(new Demo_Registry());
        $this->temp_dir = sys_get_temp_dir() . '/phantom-test-' . uniqid();
        mkdir($this->temp_dir, 0755, true);
    }

    protected function tearDown(): void {
        $this->rmdir_recursive($this->temp_dir);
    }

    public function test_validate_zip_invalid_file_returns_failure(): void {
        $result = $this->installer->validate_zip('/nonexistent/file.zip');
        $this->assertFalse($result->success);
    }

    public function test_validate_zip_not_a_zip_returns_failure(): void {
        $file = $this->temp_dir . '/not-a-zip.zip';
        file_put_contents($file, 'not a zip content');
        $result = $this->installer->validate_zip($file);
        $this->assertFalse($result->success);
    }

    public function test_validate_zip_missing_manifest_returns_failure(): void {
        $zip_path = $this->temp_dir . '/no-manifest.zip';
        $zip = new ZipArchive();
        $zip->open($zip_path, ZipArchive::CREATE);
        $zip->addFromString('some-file.txt', 'content');
        $zip->close();

        $result = $this->installer->validate_zip($zip_path);
        $this->assertFalse($result->success);
    }

    public function test_validate_zip_valid_returns_success(): void {
        $zip_path = $this->temp_dir . '/valid-demo.zip';
        $zip = new ZipArchive();
        $zip->open($zip_path, ZipArchive::CREATE);
        $zip->addFromString('demo.json', json_encode([
            'name' => 'Test Demo',
            'slug' => 'test-demo',
            'version' => '1.0.0',
        ]));
        $zip->addFromString('html/index.html', '<h1>Test</h1>');
        $zip->close();

        $result = $this->installer->validate_zip($zip_path);
        $this->assertTrue($result->success);
        $this->assertSame('test-demo', $result->data['slug']);
    }

    public function test_get_target_path_returns_correct_path(): void {
        $path = $this->installer->get_target_path('test-demo');
        $this->assertStringContainsString('frontend/templates/test-demo', $path);
    }

    public function test_delete_nonexistent_demo_returns_failure(): void {
        $result = $this->installer->delete('nonexistent-demo-xyz');
        $this->assertFalse($result->success);
    }

    public function test_delete_kids_returns_failure(): void {
        $result = $this->installer->delete('kids');
        $this->assertFalse($result->success);
    }

    public function test_install_nonexistent_file_returns_failure(): void {
        $result = $this->installer->install('/nonexistent/file.zip');
        $this->assertFalse($result->success);
    }

    private function rmdir_recursive(string $dir): void {
        if (!is_dir($dir)) {
            return;
        }
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->rmdir_recursive($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }
}
