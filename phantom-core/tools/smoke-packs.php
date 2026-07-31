<?php
declare(strict_types=1);

/**
 * Pack System smoke test — run inside the Docker container (phantom-wp).
 *
 * Usage:
 *   docker cp tools/smoke-packs.php phantom-wp:/tmp/smoke-packs.php
 *   docker exec phantom-wp php /tmp/smoke-packs.php
 *
 * Covers: GET /packs (superset shape), POST /install (201 + duplicate 400),
 * POST /activate (applied count), uninstall guards (pack_active 400, force reset),
 * legacy aliases (/template-packs, /template-pack/activate with nonce),
 * inspector asset panel markup (vc-asset-row / vc-btn-upload),
 * and restores the original phantom_template_pack option.
 *
 * Last run: 2026-07-31 — ALL PASS (23 checks).
 */

require '/var/www/html/wp-load.php';

$results = [];
$check = function (string $name, bool $ok, string $detail = '') use (&$results): void {
    $results[] = ($ok ? 'PASS' : 'FAIL') . ' | ' . $name . ($detail !== '' ? ' | ' . $detail : '');
};

wp_set_current_user(1);
$server = rest_get_server();

$orig = get_option('phantom_template_pack', 'default');
$check('backup option captured', is_string($orig), 'orig=' . var_export($orig, true));

// Pre-cleanup from any prior run
$pack_dir = '/var/www/html/wp-content/plugins/phantom-core/frontend/packs/smoke-test-pack';
if (is_dir($pack_dir)) {
    $reg = \PhantomCore\Packs\Frontend_Pack_Registry::get_instance();
    $reg->uninstall('smoke-test-pack', true);
    if ('default' === $orig) { delete_option('phantom_template_pack'); } else { update_option('phantom_template_pack', $orig); }
}
delete_option('phantom_primary_color');
$nonce = wp_create_nonce('wp_rest');

// GET /packs — superset shape
$req = new WP_REST_Request('GET', '/phantom/v1/packs');
$resp = $server->dispatch($req);
$data = $resp->get_data();
$status = $resp->get_status();
$ok = 200 === $status && is_array($data['packs'] ?? null) && isset($data['active']);
$names = array_map(fn($p) => $p['slug'] ?? '', is_array($data['packs'] ?? null) ? $data['packs'] : []);
$check('GET /packs 200 + shape', $ok, 'status=' . $status . ' packs=' . count($names));
$check('GET /packs superset (dark/minimal/bold)', in_array('dark', $names, true) && in_array('minimal', $names, true) && in_array('bold', $names, true), implode(',', $names));

// Old alias GET /template-packs
$req = new WP_REST_Request('GET', '/phantom/v1/template-packs');
$resp = $server->dispatch($req);
$check('old alias GET /template-packs 200', 200 === $resp->get_status(), 'status=' . $resp->get_status());

// Build fixture zip
$zip_path = '/tmp/smoke-test-pack.zip';
$zip = new ZipArchive();
$zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
$zip->addFromString('manifest.json', json_encode([
    'slug' => 'smoke-test-pack',
    'name' => 'Smoke Test Pack',
    'version' => '1.0.0',
    'settings' => ['primary_color' => '#FF00AA'],
    'assets' => ['css' => ['frontend/packs/smoke-test-pack/assets/css/pack.css'], 'js' => []],
]));
$zip->addFromString('assets/css/pack.css', 'body{--pc-accent:#FF00AA}');
$zip->addFromString('html/404.html', '<div>smoke 404</div>');
$zip->close();

// POST /packs/install
$_FILES['file'] = [
    'name' => 'smoke-test-pack.zip',
    'type' => 'application/zip',
    'tmp_name' => $zip_path,
    'error' => UPLOAD_ERR_OK,
    'size' => filesize($zip_path),
];
$req = new WP_REST_Request('POST', '/phantom/v1/packs/install');
$resp = $server->dispatch($req);
$data = $resp->get_data();
$install_ok = $resp->get_status() >= 200 && $resp->get_status() < 300;
$check('POST /packs/install 2xx', $install_ok, 'status=' . $resp->get_status() . ' ' . json_encode($data));
$check('install slug correct', ($data['pack']['slug'] ?? '') === 'smoke-test-pack', json_encode($data));
$check('installed dir exists', is_dir('/var/www/html/wp-content/plugins/phantom-core/frontend/packs/smoke-test-pack'));

