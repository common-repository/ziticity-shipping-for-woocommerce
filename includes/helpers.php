<?php

/*
 * Helper function to convert weight to kg.
 */
function ziticity_weight_in_kg($cart_weight) {
    $shop_weight_unit = get_option( 'woocommerce_weight_unit' );

    if ( $shop_weight_unit === 'oz' ) {
        $divider = 35.274;
    } elseif ( $shop_weight_unit === 'lbs' ) {
        $divider = 2.20462;
    } elseif ( $shop_weight_unit === 'g' ) {
        $divider = 1000;
    } else {
        $divider = 1;
    }

    return $cart_weight / $divider;
}
