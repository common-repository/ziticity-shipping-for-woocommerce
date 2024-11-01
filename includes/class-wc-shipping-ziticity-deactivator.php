<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://ziticity.com
 * @since      1.0.0
 *
 * @package    Woocommerce_Shipping_Ziticity
 * @subpackage Woocommerce_Shipping_Ziticity/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Woocommerce_Shipping_Ziticity
 * @subpackage Woocommerce_Shipping_Ziticity/includes
 * @author     ZITICITY <info@ziticity.com>
 */
class WC_Shipping_Ziticity_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
        self::delete_tables();
        self::delete_cron_jobs();
	}

    private static function delete_tables() {
        global $wpdb;

        $wpdb->hide_errors();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $tables = array(
            "{$wpdb->prefix}ziticity_parcel_lockers",
        );

        foreach ( $tables as $table ) {
            $wpdb->query( "DROP TABLE IF EXISTS {$table}" );
        }
    }

    private static function delete_cron_jobs() {
        wp_clear_scheduled_hook( 'ziticity_parcel_lockers_updater' );
    }
}
