<?php
declare(strict_types=1);

namespace PhantomCore\Design;

defined('ABSPATH') || exit;

class Preset {
    public string $id;
    public string $name;
    public string $source;
    public string $version;
    public string $framework;
    public string $author;
    public array $tokens;
    public array $dna;
    public array $metadata;
    public ?string $parent;

    public function __construct() {
        $this->id = '';
        $this->name = '';
        $this->source = 'core';
        $this->version = '1.0.0';
        $this->framework = '>=1.5.0';
        $this->author = 'Phantom Core';
        $this->tokens = [];
        $this->dna = [];
        $this->metadata = [];
        $this->parent = null;
    }

    public static function from_array(array $data): self {
        $preset = new self();
        $preset->id = $data['id'] ?? '';
        $preset->name = $data['name'] ?? '';
        $preset->source = $data['source'] ?? 'core';
        $preset->version = $data['version'] ?? '1.0.0';
        $preset->framework = $data['framework'] ?? '>=1.5.0';
        $preset->author = $data['author'] ?? 'Phantom Core';
        $preset->tokens = $data['tokens'] ?? [];
        $preset->dna = $data['dna'] ?? [];
        $preset->metadata = $data['metadata'] ?? [];
        $preset->parent = $data['parent'] ?? null;
        return $preset;
    }

    public function to_array(): array {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'source' => $this->source,
            'version' => $this->version,
            'framework' => $this->framework,
            'author' => $this->author,
            'tokens' => $this->tokens,
            'dna' => $this->dna,
            'metadata' => $this->metadata,
            'parent' => $this->parent,
        ];
    }

    public function to_json(): string {
        return wp_json_encode($this->to_array(), JSON_PRETTY_PRINT);
    }

    public function merge(self $parentPreset): self {
        $merged = clone $this;
        $merged->tokens = array_merge($parentPreset->tokens, $this->tokens);
        $merged->dna = array_merge($parentPreset->dna, $this->dna);
        $merged->metadata = array_merge($parentPreset->metadata, $this->metadata);
        return $merged;
    }

    public function isCompatible(string $frameworkVersion): bool {
        return version_compare($frameworkVersion, ltrim($this->framework, '>=~^'), '>=');
    }
}
