<?php
use PHPUnit\Framework\TestCase;
use PhantomCore\Admin\ImportExportPage;

class Design_Import_Export_Test extends TestCase {
    private ImportExportPage $page;

    protected function setUp(): void {
        $this->page = ImportExportPage::get_instance();
    }

    public function test_singleton(): void {
        $this->assertSame($this->page, ImportExportPage::get_instance());
    }

    public function test_render_outputs_export_section(): void {
        $this->expectOutputRegex('/Export Current Design/');
        $this->page->render();
    }

    public function test_render_outputs_import_section(): void {
        $this->expectOutputRegex('/Import Preset/');
        $this->page->render();
    }
}
