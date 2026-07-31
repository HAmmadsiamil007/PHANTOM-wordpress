<?php
declare(strict_types=1);

namespace PhantomCore\Inspector;

defined('ABSPATH') || exit;

class State_Manager {
    private static ?self $instance = null;

    public const BREAKPOINTS = [
        'desktop' => ['label' => 'Desktop', 'max_width' => null, 'icon' => 'desktop'],
        'tablet'  => ['label' => 'Tablet', 'max_width' => 1024, 'icon' => 'tablet'],
        'mobile'  => ['label' => 'Mobile', 'max_width' => 768, 'icon' => 'smartphone'],
    ];

    public const STATES = ['normal', 'hover', 'focus', 'active', 'disabled'];

    private string $current_state = 'normal';
    private string $current_viewport = 'desktop';

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function set_state(string $state): void {
        if (in_array($state, self::STATES, true)) {
            $this->current_state = $state;
        }
    }

    public function get_current_state(): string {
        return $this->current_state;
    }

    public function set_viewport(string $viewport): void {
        if (isset(self::BREAKPOINTS[$viewport])) {
            $this->current_viewport = $viewport;
        }
    }

    public function get_current_viewport(): string {
        return $this->current_viewport;
    }

    public function get_breakpoints(): array {
        return self::BREAKPOINTS;
    }

    public function get_breakpoint(string $viewport): ?array {
        return self::BREAKPOINTS[$viewport] ?? null;
    }

    public function resolve_value(\PhantomCore\Components\ComponentInstance $instance, string $token): mixed {
        $state = $this->current_state;
        if ('normal' !== $state && $instance->has_state_override($token, $state)) {
            return $instance->get_state_value($token, $state);
        }

        $value = $this->get_viewport_value($instance, $token);
        if (null !== $value) {
            return $value;
        }

        return $instance->overrides[$token] ?? null;
    }

    public function has_override(\PhantomCore\Components\ComponentInstance $instance, string $token): bool {
        $state = $this->current_state;
        if ('normal' !== $state && $instance->has_state_override($token, $state)) {
            return true;
        }
        if ($this->has_viewport_override($instance, $token)) {
            return true;
        }
        return $instance->is_overridden($token);
    }

    public function is_overridden_in_state(\PhantomCore\Components\ComponentInstance $instance, string $token, string $state): bool {
        return $instance->has_state_override($token, $state);
    }

    public function is_overridden_in_viewport(\PhantomCore\Components\ComponentInstance $instance, string $token, string $viewport): bool {
        return $instance->has_viewport_override($token, $viewport);
    }

    private function get_viewport_value(\PhantomCore\Components\ComponentInstance $instance, string $token): mixed {
        $vp = $this->current_viewport;
        if ('desktop' === $vp) {
            return null;
        }
        return $instance->get_viewport_value($token, $vp);
    }

    private function has_viewport_override(\PhantomCore\Components\ComponentInstance $instance, string $token): bool {
        $vp = $this->current_viewport;
        if ('desktop' === $vp) {
            return false;
        }
        return $instance->has_viewport_override($token, $vp);
    }
}
