<?php
declare(strict_types=1);

namespace PhantomCore\Animation;

use PhantomCore\Bridges\Plugin_Bridge;
use PhantomCore\Registry\Asset_Registry;

defined('ABSPATH') || exit;

class Three_Bridge extends Plugin_Bridge {
    private static ?self $instance = null;
    private bool $enqueued = false;
    private array $scenes = [];

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->id = 'three-js';
        $this->label = 'Three.js 3D Scenes';
    }

    public function is_active(): bool {
        $registry = Asset_Registry::get_instance();
        return $registry->has('three-js') && $registry->has('phantom-three-scenes');
    }

    public function get_supported_hooks(): array {
        return ['wp_enqueue_scripts'];
    }

    public function init(): void {
        if (!$this->is_active()) {
            return;
        }
        add_action('wp_enqueue_scripts', [$this, 'enqueue'], 25);
        add_action('wp_head', [$this, 'register_inline_script'], 50);
    }

    public function enqueue(): void {
        if ($this->enqueued) {
            return;
        }
        $this->enqueued = true;

        $three_enabled = get_option('phantom_animations_three', '0');
        if ('1' !== $three_enabled) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[Three_Bridge] Three.js disabled via phantom_animations_three option');
            }
            return;
        }

        wp_enqueue_script('three-core');
        wp_enqueue_script('phantom-three-scenes');

        $scene_data = $this->get_scene_config();
        if (!empty($scene_data)) {
            wp_add_inline_script('phantom-three-scenes', 'window.PhantomThreeData=' . wp_json_encode($scene_data) . ';', 'before');
        }
    }

    public function get_scene_config(): array {
        if (empty($this->scenes)) {
            $this->register_scenes();
        }

        $config = [];
        foreach ($this->scenes as $name => $scene) {
            if (!empty($scene['enabled'])) {
                $config[$name] = $scene['config'];
            }
        }
        return $config;
    }

    public function register_inline_script(): void {
        if (!$this->is_active()) {
            return;
        }
        $scene_data = $this->get_scene_config();
        if (empty($scene_data)) {
            return;
        }
        printf(
            '<script>window.PhantomThreeData=%s;</script>',
            wp_json_encode($scene_data)
        );
    }

    private function register_scenes(): void {
        $this->scenes = [
            'fog-particles' => [
                'label' => 'Fog Particles',
                'description' => 'Ambient fog particle system with mouse-follow rotation.',
                'enabled' => true,
                'config' => [
                    'particleCount' => 1500,
                    'color' => '#c1121f',
                    'size' => 0.02,
                    'opacity' => 0.6,
                    'rotationSpeed' => 0.0003,
                    'mouseInfluence' => 0.0005,
                ],
            ],
            'floating-geo' => [
                'label' => 'Floating Geometry',
                'description' => 'Icosahedrons and torus knots floating and rotating in space.',
                'enabled' => true,
                'config' => [
                    'count' => 12,
                    'sizeRange' => [0.3, 1.2],
                    'rotationSpeed' => 0.005,
                    'floatSpeed' => 0.002,
                    'floatAmplitude' => 0.5,
                    'colors' => ['#c1121f', '#ff6b35', '#4ecdc4', '#ffffff'],
                ],
            ],
            'star-field' => [
                'label' => 'Star Field',
                'description' => 'Star field with smooth color transitions and twinkling.',
                'enabled' => true,
                'config' => [
                    'starCount' => 3000,
                    'size' => 0.015,
                    'spread' => 50,
                    'colorCycleSpeed' => 0.001,
                    'twinkleSpeed' => 0.005,
                    'twinkleIntensity' => 0.3,
                ],
            ],
        ];

        $custom_scenes = get_option('phantom_animations_three_scenes', []);
        if (is_array($custom_scenes) && !empty($custom_scenes)) {
            foreach ($custom_scenes as $key => $config) {
                if (isset($this->scenes[$key])) {
                    $this->scenes[$key]['config'] = array_merge($this->scenes[$key]['config'], $config);
                }
            }
        }
    }
}