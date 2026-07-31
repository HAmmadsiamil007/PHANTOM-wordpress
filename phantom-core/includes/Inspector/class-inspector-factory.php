<?php
declare(strict_types=1);

namespace PhantomCore\Inspector;

use PhantomCore\Components\ComponentDefinition;
use PhantomCore\Components\ComponentInstance;
use PhantomCore\Components\Property_Registry;
use PhantomCore\Components\Property;

defined('ABSPATH') || exit;

class Inspector_Factory {
    private static ?self $instance = null;
    private Property_Registry $property_registry;
    private State_Manager $state_manager;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->property_registry = Property_Registry::get_instance();
        $this->state_manager = State_Manager::get_instance();
    }

    public function render_panels(string $component_name, ?ComponentInstance $instance = null, string $state = 'normal', string $viewport = 'desktop'): string {
        $this->state_manager->set_state($state);
        $this->state_manager->set_viewport($viewport);

        $registry = \PhantomCore\Components\Component_Registry::get_instance();
        $component = $registry->get($component_name);

        if (null === $component) {
            return '<div class="vc-panel vc-panel-error">Component not found: ' . esc_html($component_name) . '</div>';
        }

        $output = '';

        $output .= $this->render_header($component, $instance);
        $output .= $this->render_toolbar($component, $instance, $state, $viewport);

        $panels = $this->get_panels($component, $instance);
        foreach ($panels as $panel) {
            $output .= $panel;
        }

        return $output;
    }

    private function render_toolbar(ComponentDefinition $component, ?ComponentInstance $instance, string $current_state, string $current_viewport): string {
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

    private function render_header(ComponentDefinition $component, ?ComponentInstance $instance): string {
        $label = esc_html($component->label);
        $name = esc_html($component->name);
        $source = $instance ? esc_html($instance->source) : esc_html($component->content_type);
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

    public function get_panels(ComponentDefinition $component, ?ComponentInstance $instance = null): array {
        $panels = [];
        $panels_by_category = [];

        foreach ($component->properties as $prop_name) {
            $prop = $this->property_registry->get($prop_name);
            if (null === $prop) {
                continue;
            }
            $panels_by_category[$prop->category][] = $prop;
        }

        $panel_order = ['colors', 'typography', 'spacing', 'layout', 'effects'];
        foreach ($panel_order as $cat) {
            if (!isset($panels_by_category[$cat])) {
                continue;
            }
            $panel_html = $this->render_panel($cat, $panels_by_category[$cat], $component, $instance);
            if ($panel_html) {
                $panels[] = $panel_html;
            }
        }

        $content_panel = $this->render_content_panel($component, $instance);
        if ($content_panel) {
            $panels[] = $content_panel;
        }

        $asset_panel = $this->render_asset_panel($component, $instance);
        if ($asset_panel) {
            $panels[] = $asset_panel;
        }

        return $panels;
    }

    private function render_panel(string $category, array $properties, ComponentDefinition $component, ?ComponentInstance $instance): string {
        $labels = [
            'colors' => 'Colors',
            'typography' => 'Typography',
            'spacing' => 'Spacing',
            'layout' => 'Layout',
            'effects' => 'Effects',
        ];
        $icons = [
            'colors' => 'dashicons-admin-appearance',
            'typography' => 'dashicons-editor-textcolor',
            'spacing' => 'dashicons-editor-expand',
            'layout' => 'dashicons-grid-view',
            'effects' => 'dashicons-visibility',
        ];

        $label = $labels[$category] ?? ucfirst($category);
        $icon = $icons[$category] ?? 'dashicons-admin-generic';

        $html = '<div class="vc-panel vc-panel-' . esc_attr($category) . '">';
        $html .= '<div class="vc-panel-header" data-panel="' . esc_attr($category) . '">';
        $html .= '<span class="dashicons ' . esc_attr($icon) . '"></span>';
        $html .= '<span class="vc-panel-title">' . esc_html($label) . '</span>';
        $html .= '<span class="vc-panel-toggle dashicons dashicons-arrow-up"></span>';
        $html .= '</div>';
        $html .= '<div class="vc-panel-body">';

        foreach ($properties as $prop) {
            $html .= $this->render_control($prop, $component, $instance);
        }

        $html .= '</div></div>';
        return $html;
    }

    private function render_control(Property $prop, ComponentDefinition $component, ?ComponentInstance $instance): string {
        $state = $this->state_manager->get_current_state();
        $viewport = $this->state_manager->get_current_viewport();

        $value = null;
        if ($instance) {
            $value = $this->state_manager->resolve_value($instance, $prop->name);
        }
        if (null === $value) {
            $value = $prop->default;
        }

        $name = esc_attr($prop->name);
        $label = esc_html($prop->label);
        $type = $prop->type;
        $current_value = esc_attr((string)$value);

        $override_classes = '';
        $override_dot = '';
        if ($instance) {
            if ('normal' !== $state && $this->state_manager->is_overridden_in_state($instance, $prop->name, $state)) {
                $override_classes = ' vc-control-has-state-override';
                $override_dot = '<span class="vc-override-dot vc-override-dot-state" title="' . esc_attr(ucfirst($state)) . ' override active"></span>';
            } elseif ('desktop' !== $viewport && $this->state_manager->is_overridden_in_viewport($instance, $prop->name, $viewport)) {
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

            case 'range':
                $min = $prop->range['min'] ?? 0;
                $max = $prop->range['max'] ?? 100;
                $step = $prop->range['step'] ?? 1;
                $unit = $prop->unit ?? '';
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
                foreach ($prop->options as $opt_value => $opt_label) {
                    $selected = ((string)$opt_value === (string)$value) ? ' selected' : '';
                    $html .= '<option value="' . esc_attr($opt_value) . '"' . $selected . '>' . esc_html($opt_label) . '</option>';
                }
                $html .= '</select>';
                break;

            case 'font_picker':
                $fonts = [
                    'Inter' => 'Inter',
                    'Archivo' => 'Archivo',
                    'Playfair Display' => 'Playfair Display',
                    'Roboto' => 'Roboto',
                    'Open Sans' => 'Open Sans',
                    'Lato' => 'Lato',
                    'Montserrat' => 'Montserrat',
                    'Poppins' => 'Poppins',
                    'Source Serif 4' => 'Source Serif 4',
                    'DM Sans' => 'DM Sans',
                    'Space Grotesk' => 'Space Grotesk',
                    'Manrope' => 'Manrope',
                    'Plus Jakarta Sans' => 'Plus Jakarta Sans',
                    'Syne' => 'Syne',
                ];
                $html .= '<select class="vc-font-picker" data-property="' . $name . '">';
                foreach ($fonts as $font_value => $font_label) {
                    $selected = ($font_value === $value) ? ' selected' : '';
                    $html .= '<option value="' . esc_attr($font_value) . '"' . $selected . '>' . esc_html($font_label) . '</option>';
                }
                $html .= '</select>';
                break;

            default:
                $html .= '<input type="text" class="vc-text-input" value="' . $current_value . '" data-property="' . $name . '" />';
                break;
        }

        $html .= '</div></div>';
        return $html;
    }

    private function render_content_panel(ComponentDefinition $component, ?ComponentInstance $instance): string {
        if ('static' !== $component->content_type || empty($component->component_settings)) {
            return '';
        }

        $html = '<div class="vc-panel vc-panel-content">';
        $html .= '<div class="vc-panel-header" data-panel="content">';
        $html .= '<span class="dashicons dashicons-edit"></span>';
        $html .= '<span class="vc-panel-title">Content</span>';
        $html .= '<span class="vc-panel-toggle dashicons dashicons-arrow-up"></span>';
        $html .= '</div>';
        $html .= '<div class="vc-panel-body">';

        $settings = \PhantomCore\Settings_Registry::get_instance();
        foreach ($component->component_settings as $setting_key) {
            $setting_value = $settings->get_string($setting_key);
            $label = ucwords(str_replace('_', ' ', $setting_key));
            $html .= '<div class="vc-control vc-control-text" data-setting="' . esc_attr($setting_key) . '">';
            $html .= '<label class="vc-control-label">' . esc_html($label) . '</label>';
            $html .= '<div class="vc-control-input">';
            $html .= '<input type="text" class="vc-text-input" value="' . esc_attr($setting_value) . '" data-setting="' . esc_attr($setting_key) . '" />';
            $html .= '</div></div>';
        }

        $html .= '</div></div>';
        return $html;
    }

    private function render_asset_panel(ComponentDefinition $component, ?ComponentInstance $instance): string {
        if (empty($component->default_assets)) {
            return '';
        }

        $html = '<div class="vc-panel vc-panel-assets">';
        $html .= '<div class="vc-panel-header" data-panel="assets">';
        $html .= '<span class="dashicons dashicons-format-image"></span>';
        $html .= '<span class="vc-panel-title">Media</span>';
        $html .= '<span class="vc-panel-toggle dashicons dashicons-arrow-up"></span>';
        $html .= '</div>';
        $html .= '<div class="vc-panel-body">';

        $media_registry = \PhantomCore\Components\Media_Asset_Registry::get_instance();
        foreach ($component->default_assets as $asset_key) {
            $asset = $media_registry->get($asset_key);
            if (null === $asset) {
                continue;
            }
            $url = $media_registry->get_url($asset_key);
            $html .= '<div class="vc-control vc-control-asset" data-asset="' . esc_attr($asset_key) . '">';
            $html .= '<label class="vc-control-label">' . esc_html($asset->label) . '</label>';
            $html .= '<div class="vc-asset-preview">';
            if ($url) {
                $html .= '<img src="' . esc_url($url) . '" alt="' . esc_attr($asset->label) . '" style="max-width:100%;max-height:80px;object-fit:cover;" />';
            }
            $html .= '</div>';
            $html .= '<div class="vc-asset-actions">';
            $html .= '<button type="button" class="vc-btn vc-btn-upload" data-asset="' . esc_attr($asset_key) . '">Upload</button>';
            $html .= '<button type="button" class="vc-btn vc-btn-reset" data-asset="' . esc_attr($asset_key) . '">Reset</button>';
            $html .= '</div>';
            $html .= '</div>';
        }

        $html .= '</div></div>';
        return $html;
    }

    public function render_state_selector(ComponentDefinition $component, ?ComponentInstance $instance = null, string $current_state = 'normal'): string {
        $states = $component->style_states;
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
