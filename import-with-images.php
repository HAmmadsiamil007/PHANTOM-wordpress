<?php
/**
 * WooCommerce Product Import with Image Downloads
 * Imports from CSV and downloads remote images as featured/gallery images
 */
require_once "/var/www/html/wp-load.php";

// Allow external HTTP requests
add_action( 'http_api_curl', function( $handle ) {
    curl_setopt( $handle, CURLOPT_SSL_VERIFYPEER, false );
});

echo "=== WooCommerce Product Import with Images ===\n\n";

// Step 1: Delete ALL existing products
echo "Step 1: Clearing existing products...\n";
$existing = wc_get_products( array( 'limit' => -1, 'return' => 'ids' ) );
foreach ( $existing as $pid ) {
    $p = wc_get_product( $pid );
    if ( $p ) $p->delete( true );
}
echo "  Deleted " . count( $existing ) . " products\n\n";

// Step 2: Create categories
echo "Step 2: Creating categories...\n";
$category_map = array(
    'Accessories' => 'accessories',
    'Men' => 'men',
    'Men > Hoodies' => 'men-hoodies',
    'Men > Shirts' => 'men-shirts',
    'Women' => 'women',
    'Women > Hoodies' => 'women-hoodies',
    'Women > Shirts' => 'women-shirts',
);

$term_ids = array();
foreach ( $category_map as $name => $slug ) {
    $parent = 0;
    // Handle subcategories
    if ( strpos( $name, ' > ' ) !== false ) {
        $parts = explode( ' > ', $name );
        $parent_name = $parts[0];
        if ( isset( $term_ids[ $parent_name ] ) ) {
            $parent = $term_ids[ $parent_name ];
        }
    }
    $term = term_exists( $name, 'product_cat' );
    if ( ! $term ) {
        $term = wp_insert_term( $name, 'product_cat', array( 'slug' => $slug, 'parent' => $parent ) );
    }
    if ( ! is_wp_error( $term ) ) {
        $term_ids[ $name ] = is_array( $term ) ? $term['term_id'] : $term;
        echo "  Category: $name (ID: {$term_ids[$name]})\n";
    }
}
echo "\n";

// Step 3: Read CSV
echo "Step 3: Reading CSV...\n";
$file = fopen( '/tmp/products.csv', 'r' );
$headers = fgetcsv( $file );
echo "  Headers: " . count( $headers ) . " columns\n";

// Step 4: Import products
echo "\nStep 4: Importing products...\n";
$parents = array();
$imported = 0;
$image_cache = array();

while ( ( $row = fgetcsv( $file ) ) !== false ) {
    if ( count( $row ) !== count( $headers ) ) continue;
    $data = array_combine( $headers, $row );
    $type = $data['Type'];
    $name = $data['Name'] ?? '';
    if ( empty( $name ) ) continue;

    // Skip variations - handle them after parents
    if ( $type === 'variation' ) {
        continue;
    }

    echo "\n  [$type] $name\n";

    // Create product
    if ( $type === 'variable' ) {
        $product = new WC_Product_Variable();
    } else {
        $product = new WC_Product_Simple();
    }

    $product->set_name( $name );
    $product->set_description( $data['Description'] ?? '' );
    $product->set_short_description( $data['Short description'] ?? '' );
    if ( ! empty( $data['Regular price'] ) ) $product->set_regular_price( $data['Regular price'] );
    if ( ! empty( $data['Sale price'] ) ) $product->set_sale_price( $data['Sale price'] );
    $product->set_sku( $data['SKU'] ?? '' );
    $product->set_stock_status( ( $data['In stock?'] ?? '1' ) === '1' ? 'instock' : 'outofstock' );
    $product->set_catalog_visibility( $data['Visibility in catalog'] ?? 'visible' );
    $product->set_featured( ( $data['Is featured?'] ?? '0' ) === '1' );
    $product->set_reviews_allowed( ( $data['Allow customer reviews?'] ?? '1' ) === '1' );
    $product->set_status( 'publish' );

    // Categories
    if ( ! empty( $data['Categories'] ) ) {
        $cats = array_map( 'trim', explode( ',', $data['Categories'] ) );
        $cat_ids = array();
        foreach ( $cats as $cat_name ) {
            if ( isset( $term_ids[ $cat_name ] ) ) {
                $cat_ids[] = $term_ids[ $cat_name ];
            }
        }
        if ( ! empty( $cat_ids ) ) $product->set_category_ids( $cat_ids );
        echo "    Categories: " . implode( ', ', $cats ) . "\n";
    }

    // Attributes for variable products
    if ( $type === 'variable' ) {
        $attributes = array();
        for ( $i = 1; $i <= 3; $i++ ) {
            $attr_name = $data["Attribute $i name"] ?? '';
            $attr_values_str = $data["Attribute $i value(s)"] ?? '';
            if ( ! empty( $attr_name ) && ! empty( $attr_values_str ) ) {
                $values = array_map( 'trim', explode( ',', $attr_values_str ) );
                $attribute = new WC_Product_Attribute();
                $attribute->set_name( $attr_name );
                $attribute->set_options( $values );
                $attribute->set_visible( true );
                $attribute->set_variation( true );
                $attributes[] = $attribute;
                echo "    Attribute: $attr_name = " . implode( ', ', $values ) . "\n";
            }
        }
        $product->set_attributes( $attributes );
    }

    $product_id = $product->save();
    echo "    Saved as ID: $product_id\n";

    // Download and attach images
    if ( ! empty( $data['Images'] ) ) {
        $image_urls = array_map( 'trim', explode( ',', $data['Images'] ) );
        $image_ids = array();

        foreach ( $image_urls as $img_url ) {
            if ( empty( $img_url ) || ! filter_var( $img_url, FILTER_VALIDATE_URL ) ) continue;

            echo "    Downloading: $img_url\n";

            // Check cache
            if ( isset( $image_cache[ $img_url ] ) ) {
                $attach_id = $image_cache[ $img_url ];
                echo "      -> Cached as ID: $attach_id\n";
            } else {
                $attach_id = download_image_to_media( $img_url, $name );
                if ( $attach_id && ! is_wp_error( $attach_id ) ) {
                    $image_cache[ $img_url ] = $attach_id;
                    echo "      -> Downloaded as ID: $attach_id\n";
                } else {
                    echo "      -> FAILED: " . ( is_wp_error( $attach_id ) ? $attach_id->get_error_message() : 'unknown' ) . "\n";
                    $attach_id = 0;
                }
            }

            if ( $attach_id ) {
                $image_ids[] = $attach_id;
            }
        }

        // Set featured image (first image)
        if ( ! empty( $image_ids ) ) {
            $product->set_image_id( $image_ids[0] );
            echo "    Featured image: ID {$image_ids[0]}\n";
        }

        // Set gallery images (remaining images)
        if ( count( $image_ids ) > 1 ) {
            $product->set_gallery_image_ids( array_slice( $image_ids, 1 ) );
            echo "    Gallery: " . count( $image_ids ) - 1 . " images\n";
        }

        $product->save();
    }

    // Store parent mapping
    if ( $type === 'variable' ) {
        $parents[ $data['ID'] ] = $product_id;
    }

    $imported++;
}

