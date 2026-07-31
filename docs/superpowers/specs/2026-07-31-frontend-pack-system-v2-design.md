# Frontend Pack System v2 — Design (2026-07-31)

## Purpose

Close the last remaining gap in the Visual Customizer 2.0 spec: `Frontend_Pack` class + `Frontend_Pack_Registry` + pack install/uninstall/activate API. Today packs are plugin-bundled directories (`frontend/packs/{slug}/manifest.json`) read ad-hoc by `Template_Loader` (Engine), with two manual REST endpoints in `class-rest-controller.php`. This design makes a registry the single source of truth, adds a rich DTO, and adds ZIP install + uninstall + activate-via-code/REST, applying manifest settings on activation.

## Decisions (agreed)

1. **Scope**: Bundled packs + ZIP install (upload → validate → extract into `frontend/packs/`). Uninstall refuses active pack; builtin packs refuse unless `force=true`.
2. **Integration**: `Frontend_Pack_Registry` is the single source of truth; `Template_Loader`'s pack methods delegate to it. Old REST endpoints stay as aliases.
3. **Settings**: Activating a pack applies its manifest `settings` block (design tokens when known, else `phantom_{key}` options).

## Architecture

```
PhantomCore\Packs\
  ├── Frontend_Pack          — DTO from manifest
  ├── Frontend_Pack_Registry — singleton: scan/index/install/uninstall/activate
  └── Pack_Rest              — REST routes (array-driven, deduped)
```

