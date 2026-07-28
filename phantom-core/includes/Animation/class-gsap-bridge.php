<?php
declare(strict_types=1);

namespace PhantomCore\Animation;

defined('ABSPATH') || exit;

class GSAP_Bridge {
    private static ?self $instance = null;
    private array $registered_tweens = [];
    private bool $gsap_enqueued = false;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function enqueue_gsap(): void {
        if ($this->gsap_enqueued) return;
        $this->gsap_enqueued = true;

        $version = '3.12.5';

        wp_enqueue_script('gsap', '//cdnjs.cloudflare.com/ajax/libs/gsap/' . $version . '/gsap.min.js', [], $version, true);
        wp_enqueue_script('gsap-scroll-trigger', '//cdnjs.cloudflare.com/ajax/libs/gsap/' . $version . '/ScrollTrigger.min.js', ['gsap'], $version, true);
        wp_enqueue_script('gsap-draggable', '//cdnjs.cloudflare.com/ajax/libs/gsap/' . $version . '/Draggable.min.js', ['gsap'], $version, true);
        wp_enqueue_script('gsap-motion-path', '//cdnjs.cloudflare.com/ajax/libs/gsap/' . $version . '/MotionPathPlugin.min.js', ['gsap'], $version, true);
        wp_enqueue_script('gsap-text', '//cdnjs.cloudflare.com/ajax/libs/gsap/' . $version . '/TextPlugin.min.js', ['gsap'], $version, true);

        $this->register_core_tweens();
    }

    public function enqueue_three(): void {
        _deprecated_function(__METHOD__, '2.0.0', 'PhantomCore\Animation\Three_Bridge::enqueue()');
        wp_enqueue_script('three-core', '//cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js', [], 'r128', true);
    }

    public function enqueue_lenis(): void {
        wp_enqueue_script('lenis', '//unpkg.com/lenis@1.1.13/dist/lenis.min.js', [], '1.1.13', true);
    }

    public function enqueue_lottie(): void {
        wp_enqueue_script('lottie', '//cdnjs.cloudflare.com/ajax/libs/lottie-web/5.12.2/lottie.min.js', [], '5.12.2', true);
    }

    private function register_core_tweens(): void {
        // Scroll reveal tweens
        $this->register_tween('fade-up', [
            'from' => ['opacity' => 0, 'y' => 40],
            'to' => ['opacity' => 1, 'y' => 0],
            'scrollTrigger' => ['trigger' => '.pr-reveal', 'start' => 'top 85%', 'toggleActions' => 'play none none none'],
        ]);
        $this->register_tween('fade-down', [
            'from' => ['opacity' => 0, 'y' => -40],
            'to' => ['opacity' => 1, 'y' => 0],
            'scrollTrigger' => ['trigger' => '.pr-reveal', 'start' => 'top 85%'],
        ]);
        $this->register_tween('fade-left', [
            'from' => ['opacity' => 0, 'x' => -40],
            'to' => ['opacity' => 1, 'x' => 0],
            'scrollTrigger' => ['trigger' => '.pr-reveal', 'start' => 'top 85%'],
        ]);
        $this->register_tween('fade-right', [
            'from' => ['opacity' => 0, 'x' => 40],
            'to' => ['opacity' => 1, 'x' => 0],
            'scrollTrigger' => ['trigger' => '.pr-reveal', 'start' => 'top 85%'],
        ]);
        $this->register_tween('zoom-in', [
            'from' => ['opacity' => 0, 'scale' => 0.8],
            'to' => ['opacity' => 1, 'scale' => 1],
            'scrollTrigger' => ['trigger' => '.pr-reveal', 'start' => 'top 85%'],
        ]);
        $this->register_tween('flip-up', [
            'from' => ['opacity' => 0, 'rotationX' => 90, 'y' => 40],
            'to' => ['opacity' => 1, 'rotationX' => 0, 'y' => 0],
            'scrollTrigger' => ['trigger' => '.pr-reveal', 'start' => 'top 80%'],
        ]);
        $this->register_tween('flip-down', [
            'from' => ['opacity' => 0, 'rotationX' => -90, 'y' => -40],
            'to' => ['opacity' => 1, 'rotationX' => 0, 'y' => 0],
            'scrollTrigger' => ['trigger' => '.pr-reveal', 'start' => 'top 80%'],
        ]);
        $this->register_tween('slide-down', [
            'from' => ['opacity' => 0, 'y' => -60],
            'to' => ['opacity' => 1, 'y' => 0],
            'scrollTrigger' => ['trigger' => '.pr-reveal', 'start' => 'top 85%'],
        ]);
    }

    public function register_tween(string $name, array $config): void {
        $this->registered_tweens[$name] = $config;
    }

    public function get_tween(string $name): ?array {
        return $this->registered_tweens[$name] ?? null;
    }

    public function get_all_tweens(): array {
        return $this->registered_tweens;
    }

