<?php
/**
 * E2E 02: Template_Loader resolve_path pack overrides.
 */

use PhantomCore\Engine\Template_Loader;

require_once __DIR__ . '/../bootstrap.php';

$loader = new Template_Loader();

// Resolve default path (no pack)
$loader->set_pack('default');
$path = $loader->resolve_path('index.html');
assert(strpos($path, 'frontend/html/index.html') !== false, 'Default path should point to base html dir');

// Resolve with dark pack
$loader->set_pack('dark');
$path = $loader->resolve_path('index.html');
assert(strpos($path, 'frontend/packs/dark/html/index.html') !== false, 'Dark pack path should override');
assert(file_exists($path), 'Dark pack index.html should exist');

$path = $loader->resolve_path('shop.html');
assert(strpos($path, 'frontend/packs/dark/html/shop.html') !== false, 'Dark shop.html should exist');
assert(file_exists($path), 'Dark pack shop.html should exist');

// Resolve with minimal pack
$loader->set_pack('minimal');
$path = $loader->resolve_path('index.html');
assert(strpos($path, 'frontend/packs/minimal/html/index.html') !== false, 'Minimal pack path should override');
assert(file_exists($path), 'Minimal pack index.html should exist');

// Resolve with bold pack
$loader->set_pack('bold');
$path = $loader->resolve_path('index.html');
assert(strpos($path, 'frontend/packs/bold/html/index.html') !== false, 'Bold pack path should override');
assert(file_exists($path), 'Bold pack index.html should exist');

// Fallback to default when pack doesn't have template
$loader->set_pack('dark');
$path = $loader->resolve_path('about.html');
assert(strpos($path, 'frontend/html/about.html') !== false, 'Should fallback to base html when pack lacks template');

// Fallback 404
$path = $loader->resolve_path('nonexistent-file.html');
assert(strpos($path, '404.html') !== false, 'Should fallback to 404 for missing files');

echo "02-template-resolve-path-test: PASS\n";
