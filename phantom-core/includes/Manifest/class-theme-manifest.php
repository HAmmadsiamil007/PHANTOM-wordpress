<?php
declare(strict_types=1);

namespace PhantomCore\Manifest;

defined('ABSPATH') || exit;

/**
 * Theme Manifest — a versioned, self-describing metadata object for the entire framework.
 *
 * ChatGPT P7: Every major subsystem (demo, preset, component, animation) should
 * have a manifest that makes it discoverable. The Theme Manifest is the root
 * manifest that ties all subsystems together.
 *
 * Location: Each demo pack has its own manifest.json, but this PHP class
 * provides the runtime API for reading and validating manifests.
 */
class Theme_Manifest {
    public readonly string $name;
    public readonly string $slug;
    public readonly string $version;
    public readonly string $description;
    public readonly string $author;
    public readonly string $framework_version;
    public readonly array $requires;         // ['php' => '7.4', 'wordpress' => '6.4', 'woocommerce' => '9.0']
    public readonly array $features;         // Feature flags this manifest enables
    public readonly array $templates;        // Template slugs this manifest provides
    public readonly array $components;       // Component names this manifest uses
    public readonly array $presets;          // Preset IDs this manifest defines
    public readonly array $animations;       // Animation IDs this manifest provides
    public readonly array $assets;           // ['css' => [...], 'js' => [...]]
    public readonly array $settings;         // Setting keys this manifest overrides

    public function __construct(
        string $name,
        string $slug = '',
        string $version = '1.0.0',
        string $description = '',
        string $author = 'Phantom Core',
        string $framework_version = PHANTOM_CORE_VERSION,
        array $requires = [],
        array $features = [],
        array $templates = [],
        array $components = [],
        array $presets = [],
        array $animations = [],
        array $assets = [],
        array $settings = []
    ) {
        $this->name = $name;
        $this->slug = $slug ?: sanitize_title($name);
        $this->version = $version;
        $this->description = $description;
        $this->author = $author;
        $this->framework_version = $framework_version;
        $this->requires = $requires;
        $this->features = $features;
        $this->templates = $templates;
        $this->components = $components;
        $this->presets = $presets;
        $this->animations = $animations;
        $this->assets = $assets;
        $this->settings = $settings;
    }

    /**
     * Validate that this manifest's requirements are met.
     *
     * @return array<string, bool|string> Requirement key => true if met, or error message string.
     */
    public function validate_requirements(): array {
        $results = [];

        // PHP version
        $required_php = $this->requires['php'] ?? '';
        if ($required_php && version_compare(PHP_VERSION, $required_php, '<')) {
            $results['php'] = "Requires PHP {$required_php}, running " . PHP_VERSION;
        } else {
            $results['php'] = true;
        }

        // WordPress version
        $required_wp = $this->requires['wordpress'] ?? '';
        global $wp_version;
        if ($required_wp && isset($wp_version) && version_compare($wp_version, $required_wp, '<')) {
            $results['wordpress'] = "Requires WordPress {$required_wp}, running {$wp_version}";
        } else {
            $results['wordpress'] = true;
        }

        // WooCommerce
        $required_wc = $this->requires['woocommerce'] ?? '';
        if ($required_wc) {
            if (!class_exists('WooCommerce')) {
                $results['woocommerce'] = 'WooCommerce not active';
            } else {
                $wc_version = WC()->version ?? '';
                if ($wc_version && version_compare($wc_version, $required_wc, '<')) {
                    $results['woocommerce'] = "Requires WooCommerce {$required_wc}, running {$wc_version}";
                } else {
                    $results['woocommerce'] = true;
                }
            }
        }

        // Phantom Core version
        $required_pc = $this->requires['phantom_core'] ?? '';
        if ($required_pc && defined('PHANTOM_CORE_VERSION') && version_compare(PHANTOM_CORE_VERSION, $required_pc, '<')) {
            $results['phantom_core'] = "Requires Phantom Core {$required_pc}, running " . PHANTOM_CORE_VERSION;
        } else {
            $results['phantom_core'] = true;
        }

        return $results;
    }

    /**
     * Check if all requirements are met.
     */
    public function is_compatible(): bool {
        $results = $this->validate_requirements();
        foreach ($results as $key => $value) {
            if (true !== $value) {
                return false;
            }
        }
        return true;
    }

    /**
     * Create from a manifest.json array.
     */
    public static function from_json_file(string $file_path): ?self {
        if (!file_exists($file_path)) {
            return null;
        }
        $data = json_decode(file_get_contents($file_path), true);
        if (!is_array($data)) {
            return null;
        }
        return new self(
            name: $data['name'] ?? 'Unnamed',
            slug: $data['slug'] ?? '',
            version: $data['version'] ?? '1.0.0',
            description: $data['description'] ?? '',
            author: $data['author'] ?? 'Phantom Core',
            framework_version: $data['framework_version'] ?? PHANTOM_CORE_VERSION,
            requires: $data['requires'] ?? [],
            features: $data['features'] ?? [],
            templates: $data['templates'] ?? [],
            components: $data['components'] ?? [],
            presets: $data['presets'] ?? [],
            animations: $data['animations'] ?? [],
            assets: $data['assets'] ?? [],
            settings: $data['settings'] ?? []
        );
    }

    /**
     * Create from demo.json format (compatibility method).
     */
    public static function from_demo_json(string $file_path): ?self {
        if (!file_exists($file_path)) {
            return null;
        }
        $data = json_decode(file_get_contents($file_path), true);
        if (!is_array($data)) {
            return null;
        }
        return new self(
            name: $data['name'] ?? 'Unnamed',
            slug: $data['slug'] ?? '',
            version: $data['version'] ?? '1.0.0',
            description: $data['description'] ?? '',
            author: $data['author'] ?? 'Phantom Core',
            requires: $data['requires'] ?? [],
            features: $data['features'] ?? [],
            templates: $data['templates'] ?? [],
            settings: $data['settings'] ?? []
        );
    }

    public function to_array(): array {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'version' => $this->version,
            'description' => $this->description,
            'author' => $this->author,
            'framework_version' => $this->framework_version,
            'requires' => $this->requires,
            'features' => $this->features,
            'templates' => $this->templates,
            'components' => $this->components,
            'presets' => $this->presets,
            'animations' => $this->animations,
            'assets' => $this->assets,
            'settings' => $this->settings,
            'is_compatible' => $this->is_compatible(),
        ];
    }
}
