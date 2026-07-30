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
        add_action('wp_ajax_phantom_apply_preset', [$this, 'ajax_apply_preset']);
    }

    public function ajax_apply_preset(): void {
        if (!current_user_can('manage_options') || !wp_verify_nonce(wp_unslash($_POST['_wpnonce'] ?? ''), 'phantom_design_nonce')) {
            wp_send_json_error('Security check failed.');
        }
        $presetId = sanitize_text_field(wp_unslash($_POST['preset_id'] ?? ''));
        if (empty($presetId)) {
            wp_send_json_error('No preset ID provided.');
        }
        $result = \PhantomCore\Design\DesignSystemManager::get_instance()->applyPreset($presetId);
        if ($result) {
            wp_send_json_success(['preset' => $presetId]);
        } else {
            wp_send_json_error('Failed to apply preset: ' . $presetId);
        }
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
            'phantom-design-system'  => ['title' => __('Design System', 'phantom-core'), 'render' => [$this, 'render_design_system']],
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

        // Design Studio assets are enqueued by DesignStudioPage::enqueue_assets.
        // Only enqueue design-studio CSS here as shared dependency for other phantom pages.
        if (str_contains($hook, '_page_phantom-design-studio')) {
            return; // DesignStudioPage handles its own assets
        }
        wp_enqueue_style('phantom-design-studio', PHANTOM_CORE_URL . 'admin/css/design-studio.css', ['phantom-admin'], PHANTOM_CORE_VERSION);
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

    public function render_design_system(): void {
        Customizer_Design_Panel::get_instance()->render();
    }
}
