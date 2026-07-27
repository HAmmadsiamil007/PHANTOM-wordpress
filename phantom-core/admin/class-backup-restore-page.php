<?php
declare(strict_types=1);

namespace PhantomCore\Admin;

use PhantomCore\Design\DesignSystemManager;
use PhantomCore\Settings_Registry;

defined('ABSPATH') || exit;

class BackupRestorePage {
    private static ?self $instance = null;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init(): void {
        add_action('admin_post_phantom_export_all', [$this, 'handle_export']);
        add_action('admin_post_phantom_import_all', [$this, 'handle_import']);
    }

    public function render(): void {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions.', 'phantom-core'));
        }

        $this->handle_actions();

        $active_tab = sanitize_key($_GET['tab'] ?? 'export');
        ?>
        <div class="wrap phantom-backup-restore">
            <h1><?php esc_html_e('Backup & Restore', 'phantom-core'); ?></h1>
            <p class="description"><?php esc_html_e('Export, import, backup, and restore your Phantom Core settings, design tokens, and configuration.', 'phantom-core'); ?></p>

            <nav class="nav-tab-wrapper" style="margin-top:20px;">
                <a href="?page=phantom-backup-restore&tab=export" class="nav-tab <?php echo 'export' === $active_tab ? 'nav-tab-active' : ''; ?>">📤 <?php esc_html_e('Export', 'phantom-core'); ?></a>
                <a href="?page=phantom-backup-restore&tab=import" class="nav-tab <?php echo 'import' === $active_tab ? 'nav-tab-active' : ''; ?>">📥 <?php esc_html_e('Import', 'phantom-core'); ?></a>
                <a href="?page=phantom-backup-restore&tab=backup" class="nav-tab <?php echo 'backup' === $active_tab ? 'nav-tab-active' : ''; ?>">💾 <?php esc_html_e('Manage Backups', 'phantom-core'); ?></a>
                <a href="?page=phantom-backup-restore&tab=reset" class="nav-tab <?php echo 'reset' === $active_tab ? 'nav-tab-active' : ''; ?>">⚠️ <?php esc_html_e('Reset', 'phantom-core'); ?></a>
            </nav>

            <?php if ('export' === $active_tab): ?>
                <div class="phantom-section" style="margin-top:20px;">
                    <h2><?php esc_html_e('Export Configuration', 'phantom-core'); ?></h2>
                    <p><?php esc_html_e('Download your complete Phantom Core configuration as a JSON file for backup or transfer.', 'phantom-core'); ?></p>

                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <?php wp_nonce_field('phantom_backup_export', 'phantom_backup_nonce'); ?>
                        <input type="hidden" name="action" value="phantom_export_all" />

                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php esc_html_e('Include Options', 'phantom-core'); ?></th>
                                <td>
                                    <label><input type="checkbox" name="include_settings" value="1" checked /> <?php esc_html_e('All phantom_* WordPress options', 'phantom-core'); ?></label><br />
                                    <label><input type="checkbox" name="include_presets" value="1" checked /> <?php esc_html_e('Design presets', 'phantom-core'); ?></label><br />
                                    <label><input type="checkbox" name="include_tokens" value="1" checked /> <?php esc_html_e('Design tokens (colors, typography, spacing, motion)', 'phantom-core'); ?></label><br />
                                    <label><input type="checkbox" name="include_dna" value="1" checked /> <?php esc_html_e('Theme DNA configuration', 'phantom-core'); ?></label><br />
                                    <label><input type="checkbox" name="include_custom_css" value="1" checked /> <?php esc_html_e('Custom CSS', 'phantom-core'); ?></label><br />
                                    <label><input type="checkbox" name="include_menus" value="1" /> <?php esc_html_e('Menu assignments', 'phantom-core'); ?></label><br />
                                    <label><input type="checkbox" name="include_widgets" value="1" /> <?php esc_html_e('Widget assignments', 'phantom-core'); ?></label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="export_label"><?php esc_html_e('Export Label', 'phantom-core'); ?></label></th>
                                <td>
                                    <input type="text" id="export_label" name="export_label" class="regular-text" value="" placeholder="<?php esc_attr_e('e.g., Before redesign v2', 'phantom-core'); ?>" />
                                </td>
                            </tr>
                        </table>

                        <?php submit_button(__('Download Export (.json)', 'phantom-core'), 'primary', 'export_backup'); ?>
                    </form>