Autoloader rule: `Packs\` → `includes/Packs/class-{name}.php` (kebab-case).

## Component 1 — Frontend_Pack (DTO)

`includes/Packs/class-frontend-pack.php`

Public properties:
- `string $slug` — directory name, `^[a-z0-9-]{2,32}$`
- `string $name` — display name (manifest `name`)
- `string $version` — manifest `version`
- `string $description` — manifest `description`
- `string $author` — manifest `author`
- `array $settings` — manifest `settings` (e.g. `primary_color`, `dark_mode`, `font_heading`, `font_body`)
- `array $templates` — manifest `templates` (`override_count`, `base`)
- `array $assets` — manifest `assets` (`css[]`, `js[]` relative paths)
- `string $path` — absolute directory path
- `bool $builtin` — true for the 3 bundled packs (dark/minimal/bold), false for ZIP-installed
- `bool $active` — equals `phantom_template_pack` option

Factory + helpers:
- `public static function from_manifest(string $slug, array $manifest): self`
- `public function to_array(): array` — for REST (includes `active`, `builtin`)
- `public function get_css_urls(): array` / `get_js_urls(): array` — asset URL resolution (same base logic as today's `get_pack_asset_urls`: `content_url() . '/plugins/phantom-core/'` with `PHANTOM_CORE_URL` fallback)

## Component 2 — Frontend_Pack_Registry (singleton)

`includes/Packs/class-frontend-pack-registry.php`

State: `?self $instance`, `array $packs` (slug → Frontend_Pack), `bool $scanned`, `?string $active_slug`.

Methods:
- `get_instance(): self`
- `scan(): void` — if already scanned, no-op; else `scandir(PHANTOM_CORE_PATH . 'frontend/packs/')`, read each `manifest.json`, build `Frontend_Pack`; sets `builtin` from a hardcoded list `['dark','minimal','bold']`; marks active from `get_option('phantom_template_pack', 'default')`.
- `get(string $slug): ?Frontend_Pack` / `get_all(): array` / `count(): int` / `has(string $slug): bool` / `get_active(): ?Frontend_Pack` (null when default)
- `refresh(): void` — clear scan + transient, rescan
- `install(array $file): bool|\WP_Error` — see flow below
- `uninstall(string $slug, bool $force = false): bool|\WP_Error`
- `activate(string $slug): bool|\WP_Error`
- `apply_pack_settings(Frontend_Pack $pack): void`
- `get_pack_list(): array` — flat list for `Template_Loader::get_packs()` compat: `['default' => 'Default', slug => name, ...]`

### install() flow
1. Validate `$file['error'] === UPLOAD_ERR_OK`, `$file['size'] > 0`, extension `.zip` (check `name` + `tmp_name` magic bytes `PK`).
2. `$temp = wp_tempnam()`-style dir under `wp_upload_dir()['basedir'] . '/phantom-packs-tmp-' . uniqid()`; `unzip_file($file['tmp_name'], $temp)` — returns `true` or `WP_Error`.
3. Locate `manifest.json` in zip root (or single root folder containing it); reject if missing.
4. Slug from that root folder name; validate `/^[a-z0-9-]{2,32}$/`; reject if `has($slug)` (or equals `default`).
5. Parse manifest as JSON object; require `name`; copy extracted pack dir to `PHANTOM_CORE_PATH . 'frontend/packs/' . $slug` (recursive `WP_Filesystem` copy); `unlink` temp dir (cleanup).
6. `refresh()`; flush CSS cache (`\Phantom_Custom_CSS::flush_cache()` when available).

### uninstall()
- Guard: `has($slug)` else `WP_Error('pack_missing')`.
- Guard: `$slug === active` else `WP_Error('pack_active')`.
- Guard: `builtin && !$force` else `WP_Error('builtin')`.
- Delete directory recursively via `WP_Filesystem`; `refresh()`; flush CSS cache.

### activate()
- Guard: `has($slug)` else `WP_Error('pack_missing')`.
- `update_option('phantom_template_pack', $slug)`; update active flags in memory; `flush_rewrite_rules()` (parity with current endpoint); `apply_pack_settings($pack)`; flush CSS cache.

### apply_pack_settings()
- For each `$key => $value` in manifest `settings`:
  - If `PhantomCore\Design\TokenRegistry::get_instance()->has($key)` → `(new PhantomCore\Design\TokenResolver())->save($key, $value)`.
  - Else if `PhantomCore\Settings_Registry::get_instance()->has($key)` → `Settings_Registry::set($key, $value)` (writes `phantom_{$key}` option through the registered sanitizer; verified: `set()` exists at class-settings-registry.php:56).
  - Else → `update_option('phantom_' . sanitize_key($key), $value)`.
- No snapshot/restore on deactivate (decided).

## Component 3 — Pack_Rest (routes)

`includes/Packs/class-pack-rest.php`

`register_routes()` hooked to `rest_api_init` at priority 15, each route guarded by `wp_route_exists()` dedupe (pattern from VC stack):

| Route | Method | Params | Callback → registry | Errors |
|---|---|---|---|---|
| `/packs` | GET | — | `get_all()` as array | — |
| `/packs/activate` | POST | `slug` (sanitize_text_field) | `activate()` | 400 pack_missing |
| `/packs/install` | POST | multipart `file` from `$_FILES['file']` | `install()` | 400/500 WP_Error mapped |
| `/packs/uninstall` | POST | `slug`, `force` (bool) | `uninstall()` | 400 pack_active/builtin/pack_missing |

- Permission callbacks: `current_user_can('manage_options')` (install/uninstall/activate); GET `/packs` open.
- Nonce: standard `X-WP-Nonce` (wp_rest) — REST nonce flow already established.
- Response shape: `{ success: true, pack: {...}, packs: [...] }` for mutations; `{ success: true, packs: [...] }` for GET.
- Old endpoints (`/template-packs`, `/template-pack/activate` in class-rest-controller.php) remain registered and functional — aliases, no removal.

## Component 4 — Template_Loader delegation

In `includes/Engine/Template_Loader.php`:
- `pack_exists()` → `Frontend_Pack_Registry::get_instance()->has($pack)`
- `get_pack_manifest()` → `registry->get($pack)->to_array()`-derived manifest (or `null` for `default`); keep returning the raw manifest-shaped array so existing consumers (`Placeholder_Replacer`, REST list) are unaffected.
- `get_pack_asset_urls()` → `registry->get($pack)->get_css_urls()/get_js_urls()`
- `get_packs()` → `registry->get_pack_list()`

`Component_Renderer::load_template()`, `Activation_Wizard`, `Demo_Content_Generator`, `Demo_Switcher`, `Upgrade_Manager` need **no changes** (they read the option / use Template_Loader).

## Wiring

- `phantom-core.php` autoloader: add `Packs\` → `includes/Packs/class-{name}.php`.
- Bootstrap: `add_action('rest_api_init', [Pack_Rest::class, 'register_routes'], 15)` — unconditional (REST-only).
- No `plugins_loaded` singleton init needed (registry is lazy via `get_instance()`).

## Error handling

`WP_Error` codes: `pack_missing`, `pack_exists`, `pack_active`, `builtin`, `invalid_slug`, `manifest_missing`, `manifest_invalid`, `zip_failed`, `zip_invalid`, `io_error`. REST maps: `pack_missing` → 404; validation/guard codes → 400; io/zip failures → 500. All return `success:false` + `message`.

## Testing

- Registry scan: finds 3 bundled packs (dark/minimal/bold), correct builtin/active flags, `default` excluded.
- `install()`: rejects non-zip, rejects missing manifest (fixture zip), rejects duplicate slug, accepts valid fixture zip → dir exists, index updated.
- `uninstall()`: rejects active pack, rejects builtin without force, deletes installed pack with force.
- `activate()`: sets option, applies manifest settings (token path + generic option path), rejects unknown slug.
- `apply_pack_settings()`: token exists → TokenResolver called; settings-registry key → registry set; else `phantom_{key}` option written.
- Template_Loader delegation: `get_packs()`/`pack_exists()`/`get_pack_manifest()` return same shapes as before for bundled packs.
- Fixtures: `tests/fixtures/packs/valid-pack.zip` (tiny, built at test time via ZipArchive — skip cleanly if zip ext missing), plus invalid variants (no-manifest).
- Full suite stays green (463 tests baseline).

## Out of scope

- External pack stores (uploads/theme dirs) — `templates.base` kept in DTO for future use.
- Snapshot/restore of settings on deactivate.
- Pack marketplace/download URLs.
- Removing old `/template-packs` + `/template-pack/activate` endpoints (aliases kept).
- Frontend UI for pack management (admin page remains the Settings dropdown).
