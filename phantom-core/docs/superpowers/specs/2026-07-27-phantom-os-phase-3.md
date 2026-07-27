# Phantom OS Framework — Phase 3: Demo Manager Subsystem

**Date:** 2026-07-27
**Version:** 1.0
**Status:** Approved
**Author:** AI Agent + Human Partner

---

## 1. Domain Glossary

| Term | Definition |
|------|-----------|
| **Demo** | A self-contained visual skin consisting of HTML templates, CSS, JS, and a `demo.json` manifest. Fully swappable without touching PHP. |
| **Template Pack** | The filesystem storage for a Demo. Lives at `frontend/templates/{slug}/`. Contains `html/`, `css/`, `js/` subdirectories. |
| **Demo Registry** | Service that scans `frontend/templates/` for valid demos (presence of `demo.json`), parses manifests, and returns structured demo lists. |
| **Demo Contract** | Validation rules defining what constitutes a valid demo: version requirements, file integrity, template coverage. |
| **Demo Loader** | Service that loads all resources from the active demo: templates, assets, config. Wraps `Template_Loader`. |
| **Demo Switcher** | Service that handles deactivating the current demo and activating a new one, including cache flush and asset refresh. |
| **Demo Installer** | Service that handles ZIP upload, extraction, `demo.json` validation, and filesystem installation. |

---

## 2. Architecture

```
Demo Manager Subsystem
├── Phase 3A: Registry + Contracts
│   ├── Demo_Registry      — scans, caches, lists demos
│   └── Demo_Contract      — validates demo integrity
├── Phase 3B: Loader + Switcher + Installer
│   ├── Demo_Loader        — loads active demo resources
│   ├── Demo_Switcher      — activate/deactivate workflow
│   └── Demo_Installer     — ZIP upload and extraction
└── Phase 3C: Admin UI
    └── Demo_Admin         — WordPress admin page
```

### File Map

```
includes/Demo/
├── class-demo-registry.php      # Phase 3A
├── class-demo-contract.php      # Phase 3A
├── class-demo-loader.php        # Phase 3B
├── class-demo-switcher.php      # Phase 3B
└── class-demo-installer.php     # Phase 3B

admin/class-demo-admin.php       # Phase 3C

frontend/templates/{slug}/
├── demo.json                    # Demo manifest
├── preview.jpg                  # Admin preview screenshot (optional)
└── html/                        # Template files
```

### Dependency Flow

```
Demo_Registry ──reads──► demo.json files
     │
     ├──► Demo_Contract (validate)
     │
     └──► Demo_Switcher ──► Demo_Loader ──► Template_Loader
                 │
                 └──► Settings_Registry (update template_pack)
```

### Integration Points

| Integration | File | What Changes |
|-------------|------|-------------|
| Container_Config | `includes/Engine/Container_Config.php` | Register Demo_Registry (no deps), Demo_Switcher (needs Demo_Registry), Demo_Loader (needs Template_Loader + Demo_Registry) |
| Template_Loader | `includes/Engine/Template_Loader.php` | No changes needed — Demo_Loader wraps Template_Loader |
| Settings_Registry | `includes/class-settings-registry.php` | No changes needed — template_pack setting already exists |
| phantom-core.php | `phantom-core.php` | Auto-load Demo directory; conditionally load admin page |
| shell.php | `templates/shell.php` | No changes needed |

---

## 3. Phase 3A: Demo Registry & Contracts

### 3.1 Demo_Registry

**File:** `includes/Demo/class-demo-registry.php`
**Namespace:** `PhantomCore\Demo`

```php
class Demo_Registry {
    public function __construct() {}

    // Returns all installed demos as Demo_Contract[] (parsed from demo.json)
    public function get_all(): array;

    // Returns single demo by slug
    public function get(string $slug): ?Demo_Contract;

    // Returns currently active demo
    public function get_active(): ?Demo_Contract;

    // Check if a demo is installed
    public function has(string $slug): bool;

    // Scan filesystem for new demos (clears cache)
    public function refresh(): void;

    // Returns number of installed demos
    public function count(): int;
}
```

**Behavior:**
- Scans `frontend/templates/` for subdirectories containing `demo.json`
- Parses `demo.json` into a `Demo_Contract` value object
- Caches results in a static array (refreshes on `refresh()` call)
- If no `demo.json` found in a subdirectory, that directory is ignored

