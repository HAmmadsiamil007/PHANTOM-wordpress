<?php
declare(strict_types=1);

namespace PhantomCore\Admin;

use PhantomCore\Design\DesignSystemManager;

defined('ABSPATH') || exit;

class ImportExportPage {
    private static ?self $instance = null;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function render(): void {
        $dsm = DesignSystemManager::get_instance();
        ?>
        <div class="wrap phantom-import-export">
            <h1><?php esc_html_e('Import / Export', 'phantom-core'); ?></h1>
            <div class="phantom-export-section">
                <h2><?php esc_html_e('Export Current Design', 'phantom-core'); ?></h2>
                <p><?php esc_html_e('Download the current preset configuration as a JSON file.', 'phantom-core'); ?></p>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('phantom_export', 'phantom_export_nonce'); ?>
                    <input type="hidden" name="action" value="phantom_export_preset">
                    <select name="preset_id">
                        <option value=""><?php esc_html_e('Current active preset', 'phantom-core'); ?></option>
                        <?php foreach ($dsm->availablePresets() as $preset): ?>
                            <option value="<?php echo esc_attr($preset->id); ?>"><?php echo esc_html($preset->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php submit_button(__('Export as JSON', 'phantom-core'), 'primary', 'export_preset'); ?>
                </form>
            </div>
            <div class="phantom-import-section">
                <h2><?php esc_html_e('Import Preset', 'phantom-core'); ?></h2>
                <p><?php esc_html_e('Upload a preset JSON file to import design settings.', 'phantom-core'); ?></p>
                <form method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('phantom_import', 'phantom_import_nonce'); ?>
                    <input type="hidden" name="action" value="phantom_import_preset">
                    <input type="file" name="preset_file" accept=".json" required>
                    <?php submit_button(__('Import Preset', 'phantom-core'), 'secondary', 'import_preset'); ?>
                </form>
            </div>
        </div>
        <?php
    }
}
