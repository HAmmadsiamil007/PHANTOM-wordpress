<?php
require_once "/var/www/html/wp-load.php";

// Force allow external HTTP
add_action('http_api_curl', function($handle) {
    curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($handle, CURLOPT_TIMEOUT, 15);
});

echo "=== STEP 1: Delete existing products ===\n";
$existing = wc_get_products(array('limit' => -1, 'return' => 'ids'));
foreach ($existing as $pid) {
    $p = wc_get_product($pid);
    if ($p) $p->delete(true);
}
echo "Deleted " . count($existing) . " products\n";

// Delete any orphan variations
global $wpdb;
$wpdb->query("DELETE FROM wp_posts WHERE post_type = 'product_variation'");
$wpdb->query("DELETE FROM wp_postmeta WHERE post_id NOT IN (SELECT ID FROM wp_posts)");
echo "Cleaned up orphan data\n\n";

echo "=== STEP 2: Create categories ===\n";
$cats = array(
    'Accessories' => 0,
    'Men' => 0,
    'Men > Hoodies' => null,
    'Men > Shirts' => null,
    'Women' => 0,
    'Women > Hoodies' => null,
    'Women > Shirts' => null,
);
$term_ids = array();
foreach ($cats as $name => $parent_override) {
    $parent = 0;
    if (strpos($name, ' > ') !== false) {
        $parts = explode(' > ', $name);
        $parent_name = $parts[0];
        $parent = $term_ids[$parent_name] ?? 0;
        $name = $parts[1];
    }
    $term = term_exists($name, 'product_cat', $parent);
    if (!$term) {
        $slug = sanitize_title($name);
        $term = wp_insert_term($name, 'product_cat', array('slug' => $slug, 'parent' => $parent));
    }
    $full_name = $parent > 0 ? $cats[array_search($parent, $term_ids)] . ' > ' . $name : $name;
    if (!is_wp_error($term)) {
        $term_ids[$full_name] = is_array($term) ? $term['term_id'] : $term;
        echo "  $full_name (ID: {$term_ids[$full_name]})\n";
    }
}
echo "\n";

echo "=== STEP 3: Read CSV and import products ===\n";
$csv = array_map('str_getcsv', file('/tmp/products.csv'));
$headers = array_shift($csv);
echo "  " . count($csv) . " rows to process\n";

$parents = array();
$products_data = array();
$image_urls = array();

foreach ($csv as $row) {
    if (count($row) !== count($headers)) continue;
    $data = array_combine($headers, $row);
    $type = $data['Type'];
    $name = $data['Name'] ?? '';
    if (empty($name)) continue;

    if ($type === 'variation') {
        // Store variation data for later
        $parent_csv_id = preg_replace('/[^0-9]/', '', $data['Parent']);
        $parent_id = $parents[$parent_csv_id] ?? 0;
        if (!$parent_id) continue;

        $variation = new WC_Product_Variation();
        $variation->set_parent_id($parent_id);
        $variation->set_name($name);
        $variation->set_sku($data['SKU'] ?? '');
        $variation->set_regular_price($data['Regular price'] ?: '0');
        if (!empty($data['Sale price'])) $variation->set_sale_price($data['Sale price']);
        $variation->set_stock_status(($data['In stock?'] ?? '1') === '1' ? 'instock' : 'outofstock');

        $attributes = array();
        for ($i = 1; $i <= 3; $i++) {
            $attr_name = $data["Attribute $i name"] ?? '';
            $attr_value = $data["Attribute $i value(s)"] ?? '';
            if (!empty($attr_name) && !empty($attr_value)) {
                $attributes['attribute_pa_' . sanitize_title($attr_name)] = sanitize_title($attr_value);
            }
        }
        $variation->set_attributes($attributes);
        $vid = $variation->save();

        // Store image URL for variation
        if (!empty($data['Images']) && filter_var(trim($data['Images']), FILTER_VALIDATE_URL)) {
            $image_urls[$vid] = trim($data['Images']);
        }
        continue;
    }

    // Simple or Variable product
    if ($type === 'variable') {
        $product = new WC_Product_Variable();
    } else {
        $product = new WC_Product_Simple();
    }

    $product->set_name($name);
    $product->set_description($data['Description'] ?? '');
    $product->set_short_description($data['Short description'] ?? '');
    if (!empty($data['Regular price'])) $product->set_regular_price($data['Regular price']);
    if (!empty($data['Sale price'])) $product->set_sale_price($data['Sale price']);
    $product->set_sku($data['SKU'] ?? '');
    $product->set_stock_status(($data['In stock?'] ?? '1') === '1' ? 'instock' : 'outofstock');
    $product->set_catalog_visibility($data['Visibility in catalog'] ?? 'visible');
    $product->set_featured(($data['Is featured?'] ?? '0') === '1');
    $product->set_reviews_allowed(($data['Allow customer reviews?'] ?? '1') === '1');
    $product->set_status('publish');

    // Categories
    if (!empty($data['Categories'])) {
        $cats_list = array_map('trim', explode(',', $data['Categories']));
        $cat_ids = array();
        foreach ($cats_list as $cn) {
            if (isset($term_ids[$cn])) $cat_ids[] = $term_ids[$cn];
        }
        if (!empty($cat_ids)) $product->set_category_ids($cat_ids);
    }

    // Attributes
    if ($type === 'variable') {
        $attributes = array();
        for ($i = 1; $i <= 3; $i++) {
            $attr_name = $data["Attribute $i name"] ?? '';
            $attr_values_str = $data["Attribute $i value(s)"] ?? '';
            if (!empty($attr_name) && !empty($attr_values_str)) {
                $values = array_map('trim', explode(',', $attr_values_str));
                $attribute = new WC_Product_Attribute();
                $attribute->set_name($attr_name);
                $attribute->set_options($values);
                $attribute->set_visible(true);
                $attribute->set_variation(true);
                $attributes[] = $attribute;
            }
        }
        $product->set_attributes($attributes);
    }

    $product_id = $product->save();
    $parents[$data['ID']] = $product_id;
    echo "  [$type] $name -> ID $product_id\n";

    // Store image URL for parent
    if (!empty($data['Images'])) {
        $urls = array_map('trim', explode(',', $data['Images']));
        $image_urls[$product_id] = $urls;
    }
}

$total_products = count(wc_get_products(array('limit' => -1, 'return' => 'ids')));
$total_variations = $wpdb->get_var("SELECT COUNT(*) FROM wp_posts WHERE post_type = 'product_variation' AND post_status = 'publish'");
echo "\n  Total: $total_products products + $total_variations variations\n";
echo "  Products with image URLs: " . count($image_urls) . "\n\n";

// Save image URLs to a JSON file for batch processing
file_put_contents('/tmp/image_urls.json', json_encode($image_urls));
echo "=== Image URLs saved to /tmp/image_urls.json ===\n";
echo "=== Products imported successfully! ===\n";
