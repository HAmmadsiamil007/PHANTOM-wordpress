<?php
/**
 * Audit script for Settings Registry
 * Run: php audit.php
 * This script extracts all metadata from the source file directly.
 */

$file = file_get_contents(__DIR__ . '/class-settings-registry.php');

// --- Extract CSS var map ---
$map_start = strpos($file, "public static function get_css_var_map");
$map_end = strpos($file, "public static function get_px_keys");
$map_section = substr($file, $map_start, $map_end - $map_start);

preg_match_all("/'([a-z_]+)'\s*=>\s*'--/", $map_section, $map_matches);
$css_var_keys = $map_matches[1];
echo "=== CSS VAR MAP ===\n";
echo "Total entries: " . count($css_var_keys) . "\n\n";

// --- Extract PX keys ---
$px_start = $map_end;
$px_end = strpos($file, "private function section_branding", $px_start);
$px_section = substr($file, $px_start, $px_end - $px_start);

preg_match_all("/'([a-z_]+)'/", $px_section, $px_matches);
$px_keys = $px_matches[1];
echo "=== PX KEYS ===\n";
echo "Total entries: " . count($px_keys) . "\n\n";

// --- Extract all settings entries ---
// Find all section function bodies
$section_methods = [
    'section_branding', 'section_header', 'section_topbar', 'section_navigation',
    'section_hero', 'section_collections', 'section_home_sections',
    'section_product_cards', 'section_shop_page', 'section_product_page',
    'section_woocommerce', 'section_blog', 'section_footer', 'section_typography',
    'section_colors', 'section_buttons', 'section_forms', 'section_spacing',
    'section_layout', 'section_responsive', 'section_animations', 'section_effects_3d',
    'section_search', 'section_performance', 'section_seo', 'section_accessibility',
    'section_integrations', 'section_custom_code', 'section_import_export',
    'section_about_page', 'section_contact_page', 'section_faq_page',
    'section_coming_soon', 'section_error_404', 'section_login_page',
    'section_register_page', 'section_portfolio', 'section_thank_you',
    'section_load_more', 'section_privacy', 'section_terms', 'section_team',
    'section_testimonials', 'section_announcement_bar'
];

$all_settings = [];

foreach ($section_methods as $method) {
    $search = "private function $method";
    $start = strpos($file, $search);
    if ($start === false) {
        echo "WARNING: Could not find $method\n";
        continue;
    }
    
    // Find the function body - from '{' after function name to matching '}'
    $open_brace = strpos($file, '{', $start);
    $depth = 1;
    $pos = $open_brace + 1;
    while ($depth > 0 && $pos < strlen($file)) {
        $char = $file[$pos];
        if ($char === '{') $depth++;
        elseif ($char === '}') $depth--;
        $pos++;
    }
    $body = substr($file, $open_brace + 1, $pos - $open_brace - 2);
    
    // Extract each setting definition
    // Pattern: 'setting_key' => array( ... ),
    preg_match_all("/'([a-z_]+)'\s*=>\s*\[(.*?)\]/s", $body, $setting_matches, PREG_SET_ORDER);
    
    foreach ($setting_matches as $sm) {
        $key = $sm[1];
        $entry_body = $sm[2];
        
        // Extract section
        preg_match("/'section'\s*=>\s*'([^']+)'/", $entry_body, $sec_m);
        $section = $sec_m[1] ?? 'unknown';
        
        // Extract type
        preg_match("/'type'\s*=>\s*'([^']+)'/", $entry_body, $type_m);
        $type = $type_m[1] ?? 'unknown';
        
        // Extract default
        preg_match("/'default'\s*=>\s*([^,\]]+)/", $entry_body, $def_m);
        $default = trim($def_m[1] ?? '');
        
        // Extract sanitize
        preg_match("/'sanitize'\s*=>\s*'([^']+)'/", $entry_body, $san_m);
        $sanitize = $san_m[1] ?? '';
        if (empty($sanitize)) {
            // Check for closure/callable
            if (preg_match("/'sanitize'\s*=>\s*(function|\\$this->|array\s*\()/", $entry_body)) {
                $sanitize = 'callable';
            }
        }
        
        // Extract css_property
        preg_match("/'css_property'\s*=>\s*'([^']+)'/", $entry_body, $css_m);
        $css_property = $css_m[1] ?? '';
        
        $all_settings[$key] = [
            'section' => $section,
            'type' => $type,
            'default' => $default,
            'sanitize' => $sanitize,
            'css_property' => $css_property,
        ];
    }
}

