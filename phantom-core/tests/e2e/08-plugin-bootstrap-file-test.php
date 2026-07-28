<?php
/**
 * E2E 08: Plugin bootstrap file integrity.
 */

require_once __DIR__ . '/../bootstrap.php';

$pluginFile = PHANTOM_CORE_PATH . 'phantom-core.php';
assert(file_exists($pluginFile), 'Plugin bootstrap file should exist');

$content = file_get_contents($pluginFile);

// Verify constants
assert(strpos($content, 'PHANTOM_CORE_VERSION') !== false, 'Should define PHANTOM_CORE_VERSION');
assert(strpos($content, 'PHANTOM_CORE_PATH') !== false, 'Should define PHANTOM_CORE_PATH');
assert(strpos($content, 'PHANTOM_CORE_URL') !== false, 'Should define PHANTOM_CORE_URL');

// Verify autoloader prefixes
$autoloadPrefixes = ['Adapters\\\\', 'ViewModels\\\\', 'Renderer\\\\', 'Bridges\\\\', 'Compatibility\\\\', 'Setup\\\\'];
foreach ($autoloadPrefixes as $prefix) {
  assert(strpos($content, $prefix) !== false, "Autoloader should handle {$prefix} namespace");
}

// Verify required files
$requiredFiles = [
  'class-settings-registry.php', 'class-core-plugin.php', 'class-rest-controller.php',
  'class-customizer.php', 'class-custom-css.php', 'class-phantom-global-palette.php',
];
foreach ($requiredFiles as $file) {
  assert(strpos($content, $file) !== false, "Should require {$file}");
}

echo "08-plugin-bootstrap-file-test: PASS\n";