### 3.2 Demo_Contract

**File:** `includes/Demo/class-demo-contract.php`
**Namespace:** `PhantomCore\Demo`

A value object (immutable, readonly properties) representing a validated demo:

```php
class Demo_Contract {
    public readonly string $name;
    public readonly string $slug;
    public readonly string $version;
    public readonly string $description;
    public readonly string $author;
    public readonly array $requires;       // ['php' => '7.4', 'wordpress' => '6.4', ...]
    public readonly array $templates;       // list of template names this pack overrides
    public readonly array $tags;
    public readonly bool $has_screenshot;
    public readonly bool $is_compatible;    // computed: meets WP/PHP/WC version reqs
    public readonly array $errors;          // validation errors

    // Factory: parse from demo.json array
    public static function from_array(array $data, string $slug): self;

    // Check version requirements against current environment
    public function check_compatibility(): bool;
}
```

### 3.3 demo.json Format

```json
{
  "name": "Fashion Store",
  "slug": "fashion",
  "version": "1.0.0",
  "description": "A modern fashion e-commerce demo with bold typography.",
  "author": "Phantom Core",
  "requires": {
    "php": "7.4",
    "wordpress": "6.4",
    "woocommerce": "9.0",
    "phantom_core": "1.5.0"
  },
  "templates": [
    "index",
    "shop",
    "product-detail",
    "blog",
    "contact"
  ],
  "tags": ["fashion", "ecommerce", "modern"]
}
```

**Validation rules:**
- `slug`: Required, alphanumeric + hyphens only, must match directory name
- `name`: Required, non-empty string
- `version`: Required, semver format (MAJOR.MINOR.PATCH)
- `templates`: Optional array of template names this pack overrides. Any listed files must exist in `{slug}/html/`. Not listed → falls back to `frontend/html/` default templates.
- `requires.php`, `requires.wordpress`: Optional, checked against `PHP_VERSION` and `$wp_version`
- `requires.woocommerce`: Optional, checked if WooCommerce active
- `requires.phantom_core`: Optional, checked against `PHANTOM_CORE_VERSION`
- Unknown fields are ignored (forward-compatible)

### 3.4 Contract Validation Flow

```
demo.json found
    ↓
Parse JSON → Demo_Contract::from_array()
    ↓
Validate structure (slug, name, version)
    ↓
Validate listed templates exist on disk as {slug}/html/{template}.html
    (warn on missing, don't block — fallback provides them)
    ↓
Check version compatibility (PHP, WP, WC, Phantom Core)
    ↓
Return Demo_Contract with is_compatible flag + errors[] list
```

---

## 4. Phase 3B: Demo Loader, Switcher & Installer

### 4.1 Demo_Loader

**File:** `includes/Demo/class-demo-loader.php`
**Namespace:** `PhantomCore\Demo`

```php
class Demo_Loader {
    public function __construct(
        private Template_Loader $template_loader,
        private Demo_Registry $registry
    ) {}

    // Get the active demo's template directory path
    public function get_active_template_path(): string;

    // Get the active demo's asset URL base
    public function get_active_asset_url(): string;

    // Check if a template exists in the active demo
    public function has_template(string $template_name): bool;

    // List all CSS files in active demo
    public function get_active_css_files(): array;

    // List all JS files in active demo
    public function get_active_js_files(): array;

    // Get the active demo's screenshot URL
    public function get_screenshot_url(string $slug): ?string;
}
```

**Behavior:**
- Wraps `Template_Loader` to provide demo-aware resource resolution
- Asset URLs are constructed from the active demo's directory
- CSS/JS file listing scans `frontend/templates/{active}/css/` and `js/`

**Security:**
- ZIP extraction uses WordPress `WP_Filesystem` API (not direct PHP ZipArchive)
- Only allows `.html`, `.css`, `.js`, `.json`, `.png`, `.jpg`, `.jpeg`, `.gif`, `.svg`, `.webp`, `.woff`, `.woff2`, `.ttf`, `.eot` file extensions inside ZIP
- Skips `..` path traversal attempts
- Validates `demo.json` before extracting any files
- Maximum ZIP size: 50MB (configurable via `phantom_max_zip_size` filter)

### 4.2 Demo_Switcher

**File:** `includes/Demo/class-demo-switcher.php`
**Namespace:** `PhantomCore\Demo`

