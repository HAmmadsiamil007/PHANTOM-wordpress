<?php
declare(strict_types=1);

namespace PhantomCore\Design;

defined('ABSPATH') || exit;

/**
 * Property_Registry — the unit of editing for the Visual Tool Engine.
 *
 * Properties are generic ("background-color", "font-size") and belong to a
 * tool ("colors", "typography", "spacing", ...). Tools know nothing about
 * components; components map generic properties to storage keys via
 * Component_Metadata parts. This keeps the editor pack-agnostic: any future
 * frontend pack can describe new components with the same properties.
 *
 * @package PhantomCore\Design
 */
class Property_Registry {

    public const TOOLS = [
        'colors'      => ['label' => 'Colors',      'icon' => 'art',             'implemented' => true],
        'typography'  => ['label' => 'Typography',  'icon' => 'editor-textcolor', 'implemented' => true],
        'spacing'     => ['label' => 'Spacing',     'icon' => 'editor-expand',    'implemented' => true],
        'assets'      => ['label' => 'Assets',      'icon' => 'format-image',     'implemented' => true],
        'animation'   => ['label' => 'Animation',   'icon' => 'visibility',       'implemented' => true],
        'responsive'  => ['label' => 'Responsive',  'icon' => 'desktop',          'implemented' => true],
        'content'     => ['label' => 'Content',     'icon' => 'edit',             'implemented' => true],
    ];

    private static ?self $instance = null;

    /** @var array<string, array> */
    private array $properties = [];

    private bool $defaultsRegistered = false;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function register(string $key, array $definition): void {
        $definition['key'] = $key;
        $this->properties[$key] = $definition;
    }

    /**
     * Get a property definition (null when unknown).
     */
    public function get(string $key): ?array {
        $this->ensure_defaults();
        return $this->properties[$key] ?? null;
    }

    public function has(string $key): bool {
        $this->ensure_defaults();
        return isset($this->properties[$key]);
    }

    /**
     * All registered properties keyed by property key.
     *
     * @return array<string, array>
     */
    public function get_all(): array {
        $this->ensure_defaults();
        return $this->properties;
    }

    /**
     * Properties belonging to a tool.
     *
     * @return array<string, array>
     */
    public function get_for_tool(string $tool): array {
        $this->ensure_defaults();
        $out = [];
        foreach ($this->properties as $key => $def) {
            if (($def['tool'] ?? '') === $tool) {
                $out[$key] = $def;
            }
        }
        return $out;
    }

    /**
     * All registered tools with defaults applied.
     *
     * @return array<string, array{label: string, icon: string, implemented: bool}>
     */
    public function get_tools(): array {
        return self::TOOLS;
    }

    public function tool_exists(string $tool): bool {
        return isset(self::TOOLS[$tool]);
    }

    private function ensure_defaults(): void {
        if ($this->defaultsRegistered) {
            return;
        }
        $this->defaultsRegistered = true;
        $this->register_defaults();
    }

