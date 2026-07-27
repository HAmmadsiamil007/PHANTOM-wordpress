<?php
declare(strict_types=1);

namespace PhantomCore\Manifest;

defined('ABSPATH') || exit;

class Template_Manifest {
  public string $slug;
  public string $label;
  public string $category;
  public array $required_features;
  public array $required_assets;
  public array $compatible_components;
  public array $settings_overrides;
  public string $layout;
  public string $version;

  public function __construct(
    string $slug,
    string $label = '',
    string $category = 'page',
    array $required_features = [],
    array $required_assets = [],
    array $compatible_components = [],
    array $settings_overrides = [],
    string $layout = 'default',
    string $version = '1.0.0'
  ) {
    $this->slug = $slug;
    $this->label = $label ?: ucwords(str_replace(['_', '-'], ' ', $slug));
    $this->category = $category;
    $this->required_features = $required_features;
    $this->required_assets = $required_assets;
    $this->compatible_components = $compatible_components;
    $this->settings_overrides = $settings_overrides;
    $this->layout = $layout;
    $this->version = $version;
  }

  public static function from_json(string $json): self {
    $data = json_decode($json, true);
    if (!is_array($data)) {
      throw new \InvalidArgumentException('Invalid JSON for Template_Manifest');
    }
    return new self(
      slug: $data['slug'] ?? '',
      label: $data['label'] ?? '',
      category: $data['category'] ?? 'page',
      required_features: $data['required_features'] ?? [],
      required_assets: $data['required_assets'] ?? [],
      compatible_components: $data['compatible_components'] ?? [],
      settings_overrides: $data['settings_overrides'] ?? [],
      layout: $data['layout'] ?? 'default',
      version: $data['version'] ?? '1.0.0'
    );
  }

  public static function from_json_file(string $path): self {
    if (!file_exists($path) || !is_readable($path)) {
      throw new \RuntimeException("Cannot read manifest file: {$path}");
    }
    $contents = file_get_contents($path);
    if ($contents === false) {
      throw new \RuntimeException("Failed to read manifest file: {$path}");
    }
    return self::from_json($contents);
  }

  public function to_array(): array {
    return [
      'slug' => $this->slug,
      'label' => $this->label,
      'category' => $this->category,
      'required_features' => $this->required_features,
      'required_assets' => $this->required_assets,
      'compatible_components' => $this->compatible_components,
      'settings_overrides' => $this->settings_overrides,
      'layout' => $this->layout,
      'version' => $this->version,
    ];
  }

  public function requires_feature(string $feature): bool {
    return in_array($feature, $this->required_features, true);
  }

  public function requires_asset(string $handle): bool {
    return in_array($handle, $this->required_assets, true);
  }

  public function is_compatible_with(string $component_slug): bool {
    return in_array($component_slug, $this->compatible_components, true);
  }
}
