<?php
/**
 * One-shot migration: update all color/button settings to AETHER dark palette defaults.
 * Run: wp eval-file tools/migrate-aether-colors.php --path=/var/www/html
 */

defined('ABSPATH') || define('ABSPATH', dirname(__DIR__, 4) . '/wp-load.php');
if (!defined('WPINC')) {
    require_once dirname(__DIR__, 4) . '/wp-load.php';
}

// AETHER dark palette color defaults (matching class-settings-loader.php)
$colors = [
    'color_primary'                  => '#C8956C',
    'color_secondary'                => '#A8B5C0',
    'color_accent'                   => '#705B53',
    'color_background'               => '#09090B',
    'color_text'                     => '#A8B5C0',
    'color_heading'                  => '#FFFFFF',
    'color_link'                     => '#C8956C',
    'color_link_hover'               => '#D4A574',
    'color_border'                   => '#2D2D2D',
    'color_light_bg'                 => '#141416',
    'color_grey'                     => '#6B7280',
    'color_success'                  => '#22C55E',
    'color_error'                    => '#EF4444',
    'color_warning'                  => '#F59E0B',
    'color_info'                     => '#3B82F6',
    'color_gradient_start'           => '#C8956C',
    'color_gradient_end'             => '#09090B',
    'color_featured_badge'           => '#C8956C',
    'color_header_bg'                => '#09090B',
    'color_header_text'              => '#A8B5C0',
    'color_footer_bg'                => '#09090B',
    'color_footer_text'              => '#6B7280',
    'color_topbar_bg'                => '#141416',
    'color_topbar_text'              => '#6B7280',
    'color_announcement_bg'          => '#C8956C',
    'color_announcement_text'        => '#09090B',
    'color_hero_bg'                  => '#09090B',
    'color_card_bg'                  => '#141416',
    'color_card_text'                => '#A8B5C0',
    'color_card_border'              => '#2D2D2D',
    'color_rating'                   => '#F59E0B',
    'color_sale'                     => '#EF4444',
    'color_button_bg'                => '#C8956C',
    'color_button_text'              => '#09090B',
    'color_button_hover_bg'          => '#D4A574',
    'color_badge_sale_bg'            => '#EF4444',
    'color_badge_sale_text'          => '#FFFFFF',
    'color_badge_new_bg'             => '#22C55E',
    'color_badge_new_text'           => '#09090B',
    // Buttons tab
    'button_bg'                      => '#C8956C',
    'button_text'                    => '#09090B',
    'button_bg_hover'                => '#D4A574',
    'button_text_hover'              => '#09090B',
    'button_border_radius'           => 4,
    'button_padding_x'               => 24,
    'button_padding_y'               => 12,
    'button_font_size'               => 16,
    'button_font_weight'             => 600,
    'button_text_transform'          => 'none',
    'button_border_width'            => 0,
    'button_border_color'            => '#C8956C',
    // Typography tab
    'typography_body_font'           => 'Archivo',
    'typography_heading_font'        => 'Archivo',
    'typography_body_font_size'      => 16,
    'typography_body_line_height'    => 1.6,
    'typography_h1_font_size'        => 48,
    'typography_h2_font_size'        => 36,
    'typography_h3_font_size'        => 28,
    'typography_h4_font_size'        => 20,
    'typography_h5_font_size'        => 16,
    'typography_h6_font_size'        => 14,
    'typography_h1_line_height'      => 1.2,
    'typography_h2_line_height'      => 1.25,
    'typography_h3_line_height'      => 1.3,
    'typography_h4_line_height'      => 1.35,
    'typography_h5_line_height'      => 1.4,
    'typography_h6_line_height'      => 1.4,
    'typography_body_letter_spacing' => 0,
    'typography_h1_letter_spacing'   => -1,
    'typography_h2_letter_spacing'   => -0.5,
    'typography_h3_letter_spacing'   => -0.25,
    'typography_body_text_align'     => 'left',
    'typography_h1_text_align'       => 'left',
    'typography_h2_text_align'       => 'left',
    'typography_h3_text_align'       => 'left',
];

$updated_count = 0;
$errors = [];

foreach ($colors as $key => $default) {
    $option_name = 'phantom_' . $key;
    $current = get_option($option_name, '__NOT_SET__');
    if ('__NOT_SET__' === $current) {
        // Option doesn't exist — add it
        $result = add_option($option_name, $default, '', false);
        if ($result) {
            echo "CREATED: {$option_name} = {$default}\n";
            $updated_count++;
        } else {
            $errors[] = "Failed to create: {$option_name}";
        }
    } elseif ($current !== $default) {
        // Option exists with different value — update
        $result = update_option($option_name, $default);
        if ($result) {
            echo "UPDATED: {$option_name}: '{$current}' → '{$default}'\n";
            $updated_count++;
        } else {
            $errors[] = "Failed to update: {$option_name}";
        }
    } else {
        echo "SKIP (unchanged): {$option_name} = {$default}\n";
    }
}

// Also rebuild phantom_options bulk array
echo "\n--- Rebuilding phantom_options bulk array ---\n";
$phantom_options = get_option('phantom_options', []);
$entries = \PhantomCore\Settings_Registry::get_instance()->get_entries();
$changed = false;
foreach (array_keys($entries) as $key) {
    $opt_name = 'phantom_' . $key;
    $opt_val = get_option($opt_name, null);
    if (null !== $opt_val) {
        if (!array_key_exists($key, $phantom_options) || $phantom_options[$key] !== $opt_val) {
            $phantom_options[$key] = $opt_val;
            $changed = true;
        }
    } elseif (isset($colors[$key])) {
        // Use AETHER default
        $phantom_options[$key] = $colors[$key];
        $changed = true;
    }
}
if ($changed) {
    update_option('phantom_options', $phantom_options, false);
    echo "phantom_options array updated with " . count($phantom_options) . " entries.\n";
}

// Flush CSS cache
if (class_exists('\Phantom_Custom_CSS')) {
    \Phantom_Custom_CSS::flush_cache();
    echo "CSS cache flushed.\n";
}

echo "\n=== Migration complete ===\n";
echo "Updated/Created: {$updated_count} options\n";
if (!empty($errors)) {
    echo "Errors:\n";
    foreach ($errors as $e) {
        echo "  - {$e}\n";
    }
}
echo "phantom_options count: " . count($phantom_options) . "\n";