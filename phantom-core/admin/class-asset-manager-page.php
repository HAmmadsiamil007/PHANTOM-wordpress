<?php
declare(strict_types=1);

namespace PhantomCore\Admin;

defined('ABSPATH') || exit;

class AssetManagerPage {
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

        $this->handle_save();

        $cdn_enabled = get_option('phantom_asset_cdn_enabled', '0');
        $cdn_url = get_option('phantom_asset_cdn_url', '');
        $version_param = get_option('phantom_asset_version_param', '1');
        $concat_css = get_option('phantom_asset_concat_css', '0');

        // Collect registered assets
        $wp_styles = wp_styles();
        $wp_scripts = wp_scripts();
        $registered_css = [];
        $registered_js = [];

        foreach ($wp_styles->registered as $handle => $dep) {
            if (!empty($dep->src)) {
                $registered_css[] = [
                    'handle' => $handle,
                    'src' => $dep->src,
                    'ver' => $dep->ver ?: '',
                    'deps' => implode(', ', $dep->deps),
                ];
            }
        }

        foreach ($wp_scripts->registered as $handle => $dep) {
            if (!empty($dep->src)) {
                $registered_js[] = [
                    'handle' => $handle,
                    'src' => $dep->src,
                    'ver' => $dep->ver ?: '',
                    'deps' => implode(', ', $dep->deps),
                    'in_footer' => $dep->extra['group'] ?? 0 ? __('Yes', 'phantom-core') : __('No', 'phantom-core'),
                ];
            }
        }

        // Phantom-specific assets
        $phantom_dir = PHANTOM_CORE_URL . 'frontend/assets/';
        $phantom_assets = [
            'css' => glob(PHANTOM_CORE_PATH . 'frontend/assets/css/*.css'),
            'js' => glob(PHANTOM_CORE_PATH . 'frontend/assets/js/*.js'),
        ];
        ?>
        <div class="wrap phantom-asset-manager">
            <h1><?php esc_html_e('Asset Manager', 'phantom-core'); ?></h1>
            <p class="description"><?php esc_html_e('Manage CSS, JavaScript, and media assets. Configure CDN, versioning, and concatenation.', 'phantom-core'); ?></p>

            <form method="post" action="">
                <?php wp_nonce_field('phantom_asset_save', 'phantom_asset_nonce'); ?>
                <input type="hidden" name="action" value="save_assets" />

