<?php
declare(strict_types=1);

namespace PhantomCore\Diagnostics;

use PhantomCore\Design\DesignSystemManager;
use PhantomCore\Design\TokenRegistry;
use PhantomCore\Components\Component_Registry;
use PhantomCore\Animation\Animation_Registry;
use PhantomCore\Registry\Asset_Registry;
use PhantomCore\Feature\Feature_Registry;
use PhantomCore\Settings_Registry;
use PhantomCore\Engine\Cache;

defined('ABSPATH') || exit;

class Diagnostics_Panel {
    private static ?self $instance = null;
    private array $healthReport = [];
    private bool $diagnosticsRun = false;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init(): void {
        add_action('admin_menu', [$this, 'register_admin_page'], 20);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function register_admin_page(): void {
        add_submenu_page(
            'phantom-dashboard',
            __('Diagnostics', 'phantom-core'),
            __('Diagnostics', 'phantom-core'),
            'manage_options',
            'phantom-diagnostics',
            [$this, 'render_page']
        );
    }

    public function enqueue_assets(string $hook): void {
        if (!str_contains($hook, 'phantom-diagnostics')) return;
        wp_enqueue_style('phantom-admin', PHANTOM_CORE_URL . 'admin/css/admin.css', [], PHANTOM_CORE_VERSION);
        wp_add_inline_style('phantom-admin', '
            .phantom-diag { max-width:1200px }
            .phantom-diag h1 { margin-bottom:10px }
            .phantom-diag .description { margin-bottom:20px }
            .phantom-diag-score { display:inline-flex;align-items:center;gap:12px;padding:16px 24px;border-radius:8px;font-size:24px;font-weight:700;margin:16px 0 24px }
            .phantom-diag-score.green { background:#e6f7e6;color:#1a7d1a;border:1px solid #b8e6b8 }
            .phantom-diag-score.yellow { background:#fff8e6;color:#8a6d00;border:1px solid #f0dca0 }
            .phantom-diag-score.red { background:#fde8e8;color:#b91c1c;border:1px solid #f5c6c6 }
            .phantom-diag-grid { display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:16px;margin:20px 0 }
            .phantom-diag-card { background:#fff;border:1px solid #dcdcde;border-radius:6px;padding:16px }
            .phantom-diag-card h2 { margin:0 0 12px;font-size:14px;text-transform:uppercase;letter-spacing:0.5px;color:#50575e;border-bottom:1px solid #f0f0f1;padding-bottom:8px }
            .phantom-diag-card table { width:100%;border-collapse:collapse }
            .phantom-diag-card td { padding:6px 8px;font-size:13px;border-bottom:1px solid #f0f0f1 }
            .phantom-diag-card td:first-child { color:#50575e;width:55% }
            .phantom-diag-badge { display:inline-block;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600 }
            .phantom-diag-badge.ok { background:#e6f7e6;color:#1a7d1a }
            .phantom-diag-badge.warn { background:#fff8e6;color:#8a6d00 }
            .phantom-diag-badge.err { background:#fde8e8;color:#b91c1c }
            .phantom-diag-list { margin:0;padding:0;list-style:none }
            .phantom-diag-list li { padding:6px 8px;font-size:13px;border-bottom:1px solid #f0f0f1;display:flex;align-items:center;gap:8px }
            .phantom-diag-list li:last-child { border-bottom:none }
            .phantom-diag-actions { margin:20px 0;display:flex;gap:10px;flex-wrap:wrap }
        ');
    }

    public function get_health_report(): array {
        if ($this->diagnosticsRun) {
            return $this->healthReport;
        }

        $registries = [
            'component_registry' => $this->check_component_registry(),
            'token_registry'     => $this->check_token_registry(),
            'property_registry'  => $this->check_property_registry(),
            'asset_registry'     => $this->check_asset_registry(),
            'animation_registry' => $this->check_animation_registry(),
            'frontend_packs'     => $this->check_frontend_packs(),
        ];

        $components   = $this->check_instances();
        $cssVariables = $this->check_css_variables();
        $performance  = $this->check_performance();
        $woocommerce  = $this->check_woocommerce();
        $restApi      = $this->check_rest_api();
        $settings     = $this->check_settings();
        $features     = $this->check_features();

        $errors   = [];
        $warnings = [];

        foreach ($registries as $key => $result) {
            if (!$result['healthy']) {
                if ($result['critical'] ?? false) {
                    $errors[] = $result['message'];
                } else {
                    $warnings[] = $result['message'];
                }
            }
        }

        foreach ($components as $comp) {
            if (!$comp['healthy']) {
                $errors[] = $comp['message'];
            }
        }

        if (!$woocommerce['connected']) {
            $warnings[] = __('WooCommerce is not active — shop features unavailable.', 'phantom-core');
        }

        $healthyCount = 0;
        $totalChecked = 0;
        foreach ($registries as $r) {
            $totalChecked++;
            if ($r['healthy']) $healthyCount++;
        }
        foreach ($components as $c) {
            $totalChecked++;
            if ($c['healthy']) $healthyCount++;
        }
        $totalChecked += ($cssVariables['conflicts'] > 0 ? 0 : 1);
        if ($cssVariables['conflicts'] === 0) $healthyCount++;
        $totalChecked += ($woocommerce['connected'] ? 1 : 0);
        if ($woocommerce['connected']) $healthyCount++;

        $score = $totalChecked > 0 ? (int) round(($healthyCount / $totalChecked) * 100) : 100;
        $score = max(0, min(100, $score));

        $this->healthReport = [
            'score'         => $score,
            'registries'    => $registries,
            'components'    => $components,
            'css_variables' => $cssVariables,
            'performance'   => $performance,
            'woocommerce'   => $woocommerce,
            'rest_api'      => $restApi,
            'settings'      => $settings,
            'features'      => $features,
            'errors'        => $errors,
            'warnings'      => $warnings,
        ];

        $this->diagnosticsRun = true;
        return $this->healthReport;
    }

    public function check_component_registry(): array {
        try {
            $registry = Component_Registry::get_instance();
            $count = $registry->count();
            $categories = $registry->get_categories();
            return [
                'healthy'    => $count > 0,
                'count'      => $count,
                'categories' => $categories,
                'critical'   => true,
                'message'    => $count > 0
                    ? sprintf(__('Component Registry: %d components registered', 'phantom-core'), $count)
                    : __('Component Registry is empty — no components registered', 'phantom-core'),
            ];
        } catch (\Throwable $e) {
            return [
                'healthy'  => false,
                'count'    => 0,
                'critical' => true,
                'message'  => __('Component Registry error: ', 'phantom-core') . $e->getMessage(),
            ];
        }
    }

    public function check_token_registry(): array {
        try {
            if (class_exists(TokenRegistry::class)) {
                $registry = TokenRegistry::get_instance();
                $count = $registry->count();
                return [
                    'healthy'  => $count > 0,
                    'count'    => $count,
                    'critical' => true,
                    'message'  => $count > 0
                        ? sprintf(__('Token Registry: %d design tokens', 'phantom-core'), $count)
                        : __('Token Registry is empty', 'phantom-core'),
                ];
            }
            return [
                'healthy'  => false,
                'count'    => 0,
                'critical' => true,
                'message'  => __('Token Registry class not found', 'phantom-core'),
            ];
        } catch (\Throwable $e) {
            return [
                'healthy'  => false,
                'count'    => 0,
                'critical' => true,
                'message'  => __('Token Registry error: ', 'phantom-core') . $e->getMessage(),
            ];
        }
    }

    public function check_property_registry(): array {
        $propertyClass = 'PhantomCore\\Registry\\Property_Registry';
        $propFile = PHANTOM_CORE_PATH . 'includes/Registry/class-property-registry.php';
        if (class_exists($propertyClass) || file_exists($propFile)) {
            try {
                $registry = $propertyClass::get_instance();
                $count = method_exists($registry, 'count') ? $registry->count() : count($registry->get_all());
                return [
                    'healthy'  => $count > 0,
                    'count'    => $count,
                    'critical' => false,
                    'message'  => $count > 0
                        ? sprintf(__('Property Registry: %d properties', 'phantom-core'), $count)
                        : __('Property Registry is empty', 'phantom-core'),
                ];
            } catch (\Throwable $e) {
                return [
                    'healthy'  => false,
                    'count'    => 0,
                    'critical' => false,
                    'message'  => __('Property Registry error: ', 'phantom-core') . $e->getMessage(),
                ];
            }
        }
        return [
            'healthy'  => true,
            'count'    => 0,
            'critical' => false,
            'message'  => __('Property Registry: not implemented (optional)', 'phantom-core'),
        ];
    }

    public function check_asset_registry(): array {
        try {
            $registry = Asset_Registry::get_instance();
            $all = $registry->get_all();
            $jsCount = count($registry->get_all('js'));
            $cssCount = count($registry->get_all('css'));
            return [
                'healthy'  => count($all) > 0,
                'count'    => count($all),
                'js'       => $jsCount,
                'css'      => $cssCount,
                'critical' => true,
                'message'  => sprintf(__('Asset Registry: %d assets (%d JS, %d CSS)', 'phantom-core'), count($all), $jsCount, $cssCount),
            ];
        } catch (\Throwable $e) {
            return [
                'healthy'  => false,
                'count'    => 0,
                'critical' => true,
                'message'  => __('Asset Registry error: ', 'phantom-core') . $e->getMessage(),
            ];
        }
    }

    public function check_animation_registry(): array {
        try {
            $registry = Animation_Registry::get_instance();
            $count = $registry->count();
            $categories = $registry->get_categories();
            return [
                'healthy'    => $count > 0,
                'count'      => $count,
                'categories' => $categories,
                'critical'   => false,
                'message'    => $count > 0
                    ? sprintf(__('Animation Registry: %d animations in %d categories', 'phantom-core'), $count, count($categories))
                    : __('Animation Registry is empty', 'phantom-core'),
            ];
        } catch (\Throwable $e) {
            return [
                'healthy'  => false,
                'count'    => 0,
                'critical' => false,
                'message'  => __('Animation Registry error: ', 'phantom-core') . $e->getMessage(),
            ];
        }
    }

    public function check_frontend_packs(): array {
        $packsDir = PHANTOM_CORE_PATH . 'frontend/packs/';
        if (!is_dir($packsDir)) {
            return [
                'healthy'  => false,
                'count'    => 0,
                'packs'    => [],
                'critical' => false,
                'message'  => __('Frontend packs directory not found', 'phantom-core'),
            ];
        }
        $entries = scandir($packsDir);
        $packs = [];
        foreach ($entries as $entry) {
            if (in_array($entry, ['.', '..'], true) || !is_dir($packsDir . $entry)) continue;
            $manifest = $packsDir . $entry . '/manifest.json';
            if (file_exists($manifest)) {
                $data = json_decode(file_get_contents($manifest), true);
                $packs[$entry] = [
                    'name'    => $data['name'] ?? $entry,
                    'version' => $data['version'] ?? '—',
                ];
            } else {
                $packs[$entry] = ['name' => $entry, 'version' => '—'];
            }
        }
        return [
            'healthy'  => count($packs) > 0,
            'count'    => count($packs),
            'packs'    => $packs,
            'critical' => false,
            'message'  => count($packs) > 0
                ? sprintf(__('Frontend Packs: %d packs detected', 'phantom-core'), count($packs))
                : __('No frontend packs found', 'phantom-core'),
        ];
    }

    public function check_instances(): array {
        $checks = [];
        $classes = [
            'Settings_Registry'    => 'PhantomCore\\Settings_Registry',
            'Component_Registry'   => 'PhantomCore\\Components\\Component_Registry',
            'Animation_Registry'   => 'PhantomCore\\Animation\\Animation_Registry',
            'Asset_Registry'       => 'PhantomCore\\Registry\\Asset_Registry',
            'DesignSystemManager'  => 'PhantomCore\\Design\\DesignSystemManager',
            'Feature_Registry'     => 'PhantomCore\\Feature\\Feature_Registry',
            'Cache'                => 'PhantomCore\\Engine\\Cache',
        ];
        foreach ($classes as $name => $class) {
            try {
                $healthy = class_exists($class) && method_exists($class, 'get_instance');
                $checks[] = [
                    'name'    => $name,
                    'healthy' => $healthy,
                    'message' => $healthy
                        /* translators: %s: class name */
                        ? sprintf(__('%s: available', 'phantom-core'), $name)
                        /* translators: %s: class name */
                        : sprintf(__('%s: class missing', 'phantom-core'), $name),
                ];
            } catch (\Throwable $e) {
                $checks[] = [
                    'name'    => $name,
                    'healthy' => false,
                    'message' => $name . ': ' . $e->getMessage(),
                ];
            }
        }
        return $checks;
    }

    public function check_settings(): array {
        try {
            $registry = Settings_Registry::get_instance();
            $entries = $registry->get_entries();
            $sections = $registry->get_sections();
            $count = is_array($entries) ? count($entries) : 0;
            $sectionCount = is_array($sections) ? count($sections) : 0;
            return [
                'healthy'      => $count > 0,
                'count'        => $count,
                'sections'     => $sectionCount,
                'message'      => sprintf(__('Settings: %d entries across %d sections', 'phantom-core'), $count, $sectionCount),
            ];
        } catch (\Throwable $e) {
            return [
                'healthy' => false,
                'count'   => 0,
                'message' => __('Settings Registry error: ', 'phantom-core') . $e->getMessage(),
            ];
        }
    }

    public function check_features(): array {
        try {
            $registry = Feature_Registry::get_instance();
            $all = $registry->get_all();
            $enabled = count(array_filter($all, fn($f) => $f->enabled()));
            return [
                'healthy' => count($all) > 0,
                'total'   => count($all),
                'enabled' => $enabled,
                'message' => sprintf(__('Features: %d total, %d enabled', 'phantom-core'), count($all), $enabled),
            ];
        } catch (\Throwable $e) {
            return [
                'healthy' => false,
                'total'   => 0,
                'message' => __('Feature Registry error: ', 'phantom-core') . $e->getMessage(),
            ];
        }
    }

    public function check_woocommerce(): array {
        $wcActive = class_exists('WooCommerce');
        $products = 0;
        $categories = 0;
        if ($wcActive) {
            try {
                $products = (int) wp_count_posts('product')->publish;
            } catch (\Throwable $e) {
                $products = -1;
            }
            try {
                $cats = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false, 'fields' => 'count']);
                $categories = is_wp_error($cats) ? 0 : (int) $cats;
            } catch (\Throwable $e) {
                $categories = -1;
            }
        }
        return [
            'connected'  => $wcActive,
            'version'    => $wcActive && defined('WC_VERSION') ? WC_VERSION : '—',
            'products'   => $products,
            'categories' => $categories,
            'message'    => $wcActive
                ? sprintf(__('WooCommerce %s — %d products, %d categories', 'phantom-core'), WC_VERSION, max(0, $products), max(0, $categories))
                : __('WooCommerce not detected', 'phantom-core'),
        ];
    }

    public function check_rest_api(): array {
        $endpoints = [];
        $namespaces = ['phantom/v1'];
        foreach ($namespaces as $ns) {
            $endpoints[$ns] = rest_get_server()->get_namespace_endpoints($ns);
        }
        $total = 0;
        foreach ($endpoints as $ns => $routes) {
            $total += count($routes);
        }
        return [
            'healthy'   => $total > 0,
            'count'     => $total,
            'endpoints' => $endpoints,
            'message'   => sprintf(__('REST API: %d routes under phantom/v1', 'phantom-core'), $total),
        ];
    }

    public function check_css_variables(): array {
        try {
            $dsm = DesignSystemManager::get_instance();
            $vars = $dsm->allCssVars();
            $count = is_array($vars) ? count($vars) : 0;
            // Basic conflict detection: check for duplicate var names
            $names = is_array($vars) ? array_keys($vars) : [];
            $conflicts = count($names) !== count(array_unique($names)) ? count($names) - count(array_unique($names)) : 0;
            return [
                'healthy'   => $count > 0,
                'count'     => $count,
                'conflicts' => $conflicts,
                'message'   => $conflicts > 0
                    ? sprintf(__('CSS Variables: %d generated, %d conflicts detected', 'phantom-core'), $count, $conflicts)
                    : sprintf(__('CSS Variables: %d generated, no conflicts', 'phantom-core'), $count),
            ];
        } catch (\Throwable $e) {
            return [
                'healthy'   => false,
                'count'     => 0,
                'conflicts' => 0,
                'message'   => __('CSS Variables error: ', 'phantom-core') . $e->getMessage(),
            ];
        }
    }

    public function check_performance(): array {
        $memoryPeak = memory_get_peak_usage(true);
        $memoryFormatted = size_format($memoryPeak, 2);
        $queries = 0;
        if (function_exists('get_num_queries')) {
            $queries = (int) get_num_queries();
        }
        return [
            'memory'      => $memoryFormatted,
            'memoryBytes' => $memoryPeak,
            'queries'     => $queries,
            'message'     => sprintf(__('Memory peak: %s, DB queries: %d', 'phantom-core'), $memoryFormatted, $queries),
        ];
    }

    public function render_page(): void {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions.', 'phantom-core'));
        }

        if (isset($_POST['action']) && 'run_diagnostics' === $_POST['action']) {
            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(wp_unslash($_POST['_wpnonce']), 'phantom_diagnostics')) {
                wp_die(esc_html__('Security check failed.', 'phantom-core'));
            }
            $this->diagnosticsRun = false;
        }

        $report = $this->get_health_report();
        $scoreClass = $report['score'] >= 80 ? 'green' : ($report['score'] >= 50 ? 'yellow' : 'red');
        ?>
        <div class="wrap phantom-diag">
            <h1><?php esc_html_e('System Diagnostics', 'phantom-core'); ?></h1>
            <p class="description"><?php esc_html_e('Comprehensive health report for Phantom Core Framework.', 'phantom-core'); ?></p>

            <div class="phantom-diag-actions">
                <form method="post" style="display:inline;">
                    <?php wp_nonce_field('phantom_diagnostics', '_wpnonce'); ?>
                    <input type="hidden" name="action" value="run_diagnostics" />
                    <button type="submit" class="button button-primary">
                        <?php esc_html_e('Run Diagnostics', 'phantom-core'); ?>
                    </button>
                </form>
                <a href="<?php echo esc_url(admin_url('admin.php?page=phantom-system')); ?>" class="button">
                    <?php esc_html_e('System Info', 'phantom-core'); ?>
                </a>
            </div>

            <div class="phantom-diag-score <?php echo esc_attr($scoreClass); ?>">
                <span><?php esc_html_e('System Health', 'phantom-core'); ?>:</span>
                <span><?php echo esc_html($report['score']); ?>/100</span>
            </div>

            <?php if (!empty($report['errors'])) : ?>
                <div class="notice notice-error">
                    <p><strong><?php esc_html_e('Errors', 'phantom-core'); ?></strong></p>
                    <ul class="phantom-diag-list">
                        <?php foreach ($report['errors'] as $err) : ?>
                            <li><span class="dashicons dashicons-dismiss" style="color:#b91c1c"></span> <?php echo esc_html($err); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (!empty($report['warnings'])) : ?>
                <div class="notice notice-warning">
                    <p><strong><?php esc_html_e('Warnings', 'phantom-core'); ?></strong></p>
                    <ul class="phantom-diag-list">
                        <?php foreach ($report['warnings'] as $warn) : ?>
                            <li><span class="dashicons dashicons-warning" style="color:#8a6d00"></span> <?php echo esc_html($warn); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="phantom-diag-grid">
                <!-- Registries -->
                <div class="phantom-diag-card">
                    <h2><?php esc_html_e('Registries', 'phantom-core'); ?></h2>
                    <table>
                        <?php foreach ($report['registries'] as $key => $r) : ?>
                            <tr>
                                <td><?php echo esc_html(ucwords(str_replace('_', ' ', $key))); ?></td>
                                <td>
                                    <span class="phantom-diag-badge <?php echo $r['healthy'] ? 'ok' : 'err'; ?>">
                                        <?php echo $r['healthy'] ? '✓' : '✗'; ?>
                                    </span>
                                    <span style="font-size:12px;color:#50575e"><?php echo esc_html($r['count'] ?? 0); ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>

                <!-- Components -->
                <div class="phantom-diag-card">
                    <h2><?php esc_html_e('Components', 'phantom-core'); ?></h2>
                    <table>
                        <?php foreach ($report['components'] as $c) : ?>
                            <tr>
                                <td><?php echo esc_html($c['name']); ?></td>
                                <td>
                                    <span class="phantom-diag-badge <?php echo $c['healthy'] ? 'ok' : 'err'; ?>">
                                        <?php echo $c['healthy'] ? '✓' : '✗'; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>

                <!-- CSS Variables -->
                <div class="phantom-diag-card">
                    <h2><?php esc_html_e('CSS Variables', 'phantom-core'); ?></h2>
                    <table>
                        <tr><td><?php esc_html_e('Generated', 'phantom-core'); ?></td><td><?php echo esc_html($report['css_variables']['count']); ?></td></tr>
                        <tr><td><?php esc_html_e('Conflicts', 'phantom-core'); ?></td><td>
                            <span class="phantom-diag-badge <?php echo $report['css_variables']['conflicts'] > 0 ? 'err' : 'ok'; ?>">
                                <?php echo $report['css_variables']['conflicts'] > 0 ? esc_html($report['css_variables']['conflicts']) : '0'; ?>
                            </span>
                        </td></tr>
                    </table>
                </div>

                <!-- Performance -->
                <div class="phantom-diag-card">
                    <h2><?php esc_html_e('Performance', 'phantom-core'); ?></h2>
                    <table>
                        <tr><td><?php esc_html_e('Memory Peak', 'phantom-core'); ?></td><td><?php echo esc_html($report['performance']['memory']); ?></td></tr>
                        <tr><td><?php esc_html_e('DB Queries', 'phantom-core'); ?></td><td><?php echo esc_html($report['performance']['queries']); ?></td></tr>
                    </table>
                </div>

                <!-- WooCommerce -->
                <div class="phantom-diag-card">
                    <h2><?php esc_html_e('WooCommerce', 'phantom-core'); ?></h2>
                    <table>
                        <tr><td><?php esc_html_e('Active', 'phantom-core'); ?></td><td>
                            <span class="phantom-diag-badge <?php echo $report['woocommerce']['connected'] ? 'ok' : 'warn'; ?>">
                                <?php echo $report['woocommerce']['connected'] ? '✓' : '✗'; ?>
                            </span>
                        </td></tr>
                        <tr><td><?php esc_html_e('Version', 'phantom-core'); ?></td><td><?php echo esc_html($report['woocommerce']['version']); ?></td></tr>
                        <tr><td><?php esc_html_e('Products', 'phantom-core'); ?></td><td><?php echo esc_html(max(0, (int) $report['woocommerce']['products'])); ?></td></tr>
                        <tr><td><?php esc_html_e('Categories', 'phantom-core'); ?></td><td><?php echo esc_html(max(0, (int) $report['woocommerce']['categories'])); ?></td></tr>
                    </table>
                </div>

                <!-- REST API -->
                <div class="phantom-diag-card">
                    <h2><?php esc_html_e('REST API', 'phantom-core'); ?></h2>
                    <table>
                        <tr><td><?php esc_html_e('Routes (phantom/v1)', 'phantom-core'); ?></td><td><?php echo esc_html($report['rest_api']['count']); ?></td></tr>
                    </table>
                    <?php if (!empty($report['rest_api']['endpoints']['phantom/v1'])) : ?>
                        <details style="margin-top:8px">
                            <summary style="cursor:pointer;font-size:12px;color:#50575e">
                                <?php esc_html_e('Show routes', 'phantom-core'); ?>
                            </summary>
                            <ul class="phantom-diag-list" style="margin-top:8px;max-height:200px;overflow-y:auto">
                                <?php foreach ($report['rest_api']['endpoints']['phantom/v1'] as $route => $handlers) : ?>
                                    <li style="font-size:11px;font-family:monospace"><?php echo esc_html($route); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </details>
                    <?php endif; ?>
                </div>

                <!-- Settings & Features -->
                <div class="phantom-diag-card">
                    <h2><?php esc_html_e('Settings & Features', 'phantom-core'); ?></h2>
                    <table>
                        <tr><td><?php esc_html_e('Settings Entries', 'phantom-core'); ?></td><td><?php echo esc_html($report['settings']['count'] ?? '—'); ?></td></tr>
                        <tr><td><?php esc_html_e('Settings Sections', 'phantom-core'); ?></td><td><?php echo esc_html($report['settings']['sections'] ?? '—'); ?></td></tr>
                        <tr><td><?php esc_html_e('Feature Flags', 'phantom-core'); ?></td><td><?php echo esc_html($report['features']['enabled'] ?? 0); ?> / <?php echo esc_html($report['features']['total'] ?? 0); ?></td></tr>
                    </table>
                </div>

                <!-- Frontend Packs -->
                <div class="phantom-diag-card">
                    <h2><?php esc_html_e('Frontend Packs', 'phantom-core'); ?></h2>
                    <table>
                        <?php if (!empty($report['registries']['frontend_packs']['packs'])) : ?>
                            <?php foreach ($report['registries']['frontend_packs']['packs'] as $slug => $pack) : ?>
                                <tr>
                                    <td><?php echo esc_html($pack['name']); ?></td>
                                    <td style="font-size:12px;color:#50575e"><?php echo esc_html($pack['version']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr><td colspan="2"><?php esc_html_e('No packs found', 'phantom-core'); ?></td></tr>
                        <?php endif; ?>
                    </table>
                </div>

                <!-- Registry Messages -->
                <div class="phantom-diag-card" style="grid-column:1/-1">
                    <h2><?php esc_html_e('Registry Details', 'phantom-core'); ?></h2>
                    <ul class="phantom-diag-list">
                        <?php foreach ($report['registries'] as $key => $r) : ?>
                            <li>
                                <span class="phantom-diag-badge <?php echo $r['healthy'] ? 'ok' : ($r['critical'] ? 'err' : 'warn'); ?>">
                                    <?php echo $r['healthy'] ? '✓' : '✗'; ?>
                                </span>
                                <strong><?php echo esc_html(ucwords(str_replace('_', ' ', $key))); ?>:</strong>
                                <?php echo esc_html($r['message']); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
        <?php
    }
}
