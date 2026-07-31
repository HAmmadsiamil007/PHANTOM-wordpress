<?php
declare(strict_types=1);

namespace PhantomCore\Lock;

defined('ABSPATH') || exit;

class Lock_UI {
    private static ?self $instance = null;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function render_lock_badge(string $instance_id, bool $locked): string {
        if (!$locked) {
            return '';
        }

        $manager = Lock_Manager::get_instance();
        $locks = $manager->get_locked();
        $lock_info = $locks[$instance_id] ?? [];
        $user_name = $lock_info['user_name'] ?? 'Unknown';
        $locked_at = $lock_info['locked_at'] ?? '';

        $html = '<span class="vc-lock-badge" title="Locked by ' . esc_attr($user_name) . ' since ' . esc_attr($locked_at) . '">';
        $html .= '<span class="dashicons dashicons-lock"></span>';
        $html .= '<span class="vc-lock-badge-text">Locked</span>';
        $html .= '</span>';

        return $html;
    }

    public function render_lock_toggle(string $instance_id, bool $locked): string {
        $class = $locked ? 'vc-lock-toggle locked' : 'vc-lock-toggle';
        $icon = $locked ? 'dashicons-unlock' : 'dashicons-lock';
        $label = $locked ? 'Unlock' : 'Lock';

        $html = '<button type="button" class="' . esc_attr($class) . '" data-id="' . esc_attr($instance_id) . '" title="' . esc_attr($label) . '">';
        $html .= '<span class="dashicons ' . esc_attr($icon) . '"></span>';
        $html .= '</button>';

        return $html;
    }

    public function render_locked_list(array $locked_instances): string {
        if (empty($locked_instances)) {
            return '<div class="vc-locked-empty">No locked instances.</div>';
        }

        $html = '';
        foreach ($locked_instances as $id => $info) {
            $component = $info['instance_id'] ?? $id;
            $user_name = $info['user_name'] ?? 'Unknown';
            $locked_at = $info['locked_at'] ?? '';

            $html .= '<div class="vc-locked-item" data-instance-id="' . esc_attr($id) . '">';
            $html .= '<span class="dashicons dashicons-lock"></span>';
            $html .= '<span class="vc-locked-name">' . esc_html($component) . '</span>';
            $html .= '<span class="vc-locked-meta">by ' . esc_html($user_name) . '</span>';
            $html .= '<button type="button" class="vc-btn-unlock" data-id="' . esc_attr($id) . '">Unlock</button>';
            $html .= '</div>';
        }

        return $html;
    }

    public function enqueue_assets(): void {
        $ver = defined('PHANTOM_CORE_VERSION') ? PHANTOM_CORE_VERSION : '2.0.0';

        wp_add_inline_style('phantom-visual-customizer', $this->get_inline_styles());
    }

    private function get_inline_styles(): string {
        return '
            .vc-lock-badge {
                display: inline-flex;
                align-items: center;
                gap: 4px;
                font-size: 11px;
                color: #b32d2e;
                background: #fcf0f1;
                padding: 2px 8px;
                border-radius: 3px;
                font-weight: 500;
            }
            .vc-lock-badge .dashicons {
                font-size: 14px;
                width: 14px;
                height: 14px;
            }
            .vc-locked-meta {
                font-size: 10px;
                color: #646970;
                margin-left: auto;
            }
        ';
    }
}
