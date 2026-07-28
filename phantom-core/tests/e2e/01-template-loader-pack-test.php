<?php
/**
 * E2E 01: Template_Loader pack switching.
 * Tests: set_pack, get_pack, pack_exists, get_pack_manifest.
 */

use PhantomCore\Engine\Template_Loader;

require_once __DIR__ . '/../bootstrap.php';

$loader = new Template_Loader();

// Default pack
assert($loader->get_pack() === 'default', 'Default pack should be "default"');

// Pack exists checks
assert($loader->pack_exists('dark') === true, 'Dark pack should exist');
assert($loader->pack_exists('minimal') === true, 'Minimal pack should exist');
assert($loader->pack_exists('bold') === true, 'Bold pack should exist');
assert($loader->pack_exists('nonexistent') === false, 'Non-existent pack should not exist');

// Manifest loading
$manifest = $loader->get_pack_manifest('dark');
assert($manifest !== null, 'Dark pack should have manifest');
assert($manifest['name'] === 'Dark', 'Dark pack name should be "Dark"');
assert(isset($manifest['assets']['css']), 'Dark pack should have CSS assets');
assert(isset($manifest['assets']['js']), 'Dark pack should have JS assets');

$minimal = $loader->get_pack_manifest('minimal');
assert($minimal['name'] === 'Minimal', 'Minimal pack name should be "Minimal"');

$bold = $loader->get_pack_manifest('bold');
assert($bold['name'] === 'Bold', 'Bold pack name should be "Bold"');

// Pack switching
$loader->set_pack('dark');
assert($loader->get_pack() === 'dark', 'Pack should be set to "dark"');

$loader->set_pack('default');
assert($loader->get_pack() === 'default', 'Pack should reset to "default"');

echo "01-template-loader-pack-test: PASS\n";
