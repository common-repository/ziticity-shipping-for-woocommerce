<?php

/**
 * Fired during plugin activation
 *
 * @link       https://ziticity.com
 * @since      1.0.0
 *
 * @package    Woocommerce_Shipping_Ziticity
 * @subpackage Woocommerce_Shipping_Ziticity/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Woocommerce_Shipping_Ziticity
 * @subpackage Woocommerce_Shipping_Ziticity/includes
 * @author     ZITICITY <info@ziticity.com>
 */
class WC_Shipping_Ziticity_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
        self::update_tables();
        self::create_cron_jobs();
	}

    public static function update() {
        self::update_tables();
    }

    private static function update_tables() {
        global $wpdb;

        $wpdb->hide_errors();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $installed_version = get_option( 'wc_shipping_ziticity_db_version' );

        if ( ! defined( 'IFRAME_REQUEST' ) && WC_SHIPPING_ZITICITY_VERSION != $installed_version ) {
            $collate = '';

            if ($wpdb->has_cap('collation')) {
                $collate = $wpdb->get_charset_collate();
            }

            if ( version_compare( $installed_version, '1.1.0', '<' ) ) {
                $tables[] = "
CREATE TABLE IF NOT EXISTS {$wpdb->prefix}ziticity_parcel_lockers (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  parcel_locker_id INT NOT NULL,
  name varchar(50) NOT NULL DEFAULT '',
  label text NOT NULL,
  notes text NULL,
  country varchar(2) NOT NULL DEFAULT '',
  city varchar(40) NOT NULL DEFAULT '',
  post_code varchar(5) NOT NULL DEFAULT '',
  street varchar(100) NOT NULL DEFAULT '',
  longitude float NULL,
  latitude float NULL,
  available_box_sizes text NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY parcel_locker_id (parcel_locker_id)
) $collate;
		";

                foreach ($tables as $table) {
                    dbDelta($table);
                }
            }
        }

        delete_option( 'wc_shipping_ziticity_db_version' );
        add_option( 'wc_shipping_ziticity_db_version', WC_SHIPPING_ZITICITY_VERSION );
    }

    private static function create_cron_jobs() {
        wp_clear_scheduled_hook( 'ziticity_parcel_lockers_updater' );
        wp_schedule_event( time(), 'daily', 'ziticity_parcel_lockers_updater' );
    }
}