                <!-- CDN Settings -->
                <div class="phantom-section" style="margin-top:20px;">
                    <h2><?php esc_html_e('CDN Configuration', 'phantom-core'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_html_e('Enable CDN', 'phantom-core'); ?></th>
                            <td>
                                <label><input type="checkbox" name="cdn_enabled" value="1" <?php checked('1', $cdn_enabled); ?> />
                                <?php esc_html_e('Serve static assets from a CDN.', 'phantom-core'); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="cdn_url"><?php esc_html_e('CDN Base URL', 'phantom-core'); ?></label></th>
                            <td>
                                <input type="url" id="cdn_url" name="cdn_url" class="regular-text" value="<?php echo esc_attr($cdn_url); ?>" placeholder="https://cdn.example.com" />
                                <p class="description"><?php esc_html_e('Full URL to your CDN (e.g., https://cdn.mysite.com).', 'phantom-core'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Optimization -->
                <div class="phantom-section" style="margin-top:30px;">
                    <h2><?php esc_html_e('Asset Optimization', 'phantom-core'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_html_e('Query String Versioning', 'phantom-core'); ?></th>
                            <td>
                                <label><input type="checkbox" name="version_param" value="1" <?php checked('1', $version_param); ?> />
                                <?php esc_html_e('Append version query strings to asset URLs for cache busting.', 'phantom-core'); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Concatenate CSS', 'phantom-core'); ?></th>
                            <td>
                                <label><input type="checkbox" name="concat_css" value="1" <?php checked('1', $concat_css); ?> />
                                <?php esc_html_e('Combine multiple CSS files into a single request.', 'phantom-core'); ?></label>
                            </td>
                        </tr>
                    </table>
                </div>
            </form>

            <!-- Phantom Assets -->
            <div class="phantom-section" style="margin-top:30px;">
                <h2><?php esc_html_e('PHANTOM Core Assets', 'phantom-core'); ?></h2>
                <p class="description"><?php esc_html_e('Files located in your Phantom Core installation.', 'phantom-core'); ?></p>

                <h3><?php esc_html_e('CSS Files', 'phantom-core'); ?> (<?php echo count($phantom_assets['css']); ?>)</h3>
                <table class="widefat striped" style="max-width:800px;">
                    <thead><tr><th><?php esc_html_e('File', 'phantom-core'); ?></th><th><?php esc_html_e('Size', 'phantom-core'); ?></th></tr></thead>
                    <tbody>
                        <?php foreach ($phantom_assets['css'] as $file): $name = basename($file); ?>
                            <tr>
                                <td><code><?php echo esc_html('frontend/assets/css/' . $name); ?></code></td>
                                <td><?php echo esc_html(size_format(filesize($file), 2) ?: '—'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <h3 style="margin-top:20px;"><?php esc_html_e('JavaScript Files', 'phantom-core'); ?> (<?php echo count($phantom_assets['js']); ?>)</h3>
                <table class="widefat striped" style="max-width:800px;">
                    <thead><tr><th><?php esc_html_e('File', 'phantom-core'); ?></th><th><?php esc_html_e('Size', 'phantom-core'); ?></th></tr></thead>
                    <tbody>
                        <?php foreach ($phantom_assets['js'] as $file): $name = basename($file); ?>
                            <tr>
                                <td><code><?php echo esc_html('frontend/assets/js/' . $name); ?></code></td>
                                <td><?php echo esc_html(size_format(filesize($file), 2) ?: '—'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- All Registered CSS -->
            <details style="margin-top:30px;">
                <summary style="cursor:pointer;font-weight:600;font-size:16px;"><?php esc_html_e('All Registered CSS Handles', 'phantom-core'); ?> (<?php echo count($registered_css); ?>)</summary>
                <table class="widefat striped" style="margin-top:10px;">
                    <thead><tr><th><?php esc_html_e('Handle', 'phantom-core'); ?></th><th><?php esc_html_e('Source', 'phantom-core'); ?></th><th><?php esc_html_e('Version', 'phantom-core'); ?></th><th><?php esc_html_e('Dependencies', 'phantom-core'); ?></th></tr></thead>
                    <tbody>
                        <?php foreach ($registered_css as $asset): ?>
                            <tr><td><code><?php echo esc_html($asset['handle']); ?></code></td><td><code><?php echo esc_html($asset['src']); ?></code></td><td><?php echo esc_html($asset['ver']); ?></td><td><?php echo esc_html($asset['deps']); ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </details>

            <!-- All Registered JS -->
            <details style="margin-top:20px;">
                <summary style="cursor:pointer;font-weight:600;font-size:16px;"><?php esc_html_e('All Registered JS Handles', 'phantom-core'); ?> (<?php echo count($registered_js); ?>)</summary>
                <table class="widefat striped" style="margin-top:10px;">
                    <thead><tr><th><?php esc_html_e('Handle', 'phantom-core'); ?></th><th><?php esc_html_e('Source', 'phantom-core'); ?></th><th><?php esc_html_e('Version', 'phantom-core'); ?></th><th><?php esc_html_e('Footer', 'phantom-core'); ?></th><th><?php esc_html_e('Dependencies', 'phantom-core'); ?></th></tr></thead>
                    <tbody>
                        <?php foreach ($registered_js as $asset): ?>
                            <tr><td><code><?php echo esc_html($asset['handle']); ?></code></td><td><code><?php echo esc_html($asset['src']); ?></code></td><td><?php echo esc_html($asset['ver']); ?></td><td><?php echo esc_html($asset['in_footer']); ?></td><td><?php echo esc_html($asset['deps']); ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </details>

            <!-- Persist CDN form submit -->
            <script>
            (function() {
                var form = document.querySelector('.phantom-asset-manager form');
                if (form) {
                    var submit = document.createElement('p');
                    submit.className = 'submit';
                    var btn = document.createElement('button');
                    btn.type = 'submit';
                    btn.className = 'button button-primary';
                    btn.textContent = '<?php echo esc_js(__('Save Asset Settings', 'phantom-core')); ?>';
                    submit.appendChild(btn);
                    form.appendChild(submit);
                }
            })();
            </script>
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

        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Asset settings saved.', 'phantom-core') . '</p></div>';
    }
}