// Re-install attempt must be rejected (pack_exists)
$req = new WP_REST_Request('POST', '/phantom/v1/packs/install');
$_FILES['file'] = [
    'name' => 'smoke-test-pack.zip',
    'type' => 'application/zip',
    'tmp_name' => $zip_path,
    'error' => UPLOAD_ERR_OK,
    'size' => filesize($zip_path),
];
$resp = $server->dispatch($req);
$data = $resp->get_data();
$check('duplicate install rejected', 400 === $resp->get_status() && ($data['code'] ?? '') === 'pack_exists', json_encode($data));

// POST /packs/activate
$req = new WP_REST_Request('POST', '/phantom/v1/packs/activate');
$req->set_param('slug', 'smoke-test-pack');
$resp = $server->dispatch($req);
$data = $resp->get_data();
$check('POST /packs/activate 200', 200 === $resp->get_status(), json_encode($data));
$check('activate applied settings count 1', ($data['applied'] ?? -1) === 1, 'applied=' . ($data['applied'] ?? 'null'));
$check('pack setting persisted', get_option('phantom_primary_color', '') === '#FF00AA');
$check('option set to smoke-test-pack', get_option('phantom_template_pack') === 'smoke-test-pack');

// POST /packs/uninstall — active pack requires force; force path resets option to 'default'
$req = new WP_REST_Request('POST', '/phantom/v1/packs/uninstall');
$req->set_param('slug', 'smoke-test-pack');
$resp = $server->dispatch($req);
$data = $resp->get_data();
$check('uninstall active without force blocked', 400 === $resp->get_status() && ($data['code'] ?? '') === 'pack_active', json_encode($data));

$req = new WP_REST_Request('POST', '/phantom/v1/packs/uninstall');
$req->set_param('slug', 'smoke-test-pack');
$req->set_param('force', true);
$resp = $server->dispatch($req);
$data = $resp->get_data();
$check('POST /packs/uninstall force 200', 200 === $resp->get_status(), json_encode($data));
$check('uninstall removed dir', !is_dir('/var/www/html/wp-content/plugins/phantom-core/frontend/packs/smoke-test-pack'));
$check('force uninstall reset option to default', get_option('phantom_template_pack', 'default') === 'default');

// Old alias POST /template-pack/activate (legacy contract uses param "pack" + nonce)
$req = new WP_REST_Request('POST', '/phantom/v1/template-pack/activate');
$req->set_header('X-WP-Nonce', $nonce);
$req->set_param('pack', 'dark');
$resp = $server->dispatch($req);
$check('old alias POST /template-pack/activate 200', 200 === $resp->get_status(), 'status=' . $resp->get_status());
$check('option switched to dark', get_option('phantom_template_pack') === 'dark');

// GET /packs reflects active pack
$req = new WP_REST_Request('GET', '/phantom/v1/packs');
$resp = $server->dispatch($req);
$data = $resp->get_data();
$check('GET /packs active=dark', ($data['active'] ?? '') === 'dark', 'active=' . ($data['active'] ?? ''));

// Inspector panel
$req = new WP_REST_Request('GET', '/phantom/v1/components/hero/inspector');
$resp = $server->dispatch($req);
$data = $resp->get_data();
$html = is_array($data['data'] ?? null) ? ($data['data']['panels'] ?? '') : '';
$check('GET inspector 200', 200 === $resp->get_status(), 'status=' . $resp->get_status());
$check('inspector has vc-asset-row', str_contains((string) $html, 'vc-asset-row'));
$check('inspector has vc-btn-upload', str_contains((string) $html, 'vc-btn-upload'));

// Restore original option
if ('default' === $orig) {
    delete_option('phantom_template_pack');
} else {
    update_option('phantom_template_pack', $orig);
}
$check('option restored', get_option('phantom_template_pack', 'default') === $orig, 'now=' . var_export(get_option('phantom_template_pack', 'default'), true));

$fails = 0;
foreach ($results as $r) {
    echo $r . PHP_EOL;
    if (str_starts_with($r, 'FAIL')) {
        $fails++;
    }
}
echo 'RESULT: ' . (0 === $fails ? 'ALL PASS' : $fails . ' FAILURES') . PHP_EOL;
exit(0 === $fails ? 0 : 1);
