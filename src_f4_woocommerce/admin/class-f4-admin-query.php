<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       factureaza.ro
 * @since      1.0.6
 *
 * @package    F4
 * @subpackage F4/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    F4
 * @subpackage F4/admin
 * @author     F4 <office@factureaza.ro>
 */

include_once 'class-f4-admin-authentication.php';

define('FAC_WOO_F4_URL', 'https://factureaza.ro');
define('FAC_WOO_F4_SHARED_DOCUMENT_TLD', 'vizualizare');
define('FAC_WOO_F4_ERRORS_LOG', plugin_dir_path( __FILE__ ) . 'fac-woo-f4-queries.log');
define('FAC_WOO_F4_SIMILAR_CLIENT_PERCENTAGE_ACCEPTED', 83.5);

class F4_Admin_Query {

	public static function account_informations() {
		$query_string = '{ account { id name companyAddress1 companyCity companyFax companyUid companyName companyState totalOpen } }';

		$account_informations_response = self::query(F4_Admin_Authentication::global_api_key(), $query_string);
        
        if(is_wp_error($account_informations_response)) {
			print_r($account_informations_response);
		}
		
        $account_data_present = false;
		if(!empty($account_informations_response)) {
		  $account_informations_json = json_decode($account_informations_response['body'], true);
		  $account_data_present = (!empty($account_informations_json['data']['account'][0]['id']) || !empty($account_informations_json['data']['account'][0]['name']));
		}

		$api_key = F4_Admin_Authentication::global_api_key();
		$last_six = substr($api_key, -6);
		$display_api_key = str_repeat('*', 20 + rand(0, 5)) . $last_six;

		if($account_data_present) {
			echo "<h1> You are now connected to factureaza.ro </h1>";
			echo "<p class=\"api-key-text\">Current API_KEY: " . $display_api_key . "</p>";			
			echo "<p class=\"account-text\">Current account: " . "<b>" . $account_informations_json['data']['account'][0]['name'] . '</b> (' . $account_informations_json['data']['account'][0]['companyName'] . ") </p>";
		} else {
			echo "<p>Something went wrong.</p>";

			global $wpdb;
			$f4 = $wpdb->prefix . "wc_f4_api_key";
			$results = $wpdb->get_results( "DELETE FROM " . $f4 . " ORDER BY id DESC LIMIT 1", OBJECT );

			if(!empty($account_informations_response)) {
				print_r(esc_html($account_informations_response['body']));
			}
		}		
	}

	public static function get_invoice_series() {
		global $wpdb;
	    $f4 = $wpdb->prefix . "wc_f4_settings";
	    $results = $wpdb->get_results( "SELECT * FROM " . $f4 . " ORDER BY id DESC LIMIT 1", OBJECT );
	    
	    if(!empty($results[0])) {
		    $custom_series_prefix = $results[0]->custom_series_prefix;
		    $custom_series_suffix = $results[0]->custom_series_suffix;

			global $wpdb;
			$f4 = $wpdb->prefix . "wc_f4_settings";
			$results = $wpdb->get_results( "SELECT * FROM " . $f4 . " ORDER BY id DESC LIMIT 1", OBJECT );
			$proforma_invoices = $results[0]->proforma_invoices;

			if($proforma_invoices) {
				$invoice_or_proforma = "proformaInvoiceSeries";
			} else {
				$invoice_or_proforma = "invoiceSeries";
			}

			$query_string = '{ ' . $invoice_or_proforma . ' { id prefix suffix year counterCurrent } }';
			$invoice_series_response = self::query(F4_Admin_Authentication::global_api_key(), $query_string);
			$invoice_series_json = json_decode($invoice_series_response['body'], true);
			$invoice_series = $invoice_series_json['data'][$invoice_or_proforma];

			$current_year = date('Y');

			if(!empty($invoice_series)) {
				foreach ($invoice_series as $is) {

					if ($is['prefix'] === $custom_series_prefix && $is['suffix'] === $custom_series_suffix && $is['year'] === $current_year) {
						return $is['id'] . ',' . $is['counterCurrent'];
					} // else { echo "ERROR"; }
				}
			}
		}
	}

	public static function get_user_id() {
		$query_string = '{ users { id } }';

		$users_response = self::query(F4_Admin_Authentication::global_api_key(), $query_string);
		$users_json = json_decode($users_response['body'], true);
		$user = $users_json['data']['users'][0]['id'];
		
		if(!empty($user)) {
			return $user;
		}
	}

	public static function billing_order_name($order_id) {
		$order = wc_get_order( $order_id );
		$order_data = $order->get_data();

		global $wpdb;
	    $f4 = $wpdb->prefix . "wc_f4_settings";
	    $results = $wpdb->get_results( "SELECT * FROM " . $f4 . " ORDER BY id DESC LIMIT 1", OBJECT );
	    
	    if(!empty($results[0]) && !empty($order_data['billing']['company'])) {
		    $company_label_name = $results[0]->company_label_name;
			if($company_label_name == 'name_first') {
				$order_billing_name = $order_data['billing']['first_name'] . ' ' . $order_data['billing']['last_name'] . ' ' . '(' . $order_data['billing']['company'] . ')';
			} else if($company_label_name == 'company_first') {
				$order_billing_name =  $order_data['billing']['company'] . ' ' . '(' . $order_data['billing']['first_name'] . ' ' . $order_data['billing']['last_name'] . ')';
			} else if($company_label_name == 'only_company') {
				$order_billing_name = $order_data['billing']['company'];
			} else {
				$order_billing_name = $order_data['billing']['first_name'] . ' ' . $order_data['billing']['last_name'] . ' ' . '(' . $order_data['billing']['company'] . ')';
			}

		} else {
			if(!empty($order_data['billing']['company'])) {
				$order_billing_name = $order_data['billing']['first_name'] . ' ' . $order_data['billing']['last_name'] . ' ' . '(' . $order_data['billing']['company'] . ')';
			} else {
				$order_billing_name = $order_data['billing']['first_name'] . ' ' . $order_data['billing']['last_name'];
			}
		}

		return $order_billing_name;
	}

