<?php
echo '=== VARIATION 362 DETAILS ===' . PHP_EOL;
$v362 = wc_get_product(362);
echo 'Type: ' . $v362->get_type() . PHP_EOL;
echo 'Parent ID: ' . $v362->get_parent_id() . PHP_EOL;
echo 'Attributes raw: ' . print_r($v362->get_attributes(), true) . PHP_EOL;
echo '---WP_Terms for pa_size---' . PHP_EOL;
$terms = get_terms(array('taxonomy' => 'pa_size', 'hide_empty' => false));
echo 'pa_size terms: ' . print_r($terms, true) . PHP_EOL;
echo '---Check attribute taxonomy (pa_size)---' . PHP_EOL;
$tax = get_taxonomy('pa_size');
echo 'pa_size taxonomy exists: ' . ($tax ? 'YES' : 'NO') . PHP_EOL;
echo '---All attribute taxonomies---' . PHP_EOL;
$attrs_tax = wc_get_attribute_taxonomies();
foreach ($attrs_tax as $at) {
    echo $at->attribute_name . ' (label: ' . $at->attribute_label . ')' . PHP_EOL;
}
echo '---META keys for variation 362---' . PHP_EOL;
global $wpdb;
$metas = $wpdb->get_results("SELECT meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id = 362 AND meta_key LIKE 'attribute_%'");
foreach ($metas as $m) {
    echo $m->meta_key . ' = ' . $m->meta_value . PHP_EOL;
}
echo '---TEST add_to_cart with explicit attributes---' . PHP_EOL;
WC()->frontend_includes();
if (!WC()->session) { WC()->session = new WC_Session_Handler(); WC()->session->init(); }
if (!WC()->cart) { WC()->cart = new WC_Cart(); }
$result = WC()->cart->add_to_cart(361, 1, 362, array('pa_size' => 'large'));
echo 'add_to_cart with pa_size=large: '; var_dump($result);
$result2 = WC()->cart->add_to_cart(361, 1, 362, array('size' => 'large'));
echo 'add_to_cart with size=large: '; var_dump($result2);
echo 'Cart: ' . print_r(WC()->cart->get_cart(), true) . PHP_EOL;
