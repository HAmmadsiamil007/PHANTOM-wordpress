# Phase 4: Design System Engine — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use subagent-driven-development (recommended) or executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a complete Design System Engine with Token Registry, Token Resolver, CSS Variable Generator, Preset Manager, Theme DNA, 7 foundation presets, 14-page Phantom admin menu, and Customizer quick-edit.

**Architecture:** 11 classes across 3 layers (Token System → Preset System → Presentation), wrapped by `DesignSystem` facade, backed by existing Settings Registry as single source of truth. 3-tier preset providers (Core PHP, Demo JSON, User DB). Plugin namespace `PhantomCore\Design`, admin pages at `admin/*`.

**Tech Stack:** PHP 8.0+, WordPress Options API, Customizer API, vanilla JS, CSS3, PHPUnit for tests

## Global Constraints
- All PHP classes use `PhantomCore\Design` namespace (or `PhantomCore\Design\Providers`)
- All files follow `class-{name}.php` naming in `includes/Design/`
- Providers use `interface-preset-provider.php` and `class-{name}-provider.php` in `includes/Design/Providers/`
- All admin page classes in `admin/class-{name}.php`
- Tests in `tests/Design_*_Test.php` format
- `declare(strict_types=1)` and `defined('ABSPATH') || exit;` in all PHP files
- Token naming: dot-notation `{category}.{subcategory}.{name}` (max 3 levels)
- CSS var naming: auto-generated `--{category}-{subcategory}-{name}` (dots → hyphens)
- Existing CSS modules continue unchanged — new token vars are additive, not replacive
- Preset ID format: `{source}:{id}` — e.g., `core:luxury`, `demo:kids:bright`, `user:my-brand`

---

## Phase 4A — Token Core (Foundation)

**Goal:** TokenRegistry, TokenResolver, TokenValidator, TokenCompiler, CSSVariableGenerator, DesignSystemManager facade, token definitions, new settings in Settings_Registry.

### Task 4A.1: Autoloader + Container Registration

**Files:**
- Modify: `phantom-core.php:29-87` (add Design autoloader branch)
- Modify: `includes/Engine/Container_Config.php` (add DesignSystemManager registration)
- Create: `includes/Design/` directory

- [ ] **Step 1: Add Design autoloader branch in phantom-core.php**

Edit `phantom-core.php` — add before the fallback `$file = ...` line (line 82):

```php
// Design System uses includes/Design/ with class-{name}.php
$design_prefix = 'Design\\';
if ( strncmp( $design_prefix, $relative_class, strlen( $design_prefix ) ) === 0 ) {
    $short = substr( $relative_class, strlen( $design_prefix ) );
    $file  = PHANTOM_CORE_PATH . 'includes/Design/class-' . str_replace( '_', '-', strtolower( $short ) ) . '.php';
    if ( file_exists( $file ) ) {
        require_once $file;
        return;
    }
}
```

Also add a sub-handler for the Providers namespace:
```php
// Design Providers
$providers_prefix = 'Design\\Providers\\';
if ( strncmp( $providers_prefix, $relative_class, strlen( $providers_prefix ) ) === 0 ) {
    $short = substr( $relative_class, strlen( $providers_prefix ) );
    $file  = PHANTOM_CORE_PATH . 'includes/Design/Providers/class-' . str_replace( '_', '-', strtolower( $short ) ) . '.php';
    if ( file_exists( $file ) ) {
        require_once $file;
        return;
    }
}
```

- [ ] **Step 2: Add DesignSystemManager to Container_Config**

```php
use PhantomCore\Design\DesignSystemManager;

// Add to configure() after Demo registrations:
$container->singleton(DesignSystemManager::class, function () {
    return DesignSystemManager::get_instance();
});
```

- [ ] **Step 3: Add init hook in phantom-core.php**

Find the main plugin init (where `Phantom_Global_Palette::instance()->init()` is called) and add:

```php
// Phase 4: Design System Engine
\PhantomCore\Design\DesignSystemManager::get_instance()->init();
```

- [ ] **Step 4: Create includes/Design/ directory**

```bash
mkdir -p includes/Design/Providers
```

- [ ] **Step 5: Verify**

Run: `php -l phantom-core.php`

### Task 4A.2: Token Definitions

**Files:**
- Create: `includes/Design/data/token-definitions.php`

- [ ] **Step 1: Write token definitions**

