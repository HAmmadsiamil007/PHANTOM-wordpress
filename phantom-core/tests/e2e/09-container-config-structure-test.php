<?php
/**
 * E2E 09: Container_Config structural completeness.
 */

use PhantomCore\Engine\Container_Config;

require_once __DIR__ . '/../bootstrap.php';

$ref = new ReflectionClass(Container_Config::class);
$methods = $ref->getMethods();
assert(count($methods) >= 1, 'Container_Config should have at least 1 method');

// Verify configure method exists
assert(method_exists(Container_Config::class, 'configure'), 'Should have configure method');
$configMethod = $ref->getMethod('configure');
assert($configMethod->isStatic(), 'configure should be static');

// Read the config file and count service registrations
$configFile = PHANTOM_CORE_PATH . 'includes/Engine/Container_Config.php';
$content = file_get_contents($configFile);

preg_match_all('/\$container->singleton\(/', $content, $singletons);
preg_match_all('/\$container->set\(/', $content, $sets);
$totalServices = count($singletons[0]) + count($sets[0]);

assert($totalServices >= 30, "Should have at least 30 registered services (has {$totalServices})");

// Verify key services
$services = [
  'Template_Loader', 'Render_Engine', 'EventDispatcher', 'WooCommerce_Injector',
  'Bridge_Manager', 'Component_Registry', 'Template_Registry',
];
foreach ($services as $service) {
  assert(strpos($content, $service) !== false, "Container_Config should register {$service}");
}

echo "09-container-config-structure-test: PASS ({$totalServices} services)\n";