	public static function billing_order_company_name($order_id) {
		$order = wc_get_order( $order_id );
		$order_data = $order->get_data();

		if(!empty($order_data['billing']['company'])) {
			$billing_order_company_name = $order_data['billing']['company'];
		} else {
			$billing_order_company_name = '';
		}

		return $billing_order_company_name;
	}

	public static function get_client_facturare_details($order_id) {
		$client_facturare_details = get_post_meta( $order_id, 'av_facturare', true );
		return $client_facturare_details;
	}

	public static function client_from_order( $order_id ) {
		$order = wc_get_order( $order_id );
		$order_data = $order->get_data();

		// Check if Facturare WooCommerce exists		
		if ( in_array( 'facturare-persoana-fizica-sau-juridica/facturare.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
			// get client_uid, and tax_id if present from https://wordpress.org/plugins/facturare-persoana-fizica-sau-juridica/
			$client_facturare_details = self::get_client_facturare_details($order_id);
			if(!empty($client_facturare_details)){
				$order_billing_company_uid = $client_facturare_details['cui'];
				$order_billing_company_reg_id = $client_facturare_details['nr_reg_com'];
				// $order_billing_company_bank_iban = $client_facturare_details['iban'];
				// $order_billing_company_bank_name = $client_facturare_details['nume_banca'];

				if($order_billing_company_reg_id == '' || $order_billing_company_reg_id == NULL) {
					$order_billing_company_reg_id = '-';
				}
			}
			
			// if($order_billing_company_bank_iban == '' || $order_billing_company_bank_iban == NULL) {
			// 	$order_billing_company_bank_iban = '-';
			// }

			// if($order_billing_company_bank_name == '' || $order_billing_company_bank_name == NULL) {
			// 	$order_billing_company_bank_name = '-';
			// }
		}

		$order_billing_name = self::billing_order_name($order_id);
		$order_billing_company_name = self::billing_order_company_name($order_id);
		$client_present_id = self::client_exists_for_order($order_billing_name);
		$order_billing_email = self::preprocess_special_characteres($order_data['billing']['email']);
		$order_billing_phone = $order_data['billing']['phone'];
		$order_billing_state = $order_billing_state = $order_data['billing']['state'];
		$order_billing_country = self::preprocess_special_characteres($order_data['billing']['country']);

		if(!empty($order_billing_company_uid)) {
			$client_present_id = self::client_exists_for_order($order_billing_name, $order_billing_company_uid, $order_billing_email, $order_billing_phone, $order_billing_country, $order_billing_company_name);
		} else {
			$client_present_id = self::client_exists_for_order($order_billing_name, NULL, $order_billing_email, $order_billing_phone, $order_billing_country);
		}

		if(empty($client_present_id)) {
			$order_billing_address_1 = self::preprocess_special_characteres($order_data['billing']['address_1']);
			$order_billing_city = self::preprocess_special_characteres($order_data['billing']['city']);
			$order_billing_country = self::preprocess_special_characteres($order_data['billing']['country']);
			
			global $wpdb;
		    $f4 = $wpdb->prefix . "wc_f4_settings";
		    $results = $wpdb->get_results( "SELECT * FROM " . $f4 . " ORDER BY id DESC LIMIT 1", OBJECT );

	    	if(!empty($results[0])) {
		    	$invoicing_without_client_address = $results[0]->invoicing_without_client_address;
			}

			if(empty($order_billing_country)) {
				global $wpdb;
				$f4 = $wpdb->prefix . "wc_f4_settings";
				$results = $wpdb->get_results( "SELECT * FROM " . $f4 . " ORDER BY id DESC LIMIT 1", OBJECT );
				
				if(!empty($results[0])) {
					$order_billing_country = $results[0]->default_country;
				}
			} 

			if($invoicing_without_client_address) {
				$query_string = '{ account { companyCountry { printableName } } }';				
				$account_informations_response = self::query(F4_Admin_Authentication::global_api_key(), $query_string);
				$account_informations_json = json_decode($account_informations_response['body'], true);
				$client_country_from_account = $account_informations_json['data']['account'][0]['companyCountry']['printableName'];

				if($order_billing_company_uid) {
					$query_string = 'mutation { createClient( state: \\"' . $order_billing_state . '\\", isCompany: \\"' . 'true' . '\\", uid: \\"' . $order_billing_company_uid . '\\", registrationId: \\"' . $order_billing_company_reg_id . '\\", name: \\"' . $order_billing_name . '\\", address: \\"' . '-' . '\\", city: \\"' . '-' . '\\", country: \\"' . $client_country_from_account .  '\\", email: \\"' . $order_billing_email .  '\\", telephone: \\"' . '-' . '\\" ) { id } }';
				} else {
					$query_string = 'mutation { createClient( state: \\"' . $order_billing_state . '\\", isCompany: \\"' . 'false' . '\\", uid: \\"' . '' . '\\", name: \\"' . $order_billing_name . '\\", address: \\"' . '-' . '\\", city: \\"' . '-' . '\\", country: \\"' . $client_country_from_account .  '\\", email: \\"' . $order_billing_email .  '\\", telephone: \\"' . '-' . '\\" ) { id } }';
				}
			} else {
				if($order_billing_company_uid) {
					$query_string = 'mutation { createClient( state: \\"' . $order_billing_state . '\\", isCompany: \\"' . 'true' . '\\", uid: \\"' . $order_billing_company_uid . '\\", registrationId: \\"' . $order_billing_company_reg_id . '\\", name: \\"' . $order_billing_name . '\\", address: \\"' . $order_billing_address_1 . '\\", city: \\"' . $order_billing_city . '\\", country: \\"' . $order_billing_country .  '\\", email: \\"' . $order_billing_email .  '\\", telephone: \\"' . $order_billing_phone . '\\" ) { id } }';
				} else {
					$query_string = 'mutation { createClient( state: \\"' . $order_billing_state . '\\", isCompany: \\"' . 'false' . '\\", uid: \\"' . '' . '\\", name: \\"' . $order_billing_name . '\\", address: \\"' . $order_billing_address_1 . '\\", city: \\"' . $order_billing_city . '\\", country: \\"' . $order_billing_country .  '\\", email: \\"' . $order_billing_email .  '\\", telephone: \\"' . $order_billing_phone . '\\" ) { id } }';
				}
			}

			$create_client_response = self::query(F4_Admin_Authentication::global_api_key(), $query_string);
			$create_client_json = json_decode($create_client_response['body'], true);
			
			file_put_contents(FAC_WOO_F4_ERRORS_LOG, $query_string . PHP_EOL, FILE_APPEND);
			file_put_contents(FAC_WOO_F4_ERRORS_LOG, $create_client_json['error'] . PHP_EOL, FILE_APPEND);
			file_put_contents(FAC_WOO_F4_ERRORS_LOG, $create_client_json['errors'][0]['message'] . PHP_EOL, FILE_APPEND);

			if(empty($create_client_json['error']) && empty($create_client_json['errors'][0]['message'])) {
				$client_id = $create_client_json['data']['createClient']['id'];
			} else {
				$client_id = NULL;
				echo "Invalid client to add on factureaza for order: " . $order_id;
				echo "<br>";
				echo $create_client_json['errors'][0]['message'];
				echo "<br>";
			}
			
		} else {
			$client_id = $client_present_id;
		}

		return $client_id;
		echo "<br>";
	}

	public static function client_exists_for_order($order_billing_name, $order_billing_company_uid = NULL, 
		$order_billing_email = NULL, $order_billing_phone = NULL, $order_billing_country = NULL, $order_billing_company_name = NULL) {
		if(!empty($order_billing_company_uid)) { 
			$params = [
				"uid" => $order_billing_company_uid, 
				"email" => $order_billing_email,
				"telephone" => $order_billing_phone,
				"country" => $order_billing_country
			];
		} else {
			$params = [
				"email" => $order_billing_email,
				"telephone" => $order_billing_phone,
				"country" => $order_billing_country
			];
		}

		$clients = self::generic_fallback_client_exists_for($params, $order_billing_name, $order_billing_company_name);
		if(empty($clients)) {
			$clients = [];
			while(count($clients) == 0 && count($params) > 1) {
				array_pop($params);
				$clients = self::generic_fallback_client_exists_for($params, $order_billing_name, $order_billing_company_name);
				if(count($clients) >= 1) {
					break;
				}
			}
		}

		if(empty($clients[0]['id'])) {
			$clients[0]['id'] = NULL;
		}
		
		return $clients[0]['id'];
	}

	public static function generic_fallback_client_exists_for($params, $order_billing_name, $order_billing_company_name) {
		$query_string = '{ clients(name: \\"' . $order_billing_name . '\\", ';
		foreach($params as $key => $param) {
			if(!empty($param)) {
				$query_string = $query_string . $key . ': \\"' . $param . '\\", ';
			}
		} 
		$query_string = $query_string . 'limit: 1) { id } }';

		$clients_response = self::query(F4_Admin_Authentication::global_api_key(), $query_string);
		$clients_json = json_decode($clients_response['body'], true);
		$clients = $clients_json['data']['clients'];

		// if there is no client and query params are uid and the default one (name)
		// try to identify client by the uid and compare similarites between names
		if(empty($clients) && in_array('uid', array_keys($params)) == true && count($params) == 1) {
			$clients = self::get_client_by_eq_uid_and_similar_name($params['uid'], $order_billing_name, $order_billing_company_name);
		} else if(empty($clients)) {
			$clients = [];
		}

		return $clients;
	}

	public static function get_client_by_eq_uid_and_similar_name($order_billing_uid, $order_billing_name, $order_billing_company_name) {
		$query_string = '{ clients(uid: \\"' . $order_billing_uid . '\\", limit: 1) { id name } }';
		$clients_response = self::query(F4_Admin_Authentication::global_api_key(), $query_string);
		$clients_json = json_decode($clients_response['body'], true);
		$clients = $clients_json['data']['clients'];
		$similar_client_names = self::compare_clients_names($clients[0]['name'], $order_billing_name, $order_billing_company_name);
		if($similar_client_names) {
			return $clients;
		}
	}

	public static function compare_clients_names($existent_client_name, $new_client_name, $new_client_company_name) {
		$existent_client_name = self::preprocess_client_name($existent_client_name);
		$new_client_name = self::preprocess_client_name($new_client_name);
		$new_client_company_name = self::preprocess_client_name($new_client_company_name);

		similar_text($existent_client_name, $new_client_name, $percentage_similar_client_names);
		similar_text($existent_client_name, $new_client_company_name, $percentage_similar_client_company_names);
		return ($percentage_similar_client_names > FAC_WOO_F4_SIMILAR_CLIENT_PERCENTAGE_ACCEPTED || $percentage_similar_client_company_names > FAC_WOO_F4_SIMILAR_CLIENT_PERCENTAGE_ACCEPTED);
	}

	public static function preprocess_client_name($client_name) {
		$company_prefixes_replace_regex = "/^(s\.c\.|sc|societatea)\W/i";
		$company_suffixes_replace_regex = "/\W(srl|s\.r\.l\.|sa|s\.a\.|gmbh|sarl)$/i";

		$client_name = strtolower(trim($client_name));
		$client_name = preg_replace("/\./", "", $client_name);
		$client_name = preg_replace($company_prefixes_replace_regex, "", $client_name);
		$client_name = preg_replace($company_suffixes_replace_regex, "", $client_name);
		$client_name = trim($client_name);
		$client_name = preg_replace("/\W/i", "", $client_name);
		return $client_name;
	}

	public static function invoice_exists_for_order() {
		global $wpdb;
		$f4 = $wpdb->prefix . "wc_f4_invoicing";

		global $wpdb;
		$f4 = $wpdb->prefix . "wc_f4_settings";
		$results = $wpdb->get_results( "SELECT * FROM " . $f4 . " ORDER BY id DESC LIMIT 1", OBJECT );
		$proforma_invoices = $results[0]->proforma_invoices;

		if($proforma_invoices) {
			$invoice_or_proforma = "poformaInvoices";
		} else {
			$invoice_or_proforma = "invoices";
		}

		$query_string = '{ ' . $invoice_or_proforma . ' { id } }';

		$invoices_response = self::query(F4_Admin_Authentication::global_api_key(), $query_string);
		
		try {
			$invoices_json = json_decode($invoices_response['body'], true);
		} catch (Exception $e) {
			echo 'Caught exception: ',  $e->getMessage(), "\n";
			echo "<br>";
			echo "invoices_response: " . $invoices_response;
			echo "<br>";
			echo "key: " . F4_Admin_Authentication::global_api_key();
			echo "<br>";
			echo "query: " . $query_string;
		}

		$invoices_json = json_decode($invoices_response['body'], true);
		$invoices = $invoices_json['data'][$invoice_or_proforma];

		$already_invoiced_orders = [];

		if(!empty($invoices)) {
			foreach ($invoices as $invoice) {
				global $wpdb;
				$f4 = $wpdb->prefix . "wc_f4_invoicing";
				$results = $wpdb->get_results( "SELECT * FROM " . $f4 . " WHERE invoice_id ='" . $invoice['id'] . "'", OBJECT );
				foreach ($results as $result) {
					array_push($already_invoiced_orders, $result->order_id);
				}
			}

			return $already_invoiced_orders;
		}
	}

	public static function save_exchange_rate($order_id) {
		$order = wc_get_order( $order_id );
		$order_date = $order->get_date_created()->format ('Y-m-d');
		$order_currency = $order->get_currency();
		$query_string = '{ account { domesticCurrency } }';

		if(isset($_SESSION['exchange_rate'])) { 
			$order_exchange_rate = sanitize_text_field($_SESSION['exchange_rate']);
		}

		$domestic_currency_response = self::query(F4_Admin_Authentication::global_api_key(), $query_string);
		$domestic_currency_json = json_decode($domestic_currency_response['body'], true);
		$domestic_currency = $domestic_currency_json['data']['account'][0]['domesticCurrency'];
		
		global $wpdb;
		$f4 = $wpdb->prefix . "wc_f4_exchange_rates";

		$results = $wpdb->get_results( "SELECT * FROM " . $f4 . " WHERE order_date ='" . $order_date . "'" . " AND currencies ='" . $order_currency . '/' . $domestic_currency . "'", OBJECT );
		if(!empty($results[0]->id)) {
			$wpdb->update( 
				$f4, 
				array( 
					'exchange_rate' => $order_exchange_rate
				), 
				array( 
					'id' => $results[0]->id
				)
			);
		} else {
			if(!empty($order_id)) {
				$wpdb->insert( 
					$f4, 
					array( 
						'order_date' => $order_date,
						'exchange_rate' => $order_exchange_rate,
						'currencies' => $order_currency . '/' . $domestic_currency
					) 
				);
			}
		}
	}
	
	public static function pays_vat() {
		$query_string = '{ account { id companyTaxId } }';

		$account_informations_response = self::query(F4_Admin_Authentication::global_api_key(), $query_string);
		$account_informations_json = json_decode($account_informations_response['body'], true);

		$company_vat_id = $account_informations_json['data']['account'][0]['companyTaxId'];
		$if_company_vat_id_blank = !(empty($company_vat_id) && !is_numeric($company_vat_id));

		return $if_company_vat_id_blank;
	}

	public static function preprocess_special_characteres($raw_string) {
		return esc_html(rtrim(str_replace('\\\\', '\\', addslashes($raw_string)), '\\'));
	}
	
	public static function create_invoice_from_order( $order_id, $client_id ) {
		$order = wc_get_order( $order_id );
		$order_data = $order->get_data();

		$order_currency = $order->get_currency();
		$invoice_series = self::get_invoice_series();
		$invoice_series = explode(",", $invoice_series); 
		$current_date = date('d.m.Y');
		$delegate_id = self::get_user_id();
		
		if(empty($invoice_series[1]) && $invoice_series[1] != 0) {
			echo "Invoice series missing on your factureaza account.";
		} 

		$new_seris_counter =  $invoice_series[1] + 1; 

		if(isset($_SESSION['exchange_rate'])) {
			self::save_exchange_rate($order_id);
		}

		global $wpdb;
		$f4 = $wpdb->prefix . "wc_f4_settings";
		$results = $wpdb->get_results( "SELECT * FROM " . $f4 . " ORDER BY id DESC LIMIT 1", OBJECT );
		$document_state_settings = $results[0]->document_state;
		if($document_state_settings == NULL ) {
			$document_state = 'open';
		} elseif ($document_state_settings == 1) {
			$document_state = 'draft';
		} elseif ($document_state_settings == 0) {
			$document_state = 'open';
		}

		self::pays_vat() ? $vat_type = 1 : $vat_type = 0;

		if(isset($_SESSION['exchange_rate'])) { 
			$order_exchange_rate = sanitize_text_field($_SESSION['exchange_rate']);
		}

		global $wpdb;
		$f4 = $wpdb->prefix . "wc_f4_settings";
		$results = $wpdb->get_results( "SELECT * FROM " . $f4 . " ORDER BY id DESC LIMIT 1", OBJECT );
		$proforma_invoices = $results[0]->proforma_invoices;

		if($proforma_invoices) {
			$invoice_or_proforma = "createProformaInvoice";
		} else {
			$invoice_or_proforma = "createInvoice";
		}

		$query_string = 'mutation { ' . $invoice_or_proforma . '( replaceMissingAttributesWithDefaults: true, documentState: \\"' . $document_state .  '\\", note: \\"' . 'Created with factureaza.ro woocommerce plugin v1.0.6' .  '\\", exchangeRate: \\"' . $order_exchange_rate .  '\\", currency: \\"' . $order_currency .  '\\", clientId: \\"' . $client_id .'\\", documentSeriesId: \\"' . $invoice_series[0] . '\\", documentDate: \\"' . $current_date . '\\", documentSeriesCounter: \\"' . $new_seris_counter . '\\", vatType: \\"' . $vat_type . '\\", delegateId: \\"' . $delegate_id . '\\", documentPositions: [';

		$no_of_products = sizeof($order->get_items());
		foreach ($order->get_items() as $item_key => $item ) {
			$item_data = $item->get_data();

			$product_name = $item_data['name'];
		    $product_id   = $item_data['product_id'];
		    $variation_id = $item_data['variation_id'];
		    $quantity     = $item_data['quantity'];
		    $tax_class    = $item_data['tax_class'];
		    $line_subtotal     = $item_data['subtotal'];
			$line_subtotal_tax = $item_data['subtotal_tax'];
		    $line_total        = $item_data['total'];
			$line_total_tax    = $item_data['total_tax'];

			$total_with_tax = $line_total + $line_total_tax;			

		    global $wpdb;
		    $f4 = $wpdb->prefix . "woocommerce_tax_rates";
		    $results = $wpdb->get_results( "SELECT * FROM " . $f4 . " WHERE tax_rate_class ='" . $tax_class . "'", OBJECT );
		    if(!empty($results[0]->tax_rate) && wc_tax_enabled()) {
				$tax_rate_vat = $results[0]->tax_rate;
				$tax_rate_shipping = $results[0]->tax_rate_shipping;
		    } else {
		    	$tax_rate_vat = NULL;
			}

			global $wpdb;
			$f4 = $wpdb->prefix . "wc_f4_settings";
			$results = $wpdb->get_results( "SELECT * FROM " . $f4 . " ORDER BY id DESC LIMIT 1", OBJECT );
			$document_position_unit_settings = $results[0]->document_position_unit;
			if(!empty($document_position_unit_settings)) {
				$document_position_unit = $document_position_unit_settings;
			} else {
				$document_position_unit = 'Qty.';
			}

			$product = wc_get_product($product_id);
			$regular_price = $product->get_regular_price();
			$sale_price = $product->get_sale_price();
			$product_sku = $product->get_sku();
			$regular_price = (float)$regular_price;
			$sale_price = (float)$sale_price;

			if($regular_price == 0 || $sale_price == 0) {
				$discount = 0;
			} else {
				$discount = -(round(($sale_price - $regular_price) / $regular_price * 100, 3));
				$discount = (float)$discount;
			}

			$product_name = self::preprocess_special_characteres($product_name);
			$query_string = $query_string . '{description: \\"' . $product_name . '\\", unit: \\"' . $document_position_unit .  '\\", unitCount: \\"' . $quantity .  '\\", total: \\"' . $total_with_tax . '\\"';
			
			if($product_sku) {
				$query_string =	$query_string . ', productCode: \\"' . $product_sku . '\\"';
			} else {
				$query_string =	$query_string . ', productCode: \\"' . $product_name . '\\"';
			}
			
			if(!empty($discount) && count($order->get_items()) == 1 && !$order->get_total_shipping() && !$order->get_items('fee')) {
				$query_string = $query_string . ', discountRate: \\"' . $discount . '\\"';
			}
				
			if(self::pays_vat()) {
				$query_string = $query_string . ', vat: \\"' . $tax_rate_vat . '\\"}, ';
			} else {
				$query_string = $query_string . '}, ';
			}

			// if(!empty($discount) && count($order->get_items()) == 1 && !$order->get_total_shipping() && !$order->get_items('fee')) {
			// create_discount_position(); invoice_position can't have type: 'DiscountPosition'
			// }
		}

		$order_shipping_total = $order->get_total_shipping();
		if($order_shipping_total > 0) {
			global $wpdb;
			$f4 = $wpdb->prefix . "wc_f4_settings";
			$results = $wpdb->get_results( "SELECT * FROM " . $f4 . " ORDER BY id DESC LIMIT 1", OBJECT );
			$shipping_tax_name_settings = $results[0]->shipping_tax_name;

			if(!empty($shipping_tax_name_settings)) {
				$shipping_tax_name = $shipping_tax_name_settings;
			} else {
				$shipping_tax_name = 'Shipping tax';
			}

			$query_string = $query_string . '{description: \\"' . self::preprocess_special_characteres($shipping_tax_name) . '\\", unit: \\"-\\", unitCount: \\"' . 1 .  '\\" , productCode: \\"' . self::preprocess_special_characteres($shipping_tax_name) . '\\" ,';

			global $wpdb;
			$f4 = $wpdb->prefix . "wc_f4_settings";
			$results = $wpdb->get_results( "SELECT * FROM " . $f4 . " ORDER BY id DESC LIMIT 1", OBJECT );

			if(!empty($results[0])) {
				$vat_on_transport = $results[0]->vat_on_transport;
				$tax_rate_shipping_included_vat = $results[0]->tax_rate_shipping_included_vat;
				$custom_shipping_vat = $results[0]->custom_shipping_vat;
			}

			if(!empty($custom_shipping_vat)) {
				$transport_tax_rate_vat = $custom_shipping_vat;
			} else {
				$transport_tax_rate_vat = $tax_rate_vat;
			}
			
			if(!$tax_rate_shipping_included_vat) {
				if($tax_rate_shipping && self::pays_vat() && !$vat_on_transport) {
					$query_string = $query_string . 'price: \\"' . $order_shipping_total . '\\", vat: \\"' . $transport_tax_rate_vat . '\\"}, ';
				} else if($tax_rate_shipping && !self::pays_vat() && !$vat_on_transport) {
					$order_shipping_total_with_tax = ($order_shipping_total * (1 + $transport_tax_rate_vat / 100.0));
					$query_string = $query_string . 'price: \\"' . $order_shipping_total_with_tax . '\\"}, ';
				} else {
					if(!empty($custom_shipping_vat)) {
						$query_string = $query_string . 'price: \\"' . $order_shipping_total . '\\", vat: \\"' . $transport_tax_rate_vat . '\\"}, ';
					} else {
						$query_string = $query_string . 'price: \\"' . $order_shipping_total . '\\"}, ';
					}
				}
			} else {
				if($tax_rate_shipping && self::pays_vat()) {
					$shipping_price_without_vat = floatval($order_shipping_total / ( 1 + $transport_tax_rate_vat / 100 ));
					$query_string = $query_string . 'price: \\"' . $shipping_price_without_vat . '\\", vat: \\"' . $transport_tax_rate_vat . '\\"}, ';
				} else {
					if(!empty($custom_shipping_vat)) {
						$shipping_price_without_vat = floatval($order_shipping_total / ( 1 + $transport_tax_rate_vat / 100 ));
						$query_string = $query_string . 'price: \\"' . $shipping_price_without_vat . '\\", vat: \\"' . $transport_tax_rate_vat . '\\"}, ';
					} else {
						$query_string = $query_string . 'price: \\"' . $order_shipping_total . '\\"}, ';
					}
				}
			}
		}

		foreach( $order->get_items('fee') as $item_id => $item_fee ){
			$fee_name = $item_fee->get_name();
			$fee_total = $item_fee->get_total();

			$query_string = $query_string . '{description: \\"' . self::preprocess_special_characteres($fee_name) . '\\", unit: \\"-\\", unitCount: \\"' . 1 .  '\\" , productCode: \\"' . self::preprocess_special_characteres($fee_name) . '\\" ,'; 
			if(self::pays_vat()) {
				$query_string = $query_string . 'price: \\"' . $fee_total . '\\", vat: \\"' . $tax_rate_vat . '\\"}, ';
			} else {
				if (!empty($tax_rate_vat)) {
					$fee_total_with_tax = ($fee_total * (1 + $tax_rate_vat / 100.0));
					$query_string = $query_string . 'price: \\"' . $fee_total_with_tax . '\\"}, ';
				} else {
					$query_string = $query_string . 'price: \\"' . $fee_total . '\\"}, ';
				}
				
			}
		}
		
		$query_string = $query_string . ']) { id } }';

		$create_invoice_response = self::query(F4_Admin_Authentication::global_api_key(), $query_string);
		$create_invoice_json = json_decode($create_invoice_response['body'], true);
		
        file_put_contents(FAC_WOO_F4_ERRORS_LOG, $query_string . PHP_EOL, FILE_APPEND);

		// if($create_invoice_json['error']) {
		// 	file_put_contents(FAC_WOO_F4_ERRORS_LOG, $create_invoice_json['error'] . PHP_EOL, FILE_APPEND);
		// }

		if(isset($create_invoice_json['errors'])) {
			print_r($create_invoice_json);
			echo "<br>";
			file_put_contents(FAC_WOO_F4_ERRORS_LOG, $create_invoice_json['errors'][0]['message'] . PHP_EOL, FILE_APPEND);
		}

		if(!empty($create_invoice_json['data'][$invoice_or_proforma]['id'])) {
			return $create_invoice_json['data'][$invoice_or_proforma]['id'];
		} else if(!empty($create_invoice_json['error']['message'])) {
			print_r(esc_html($create_invoice_json['error']['message']));
			echo "<br>";
		}
	}

	// public static function create_discount_position() {
	// $query_string = $query_string . '{description: \\"' . 'discount' . '\\", unit: \\"' . '-' .  '\\", discountRate: \\"' . $discount .  '\\", total: \\"' . 0 . '\\", vat: \\"' . 0.0 . '\\", type: \\"' . 'DiscountPosition' . '\\"}';
	// }	

	public static function display_invoices() {
		global $wpdb;
		$f4 = $wpdb->prefix . "wc_f4_invoicing";

		global $wpdb;
		$f4 = $wpdb->prefix . "wc_f4_settings";
		$results = $wpdb->get_results( "SELECT * FROM " . $f4 . " ORDER BY id DESC LIMIT 1", OBJECT );
		$proforma_invoices = $results[0]->proforma_invoices;

		if($proforma_invoices) {
			$invoice_or_proforma = "poformaInvoices";
		} else {
			$invoice_or_proforma = "invoices";
		}

		$invoices_params = '(limit: 50, orderBy: \"{\\\\\"createdAt\\\\\": \\\\\"desc\\\\\"}\")';
		$query_string = '{ ' . $invoice_or_proforma . $invoices_params . ' { id hashcode } }';

		$invoices_response = self::query(F4_Admin_Authentication::global_api_key(), $query_string);
		$invoices_json = json_decode($invoices_response['body'], true);
		$invoices = $invoices_json['data'][$invoice_or_proforma];

		$_SESSION['f4_invoices']['url'] = array();
		$_SESSION['f4_invoices']['oid'] = array();

		if(!empty($invoices)) {
			foreach ($invoices as $invoice) {
				global $wpdb;
				$f4 = $wpdb->prefix . "wc_f4_invoicing";
				$results = $wpdb->get_results( "SELECT * FROM " . $f4 . " WHERE invoice_id ='" . $invoice['id'] . "'", OBJECT );
				
				foreach ($results as $result) {
					array_push($_SESSION['f4_invoices']['url'], FAC_WOO_F4_URL . '/' . FAC_WOO_F4_SHARED_DOCUMENT_TLD . '/' . $result->invoice_id . '-' . $invoice['hashcode']);
					array_push($_SESSION['f4_invoices']['oid'], $result->order_id);
				}	
			}
		}

		if(isset($_SESSION['f4_invoices']['url']) && $_SESSION['f4_invoices']['oid']) {
			require_once plugin_dir_path( __FILE__ ) . '/partials/f4-admin-display-invoices-table.php';
		}	
	}	

	public static function invoice_id_to_wp_db( $invoice_id, $order_id ) {
		global $wpdb;
		$f4 = $wpdb->prefix . "wc_f4_invoicing";

		if(!empty($invoice_id)) {
			$wpdb->insert( 
				$f4, 
				array( 
					'invoice_id' => $invoice_id,
					'order_id' => $order_id
				) 
			);
		}
	}

	public static function send_email_with_invoice( $invoice_id, $order_id ) {
		global $wpdb;
	    $f4 = $wpdb->prefix . "wc_f4_settings";
	    $results = $wpdb->get_results( "SELECT * FROM " . $f4 . " ORDER BY id DESC LIMIT 1", OBJECT );

	    if(!empty($results[0])) {
		    $auto_email = $results[0]->auto_email;

		    if($auto_email){
		    	$order = wc_get_order( $order_id );
				$order_data = $order->get_data();
				$order_billing_email = $order_data['billing']['email'];


				$query_string = 'mutation { sendDocument(to: \\"' . $order_billing_email . '\\", documentId: \\"' . $invoice_id . '\\" body: \\"Invoiced with factureaza.ro\\") { id } }';

				$send_email_response = self::query(F4_Admin_Authentication::global_api_key(), $query_string);

				if(!empty($send_email_response['error'])) {
					echo "Send email error for order: " . $order_id;
				 echo "<br>";
				}
		    }
		}		
	}

	public static function query( $api_key, $query_string ) {
		$url = FAC_WOO_F4_URL . '/graphql';
		$body = '{"query": "' . $query_string .  '"}';
		$args = array(
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( $api_key . ':x' ),
				'Content-Type' => 'application/json'
			),
			'body' => $body
		);

		$response = wp_remote_post( $url, $args );
		return $response;
	}

