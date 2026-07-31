<?php
declare(strict_types=1);

namespace PhantomCore\Inspector;

use PhantomCore\Components\ComponentInstance;
use PhantomCore\Design\Component_Definition;
use PhantomCore\Design\Component_Definition_Registry;

defined('ABSPATH') || exit;

class Inspector_Factory {
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

    public function render_panels(string $component_name, ?ComponentInstance $instance = null, string $state = 'normal', string $viewport = 'desktop'): string {
        $this->state_manager->set_state($state);
        $this->state_manager->set_viewport($viewport);

        $definition = Component_Definition_Registry::get_instance()->get($component_name);

        if (null === $definition) {
            return '<div class="vc-panel vc-panel-error">Component not found: ' . esc_html($component_name) . '</div>';
        }

        $output = '';

        $output .= $this->render_header($definition, $instance);
        $output .= $this->render_toolbar($definition, $instance, $state, $viewport);

        $panels = $this->get_panels($definition, $instance);
        foreach ($panels as $panel) {
            $output .= $panel;
        }

        return $output;
    }

    private function render_toolbar(Component_Definition $component, ?ComponentInstance $instance, string $current_state, string $current_viewport): string {
        $html = '<div class="vc-inspector-toolbar">';
        $html .= $this->render_state_selector($component, $instance, $current_state);
        $html .= $this->render_viewport_indicator($current_viewport);
        $html .= '</div>';
        return $html;
    }

    private function render_viewport_indicator(string $current_viewport): string {
        $breakpoints = State_Manager::BREAKPOINTS;
        $label = $breakpoints[$current_viewport]['label'] ?? ucfirst($current_viewport);

        $html = '<div class="vc-viewport-indicator">';
        $html .= '<div class="vc-viewport-dropdown-wrapper">';
        $html .= '<button type="button" class="vc-viewport-indicator-btn" title="Viewport scope">';
        $html .= '<span class="dashicons dashicons-' . esc_attr($breakpoints[$current_viewport]['icon'] ?? 'desktop') . '"></span>';
        $html .= '<span class="vc-viewport-label">' . esc_html($label) . '</span>';
        $html .= '</button>';
        $html .= '<div class="vc-viewport-dropdown">';
        foreach ($breakpoints as $key => $bp) {
            $active = ($key === $current_viewport) ? ' active' : '';
            $icon = $bp['icon'] ?? 'desktop';
            $html .= '<button type="button" class="vc-viewport-option' . $active . '" data-viewport="' . esc_attr($key) . '">';
            $html .= '<span class="dashicons dashicons-' . esc_attr($icon) . '"></span>';
            $html .= '<span>' . esc_html($bp['label']) . '</span>';
            if ($bp['max_width']) {
                $html .= '<span class="vc-viewport-width">' . esc_html($bp['max_width']) . 'px</span>';
            }
            $html .= '</button>';
        }
        $html .= '</div></div></div>';
        return $html;
    }