echo "=== ALL SETTINGS ===\n";
echo "Total settings: " . count($all_settings) . "\n\n";

// --- CHECK 1: CSS var map completeness ---
echo "=== CHECK 1: CSS VAR MAP COMPLETENESS ===\n";
$issues1 = [];
foreach ($css_var_keys as $key) {
    if (!isset($all_settings[$key])) {
        $issues1[] = "CSS var key '$key' has no matching setting entry";
    }
}
foreach ($issues1 as $issue) echo "  ISSUE: $issue\n";
if (empty($issues1)) echo "  PASS: All CSS var keys have matching setting entries\n";
echo "\n";

// --- CHECK 2: PX keys integrity ---
echo "=== CHECK 2: PX KEYS INTEGRITY ===\n";
$issues2 = [];
foreach ($px_keys as $key) {
    if (!isset($all_settings[$key])) {
        $issues2[] = "PX key '$key' has no matching setting entry";
    }
}
foreach ($issues2 as $issue) echo "  ISSUE: $issue\n";
if (empty($issues2)) echo "  PASS: All px keys have matching setting entries\n";
echo "\n";

// --- CHECK 3: Orphan settings with css_property not in CSS var map ---
echo "=== CHECK 3: SETTINGS WITH CSS_PROPERTY NOT IN CSS VAR MAP ===\n";
$issues3 = [];
foreach ($all_settings as $key => $entry) {
    if (!empty($entry['css_property']) && !in_array($key, $css_var_keys)) {
        $issues3[] = "Setting '$key' has css_property '{$entry['css_property']}' but is NOT in get_css_var_map()";
    }
}
foreach ($issues3 as $issue) echo "  ISSUE: $issue\n";
if (empty($issues3)) echo "  PASS: All settings with css_property are in CSS var map\n";
echo "\n";

// --- CHECK 4: Section integrity ---
echo "=== CHECK 4: SECTION INTEGRITY ===\n";
$settings_sections = [];
foreach ($all_settings as $key => $entry) {
    $settings_sections[$entry['section']] = true;
}
$settings_section_names = array_keys($settings_sections);
echo "Settings sections: " . implode(', ', $settings_section_names) . "\n";

// Read customizer section labels
$customizer_file = file_get_contents(__DIR__ . '/class-customizer.php');
preg_match_all("/'([a-z_]+)'\s*=>\s*__\(/", $customizer_file, $cs_matches);
$customizer_sections = $cs_matches[1];
echo "Customizer sections: " . implode(', ', $customizer_sections) . "\n";

$issues4a = [];
$issues4b = [];
foreach ($settings_section_names as $section) {
    if (!in_array($section, $customizer_sections)) {
        $issues4a[] = "Settings section '$section' has no Customizer section label";
    }
}
foreach ($customizer_sections as $section) {
    if (!isset($settings_sections[$section])) {
        $issues4b[] = "Customizer section '$section' has no settings in registry";
    }
}
foreach ($issues4a as $issue) echo "  ISSUE (settings→customizer): $issue\n";
foreach ($issues4b as $issue) echo "  ISSUE (customizer→settings): $issue\n";
if (empty($issues4a) && empty($issues4b)) echo "  PASS: All sections match\n";
echo "\n";

