<?php
declare(strict_types=1);

namespace PhantomCore\Inspector;

defined('ABSPATH') || exit;

class Selection_Engine {
    private static ?self $instance = null;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init(): void {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_preview_assets']);
    }

    public function enqueue_preview_assets(): void {
        if (!$this->is_customizer_preview()) {
            return;
        }

        $ver = defined('PHANTOM_CORE_VERSION') ? PHANTOM_CORE_VERSION : '2.0.0';
        $url = defined('PHANTOM_CORE_URL') ? PHANTOM_CORE_URL : '';

        wp_enqueue_style(
            'phantom-selection-engine',
            $url . 'admin/css/visual-customizer.css',
            [],
            $ver
        );

        wp_enqueue_script(
            'phantom-selection-engine',
            $url . 'admin/js/visual-customizer/selection-engine.js',
            [],
            $ver,
            true
        );

        wp_localize_script('phantom-selection-engine', 'PhantomSelection', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restUrl' => rest_url('phantom/v1'),
            'nonce' => wp_create_nonce('phantom_api'),
            'isCustomizer' => true,
        ]);
    }

    public function is_customizer_preview(): bool {
        if (!current_user_can('edit_theme_options')) {
            return false;
        }
        if (isset($_GET['vc_preview']) && '1' === $_GET['vc_preview']) {
            return true;
        }
        if (isset($_POST['vc_preview']) && '1' === $_POST['vc_preview']) {
            return true;
        }
        if (function_exists('is_customize_preview') && is_customize_preview()) {
            return true;
        }
        return false;
    }
}
