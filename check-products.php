<?php
require_once "/var/www/html/wp-load.php";
global $wpdb;
$product_count = $wpdb->get_var("SELECT COUNT(*) FROM wp_posts WHERE post_type = 'product' AND post_status = 'publish'");
$variation_count = $wpdb->get_var("SELECT COUNT(*) FROM wp_posts WHERE post_type = 'product_variation' AND post_status = 'publish'");
$image_count = $wpdb->get_var("SELECT COUNT(*) FROM wp_posts WHERE post_type = 'attachment' AND post_mime_type LIKE 'image/%'");
$meta_count = $wpdb->get_var("SELECT COUNT(*) FROM wp_postmeta pm JOIN wp_posts p ON p.ID = pm.post_id WHERE pm.meta_key = '_thumbnail_id' AND p.post_type = 'product' AND p.post_status = 'publish'");
echo "Published products: $product_count\n";
echo "Published variations: $variation_count\n";
echo "Image attachments: $image_count\n";
echo "Products with featured image: $meta_count\n";
echo "\nProduct IDs with thumbnails:\n";
$results = $wpdb->get_results("SELECT p.ID, p.post_title, pm.meta_value as thumb_id FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id WHERE pm.meta_key = '_thumbnail_id' AND p.post_type = 'product' AND p.post_status = 'publish' LIMIT 5");
foreach ($results as $r) {
    $url = wp_get_attachment_url($r->thumb_id);
    echo "  [{$r->ID}] {$r->post_title} -> thumb {$r->thumb_id} ($url)\n";
}
echo "\nProducts WITHOUT featured image:\n";
$noimg = $wpdb->get_results("SELECT p.ID, p.post_title FROM wp_posts p LEFT JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_thumbnail_id' WHERE pm.post_id IS NULL AND p.post_type = 'product' AND p.post_status = 'publish'");
foreach ($noimg as $r) {
    echo "  [{$r->ID}] {$r->post_title}\n";
}
