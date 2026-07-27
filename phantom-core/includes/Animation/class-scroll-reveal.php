<?php
declare(strict_types=1);

namespace PhantomCore\Animation;

defined('ABSPATH') || exit;

class Scroll_Reveal {
    private static ?self $instance = null;
    private array $presets = [];

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->presets = [
            'fade-up' => [
                'label' => __('Fade Up', 'phantom-core'),
                'from' => ['opacity' => 0, 'y' => 40],
                'duration' => 0.6,
                'ease' => 'power2.out',
                'trigger' => ['start' => 'top 85%'],
            ],
            'fade-down' => [
                'label' => __('Fade Down', 'phantom-core'),
                'from' => ['opacity' => 0, 'y' => -40],
                'duration' => 0.6,
                'ease' => 'power2.out',
            ],
            'fade-left' => [
                'label' => __('Fade Left', 'phantom-core'),
                'from' => ['opacity' => 0, 'x' => -40],
                'duration' => 0.6,
                'ease' => 'power2.out',
            ],
            'fade-right' => [
                'label' => __('Fade Right', 'phantom-core'),
                'from' => ['opacity' => 0, 'x' => 40],
                'duration' => 0.6,
                'ease' => 'power2.out',
            ],
            'zoom-in' => [
                'label' => __('Zoom In', 'phantom-core'),
                'from' => ['opacity' => 0, 'scale' => 0.8],
                'duration' => 0.6,
                'ease' => 'power2.out',
            ],
            'zoom-out' => [
                'label' => __('Zoom Out', 'phantom-core'),
                'from' => ['opacity' => 0, 'scale' => 1.2],
                'duration' => 0.6,
                'ease' => 'power2.out',
            ],
            'flip-up' => [
                'label' => __('Flip Up', 'phantom-core'),
                'from' => ['opacity' => 0, 'rotationX' => 90, 'y' => 40],
                'duration' => 0.7,
                'ease' => 'power3.out',
            ],
            'flip-down' => [
                'label' => __('Flip Down', 'phantom-core'),
                'from' => ['opacity' => 0, 'rotationX' => -90, 'y' => -40],
                'duration' => 0.7,
                'ease' => 'power3.out',
            ],
            'slide-up' => [
                'label' => __('Slide Up', 'phantom-core'),
                'from' => ['y' => 60],
                'duration' => 0.5,
                'ease' => 'power2.out',
            ],
            'slide-down' => [
                'label' => __('Slide Down', 'phantom-core'),
                'from' => ['y' => -60],
                'duration' => 0.5,
                'ease' => 'power2.out',
            ],
        ];
    }

    public function get_presets(): array {
        return $this->presets;
    }

    public function get_preset(string $id): ?array {
        return $this->presets[$id] ?? null;
    }

    public function has_preset(string $id): bool {
        return isset($this->presets[$id]);
    }

    public function get_active_preset(): string {
        return get_option('phantom_animations_scroll_reveal', 'fade-up');
    }

    public function generate_inline_init(string $preset_id = ''): string {
        if (empty($preset_id)) {
            $preset_id = $this->get_active_preset();
        }
        if ('none' === $preset_id) return '';

        $preset = $this->presets[$preset_id] ?? $this->presets['fade-up'];
        $duration = get_option('phantom_animations_scroll_duration', '0.6');
        $delay = get_option('phantom_animations_scroll_delay', '0.1');

        $from = $preset['from'] ?? ['opacity' => 0, 'y' => 40];
        $json_from = wp_json_encode($from);

        $trigger_start = $preset['trigger']['start'] ?? 'top 85%';
        $trigger = wp_json_encode(['trigger' => '.pr-reveal', 'start' => $trigger_start, 'toggleActions' => 'play none none none']);

        return <<<JS
(function() {
    if (typeof gsap === 'undefined') return;
    gsap.registerPlugin(ScrollTrigger);
    var els = document.querySelectorAll('.pr-reveal');
    if (!els.length) return;
    els.forEach(function(el, i) {
        gsap.from(el, {$json_from}, {
            duration: {$duration},
            delay: i * {$delay},
            ease: '{$preset['ease']}',
            scrollTrigger: {$trigger}
        });
    });
})();
JS;
    }

    public function generate_inline_style(): string {
        return <<<CSS
.pr-reveal{opacity:0;will-change:transform,opacity}
.pr-reveal.pr-visible{opacity:1}
CSS;
    }
}
