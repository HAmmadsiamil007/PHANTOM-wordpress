<?php
declare(strict_types=1);

namespace PhantomCore\Favorites;

defined('ABSPATH') || exit;

class Favorites_UI {
    private static ?self $instance = null;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function render_favorites_list(array $favorites): string {
        if (empty($favorites)) {
            return '<div class="vc-favorites-empty">No favorites yet. Click the star icon next to any component to add it.</div>';
        }

        $html = '<div class="vc-favorites-list">';
        foreach ($favorites as $fav) {
            $key = esc_attr($fav['key'] ?? '');
            $type = esc_attr($fav['type'] ?? '');
            $id = esc_attr($fav['id'] ?? '');
            $label = esc_html($fav['label'] ?? $id);
            $category = esc_html($fav['category'] ?? '');

            $html .= '<div class="vc-fav-item" data-key="' . $key . '" data-type="' . $type . '" data-id="' . $id . '">';
            $html .= '<span class="dashicons dashicons-star-filled vc-fav-icon"></span>';
            $html .= '<span class="vc-fav-label">' . $label . '</span>';
            if ($category) {
                $html .= '<span class="vc-fav-category">' . $category . '</span>';
            }
            $html .= '<button type="button" class="vc-fav-remove" title="Remove from favorites">';
            $html .= '<span class="dashicons dashicons-no-alt"></span>';
            $html .= '</button>';
            $html .= '</div>';
        }
        $html .= '</div>';
        return $html;
    }

    public function render_favorite_toggle(string $type, string $id, bool $is_favorite): string {
        $active_class = $is_favorite ? ' active' : '';
        return '<button type="button" class="vc-fav-btn' . $active_class . '" data-type="' . esc_attr($type) . '" data-id="' . esc_attr($id) . '" title="' . ($is_favorite ? 'Remove from favorites' : 'Add to favorites') . '">'
            . '<span class="dashicons dashicons-star-filled"></span>'
            . '</button>';
    }

    public function enqueue_assets(): void {
        $ver = defined('PHANTOM_CORE_VERSION') ? PHANTOM_CORE_VERSION : '2.0.0';
        $url = defined('PHANTOM_CORE_URL') ? PHANTOM_CORE_URL : '';

        wp_enqueue_script(
            'phantom-favorites',
            $url . 'admin/js/visual-customizer/favorites.js',
            ['jquery'],
            $ver,
            true
        );

        wp_localize_script('phantom-favorites', 'PhantomFav', [
            'restUrl' => rest_url('phantom/v1'),
            'nonce' => wp_create_nonce('wp_rest'),
        ]);
    }
}