```php
<?php
declare(strict_types=1);

namespace PhantomCore\Design;

defined('ABSPATH') || exit;

return [
    // ============================================================
    // COLORS (39 tokens)
    // ============================================================
    'color.primary' => [
        'name' => 'color.primary', 'category' => 'color', 'type' => 'color',
        'default' => '#C1121F', 'option_key' => 'phantom_primary_color',
        'description' => 'Primary brand color',
    ],
    'color.secondary' => [
        'name' => 'color.secondary', 'category' => 'color', 'type' => 'color',
        'default' => '#2D2D2D', 'option_key' => 'phantom_secondary_color',
        'description' => 'Secondary brand color',
    ],
    'color.accent' => [
        'name' => 'color.accent', 'category' => 'color', 'type' => 'color',
        'default' => '#705B53', 'option_key' => 'phantom_accent_color',
        'description' => 'Accent color',
    ],
    'color.success' => [
        'name' => 'color.success', 'category' => 'color', 'type' => 'color',
        'default' => '#2E7D32', 'option_key' => 'phantom_success_color',
        'description' => 'Success/positive color',
    ],
    'color.warning' => [
        'name' => 'color.warning', 'category' => 'color', 'type' => 'color',
        'default' => '#F9A825', 'option_key' => 'phantom_warning_color',
        'description' => 'Warning color',
    ],
    'color.danger' => [
        'name' => 'color.danger', 'category' => 'color', 'type' => 'color',
        'default' => '#D32F2F', 'option_key' => 'phantom_error_color',
        'description' => 'Danger/error color',
    ],
    'color.info' => [
        'name' => 'color.info', 'category' => 'color', 'type' => 'color',
        'default' => '#0288D1', 'option_key' => 'phantom_info_color',
        'description' => 'Info color',
    ],
    'color.background' => [
        'name' => 'color.background', 'category' => 'color', 'type' => 'color',
        'default' => '#FFFFFF', 'option_key' => 'phantom_bg_color',
        'description' => 'Page background color',
    ],
    'color.surface' => [
        'name' => 'color.surface', 'category' => 'color', 'type' => 'color',
        'default' => '#F5F5F5', 'option_key' => 'phantom_light_bg_color',
        'description' => 'Surface/card background color',
    ],
    'color.surface.alt' => [
        'name' => 'color.surface.alt', 'category' => 'color', 'type' => 'color',
        'default' => '#E5E5E5', 'option_key' => 'phantom_grey_color',
        'description' => 'Alternate surface color',
    ],
    'color.text.primary' => [
        'name' => 'color.text.primary', 'category' => 'color', 'type' => 'color',
        'default' => '#333333', 'option_key' => 'phantom_text_color',
        'description' => 'Primary text color',
    ],
    'color.text.secondary' => [
        'name' => 'color.text.secondary', 'category' => 'color', 'type' => 'color',
        'default' => '#666666', 'option_key' => 'phantom_heading_color',
        'description' => 'Secondary text color',
    ],
    'color.border' => [
        'name' => 'color.border', 'category' => 'color', 'type' => 'color',
        'default' => '#E5E5E5', 'option_key' => 'phantom_border_color',
        'description' => 'Default border color',
    ],
    'color.divider' => [
        'name' => 'color.divider', 'category' => 'color', 'type' => 'color',
        'default' => '#E0E0E0', 'option_key' => 'phantom_divider_color',
        'description' => 'Divider line color',
    ],
    'color.overlay' => [
        'name' => 'color.overlay', 'category' => 'color', 'type' => 'color',
        'default' => 'rgba(0,0,0,0.5)', 'option_key' => 'phantom_overlay_color',
        'description' => 'Overlay/modal backdrop color',
    ],
    'color.link' => [
        'name' => 'color.link', 'category' => 'color', 'type' => 'color',
        'default' => '#C1121F', 'option_key' => 'phantom_link_color',
        'description' => 'Link color',
    ],
    'color.link.hover' => [
        'name' => 'color.link.hover', 'category' => 'color', 'type' => 'color',
        'default' => '#8B0000', 'option_key' => 'phantom_link_hover_color',
        'description' => 'Link hover color',
    ],
    'color.gradient.primary' => [
        'name' => 'color.gradient.primary', 'category' => 'color', 'type' => 'color',
        'default' => '#C1121F', 'option_key' => 'phantom_gradient_start_color',
        'description' => 'Gradient start color',
    ],
    'color.gradient.secondary' => [
        'name' => 'color.gradient.secondary', 'category' => 'color', 'type' => 'color',
        'default' => '#8B0000', 'option_key' => 'phantom_gradient_end_color',
        'description' => 'Gradient end color',
    ],
    'color.header.bg' => [
        'name' => 'color.header.bg', 'category' => 'color', 'type' => 'color',
        'default' => '#FFFFFF', 'option_key' => 'phantom_color_header_bg',
        'description' => 'Header background color',
    ],
    'color.header.text' => [
        'name' => 'color.header.text', 'category' => 'color', 'type' => 'color',
        'default' => '#222222', 'option_key' => 'phantom_header_text_color',
        'description' => 'Header text color',
    ],
    'color.footer.bg' => [
        'name' => 'color.footer.bg', 'category' => 'color', 'type' => 'color',
        'default' => '#222222', 'option_key' => 'phantom_color_footer_bg',
        'description' => 'Footer background color',
    ],
    'color.footer.text' => [
        'name' => 'color.footer.text', 'category' => 'color', 'type' => 'color',
        'default' => '#FFFFFF', 'option_key' => 'phantom_footer_text',
        'description' => 'Footer text color',
    ],
    'color.topbar.bg' => [
        'name' => 'color.topbar.bg', 'category' => 'color', 'type' => 'color',
        'default' => '#222222', 'option_key' => 'phantom_topbar_bg',
        'description' => 'Top bar background color',
    ],
    'color.topbar.text' => [
        'name' => 'color.topbar.text', 'category' => 'color', 'type' => 'color',
        'default' => '#FFFFFF', 'option_key' => 'phantom_topbar_text',
        'description' => 'Top bar text color',
    ],
    'color.announcement.bg' => [
        'name' => 'color.announcement.bg', 'category' => 'color', 'type' => 'color',
        'default' => '#C1121F', 'option_key' => 'phantom_announcement_bar_bg',
        'description' => 'Announcement bar background',
    ],
    'color.announcement.text' => [
        'name' => 'color.announcement.text', 'category' => 'color', 'type' => 'color',
        'default' => '#FFFFFF', 'option_key' => 'phantom_announcement_bar_text_color',
        'description' => 'Announcement bar text color',
    ],
    'color.hero.bg' => [
        'name' => 'color.hero.bg', 'category' => 'color', 'type' => 'color',
        'default' => '#F5F3F2', 'option_key' => 'phantom_hero_bg_color',
        'description' => 'Hero section background',
    ],
    'color.card.bg' => [
        'name' => 'color.card.bg', 'category' => 'color', 'type' => 'color',
        'default' => '#FFFFFF', 'option_key' => 'phantom_product_card_bg',
        'description' => 'Product card background',
    ],
    'color.card.text' => [
        'name' => 'color.card.text', 'category' => 'color', 'type' => 'color',
        'default' => '#333333', 'option_key' => 'phantom_product_card_text',
        'description' => 'Product card text color',
    ],
    'color.card.border' => [
        'name' => 'color.card.border', 'category' => 'color', 'type' => 'color',
        'default' => '#E5E5E5', 'option_key' => 'phantom_product_card_border',
        'description' => 'Product card border color',
    ],
    'color.sale' => [
        'name' => 'color.sale', 'category' => 'color', 'type' => 'color',
        'default' => '#D32F2F', 'option_key' => 'phantom_sale_color',
        'description' => 'Sale/price color',
    ],
    'color.rating' => [
        'name' => 'color.rating', 'category' => 'color', 'type' => 'color',
        'default' => '#FFB800', 'option_key' => 'phantom_woo_rating',
        'description' => 'Star rating color',
    ],
    'color.button.bg' => [
        'name' => 'color.button.bg', 'category' => 'color', 'type' => 'color',
        'default' => '#C1121F', 'option_key' => 'phantom_button_bg',
        'description' => 'Button background color',
    ],
    'color.button.text' => [
        'name' => 'color.button.text', 'category' => 'color', 'type' => 'color',
        'default' => '#FFFFFF', 'option_key' => 'phantom_button_text',
        'description' => 'Button text color',
    ],
    'color.button.hover.bg' => [
        'name' => 'color.button.hover.bg', 'category' => 'color', 'type' => 'color',
        'default' => '#8B0000', 'option_key' => 'phantom_button_hover_bg',
        'description' => 'Button hover background',
    ],
    'color.button.hover.text' => [
        'name' => 'color.button.hover.text', 'category' => 'color', 'type' => 'color',
        'default' => '#FFFFFF', 'option_key' => 'phantom_button_hover_text',
        'description' => 'Button hover text color',
    ],
    'color.badge.sale.bg' => [
        'name' => 'color.badge.sale.bg', 'category' => 'color', 'type' => 'color',
        'default' => '#D32F2F', 'option_key' => 'phantom_product_badge_sale_bg',
        'description' => 'Sale badge background',
    ],
    'color.badge.sale.text' => [
        'name' => 'color.badge.sale.text', 'category' => 'color', 'type' => 'color',
        'default' => '#FFFFFF', 'option_key' => 'phantom_product_badge_sale_text',
        'description' => 'Sale badge text color',
    ],
    'color.badge.new.bg' => [
        'name' => 'color.badge.new.bg', 'category' => 'color', 'type' => 'color',
        'default' => '#2E7D32', 'option_key' => 'phantom_product_badge_new_bg',
        'description' => 'New badge background',
    ],
    'color.badge.new.text' => [
        'name' => 'color.badge.new.text', 'category' => 'color', 'type' => 'color',
        'default' => '#FFFFFF', 'option_key' => 'phantom_product_badge_new_text',
        'description' => 'New badge text color',
    ],

    // ============================================================
    // TYPOGRAPHY (30 tokens)
    // ============================================================
    'typography.heading.font' => [
        'name' => 'typography.heading.font', 'category' => 'typography', 'type' => 'font_family',
        'default' => "'Playfair Display', serif", 'option_key' => 'phantom_font_heading',
        'description' => 'Heading font family',
    ],
    'typography.heading.weight' => [
        'name' => 'typography.heading.weight', 'category' => 'typography', 'type' => 'select',
        'default' => '700', 'option_key' => 'phantom_font_heading_weight',
        'description' => 'Heading font weight',
    ],
    'typography.heading.case' => [
        'name' => 'typography.heading.case', 'category' => 'typography', 'type' => 'select',
        'default' => 'none', 'option_key' => 'phantom_font_heading_case',
        'description' => 'Heading text transform',
    ],
    'typography.heading.spacing' => [
        'name' => 'typography.heading.spacing', 'category' => 'typography', 'type' => 'size',
        'default' => '0', 'option_key' => 'phantom_font_heading_spacing',
        'description' => 'Heading letter spacing',
    ],
    'typography.body.font' => [
        'name' => 'typography.body.font', 'category' => 'typography', 'type' => 'font_family',
        'default' => "'Inter', sans-serif", 'option_key' => 'phantom_font_body',
        'description' => 'Body font family',
    ],
    'typography.body.weight' => [
        'name' => 'typography.body.weight', 'category' => 'typography', 'type' => 'select',
        'default' => '400', 'option_key' => 'phantom_font_body_weight',
        'description' => 'Body font weight',
    ],
    'typography.body.style' => [
        'name' => 'typography.body.style', 'category' => 'typography', 'type' => 'select',
        'default' => 'normal', 'option_key' => 'phantom_font_body_style',
        'description' => 'Body font style',
    ],
    'typography.body.size' => [
        'name' => 'typography.body.size', 'category' => 'typography', 'type' => 'font_size',
        'default' => '16px', 'option_key' => 'phantom_font_base_size',
        'description' => 'Base font size',
    ],
    'typography.body.line_height' => [
        'name' => 'typography.body.line_height', 'category' => 'typography', 'type' => 'unitless',
        'default' => '1.6', 'option_key' => 'phantom_font_line_height',
        'description' => 'Body line height',
    ],
    'typography.body.spacing' => [
        'name' => 'typography.body.spacing', 'category' => 'typography', 'type' => 'size',
        'default' => '0', 'option_key' => 'phantom_font_body_spacing',
        'description' => 'Body letter spacing',
    ],
    'typography.h1.size' => ['name' => 'typography.h1.size', 'category' => 'typography', 'type' => 'font_size', 'default' => '48px', 'option_key' => 'phantom_h1_size', 'description' => 'H1 font size'],
    'typography.h2.size' => ['name' => 'typography.h2.size', 'category' => 'typography', 'type' => 'font_size', 'default' => '36px', 'option_key' => 'phantom_h2_size', 'description' => 'H2 font size'],
    'typography.h3.size' => ['name' => 'typography.h3.size', 'category' => 'typography', 'type' => 'font_size', 'default' => '28px', 'option_key' => 'phantom_h3_size', 'description' => 'H3 font size'],
    'typography.h4.size' => ['name' => 'typography.h4.size', 'category' => 'typography', 'type' => 'font_size', 'default' => '24px', 'option_key' => 'phantom_h4_size', 'description' => 'H4 font size'],
    'typography.h5.size' => ['name' => 'typography.h5.size', 'category' => 'typography', 'type' => 'font_size', 'default' => '20px', 'option_key' => 'phantom_h5_size', 'description' => 'H5 font size'],
    'typography.h6.size' => ['name' => 'typography.h6.size', 'category' => 'typography', 'type' => 'font_size', 'default' => '16px', 'option_key' => 'phantom_h6_size', 'description' => 'H6 font size'],
    'typography.menu.font' => ['name' => 'typography.menu.font', 'category' => 'typography', 'type' => 'font_size', 'default' => '14px', 'option_key' => 'phantom_menu_font_size', 'description' => 'Menu font size'],
    'typography.button.font' => ['name' => 'typography.button.font', 'category' => 'typography', 'type' => 'font_size', 'default' => '14px', 'option_key' => 'phantom_button_font_size', 'description' => 'Button font size'],
    'typography.code.font' => ['name' => 'typography.code.font', 'category' => 'typography', 'type' => 'font_family', 'default' => "'Fira Code', monospace", 'option_key' => 'phantom_code_font', 'description' => 'Code font family'],
    'typography.scale.xs' => ['name' => 'typography.scale.xs', 'category' => 'typography', 'type' => 'font_size', 'default' => '12px', 'option_key' => 'phantom_font_scale_xs', 'description' => 'Extra small font size'],
    'typography.scale.sm' => ['name' => 'typography.scale.sm', 'category' => 'typography', 'type' => 'font_size', 'default' => '14px', 'option_key' => 'phantom_font_scale_sm', 'description' => 'Small font size'],
    'typography.scale.base' => ['name' => 'typography.scale.base', 'category' => 'typography', 'type' => 'font_size', 'default' => '16px', 'option_key' => 'phantom_font_scale_base', 'description' => 'Base font size'],
    'typography.scale.lg' => ['name' => 'typography.scale.lg', 'category' => 'typography', 'type' => 'font_size', 'default' => '20px', 'option_key' => 'phantom_font_scale_lg', 'description' => 'Large font size'],
    'typography.scale.xl' => ['name' => 'typography.scale.xl', 'category' => 'typography', 'type' => 'font_size', 'default' => '24px', 'option_key' => 'phantom_font_scale_xl', 'description' => 'Extra large font size'],
    'typography.scale.2xl' => ['name' => 'typography.scale.2xl', 'category' => 'typography', 'type' => 'font_size', 'default' => '32px', 'option_key' => 'phantom_font_scale_2xl', 'description' => '2X large font size'],
    'typography.scale.3xl' => ['name' => 'typography.scale.3xl', 'category' => 'typography', 'type' => 'font_size', 'default' => '48px', 'option_key' => 'phantom_font_scale_3xl', 'description' => '3X large font size'],
    'typography.scale.4xl' => ['name' => 'typography.scale.4xl', 'category' => 'typography', 'type' => 'font_size', 'default' => '64px', 'option_key' => 'phantom_font_scale_4xl', 'description' => '4X large font size'],

    // ============================================================
    // SPACING (17 tokens)
    // ============================================================
    'space.xs' => ['name' => 'space.xs', 'category' => 'space', 'type' => 'size', 'default' => '4px', 'option_key' => 'phantom_space_xs', 'description' => 'Extra small spacing'],
    'space.sm' => ['name' => 'space.sm', 'category' => 'space', 'type' => 'size', 'default' => '8px', 'option_key' => 'phantom_space_sm', 'description' => 'Small spacing'],
    'space.md' => ['name' => 'space.md', 'category' => 'space', 'type' => 'size', 'default' => '16px', 'option_key' => 'phantom_space_md', 'description' => 'Medium spacing'],
    'space.lg' => ['name' => 'space.lg', 'category' => 'space', 'type' => 'size', 'default' => '32px', 'option_key' => 'phantom_space_lg', 'description' => 'Large spacing'],
    'space.xl' => ['name' => 'space.xl', 'category' => 'space', 'type' => 'size', 'default' => '64px', 'option_key' => 'phantom_space_xl', 'description' => 'Extra large spacing'],
    'space.xxl' => ['name' => 'space.xxl', 'category' => 'space', 'type' => 'size', 'default' => '96px', 'option_key' => 'phantom_space_xxl', 'description' => '2X large spacing'],
    'spacing.section.padding_x' => ['name' => 'spacing.section.padding_x', 'category' => 'spacing', 'type' => 'size', 'default' => '24px', 'option_key' => 'phantom_section_padding_x', 'description' => 'Section horizontal padding'],
    'spacing.section.padding_y' => ['name' => 'spacing.section.padding_y', 'category' => 'spacing', 'type' => 'size', 'default' => '64px', 'option_key' => 'phantom_section_padding_y', 'description' => 'Section vertical padding'],
    'spacing.container.gutter' => ['name' => 'spacing.container.gutter', 'category' => 'spacing', 'type' => 'size', 'default' => '24px', 'option_key' => 'phantom_container_gutter', 'description' => 'Container gutter width'],
    'spacing.content.gap' => ['name' => 'spacing.content.gap', 'category' => 'spacing', 'type' => 'size', 'default' => '32px', 'option_key' => 'phantom_content_gap', 'description' => 'Content section gap'],
    'spacing.element.margin' => ['name' => 'spacing.element.margin', 'category' => 'spacing', 'type' => 'size', 'default' => '24px', 'option_key' => 'phantom_element_margin_bottom', 'description' => 'Element bottom margin'],
    'spacing.widget' => ['name' => 'spacing.widget', 'category' => 'spacing', 'type' => 'size', 'default' => '32px', 'option_key' => 'phantom_widget_spacing', 'description' => 'Widget spacing'],
    'spacing.grid.gap' => ['name' => 'spacing.grid.gap', 'category' => 'spacing', 'type' => 'size', 'default' => '24px', 'option_key' => 'phantom_grid_gap', 'description' => 'Grid gap'],
    'spacing.grid.column_gap' => ['name' => 'spacing.grid.column_gap', 'category' => 'spacing', 'type' => 'size', 'default' => '16px', 'option_key' => 'phantom_grid_column_gap', 'description' => 'Grid column gap'],
    'spacing.grid.row_gap' => ['name' => 'spacing.grid.row_gap', 'category' => 'spacing', 'type' => 'size', 'default' => '16px', 'option_key' => 'phantom_grid_row_gap', 'description' => 'Grid row gap'],
    'spacing.card.padding' => ['name' => 'spacing.card.padding', 'category' => 'spacing', 'type' => 'size', 'default' => '16px', 'option_key' => 'phantom_card_padding', 'description' => 'Card padding'],
    'spacing.button.padding_x' => ['name' => 'spacing.button.padding_x', 'category' => 'spacing', 'type' => 'size', 'default' => '24px', 'option_key' => 'phantom_button_padding_x', 'description' => 'Button horizontal padding'],
    'spacing.button.padding_y' => ['name' => 'spacing.button.padding_y', 'category' => 'spacing', 'type' => 'size', 'default' => '12px', 'option_key' => 'phantom_button_padding_y', 'description' => 'Button vertical padding'],

    // ============================================================
    // BORDER RADIUS (11 tokens)
    // ============================================================
    'radius.none' => ['name' => 'radius.none', 'category' => 'radius', 'type' => 'size', 'default' => '0', 'option_key' => 'phantom_radius_none', 'description' => 'No border radius'],
    'radius.sm' => ['name' => 'radius.sm', 'category' => 'radius', 'type' => 'size', 'default' => '4px', 'option_key' => 'phantom_radius_sm', 'description' => 'Small border radius'],
    'radius.md' => ['name' => 'radius.md', 'category' => 'radius', 'type' => 'size', 'default' => '8px', 'option_key' => 'phantom_radius_md', 'description' => 'Medium border radius'],
    'radius.lg' => ['name' => 'radius.lg', 'category' => 'radius', 'type' => 'size', 'default' => '16px', 'option_key' => 'phantom_radius_lg', 'description' => 'Large border radius'],
    'radius.xl' => ['name' => 'radius.xl', 'category' => 'radius', 'type' => 'size', 'default' => '24px', 'option_key' => 'phantom_radius_xl', 'description' => 'Extra large border radius'],
    'radius.full' => ['name' => 'radius.full', 'category' => 'radius', 'type' => 'size', 'default' => '9999px', 'option_key' => 'phantom_radius_full', 'description' => 'Full/pill border radius'],
    'radius.button' => ['name' => 'radius.button', 'category' => 'radius', 'type' => 'size', 'default' => '8px', 'option_key' => 'phantom_radius_button', 'description' => 'Button border radius'],
    'radius.card' => ['name' => 'radius.card', 'category' => 'radius', 'type' => 'size', 'default' => '8px', 'option_key' => 'phantom_radius_card', 'description' => 'Card border radius'],
    'radius.input' => ['name' => 'radius.input', 'category' => 'radius', 'type' => 'size', 'default' => '8px', 'option_key' => 'phantom_radius_input', 'description' => 'Input border radius'],
    'radius.modal' => ['name' => 'radius.modal', 'category' => 'radius', 'type' => 'size', 'default' => '16px', 'option_key' => 'phantom_radius_modal', 'description' => 'Modal border radius'],
    'radius.badge' => ['name' => 'radius.badge', 'category' => 'radius', 'type' => 'size', 'default' => '4px', 'option_key' => 'phantom_radius_badge', 'description' => 'Badge border radius'],

    // ============================================================
    // SHADOWS (10 tokens)
    // ============================================================
    'shadow.xs' => ['name' => 'shadow.xs', 'category' => 'shadow', 'type' => 'shadow', 'default' => '0 1px 2px rgba(0,0,0,0.05)', 'option_key' => 'phantom_shadow_xs', 'description' => 'Extra small shadow'],
    'shadow.sm' => ['name' => 'shadow.sm', 'category' => 'shadow', 'type' => 'shadow', 'default' => '0 1px 3px rgba(0,0,0,0.1)', 'option_key' => 'phantom_shadow_sm', 'description' => 'Small shadow'],
    'shadow.md' => ['name' => 'shadow.md', 'category' => 'shadow', 'type' => 'shadow', 'default' => '0 4px 6px rgba(0,0,0,0.1)', 'option_key' => 'phantom_shadow_md', 'description' => 'Medium shadow'],
    'shadow.lg' => ['name' => 'shadow.lg', 'category' => 'shadow', 'type' => 'shadow', 'default' => '0 10px 15px rgba(0,0,0,0.1)', 'option_key' => 'phantom_shadow_lg', 'description' => 'Large shadow'],
    'shadow.xl' => ['name' => 'shadow.xl', 'category' => 'shadow', 'type' => 'shadow', 'default' => '0 20px 25px rgba(0,0,0,0.15)', 'option_key' => 'phantom_shadow_xl', 'description' => 'Extra large shadow'],
    'shadow.card' => ['name' => 'shadow.card', 'category' => 'shadow', 'type' => 'shadow', 'default' => '0 2px 4px rgba(0,0,0,0.08)', 'option_key' => 'phantom_shadow_card', 'description' => 'Card shadow'],
    'shadow.button' => ['name' => 'shadow.button', 'category' => 'shadow', 'type' => 'shadow', 'default' => '0 2px 4px rgba(0,0,0,0.15)', 'option_key' => 'phantom_shadow_button', 'description' => 'Button shadow'],
    'shadow.dropdown' => ['name' => 'shadow.dropdown', 'category' => 'shadow', 'type' => 'shadow', 'default' => '0 8px 16px rgba(0,0,0,0.15)', 'option_key' => 'phantom_shadow_dropdown', 'description' => 'Dropdown shadow'],
    'shadow.modal' => ['name' => 'shadow.modal', 'category' => 'shadow', 'type' => 'shadow', 'default' => '0 20px 60px rgba(0,0,0,0.3)', 'option_key' => 'phantom_shadow_modal', 'description' => 'Modal shadow'],
    'shadow.nav' => ['name' => 'shadow.nav', 'category' => 'shadow', 'type' => 'shadow', 'default' => '0 2px 4px rgba(0,0,0,0.05)', 'option_key' => 'phantom_shadow_nav', 'description' => 'Navigation shadow'],

    // ============================================================
    // MOTION (10 tokens)
    // ============================================================
    'motion.duration.fast' => ['name' => 'motion.duration.fast', 'category' => 'motion', 'type' => 'duration', 'default' => '150ms', 'option_key' => 'phantom_motion_duration_fast', 'description' => 'Fast animation duration'],
    'motion.duration.normal' => ['name' => 'motion.duration.normal', 'category' => 'motion', 'type' => 'duration', 'default' => '300ms', 'option_key' => 'phantom_motion_duration_normal', 'description' => 'Normal animation duration'],
    'motion.duration.slow' => ['name' => 'motion.duration.slow', 'category' => 'motion', 'type' => 'duration', 'default' => '500ms', 'option_key' => 'phantom_motion_duration_slow', 'description' => 'Slow animation duration'],
    'motion.easing.default' => ['name' => 'motion.easing.default', 'category' => 'motion', 'type' => 'easing', 'default' => 'cubic-bezier(0.4,0,0.2,1)', 'option_key' => 'phantom_motion_easing', 'description' => 'Default easing function'],
    'motion.easing.in' => ['name' => 'motion.easing.in', 'category' => 'motion', 'type' => 'easing', 'default' => 'cubic-bezier(0.4,0,1,1)', 'option_key' => 'phantom_motion_easing_in', 'description' => 'Ease-in function'],
    'motion.easing.out' => ['name' => 'motion.easing.out', 'category' => 'motion', 'type' => 'easing', 'default' => 'cubic-bezier(0,0,0.2,1)', 'option_key' => 'phantom_motion_easing_out', 'description' => 'Ease-out function'],
    'motion.easing.in_out' => ['name' => 'motion.easing.in_out', 'category' => 'motion', 'type' => 'easing', 'default' => 'cubic-bezier(0.4,0,0.2,1)', 'option_key' => 'phantom_motion_easing_in_out', 'description' => 'Ease-in-out function'],
    'motion.delay' => ['name' => 'motion.delay', 'category' => 'motion', 'type' => 'duration', 'default' => '0ms', 'option_key' => 'phantom_motion_delay', 'description' => 'Animation delay'],
    'motion.stagger' => ['name' => 'motion.stagger', 'category' => 'motion', 'type' => 'duration', 'default' => '50ms', 'option_key' => 'phantom_motion_stagger', 'description' => 'Stagger delay between items'],

    // ============================================================
    // LAYOUT (9 tokens)
    // ============================================================
    'layout.container.width' => ['name' => 'layout.container.width', 'category' => 'layout', 'type' => 'size', 'default' => '1200px', 'option_key' => 'phantom_container_width', 'description' => 'Container max width'],
    'layout.content.width' => ['name' => 'layout.content.width', 'category' => 'layout', 'type' => 'size', 'default' => '800px', 'option_key' => 'phantom_content_width', 'description' => 'Content area width'],
    'layout.sidebar.width' => ['name' => 'layout.sidebar.width', 'category' => 'layout', 'type' => 'size', 'default' => '320px', 'option_key' => 'phantom_sidebar_width', 'description' => 'Sidebar width'],
    'layout.boxed.width' => ['name' => 'layout.boxed.width', 'category' => 'layout', 'type' => 'size', 'default' => '1400px', 'option_key' => 'phantom_boxed_width', 'description' => 'Boxed layout width'],
    'layout.columns' => ['name' => 'layout.columns', 'category' => 'layout', 'type' => 'number', 'default' => '4', 'option_key' => 'phantom_layout_columns', 'description' => 'Grid column count'],
    'layout.header.height' => ['name' => 'layout.header.height', 'category' => 'layout', 'type' => 'size', 'default' => '80px', 'option_key' => 'phantom_header_height', 'description' => 'Header height'],
    'layout.header.mobile_height' => ['name' => 'layout.header.mobile_height', 'category' => 'layout', 'type' => 'size', 'default' => '60px', 'option_key' => 'phantom_header_mobile_height', 'description' => 'Mobile header height'],
    'layout.banner.height' => ['name' => 'layout.banner.height', 'category' => 'layout', 'type' => 'size', 'default' => '400px', 'option_key' => 'phantom_banner_height', 'description' => 'Banner height'],
    'layout.hero.height' => ['name' => 'layout.hero.height', 'category' => 'layout', 'type' => 'size', 'default' => '600px', 'option_key' => 'phantom_hero_height', 'description' => 'Hero section height'],

    // ============================================================
    // 3D / EFFECTS (11 tokens)
    // ============================================================
    'effect.tilt.intensity' => ['name' => 'effect.tilt.intensity', 'category' => 'effect', 'type' => 'number', 'default' => '10', 'option_key' => 'phantom_tilt_intensity', 'description' => 'Tilt effect intensity'],
    'effect.perspective' => ['name' => 'effect.perspective', 'category' => 'effect', 'type' => 'size', 'default' => '1000px', 'option_key' => 'phantom_effect_perspective', 'description' => '3D perspective value'],
    'effect.depth' => ['name' => 'effect.depth', 'category' => 'effect', 'type' => 'size', 'default' => '100px', 'option_key' => 'phantom_effect_depth', 'description' => '3D depth value'],
    'effect.blur.sm' => ['name' => 'effect.blur.sm', 'category' => 'effect', 'type' => 'size', 'default' => '4px', 'option_key' => 'phantom_blur_sm', 'description' => 'Small blur radius'],
    'effect.blur.md' => ['name' => 'effect.blur.md', 'category' => 'effect', 'type' => 'size', 'default' => '8px', 'option_key' => 'phantom_blur_md', 'description' => 'Medium blur radius'],
    'effect.blur.lg' => ['name' => 'effect.blur.lg', 'category' => 'effect', 'type' => 'size', 'default' => '16px', 'option_key' => 'phantom_blur_lg', 'description' => 'Large blur radius'],
    'effect.opacity.normal' => ['name' => 'effect.opacity.normal', 'category' => 'effect', 'type' => 'number', 'default' => '1', 'option_key' => 'phantom_opacity_normal', 'description' => 'Normal opacity'],
    'effect.opacity.hover' => ['name' => 'effect.opacity.hover', 'category' => 'effect', 'type' => 'number', 'default' => '0.8', 'option_key' => 'phantom_opacity_hover', 'description' => 'Hover opacity'],
    'effect.opacity.disabled' => ['name' => 'effect.opacity.disabled', 'category' => 'effect', 'type' => 'number', 'default' => '0.5', 'option_key' => 'phantom_opacity_disabled', 'description' => 'Disabled opacity'],
    'effect.glow' => ['name' => 'effect.glow', 'category' => 'effect', 'type' => 'shadow', 'default' => '0 0 20px rgba(193,18,31,0.3)', 'option_key' => 'phantom_effect_glow', 'description' => 'Glow effect'],
    'effect.glass.reflection' => ['name' => 'effect.glass.reflection', 'category' => 'effect', 'type' => 'color', 'default' => 'rgba(255,255,255,0.1)', 'option_key' => 'phantom_glass_reflection', 'description' => 'Glass reflection highlight'],

    // ============================================================
    // BREAKPOINTS (5 tokens)
    // ============================================================
    'breakpoint.sm' => ['name' => 'breakpoint.sm', 'category' => 'breakpoint', 'type' => 'size', 'default' => '576px', 'option_key' => 'phantom_breakpoint_sm', 'description' => 'Small breakpoint'],
    'breakpoint.md' => ['name' => 'breakpoint.md', 'category' => 'breakpoint', 'type' => 'size', 'default' => '768px', 'option_key' => 'phantom_breakpoint_md', 'description' => 'Medium breakpoint'],
    'breakpoint.lg' => ['name' => 'breakpoint.lg', 'category' => 'breakpoint', 'type' => 'size', 'default' => '992px', 'option_key' => 'phantom_breakpoint_lg', 'description' => 'Large breakpoint'],
    'breakpoint.xl' => ['name' => 'breakpoint.xl', 'category' => 'breakpoint', 'type' => 'size', 'default' => '1200px', 'option_key' => 'phantom_breakpoint_xl', 'description' => 'Extra large breakpoint'],
    'breakpoint.xxl' => ['name' => 'breakpoint.xxl', 'category' => 'breakpoint', 'type' => 'size', 'default' => '1400px', 'option_key' => 'phantom_breakpoint_xxl', 'description' => '2X large breakpoint'],

    // ============================================================
    // Z-INDEX (8 tokens)
    // ============================================================
    'z-index.dropdown' => ['name' => 'z-index.dropdown', 'category' => 'z-index', 'type' => 'number', 'default' => '100', 'option_key' => 'phantom_z_dropdown', 'description' => 'Dropdown z-index'],
    'z-index.sticky' => ['name' => 'z-index.sticky', 'category' => 'z-index', 'type' => 'number', 'default' => '200', 'option_key' => 'phantom_z_sticky', 'description' => 'Sticky z-index'],
    'z-index.fixed' => ['name' => 'z-index.fixed', 'category' => 'z-index', 'type' => 'number', 'default' => '300', 'option_key' => 'phantom_z_fixed', 'description' => 'Fixed z-index'],
    'z-index.modal' => ['name' => 'z-index.modal', 'category' => 'z-index', 'type' => 'number', 'default' => '400', 'option_key' => 'phantom_z_modal', 'description' => 'Modal z-index'],
    'z-index.popover' => ['name' => 'z-index.popover', 'category' => 'z-index', 'type' => 'number', 'default' => '500', 'option_key' => 'phantom_z_popover', 'description' => 'Popover z-index'],
    'z-index.tooltip' => ['name' => 'z-index.tooltip', 'category' => 'z-index', 'type' => 'number', 'default' => '600', 'option_key' => 'phantom_z_tooltip', 'description' => 'Tooltip z-index'],
    'z-index.toast' => ['name' => 'z-index.toast', 'category' => 'z-index', 'type' => 'number', 'default' => '700', 'option_key' => 'phantom_z_toast', 'description' => 'Toast notification z-index'],
    'z-index.loader' => ['name' => 'z-index.loader', 'category' => 'z-index', 'type' => 'number', 'default' => '800', 'option_key' => 'phantom_z_loader', 'description' => 'Loader z-index'],
];
```

