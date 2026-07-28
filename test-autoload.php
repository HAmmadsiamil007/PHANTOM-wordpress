<?php
require '/var/www/html/wp-content/plugins/phantom-core/phantom-core.php';
echo 'Autoloader loaded' . PHP_EOL;
$c = 'PhantomCore\ViewModels\Product_ViewModel';
echo 'Testing: ' . $c . PHP_EOL;
echo class_exists($c) ? 'FOUND' : 'NOT FOUND';
echo PHP_EOL;
