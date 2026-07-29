# Design Tokens Panel — `phantom_design`

## Panel Overview

| Property | Value |
|----------|-------|
| Panel ID | `phantom_design` |
| Sections | 1 |
| Total Settings | ~168 (dynamic) |
| Control Types | `ast-color` (14), `ast-select` (38), `number` (116) |

---

## Section: `design_tokens` (`phantom_section_design_tokens`)

### Setting Generation

Tokens are **auto-generated** from `includes/Design/data/token-definitions.php` via `DesignSystemManager::cssVar()`. The Settings_Loader iterates over definitions and creates controls dynamically — no manual registration required.

### Token Categories

#### Color Tokens (14)

| Token Path | CSS Var | Description |
|------------|---------|-------------|
| `color.primary` | `--color-primary` | Primary brand color |
| `color.secondary` | `--color-secondary` | Secondary brand color |
| `color.accent` | `--color-accent` | Accent color |
| `color.background` | `--color-background` | Page background |
| `color.text.primary` | `--color-text-primary` | Primary text color |
| `color.text.secondary` | `--color-text-secondary` | Secondary text color |
| `color.link` | `--color-link` | Link color |
| `color.link.hover` | `--color-link-hover` | Link hover color |
| `color.border` | `--color-border` | Border color |
| `color.sale` | `--color-sale` | Sale price color |
| `color.light-bg` | `--color-light-bg` | Light background variant |
| `color.grey` | `--color-grey` | Grey/muted color |
| `color.success` | `--color-success` | Success state color |
| `color.error` | `--color-error` | Error state color |
| `color.warning` | `--color-warning` | Warning state color |
| `color.info` | `--color-info` | Info state color |

**Control type:** `ast-color` — opens a color picker with hex/rgba input.

#### Typography Tokens (42)

**Body typography (7):**

| Token Path | CSS Var | Description |
|------------|---------|-------------|
| `typography.body.font` | `--font-body` | Body font family |
| `typography.body.weight` | `--font-body-weight` | Body font weight |
| `typography.body.style` | `--font-body-style` | Body font style |
| `typography.body.size` | `--font-body-size` | Base font size |
| `typography.body.line_height` | `--font-body-line-height` | Line height |
| `typography.body.spacing` | `--font-body-spacing` | Letter spacing |

**Heading typography (4):**

| Token Path | CSS Var | Description |
|------------|---------|-------------|
| `typography.heading.font` | `--font-heading` | Heading font family |
| `typography.heading.weight` | `--font-heading-weight` | Heading font weight |
| `typography.heading.case` | `--font-heading-case` | Text transform |
| `typography.heading.spacing` | `--font-heading-spacing` | Letter spacing |

**Per-heading tokens (30):**

For each of `h1` through `h6`:

| Token Path | CSS Var | Description |
|------------|---------|-------------|
| `typography.h{1-6}.font` | `--font-h{1-6}` | Override font family |
| `typography.h{1-6}.weight` | `--font-h{1-6}-weight` | Override font weight |
| `typography.h{1-6}.size` | `--font-h{1-6}-size` | Override font size |
| `typography.h{1-6}.line_height` | `--font-h{1-6}-line-height` | Override line height |
| `typography.h{1-6}.style` | `--font-h{1-6}-style` | Override font style |
| `typography.h{1-6}.spacing` | `--font-h{1-6}-spacing` | Override letter spacing |
| `typography.h{1-6}.case` | `--font-h{1-6}-case` | Override text transform |

**Control types:** `ast-select` for font family/weight/style/case, `number` for size/line-height/spacing.

#### Spacing Tokens (7)

| Token Path | CSS Var | Description |
|------------|---------|-------------|
| `spacing.xs` | `--spacing-xs` | Extra small spacing |
| `spacing.sm` | `--spacing-sm` | Small spacing |
| `spacing.md` | `--spacing-md` | Medium spacing |
| `spacing.lg` | `--spacing-lg` | Large spacing |
| `spacing.xl` | `--spacing-xl` | Extra large spacing |
| `spacing.2xl` | `--spacing-2xl` | 2x large spacing |
| `spacing.3xl` | `--spacing-3xl` | 3x large spacing |

