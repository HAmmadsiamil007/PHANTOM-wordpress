<?php
declare(strict_types=1);

namespace PhantomCore\Admin;

use PhantomCore\Assets\Asset_Manager;
use PhantomCore\Assets\Asset_Resolver;
use PhantomCore\Assets\Asset_Validator;
use PhantomCore\Assets\Asset_Optimizer;
use PhantomCore\Registry\Asset_Registry;

defined('ABSPATH') || exit;

class AssetManagerPage {
    private static ?self $instance = null;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init(): void {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function enqueue_assets(string $hook): void {
        if (!str_contains($hook, '_page_phantom-asset-manager')) {
            return;
        }
        wp_enqueue_style('phantom-asset-manager', PHANTOM_CORE_URL . 'admin/css/admin.css', [], PHANTOM_CORE_VERSION);
        wp_add_inline_style('phantom-asset-manager', '
            .phantom-asset-health-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 16px; margin: 20px 0; }
            .phantom-health-card { background: #fff; border: 1px solid #c3c4c7; padding: 16px; border-radius: 4px; text-align: center; box-shadow: 0 1px 2px rgba(0,0,0,0.04); }
            .phantom-health-card h3 { margin: 0 0 4px; font-size: 28px; font-weight: 700; }
            .phantom-health-card p { margin: 0; color: #646970; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; }
            .phantom-health-card.good h3 { color: #00a32a; }
            .phantom-health-card.warn h3 { color: #d63638; }
            .phantom-health-card.neutral h3 { color: #2271b1; }
            .phantom-perf-gauge { display: inline-flex; align-items: center; gap: 8px; }
            .phantom-perf-gauge .grade { font-size: 32px; font-weight: 800; padding: 4px 12px; border-radius: 6px; }
            .phantom-perf-gauge .grade.a { background: #00a32a; color: #fff; }
            .phantom-perf-gauge .grade.b { background: #2271b1; color: #fff; }
            .phantom-perf-gauge .grade.c { background: #dba617; color: #fff; }
            .phantom-perf-gauge .grade.d { background: #d63638; color: #fff; }
            .phantom-asset-table .status-dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; margin-right: 6px; }
            .phantom-asset-table .status-dot.valid { background: #00a32a; }
            .phantom-asset-table .status-dot.invalid { background: #d63638; }
            .phantom-asset-table .status-dot.warning { background: #dba617; }
        ');
    }

    public function render(): void {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions.', 'phantom-core'));
        }

        $this->handle_save();

        $cdn_enabled = get_option('phantom_asset_cdn_enabled', '0');
        $cdn_url = get_option('phantom_asset_cdn_url', '');
        $version_param = get_option('phantom_asset_version_param', '1');
        $concat_css = get_option('phantom_asset_concat_css', '0');
        $defer_js = get_option('phantom_asset_defer_js', '0');
        $preconnect_enabled = get_option('phantom_asset_preconnect', '1');

        // Get full asset intelligence from Asset_Manager
        $manager    = Asset_Manager::get_instance();
        $manager->init();
        $allInfo    = $manager->get_all_asset_info();
        $assets     = $allInfo['assets'];
        $summary    = $allInfo['summary'];
        $perf       = $allInfo['performance'];
        $health     = $summary['health'];
        $cdnStatus  = $summary['cdn'];

        // Get performance suggestions
        $optimizer = Asset_Optimizer::get_instance();
        $perfSumm  = $optimizer->get_performance_summary();
        $grade     = strtolower($perfSumm['grade']);

        // Phantom-specific local files
        $phantom_assets_list = [
            'css' => glob(PHANTOM_CORE_PATH . 'frontend/assets/css/*.css') ?: [],
            'js'  => glob(PHANTOM_CORE_PATH . 'frontend/assets/js/*.js') ?: [],
        ];
        ?>
        <div class="wrap phantom-asset-manager">
            <h1><?php esc_html_e('Asset Manager', 'phantom-core'); ?></h1>
            <p class="description"><?php esc_html_e('Full asset intelligence — registration, resolution, validation, and optimization.', 'phantom-core'); ?></p>

            <!-- Performance Gauge -->
            <div class="phantom-asset-health-grid">
                <div class="phantom-health-card">
                    <div class="phantom-perf-gauge">
                        <span class="grade <?php echo esc_attr($grade); ?>"><?php echo esc_html($perfSumm['grade']); ?></span>
                    </div>
                    <p><?php esc_html_e('Asset Score', 'phantom-core'); ?></p>
                </div>
                <div class="phantom-health-card <?php echo $health['valid'] === $health['total'] ? 'good' : 'warn'; ?>">
                    <h3><?php echo esc_html($health['valid']); ?>/<?php echo esc_html($health['total']); ?></h3>
                    <p><?php esc_html_e('Valid Assets', 'phantom-core'); ?></p>
                </div>
                <div class="phantom-health-card <?php echo $health['missing'] > 0 ? 'warn' : 'good'; ?>">
                    <h3><?php echo esc_html($health['missing']); ?></h3>
                    <p><?php esc_html_e('Missing Files', 'phantom-core'); ?></p>
                </div>
                <div class="phantom-health-card neutral">
                    <h3><?php echo esc_html($summary['css']); ?> / <?php echo esc_html($summary['js']); ?></h3>
                    <p><?php esc_html_e('CSS / JS', 'phantom-core'); ?></p>
                </div>
                <div class="phantom-health-card neutral">
                    <h3><?php echo esc_html($health['totalSize']); ?></h3>
                    <p><?php esc_html_e('Total Size', 'phantom-core'); ?></p>
                </div>
                <div class="phantom-health-card <?php echo $cdnStatus['enabled'] ? 'good' : 'neutral'; ?>">
                    <h3><?php echo $cdnStatus['enabled'] ? esc_html__('ON', 'phantom-core') : esc_html__('OFF', 'phantom-core'); ?></h3>
                    <p><?php esc_html_e('CDN Status', 'phantom-core'); ?></p>
                </div>
            </div>

            <!-- Configuration Form -->
            <form method="post" action="">
                <?php wp_nonce_field('phantom_asset_save', 'phantom_asset_nonce'); ?>
                <input type="hidden" name="action" value="save_assets" />

                <div id="phantom-asset-config" style="background:#fff;border:1px solid #c3c4c7;padding:20px;margin-top:20px;">
                    <h2 style="margin-top:0;"><?php esc_html_e('CDN & Optimization', 'phantom-core'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_html_e('Enable CDN', 'phantom-core'); ?></th>
                            <td>
                                <label><input type="checkbox" name="cdn_enabled" value="1" <?php checked('1', $cdn_enabled); ?> />
                                <?php esc_html_e('Serve static assets from a custom CDN endpoint.', 'phantom-core'); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="cdn_url"><?php esc_html_e('CDN Base URL', 'phantom-core'); ?></label></th>
                            <td>
                                <input type="url" id="cdn_url" name="cdn_url" class="regular-text" value="<?php echo esc_attr($cdn_url); ?>" placeholder="https://cdn.example.com" />
                                <p class="description"><?php esc_html_e('Plugin assets served from this URL (e.g., https://cdn.mysite.com/phantom-core/...).', 'phantom-core'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Version Query Strings', 'phantom-core'); ?></th>
                            <td>
                                <label><input type="checkbox" name="version_param" value="1" <?php checked('1', $version_param); ?> />
                                <?php esc_html_e('Append ?v=VERSION to asset URLs for cache busting.', 'phantom-core'); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Defer Non-Critical JS', 'phantom-core'); ?></th>
                            <td>
                                <label><input type="checkbox" name="defer_js" value="1" <?php checked('1', $defer_js); ?> />
                                <?php esc_html_e('Add defer attribute to non-critical JavaScript files.', 'phantom-core'); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Preconnect Hints', 'phantom-core'); ?></th>
                            <td>
                                <label><input type="checkbox" name="preconnect" value="1" <?php checked('1', $preconnect_enabled); ?> />
                                <?php esc_html_e('Automatically add preconnect hints for third-party asset domains.', 'phantom-core'); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Concatenate CSS', 'phantom-core'); ?></th>
                            <td>
                                <label><input type="checkbox" name="concat_css" value="1" <?php checked('1', $concat_css); ?> />
                                <?php esc_html_e('Combine CSS files into fewer requests (requires build step).', 'phantom-core'); ?></label>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <button type="submit" class="button button-primary"><?php esc_html_e('Save Asset Settings', 'phantom-core'); ?></button>
                    </p>
                </div>
            </form>

            <!-- Recommendations -->
            <?php if (!empty($perfSumm['recommendations'])): ?>
            <div style="background:#f6f7f7;border:1px solid #c3c4c7;padding:16px 20px;margin-top:20px;">
                <h3 style="margin-top:0;"><?php esc_html_e('Optimization Recommendations', 'phantom-core'); ?></h3>
                <ul style="margin:0;padding-left:20px;">
                    <?php foreach ($perfSumm['recommendations'] as $rec): ?>
                        <li style="margin-bottom:6px;"><?php echo esc_html($rec); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <!-- Registered Phantom Assets with Resolution & Validation -->
            <div style="margin-top:30px;">
                <h2><?php esc_html_e('Registered Phantom Assets', 'phantom-core'); ?>
                    <span style="font-size:13px;font-weight:400;color:#646970;margin-left:8px;"><?php echo count($assets); ?> total — resolved, validated, optimized</span>
                </h2>

                <table class="widefat striped phantom-asset-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Handle', 'phantom-core'); ?></th>
                            <th><?php esc_html_e('Type', 'phantom-core'); ?></th>
                            <th><?php esc_html_e('Source', 'phantom-core'); ?></th>
                            <th><?php esc_html_e('Resolved URL', 'phantom-core'); ?></th>
                            <th><?php esc_html_e('CDN', 'phantom-core'); ?></th>
                            <th><?php esc_html_e('Status', 'phantom-core'); ?></th>
                            <th><?php esc_html_e('Size', 'phantom-core'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assets as $handle => $info): 
                            $type     = $info['type'] ?? 'js';
                            $resolved = $info['resolved'] ?? [];
                            $val      = $info['validation'] ?? [];
                            $src      = $info['src'] ?? '';
                            $resolvedUrl = $resolved['url'] ?? $src;
                            $onCdn    = !empty($resolved['cdn']);
                            $valid    = !empty($val['valid']);
                            $exists   = !empty($val['exists']);
                            $size     = $val['size'] ?? 0;
                            $sizeDisplay = $size ? size_format($size, 1) : '—';
                            $statusClass = $valid ? 'valid' : ($exists ? 'warning' : 'invalid');
                            $statusLabel = $valid ? __('OK', 'phantom-core') : ($exists ? __('MIME', 'phantom-core') : __('MISS', 'phantom-core'));
                        ?>
                            <tr>
                                <td><code><?php echo esc_html($handle); ?></code></td>
                                <td><code><?php echo esc_html(strtoupper($type)); ?></code></td>
                                <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?php echo esc_attr($src); ?>">
                                    <code><?php echo esc_html(basename($src ?: $handle)); ?></code>
                                </td>
                                <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?php echo esc_attr($resolvedUrl); ?>">
                                    <code><?php echo esc_html(basename($resolvedUrl)); ?></code>
                                </td>
                                <td><?php echo $onCdn ? '<span style="color:#00a32a;">✓</span>' : '<span style="color:#c3c4c7;">—</span>'; ?></td>
                                <td><span class="status-dot <?php echo esc_attr($statusClass); ?>"></span><?php echo esc_html($statusLabel); ?></td>
                                <td><?php echo esc_html($sizeDisplay); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Local Files -->
            <details style="margin-top:30px;">
                <summary style="cursor:pointer;font-weight:600;font-size:14px;"><?php esc_html_e('Local Phantom Files', 'phantom-core'); ?> (<?php echo count($phantom_assets_list['css']) + count($phantom_assets_list['js']); ?>)</summary>
                <div style="display:flex;gap:20px;margin-top:10px;">
                    <div style="flex:1;">
                        <h3><?php esc_html_e('CSS Files', 'phantom-core'); ?> (<?php echo count($phantom_assets_list['css']); ?>)</h3>
                        <table class="widefat striped">
                            <thead><tr><th><?php esc_html_e('File', 'phantom-core'); ?></th><th><?php esc_html_e('Size', 'phantom-core'); ?></th></tr></thead>
                            <tbody>
                                <?php foreach ($phantom_assets_list['css'] as $file): $name = basename($file); ?>
                                    <tr><td><code>css/<?php echo esc_html($name); ?></code></td><td><?php echo esc_html(size_format(filesize($file), 1) ?: '—'); ?></td></tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div style="flex:1;">
                        <h3><?php esc_html_e('JavaScript Files', 'phantom-core'); ?> (<?php echo count($phantom_assets_list['js']); ?>)</h3>
                        <table class="widefat striped">
                            <thead><tr><th><?php esc_html_e('File', 'phantom-core'); ?></th><th><?php esc_html_e('Size', 'phantom-core'); ?></th></tr></thead>
                            <tbody>
                                <?php foreach ($phantom_assets_list['js'] as $file): $name = basename($file); ?>
                                    <tr><td><code>js/<?php echo esc_html($name); ?></code></td><td><?php echo esc_html(size_format(filesize($file), 1) ?: '—'); ?></td></tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </details>
        </div>
        <?php
    }

    private function handle_save(): void {
        if ('POST' !== strtoupper($_SERVER['REQUEST_METHOD'] ?? '')) return;
        if (!isset($_POST['phantom_asset_nonce'])) return;
        if (!wp_verify_nonce(wp_unslash($_POST['phantom_asset_nonce']), 'phantom_asset_save')) {
            wp_die(esc_html__('Security check failed.', 'phantom-core'));
        }
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission.', 'phantom-core'));
        }

        update_option('phantom_asset_cdn_enabled', isset($_POST['cdn_enabled']) ? '1' : '0');
        update_option('phantom_asset_cdn_url', esc_url_raw(wp_unslash($_POST['cdn_url'] ?? '')));
        update_option('phantom_asset_version_param', isset($_POST['version_param']) ? '1' : '0');
        update_option('phantom_asset_concat_css', isset($_POST['concat_css']) ? '1' : '0');
        update_option('phantom_asset_defer_js', isset($_POST['defer_js']) ? '1' : '0');
        update_option('phantom_asset_preconnect', isset($_POST['preconnect']) ? '1' : '0');

        // Clear asset caches
        Asset_Manager::get_instance()->clear_cache();

        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Asset settings saved. Caches cleared.', 'phantom-core') . '</p></div>';
    }
}
