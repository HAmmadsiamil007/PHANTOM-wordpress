# Phase 3: Asset Pipeline — Design Spec (v2)

## Architecture

```
Customizer → Visual Editor → Preview State → Diff Engine → iframe
                                                                      │
                                                                   Publish
                                                                      │
                                                                      ▼
                                                              Build Queue
                                                                      │
                                                                      ▼
                                                              Asset Pipeline
                                                                      │
                                            ┌─────────────────────────┼─────────────────────────┐
                                            │                         │                         │
                                            ▼                         ▼                         ▼
                                      CSS Compiler              JS Compiler           Font / Image / Icon
                                            │                    (future)                   (future)
                                            │
                                ┌───────────┼───────────┐
                                │           │           │
                                ▼           ▼           ▼
                       CSS Optimizer   Version Mgr   Manifest
                                │           │           │
                                └───────────┴───────────┘
                                            │
                                            ▼
                                   Cache Manager
                                            │
                                            ▼
                              uploads/phantom/{css,js,fonts,images}/
                              manifest.json
                                            │
                                            ▼
                                      Frontend
```

---

## 1. Asset Pipeline (not "CSS Engine")

Future-ready to handle CSS, JS, Fonts, Icons, SVG sprites, Theme JSON, Critical CSS, Responsive Images.

```
includes/Asset/
├── Pipeline/
│   ├── class-pipeline.php          — orchestrator
│   └── class-build-queue.php       — async build request queue
├── CSS/
│   ├── class-compiler.php          — reads resolved theme, compiles CSS
│   ├── class-variable-generator.php  — creates `--var` declarations only
│   ├── class-rule-builder.php      — creates `.selector { property: var(--x); }`
│   └── class-optimizer.php         — merge, deduplicate, sort, media combine
├── JS/
├── Font/
├── Image/
├── class-version-manager.php       — hash-based versioning (CSS+theme+preset+registry)
├── class-manifest.php              — manifest.json read/write
├── class-cache-manager.php         — file lifecycle, enqueue, cleanup (keep last 10)
└── class-dependency-graph.php      — component→asset dependency mapping
```

---

## 2. CSS Variable Generator → Rule Builder split

**Variable Generator** — pure function: given resolved tokens, emit:
```css
--hero-bg: #1a1a2e;
--hero-title: 3.5rem;
--button-radius: 8px;
```

**Rule Builder** — takes component manifest + variable declarations → emits:
```css
.hero { background: var(--hero-bg); }
.hero__title { font-size: var(--hero-title); }
.btn { border-radius: var(--button-radius); }
```

**Compiler** — orchestrates both:
1. Read resolved theme from Theme State Engine
2. Variable Generator emits declarations per component
3. Rule Builder wraps them in selectors
4. State overrides → pseudo-class blocks
5. Viewport overrides → `@media` blocks
6. Passes raw CSS to Optimizer

---

## 3. Dependency Graph

Every component declares its asset dependencies:

```php
// In component manifest
'dependencies' => [
    'css'  => ['base', 'hero', 'buttons'],
    'anim' => ['scroll-reveal'],
    'font' => ['inter'],
]
```

Compiler only builds what exists. If no WooCommerce component registered, `woocommerce.css` is not generated.

---

## 4. Compiler reads Theme State Engine, NOT ComponentInstance directly

```
Theme State Engine
        ↓
Resolved Theme (normalized array of all tokens, overrides, states, viewports)
        ↓
CSS Compiler
```

The Theme State Engine aggregates:
- ComponentInstance overrides
- State overrides
- Viewport overrides
- Design tokens
- Preset data
- Demo pack overrides
- Plugin overrides

Compiler receives a single `ResolvedTheme` object. It doesn't know where data came from.

---

## 5. Multi-file output

```
uploads/phantom/css/
├── theme.css          — core variables + base selectors
├── components.css     — component-specific rules
├── woocommerce.css    — WooCommerce overrides (only if WC active + components exist)
├── animations.css     — animation keyframes + scroll-reveal
├── responsive.css     — all @media blocks
├── critical.css       — above-fold CSS
└── manifest.json      — build manifest
```

Future: parallel generation per file, then concatenation in production profile.

---

## 6. manifest.json

```json
{
  "version": "a91f23",
  "build": 142,
  "date": "2026-07-29T21:00:00Z",
  "checksum": "sha256-...",
  "profile": "production",
  "css": {
    "theme": "theme-a91f23.css",
    "components": "components-a91f23.css",
    "woocommerce": "woocommerce-a91f23.css",
    "animations": "animations-a91f23.css",
    "responsive": "responsive-a91f23.css",
    "critical": "critical-a91f23.css"
  },
  "js": {},
  "fonts": [],
  "images": {}
}
```

Industry-standard single manifest for all assets.

---

## 7. CSS Optimizer (always included)

Responsibilities:
- Merge adjacent identical `@media` blocks
- Deduplicate identical rules across components
- Remove unreferenced CSS variables (dead var elimination)
- Sort property order consistently
- Combine `@media` queries by breakpoint
- Compression (strip whitespace/comments)

---

## 8. Build Queue

```
Publish / Import Demo / Change Preset / Plugin Request
        │
        ▼
Build_Queue::request('publish', ['profile' => 'production'])
Build_Queue::request('import_demo', ['demo' => 'fashion'])
        │
        ▼
Pipeline::process_next() — sequential, FIFO, dedup pending same type
```

