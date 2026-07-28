<?php
/**
 * E2E 05: Pack component template overrides.
 */

require_once __DIR__ . '/../bootstrap.php';

$packs = ['dark', 'minimal', 'bold'];
$components = ['product-card.html'];

foreach ($packs as $pack) {
  foreach ($components as $component) {
    $path = PHANTOM_CORE_PATH . "frontend/packs/{$pack}/html/components/{$component}";
    assert(file_exists($path), "{$pack} pack should override {$component}");

    $content = file_get_contents($path);
    assert($content !== false, "Should be able to read {$pack}/{$component}");
    assert(strpos($content, '{{') !== false, "Component template should contain placeholders");
    assert(strpos($content, '}}') !== false, "Component template should contain closing placeholders");
  }
}

echo "05-pack-component-override-test: PASS\n";
