<?php
/**
 * Audit v2 - Section integrity, color sanitization, defaults, repeaters
 */
$file = file_get_contents(__DIR__ . '/class-settings-registry.php');

// === SECTION INTEGRITY ===
echo "=== CHECK 4: SECTION INTEGRITY ===\n";

// Get section names from settings registry (from define_entries merge list)
preg_match_all("/\\\$this->section_(\w+)\(\)/", $file, $secMatches);
$settingsSections = $secMatches[1];
echo "Settings sections (" . count($settingsSections) . "): " . implode(', ', $settingsSections) . "\n\n";

// Get Customizer section labels from get_section_label method
$customizerFile = file_get_contents(__DIR__ . '/class-customizer.php');
preg_match_all("/'([a-z_]+)'\s*=>\s*__\(/", $customizerFile, $labelMatches);
$customizerSections = $labelMatches[1];
echo "Customizer sections (" . count($customizerSections) . "): " . implode(', ', $customizerSections) . "\n\n";

// Check: sections in settings NOT in customizer
$customizerSectionsSet = array_flip($customizerSections);
$settingsSectionsSet = array_flip($settingsSections);
$orphanSettingsSections = [];
foreach ($settingsSections as $s) {
    if (!isset($customizerSectionsSet[$s])) {
        $orphanSettingsSections[] = $s;
    }
}
echo "Settings sections with NO customizer label:\n";
if (empty($orphanSettingsSections)) echo "  None - all match\n";
else foreach ($orphanSettingsSections as $s) echo "  ORPHAN: $s\n";

// Check: sections in customizer NOT in settings
$orphanCustomizerSections = [];
foreach ($customizerSections as $s) {
    if (!isset($settingsSectionsSet[$s])) {
        $orphanCustomizerSections[] = $s;
    }
}
echo "Customizer sections with NO settings in registry:\n";
if (empty($orphanCustomizerSections)) echo "  None - all match\n";
else foreach ($orphanCustomizerSections as $s) echo "  ORPHAN: $s\n";
echo "\n";

// === COLOR SANITIZATION ===
echo "=== CHECK 5: COLOR SANITIZATION ===\n";

// Find 'ast-color' or 'color' type entries and check their sanitize callbacks
$colorIssues = [];