```php
class Demo_Switcher {
    public function __construct(
        private Demo_Registry $registry
    ) {}

    // Activate a demo: validate → switch setting → flush → fire event
    public function activate(string $slug): Result;

    // Deactivate current demo (reverts to default 'kids')
    public function deactivate(): Result;

    // Get the current active demo slug
    public function get_active_slug(): string;

    // Check if a demo can be activated (pre-flight check)
    public function can_activate(string $slug): array;  // returns [pass: bool, checks: array]
}
```

**Activation flow:**

```
Demo_Switcher::activate('fashion')
    ↓
1. Check demo exists via Registry
    ↓
2. Run compatibility check (Demo_Contract::check_compatibility())
    ↓
3. If compatible:
   a. `update_option('template_pack', $slug)` — matches Settings_Registry option
   b. `update_option('phantom_active_demo', $slug)` — tracks active demo
   c. Flush rewrite rules via `flush_rewrite_rules()`
   d. Fire `'phantom_demo_activated'` event with slug + old_slug payload
   e. Return success Result
    ↓
4. If incompatible:
   Return failure Result with error messages
```

**Compatibility check results:**

```php
[
  'pass' => true,
  'checks' => [
    ['name' => 'PHP Version', 'status' => 'pass', 'message' => 'PHP 8.2 >= 7.4'],
    ['name' => 'WordPress', 'status' => 'pass', 'message' => 'WP 6.7 >= 6.4'],
    ['name' => 'WooCommerce', 'status' => 'pass', 'message' => 'WC 9.x >= 9.0'],
    ['name' => 'Required Templates', 'status' => 'pass', 'message' => 'All 7 required templates found'],
    ['name' => 'Assets', 'status' => 'pass', 'message' => 'CSS + JS directories exist'],
  ]
]
```

### 4.3 Demo_Installer

**File:** `includes/Demo/class-demo-installer.php`
**Namespace:** `PhantomCore\Demo`

```php
class Demo_Installer {
    public function __construct(
        private Demo_Registry $registry
    ) {}

    // Install a demo from an uploaded ZIP file
    public function install(string $zip_path): Result;

    // Delete an installed demo
    public function delete(string $slug): Result;

    // Validate ZIP file structure before extraction
    public function validate_zip(string $zip_path): Result;

    // Get the target extraction path for a demo slug
    public function get_target_path(string $slug): string;
}
```

**Install flow:**

```
Demo_Installer::install('/tmp/uploaded.zip')
    ↓
1. Validate ZIP is readable
    ↓
2. Look for demo.json at root of ZIP
    ↓
3. Parse + validate demo.json (Demo_Contract::from_array)
    ↓
4. Check slug doesn't already exist on filesystem
    ↓
5. Extract ZIP to frontend/templates/{slug}/
    ↓
6. Verify required directories exist (html/, demo.json)
    ↓
7. Refresh Registry cache
    ↓
8. Return success Result with demo info

Demo_Installer::delete('fashion')
    ↓
1. Check demo is installed (via Registry)
    ↓
2. Check demo is not active (cannot delete active)
    ↓
3. Recursively delete frontend/templates/fashion/
    ↓
4. Refresh Registry cache
    ↓
5. Return success Result
```

**Result value object:**

**File:** `includes/Demo/class-demo-result.php`
**Namespace:** `PhantomCore\Demo`

```php
class Result {
    public readonly bool $success;
    public readonly string $message;
    public readonly array $data;      // optional payload
    public readonly array $errors;    // validation errors if failed

    public static function ok(string $message, array $data = []): self;
    public static function fail(string $message, array $errors = []): self;
}
```

---

## 5. Phase 3C: Admin UI

### 5.1 Demo_Admin

**File:** `admin/class-demo-admin.php`
**Namespace:** `PhantomCore\Admin`

```php
class Demo_Admin {
    public function __construct(
        private Demo_Registry $registry,
        private Demo_Switcher $switcher,
        private Demo_Installer $installer
    ) {}

    // Register admin menu page
    public function register_menu(): void;

    // Enqueue admin CSS/JS
    public function enqueue_assets(string $hook): void;

    // Render the admin page
    public function render_page(): void;

    // Handle AJAX: activate demo
    public function ajax_activate(): void;

    // Handle AJAX: delete demo
    public function ajax_delete(): void;

    // Handle POST: install demo from ZIP
    public function handle_install(): void;
}
```

