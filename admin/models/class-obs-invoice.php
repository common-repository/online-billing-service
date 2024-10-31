<?php

include_once 'obs-initializers.php';
class OBS_Invoice {
	public static function invoice_exists_for_order($invoice_type = null) {
		$invoice_or_proforma = empty($invoice_type) ? self::invoice_or_proforma_type() : $invoice_type;
		$query_string = '{ ' . $invoice_or_proforma . 's' . ' { id } }';
		$invoices_response = OBS_Admin_Query::query(OBS_Admin_Authentication::global_api_key(), $query_string);
		try {
			if(!is_wp_error($invoices_response) && isset($invoices_response['body'])) {
				$invoices_json = json_decode($invoices_response['body'], true);
				$invoices = $invoices_json['data'][$invoice_or_proforma . 's'];
			} else {
				throw new Exception('$invoices_response is WP_ERROR or is not set!');
			}
		} catch (Exception $e) {
			_e('Caught exception: ',  $e->getMessage(), "\n");
			_e("<br>");
			_e("<br>");
			_e("key: " . OBS_Admin_Authentication::global_api_key());
			_e("<br>");
			_e("query: " . $query_string);
		}

		$already_invoiced_orders = [];
		if(!empty($invoices)) {
			foreach ($invoices as $invoice) {
				global $wpdb;
				$obs = $wpdb->prefix . "wc_obs_invoicing";
				$results = $wpdb->get_results( "SELECT * FROM " . $obs . " WHERE invoice_id ='" . $invoice['id'] . "'", OBJECT );
				foreach ($results as $result) {
					array_push($already_invoiced_orders, $result->order_id);
				}
			}

			return $already_invoiced_orders;
		}
	}

