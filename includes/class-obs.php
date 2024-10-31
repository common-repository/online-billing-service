<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       online-billing-service.com
 * @since      1.0.0
 *
 * @package    OBS
 * @subpackage OBS/includes
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
 * @package    OBS
 * @subpackage OBS/includes
 * @author     OBS <office@online-billing-service.com>
 */
class OBS {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      OBS_Loader    $loader    Maintains and registers all hooks for the plugin.
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
		if ( defined( 'FAC_WOO_OBS_VERSION' ) ) {
			$this->version = FAC_WOO_OBS_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'online billing service-woocommerce';
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
	 * - OBS_Loader. Orchestrates the hooks of the plugin.
	 * - OBS_i18n. Defines internationalization functionality.
	 * - OBS_Admin. Defines all hooks for the admin area.
	 * - OBS_Public. Defines all hooks for the public side of the site.
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
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-obs-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-obs-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-obs-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-obs-public.php';
		$this->loader = new OBS_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the OBS_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {
		$plugin_i18n = new OBS_i18n();
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
		include_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-obs-admin-authentication.php';
		include_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-obs-admin-query.php';

		$plugin_admin = new OBS_Admin( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'display_admin_pages' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		if ( !OBS_Admin_Query::obs_wc_stripe_integration_workflow() ) {
			$this->loader->add_action( 'init', $plugin_admin, 'start_session_if_not_started', 1, 1 );
			$this->loader->add_action( 'woocommerce_order_status_completed', $plugin_admin, 'auto_generate_invoice', 10, 1 );

			if(OBS_Admin_Authentication::global_api_key()) {
				$this->loader->add_filter( 'woocommerce_admin_order_actions', $plugin_admin, 'generate_invoice_button', 10, 2 );
				$this->loader->add_filter( 'woocommerce_admin_order_actions', $plugin_admin, 'generate_proforma_invoice_button', 10, 2 );
				$this->loader->add_filter( 'woocommerce_admin_order_actions', $plugin_admin, 'send_document_button', 10, 2 );
			}
			$this->loader->add_action( 'wp_ajax_prepare_params_for_order', $plugin_admin, 'prepare_params_for_order' );
			$this->loader->add_action( 'wp_ajax_get_order_params', $plugin_admin, 'get_order_params' );

			// Invoicing batch actions
			$this->loader->add_filter( 'bulk_actions-edit-shop_order', $plugin_admin, 'add_batch_generate_invoices', 20, 1 );
			$this->loader->add_filter( 'handle_bulk_actions-edit-shop_order', $plugin_admin, 'get_batch_orders_ids_invoices', 10, 3 );

			$this->loader->add_filter( 'bulk_actions-edit-shop_order', $plugin_admin, 'add_batch_generate_proforma_invoices', 20, 1 );
			$this->loader->add_filter( 'handle_bulk_actions-edit-shop_order', $plugin_admin, 'get_batch_orders_ids_proforma_invoices', 10, 3 );
		} else {
			$this->loader->add_filter( 'wc_stripe_payment_metadata', $plugin_admin, 'obs_filter_wc_stripe_payment_metadata', 10, 3 );
		}
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {
		$plugin_public = new OBS_Public( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
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
	 * @return    OBS_Loader    Orchestrates the hooks of the plugin.
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
}

?>