- [ ] **Step 2: Verify file syntax**

Run: `php -l includes/Design/data/token-definitions.php`

### Task 4A.3: TokenRegistry Class

**File:** Create `includes/Design/class-token-registry.php`
**Produces:** `TokenRegistry` with methods: `get_all()`, `get()`, `get_by_category()`, `has()`, `get_css_var()`, `get_option_key()`, `get_default()`

- [ ] **Step 1: Write the class**

```php
<?php
declare(strict_types=1);

namespace PhantomCore\Design;

defined('ABSPATH') || exit;

class TokenRegistry {
    private static ?self $instance = null;
    private array $tokens = [];
    private bool $loaded = false;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function load(): void {
        if ($this->loaded) return;
        $this->tokens = require PHANTOM_CORE_PATH . 'includes/Design/data/token-definitions.php';
        $this->loaded = true;
    }

    public function get_all(): array {
        $this->load();
        return $this->tokens;
    }

    public function get(string $name): ?array {
        $this->load();
        return $this->tokens[$name] ?? null;
    }

    public function get_by_category(string $category): array {
        $this->load();
        return array_filter($this->tokens, fn($t) => ($t['category'] ?? '') === $category);
    }

    public function has(string $name): bool {
        $this->load();
        return isset($this->tokens[$name]);
    }

    public function get_css_var(string $name): string {
        return '--' . str_replace('.', '-', $name);
    }

    public function get_option_key(string $name): string {
        $this->load();
        return $this->tokens[$name]['option_key'] ?? 'phantom_' . str_replace('.', '_', $name);
    }

    public function get_default(string $name): mixed {
        $this->load();
        return $this->tokens[$name]['default'] ?? null;
    }

    public function get_type(string $name): ?string {
        $this->load();
        return $this->tokens[$name]['type'] ?? null;
    }

    public function count(): int {
        $this->load();
        return count($this->tokens);
    }
}
```