**Control type:** `number` (px values)

#### Border Tokens (6)

| Token Path | CSS Var | Description |
|------------|---------|-------------|
| `border.radius.sm` | `--border-radius-sm` | Small border radius |
| `border.radius.md` | `--border-radius-md` | Medium border radius |
| `border.radius.lg` | `--border-radius-lg` | Large border radius |
| `border.radius.xl` | `--border-radius-xl` | Extra large border radius |
| `border.radius.full` | `--border-radius-full` | Full pill/circle radius |

**Control type:** `number` (px values)

#### Shadow Tokens (4)

| Token Path | CSS Var | Description |
|------------|---------|-------------|
| `shadow.sm` | `--shadow-sm` | Small shadow |
| `shadow.md` | `--shadow-md` | Medium shadow |
| `shadow.lg` | `--shadow-lg` | Large shadow |
| `shadow.xl` | `--shadow-xl` | Extra large shadow |

**Control type:** `ast-select` (shadow preset dropdown)

#### Breakpoint Tokens (4)

| Token Path | CSS Var | Description |
|------------|---------|-------------|
| `breakpoint.sm` | `--breakpoint-sm` | Small breakpoint |
| `breakpoint.md` | `--breakpoint-md` | Medium breakpoint |
| `breakpoint.lg` | `--breakpoint-lg` | Large breakpoint |
| `breakpoint.xl` | `--breakpoint-xl` | Extra large breakpoint |

**Control type:** `number` (px values)

---

## Code Flow

```
Token definitions loaded (token-definitions.php)
    ↓
DesignSystemManager registers tokens
    ↓
Settings_Loader creates settings for each token
    ↓
Customizer renders controls (ast-color, ast-select, number)
    ↓
User changes token
    ↓
Value saved to wp_options
    ↓
DesignSystemManager::cssVar() generates CSS var
    ↓
Output in <style> tag via CSS Generation Engine
```

## REST API

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/phantom/v1/design/tokens` | GET | List all tokens (optional `category` param) |
| `/phantom/v1/design/tokens/{name}` | GET | Get single token value (dot notation) |
| `/phantom/v1/design/tokens/{name}` | PUT | Update token value |
| `/phantom/v1/design/presets` | GET | List all design presets |
| `/phantom/v1/design/presets/{id}` | GET | Get single preset details |
| `/phantom/v1/design/presets/apply` | POST | Apply a preset (requires `edit_theme_options`) |
| `/phantom/v1/design/css` | GET | Get generated CSS string |

## Admin Integration

**Design_Studio_Page** provides a visual token editor with live preview. Tokens can be saved as presets and shared across sites.

## Customizer Live Preview

All design token settings use `transport: 'postMessage'` for instant live preview. The `css_var` property on each setting maps it to a CSS custom property, enabling the browser to apply changes without a page reload.

```
User adjusts color.primary
    ↓
postMessage sends value to preview frame
    ↓
customizer-preview.js updates CSS var: document.documentElement.style.setProperty('--color-primary', value)
    ↓
All elements using var(--color-primary) update instantly
```

## Key Files

| File | Purpose |
|------|---------|
| `includes/Design/data/token-definitions.php` | Token definitions (source of truth) |
| `includes/Design/class-design-system-manager.php` | `cssVar()` — generates CSS var from token |
| `includes/Settings/class-settings-loader.php` | `section_design_tokens()` — creates settings |
| `includes/class-settings-registry.php` | `get_css_var_map()` — CSS var → setting mapping |
| `admin/js/customizer-preview.js` | Auto-bind for all CSS var settings |
| `admin/class-design-studio-page.php` | Design Studio UI (token editor) |
| `admin/js/design-studio.js` | Toast notifications, preset apply |
| `includes/class-rest-controller.php` | 6 design token REST endpoints |
