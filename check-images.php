<?php
require_once "/var/www/html/wp-load.php";

// Check uploaded images
$images = get_posts(array(
    'post_type' => 'attachment',
    'post_mime_type' => 'image/%',
    'numberposts' => 5,
    'post_status' => 'inherit',
    'orderby' => 'ID',
    'order' => 'DESC'
));

echo "=== Sample uploaded images ===\n";
foreach ($images as $img) {
    $url = wp_get_attachment_url($img->ID);
    $file = get_attached_file($img->ID);
    $exists = file_exists($file);
    echo "  [{$img->ID}] {$img->post_title}\n";
    echo "    URL: $url\n";
    echo "    File: $file (exists: " . ($exists ? 'YES' : 'NO') . ")\n";
}

// Check shop page rendering
echo "\n=== Shop page products from Phantom REST ===\n";
$request = new WP_REST_Request('GET', '/phantom/v1/page-data');
$request->set_param('page', 'shop');
$response = rest_do_request($request);
$data = $response->get_data();
if (isset($data['products'])) {
    foreach (array_slice($data['products'], 0, 3) as $p) {
        echo "  {$p['name']}\n";
        echo "    image: {$p['image']}\n";
        echo "    url: {$p['url']}\n";
    }
}

// Also check phantom products endpoint
echo "\n=== Phantom products endpoint ===\n";
$request2 = new WP_REST_Request('GET', '/phantom/v1/products');
$response2 = rest_do_request($request2);
$data2 = $response2->get_data();
if (isset($data2['products'])) {
    echo "Count: " . count($data2['products']) . "\n";
    foreach (array_slice($data2['products'], 0, 3) as $p) {
        echo "  {$p['name']} - image: {$p['image']}\n";
    }
}
