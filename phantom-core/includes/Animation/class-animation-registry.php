<?php
declare(strict_types=1);

namespace PhantomCore\Animation;

defined('ABSPATH') || exit;

class Animation_Registry {
    private static ?self $instance = null;
    private array $animations = [];
    private bool $defaults_registered = false;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function register(Animation $animation): void {
        $this->animations[$animation->id] = $animation;
    }

    public function register_defaults(): void {
        if ($this->defaults_registered) return;

        // GSAP-powered scroll reveal animations
        $this->register(new Animation('fade-up', __('Fade Up', 'phantom-core'), 'scroll', 'entrance', '.pr-reveal', [
            'opacity' => 0,
            'y' => 40,
            'duration' => 0.6,
            'ease' => 'power2.out',
        ]));
        $this->register(new Animation('fade-down', __('Fade Down', 'phantom-core'), 'scroll', 'entrance', '.pr-reveal', [
            'opacity' => 0,
            'y' => -40,
            'duration' => 0.6,
            'ease' => 'power2.out',
        ]));
        $this->register(new Animation('fade-left', __('Fade Left', 'phantom-core'), 'scroll', 'entrance', '.pr-reveal', [
            'opacity' => 0,
            'x' => -40,
            'duration' => 0.6,
            'ease' => 'power2.out',
        ]));
        $this->register(new Animation('fade-right', __('Fade Right', 'phantom-core'), 'scroll', 'entrance', '.pr-reveal', [
            'opacity' => 0,
            'x' => 40,
            'duration' => 0.6,
            'ease' => 'power2.out',
        ]));
        $this->register(new Animation('zoom-in', __('Zoom In', 'phantom-core'), 'scroll', 'entrance', '.pr-reveal', [
            'opacity' => 0,
            'scale' => 0.8,
            'duration' => 0.6,
            'ease' => 'power2.out',
        ]));
        $this->register(new Animation('zoom-out', __('Zoom Out', 'phantom-core'), 'scroll', 'entrance', '.pr-reveal', [
            'opacity' => 0,
            'scale' => 1.2,
            'duration' => 0.6,
            'ease' => 'power2.out',
        ]));
        $this->register(new Animation('flip-up', __('Flip Up', 'phantom-core'), 'scroll', 'entrance', '.pr-reveal', [
            'opacity' => 0,
            'rotationX' => 90,
            'y' => 40,
            'duration' => 0.7,
            'ease' => 'power3.out',
        ]));
        $this->register(new Animation('slide-up', __('Slide Up', 'phantom-core'), 'scroll', 'entrance', '.pr-reveal', [
            'y' => 60,
            'duration' => 0.5,
            'ease' => 'power2.out',
        ]));

        // Hover animations
        $this->register(new Animation('hover-lift', __('Hover Lift', 'phantom-core'), 'hover', 'interaction', '.product-card, .category-card', [
            'y' => -6,
            'shadow' => '0 12px 24px rgba(0,0,0,0.12)',
            'duration' => 0.3,
            'ease' => 'power2.out',
        ]));
        $this->register(new Animation('hover-glow', __('Hover Glow', 'phantom-core'), 'hover', 'interaction', '.product-card, .category-card', [
            'shadow' => '0 0 30px rgba(193,18,31,0.15)',
            'duration' => 0.3,
            'ease' => 'power2.out',
        ]));
        $this->register(new Animation('hover-scale', __('Hover Scale', 'phantom-core'), 'hover', 'interaction', '.product-card, .category-card', [
            'scale' => 1.03,
            'duration' => 0.3,
            'ease' => 'power2.out',
        ]));
        $this->register(new Animation('hover-shine', __('Hover Shine', 'phantom-core'), 'hover', 'interaction', '.product-card, .category-card', [
            'background' => 'linear-gradient(105deg, transparent 40%, rgba(255,255,255,0.3) 50%, transparent 60%)',
            'duration' => 0.5,
            'ease' => 'power2.out',
        ]));

        // Page transitions
        $this->register(new Animation('page-fade', __('Page Fade', 'phantom-core'), 'page', 'transition', '#main-content', [
            'opacity' => [0, 1],
            'duration' => 0.4,
            'ease' => 'power2.inOut',
        ]));
        $this->register(new Animation('page-slide-left', __('Page Slide Left', 'phantom-core'), 'page', 'transition', '#main-content', [
            'x' => [60, 0],
            'opacity' => [0, 1],
            'duration' => 0.4,
            'ease' => 'power2.inOut',
        ]));
        $this->register(new Animation('page-slide-up', __('Page Slide Up', 'phantom-core'), 'page', 'transition', '#main-content', [
            'y' => [40, 0],
            'opacity' => [0, 1],
            'duration' => 0.4,
            'ease' => 'power2.inOut',
        ]));
        $this->register(new Animation('page-zoom', __('Page Zoom', 'phantom-core'), 'page', 'transition', '#main-content', [
            'scale' => [0.95, 1],
            'opacity' => [0, 1],
            'duration' => 0.4,
            'ease' => 'power2.inOut',
        ]));

        // Parallax
        $this->register(new Animation('parallax-slow', __('Parallax Slow', 'phantom-core'), 'parallax', 'scroll_effect', '.hero-section, .parallax-section', [
            'speed' => 0.2,
            'direction' => 'vertical',
        ], false));
        $this->register(new Animation('parallax-medium', __('Parallax Medium', 'phantom-core'), 'parallax', 'scroll_effect', '.hero-section, .parallax-section', [
            'speed' => 0.4,
            'direction' => 'vertical',
        ], false));
        $this->register(new Animation('parallax-fast', __('Parallax Fast', 'phantom-core'), 'parallax', 'scroll_effect', '.hero-section, .parallax-section', [
            'speed' => 0.6,
            'direction' => 'vertical',
        ], false));

        // 3D Tilt
        $this->register(new Animation('tilt-subtle', __('3D Tilt Subtle', 'phantom-core'), 'tilt', '3d', '.product-card, .category-card', [
            'max' => 5,
            'perspective' => 1000,
            'scale' => 1.02,
        ], false));
        $this->register(new Animation('tilt-dramatic', __('3D Tilt Dramatic', 'phantom-core'), 'tilt', '3d', '.product-card, .category-card', [
            'max' => 15,
            'perspective' => 1000,
            'scale' => 1.05,
        ], false));

        $this->defaults_registered = true;
    }

    public function get(string $id): ?Animation {
        return $this->animations[$id] ?? null;
    }

    public function has(string $id): bool {
        return isset($this->animations[$id]);
    }

    public function get_all(): array {
        return $this->animations;
    }

    public function get_by_category(string $category): array {
        return array_filter(
            $this->animations,
            fn(Animation $a) => $a->category === $category
        );
    }

    public function get_by_type(string $type): array {
        return array_filter(
            $this->animations,
            fn(Animation $a) => $a->type === $type
        );
    }

    public function get_categories(): array {
        $cats = [];
        foreach ($this->animations as $a) {
            $cats[$a->category] = true;
        }
        return array_keys($cats);
    }

    public function deregister(string $id): bool {
        if (!isset($this->animations[$id])) return false;
        unset($this->animations[$id]);
        return true;
    }

    public function count(): int {
        return count($this->animations);
    }
}
