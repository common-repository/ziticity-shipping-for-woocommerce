<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://ziticity.com
 * @since      1.0.0
 *
 * @package    Woocommerce_Shipping_Ziticity
 * @subpackage Woocommerce_Shipping_Ziticity/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Woocommerce_Shipping_Ziticity
 * @subpackage Woocommerce_Shipping_Ziticity/includes
 * @author     ZITICITY <info@ziticity.com>
 */
class WC_Shipping_Ziticity_Core {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      WC_Shipping_Ziticity_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'WC_SHIPPING_ZITICITY_VERSION' ) ) {
			$this->version = WC_SHIPPING_ZITICITY_VERSION;
		} else {
			$this->version = '1.1.0';
		}
		$this->plugin_name = 'woocommerce-shipping-ziticity';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - WC_Shipping_Ziticity_Loader. Orchestrates the hooks of the plugin.
	 * - WC_Shipping_Ziticity_i18n. Defines internationalization functionality.
	 * - WC_Shipping_Ziticity_Admin. Defines all hooks for the admin area.
	 * - WC_Shipping_Ziticity_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wc-shipping-ziticity-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wc-shipping-ziticity-i18n.php';

        /**
         * Helpers.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/helpers.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wc-shipping-ziticity-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-wc-shipping-ziticity-public.php';

        /**
         * Plugin AJAX methods.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wc-shipping-ziticity-ajax.php';

		$this->loader = new WC_Shipping_Ziticity_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the WC_Shipping_Ziticity_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new WC_Shipping_Ziticity_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new WC_Shipping_Ziticity_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

//        $this->loader->add_action( 'woocommerce_shipping_init', $this, 'includes' );
//        $this->loader->add_filter( 'woocommerce_shipping_methods', $this, 'add_methods' );

        $this->loader->add_action( 'ziticity_parcel_lockers_updater', $plugin_admin, 'get_parcel_lockers_list' );

        // Custom order actions
        $this->loader->add_action( 'add_meta_boxes', $plugin_admin, 'add_meta_box', 10, 1 );
        $this->loader->add_action( 'woocommerce_process_shop_order_meta', $plugin_admin, 'save_meta_box', 0, 2 );

        // Request shipping label
        $this->loader->add_action( 'wp_ajax_ziticity_get_labels', $plugin_admin, 'get_shipping_labels' );
        $this->loader->add_action( 'wp_ajax_ziticity_cancel_order', $plugin_admin, 'cancel_order' );

        // Bulk order actions
        $this->loader->add_filter( 'bulk_actions-edit-shop_order', $plugin_admin, 'define_orders_bulk_actions', 10 );
        $this->loader->add_filter( 'handle_bulk_actions-edit-shop_order', $plugin_admin, 'handle_orders_bulk_actions', 10, 3 );
        $this->loader->add_filter( 'admin_notices', $plugin_admin, 'bulk_admin_notices' );

        $this->loader->add_action( 'woocommerce_email', $this, 'load_shipping_method', 1, 1 );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new WC_Shipping_Ziticity_Public( $this->get_plugin_name(), $this->get_version() );
		$plugin_ajax = new WC_Shipping_Ziticity_Ajax();

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

        $this->loader->add_filter( 'woocommerce_locate_template', $plugin_public, 'locate_template', 20, 3 );
        $this->loader->add_filter( 'woocommerce_locate_core_template', $plugin_public, 'locate_template', 20, 3 );

        $this->loader->add_action( 'woocommerce_shipping_init', $this, 'includes' );
        $this->loader->add_filter( 'woocommerce_shipping_methods', $this, 'add_methods' );

        $this->loader->add_action( 'woocommerce_email', $this, 'load_shipping_method', 1, 1 );

        $this->loader->add_action( 'woocommerce_checkout_update_order_review', $plugin_ajax, 'checkout_save_session_fields', 10, 1 );

        // Available payment methods
        $this->loader->add_filter( 'woocommerce_available_payment_gateways', $plugin_public, 'available_payment_gateways', 10, 1 );
        $this->loader->add_filter( 'woocommerce_checkout_process', $plugin_public, 'check_parcel_locker', 10, 0 );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    WC_Shipping_Ziticity_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

    /**
     * Include needed files.
     */
    public function includes() {
        include_once __DIR__ . '/class-wc-shipping-ziticity.php';

        // @todo change to static method
        $ziticity = new WC_Shipping_Ziticity();
        $ziticity->init_actions_and_filters();
    }

    /**
     * Add ZITICITY shipping methods.
     *
     * @since 1.0.0
     * @param array $methods Shipping methods.
     * @return array Shipping methods.
     */
    public function add_methods( $methods ) {
        $methods['ziticity'] = 'WC_Shipping_Ziticity';

        return $methods;
    }

    public function load_shipping_method( $order_id ) {
        WC()->shipping();
    }

}
