<?php
declare(strict_types=1);

namespace PhantomCore\Packs;

defined('ABSPATH') || exit;

class Frontend_Pack {
    public string $slug;
    public string $name;
    public string $version;
    public string $description;
    public string $author;
    public array $settings;
    public array $templates;
    public array $assets;
    public string $path;
    public bool $builtin;
    public bool $active;

    public function __construct(
        string $slug,
        string $name = '',
        string $version = '',
        string $description = '',
        string $author = '',
        array $settings = [],
        array $templates = [],
        array $assets = [],
        string $path = '',
        bool $builtin = false,
        bool $active = false
    ) {
        $this->slug = $slug;
        $this->name = $name;
        $this->version = $version;
        $this->description = $description;
        $this->author = $author;
        $this->settings = $settings;
        $this->templates = $templates;
        $this->assets = $assets;
        $this->path = $path;
        $this->builtin = $builtin;
        $this->active = $active;
    }

    public static function from_manifest(array $manifest, string $slug, string $path, bool $builtin = false): self {
        $settings = is_array($manifest['settings'] ?? null) ? $manifest['settings'] : [];
        $templates = is_array($manifest['templates'] ?? null) ? $manifest['templates'] : [];
        $assets = is_array($manifest['assets'] ?? null) ? $manifest['assets'] : [];

        return new self(
            slug: $slug,
            name: (string)($manifest['name'] ?? ucwords(str_replace('-', ' ', $slug))),
            version: (string)($manifest['version'] ?? ''),
            description: (string)($manifest['description'] ?? ''),
            author: (string)($manifest['author'] ?? ''),
            settings: $settings,
            templates: $templates,
            assets: $assets,
            path: $path,
            builtin: $builtin
        );
    }

    public function to_manifest(): array {
        return [
            'name' => $this->name,
            'version' => $this->version,
            'description' => $this->description,
            'author' => $this->author,
            'settings' => $this->settings,
            'templates' => $this->templates,
            'assets' => $this->assets,
        ];
    }

    public function to_array(): array {
        return $this->to_manifest() + [
            'slug' => $this->slug,
            'path' => $this->path,
            'builtin' => $this->builtin,
            'active' => $this->active,
        ];
    }

    public function get_css_urls(): array {
        return $this->resolve_asset_urls('css');
    }

    public function get_js_urls(): array {
        return $this->resolve_asset_urls('js');
    }

    private function resolve_asset_urls(string $type): array {
        $base = $this->asset_base();
        $urls = [];
        foreach ($this->assets[$type] ?? [] as $rel) {
            if (!is_string($rel) || '' === $rel) {
                continue;
            }
            $urls[] = $base . $rel;
        }
        return $urls;
    }

    private function asset_base(): string {
        if (function_exists('content_url')) {
            return content_url() . '/plugins/phantom-core/';
        }
        return PHANTOM_CORE_URL;
    }
}