                    <!-- Quick Export (pre-built) -->
                    <div class="phantom-section" style="margin-top:20px;padding:15px;background:#f9f9f9;border:1px solid #ddd;border-radius:4px;">
                        <h3><?php esc_html_e('Preview Export Data', 'phantom-core'); ?></h3>
                        <p><?php esc_html_e('Click to preview what will be exported:', 'phantom-core'); ?></p>
                        <form method="post" style="display:inline;">
                            <?php wp_nonce_field('phantom_export_preview', 'phantom_preview_nonce'); ?>
                            <input type="hidden" name="action" value="preview_export" />
                            <button type="submit" class="button"><?php esc_html_e('Preview Export JSON', 'phantom-core'); ?></button>
                        </form>
                        <?php
                        if (isset($_POST['action']) && 'preview_export' === $_POST['action']) {
                            if (isset($_POST['phantom_preview_nonce']) && wp_verify_nonce(wp_unslash($_POST['phantom_preview_nonce']), 'phantom_export_preview')) {
                                $preview = $this->build_export_data();
                                echo '<pre style="margin-top:15px;background:#fff;padding:15px;border:1px solid #ddd;border-radius:3px;max-height:400px;overflow-y:auto;font-size:11px;">' . esc_html(substr(wp_json_encode($preview, JSON_PRETTY_PRINT), 0, 3000)) . '...</pre>';
                            }
                        }
                        ?>
                    </div>
                </div>

            <?php elseif ('import' === $active_tab): ?>
                <div class="phantom-section" style="margin-top:20px;">
                    <?php if (isset($_GET['import_success'])): ?>
                        <div class="notice notice-success is-dismissible"><p><?php esc_html_e('✅ Configuration imported successfully.', 'phantom-core'); ?></p></div>
                    <?php elseif (isset($_GET['import_error']) && '1' === $_GET['import_error']): ?>
                        <div class="notice notice-error is-dismissible"><p><?php esc_html_e('❌ File upload error. Please try again.', 'phantom-core'); ?></p></div>
                    <?php elseif (isset($_GET['import_error']) && '2' === $_GET['import_error']): ?>
                        <div class="notice notice-error is-dismissible"><p><?php esc_html_e('❌ Invalid import file format. Expected a valid Phantom Core JSON export.', 'phantom-core'); ?></p></div>
                    <?php endif; ?>
                    <h2><?php esc_html_e('Import Configuration', 'phantom-core'); ?></h2>
                    <p><?php esc_html_e('Upload a previously exported Phantom Core configuration JSON file to restore settings.', 'phantom-core'); ?></p>

