<?php
declare(strict_types=1);

namespace PhantomCore\Upgrade;

defined('ABSPATH') || exit;

class Upgrade_Manager {
    private static ?self $instance = null;
    private const VERSION_OPTION = 'phantom_core_db_version';
    private array $migrations = [];

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init(): void {
        $this->register_migrations();
        $this->run_pending();
    }

    private function register_migrations(): void {
        $this->register('1.5.0', function () {
            $this->migrate_css_var_names();
        });

        $this->register('1.5.1', function () {
            $this->migrate_cart_settings();
        });

        $this->register('1.5.2', function () {
            $this->migrate_responsive_hero();
        });

        $this->register('1.5.3', function () {
            $this->migrate_feature_flags();
        });
    }

    public function register(string $version, callable $callback): void {
        $this->migrations[$version] = $callback;
    }

    private function run_pending(): void {
        $current_db_version = get_option(self::VERSION_OPTION, '0.0.0');

        foreach ($this->migrations as $version => $callback) {
            if (version_compare($current_db_version, $version, '<')) {
                try {
                    $callback();
                    update_option(self::VERSION_OPTION, $version, false);
                } catch (\Throwable $e) {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log(sprintf(
                            '[PhantomCore] Upgrade migration %s failed: %s',
                            $version,
                            $e->getMessage()
                        ));
                    }
                }
            }
        }

        update_option(self::VERSION_OPTION, PHANTOM_CORE_VERSION, false);
    }

    private function migrate_css_var_names(): void {
        $options = get_option('phantom_options', []);
        if (!is_array($options)) return;

        $legacy_map = [
            'primary_color' => 'color_primary',
            'secondary_color' => 'color_secondary',
            'accent_color' => 'color_accent',
            'text_color' => 'color_text',
            'heading_color' => 'color_heading',
            'background_color' => 'color_background',
        ];

        $changed = false;
        foreach ($legacy_map as $old => $new) {
            if (isset($options[$old]) && !isset($options[$new])) {
                $options[$new] = $options[$old];
                $changed = true;
            }
        }
        if ($changed) {
            update_option('phantom_options', $options, false);
        }
    }

    private function migrate_cart_settings(): void {
        // Migrate from phantom_cart_* to phantom_shop_cart_* naming
        global $wpdb;
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s",
                'phantom_cart_%'
            )
        );
        foreach ($rows as $row) {
            $new_name = str_replace('phantom_cart_', 'phantom_shop_cart_', $row->option_name);
            if (false === get_option($new_name)) {
                add_option($new_name, $row->option_value, '', 'no');
            }
        }
    }

    private function migrate_responsive_hero(): void {
        // Ensure responsive hero settings exist with defaults
        $defaults = [
            'phantom_hero_image_tablet' => '',
            'phantom_hero_image_mobile' => '',
            'phantom_hero_enable_responsive' => '0',
            'phantom_hero_tablet_breakpoint' => '1024',
            'phantom_hero_mobile_breakpoint' => '768',
            'phantom_hero_loading' => 'lazy',
        ];
        foreach ($defaults as $key => $value) {
            if (false === get_option($key)) {
                add_option($key, $value, '', 'yes');
            }
        }
    }

    private function migrate_feature_flags(): void {
        // Initialize feature flags with defaults (stored as enabled/disabled)
        $registry = \PhantomCore\Feature\Feature_Registry::get_instance();
        $registry->load();
        foreach ($registry->get_all() as $feature) {
            $option_key = 'phantom_feature_' . $feature->id;
            if (false === get_option($option_key)) {
                add_option($option_key, $feature->default ? '1' : '0', '', 'yes');
            }
        }
    }

    public function get_current_db_version(): string {
        return get_option(self::VERSION_OPTION, '0.0.0');
    }

    public function get_pending_migrations(): array {
        $pending = [];
        $current = $this->get_current_db_version();
        foreach ($this->migrations as $version => $callback) {
            if (version_compare($current, $version, '<')) {
                $pending[] = $version;
            }
        }
        return $pending;
    }
}