- [ ] **Step 2: Verify syntax**

Run: `php -l includes/Design/class-token-registry.php`

### Task 4A.4: TokenRegistry Tests

**File:** Create `tests/Design_Token_Registry_Test.php`

- [ ] **Step 1: Write the test**

```php
<?php
use PHPUnit\Framework\TestCase;
use PhantomCore\Design\TokenRegistry;

class Design_Token_Registry_Test extends TestCase {
    private TokenRegistry $registry;

    protected function setUp(): void {
        $this->registry = TokenRegistry::get_instance();
        $this->registry->load();
    }

    public function test_singleton_returns_same_instance(): void {
        $this->assertSame(TokenRegistry::get_instance(), $this->registry);
    }

    public function test_get_all_returns_array(): void {
        $tokens = $this->registry->get_all();
        $this->assertIsArray($tokens);
        $this->assertGreaterThan(100, count($tokens));
    }

    public function test_get_returns_token_definition(): void {
        $token = $this->registry->get('color.primary');
        $this->assertIsArray($token);
        $this->assertSame('color', $token['category']);
        $this->assertSame('#C1121F', $token['default']);
        $this->assertSame('phantom_primary_color', $token['option_key']);
    }

    public function test_get_returns_null_for_nonexistent(): void {
        $this->assertNull($this->registry->get('nonexistent.token'));
    }

    public function test_has_returns_true_for_existing(): void {
        $this->assertTrue($this->registry->has('color.primary'));
    }

    public function test_has_returns_false_for_nonexistent(): void {
        $this->assertFalse($this->registry->has('color.nonexistent'));
    }

    public function test_get_by_category_returns_only_matching(): void {
        $radiusTokens = $this->registry->get_by_category('radius');
        $this->assertNotEmpty($radiusTokens);
        foreach ($radiusTokens as $t) {
            $this->assertSame('radius', $t['category']);
        }
    }

    public function test_get_css_var_converts_dot_notation(): void {
        $this->assertSame('--color-primary', $this->registry->get_css_var('color.primary'));
        $this->assertSame('--typography-heading-font', $this->registry->get_css_var('typography.heading.font'));
        $this->assertSame('--space-xl', $this->registry->get_css_var('space.xl'));
    }

    public function test_get_option_key_returns_configured_key(): void {
        $this->assertSame('phantom_primary_color', $this->registry->get_option_key('color.primary'));
    }

    public function test_get_default_returns_configured_default(): void {
        $this->assertSame('#C1121F', $this->registry->get_default('color.primary'));
    }

    public function test_get_type_returns_correct_type(): void {
        $this->assertSame('color', $this->registry->get_type('color.primary'));
        $this->assertSame('size', $this->registry->get_type('space.md'));
        $this->assertSame('shadow', $this->registry->get_type('shadow.md'));
    }

    public function test_count_returns_expected(): void {
        $this->assertGreaterThanOrEqual(140, $this->registry->count());
    }

    public function test_all_token_names_use_valid_format(): void {
        foreach ($this->registry->get_all() as $name => $def) {
            $this->assertMatchesRegularExpression('/^[a-z0-9]+(\.[a-z0-9_-]+)+$/', $name, "Invalid token name: $name");
        }
    }

    public function test_all_tokens_have_required_fields(): void {
        foreach ($this->registry->get_all() as $name => $def) {
            $this->assertArrayHasKey('name', $def, "Missing name for $name");
            $this->assertArrayHasKey('category', $def, "Missing category for $name");
            $this->assertArrayHasKey('type', $def, "Missing type for $name");
            $this->assertArrayHasKey('default', $def, "Missing default for $name");
            $this->assertArrayHasKey('option_key', $def, "Missing option_key for $name");
        }
    }
}
```

