<?php
declare(strict_types=1);

namespace PhantomCore\Admin;

use PhantomCore\Engine\Cache;

defined('ABSPATH') || exit;

class SystemPage {
    private static ?self $instance = null;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function render(): void {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions.', 'phantom-core'));
        }

        $this->handle_actions();

        $php_version = phpversion();
        $wp_version = get_bloginfo('version');
        $memory_limit = ini_get('memory_limit');
        $max_execution = ini_get('max_execution_time');
        $upload_max = ini_get('upload_max_filesize');
        $wc_active = class_exists('WooCommerce');
        $wc_version = $wc_active && defined('WC_VERSION') ? WC_VERSION : '—';
        $active_plugins = count(get_option('active_plugins', []));
        $debug_log = WP_DEBUG ? (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ? ini_get('error_log') : __('Enabled (no file)', 'phantom-core')) : __('Disabled', 'phantom-core');
        $debug_log_size = '—';
        if (WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG && is_string(WP_DEBUG_LOG) && file_exists(WP_DEBUG_LOG)) {
            $size = filesize(WP_DEBUG_LOG);
            $debug_log_size = size_format($size, 2);
        } elseif (WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            $default_log = WP_CONTENT_DIR . '/debug.log';
            if (file_exists($default_log)) {
                $size = filesize($default_log);
                $debug_log_size = size_format($size, 2);
            }
        }
        $phantom_version = PHANTOM_CORE_VERSION;
        $settings_count = count(\PhantomCore\Settings_Registry::get_instance()->get_entries());
        $rest_routes = 43; // Known count from architecture
        $demo_active = get_option('phantom_active_demo', 'none');

        $css_vars_count = count(\PhantomCore\Design\DesignSystemManager::get_instance()->allCssVars());
        $feature_flags = count(\PhantomCore\Feature\Feature_Registry::get_instance()->get_all());
        $feature_enabled = count(array_filter(\PhantomCore\Feature\Feature_Registry::get_instance()->get_all(), fn($f) => $f->enabled()));
        ?>
        <div class="wrap phantom-system">
            <h1><?php esc_html_e('System & Diagnostics', 'phantom-core'); ?></h1>
            <p class="description"><?php esc_html_e('View system information, run diagnostics, and manage cache.', 'phantom-core'); ?></p>

            <!-- Quick Actions -->
            <div class="phantom-system-actions" style="margin:20px 0;display:flex;gap:10px;flex-wrap:wrap;">
                <form method="post" style="display:inline;">
                    <?php wp_nonce_field('phantom_cache_flush', 'phantom_cache_nonce'); ?>
                    <input type="hidden" name="action" value="flush_cache" />
                    <button type="submit" class="button" onclick="return confirm('<?php esc_attr_e('Flush all Phantom Core caches?', 'phantom-core'); ?>')">
                        🗑️ <?php esc_html_e('Flush Cache', 'phantom-core'); ?>
                    </button>
                </form>
                <form method="post" style="display:inline;">
                    <?php wp_nonce_field('phantom_diagnostics', 'phantom_diagnostics_nonce'); ?>
                    <input type="hidden" name="action" value="run_diagnostics" />
                    <button type="submit" class="button">🔍 <?php esc_html_e('Run Diagnostics', 'phantom-core'); ?></button>
                </form>
                <a href="<?php echo esc_url(admin_url('site-health.php')); ?>" class="button">🩺 <?php esc_html_e('WordPress Site Health', 'phantom-core'); ?></a>
            </div>

            <!-- Phantom Specific -->
            <div class="phantom-section">
                <h2><?php esc_html_e('PHANTOM Core', 'phantom-core'); ?></h2>
                <table class="widefat striped" style="max-width:800px;">
                    <tbody>
                        <tr><td style="width:250px;"><strong><?php esc_html_e('Version', 'phantom-core'); ?></strong></td><td><?php echo esc_html($phantom_version); ?></td></tr>
                        <tr><td><strong><?php esc_html_e('Active Demo Pack', 'phantom-core'); ?></strong></td><td><?php echo esc_html(ucfirst($demo_active)); ?></td></tr>
                        <tr><td><strong><?php esc_html_e('Registered Settings', 'phantom-core'); ?></strong></td><td><?php echo esc_html((string) $settings_count); ?></td></tr>
                        <tr><td><strong><?php esc_html_e('REST API Routes', 'phantom-core'); ?></strong></td><td><?php echo esc_html((string) $rest_routes); ?></td></tr>
                        <tr><td><strong><?php esc_html_e('CSS Variables Generated', 'phantom-core'); ?></strong></td><td><?php echo esc_html((string) $css_vars_count); ?></td></tr>
                        <tr><td><strong><?php esc_html_e('Feature Flags', 'phantom-core'); ?></strong></td><td><?php echo esc_html((string) $feature_enabled); ?> / <?php echo esc_html((string) $feature_flags); ?> <?php esc_html_e('enabled', 'phantom-core'); ?></td></tr>
                    </tbody>
                </table>
            </div>

