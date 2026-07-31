<?php
use PHPUnit\Framework\TestCase;
use PhantomCore\Admin\PhantomAdmin;
use PhantomCore\Admin\DashboardPage;
use PhantomCore\Admin\ComponentLibraryPage;
use PhantomCore\Admin\ImportExportPage;

class Design_Admin_Menu_Test extends TestCase {
    private PhantomAdmin $admin;

    protected function setUp(): void {
        $GLOBALS['_phantom_actions'] = [];
        $GLOBALS['_phantom_submenu_pages'] = [];
        $this->admin = PhantomAdmin::get_instance();
    }

    public function test_singleton(): void {
        $this->assertSame($this->admin, PhantomAdmin::get_instance());
    }

    public function test_register_menu_adds_top_level(): void {
        $this->admin->register_menu();
        $this->assertNotEmpty($GLOBALS['_phantom_submenu_pages']);
    }

    public function test_dashboard_page_singleton(): void {
        $this->assertInstanceOf(DashboardPage::class, DashboardPage::get_instance());
    }

    public function test_menu_has_no_design_studio_tab(): void {
        $this->admin->register_menu();
        foreach ($GLOBALS['_phantom_submenu_pages'] as $page) {
            $this->assertNotSame('phantom-design-studio', $page['slug'] ?? $page[0] ?? '');
        }
    }

    public function test_component_library_page_singleton(): void {
        $this->assertInstanceOf(ComponentLibraryPage::class, ComponentLibraryPage::get_instance());
    }

    public function test_import_export_page_singleton(): void {
        $this->assertInstanceOf(ImportExportPage::class, ImportExportPage::get_instance());
    }

    public function test_dashboard_page_render_outputs(): void {
        $page = DashboardPage::get_instance();
        $this->expectOutputRegex('/PHANTOM Dashboard/');
        $page->render();
    }

    public function test_import_export_page_render_outputs(): void {
        $page = ImportExportPage::get_instance();
        $this->expectOutputRegex('/Import \/ Export/');
        $page->render();
    }
}