	public static function get_exchange_rate_for_order($order_id) {
		include_once 'class-f4-admin-query.php';
		$order = wc_get_order( $order_id );
		$order_date = $order->get_date_created()->format ('Y-m-d');

		global $wpdb;
		$f4 = $wpdb->prefix . "wc_f4_exchange_rates";

		$order_currency = $order->get_currency();
		$query_string = '{ account { domesticCurrency } }';
		$domestic_currency_response = self::query(F4_Admin_Authentication::global_api_key(), $query_string);
		$domestic_currency_json = json_decode($domestic_currency_response['body'], true);
		$domestic_currency = $domestic_currency_json['data']['account'][0]['domesticCurrency'];
		$results = $wpdb->get_results( "SELECT * FROM " . $f4 . " WHERE order_date ='" . $order_date . "'" . " AND currencies ='" . $order_currency . '/' . $domestic_currency . "'", OBJECT );

		if(!empty($results)) {
			return $results[0]->exchange_rate;
		}
	}

	// public static function generate_all_invoices() {
	// 	global $wpdb;
	//     $f4 = $wpdb->prefix . "wc_f4_settings";
	//     $results = $wpdb->get_results( "SELECT * FROM " . $f4 . " ORDER BY id DESC LIMIT 1", OBJECT );

	//     if(!empty($results[0])) {
	// 	    $auto_invoicing = $results[0]->auto_invoicing;