- [ ] **Step 2: Run tests**

Run: `vendor/bin/phpunit tests/Design_Token_Registry_Test.php --verbose`
Expected: All pass

### Task 4A.5: TokenResolver Class

**File:** Create `includes/Design/class-token-resolver.php`

- [ ] **Step 1: Write the class**

```php
<?php
declare(strict_types=1);

namespace PhantomCore\Design;

defined('ABSPATH') || exit;

class TokenResolver {
    private TokenRegistry $registry;
    private array $cache = [];
    private const MAX_INHERITANCE_DEPTH = 5;

    public function __construct() {
        $this->registry = TokenRegistry::get_instance();
    }

    public function resolve(string $name): mixed {
        if (isset($this->cache[$name])) {
            return $this->cache[$name];
        }

        $def = $this->registry->get($name);
        if (null === $def) {
            return null;
        }

        $optionKey = $def['option_key'];
        $value = get_option($optionKey, '__not_set__');

        if ('__not_set__' === $value) {
            $value = $def['default'];
        }

        $value = $this->resolveInheritance($value, 0);
        $value = $this->castValue($value, $def['type'] ?? 'string');

        $this->cache[$name] = $value;
        return $value;
    }

    public function resolveAll(?array $names = null): array {
        $tokens = $names ? array_intersect_key($this->registry->get_all(), array_flip($names)) : $this->registry->get_all();
        $result = [];
        foreach ($tokens as $name => $def) {
            $result[$name] = $this->resolve($name);
        }
        return $result;
    }

    public function resolveCategory(string $category): array {
        $tokens = $this->registry->get_by_category($category);
        $result = [];
        foreach ($tokens as $name => $def) {
            $result[$name] = $this->resolve($name);
        }
        return $result;
    }

    private function resolveInheritance(mixed $value, int $depth): mixed {
        if ($depth > self::MAX_INHERITANCE_DEPTH) {
            return $value;
        }
        if (!is_string($value)) {
            return $value;
        }
        if (preg_match('/^\{([a-z0-9._-]+)\}$/', $value, $matches)) {
            $refName = $matches[1];
            if (!$this->registry->has($refName)) {
                return $value;
            }
            $refValue = get_option($this->registry->get_option_key($refName), '__not_set__');
            if ('__not_set__' === $refValue) {
                $refValue = $this->registry->get_default($refName);
            }
            return $this->resolveInheritance($refValue, $depth + 1);
        }
        return $value;
    }

    private function castValue(mixed $value, string $type): mixed {
        if (!is_string($value)) {
            return $value;
        }
        return match ($type) {
            'number' => is_numeric($value) ? $value : $this->registry->get_default(array_search($type, ['number']) ? '' : ''),
            default => $value,
        };
    }

    public function invalidateCache(): void {
        $this->cache = [];
    }
}
```

- [ ] **Step 2: Verify syntax**

Run: `php -l includes/Design/class-token-resolver.php`

### Task 4A.6: TokenResolver Tests

**File:** Create `tests/Design_Token_Resolver_Test.php`

- [ ] **Step 1: Write the test**

```php
<?php
use PHPUnit\Framework\TestCase;
use PhantomCore\Design\TokenResolver;
use PhantomCore\Design\TokenRegistry;

class Design_Token_Resolver_Test extends TestCase {
    private TokenResolver $resolver;

    protected function setUp(): void {
        // Ensure we get fresh state
        TokenRegistry::get_instance()->load();
        $this->resolver = new TokenResolver();
    }

    public function test_resolve_returns_default_when_option_not_set(): void {
        // color.primary has default #C1121F
        $value = $this->resolver->resolve('color.primary');
        $this->assertSame('#C1121F', $value);
    }

    public function test_resolve_returns_option_value_when_set(): void {
        update_option('phantom_primary_color', '#FF0000');
        $this->resolver->invalidateCache();
        $this->assertSame('#FF0000', $this->resolver->resolve('color.primary'));
    }

    public function test_resolve_returns_null_for_nonexistent_token(): void {
        $this->assertNull($this->resolver->resolve('nonexistent.token'));
    }

    public function test_resolveAll_returns_array(): void {
        $all = $this->resolver->resolveAll();
        $this->assertIsArray($all);
        $this->assertArrayHasKey('color.primary', $all);
        $this->assertArrayHasKey('space.md', $all);
    }

    public function test_resolveAll_with_filter(): void {
        $subset = $this->resolver->resolveAll(['color.primary', 'space.md']);
        $this->assertCount(2, $subset);
        $this->assertArrayHasKey('color.primary', $subset);
        $this->assertArrayHasKey('space.md', $subset);
    }

    public function test_resolveCategory_returns_only_category(): void {
        $radius = $this->resolver->resolveCategory('radius');
        $this->assertNotEmpty($radius);
        foreach ($radius as $name => $value) {
            $this->assertStringStartsWith('radius.', $name);
        }
    }

    public function test_invalidateCache_clears(): void {
        update_option('phantom_primary_color', '#00FF00');
        $this->resolver->invalidateCache();
        $this->assertSame('#00FF00', $this->resolver->resolve('color.primary'));
    }
}
```

- [ ] **Step 2: Run tests**

Run: `vendor/bin/phpunit tests/Design_Token_Resolver_Test.php --verbose`
Expected: All pass

### Task 4A.7: TokenValidator Class

**File:** Create `includes/Design/class-token-validator.php`

- [ ] **Step 1: Write the class**

```php
<?php
declare(strict_types=1);

namespace PhantomCore\Design;

defined('ABSPATH') || exit;

class TokenValidator {
    private TokenRegistry $registry;
    private TokenResolver $resolver;

    public function __construct() {
        $this->registry = TokenRegistry::get_instance();
        $this->resolver = new TokenResolver();
    }

    public function validate(?string $name = null): array {
        if (null !== $name) {
            return [$this->validateSingle($name)];
        }
        return $this->validateAll();
    }

    public function validateAll(): array {
        $results = [];
        foreach ($this->registry->get_all() as $name => $def) {
            $results[] = $this->validateSingle($name);
        }
        return $results;
    }

    public function isHealthy(): bool {
        foreach ($this->validateAll() as $result) {
            if ('error' === $result['status']) {
                return false;
            }
        }
        return true;
    }

    private function validateSingle(string $name): array {
        $def = $this->registry->get($name);
        if (null === $def) {
            return ['token' => $name, 'status' => 'error', 'message' => 'Token not found in registry'];
        }

        $value = $this->resolver->resolve($name);
        if (null === $value) {
            return ['token' => $name, 'status' => 'error', 'message' => 'Failed to resolve value'];
        }

        $type = $def['type'] ?? 'string';
        $error = $this->validateByType($value, $type);

        if (null !== $error) {
            return ['token' => $name, 'status' => 'warning', 'message' => $error, 'context' => ['value' => $value]];
        }

        return ['token' => $name, 'status' => 'ok', 'message' => ''];
    }

    private function validateByType(mixed $value, string $type): ?string {
        if (!is_string($value)) {
            return null;
        }
        return match ($type) {
            'color' => $this->validateColor($value),
            'size' => is_numeric(str_replace(['px', 'rem', 'em', '%', 'vh', 'vw'], '', $value)) ? null : "Invalid size: $value",
            'font_size' => is_numeric(str_replace(['px', 'rem', 'em'], '', $value)) ? null : "Invalid font size: $value",
            'duration' => is_numeric(str_replace(['ms', 's'], '', $value)) ? null : "Invalid duration: $value",
            'number' => is_numeric($value) ? null : "Not a number: $value",
            default => null,
        };
    }

    private function validateColor(string $value): ?string {
        if (preg_match('/^#[0-9a-fA-F]{3,8}$/', $value)) return null;
        if (preg_match('/^rgba?\(/', $value)) return null;
        if (preg_match('/^hsla?\(/', $value)) return null;
        return "Invalid color format: $value";
    }
}
```

- [ ] **Step 2: Verify syntax**

Run: `php -l includes/Design/class-token-validator.php`

### Task 4A.8: TokenValidator Tests

**File:** Create `tests/Design_Token_Validator_Test.php`

- [ ] **Step 1: Write the test**

