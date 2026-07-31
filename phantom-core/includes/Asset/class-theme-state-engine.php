<?php
declare(strict_types=1);

namespace PhantomCore\Asset;

use PhantomCore\Components\ComponentInstance;

defined('ABSPATH') || exit;

class Theme_State_Engine {
    private static ?self $instance = null;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get_resolved_theme(): ResolvedTheme {
        $instances = ComponentInstance::load_all();

        $design_tokens = [];
        if (class_exists('\PhantomCore\Design\Token_Registry')) {
            $registry = \PhantomCore\Design\Token_Registry::get_instance();
            $design_tokens = $registry->get_all();
        }

        $preset = [];
        $preset_name = '';
        if (class_exists('\PhantomCore\Design\Preset_Manager')) {
            $preset_manager = \PhantomCore\Design\Preset_Manager::get_instance();
            $preset_name = $preset_manager->get_active_preset_name();
            $preset = $preset_manager->get_active_preset_data();
        }

        $registry_version = '';
        if (class_exists('\PhantomCore\Components\Component_Registry')) {
            $registry = \PhantomCore\Components\Component_Registry::get_instance();
            $registry_version = md5(serialize($registry->get_all()));
        }

        return new ResolvedTheme(
            instances: $instances,
            design_tokens: $design_tokens,
            preset: $preset,
            active_preset_name: $preset_name,
            component_registry_version: $registry_version
        );
    }

    public function get_resolved_value(ComponentInstance $instance, string $token, string $state = 'normal', string $viewport = 'desktop'): mixed {
        if ('desktop' !== $viewport && $instance->has_viewport_override($token, $viewport)) {
            return $instance->get_viewport_value($token, $viewport);
        }
        if ('normal' !== $state && $instance->has_state_override($token, $state)) {
            return $instance->get_state_value($token, $state);
        }
        return $instance->overrides[$token] ?? null;
    }

    public function has_any_override(ComponentInstance $instance): bool {
        return !empty($instance->overrides)
            || !empty($instance->state_overrides)
            || !empty($instance->viewport_overrides);
    }
}