	// 	    if($auto_invoicing){
	// 			$orders_ids = F4_Admin_Orders::all_orders_id();
	// 			$invoiced_orders = self::invoice_exists_for_order();
	// 			if($invoiced_orders == null) {
	// 				$invoiced_orders = [];
	// 			}

	// 			$admin_email = get_option('admin_email');
	// 			$subject = 'factureaza.ro woocommerce';
	// 			$message = '';
	// 			$headers[] = 'Content-type: text/plain; charset=utf-8';
	// 			$headers[] = 'From:' . $admin_email;

	// 			foreach ($orders_ids as $order_id) {
	// 				$client_id = self::client_from_order($order_id);

	// 				if(array_search($order_id, $invoiced_orders) === false) {

	// 					$needs_exchange = self::exchange_rate_needed($order_id);

	// 					$order = wc_get_order( $order_id );
	// 					$order_date = $order->get_date_created()->format ('Y-m-d');
	// 					$order_currency = $order->get_currency();
	// 					$query_string = '{ account { domesticCurrency } }';
	// 					$domestic_currency_response = self::query(F4_Admin_Authentication::global_api_key(), $query_string);
	// 					$domestic_currency_json = json_decode($domestic_currency_response['body'], true);
	// 					$domestic_currency = $domestic_currency_json['data']['account'][0]['domesticCurrency'];
	// 					$currencies = $order_currency . '/' . $domestic_currency;