```php
<?php
use PHPUnit\Framework\TestCase;
use PhantomCore\Design\TokenValidator;
use PhantomCore\Design\TokenRegistry;

class Design_Token_Validator_Test extends TestCase {
    private TokenValidator $validator;

    protected function setUp(): void {
        TokenRegistry::get_instance()->load();
        $this->validator = new TokenValidator();
    }

    public function test_validate_known_token_returns_ok(): void {
        $result = $this->validator->validate('color.primary');
        $this->assertIsArray($result);
        $this->assertSame('ok', $result[0]['status']);
    }

    public function test_validate_unknown_token_returns_error(): void {
        $result = $this->validator->validate('nonexistent');
        $this->assertSame('error', $result[0]['status']);
    }

    public function test_validateAll_returns_results_for_all(): void {
        $results = $this->validator->validateAll();
        $this->assertGreaterThan(100, count($results));
        $allOk = true;
        foreach ($results as $r) {
            if ('error' === $r['status']) {
                $allOk = false;
                break;
            }
        }
        $this->assertTrue($allOk, 'All tokens should validate without errors');
    }

    public function test_isHealthy_returns_true(): void {
        $this->assertTrue($this->validator->isHealthy());
    }
}
```

- [ ] **Step 2: Run tests**

Run: `vendor/bin/phpunit tests/Design_Token_Validator_Test.php --verbose`
Expected: All pass

### Task 4A.9: TokenCompiler + CompiledTokenSet

**Files:**
- Create: `includes/Design/class-compiled-token-set.php`
- Create: `includes/Design/class-token-compiler.php`

- [ ] **Step 1: Write CompiledTokenSet value object**

```php
<?php
declare(strict_types=1);

namespace PhantomCore\Design;

defined('ABSPATH') || exit;

class CompiledTokenSet {
    public array $tokens = [];
    public array $cssVars = [];
    public array $components = [];
    public array $responsive = [];
    public string $css = '';
}
```

- [ ] **Step 2: Write TokenCompiler**

```php
<?php
declare(strict_types=1);

namespace PhantomCore\Design;

defined('ABSPATH') || exit;

class TokenCompiler {
    private TokenRegistry $registry;
    private TokenResolver $resolver;
    private TokenValidator $validator;
    private ?CompiledTokenSet $cached = null;

    public function __construct() {
        $this->registry = TokenRegistry::get_instance();
        $this->resolver = new TokenResolver();
        $this->validator = new TokenValidator();
    }

    public function compile(): CompiledTokenSet {
        if (null !== $this->cached) {
            return $this->cached;
        }

        $set = new CompiledTokenSet();
        $set->tokens = $this->resolver->resolveAll();
        $set->cssVars = $this->buildCssVars($set->tokens);
        $set->components = $this->extractComponents($set->tokens);
        $set->responsive = $this->extractResponsive($set->tokens);

        $this->cached = $set;
        return $set;
    }

    public function compileCategory(string $category): array {
        return $this->resolver->resolveCategory($category);
    }

    public function invalidateCache(): void {
        $this->cached = null;
        $this->resolver->invalidateCache();
    }

    private function buildCssVars(array $tokens): array {
        $vars = [];
        foreach ($tokens as $name => $value) {
            $vars[$name] = [
                'var' => $this->registry->get_css_var($name),
                'value' => $value,
            ];
        }
        return $vars;
    }

    private function extractComponents(array $tokens): array {
        $components = [];
        foreach ($tokens as $name => $value) {
            if (str_starts_with($name, 'component.')) {
                $parts = explode('.', $name);
                $component = $parts[1] ?? 'unknown';
                if (!isset($components[$component])) {
                    $components[$component] = [];
                }
                $components[$component][$name] = $value;
            }
        }
        return $components;
    }

    private function extractResponsive(array $tokens): array {
        $responsive = [];
        foreach ($tokens as $name => $value) {
            if (str_starts_with($name, 'breakpoint.')) {
                $bp = substr($name, strlen('breakpoint.'));
                $responsive[$bp] = $value;
            }
        }
        return $responsive;
    }
}
```

- [ ] **Step 3: Verify syntax**

Run: `php -l includes/Design/class-compiled-token-set.php; php -l includes/Design/class-token-compiler.php`

### Task 4A.10: TokenCompiler Tests

**File:** Create `tests/Design_Token_Compiler_Test.php`

- [ ] **Step 1: Write the test**

```php
<?php
use PHPUnit\Framework\TestCase;
use PhantomCore\Design\TokenCompiler;
use PhantomCore\Design\TokenRegistry;

class Design_Token_Compiler_Test extends TestCase {
    private TokenCompiler $compiler;

    protected function setUp(): void {
        TokenRegistry::get_instance()->load();
        $this->compiler = new TokenCompiler();
    }

    public function test_compile_returns_compiled_token_set(): void {
        $set = $this->compiler->compile();
        $this->assertInstanceOf(\PhantomCore\Design\CompiledTokenSet::class, $set);
    }

    public function test_compile_contains_all_tokens(): void {
        $set = $this->compiler->compile();
        $this->assertGreaterThan(100, count($set->tokens));
        $this->assertArrayHasKey('color.primary', $set->tokens);
    }

    public function test_compile_contains_css_vars(): void {
        $set = $this->compiler->compile();
        $this->assertArrayHasKey('color.primary', $set->cssVars);
        $this->assertSame('--color-primary', $set->cssVars['color.primary']['var']);
    }

    public function test_compile_contains_component_tokens(): void {
        $set = $this->compiler->compile();
        $this->assertIsArray($set->components);
    }

    public function test_compile_contains_responsive_tokens(): void {
        $set = $this->compiler->compile();
        $this->assertArrayHasKey('sm', $set->responsive);
    }

    public function test_invalidateCache_clears(): void {
        $set1 = $this->compiler->compile();
        $this->compiler->invalidateCache();
        // Should get a fresh instance
        $this->assertNotNull($this->compiler->compile());
    }

    public function test_compileCategory_returns_category(): void {
        $radius = $this->compiler->compileCategory('radius');
        $this->assertNotEmpty($radius);
        foreach ($radius as $name => $value) {
            $this->assertStringStartsWith('radius.', $name);
        }
    }
}
```

- [ ] **Step 2: Run tests**

Run: `vendor/bin/phpunit tests/Design_Token_Compiler_Test.php --verbose`
Expected: All pass

### Task 4A.11: CSSVariableGenerator Class

**File:** Create `includes/Design/class-css-variable-generator.php`

- [ ] **Step 1: Write the class**

```php
<?php
declare(strict_types=1);

namespace PhantomCore\Design;

defined('ABSPATH') || exit;

class CSSVariableGenerator {
    private TokenCompiler $compiler;
    private TokenRegistry $registry;
    private ?string $cachedCss = null;

    public function __construct() {
        $this->compiler = new TokenCompiler();
        $this->registry = TokenRegistry::get_instance();
    }

    public function generate(): string {
        if (null !== $this->cachedCss) {
            return $this->cachedCss;
        }

        $set = $this->compiler->compile();
        $css = $this->generateRoot($set);
        $css .= $this->generateComponentScoped($set);
        $css .= $this->generateResponsive($set);

        $this->cachedCss = $css;
        return $css;
    }

    public function generateRoot(CompiledTokenSet $set): string {
        $lines = [':root {'];
        foreach ($set->cssVars as $info) {
            $lines[] = "    {$info['var']}: {$info['value']};";
        }
        $lines[] = '}';
        return implode("\n", $lines) . "\n";
    }

    public function generateComponentScoped(CompiledTokenSet $set): string {
        $css = '';
        foreach ($set->components as $component => $tokens) {
            $className = '.phantom-' . str_replace('_', '-', $component);
            $css .= $className . " {\n";
            foreach ($tokens as $name => $value) {
                $varName = '--component-' . str_replace(['.', '_'], ['-', '-'], substr($name, strlen('component.')));
                $css .= "    {$varName}: {$value};\n";
            }
            $css .= "}\n";
        }
        return $css;
    }

    public function generateResponsive(CompiledTokenSet $set): string {
        $css = '';
        $bpMap = [
            'sm' => '576px', 'md' => '768px',
            'lg' => '992px', 'xl' => '1200px', 'xxl' => '1400px',
        ];
        foreach ($set->responsive as $bp => $val) {
            if (isset($bpMap[$bp])) {
                $bpVal = $set->tokens['breakpoint.' . $bp] ?? $bpMap[$bp];
                $css .= "@media (min-width: {$bpVal}) {\n";
                $css .= "    :root {\n";
                $css .= "        --breakpoint-{$bp}: {$bpVal};\n";
                $css .= "    }\n";
                $css .= "}\n";
            }
        }
        return $css;
    }

    public function getOutputHook(): callable {
        return function (string $css): string {
            return $css . $this->generate();
        };
    }

    public function invalidateCache(): void {
        $this->cachedCss = null;
        $this->compiler->invalidateCache();
    }
}
```

- [ ] **Step 2: Verify syntax**

Run: `php -l includes/Design/class-css-variable-generator.php`

### Task 4A.12: CSSVariableGenerator Tests

**File:** Create `tests/Design_CSS_Variable_Generator_Test.php`

- [ ] **Step 1: Write the test**

```php
<?php
use PHPUnit\Framework\TestCase;
use PhantomCore\Design\CSSVariableGenerator;
use PhantomCore\Design\TokenRegistry;

class Design_CSS_Variable_Generator_Test extends TestCase {
    private CSSVariableGenerator $generator;

    protected function setUp(): void {
        TokenRegistry::get_instance()->load();
        $this->generator = new CSSVariableGenerator();
    }

    public function test_generate_returns_css_string(): void {
        $css = $this->generator->generate();
        $this->assertIsString($css);
        $this->assertStringStartsWith(':root {', $css);
    }

    public function test_generate_contains_semantic_var_names(): void {
        $css = $this->generator->generate();
        $this->assertStringContainsString('--color-primary', $css);
        $this->assertStringContainsString('--space-md', $css);
        $this->assertStringContainsString('--shadow-sm', $css);
    }

    public function test_generate_does_not_contain_legacy_var_names(): void {
        $css = $this->generator->generate();
        $this->assertStringNotContainsString('--primary--color', $css);
        $this->assertStringNotContainsString('--button-bg', $css);
    }

    public function test_generate_ends_with_newline(): void {
        $css = $this->generator->generate();
        $this->assertStringEndsWith("\n", $css);
    }

    public function test_getOutputHook_returns_callable(): void {
        $hook = $this->generator->getOutputHook();
        $this->assertIsCallable($hook);
        $result = $hook('existing-css');
        $this->assertStringStartsWith('existing-css', $result);
        $this->assertStringContainsString('--color-primary', $result);
    }

    public function test_invalidateCache_clears(): void {
        $css1 = $this->generator->generate();
        $this->generator->invalidateCache();
        $css2 = $this->generator->generate();
        $this->assertSame($css1, $css2); // Same values, same output
    }

    public function test_generate_contains_component_scoped_vars(): void {
        $css = $this->generator->generate();
        // Component tokens should generate scoped CSS
        $this->assertStringContainsString('.phantom-', $css);
    }
}
```

