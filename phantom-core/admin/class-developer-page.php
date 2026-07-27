<?php
declare(strict_types=1);

namespace PhantomCore\Admin;

defined('ABSPATH') || exit;

class DeveloperPage {
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

        $debug_mode = get_option('phantom_dev_debug_mode', '0');
        $script_debug = get_option('phantom_dev_script_debug', '0');
        $hook_logging = get_option('phantom_dev_hook_logging', '0');
        $rest_logging = get_option('phantom_dev_rest_logging', '0');
        $query_monitor = get_option('phantom_dev_query_monitor', '0');
        $template_override = get_option('phantom_dev_template_override', '');
        $active_tab = sanitize_key($_GET['tab'] ?? 'settings');

        // Collect hooks
        global $wp_filter;
        $hook_count = is_array($wp_filter) ? count($wp_filter) : 0;
        $action_count = did_action('init') + did_action('plugins_loaded') + did_action('wp_loaded');
        ?>
        <div class="wrap phantom-developer">
            <h1><?php esc_html_e('Developer Tools', 'phantom-core'); ?></h1>
            <p class="description"><?php esc_html_e('Debugging tools, hook inspection, and development utilities for Phantom Core.', 'phantom-core'); ?></p>

            <nav class="nav-tab-wrapper" style="margin-top:20px;">
                <a href="?page=phantom-developer&tab=settings" class="nav-tab <?php echo 'settings' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Settings', 'phantom-core'); ?></a>
                <a href="?page=phantom-developer&tab=hooks" class="nav-tab <?php echo 'hooks' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Hook Inspector', 'phantom-core'); ?></a>
                <a href="?page=phantom-developer&tab=rest" class="nav-tab <?php echo 'rest' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('REST API', 'phantom-core'); ?></a>
                <a href="?page=phantom-developer&tab=options" class="nav-tab <?php echo 'options' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Options', 'phantom-core'); ?></a>
            </nav>

            <?php if ('settings' === $active_tab): ?>
                <form method="post" action="">
                    <?php wp_nonce_field('phantom_dev_save', 'phantom_dev_nonce'); ?>
                    <input type="hidden" name="action" value="save_dev_settings" />
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_html_e('Debug Mode', 'phantom-core'); ?></th>
                            <td>
                                <label><input type="checkbox" name="debug_mode" value="1" <?php checked('1', $debug_mode); ?> />
                                <?php esc_html_e('Enable Phantom Core debug mode (verbose logging, detailed error messages).', 'phantom-core'); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Unminified Scripts', 'phantom-core'); ?></th>
                            <td>
                                <label><input type="checkbox" name="script_debug" value="1" <?php checked('1', $script_debug); ?> />
                                <?php esc_html_e('Load unminified JavaScript and CSS files for easier debugging.', 'phantom-core'); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Hook Logging', 'phantom-core'); ?></th>
                            <td>
                                <label><input type="checkbox" name="hook_logging" value="1" <?php checked('1', $hook_logging); ?> />
                                <?php esc_html_e('Log all fired WordPress hooks to the debug log (VERBOSE!).', 'phantom-core'); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('REST API Logging', 'phantom-core'); ?></th>
                            <td>
                                <label><input type="checkbox" name="rest_logging" value="1" <?php checked('1', $rest_logging); ?> />
                                <?php esc_html_e('Log all Phantom REST API requests and responses.', 'phantom-core'); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Query Monitor', 'phantom-core'); ?></th>
                            <td>
                                <label><input type="checkbox" name="query_monitor" value="1" <?php checked('1', $query_monitor); ?> />
                                <?php esc_html_e('Log database queries to debug log (performance impact).', 'phantom-core'); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="template_override"><?php esc_html_e('Template Override', 'phantom-core'); ?></label></th>
                            <td>
                                <input type="text" id="template_override" name="template_override" class="regular-text" value="<?php echo esc_attr($template_override); ?>" placeholder="e.g., fashion" />
                                <p class="description"><?php esc_html_e('Force a specific template pack for testing (overrides the active setting).', 'phantom-core'); ?></p>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button(__('Save Developer Settings', 'phantom-core'), 'primary', 'save_dev'); ?>
                </form>

