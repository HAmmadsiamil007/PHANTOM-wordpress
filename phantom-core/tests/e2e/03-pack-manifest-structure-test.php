<?php
/**
 * E2E 03: Pack manifest structural integrity.
 */

require_once __DIR__ . '/../bootstrap.php';

$packs = ['dark', 'minimal', 'bold'];

foreach ($packs as $pack) {
  $file = PHANTOM_CORE_PATH . "frontend/packs/{$pack}/manifest.json";
  assert(file_exists($file), "Manifest should exist for {$pack}");

  $json = json_decode((string) file_get_contents($file), true);
  assert($json !== null, "Manifest should be valid JSON for {$pack}");
  assert(isset($json['name']), "Manifest should have 'name' for {$pack}");
  assert(isset($json['version']), "Manifest should have 'version' for {$pack}");
  assert(isset($json['description']), "Manifest should have 'description' for {$pack}");
  assert(isset($json['assets']), "Manifest should have 'assets' for {$pack}");
  assert(isset($json['assets']['css']), "Manifest should have assets.css for {$pack}");
  assert(isset($json['assets']['js']), "Manifest should have assets.js for {$pack}");

  // Verify SCSS exists
  $scss = PHANTOM_CORE_PATH . "frontend/packs/{$pack}/scss/pack.scss";
  assert(file_exists($scss), "SCSS file should exist for {$pack}");

  // Verify HTML overrides
  $overrides = PHANTOM_CORE_PATH . "frontend/packs/{$pack}/html/";
  if (is_dir($overrides)) {
    $files = scandir($overrides);
    $htmlFiles = array_filter($files, fn($f) => str_ends_with($f, '.html'));
    assert(count($htmlFiles) > 0, "{$pack} pack should have at least one HTML override");
  }
}

echo "03-pack-manifest-structure-test: PASS\n";
