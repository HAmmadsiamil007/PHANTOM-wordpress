<?php
declare(strict_types=1);

namespace PhantomCore\Design;

defined('ABSPATH') || exit;

return [
    // ============================================================
    // COLORS (41 tokens)
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
    // TYPOGRAPHY (27 tokens)
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
    // SPACING (18 tokens)
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
    // MOTION (9 tokens)
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
    // COMPONENT-SPECIFIC TOKENS (30 tokens) — Phase 5F
    // ============================================================
    'component.button.border_width' => ['name' => 'component.button.border_width', 'category' => 'component', 'type' => 'size', 'default' => '2px', 'option_key' => 'phantom_component_button_border_width', 'description' => 'Button border width'],
    'component.button.radius' => ['name' => 'component.button.radius', 'category' => 'component', 'type' => 'size', 'default' => '8px', 'option_key' => 'phantom_component_button_radius', 'description' => 'Button border radius'],
    'component.button.font_weight' => ['name' => 'component.button.font_weight', 'category' => 'component', 'type' => 'select', 'default' => '600', 'option_key' => 'phantom_component_button_font_weight', 'description' => 'Button font weight'],
    'component.button.text_transform' => ['name' => 'component.button.text_transform', 'category' => 'component', 'type' => 'select', 'default' => 'none', 'option_key' => 'phantom_component_button_text_transform', 'description' => 'Button text transform'],
    'component.card.radius' => ['name' => 'component.card.radius', 'category' => 'component', 'type' => 'size', 'default' => '8px', 'option_key' => 'phantom_component_card_radius', 'description' => 'Card border radius'],
    'component.card.padding' => ['name' => 'component.card.padding', 'category' => 'component', 'type' => 'size', 'default' => '16px', 'option_key' => 'phantom_component_card_padding', 'description' => 'Card padding'],
    'component.card.shadow' => ['name' => 'component.card.shadow', 'category' => 'component', 'type' => 'shadow', 'default' => '0 2px 8px rgba(0,0,0,0.08)', 'option_key' => 'phantom_component_card_shadow', 'description' => 'Card shadow'],
    'component.card.hover_shadow' => ['name' => 'component.card.hover_shadow', 'category' => 'component', 'type' => 'shadow', 'default' => '0 8px 24px rgba(0,0,0,0.12)', 'option_key' => 'phantom_component_card_hover_shadow', 'description' => 'Card hover shadow'],
    'component.card.image_height' => ['name' => 'component.card.image_height', 'category' => 'component', 'type' => 'size', 'default' => '280px', 'option_key' => 'phantom_component_card_image_height', 'description' => 'Card image height'],
    'component.hero.height' => ['name' => 'component.hero.height', 'category' => 'component', 'type' => 'size', 'default' => '600px', 'option_key' => 'phantom_component_hero_height', 'description' => 'Hero section height'],
    'component.hero.mobile_height' => ['name' => 'component.hero.mobile_height', 'category' => 'component', 'type' => 'size', 'default' => '400px', 'option_key' => 'phantom_component_hero_mobile_height', 'description' => 'Hero mobile height'],
    'component.hero.overlay' => ['name' => 'component.hero.overlay', 'category' => 'component', 'type' => 'color', 'default' => 'rgba(0,0,0,0.3)', 'option_key' => 'phantom_component_hero_overlay', 'description' => 'Hero overlay color'],
    'component.hero.title_size' => ['name' => 'component.hero.title_size', 'category' => 'component', 'type' => 'font_size', 'default' => '48px', 'option_key' => 'phantom_component_hero_title_size', 'description' => 'Hero title font size'],
    'component.modal.width' => ['name' => 'component.modal.width', 'category' => 'component', 'type' => 'size', 'default' => '600px', 'option_key' => 'phantom_component_modal_width', 'description' => 'Modal max width'],
    'component.modal.radius' => ['name' => 'component.modal.radius', 'category' => 'component', 'type' => 'size', 'default' => '16px', 'option_key' => 'phantom_component_modal_radius', 'description' => 'Modal border radius'],
    'component.modal.backdrop' => ['name' => 'component.modal.backdrop', 'category' => 'component', 'type' => 'color', 'default' => 'rgba(0,0,0,0.5)', 'option_key' => 'phantom_component_modal_backdrop', 'description' => 'Modal backdrop color'],
    'component.badge.radius' => ['name' => 'component.badge.radius', 'category' => 'component', 'type' => 'size', 'default' => '4px', 'option_key' => 'phantom_component_badge_radius', 'description' => 'Badge border radius'],
    'component.badge.font_size' => ['name' => 'component.badge.font_size', 'category' => 'component', 'type' => 'font_size', 'default' => '11px', 'option_key' => 'phantom_component_badge_font_size', 'description' => 'Badge font size'],
    'component.input.height' => ['name' => 'component.input.height', 'category' => 'component', 'type' => 'size', 'default' => '48px', 'option_key' => 'phantom_component_input_height', 'description' => 'Input field height'],
    'component.input.radius' => ['name' => 'component.input.radius', 'category' => 'component', 'type' => 'size', 'default' => '8px', 'option_key' => 'phantom_component_input_radius', 'description' => 'Input border radius'],
    'component.input.border' => ['name' => 'component.input.border', 'category' => 'component', 'type' => 'color', 'default' => '#E0E0E0', 'option_key' => 'phantom_component_input_border', 'description' => 'Input border color'],
    'component.input.focus' => ['name' => 'component.input.focus', 'category' => 'component', 'type' => 'color', 'default' => '#C1121F', 'option_key' => 'phantom_component_input_focus', 'description' => 'Input focus border color'],
    'component.nav.font_size' => ['name' => 'component.nav.font_size', 'category' => 'component', 'type' => 'font_size', 'default' => '14px', 'option_key' => 'phantom_component_nav_font_size', 'description' => 'Navigation font size'],
    'component.nav.link_gap' => ['name' => 'component.nav.link_gap', 'category' => 'component', 'type' => 'size', 'default' => '24px', 'option_key' => 'phantom_component_nav_link_gap', 'description' => 'Navigation link spacing'],
    'component.nav.dropdown_radius' => ['name' => 'component.nav.dropdown_radius', 'category' => 'component', 'type' => 'size', 'default' => '8px', 'option_key' => 'phantom_component_nav_dropdown_radius', 'description' => 'Dropdown border radius'],
    'component.footer.padding' => ['name' => 'component.footer.padding', 'category' => 'component', 'type' => 'size', 'default' => '64px', 'option_key' => 'phantom_component_footer_padding', 'description' => 'Footer vertical padding'],
    'component.footer.widget_gap' => ['name' => 'component.footer.widget_gap', 'category' => 'component', 'type' => 'size', 'default' => '32px', 'option_key' => 'phantom_component_footer_widget_gap', 'description' => 'Footer widget spacing'],
    'component.section.padding' => ['name' => 'component.section.padding', 'category' => 'component', 'type' => 'size', 'default' => '80px', 'option_key' => 'phantom_component_section_padding', 'description' => 'Section vertical padding'],
    'component.section.mobile_padding' => ['name' => 'component.section.mobile_padding', 'category' => 'component', 'type' => 'size', 'default' => '48px', 'option_key' => 'phantom_component_section_mobile_padding', 'description' => 'Section mobile padding'],
    'component.grid.columns' => ['name' => 'component.grid.columns', 'category' => 'component', 'type' => 'number', 'default' => '4', 'option_key' => 'phantom_component_grid_columns', 'description' => 'Default grid columns'],

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
