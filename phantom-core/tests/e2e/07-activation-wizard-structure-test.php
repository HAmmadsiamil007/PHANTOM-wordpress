<?php
/**
 * E2E 07: Activation Wizard structural checks.
 */

require_once __DIR__ . '/../bootstrap.php';
require_once PHANTOM_CORE_PATH . 'includes/Setup/class-activation-wizard.php';

assert(class_exists('PhantomCore\\Setup\\Activation_Wizard'), 'Activation_Wizard class should exist');

$wizard = new PhantomCore\Setup\Activation_Wizard();

assert(method_exists($wizard, 'add_wizard_page'), 'Should have add_wizard_page()');
assert(method_exists($wizard, 'render_page'), 'Should have render_page()');
assert(method_exists($wizard, 'handle_ajax'), 'Should have handle_ajax()');
assert(method_exists($wizard, 'get_steps_config'), 'Should have get_steps_config()');

// Steps config
$steps = $wizard->get_steps_config();
assert(is_array($steps), 'Steps config should be an array');
assert(isset($steps['welcome']), 'Should have welcome step');
assert(isset($steps['pack']), 'Should have pack step');
assert(isset($steps['content']), 'Should have content step');
assert(isset($steps['complete']), 'Should have complete step');

// Static methods
assert(method_exists('PhantomCore\\Setup\\Activation_Wizard', 'is_completed'), 'Should have static is_completed()');
assert(method_exists('PhantomCore\\Setup\\Activation_Wizard', 'mark_completed'), 'Should have static mark_completed()');
assert(method_exists('PhantomCore\\Setup\\Activation_Wizard', 'reset'), 'Should have static reset()');

echo "07-activation-wizard-structure-test: PASS\n";
