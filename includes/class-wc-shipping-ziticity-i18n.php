<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://ziticity.com
 * @since      1.0.0
 *
 * @package    Woocommerce_Shipping_Ziticity
 * @subpackage Woocommerce_Shipping_Ziticity/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Woocommerce_Shipping_Ziticity
 * @subpackage Woocommerce_Shipping_Ziticity/includes
 * @author     ZITICITY <info@ziticity.com>
 */
class WC_Shipping_Ziticity_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'ziticity-shipping-for-woocommerce',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
