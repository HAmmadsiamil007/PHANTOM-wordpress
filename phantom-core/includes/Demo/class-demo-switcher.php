<?php
declare(strict_types=1);

namespace PhantomCore\Demo;

defined('ABSPATH') || exit;

class Demo_Switcher {
    public function __construct(
        private Demo_Registry $registry
    ) {}

    public function activate(string $slug): Result {
        $demo = $this->registry->get($slug);
        if ($demo === null) {
            return Result::fail(
                sprintf('Demo "%s" is not installed.', $slug),
                ['demo_not_found' => "No demo found with slug: $slug"]
            );
        }

        if (!$demo->is_compatible) {
            return Result::fail(
                sprintf('Demo "%s" is not compatible with the current environment.', $slug),
                $demo->errors
            );
        }

        $old_slug = $this->get_active_slug();

        update_option('template_pack', $slug);
        update_option('phantom_template_pack', $slug);
        update_option('phantom_active_demo', $slug);

        if (!empty($demo->preset)) {
            $dsmClassName = 'PhantomCore\\Design\\DesignSystemManager';
            if (class_exists($dsmClassName)) {
                $dsm = $dsmClassName::get_instance();
                $dsm->applyPreset($demo->preset);
            }
        }

        if (function_exists('flush_rewrite_rules')) {
            flush_rewrite_rules();
        }

        if (function_exists('do_action')) {
            do_action('phantom_demo_activated', $slug, $old_slug);
        }

        $this->registry->refresh();

        return Result::ok(
            sprintf('Demo "%s" activated successfully.', $demo->name),
            ['slug' => $slug, 'name' => $demo->name, 'previous' => $old_slug]
        );
    }

    public function deactivate(): Result {
        $old_slug = $this->get_active_slug();
        $old_name = 'Unknown';

        $demo = $this->registry->get($old_slug);
        if ($demo !== null) {
            $old_name = $demo->name;
        }

        update_option('template_pack', 'kids');
        update_option('phantom_active_demo', 'kids');

        if (function_exists('flush_rewrite_rules')) {
            flush_rewrite_rules();
        }

        if (function_exists('do_action')) {
            do_action('phantom_demo_deactivated', $old_slug);
        }

        $this->registry->refresh();

        return Result::ok(
            'Demo deactivated. Default template pack restored.',
            ['previous' => $old_slug, 'previous_name' => $old_name]
        );
    }

    public function get_active_slug(): string {
        return get_option('phantom_active_demo', 'kids');
    }

    public function can_activate(string $slug): array {
        $demo = $this->registry->get($slug);
        if ($demo === null) {
            return [
                'pass' => false,
                'checks' => [
                    ['name' => 'Demo Installed', 'status' => 'fail', 'message' => "Demo '$slug' not found"],
                ],
            ];
        }

        $checks = [];

        if (!empty($demo->requires['php'])) {
            $pass = version_compare(PHP_VERSION, (string) $demo->requires['php'], '>=');
            $checks[] = [
                'name' => 'PHP Version',
                'status' => $pass ? 'pass' : 'fail',
                'message' => $pass
                    ? sprintf('PHP %s >= %s', PHP_VERSION, $demo->requires['php'])
                    : sprintf('PHP %s < %s (required)', PHP_VERSION, $demo->requires['php']),
            ];
        }

        if (!empty($demo->requires['wordpress'])) {
            $wp_ver = function_exists('get_bloginfo') ? get_bloginfo('version') : '0.0';
            $pass = version_compare($wp_ver, (string) $demo->requires['wordpress'], '>=');
            $checks[] = [
                'name' => 'WordPress',
                'status' => $pass ? 'pass' : 'fail',
                'message' => $pass
                    ? sprintf('WP %s >= %s', $wp_ver, $demo->requires['wordpress'])
                    : sprintf('WP %s < %s (required)', $wp_ver, $demo->requires['wordpress']),
            ];
        }

        if (!empty($demo->requires['woocommerce'])) {
            $wc_ver = defined('WC_VERSION') ? WC_VERSION : '0.0';
            $pass = version_compare($wc_ver, (string) $demo->requires['woocommerce'], '>=');
            $checks[] = [
                'name' => 'WooCommerce',
                'status' => $pass ? 'pass' : 'fail',
                'message' => $pass
                    ? sprintf('WC %s >= %s', $wc_ver, $demo->requires['woocommerce'])
                    : sprintf('WC %s < %s (required)', $wc_ver, $demo->requires['woocommerce']),
            ];
        }

        if (!empty($demo->requires['phantom_core'])) {
            $pc_ver = defined('PHANTOM_CORE_VERSION') ? PHANTOM_CORE_VERSION : '0.0';
            $pass = version_compare($pc_ver, (string) $demo->requires['phantom_core'], '>=');
            $checks[] = [
                'name' => 'Phantom Core',
                'status' => $pass ? 'pass' : 'fail',
                'message' => $pass
                    ? sprintf('Phantom Core %s >= %s', $pc_ver, $demo->requires['phantom_core'])
                    : sprintf('Phantom Core %s < %s (required)', $pc_ver, $demo->requires['phantom_core']),
            ];
        }

        $demo_path = PHANTOM_CORE_PATH . 'frontend/templates/' . $slug . '/html/';
        $pack_path = PHANTOM_CORE_PATH . 'frontend/packs/' . $slug . '/html/';
        $tpl_pass = is_dir($demo_path) || is_dir($pack_path);
        $checks[] = [
            'name' => 'Template Directory',
            'status' => $tpl_pass ? 'pass' : 'warn',
            'message' => $tpl_pass
                ? 'Template directory exists'
                : 'No templates directory — falling back to defaults',
        ];

        if (!empty($demo->templates)) {
            $missing = $demo->validate_templates();
            $checks[] = [
                'name' => 'Demo Templates',
                'status' => empty($missing) ? 'pass' : 'warn',
                'message' => empty($missing)
                    ? 'All listed templates found'
                    : sprintf('Missing: %s', implode(', ', $missing)),
            ];
        }

        $all_pass = empty(array_filter($checks, fn($c) => $c['status'] === 'fail'));

        return ['pass' => $all_pass, 'checks' => $checks];
    }
}