- [ ] **Step 2: Run tests**

Run: `vendor/bin/phpunit tests/Design_CSS_Variable_Generator_Test.php --verbose`
Expected: All pass

### Task 4A.13: DesignSystemManager Facade

**File:** Create `includes/Design/class-design-system-manager.php`

- [ ] **Step 1: Write the facade**

```php
<?php
declare(strict_types=1);

namespace PhantomCore\Design;

defined('ABSPATH') || exit;

class DesignSystemManager {
    private static ?self $instance = null;
    private TokenRegistry $registry;
    private TokenResolver $resolver;
    private TokenValidator $validator;
    private TokenCompiler $compiler;
    private CSSVariableGenerator $cssGenerator;
    private bool $initialized = false;

    private function __construct() {
        $this->registry = TokenRegistry::get_instance();
        $this->resolver = new TokenResolver();
        $this->validator = new TokenValidator();
        $this->compiler = new TokenCompiler();
        $this->cssGenerator = new CSSVariableGenerator();
    }

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init(): void {
        if ($this->initialized) return;
        $this->registry->load();
        add_filter('phantom_dynamic_css', $this->cssGenerator->getOutputHook(), 2);
        $this->initialized = true;
    }

    public function token(string $name): mixed {
        return $this->resolver->resolve($name);
    }

    public function tokens(?array $categories = null): array {
        if (null === $categories) {
            return $this->resolver->resolveAll();
        }
        $result = [];
        foreach ($categories as $cat) {
            $result[$cat] = $this->resolver->resolveCategory($cat);
        }
        return $result;
    }

    public function cssVar(string $name): string {
        return $this->registry->get_css_var($name);
    }

    public function allCssVars(): array {
        $set = $this->compiler->compile();
        $vars = [];
        foreach ($set->cssVars as $name => $info) {
            $vars[$info['var']] = $info['value'];
        }
        return $vars;
    }

    public function generateCSS(): string {
        return $this->cssGenerator->generate();
    }

    public function validate(): array {
        return $this->validator->validateAll();
    }

    public function compile(): CompiledTokenSet {
        return $this->compiler->compile();
    }

    // Preset methods will be added in Phase 4B
    public function applyPreset(string $id): bool {
        return false; // Stub — implemented in Phase 4B
    }

    public function availablePresets(): array {
        return []; // Stub — implemented in Phase 4B
    }

    public function currentPreset(): ?array {
        return null; // Stub — implemented in Phase 4B
    }

    public function currentThemeDNA(): array {
        return []; // Stub — implemented in Phase 4B
    }

    public function exportPreset(string $id): string {
        return '{}'; // Stub — implemented in Phase 4B
    }

    public function importPreset(string $json): bool {
        return false; // Stub — implemented in Phase 4B
    }
}

// Global facade accessor
class_alias(DesignSystemManager::class, 'DesignSystem');

if (!function_exists('DesignSystem')) {
    function DesignSystem(): DesignSystemManager {
        return DesignSystemManager::get_instance();
    }
}
```

- [ ] **Step 2: Verify syntax**

Run: `php -l includes/Design/class-design-system-manager.php`

### Task 4A.14: DesignSystemManager Tests

**File:** Create `tests/Design_System_Manager_Test.php`

- [ ] **Step 1: Write the test**

```php
<?php
use PHPUnit\Framework\TestCase;
use PhantomCore\Design\DesignSystemManager;

class Design_System_Manager_Test extends TestCase {
    private DesignSystemManager $dsm;

    protected function setUp(): void {
        $this->dsm = DesignSystemManager::get_instance();
        $this->dsm->init();
    }

    public function test_singleton(): void {
        $this->assertSame($this->dsm, DesignSystemManager::get_instance());
    }

    public function test_token_returns_value(): void {
        $this->assertSame('#C1121F', $this->dsm->token('color.primary'));
    }

    public function test_tokens_returns_all(): void {
        $all = $this->dsm->tokens();
        $this->assertArrayHasKey('color.primary', $all);
    }

    public function test_tokens_with_categories(): void {
        $result = $this->dsm->tokens(['radius', 'shadow']);
        $this->assertArrayHasKey('radius', $result);
        $this->assertArrayHasKey('shadow', $result);
    }

    public function test_cssVar_returns_var_name(): void {
        $this->assertSame('--color-primary', $this->dsm->cssVar('color.primary'));
    }

    public function test_allCssVars_returns_map(): void {
        $vars = $this->dsm->allCssVars();
        $this->assertArrayHasKey('--color-primary', $vars);
        $this->assertGreaterThan(100, count($vars));
    }

    public function test_generateCSS_returns_css(): void {
        $css = $this->dsm->generateCSS();
        $this->assertStringContainsString('--color-primary', $css);
    }

    public function test_validate_returns_array(): void {
        $results = $this->dsm->validate();
        $this->assertGreaterThan(100, count($results));
    }

    public function test_compile_returns_compiled_token_set(): void {
        $set = $this->dsm->compile();
        $this->assertInstanceOf(\PhantomCore\Design\CompiledTokenSet::class, $set);
    }

    // Global facade
    public function test_design_system_function_exists(): void {
        $this->assertTrue(function_exists('DesignSystem'));
    }

    public function test_design_system_function_returns_manager(): void {
        $this->assertInstanceOf(DesignSystemManager::class, DesignSystem());
    }

    public function test_init_hooks_filter(): void {
        $this->assertSame(2, has_filter('phantom_dynamic_css'));
    }
}
```

- [ ] **Step 2: Run tests**

Run: `vendor/bin/phpunit tests/Design_System_Manager_Test.php --verbose`
Expected: All pass

### Task 4A.15: New Settings in Settings_Registry

**File:** Modify `includes/class-settings-registry.php`

- [ ] **Step 1: Add section_design_tokens method**

Add a new section method and wire it into `define_entries()`:

```php
// In define_entries(), add to the $sections array:
'design_tokens' => $this->section_design_tokens(),

// Add method:
private function section_design_tokens(): array {
    $settings = [];
    $tokenFile = PHANTOM_CORE_PATH . 'includes/Design/data/token-definitions.php';
    if (!file_exists($tokenFile)) {
        return $settings;
    }
    $tokenDefs = require $tokenFile;
    foreach ($tokenDefs as $name => $def) {
        // Only register tokens that don't already have settings
        $key = str_replace(['.', '-'], '_', $name);
        if (!str_starts_with($def['option_key'], 'phantom_')) {
            continue;
        }
        $settingKey = substr($def['option_key'], strlen('phantom_'));
        if (isset($settings[$settingKey])) {
            continue;
        }
        $type = $def['type'] ?? 'text';
        $settings[$settingKey] = [
            'default' => $def['default'] ?? '',
            'type' => $type,
            'section' => 'design_tokens',
            'sanitize' => match ($type) {
                'color' => 'sanitize_hex_color',
                'number' => 'absint',
                default => 'sanitize_text_field',
            },
        ];
    }
    return $settings;
}
```

- [ ] **Step 2: Add section label for design_tokens**

Find the `get_sections()` method or similar and add:
```php
'design_tokens' => __('Design Tokens', 'phantom-core'),
```

- [ ] **Step 3: Verify syntax**

Run: `php -l includes/class-settings-registry.php`

### Task 4A.16: Phase 4A Integration Verification

- [ ] **Step 1: Run all existing tests to ensure no regressions**

Run: `vendor/bin/phpunit`
Expected: All tests pass (existing + new)

- [ ] **Step 2: Run PHP lint on all new files**

Run: `Get-ChildItem -Recurse includes/Design/*.php | ForEach-Object { php -l $_.FullName }`
Expected: No syntax errors

- [ ] **Step 3: Verify CSS generator output is valid**

Create a quick test script:
```php
<?php
require_once 'tests/bootstrap.php';
\PhantomCore\Design\DesignSystemManager::get_instance()->init();
$css = apply_filters('phantom_dynamic_css', '');
echo $css;
```

Run it and verify: `--color-primary` appears, no `--primary--color` or `--button-bg` (those are legacy names)

- [ ] **Step 4: Run full test suite**

Run: `vendor/bin/phpunit --verbose`
Expected: 0 failures, 0 errors

---

## Phase 4B — Preset Infrastructure

**Goal:** Preset value object, PresetManager, PresetRegistry, 4 providers, ThemeDNAEngine, 7 foundation presets

### Task 4B.1: Preset Value Object

**File:** Create `includes/Design/class-preset.php`

Key methods: `from_array()`, `to_array()`, `to_json()`, `merge()`, `isCompatible()`

```php
class Preset {
    public string $id;
    public string $name;
    public string $source;     // 'core' | 'demo' | 'user'
    public string $version;
    public string $framework;  // semver constraint
    public string $author;
    public array $tokens;      // token_name => value
    public array $dna;         // Theme DNA settings
    public array $metadata;    // description, tags, thumbnail
    public ?string $parent;    // parent preset ID

    public static function from_array(array $data): self;
    public function to_array(): array;
    public function to_json(): string;
    public function merge(self $parent): self;
    public function isCompatible(string $frameworkVersion): bool;
}
```

### Task 4B.2: Foundation Presets Data (7 presets)

**File:** Create `includes/Design/data/presets.php`

