# Implementation Plan — Frontend Pack System v2 + Inspector Asset Panel

**Date**: 2026-07-31
**Implements**: `docs/superpowers/specs/2026-07-31-frontend-pack-system-v2-design.md` (approved, commit `c4c5be1`)
**Baseline**: 463 tests / 11,850 assertions green · debug.log 0 bytes · smoke harness 20/20 OK
**Commit pattern**: one commit per task; short Conventional Commits messages.

## PRO-REVIEW AMENDMENTS (2026-07-31, code-verified)

1. **`/packs` GET already exists** in `includes/Rest/class-auto-register.php` `register_pack_routes()` — public (`__return_true`), `rest_api_init` priority 20, with in-class `wp_route_exists` dedupe. Pack_Rest registers at priority 15 → wins dedupe; response MUST keep `packs` array key and each entry = manifest fields + `slug` + `builtin` + `active` (superset of existing shape). GET stays public.
2. **Manifest `assets` entries are FULL relative paths** (`frontend/packs/{slug}/assets/css/pack.css`), not short names — DTO must resolve as `base_url . $rel` (same logic as `Template_Loader::get_pack_asset_urls`, incl. `content_url()` dual path), never `base . 'frontend/packs/{slug}/' . $rel` (would double-prefix).
3. **`Template_Loader::get_packs()` returns `slug => 'Display Name'`** (with `'default' => 'Default'` first) — delegation must preserve that exact shape (registry supplies display names).
4. **activate() must flush caches** exactly like the `/publish` route: `\Phantom_Custom_CSS::flush_cache()` (class_exists guard) + `delete_transient('phantom_page_data_v2')`.
5. **Security/hardening**: ZIP extraction must reject path-traversal entries (zip-slip: `..`, absolute paths); support manifests at root OR in a single top-level dir; `is_writable($base_path)` → `io_error`; force-uninstall of ACTIVE pack must reset option to `'default'`.
6. **vc.js asset markup contract** (exact): per-asset row `<div class="vc-asset-row" data-asset="KEY">` containing `.vc-asset-preview` (img or Default span) + `.vc-btn-upload`/`.vc-btn-reset` (each `data-asset="KEY"`, buttons INSIDE the row so `$('[data-asset=K]').find('.vc-asset-preview')` resolves). Upload → `queueChange('asset_' + key, attachmentId)`; reset → `queueChange('asset_' + key, '')`. Panel wrapper uses `vc-panel`/`vc-panel-header`/`vc-panel-body`.
7. **TokenResolver::save** = `update_option($def['option_key'], $value)`; **Settings_Registry::set** = sanitize + `update_option('phantom_'.$key)`; fallback `update_option('phantom_'.$key)`. Precedence chain confirmed valid; bootstrap stubs (`get_option/update_option/add_action/current_user_can/wp_verify_nonce`) sufficient; ZipArchive present in CLI.
8. **Smoke safety**: preserve original `phantom_template_pack` value; smoke installs fixture pack `smoke-test-pack` → activate → uninstall → restore original.

## Context & Constraints

- New namespace `PhantomCore\Packs\`, files in `phantom-core/includes/Packs/class-*.php`, autoload rule `Packs\` → `includes/Packs/class-{name}.php`.
- Tests are standalone (no WP runtime; `tests/bootstrap.php` stubs `get_option/update_option/delete_option/sanitize_key/flush_rewrite_rules/get_transient/set_transient`). PHONTOM constants point at the real plugin dir — scanning hits real bundled packs (`frontend/packs/{dark,minimal,bold}`).
- **No `unzip_file`/`WP_Filesystem`**: ZIP handling via PHP `ZipArchive` (present in phpunit CLI) so install/uninstall stay pure-PHP and testable.
- **Testable seam**: `scan()`, `install_zip()`, `uninstall()` accept an optional `$base_path` argument (default `PHANTOM_CORE_PATH . 'frontend/packs'`) so tests operate on `sys_get_temp_dir()` fixtures and never touch the plugin dir.
- Active pack option: `phantom_template_pack` (existing; `Template_Loader`, `Component_Renderer`, Activation_Wizard, Demo_Content_Generator, Upgrade_Manager already read it — no changes to them beyond delegation).
- Old REST endpoints `GET /template-packs` + `POST /template-pack/activate` stay as aliases (no changes).
- Legacy dead-code + nested stale copy `phantom-core/phantom-core/` — never edited, never scanned (grep exclusions).
- Zip magic check: bytes 0-1 = `PK`. Slug regex: `^[a-z0-9-]{2,32}$`. Builtin list: `['dark','minimal','bold']`.
- WP_Error codes: `pack_missing / pack_exists / pack_active / builtin / invalid_slug / manifest_missing / manifest_invalid / zip_failed / zip_invalid / io_error`.
- Apply-on-activate precedence: `TokenRegistry::has($key)` → `TokenResolver::save($key, $value)`; else `Settings_Registry::has($key)` → `Settings_Registry::get_instance()->set($key, $value)`; else `update_option('phantom_' . $key, $value)`. No snapshot/restore on deactivate.

## File Structure

```
phantom-core/
  phantom-core.php                     (T1: autoload rule + Pack_Rest hook)
  includes/Packs/
    class-frontend-pack.php            (T2: DTO)
    class-frontend-pack-registry.php   (T3-4: registry)
    class-pack-rest.php                (T5: REST)
  includes/Engine/Template_Loader.php  (T6: delegation)
  includes/Inspector/class-inspector-factory.php (T7: asset panel restore)
  tests/
    Frontend_Pack_Test.php             (T2)
    Frontend_Pack_Registry_Scan_Test.php      (T3)
    Frontend_Pack_Registry_Install_Test.php   (T4)
    Pack_Rest_Test.php                 (T5)
    Template_Loader_Pack_Test.php      (T6)
    Inspector_Assets_Test.php          (T7)
    fixtures/packs/ (built by tests at runtime via ZipArchive)
