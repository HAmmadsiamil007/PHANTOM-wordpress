<?php
declare(strict_types=1);

namespace PhantomCore\Admin;

use PhantomCore\Design\DesignSystemManager;
use PhantomCore\Design\PresetRegistry;
use PhantomCore\Settings_Registry;

defined('ABSPATH') || exit;

class Customizer_Design_Panel {

    private static ?self $instance = null;
    private DesignSystemManager $dsm;
    private Settings_Registry $registry;

    public function __construct() {
        $this->dsm = DesignSystemManager::get_instance();
        $this->registry = Settings_Registry::get_instance();
    }

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

        $compileResult = $this->dsm->compile();
        $tokenCount = count($compileResult->tokens);
        $presets = $this->dsm->availablePresets();
        $currentPreset = $this->dsm->currentPreset();
        $dna = $this->dsm->currentThemeDNA();
        ?>
        <div class="wrap phantom-design-panel-wrap">
            <h1><?php echo esc_html__('Design System', 'phantom-core'); ?></h1>
            <p><?php echo esc_html__('Quick overview of your design system state.', 'phantom-core'); ?></p>

            <div class="phantom-dashboard-stats" style="display:flex;gap:20px;margin:20px 0;">
                <div class="phantom-stat-card">
                    <h3><?php echo esc_html__('Tokens', 'phantom-core'); ?></h3>
                    <div class="phantom-stat-value"><?php echo (int) $tokenCount; ?></div>
                </div>
                <div class="phantom-stat-card">
                    <h3><?php echo esc_html__('Active Preset', 'phantom-core'); ?></h3>
                    <div class="phantom-stat-value"><?php echo esc_html($currentPreset['name'] ?? 'None'); ?></div>
                </div>
                <div class="phantom-stat-card">
                    <h3><?php echo esc_html__('Available Presets', 'phantom-core'); ?></h3>
                    <div class="phantom-stat-value"><?php echo count($presets); ?></div>
                </div>
            </div>

            <h2><?php echo esc_html__('Theme DNA Profile', 'phantom-core'); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th><?php echo esc_html__('Dimension', 'phantom-core'); ?></th><th><?php echo esc_html__('Value', 'phantom-core'); ?></th></tr></thead>
                <tbody>
                    <?php foreach ($dna as $dim => $value): ?>
                    <tr><td><?php echo esc_html(ucwords(str_replace('_', ' ', $dim))); ?></td><td><?php echo esc_html($value); ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <p style="margin-top:20px;">
                <a href="<?php echo esc_url(admin_url('admin.php?page=phantom-design-studio')); ?>" class="button button-primary">
                    <?php echo esc_html__('Open Design Studio', 'phantom-core'); ?>
                </a>
            </p>
        </div>
        <?php
    }
}
