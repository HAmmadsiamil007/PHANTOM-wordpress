<?php
declare(strict_types=1);

namespace PhantomCore\Search;

defined('ABSPATH') || exit;

class Search_UI {
    private static ?self $instance = null;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function render_search_bar(): string {
        ob_start();
        ?>
        <div class="vc-search-wrapper">
            <span class="dashicons dashicons-search vc-search-icon"></span>
            <input type="text"
                   class="vc-search-input"
                   id="vc-global-search"
                   placeholder="Search components, instances, settings... (Ctrl+K)"
                   autocomplete="off" />
            <div class="vc-search-results" id="vc-search-results"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function enqueue_assets(): void {
        $ver = defined('PHANTOM_CORE_VERSION') ? PHANTOM_CORE_VERSION : '2.0.0';
        $url = defined('PHANTOM_CORE_URL') ? PHANTOM_CORE_URL : '';

        wp_enqueue_script(
            'phantom-global-search',
            $url . 'admin/js/visual-customizer/global-search.js',
            ['jquery'],
            $ver,
            true
        );

        wp_localize_script('phantom-global-search', 'PhantomSearch', [
            'restUrl' => rest_url('phantom/v1'),
            'nonce' => wp_create_nonce('wp_rest'),
        ]);
    }
}
