<?php
declare(strict_types=1);

namespace PhantomCore\Admin;

defined('ABSPATH') || exit;

class DesignStudioPage {
    private static ?self $instance = null;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init(): void {
        add_filter('admin_body_class', [$this, 'body_class'], 10, 1);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function body_class(array $classes): array {
        $screen = get_current_screen();
        if ($screen && str_contains($screen->id, 'phantom-design-studio')) {
            $classes[] = 'phantom-design-studio-active';
        }
        return $classes;
    }

    public function enqueue_assets(string $hook): void {
        if (!str_contains($hook, '_page_phantom-design-studio')) {
            return;
        }
        wp_enqueue_style(
            'phantom-design-studio',
            PHANTOM_CORE_URL . 'admin/css/design-studio.css',
            [],
            PHANTOM_CORE_VERSION
        );
        wp_enqueue_script(
            'phantom-design-studio',
            PHANTOM_CORE_URL . 'admin/js/design-studio.js',
            ['jquery'],
            PHANTOM_CORE_VERSION,
            true
        );
        wp_localize_script('phantom-design-studio', 'phantomDS', [
            'restUrl' => esc_url_raw(rest_url('phantom/v1')),
            'nonce'   => wp_create_nonce('wp_rest'),
            'siteUrl' => esc_url_raw(home_url('/')),
            'strings' => [
                'navigator'      => __('Navigator', 'phantom-core'),
                'inspector'      => __('Inspector', 'phantom-core'),
                'toolbar'        => __('Toolbar', 'phantom-core'),
                'desktop'        => __('Desktop', 'phantom-core'),
                'tablet'         => __('Tablet', 'phantom-core'),
                'mobile'         => __('Mobile', 'phantom-core'),
                'darkMode'       => __('Dark Mode', 'phantom-core'),
                'lightMode'      => __('Light Mode', 'phantom-core'),
                'undo'           => __('Undo', 'phantom-core'),
                'redo'           => __('Redo', 'phantom-core'),
                'presets'        => __('Presets', 'phantom-core'),
                'history'        => __('History', 'phantom-core'),
                'saveDraft'      => __('Save Draft', 'phantom-core'),
                'publish'        => __('Publish', 'phantom-core'),
                'search'         => __('Search components...', 'phantom-core'),
                'unsaved'        => __('Unsaved changes', 'phantom-core'),
                'saved'          => __('All changes saved', 'phantom-core'),
                'noSelection'    => __('Select a component on the canvas to edit its settings.', 'phantom-core'),
                'selectComponent' => __('Select Component', 'phantom-core'),
                'content'        => __('Content', 'phantom-core'),
                'background'     => __('Background', 'phantom-core'),
                'typography'     => __('Typography', 'phantom-core'),
                'spacing'        => __('Spacing', 'phantom-core'),
                'border'         => __('Border', 'phantom-core'),
                'shadow'         => __('Shadow', 'phantom-core'),
                'animation'      => __('Animation', 'phantom-core'),
                'responsive'     => __('Responsive', 'phantom-core'),
                'advanced'       => __('Advanced', 'phantom-core'),
                'reset'          => __('Reset to default', 'phantom-core'),
                'pages'          => __('Pages', 'phantom-core'),
                'homepage'       => __('Homepage', 'phantom-core'),
                'noComponents'   => __('No components found', 'phantom-core'),
                'export'         => __('Export', 'phantom-core'),
                'import'         => __('Import', 'phantom-core'),
                'publishing'     => __('Publishing...', 'phantom-core'),
                'published'      => __('Published successfully', 'phantom-core'),
                'publishFailed'  => __('Publish failed', 'phantom-core'),
            ],
        ]);
    }

    public function render(): void {
        $home_url = home_url('/?design-studio=1');
        ?>
        <div id="phantom-ds-wrapper">
            <div id="phantom-ds-toolbar" class="phantom-ds-toolbar">
                <div class="phantom-ds-toolbar-left">
                    <span class="phantom-ds-brand">PHANTOM Design Studio</span>
                </div>
                <div class="phantom-ds-toolbar-center">
                    <div class="phantom-ds-device-switcher" role="group" aria-label="<?php esc_attr_e('Device preview', 'phantom-core'); ?>">
                        <button type="button" class="phantom-ds-device-btn active" data-device="desktop" title="<?php esc_attr_e('Desktop', 'phantom-core'); ?>">
                            <span class="dashicons dashicons-desktop"></span>
                        </button>
                        <button type="button" class="phantom-ds-device-btn" data-device="tablet" title="<?php esc_attr_e('Tablet', 'phantom-core'); ?>">
                            <span class="dashicons dashicons-tablet"></span>
                        </button>
                        <button type="button" class="phantom-ds-device-btn" data-device="mobile" title="<?php esc_attr_e('Mobile', 'phantom-core'); ?>">
                            <span class="dashicons dashicons-smartphone"></span>
                        </button>
                    </div>
                    <div class="phantom-ds-separator"></div>
                    <button type="button" class="phantom-ds-action-btn" id="phantom-ds-undo" title="<?php esc_attr_e('Undo', 'phantom-core'); ?>" disabled>
                        <span class="dashicons dashicons-undo"></span>
                    </button>
                    <button type="button" class="phantom-ds-action-btn" id="phantom-ds-redo" title="<?php esc_attr_e('Redo', 'phantom-core'); ?>" disabled>
                        <span class="dashicons dashicons-redo"></span>
                    </button>
                    <div class="phantom-ds-separator"></div>
                    <button type="button" class="phantom-ds-action-btn" id="phantom-ds-dark-mode" title="<?php esc_attr_e('Toggle Dark Mode', 'phantom-core'); ?>">
                        <span class="dashicons dashicons-visibility"></span>
                    </button>
                    <div class="phantom-ds-separator"></div>
                    <select id="phantom-ds-preset-select" class="phantom-ds-select" title="<?php esc_attr_e('Select preset', 'phantom-core'); ?>">
                        <option value=""><?php esc_html_e('Presets', 'phantom-core'); ?></option>
                    </select>
                </div>
                <div class="phantom-ds-toolbar-right">
                    <button type="button" class="phantom-ds-action-btn" id="phantom-ds-history" title="<?php esc_attr_e('History', 'phantom-core'); ?>">
                        <span class="dashicons dashicons-backup"></span>
                    </button>
                    <button type="button" class="phantom-ds-action-btn" id="phantom-ds-export" title="<?php esc_attr_e('Export', 'phantom-core'); ?>">
                        <span class="dashicons dashicons-download"></span>
                    </button>
                    <button type="button" class="button phantom-ds-btn-save" id="phantom-ds-save-draft">
                        <?php esc_html_e('Save Draft', 'phantom-core'); ?>
                    </button>
                    <button type="button" class="button button-primary phantom-ds-btn-publish" id="phantom-ds-publish">
                        <?php esc_html_e('Publish', 'phantom-core'); ?>
                    </button>
                </div>
            </div>

            <div id="phantom-ds-main">
                <div id="phantom-ds-navigator" class="phantom-ds-navigator">
                    <div class="phantom-ds-panel-header">
                        <span class="dashicons dashicons-menu-alt2"></span>
                        <span><?php esc_html_e('Navigator', 'phantom-core'); ?></span>
                    </div>
                    <div class="phantom-ds-search-box">
                        <span class="dashicons dashicons-search"></span>
                        <input type="text" id="phantom-ds-search" placeholder="<?php esc_attr_e('Search components...', 'phantom-core'); ?>" />
                    </div>
                    <div id="phantom-ds-component-tree" class="phantom-ds-component-tree">
                        <div class="phantom-ds-tree-loading"><?php esc_html_e('Loading...', 'phantom-core'); ?></div>
                    </div>
                </div>

                <div id="phantom-ds-canvas" class="phantom-ds-canvas">
                    <iframe
                        id="phantom-ds-iframe"
                        src="<?php echo esc_url($home_url); ?>"
                        title="<?php esc_attr_e('Design Studio Preview', 'phantom-core'); ?>"
                        sandbox="allow-scripts allow-same-origin allow-forms allow-popups"
                    ></iframe>
                    <div id="phantom-ds-breadcrumb" class="phantom-ds-breadcrumb"></div>
                </div>

                <div id="phantom-ds-inspector" class="phantom-ds-inspector">
                    <div class="phantom-ds-panel-header">
                        <span class="dashicons dashicons-admin-customizer"></span>
                        <span><?php esc_html_e('Inspector', 'phantom-core'); ?></span>
                    </div>
                    <div id="phantom-ds-inspector-body" class="phantom-ds-inspector-body">
                        <div class="phantom-ds-no-selection">
                            <span class="dashicons dashicons-arrow-left-alt"></span>
                            <p><?php esc_html_e('Select a component on the canvas to edit its settings.', 'phantom-core'); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div id="phantom-ds-statusbar" class="phantom-ds-statusbar">
                <div class="phantom-ds-status-left">
                    <span class="phantom-ds-status-label" id="phantom-ds-status-page">Homepage</span>
                </div>
                <div class="phantom-ds-status-center">
                    <span class="phantom-ds-status-item" id="phantom-ds-status-device">Desktop &mdash; 1280px</span>
                    <span class="phantom-ds-status-sep">|</span>
                    <span class="phantom-ds-status-item" id="phantom-ds-status-dark"><?php esc_html_e('Light Mode', 'phantom-core'); ?></span>
                    <span class="phantom-ds-status-sep">|</span>
                    <span class="phantom-ds-status-item" id="phantom-ds-status-preset"><?php esc_html_e('Default', 'phantom-core'); ?></span>
                </div>
                <div class="phantom-ds-status-right">
                    <span class="phantom-ds-status-item" id="phantom-ds-status-save"><?php esc_html_e('All changes saved', 'phantom-core'); ?></span>
                    <span class="phantom-ds-status-sep">|</span>
                    <span class="phantom-ds-status-item" id="phantom-ds-status-history">Step 0/0</span>
                </div>
            </div>

            <div id="phantom-ds-overlay" class="phantom-ds-overlay" style="display:none;">
                <div class="phantom-ds-overlay-content">
                    <span class="spinner" style="display:inline-block;float:none;visibility:visible;"></span>
                    <p id="phantom-ds-overlay-message"><?php esc_html_e('Publishing...', 'phantom-core'); ?></p>
                </div>
            </div>
        </div>
        <?php
    }
}