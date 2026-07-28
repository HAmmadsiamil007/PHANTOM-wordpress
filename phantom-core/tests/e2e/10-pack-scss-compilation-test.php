<?php
/**
 * E2E 10: Pack SCSS files compile cleanly.
 * Requires node-sass or dart-sass installed.
 * Skipped if no Sass compiler found.
 */

require_once __DIR__ . '/../bootstrap.php';

$sassCmd = '';
foreach (['sass', 'node-sass', 'dart-sass'] as $cmd) {
  $output = [];
  $rc = 0;
  exec("where $cmd 2>NUL", $output, $rc);
  if ($rc === 0) { $sassCmd = $cmd; break; }
}

if (!$sassCmd) {
  echo "10-pack-scss-compilation-test: SKIP (no Sass compiler found)\n";
  exit(0);
}

$packs = ['dark', 'minimal', 'bold'];
$errors = [];

foreach ($packs as $pack) {
  $scssFile = PHANTOM_CORE_PATH . "frontend/packs/{$pack}/scss/pack.scss";
  if (!file_exists($scssFile)) {
    $errors[] = "{$pack}: SCSS file not found";
    continue;
  }
  $output = [];
  $rc = 0;
  exec(escapeshellcmd($sassCmd) . " " . escapeshellarg($scssFile) . " 2>&1", $output, $rc);
  if ($rc !== 0) {
    $errors[] = "{$pack}: " . implode("\n", $output);
  }
}

if (!empty($errors)) {
  echo "10-pack-scss-compilation-test: FAIL\n";
  foreach ($errors as $e) echo "  $e\n";
  exit(1);
}

echo "10-pack-scss-compilation-test: PASS\n";