// Step 5: Import variations
echo "\nStep 5: Importing variations...\n";
fseek( $file, 0 );
fgetcsv( $file ); // skip header

$var_count = 0;
while ( ( $row = fgetcsv( $file ) ) !== false ) {
    if ( count( $row ) !== count( $headers ) ) continue;
    $data = array_combine( $headers, $row );

    if ( $data['Type'] !== 'variation' ) continue;

    $parent_csv_raw = $data['Parent'];
    $parent_csv_id = preg_replace( '/[^0-9]/', '', $parent_csv_raw );
    $parent_id = $parents[ $parent_csv_id ] ?? 0;

    if ( ! $parent_id ) {
        echo "  SKIP variation '$name' - parent $parent_csv_id not found\n";
        continue;
    }

    $name = $data['Name'] ?? '';

    $variation = new WC_Product_Variation();
    $variation->set_parent_id( $parent_id );
    $variation->set_sku( $data['SKU'] ?? '' );
    $variation->set_regular_price( $data['Regular price'] ?? '0' );
    if ( ! empty( $data['Sale price'] ) ) $variation->set_sale_price( $data['Sale price'] );
    $variation->set_stock_status( ( $data['In stock?'] ?? '1' ) === '1' ? 'instock' : 'outofstock' );

    // Attributes
    $attributes = array();
    for ( $i = 1; $i <= 3; $i++ ) {
        $attr_name = $data["Attribute $i name"] ?? '';
        $attr_value = $data["Attribute $i value(s)"] ?? '';
        if ( ! empty( $attr_name ) && ! empty( $attr_value ) ) {
            $attributes[ 'attribute_pa_' . sanitize_title( $attr_name ) ] = sanitize_title( $attr_value );
        }
    }
    $variation->set_attributes( $attributes );

    // Download variation image if present
    if ( ! empty( $data['Images'] ) ) {
        $img_url = trim( $data['Images'] );
        if ( filter_var( $img_url, FILTER_VALIDATE_URL ) ) {
            if ( isset( $image_cache[ $img_url ] ) ) {
                $attach_id = $image_cache[ $img_url ];
            } else {
                $attach_id = download_image_to_media( $img_url, $name );
                if ( $attach_id && ! is_wp_error( $attach_id ) ) {
                    $image_cache[ $img_url ] = $attach_id;
                } else {
                    $attach_id = 0;
                }
            }
            if ( $attach_id ) {
                $variation->set_image_id( $attach_id );
                echo "  VAR: $name (ID: " . $variation->save() . ", image: $attach_id)\n";
            }
        }
    }

    $variation->save();
    $var_count++;
}

fclose( $file );
echo "\n=== DONE: $imported products + $var_count variations imported ===\n";
echo "=== Image cache: " . count( $image_cache ) . " unique images downloaded ===\n";

// Clear page-data cache
delete_transient( 'phantom_page_data' );
echo "=== Page-data cache cleared ===\n";

/**
 * Download an image URL and create a WordPress media attachment
 */
function download_image_to_media( $url, $product_name ) {
    // Download to temp file
    $tmp = download_url( $url, 30 );
    if ( is_wp_error( $tmp ) ) {
        return $tmp;
    }

    // Get filename from URL
    $filename = basename( parse_url( $url, PHP_URL_PATH ) );
    if ( empty( $filename ) ) {
        $filename = 'product-image-' . sanitize_title( $product_name ) . '.jpg';
    }

    // Prepare file array for media_handle_sideload
    $file_array = array(
        'name'     => $filename,
        'tmp_name' => $tmp,
    );

    // Include media functions
    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';

    // Sideload the file
    $attach_id = media_handle_sideload( $file_array, 0 );

    return $attach_id;
}