	// 					global $wpdb;
	// 					$f4 = $wpdb->prefix . "wc_f4_settings";
	// 					$results = $wpdb->get_results( "SELECT * FROM " . $f4 . " ORDER BY id DESC LIMIT 1", OBJECT );

	// 					if(!empty($results[0])) {
	// 						$multiple_currencies = $results[0]->multiple_currencies;
	// 					} else {
	// 						$multiple_currencies = null;
	// 					}

	// 					if($needs_exchange == true) {
	// 						if(!empty($multiple_currencies)) {
	// 							$exchange_rate = self::get_exchange_rate_for_order($order_id);
	// 							if(!empty($exchange_rate)) {
	// 								$_SESSION['exchange_rate'] = $exchange_rate;
	// 								$invoice_id = self::create_invoice_from_order($order_id, $client_id);
	// 								self::invoice_id_to_wp_db($invoice_id, $order_id);
	// 								self::send_email_with_invoice($invoice_id, $order_id);
									
	// 								$message = $message . 'Invoice generated for order: ' . $order_id . "\n";
	// 							} else {
	// 								echo "We don't have an exchnage rate for order " . $order_id . " date: " . $order_date . " plesase issue invoice from order actions.";
	// 								echo "<br>";

	// 								$message = $message . "We don't have an exchnage rate for order " . $order_id . " date: " . $order_date . " plesase issue invoice from order actions. \n";
	// 							}
	// 						} else {
	// 							echo "Multiple currencies isn't active.";
	// 						}
	// 					} else {
	// 						$invoice_id = self::create_invoice_from_order($order_id, $client_id);
	// 						self::invoice_id_to_wp_db($invoice_id, $order_id);
	// 						self::send_email_with_invoice($invoice_id, $order_id);
	// 					}	
	// 					unset($_SESSION['exchange_rate']);
	// 				}			
	// 			}
	// 			wp_mail( $admin_email, $subject, $message, $headers );
	// 		}
	// 	}
	// }

