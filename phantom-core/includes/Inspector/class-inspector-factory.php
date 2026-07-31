<?php
declare(strict_types=1);

namespace PhantomCore\Inspector;

use PhantomCore\Components\ComponentInstance;
use PhantomCore\Design\Component_Definition;
use PhantomCore\Design\Component_Definition_Registry;
use PhantomCore\Design\Component_Metadata;
use PhantomCore\Design\Property_Registry;

defined('ABSPATH') || exit;

/**
 * Inspector_Factory — renders the Visual Customizer inspector panels.
 *
 * Components with metadata parts (Component_Metadata) are rendered from the
 * parts/property definitions, optionally filtered by a single tool (the
 * universal Tool Engine). Components without metadata fall back to their
 * legacy definition tabs. All control markup comes from Control_Renderer.
 */
class Inspector_Factory {
    private static ?self $instance = null;
    private State_Manager $state_manager;
    private Control_Renderer $renderer;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->state_manager = State_Manager::get_instance();
        $this->renderer = Control_Renderer::get_instance();
    }

    public function render_panels(string $component_name, ?ComponentInstance $instance = null, string $state = 'normal', string $viewport = 'desktop', array $editable = [], string $tool = ''): string {
        $this->state_manager->set_state($state);
        $this->state_manager->set_viewport($viewport);

        $definition = Component_Definition_Registry::get_instance()->resolve($component_name, $editable);

        if (null === $definition) {
            return '<div class="vc-panel vc-panel-error">Component not found: ' . esc_html($component_name) . '</div>';
        }

        $output = '';

        $output .= $this->render_header($definition, $instance);
        $output .= $this->render_toolbar($definition, $instance, $state, $viewport);

        if ('' !== $tool && 'assets' !== $tool && !Property_Registry::get_instance()->tool_exists($tool)) {
            $tool = '';
        }

        $metadata = Component_Metadata::get_instance();
        $panels = $metadata->has($definition->id)
            ? $this->get_metadata_panels($definition, $instance, $tool)
            : $this->get_panels($definition, $instance);

        if ('' === $tool || 'assets' === $tool) {
            $asset_panel = $this->render_assets_panel();
            if ($asset_panel) {
                $panels[] = $asset_panel;
            }
        }

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
            $panel_html = $this->renderer->render_panel(
                (string) ($tab['key'] ?? 'general'),
                (string) ($tab['label'] ?? 'General'),
                (array) ($tab['fields'] ?? []),
                $component,
                $instance
            );
            if ($panel_html) {
                $panels[] = $panel_html;
            }
        }

        return $panels;
    }

    /**
     * Metadata-driven panels for a component, optionally filtered to one tool.
     *
     * @return string[]
     */
    private function get_metadata_panels(Component_Definition $component, ?ComponentInstance $instance, string $tool): array {
        $metadata = Component_Metadata::get_instance();
        $parts = $metadata->get_parts_for_tool($component->id, $tool);

        $panels = [];
        foreach ($parts as $part_id => $part) {
            $fields = [];
            $icon = '';
            foreach ($part['properties'] as $entry) {
                $def = $entry['def'];
                $field = [
                    'key'     => $entry['key'],
                    'type'    => $def['type'] ?? 'text',
                    'label'   => $entry['label'] ?? $def['label'] ?? $entry['property'],
                    'default' => $def['default'] ?? '',
                ];
                foreach (['min', 'max', 'step', 'unit', 'options'] as $extra) {
                    if (isset($def[$extra])) {
                        $field[$extra] = $def[$extra];
                    }
                }
                $fields[] = $field;
                if (!$icon && isset($def['tool'])) {
                    $icon = Control_Renderer::TOOL_ICONS[$def['tool']] ?? '';
                }
            }
            if (empty($fields)) {
                continue;
            }
            $panels[] = $this->renderer->render_panel($part_id, $part['label'], $fields, $component, $instance, $icon);
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