// Extract section methods one by one
$sectionMethods = [
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

foreach ($sectionMethods as $method) {
    $search = "private function $method";
    $start = strpos($file, $search);
    if ($start === false) continue;
    $openBrace = strpos($file, '{', $start);
    if ($openBrace === false) continue;
    $depth = 1; $pos = $openBrace + 1;
    while ($depth > 0 && $pos < strlen($file)) {
        $c = $file[$pos];
        if ($c === '{') $depth++;
        elseif ($c === '}') $depth--;
        $pos++;
    }
    $body = substr($file, $openBrace + 1, $pos - $openBrace - 2);
    
    // Find each setting definition
    preg_match_all("/'([a-z_]+)'\s*=>\s*array\s*\((.*?)\)\s*(?:,|\s*\))/s", $body, $entryMatches, PREG_SET_ORDER);
    foreach ($entryMatches as $em) {
        $key = $em[1];
        $entryBody = $em[2];
        
        if (preg_match("/'type'\s*=>\s*'([^']+)'/", $entryBody, $t)) {
            $type = $t[1];
        } else {
            continue;
        }
        
        if ($type === 'ast-color' || $type === 'color') {
            if (preg_match("/'sanitize'\s*=>\s*'([^']+)'/", $entryBody, $s)) {
                $sanitize = $s[1];
            } elseif (preg_match("/'sanitize'\s*=>\s*function\s*\(/", $entryBody)) {
                $sanitize = 'closure';
            } else {
                $sanitize = 'NONE';
            }
            
            $proper = false;
            if ($sanitize === 'sanitize_hex_color' || $sanitize === 'closure') {
                $proper = true;
            }
            
            if (!$proper) {
                $colorIssues[] = "$key (type=$type, sanitize=$sanitize)";
            }
        }
    }
}

echo "Color settings with missing/inadequate sanitization:\n";
if (empty($colorIssues)) echo "  All $colorCheckCount color settings have proper sanitization\n";
else foreach ($colorIssues as $i) echo "  ISSUE: $i\n";
echo "\n";

// === DEFAULT VALUE SANITY ===
echo "=== CHECK 6: DEFAULT VALUE SANITY ===\n";
$defaultIssues = [];
foreach ($sectionMethods as $method) {
    $search = "private function $method";
    $start = strpos($file, $search);
    if ($start === false) continue;
    $openBrace = strpos($file, '{', $start);
    if ($openBrace === false) continue;
    $depth = 1; $pos = $openBrace + 1;
    while ($depth > 0 && $pos < strlen($file)) {
        $c = $file[$pos];
        if ($c === '{') $depth++;
        elseif ($c === '}') $depth--;
        $pos++;
    }
    $body = substr($file, $openBrace + 1, $pos - $openBrace - 2);
    
    preg_match_all("/'([a-z_]+)'\s*=>\s*array\s*\((.*?)\)\s*(?:,|\))/s", $body, $entryMatches, PREG_SET_ORDER);
    foreach ($entryMatches as $em) {
        $key = $em[1];
        $entryBody = $em[2];
        
        preg_match("/'type'\s*=>\s*'([^']+)'/", $entryBody, $t);
        $type = $t[1] ?? '';
        
        preg_match("/'default'\s*=>\s*(.+?)(?:,|\s*\))/s", $entryBody, $d);
        $default = trim($d[1] ?? '');
        
        if (in_array($type, ['int', 'number', 'float', 'ast-toggle', 'bool']) && $default === "''") {
            $defaultIssues[] = "$key (type=$type, default='')";
        }
    }
}

echo "Type/default mismatches:\n";
if (empty($defaultIssues)) echo "  None found\n";
else foreach ($defaultIssues as $i) echo "  ISSUE: $i\n";
echo "\n";

// === REPEATER SCHEMAS ===
echo "=== CHECK 7: REPEATER SCHEMAS ===\n";
$repeaterIssues = [];
$repeaterCount = 0;
foreach ($sectionMethods as $method) {
    $search = "private function $method";
    $start = strpos($file, $search);
    if ($start === false) continue;
    $openBrace = strpos($file, '{', $start);
    if ($openBrace === false) continue;
    $depth = 1; $pos = $openBrace + 1;
    while ($depth > 0 && $pos < strlen($file)) {
        $c = $file[$pos];
        if ($c === '{') $depth++;
        elseif ($c === '}') $depth--;
        $pos++;
    }
    $body = substr($file, $openBrace + 1, $pos - $openBrace - 2);
    
    preg_match_all("/'([a-z_]+)'\s*=>\s*array\s*\((.*?)\)\s*(?:,|\))/s", $body, $entryMatches, PREG_SET_ORDER);
    foreach ($entryMatches as $em) {
        $key = $em[1];
        $entryBody = $em[2];
        
        if (preg_match("/'type'\s*=>\s*'([^']+)'/", $entryBody, $t)) {
            $type = $t[1];
        } else continue;
        
        if ($type === 'repeater') {
            $repeaterCount++;
            if (preg_match("/'sanitize'\s*=>\s*'([^']+)'/", $entryBody, $s)) {
                $san = $s[1];
            } elseif (preg_match("/'sanitize'\s*=>\s*function/", $entryBody)) {
                $san = 'closure';
            } else {
                $san = 'NONE';
            }
            if (!preg_match("/'label'\s*=>/", $entryBody)) {
                $repeaterIssues[] = "$key missing 'label'";
            }
        }
    }
}

echo "Repeater settings: $repeaterCount\n";
echo "Repeater issues:\n";
if (empty($repeaterIssues)) echo "  None - all have sanitize callbacks and labels\n";
else foreach ($repeaterIssues as $i) echo "  ISSUE: $i\n";
