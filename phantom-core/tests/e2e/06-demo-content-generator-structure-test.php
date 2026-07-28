<?php
/**
 * E2E 06: Demo Content Generator structural checks.
 */

require_once __DIR__ . '/../bootstrap.php';
require_once PHANTOM_CORE_PATH . 'includes/Setup/class-demo-content-generator.php';

assert(class_exists('PhantomCore\\Setup\\Demo_Content_Generator'), 'Demo_Content_Generator class should exist');

$generator = new PhantomCore\Setup\Demo_Content_Generator();

assert(method_exists($generator, 'generate_all'), 'Should have generate_all()');
assert(method_exists($generator, 'create_pages'), 'Should have create_pages()');
assert(method_exists($generator, 'create_products'), 'Should have create_products()');
assert(method_exists($generator, 'create_posts'), 'Should have create_posts()');
assert(method_exists($generator, 'create_menus'), 'Should have create_menus()');
assert(method_exists($generator, 'clear_all'), 'Should have clear_all()');

// Reflection: verify return types
$ref = new ReflectionMethod($generator, 'generate_all');
$returnType = $ref->getReturnType();
assert($returnType !== null, 'generate_all() should have return type');
assert($returnType->getName() === 'array', 'generate_all() should return array');

echo "06-demo-content-generator-structure-test: PASS\n";