### 5.2 Admin Page Layout

```
┌─────────────────────────────────────────────┐
│  PHANTOM DEMOS                [Install ZIP] │
├─────────────────────────────────────────────┤
│                                             │
│  ┌──────────────┐  ┌──────────────┐         │
│  │  ★ FASHION   │  │  KIDS        │         │
│  │  [preview]   │  │  [preview]   │         │
│  │  v1.0.0      │  │  v1.0.0      │         │
│  │  Active      │  │  Inactive    │         │
│  │  ──────────  │  │  ──────────  │         │
│  │  [Deactivate]│  │  [Activate]  │         │
│  │  [Delete]    │  │  [Delete]    │         │
│  └──────────────┘  └──────────────┘         │
│                                             │
│  ┌──────────────┐  ┌──────────────┐         │
│  │  SHOES       │  │  FURNITURE   │         │
│  │  ⚠ Incomplete│  │  [preview]   │         │
│  │  [Delete]    │  │  v1.0.0      │         │
│  │              │  │  Inactive    │         │
│  │              │  │  [Activate]  │         │
│  └──────────────┘  └──────────────┘         │
│                                             │
└─────────────────────────────────────────────┘
```

### 5.3 Activation Modal

When user clicks "Activate":

```
┌──────────────────────────────────────┐
│  Activate Demo                       │
│                                      │
│  Checking compatibility...           │
│                                      │
│  ✓  PHP 8.2 ≥ 7.4                   │
│  ✓  WordPress 6.7 ≥ 6.4             │
│  ✓  WooCommerce 9.x ≥ 9.0           │
 │  ✓  Demo templates verified          │
│  ✓  CSS + JS assets present          │
│                                      │
│  [Cancel]  [Activate Now]            │
└──────────────────────────────────────┘
```

### 5.4 Install Modal

When user clicks "Install ZIP":

```
┌──────────────────────────────────────┐
│  Install Demo from ZIP               │
│                                      │
│  [Choose File] fashion-demo.zip      │
│                                      │
│  The ZIP must contain:               │
│  • demo.json (manifest)              │
│  • html/ (templates)                  │
│                                      │
│  [Install]  [Cancel]                 │
└──────────────────────────────────────┘
```

### 5.5 User Capabilities

All admin operations require `manage_options` capability (admin-level). Nonces enforced on all actions.

---

## 6. Backward Compatibility

| Concern | Impact | Mitigation |
|---------|--------|------------|
| Existing fashion pack has no demo.json | Registry won't see it | Add demo.json to fashion pack during Phase 3A |
| Existing "kids" pack doesn't exist on disk | Registry returns null | Default 'kids' treated as virtual (no files) — Registry returns Demo_Contract with is_compatible=true |
| phantom_active_demo option not set | get_active() returns null | Initialize on plugin activation: `add_option('phantom_active_demo', 'kids')` |
| Settings_Registry template_pack setting | Still works | Demo_Switcher writes to this same option |
| Container_Config sets pack from settings | Still works — reads template_pack option |

---

## 7. Testing Strategy

### Test Files

| Test File | Tests | Phase |
|-----------|-------|-------|
| `tests/Demo_Registry_Test.php` | get_all, get, has, refresh, active | 3A |
| `tests/Demo_Contract_Test.php` | from_array, validate_templates, check_compatibility | 3A |
| `tests/Demo_Loader_Test.php` | active paths, asset URLs, file listing | 3B |
| `tests/Demo_Switcher_Test.php` | activate, deactivate, can_activate, compatibility checks | 3B |
| `tests/Demo_Installer_Test.php` | validate_zip, install, delete | 3B |

### What to Test

- Demo_Registry: Scan returns correct count, cache invalidation works
- Demo_Contract: Valid demo.json parses correctly, missing fields fail validation
- Demo_Switcher: Activation updates option, deactivation reverts, invalid slug fails
- Demo_Installer: ZIP validation rejects bad ZIPs, install creates correct structure
- Admin: Page renders without errors, AJAX handlers respond correctly

---

## 8. Success Criteria

