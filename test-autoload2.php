<?php
// Simulate the autoloader
define('PHANTOM_CORE_PATH', '/var/www/html/wp-content/plugins/phantom-core/');
spl_autoload_register(function (string $class): void {
    $prefix = 'PhantomCore\\';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) { return; }
    $relative_class = substr($class, $len);

    $pascal_to_kebab = function ($s) {
        $s = preg_replace('/([a-z0-9])([A-Z])/', '$1-$2', $s);
        return preg_replace('/([A-Z]+)([A-Z][a-z])/', '$1-$2', $s);
    };

    $viewmodels_prefix = 'ViewModels\\';
    if (strncmp($viewmodels_prefix, $relative_class, strlen($viewmodels_prefix)) === 0) {
        $short = substr($relative_class, strlen($viewmodels_prefix));
        $short = $pascal_to_kebab($short);
        $file = PHANTOM_CORE_PATH . 'includes/ViewModels/' . str_replace('_', '-', strtolower($short)) . '-view-model.php';
        echo "Trying file: $file\n";
        if (file_exists($file)) {
            echo "FILE EXISTS!\n";
            require_once $file;
            return;
        }
    }
});

$c = 'PhantomCore\ViewModels\Product_ViewModel';
echo 'Testing: ' . $c . "\n";
echo class_exists($c) ? 'FOUND' : 'NOT FOUND';
echo "\n";
