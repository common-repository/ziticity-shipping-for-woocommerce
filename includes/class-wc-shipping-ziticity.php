<?php

class WC_Shipping_Ziticity extends WC_Shipping_Method {
    /**
     * Service type.
     */
    public $service_type = '';

	/**
	 * Min amount for free shipping.
	 */
    protected $free_min_amount = '';

    /**
     * Min weight for method disable.
     */
    protected $hide_if_weight = '';

	/**
	 * Price calculation type.
     *
     * @var string
	 */
    protected $type = 'order';

	/**
	 * Price cost rates.
     *
     * @var string
	 */
	protected $cost_rates = '';

    protected $parcel_locker_field_name;

	/**
	 * Constructor.
	 *
	 * @param int $instance_id Instance ID.
	 */
	public function __construct( $instance_id = 0 ) {
		$this->id                 = 'ziticity';
		$this->instance_id        = absint( $instance_id );
		$this->method_title       = __( 'ZITICITY', 'ziticity-shipping-for-woocommerce' );
		$this->method_description = __( 'ZITICITY shipping method.', 'ziticity-shipping-for-woocommerce' );
		$this->supports           = [
			'shipping-zones',
			'instance-settings',
			'instance-settings-modal',
            'settings',
		];

        $this->parcel_locker_field_name = 'wc_shipping_' . $this->id . '_parcel_locker';

		$this->init();
	}

    private function set_settings() {
        // API Settings.
        $this->api_token = $this->get_option( 'api_token' );
        $this->test_mode = $this->get_option( 'test_mode' ) === 'yes';
        $this->warehouses = $this->get_option( 'warehouses', [] );
        $this->cod_enabled = $this->get_option( 'cod_enabled' ) === 'yes';
        $this->service_types = $this->get_option( 'service_types', [] );
        $this->parcel_locker_box_sizes = $this->get_option( 'parcel_locker_box_sizes', [] );

        // Define user set variables.
        $this->title           = $this->get_option( 'title', $this->method_title );
        $this->service_type    = $this->get_option( 'service_type', '' );
        $this->tax_status      = $this->get_option( 'tax_status' );
        $this->cost            = $this->get_option( 'cost' );
        $this->free_min_amount = $this->get_option( 'free_min_amount', '' );
        $this->hide_if_weight  = $this->get_option( 'hide_if_weight', '' );

        $this->type       = $this->get_option( 'type', 'order' );
        $this->cost_rates = $this->get_option( 'cost_rates' );

        return true;
    }

	/**
	 * Init your settings
	 *
	 * @access public
	 * @return void
	 */
	private function init() {
        $this->api_token = $this->get_option( 'api_token' );
        $this->cod_enabled = $this->get_option( 'cod_enabled' ) === 'yes';
        $this->warehouses = $this->get_option( 'warehouses', [] );
        $this->service_types = $this->get_option( 'service_types', [] );
        $this->parcel_locker_box_sizes = $this->get_option( 'parcel_locker_box_sizes', [] );

        // Load the settings.
        $this->init_form_fields();
        $this->set_settings();

        // Actions.
        add_action( 'woocommerce_update_options_shipping_' . $this->id, [ $this, 'process_admin_options' ] );
        add_action( 'admin_footer', [ 'WC_Shipping_Ziticity', 'enqueue_admin_js' ], 10 ); // Priority needs to be higher than wc_print_js (25).
	}

	public function init_actions_and_filters() {
        add_action( 'woocommerce_review_order_after_shipping', [ $this, 'parcel_lockers_html' ] );
        add_action( 'woocommerce_checkout_update_order_meta', [ $this, 'checkout_save_order_ziticity_info' ], 10, 2 );
    }

