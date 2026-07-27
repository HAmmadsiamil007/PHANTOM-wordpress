<?php
declare(strict_types=1);

namespace PhantomCore\Registry;

defined('ABSPATH') || exit;

class Template {
    public readonly string $slug;
    public readonly string $file;
    public readonly string $label;
    public readonly string $category;
    public readonly string $pack;
    public readonly bool $is_404;

    public function __construct(
        string $slug,
        string $file = '',
        string $label = '',
        string $category = 'pages',
        string $pack = 'kids',
        bool $is_404 = false
    ) {
        $this->slug = $slug;
        $this->file = $file ?: $slug . '.html';
        $this->label = $label ?: ucwords(str_replace(['-', '_'], ' ', $slug));
        $this->category = $category;
        $this->pack = $pack;
        $this->is_404 = $is_404;
    }

    public function to_array(): array {
        return [
            'slug' => $this->slug,
            'file' => $this->file,
            'label' => $this->label,
            'category' => $this->category,
            'pack' => $this->pack,
            'is_404' => $this->is_404,
        ];
    }

    public static function from_array(array $data): self {
        return new self(
            slug: $data['slug'] ?? '',
            file: $data['file'] ?? '',
            label: $data['label'] ?? '',
            category: $data['category'] ?? 'pages',
            pack: $data['pack'] ?? 'kids',
            is_404: (bool) ($data['is_404'] ?? false)
        );
    }
}
