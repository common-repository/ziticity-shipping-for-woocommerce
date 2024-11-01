<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://ziticity.com
 * @since      1.0.0
 *
 * @package    Woocommerce_Shipping_Ziticity
 * @subpackage Woocommerce_Shipping_Ziticity/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Woocommerce_Shipping_Ziticity
 * @subpackage Woocommerce_Shipping_Ziticity/admin
 * @author     ZITICITY <info@ziticity.com>
 */
class WC_Shipping_Ziticity_Admin {

    const ZITICITY = 'ziticity';

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

        $screen    = get_current_screen();
        $screen_id = $screen ? $screen->id : '';

        if ( 'woocommerce_page_wc-settings' === $screen_id ) {

            wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/wc-shipping-ziticity-admin.css', array(), $this->version, 'all');

        }

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

        $screen    = get_current_screen();
        $screen_id = $screen ? $screen->id : '';

        if ( 'woocommerce_page_wc-settings' === $screen_id ) {

            wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/wc-shipping-ziticity-admin.js', array('jquery'), $this->version, false);

        }

	}

    /**
     * Add the meta box for ZITICITY info on the order page.
     */
    public function add_meta_box() {
        global $post;

        $order = wc_get_order( $post->ID );

        if ( $order && $order->has_shipping_method( self::ZITICITY ) ) {
            add_meta_box('woocommerce-shipping-ziticity', __('ZITICITY', 'ziticity-shipping-for-woocommerce'), array($this, 'meta_box'), 'shop_order', 'side', 'high');
        }
    }

    /**
     * Show the meta box for ZITICITY info on the order page.
     */
    public function meta_box() {
        global $post, $wpdb;

        $order = wc_get_order( $post->ID );
        $settings = get_option( 'woocommerce_ziticity_settings', [] );
        $ziti_order_id = get_post_meta( $post->ID, 'wc_shipping_ziticity_order_id', true );

        $warehouses = isset( $settings['warehouses'] ) ? $settings['warehouses'] : [];
        $warehouses_select = [];
        $parcel_locker_box_sizes = [];

        $warehouses_select[''] = __('Select a warehouse', 'ziticity-shipping-for-woocommerce');

        foreach ( $warehouses as $key => $warehouse ) {
            $warehouses_select[ $key ] = $warehouse['address'];
        }

        if ( count( $warehouses_select ) < 2 ) {
            echo '<p>' . __('You must have at least one warehouse to submit an order to ZITICITY.', 'ziticity-shipping-for-woocommerce') . '</p>';

            return;
        }

        if ( ! $order->is_paid() ) {
            echo '<p>' . __('After the order status becomes paid, you will be allowed to submit the order to ZITICITY.', 'ziticity-shipping-for-woocommerce') . '</p>';

            return;
        }

        echo '<div id="order-ziticity-form">';

        if ( empty( $ziti_order_id ) || get_post_meta( $post->ID, 'wc_shipping_ziticity_order_status', true ) == 'cancelled' ) {
            // If shipping to parcel locker
            $service_type = get_post_meta( $post->ID, 'wc_shipping_ziticity_service_type', true );
            $parcel_locker_service = $this->parcel_locker_service_type();
            $parcel_locker = false;
            $custom_attributes = [];

            if ( $service_type == $parcel_locker_service->id ) {
                $parcel_locker = true;
                $parcel_lockers_select = [];
                $parcel_lockers = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}ziticity_parcel_lockers ORDER BY id ASC", ARRAY_A );

                foreach ($parcel_lockers as $locker) {
                    $parcel_lockers_select[ $locker['parcel_locker_id'] ] = $locker['label'];
                }

                $box_sizes = isset( $settings['parcel_locker_box_sizes'] ) ? $settings['parcel_locker_box_sizes'] : [];

                if ( ! empty( $box_sizes ) ) {
                    foreach ( $box_sizes as $parcel_locker_box_size ) {
                        $parcel_locker_box_sizes[ $parcel_locker_box_size->value ] = $parcel_locker_box_size->label;
                    }
                }

                $custom_attributes = ['disabled' => $parcel_locker];
            }

            $products = $order->get_items();
            $product_weight = 0;

            foreach ( $products as $product ) {
                $product_data = $product->get_product();

                if ($product_data->get_weight()) {
                    $product_weight += ($product_data->get_weight() / $this->shop_weight_divider()) * $product->get_quantity();
                }
            }

            if ($product_weight == 0 && $settings['default_total_weight'] >= 0) {
                $product_weight = $settings['default_total_weight'];
            }

            woocommerce_wp_hidden_input(array(
                'id' => 'wc_shipment_tracking_get_nonce',
                'value' => wp_create_nonce('get-tracking-item'),
            ));

            woocommerce_wp_hidden_input(array(
                'id' => 'wc_shipment_tracking_create_nonce',
                'value' => wp_create_nonce('create-tracking-item'),
            ));

            woocommerce_wp_select(array(
                'id' => 'ziticity_warehouse',
                'label' => __('Select a warehouse:', 'ziticity-shipping-for-woocommerce'),
                'placeholder' => '',
                'description' => '',
                'options' => $warehouses_select,
                'value' => $settings['default_warehouse'] >= 0 ? $settings['default_warehouse'] : '',
            ));

            if ( $parcel_locker ) {
                woocommerce_wp_select(array(
                    'id' => 'ziticity_parcel_locker',
                    'label' => __('Parcel locker:', 'ziticity-shipping-for-woocommerce'),
                    'placeholder' => '',
                    'description' => '',
                    'options' => $parcel_lockers_select,
                    'value' => get_post_meta( $post->ID, 'wc_shipping_ziticity_parcel_locker', true ),
                ));

                woocommerce_wp_select(array(
                    'id' => 'ziticity_parcel_locker_size',
                    'label' => __('Parcel locker box size:', 'ziticity-shipping-for-woocommerce'),
                    'placeholder' => '',
                    'description' => '',
                    'options' => $parcel_locker_box_sizes,
                    'value' => ! empty( $settings['default_parcel_locker_size'] ) ? $settings['default_parcel_locker_size'] : '',
                ));
            }

            woocommerce_wp_checkbox(array(
                'id' => 'ziticity_contains_alco',
                'label' => '',
                'placeholder' => '',
                'description' => __('Does the order contain alcohol?', 'ziticity-shipping-for-woocommerce'),
                'cbvalue' => 'yes',
                'value' => $settings['default_contains_alcohol'] == 'yes' && ! $parcel_locker ? 'yes' : 'no',
                'custom_attributes' => $custom_attributes,
            ));

            woocommerce_wp_checkbox(array(
                'id' => 'ziticity_contains_energy',
                'label' => '',
                'placeholder' => '',
                'description' => __('Does the order contain energy drinks?', 'ziticity-shipping-for-woocommerce'),
                'cbvalue' => 'yes',
                'value' => $settings['default_contains_energy'] == 'yes' && ! $parcel_locker ? 'yes' : 'no',
                'custom_attributes' => $custom_attributes,
            ));

            woocommerce_wp_text_input(array(
                'id' => 'ziticity_qty',
                'label' => __('Number of packages:', 'ziticity-shipping-for-woocommerce'),
                'placeholder' => '',
                'description' => '',
                'type' => 'number',
                'value' => 1,
                'custom_attributes' => $custom_attributes,
            ));

            woocommerce_wp_text_input(array(
                'id' => 'ziticity_weight',
                'label' => __('Total weight (kg):', 'ziticity-shipping-for-woocommerce'),
                'placeholder' => '',
                'description' => '',
                'value' => $product_weight,
            ));

            echo '<button class="button button-primary button-save-form">' . __('Submit the order to ZITICITY', 'ziticity-shipping-for-woocommerce') . '</button>';

        } else {

            echo '<p>' . __('The order was submitted to ZITICITY.', 'ziticity-shipping-for-woocommerce') . '</p>';

            if ($management_url = get_post_meta($post->ID, 'wc_shipping_ziticity_management_url', true)) {
                echo '<p>' . __('Track order status ', 'ziticity-shipping-for-woocommerce') . '<a href="' . esc_url($management_url) . '" target="_blank">' . __('here', 'ziticity-shipping-for-woocommerce') . '</a></p>';
            }

            echo '<p><a class="button button-primary button-save-form" href="' . wp_nonce_url( admin_url( 'admin-ajax.php' ) . '?action=ziticity_get_labels&order=' . $post->ID, 'ziticity-request-labels' ) . '">' . __('Get shipping labels', 'ziticity-shipping-for-woocommerce') . '</a></p>';
            echo '<p><a class="button button-save-form" href="' . wp_nonce_url(admin_url('admin-ajax.php') . '?action=ziticity_cancel_order&order=' . $post->ID, 'ziticity-cancel-order') . '">' . __('Cancel delivery', 'ziticity-shipping-for-woocommerce') . '</a></p>';

        }

        echo '</div>';

    }

    /**
     * Order ZITICITY info save.
     *
     * Function for submitting order to ZITICITY.
     */
    public function save_meta_box( $post_id, $post ) {
        if ( isset( $_POST['ziticity_warehouse'] ) && strlen( $_POST['ziticity_warehouse'] ) > 0 ) {
            $this->submit_order_to_ziticity( $post_id );
        }
    }

    /**
     * Request shipping labels from ZITICITY.
     */
    public function get_shipping_labels() {
        if ( current_user_can( 'edit_shop_orders' ) && check_admin_referer( 'ziticity-request-labels' ) ) {
            $order = wc_get_order( intval( $_GET['order'] ) );
            $ziti_order_id = get_post_meta( $order->get_id(), 'wc_shipping_ziticity_order_id', true );

            if ( ! empty( $ziti_order_id ) ) {
                if ( $response = WC_Shipping_Ziticity_Ajax::http_client( 'orders/' . $ziti_order_id . '/generate-shipping-labels' ) ) {
                    $this->get_labels_output( $response, $order );
                }
            }

            die('Something went is wrong, please try again.');
        }
    }

    /**
     * Request ZITICITY order cancellation.
     */
    public function cancel_order() {
        if ( current_user_can( 'edit_shop_orders' ) && check_admin_referer( 'ziticity-cancel-order' ) ) {
            $order = wc_get_order( intval( $_GET['order'] ) );
            $ziti_order_id = get_post_meta( $order->get_id(), 'wc_shipping_ziticity_order_id', true );
            $has_errors = true;

            if ( ! empty( $ziti_order_id ) ) {
                if ( $response = WC_Shipping_Ziticity_Ajax::http_client( 'orders/' . $ziti_order_id . '/cancel' ) ) {
                    if ( ! empty( $response->id ) ) {
                        update_post_meta( $order->get_id(), 'wc_shipping_ziticity_order_status', $response->status );

                        if ( $response->status != 'cancelled' ) {
                            $order->add_order_note( __('This order delivery cannot be cancelled.', 'ziticity-shipping-for-woocommerce'), false, true );
                        }

                        wp_safe_redirect( admin_url( 'post.php?post=' . $order->get_id() . '&action=edit' ) );
                        exit;
                    }
                }
            }

            if ( $has_errors ) {
                die('Something went is wrong, please try again.');
            }
        }
    }

    /**
     * Define orders bulk actions.
     */
    public function define_orders_bulk_actions( $actions ) {
        $actions['ziticity_print_shipping_labels'] = __( 'Print ZITICITY shipping labels', 'ziticity-shipping-for-woocommerce' );

        return $actions;
    }

    /**
     * Handle orders bulk actions.
     */
    public function handle_orders_bulk_actions( $redirect_to, $action, $ids ) {
        $ids     = array_map( 'absint', $ids );
        $failed = 0;

        if ( 'ziticity_print_shipping_labels' === $action ) {
            if ( current_user_can( 'edit_shop_orders' ) ) {
                $ziticity_ids = [];
                $failed_ids = [];

                foreach ( $ids as $id ) {
                    $order = wc_get_order( $id );

                    if ( $order ) {
                        $ziti_order_id = $this->submit_order_to_ziticity( $id, true );

                        if ( ! empty( $ziti_order_id ) ) {
                            $ziticity_ids[] = $ziti_order_id;
                        } else {
                            $failed_ids[] = $id;
                            $failed++;
                        }
                    }
                }

                if ($failed > 0) {
                    $redirect_to = add_query_arg([
                        'post_type'   => 'shop_order',
                        'bulk_action' => 'ziticity_printed_shipping_labels',
                        'failed'     => $failed,
                        'ids'         => join( ',', $failed_ids ),
                    ], $redirect_to);

                    return esc_url_raw( $redirect_to );
                }

                if ( count( $ziticity_ids ) > 0 ) {
                    if ( $response = WC_Shipping_Ziticity_Ajax::http_client( 'orders/generate-shipping-labels', [
                        'orders' => $ziticity_ids,
                    ] ) ) {
                        $this->get_labels_output( $response );
                    }
                }
            }
        }

        return esc_url_raw( $redirect_to );
    }

    public function bulk_admin_notices() {
        global $post_type, $pagenow;

        // Bail out if not on shop order list page.
        if ( 'edit.php' !== $pagenow || 'shop_order' !== $post_type || ! isset( $_REQUEST['bulk_action'] ) ) { // WPCS: input var ok, CSRF ok.
            return;
        }

        $number      = isset( $_REQUEST['failed'] ) ? intval( $_REQUEST['failed'] ) : 0; // WPCS: input var ok, CSRF ok.
        $ids         = isset( $_REQUEST['ids'] ) ? wc_clean( wp_unslash( $_REQUEST['ids'] ) ) : ''; // WPCS: input var ok, CSRF ok.
        $bulk_action = wc_clean( wp_unslash( $_REQUEST['bulk_action'] ) ); // WPCS: input var ok, CSRF ok.
        $message     = '';

        if ( 'ziticity_printed_shipping_labels' === $bulk_action ) { // WPCS: input var ok, CSRF ok.
            if ( $number > 0 ) {
                $message = sprintf( __( 'Could not send these orders to ZITICITY: %s. Check ZITICITY order configuration and try again.', 'ziticity-shipping-for-woocommerce' ), $ids );
            } else {
                $message = __( 'Could not send orders to ZITICITY due to unknown error.', 'ziticity-shipping-for-woocommerce' );
            }
        }

        if ( ! empty( $message ) ) {
            echo '<div class="updated"><p>' . wp_kses( $message, [
                    'a' => [
                        'href'  => [],
                        'title' => [],
                    ],
                ] ) . '</p></div>';
        }
    }

    public static function get_parcel_lockers_list() {
        global $wpdb;

        $settings = get_option( 'woocommerce_ziticity_settings', [] );

        if ( ! empty( $settings ) ) {
            $service_types = $settings['service_types'] ? $settings['service_types'] : [];

            $service_types = array_map(function ($type) {
                return $type->label;
            }, $service_types);

            if ( in_array( 'parcel_locker', $service_types ) ) {
                $wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}ziticity_parcel_lockers" );

                $data = self::ziticity_parcel_lockers_list();

                if ( $data ) {
                    self::update_parcel_lockers_list( $data );
                }

                update_option( 'woocommerce_ziticity_lockers_updated_at', current_time('Y-m-d H:i') );
            }
        }
    }

    private static function ziticity_parcel_lockers_list() {
        $parcels_list = [];
        $response = WC_Shipping_Ziticity_Ajax::http_client( 'parcel-lockers' );

        if ( ! empty( $response ) ) {
            foreach ( $response as $locker ) {
                $parcels_list[] = [
                    'parcel_locker_id' => $locker->id,
                    'name' => $locker->name,
                    'label' => $locker->label,
                    'notes' => $locker->address->notes,
                    'country' => $locker->address->country,
                    'city' => $locker->address->city,
                    'post_code' => $locker->address->postal_code,
                    'street' => $locker->address->street_address,
                    'longitude' => $locker->address->coordinates->lng,
                    'latitude' => $locker->address->coordinates->lat,
                    'available_box_sizes' => maybe_serialize($locker->available_box_sizes),
                ];
            }
        }

        return $parcels_list;
    }

    private static function update_parcel_lockers_list( $lockers = [] ) {
        global $wpdb;

        foreach ( $lockers as $locker ) {
            $wpdb->insert( $wpdb->prefix . 'ziticity_parcel_lockers', $locker );
        }
    }

    /**
     * Submit order.
     */
    private function submit_order_to_ziticity( $post_id, $use_defaults = false ) {
        $order = wc_get_order( $post_id );
        $ziti_order_id = get_post_meta( $post_id, 'wc_shipping_ziticity_order_id', true );
        $ziti_order_status = get_post_meta( $post_id, 'wc_shipping_ziticity_order_status', true );

        if ( $order && $order->has_shipping_method( self::ZITICITY ) && $order->is_paid() && ( empty( $ziti_order_id ) || $ziti_order_status == 'cancelled' ) ) {
            $settings = get_option( 'woocommerce_ziticity_settings', [] );
            $warehouses = isset( $settings['warehouses'] ) ? $settings['warehouses'] : [];

            // If shipping to parcel locker
            $service_type = get_post_meta( $post_id, 'wc_shipping_ziticity_service_type', true );
            $parcel_locker_service = $this->parcel_locker_service_type();
            $parcel_locker = false;

            if ( $service_type == $parcel_locker_service->id ) {
                $parcel_locker = true;
            }

            if (! $use_defaults) {
                $data = [
                    'ziticity_warehouse' => wc_clean( $_POST['ziticity_warehouse'] ),
                    'ziticity_qty' => $parcel_locker ? 1 : intval( $_POST[ 'ziticity_qty' ] ),
                    'ziticity_weight' => (float) $_POST[ 'ziticity_weight' ],
                    'has_alcohol' => wc_clean( $_POST['ziticity_contains_alco'] ) == 'yes' && ! $parcel_locker,
                    'has_energy_drinks' => wc_clean( $_POST['ziticity_contains_energy'] ) == 'yes' && ! $parcel_locker,
                ];

                if ( $parcel_locker ) {
                    $data['ziticity_parcel_locker'] = wc_clean( $_POST['ziticity_parcel_locker'] );
                    $data['ziticity_parcel_locker_size'] = wc_clean( $_POST['ziticity_parcel_locker_size'] );
                }
            } else {
                $products = $order->get_items();
                $product_weight = 0;

                foreach ( $products as $product ) {
                    $product_data = $product->get_product();

                    if ($product_data->get_weight()) {
                        $product_weight += ($product_data->get_weight() / $this->shop_weight_divider()) * $product->get_quantity();
                    }
                }

                if ($product_weight == 0 && $settings['default_total_weight'] >= 0) {
                    $product_weight = $settings['default_total_weight'];
                }

                $data = [
                    'ziticity_warehouse' => $settings['default_warehouse'] >= 0 ? $settings['default_warehouse'] : '',
                    'ziticity_qty' => 1,
                    'ziticity_weight' => (float) $product_weight,
                    'has_alcohol' => $settings['default_contains_alcohol'] == 'yes' && ! $parcel_locker,
                    'has_energy_drinks' => $settings['default_contains_energy'] == 'yes' && ! $parcel_locker,
                ];

                if ( $parcel_locker ) {
                    $data['ziticity_parcel_locker'] = get_post_meta( $post_id, 'wc_shipping_ziticity_parcel_locker', true );
                    $data['ziticity_parcel_locker_size'] = ! empty( $settings['default_parcel_locker_size'] ) ? $settings['default_parcel_locker_size'] : '';
                }
            }

            if ( empty( $warehouses ) || ! isset( $warehouses[ $data['ziticity_warehouse'] ] ) ) {
                return null;
            }

            $warehouse = $warehouses[ $data['ziticity_warehouse'] ];
            $delivery_time = get_post_meta( $post_id, 'wc_shipping_ziticity_delivery_time', true );
            $delivery_time = explode( '|', $delivery_time );
            $delivery = null;
            $packages = [];

            $qty = $data['ziticity_qty'];
            $weight = $data['ziticity_weight'];

            if ( $qty > 0 && $weight > 0 ) {
                $weight_per_package = (int) floor( ($weight * 1000) / $qty );
                $weight_last_package = ((int) ($weight * 1000) - $weight_per_package * $qty) + $weight_per_package;

                for ( $i = 0; $i < $qty; $i++ ) {
                    $packages[] = [
                        'description' => $order->get_order_number(),
                        'weight' => ($i > 0 && $i == $qty - 1) ? $weight_last_package : $weight_per_package,
                    ];
                }
            }

            if ( empty( $packages ) || empty( $weight ) || $weight == 0 ) {
                return null;
            }

            if ( ! empty( $delivery_time ) && isset( $delivery_time[0] ) && isset( $delivery_time[1] ) ) {
                $delivery = [
                    'start' => $delivery_time[0],
                    'end' => $delivery_time[1],
                ];
            }

            if ( ! $parcel_locker ) {
                $params = [
                    'pickup_address' => [
                        'address' => wc_clean($warehouse['address']),
                        'contact_name' => wc_clean($warehouse['contact_person']),
                        'contact_phone' => wc_clean($warehouse['contact_phone']),
                        'notes' => wc_clean($warehouse['comment']),
                    ],
                    'deliveries' => [
                        [
                            'delivery_address' => [
                                'street_address' => $order->get_shipping_address_1(),
                                'apt_or_company' => $order->get_shipping_company(),
                                'city' => $order->get_shipping_city(),
                                'postal_code' => $order->get_shipping_postcode(),
                                'country_code' => $order->get_shipping_country(),
                                'contact_name' => $order->get_formatted_shipping_full_name(),
                                'contact_phone' => $order->get_billing_phone(),
                                'contact_email' => $order->get_billing_email(),
                                'notes' => $order->get_customer_note(),
                            ],
                            'payment_type' => $order->get_payment_method() == 'cod' ? 'card' : 'prepaid',
                            'payment_amount' => number_format($order->get_total(), 2, '.', ''),
                            'has_alcohol' => $data['has_alcohol'],
                            'has_energy_drinks' => $data['has_energy_drinks'],
                            'packages' => $packages,
                        ],
                    ],
                    'service_type' => $this->service_type_label_by_id($service_type),
                    'delivery_time_block' => $delivery,
                ];
            } else {
                $params = [
                    'pickup_address' => [
                        'address' => wc_clean($warehouse['address']),
                        'contact_name' => wc_clean($warehouse['contact_person']),
                        'contact_phone' => wc_clean($warehouse['contact_phone']),
                    ],
                    'deliveries' => [
                        [
                            'parcel_locker_delivery' => [
                                'contact_name' => $order->get_formatted_shipping_full_name(),
                                'contact_phone' => $order->get_billing_phone(),
                                'parcel_locker_id' => $data['ziticity_parcel_locker'],
                                'box_size' => $data['ziticity_parcel_locker_size'],
                            ],
                            'packages' => $packages,
                        ],
                    ],
                    'service_type' => 'parcel_locker',
                ];
            }

            if ( $response = WC_Shipping_Ziticity_Ajax::http_client( 'orders', $params ) ) {
                if ( ! empty( $response->id ) ) {
                    update_post_meta( $post_id, 'wc_shipping_ziticity_order_id', $response->id );
                    update_post_meta( $post_id, 'wc_shipping_ziticity_order_status', $response->status );
                    update_post_meta( $post_id, 'wc_shipping_ziticity_management_url', $response->management_url );

                    return $response->id;
                } else {
                    if ( isset( $response->deliveries[0]->packages[0]->message ) ) {
                        $order->add_order_note( 'ZITICITY: ' . $response->deliveries[0]->packages[0]->message, false, true );
                    } elseif ( isset( $response->delivery_time_block->start[0]->message ) ) {
                        $order->add_order_note( 'ZITICITY: ' . $response->delivery_time_block->start[0]->message, false, true );
                    } else {
                        $order->add_order_note( __('Cannot submit this order to ZITICITY.', 'ziticity-shipping-for-woocommerce'), false, true );
                    }

                    return null;
                }
            }
        }

        return ! empty( $ziti_order_id ) ? $ziti_order_id : null;
    }

    private function shop_weight_divider() {
        $shop_weight_unit = get_option( 'woocommerce_weight_unit' );

        if ( $shop_weight_unit === 'oz' ) {
            return 35.274;
        } elseif ( $shop_weight_unit === 'lbs' ) {
            return 2.20462;
        } elseif ( $shop_weight_unit === 'g' ) {
            return 1000;
        }

        return 1;
    }

    /**
     * Output PDF.
     */
    private function get_labels_output( $pdf, $order = null, $file_name = 'ZITICITY-shipping-labels' ) {
        $name = $file_name . '.pdf';

        if ($order != null) {
            $name = $file_name . '-' . $order->get_order_number() . '.pdf';
        }

        header( 'Content-Description: File Transfer' );
        header( 'Content-Type: application/pdf' );
        header( 'Content-Disposition: attachment; filename="' . $name . '"' );
        header( 'Content-Transfer-Encoding: binary' );
        header( 'Connection: Keep-Alive' );
        header( 'Expires: 0' );
        header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
        header( 'Pragma: public' );

        echo $pdf;

        die;
    }

    private function parcel_locker_service_type() {
        $settings = get_option( 'woocommerce_ziticity_settings', [] );
        $parcel_locker = array_filter($settings['service_types'], function ($type) {
            return $type->label == 'parcel_locker';
        });

        return reset($parcel_locker);
    }

    private function service_type_label_by_id($id) {
        $settings = get_option( 'woocommerce_ziticity_settings', [] );
        $service_type = array_filter($settings['service_types'], function ($type) use ($id) {
            return $type->id == $id;
        });

        $reset = reset($service_type);

        return $reset->label;
    }
}