	/**
	 * Define settings field for this shipping
	 * @return void
	 */
    public function init_form_fields() {
        $cod_desc = '';
        $warehouses[''] = __('Select a warehouse', 'ziticity-shipping-for-woocommerce');
        $service_types[0] = __( 'Select a service type', 'ziticity-shipping-for-woocommerce' );
        $parcel_locker_box_sizes[0] = __( 'Select a parcel locker box size', 'ziticity-shipping-for-woocommerce' );
        $available_service_types = [];

        if ( ! empty( $this->warehouses ) ) {
            foreach ( $this->warehouses as $key => $warehouse ) {
                $warehouses[ $key ] = $warehouse['address'];
            }
        }

        if ( ! empty( $this->service_types ) ) {
            foreach ( $this->service_types as $service_type ) {
                $service_types[ $service_type->id ] = $service_type->label;
                $available_service_types[] = $service_type->label;
            }
        }

        if ( ! empty( $this->parcel_locker_box_sizes ) ) {
            foreach ( $this->parcel_locker_box_sizes as $parcel_locker_box_size ) {
                $parcel_locker_box_sizes[ $parcel_locker_box_size->value ] = $parcel_locker_box_size->label;
            }
        }

	    if ( $this->api_token && $this->cod_enabled ) {
            $cod_desc = __( 'COD is <strong>available</strong> for this API token.', 'ziticity-shipping-for-woocommerce' );
        } elseif ( $this->api_token && ! $this->cod_enabled ) {
            $cod_desc = __( 'COD is <strong>disabled</strong> for this API token.', 'ziticity-shipping-for-woocommerce' );
        }

        $cod_desc .= '<br>' . __( 'Available delivery services:', 'ziticity-shipping-for-woocommerce' ) . ' ' . implode(', ', $available_service_types);

        if ( in_array( 'parcel_locker', $available_service_types ) ) {
            $cod_desc .= '<br>' . __( 'Parcel lockers list updated at:', 'ziticity-shipping-for-woocommerce' ) . ' ' . get_option( 'woocommerce_ziticity_lockers_updated_at', '--' );
        }

		$this->instance_form_fields = [
            'service_type' => [
                'title'   => __( 'Service type', 'woocommerce' ),
                'type'    => 'select',
                'class'   => 'wc-enhanced-select',
                'options' => $service_types,
            ],
			'title' => [
				'title'       => __( 'Method title', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
				'default'     => __( 'ZITICITY', 'ziticity-shipping-for-woocommerce' ),
				'desc_tip'    => true,
			],
			'tax_status' => [
				'title'   => __( 'Tax status', 'woocommerce' ),
				'type'    => 'select',
				'class'   => 'wc-enhanced-select',
				'default' => 'taxable',
				'options' => [
					'taxable' => __( 'Taxable', 'woocommerce' ),
					'none'    => _x( 'None', 'Tax status', 'woocommerce' ),
				],
			],
			'cost' => [
				'title'       => __( 'Cost', 'woocommerce' ),
				'type'        => 'text',
				'placeholder' => '',
				'description' => '',
				'default'     => '0',
				'desc_tip'    => true,
			],
			'free_min_amount' => [
				'title'       => __( 'Minimum order amount for free shipping', 'ziticity-shipping-for-woocommerce' ),
				'type'        => 'price',
				'placeholder' => '',
				'description' => __( 'Users have to spend this amount to get free shipping.', 'ziticity-shipping-for-woocommerce' ),
				'default'     => '',
				'desc_tip'    => true,
			],
            'hide_if_weight' => [
                'title'       => __( 'Hide this method if cart weight is more/equal than', 'ziticity-shipping-for-woocommerce' ),
                'type'        => 'decimal',
                'placeholder' => '',
                'description' => __( 'Products must have weight to make this option work. Enter weight in the same units that you have defined in settings.', 'ziticity-shipping-for-woocommerce' ),
                'default'     => '',
                'desc_tip'    => true,
            ],
			'type' => [
				'title'   => __( 'Calculation type', 'ziticity-shipping-for-woocommerce' ),
				'type'    => 'select',
				'class'   => 'wc-enhanced-select',
				'default' => 'order',
				'options' => [
					'order'  => __( 'Per order', 'ziticity-shipping-for-woocommerce' ),
					'weight' => __( 'Weight-based', 'ziticity-shipping-for-woocommerce' ),
				],
			],
			'cost_rates' => [
				'title'       => __( 'Rates', 'ziticity-shipping-for-woocommerce' ),
				'type'        => 'textarea',
				'placeholder' => '',
				'description' => __( 'Example: 5:10.00,7:12.00 Weight:Price,Weight:Price, etc...', 'ziticity-shipping-for-woocommerce' ),
				'default'     => '',
				'desc_tip'    => true,
			],
		];

        $this->form_fields = [
            'api' => [
                'title'       => __( 'API Settings', 'ziticity-shipping-for-woocommerce' ),
                'type'        => 'title',
                'description' => __( 'Your API access details are obtained from ZITICITY. API settings refresh every time you save changes.', 'ziticity-shipping-for-woocommerce' ),
            ],
            'api_token' => [
                'title'       => __( 'API token', 'ziticity-shipping-for-woocommerce' ),
                'type'        => 'password',
                'description' => $cod_desc,
                'default'     => '',
            ],
            'test_mode' => [
                'title'       => __( 'Test mode', 'ziticity-shipping-for-woocommerce' ),
                'label'       => __( 'Enable test mode', 'ziticity-shipping-for-woocommerce' ),
                'type'        => 'checkbox',
                'default'     => 'no',
                'description' => __( 'Enable this if you want your requests to go to ZITICITY test server.', 'ziticity-shipping-for-woocommerce' ),
                'desc_tip'    => true,
            ],
            'warehouses' => array(
                'type' => 'warehouses_locations',
            ),
            'default' => [
                'title'       => __( 'Default values', 'ziticity-shipping-for-woocommerce' ),
                'type'        => 'title',
                'description' => __( 'These values will be prefilled on Order page. You will be able to change them for each order separately.', 'ziticity-shipping-for-woocommerce' ),
            ],
            'default_warehouse' => [
                'title'   => __( 'Warehouse / pick-up location', 'ziticity-shipping-for-woocommerce' ),
                'type'    => 'select',
                'class'   => 'wc-enhanced-select',
                'default' => 0,
                'options' => $warehouses,
            ],
            'default_total_weight' => [
                'title'       => __( 'Total weight (kg)', 'ziticity-shipping-for-woocommerce' ),
                'type'        => 'text',
                'placeholder' => '',
                'description' => __( 'The default value will be used to products in the order do not have weight information.', 'ziticity-shipping-for-woocommerce' ),
                'default'     => '',
            ],
            'default_parcel_locker_size' => [
                'title'   => __( 'Parcel locker box size', 'ziticity-shipping-for-woocommerce' ),
                'type'    => 'select',
                'class'   => 'wc-enhanced-select',
                'default' => '',
                'options' => $parcel_locker_box_sizes,
            ],
            'default_contains_alcohol' => [
                'title'       => __( 'Has Alcohol', 'ziticity-shipping-for-woocommerce' ),
                'label'       => __( 'Yes', 'ziticity-shipping-for-woocommerce' ),
                'type'        => 'checkbox',
                'default'     => 'no',
                'description' => __( 'Not available for parcel locker deliveries.', 'ziticity-shipping-for-woocommerce' ),
            ],
            'default_contains_energy' => [
                'title'       => __( 'Has Energy drinks', 'ziticity-shipping-for-woocommerce' ),
                'label'       => __( 'Yes', 'ziticity-shipping-for-woocommerce' ),
                'type'        => 'checkbox',
                'default'     => 'no',
                'description' => __( 'Not available for parcel locker deliveries.', 'ziticity-shipping-for-woocommerce' ),
            ],
        ];
	}

    /**
     * Environment check.
     *
     * @return void
     */
    private function environment_check() {
        $error_message = '';

        if ( ! $this->api_token ) {
            $error_message .= '<p>' . __( 'ZITICITY is enabled, but you have not entered the API token!', 'ziticity-shipping-for-woocommerce' ) . '</p>';
        }

        if ( '' !== $error_message ) {
            echo '<div class="error">';
            echo $error_message;
            echo '</div>';
        }
    }

    /**
     * Admin options.
     *
     * @access public
     * @return void
     */
    public function admin_options() {
        // Check users environment supports this method.
        $this->environment_check();

        // Show settings.
        parent::admin_options();
    }

    /**
     * Validate API token.
     *
     * @return void
     */
    public function process_admin_options() {
        parent::process_admin_options();

        $this->set_settings();

        if ( $response = WC_Shipping_Ziticity_Ajax::http_client( 'properties' ) ) {
            if ( isset( $response->code ) && $response->code == 'authentication_failed' ) {
                // @todo remove api key if authentication fails

                echo '<div class="error">';
                echo '<p>' . $response->message . '</p>';
                echo '</div>';
            } else {
                $this->update_option( 'cod_enabled', $response->cod->enabled == true ? 'yes' : 'no' );
                $this->update_option( 'service_types', $response->service_types );
                $this->update_option( 'package_size_presets', $response->package_size_presets );
                $this->update_option( 'parcel_locker_box_sizes', $response->parcel_locker_box_sizes );

                wp_schedule_single_event( time() + 3, 'ziticity_parcel_lockers_updater' );
            }
        }
    }

    /**
     * HTML for warehouses locations.
     *
     * @return string HTML string.
     */
    public function generate_warehouses_locations_html() {
        ob_start();
        ?>
        <tr valign="top" id="warehouses_locations">
            <th scope="row" class="titledesc"><?php _e( 'Warehouses / pick-up locations', 'ziticity-shipping-for-woocommerce' ); ?></th>
            <td class="forminp">
                <table class="ziticity_warehouses widefat">
                    <thead>
                        <tr>
                            <th class="check-column"><input type="checkbox" /></th>
                            <th><?php _e( 'Address', 'ziticity-shipping-for-woocommerce' ); ?> <abbr>*</abbr></th>
                            <th><?php _e( 'Contact person / company name', 'ziticity-shipping-for-woocommerce' ); ?> <abbr>*</abbr></th>
                            <th><?php _e( 'Contact phone', 'ziticity-shipping-for-woocommerce' ); ?> <abbr>*</abbr></th>
                            <th><?php _e( 'Comment', 'ziticity-shipping-for-woocommerce' ); ?></th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th colspan="2">
                                <a href="#" class="button plus insert"><?php _e( 'Add Location', 'ziticity-shipping-for-woocommerce' ); ?></a>
                                <a href="#" class="button minus remove"><?php _e( 'Remove selected location(s)', 'ziticity-shipping-for-woocommerce' ); ?></a>
                            </th>
                            <th colspan="3">
                                <small class="description"><?php _e( 'You will be able to select from these locations when sending order information to ZITICITY.', 'ziticity-shipping-for-woocommerce' ); ?></small>
                            </th>
                        </tr>
                    </tfoot>
                    <tbody id="locations">
                        <tr>
                            <td class="check-column"></td>
                            <td><input type="text" size="45" name="warehouses_address[0]" value="<?php echo isset( $this->warehouses[0]['address'] ) ? esc_attr($this->warehouses[0]['address']) : ''; ?>" placeholder="<?php esc_attr_e( 'Ex., Ateities g. 10A, Vilnius, Lietuva', 'ziticity-shipping-for-woocommerce' ); ?>" /></td>
                            <td><input type="text" size="35" name="warehouses_contact_person[0]" value="<?php echo isset( $this->warehouses[0]['contact_person'] ) ? esc_attr($this->warehouses[0]['contact_person']) : ''; ?>" /></td>
                            <td><input type="text" size="20" name="warehouses_contact_phone[0]" value="<?php echo isset( $this->warehouses[0]['contact_phone'] ) ? esc_attr($this->warehouses[0]['contact_phone']) : ''; ?>" /></td>
                            <td><input type="text" size="30" name="warehouses_comment[0]" value="<?php echo isset( $this->warehouses[0]['comment'] ) ? esc_attr($this->warehouses[0]['comment']) : ''; ?>" /></td>
                        </tr>
                        <?php
                        if ( $this->warehouses && ! empty( $this->warehouses ) ) {
                            foreach ( $this->warehouses as $key => $warehouse ) {
                                if ( $key > 0 ) {
                                    ?>
                                    <tr>
                                        <td class="check-column"><input type="checkbox" /></td>
                                        <td><input type="text" size="45" name="warehouses_address[<?php echo $key; ?>]" value="<?php echo esc_attr( $warehouse['address'] ); ?>" placeholder="<?php esc_attr_e( 'Ex., Ateities g. 10A, Vilnius, Lietuva', 'ziticity-shipping-for-woocommerce' ); ?>" /></td>
                                        <td><input type="text" size="35" name="warehouses_contact_person[<?php echo $key; ?>]" value="<?php echo esc_attr( $warehouse['contact_person'] ); ?>" /></td>
                                        <td><input type="text" size="20" name="warehouses_contact_phone[<?php echo $key; ?>]" value="<?php echo esc_attr( $warehouse['contact_phone'] ); ?>" /></td>
                                        <td><input type="text" size="30" name="warehouses_comment[<?php echo $key; ?>]" value="<?php echo esc_attr( $warehouse['comment'] ); ?>" /></td>
                                    </tr>
                                    <?php
                                }
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </td>
        </tr>
        <?php
        return ob_get_clean();
    }

    /**
     * Validate warehouses locations.
     *
     * @param mixed $key Option's key.
     *
     * @return mixed Validated value.
     */
    public function validate_warehouses_locations_field( $key ) {
        $warehouses = array();

        if ( isset( $_POST['warehouses_address'] ) ) {
            $warehouses_address = wc_clean( $_POST['warehouses_address'] );
            $warehouses_contact_person = wc_clean( $_POST['warehouses_contact_person'] );
            $warehouses_contact_phone = wc_clean( $_POST['warehouses_contact_phone'] );
            $warehouses_comment = wc_clean( $_POST['warehouses_comment'] );

            for ( $i = 0; $i < sizeof( $warehouses_address ); $i ++ ) {
                if ( $warehouses_address[ $i ] && $warehouses_contact_person[ $i ] && $warehouses_contact_phone[ $i ] ) {
                    $warehouses[] = array(
                        'address'        => $warehouses_address[ $i ],
                        'contact_person' => $warehouses_contact_person[ $i ],
                        'contact_phone'  => $warehouses_contact_phone[ $i ],
                        'comment'        => $warehouses_comment[ $i ],
                    );
                }
            }
        }

        return $warehouses;
    }

	/**
	 * This function is used to calculate the shipping cost. Within this function we can check for weights, dimensions and other parameters.
	 *
	 * @access public
	 *
	 * @param mixed $package
	 *
	 * @return void
	 */
	public function calculate_shipping( $package = [] ) {
		$has_met_min_amount = false;
		$cost               = $this->cost;
		$weight             = WC()->cart ? WC()->cart->get_cart_contents_weight() : 0;

		if ( WC()->cart && ! empty( $this->free_min_amount ) && $this->free_min_amount > 0 ) {
			$total = WC()->cart->get_displayed_subtotal();

            if ( WC()->cart->display_prices_including_tax() ) {
                $total = $total - WC()->cart->get_discount_tax();
            }

            // Do not ignore discounts for totals
            $total = $total - WC()->cart->get_discount_total();

            $total = round( $total, wc_get_price_decimals() );

            if ( $total >= $this->free_min_amount ) {
                $has_met_min_amount = true;
            }
		}

		if ( $this->type == 'weight' ) {
			$rates = explode( ',', $this->cost_rates );

			foreach ( $rates as $rate ) {
				$data = explode( ':', $rate );

				if ( $data[0] >= $weight ) {
					if ( isset( $data[1] ) ) {
						$cost = str_replace( ',', '.', $data[1] );
					}

					break;
				}
			}
		}

		$rate = array(
			'id'      => $this->get_rate_id(),
			'label'   => $this->title,
			'cost'    => $has_met_min_amount ? 0 : $cost,
			'package' => $package,
		);

		$this->add_rate( $rate );

		do_action( 'woocommerce_' . $this->id . '_shipping_add_rate', $this, $rate );
	}

    /**
     * Is this method available?
     *
     * @param array $package Package.
     *
     * @return bool
     */
	public function is_available($package)
    {
        $available = $this->is_enabled();

        if ( empty( $this->service_type ) ) {
            return false;
        }

        $parcel_locker = $this->parcel_locker_service_type();

        if ( $this->service_type != $parcel_locker->id ) {
            $params = [
                'address' => [
                    'postal_code' => WC()->customer->get_shipping_postcode(),
                    'street_address' => WC()->customer->get_shipping_address(),
                    'city' => WC()->customer->get_shipping_city(),
                    'apt_or_company' => WC()->customer->get_shipping_company(),
                    'country_code' => WC()->customer->get_shipping_country(),
                ],
                'service_type' => $this->service_type,
            ];

            if ( empty( $params['address']['postal_code'] ) || empty( $params['address']['street_address'] ) || empty( $params['address']['city'] ) || empty( $params['address']['country_code'] ) ) {
                return $available;
            }

            if ( $response = WC_Shipping_Ziticity_Ajax::http_client( 'get-checkout-options', $params ) ) {
                if ( $response->address_serviced == false ) {
                    return false;
                }
            }
        }

        if ( empty( $this->hide_if_weight ) ) {
            return $available;
        }

        $cart_weight = WC()->cart === null ? 0 : WC()->cart->get_cart_contents_weight();
        $cart_weight_kg = ziticity_weight_in_kg($cart_weight);

        if ( $cart_weight_kg >= $this->hide_if_weight ) {
            return false;
        }

        return $available;
    }

    public function parcel_lockers_html( $method ) {
        global $wpdb;

        $chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods' );

        if ( ! empty( $chosen_shipping_methods ) && substr( $chosen_shipping_methods[0], 0, strlen( $this->id ) ) === $this->id ) {
            $current_shipping_method = explode( ':', $chosen_shipping_methods[0] );
            $settings = get_option( 'woocommerce_' . $this->id . '_' . $current_shipping_method[1] . '_settings', [] );

            if ( empty( $settings ) ) {
                return;
            }

            $parcel_locker = $this->parcel_locker_service_type();

            if ( $settings['service_type'] != $parcel_locker->id ) {
                return;
            }

            $select_data = [];

            if ( ! empty( WC()->customer->get_shipping_postcode() ) && ! empty( WC()->customer->get_shipping_address() ) && ! empty( WC()->customer->get_shipping_city() ) && ! empty( WC()->customer->get_shipping_country() ) ) {
                $params = [
                    'street_address' => WC()->customer->get_shipping_address(),
                    'city' => WC()->customer->get_shipping_city(),
                    'postal_code' => WC()->customer->get_shipping_postcode(),
                    'country_code' => WC()->customer->get_shipping_country(),
                ];

                $transient_name = "ziticity_parcel_lockers_{$params['street_address']}_{$params['city']}_{$params['postal_code']}_{$params['country_code']}";
                $transient = get_transient($transient_name);

                if ( empty( $transient ) ) {
                    if ( $response = WC_Shipping_Ziticity_Ajax::http_client( 'parcel-lockers/order-by-proximity', $params ) ) {
                        if (! empty($response)) {
                            if (! isset($response->address)) {
                                foreach ($response as $locker) {
                                    $select_data[] = [
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
                                        'distance' => $locker->distance,
                                    ];
                                }

                                set_transient($transient_name, $select_data, MINUTE_IN_SECONDS * 5);
                            }
                        }
                    }
                } else {
                    $select_data = $transient;
                }
            }

            if ( empty( $select_data ) ) {
                $select_data = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}ziticity_parcel_lockers ORDER BY id ASC", ARRAY_A );
            }

            $selected_parcel_locker = WC()->session->get( $this->parcel_locker_field_name );

            wc_get_template( 'checkout/ziticity-parcel-lockers.php', [
                'lockers'    => $select_data,
                'field_name' => $this->parcel_locker_field_name,
                'selected'   => '',
            ] );

        }
    }

    public function checkout_save_order_ziticity_info( $order_id ) {
	    $order = wc_get_order( $order_id );
        $selected_parcel_locker = $_POST[ $this->parcel_locker_field_name ] ? sanitize_text_field( $_POST[ $this->parcel_locker_field_name ] ) : false;

        if ( $order->has_shipping_method( 'ziticity' ) ) {
            foreach ( $order->get_shipping_methods() as $shipping_method ) {
                $settings = get_option( 'woocommerce_' . $this->id . '_' . $shipping_method->get_instance_id() . '_settings', [] );

                if ( ! empty( $settings ) ) {
                    update_post_meta( $order_id, 'wc_shipping_ziticity_service_type', $settings['service_type'] );
                }
            }

            if ( $selected_parcel_locker && $selected_parcel_locker != -1 ) {
                update_post_meta( $order_id, $this->parcel_locker_field_name, $selected_parcel_locker );
                WC()->session->set( $this->parcel_locker_field_name, null );
            }
        }
    }

    /**
     * Enqueue JS to handle ZITICITY options.
     *
     * Static so that's enqueued only once.
     */
    public static function enqueue_admin_js() {
        $settings = get_option( 'woocommerce_ziticity_settings', [] );

        $title_ifs = '';
        $weight_ifs = '';

        $locale = get_bloginfo('language');
        $locale = explode('-', $locale);
        $locale = $locale[0];

        if (! in_array($locale, ['en', 'lt', 'lv', 'et', 'fr'])) {
            $locale = 'en';
        }

        // Dynamic title from default values
        if ( ! empty( $settings ) ) {
            $i = 0;

            foreach ($settings['service_types'] as $service_type) {
                $i++;

                $title = $service_type->carrier_defaults->name->{$locale} ? $service_type->carrier_defaults->name->{$locale} : $service_type->carrier_defaults->name->en;

                $title_ifs .= ($i == 1 ? "if" : "else if") . " ( '{$service_type->id}' === $( el ).val() ) { titleField.val( '{$title}' ); }";
                $weight_ifs .= ($i == 1 ? "if" : "else if") . " ( '{$service_type->id}' === $( el ).val() ) { weightField.val( '{$service_type->carrier_defaults->maximum_package_weight_kg}' ); }";
            }
        }

        wc_enqueue_js(
            "jQuery( function( $ ) {
                function wcZiticityShowHideRatesField( el ) {
                    var form = $( el ).closest( 'form' );
                    var ratesField = $( '#woocommerce_ziticity_cost_rates', form ).closest( 'tr' );
                    if ( 'weight' !== $( el ).val() || '' === $( el ).val() ) {
                        ratesField.hide();
                    } else {
                        ratesField.show();
                    }
                }

                $( document.body ).on( 'change', '#woocommerce_ziticity_type', function() {
                    wcZiticityShowHideRatesField( this );
                });

                // Change while load.
                $( '#woocommerce_ziticity_type' ).change();
                $( document.body ).on( 'wc_backbone_modal_loaded', function( evt, target ) {
                    if ( 'wc-modal-shipping-method-settings' === target ) {
                        wcZiticityShowHideRatesField( $( '#wc-backbone-modal-dialog #woocommerce_ziticity_type', evt.currentTarget ) );
                    }
                } );
                
                function wcZiticityChangeInputFieldsValues( el ) {
                    var titleField = $( '#woocommerce_ziticity_title' );
                    var weightField = $( '#woocommerce_ziticity_hide_if_weight' );

                    {$title_ifs}
                    {$weight_ifs}
                }
                
                $( document.body ).on( 'change', '#woocommerce_ziticity_service_type', function() {
                    wcZiticityChangeInputFieldsValues( this );
                });
            });"
        );
    }

    private function parcel_locker_service_type() {
        $settings = get_option( 'woocommerce_ziticity_settings', [] );
        $parcel_locker = array_filter($settings['service_types'], function ($type) {
            return $type->label == 'parcel_locker';
        });

        return reset($parcel_locker);
    }
}
