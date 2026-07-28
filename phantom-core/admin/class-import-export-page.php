<?php
declare(strict_types=1);

namespace PhantomCore\Admin;

use PhantomCore\Design\DesignSystemManager;
use PhantomCore\Components\Component_Registry;
use PhantomCore\Template_Loader;
use PhantomCore\Demo\Demo_Registry;

defined('ABSPATH') || exit;

class ImportExportPage {
    private static ?self $instance = null;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init_hooks(): void {
        add_action('admin_post_phantom_export_preset', [ $this, 'handle_export_preset' ]);
        add_action('admin_post_phantom_import_preset', [ $this, 'handle_import_preset' ]);
        add_action('admin_post_phantom_export_components', [ $this, 'handle_export_components' ]);
        add_action('admin_post_phantom_import_components', [ $this, 'handle_import_components' ]);
        add_action('admin_post_phantom_export_template_pack', [ $this, 'handle_export_template_pack' ]);
        add_action('admin_post_phantom_import_template_pack', [ $this, 'handle_import_template_pack' ]);
        add_action('admin_post_phantom_backup_full', [ $this, 'handle_full_backup' ]);
        add_action('admin_post_phantom_restore_full', [ $this, 'handle_full_restore' ]);
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

            <div class="phantom-export-section">
                <h2><?php esc_html_e('Export Components', 'phantom-core'); ?></h2>
                <p><?php esc_html_e('Download all registered components and their configurations as a JSON bundle.', 'phantom-core'); ?></p>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('phantom_export_components', 'phantom_export_components_nonce'); ?>
                    <input type="hidden" name="action" value="phantom_export_components">
                    <?php submit_button(__('Export Components', 'phantom-core'), 'primary', 'export_components'); ?>
                </form>
            </div>

            <div class="phantom-import-section">
                <h2><?php esc_html_e('Import Components', 'phantom-core'); ?></h2>
                <p><?php esc_html_e('Upload a component bundle JSON file to register or update components.', 'phantom-core'); ?></p>
                <form method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('phantom_import_components', 'phantom_import_components_nonce'); ?>
                    <input type="hidden" name="action" value="phantom_import_components">
                    <input type="file" name="components_file" accept=".json" required>
                    <?php submit_button(__('Import Components', 'phantom-core'), 'secondary', 'import_components'); ?>
                </form>
            </div>

            <div class="phantom-export-section">
                <h2><?php esc_html_e('Export Template Pack', 'phantom-core'); ?></h2>
                <p><?php esc_html_e('Download the active pack override set as a JSON bundle.', 'phantom-core'); ?></p>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('phantom_export_template_pack', 'phantom_export_template_pack_nonce'); ?>
                    <input type="hidden" name="action" value="phantom_export_template_pack">
                    <?php submit_button(__('Export Template Pack', 'phantom-core'), 'primary', 'export_template_pack'); ?>
                </form>
            </div>

            <div class="phantom-import-section">
                <h2><?php esc_html_e('Import Template Pack', 'phantom-core'); ?></h2>
                <p><?php esc_html_e('Upload a template pack JSON bundle to set as active.', 'phantom-core'); ?></p>
                <form method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('phantom_import_template_pack', 'phantom_import_template_pack_nonce'); ?>
                    <input type="hidden" name="action" value="phantom_import_template_pack">
                    <input type="file" name="template_pack_file" accept=".json" required>
                    <?php submit_button(__('Import Template Pack', 'phantom-core'), 'secondary', 'import_template_pack'); ?>
                </form>
            </div>

            <div class="phantom-export-section">
                <h2><?php esc_html_e('Full Backup', 'phantom-core'); ?></h2>
                <p><?php esc_html_e('Download a complete backup including presets, components, template packs, and all options.', 'phantom-core'); ?></p>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('phantom_backup_full', 'phantom_backup_full_nonce'); ?>
                    <input type="hidden" name="action" value="phantom_backup_full">
                    <?php submit_button(__('Create Full Backup', 'phantom-core'), 'primary', 'backup_full'); ?>
                </form>
            </div>

            <div class="phantom-import-section">
                <h2><?php esc_html_e('Restore Backup', 'phantom-core'); ?></h2>
                <p><?php esc_html_e('Upload a full backup JSON file to restore all settings, components, and template packs.', 'phantom-core'); ?></p>
                <form method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('phantom_restore_full', 'phantom_restore_full_nonce'); ?>
                    <input type="hidden" name="action" value="phantom_restore_full">
                    <input type="file" name="backup_file" accept=".json" required>
                    <?php submit_button(__('Restore Backup', 'phantom-core'), 'secondary', 'restore_full'); ?>
                </form>
            </div>
        </div>
        <?php
    }

