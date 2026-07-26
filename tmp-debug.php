<?php
echo '=== PRODUCT 361 ===' . PHP_EOL;
$product = wc_get_product(361);
if (!$product) { echo 'Product 361 not found!' . PHP_EOL; exit; }
echo 'Type: ' . $product->get_type() . PHP_EOL;
echo 'Name: ' . $product->get_name() . PHP_EOL;
echo 'Price: ' . $product->get_price() . PHP_EOL;
$attrs = $product->get_attributes();
foreach ($attrs as $key => $attr) {
    echo 'Attr ' . $key . ': ' . print_r($attr, true) . PHP_EOL;
}
echo '---AVAILABLE VARIATIONS---' . PHP_EOL;
$av = $product->get_available_variations();
echo 'Count: ' . count($av) . PHP_EOL;
foreach ($av as $v) {
    echo 'Var ' . $v['variation_id'] . ': attrs=' . json_encode($v['attributes']) . ' price=' . $v['display_price'] . PHP_EOL;
}
echo '---VARIATION ATTRIBUTES---' . PHP_EOL;
$va = $product->get_variation_attributes();
foreach ($va as $key => $vals) {
    echo $key . ': ' . json_encode($vals) . PHP_EOL;
}
