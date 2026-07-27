<?php
declare(strict_types=1);

namespace PhantomCore\Admin;

defined('ABSPATH') || exit;

class PhantomAdmin {
    private static ?self $instance = null;
    private array $pages = [];

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init(): void {
        add_action('admin_menu', [$this, 'register_menu'], 5);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function register_menu(): void {
        add_menu_page(
            __('PHANTOM', 'phantom-core'),
            __('PHANTOM', 'phantom-core'),
            'manage_options',
            'phantom-dashboard',
            [$this, 'render_dashboard'],
            'dashicons-star-filled',
            3
        );

        $this->pages = [
            'phantom-dashboard'        => ['title' => __('Dashboard', 'phantom-core'), 'render' => [$this, 'render_dashboard']],
            'phantom-design-studio'    => ['title' => __('Design Studio', 'phantom-core'), 'render' => [$this, 'render_design_studio']],
            'phantom-component-library'=> ['title' => __('Component Library', 'phantom-core'), 'render' => [$this, 'render_component_library']],
            'phantom-template-manager' => ['title' => __('Template Manager', 'phantom-core'), 'render' => [$this, 'render_template_manager']],
            'phantom-animation-studio' => ['title' => __('Animation Studio', 'phantom-core'), 'render' => [$this, 'render_animation_studio']],
            'phantom-asset-manager'    => ['title' => __('Asset Manager', 'phantom-core'), 'render' => [$this, 'render_asset_manager']],
            'phantom-performance'      => ['title' => __('Performance', 'phantom-core'), 'render' => [$this, 'render_performance']],
            'phantom-seo'              => ['title' => __('SEO', 'phantom-core'), 'render' => [$this, 'render_seo']],
            'phantom-theme-options'    => ['title' => __('Theme Options', 'phantom-core'), 'render' => [$this, 'render_theme_options']],
            'phantom-demo-manager'     => ['title' => __('Demo Manager', 'phantom-core'), 'render' => [$this, 'render_demo_manager']],
            'phantom-font-download'    => ['title' => __('Download Fonts', 'phantom-core'), 'render' => [$this, 'render_font_download']],
            'phantom-import-export'    => ['title' => __('Import / Export', 'phantom-core'), 'render' => [$this, 'render_import_export']],
            'phantom-backup-restore'   => ['title' => __('Backup & Restore', 'phantom-core'), 'render' => [$this, 'render_backup_restore']],
            'phantom-developer'        => ['title' => __('Developer', 'phantom-core'), 'render' => [$this, 'render_developer']],
            'phantom-system'           => ['title' => __('System', 'phantom-core'), 'render' => [$this, 'render_system']],
        ];

        foreach ($this->pages as $slug => $page) {
            if ('phantom-dashboard' === $slug) continue;
            add_submenu_page(
                'phantom-dashboard',
                $page['title'],
                $page['title'],
                'manage_options',
                $slug,
                $page['render']
            );
        }
    }

    public function enqueue_assets(string $hook): void {
        if (!str_starts_with($hook, 'toplevel_page_phantom') && !str_contains($hook, '_page_phantom')) {
            return;
        }
        wp_enqueue_style('phantom-admin', PHANTOM_CORE_URL . 'admin/css/admin.css', [], PHANTOM_CORE_VERSION);
        wp_enqueue_style('phantom-design-studio', PHANTOM_CORE_URL . 'admin/css/design-studio.css', ['phantom-admin'], PHANTOM_CORE_VERSION);
        wp_enqueue_script('phantom-design-studio', PHANTOM_CORE_URL . 'admin/js/design-studio.js', ['jquery'], PHANTOM_CORE_VERSION, true);
        wp_localize_script('phantom-design-studio', 'phantomDesign', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('phantom_design_nonce'),
        ]);
    }

    public function render_dashboard(): void {
        DashboardPage::get_instance()->render();
    }

    public function render_design_studio(): void {
        DesignStudioPage::get_instance()->render();
    }

    public function render_component_library(): void {
        ComponentLibraryPage::get_instance()->render();
    }

    public function render_template_manager(): void {
        TemplateManagerPage::get_instance()->render();
    }

    public function render_animation_studio(): void {
        AnimationStudioPage::get_instance()->render();
    }

    public function render_asset_manager(): void {
        AssetManagerPage::get_instance()->render();
    }

    public function render_performance(): void {
        PerformancePage::get_instance()->render();
    }

    public function render_seo(): void {
        SEOPage::get_instance()->render();
    }

    public function render_theme_options(): void {
        Settings_Page::get_instance()->render_page();
    }

    public function render_demo_manager(): void {
        Demo_Admin::get_instance()->render_page();
    }

    public function render_font_download(): void {
        \Phantom_Font_Download_Page::instance()->render_page();
    }

    public function render_import_export(): void {
        ImportExportPage::get_instance()->render();
    }

    public function render_backup_restore(): void {
        BackupRestorePage::get_instance()->render();
    }

    public function render_developer(): void {
        DeveloperPage::get_instance()->render();
    }

    public function render_system(): void {
        SystemPage::get_instance()->render();
    }
}