            <!-- Server Environment -->
            <div class="phantom-section" style="margin-top:30px;">
                <h2><?php esc_html_e('Server Environment', 'phantom-core'); ?></h2>
                <table class="widefat striped" style="max-width:800px;">
                    <tbody>
                        <tr><td style="width:250px;"><strong><?php esc_html_e('PHP Version', 'phantom-core'); ?></strong></td><td><?php echo esc_html($php_version); ?> <?php echo version_compare($php_version, '7.4', '>=') ? '✅' : '⚠️'; ?></td></tr>
                        <tr><td><strong><?php esc_html_e('WordPress Version', 'phantom-core'); ?></strong></td><td><?php echo esc_html($wp_version); ?></td></tr>
                        <tr><td><strong><?php esc_html_e('Memory Limit', 'phantom-core'); ?></strong></td><td><?php echo esc_html($memory_limit); ?> <?php echo wp_convert_hr_to_bytes($memory_limit) >= 67108864 ? '✅' : '⚠️ ' . esc_html__('Recommended: 64M+', 'phantom-core'); ?></td></tr>
                        <tr><td><strong><?php esc_html_e('Max Execution Time', 'phantom-core'); ?></strong></td><td><?php echo esc_html((string) $max_execution); ?>s <?php echo $max_execution >= 60 ? '✅' : '⚠️ ' . esc_html__('Recommended: 60s+', 'phantom-core'); ?></td></tr>
                        <tr><td><strong><?php esc_html_e('Upload Max Size', 'phantom-core'); ?></strong></td><td><?php echo esc_html($upload_max); ?></td></tr>
                        <tr><td><strong><?php esc_html_e('Active Plugins', 'phantom-core'); ?></strong></td><td><?php echo esc_html((string) $active_plugins); ?></td></tr>
                        <tr><td><strong><?php esc_html_e('WooCommerce', 'phantom-core'); ?></strong></td><td><?php echo $wc_active ? '✅ ' . esc_html($wc_version) : '❌ ' . esc_html__('Not installed', 'phantom-core'); ?></td></tr>
                        <tr><td><strong><?php esc_html_e('WP Debug Mode', 'phantom-core'); ?></strong></td><td><?php echo WP_DEBUG ? '⚠️ ' . esc_html__('Enabled', 'phantom-core') : '✅ ' . esc_html__('Disabled', 'phantom-core'); ?></td></tr>
                        <tr><td><strong><?php esc_html_e('Debug Log', 'phantom-core'); ?></strong></td><td><?php echo esc_html($debug_log); ?><?php echo $debug_log_size !== '—' ? ' (' . esc_html($debug_log_size) . ')' : ''; ?></td></tr>
                        <tr><td><strong><?php esc_html_e('PHP Memory Usage', 'phantom-core'); ?></strong></td><td><?php echo esc_html(size_format(memory_get_usage(true), 2)); ?> / <?php echo esc_html($memory_limit); ?></td></tr>
                    </tbody>
                </table>
            </div>

            <!-- Cache Status -->
            <div class="phantom-section" style="margin-top:30px;">
                <h2><?php esc_html_e('Cache Status', 'phantom-core'); ?></h2>
                <table class="widefat striped" style="max-width:800px;">
                    <tbody>
                        <tr><td style="width:250px;"><strong><?php esc_html_e('Object Cache', 'phantom-core'); ?></strong></td><td><?php echo wp_using_ext_object_cache() ? '✅ ' . esc_html__('External', 'phantom-core') : '⚠️ ' . esc_html__('Not detected', 'phantom-core'); ?></td></tr>
                        <tr><td><strong><?php esc_html_e('OPcache', 'phantom-core'); ?></strong></td><td><?php echo function_exists('opcache_get_status') && opcache_get_status(false) ? '✅ ' . esc_html__('Active', 'phantom-core') : '⚠️ ' . esc_html__('Not detected', 'phantom-core'); ?></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    private function handle_actions(): void {
        if ('POST' !== strtoupper($_SERVER['REQUEST_METHOD'] ?? '')) return;

        if (isset($_POST['action']) && 'flush_cache' === $_POST['action']) {
            if (!isset($_POST['phantom_cache_nonce']) || !wp_verify_nonce(wp_unslash($_POST['phantom_cache_nonce']), 'phantom_cache_flush')) {
                wp_die(esc_html__('Security check failed.', 'phantom-core'));
            }
            wp_cache_flush();
            if (function_exists('opcache_reset')) opcache_reset();
            delete_transient('phantom_css_cache');
            delete_transient('phantom_token_cache');
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('All Phantom Core caches flushed successfully.', 'phantom-core') . '</p></div>';
        }

        if (isset($_POST['action']) && 'run_diagnostics' === $_POST['action']) {
            if (!isset($_POST['phantom_diagnostics_nonce']) || !wp_verify_nonce(wp_unslash($_POST['phantom_diagnostics_nonce']), 'phantom_diagnostics')) {
                wp_die(esc_html__('Security check failed.', 'phantom-core'));
            }
            $dsm = \PhantomCore\Design\DesignSystemManager::get_instance();
            $validation = $dsm->validate();
            $healthy = true;
            $output = '';
            foreach ($validation as $r) {
                $icon = 'error' === $r['status'] ? '❌' : ('warning' === $r['status'] ? '⚠️' : '✅');
                $output .= '<li>' . $icon . ' ' . esc_html($r['message']) . '</li>';
                if ('error' === $r['status']) $healthy = false;
            }
            $output .= '<li>' . ($healthy ? '✅ ' : '⚠️ ') . esc_html__('Design System: ', 'phantom-core') . ($healthy ? esc_html__('All checks passed', 'phantom-core') : esc_html__('Issues found', 'phantom-core')) . '</li>';
            echo '<div class="notice notice-' . ($healthy ? 'success' : 'warning') . ' is-dismissible"><ul>' . $output . '</ul></div>';
        }
    }
}
