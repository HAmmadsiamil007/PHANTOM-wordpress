<?php
declare(strict_types=1);

namespace PhantomCore\Components;

defined('ABSPATH') || exit;

class Component_Metadata {
  public string $slug;
  public string $label;
  public string $description;
  public string $category;
  public array $required_features;
  public array $required_assets;
  public array $supported_templates;
  public array $default_settings;
  public bool $is_core;
  public string $version;

  public function __construct(string $slug, array $metadata = []) {
    $this->slug = $slug;
    $this->label = $metadata['label'] ?? ucwords(str_replace(['_', '-'], ' ', $slug));
    $this->description = $metadata['description'] ?? '';
    $this->category = $metadata['category'] ?? 'content';
    $this->required_features = $metadata['required_features'] ?? [];
    $this->required_assets = $metadata['required_assets'] ?? [];
    $this->supported_templates = $metadata['supported_templates'] ?? [];
    $this->default_settings = $metadata['default_settings'] ?? [];
    $this->is_core = $metadata['is_core'] ?? false;
    $this->version = $metadata['version'] ?? '1.0.0';
  }

  public function to_array(): array {
    return [
      'slug' => $this->slug,
      'label' => $this->label,
      'description' => $this->description,
      'category' => $this->category,
      'required_features' => $this->required_features,
      'required_assets' => $this->required_assets,
      'supported_templates' => $this->supported_templates,
      'default_settings' => $this->default_settings,
      'is_core' => $this->is_core,
      'version' => $this->version,
    ];
  }
}
