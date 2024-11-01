<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://ziticity.com
 * @since      1.0.0
 *
 * @package    Woocommerce_Shipping_Ziticity
 * @subpackage Woocommerce_Shipping_Ziticity/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Woocommerce_Shipping_Ziticity
 * @subpackage Woocommerce_Shipping_Ziticity/public
 * @author     ZITICITY <info@ziticity.com>
 */
class WC_Shipping_Ziticity_Public {

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
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/wc-shipping-ziticity-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/wc-shipping-ziticity-public.js', array( 'jquery' ), $this->version, false );

	}

    public function locate_template( $template, $template_name, $template_path ) {
        // Tmp holder
        $_template = $template;

        if ( ! $template_path ) {
            $template_path = WC_TEMPLATE_PATH;
        }

        // Set our base path
        $plugin_path = untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/woocommerce/';

        // Look within passed path within the theme - this is priority
        $template = locate_template(
            array(
                trailingslashit( $template_path ) . $template_name,
                $template_name,
            )
        );

        // Get the template from this plugin, if it exists
        if ( ! $template && file_exists( $plugin_path . $template_name ) ) {
            $template = $plugin_path . $template_name;
        }

        // Use default template
        if ( ! $template ) {
            $template = $_template;
        }

        // Return what we found
        return $template;
    }

    /*
     * Disable COD payment if ZITICITY is disabled COD for client.
     */
    public function available_payment_gateways( $available_gateways ) {
        if ( isset( $available_gateways['cod'] ) ) {
            if ( WC()->session ) {
                $chosen_shipping_methods = WC()->session->get('chosen_shipping_methods');

                if ( ! empty( $chosen_shipping_methods ) && substr( $chosen_shipping_methods[0], 0, strlen( 'ziticity' ) ) === 'ziticity' ) {
                    WC()->session->set('shipping_for_package_0', '');

                    $available_shipping_methods = WC()->shipping->get_shipping_methods();
                    $method_id = explode(':', $chosen_shipping_methods[0]);

                    if ( isset( $available_shipping_methods[ $method_id[1] ] ) ) {
                        $method = $available_shipping_methods[ $method_id[1] ];
                        $cod_enabled = $method->cod_enabled ? 'yes' : 'no';

                        // Disable if current service type does not allow COD
                        foreach ( $method->service_types as $service_type ) {
                            if ( $service_type->id == $method->service_type ) {
                                $cod_enabled = $service_type->cod_available ? $cod_enabled : 'no';
                                break;
                            }
                        }
                    } else {
                        $settings = get_option( 'woocommerce_ziticity_settings', [] );
                        $cod_enabled = isset( $settings['cod_enabled'] ) ? $settings['cod_enabled'] : 'no';
                    }

                    if ( $cod_enabled == 'no' ) {
                        unset( $available_gateways['cod'] );
                    }
                }
            } else {
                unset( $available_gateways['cod'] );
            }
        }

        return $available_gateways;
    }

    /*
     * Do not allow to continue if no parcel locker was selected.
     */
    public function check_parcel_locker() {
        if ( isset( $_POST[ 'wc_shipping_ziticity_parcel_locker' ] ) ) {
            $locker = sanitize_text_field( $_POST[ 'wc_shipping_ziticity_parcel_locker' ] );

            if ( empty( $locker ) || $locker == '-1' ) {
                wc_add_notice( __( 'Please select your preferred parcel locker.', 'ziticity-shipping-for-woocommerce' ), 'error' );
            }
        }
    }
}
