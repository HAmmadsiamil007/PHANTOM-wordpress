<?php
declare(strict_types=1);

namespace PhantomCore\Adapters;

use PhantomCore\Contracts\AdapterInterface;
use PhantomCore\Settings_Registry;

defined('ABSPATH') || exit;

class Settings_Adapter implements AdapterInterface {

  private ?Settings_Registry $registry = null;
  private ?array $sections = null;

  public function normalize($section = null): array {
    if (null === $this->registry) {
      $this->registry = Settings_Registry::get_instance();
    }
    $this->load_sections();

    if (is_string($section) && isset($this->sections[$section])) {
      return $this->normalize_section($section);
    }

    $result = [];
    foreach (array_keys($this->sections) as $slug) {
      $result[$slug] = $this->normalize_section($slug);
    }
    return $result;
  }

  public function normalize_collection(array $sections): array {
    return array_map([$this, 'normalize'], $sections);
  }

  private function normalize_section(string $slug): array {
    $entries = $this->sections[$slug];
    $data = [];
    foreach ($entries as $key => $entry) {
      $data[$key] = $this->registry->get($key);
    }
    return $data;
  }

  private function load_sections(): void {
    if (null !== $this->sections) return;
    if (!class_exists('\PhantomCore\Settings\Settings_Loader')) {
      $this->sections = [];
      return;
    }
    $loader = new \PhantomCore\Settings\Settings_Loader();
    $this->sections = $loader->get_all_sections();
  }

  private function empty(): array {
    return [];
  }
}