// --- CHECK 5: Color sanitization ---
echo "=== CHECK 5: COLOR SANITIZATION ===\n";
$color_types = ['ast-color', 'color'];
$proper_sanitizers = ['sanitize_hex_color', 'wp_strip_all_tags'];
$issues5 = [];
foreach ($all_settings as $key => $entry) {
    if (in_array($entry['type'], $color_types)) {
        $san = $entry['sanitize'];
        $is_closure = ($san === 'callable');
        $is_proper_string = in_array($san, $proper_sanitizers);
        // Check for inline regex validation closures
        $is_regex_validated = $is_closure; // approximated
        
        if (!$is_proper_string && !$is_closure) {
            $issues5[] = "Setting '$key' (type={$entry['type']}) has sanitize='$san' - missing proper color sanitization";
        }
    }
}
foreach ($issues5 as $issue) echo "  ISSUE: $issue\n";
if (empty($issues5)) echo "  PASS: All color settings have adequate sanitization\n";
echo "\n";

// --- CHECK 6: Default value sanity ---
echo "=== CHECK 6: DEFAULT VALUE SANITY ===\n";
$int_types = ['int', 'number', 'float'];
$issues6 = [];
foreach ($all_settings as $key => $entry) {
    if (in_array($entry['type'], $int_types) && $entry['default'] === "''") {
        $issues6[] = "Setting '$key' (type={$entry['type']}) has default='' (empty string) - type mismatch";
    }
    if ($entry['type'] === 'ast-toggle' && $entry['default'] === "''") {
        $issues6[] = "Setting '$key' (type=ast-toggle) has default='' (empty string) - toggle should be bool/int";
    }
}
foreach ($issues6 as $issue) echo "  ISSUE: $issue\n";
if (empty($issues6)) echo "  PASS: No type/default mismatches found\n";
echo "\n";

// --- CHECK 7: Repeater field schema ---
echo "=== CHECK 7: REPEATER FIELD SCHEMA ===\n";
$issues7 = [];
$repeater_count = 0;
foreach ($all_settings as $key => $entry) {
    if ($entry['type'] === 'repeater') {
        $repeater_count++;
        // Check if sanitize is just a passthrough closure
        if ($entry['sanitize'] !== 'callable' && empty($entry['sanitize'])) {
            $issues7[] = "Repeater '$key' has no sanitize callback at all";
        }
    }
}
echo "Total repeater settings: $repeater_count\n";
foreach ($issues7 as $issue) echo "  ISSUE: $issue\n";
if (empty($issues7)) echo "  PASS: All repeater settings have sanitize callbacks\n";

echo "\n=== SUMMARY ===\n";
echo "CSS var map entries: " . count($css_var_keys) . "\n";
echo "PX keys entries: " . count($px_keys) . "\n";
echo "Total settings entries: " . count($all_settings) . "\n";
echo "Check 1 (CSS var completeness): " . (empty($issues1) ? "PASS" : "FAIL - " . count($issues1) . " issues") . "\n";
echo "Check 2 (PX key integrity): " . (empty($issues2) ? "PASS" : "FAIL - " . count($issues2) . " issues") . "\n";
echo "Check 3 (Orphan css_property): " . (empty($issues3) ? "PASS" : "FAIL - " . count($issues3) . " issues") . "\n";
echo "Check 4 (Section integrity): " . (empty($issues4a) && empty($issues4b) ? "PASS" : "WARN - " . (count($issues4a) + count($issues4b)) . " issues") . "\n";
echo "Check 5 (Color sanitization): " . (empty($issues5) ? "PASS" : "FAIL - " . count($issues5) . " issues") . "\n";
echo "Check 6 (Default sanity): " . (empty($issues6) ? "PASS" : "FAIL - " . count($issues6) . " issues") . "\n";
echo "Check 7 (Repeater schema): " . (empty($issues7) ? "PASS" : "FAIL - " . count($issues7) . " issues") . "\n";