```php
return [
    'core:light' => [
        'id' => 'core:light',
        'name' => 'Light',
        'source' => 'core',
        'version' => '1.0.0',
        'framework' => '>=1.5.0',
        'author' => 'Phantom Core',
        'dna' => [
            'design_style' => 'classic', 'motion_style' => 'subtle',
            'shape_style' => 'sharp', 'typography_style' => 'sans',
            'elevation_style' => 'soft', 'color_style' => 'neutral',
        ],
        'tokens' => [
            'color.primary' => '#C1121F', 'color.background' => '#FFFFFF',
            'color.text.primary' => '#333333',
            'typography.heading.font' => "'Playfair Display', serif",
            'typography.body.font' => "'Inter', sans-serif",
            'shadow.sm' => '0 1px 3px rgba(0,0,0,0.1)',
            'radius.md' => '8px',
        ],
    ],
    'core:dark' => [
        'id' => 'core:dark',
        'name' => 'Dark',
        'source' => 'core',
        'version' => '1.0.0',
        'framework' => '>=1.5.0',
        'author' => 'Phantom Core',
        'dna' => [
            'design_style' => 'modern', 'motion_style' => 'dynamic',
            'shape_style' => 'rounded', 'typography_style' => 'sans',
            'elevation_style' => 'floating', 'color_style' => 'vibrant',
        ],
        'tokens' => [
            'color.primary' => '#E53935', 'color.background' => '#121212',
            'color.surface' => '#1E1E1E', 'color.text.primary' => '#FFFFFF',
            'color.border' => '#333333',
        ],
    ],
    'core:minimal' => [
        'id' => 'core:minimal',
        'name' => 'Minimal',
        'source' => 'core',
        'version' => '1.0.0',
        'framework' => '>=1.5.0',
        'author' => 'Phantom Core',
        'dna' => [
            'design_style' => 'minimal', 'motion_style' => 'subtle',
            'shape_style' => 'sharp', 'typography_style' => 'sans',
            'elevation_style' => 'flat', 'color_style' => 'monochrome',
        ],
        'tokens' => [
            'color.primary' => '#000000', 'color.background' => '#FFFFFF',
            'color.surface' => '#F8F8F8', 'color.text.primary' => '#111111',
            'space.lg' => '48px', 'space.xl' => '96px',
            'shadow.xs' => 'none', 'shadow.sm' => 'none',
            'shadow.md' => 'none', 'shadow.lg' => 'none',
            'shadow.xl' => 'none',
        ],
    ],
    'core:modern' => [
        'id' => 'core:modern',
        'name' => 'Modern',
        'source' => 'core',
        'version' => '1.0.0',
        'framework' => '>=1.5.0',
        'author' => 'Phantom Core',
        'dna' => [
            'design_style' => 'modern', 'motion_style' => 'dynamic',
            'shape_style' => 'sharp', 'typography_style' => 'sans',
            'elevation_style' => 'floating', 'color_style' => 'vibrant',
        ],
        'tokens' => [
            'color.primary' => '#6C63FF', 'color.secondary' => '#FF6584',
            'typography.heading.font' => "'Inter', sans-serif",
            'typography.h1.size' => '64px',
            'shadow.lg' => '0 20px 40px rgba(108,99,255,0.15)',
        ],
    ],
    'core:luxury' => [
        'id' => 'core:luxury',
        'name' => 'Luxury',
        'source' => 'core',
        'version' => '1.0.0',
        'framework' => '>=1.5.0',
        'author' => 'Phantom Core',
        'dna' => [
            'design_style' => 'luxury', 'motion_style' => 'elegant',
            'shape_style' => 'rounded', 'typography_style' => 'serif',
            'elevation_style' => 'soft', 'color_style' => 'vibrant',
        ],
        'tokens' => [
            'color.primary' => '#000000', 'color.secondary' => '#D4AF37',
            'color.accent' => '#8B7355',
            'typography.heading.font' => "'Playfair Display', serif",
            'typography.body.font' => "'Inter', sans-serif",
            'radius.md' => '12px', 'radius.lg' => '24px',
            'space.md' => '24px', 'space.lg' => '48px', 'space.xl' => '96px',
            'motion.duration.normal' => '400ms',
            'shadow.md' => '0 8px 24px rgba(0,0,0,0.12)',
        ],
    ],
    'core:classic' => [
        'id' => 'core:classic',
        'name' => 'Classic',
        'source' => 'core',
        'version' => '1.0.0',
        'framework' => '>=1.5.0',
        'author' => 'Phantom Core',
        'dna' => [
            'design_style' => 'classic', 'motion_style' => 'subtle',
            'shape_style' => 'sharp', 'typography_style' => 'serif',
            'elevation_style' => 'soft', 'color_style' => 'neutral',
        ],
        'tokens' => [
            'color.primary' => '#1A365D', 'color.secondary' => '#2D3748',
            'typography.heading.font' => "'Playfair Display', serif",
            'typography.body.font' => "'Source Serif 4', serif",
            'radius.sm' => '2px', 'radius.md' => '4px',
        ],
    ],
    'core:glass' => [
        'id' => 'core:glass',
        'name' => 'Glass',
        'source' => 'core',
        'version' => '1.0.0',
        'framework' => '>=1.5.0',
        'author' => 'Phantom Core',
        'dna' => [
            'design_style' => 'modern', 'motion_style' => 'smooth',
            'shape_style' => 'rounded', 'typography_style' => 'sans',
            'elevation_style' => 'glass', 'color_style' => 'vibrant',
        ],
        'tokens' => [
            'color.primary' => '#7C3AED', 'color.secondary' => '#EC4899',
            'color.surface' => 'rgba(255,255,255,0.1)',
            'color.border' => 'rgba(255,255,255,0.2)',
            'effect.glass.reflection' => 'rgba(255,255,255,0.15)',
            'effect.blur.md' => '12px',
            'shadow.card' => '0 8px 32px rgba(0,0,0,0.1)',
        ],
    ],
];
```

### Task 4B.3: PresetProviderInterface

**File:** Create `includes/Design/Providers/interface-preset-provider.php`

```php
interface PresetProviderInterface {
    public function get_presets(): array;
    public function get_preset(string $id): ?Preset;
    public function exists(string $id): bool;
    public function source(): string;
}
```

### Task 4B.4: CoreProvider

**File:** Create `includes/Design/Providers/class-core-provider.php`

Returns 7 foundation presets from `data/presets.php`.

### Task 4B.5: DemoProvider

**File:** Create `includes/Design/Providers/class-demo-provider.php`

Scans `frontend/templates/{slug}/presets/*.json`. Parses JSON files into Preset objects.

### Task 4B.6: UserProvider

**File:** Create `includes/Design/Providers/class-user-provider.php`

CRUD for stored user presets in `phantom_user_presets` WP option.

### Task 4B.7: PresetRegistry

**File:** Create `includes/Design/class-preset-registry.php`

Discovers presets from providers. Merges with priority (User > Demo > Core). Methods: `register_provider()`, `get_all()`, `get()`, `has()`, `get_by_source()`.

### Task 4B.8: PresetManager

**File:** Create `includes/Design/class-preset-manager.php`

Methods: `apply()`, `save()`, `delete()`, `duplicate()`, `current()`, `reset()`.

`apply()` flow: get preset from registry → validate version → for each token, `update_option(option_key, value)` → update DNA → store active preset ID → invalidate CSS caches.

### Task 4B.9: ThemeDNAEngine

**File:** Create `includes/Design/class-theme-dna-engine.php`

Defines 6 DNA dimensions with their values. Stores in `phantom_theme_dna` option. Methods: `getDimensions()`, `getCurrent()`, `set()`, `applyOverrides()`.

### Task 4B.10: Phase 4B Tests & Verification

Test files:
- `tests/Design_Preset_Test.php`
- `tests/Design_Preset_Manager_Test.php`
- `tests/Design_Preset_Registry_Test.php`
- `tests/Design_Theme_DNA_Engine_Test.php`
- `tests/Design_Core_Provider_Test.php`
- `tests/Design_Demo_Provider_Test.php`
- `tests/Design_User_Provider_Test.php`

Run: `vendor/bin/phpunit` — all pass (existing + new)

---

## Phase 4C — Admin UI

**Goal:** Top-level Phantom menu, Design Studio, migrated Theme Options + Demo Manager, skeleton pages, 14 sub-pages total.

### Task 4C.1: Phantom Admin Menu

**File:** Create `admin/class-phantom-admin.php`

Registers top-level menu `PHANTOM` with `dashicons-star-filled` and 14 sub-pages.

### Task 4C.2: Dashboard Page

**File:** Create `admin/class-dashboard-page.php`

Shows: framework version, active demo, active preset, token health, CSS var count, quick actions.

### Task 4C.3: Design Studio Page (Multi-Tab)

**File:** Create `admin/class-design-studio-page.php`

9 tabs: Presets, Theme DNA, Colors, Typography, Spacing, Motion, 3D, Tokens, CSS Preview.

### Task 4C.4-12: Skeleton Pages

8 skeleton pages, one per file:
- `admin/class-component-library-page.php`
- `admin/class-template-manager-page.php`
- `admin/class-animation-studio-page.php`
- `admin/class-asset-manager-page.php`
- `admin/class-performance-page.php`
- `admin/class-seo-page.php`
- `admin/class-backup-restore-page.php`
- `admin/class-developer-page.php`
- `admin/class-system-page.php`

### Task 4C.13: Theme Options Migration

Modify `admin/class-settings-page.php`:
- Register under Phantom menu instead of standalone
- Update page title/slug

### Task 4C.14: Demo Manager Migration

Modify `admin/class-demo-admin.php`:
- Change menu parent to `phantom-dashboard`
- Update menu slug if needed

### Task 4C.15: Design Studio CSS + JS

Create `admin/css/design-studio.css` and `admin/js/design-studio.js` for tab switching and AJAX preset operations.

### Task 4C.16: Import/Export Admin Page

**File:** Create `admin/class-import-export-page.php`

Upload JSON file to import preset. Download current preset as JSON.

### Task 4C.17: Phase 4C Tests & Verification

Test files:
- `tests/Design_Admin_Menu_Test.php`
- `tests/Design_Design_Studio_Test.php`
- `tests/Design_Import_Export_Test.php`

Run: `vendor/bin/phpunit`

---

## Phase 4D — Integration & Polish

**Goal:** Customizer quick-edit, Export/Import classes, Demo Manager integration, final 100/100 verification.

### Task 4D.1: DesignExporter Class

**File:** Create `includes/Design/class-design-exporter.php`

Reads current token values + DNA, exports as JSON matching preset schema.

### Task 4D.2: DesignImporter Class

**File:** Create `includes/Design/class-design-importer.php`

Validates JSON, checks framework compatibility, applies preset.

### Task 4D.3: ImportProvider

**File:** Create `includes/Design/Providers/class-import-provider.php`

Temporary provider for imported presets during import workflow.

### Task 4D.4: Customizer Design Panel

**File:** Create `admin/class-customizer-design-panel.php`

Registers `phantom_design_panel` with controls: preset selector, color pickers, font selectors.

### Task 4D.5: Customizer Integration

Modify `includes/class-customizer.php` to register the Design panel. Add `customize_save_after` hook to clear Design System cache.

### Task 4D.6: Demo Manager Preset Assignment

Modify `admin/class-demo-admin.php` to show "Default Preset" field. On demo activation, apply the default preset.

### Task 4D.7: Final Integration & Full Suite

- Run `vendor/bin/phpunit` — all pass
- PHP lint all files
- Verify debug log empty
- Update AGENTS.md with Phase 4 status
- Update Serena memory

---

## Self-Review Checklist

- [x] Phase 4A has concrete code for all 15 tasks with exact file paths
- [x] Phase 4B-4D have task outlines with clear specifications (deferred detailed code to match existing patterns from 4A)
- [x] All tasks reference existing patterns (singleton, autoloader, test conventions)
- [x] No placeholder text or TBD in tasks
- [x] Type consistency maintained across method signatures
- [x] Each sub-phase produces independently testable software
- [x] Testing strategy covers all new classes
