<?php
require_once "/var/www/html/wp-load.php";

$products = wc_get_products(array('limit' => 8, 'status' => 'publish'));
echo "=== Debug format_product output ===\n\n";

foreach ($products as $product) {
    $image_id = $product->get_image_id();
    $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'large') : 'EMPTY_ID';
    $placeholder = wc_placeholder_img_src();
    $permalink = $product->get_permalink();

    echo "Product: {$product->get_name()} (ID: {$product->get_id()})\n";
    echo "  image_id: " . var_export($image_id, true) . "\n";
    echo "  image_url: $image_url\n";
    echo "  placeholder: $placeholder\n";
    echo "  permalink: $permalink\n";

    // Check post thumbnail
    $thumb_id = get_post_thumbnail_id($product->get_id());
    $thumb_url = get_the_post_thumbnail_url($product->get_id(), 'large');
    echo "  post_thumbnail_id: " . var_export($thumb_id, true) . "\n";
    echo "  post_thumbnail_url: " . var_export($thumb_url, true) . "\n";
    echo "\n";
}

// Now test actual REST response
echo "=== Actual REST Response ===\n";
$response = rest_do_request(new WP_REST_Request('GET', '/phantom/v1/page-data'));
$data = $response->get_data();
if (isset($data['products'])) {
    echo "Products: " . count($data['products']) . "\n";
    foreach (array_slice($data['products'], 0, 3) as $p) {
        echo "  {$p['name']} - image: " . var_export($p['image'] ?? 'MISSING', true) . "\n";
    }
}