    private function render_header(Component_Definition $component, ?ComponentInstance $instance): string {
        $label = esc_html($component->name);
        $name = esc_html($component->id);
        $source = $instance ? esc_html($instance->source) : esc_html(ucfirst($component->id));
        $locked = $instance && $instance->locked;

        $html = '<div class="vc-panel vc-header-panel">';
        $html .= '<div class="vc-header-title">';
        $html .= '<span class="vc-header-icon dashicons dashicons-layout"></span>';
        $html .= "<h3>{$label}</h3>";
        $html .= '</div>';
        $html .= '<div class="vc-header-meta">';
        $html .= "<span class=\"vc-badge vc-badge-source\">{$source}</span>";
        if ($locked) {
            $html .= '<span class="vc-badge vc-badge-locked">Locked</span>';
        }
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    public function get_panels(Component_Definition $component, ?ComponentInstance $instance = null): array {
        $panels = [];

        foreach ($component->tabs as $tab) {
            $panel_html = $this->render_panel($tab, $component, $instance);
            if ($panel_html) {
                $panels[] = $panel_html;
            }
        }

        $asset_panel = $this->render_assets_panel();
        if ($asset_panel) {
            $panels[] = $asset_panel;
        }

        return $panels;
    }

    private function render_assets_panel(): string {
        if (!class_exists(\PhantomCore\Components\Media_Asset_Registry::class)) {
            return '';
        }

        $registry = \PhantomCore\Components\Media_Asset_Registry::get_instance();
        if (method_exists($registry, 'register_defaults')) {
            $registry->register_defaults();
        }

        $assets = $registry->get_all('image');
        if (empty($assets)) {
            return '';
        }

        $html = '<div class="vc-panel vc-panel-assets">';
        $html .= '<div class="vc-panel-header" data-panel="assets">';
        $html .= '<span class="dashicons dashicons-format-image"></span>';
        $html .= '<span class="vc-panel-title">' . esc_html__('Assets', 'phantom-core') . '</span>';
        $html .= '<span class="vc-panel-toggle dashicons dashicons-arrow-up"></span>';
        $html .= '</div>';
        $html .= '<div class="vc-panel-body">';

        foreach ($assets as $asset) {
            $html .= $this->render_asset_row($asset);
        }

        $html .= '</div></div>';
        return $html;
    }

    private function render_asset_row(\PhantomCore\Components\MediaAsset $asset): string {
        $key = esc_attr($asset->key);
        $label = esc_html($asset->label);
        $url = esc_url(\PhantomCore\Components\Media_Asset_Registry::get_instance()->get_url($asset->key));
        $default = esc_url($asset->default);

        $preview = $url
            ? '<img class="vc-asset-preview" src="' . $url . '" alt="' . $label . '" data-default="' . $default . '" onerror="this.onerror=null;this.src=this.getAttribute(\'data-default\');">'
            : '<span class="vc-asset-preview">' . esc_html__('Default', 'phantom-core') . '</span>';

        $html = '<div class="vc-asset-row" data-asset="' . $key . '">';
        $html .= '<div class="vc-asset-info">';
        $html .= '<span class="vc-asset-label">' . $label . '</span>';
        $html .= $preview;
        $html .= '</div>';
        $html .= '<button type="button" class="vc-btn-upload" data-asset="' . $key . '">' . esc_html__('Upload', 'phantom-core') . '</button>';
        $html .= '<button type="button" class="vc-btn-reset" data-asset="' . $key . '">' . esc_html__('Reset', 'phantom-core') . '</button>';
        $html .= '</div>';

        return $html;
    }

    private function render_panel(array $tab, Component_Definition $component, ?ComponentInstance $instance): string {
        $key = $tab['key'] ?? 'general';
        $label = $tab['label'] ?? ucfirst($key);
        $fields = $tab['fields'] ?? [];

        $icons = [
            'content' => 'dashicons-edit',
            'background' => 'dashicons-admin-appearance',
            'typography' => 'dashicons-editor-textcolor',
            'spacing' => 'dashicons-editor-expand',
            'layout' => 'dashicons-grid-view',
            'animation' => 'dashicons-visibility',
            'responsive' => 'dashicons-desktop',
        ];
        $icon = $icons[$key] ?? 'dashicons-admin-generic';

        $html = '<div class="vc-panel vc-panel-' . esc_attr($key) . '">';
        $html .= '<div class="vc-panel-header" data-panel="' . esc_attr($key) . '">';
        $html .= '<span class="dashicons ' . esc_attr($icon) . '"></span>';
        $html .= '<span class="vc-panel-title">' . esc_html($label) . '</span>';
        $html .= '<span class="vc-panel-toggle dashicons dashicons-arrow-up"></span>';
        $html .= '</div>';
        $html .= '<div class="vc-panel-body">';

        foreach ($fields as $field) {
            $html .= $this->render_control($field, $component, $instance);
        }

        $html .= '</div></div>';
        return $html;
    }

    private function render_control(array $field, Component_Definition $component, ?ComponentInstance $instance): string {
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

        $html = '<div class="vc-control vc-control-' . esc_attr($type) . $override_classes . '" data-property="' . $name . '">';
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

    public function render_state_selector(Component_Definition $component, ?ComponentInstance $instance = null, string $current_state = 'normal'): string {
        $states = ['normal'];
        if ($component->supports('animate')) {
            $states[] = 'hover';
        }
        if (count($states) <= 1) {
            return '';
        }

        $labels = [
            'normal' => 'Normal',
            'hover' => 'Hover',
            'focus' => 'Focus',
            'active' => 'Active',
            'disabled' => 'Disabled',
        ];

        $html = '<div class="vc-state-selector">';
        $html .= '<label class="vc-control-label">State</label>';
        $html .= '<div class="vc-state-buttons">';
        foreach ($states as $state) {
            $active = ($state === $current_state) ? ' active' : '';
            $label = $labels[$state] ?? ucfirst($state);
            $has_overrides = ($instance && $this->has_state_overrides($instance, $state));
            $dot = $has_overrides ? '<span class="vc-state-dot"></span>' : '';
            $html .= '<button type="button" class="vc-state-btn' . $active . '" data-state="' . esc_attr($state) . '">' . $dot . esc_html($label) . '</button>';
        }
        $html .= '</div></div>';

        return $html;
    }

    private function has_state_overrides(ComponentInstance $instance, string $state): bool {
        return !empty($instance->state_overrides[$state]);
    }
}