    /**
     * Register the built-in generic properties. Per-component storage keys
     * are NOT defined here — parts data maps these to settings keys.
     */
    public function register_defaults(): void {
        $this->register('background-color', [
            'tool'    => 'colors',
            'label'   => __('Background', 'phantom-core'),
            'type'    => 'color',
            'default' => '#1a1a2e',
            'types'   => ['solid', 'gradient'],
        ]);
        $this->register('text-color', [
            'tool'    => 'colors',
            'label'   => __('Text', 'phantom-core'),
            'type'    => 'color',
            'default' => '#ffffff',
        ]);
        $this->register('border-color', [
            'tool'    => 'colors',
            'label'   => __('Border', 'phantom-core'),
            'type'    => 'color',
            'default' => '#e94560',
        ]);
        $this->register('link-color', [
            'tool'    => 'colors',
            'label'   => __('Link', 'phantom-core'),
            'type'    => 'color',
            'default' => '#1a1a2e',
        ]);
        $this->register('link-hover-color', [
            'tool'    => 'colors',
            'label'   => __('Link Hover', 'phantom-core'),
            'type'    => 'color',
            'default' => '#e94560',
        ]);
        $this->register('icon-color', [
            'tool'    => 'colors',
            'label'   => __('Icon', 'phantom-core'),
            'type'    => 'color',
            'default' => '#1a1a2e',
        ]);
        $this->register('overlay-color', [
            'tool'    => 'colors',
            'label'   => __('Overlay', 'phantom-core'),
            'type'    => 'color',
            'default' => 'rgba(0,0,0,0.4)',
        ]);
        $this->register('overlay-opacity', [
            'tool'    => 'colors',
            'label'   => __('Overlay Opacity', 'phantom-core'),
            'type'    => 'slider',
            'default' => '0.4',
            'min'     => 0,
            'max'     => 1,
            'step'    => 0.05,
        ]);

        // ── Typography ────────────────────────────────────────
        $this->register('font-family', [
            'tool'    => 'typography',
            'label'   => __('Font', 'phantom-core'),
            'type'    => 'select',
            'default' => 'Inter',
            'options' => ['Inter', 'Playfair Display', 'Space Grotesk', 'DM Sans', 'Montserrat'],
        ]);
        $this->register('font-size', [
            'tool'       => 'typography',
            'label'      => __('Size', 'phantom-core'),
            'type'       => 'slider',
            'default'    => '16',
            'min'        => 8,
            'max'        => 96,
            'step'       => 1,
            'unit'       => 'px',
            'responsive' => true,
        ]);
        $this->register('font-weight', [
            'tool'    => 'typography',
            'label'   => __('Weight', 'phantom-core'),
            'type'    => 'select',
            'default' => '400',
            'options' => ['300', '400', '500', '600', '700', '800', '900'],
        ]);
        $this->register('line-height', [
            'tool'    => 'typography',
            'label'   => __('Line Height', 'phantom-core'),
            'type'    => 'slider',
            'default' => '1.5',
            'min'     => 1,
            'max'     => 2.5,
            'step'    => 0.05,
        ]);
        $this->register('letter-spacing', [
            'tool'       => 'typography',
            'label'      => __('Letter Spacing', 'phantom-core'),
            'type'       => 'slider',
            'default'    => '0',
            'min'        => 0,
            'max'        => 10,
            'step'       => 0.5,
            'unit'       => 'px',
            'responsive' => true,
        ]);
        $this->register('text-align', [
            'tool'    => 'typography',
            'label'   => __('Align', 'phantom-core'),
            'type'    => 'select',
            'default' => 'left',
            'options' => ['left', 'center', 'right'],
        ]);
        $this->register('text-transform', [
            'tool'    => 'typography',
            'label'   => __('Transform', 'phantom-core'),
            'type'    => 'select',
            'default' => 'none',
            'options' => ['none', 'uppercase', 'lowercase', 'capitalize'],
        ]);

        // ── Spacing ───────────────────────────────────────────
        $this->register('padding-x', [
            'tool'       => 'spacing',
            'label'      => __('Horizontal Padding', 'phantom-core'),
            'type'       => 'slider',
            'default'    => '24',
            'min'        => 0,
            'max'        => 200,
            'step'       => 2,
            'unit'       => 'px',
            'responsive' => true,
        ]);
        $this->register('padding-y', [
            'tool'       => 'spacing',
            'label'      => __('Vertical Padding', 'phantom-core'),
            'type'       => 'slider',
            'default'    => '60',
            'min'        => 0,
            'max'        => 300,
            'step'       => 4,
            'unit'       => 'px',
            'responsive' => true,
        ]);
        $this->register('margin-x', [
            'tool'       => 'spacing',
            'label'      => __('Horizontal Margin', 'phantom-core'),
            'type'       => 'slider',
            'default'    => '0',
            'min'        => 0,
            'max'        => 200,
            'step'       => 2,
            'unit'       => 'px',
            'responsive' => true,
        ]);
        $this->register('margin-y', [
            'tool'       => 'spacing',
            'label'      => __('Vertical Margin', 'phantom-core'),
            'type'       => 'slider',
            'default'    => '0',
            'min'        => 0,
            'max'        => 200,
            'step'       => 2,
            'unit'       => 'px',
            'responsive' => true,
        ]);
        $this->register('gap', [
            'tool'       => 'spacing',
            'label'      => __('Gap', 'phantom-core'),
            'type'       => 'slider',
            'default'    => '24',
            'min'        => 0,
            'max'        => 100,
            'step'       => 2,
            'unit'       => 'px',
            'responsive' => true,
        ]);
        $this->register('border-radius', [
            'tool'    => 'spacing',
            'label'   => __('Border Radius', 'phantom-core'),
            'type'    => 'slider',
            'default' => '0',
            'min'     => 0,
            'max'     => 50,
            'step'    => 2,
            'unit'    => 'px',
        ]);

        // ── Assets ────────────────────────────────────────────
        $this->register('background-image', [
            'tool'    => 'assets',
            'label'   => __('Background Image', 'phantom-core'),
            'type'    => 'image',
            'default' => '',
        ]);
        $this->register('logo-image', [
            'tool'    => 'assets',
            'label'   => __('Logo Image', 'phantom-core'),
            'type'    => 'image',
            'default' => '',
        ]);

        // ── Animation ─────────────────────────────────────────
        $this->register('animation-type', [
            'tool'    => 'animation',
            'label'   => __('Animation', 'phantom-core'),
            'type'    => 'select',
            'default' => 'fade',
            'options' => ['none', 'fade', 'slide-up', 'slide-left', 'zoom', 'parallax'],
        ]);
        $this->register('animation-delay', [
            'tool'    => 'animation',
            'label'   => __('Delay', 'phantom-core'),
            'type'    => 'slider',
            'default' => '0',
            'min'     => 0,
            'max'     => 2000,
            'step'    => 100,
            'unit'    => 'ms',
        ]);
        $this->register('animation-duration', [
            'tool'    => 'animation',
            'label'   => __('Duration', 'phantom-core'),
            'type'    => 'slider',
            'default' => '600',
            'min'     => 100,
            'max'     => 3000,
            'step'    => 100,
            'unit'    => 'ms',
        ]);

        // ── Content ───────────────────────────────────────────
        $this->register('text-content', [
            'tool'    => 'content',
            'label'   => __('Text', 'phantom-core'),
            'type'    => 'textarea',
            'default' => '',
        ]);
        $this->register('subtitle-text', [
            'tool'    => 'content',
            'label'   => __('Subtitle', 'phantom-core'),
            'type'    => 'textarea',
            'default' => '',
        ]);
        $this->register('button-text', [
            'tool'    => 'content',
            'label'   => __('Button Label', 'phantom-core'),
            'type'    => 'text',
            'default' => '',
        ]);
        $this->register('button-secondary-text', [
            'tool'    => 'content',
            'label'   => __('Secondary Button Label', 'phantom-core'),
            'type'    => 'text',
            'default' => '',
        ]);
        $this->register('link-url', [
            'tool'    => 'content',
            'label'   => __('Link URL', 'phantom-core'),
            'type'    => 'text',
            'default' => '',
        ]);

        // ── Responsive ────────────────────────────────────────
        $this->register('hide-desktop', [
            'tool'    => 'responsive',
            'label'   => __('Hide on Desktop', 'phantom-core'),
            'type'    => 'toggle',
            'default' => '0',
        ]);
        $this->register('hide-tablet', [
            'tool'    => 'responsive',
            'label'   => __('Hide on Tablet', 'phantom-core'),
            'type'    => 'toggle',
            'default' => '0',
        ]);
        $this->register('hide-mobile', [
            'tool'    => 'responsive',
            'label'   => __('Hide on Mobile', 'phantom-core'),
            'type'    => 'toggle',
            'default' => '0',
        ]);

        /**
         * Filters the registered properties. Packs can add custom
         * properties or override defaults.
         *
         * @param array $properties Property definitions.
         */
        $this->properties = (array) apply_filters('phantom_property_registry', $this->properties);
    }
}