                    <form method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <?php wp_nonce_field('phantom_backup_import', 'phantom_import_nonce'); ?>
                        <input type="hidden" name="action" value="phantom_import_all" />

                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="import_file"><?php esc_html_e('Select File', 'phantom-core'); ?></label></th>
                                <td>
                                    <input type="file" id="import_file" name="import_file" accept=".json" required />
                                    <p class="description"><?php esc_html_e('Upload a .json file exported from Phantom Core.', 'phantom-core'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('Import Options', 'phantom-core'); ?></th>
                                <td>
                                    <label><input type="checkbox" name="overwrite" value="1" checked /> <?php esc_html_e('Overwrite existing settings', 'phantom-core'); ?></label>
                                    <p class="description"><?php esc_html_e('Uncheck to only restore missing settings.', 'phantom-core'); ?></p>
                                </td>
                            </tr>
                        </table>

                        <?php submit_button(__('Import & Restore', 'phantom-core'), 'primary', 'import_backup'); ?>
                    </form>

                    <div class="notice notice-warning" style="margin-top:20px;">
                        <p><?php esc_html_e('⚠️ Importing will overwrite your current Phantom Core settings. Export your current configuration first if you want to keep it.', 'phantom-core'); ?></p>
                    </div>
                </div>

            <?php elseif ('backup' === $active_tab): ?>
                <div class="phantom-section" style="margin-top:20px;">
                    <h2><?php esc_html_e('Manage Backups', 'phantom-core'); ?></h2>
                    <p><?php esc_html_e('Phantom Core can store up to 5 automatic backups locally. Previous exports are saved as WordPress options for quick restoration.', 'phantom-core'); ?></p>

                    <?php
                    global $wpdb;
                    $backups = $wpdb->get_results(
                        $wpdb->prepare(
                            "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s ORDER BY option_name DESC LIMIT 10",
                            'phantom_backup_%'
                        )
                    );
                    ?>
                    <table class="widefat striped" style="max-width:800px;">
                        <thead><tr><th><?php esc_html_e('Backup Name', 'phantom-core'); ?></th><th><?php esc_html_e('Size', 'phantom-core'); ?></th><th><?php esc_html_e('Date', 'phantom-core'); ?></th><th><?php esc_html_e('Actions', 'phantom-core'); ?></th></tr></thead>
                        <tbody>
                            <?php if (!empty($backups)): ?>
                                <?php foreach ($backups as $backup): ?>
                                    <?php
                                    $size = strlen($backup->option_value);
                                    $timestamp = str_replace(['phantom_backup_', '_'], ['', ' '], $backup->option_name);
                                    ?>
                                    <tr>
                                        <td><code><?php echo esc_html($backup->option_name); ?></code></td>
                                        <td><?php echo esc_html(size_format($size, 2) ?: $size . ' bytes'); ?></td>
                                        <td><?php echo esc_html($timestamp ?: '—'); ?></td>
                                        <td>
                                            <form method="post" style="display:inline;">
                                                <?php wp_nonce_field('phantom_restore_backup', 'phantom_restore_nonce'); ?>
                                                <input type="hidden" name="action" value="restore" />
                                                <input type="hidden" name="backup_name" value="<?php echo esc_attr($backup->option_name); ?>" />
                                                <button type="submit" class="button button-small" onclick="return confirm('<?php esc_attr_e('Restore this backup? Current settings will be overwritten.', 'phantom-core'); ?>')"><?php esc_html_e('Restore', 'phantom-core'); ?></button>
                                            </form>
                                            <form method="post" style="display:inline;">
                                                <?php wp_nonce_field('phantom_delete_backup', 'phantom_delete_nonce'); ?>
                                                <input type="hidden" name="action" value="delete_backup" />
                                                <input type="hidden" name="backup_name" value="<?php echo esc_attr($backup->option_name); ?>" />
                                                <button type="submit" class="button button-small" onclick="return confirm('<?php esc_attr_e('Delete this backup?', 'phantom-core'); ?>')"><?php esc_html_e('Delete', 'phantom-core'); ?></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="4"><em><?php esc_html_e('No backups found. Export your configuration to create a backup.', 'phantom-core'); ?></em></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ('reset' === $active_tab): ?>
                <div class="phantom-section" style="margin-top:20px;">
                    <h2 style="color:#dc3232;"><?php esc_html_e('⚠️ Reset Everything', 'phantom-core'); ?></h2>
                    <p><?php esc_html_e('Restore all Phantom Core settings to their default values. This cannot be undone!', 'phantom-core'); ?></p>

                    <form method="post" action="" style="margin-top:20px;">
                        <?php wp_nonce_field('phantom_reset_all', 'phantom_reset_nonce'); ?>
                        <input type="hidden" name="action" value="reset_all" />

                        <div class="notice notice-error" style="max-width:600px;">
                            <p><strong><?php esc_html_e('This will delete ALL phantom_* options from the database.', 'phantom-core'); ?></strong></p>
                            <p><?php esc_html_e('Including:', 'phantom-core'); ?></p>
                            <ul style="list-style:disc;padding-left:20px;">
                                <li><?php esc_html_e('All theme settings', 'phantom-core'); ?></li>
                                <li><?php esc_html_e('Design presets and tokens', 'phantom-core'); ?></li>
                                <li><?php esc_html_e('Feature flag configurations', 'phantom-core'); ?></li>
                                <li><?php esc_html_e('Performance and SEO settings', 'phantom-core'); ?></li>
                                <li><?php esc_html_e('Animation and asset configurations', 'phantom-core'); ?></li>
                            </ul>
                        </div>

                        <label style="display:block;margin:15px 0;">
                            <input type="checkbox" name="confirm_reset" value="1" required />
                            <strong><?php esc_html_e('I understand this cannot be undone. Please reset everything.', 'phantom-core'); ?></strong>
                        </label>

                        <?php submit_button(__('Reset All Phantom Settings', 'phantom-core'), 'delete', 'reset_all_btn'); ?>
                    </form>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    private function build_export_data(): array {
        global $wpdb;
        $data = [
            'exported_at' => current_time('mysql'),
            'php_version' => phpversion(),
            'wp_version' => get_bloginfo('version'),
            'phantom_version' => PHANTOM_CORE_VERSION,
            'label' => sanitize_text_field(wp_unslash($_POST['export_label'] ?? '')),
        ];

        if (isset($_POST['include_settings'])) {
            $options = $wpdb->get_results(
                $wpdb->prepare("SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s", 'phantom_%')
            );
            $data['options'] = [];
            foreach ($options as $opt) {
                $data['options'][$opt->option_name] = $opt->option_value;
            }
        }

        if (isset($_POST['include_presets'])) {
            $dsm = DesignSystemManager::get_instance();
            $data['presets'] = $dsm->availablePresets();
            $data['current_preset'] = $dsm->currentPreset();
        }

        if (isset($_POST['include_tokens'])) {
            $dsm = DesignSystemManager::get_instance();
            $data['tokens'] = $dsm->tokens();
        }

        if (isset($_POST['include_dna'])) {
            $dsm = DesignSystemManager::get_instance();
            $data['theme_dna'] = $dsm->currentThemeDNA();
        }

        if (isset($_POST['include_custom_css'])) {
            $data['custom_css'] = get_option('phantom_custom_css', '');
        }

        return $data;
    }

    /**
     * Handle admin-post.php export action.
     */
    public function handle_export(): void {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions.', 'phantom-core'));
        }
        if (!isset($_POST['phantom_backup_nonce']) || !wp_verify_nonce(wp_unslash($_POST['phantom_backup_nonce']), 'phantom_backup_export')) {
            wp_die(esc_html__('Security check failed.', 'phantom-core'));
        }

        $data = $this->build_export_data();
        $json = wp_json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $filename = 'phantom-export-' . gmdate('Y-m-d-His') . '.json';

        // Also save as backup
        update_option('phantom_backup_' . gmdate('Y_m_d_His'), $json);

        // Send file download
        nocache_headers();
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($json));
        echo $json;
        exit;
    }

    /**
     * Handle admin-post.php import action.
     */
    public function handle_import(): void {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions.', 'phantom-core'));
        }
        if (!isset($_POST['phantom_import_nonce']) || !wp_verify_nonce(wp_unslash($_POST['phantom_import_nonce']), 'phantom_backup_import')) {
            wp_die(esc_html__('Security check failed.', 'phantom-core'));
        }

        if (!isset($_FILES['import_file']) || UPLOAD_ERR_OK !== $_FILES['import_file']['error']) {
            wp_redirect(add_query_arg(['page' => 'phantom-backup-restore', 'tab' => 'import', 'import_error' => '1'], admin_url('admin.php')));
            exit;
        }

        $content = file_get_contents($_FILES['import_file']['tmp_name']);
        $data = json_decode($content, true);

        if (!$data || !isset($data['options'])) {
            wp_redirect(add_query_arg(['page' => 'phantom-backup-restore', 'tab' => 'import', 'import_error' => '2'], admin_url('admin.php')));
            exit;
        }

        $overwrite = isset($_POST['overwrite']) && '1' === $_POST['overwrite'];
        foreach ($data['options'] as $key => $value) {
            if ($overwrite || false === get_option($key)) {
                update_option($key, $value);
            }
        }

        wp_redirect(add_query_arg(['page' => 'phantom-backup-restore', 'tab' => 'import', 'import_success' => '1'], admin_url('admin.php')));
        exit;
    }

    private function handle_actions(): void {
        if ('POST' !== strtoupper($_SERVER['REQUEST_METHOD'] ?? '')) return;

        // Restore a backup
        if (isset($_POST['action']) && 'restore' === $_POST['action']) {
            if (!isset($_POST['phantom_restore_nonce']) || !wp_verify_nonce(wp_unslash($_POST['phantom_restore_nonce']), 'phantom_restore_backup')) return;
            if (!current_user_can('manage_options')) return;
            $backup_name = sanitize_key($_POST['backup_name'] ?? '');
            $backup_data = get_option($backup_name);
            if ($backup_data) {
                $decoded = json_decode($backup_data, true);
                if ($decoded && isset($decoded['options'])) {
                    foreach ($decoded['options'] as $key => $value) {
                        update_option($key, $value);
                    }
                    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Backup restored successfully.', 'phantom-core') . '</p></div>';
                }
            }
        }

        // Delete a backup
        if (isset($_POST['action']) && 'delete_backup' === $_POST['action']) {
            if (!isset($_POST['phantom_delete_nonce']) || !wp_verify_nonce(wp_unslash($_POST['phantom_delete_nonce']), 'phantom_delete_backup')) return;
            if (!current_user_can('manage_options')) return;
            $backup_name = sanitize_key($_POST['backup_name'] ?? '');
            delete_option($backup_name);
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Backup deleted.', 'phantom-core') . '</p></div>';
        }

        // Reset everything
        if (isset($_POST['action']) && 'reset_all' === $_POST['action']) {
            if (!isset($_POST['phantom_reset_nonce']) || !wp_verify_nonce(wp_unslash($_POST['phantom_reset_nonce']), 'phantom_reset_all')) return;
            if (!current_user_can('manage_options')) return;
            if (!isset($_POST['confirm_reset'])) {
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('You must confirm the reset.', 'phantom-core') . '</p></div>';
                return;
            }
            global $wpdb;
            $deleted = $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", 'phantom_%'));
            echo '<div class="notice notice-warning is-dismissible"><p>' . sprintf(esc_html__('Reset complete. %d options deleted. Phantom Core will reinitialize with defaults on next page load.', 'phantom-core'), $deleted) . '</p></div>';
        }
    }
}