```

## Tasks

### T1 — Autoloader rule + REST wiring
- `phantom-core.php`: add `'Packs' => 'includes/Packs/class-{name}.php'` to the autoload map; wire `Pack_Rest::get_instance()->register_routes()` on `rest_api_init` priority 15 (class not yet existing is fine — hook fires later).
- Verify: `php -l phantom-core.php`.

### T2 — `Frontend_Pack` DTO
- `includes/Packs/class-frontend-pack.php`: `declare(strict_types=1); namespace PhantomCore\Packs;`
- Props: `slug, name, version, description, author, settings[], templates[], assets[], path, builtin(bool), active(bool)`; constructor with defaults.
- `static from_manifest(array $manifest, string $slug, string $path, bool $builtin = false): self` — maps `name/version/description/author/settings/templates/assets` with `?? ''` / `?? []` defaults.
- `to_array(): array` (all fields incl. `path`, `builtin`, `active`).
- `get_css_urls(): array` / `get_js_urls(): array` — `assets.css[]`/`assets.js[]` are FULL relative paths (`frontend/packs/{slug}/...`): resolve as `base . $rel` with `$base = function_exists('content_url') ? content_url() . '/plugins/phantom-core/' : PHANTOM_CORE_URL` (identical to Template_Loader logic; never slug-prefix again).
- Tests (`Frontend_Pack_Test.php`, TDD): from_manifest maps all fields; missing keys → defaults; to_array roundtrip; css/js URL resolution (`PHANTOM_CORE_URL` + full rel path, no double prefix); empty assets → empty arrays.

### T3 — Registry: scan & queries
- `includes/Packs/class-frontend-pack-registry.php`: singleton (`get_instance()`), `private const BUILTIN = ['dark','minimal','bold'];`
- `scan(?string $base_path = null): void` — `scandir()` packs dir, skip dot entries, read `{slug}/manifest.json` (skips silently when unreadable/invalid JSON), build `Frontend_Pack` via `from_manifest`; `builtin = in_array(slug, BUILTIN, true)`; `active = (slug === $this->get_active_slug())`.
- `refresh()`, `get(string $slug): ?Frontend_Pack`, `get_all(): array`, `has(string $slug): bool`, `count(): int`, `get_active_slug(): string` (from `get_option('phantom_template_pack', 'default')`), `get_active(): ?Frontend_Pack`, `get_pack_list(): array` (slug→to_array for REST).
- Tests (`Frontend_Pack_Registry_Scan_Test.php`): singleton; scan of real plugin dir finds `dark/minimal/bold`; manifest fields parsed (e.g. dark name/version); `has('nope')` false; `get_active_slug` matches option stub; `get_pack_list` shape.

### T4 — Registry: install / uninstall / activate / apply_pack_settings
- `validate_slug(string $slug): ?string` — returns error key or null (regex; used by both install & tests).
- `install_zip(string $zip_path, ?string $base_path = null): Frontend_Pack|WP_Error`
  1. File exists + `PK` magic + size < 20 MB else `zip_invalid`/`zip_failed`.
  2. ZipArchive open; locate `manifest.json` at root OR under a single top-level directory (nested-zip support); missing → `manifest_missing`.
  3. Parse manifest; derive slug from manifest `slug` ?? top-level dir name ?? `$slug` param; `validate_slug` else `invalid_slug`; `has($slug)` else `pack_exists`.
  4. **Zip-slip guard**: reject any entry with `..`, leading `/`, `\`, or Windows drive letters; extraction target must stay inside the temp dir.
  5. `is_writable($base_path)` else `io_error`; extract to temp dir under `sys_get_temp_dir()`, copy tree to `$base_path/{slug}` (pure-PHP recursive copy), cleanup temp; on failure `io_error`.
  6. `refresh()`; flush CSS cache: `\Phantom_Custom_CSS::flush_cache()` (class_exists guard) + `delete_transient('phantom_page_data_v2')`; return pack.
- `install_from_upload(array $file, ?string $base_path = null)` — guards `$file['error'] === UPLOAD_ERR_OK` + `$file['tmp_name']`; delegates to `install_zip`.
- `uninstall(string $slug, bool $force = false, ?string $base_path = null): true|WP_Error` — `pack_missing`; `pack_active` (matches active slug, `!$force`); `builtin` (`!$force`); recursive delete; `refresh()`; if deleted pack WAS active → `update_option('phantom_template_pack', 'default')`.
- `activate(string $slug): true|WP_Error` — `pack_missing` guard; `update_option('phantom_template_pack', $slug)`; `flush_rewrite_rules()`; `apply_pack_settings($slug)`; flush CSS cache (same pattern as install); return true.
- `apply_pack_settings(string $slug): int` — per manifest `settings` key/value with the precedence chain above (TokenResolver::save → Settings_Registry::set → update_option fallback); returns count applied.
- Tests (`Frontend_Pack_Registry_Install_Test.php`, temp-dir based): validate_slug table (valid, uppercase → invalid, too short, special chars); install fixture zip built at runtime by a `make_zip(string $dir): string` helper (ZipArchive; skipped via `markTestSkipped` if extension missing); missing-manifest zip → `manifest_missing`; nested single-dir zip → installs from subdir; malicious entry `../evil.txt` → rejected (`zip_invalid`); invalid-slug manifest → `invalid_slug`; duplicate install → `pack_exists`; install → `has()` true + manifest parsed; uninstall guards (`pack_missing`, `pack_active`, `builtin`); force uninstall of builtin from temp base; force uninstall of active pack resets option to `default`; activate applies settings (assert option stub write for unknown key; count returned).

### T5 — `Pack_Rest`
- `includes/Packs/class-pack-rest.php`; `register_routes()` on `rest_api_init` priority 15, guarded by `wp_route_exists()` for `/phantom/v1/packs` (Auto_Register registers `/packs` GET at priority 20 with its own dedupe — Pack_Rest at 15 wins and supersedes it).
- Routes:
  - `GET /phantom/v1/packs` → `get_packs` — **public** (`__return_true`, same as superseded route); response `{packs: [manifest fields + slug + builtin + active], active: slug}` (superset of existing shape).
  - `POST /phantom/v1/packs/activate` body `{slug}` → registry->activate; WP_Error passthrough with mapped code; success `{success, pack, applied: n}`.
  - `POST /phantom/v1/packs/install` multipart `$_FILES['file']` → `install_from_upload`; permission `manage_options` + nonce check mirroring existing verify pattern.
  - `POST /phantom/v1/packs/uninstall` body `{slug, force}` → registry->uninstall.
- `static get_route_specs(): array` — pure route table (path/methods/callback/permission) used by both register_routes and tests.
- Tests (`Pack_Rest_Test.php`): route specs contain the 4 routes with expected callbacks/permissions; error-mapping table via registry guard calls (e.g. activate('nope') → WP_Error code `pack_missing`).

### T6 — Template_Loader delegation
- `includes/Engine/Template_Loader.php`: delegate to `Frontend_Pack_Registry` while preserving EXACT current semantics:
  - `pack_exists($pack)` → `registry->has($pack)` (returns false for 'default' — same as today).
  - `get_pack_manifest($pack)` → registry `get($pack)->to_manifest()` or null ('default' guard preserved → null).
  - `get_pack_asset_urls($pack)` → manifest from registry, URL logic unchanged (content_url dual path; assets are full relative paths).
  - `get_packs()` → `['default' => 'Default'] + registry display-name map` (slug → ucwords name) — shape unchanged.
- Tests (`Template_Loader_Pack_Test.php`): pack_exists('dark') true / 'default' false; get_pack_manifest('dark')['name'] non-empty; get_pack_manifest('default') null; get_packs contains 'default' + 'dark'; get_pack_asset_urls returns css/js URL lists prefixed correctly.

### T7 — Inspector asset panel restore (VC gap fix)
- `includes/Inspector/class-inspector-factory.php`: re-add an **Assets** panel to inspector output (dropped in the rewrite): call `Media_Asset_Registry::get_instance()->register_defaults()` (guarded), iterate `get_all()` (type 'image' only): per-asset row EXACTLY matching vc.js contract:
  `<div class="vc-asset-row" data-asset="{key}">` containing `.vc-asset-preview` (`<img>` of `get_url($key)` with onerror fallback to default) + `<button class="vc-btn-upload" data-asset="{key}">Upload</button>` + `<button class="vc-btn-reset" data-asset="{key}">Reset</button>`; panel wrapper `vc-panel` + `vc-panel-header` ("Assets") + `vc-panel-body` (bindPanelToggles click contract).
- Panel placement: after Typography, before state selector; omit entirely when zero assets.
- Tests (`Inspector_Assets_Test.php`): factory output includes an assets panel with upload/reset buttons + data-asset rows when `Media_Asset_Registry` has assets; row markup order (row first, buttons inside); no panel when registry empty.

### T8 — Full verification, deploy, commit
1. `php -l` on all new/changed PHP files.
2. `php phpunit.phar` — 463 baseline + ~60 new tests green.
3. `docker cp` **only changed files** to `phantom-wp:/var/www/html/wp-content/plugins/phantom-core/...` + md5 verification (host vs container).
4. Smoke (preserve original `phantom_template_pack` value, restore after): GET `/phantom/v1/packs` (superset shape, 200); POST `/packs/install` with fixture zip (`smoke-test-pack`, built via ZipArchive) → 200; POST `/packs/activate` on it → 200; confirm old aliases (`/template-packs`, `/template-pack/activate`) still 200; POST `/packs/uninstall` → 200 + option restored; inspector HTML (`/components/hero/inspector`) contains `vc-asset-row` + `vc-btn-upload`. Confirm debug.log still 0 bytes.
5. Commit: T1-T7 each on their own commit; final commit with smoke results note.

## Testing Strategy
- TDD per task: write failing test → implement → green.
- All new tests standalone (no WP runtime): registry tests use temp-dir `$base_path` seam + runtime ZipArchive fixtures; DTO/route-spec/apply-precedence tests are pure.
- Keep `463/11850` baseline intact: full suite run after each task.
- `./vendor`-independent: use `php phpunit.phar` from `phantom-core/`.

## Risks / Notes
- `scandir` on missing dir → guard with `is_dir()` (scan returns empty, no warning).
- ZipArchive missing in some CI → tests `markTestSkipped` (phpunit CLI env has it).
- Do not touch `class-rest-controller.php` pack endpoints (aliases); do not touch nested stale copy.
- `Media_Asset_Registry::get_url()` calls `wp_get_attachment_image_url` — not stubbed; Inspector_Assets_Test uses assets with non-numeric stored values so the stub-free path (return `(string)$stored`) runs; keep fixture values numeric-free or add stub in test file.

## COMPLETED � 2026-07-31

All tasks T1-T8 delivered. Commits on master:
- fdcc010 � T1 autoload + bootstrap (Packs\ prefix, plugins_loaded init)
- 34d0b0f � T2 Frontend_Pack DTO (80 assertions suite incl. URL resolution)
- dfb1e43 � T3 Frontend_Pack_Registry (scan/get/display names, lazy scan)
- e01e160 � T4 install_zip / install_from_upload / uninstall / activate / apply_pack_settings
- fa34c5a � T5 Pack_Rest (4 routes, priority 15, route dedupe incl. test collector)
- e38e3be � T6 Template_Loader delegation to registry (lazy default scan added)
- 853ab02 � T7 Inspector Assets panel restore (vc.js contract rows)
- fab44ea � T5 polish: WP_Error status 400 + accurate applied count
- (final) � smoke script committed to tools/smoke-packs.php + completion notes

Verification: full suite 543 tests / 12,140 assertions green (463 baseline + 80 new);
php -l 0 errors across all PHP files; Docker deploy md5-verified on 6 files;
smoke ALL PASS (24 checks) via tools/smoke-packs.php (2026-07-31); debug.log 0 bytes.

Findings during execution:
- Registry needed lazy default scan (ensure_scanned) so Template_Loader delegation works in production without pre-scan.
- Pack_Rest::activate double-applied settings (activate() applies internally, callback re-applied) � callback now measures applied count BEFORE activate.
- Registry WP_Error returns lacked status data ? REST returned 500 for client errors; Pack_Rest attaches 400.
- Test bootstrap WP_Error stub lacked add_data() � extended.
- update_option returns false when value unchanged � applied-count semantics verified with clean-state probe.
- Old alias /template-pack/activate requires param pack + X-WP-Nonce (verified working).
- /wp-json/ pretty prefix is intentionally absent (plain permalinks; rest_url() uses ?rest_route=) � not a regression.
- Uninstall does not delete options written by apply_pack_settings (by design; settings are global design options).

Environment notes: container phantom-wp (PHP 8.2.17, ZipArchive present); site URL localhost:8080.
