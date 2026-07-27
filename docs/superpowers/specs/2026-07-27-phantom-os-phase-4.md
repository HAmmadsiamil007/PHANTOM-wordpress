# Phase 4: Design System Engine — Specification

**Goal:** Build a complete Design System Engine that makes every visual aspect of Phantom token-driven. Changing from a Luxury store to a Minimal store becomes a single preset switch instead of editing hundreds of CSS values.

**Architecture:** 11 classes across 3 layers (Token System → Preset System → Presentation), wrapped by a single `DesignSystem` facade, backed by the existing Settings Registry as the single source of truth, with a 14-page Phantom admin menu and Customizer quick-edit.

**Tech Stack:** PHP 8.0+, WordPress Options API, WordPress Customizer API, vanilla JS, CSS3

---

## Table of Contents

1. [Architecture Overview](#1-architecture-overview)
2. [Class Specifications](#2-class-specifications)
3. [Token Schema](#3-token-schema)
4. [Preset Format](#4-preset-format)
5. [Theme DNA Model](#5-theme-dna-model)
6. [Foundation Presets](#6-foundation-presets)
7. [Admin UI](#7-admin-ui)
8. [Customizer Integration](#8-customizer-integration)
9. [Export / Import](#9-export--import)
10. [Integration Points](#10-integration-points)
11. [Sub-Phase Breakdown](#11-sub-phase-breakdown)
12. [Testing Strategy](#12-testing-strategy)
13. [Verification](#13-verification)
14. [File Manifest](#14-file-manifest)

---

## 1. Architecture Overview

```
                    Options API
                        │
                        ▼
              Settings Registry (564 settings)
               (Single Source of Truth)
                        │
                        ▼
─────────────────────────────────────────────────
  TOKEN SYSTEM
─────────────────────────────────────────────────
                    │
                    ▼
               Token Resolver
          Settings → Semantic Token Values
                    │
                    ▼
              Token Validator
          Type check, existence, version
                    │
                    ▼
              Token Compiler
          Optimize into compiled set
                    │
                    ▼
              Token Registry
          Read-only catalog of ALL tokens
                    │
                    ▼
─────────────────────────────────────────────────
  PRESET SYSTEM
─────────────────────────────────────────────────
                    │
                    ▼
              Preset Registry
          Discovers presets from providers
                    │
                    ▼
              Preset Manager
          Apply, save, delete, export, import
                    │
                    ▼
              Theme DNA Engine
          DNA settings → token overrides
                    │
                    ▼
─────────────────────────────────────────────────
  PRESENTATION
─────────────────────────────────────────────────
                    │
                    ▼
            CSS Variable Generator
          :root vars + component-scoped
                    │
                    ▼
         phantom_dynamic_css filter
              (priority 2)
                    │
                    ▼
              Render Engine
                    │
                    ▼
              Premium Frontend
          (uses ONLY var(--token-name))
```

### 1.1 Data Flow — Preset Apply

```
User clicks "Luxury"
        │
        ▼
PresetManager::apply('luxury')
        │
        ▼
CoreProvider::get_preset('luxury')
   Returns: ['tokens' => [...], 'dna' => [...], ...]
        │
        ▼
For each token in preset:
  update_option('phantom_' . $key, $value)
        │
        ▼
Settings Registry now has new values
        │
        ▼
TokenResolver reads new settings
        │
        ▼
TokenValidator validates
        │
        ▼
TokenCompiler compiles
        │
        ▼
CSSVariableGenerator generates new CSS
        │
        ▼
phantom_dynamic_css filter fires
        │
        ▼
Frontend HTML gets new <style id="phantom-design-tokens">
        │
        ▼
All var(--color-primary), var(--space-xl), etc. update instantly
```

### 1.2 Data Flow — Token Read

```
Component needs color.primary
        │
        ▼
DesignSystem::token('color.primary')
        │
        ▼
TokenResolver::resolve('color.primary')
        │
        ▼
Looks up token in TokenRegistry:
  - Finds: 'color.primary' → maps to setting 'phantom_primary_color'
  - Reads: get_option('phantom_primary_color', '#C1121F')
  - Applies inheritance: resolves {color.secondary} references
        │
        ▼
Returns: '#C1121F'
```

### 1.3 DesignSystem Facade API

```php
// Single public interface for all of Phantom
DesignSystem::token(string $name): mixed              // Get a single token value
DesignSystem::tokens(?array $categories = null): array // Get multiple tokens
DesignSystem::cssVar(string $name): string             // Get CSS var name for token
DesignSystem::allCssVars(): array                      // Get all CSS vars map
DesignSystem::applyPreset(string $id): bool            // Apply a preset
DesignSystem::currentPreset(): ?array                  // Get active preset info
DesignSystem::availablePresets(): array                // List all available presets
DesignSystem::exportPreset(string $id): string         // Export preset as JSON
DesignSystem::importPreset(string $json): bool         // Import preset from JSON
DesignSystem::generateCSS(): string                    // Generate full CSS string
DesignSystem::currentThemeDNA(): array                 // Get current DNA settings
DesignSystem::validate(): array                        // Validate all tokens
DesignSystem::compile(): CompiledTokenSet              // Get compiled tokens
```

---

## 2. Class Specifications

### 2.1 DesignSystemManager (Facade)

**File:** `includes/Design/class-design-system-manager.php`
**Namespace:** `PhantomCore\Design`

The orchestrator. Coordinates all subsystems. Does not contain domain logic itself.

```php
class DesignSystemManager {
    private TokenRegistry $registry;
    private TokenResolver $resolver;
    private TokenValidator $validator;
    private TokenCompiler $compiler;
    private CSSVariableGenerator $cssGenerator;
    private PresetManager $presetManager;
    private PresetRegistry $presetRegistry;
    private ThemeDNAEngine $dnaEngine;

    // Delegates to subsystems
    public function token(string $name): mixed;
    public function tokens(?array $categories = null): array;
    public function cssVar(string $name): string;
    public function applyPreset(string $id): bool;
    public function currentPreset(): ?array;
    public function availablePresets(): array;
    public function exportPreset(string $id): string;
    public function importPreset(string $json): bool;
    public function generateCSS(): string;
    public function currentThemeDNA(): array;
    public function validate(): array;
    public function compile(): CompiledTokenSet;
}
```

**Implementation notes:**
- `DesignSystem::token()` is a static alias that calls `DesignSystemManager::get_instance()->token()`
- Uses singleton pattern (consistent with existing `Settings_Registry::get_instance()`)
- All subsystems injected via constructor (DI-ready for Container_Config)

### 2.2 TokenRegistry

**File:** `includes/Design/class-token-registry.php`
**Namespace:** `PhantomCore\Design`

Read-only catalog of all available tokens. Defines what tokens exist, their types, defaults, and CSS var names.

**Responsibilities:**
- Define ALL tokens via `get_all()` method
- Provide token metadata (name, category, type, default, css_var, description)
- Map token names to WP option keys
- Provide CSS var naming convention

```php
class TokenRegistry {
    public function get_all(): array;     // All token definitions
    public function get(string $name): ?array;  // Single token definition
    public function get_by_category(string $category): array;
    public function has(string $name): bool;
    public function get_css_var(string $name): string;
    public function get_option_key(string $name): string;
    public function get_default(string $name): mixed;
}
```

**Token definition structure:**
```php
[
    'name'        => 'color.primary',
    'category'    => 'color',
    'type'        => 'color',
    'default'     => '#C1121F',
    'css_var'     => '--color-primary',
    'option_key'  => 'phantom_primary_color',
    'description' => 'Primary brand color',
    'inherits'    => null,        // or 'color.secondary' for dependencies
    'depends_on'  => [],          // Setting keys this token reads
    'group'       => 'branding',  // Settings Registry section
    'validate'    => 'is_color',  // Validator function hint
    'version'     => '1.0.0',
]
```

**Token naming convention:**
- Format: `{category}.{subcategory}.{name}`
- Examples: `color.primary`, `typography.heading.font`, `spacing.section.padding`, `shadow.card`
- Max 3 levels deep

**CSS var naming convention:**
- Format: `--{category}-{subcategory}-{name}`
- Examples: `--color-primary`, `--typography-heading-font`, `--spacing-section-padding`
- Auto-generated from dot-notation token name

**All tokens are defined as a static PHP array in `get_all()`.**
No database reads. No file I/O for token definitions.

### 2.3 TokenResolver

**File:** `includes/Design/class-token-resolver.php`
**Namespace:** `PhantomCore\Design`

Reads Settings Registry options and resolves them to semantic token values.

**Responsibilities:**
- Read WP option for each token
- Apply default if option is empty
- Resolve token inheritance chains (tokens referencing other tokens)
- Handle type casting (string→int, hex validation, etc.)

```php
class TokenResolver {
    private TokenRegistry $registry;

    public function resolve(string $name): mixed;
    public function resolveAll(?array $names = null): array;
    public function resolveCategory(string $category): array;

    // Resolve inheritance: {token.name} → actual value
    private function resolveInheritance(mixed $value): mixed;
}
```

**Inheritance syntax:**
- A token's value can reference another token using `{token.name}` syntax
- Example: `button.background` → `'{color.primary}'` → resolved to `#C1121F`
- Resolution is recursive (max depth 5 to prevent infinite loops)
- Circular reference detection (throws `TokenInheritanceException`)

**Type casting rules:**
| Type | Casting |
|------|---------|
| `color` | Validate hex/rgba/hsl, return normalized |
| `size` | Append `px` if numeric-only |
| `font_size` | Append `rem` if numeric-only |
| `duration` | Append `ms` if numeric-only |
| `shadow` | Return as CSS shadow string |
| `font_family` | Return as quoted CSS font stack |
| `boolean` | Cast to '0'/'1' |
| `select` | Return raw string value |

### 2.4 TokenValidator

**File:** `includes/Design/class-token-validator.php`
**Namespace:** `PhantomCore\Design`

Validates tokens before they're compiled and output as CSS.

**Responsibilities:**
- Check token exists in registry
- Validate type correctness (color is valid hex, size is numeric, etc.)
- Check for missing values (empty after resolution)
- Detect deprecated tokens
- Version compatibility checks
- Report all issues as structured errors

```php
class TokenValidator {
    private TokenRegistry $registry;
    private TokenResolver $resolver;

    public function validate(?string $name = null): ValidationResult;
    public function validateAll(): array; // Array of ValidationResult
    public function isHealthy(): bool;     // No critical errors

    private function validateColor(string $value): ?string;
    private function validateSize(mixed $value): ?string;
    private function validateFontFamily(mixed $value): ?string;
    private function validateShadow(string $value): ?string;
}
```

**ValidationResult structure:**
```php
[
    'token'    => 'color.primary',
    'status'   => 'ok' | 'warning' | 'error',
    'message'  => '...',
    'context'  => ['setting' => 'phantom_primary_color', 'value' => '#xyz'],
]
```

### 2.5 TokenCompiler

**File:** `includes/Design/class-token-compiler.php`
**Namespace:** `PhantomCore\Design`

Compiles resolved tokens into an optimized array ready for CSS generation.

**Responsibilities:**
- Take resolved token values
- Apply Theme DNA overrides
- Generate semantic CSS var names from dot-notation tokens
- Flatten token group values (component tokens → individual CSS vars)
- Cache compiled result

```php
class TokenCompiler {
    private TokenRegistry $registry;
    private TokenResolver $resolver;
    private TokenValidator $validator;
    private ThemeDNAEngine $dnaEngine;

    public function compile(): CompiledTokenSet;
    public function compileCategory(string $category): array;
    public function invalidateCache(): void;
}

class CompiledTokenSet {
    public array $tokens;      // ['color.primary' => '#C1121F', ...]
    public array $cssVars;     // ['--color-primary' => '#C1121F', ...]
    public array $components;  // Component-scoped vars
    public array $responsive;  // Responsive var variants
    public string $css;        // Pre-rendered CSS string (optional cache)
}
```

### 2.6 CSSVariableGenerator

**File:** `includes/Design/class-css-variable-generator.php`
**Namespace:** `PhantomCore\Design`

Takes compiled tokens and generates CSS output. Very small class — single responsibility.

**Responsibilities:**
- Generate `:root { ... }` block with all CSS vars
- Optionally generate component-scoped blocks
- Output responsive var variants via `@media` queries
- Plugs into `phantom_dynamic_css` filter at priority 2

```php
class CSSVariableGenerator {
    private TokenCompiler $compiler;

    public function generate(): string;
    public function generateRoot(): string;
    public function generateComponent(string $component): string;
    public function generateResponsive(): string;
    public function getOutputHook(): callable; // Returns closure for phantom_dynamic_css
}
```

**Output format:**
```css
:root {
    /* Colors */
    --color-primary: #C1121F;
    --color-secondary: #2D2D2D;
    /* ... */

    /* Spacing */
    --space-xs: 4px;
    --space-sm: 8px;
    --space-md: 16px;
    --space-lg: 32px;
    --space-xl: 64px;
    /* ... */

    /* Shadows */
    --shadow-sm: 0 1px 2px rgba(0,0,0,0.05);
    --shadow-md: 0 4px 6px rgba(0,0,0,0.1);
    /* ... */
}

/* Component-scoped example */
.phantom-product-card {
    --product-card-radius: var(--radius-md);
    --product-card-shadow: var(--shadow-sm);
}
```

**Note on backward compatibility:**
The CSS Variable Generator outputs ONLY new semantic var names (e.g., `--color-primary`). It does NOT generate backward-compatible aliases like `--primary--color` — those are still generated by the existing CSS module files (`colors.php`, `typography.php`, etc.) at their current priorities. This avoids conflicts and ensures existing components continue to work unchanged. In a future phase, the existing modules can be refactored to reference the new token vars, at which point aliases can be introduced.

### 2.7 PresetManager

**File:** `includes/Design/class-preset-manager.php`
**Namespace:** `PhantomCore\Design`

CRUD operations on presets. Does NOT discover presets — that's PresetRegistry's job.

**Responsibilities:**
- Apply preset (write all token values to Settings Registry + set DNA)
- Save current configuration as a new user preset
- Delete user preset
- Duplicate preset
- Get current active preset info
- Export preset as JSON (delegates to Exporter)
- Import preset from JSON (delegates to Importer)

```php
class PresetManager {
    private PresetRegistry $presetRegistry;
    private TokenRegistry $tokenRegistry;

    public function apply(string $id): bool;
    public function save(string $name, ?array $overrides = null): string; // Returns preset ID
    public function delete(string $id): bool;
    public function duplicate(string $id, string $newName): ?string;
    public function current(): ?string;  // Active preset ID
    public function reset(): bool;       // Reset to Light preset
}
```

**Preset apply flow:**
1. Get preset data from PresetRegistry
2. Validate preset version against framework version
3. For each token in preset → `update_option($optionKey, $value)`
4. Set Theme DNA settings → `update_option('phantom_theme_dna', $dna)`
5. Store active preset ID → `update_option('phantom_active_preset', $id)`
6. Invalidate CSS cache
7. Return true

### 2.8 PresetRegistry

**File:** `includes/Design/class-preset-registry.php`
**Namespace:** `PhantomCore\Design`

Discovers and aggregates presets from all providers.

**Responsibilities:**
- Collect presets from all registered providers
- Merge with correct priority (User > Demo > Core)
- Provide single `get_all()` / `get($id)` interface
- Handle naming collisions (prefix IDs with provider namespace)

```php
class PresetRegistry {
    private array $providers = [];

    public function registerProvider(PresetProviderInterface $provider): void;
    public function get_all(): array;    // All presets, merged
    public function get(string $id): ?Preset;
    public function has(string $id): bool;
    public function getBySource(string $source): array; // 'core' | 'demo' | 'user'
}
```

**Preset ID format:** `{source}:{id}` — e.g., `core:luxury`, `demo:kids:bright`, `user:my-brand`

### 2.9 PresetProviderInterface

**File:** `includes/Design/Providers/interface-preset-provider.php`
**Namespace:** `PhantomCore\Design\Providers`

```php
interface PresetProviderInterface {
    public function get_presets(): array;   // All presets from this source
    public function get_preset(string $id): ?Preset;
    public function exists(string $id): bool;
    public function source(): string;       // 'core' | 'demo' | 'user'
}
```

### 2.10 CoreProvider

**File:** `includes/Design/Providers/class-core-provider.php`
**Namespace:** `PhantomCore\Design\Providers`

Provides 7 foundation presets as PHP arrays. Fastest provider — no I/O.

**Presets defined in:** `includes/Design/data/presets.php` (separate data file for maintainability)

```php
class CoreProvider implements PresetProviderInterface {
    public function get_presets(): array {
        return [
            'core:light'   => Preset::from_array([...]),
            'core:dark'    => Preset::from_array([...]),
            'core:minimal' => Preset::from_array([...]),
            'core:modern'  => Preset::from_array([...]),
            'core:luxury'  => Preset::from_array([...]),
            'core:classic' => Preset::from_array([...]),
            'core:glass'   => Preset::from_array([...]),
        ];
    }
    // ...
}
```

### 2.11 DemoProvider

**File:** `includes/Design/Providers/class-demo-provider.php`
**Namespace:** `PhantomCore\Design\Providers`

Scans active demo pack for JSON preset files in `frontend/templates/{slug}/presets/*.json`.

```php
class DemoProvider implements PresetProviderInterface {
    private string $demoSlug;

    public function get_presets(): array {
        $dir = PHANTOM_CORE_PATH . 'frontend/templates/' . $this->demoSlug . '/presets/';
        if (!is_dir($dir)) return [];
        // Scan *.json, decode, return as Preset objects
    }
}
```

### 2.12 UserProvider

**File:** `includes/Design/Providers/class-user-provider.php`
**Namespace:** `PhantomCore\Design\Providers`

Stores user-saved presets in a single WP option: `phantom_user_presets`.

```php
class UserProvider implements PresetProviderInterface {
    public function get_presets(): array {
        $data = get_option('phantom_user_presets', []);
        // Convert array data to Preset objects
    }
    public function save(Preset $preset): bool {
        $presets = get_option('phantom_user_presets', []);
        $presets[$preset->id] = $preset->to_array();
        return update_option('phantom_user_presets', $presets);
    }
    public function delete(string $id): bool {
        $presets = get_option('phantom_user_presets', []);
        unset($presets[$id]);
        return update_option('phantom_user_presets', $presets);
    }
}
```

### 2.13 ImportProvider ⚠ (Phase 4D)

**File:** `includes/Design/Providers/class-import-provider.php`
**Namespace:** `PhantomCore\Design\Providers`

Temporarily provides a preset imported from a JSON file (not persisted). Used during the import workflow.

### 2.14 ThemeDNAEngine

**File:** `includes/Design/class-theme-dna-engine.php`
**Namespace:** `PhantomCore\Design`

Defines and resolves Theme DNA — the "personality" dimensions that give each preset its character.

**Responsibilities:**
- Define DNA dimensions and their possible values
- Store current DNA settings in `phantom_theme_dna` option
- Apply DNA overrides to token values
- Provide DNA-aware token resolution

```php
class ThemeDNAEngine {
    public function getDimensions(): array;  // All DNA dimensions + options
    public function getCurrent(): array;     // Current DNA settings
    public function set(array $dna): bool;   // Update DNA settings
    public function applyOverrides(array $tokens): array; // Apply DNA → token overrides
    public function getStyle(string $dimension): string;   // Get single dimension value
}
```

**DNA Dimensions:**
| Dimension | Values | Description |
|-----------|--------|-------------|
| `design_style` | `luxury`, `modern`, `minimal`, `classic`, `playful` | Overall design direction |
| `motion_style` | `elegant`, `fast`, `dynamic`, `subtle` | Animation personality |
| `shape_style` | `sharp`, `rounded`, `pill` | Corner roundness preference |
| `typography_style` | `serif`, `sans`, `display` | Font personality |
| `elevation_style` | `flat`, `soft`, `floating`, `glass` | Shadow/depth style |
| `color_style` | `monochrome`, `vibrant`, `pastel`, `neutral` | Color treatment |

**DNA → Token mapping:**
Each DNA value maps to specific token overrides. Example — `shape_style: rounded`:

```php
[
    'radius.sm'  => '8px',
    'radius.md'  => '12px',
    'radius.lg'  => '16px',
    'radius.xl'  => '24px',
    'button.radius' => '12px',
    'card.radius'   => '16px',
]
```

### 2.15 Preset Value Object

**File:** `includes/Design/class-preset.php`
**Namespace:** `PhantomCore\Design`

```php
class Preset {
    public string $id;           // 'core:luxury'
    public string $name;         // 'Luxury'
    public string $source;       // 'core' | 'demo' | 'user'
    public string $version;      // '1.0.0'
    public string $framework;    // '^2.0'
    public string $author;       // 'Phantom Core'
    public array $tokens;        // ['color.primary' => '#000000', ...]
    public array $dna;           // ['design_style' => 'luxury', ...]
    public array $metadata;      // ['thumbnail' => '...', 'description' => '...']
    public ?string $parent;      // Parent preset ID for inheritance

    public static function from_array(array $data): self;
    public function to_array(): array;
    public function to_json(): string;
    public function merge(self $parent): self;  // Inherit from parent
    public function isCompatible(string $frameworkVersion): bool;
}
```

### 2.16 Preset Version Compatibility

The `framework` field uses semver constraints (same as Composer):
- `^2.0` = >=2.0, <3.0
- `~2.1` = >=2.1, <3.0
- `>=2.0` = >=2.0
- `*` = any

Use `composer/semver` if available, otherwise a simple `version_compare()` check.

---

## 3. Token Schema

### 3.1 Colors

| Token | CSS Var | Setting Key | Default | Type |
|-------|---------|-------------|---------|------|
| `color.primary` | `--color-primary` | `phantom_primary_color` | `#C1121F` | color |
| `color.secondary` | `--color-secondary` | `phantom_secondary_color` | `#2D2D2D` | color |
| `color.accent` | `--color-accent` | `phantom_accent_color` | `#705B53` | color |
| `color.success` | `--color-success` | `phantom_success_color` | `#2E7D32` | color |
| `color.warning` | `--color-warning` | `phantom_warning_color` | `#F9A825` | color |
| `color.danger` | `--color-danger` | `phantom_error_color` | `#D32F2F` | color |
| `color.info` | `--color-info` | `phantom_info_color` | `#0288D1` | color |
| `color.background` | `--color-bg` | `phantom_bg_color` | `#FFFFFF` | color |
| `color.surface` | `--color-surface` | `phantom_light_bg_color` | `#F5F5F5` | color |
| `color.surface.alt` | `--color-surface-alt` | `phantom_grey_color` | `#E5E5E5` | color |
| `color.text.primary` | `--color-text-primary` | `phantom_text_color` | `#333333` | color |
| `color.text.secondary` | `--color-text-secondary` | `phantom_heading_color` | `#666666` | color |
| `color.border` | `--color-border` | `phantom_border_color` | `#E5E5E5` | color |
| `color.divider` | `--color-divider` | (border_color) | `#E0E0E0` | color |
| `color.overlay` | `--color-overlay` | (new) | `rgba(0,0,0,0.5)` | color |
| `color.gradient.primary` | `--color-gradient-start` | `phantom_gradient_start_color` | `#C1121F` | color |
| `color.gradient.secondary` | `--color-gradient-end` | `phantom_gradient_end_color` | `#8B0000` | color |
| `color.link` | `--color-link` | `phantom_link_color` | `#C1121F` | color |
| `color.link.hover` | `--color-link-hover` | `phantom_link_hover_color` | `#8B0000` | color |
| `color.hero.bg` | `--color-hero-bg` | (new) | `#F5F3F2` | color |
| `color.card.bg` | `--color-card-bg` | `phantom_product_card_bg` | `#FFFFFF` | color |
| `color.card.text` | `--color-card-text` | `phantom_product_card_text` | `#333333` | color |
| `color.card.border` | `--color-card-border` | `phantom_product_card_border` | `#E5E5E5` | color |
| `color.footer.bg` | `--color-footer-bg` | `phantom_color_footer_bg` | `#222222` | color |
| `color.footer.text` | `--color-footer-text` | `phantom_footer_text` | `#FFFFFF` | color |
| `color.header.bg` | `--color-header-bg` | `phantom_color_header_bg` | `#FFFFFF` | color |
| `color.header.text` | `--color-header-text` | `phantom_header_text_color` | `#222222` | color |
| `color.topbar.bg` | `--color-topbar-bg` | `phantom_topbar_bg` | `#222222` | color |
| `color.topbar.text` | `--color-topbar-text` | `phantom_topbar_text` | `#FFFFFF` | color |
| `color.announcement.bg` | `--color-announcement-bg` | `phantom_announcement_bar_bg` | `#C1121F` | color |
| `color.announcement.text` | `--color-announcement-text` | `phantom_announcement_bar_text_color` | `#FFFFFF` | color |
| `color.sale` | `--color-sale` | `phantom_sale_color` | `#D32F2F` | color |
| `color.rating` | `--color-rating` | `phantom_woo_rating` | `#FFB800` | color |
| `color.button.bg` | `--color-button-bg` | `phantom_button_bg` | `#C1121F` | color |
| `color.button.text` | `--color-button-text` | `phantom_button_text` | `#FFFFFF` | color |
| `color.button.hover.bg` | `--color-button-hover-bg` | `phantom_button_hover_bg` | `#8B0000` | color |
| `color.button.hover.text` | `--color-button-hover-text` | `phantom_button_hover_text` | `#FFFFFF` | color |
| `color.badge.sale.bg` | `--color-badge-sale-bg` | `phantom_product_badge_sale_bg` | `#D32F2F` | color |
| `color.badge.sale.text` | `--color-badge-sale-text` | `phantom_product_badge_sale_text` | `#FFFFFF` | color |
| `color.badge.new.bg` | `--color-badge-new-bg` | `phantom_product_badge_new_bg` | `#2E7D32` | color |
| `color.badge.new.text` | `--color-badge-new-text` | `phantom_product_badge_new_text` | `#FFFFFF` | color |

### 3.2 Typography

| Token | CSS Var | Setting Key | Default | Type |
|-------|---------|-------------|---------|------|
| `typography.heading.font` | `--typography-heading-font` | `phantom_font_heading` | `'Playfair Display', serif` | font_family |
| `typography.heading.weight` | `--typography-heading-weight` | `phantom_font_heading_weight` | `700` | select |
| `typography.heading.case` | `--typography-heading-case` | `phantom_font_heading_case` | `none` | select |
| `typography.heading.spacing` | `--typography-heading-spacing` | `phantom_font_heading_spacing` | `0` | size |
| `typography.body.font` | `--typography-body-font` | `phantom_font_body` | `'Inter', sans-serif` | font_family |
| `typography.body.weight` | `--typography-body-weight` | `phantom_font_body_weight` | `400` | select |
| `typography.body.style` | `--typography-body-style` | `phantom_font_body_style` | `normal` | select |
| `typography.body.size` | `--typography-body-size` | `phantom_font_base_size` | `16px` | font_size |
| `typography.body.line_height` | `--typography-body-line-height` | `phantom_font_line_height` | `1.6` | unitless |
| `typography.body.spacing` | `--typography-body-spacing` | `phantom_font_body_spacing` | `0` | size |
| `typography.h1.size` | `--typography-h1-size` | `phantom_h1_size` | `48px` | font_size |
| `typography.h2.size` | `--typography-h2-size` | `phantom_h2_size` | `36px` | font_size |
| `typography.h3.size` | `--typography-h3-size` | `phantom_h3_size` | `28px` | font_size |
| `typography.h4.size` | `--typography-h4-size` | `phantom_h4_size` | `24px` | font_size |
| `typography.h5.size` | `--typography-h5-size` | `phantom_h5_size` | `20px` | font_size |
| `typography.h6.size` | `--typography-h6-size` | `phantom_h6_size` | `16px` | font_size |
| `typography.menu.font` | `--typography-menu-font` | `phantom_menu_font_size` | `14px` | font_size |
| `typography.button.font` | `--typography-button-font` | `phantom_button_font_size` | `14px` | font_size |
| `typography.price.font` | `--typography-price-font` | (heading font) | `'Playfair Display', serif` | font_family |
| `typography.logo.font` | `--typography-logo-font` | (heading font) | `'Playfair Display', serif` | font_family |
| `typography.hero.font` | `--typography-hero-font` | (heading font) | `'Playfair Display', serif` | font_family |
| `typography.caption.font` | `--typography-caption-font` | (body font) | `'Inter', sans-serif` | font_family |
| `typography.code.font` | `--typography-code-font` | (new) | `'Fira Code', monospace` | font_family |

**Font size scale:**
| Token | CSS Var | Default | Size |
|-------|---------|---------|------|
| `typography.scale.xs` | `--text-xs` | `12px` | font_size |
| `typography.scale.sm` | `--text-sm` | `14px` | font_size |
| `typography.scale.base` | `--text-base` | `16px` | font_size |
| `typography.scale.lg` | `--text-lg` | `20px` | font_size |
| `typography.scale.xl` | `--text-xl` | `24px` | font_size |
| `typography.scale.2xl` | `--text-2xl` | `32px` | font_size |
| `typography.scale.3xl` | `--text-3xl` | `48px` | font_size |
| `typography.scale.4xl` | `--text-4xl` | `64px` | font_size |

### 3.3 Spacing

| Token | CSS Var | Default | Type |
|-------|---------|---------|------|
| `space.xs` | `--space-xs` | `4px` | size |
| `space.sm` | `--space-sm` | `8px` | size |
| `space.md` | `--space-md` | `16px` | size |
| `space.lg` | `--space-lg` | `32px` | size |
| `space.xl` | `--space-xl` | `64px` | size |
| `space.xxl` | `--space-xxl` | `96px` | size |
| `spacing.section.padding_x` | `--spacing-section-padding-x` | `24px` | size |
| `spacing.section.padding_y` | `--spacing-section-padding-y` | `64px` | size |
| `spacing.container.gutter` | `--spacing-container-gutter` | `24px` | size |
| `spacing.content.gap` | `--spacing-content-gap` | `32px` | size |
| `spacing.element.margin` | `--spacing-element-margin` | `24px` | size |
| `spacing.widget` | `--spacing-widget` | `32px` | size |
| `spacing.grid.gap` | `--spacing-grid-gap` | `24px` | size |
| `spacing.grid.column_gap` | `--spacing-grid-column-gap` | `16px` | size |
| `spacing.grid.row_gap` | `--spacing-grid-row-gap` | `16px` | size |
| `spacing.card.padding` | `--spacing-card-padding` | `16px` | size |
| `spacing.button.padding_x` | `--spacing-button-padding-x` | `24px` | size |
| `spacing.button.padding_y` | `--spacing-button-padding-y` | `12px` | size |

### 3.4 Border Radius

| Token | CSS Var | Default | Type |
|-------|---------|---------|------|
| `radius.none` | `--radius-none` | `0` | size |
| `radius.sm` | `--radius-sm` | `4px` | size |
| `radius.md` | `--radius-md` | `8px` | size |
| `radius.lg` | `--radius-lg` | `16px` | size |
| `radius.xl` | `--radius-xl` | `24px` | size |
| `radius.full` | `--radius-full` | `9999px` | size |
| `radius.button` | `--radius-button` | `8px` | size |
| `radius.card` | `--radius-card` | `8px` | size |
| `radius.input` | `--radius-input` | `8px` | size |
| `radius.modal` | `--radius-modal` | `16px` | size |
| `radius.badge` | `--radius-badge` | `4px` | size |

### 3.5 Shadows

| Token | CSS Var | Default | Type |
|-------|---------|---------|------|
| `shadow.xs` | `--shadow-xs` | `0 1px 2px rgba(0,0,0,0.05)` | shadow |
| `shadow.sm` | `--shadow-sm` | `0 1px 3px rgba(0,0,0,0.1)` | shadow |
| `shadow.md` | `--shadow-md` | `0 4px 6px rgba(0,0,0,0.1)` | shadow |
| `shadow.lg` | `--shadow-lg` | `0 10px 15px rgba(0,0,0,0.1)` | shadow |
| `shadow.xl` | `--shadow-xl` | `0 20px 25px rgba(0,0,0,0.15)` | shadow |
| `shadow.card` | `--shadow-card` | `0 2px 4px rgba(0,0,0,0.08)` | shadow |
| `shadow.button` | `--shadow-button` | `0 2px 4px rgba(0,0,0,0.15)` | shadow |
| `shadow.dropdown` | `--shadow-dropdown` | `0 8px 16px rgba(0,0,0,0.15)` | shadow |
| `shadow.modal` | `--shadow-modal` | `0 20px 60px rgba(0,0,0,0.3)` | shadow |
| `shadow.nav` | `--shadow-nav` | `0 2px 4px rgba(0,0,0,0.05)` | shadow |

### 3.6 Motion / Animation

| Token | CSS Var | Default | Type |
|-------|---------|---------|------|
| `motion.duration.fast` | `--motion-duration-fast` | `150ms` | duration |
| `motion.duration.normal` | `--motion-duration-normal` | `300ms` | duration |
| `motion.duration.slow` | `--motion-duration-slow` | `500ms` | duration |
| `motion.easing.default` | `--motion-easing` | `cubic-bezier(0.4, 0, 0.2, 1)` | easing |
| `motion.easing.in` | `--motion-easing-in` | `cubic-bezier(0.4, 0, 1, 1)` | easing |
| `motion.easing.out` | `--motion-easing-out` | `cubic-bezier(0, 0, 0.2, 1)` | easing |
| `motion.easing.in_out` | `--motion-easing-in-out` | `cubic-bezier(0.4, 0, 0.2, 1)` | easing |
| `motion.delay` | `--motion-delay` | `0ms` | duration |
| `motion.stagger` | `--motion-stagger` | `50ms` | duration |
| `motion.spring` | `--motion-spring` | `spring(1, 100, 10, 0)` | string |
| `motion.bounce` | `--motion-bounce` | `cubic-bezier(0.68, -0.55, 0.27, 1.55)` | easing |

### 3.7 Layout

| Token | CSS Var | Setting Key | Default | Type |
|-------|---------|-------------|---------|------|
| `layout.container.width` | `--layout-container-width` | `phantom_container_width` | `1200px` | size |
| `layout.content.width` | `--layout-content-width` | `phantom_content_width` | `800px` | size |
| `layout.sidebar.width` | `--layout-sidebar-width` | `phantom_sidebar_width` | `320px` | size |
| `layout.boxed.width` | `--layout-boxed-width` | `phantom_boxed_width` | `1400px` | size |
| `layout.columns` | `--layout-columns` | `phantom_layout_columns` | `4` | number |
| `layout.header.height` | `--layout-header-height` | `phantom_header_height` | `80px` | size |
| `layout.header.mobile_height` | `--layout-header-mobile-height` | `phantom_header_mobile_height` | `60px` | size |
| `layout.banner.height` | `--layout-banner-height` | `phantom_banner_height` | `400px` | size |
| `layout.hero.height` | `--layout-hero-height` | (new) | `600px` | size |

### 3.8 3D / Effects

| Token | CSS Var | Default | Type |
|-------|---------|---------|------|
| `effect.tilt.intensity` | `--effect-tilt-intensity` | `10` | number |
| `effect.perspective` | `--effect-perspective` | `1000px` | size |
| `effect.depth` | `--effect-depth` | `100px` | size |
| `effect.blur.sm` | `--effect-blur-sm` | `4px` | size |
| `effect.blur.md` | `--effect-blur-md` | `8px` | size |
| `effect.blur.lg` | `--effect-blur-lg` | `16px` | size |
| `effect.opacity.normal` | `--effect-opacity-normal` | `1` | number |
| `effect.opacity.hover` | `--effect-opacity-hover` | `0.8` | number |
| `effect.opacity.disabled` | `--effect-opacity-disabled` | `0.5` | number |
| `effect.glow` | `--effect-glow` | `0 0 20px rgba(193,18,31,0.3)` | shadow |
| `effect.glass.reflection` | `--effect-glass-reflection` | `rgba(255,255,255,0.1)` | color |

### 3.9 Breakpoints / Responsive

| Token | CSS Var | Default | Type |
|-------|---------|---------|------|
| `breakpoint.sm` | `--breakpoint-sm` | `576px` | size |
| `breakpoint.md` | `--breakpoint-md` | `768px` | size |
| `breakpoint.lg` | `--breakpoint-lg` | `992px` | size |
| `breakpoint.xl` | `--breakpoint-xl` | `1200px` | size |
| `breakpoint.xxl` | `--breakpoint-xxl` | `1400px` | size |

### 3.10 Z-Index Scale

| Token | CSS Var | Default |
|-------|---------|---------|
| `z-index.dropdown` | `--z-dropdown` | `100` |
| `z-index.sticky` | `--z-sticky` | `200` |
| `z-index.fixed` | `--z-fixed` | `300` |
| `z-index.modal` | `--z-modal` | `400` |
| `z-index.popover` | `--z-popover` | `500` |
| `z-index.tooltip` | `--z-tooltip` | `600` |
| `z-index.toast` | `--z-toast` | `700` |
| `z-index.loader` | `--z-loader` | `800` |

### 3.11 Component Tokens

**Header:**
| Token | CSS Var | Default |
|-------|---------|---------|
| `component.header.background` | `--component-header-bg` | `{color.header.bg}` |
| `component.header.text` | `--component-header-text` | `{color.header.text}` |
| `component.header.transparency` | `--component-header-transparency` | `1` |
| `component.header.blur` | `--component-header-blur` | `0` |
| `component.header.border` | `--component-header-border` | `{color.border}` |
| `component.header.shadow` | `--component-header-shadow` | `{shadow.nav}` |

**Hero:**
| Token | CSS Var | Default |
|-------|---------|---------|
| `component.hero.overlay` | `--component-hero-overlay` | `{color.overlay}` |
| `component.hero.overlay_opacity` | `--component-hero-overlay-opacity` | `0.5` |
| `component.hero.content_width` | `--component-hero-content-width` | `600px` |
| `component.hero.animation` | `--component-hero-animation` | `fade-in` |

**Product Card:**
| Token | CSS Var | Default |
|-------|---------|---------|
| `component.product-card.background` | `--component-product-card-bg` | `{color.card.bg}` |
| `component.product-card.radius` | `--component-product-card-radius` | `{radius.card}` |
| `component.product-card.shadow` | `--component-product-card-shadow` | `{shadow.card}` |
| `component.product-card.padding` | `--component-product-card-padding` | `{spacing.card.padding}` |
| `component.product-card.hover_scale` | `--component-product-card-hover-scale` | `1.02` |
| `component.product-card.hover_shadow` | `--component-product-card-hover-shadow` | `{shadow.md}` |
| `component.product-card.title_size` | `--component-product-card-title-size` | `{typography.scale.base}` |
| `component.product-card.price_size` | `--component-product-card-price-size` | `{typography.scale.lg}` |

**Button:**
| Token | CSS Var | Default |
|-------|---------|---------|
| `component.button.background` | `--component-button-bg` | `{color.button.bg}` |
| `component.button.text` | `--component-button-text` | `{color.button.text}` |
| `component.button.radius` | `--component-button-radius` | `{radius.button}` |
| `component.button.padding_x` | `--component-button-padding-x` | `{spacing.button.padding_x}` |
| `component.button.padding_y` | `--component-button-padding-y` | `{spacing.button.padding_y}` |
| `component.button.font_size` | `--component-button-font-size` | `{typography.button.font}` |
| `component.button.shadow` | `--component-button-shadow` | `{shadow.button}` |
| `component.button.hover.background` | `--component-button-hover-bg` | `{color.button.hover.bg}` |
| `component.button.hover.text` | `--component-button-hover-text` | `{color.button.hover.text}` |
| `component.button.hover.shadow` | `--component-button-hover-shadow` | `{shadow.md}` |

### 3.12 Token Count Summary

| Category | Token Count |
|----------|-------------|
| Colors | 39 |
| Typography | 30 |
| Spacing | 17 |
| Border Radius | 11 |
| Shadows | 10 |
| Motion / Animation | 10 |
| Layout | 9 |
| 3D / Effects | 10 |
| Breakpoints | 5 |
| Z-Index | 8 |
| Component: Header | 6 |
| Component: Hero | 4 |
| Component: Product Card | 8 |
| Component: Button | 10 |
| **Total** | **~177** |

---

## 4. Preset Format

### 4.1 PHP Array Format (Core Presets)

```php
return [
    'id'          => 'core:luxury',
    'name'        => 'Luxury',
    'source'      => 'core',
    'version'     => '1.0.0',
    'framework'   => '>=1.5.0',
    'author'      => 'Phantom Core',
    'metadata'    => [
        'description' => 'Premium luxury aesthetic with gold accents and refined typography.',
        'thumbnail'   => PHANTOM_CORE_URL . 'admin/images/presets/luxury.jpg',
        'tags'        => ['premium', 'elegant', 'dark'],
        'category'    => 'sophisticated',
    ],
    'dna' => [
        'design_style'      => 'luxury',
        'motion_style'      => 'elegant',
        'shape_style'       => 'rounded',
        'typography_style'  => 'serif',
        'elevation_style'   => 'soft',
        'color_style'       => 'vibrant',
    ],
    'tokens' => [
        // Colors
        'color.primary'   => '#000000',
        'color.secondary' => '#D4AF37',
        'color.accent'    => '#8B7355',
        // ... all other token overrides

        // Typography
        'typography.heading.font' => "'Playfair Display', serif",
        'typography.body.font'    => "'Inter', sans-serif",

        // Spacing — luxury uses larger spacing
        'space.md'  => '24px',
        'space.lg'  => '48px',
        'space.xl'  => '96px',

        // Radius — more rounded
        'radius.md' => '12px',
        'radius.lg' => '24px',

        // Shadows — softer
        'shadow.sm' => '0 2px 8px rgba(0,0,0,0.08)',

        // Motion — slower, elegant
        'motion.duration.normal' => '400ms',
        'motion.easing.default'  => 'cubic-bezier(0.25, 0.46, 0.45, 0.94)',
    ],
];
```

### 4.2 JSON Format (Export / Import)

```json
{
    "id": "my-brand-preset",
    "name": "My Brand",
    "source": "user",
    "version": "1.0.0",
    "framework": ">=1.5.0",
    "author": "User",
    "metadata": {
        "description": "Custom brand preset",
        "tags": ["custom", "brand"],
        "exported": "2026-07-27",
        "origin": "phantom-core"
    },
    "dna": {
        "design_style": "modern",
        "motion_style": "fast",
        "shape_style": "sharp",
        "typography_style": "sans",
        "elevation_style": "flat",
        "color_style": "vibrant"
    },
    "tokens": {
        "color.primary": "#0055FF",
        "color.secondary": "#00CC66",
        "typography.heading.font": "'Inter', sans-serif",
        "space.lg": "40px",
        "radius.sm": "2px"
    },
    "parent": null
}
```

### 4.3 Demo Pack JSON Format

Each demo pack can optionally include a `presets/` directory. The DemoProvider scans:

```
frontend/templates/{slug}/presets/*.json
```

Each file contains a single preset in JSON format (same schema as 4.2). The `source` field is automatically set to `demo`.

Demo packs can also specify a `default_preset` in their `demo.json`:

```json
{
    "id": "fashion",
    "name": "Fashion",
    "version": "1.0.0",
    "default_preset": "core:luxury",
    ...
}
```

---

## 5. Theme DNA Model

### 5.1 Dimensions

| Dimension | Values | Effect on Tokens |
|-----------|--------|-----------------|
| `design_style` | `luxury`, `modern`, `minimal`, `classic`, `playful` | Overall token selection — colors, spacing, radii |
| `motion_style` | `elegant`, `fast`, `dynamic`, `subtle` | Duration, easing, stagger values |
| `shape_style` | `sharp`, `rounded`, `pill` | All radius tokens |
| `typography_style` | `serif`, `sans`, `display` | Font family selection |
| `elevation_style` | `flat`, `soft`, `floating`, `glass` | Shadow tokens, transparency, blur |
| `color_style` | `monochrome`, `vibrant`, `pastel`, `neutral` | Saturation/brightness modifiers |

### 5.2 DNA → Token Mapping Table

Each DNA value maps to specific token overrides. These are defined in ThemeDNAEngine.

**shape_style: rounded →**
| Token | Value |
|-------|-------|
| `radius.sm` | `8px` |
| `radius.md` | `12px` |
| `radius.lg` | `20px` |
| `radius.xl` | `32px` |
| `radius.button` | `12px` |
| `radius.card` | `16px` |
| `radius.input` | `12px` |
| `radius.badge` | `8px` |

**shape_style: sharp →**
| Token | Value |
|-------|-------|
| `radius.sm` | `0` |
| `radius.md` | `0` |
| `radius.lg` | `0` |
| `radius.xl` | `0` |
| `radius.button` | `0` |
| `radius.card` | `0` |
| `radius.input` | `0` |
| `radius.badge` | `0` |

**elevation_style: glass →**
| Token | Value |
|-------|-------|
| `effect.glass.reflection` | `rgba(255,255,255,0.15)` |
| `effect.blur.md` | `12px` |
| `component.header.transparency` | `0.8` |
| `component.header.blur` | `10px` |
| `shadow.card` | `0 8px 32px rgba(0,0,0,0.1)` |

### 5.3 DNA Application Order

1. Foundation preset tokens applied (highest priority)
2. DNA style overrides applied (lower priority — refine, not replace)
3. If DNA and preset conflict, explicit token values from preset win

---

## 6. Foundation Presets

### 6.1 Light

The clean default. Neutral colors, readable typography, balanced spacing.

**DNA:** design_style=classic, motion_style=subtle, shape_style=sharp, typography_style=sans, elevation_style=soft, color_style=neutral

**Key tokens:**
- `color.primary`: `#C1121F` (red brand accent)
- `color.background`: `#FFFFFF`
- `color.text.primary`: `#333333`
- `typography.heading.font`: `'Playfair Display', serif`
- `typography.body.font`: `'Inter', sans-serif`
- `shadow.sm`: `0 1px 3px rgba(0,0,0,0.1)`
- `radius.md`: `8px`

### 6.2 Dark

Dark UI foundation. Inverted color scheme, suitable for modern stores.

**DNA:** design_style=modern, motion_style=dynamic, shape_style=rounded, typography_style=sans, elevation_style=floating, color_style=vibrant

**Key tokens:**
- `color.primary`: `#E53935`
- `color.background`: `#121212`
- `color.surface`: `#1E1E1E`
- `color.text.primary`: `#FFFFFF`
- `color.border`: `#333333`

### 6.3 Minimal

White space, simple layouts, reduced visual noise.

**DNA:** design_style=minimal, motion_style=subtle, shape_style=sharp, typography_style=sans, elevation_style=flat, color_style=monochrome

**Key tokens:**
- `color.primary`: `#000000`
- `color.background`: `#FFFFFF`
- `color.surface`: `#F8F8F8`
- `color.text.primary`: `#111111`
- `space.lg`: `48px`
- `space.xl`: `96px`
- All shadows: `none`

### 6.4 Modern

Contemporary startup/SaaS look. Bold colors, large typography, dynamic feel.

**DNA:** design_style=modern, motion_style=dynamic, shape_style=sharp, typography_style=sans, elevation_style=floating, color_style=vibrant

**Key tokens:**
- `color.primary`: `#6C63FF`
- `color.secondary`: `#FF6584`
- `typography.heading.font`: `'Inter', sans-serif`
- `typography.h1.size`: `64px`
- `shadow.lg`: `0 20px 40px rgba(108,99,255,0.15)`

### 6.5 Luxury

Premium brands, jewelry, watches, high-end fashion. Gold accents, serif typography, generous spacing.

**DNA:** design_style=luxury, motion_style=elegant, shape_style=rounded, typography_style=serif, elevation_style=soft, color_style=vibrant

**Key tokens:**
- `color.primary`: `#000000`
- `color.secondary`: `#D4AF37`
- `color.accent`: `#8B7355`
- `typography.heading.font`: `'Playfair Display', serif`
- `typography.body.font`: `'Inter', sans-serif`
- `radius.md`: `12px`
- `radius.lg`: `24px`
- `space.md`: `24px`
- `space.lg`: `48px`
- `space.xl`: `96px`
- `motion.duration.normal`: `400ms`
- `shadow.md`: `0 8px 24px rgba(0,0,0,0.12)`

### 6.6 Classic

Traditional corporate/editorial style. Trustworthy, established feel.

**DNA:** design_style=classic, motion_style=subtle, shape_style=sharp, typography_style=serif, elevation_style=soft, color_style=neutral

**Key tokens:**
- `color.primary`: `#1A365D` (navy)
- `color.secondary`: `#2D3748`
- `typography.heading.font`: `'Playfair Display', serif`
- `typography.body.font`: `'Source Serif 4', serif`
- `radius.sm`: `2px`
- `radius.md`: `4px`

### 6.7 Glass

Glassmorphism and modern UI effects. Transparency, blur, floating elements.

**DNA:** design_style=modern, motion_style=smooth, shape_style=rounded, typography_style=sans, elevation_style=glass, color_style=vibrant

**Key tokens:**
- `color.primary`: `#7C3AED` (purple)
- `color.secondary`: `#EC4899`
- `color.surface`: `rgba(255,255,255,0.1)`
- `color.border`: `rgba(255,255,255,0.2)`
- `effect.glass.reflection`: `rgba(255,255,255,0.15)`
- `effect.blur.md`: `12px`
- `component.header.transparency`: `0.8`
- `component.header.blur`: `10px`
- `shadow.card`: `0 8px 32px rgba(0,0,0,0.1)`

---

## 7. Admin UI

### 7.1 Top-Level Phantom Menu

```
PHANTOM (top-level, icon: dashicons-star-filled)
│
├── Dashboard              index.php?page=phantom-dashboard
├── Design Studio          admin.php?page=phantom-design-studio
├── Demo Manager           admin.php?page=phantom-demo-manager
├── Component Library      admin.php?page=phantom-components
├── Template Manager       admin.php?page=phantom-templates
├── Animation Studio       admin.php?page=phantom-animations
├── Theme Options          admin.php?page=phantom-theme-options
├── Asset Manager          admin.php?page=phantom-assets
├── Performance            admin.php?page=phantom-performance
├── SEO                    admin.php?page=phantom-seo
├── Import / Export        admin.php?page=phantom-import-export
├── Backup & Restore       admin.php?page=phantom-backup
├── Developer              admin.php?page=phantom-developer
└── System                 admin.php?page=phantom-system
```

### 7.2 Page Implementation Status

| Page | Phase 4 Status | Notes |
|------|---------------|-------|
| Dashboard | ✅ Full | Framework overview, version info, quick actions |
| Design Studio | ✅ Full | Multi-tab: Presets, DNA, Colors, Typography, Spacing, Motion, 3D, Tokens, CSS Preview |
| Demo Manager | ✅ Full | Already built in Phase 3C — migrate under Phantom menu |
| Component Library | ⏸ Skeleton | Basic placeholder page |
| Template Manager | ⏸ Skeleton | Basic placeholder page |
| Animation Studio | ⏸ Skeleton | Basic placeholder page |
| Theme Options | ✅ Full | Existing settings page — migrate under Phantom menu |
| Asset Manager | ⏸ Skeleton | Basic placeholder page |
| Performance | ⏸ Skeleton | Basic placeholder page |
| SEO | ⏸ Skeleton | Basic placeholder page |
| Import / Export | ✅ Full | Preset import/export + settings import/export |
| Backup & Restore | ⏸ Skeleton | Basic placeholder page |
| Developer | ⏸ Skeleton | Info page: hooks, filters, version info |
| System | ⏸ Skeleton | System status, debug info |

### 7.3 Design Studio Page Layout

```
┌─────────────────────────────────────────────────┐
│  DESIGN STUDIO                                    │
│  ┌─────────────────────────────────────────────┐ │
│  │ [Presets] [DNA] [Colors] [Typography] ...    │ │
│  ├─────────────────────────────────────────────┤ │
│  │                                               │ │
│  │  PRESET LIBRARY                               │ │
│  │                                               │ │
│  │  ┌─────────┐ ┌─────────┐ ┌─────────┐         │ │
│  │  │ LUXURY  │ │ MODERN  │ │ MINIMAL │         │ │
│  │  │ [ACTIVE]│ │         │ │         │         │ │
│  │  └─────────┘ └─────────┘ └─────────┘         │ │
│  │  ┌─────────┐ ┌─────────┐ ┌─────────┐         │ │
│  │  │  LIGHT  │ │   DARK  │ │ CLASSIC │         │ │
│  │  └─────────┘ └─────────┘ └─────────┘         │ │
│  │  ┌─────────┐ ┌─────────┐                      │ │
│  │  │  GLASS  │ │ IMPORT  │                      │ │
│  │  └─────────┘ └─────────┘                      │ │
│  │                                               │ │
│  │  ── Demo Presets ──                           │ │
│  │                                               │ │
│  │  ┌─────────┐ ┌─────────┐                      │ │
│  │  │  BRIGHT │ │ PASTEL  │                      │ │
│  │  │  (Kids) │ │  (Kids) │                      │ │
│  │  └─────────┘ └─────────┘                      │ │
│  │                                               │ │
│  └─────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────┘
```

**Design Studio tabs:**
1. **Presets** — preset selector grid, import/export buttons
2. **Theme DNA** — visual selectors for each DNA dimension
3. **Colors** — all color tokens with color pickers
4. **Typography** — font family selectors, size sliders
5. **Spacing** — spacing scale visual editor
6. **Motion** — duration, easing selectors
7. **3D** — tilt, perspective, blur controls
8. **Tokens** — read-only catalog of all tokens with current values
9. **CSS Preview** — live CSS output view

### 7.4 Demo Manager Integration

The existing Demo Manager (Phase 3C) gets:
1. Migrated under the `PHANTOM` top-level menu
2. A "Default Preset" field in each demo's detail view
3. When a demo is activated, its `default_preset` is applied

### 7.5 Dashboard Page Layout

```
┌────────────────────────────────────────────────────┐
│  PHANTOM DASHBOARD                                  │
│                                                      │
│  Version: 1.5.3                                     │
│  Framework: Phantom Core                            │
│  Active Demo: Fashion ↓                             │
│  Active Preset: Luxury ↓                            │
│                                                      │
│  ┌──────────────┐ ┌──────────────┐                  │
│  │ Active Preset │ │ Active Demo  │                  │
│  │    Luxury     │ │   Fashion    │                  │
│  │  [Change]     │ │  [Change]    │                  │
│  └──────────────┘ └──────────────┘                  │
│  ┌──────────────┐ ┌──────────────┐                  │
│  │ Token Health  │ │ CSS Vars     │                  │
│  │ ✅ 177/177   │ │ 177 generated│                  │
│  └──────────────┘ └──────────────┘                  │
│                                                      │
│  ── Quick Actions ──                                 │
│  [Design Studio] [Demo Manager] [Theme Options]      │
│  [Export Preset] [Import Preset] [Clear Cache]       │
└────────────────────────────────────────────────────┘
```

---

## 8. Customizer Integration

### 8.1 Quick-Edit Panel

New Customizer panel: **Design** (under `phantom_design_panel`)

Controls:
1. **Preset Selector** — dropdown of all available presets. Changing triggers full preset apply + refresh.
2. **Colors** — individual color controls for primary, secondary, background, text
3. **Typography** — heading font, body font, base size
4. **Layout** — container width, header height
5. **Buttons** — button radius, button style (filled/outline)

### 8.2 Live Preview

- Preset change triggers full page refresh (due to many setting changes)
- Individual color/typography changes use selective refresh via `postMessage`
- CSS vars update in real-time via `customizer-preview.js` (existing pattern)

### 8.3 Settings Sync

When Customizer saves:
1. Individual settings saved to Options API (existing flow)
2. Additional hook: `customize_save_after` → clears Design System cache

---

## 9. Export / Import

### 9.1 Export

**File:** `includes/Design/class-design-exporter.php`
**Namespace:** `PhantomCore\Design`

```php
class DesignExporter {
    private TokenRegistry $registry;
    private TokenResolver $resolver;
    private ThemeDNAEngine $dna;

    public function exportPreset(string $name): string; // Returns JSON
    public function exportCurrentPreset(): string;
    public function downloadPreset(string $name): void;  // Sends file to browser
}
```

**Export format:** JSON matching the schema in Section 4.2
**Export endpoint:** `admin-post.php?action=phantom_export_preset&preset=core:luxury&_wpnonce=...`

### 9.2 Import

**File:** `includes/Design/class-design-importer.php`
**Namespace:** `PhantomCore\Design`

```php
class DesignImporter {
    public function importFromString(string $json): Preset;
    public function importFromFile(string $path): Preset;
    public function validateImport(string $json): ValidationResult;
}
```

**Import validation:**
- Valid JSON
- All required fields present
- Framework version compatible
- Token names exist in TokenRegistry
- Token values pass type validation

**Import endpoint:** `admin-post.php?action=phantom_import_preset&_wpnonce=...` (multipart POST with JSON file)

---

## 10. Integration Points

### 10.1 CSS Generation Engine

**Integration:** CSSVariableGenerator hooks into `phantom_dynamic_css` at priority 2.

```php
add_filter('phantom_dynamic_css', function ($css) {
    return $css . DesignSystem::generateCSS();
}, 2);
```

This places Design System CSS **before** the palette system (priority 5) and existing modules (priorities 10-100). Both coexist — the new semantic vars don't conflict with existing var names.

### 10.2 Phantom_Global_Palette Bridge

The existing `Phantom_Global_Palette` class (4 color presets: light, dark, vibrant, pastel) is **not removed** in Phase 4. Instead:

1. **Color presets become CoreProvider presets** — each existing palette preset is converted to a token-based preset in `data/presets.php`
2. **Legacy class remains active** — `Phantom_Global_Palette` continues to output `--phantom-color-0..8` CSS vars at priority 5 for backward compatibility
3. **Token colors reference palette** — `color.primary`, `color.secondary`, etc. map to the same settings the palette reads, so changes stay in sync
4. **Future deprecation path** — once all components use `--color-primary` instead of `--phantom-color-0`, the legacy class can be deprecated

### 10.3 Demo Manager

**Integration:** When a demo is activated:
1. Read demo's `default_preset` from `demo.json`
2. If preset specified → `PresetManager::apply($presetId)`

Existing Demo Installer extended with a `post_install()` step.

### 10.3 Settings Registry

**Integration:** TokenResolver reads from existing `phantom_*` options. No changes needed to Settings Registry.

For new tokens that don't have existing settings (e.g., shadow tokens, motion tokens), new settings are added to the registry:

```php
// In register_settings():
$this->add_setting('design_shadow_sm', [
    'default' => '0 1px 2px rgba(0,0,0,0.05)',
    'type' => 'text',
    'section' => 'design_tokens',
    'sanitize_callback' => 'sanitize_text_field',
]);
```

New section: `design_tokens` (or distribute across existing sections)

**Settings count impact:** The new token categories (shadows, motion, 3D, etc.) add approximately 60 new `phantom_*` settings. The Settings Registry grows from ~564 to ~624 entries.

**Phase 4A must add these new settings to Settings_Registry** alongside the token definitions. Each new token that lacks a corresponding setting needs: (a) a setting entry in the registry, (b) a default value, (c) a sanitize callback, and (d) a section assignment.

### 10.4 Container (Service Container)

**Registration in Container_Config:**
```php
$this->singleton(DesignSystemManager::class, function () {
    return DesignSystemManager::get_instance();
});
```

### 10.5 Autoloader

New namespace: `PhantomCore\Design\*` → `includes/Design/class-*.php`

```php
$prefix   = 'PhantomCore\\Design\\';
$base_dir = PHANTOM_CORE_PATH . 'includes/Design/';
```

---

## 11. Sub-Phase Breakdown

### Phase 4A — Token Core (Foundation)

**Goal:** TokenRegistry, TokenResolver, TokenValidator, TokenCompiler, CSSVariableGenerator, DesignSystemManager facade

**Files to create:**
- `includes/Design/class-token-registry.php`
- `includes/Design/class-token-resolver.php`
- `includes/Design/class-token-validator.php`
- `includes/Design/class-token-compiler.php`
- `includes/Design/class-css-variable-generator.php`
- `includes/Design/class-design-system-manager.php`
- `includes/Design/class-compiled-token-set.php`
- `includes/Design/data/token-definitions.php` (all ~177 tokens)

**Files to modify:**
- `phantom-core.php` (Design autoloader, init hook)
- `includes/Engine/Container_Config.php` (DesignSystemManager registration)

**Tests:**
- `tests/Design_Token_Registry_Test.php`
- `tests/Design_Token_Resolver_Test.php`
- `tests/Design_Token_Validator_Test.php`
- `tests/Design_Token_Compiler_Test.php`
- `tests/Design_CSS_Variable_Generator_Test.php`
- `tests/Design_System_Manager_Test.php`

**Verification:**
- All 177+ tokens registered correctly
- TokenResolver reads from Settings Registry correctly
- TokenValidator catches bad values
- TokenCompiler produces correct compiled set (no alias generation — new names only)
- CSSGenerator produces valid CSS with only semantic var names
- DesignSystem facade delegates correctly
- Output hooks into `phantom_dynamic_css` at priority 2
- New settings (~60) added to Settings_Registry for shadow/motion/3D/z-index tokens
- Settings_Registry total grows from 564 to ~624
- No PHP errors or warnings

### Phase 4B — Preset Infrastructure

**Goal:** Preset value object, PresetManager, PresetRegistry, PresetProviderInterface, CoreProvider, DemoProvider, UserProvider, ThemeDNAEngine, 7 foundation presets

**Files to create:**
- `includes/Design/class-preset.php`
- `includes/Design/class-preset-manager.php`
- `includes/Design/class-preset-registry.php`
- `includes/Design/class-theme-dna-engine.php`
- `includes/Design/Providers/interface-preset-provider.php`
- `includes/Design/Providers/class-core-provider.php`
- `includes/Design/Providers/class-demo-provider.php`
- `includes/Design/Providers/class-user-provider.php`
- `includes/Design/data/presets.php` (7 foundation presets)

**Tests:**
- `tests/Design_Preset_Test.php`
- `tests/Design_Preset_Manager_Test.php`
- `tests/Design_Preset_Registry_Test.php`
- `tests/Design_Theme_DNA_Engine_Test.php`
- `tests/Design_Core_Provider_Test.php`
- `tests/Design_Demo_Provider_Test.php`
- `tests/Design_User_Provider_Test.php`

**Verification:**
- All 7 foundation presets apply correctly
- Token values written to Settings Registry
- Theme DNA saves/loads correctly
- DNA → token overrides work
- Provider priority (User > Demo > Core) enforced
- Preset merge with parent preset works
- Version compatibility check works

### Phase 4C — Admin UI

**Goal:** Top-level Phantom menu with 14 sub-pages, Design Studio (full), Theme Options migration, Demo Manager migration, skeleton pages

**Files to create:**
- `admin/class-phantom-admin.php` (menu + page router)
- `admin/class-dashboard-page.php`
- `admin/class-design-studio-page.php`
- `admin/class-component-library-page.php` (skeleton)
- `admin/class-template-manager-page.php` (skeleton)
- `admin/class-animation-studio-page.php` (skeleton)
- `admin/class-asset-manager-page.php` (skeleton)
- `admin/class-performance-page.php` (skeleton)
- `admin/class-seo-page.php` (skeleton)
- `admin/class-import-export-page.php`
- `admin/class-backup-restore-page.php` (skeleton)
- `admin/class-developer-page.php` (skeleton)
- `admin/class-system-page.php` (skeleton)
- `admin/css/design-studio.css`
- `admin/js/design-studio.js`

**Files to modify:**
- `phantom-core.php` (admin init, menu registration)
- `admin/class-settings-page.php` (migrate under Phantom menu)
- `admin/class-demo-admin.php` (migrate under Phantom menu, update menu slug)
- `includes/Engine/Container_Config.php` (register admin page classes)

**Tests:**
- `tests/Design_Admin_Menu_Test.php`
- `tests/Design_Design_Studio_Test.php`
- `tests/Design_Import_Export_Test.php`

**Verification:**
- All 14 menu items visible under Phantom top-level menu
- Design Studio renders all tabs with correct data
- Preset selector grid shows all 7 foundation presets + demo presets
- Preset apply works from UI
- Theme DNA selectors work
- Import/Export page accepts file uploads, downloads files
- All skeleton pages render without errors
- Theme Options page works under new location
- Demo Manager page works under new location
- Menu capability checks correct (manage_options)

### Phase 4D — Integration & Polish

**Goal:** Customizer quick-edit panel, Export/Import functionality, Demo Manager integration, backward compatibility, cleanup

**Files to create:**
- `admin/class-customizer-design-panel.php`
- `includes/Design/class-design-exporter.php`
- `includes/Design/class-design-importer.php`
- `includes/Design/Providers/class-import-provider.php`

**Files to modify:**
- `includes/class-customizer.php` (register Design panel)
- `admin/class-demo-admin.php` (add preset assignment UI)
- `admin/js/customizer-preview.js` (add token live preview)

**Tests:**
- `tests/Design_Customizer_Panel_Test.php`
- `tests/Design_Exporter_Test.php`
- `tests/Design_Importer_Test.php`
- `tests/Design_Demo_Integration_Test.php`

**Verification:**
- Customizer Design panel shows preset selector
- Preset change in Customizer triggers page refresh
- Export generates valid JSON matching schema
- Import validates and applies JSON preset
- Demo activation triggers preset apply
- Backward compatibility: existing CSS vars unchanged
- No broken settings from migration
- Full loop-engineering self-review: 100/100

---

## 12. Testing Strategy

### 12.1 Unit Tests

| Test Suite | Tests | Key Assertions |
|-----------|-------|---------------|
| TokenRegistry | 15+ | All tokens defined, correct types, CSS var naming, option key mapping |
| TokenResolver | 20+ | Reads settings, applies defaults, resolves inheritance, type casting |
| TokenValidator | 15+ | Valid hex, valid size, missing values, deprecated warnings |
| TokenCompiler | 10+ | Compiled set complete, component tokens flattened, CSS var names correct |
| CSSVariableGenerator | 10+ | Valid CSS output, component scoping, media queries, hook integration |
| DesignSystemManager | 10+ | Facade delegates correctly, singleton works |
| Preset | 10+ | from_array, to_array, to_json, merge, isCompatible |
| PresetManager | 15+ | Apply writes options, save, delete, duplicate, current |
| PresetRegistry | 10+ | Provider registration, merge priority, get, has |
| ThemeDNAEngine | 10+ | Dimensions defined, get/set, applyOverrides |
| CoreProvider | 5+ | Returns 7 presets, all have required fields |
| DemoProvider | 5+ | Scans directory, parses JSON, handles missing dir |
| UserProvider | 10+ | CRUD operations, option storage |
| Exporter | 5+ | Valid JSON output, file download |
| Importer | 10+ | Validate JSON, reject invalid, apply preset |
| Admin Menu | 5+ | Menu registered, submenus exist, capabilities correct |
| Customizer Panel | 5+ | Panel registered, controls exist, sanitize callbacks |

**Total: ~160+ tests**

### 12.2 Integration Tests

- Preset apply → CSS output matches expected values
- Theme DNA + preset merge produces correct combined tokens
- Export → reimport → CSS output is identical (round-trip)
- Demo activation triggers correct preset
- Customizer save triggers cache clear

### 12.3 Mock Strategy

- Mock `get_option`/`update_option` for token resolution tests
- Mock `is_admin()` for admin menu tests
- Mock `current_user_can()` for capability tests
- Use `WP_Mock` for WordPress function mocking

---

## 13. Verification

### 13.1 Quality Gates (Each Sub-Phase)

1. ✅ All tests pass (`vendor/bin/phpunit`)
2. ✅ No PHP syntax errors (`php -l includes/Design/*.php`)
3. ✅ No PHP notices/warnings (debug.log empty)
4. ✅ No TODO/FIXME left in new code
5. ✅ Code follows existing conventions (naming, file structure)
6. ✅ All public methods documented (PHPDoc)
7. ✅ No hardcoded colors/values in components (all use tokens)
8. ✅ Backward compatibility verified (existing features unchanged)

### 13.2 Health Score: 100/100

After each sub-phase, run full test suite. Target: 0 failures, 0 errors across all existing + new tests.

### 13.3 Spec Self-Review Checklist

- [x] No placeholder text (`TODO`, `FIXME`, `TBD` in spec)
- [x] All token categories described with concrete values (Section 3: 177+ tokens across 11 categories)
- [x] All class responsibilities clear with no overlap (Section 2: 11 classes + facade)
- [x] Integration points specified (Section 10: CSS Engine, Palette Bridge, Demo Manager, Settings Registry, Container)
- [x] All 7 foundation presets defined with DNA settings and key tokens (Section 6)
- [x] Admin UI structure complete with all 14 sub-pages (Section 7)
- [x] Customizer integration clearly specified (Section 8)
- [x] Export/Import format and flow specified (Section 9)
- [x] Sub-phase breakdown contains all necessary files and tests (Section 11)
- [x] Testing strategy covers all classes (Section 12: ~160+ tests)
- [x] No contradictions between sections (backward-compatible aliases removed; new settings requirement explicit)
- [x] Version constraints and compatibility logic specified (Section 2.16)

---

## 14. File Manifest

### New Files (~45)

```
includes/Design/
├── class-design-system-manager.php
├── class-token-registry.php
├── class-token-resolver.php
├── class-token-validator.php
├── class-token-compiler.php
├── class-compiled-token-set.php
├── class-css-variable-generator.php
├── class-preset.php
├── class-preset-manager.php
├── class-preset-registry.php
├── class-theme-dna-engine.php
├── class-design-exporter.php
├── class-design-importer.php
├── data/
│   ├── token-definitions.php
│   └── presets.php
├── Providers/
│   ├── interface-preset-provider.php
│   ├── class-core-provider.php
│   ├── class-demo-provider.php
│   ├── class-user-provider.php
│   └── class-import-provider.php

admin/
├── class-phantom-admin.php
├── class-dashboard-page.php
├── class-design-studio-page.php
├── class-component-library-page.php
├── class-template-manager-page.php
├── class-animation-studio-page.php
├── class-asset-manager-page.php
├── class-performance-page.php
├── class-seo-page.php
├── class-import-export-page.php
├── class-backup-restore-page.php
├── class-developer-page.php
├── class-system-page.php
├── class-customizer-design-panel.php
├── css/design-studio.css
└── js/design-studio.js

tests/
├── Design_Token_Registry_Test.php
├── Design_Token_Resolver_Test.php
├── Design_Token_Validator_Test.php
├── Design_Token_Compiler_Test.php
├── Design_CSS_Variable_Generator_Test.php
├── Design_System_Manager_Test.php
├── Design_Preset_Test.php
├── Design_Preset_Manager_Test.php
├── Design_Preset_Registry_Test.php
├── Design_Theme_DNA_Engine_Test.php
├── Design_Core_Provider_Test.php
├── Design_Demo_Provider_Test.php
├── Design_User_Provider_Test.php
├── Design_Exporter_Test.php
├── Design_Importer_Test.php
├── Design_Admin_Menu_Test.php
├── Design_Design_Studio_Test.php
├── Design_Import_Export_Test.php
├── Design_Customizer_Panel_Test.php
└── Design_Demo_Integration_Test.php
```

**Total new files:** ~45 files
- PHP: ~35 files
- CSS: 1 file
- JS: 1 file
- Test: ~20 files

### Modified Files (~6)

- `phantom-core.php` (autoloader + init)
- `includes/Engine/Container_Config.php` (DI registration)
- `includes/class-customizer.php` (Design panel)
- `admin/class-settings-page.php` (migrate under Phantom)
- `admin/class-demo-admin.php` (migrate + preset assignment)
- `admin/js/customizer-preview.js` (token live preview)

---

**End of Phase 4 Specification**
