<?php
/**
 * E2E 04: PHP syntax check across all source files.
 */

require_once __DIR__ . '/../bootstrap.php';

$srcDir = PHANTOM_CORE_PATH . 'includes/';
$iterator = new RecursiveIteratorIterator(
  new RecursiveDirectoryIterator($srcDir, RecursiveDirectoryIterator::SKIP_DOTS)
);

$errors = [];
$count = 0;

foreach ($iterator as $file) {
  if ($file->getExtension() !== 'php') continue;
  $count++;
  $output = [];
  $returnCode = 0;
  exec("php -l " . escapeshellarg((string)$file), $output, $returnCode);
  if ($returnCode !== 0) {
    $errors[] = (string)$file . ': ' . implode("\n", $output);
  }
}

$packsDir = PHANTOM_CORE_PATH . 'frontend/packs/';
foreach (['dark', 'minimal', 'bold'] as $pack) {
  $assetsDir = $packsDir . $pack . '/assets/';
  if (!is_dir($assetsDir)) continue;
  $it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($assetsDir, RecursiveDirectoryIterator::SKIP_DOTS)
  );
  foreach ($it as $file) {
    if ($file->getExtension() !== 'php') continue;
    $count++;
    $output = [];
    $returnCode = 0;
    exec("php -l " . escapeshellarg((string)$file), $output, $returnCode);
    if ($returnCode !== 0) {
      $errors[] = (string)$file . ': ' . implode("\n", $output);
    }
  }
}

if (!empty($errors)) {
  echo "04-php-syntax-all-files: FAIL\n";
  foreach ($errors as $e) echo "  $e\n";
  exit(1);
}

echo "04-php-syntax-all-files: PASS ({$count} files checked)\n";
