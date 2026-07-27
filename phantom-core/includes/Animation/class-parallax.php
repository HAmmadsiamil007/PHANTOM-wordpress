<?php
declare(strict_types=1);

namespace PhantomCore\Animation;

defined('ABSPATH') || exit;

class Parallax {
    private static ?self $instance = null;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function is_enabled(): bool {
        return '1' === get_option('phantom_animations_parallax', '0');
    }

    public function get_speed(): float {
        return (float) get_option('phantom_animations_parallax_speed', '0.3');
    }

    public function get_inline_script(): string {
        if (!$this->is_enabled()) return '';
        $speed = $this->get_speed();

        return <<<JS
(function() {
    if (typeof gsap === 'undefined') return;
    gsap.registerPlugin(ScrollTrigger);
    var els = document.querySelectorAll('.parallax-section, .hero-section .hero-image, [data-parallax]');
    if (!els.length) return;
    els.forEach(function(el) {
        var s = parseFloat(el.getAttribute('data-parallax-speed')) || {$speed};
        gsap.to(el, {
            y: function() { return -(ScrollTrigger.maxScroll(window)) * s; },
            ease: 'none',
            scrollTrigger: {
                trigger: el.parentElement || el,
                start: 'top bottom',
                end: 'bottom top',
                scrub: true
            }
        });
    });
})();
JS;
    }
}
