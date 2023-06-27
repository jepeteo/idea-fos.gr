<?php

// Calculate shipping costs based on cart value and product weight
function idea_wc_calc_shipping_cost( $cost, $method ) {
    if ( in_array( $method->get_instance_id(), array( 11 ) ) && WC()->cart ) {
        $cart_value = idea_wc_calc_cart_value();

        if ( $cart_value >= 200 ) {
            $cost = 0; // Free shipping
        } else {
            $non_glb_cost = idea_wc_calc_shipping_cost_no_glb();
            $glb_cost = idea_wc_calc_shipping_cost_glb();

            $cost = $non_glb_cost + $glb_cost;
        }
    }
//    echo '[DEBUG] Final shipping cost: ' . $cost . PHP_EOL;
    return $cost;
}

// Calculate the total cart value
function idea_wc_calc_cart_value() {
    $total_cost = 0;

    foreach ( WC()->cart->get_cart() as $cart_item ) {
        $product = $cart_item['data'];
        $total_cost += $product->get_price() * $cart_item['quantity'];
    }

    return $total_cost;
}

// Calculate shipping cost for products without the "glb" tag
function idea_wc_calc_shipping_cost_no_glb() {
    $non_glb_item_count = 0;

    foreach ( WC()->cart->get_cart() as $cart_item ) {
        $product = $cart_item['data'];

        if ( ! has_term( 'glb', 'product_tag', $product->get_id() ) ) {
            $non_glb_item_count += $cart_item['quantity'];
        }
    }

    $additional_cost = 0;

    if ( $non_glb_item_count > 0 ) {
        $additional_cost = 5 + ($non_glb_item_count - 1) * 3.9;
    }

//    echo '[DEBUG] Additional cost for non-glb items: '  . $additional_cost . PHP_EOL;

    return $additional_cost;
}

// Calculate shipping cost for products with the "glb" tag
function idea_wc_calc_shipping_cost_glb() {
    $glb_weight = 0;
    $shipping_cost_per_kg = 1;
    $base_cost = 4.5;
    $has_glb_products = false;

    foreach ( WC()->cart->get_cart() as $cart_item ) {
        $product = $cart_item['data'];

        if ( has_term( 'glb', 'product_tag', $product->get_id() ) ) {
            $has_glb_products = true;
            $glb_weight += $product->get_weight() * $cart_item['quantity'];
        }
    }

    $additional_cost = 0;

    if ( $has_glb_products ) {
        if ( $glb_weight > 2 ) {
            $additional_cost = $base_cost + ($glb_weight - 2) * $shipping_cost_per_kg;
        } else {
            $additional_cost = $base_cost;
        }
    }

//    echo '[DEBUG] Shipping cost for glb items: ' . $additional_cost . PHP_EOL;

    return $additional_cost;
}
// Hook into the shipping rate cost filter
add_filter( 'woocommerce_shipping_rate_cost', 'idea_wc_calc_shipping_cost', 10, 2 );


// Prevent adding products to cart that are on backorder
function prevent_backorders_add_to_cart_validation( $passed_validation, $product_id, $quantity, $variation_id = '', $variations = '' ) {
    $product = wc_get_product( $product_id );
    if ( $product->is_on_backorder() ) {
        // Product is on backorder, prevent adding to cart
        wc_add_notice( __( 'Προϊόντα σε προπαραγγελία δεν μπορουν να προστεθουν στο καλάθι.', 'your-text-domain' ), 'error' );
        $passed_validation = false;
    }
    return $passed_validation;
}
add_filter( 'woocommerce_add_to_cart_validation', 'prevent_backorders_add_to_cart_validation', 10, 5 );


// Add a notice before payment regarding shipping costs
function notice_beforepayment() {
    echo  '<p class="shipping-notice">' .__('Λόγο των συνεχών ανατιμήσεων των προϊόντων, σας παρακαλούμε πριν την κατάθεση των χρημάτων επικοινωνήσετε με την εταιρεία στον τηλέφωνο 2238024802.<br> Ευχαριστούμε για την κατανόηση. ','greekonline').'</p>';
}
add_action( 'woocommerce_review_order_before_payment', 'notice_beforepayment' );

?>