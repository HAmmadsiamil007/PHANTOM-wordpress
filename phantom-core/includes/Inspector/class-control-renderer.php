<?php
declare(strict_types=1);

namespace PhantomCore\Inspector;

use PhantomCore\Components\ComponentInstance;
use PhantomCore\Design\Component_Definition;

defined('ABSPATH') || exit;

/**
 * Control_Renderer — shared rendering for inspector controls and panels.
 *
 * Used by Inspector_Factory for both legacy definition tabs and metadata
 * parts, so every control type renders identically regardless of source.
 *
 * @package PhantomCore\Inspector
 */
class Control_Renderer {

    public const TOOL_ICONS = [
        'colors'     => 'art',
        'typography' => 'editor-textcolor',
        'spacing'    => 'editor-expand',
        'assets'     => 'format-image',
        'animation'  => 'visibility',
        'responsive' => 'desktop',
        'content'    => 'edit',
    ];

    private static ?self $instance = null;

    private State_Manager $state_manager;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->state_manager = State_Manager::get_instance();
    }

    /**
     * Render a collapsible panel around a set of fields.
     */
    public function render_panel(string $key, string $label, array $fields, Component_Definition $component, ?ComponentInstance $instance = null, string $icon = ''): string {
        $icons = [
            'content'    => 'dashicons-edit',
            'background' => 'dashicons-admin-appearance',
            'typography' => 'dashicons-editor-textcolor',
            'spacing'    => 'dashicons-editor-expand',
            'layout'     => 'dashicons-grid-view',
            'animation'  => 'dashicons-visibility',
            'responsive' => 'dashicons-desktop',
        ];
        if (!$icon) {
            $icon = $icons[$key] ?? 'dashicons-admin-generic';
        }
        if (strpos($icon, 'dashicons-') !== 0) {
            $icon = 'dashicons-' . $icon;
        }

        $html = '<div class="vc-panel vc-panel-' . esc_attr($key) . '">';
        $html .= '<div class="vc-panel-header" data-panel="' . esc_attr($key) . '">';
        $html .= '<span class="dashicons ' . esc_attr($icon) . '"></span>';
        $html .= '<span class="vc-panel-title">' . esc_html($label) . '</span>';
        $html .= '<span class="vc-panel-toggle dashicons dashicons-arrow-up"></span>';
        $html .= '</div>';
        $html .= '<div class="vc-panel-body">';

        foreach ($fields as $field) {
            $html .= $this->render_field($field, $component, $instance);
        }

        $html .= '</div></div>';
        return $html;
    }

    /**
     * Render a single control for a field definition.
     */
    public function render_field(array $field, Component_Definition $component, ?ComponentInstance $instance = null): string {
        $state = $this->state_manager->get_current_state();
        $viewport = $this->state_manager->get_current_viewport();

        $key = $field['key'] ?? '';
        $type = $field['type'] ?? 'text';

        $value = null;
        if ($instance) {
            $value = $this->state_manager->resolve_value($instance, $key);
        }
        if (null === $value) {
            $settings = \PhantomCore\Settings_Registry::get_instance();
            if ($settings->has($key)) {
                $value = $settings->get_string($key);
            }
        }
        if (null === $value) {
            $value = $field['default'] ?? '';
        }

        $name = esc_attr($key);
        $label = esc_html($field['label'] ?? ucwords(str_replace('_', ' ', $key)));
        $current_value = esc_attr((string) $value);
        $target_attr = !empty($field['target']) ? ' data-target="' . esc_attr($field['target']) . '"' : '';
        $part_attr = !empty($field['part']) ? ' data-part="' . esc_attr($field['part']) . '"' : '';

        $override_classes = '';
        $override_dot = '';
        if ($instance) {
            if ('normal' !== $state && $this->state_manager->is_overridden_in_state($instance, $key, $state)) {
                $override_classes = ' vc-control-has-state-override';
                $override_dot = '<span class="vc-override-dot vc-override-dot-state" title="' . esc_attr(ucfirst($state)) . ' override active"></span>';
            } elseif ('desktop' !== $viewport && $this->state_manager->is_overridden_in_viewport($instance, $key, $viewport)) {
                $override_classes = ' vc-control-has-viewport-override';
                $override_dot = '<span class="vc-override-dot vc-override-dot-viewport" title="' . esc_attr(ucfirst($viewport)) . ' override active"></span>';
            }
        }

        $html = '<div class="vc-control vc-control-' . esc_attr($type) . $override_classes . '" data-property="' . $name . '"' . $target_attr . $part_attr . '>';
        $html .= '<label class="vc-control-label">' . $override_dot . $label . '</label>';
        $html .= '<div class="vc-control-input">';

        switch ($type) {
            case 'color':
                $html .= '<div class="vc-color-picker-wrapper">';
                $html .= '<input type="text" class="vc-color-picker" value="' . $current_value . '" data-property="' . $name . '" />';
                $html .= '<span class="vc-color-swatch" style="background:' . $current_value . '"></span>';
                $html .= '</div>';
                break;

            case 'slider':
            case 'range':
                $min = $field['min'] ?? 0;
                $max = $field['max'] ?? 100;
                $step = $field['step'] ?? 1;
                $unit = $field['unit'] ?? '';
                $html .= '<div class="vc-range-wrapper">';
                $html .= '<input type="range" class="vc-range" min="' . esc_attr($min) . '" max="' . esc_attr($max) . '" step="' . esc_attr($step) . '" value="' . $current_value . '" data-property="' . $name . '" />';
                $html .= '<input type="number" class="vc-range-value" value="' . $current_value . '" min="' . esc_attr($min) . '" max="' . esc_attr($max) . '" step="' . esc_attr($step) . '" data-property="' . $name . '" />';
                if ($unit) {
                    $html .= '<span class="vc-range-unit">' . esc_html($unit) . '</span>';
                }
                $html .= '</div>';
                break;

            case 'select':
                $html .= '<select class="vc-select" data-property="' . $name . '">';
                $options = is_array($field['options'] ?? null) ? $field['options'] : [];
                foreach ($options as $opt_value => $opt_label) {
                    if (is_int($opt_value)) {
                        $opt_value = $opt_label;
                    }
                    $selected = ((string) $opt_value === (string) $value) ? ' selected' : '';
                    $html .= '<option value="' . esc_attr($opt_value) . '"' . $selected . '>' . esc_html($opt_label) . '</option>';
                }
                $html .= '</select>';
                break;

            case 'toggle':
                $checked = in_array((string) $value, ['1', 'true', 'yes', 'on', 'enabled'], true);
                $html .= '<select class="vc-select" data-property="' . $name . '">';
                $html .= '<option value="1"' . ($checked ? ' selected' : '') . '>' . esc_html__('Enabled', 'phantom-core') . '</option>';
                $html .= '<option value="0"' . (!$checked ? ' selected' : '') . '>' . esc_html__('Disabled', 'phantom-core') . '</option>';
                $html .= '</select>';
                break;

            case 'textarea':
                $html .= '<textarea class="vc-text-input" data-property="' . $name . '" rows="3">' . esc_textarea((string) $value) . '</textarea>';
                break;

            default:
                $html .= '<input type="text" class="vc-text-input" value="' . $current_value . '" data-property="' . $name . '" />';
                break;
        }

        $html .= '</div></div>';
        return $html;
    }
}