Prevents stampedes when multiple events request builds simultaneously.

---

## 9. Hash-based versioning

```php
$hash = md5($css_content . PHANTOM_CORE_VERSION . $active_preset . $component_registry_version);
```

Never timestamp. If nothing changed, hash is identical → no rebuild needed. Cache invalidation by hash change only.

---

## 10. Preview Diff Engine

JS sends only changed vars to iframe:
```js
// Before: resends ALL 300 variables
// After:
VC.pendingChanges = { '--btn-bg': '#ff0000' };
VC.previewFrame.postMessage({ type: 'vc-apply-css', cssVars: VC.pendingChanges });
```

Already done in current visual-customizer.js. Verified.

---

## 11. Asset Pipeline orchestrator

```
Pipeline
├── queue (Build_Queue)
├── process (reads queue, delegates to type-specific compiler)
├── CSS → CSS_Compiler → Optimizer → Version_Manager → Manifest → Cache_Manager
├── JS  → JS_Compiler (future)
├── Font → Font_Manager (future)
└── Image → Image_Optimizer (future)
```

---

## 12. Build Profiles

```
development  — no minify, no combine, fast rebuild, debug comments
production   — minify, optimize, combine where possible, gzip
export       — full package + ZIP + manifest for distribution
```

Profile passed to all stages (Compiler, Optimizer, Version_Manager, Cache_Manager).

---

## 13. Rollback

```
Admin UI: Build History → Select build → "Activate"
        ↓
Cache_Manager::activate_version('theme-b44c89')
        ↓
manifest.json updated → frontend loads previous version
```

No manual JSON editing. Clean abstracted API.
Old builds retained (last 10) for rollback. Expire builds beyond 10.

---

## 14. AI Extension Points

Hooks/filters for future plugins:

```php
apply_filters('phantom/asset/compiled_css', $css, $resolved_theme);
apply_filters('phantom/asset/optimized_css', $css);
apply_filters('phantom/asset/manifest', $manifest);
apply_filters('phantom/asset/critical_css', $critical_css);
```

Allows AI optimizer plugin, unused CSS removal, accessibility overlay CSS injection without core changes.

---

## File Changes Summary

### New files:
```
includes/Asset/
├── Pipeline/
│   ├── class-pipeline.php
│   └── class-build-queue.php
├── CSS/
│   ├── class-compiler.php
│   ├── class-variable-generator.php
│   ├── class-rule-builder.php
│   └── class-optimizer.php
├── class-dependency-graph.php
├── class-version-manager.php
├── class-manifest.php
└── class-cache-manager.php
```

### Modified files:
```
phantom-core.php                          — init Asset Pipeline, define uploads dir
includes/class-rest-controller.php         — publish endpoint, build status endpoint, build history
includes/Inspector/class-state-manager.php — already has breakpoints + states
```

### State_Manager retained as-is — it owns **editing context** (current state/viewport for inspector). Theme State Engine (new in this phase) owns **resolved theme** for compilation.

---

## Pipeline Execution Flow

```
[Publish clicked]
  → REST POST /publish (triggers build)
  → Build_Queue::request('publish', ['profile' => 'production'])
  → (async or inline) Pipeline::process_next()
      → CSS_Compiler::compile($resolved_theme, $profile)
          → Variable_Generator::generate($tokens) → CSS var strings
          → Rule_Builder::build($component_manifest, $var_strings) → scoped rules
          → Inject state/viewport overrides via pseudo-classes + @media
          → Return raw CSS (may be multi-section: theme/components/woocommerce/animations/responsive)
      → CSS_Optimizer::optimize($raw_css, $profile)
          → Merge @media, deduplicate, sort, minify
          → Return optimized CSS (per section)
      → Version_Manager::version($optimized_css)
          → hash = md5($css . PHANTOM_CORE_VERSION . $preset . $registry_version)
          → If hash same as active → skip write (no changes)
          → Else write versioned files
      → Manifest::update($manifest)
          → Write manifest.json
      → Cache_Manager::cleanup()
          → Delete builds beyond last 10
  → Return { success: true, version: 'a91f23', url: '...' }
```

---

## Frontend Enqueue

```php
// wp_enqueue_scripts
$manifest = Manifest::get_active();
foreach ($manifest['css'] as $handle => $file) {
    if (file_exists($file['path'])) {
        wp_enqueue_style("phantom-{$handle}", $file['url'], [], $manifest['version']);
    }
}
```

Preview page uses inline `<style id="phantom-preview-css">` instead.

---

## Success Criteria

- [x] Preview editing instant — no blocking I/O, only JS var swap
- [x] Publish generates versioned CSS files
- [x] State overrides → `:hover`, `:focus`, `:active`, `:disabled` selectors
- [x] Viewport overrides → `@media (max-width: Npx)` blocks matching breakpoints
- [x] Multi-file output (theme, components, woocommerce, animations, responsive)
- [x] manifest.json tracks all asset versions
- [x] Hash-based versioning — no rebuild if CSS unchanged
- [x] Optimizer always active — merge, deduplicate, sort, minify
- [x] Build queue prevents stampedes
- [x] Rollback via build history
- [x] Keep last 10 builds, auto cleanup
- [x] AI extension points via filters
- [x] Build profiles (dev / production / export)
- [x] All 138+ PHP files pass syntax check
- [x] Serena memory updated