	public static function invoice_or_proforma_type() {
		global $wpdb;
		$obs = $wpdb->prefix . "wc_obs_settings";
		$results = $wpdb->get_results( "SELECT * FROM " . $obs . " ORDER BY id DESC LIMIT 1", OBJECT );

		if(!empty($results)) {
			$proforma_invoices = $results[0]->proforma_invoices;
        	return $proforma_invoices ? 'poformaInvoices' : 'invoices';
		} else {
			return "invoices";
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
		$domestic_currency_response = OBS_Admin_Query::query(OBS_Admin_Authentication::global_api_key(), $query_string);

		if(is_wp_error($domestic_currency_response) && !isset($domestic_currency_response['body'])) { return; }
		$domestic_currency_json = json_decode($domestic_currency_response['body'], true);
		$domestic_currency = $domestic_currency_json['data']['account'][0]['domesticCurrency'];

		global $wpdb;
		$obs = $wpdb->prefix . "wc_obs_exchange_rates";

		$results = $wpdb->get_results( "SELECT * FROM " . $obs . " WHERE order_date ='" . $order_date . "'" . " AND currencies ='" . $order_currency . '/' . $domestic_currency . "'", OBJECT );
		if(!empty($results[0]->id)) {
			$wpdb->update(
				$obs,
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
					$obs,
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
		$account_informations_response = OBS_Admin_Query::query(OBS_Admin_Authentication::global_api_key(), $query_string);

		if(is_wp_error($account_informations_response) && !isset($account_informations_response['body'])) { return; }
		$account_informations_json = json_decode($account_informations_response['body'], true);
		$company_vat_id = $account_informations_json['data']['account'][0]['companyTaxId'];
		$if_company_vat_id_blank = !(empty($company_vat_id) && !is_numeric($company_vat_id));
		return $if_company_vat_id_blank;
	}

	public static function create_invoice_from_order( $order_id, $client_id, $invoice_type = null) {
		$order = wc_get_order( $order_id );
		if(empty($order)) { return; }
		$order_data = $order->get_data();

		$order_currency = $order->get_currency();
		$invoice_series = OBS_Invoice_Series::get_invoice_series($invoice_type);
		$invoice_series = explode(",", $invoice_series);
		$current_date = date('d.m.Y');
		$delegate_id = OBS_User::get_user_id();

		if(!empty($invoice_series) && !array_key_exists(1, $invoice_series) && empty($invoice_series[1]) && $invoice_series[1] != 0) {
			_e("Invoice series missing on your online billing service account.");
		}
		$new_series_counter = !empty($invoice_series) && array_key_exists(1, $invoice_series) ? $invoice_series[1] + 1 : 1;

		if(isset($_SESSION['exchange_rate'])) {
			OBS_Invoice::save_exchange_rate($order_id);
		}

		global $wpdb;
		$obs = $wpdb->prefix . "wc_obs_settings";
		$results = $wpdb->get_results( "SELECT * FROM " . $obs . " ORDER BY id DESC LIMIT 1", OBJECT );
		$document_state_settings = $results[0]->document_state;
		if($document_state_settings == NULL ) {
			$document_state = 'open';
		} else if ($document_state_settings == 1) {
			$document_state = 'draft';
		} else if ($document_state_settings == 0) {
			$document_state = 'open';
		}

		OBS_Invoice::pays_vat() ? $vat_type = 1 : $vat_type = 0;
		$order_exchange_rate = !empty($_SESSION['exchange_rate']) ? sanitize_text_field($_SESSION['exchange_rate']) : null;

		global $wpdb;
		$obs = $wpdb->prefix . "wc_obs_settings";
		$results = $wpdb->get_results( "SELECT * FROM " . $obs . " ORDER BY id DESC LIMIT 1", OBJECT );

   		if(empty($invoice_type)) {
        	$proforma_invoices = $results[0]->proforma_invoices;
      		$invoice_or_proforma = $proforma_invoices ? "createProformaInvoice" : "createInvoice";
    	} else {
      		$invoice_or_proforma = "create" . ucfirst($invoice_type);
    	}

	    $query_string = 'mutation { ' . $invoice_or_proforma . '( replaceMissingAttributesWithDefaults: true, documentState: \\"' . $document_state .  '\\", note: \\"' . 'Created with online-billing-service.com woocommerce plugin v1.4.9' .  '\\", exchangeRate: \\"' . $order_exchange_rate .  '\\", currency: \\"' . $order_currency .  '\\", clientId: \\"' . $client_id .'\\", documentSeriesId: \\"' . $invoice_series[0] . '\\", documentDate: \\"' . $current_date . '\\", documentSeriesCounter: \\"' . $new_series_counter . '\\", vatType: \\"' . $vat_type . '\\", delegateId: \\"' . $delegate_id . '\\", documentPositions: [';

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
		    $obs = $wpdb->prefix . "woocommerce_tax_rates";
		    $results = $wpdb->get_results( "SELECT * FROM " . $obs . " WHERE tax_rate_class ='" . $tax_class . "'", OBJECT );
		    if(!empty($results[0]->tax_rate) && wc_tax_enabled()) {
				$tax_rate_vat = $results[0]->tax_rate;
				$tax_rate_shipping = $results[0]->tax_rate_shipping;
		    } else {
		    	$tax_rate_vat = NULL;
			}

			global $wpdb;
			$obs = $wpdb->prefix . "wc_obs_settings";
			$results = $wpdb->get_results( "SELECT * FROM " . $obs . " ORDER BY id DESC LIMIT 1", OBJECT );
			$document_position_unit_settings = $results[0]->document_position_unit;
			$document_position_unit = !empty($document_position_unit_settings) ? $document_position_unit_settings : 'Pcs.';
			$product = wc_get_product($product_id);
			$regular_price = $product->get_regular_price();
			$sale_price = $product->get_sale_price();
			$product_sku = $product->get_sku();
			$regular_price = (float)$regular_price;
			$sale_price = (float)$sale_price;

			$discount = ($regular_price == 0 || $sale_price == 0) ? 0 : (float)-(round(($sale_price - $regular_price) / $regular_price * 100, 3));

			$product_name = self::preprocess_special_characteres($product_name);
			$query_string = $query_string . '{description: \\"' . $product_name . '\\", unit: \\"' . $document_position_unit .  '\\", unitCount: \\"' . $quantity .  '\\", total: \\"' . $total_with_tax . '\\"';

			$query_string = $product_sku ? $query_string . ', productCode: \\"' . $product_sku . '\\"' : $query_string . ', productCode: \\"' . $product_name . '\\"';

			if(!empty($discount) && self::obs_array_count($order->get_items()) == 1 && !$order->get_total_shipping() && !$order->get_items('fee')) {
				$query_string = $query_string . ', discountRate: \\"' . $discount . '\\"';
			}

			if(OBS_Invoice::pays_vat()) {
				$query_string = $query_string . ', vat: \\"' . $tax_rate_vat . '\\"}, ';
			} else {
				$query_string = $query_string . '}, ';
			}
		}

		$order_shipping_total = $order->get_total_shipping();
		if($order_shipping_total > 0) {
			global $wpdb;
			$obs = $wpdb->prefix . "wc_obs_settings";
			$results = $wpdb->get_results( "SELECT * FROM " . $obs . " ORDER BY id DESC LIMIT 1", OBJECT );
			$shipping_tax_name_settings = $results[0]->shipping_tax_name;

			$shipping_tax_name = !empty($shipping_tax_name_settings) ? $shipping_tax_name_settings : 'Shipping tax';
			$query_string = $query_string . '{description: \\"' . self::preprocess_special_characteres($shipping_tax_name) . '\\", unit: \\"-\\", unitCount: \\"' . 1 .  '\\" , productCode: \\"' . self::preprocess_special_characteres($shipping_tax_name) . '\\" ,';

			global $wpdb;
			$obs = $wpdb->prefix . "wc_obs_settings";
			$results = $wpdb->get_results( "SELECT * FROM " . $obs . " ORDER BY id DESC LIMIT 1", OBJECT );

			if(!empty($results[0])) {
				$vat_on_transport = $results[0]->vat_on_transport;
				$tax_rate_shipping_included_vat = $results[0]->tax_rate_shipping_included_vat;
				$custom_shipping_vat = $results[0]->custom_shipping_vat;
			}
			$transport_tax_rate_vat = !empty($custom_shipping_vat) ? $custom_shipping_vat : $tax_rate_vat;

			if(!$tax_rate_shipping_included_vat) {
				if(isset($tax_rate_shipping) && $tax_rate_shipping && OBS_Invoice::pays_vat() && !$vat_on_transport) {
					$query_string = $query_string . 'price: \\"' . $order_shipping_total . '\\", vat: \\"' . $transport_tax_rate_vat . '\\"}, ';
				} else if(isset($tax_rate_shipping) && $tax_rate_shipping && !OBS_Invoice::pays_vat() && !$vat_on_transport) {
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
				if($tax_rate_shipping && OBS_Invoice::pays_vat()) {
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
			if(OBS_Invoice::pays_vat()) {
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
		$create_invoice_response = OBS_Admin_Query::query(OBS_Admin_Authentication::global_api_key(), $query_string);
		if(!is_wp_error($create_invoice_response) && isset($create_invoice_response['body'])) {
			$create_invoice_json = json_decode($create_invoice_response['body'], true);

			// try {
			// 	file_put_contents(FAC_WOO_OBS_ERRORS_LOG, $query_string . PHP_EOL, FILE_APPEND);
            // } catch (Exception $e) {
			// 	_e("Error on writing factureaza.ro logs.");
			// }

			if(isset($create_invoice_json['errors'])) {
				print_r($create_invoice_json);
				_e("<br>");
				// try {
				// 	file_put_contents(FAC_WOO_OBS_ERRORS_LOG, $create_invoice_json['errors'][0]['message'] . PHP_EOL, FILE_APPEND);
				// } catch (Exception $e) {
				// 	_e("Error on writing factureaza.ro logs.");
				// }
			}

			if(!empty($create_invoice_json['data'][$invoice_or_proforma]['id'])) {
				return $create_invoice_json['data'][$invoice_or_proforma]['id'];
			} else if(!empty($create_invoice_json['error']['message'])) {
				print_r(esc_html($create_invoice_json['error']['message']));
				_e("<br>");
			}
		}
	}

	public static function display_invoices() {
		global $wpdb;
		$obs = $wpdb->prefix . "wc_obs_settings";
		$results = $wpdb->get_results( "SELECT * FROM " . $obs . " ORDER BY id DESC LIMIT 1", OBJECT );
		if(!empty($results[0]->custom_redirect)) {
			$custom_redirect = $results[0]->custom_redirect;
			if(isset($custom_redirect) && !empty($custom_redirect)) {
				include_once plugin_dir_path( __FILE__ ) . '../partials/obs-admin-redirect-to-custom.php';
				return;
			}
		}

		$_SESSION['obs_invoices']['url'] = array();
		$_SESSION['obs_invoices']['oid'] = array();
		$_SESSION['obs_invoices']['no_of_all_invoices'] = 0;
    	$display_all_invoices = array();

		// default pagination values
		$limit = 10;
		$offset = 0;

		$pagenum = isset( $_GET['pagenum'] ) ? absint( $_GET['pagenum'] ) : 1;
		if(!empty($pagenum)) {
			$offset = ($pagenum - 1) * 10;
		}

		// select * from invoicing where invoice_type is invoice
		global $wpdb;
		$obs = $wpdb->prefix . "wc_obs_invoicing";
		$results = $wpdb->get_results( "SELECT invoice_id FROM " . $obs . " WHERE invoice_type=" . "'invoice'" . " ORDER BY id DESC", OBJECT );
		if(!empty($results)) {
			$query_string = '{ invoices (limit:' . $limit . ', offset: ' . $offset . ', ids: [';
			$index = 0;
			$res_arr = json_decode(json_encode($results), true);
			foreach($res_arr as $key => $value) {
				if($index == count($res_arr) - 1) {
					$query_string = $query_string . '\"' . $value['invoice_id'] . '\"])';
				} else {
					$query_string = $query_string . '\"' . $value['invoice_id'] . '\", ';
				}
				$index++;
			}

			$query_string = $query_string . '{ id hashcode } }';
			$invoices_response = OBS_Admin_Query::query(OBS_Admin_Authentication::global_api_key(), $query_string);
			$invoices_json = json_decode($invoices_response['body'], true);
			if(!empty($invoices_json)) {
	    		$display_all_invoices = array_merge($display_all_invoices, $invoices_json['data']['invoices']);
			}
		}

		// select * from invoicing where invoice_type is proforma
    	global $wpdb;
		$obs = $wpdb->prefix . "wc_obs_invoicing";
		$results = $wpdb->get_results( "SELECT invoice_id FROM " . $obs . " WHERE invoice_type=" . "'proformaInvoice'" . " ORDER BY id DESC", OBJECT );
		if(!empty($results)) {
			$query_string = '{ proformaInvoices (limit:' . $limit . ', offset: ' . $offset . ', ids: [';
			$index = 0;
			$res_arr = json_decode(json_encode($results), true);
			foreach($res_arr as $key => $value) {
				if($index == count($res_arr) - 1) {
					$query_string = $query_string . '\"' . $value['invoice_id'] . '\"])';
				} else {
					$query_string = $query_string . '\"' . $value['invoice_id'] . '\", ';
				}
				$index++;
			}

			$query_string = $query_string . '{ id hashcode } }';
			$invoices_response = OBS_Admin_Query::query(OBS_Admin_Authentication::global_api_key(), $query_string);
			$invoices_json = json_decode($invoices_response['body'], true);
			if(!empty($invoices_json)) {
     			$display_all_invoices = array_merge($display_all_invoices, $invoices_json['data']['proformaInvoices']);
			}
		}

		// // select * from invoicing where invoice_type is empty
   		global $wpdb;
		$obs = $wpdb->prefix . "wc_obs_invoicing";
		$results = $wpdb->get_results( "SELECT invoice_id FROM " . $obs . " WHERE invoice_type=" . "''" . " ORDER BY id DESC", OBJECT );
		if(!empty($results)) {
			$query_string = '{ invoices (limit:' . $limit . ', offset: ' . $offset . ', ids: [';
			$index = 0;
			$res_arr = json_decode(json_encode($results), true);
			foreach($res_arr as $key => $value) {
				if($index == count($res_arr) - 1) {
					$query_string = $query_string . '\"' . $value['invoice_id'] . '\"])';
				} else {
					$query_string = $query_string . '\"' . $value['invoice_id'] . '\", ';
				}
				$index++;
			}

			$query_string = $query_string . '{ id hashcode } }';
			$invoices_response = OBS_Admin_Query::query(OBS_Admin_Authentication::global_api_key(), $query_string);
			$invoices_json = json_decode($invoices_response['body'], true);
			if(!empty($invoices_json)) {
				$display_all_invoices = array_merge($display_all_invoices, $invoices_json['data']['invoices']);
			}
		}

		foreach ($display_all_invoices as $display_invoice) {
			array_push($_SESSION['obs_invoices']['url'], FAC_WOO_OBS_URL . '/' . FAC_WOO_OBS_SHARED_DOCUMENT_TLD . '/' . $display_invoice['id'] . '-' . $display_invoice['hashcode']);

			$results = $wpdb->get_results( "SELECT order_id FROM " . $obs . " WHERE invoice_id=" . "'". $display_invoice['id'] . "'" . " ORDER BY id DESC LIMIT 1", OBJECT );
			array_push($_SESSION['obs_invoices']['oid'], $results[0]->order_id);
		}

		$_SESSION['obs_invoices']['no_of_all_invoices'] = count($display_all_invoices);
		if(isset($_SESSION['obs_invoices']['url']) && $_SESSION['obs_invoices']['oid']) {
			require_once plugin_dir_path( __FILE__ ) . '../partials/obs-admin-display-invoices-table.php';
		}
	}

	public static function preprocess_special_characteres($raw_string) {
		return esc_html(rtrim(str_replace('\\\\', '\\', addslashes($raw_string)), '\\'));
	}

	public static function invoice_id_to_wp_db( $invoice_id, $order_id, $invoice_type = null) {
		if(empty($invoice_type)) {
			global $wpdb;
			$obs = $wpdb->prefix . "wc_obs_settings";
			$results = $wpdb->get_results( "SELECT * FROM " . $obs . " ORDER BY id DESC LIMIT 1", OBJECT );

			if(!empty($results[0])) {
				$invoice_type_from_settings = $results[0]->proforma_invoices ? 'proformaInvoice' : 'invoice';
			} else {
				$invoice_type_from_settings = 'invoice'; // hardcoded fallback to invoice
			}

			$invoice_type = $invoice_type_from_settings;
		}

		global $wpdb;
		$obs = $wpdb->prefix . "wc_obs_invoicing";
		if(!empty($invoice_id)) {
			$wpdb->insert(
				$obs,
				array(
					'invoice_id' => $invoice_id,
					'order_id' => $order_id,
					'invoice_type' => $invoice_type
				)
			);
		}
	}

	public static function send_email_with_invoice($invoice_id, $order_id) {
		global $wpdb;
	    $obs = $wpdb->prefix . "wc_obs_settings";
	    $results = $wpdb->get_results( "SELECT * FROM " . $obs . " ORDER BY id DESC LIMIT 1", OBJECT );

	    if(OBS_Admin_Query::is_send_document_session_set() || (!empty($results[0]) && $results[0]->auto_email)) {
            self::send_document_email_request($invoice_id, $order_id);
		}

		if(isset($_SESSION['send_document'])) {
					session_start();
          unset($_SESSION['send_document']);
					session_write_close();
        }
	}

	public static function send_document_email_request($invoice_id, $order_id) {
		$order = wc_get_order( $order_id );
		if(empty($order)) { return; }
		$order_data = $order->get_data();
		$order_billing_email = $order_data['billing']['email'];
		$query_string = 'mutation { sendDocument(to: \\"' . $order_billing_email . '\\", documentId: \\"' . $invoice_id . '\\" body: \\"Invoiced with online-billing-service.com\\") { id } }';
		$send_email_response = OBS_Admin_Query::query(OBS_Admin_Authentication::global_api_key(), $query_string);

		if(!is_wp_error($send_email_response) && !empty($send_email_response['error'])) {
			_e("Send email error for order: " . $order_id);
			_e("<br>");
		} else {
			_e("Email sent for order: " . $order_id);
			_e("<br>");
		}
	}

	public static function generate_batch_invoices($invoice_type = null) {
		if(!isset($_SESSION['orders_ids'])) { return; }
		if(isset($_SESSION['orders_ids'])) { $order_id = $_SESSION['orders_ids']; }

		if(empty($invoice_type)) {
			if(isset($_SESSION['proforma'])) {
				$is_proforma = is_string($_SESSION['proforma']) ? $_SESSION['proforma'] === 'true' : $_SESSION['proforma'] == true;
			} else {
				$is_proforma = false;
			}
			$invoice_type = $is_proforma ? 'proformaInvoice' : 'invoice';
		}


		$invoiced_orders = OBS_Invoice::invoice_exists_for_order($invoice_type);
		if($invoiced_orders == null) {
			$invoiced_orders = [];
		}

		foreach ($order_id as $oid) {
			if(!empty($oid)) {
				$client_id = OBS_Client::client_from_order($oid);
			}

			if(array_search($oid, $invoiced_orders) === false) {
				$invoice_id = OBS_Invoice::create_invoice_from_order($oid, $client_id, $invoice_type);
				OBS_Invoice::invoice_id_to_wp_db($invoice_id, $oid, $invoice_type);
				OBS_Invoice::send_email_with_invoice($invoice_id, $oid);
				session_start();
				unset($_SESSION['orders_ids']);
				session_write_close();
			} else {
				_e("Invoice already exists for order with id: " . $oid);
				_e("<br>");
			}
		}

		session_start();
		unset($_SESSION['proforma']);
		unset($_SESSION['orders_ids']);
		session_write_close();
	}

	public static function exchange_rate_needed( $order_id ) {
		$order = wc_get_order( $order_id );
		$order_data = $order->get_data();

		$order_currency = $order->get_currency();
		$query_string = '{ account { domesticCurrency } }';

		// memoize $domestic_currency_response, only run expensive query once per request
		global $domestic_currency_response;
		if(empty($domestic_currency_response) || is_wp_error($domestic_currency_response)) {
			$domestic_currency_response = OBS_Admin_Query::query(OBS_Admin_Authentication::global_api_key(), $query_string);
		}
		$domestic_currency_json = json_decode($domestic_currency_response['body'], true);
		$domestic_currency = $domestic_currency_json['data']['account'][0]['domesticCurrency'];

		if($order_currency != $domestic_currency) {
			return true;
		}
		return false;
	}

	public static function generate_one_invoice($invoice_type = null) {
		if(!isset($_SESSION['order_id'])) { return; }

		if(empty($invoice_type)) {
			if(isset($_SESSION['proforma'])) {
				$is_proforma = is_string($_SESSION['proforma']) ? $_SESSION['proforma'] === 'true' : $_SESSION['proforma'] == true;
			} else {
				$is_proforma = false;
			}
			$invoice_type = $is_proforma ? 'proformaInvoice' : 'invoice';
		}

		$order_id = [];
		array_push($order_id, sanitize_text_field($_SESSION['order_id']));
		$invoiced_orders = OBS_Invoice::invoice_exists_for_order($invoice_type);
		if($invoiced_orders == null) { $invoiced_orders = []; }

		foreach ($order_id as $oid) {
			$client_id = OBS_Client::client_from_order($oid);

			if(array_search($oid, $invoiced_orders) === false) {
				$invoice_id = OBS_Invoice::create_invoice_from_order($oid, $client_id, $invoice_type);
				OBS_Invoice::invoice_id_to_wp_db($invoice_id, $oid, $invoice_type);
				OBS_Invoice::send_email_with_invoice($invoice_id, $oid);
				session_start();
				unset($_SESSION['order_id']);
				unset($_SESSION['proforma']);
				session_write_close();
				if(isset($_SESSION['send_document'])) {
					session_start();
					unset($_SESSION['send_document']);
					session_write_close();
				}
			} else {
				// _e("Invoice already exists for this order!");
				// _e("<br>");
				global $wpdb;
				$obs = $wpdb->prefix . "wc_obs_invoicing";
				$results = $wpdb->get_results( "SELECT * FROM " . $obs . " WHERE order_id='" . $oid . "'", OBJECT );

				if(!empty($results)) {
					$query_string = '{ ' . $invoice_type . 's' . ' (id: ' . '\"' . end($results)->invoice_id . '\"' . ') { id hashcode } }';
					$invoice_response = OBS_Admin_Query::query(OBS_Admin_Authentication::global_api_key(), $query_string);

					try {
						if(!is_wp_error($invoice_response) && isset($invoice_response['body'])) {
							$invoice_json = json_decode($invoice_response['body'], true);
							if(!empty($invoice_json)) {
								$invoices = $invoice_json['data'][$invoice_type . 's'];
								if(!empty($invoices)) {
									$invoice = end($invoices);
									$invoice_link = FAC_WOO_OBS_URL . '/' . FAC_WOO_OBS_SHARED_DOCUMENT_TLD . '/' . $invoice['id'] . '-' . $invoice['hashcode'];
									_e("<h2> Invoice already exists for this order! You can find it <a href=\"$invoice_link\"> here </a>.</h2>");
									_e("<br>");
								}
							}
						} else {
							throw new Exception('$invoices_response is WP_ERROR or is not set!');
						}
					} catch (Exception $e) {
						_e('Caught exception: ',  $e->getMessage(), "\n");
						_e("<br>");
						_e("<br>");
						_e("key: " . OBS_Admin_Authentication::global_api_key());
						_e("<br>");
						_e("query: " . $query_string);
					}
				}
			}
		}

		session_start();
		unset($_SESSION['order_id']);
		unset($_SESSION['proforma']);
		unset($_SESSION['exchange_rate']);
		session_write_close();
		if(isset($_SESSION['send_document'])) {
			session_start();
			unset($_SESSION['send_document']);
			session_write_close();
		}
	}

	public static function obs_array_count($element) {
		if(function_exists('is_countable')) {
			if(is_countable($element) && count($element)) {
				return count($element);
			}
		}
		return count($element);
	}
}

?>
