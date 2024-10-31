<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       online-billing-service.com
 * @since      1.4.9
 *
 * @package    OBS
 * @subpackage OBS/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    OBS
 * @subpackage OBS/admin
 * @author     OBS <office@online-billing-service.com>
 */

class OBS_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.4.9
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.4.9
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.4.9
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
	 * @since    1.4.9
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in OBS_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The OBS_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/obs-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.4.9
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in OBS_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The OBS_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

    if (session_id() || session_status() === PHP_SESSION_ACTIVE) {
			session_write_close();
		}
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/obs-admin.js', array( 'jquery' ), $this->version, false );
	}

	public function display_admin_pages() {
		include_once 'class-obs-admin-query.php';
		if(!OBS_Admin_Query::obs_wc_stripe_integration_workflow()) {
			add_menu_page(
				'online-billing-service.com API', // page title
				'online-billing-service.com', // menu title
				'manage_woocommerce', // capability
				'obs-admin', // menu slug
				array($this, 'showPage'), // function
				'', // icon-url
				98 // position number on menu from top
			);

			add_submenu_page(
				'obs-admin',
				'Settings',
				'Settings',
				'manage_options',
				'obs-admin-settings',
				array($this, 'showSettingsPage')
			);
		} else {
			add_menu_page(
				'online-billing-service.com API', // page title
				'online-billing-service.com', // menu title
				'manage_woocommerce', // capability
				'obs-admin', // menu slug
				array($this, 'showSettingsPage'), // function
				'', // icon-url
				98 // position number on menu from top
			);

			add_submenu_page(
				'obs-admin',
				'Settings',
				'Settings',
				'manage_options',
				'obs-admin-settings',
				array($this, 'showSettingsPage')
			);
		}
	}

	public function showPage() {
		include_once 'class-obs-admin-query.php';
		if(!OBS_Admin_Query::obs_wc_stripe_integration_workflow()) {
			include_once 'partials/obs-admin-display.php';
		}
	}

	public function showSettingsPage() {
		include_once 'partials/obs-admin-display-settings.php';
	}

	public function get_exchange_rate_for_order($order_id) {
		include_once 'class-obs-admin-query.php';
		include_once 'class-obs-admin-authentication.php';

		$order = wc_get_order( $order_id );
		$order_date = $order->get_date_created()->format ('Y-m-d');

		global $wpdb;
		$obs = $wpdb->prefix . "wc_obs_exchange_rates";

		$order_currency = $order->get_currency();
		$query_string = '{ account { domesticCurrency } }';
		$domestic_currency_response = OBS_Admin_Query::query(OBS_Admin_Authentication::global_api_key(), $query_string);
		if(!empty($domestic_currency_response) || !is_wp_error($domestic_currency_response)) {
			$domestic_currency_json = json_decode($domestic_currency_response['body'], true);
			$domestic_currency = $domestic_currency_json['data']['account'][0]['domesticCurrency'];
			$results = $wpdb->get_results( "SELECT * FROM " . $obs . " WHERE order_date ='" . $order_date . "'" . " AND currencies ='" . $order_currency . '/' . $domestic_currency . "'", OBJECT );

			if(!empty($results)) {
				return $results[0]->exchange_rate;
			}
		}
	}

	public function document_present_for($order_id, $invoice_type = null) {
		include_once 'class-obs-admin-query.php';
		include_once 'class-obs-admin-authentication.php';

		global $wpdb;
		$obs = $wpdb->prefix . "wc_obs_invoicing";
		$results = $wpdb->get_results( "SELECT * FROM " . $obs . " WHERE invoice_type=" . "'" . $invoice_type . "'" . " AND order_id ='" . $order_id . "'"  . " ORDER BY id DESC", OBJECT );

		if(!empty($results)) {
			if(!empty($results[0]->order_id)) {
				if($results[0]->order_id == $order_id) {
					return true;
				}
			}
		}
		return false;
	}

	public function get_exchange_rate($order_id) {
		$order = wc_get_order( $order_id );
		$order_date = $order->get_date_created()->format ('Y-m-d');
		$order_currency = $order->get_currency();
		$query_string = '{ account { domesticCurrency } }';
		$domestic_currency_response = OBS_Admin_Query::query(OBS_Admin_Authentication::global_api_key(), $query_string);
		if(!empty($domestic_currency_response) || !is_wp_error($domestic_currency_response)) {
			try {
				if(!is_wp_error($domestic_currency_response) && isset($domestic_currency_response['body'])) {
					$domestic_currency_json = json_decode($domestic_currency_response['body'], true);
					$domestic_currency = $domestic_currency_json['data']['account'][0]['domesticCurrency'];
				} else {
					throw new Exception('$domestic_currency_json is WP_ERROR or is not set!');
				}
			} catch (Exception $e) {
				_e('Caught exception: ',  $e->getMessage(), "\n");
				_e("<br>");
				// _e("domestic_currency_response: " . $domestic_currency_response);
				_e("<br>");
				_e("key: " . OBS_Admin_Authentication::global_api_key());
				_e("<br>");
				_e("query: " . $query_string);
			}

			if(!empty($order_currency) && !empty($domestic_currency)) {
				$currencies = $order_currency . '/' . $domestic_currency;

				global $wpdb;
				$obs = $wpdb->prefix . "wc_obs_settings";
				$results = $wpdb->get_results( "SELECT * FROM " . $obs . " ORDER BY id DESC LIMIT 1", OBJECT );

				if(!empty($results[0])) {
					$multiple_currencies = $results[0]->multiple_currencies;
				} else {
					$multiple_currencies = null;
				}
			} else {
				$currencies = $order_currency . '/';
				$multiple_currencies = null;
			}

			return [$order_date, $currencies, $multiple_currencies];
		}
	}

	public function generate_invoice_button( $actions, $order ) {
		include_once 'class-obs-admin-query.php';
		include_once 'class-obs-admin-authentication.php';
		include_once 'models/class-obs-invoice.php';

		$order_id = method_exists( $order, 'get_id' ) ? $order->get_id() : $order->id;
		$invoiced_orders = self::document_present_for($order_id, 'invoice');
		$title = $invoiced_orders ? "Re Invoice" : "Invoice";
		$url =  'admin-ajax.php?action=prepare_params_for_order&order_id=' . $order_id;
		$action = "generate_invoice_obs";
		$mouse_hover_text = $invoiced_orders ? 'Regenerate Invoice' : 'Generate Invoice'; ;

		if(!empty($url) && !empty($title) && !empty($action)) {
			$actions['invoicing'] = array(
				'url'       => wp_nonce_url( admin_url( $url )),
				'title'     => $title,
				'name'      => __( $mouse_hover_text, 'woocommerce' ),
				'action'    => $action, // keep "view" class for a clean button CSS
			);
		} else {
			$actions['invoicing'] = array();
		}
		return $actions;
	}

  public function send_document_button ( $actions, $order ) {
		include_once 'class-obs-admin-query.php';
		include_once 'class-obs-admin-authentication.php';
		include_once 'models/class-obs-invoice.php';

		$order_id = method_exists( $order, 'get_id' ) ? $order->get_id() : $order->id;
		$invoiced_orders = self::document_present_for($order_id, 'invoice');
		$proforma_invoiced_orders = self::document_present_for($order_id, 'proformaInvoice');
		$document_present = $invoiced_orders || $proforma_invoiced_orders;
		$title = $document_present ? "Send" : NULL;
		$url =  'admin-ajax.php?action=prepare_params_for_order&order_id=' . $order_id . '&send_document=true';
		$action = "send_document_obs";

		if(!empty($url) && !empty($title) && !empty($action)) {
			$actions['send_document'] = array(
				'url'       => wp_nonce_url( admin_url( $url )),
				'title'     => $title,
				'name'      => __( 'Send Document through e-mail', 'woocommerce' ),
				'action'    => $action, // keep "view" class for a clean button CSS
			);
		} else {
			$actions['send_document'] = array();
		}
		return $actions;
  }

	public function generate_proforma_invoice_button( $actions, $order ) {
		include_once 'class-obs-admin-query.php';
		include_once 'class-obs-admin-authentication.php';
		include_once 'models/class-obs-invoice.php';

		$order_id = method_exists( $order, 'get_id' ) ? $order->get_id() : $order->id;
		$invoiced_orders = self::document_present_for($order_id, 'proformaInvoice');
		$title = $invoiced_orders ? "Re Proforma" : "Proforma";
		$url =  'admin-ajax.php?action=prepare_params_for_order&order_id=' . $order_id . '&proforma=true';
		$action = "generate_invoice_obs";
		$mouse_hover_text = $invoiced_orders ? 'Regenerate Proforma Invoice' : 'Generate Proforma Invoice';

		if(!empty($url) && !empty($title) && !empty($action)) {
			$actions['proforma_invoicing'] = array(
				'url'       => wp_nonce_url( admin_url( $url )),
				'title'     => $title,
				'name'      => __( $mouse_hover_text, 'woocommerce' ),
				'action'    => $action, // keep "view" class for a clean button CSS
			);
		} else {
			$actions['proforma_invoicing'] = array();
		}
		return $actions;
	}

	function auto_generate_invoice($order_id) {
		include_once 'class-obs-admin-query.php';
		include_once 'models/class-obs-invoice.php';

		global $wpdb;
	    $obs = $wpdb->prefix . "wc_obs_settings";
	    $results = $wpdb->get_results( "SELECT * FROM " . $obs . " ORDER BY id DESC LIMIT 1", OBJECT );

	    if(!empty($results[0])) {
		    $auto_invoicing = $results[0]->auto_invoicing;

		    if($auto_invoicing){
				$_SESSION['order_id'] = $order_id;
				OBS_Invoice::generate_one_invoice();
				session_start();
				unset($_SESSION['order_id']);
				session_write_close();
			}
		}
	}

  function start_session_if_not_started() {
    if(!session_id() && session_status() !== PHP_SESSION_ACTIVE) {
      session_start();
    }
  }

	public function prepare_params_for_order() {
		//  find the domestic currency
		// depending on the currency make a call to
    if (session_id() || session_status() === PHP_SESSION_ACTIVE) {
      session_write_close();
	  }
		include_once 'class-obs-admin-query.php';
		$get_exchange_rate = self::get_exchange_rate_for_order($_GET['order_id']);
		if(isset($_GET['send_document']) && !empty($_GET['send_document'])) {
			$get_send_document = $_GET['send_document'];
		} else {
			$get_send_document = 'false';
		}

		if(!empty($get_exchange_rate)) {
			$url = 'admin-ajax.php?action=get_order_params&order_id=' . $_GET['order_id'] . '&order_date=' . $get_exchange_rate[0] . '&currencies=' . $get_exchange_rate[1] . '&exchange_rate=' . $get_exchange_rate[2] . '&send_document=' . $get_send_document;
		} else {
			$url = 'admin-ajax.php?action=get_order_params&order_id=' . $_GET['order_id'] . '&send_document=' . $get_send_document;
		}

		if(!empty($_GET['proforma'])) {
			$url = $url . '&proforma=' . $_GET['proforma'];
		}

		$final_url = admin_url( $url );
		_e("<script> window.location=\"$final_url\"; </script>");
    session_start();
	}

	public function get_order_params() {
		$_SESSION['order_id'] = sanitize_text_field($_GET['order_id']);
		$_SESSION['exchange_rate'] = sanitize_text_field($_GET['exchange_rate']);
		$_SESSION['proforma'] = sanitize_text_field($_GET['proforma']);
		$_SESSION['send_document'] = sanitize_text_field($_GET['send_document']);

		include_once 'partials/obs-admin-redirect-to-obs.php';
	}

	public function add_batch_generate_invoices( $actions ) {
		if(!empty($actions)) {
			$actions['invoice_order_id'] = __( 'Generate Invoices', 'woocommerce' );
		}
	    return $actions;
	}

	public function add_batch_generate_proforma_invoices( $actions ) {
		$actions['proforma_order_id'] = __( 'Generate Proforma Invoices', 'woocommerce' );
	    return $actions;
	}

	public function get_batch_orders_ids_invoices( $redirect_to, $action, $post_ids ) {
		global $wpdb;
	    $obs = $wpdb->prefix . "wc_obs_settings";
	    $results = $wpdb->get_results( "SELECT * FROM " . $obs . " ORDER BY id DESC LIMIT 1", OBJECT );
		$redirect_to = admin_url( 'admin.php?page=obs-admin' ); // redirect_to obs page

		if ( $action !== 'invoice_order_id' )
        	return $redirect_to; // Exit

        foreach ( $post_ids as $post_id ) {
	        $order = wc_get_order( $post_id );
	        $order_data = $order->get_data();

	        $processed_ids[] = $order->get_id();
	    }

		if(empty($_SESSION['orders_ids'])) {
			$_SESSION['orders_ids'] = $processed_ids;
		} else {
			array_push($_SESSION['orders_ids'], $processed_ids);
		}

        return $redirect_to = add_query_arg( array(
	        'batch_orders' => '1',
	        'processed_count' => count( $processed_ids ),
	        'processed_ids' => implode( ',', $processed_ids ),
	    ), $redirect_to );
    }

	public function get_batch_orders_ids_proforma_invoices( $redirect_to, $action, $post_ids ) {
		global $wpdb;
	    $obs = $wpdb->prefix . "wc_obs_settings";
	    $results = $wpdb->get_results( "SELECT * FROM " . $obs . " ORDER BY id DESC LIMIT 1", OBJECT );
		$redirect_to = admin_url( 'admin.php?page=obs-admin' ); // redirect_to obs page

		if ( $action !== 'proforma_order_id' )
        	return $redirect_to; // Exit

        foreach ( $post_ids as $post_id ) {
	        $order = wc_get_order( $post_id );
	        $order_data = $order->get_data();

	        $processed_ids[] = $order->get_id();
	    }

		if(empty($_SESSION['orders_ids'])) {
			$_SESSION['orders_ids'] = $processed_ids;
		} else {
			array_push($_SESSION['orders_ids'], $processed_ids);
		}

		$_SESSION['proforma'] = true;

        return $redirect_to = add_query_arg( array(
	        'batch_orders' => '1',
	        'processed_count' => count( $processed_ids ),
	        'processed_ids' => implode( ',', $processed_ids ),
	    ), $redirect_to );
    }

	public function obs_filter_wc_stripe_payment_metadata( $metadata, $order, $source ) {
		include_once 'class-obs-admin-query.php';

		if ( !in_array( 'woocommerce-gateway-stripe/woocommerce-gateway-stripe.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), $strict = true) ) {
			return;
		}

		if ( !OBS_Admin_Query::obs_wc_stripe_integration_workflow() ) {
			return;
		}

		$order_data = $order->get_data();
		global $wpdb;
		$obs = $wpdb->prefix . "wc_obs_settings";
		$results = $wpdb->get_results( "SELECT * FROM " . $obs . " ORDER BY id DESC LIMIT 1", OBJECT );

		if(!empty($results[0])) {
			$stripe_shipping_tax_value = $results[0]->stripe_shipping_tax_value ?? '';
			$stripe_shipping_tax_value_includes_vat = $results[0]->stripe_shipping_tax_value_includes_vat ?? '';
			$stripe_document_position_unit_name = $results[0]->stripe_document_position_unit_name ?? '';
			$stripe_shipping_description = $results[0]->stripe_shipping_description ?? '';

			if(empty($stripe_shipping_tax_value)) {
				$metadata['shipping_tax_value'] = $stripe_shipping_tax_value;
			} else {
				$metadata['shipping_tax_value'] = round(number_format($stripe_shipping_tax_value, 2) * 100);
			}

			$metadata['shipping_tax_value_includes_vat'] = $stripe_shipping_tax_value_includes_vat;
			$metadata['quantity_name'] = $stripe_document_position_unit_name;
			$metadata['shipping_description'] = $stripe_shipping_description;
		}

		// get products and uid and put them in medata...
		$metadata[ __( 'stripe_woocommerce_company', 'woocommerce-gateway-stripe' ) ] = sanitize_text_field( $order_data['billing']['company'] );

		$count = 1;
		foreach( $order->get_items() as $item_id => $line_item ){
			$item_data = $line_item->get_data();
			$product = $line_item->get_product();
			$product_name = $product->get_name();
			$formatted_product_name = htmlspecialchars($product_name);
			$item_quantity = $line_item->get_quantity();
			$item_total = round(number_format($line_item->get_total(), 2) * 100);
			$formatted_item_total = round(number_format($line_item->get_total(), 2) * 100);
			$metadata['line_item_' . $count] = "{\"product_name\": \"$formatted_product_name\",  \"quantity\": \"$item_quantity\", \"item_total\": \"$formatted_item_total\"}";
			$count += 1;
		}

		if ( in_array( 'facturare-persoana-fizica-sau-juridica/facturare.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), $strict = true) ) {
			$client_facturare_details = OBS_Client::get_client_facturare_details($order->id);
			if(!empty($client_facturare_details)) {
				$order_billing_company_uid = !empty($client_facturare_details['cui']) ? $client_facturare_details['cui'] : '';
				$order_billing_company_reg_id = !empty($client_facturare_details['nr_reg_com']) ? $client_facturare_details['nr_reg_com'] : '';
				$order_billing_company_cnp = !empty($client_facturare_details['cnp']) ? $client_facturare_details['cnp'] : '';

				if(!empty($order_billing_company_cnp)) {
					$order_billing_company_uid_as_cnp = $order_billing_company_cnp;
				} else if(!empty($order_billing_company_uid)) {
					$order_billing_company_uid_as_cnp = $order_billing_company_uid;
				} else {
					$order_billing_company_uid_as_cnp = '';
				}

				if($order_billing_company_reg_id == '' || $order_billing_company_reg_id == NULL) {
					$order_billing_company_reg_id = '-';
				}

				$metadata['company_uid'] = $order_billing_company_uid_as_cnp;
				$metadata['registration_id'] = $order_billing_company_reg_id;
			}
		}
		return $metadata;
	}
}

?>