	public static function generate_batch_invoices() {
		if(isset($_SESSION['orders_ids'])) {
			if(isset($_SESSION['orders_ids'])) { 
				$order_id = $_SESSION['orders_ids'];
			}

			$invoiced_orders = self::invoice_exists_for_order();
			if($invoiced_orders == null) {
				$invoiced_orders = [];
			}

			foreach ($order_id as $oid) {
				$client_id = self::client_from_order($oid);

				if(array_search($oid, $invoiced_orders) === false) {
					$invoice_id = self::create_invoice_from_order($oid, $client_id);
					self::invoice_id_to_wp_db($invoice_id, $oid);
					self::send_email_with_invoice($invoice_id, $oid);
					unset($_SESSION['orders_ids']);
				}else {
					echo "Invoice already exists for order with id: " . $oid;
					echo "<br>";
				}
			}
			
			unset($_SESSION['orders_ids']);
			session_write_close();
		}
	}

	public static function exchange_rate_needed( $order_id ) {
		$order = wc_get_order( $order_id );
		$order_data = $order->get_data();

		$order_currency = $order->get_currency();

		$query_string = '{ account { domesticCurrency } }';

		$domestic_currency_response = self::query(F4_Admin_Authentication::global_api_key(), $query_string);
		$domestic_currency_json = json_decode($domestic_currency_response['body'], true);
		$domestic_currency = $domestic_currency_json['data']['account'][0]['domesticCurrency'];

		if($order_currency != $domestic_currency) {
			return true;
		} 
		return false;
	}

	public static function generate_one_invoice() {
		if(isset($_SESSION['order_id'])) {
			$order_id = [];
			array_push($order_id, sanitize_text_field($_SESSION['order_id']));
			$invoiced_orders = self::invoice_exists_for_order();
			if($invoiced_orders == null) {
				$invoiced_orders = [];
			}

			foreach ($order_id as $oid) {
				$client_id = self::client_from_order($oid);
				
				if(array_search($oid, $invoiced_orders) === false) {
					$invoice_id = self::create_invoice_from_order($oid, $client_id);
					self::invoice_id_to_wp_db($invoice_id, $oid);
					self::send_email_with_invoice($invoice_id, $oid);
					unset($_SESSION['order_id']);
					session_write_close();
				} else {
					echo "Invoice already exists for this order!";
					echo "<br>";
				}
			}

			unset($_SESSION['order_id']);
			unset($_SESSION['exchange_rate']);
			session_write_close();
		}
	}

	public static function display_queries() {
		self::account_informations();
		self::generate_one_invoice();
		self::generate_batch_invoices();
		// self::generate_all_invoices();
		self::display_invoices();
	}
}

?>
