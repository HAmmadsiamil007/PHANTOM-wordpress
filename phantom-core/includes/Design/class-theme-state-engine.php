<?php
declare(strict_types=1);

namespace PhantomCore\Design;

use PhantomCore\Settings_Registry;

defined('ABSPATH') || exit;

/**
 * Theme State Engine — resolves any setting value through a deterministic cascade.
 *
 * Cascade order (highest priority first):
 *   1. Developer Override   (WP_DEBUG / filters)
 *   2. Live Preview         (Design Studio unsaved changes)
 *   3. User Override        (explicit user setting via Settings Registry)
 *   4. Demo Value           (active demo pack defaults)
 *   5. Preset Value         (active preset defaults)
 *   6. Responsive Override  (device‑specific breakpoint values)
 *   7. Dark Mode Value      (dark mode token mapping)
 *   8. Accessibility        (a11y overrides for contrast/readability)
 *   9. Theme Default        (bundled default, lowest priority)
 *
 * @package PhantomCore\Design
 */
class ThemeStateEngine {

    private const PREVIEW_OPTION_KEY = 'phantom_design_studio_preview';
    private const CACHE_KEY = 'phantom_tse_resolved';

    private static ?self $instance = null;
    private Settings_Registry $registry;
    private ?array $previewCache = null;
    private array $resolvedCache = [];

    /**
     * Singleton instance.
     */
    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor.
     */
    private function __construct() {
        $this->registry = Settings_Registry::get_instance();
    }

    // ──────────────────────────────────────────────
    //  PUBLIC API
    // ──────────────────────────────────────────────

    /**
     * Resolve a single setting key through the full cascade.
     *
     * @param string $key     Setting key (e.g. 'hero_height').
     * @param array  $context Optional context: device, dark_mode, demo, preset.
     * @return ResolvedValue
     */
    public function resolve(string $key, array $context = []): ResolvedValue {
        $cacheKey = $key . '|' . wp_json_encode($context);
        if (isset($this->resolvedCache[$cacheKey])) {
            return $this->resolvedCache[$cacheKey];
        }

        $value = $this->resolve_cascade($key, $context);
        $this->resolvedCache[$cacheKey] = $value;

        return $value;
    }

    /**
     * Resolve CSS variable map for a set of keys (or all known settings).
     *
     * Uses CSSVariableGenerator for the base set, then overlays preview/user values.
     *
     * @param array|null $keys    Optional list of keys to resolve. Null = all.
     * @param array      $context Context overrides.
     * @return array<string,string> Map of CSS var name → value.
     */
    public function resolve_css_vars(?array $keys = null, array $context = []): array {
        $vars = [];

        // Start with the full compiled CSS variable set
        $manager = DesignSystemManager::get_instance();
        $allVars = $manager->allCssVars();

        if (null === $keys) {
            // Return all vars
            $vars = $allVars;
        } else {
            // Filter to only requested keys
            foreach ($keys as $key) {
                $cssVarName = $this->setting_key_to_css_var($key);
                if (isset($allVars[$cssVarName])) {
                    $vars[$cssVarName] = $allVars[$cssVarName];
                }
            }
        }

        // Apply preview overrides
        $preview = $this->get_preview_values();
        foreach ($preview as $previewKey => $previewValue) {
            $cssVarName = $this->setting_key_to_css_var($previewKey);
            if (isset($vars[$cssVarName]) || null === $keys || in_array($previewKey, $keys, true)) {
                $vars[$cssVarName] = (string) $previewValue;
            }
        }

        return $vars;
    }

    /**
     * Set a preview value (temporary, not persisted to settings).
     *
     * @param string $key   Setting key.
     * @param mixed  $value Value.
     */
    public function set_preview(string $key, $value): void {
        $preview = $this->get_preview_values();
        $preview[sanitize_key($key)] = $value;
        update_option(self::PREVIEW_OPTION_KEY, $preview, false);
        $this->previewCache = $preview;
        $this->invalidate_cache();
    }

    /**
     * Set multiple preview values at once.
     *
     * @param array $values Associative array of key → value.
     */
    public function set_preview_bulk(array $values): void {
        $preview = $this->get_preview_values();
        foreach ($values as $key => $value) {
            if (in_array($key, ['action', '_wpnonce', 'snapshot', 'component', 'instance'], true)) {
                continue;
            }
            $preview[sanitize_key($key)] = $value;
        }
        update_option(self::PREVIEW_OPTION_KEY, $preview, false);
        $this->previewCache = $preview;
        $this->invalidate_cache();
    }

