<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://ziticity.com
 * @since             1.0.0
 * @package           Woocommerce_Shipping_Ziticity
 *
 * @wordpress-plugin
 * Plugin Name:       ZITICITY Shipping for WooCommerce
 * Description:       WooCommerce ZITICITY Shipping allows a store to integrate shipping method with ZITICITY API.
 * Version:           1.1.0
 * Author:            ZITICITY
 * Author URI:        https://ziticity.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       ziticity-shipping-for-woocommerce
 * Domain Path:       /languages
 * Requires at least: 4.4
 * Tested up to: 5.7
 * WC requires at least: 3.0
 * WC tested up to: 5.3
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'WC_SHIPPING_ZITICITY_VERSION', '1.1.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wc-shipping-ziticity-activator.php
 */
function activate_wc_shipping_ziticity() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wc-shipping-ziticity-activator.php';
    WC_Shipping_Ziticity_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wc-shipping-ziticity-deactivator.php
 */
function deactivate_wc_shipping_ziticity() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wc-shipping-ziticity-deactivator.php';
    WC_Shipping_Ziticity_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_wc_shipping_ziticity' );
register_deactivation_hook( __FILE__, 'deactivate_wc_shipping_ziticity' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-wc-shipping-ziticity-core.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_wc_shipping_ziticity() {

	$plugin = new WC_Shipping_Ziticity_Core();
	$plugin->run();

}
run_wc_shipping_ziticity();
