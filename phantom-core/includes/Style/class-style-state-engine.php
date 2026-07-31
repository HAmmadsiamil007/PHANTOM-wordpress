<?php
declare(strict_types=1);

namespace PhantomCore\Style;

use PhantomCore\Components\ComponentInstance;
use PhantomCore\Components\ComponentDefinition;

defined('ABSPATH') || exit;

class Style_State_Engine {
    private static ?self $instance = null;

    public const VALID_STATES = ['normal', 'hover', 'focus', 'active', 'disabled', 'visited'];
    public const VALID_VIEWPORTS = ['desktop', 'tablet', 'mobile'];

    private string $current_state = 'normal';
    private string $current_viewport = 'desktop';

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function set_state(string $state): void {
        if (in_array($state, self::VALID_STATES, true)) {
            $this->current_state = $state;
        }
    }

    public function get_current_state(): string {
        return $this->current_state;
    }

    public function set_viewport(string $viewport): void {
        if (in_array($viewport, self::VALID_VIEWPORTS, true)) {
            $this->current_viewport = $viewport;
        }
    }

    public function get_current_viewport(): string {
        return $this->current_viewport;
    }

    public function resolve(ComponentInstance $instance, string $token, ComponentDefinition $component): mixed {
        $state = $this->current_state;

        if ('normal' !== $state && $instance->has_state_override($token, $state)) {
            return $instance->get_state_value($token, $state);
        }

        if ('desktop' !== $this->current_viewport && $instance->has_viewport_override($token, $this->current_viewport)) {
            return $instance->get_viewport_value($token, $this->current_viewport);
        }

        if ($instance->is_overridden($token)) {
            return $instance->get_value($token);
        }

        if (isset($component->instance_defaults[$token])) {
            return $component->instance_defaults[$token];
        }

        $token_index = array_search($token, $component->tokens, true);
        if (false !== $token_index) {
            $registry = \PhantomCore\Settings_Registry::get_instance();
            $token_key = $component->name . '_' . $token;
            $default = $registry->get_string($token_key);
            if (!empty($default)) {
                return $default;
            }
        }

        return null;
    }

    public function get_state_css(string $state): string {
        $selectors = [
            'normal'   => '',
            'hover'    => ':hover',
            'focus'    => ':focus',
            'active'   => ':active',
            'disabled' => ':disabled',
            'visited'  => ':visited',
        ];
        return $selectors[$state] ?? ':' . $state;
    }

    public function generate_state_css(ComponentInstance $instance, ComponentDefinition $component): string {
        $css = '';
        $group = $instance->token_group;

        $state_overrides = $instance->state_overrides;
        foreach ($state_overrides as $state => $tokens) {
            if (empty($tokens)) {
                continue;
            }
            $pseudo = $this->get_state_css($state);
            $css .= ".vc-component-{$group}{$pseudo} {\n";
            foreach ($tokens as $token => $value) {
                $prop = $this->token_to_css_property($token, $component);
                if ($prop) {
                    $css .= "  {$prop}: {$value};\n";
                }
            }
            $css .= "}\n";
        }

        $viewport_overrides = $instance->viewport_overrides;
        $breakpoints = [
            'tablet' => 1024,
            'mobile' => 768,
        ];
        foreach ($viewport_overrides as $viewport => $tokens) {
            if (empty($tokens) || !isset($breakpoints[$viewport])) {
                continue;
            }
            $max_width = $breakpoints[$viewport];
            $css .= "@media (max-width: {$max_width}px) {\n";
            $css .= "  .vc-component-{$group} {\n";
            foreach ($tokens as $token => $value) {
                $prop = $this->token_to_css_property($token, $component);
                if ($prop) {
                    $css .= "    {$prop}: {$value};\n";
                }
            }
            $css .= "  }\n";
            $css .= "}\n";
        }

        return $css;
    }

    public function has_state_override(ComponentInstance $instance, string $token, string $state): bool {
        return $instance->has_state_override($token, $state);
    }

    public function has_viewport_override(ComponentInstance $instance, string $token, string $viewport): bool {
        return $instance->has_viewport_override($token, $viewport);
    }

    public function get_active_states(ComponentInstance $instance): array {
        $active = [];
        foreach (self::VALID_STATES as $state) {
            if (!empty($instance->state_overrides[$state])) {
                $active[] = $state;
            }
        }
        return $active;
    }

    public function get_active_viewports(ComponentInstance $instance): array {
        $active = [];
        foreach (self::VALID_VIEWPORTS as $vp) {
            if (!empty($instance->viewport_overrides[$vp])) {
                $active[] = $vp;
            }
        }
        return $active;
    }

