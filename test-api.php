<?php
require_once "/var/www/html/wp-load.php";

// Test the phantom products endpoint
$products = wc_get_products(array('limit' => 12, 'return' => 'objects'));
echo "=== WooCommerce Products (direct query) ===\n";
echo "Total found: " . count($products) . "\n\n";

foreach ($products as $p) {
    $img_id = $p->get_image_id();
    $img_url = $img_id ? wp_get_attachment_url($img_id) : 'NO IMAGE';
    echo "  [{$p->get_id()}] {$p->get_name()} - Price: {$p->get_price()} - Image: $img_url\n";
}

// Test page-data endpoint
echo "\n=== Page Data (shop) ===\n";
$result = rest_do_request(new WP_REST_Request('GET', '/phantom/v1/page-data', array('page' => 'shop')));
$data = $result->get_data();
if (isset($data['products'])) {
    echo "Products returned: " . count($data['products']) . "\n";
    if (!empty($data['products'])) {
        $first = $data['products'][0];
        echo "First product: " . ($first['name'] ?? 'N/A') . " - Image: " . ($first['featured_image'] ?? 'NONE') . "\n";
    }
} else {
    echo "No products key in response\n";
    echo "Keys: " . implode(', ', array_keys($data)) . "\n";
}
