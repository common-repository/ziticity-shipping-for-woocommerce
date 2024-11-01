<?php

class WC_Shipping_Ziticity_Ajax {
    public function checkout_save_session_fields( $post_data ) {
        parse_str( $post_data, $posted );

        if ( isset( $posted[ 'wc_shipping_ziticity_parcel_locker' ] ) && ! empty( $posted[ 'wc_shipping_ziticity_parcel_locker' ] ) ) {
            WC()->session->set( 'wc_shipping_ziticity_parcel_locker', $posted[ 'wc_shipping_ziticity_parcel_locker' ] );
        }
    }

    public static function http_client( $endpoint, $params = [] ) {
        $settings = get_option( 'woocommerce_ziticity_settings', [] );
        $api_host = 'https://api-ext.ziticity.com/';

        if ( $settings['test_mode'] == 'yes' ) {
            $api_host = 'https://api-ext.staging.ziticity.com/';
        }

        if ( empty( $settings['api_token'] ) ) {
            return false;
        }

        $method = in_array($endpoint, ['properties', 'parcel-lockers']) ? 'GET' : 'POST';

        $response = wp_remote_request( $api_host . $endpoint . '/', [
                'method'      => $method,
                'timeout'     => 15,
                'redirection' => 0,
                'httpversion' => '1.1',
                'headers'     => array(
                    'Authorization' => 'Token ' . $settings['api_token'],
                    'Content-Type' => 'application/json',
                    'Accept-Language' => get_bloginfo( 'language' ),
                    'X-Source' => 'woocommerce-' . get_bloginfo( 'version' ) . '_' . WC()->version . '_' . WC_SHIPPING_ZITICITY_VERSION,
                ),
                'body'        => ! empty( $params ) ? json_encode($params) : '',
                'sslverify'   => false,
            ]
        );

        if ( is_wp_error( $response ) ) {
            return $response->get_error_message();
        } else {
            $body = wp_remote_retrieve_body( $response );

            if ( strpos( $endpoint, 'generate-shipping-labels' ) !== false ) {
                return $body;
            }

            return json_decode( $body );
        }
    }
}
