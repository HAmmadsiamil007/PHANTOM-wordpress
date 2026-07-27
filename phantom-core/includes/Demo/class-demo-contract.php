<?php
declare(strict_types=1);

namespace PhantomCore\Demo;

defined('ABSPATH') || exit;

class Demo_Contract {
    public readonly string $name;
    public readonly string $slug;
    public readonly string $version;
    public readonly string $description;
    public readonly string $author;
    public readonly array $requires;
    public readonly array $templates;
    public readonly array $tags;
    public readonly bool $has_screenshot;
    public readonly bool $is_compatible;
    public readonly array $errors;
    public readonly string $preset;

    public function __construct(
        string $name,
        string $slug,
        string $version,
        string $description = '',
        string $author = '',
        array $requires = [],
        array $templates = [],
        array $tags = [],
        bool $has_screenshot = false,
        bool $is_compatible = true,
        array $errors = [],
        string $preset = ''
    ) {
        $this->name = $name;
        $this->slug = $slug;
        $this->version = $version;
        $this->description = $description;
        $this->author = $author;
        $this->requires = $requires;
        $this->templates = $templates;
        $this->tags = $tags;
        $this->has_screenshot = $has_screenshot;
        $this->is_compatible = $is_compatible;
        $this->errors = $errors;
        $this->preset = $preset;
    }

    public static function from_array(array $data, string $slug): self {
        $errors = [];

        if (empty($data['name'])) {
            $errors[] = 'Missing required field: name';
        }
        if (empty($data['version'])) {
            $errors[] = 'Missing required field: version';
        }

        $name = $data['name'] ?? ucwords(str_replace('-', ' ', $slug));
        $version = $data['version'] ?? '0.0.0';
        $requires = $data['requires'] ?? [];
        $templates = $data['templates'] ?? [];
        $tags = $data['tags'] ?? [];
        $preset = $data['preset'] ?? '';

        $has_screenshot = file_exists(
            PHANTOM_CORE_PATH . 'frontend/templates/' . $slug . '/preview.jpg'
        );

        $compat_errors = self::compute_compat_errors($requires);
        $all_errors = array_merge($errors, $compat_errors);
        $is_compatible = empty($all_errors);

        return new self(
            name: $name,
            slug: $slug,
            version: $version,
            description: $data['description'] ?? '',
            author: $data['author'] ?? '',
            requires: $requires,
            templates: $templates,
            tags: $tags,
            has_screenshot: $has_screenshot,
            is_compatible: $is_compatible,
            errors: $all_errors,
            preset: $preset
        );
    }

    private static function compute_compat_errors(array $requires): array {
        $errors = [];

        if (!empty($requires['php'])) {
            if (version_compare(PHP_VERSION, (string) $requires['php'], '<')) {
                $errors[] = sprintf(
                    'PHP %s required, current: %s',
                    $requires['php'],
                    PHP_VERSION
                );
            }
        }

        if (!empty($requires['wordpress'])) {
            if (function_exists('get_bloginfo')) {
                $wp_ver = get_bloginfo('version');
                if (version_compare($wp_ver, (string) $requires['wordpress'], '<')) {
                    $errors[] = sprintf(
                        'WordPress %s required, current: %s',
                        $requires['wordpress'],
                        $wp_ver
                    );
                }
            }
        }

        if (!empty($requires['woocommerce'])) {
            if (defined('WC_VERSION')) {
                if (version_compare(WC_VERSION, (string) $requires['woocommerce'], '<')) {
                    $errors[] = sprintf(
                        'WooCommerce %s required, current: %s',
                        $requires['woocommerce'],
                        WC_VERSION
                    );
                }
            }
        }

        if (!empty($requires['phantom_core'])) {
            if (defined('PHANTOM_CORE_VERSION')) {
                if (version_compare(PHANTOM_CORE_VERSION, (string) $requires['phantom_core'], '<')) {
                    $errors[] = sprintf(
                        'Phantom Core %s required, current: %s',
                        $requires['phantom_core'],
                        PHANTOM_CORE_VERSION
                    );
                }
            }
        }

        return $errors;
    }

    public function check_compatibility(): bool {
        if (!empty($this->requires['php'])) {
            if (version_compare(PHP_VERSION, (string) $this->requires['php'], '<')) {
                return false;
            }
        }
        if (!empty($this->requires['wordpress'])) {
            if (function_exists('get_bloginfo')) {
                $wp_ver = get_bloginfo('version');
                if (version_compare($wp_ver, (string) $this->requires['wordpress'], '<')) {
                    return false;
                }
            }
        }
        if (!empty($this->requires['woocommerce'])) {
            if (defined('WC_VERSION')) {
                if (version_compare(WC_VERSION, (string) $this->requires['woocommerce'], '<')) {
                    return false;
                }
            }
        }
        if (!empty($this->requires['phantom_core'])) {
            if (defined('PHANTOM_CORE_VERSION')) {
                if (version_compare(PHANTOM_CORE_VERSION, (string) $this->requires['phantom_core'], '<')) {
                    return false;
                }
            }
        }
        return true;
    }

    public function validate_templates(): array {
        $missing = [];
        $base = PHANTOM_CORE_PATH . 'frontend/templates/' . $this->slug . '/html/';
        foreach ($this->templates as $tmpl) {
            if (!file_exists($base . $tmpl . '.html')) {
                $missing[] = $tmpl;
            }
        }
        return $missing;
    }
}