    public function handle_export_preset(): void {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['phantom_export_nonce'] ?? '', 'phantom_export')) {
            wp_die(__('Security check failed.', 'phantom-core'));
        }
        $preset_id = sanitize_text_field(wp_unslash($_POST['preset_id'] ?? ''));
        $dsm = DesignSystemManager::get_instance();
        $json = $dsm->export_preset($preset_id);
        nocache_headers();
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="preset-export.json"');
        header('Content-Length: ' . strlen($json));
        echo $json;
        exit;
    }

    public function handle_import_preset(): void {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['phantom_import_nonce'] ?? '', 'phantom_import')) {
            wp_die(__('Security check failed.', 'phantom-core'));
        }
        if (empty($_FILES['preset_file']['tmp_name'])) {
            wp_die(__('No file uploaded.', 'phantom-core'));
        }
        $json = file_get_contents($_FILES['preset_file']['tmp_name']);
        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_die(__('Invalid JSON file.', 'phantom-core'));
        }
        $dsm = DesignSystemManager::get_instance();
        $dsm->import_preset($json);
        wp_redirect(add_query_arg('import_result', 'preset_success', wp_get_referer()));
        exit;
    }

    public function handle_export_components(): void {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['phantom_export_components_nonce'] ?? '', 'phantom_export_components')) {
            wp_die(__('Security check failed.', 'phantom-core'));
        }
        $registry = Component_Registry::get_instance();
        $data = $registry->export_all();
        $this->send_json_download($data, 'components-export.json');
    }

    public function handle_import_components(): void {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['phantom_import_components_nonce'] ?? '', 'phantom_import_components')) {
            wp_die(__('Security check failed.', 'phantom-core'));
        }
        if (empty($_FILES['components_file']['tmp_name'])) {
            wp_die(__('No file uploaded.', 'phantom-core'));
        }
        $json = file_get_contents($_FILES['components_file']['tmp_name']);
        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_die(__('Invalid JSON file.', 'phantom-core'));
        }
        $registry = Component_Registry::get_instance();
        $registry->import_all($data);
        wp_redirect(add_query_arg('import_result', 'components_success', wp_get_referer()));
        exit;
    }

    public function handle_export_template_pack(): void {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['phantom_export_template_pack_nonce'] ?? '', 'phantom_export_template_pack')) {
            wp_die(__('Security check failed.', 'phantom-core'));
        }
        $template_loader = new Template_Loader();
        $pack = $template_loader->get_active_pack();
        $data = [
            'pack' => $pack,
            'manifest' => $template_loader->get_pack_manifest($pack) ?? [],
        ];
        $this->send_json_download($data, 'template-pack-export.json');
    }

    public function handle_import_template_pack(): void {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['phantom_import_template_pack_nonce'] ?? '', 'phantom_import_template_pack')) {
            wp_die(__('Security check failed.', 'phantom-core'));
        }
        if (empty($_FILES['template_pack_file']['tmp_name'])) {
            wp_die(__('No file uploaded.', 'phantom-core'));
        }
        $json = file_get_contents($_FILES['template_pack_file']['tmp_name']);
        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_die(__('Invalid JSON file.', 'phantom-core'));
        }
        $template_loader = new Template_Loader();
        $template_loader->activate_pack($data['pack'] ?? 'default');
        wp_redirect(add_query_arg('import_result', 'template_pack_success', wp_get_referer()));
        exit;
    }

    public function handle_full_backup(): void {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['phantom_backup_full_nonce'] ?? '', 'phantom_backup_full')) {
            wp_die(__('Security check failed.', 'phantom-core'));
        }
        $backup = [
            'version'      => PHANTOM_CORE_VERSION,
            'timestamp'    => current_time('mysql'),
            'options'      => $this->collect_options(),
            'presets'      => DesignSystemManager::get_instance()->export_all_presets(),
            'components'   => Component_Registry::get_instance()->export_all(),
            'template_pack'=> (new Template_Loader())->get_active_pack(),
        ];
        $this->send_json_download($backup, 'phantom-full-backup.json');
    }

    public function handle_full_restore(): void {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['phantom_restore_full_nonce'] ?? '', 'phantom_restore_full')) {
            wp_die(__('Security check failed.', 'phantom-core'));
        }
        if (empty($_FILES['backup_file']['tmp_name'])) {
            wp_die(__('No file uploaded.', 'phantom-core'));
        }
        $json = file_get_contents($_FILES['backup_file']['tmp_name']);
        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_die(__('Invalid JSON file.', 'phantom-core'));
        }
        $this->restore_options($data['options'] ?? []);
        DesignSystemManager::get_instance()->import_all_presets($data['presets'] ?? []);
        Component_Registry::get_instance()->import_all($data['components'] ?? []);
        if (isset($data['template_pack'])) {
            (new Template_Loader())->activate_pack($data['template_pack']);
        }
        wp_redirect(add_query_arg('import_result', 'full_restore_success', wp_get_referer()));
        exit;
    }

    private function collect_options(): array {
        global $wpdb;
        $results = $wpdb->get_results(
            "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE 'phantom_%' OR option_name LIKE '_phantom_%'",
            ARRAY_A
        );
        $options = [];
        foreach ($results as $row) {
            $options[ $row['option_name'] ] = maybe_unserialize($row['option_value']);
        }
        return $options;
    }

    private function restore_options(array $options): void {
        foreach ($options as $name => $value) {
            update_option($name, $value, true);
        }
    }

    private function send_json_download(array $data, string $filename): void {
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo wp_json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }
}