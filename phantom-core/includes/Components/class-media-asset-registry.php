<?php
declare(strict_types=1);

namespace PhantomCore\Components;

defined('ABSPATH') || exit;

class MediaAsset {
    public string $key;
    public string $label;
    public string $type;
    public string $default;
    public array $sizes;
    public bool $responsive;
    public bool $lazyload;
    public bool $webp;
    public bool $retina;
    public array $fallback;

    public function __construct(
        string $key,
        string $label = '',
        string $type = 'image',
        string $default = '',
        array $sizes = ['full'],
        bool $responsive = false,
        bool $lazyload = true,
        bool $webp = false,
        bool $retina = false,
        array $fallback = []
    ) {
        $this->key = $key;
        $this->label = $label ?: ucwords(str_replace(['_', '-'], ' ', $key));
        $this->type = $type;
        $this->default = $default;
        $this->sizes = $sizes;
        $this->responsive = $responsive;
        $this->lazyload = $lazyload;
        $this->webp = $webp;
        $this->retina = $retina;
        $this->fallback = $fallback;
    }

    public function to_array(): array {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'type' => $this->type,
            'default' => $this->default,
            'sizes' => $this->sizes,
            'responsive' => $this->responsive,
            'lazyload' => $this->lazyload,
            'webp' => $this->webp,
            'retina' => $this->retina,
            'fallback' => $this->fallback,
        ];
    }

    public static function from_array(array $data): self {
        return new self(
            key: $data['key'] ?? '',
            label: $data['label'] ?? '',
            type: $data['type'] ?? 'image',
            default: $data['default'] ?? '',
            sizes: $data['sizes'] ?? ['full'],
            responsive: (bool)($data['responsive'] ?? false),
            lazyload: (bool)($data['lazyload'] ?? true),
            webp: (bool)($data['webp'] ?? false),
            retina: (bool)($data['retina'] ?? false),
            fallback: $data['fallback'] ?? []
        );
    }
}

class Media_Asset_Registry {
    private static ?self $instance = null;
    private array $assets = [];
    private bool $defaults_registered = false;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function register(MediaAsset $asset): void {
        $this->assets[$asset->key] = $asset;
    }

    public function register_defaults(): void {
        if ($this->defaults_registered) {
            return;
        }

        $url = PHANTOM_CORE_URL;

        $this->register(new MediaAsset('logo', 'Site Logo', 'image', $url . 'frontend/assets/images/logo.svg'));
        $this->register(new MediaAsset('logo-light', 'Logo (Light Mode)', 'image', $url . 'frontend/assets/images/logo-light.svg'));
        $this->register(new MediaAsset('logo-dark', 'Logo (Dark Mode)', 'image', $url . 'frontend/assets/images/logo-dark.svg'));
        $this->register(new MediaAsset('favicon', 'Favicon', 'image', $url . 'frontend/assets/images/favicon.ico'));
        $this->register(new MediaAsset('hero-bg', 'Hero Background', 'image', $url . 'frontend/assets/images/hero-default.jpg', sizes: ['full', 'large', 'medium'], responsive: true));
        $this->register(new MediaAsset('placeholder', 'Placeholder Image', 'image', $url . 'frontend/assets/images/placeholder.svg'));
        $this->register(new MediaAsset('404-image', '404 Page Image', 'image', $url . 'frontend/assets/images/404.svg'));
        $this->register(new MediaAsset('preloader', 'Preloader Animation', 'image', $url . 'frontend/assets/images/preloader.svg'));
        $this->register(new MediaAsset('footer-bg', 'Footer Background', 'image', $url . 'frontend/assets/images/footer-bg.svg'));

        $this->defaults_registered = true;
    }

    public function get(string $key): ?MediaAsset {
        return $this->assets[$key] ?? null;
    }

    public function has(string $key): bool {
        return isset($this->assets[$key]);
    }

    public function get_url(string $key, string $size = 'full'): string {
        $asset = $this->get($key);
        if (null === $asset) {
            return '';
        }

        $settings = \PhantomCore\Settings_Registry::get_instance();
        $stored = $settings->get("asset_{$key}");

        if (!empty($stored)) {
            if (is_numeric($stored)) {
                $src = wp_get_attachment_image_url((int)$stored, $size);
                return $src ?: $asset->default;
            }
            return (string)$stored;
        }

        return $asset->default;
    }

    public function get_all(?string $type = null): array {
        if (null === $type) {
            return $this->assets;
        }
        return array_filter(
            $this->assets,
            fn(MediaAsset $a) => $a->type === $type
        );
    }

    public function get_categories(): array {
        $types = [];
        foreach ($this->assets as $a) {
            $types[$a->type] = true;
        }
        return array_keys($types);
    }

    public function count(): int {
        return count($this->assets);
    }
}
