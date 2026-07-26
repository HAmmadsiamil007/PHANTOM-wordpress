<?php
/**
 * Batch download images and attach to products
 */
require_once "/var/www/html/wp-load.php";
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

add_action('http_api_curl', function($handle) {
    curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($handle, CURLOPT_TIMEOUT, 30);
    curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);
});

$image_urls = json_decode(file_get_contents('/tmp/image_urls.json'), true);
echo "=== Downloading images for " . count($image_urls) . " products ===\n\n";

$downloaded = 0;
$failed = 0;

foreach ($image_urls as $product_id => $urls) {
    if (!is_array($urls)) $urls = array($urls);

    $product = wc_get_product($product_id);
    if (!$product) {
        echo "SKIP: Product $product_id not found\n";
        continue;
    }

    $name = $product->get_name();
    $image_ids = array();

    foreach ($urls as $url) {
        $url = trim($url);
        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) continue;

        echo "  [$name] Downloading: " . basename($url) . " ... ";

        $tmp = @download_url($url, 30);
        if (is_wp_error($tmp) || !file_exists($tmp)) {
            echo "FAILED (" . (is_wp_error($tmp) ? $tmp->get_error_message() : 'no temp file') . ")\n";
            $failed++;
            continue;
        }

        $filename = basename(parse_url($url, PHP_URL_PATH));
        $file_array = array('name' => $filename, 'tmp_name' => $tmp);

        $attach_id = media_handle_sideload($file_array, 0);
        if (is_wp_error($attach_id)) {
            echo "FAILED (" . $attach_id->get_error_message() . ")\n";
            @unlink($tmp);
            $failed++;
            continue;
        }

        $image_ids[] = $attach_id;
        echo "OK (ID: $attach_id)\n";
        $downloaded++;
    }

    // Attach images to product
    if (!empty($image_ids)) {
        $product->set_image_id($image_ids[0]);
        if (count($image_ids) > 1) {
            $product->set_gallery_image_ids(array_slice($image_ids, 1));
        }
        $product->save();
        echo "  -> Attached " . count($image_ids) . " images to [$name]\n";
    }
    echo "\n";
}

// Clear cache
delete_transient('phantom_page_data');

echo "=== DONE ===\n";
echo "Downloaded: $downloaded images\n";
echo "Failed: $failed images\n";
echo "Cache cleared.\n";
