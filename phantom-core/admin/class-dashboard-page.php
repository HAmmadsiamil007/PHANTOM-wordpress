<?php
declare(strict_types=1);

namespace PhantomCore\Admin;

use PhantomCore\Design\DesignSystemManager;

defined('ABSPATH') || exit;

class DashboardPage {
    private static ?self $instance = null;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function render(): void {
        $dsm = DesignSystemManager::get_instance();
        $tokenCount = count($dsm->compile()->tokens);
        $cssVarCount = count($dsm->allCssVars());
        $presets = $dsm->availablePresets();
        $currentPreset = $dsm->currentPreset();
        $validation = $dsm->validate();
        $healthy = true;
        foreach ($validation as $r) {
            if ('error' === $r['status']) { $healthy = false; break; }
        }
        $activeDemo = get_option('phantom_active_demo', 'none');
        ?>
        <div class="wrap phantom-dashboard">
            <h1><?php esc_html_e('PHANTOM Dashboard', 'phantom-core'); ?></h1>
            <div class="phantom-stats-grid">
                <div class="phantom-stat-card">
                    <span class="stat-label"><?php esc_html_e('Framework Version', 'phantom-core'); ?></span>
                    <span class="stat-value"><?php echo esc_html(PHANTOM_CORE_VERSION); ?></span>
                </div>
                <div class="phantom-stat-card">
                    <span class="stat-label"><?php esc_html_e('Active Demo', 'phantom-core'); ?></span>
                    <span class="stat-value"><?php echo esc_html(ucfirst($activeDemo)); ?></span>
                </div>
                <div class="phantom-stat-card">
                    <span class="stat-label"><?php esc_html_e('Active Preset', 'phantom-core'); ?></span>
                    <span class="stat-value"><?php echo esc_html($currentPreset['name'] ?? __('None', 'phantom-core')); ?></span>
                </div>
                <div class="phantom-stat-card">
                    <span class="stat-label"><?php esc_html_e('Token Health', 'phantom-core'); ?></span>
                    <span class="stat-value <?php echo $healthy ? 'healthy' : 'unhealthy'; ?>"><?php echo $healthy ? '✅' : '⚠️'; ?></span>
                </div>
                <div class="phantom-stat-card">
                    <span class="stat-label"><?php esc_html_e('Token Count', 'phantom-core'); ?></span>
                    <span class="stat-value"><?php echo esc_html((string) $tokenCount); ?></span>
                </div>
                <div class="phantom-stat-card">
                    <span class="stat-label"><?php esc_html_e('CSS Variables', 'phantom-core'); ?></span>
                    <span class="stat-value"><?php echo esc_html((string) $cssVarCount); ?></span>
                </div>
                <div class="phantom-stat-card">
                    <span class="stat-label"><?php esc_html_e('Available Presets', 'phantom-core'); ?></span>
                    <span class="stat-value"><?php echo esc_html((string) count($presets)); ?></span>
                </div>
                <div class="phantom-stat-card">
                    <span class="stat-label"><?php esc_html_e('System Status', 'phantom-core'); ?></span>
                    <span class="stat-value healthy"><?php esc_html_e('Operational', 'phantom-core'); ?></span>
                </div>
            </div>
            <div class="phantom-quick-actions">
                <h2><?php esc_html_e('Quick Actions', 'phantom-core'); ?></h2>
                <div class="phantom-actions">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=phantom-design-studio')); ?>" class="button button-primary"><?php esc_html_e('Open Design Studio', 'phantom-core'); ?></a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=phantom-theme-options')); ?>" class="button"><?php esc_html_e('Theme Options', 'phantom-core'); ?></a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=phantom-demo-manager')); ?>" class="button"><?php esc_html_e('Manage Demos', 'phantom-core'); ?></a>
                    <a href="<?php echo esc_url(admin_url('customize.php')); ?>" class="button"><?php esc_html_e('Customizer', 'phantom-core'); ?></a>
                </div>
            </div>
        </div>
        <?php
    }
}
