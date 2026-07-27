<?php
declare(strict_types=1);

namespace PhantomCore\Feature;

defined('ABSPATH') || exit;

class Feature_Manager {
    private static ?self $instance = null;
    private Feature_Registry $registry;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->registry = Feature_Registry::get_instance();
    }

    public function init(): void {
        $this->registry->load();

        add_action('admin_post_phantom_feature_toggle', [$this, 'handle_toggle']);
        add_action('admin_post_phantom_feature_bulk', [$this, 'handle_bulk']);
        add_action('admin_notices', [$this, 'show_admin_notices']);
    }

    public function render_admin_page(): void {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions.', 'phantom-core'));
        }

        $this->handle_postback();
        $features = $this->registry->get_all();
        $categories = $this->registry->get_categories();

        $active_tab = sanitize_key($_GET['tab'] ?? 'all');
        ?>
        <div class="wrap phantom-features-wrap">
            <h1><?php esc_html_e('Feature Flags', 'phantom-core'); ?></h1>
            <p class="description">
                <?php esc_html_e('Enable or disable Phantom Core features. Disabled features will not load their assets, JavaScript, or backend processing — improving performance and reducing page weight.', 'phantom-core'); ?>
            </p>

            <form method="post" action="">
                <?php wp_nonce_field('phantom_feature_save', 'phantom_feature_nonce'); ?>
                <input type="hidden" name="action" value="phantom_feature_save" />

                <!-- Category Tabs -->
                <nav class="nav-tab-wrapper phantom-feature-tabs">
                    <a href="<?php echo esc_url(add_query_arg('tab', 'all')); ?>"
                       class="nav-tab <?php echo 'all' === $active_tab ? 'nav-tab-active' : ''; ?>">
                        <?php esc_html_e('All', 'phantom-core'); ?>
                        <span class="count">(<?php echo count($features); ?>)</span>
                    </a>
                    <?php foreach ($categories as $cat): ?>
                        <?php $cat_features = $this->registry->get_by_category($cat); ?>
                        <a href="<?php echo esc_url(add_query_arg('tab', $cat)); ?>"
                           class="nav-tab <?php echo $active_tab === $cat ? 'nav-tab-active' : ''; ?>">
                            <?php echo esc_html(ucwords(str_replace('_', ' ', $cat))); ?>
                            <span class="count">(<?php echo count($cat_features); ?>)</span>
                        </a>
                    <?php endforeach; ?>
                </nav>

                <!-- Feature Toggles -->
                <table class="wp-list-table widefat fixed striped phantom-features-table" style="margin-top:20px;">
                    <thead>
                        <tr>
                            <th scope="col" style="width:40px;" class="check-column">
                                <input type="checkbox" id="phantom-feature-select-all" />
                            </th>
                            <th scope="col"><?php esc_html_e('Feature', 'phantom-core'); ?></th>
                            <th scope="col" style="width:120px;"><?php esc_html_e('Status', 'phantom-core'); ?></th>
                            <th scope="col" style="width:100px;"><?php esc_html_e('Default', 'phantom-core'); ?></th>
                            <th scope="col"><?php esc_html_e('Description', 'phantom-core'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $display_features = 'all' === $active_tab
                            ? $features
                            : $this->registry->get_by_category($active_tab);

                        foreach ($display_features as $feature):
                            $enabled = $feature->enabled();
                            $overridden = $feature->is_overridden();
                            $row_class = $overridden ? 'overridden' : 'default';
                        ?>
                        <tr class="phantom-feature-row <?php echo esc_attr($row_class); ?>">
                            <th scope="row" class="check-column">
                                <input type="checkbox" name="feature_ids[]" value="<?php echo esc_attr($feature->id); ?>" />
                            </th>
                            <td>
                                <strong><?php echo esc_html($feature->label); ?></strong>
                                <code style="display:block;font-size:11px;color:#666;margin-top:2px;"><?php echo esc_html($feature->id); ?></code>
                            </td>
                            <td>
                                <label class="phantom-toggle-label">
                                    <input type="hidden" name="features[<?php echo esc_attr($feature->id); ?>]" value="0" />
                                    <input type="checkbox"
                                           name="features[<?php echo esc_attr($feature->id); ?>]"
                                           value="1"
                                           class="phantom-toggle"
                                           data-feature-id="<?php echo esc_attr($feature->id); ?>"
                                           <?php checked(true, $enabled); ?> />
                                    <span class="phantom-toggle-switch <?php echo $enabled ? 'active' : ''; ?>">
                                        <span class="phantom-toggle-indicator"></span>
                                    </span>
                                </label>
                                <span class="phantom-feature-status">
                                    <?php echo $enabled
                                        ? '<span style="color:#46b450;">' . esc_html__('Enabled', 'phantom-core') . '</span>'
                                        : '<span style="color:#dc3232;">' . esc_html__('Disabled', 'phantom-core') . '</span>'; ?>
                                </span>
                            </td>
                            <td>
                                <span class="phantom-default-badge <?php echo $feature->default ? 'default-on' : 'default-off'; ?>">
                                    <?php echo $feature->default
                                        ? esc_html__('ON', 'phantom-core')
                                        : esc_html__('OFF', 'phantom-core'); ?>
                                </span>
                                <?php if ($overridden): ?>
                                    <br /><small style="color:#999;"><?php esc_html_e('Overridden', 'phantom-core'); ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="description">
                                <?php echo esc_html($feature->description); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="phantom-features-actions" style="margin-top:15px;display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                    <p class="submit" style="margin:0;">
                        <button type="submit" class="button button-primary">
                            <?php esc_html_e('Save Changes', 'phantom-core'); ?>
                        </button>
                    </p>
                    <button type="button" class="button" id="phantom-features-enable-selected" data-action="enable">
                        <?php esc_html_e('Enable Selected', 'phantom-core'); ?>
                    </button>
                    <button type="button" class="button" id="phantom-features-disable-selected" data-action="disable">
                        <?php esc_html_e('Disable Selected', 'phantom-core'); ?>
                    </button>
                    <button type="button" class="button" id="phantom-features-reset-selected" data-action="reset">
                        <?php esc_html_e('Reset Selected to Default', 'phantom-core'); ?>
                    </button>
                </div>
            </form>

            <style>
                .phantom-toggle-label { display:inline-flex;align-items:center;cursor:pointer;vertical-align:middle; }
                .phantom-toggle-label input[type="checkbox"] { position:absolute;opacity:0;width:0;height:0; }
                .phantom-toggle-switch { position:relative;display:inline-block;width:44px;height:24px;background:#ccc;border-radius:12px;transition:background .3s;margin-right:8px; }
                .phantom-toggle-switch.active { background:#46b450; }
                .phantom-toggle-indicator { position:absolute;top:2px;left:2px;width:20px;height:20px;background:#fff;border-radius:50%;transition:transform .3s;box-shadow:0 1px 3px rgba(0,0,0,0.2); }
                .phantom-toggle-switch.active .phantom-toggle-indicator { transform:translateX(20px); }
                .phantom-features-table th { font-weight:600; }
                .phantom-features-table tr.overridden { background:#f0f8ff; }
                .phantom-default-badge { display:inline-block;padding:2px 8px;border-radius:3px;font-size:11px;font-weight:600; }
                .phantom-default-badge.default-on { background:#e8f5e9;color:#2e7d32; }
                .phantom-default-badge.default-off { background:#fbe9e7;color:#c62828; }
                .phantom-feature-tabs .count { font-weight:400;color:#888;font-size:12px; }
            </style>

            <script>
            (function() {
                var selectAll = document.getElementById('phantom-feature-select-all');
                if (selectAll) {
                    selectAll.addEventListener('change', function() {
                        var checkboxes = document.querySelectorAll('.phantom-features-table input[name="feature_ids[]"]');
                        checkboxes.forEach(function(cb) { cb.checked = selectAll.checked; });
                    });
                }

                document.querySelectorAll('#phantom-features-enable-selected, #phantom-features-disable-selected, #phantom-features-reset-selected').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        var checked = document.querySelectorAll('.phantom-features-table input[name="feature_ids[]"]:checked');
                        if (checked.length === 0) { alert('Please select features first.'); return; }

                        var action = this.getAttribute('data-action');
                        checked.forEach(function(cb) {
                            var row = cb.closest('tr');
                            var toggle = row.querySelector('.phantom-toggle');
                            if (toggle) {
                                if (action === 'enable') toggle.checked = true;
                                else if (action === 'disable') toggle.checked = false;
                            }
                        });
                    });
                });
            })();
            </script>
        </div>
        <?php
    }

    private function handle_postback(): void {
        if ('POST' !== strtoupper($_SERVER['REQUEST_METHOD'] ?? '')) return;
        if (!isset($_POST['phantom_feature_nonce']) || !wp_verify_nonce(wp_unslash($_POST['phantom_feature_nonce']), 'phantom_feature_save')) return;
        if (!current_user_can('manage_options')) return;
        if (!isset($_POST['features']) || !is_array($_POST['features'])) return;

        $features = wp_unslash($_POST['features']);
        $count = 0;

        foreach ($features as $id => $value) {
            $id = sanitize_key($id);
            if (!$this->registry->has($id)) continue;
            $this->registry->set_enabled($id, '1' === $value);
            $count++;
        }

        if ($count > 0) {
            set_transient('phantom_feature_notice', [
                'status' => 'success',
                'message' => sprintf(esc_html__('%d feature flags updated.', 'phantom-core'), $count),
            ], 30);
        }
    }

    public function show_admin_notices(): void {
        $notice = get_transient('phantom_feature_notice');
        if (!$notice) return;
        delete_transient('phantom_feature_notice');
        echo '<div class="notice notice-' . esc_attr($notice['status']) . ' is-dismissible"><p>' . esc_html($notice['message']) . '</p></div>';
    }

    public function handle_toggle(): void {
        if (!current_user_can('manage_options')) wp_die(-1);
        check_admin_referer('phantom_feature_toggle');

        $id = sanitize_key($_POST['feature_id'] ?? '');
        $enabled = isset($_POST['enabled']) && '1' === $_POST['enabled'];

        if ($this->registry->has($id)) {
            $this->registry->set_enabled($id, $enabled);
            wp_send_json_success(['id' => $id, 'enabled' => $enabled]);
        }
        wp_send_json_error(['message' => 'Feature not found.']);
    }

    public function handle_bulk(): void {
        if (!current_user_can('manage_options')) wp_die(-1);
        check_admin_referer('phantom_feature_bulk');

        $action = sanitize_key($_POST['bulk_action'] ?? '');
        $ids = isset($_POST['feature_ids']) && is_array($_POST['feature_ids'])
            ? array_map('sanitize_key', wp_unslash($_POST['feature_ids']))
            : [];

        $count = 0;
        foreach ($ids as $id) {
            if (!$this->registry->has($id)) continue;
            if ('enable' === $action) {
                $this->registry->set_enabled($id, true);
                $count++;
            } elseif ('disable' === $action) {
                $this->registry->set_enabled($id, false);
                $count++;
            } elseif ('reset' === $action) {
                $this->registry->reset($id);
                $count++;
            }
        }

        wp_send_json_success(['count' => $count, 'action' => $action]);
    }
}