            <?php elseif ('hooks' === $active_tab): ?>
                <div class="phantom-section" style="margin-top:20px;">
                    <h2><?php esc_html_e('Hook Inspector', 'phantom-core'); ?></h2>
                    <p><?php esc_html_e('Currently registered hooks:', 'phantom-core'); ?> <strong><?php echo esc_html((string) $hook_count); ?></strong></p>
                    <p><?php esc_html_e('Actions fired:', 'phantom-core'); ?> <strong><?php echo esc_html((string) $action_count); ?></strong></p>
                    <p class="description"><?php esc_html_e('Below is a sample of the most relevant Phantom Core hooks:', 'phantom-core'); ?></p>
                    <table class="widefat striped" style="max-width:1000px;">
                        <thead><tr><th><?php esc_html_e('Hook', 'phantom-core'); ?></th><th><?php esc_html_e('Type', 'phantom-core'); ?></th><th><?php esc_html_e('Callbacks', 'phantom-core'); ?></th></tr></thead>
                        <tbody>
                            <?php
                            $phantom_hooks = array_filter(
                                is_array($wp_filter) ? array_keys($wp_filter) : [],
                                fn($h) => str_starts_with($h, 'phantom_')
                            );
                            if (empty($phantom_hooks)):
                            ?>
                            <tr><td colspan="3"><em><?php esc_html_e('No Phantom-specific hooks found.', 'phantom-core'); ?></em></td></tr>
                            <?php else: ?>
                                <?php foreach (array_slice($phantom_hooks, 0, 50) as $hook_name):
                                    $hook_obj = $wp_filter[$hook_name] ?? null;
                                    $cb_count = 0;
                                    if ($hook_obj instanceof \WP_Hook) {
                                        foreach ($hook_obj->callbacks as $priority => $cbs) {
                                            $cb_count += count($cbs);
                                        }
                                    }
                                ?>
                                <tr>
                                    <td><code><?php echo esc_html($hook_name); ?></code></td>
                                    <td><?php echo str_starts_with($hook_name, 'phantom_event_') ? esc_html__('Event', 'phantom-core') : esc_html__('Action/Filter', 'phantom-core'); ?></td>
                                    <td><?php echo esc_html((string) $cb_count); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ('rest' === $active_tab): ?>
                <div class="phantom-section" style="margin-top:20px;">
                    <h2><?php esc_html_e('REST API Inspector', 'phantom-core'); ?></h2>
                    <p><?php esc_html_e('All registered Phantom REST API routes under phantom/v1:', 'phantom-core'); ?></p>
                    <?php
                    $rest_routes = [];
                    if (function_exists('rest_get_server')) {
                        $server = rest_get_server();
                        $all_routes = $server->get_routes('phantom/v1');
                        if (!empty($all_routes)) {
                            $rest_routes = $all_routes;
                        }
                    }
                    ?>
                    <?php if (!empty($rest_routes)): ?>
                        <table class="widefat striped" style="max-width:1000px;">
                            <thead><tr><th><?php esc_html_e('Route', 'phantom-core'); ?></th><th><?php esc_html_e('Methods', 'phantom-core'); ?></th></tr></thead>
                            <tbody>
                                <?php foreach ($rest_routes as $route => $handlers): ?>
                                    <tr>
                                        <td><code><?php echo esc_html($route); ?></code></td>
                                        <td>
                                            <?php
                                            $methods = [];
                                            foreach ($handlers as $handler) {
                                                if (isset($handler['methods'])) {
                                                    $methods = array_merge($methods, array_keys($handler['methods']));
                                                }
                                            }
                                            echo esc_html(implode(', ', array_unique($methods)));
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p><em><?php esc_html_e('REST server not available or no Phantom routes registered.', 'phantom-core'); ?></em></p>
                    <?php endif; ?>
                </div>

            <?php elseif ('options' === $active_tab): ?>
                <div class="phantom-section" style="margin-top:20px;">
                    <h2><?php esc_html_e('Phantom Options Inspector', 'phantom-core'); ?></h2>
                    <p><?php esc_html_e('All WordPress options with the phantom_ prefix:', 'phantom-core'); ?></p>
                    <?php
                    global $wpdb;
                    $phantom_options = $wpdb->get_results(
                        $wpdb->prepare(
                            "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s ORDER BY option_name ASC LIMIT 200",
                            'phantom_%'
                        )
                    );
                    ?>
                    <table class="widefat striped" style="max-width:1000px;">
                        <thead><tr><th><?php esc_html_e('Option Name', 'phantom-core'); ?></th><th><?php esc_html_e('Value', 'phantom-core'); ?></th></tr></thead>
                        <tbody>
                            <?php foreach ($phantom_options as $opt): ?>
                                <tr>
                                    <td><code><?php echo esc_html($opt->option_name); ?></code></td>
                                    <td><code style="font-size:11px;word-break:break-all;"><?php echo esc_html(substr($opt->option_value, 0, 120)) . (strlen($opt->option_value) > 120 ? '...' : ''); ?></code></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($phantom_options)): ?>
                                <tr><td colspan="2"><em><?php esc_html_e('No phantom_ options found.', 'phantom-core'); ?></em></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    private function handle_save(): void {
        if ('POST' !== strtoupper($_SERVER['REQUEST_METHOD'] ?? '')) return;
        if (!isset($_POST['phantom_dev_nonce'])) return;
        if (!wp_verify_nonce(wp_unslash($_POST['phantom_dev_nonce']), 'phantom_dev_save')) {
            wp_die(esc_html__('Security check failed.', 'phantom-core'));
        }
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission.', 'phantom-core'));
        }

        update_option('phantom_dev_debug_mode', isset($_POST['debug_mode']) ? '1' : '0');
        update_option('phantom_dev_script_debug', isset($_POST['script_debug']) ? '1' : '0');
        update_option('phantom_dev_hook_logging', isset($_POST['hook_logging']) ? '1' : '0');
        update_option('phantom_dev_rest_logging', isset($_POST['rest_logging']) ? '1' : '0');
        update_option('phantom_dev_query_monitor', isset($_POST['query_monitor']) ? '1' : '0');
        update_option('phantom_dev_template_override', sanitize_text_field(wp_unslash($_POST['template_override'] ?? '')));

        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Developer settings saved.', 'phantom-core') . '</p></div>';
    }
}