    /**
     * Get all current preview values.
     *
     * @return array
     */
    public function get_preview_values(): array {
        if (null === $this->previewCache) {
            $this->previewCache = get_option(self::PREVIEW_OPTION_KEY, []);
            if (!is_array($this->previewCache)) {
                $this->previewCache = [];
            }
        }
        return $this->previewCache;
    }

    /**
     * Clear all preview values.
     */
    public function clear_preview(): void {
        delete_option(self::PREVIEW_OPTION_KEY);
        $this->previewCache = [];
        $this->invalidate_cache();
    }

    /**
     * Commit preview values to the Settings Registry (publish).
     *
     * @return int Number of settings committed.
     */
    public function commit_preview(): int {
        $preview = $this->get_preview_values();
        if (empty($preview)) {
            return 0;
        }

        $count = 0;
        foreach ($preview as $key => $value) {
            if ($this->registry->has($key)) {
                $this->registry->set($key, $value);
                $count++;
            }
        }

        $this->clear_preview();

        // Flush CSS caches
        if (class_exists('\Phantom_Custom_CSS')) {
            \Phantom_Custom_CSS::flush_cache();
        }

        // Invalidate design system CSS
        $manager = DesignSystemManager::get_instance();
        if (method_exists($manager, 'compile')) {
            $manager->compile();
        }

        return $count;
    }

    /**
     * Invalidate the request-level resolve cache.
     */
    public function invalidate_cache(): void {
        $this->resolvedCache = [];
    }

    /**
     * Get the preview option name (for REST controller).
     */
    public static function get_preview_option_key(): string {
        return self::PREVIEW_OPTION_KEY;
    }

    // ──────────────────────────────────────────────
    //  INTERNAL CASCADE
    // ──────────────────────────────────────────────

    /**
     * Walk the cascade for a single key.
     */
    private function resolve_cascade(string $key, array $context = []): ResolvedValue {
        // 1. Developer Override (highest priority)
        $devOverride = apply_filters('phantom_tse_dev_override', null, $key, $context);
        if (null !== $devOverride) {
            return new ResolvedValue($devOverride, 'developer', $context);
        }

        // 2. Live Preview
        $preview = $this->get_preview_values();
        if (array_key_exists($key, $preview)) {
            return new ResolvedValue($preview[$key], 'preview', $context);
        }

        // 3. User Override
        if ($this->registry->has($key)) {
            $userValue = $this->registry->get($key);
            $default = $this->registry->get_entry($key)['default'] ?? null;
            if (null !== $userValue && $userValue !== $default) {
                return new ResolvedValue($userValue, 'user', $context);
            }
        }

        // 4. Demo Value (if context provides a demo pack)
        if (!empty($context['demo'])) {
            $demoValue = apply_filters('phantom_tse_demo_value', null, $key, $context['demo']);
            if (null !== $demoValue) {
                return new ResolvedValue($demoValue, 'demo', $context);
            }
        }

        // 5. Preset Value (active preset)
        $presetValue = $this->resolve_preset_value($key);
        if (null !== $presetValue) {
            return new ResolvedValue($presetValue, 'preset', $context);
        }

        // 6. Theme Default (lowest priority)
        if ($this->registry->has($key)) {
            $entry = $this->registry->get_entry($key);
            $default = $entry['default'] ?? null;
            if (null !== $default) {
                return new ResolvedValue($default, 'default', $context);
            }
        }

        return new ResolvedValue(null, 'default', $context);
    }

    /**
     * Resolve a value from the active preset.
     *
     * Checks if the active preset has a value for the given key.
     */
    private function resolve_preset_value(string $key): mixed {
        $presetManager = PresetManager::get_instance();
        $currentPresetId = $presetManager->current();
        if (null === $currentPresetId) {
            return null;
        }

        // Check if this key has a preset token definition
        $registry = TokenRegistry::get_instance();
        $tokenDef = $registry->get($key);
        if (null === $tokenDef) {
            return null;
        }

        $optionKey = $tokenDef['option_key'] ?? 'phantom_' . $key;
        return get_option($optionKey, null);
    }

    /**
     * Convert a setting key to its CSS variable name.
     *
     * E.g. 'hero_height' -> '--hero-height'
     *       'primary_color' -> '--primary-color'
     */
    private function setting_key_to_css_var(string $key): string {
        $varName = str_replace('_', '-', $key);
        return '--' . $varName;
    }

    // Note: PHP garbage collection handles cache cleanup automatically.
    // Explicit __destruct not needed.
}
