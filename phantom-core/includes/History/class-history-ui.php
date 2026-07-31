<?php
declare(strict_types=1);

namespace PhantomCore\History;

defined('ABSPATH') || exit;

class History_UI {
    private static ?self $instance = null;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function render_timeline(array $entries): string {
        if (empty($entries)) {
            return '<div class="vc-history-empty">No history yet.</div>';
        }

        $html = '<div class="vc-history-timeline">';
        $count = 0;
        foreach ($entries as $entry) {
            if ($count >= 50) {
                break;
            }
            $action = esc_html($entry->action ?? $entry['action'] ?? 'manual');
            $timestamp = esc_html($entry->timestamp ?? $entry['timestamp'] ?? '');
            $component = esc_html($entry->component ?? $entry['component'] ?? '');
            $property = esc_html($entry->property ?? $entry['property'] ?? '');
            $instance_id = esc_html($entry->instance_id ?? $entry['instance_id'] ?? '');

            $time_display = '';
            if ($timestamp) {
                $time = strtotime($timestamp);
                if ($time) {
                    $time_display = human_time_diff($time) . ' ago';
                }
            }

            $html .= '<div class="vc-history-entry" data-instance-id="' . $instance_id . '" data-action="' . $action . '">';
            $html .= '<span class="vc-history-badge vc-history-badge--' . esc_attr($action) . '">' . $action . '</span>';
            $html .= '<span class="vc-history-desc">';
            if ($component) {
                $html .= '<strong>' . $component . '</strong>';
                if ($property) {
                    $html .= ' → ' . $property;
                }
            } else {
                $html .= '<em>Snapshot</em>';
            }
            $html .= '</span>';
            $html .= '<span class="vc-history-time">' . $time_display . '</span>';
            $html .= '</div>';

            $count++;
        }
        $html .= '</div>';
        return $html;
    }

    public function render_undo_button(): string {
        return '<button type="button" class="vc-history-btn" id="vc-history-undo" title="Undo (Ctrl+Z)">'
            . '<span class="dashicons dashicons-undo"></span> Undo'
            . '</button>';
    }

    public function render_redo_button(): string {
        return '<button type="button" class="vc-history-btn" id="vc-history-redo" title="Redo (Ctrl+Shift+Z)">'
            . '<span class="dashicons dashicons-redo"></span> Redo'
            . '</button>';
    }

    public function render_clear_button(): string {
        return '<button type="button" class="vc-history-btn vc-history-btn--clear" id="vc-history-clear" title="Clear all history">'
            . '<span class="dashicons dashicons-trash"></span> Clear'
            . '</button>';
    }

    public function enqueue_assets(): void {
        $ver = defined('PHANTOM_CORE_VERSION') ? PHANTOM_CORE_VERSION : '2.0.0';
        $url = defined('PHANTOM_CORE_URL') ? PHANTOM_CORE_URL : '';

        wp_enqueue_script(
            'phantom-history-timeline',
            $url . 'admin/js/visual-customizer/history-timeline.js',
            ['jquery'],
            $ver,
            true
        );

        wp_localize_script('phantom-history-timeline', 'PhantomHistory', [
            'restUrl' => rest_url('phantom/v1'),
            'nonce' => wp_create_nonce('wp_rest'),
        ]);
    }
}
