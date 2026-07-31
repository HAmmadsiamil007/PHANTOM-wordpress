<?php
declare(strict_types=1);

namespace PhantomCore\Admin;

use PhantomCore\Inspector\Inspector_Factory;
use PhantomCore\Components\ComponentInstance;
use PhantomCore\Components\Component_Registry;
use PhantomCore\Component\Component_Tree;
use PhantomCore\Search\Search_UI;
use PhantomCore\Favorites\Favorites_UI;
use PhantomCore\Favorites\Favorites_Manager;
use PhantomCore\History\History_UI;
use PhantomCore\History\History_Manager;
use PhantomCore\Style\Style_State_Engine;
use PhantomCore\Lock\Lock_Manager;
use PhantomCore\Lock\Lock_UI;

defined('ABSPATH') || exit;

class Visual_Customizer_Page {
    private static ?self $instance = null;
    private Inspector_Factory $factory;
    private Style_State_Engine $state_engine;
    private Lock_Manager $lock_manager;
    private Lock_UI $lock_ui;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->factory = Inspector_Factory::get_instance();
        $this->state_engine = Style_State_Engine::get_instance();
        $this->lock_manager = Lock_Manager::get_instance();
        $this->lock_ui = Lock_UI::get_instance();
    }

    public function init(): void {
        add_action('admin_menu', [$this, 'register_admin_page']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function register_admin_page(): void {
        add_submenu_page(
            'phantom-dashboard',
            'Visual Customizer',
            'Visual Customizer',
            'edit_theme_options',
            'phantom-visual-customizer',
            [$this, 'render_page']
        );
    }

    public function enqueue_assets(string $hook): void {
        if ('phantom-dashboard_page_phantom-visual-customizer' !== $hook) {
            return;
        }

        $ver = defined('PHANTOM_CORE_VERSION') ? PHANTOM_CORE_VERSION : '2.0.0';
        $url = defined('PHANTOM_CORE_URL') ? PHANTOM_CORE_URL : '';

        wp_enqueue_media();
        wp_enqueue_style('wp-color-picker');

        wp_enqueue_style(
            'phantom-visual-customizer',
            $url . 'admin/css/visual-customizer.css',
            ['wp-color-picker'],
            $ver
        );

        wp_enqueue_script(
            'phantom-visual-customizer',
            $url . 'admin/js/visual-customizer/visual-customizer.js',
            ['jquery', 'wp-color-picker', 'wp-api-request'],
            $ver,
            true
        );

        wp_enqueue_script(
            'phantom-inline-editor',
            $url . 'admin/js/visual-customizer/inline-editor.js',
            ['jquery'],
            $ver,
            true
        );

        $this->lock_ui->enqueue_assets();

        wp_localize_script('phantom-visual-customizer', 'PhantomVC', [
            'restUrl' => rest_url('phantom/v1'),
            'nonce' => wp_create_nonce('wp_rest'),
            'previewUrl' => $this->get_preview_url(),
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'currentState' => $this->state_engine->get_current_state(),
            'currentViewport' => $this->state_engine->get_current_viewport(),
            'lockNonce' => wp_create_nonce('phantom_lock'),
            'i18n' => [
                'locked' => __('Locked', 'phantom-core'),
                'unlock' => __('Unlock', 'phantom-core'),
                'loading' => __('Loading...', 'phantom-core'),
                'noChanges' => __('No changes to save.', 'phantom-core'),
            ],
        ]);

        // VC module scripts
        wp_enqueue_script(
            'phantom-global-search',
            $url . 'admin/js/visual-customizer/global-search.js',
            ['jquery'],
            $ver,
            true
        );

        wp_enqueue_script(
            'phantom-favorites',
            $url . 'admin/js/visual-customizer/favorites.js',
            ['jquery'],
            $ver,
            true
        );

        wp_enqueue_script(
            'phantom-component-tree',
            $url . 'admin/js/visual-customizer/component-tree.js',
            ['jquery'],
            $ver,
            true
        );

        wp_enqueue_script(
            'phantom-history-timeline',
            $url . 'admin/js/visual-customizer/history-timeline.js',
            ['jquery'],
            $ver,
            true
        );

        wp_localize_script('phantom-global-search', 'PhantomSearch', [
            'restUrl' => rest_url('phantom/v1'),
            'nonce' => wp_create_nonce('wp_rest'),
        ]);

        wp_localize_script('phantom-favorites', 'PhantomFav', [
            'restUrl' => rest_url('phantom/v1'),
            'nonce' => wp_create_nonce('wp_rest'),
        ]);

        wp_localize_script('phantom-component-tree', 'PhantomTree', [
            'restUrl' => rest_url('phantom/v1'),
            'nonce' => wp_create_nonce('wp_rest'),
        ]);

        wp_localize_script('phantom-history-timeline', 'PhantomHistory', [
            'restUrl' => rest_url('phantom/v1'),
            'nonce' => wp_create_nonce('wp_rest'),
        ]);
    }

    private function get_preview_url(): string {
        $home = home_url('/');
        $separator = (str_contains($home, '?')) ? '&' : '?';
        return $home . $separator . 'vc_preview=1';
    }

    public function render_page(): void {
        if (!current_user_can('edit_theme_options')) {
            wp_die('You do not have sufficient permissions.');
        }

        $registry = Component_Registry::get_instance();
        $components = $registry->get_all();
        $categories = $registry->get_categories();

        ?>
        <div class="wrap vc-wrap">
            <div class="vc-toolbar">
                <div class="vc-toolbar-left">
                    <h1 class="vc-title">Visual Customizer</h1>
                    <span class="vc-subtitle">Click any element to edit</span>
                </div>
                <div class="vc-toolbar-right">
                    <?php echo Search_UI::get_instance()->render_search_bar(); ?>
                    <div class="vc-state-selector-toolbar" id="vc-state-selector-toolbar" style="display:none;">
                        <button type="button" class="vc-state-btn-toolbar active" data-state="normal">Normal</button>
                        <button type="button" class="vc-state-btn-toolbar" data-state="hover">Hover</button>
                        <button type="button" class="vc-state-btn-toolbar" data-state="focus">Focus</button>
                        <button type="button" class="vc-state-btn-toolbar" data-state="active">Active</button>
                        <button type="button" class="vc-state-btn-toolbar" data-state="disabled">Disabled</button>
                    </div>
                    <div class="vc-viewport-controls">
                        <button type="button" class="vc-viewport-btn active" data-viewport="desktop" title="Desktop">
                            <span class="dashicons dashicons-desktop"></span>
                        </button>
                        <button type="button" class="vc-viewport-btn" data-viewport="tablet" title="Tablet">
                            <span class="dashicons dashicons-tablet"></span>
                        </button>
                        <button type="button" class="vc-viewport-btn" data-viewport="mobile" title="Mobile">
                            <span class="dashicons dashicons-smartphone"></span>
                        </button>
                    </div>
                    <button type="button" class="vc-btn vc-btn-save" id="vc-save-changes">
                        <span class="dashicons dashicons-yes"></span> Publish
                    </button>
                    <div id="vc-build-status" class="vc-build-status"></div>
                </div>
            </div>

            <div class="vc-main">
                <div class="vc-sidebar" id="vc-sidebar">
                    <div class="vc-sidebar-empty" id="vc-sidebar-empty">
                        <span class="dashicons dashicons-click"></span>
                        <p>Click any element in the preview to start editing.</p>
                    </div>
                    <div class="vc-sidebar-content" id="vc-sidebar-content" style="display:none;">
                        <div class="vc-sidebar-header" id="vc-sidebar-header"></div>
                        <div class="vc-sidebar-panels" id="vc-sidebar-panels"></div>
                    </div>
                </div>

                <div class="vc-preview" id="vc-preview">
                    <div class="vc-preview-loader" id="vc-preview-loader">
                        <span class="spinner is-active"></span>
                        <p>Loading preview...</p>
                    </div>
                    <iframe src="<?php echo esc_url($this->get_preview_url()); ?>"
                            id="vc-preview-iframe"
                            class="vc-preview-iframe"
                            name="vc_preview"></iframe>
                </div>
            </div>

            <div class="vc-dev-tools" id="vc-dev-tools" style="display:none;">
                <div class="vc-dev-header">
                    <span>Dev Tools</span>
                    <button type="button" class="vc-dev-toggle" id="vc-dev-toggle">Show</button>
                </div>
                <div class="vc-dev-tree" id="vc-dev-tree">
                    <h4 style="margin:8px 0;color:#72aee6;">Component Types</h4>
                    <ul>
                        <?php foreach ($components as $name => $comp): ?>
                            <li data-component="<?php echo esc_attr($name); ?>">
                                <span class="vc-dev-node">
                                    <span class="dashicons dashicons-layout"></span>
                                    <?php echo esc_html($comp->label); ?>
                                    <span class="vc-dev-badge"><?php echo esc_html($comp->category); ?></span>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <h4 style="margin:8px 0;color:#72aee6;">Instance Tree</h4>
                    <div class="vc-instance-tree" id="vc-instance-tree">
                        <div class="vc-tree-empty">Loading...</div>
                    </div>

                    <h4 style="margin:8px 0;color:#b32d2e;">
                        Locked Instances
                        <button type="button" class="vc-lock-settings-btn" id="vc-lock-settings" title="Lock settings" style="float:right;background:none;border:none;color:#72aee6;cursor:pointer;font-size:11px;">
                            <span class="dashicons dashicons-admin-settings" style="font-size:12px;width:12px;height:12px;"></span>
                        </button>
                    </h4>
                    <div id="vc-locked-list">
                        <?php
                        $locked = $this->lock_manager->get_locked();
                        echo $this->lock_ui->render_locked_list($locked);
                        ?>
                    </div>

                    <h4 style="margin:8px 0;color:#f0b849;">Favorites</h4>
                    <div id="vc-favorites-list" style="font-size:11px;color:#646970;">
                        <?php
                        $fav_manager = Favorites_Manager::get_instance();
                        $fav_ui = Favorites_UI::get_instance();
                        echo $fav_ui->render_favorites_list($fav_manager->get_with_data());
                        ?>
                    </div>

                    <h4 style="margin:8px 0;color:#72aee6;">History</h4>
                    <div class="vc-history-actions">
                        <?php
                        $hist_ui = History_UI::get_instance();
                        echo $hist_ui->render_undo_button();
                        echo $hist_ui->render_redo_button();
                        echo $hist_ui->render_clear_button();
                        ?>
                    </div>
                    <div class="vc-history-timeline" id="vc-history-timeline">
                        <?php
                        $hist_mgr = History_Manager::get_instance();
                        echo $hist_ui->render_timeline($hist_mgr->get_timeline());
                        ?>
                    </div>
                </div>
            </div>
            <div class="vc-toast-container" id="vc-toast-container"></div>
        </div>
        <script>
        (function() {
            'use strict';
            var stateSelector = document.getElementById('vc-state-selector-toolbar');
            if (stateSelector) {
                stateSelector.addEventListener('click', function(e) {
                    var btn = e.target.closest('.vc-state-btn-toolbar');
                    if (!btn) return;
                    stateSelector.querySelectorAll('.vc-state-btn-toolbar').forEach(function(b) {
                        b.classList.remove('active');
                    });
                    btn.classList.add('active');
                    var state = btn.getAttribute('data-state');
                    var iframe = document.getElementById('vc-preview-iframe');
                    if (iframe && iframe.contentWindow) {
                        iframe.contentWindow.postMessage({
                            type: 'vc-state-change',
                            state: state
                        }, '*');
                    }
                });
            }
        })();
        </script>
        <?php
    }
}