    public function get_inline_init_script(string $active_reveal = 'fade-up', bool $parallax_enabled = false, string $page_transition = 'fade'): string {
        $script = 'window.PhantomAnimations = window.PhantomAnimations || {};';

        // Scroll reveal setup
        if ('none' !== $active_reveal && isset($this->registered_tweens[$active_reveal])) {
            $tween = $this->registered_tweens[$active_reveal];
            $script .= 'document.addEventListener("DOMContentLoaded", function() {';
            $script .= 'if (typeof gsap === "undefined") return;';
            $script .= 'gsap.registerPlugin(ScrollTrigger);';
            $script .= 'gsap.from(".pr-reveal", ' . wp_json_encode($tween['from']) . ', {';
            $script .= 'scrollTrigger: ' . wp_json_encode($tween['scrollTrigger'] ?? ['trigger' => '.pr-reveal', 'start' => 'top 85%']);
            $script .= ', duration: 0.6, ease: "power2.out"';
            $script .= '});';
            $script .= '});';
        }

        // Page transitions
        if ('none' !== $page_transition) {
            $exit = ['opacity' => 0];
            if ('slide-left' === $page_transition) $exit['x'] = -60;
            elseif ('slide-up' === $page_transition) $exit['y'] = -40;
            elseif ('zoom' === $page_transition) $exit['scale'] = 0.95;

            $script .= 'window.addEventListener("beforeunload", function() {';
            $script .= 'if (typeof gsap !== "undefined") {';
            $script .= 'gsap.to("#main-content", { ' . substr(wp_json_encode($exit), 1, -1) . ', duration: 0.3, ease: "power2.in" });';
            $script .= '}});';
        }

        // Parallax
        if ($parallax_enabled) {
            $script .= 'document.addEventListener("DOMContentLoaded", function() {';
            $script .= 'if (typeof gsap === "undefined") return;';
            $script .= 'gsap.registerPlugin(ScrollTrigger);';
            $script .= 'gsap.to(".parallax-section, .hero-section .hero-image", {';
            $script .= 'y: function() { return -(ScrollTrigger.maxScroll(window)) * 0.3; },';
            $script .= 'ease: "none", scrollTrigger: { trigger: "body", start: "top top", end: "bottom bottom", scrub: true }';
            $script .= '});';
            $script .= '});';
        }

        // Hover effects (delegated via CSS transitions by default, GSAP for advanced)
        $hover_type = get_option('phantom_animations_hover_type', 'lift');
        if ('glow' === $hover_type || 'shine' === $hover_type) {
            $script .= 'document.addEventListener("DOMContentLoaded", function() {';
            $script .= 'if (typeof gsap === "undefined") return;';
            $script .= 'document.querySelectorAll(".product-card, .category-card").forEach(function(card) {';
            $script .= 'card.addEventListener("mouseenter", function() {';
            if ('glow' === $hover_type) {
                $script .= 'gsap.to(card, {boxShadow: "0 0 30px rgba(193,18,31,0.15)", duration: 0.3, ease: "power2.out"});';
            } elseif ('shine' === $hover_type) {
                $script .= 'gsap.to(card, {backgroundImage: "linear-gradient(105deg, transparent 40%, rgba(255,255,255,0.3) 50%, transparent 60%)", duration: 0.3});';
            }
            $script .= '});';
            $script .= 'card.addEventListener("mouseleave", function() {';
            $script .= 'gsap.to(card, {boxShadow: "none", backgroundImage: "none", duration: 0.3, ease: "power2.out"});';
            $script .= '});';
            $script .= '});';
            $script .= '});';
        }

        // 3D Tilt
        $tilt_enabled = get_option('phantom_animations_tilt', '1');
        if ('1' === $tilt_enabled) {
            $script .= 'document.addEventListener("DOMContentLoaded", function() {';
            $script .= 'if (typeof gsap === "undefined") return;';
            $script .= 'var tiltCards = document.querySelectorAll("[data-tilt], .product-card, .category-card");';
            $script .= 'tiltCards.forEach(function(card) {';
            $script .= 'card.addEventListener("mousemove", function(e) {';
            $script .= 'var rect = card.getBoundingClientRect();';
            $script .= 'var x = (e.clientX - rect.left) / rect.width - 0.5;';
            $script .= 'var y = (e.clientY - rect.top) / rect.height - 0.5;';
            $script .= 'gsap.to(card, {rotationY: x * 10, rotationX: -y * 10, transformPerspective: 1000, duration: 0.3, ease: "power2.out"});';
            $script .= '});';
            $script .= 'card.addEventListener("mouseleave", function() {';
            $script .= 'gsap.to(card, {rotationY: 0, rotationX: 0, duration: 0.3, ease: "power2.out"});';
            $script .= '});';
            $script .= '});';
            $script .= '});';
        }

        return $script;
    }

    public function get_inline_styles(): string {
        return '.pr-reveal{opacity:0}.pr-reveal.pr-visible{opacity:1}[data-tilt]{will-change:transform;transition:transform 0.3s ease}';
    }
}
