<?php
declare(strict_types=1);

namespace PhantomCore\Design;

defined('ABSPATH') || exit;

/**
 * Component_Definition — single source of truth for every editable component in the Design Studio.
 *
 * Tells the Design Studio what settings a component has, what tabs to show
 * in the Inspector, how to render it, and how to resolve its data.
 *
 * @package PhantomCore\Design
 */
class Component_Definition {

    // ── Identity ────────────────────────────────────
    public string $id;          // 'hero'
    public string $name;        // 'Hero Banner'
    public string $category;    // 'sections' | 'content' | 'layout' | 'shop' | 'account'

    // ── Rendering ───────────────────────────────────
    public string $renderer;    // 'Hero_Renderer'
    public string $adapter;     // 'Hero_Adapter'
    public string $view_model;  // 'Hero_ViewModel'
    public string $template;    // 'frontend/html/hero.html'

    // ── Design System Binding ────────────────────────
    public string $token_group;     // 'hero'
    public string $settings_group;  // 'hero'

    // ── Inspector UI ────────────────────────────────
    /** @var array<int, array{key: string, label: string, fields: array}> */
    public array $tabs = [];

    // ── Capabilities ────────────────────────────────
    /** @var string[] */
    public array $capabilities = ['edit', 'reset', 'responsive', 'animate'];

    // ── Variants ────────────────────────────────────
    /** @var string[] */
    public array $variants = ['default'];

    // ── Lifecycle Hooks ─────────────────────────────
    /** @var array<string, string> */
    public array $hooks = [];

    // ── Dependencies ────────────────────────────────
    /** @var string[] */
    public array $dependencies = [];

    // ── Assets ──────────────────────────────────────
    /** @var string[] */
    public array $assets = [];

    // ── SEO ─────────────────────────────────────────
    /** @var array<string, mixed> */
    public array $seo = [];

    // ── Accessibility ──────────────────────────────
    public string $aria_role = '';

    // ── Description ────────────────────────────────
    public string $description = '';

    /**
     * @param array $data {
     *     Optional array of properties to set.
     *
     *     @type string $id            Component identifier.
     *     @type string $name          Display name.
     *     @type string $category      Component category.
     *     @type string $renderer      Renderer class name.
     *     @type string $adapter       Adapter class name.
     *     @type string $view_model    ViewModel class name.
     *     @type string $template      HTML template path.
     *     @type string $token_group   Token group for CSS vars.
     *     @type string $settings_group Settings group key.
     *     @type array  $tabs          Inspector tabs with fields.
     *     @type array  $capabilities  Allowed actions.
     *     @type array  $variants      Component variants.
     *     @type array  $hooks         Lifecycle hooks.
     *     @type array  $dependencies  Required dependencies.
     *     @type array  $assets        Asset URLs.
     *     @type array  $seo           SEO metadata.
     *     @type string $aria_role     ARIA landmark role.
     *     @type string $description   Short description.
     * }
     */
    public function __construct(array $data = []) {
        $this->id            = $data['id'] ?? '';
        $this->name          = $data['name'] ?? '';
        $this->category      = $data['category'] ?? 'general';
        $this->renderer      = $data['renderer'] ?? '';
        $this->adapter       = $data['adapter'] ?? '';
        $this->view_model    = $data['view_model'] ?? '';
        $this->template      = $data['template'] ?? '';
        $this->token_group   = $data['token_group'] ?? $this->id;
        $this->settings_group = $data['settings_group'] ?? $this->id;
        $this->tabs          = $data['tabs'] ?? [];
        $this->capabilities  = $data['capabilities'] ?? ['edit', 'reset', 'responsive', 'animate'];
        $this->variants      = $data['variants'] ?? ['default'];
        $this->hooks         = $data['hooks'] ?? [];
        $this->dependencies  = $data['dependencies'] ?? [];
        $this->assets        = $data['assets'] ?? [];
        $this->seo           = $data['seo'] ?? [];
        $this->aria_role     = $data['aria_role'] ?? '';
        $this->description   = $data['description'] ?? '';
    }

    // ── Helper Methods ───────────────────────────────

    /**
     * Check if a capability is supported.
     */
    public function supports(string $capability): bool {
        return in_array($capability, $this->capabilities, true);
    }

    /**
     * Check if a variant exists.
     */
    public function has_variant(string $variant): bool {
        return in_array($variant, $this->variants, true);
    }

    /**
     * Get the default value for a field by its key.
     */
    public function get_field_default(string $fieldKey): mixed {
        foreach ($this->tabs as $tab) {
            foreach ($tab['fields'] as $field) {
                if (($field['key'] ?? '') === $fieldKey) {
                    return $field['default'] ?? null;
                }
            }
        }
        return null;
    }

    /**
     * Get all field keys defined across all tabs.
     */
    public function get_all_field_keys(): array {
        $keys = [];
        foreach ($this->tabs as $tab) {
            foreach ($tab['fields'] as $field) {
                $keys[] = $field['key'] ?? '';
            }
        }
        return array_values(array_filter($keys));
    }

    /**
     * Get the settings group with its settings keys.
     */
    public function get_settings_keys(): array {
        return $this->get_all_field_keys();
    }

    /**
     * Export to array for REST API.
     */
    public function to_array(): array {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'category'       => $this->category,
            'description'    => $this->description,
            'renderer'       => $this->renderer,
            'adapter'        => $this->adapter,
            'view_model'     => $this->view_model,
            'template'       => $this->template,
            'tokenGroup'     => $this->token_group,
            'settingsGroup'  => $this->settings_group,
            'tabs'           => $this->tabs,
            'capabilities'   => $this->capabilities,
            'variants'       => $this->variants,
            'hooks'          => $this->hooks,
            'dependencies'   => $this->dependencies,
            'assets'         => $this->assets,
            'seo'            => $this->seo,
            'ariaRole'       => $this->aria_role,
        ];
    }
}