    public function render_state_selector(ComponentDefinition $component, ?ComponentInstance $instance, string $current_state): string {
        $states = $component->style_states;
        if (count($states) <= 1) {
            return '';
        }

        $labels = [
            'normal'   => 'Normal',
            'hover'    => 'Hover',
            'focus'    => 'Focus',
            'active'   => 'Active',
            'disabled' => 'Disabled',
            'visited'  => 'Visited',
        ];

        $html = '<div class="vc-state-selector">';
        $html .= '<label class="vc-control-label">State</label>';
        $html .= '<div class="vc-state-buttons">';
        foreach ($states as $state) {
            $active = ($state === $current_state) ? ' active' : '';
            $label = $labels[$state] ?? ucfirst($state);
            $has_overrides = ($instance && !empty($instance->state_overrides[$state]));
            $dot = $has_overrides ? '<span class="vc-state-dot"></span>' : '';
            $html .= '<button type="button" class="vc-state-btn' . $active . '" data-state="' . esc_attr($state) . '">' . $dot . esc_html($label) . '</button>';
        }
        $html .= '</div></div>';

        return $html;
    }

    public function render_viewport_selector(string $current_viewport): string {
        $viewports = [
            'desktop' => ['label' => 'Desktop', 'icon' => 'desktop'],
            'tablet'  => ['label' => 'Tablet', 'icon' => 'tablet'],
            'mobile'  => ['label' => 'Mobile', 'icon' => 'smartphone'],
        ];

        $html = '<div class="vc-viewport-selector">';
        $html .= '<label class="vc-control-label">Viewport</label>';
        $html .= '<div class="vc-viewport-buttons">';
        foreach ($viewports as $key => $vp) {
            $active = ($key === $current_viewport) ? ' active' : '';
            $html .= '<button type="button" class="vc-viewport-btn' . $active . '" data-viewport="' . esc_attr($key) . '" title="' . esc_attr($vp['label']) . '">';
            $html .= '<span class="dashicons dashicons-' . esc_attr($vp['icon']) . '"></span>';
            $html .= '</button>';
        }
        $html .= '</div></div>';

        return $html;
    }

    private function token_to_css_property(string $token, ComponentDefinition $component): ?string {
        static $inspector_map = null;
        if (null === $inspector_map) {
            $inspector_map = [
                'colors' => [
                    'text_color'       => 'color',
                    'background_color' => 'background-color',
                    'border_color'     => 'border-color',
                    'heading_color'    => 'color',
                ],
                'typography' => [
                    'font_family'    => 'font-family',
                    'font_size'      => 'font-size',
                    'font_weight'    => 'font-weight',
                    'line_height'    => 'line-height',
                    'letter_spacing' => 'letter-spacing',
                    'text_align'     => 'text-align',
                    'text_transform' => 'text-transform',
                ],
                'spacing' => [
                    'padding'       => 'padding',
                    'padding_top'    => 'padding-top',
                    'padding_right'  => 'padding-right',
                    'padding_bottom' => 'padding-bottom',
                    'padding_left'   => 'padding-left',
                    'margin'        => 'margin',
                    'margin_top'     => 'margin-top',
                    'margin_right'   => 'margin-right',
                    'margin_bottom'  => 'margin-bottom',
                    'margin_left'    => 'margin-left',
                    'gap'           => 'gap',
                ],
                'layout' => [
                    'width'             => 'width',
                    'height'            => 'height',
                    'min_width'         => 'min-width',
                    'max_width'         => 'max-width',
                    'min_height'        => 'min-height',
                    'max_height'        => 'max-height',
                    'display'           => 'display',
                    'flex_direction'    => 'flex-direction',
                    'flex_wrap'         => 'flex-wrap',
                    'align_items'       => 'align-items',
                    'justify_content'   => 'justify-content',
                    'position'          => 'position',
                    'top'               => 'top',
                    'right'             => 'right',
                    'bottom'            => 'bottom',
                    'left'              => 'left',
                    'overflow'          => 'overflow',
                    'border_radius'     => 'border-radius',
                    'opacity'           => 'opacity',
                ],
                'effects' => [
                    'box_shadow'       => 'box-shadow',
                    'text_shadow'      => 'text-shadow',
                    'transform'        => 'transform',
                    'transition'       => 'transition',
                    'animation'        => 'animation',
                    'filter'           => 'filter',
                    'backdrop_filter'  => 'backdrop-filter',
                    'mix_blend_mode'   => 'mix-blend-mode',
                ],
            ];
        }

        if (isset($inspector_map[$component->category][$token])) {
            return $inspector_map[$component->category][$token];
        }

        $prop = str_replace('_', '-', $token);
        return $prop;
    }
}