### Phase 3A (Registry + Contracts)
- [ ] `Demo_Registry::get_all()` returns all demos found in `frontend/templates/`
- [ ] `Demo_Registry::get_active()` returns the currently active demo
- [ ] `Demo_Contract::from_array()` correctly parses valid `demo.json`
- [ ] `Demo_Contract::check_compatibility()` correctly checks PHP/WP/WC versions against environment
- [ ] `Demo_Contract` correctly detects missing template files as warnings (not blockers)
- [ ] Fashion pack has `demo.json` added to its directory
- [ ] Registry ignores directories without `demo.json`
- [ ] `Result` class provides `ok()` and `fail()` factory methods
- [ ] `phantom_active_demo` option initialized to 'kids'

### Phase 3B (Loader + Switcher + Installer)
- [ ] `Demo_Switcher::activate()` updates `template_pack` setting in WordPress options
- [ ] `Demo_Switcher::activate()` fires `phantom_demo_activated` event
- [ ] `Demo_Switcher::deactivate()` reverts to 'kids' default
- [ ] `Demo_Switcher::can_activate()` returns pass/fail with detailed checks
- [ ] `Demo_Installer::install()` extracts ZIP to correct directory
- [ ] `Demo_Installer::install()` validates `demo.json` before extraction
- [ ] `Demo_Installer::delete()` removes demo directory (safely, non-active only)
- [ ] `Demo_Loader::get_active_asset_url()` returns correct URL path

### Phase 3C (Admin UI)
- [ ] Admin menu page appears under "Phantom Core" or as top-level
- [ ] All installed demos listed with preview, name, version, status
- [ ] Activate button runs compatibility check then activates
- [ ] Deactivate button works on active demo
- [ ] Delete button works on inactive demos only
- [ ] ZIP upload form accepts and installs valid demo packs
- [ ] Error messages displayed for invalid ZIPs or failed installations
- [ ] All operations require `manage_options` capability
- [ ] All POST/AJAX operations have nonce verification

### Overall
- [ ] All PHP files pass `php -l` syntax check
- [ ] All existing unit tests still pass
- [ ] New unit tests pass (target: 20+ new tests)
- [ ] Zero PHP notices/warnings in debug log

---

## 9. Files to Create

| File | Purpose | Phase |
|------|---------|-------|
| `includes/Demo/class-demo-result.php` | Result value object (shared by Switcher, Installer) | 3A |
| `includes/Demo/class-demo-registry.php` | Demo discovery and caching | 3A |
| `includes/Demo/class-demo-contract.php` | Demo value object + validation | 3A |
| `includes/Demo/class-demo-loader.php` | Active demo resource loading | 3B |
| `includes/Demo/class-demo-switcher.php` | Demo activation/deactivation | 3B |
| `includes/Demo/class-demo-installer.php` | ZIP upload/extraction/delete | 3B |
| `admin/class-demo-admin.php` | WordPress admin UI page | 3C |
| `tests/Demo_Registry_Test.php` | Registry unit tests | 3A |
| `tests/Demo_Contract_Test.php` | Contract unit tests | 3A |
| `tests/Demo_Loader_Test.php` | Loader unit tests | 3B |
| `tests/Demo_Switcher_Test.php` | Switcher unit tests | 3B |
| `tests/Demo_Installer_Test.php` | Installer unit tests | 3B |

## 10. Files to Modify

| File | Change | Phase |
|------|--------|-------|
| `phantom-core.php` | Add `Demo\` prefix handler to autoloader (resolves `class-demo-*.php` in `includes/Demo/`); conditionally require `admin/class-demo-admin.php`; add `add_option('phantom_active_demo', 'kids')` on plugin activation | 3C |
| `includes/Engine/Container_Config.php` | Register Demo_Registry, Demo_Switcher, Demo_Loader services | 3B |
| `frontend/templates/fashion/demo.json` | Create demo.json for existing fashion pack | 3A |

---

## 11. Time Estimates

| Task | Est. Time |
|------|-----------|
| 3A: Demo_Registry + Demo_Contract + demo.json for fashion | 60 min |
| 3B: Demo_Switcher + Demo_Installer + Demo_Loader | 90 min |
| 3B: Container_Config service registration | 10 min |
| 3C: Admin UI page + AJAX handlers | 60 min |
| 3C: phantom-core.php admin page wiring | 10 min |
| Tests (all sub-phases) | 60 min |
| **Total** | **~4.5 hours** |

---

## 12. Revision History

| Date | Version | Changes |
|------|---------|---------|
| 2026-07-27 | 1.0 | Initial spec based on master plan + ChatGPT design input |
