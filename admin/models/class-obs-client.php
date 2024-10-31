<?php

include_once 'obs-initializers.php';  

class OBS_Client {
	public static function billing_order_name($order_id) {
		$order = wc_get_order( $order_id );
		$order_data = $order->get_data();

		global $wpdb;
	    $obs = $wpdb->prefix . "wc_obs_settings";
	    $results = $wpdb->get_results( "SELECT * FROM " . $obs . " ORDER BY id DESC LIMIT 1", OBJECT );
	    
	    if(!empty($results[0]) && !empty($order_data['billing']['company'])) {
		    $company_label_name = $results[0]->company_label_name;
			$order_billing_name = self::prepare_order_billing_name_for($order_data, $company_label_name);	
		} else {
			$order_billing_name = self::prepare_order_billing_name_for($order_data);	
		}

		return $order_billing_name;
	}

	public static function prepare_order_billing_name_for($order_data, $company_label_name = null) {
		if($company_label_name == 'name_first') {
			return $order_data['billing']['first_name'] . ' ' . $order_data['billing']['last_name'] . ' ' . '(' . $order_data['billing']['company'] . ')';
		} else if($company_label_name == 'company_first') {
			return $order_data['billing']['company'] . ' ' . '(' . $order_data['billing']['first_name'] . ' ' . $order_data['billing']['last_name'] . ')';
		} else if($company_label_name == 'only_company') {
			return $order_data['billing']['company'];
		} else {
			if(!empty($order_data['billing']['company'])) {
				return $order_data['billing']['first_name'] . ' ' . $order_data['billing']['last_name'] . ' ' . '(' . $order_data['billing']['company'] . ')';
			} else {
				return $order_data['billing']['first_name'] . ' ' . $order_data['billing']['last_name'];
			}
		}
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

	public static function get_client_colete_online_details($order_id) {
		$colete_online_fields = ["address", "address_1", "address_2", "street", "street_number"];

		$colete_online_address_arr = array();
		foreach($colete_online_fields as $colete_online_field) {
			$billing_colete_online_field = get_post_meta($order_id, "_billing_" . $colete_online_field, true);
			$shipping_colete_online_field = empty($billing_colete_online_field) ? get_post_meta($order_id, '_shipping_' . $colete_online_field, true) : '';

			$colete_online_address_arr[] = $billing_colete_online_field . " " . $shipping_colete_online_field;
		}

		return $colete_online_address_arr;
	}

	public static function remove_duplicate_address_fields($order_billing_address, $order_billing_address_colete_online) {
        	$order_billing_address_colete_online[] = $order_billing_address;
	        $address_string = trim(join(" ", array_unique($order_billing_address_colete_online)));
        	$colete_online_string_address = trim(join(" ", array_unique($order_billing_address_colete_online)));

	        if(str_contains($address_string, $order_billing_address)) {
                	$address_string = $order_billing_address;
        	}

	        if(str_contains($address_string, $colete_online_string_address)) {
               		$address_string = $colete_online_string_address;
        	}

        	return preg_replace('!\s+!', ' ', $address_string);
	}

	public static function update_outdated_client_fields(
		$client_present_id,
		$order_billing_company_uid,
		$order_billing_company_reg_id,
		$order_billing_company_uid_as_cnp,
		$order_billing_name,
		$order_billing_email,
		$order_billing_phone,
		$order_billing_state, 
		$order_billing_country, 
		$order_billing_address, 
		$order_billing_city) {
			if($order_billing_name == '' || $order_billing_name == NULL) { $order_billing_name = '-'; }
			if($order_billing_email == '' || $order_billing_email == NULL) { $order_billing_email = '-'; }
			if($order_billing_phone == '' || $order_billing_phone == NULL) { $order_billing_phone = '-'; }
			if($order_billing_state == '' || $order_billing_state == NULL) { $order_billing_state = '-'; }
			if($order_billing_country == '' || $order_billing_country == NULL) { $order_billing_country = '-'; }
			if($order_billing_address == '' || $order_billing_address == NULL) { $order_billing_address = '-'; }
			if($order_billing_city == '' || $order_billing_city == NULL) { $order_billing_city = '-'; }

			if($order_billing_company_uid) {
				$query_string = 'mutation { updateClient( id: \\"' . $client_present_id . '\\", state: \\"' . $order_billing_state . '\\", isCompany: \\"' . 'true' . '\\", uid: \\"' . $order_billing_company_uid . '\\", registrationId: \\"' . $order_billing_company_reg_id . '\\", name: \\"' . $order_billing_name . '\\", address: \\"' . $order_billing_address . '\\", city: \\"' . $order_billing_city . '\\", country: \\"' . $order_billing_country .  '\\", email: \\"' . $order_billing_email .  '\\", telephone: \\"' . $order_billing_phone . '\\" ) { id } }';
			} else {
				$query_string = 'mutation { updateClient( id: \\"' . $client_present_id . '\\", state: \\"' . $order_billing_state . '\\", isCompany: \\"' . 'false' . '\\", uid: \\"' . $order_billing_company_uid_as_cnp . '\\", name: \\"' . $order_billing_name . '\\", address: \\"' . $order_billing_address . '\\", city: \\"' . $order_billing_city . '\\", country: \\"' . $order_billing_country .  '\\", email: \\"' . $order_billing_email .  '\\", telephone: \\"' . $order_billing_phone . '\\" ) { id } }';
			}

			$create_client_response = OBS_Admin_Query::query(OBS_Admin_Authentication::global_api_key(), $query_string);
			if(!is_wp_error($create_client_response) && isset($create_client_response['body'])) {	
				$create_client_json = json_decode($create_client_response['body'], true);
				$create_client_json['data']['updateClient']['id'];
				if($create_client_json) {
					return true;
				}	
			}													
	}

	public static function client_from_order( $order_id ) {
		$order = wc_get_order( $order_id );
		if(empty($order)) { return; }
		$order_data = $order->get_data();

		// Check if Facturare WooCommerce exists		
		if ( in_array( 'facturare-persoana-fizica-sau-juridica/facturare.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), $strict = true) ) {
			// get client_uid, and tax_id if present from https://wordpress.org/plugins/facturare-persoana-fizica-sau-juridica/
			$client_facturare_details = OBS_Client::get_client_facturare_details($order_id);
			if(!empty($client_facturare_details)){
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
				
				// $order_billing_company_bank_iban = $client_facturare_details['iban'];
				// $order_billing_company_bank_name = $client_facturare_details['nume_banca'];

				if(empty($order_billing_company_reg_id)) { $order_billing_company_reg_id = '-'; }
			} else {
				$order_billing_company_uid_as_cnp = '';
			}
			
			// if($order_billing_company_bank_iban == '' || $order_billing_company_bank_iban == NULL) {
			// 	$order_billing_company_bank_iban = '-';
			// }

			// if($order_billing_company_bank_name == '' || $order_billing_company_bank_name == NULL) {
			// 	$order_billing_company_bank_name = '-';
			// }
		}

		$order_billing_name = OBS_Client::billing_order_name($order_id);
		$order_billing_company_name = OBS_Client::billing_order_company_name($order_id);
		$client_present_id = self::client_exists_for_order($order_billing_name);
		$order_billing_email = self::preprocess_special_characteres($order_data['billing']['email']);
		$order_billing_phone = $order_data['billing']['phone'];
		$order_billing_state = $order_data['billing']['state'];
		$order_billing_country = self::preprocess_special_characteres($order_data['billing']['country']);
		$existing_uid = !empty($order_billing_company_uid) ? $order_billing_company_uid : null;
		$existing_company_name = !empty($order_billing_company_name) ? $order_billing_company_name : null;
		$client_present_id = self::client_exists_for_order($order_billing_name, $existing_uid, $order_billing_email, $order_billing_phone, $order_billing_country, $existing_company_name);
		$order_billing_address_1 = self::preprocess_special_characteres($order_data['billing']['address_1']);
		$order_billing_address_2 = self::preprocess_special_characteres($order_data['billing']['address_2']);
		$order_billing_address = $order_billing_address_1 . ' ' . $order_billing_address_2;

		if ( in_array( 'colete-online/colete-online.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), $strict = true) ) {
			$order_billing_address_colete_online = OBS_Client::get_client_colete_online_details($order_id);
			if(!empty($order_billing_address_colete_online)) {
				$order_billing_address = OBS_Client::remove_duplicate_address_fields($order_billing_address, $order_billing_address_colete_online);
			}
		}

		$order_billing_city = self::preprocess_special_characteres($order_data['billing']['city']);
		$order_billing_country = self::preprocess_special_characteres($order_data['billing']['country']);

		if(empty($order_billing_country)) {
			global $wpdb;
			$obs = $wpdb->prefix . "wc_obs_settings";
			$results = $wpdb->get_results( "SELECT * FROM " . $obs . " ORDER BY id DESC LIMIT 1", OBJECT );
			if(!empty($results[0])) { $order_billing_country = $results[0]->default_country; }
		}

		if(empty($client_present_id)) {
			global $wpdb;
		    $obs = $wpdb->prefix . "wc_obs_settings";
		    $results = $wpdb->get_results( "SELECT * FROM " . $obs . " ORDER BY id DESC LIMIT 1", OBJECT );
	    	if(!empty($results[0])) { $invoicing_without_client_address = $results[0]->invoicing_without_client_address; } 

			if($invoicing_without_client_address) {
				$query_string = '{ account { companyCountry { printableName } } }';				
				$account_informations_response = OBS_Admin_Query::query(OBS_Admin_Authentication::global_api_key(), $query_string);
				$account_informations_json = json_decode($account_informations_response['body'], true);
				$client_country_from_account = $account_informations_json['data']['account'][0]['companyCountry']['printableName'];

				if($order_billing_company_uid) {
					$query_string = 'mutation { createClient( state: \\"' . $order_billing_state . '\\", isCompany: \\"' . 'true' . '\\", uid: \\"' . $order_billing_company_uid . '\\", registrationId: \\"' . $order_billing_company_reg_id . '\\", name: \\"' . $order_billing_name . '\\", address: \\"' . '-' . '\\", city: \\"' . '-' . '\\", country: \\"' . $client_country_from_account .  '\\", email: \\"' . $order_billing_email .  '\\", telephone: \\"' . '-' . '\\" ) { id } }';
				} else {
					$query_string = 'mutation { createClient( state: \\"' . $order_billing_state . '\\", isCompany: \\"' . 'false' . '\\", uid: \\"' . $order_billing_company_uid_as_cnp . '\\", name: \\"' . $order_billing_name . '\\", address: \\"' . '-' . '\\", city: \\"' . '-' . '\\", country: \\"' . $client_country_from_account .  '\\", email: \\"' . $order_billing_email .  '\\", telephone: \\"' . '-' . '\\" ) { id } }';
				}
			} else {
				if(!empty($order_billing_company_uid) && $order_billing_company_uid) {
					$query_string = 'mutation { createClient( state: \\"' . $order_billing_state . '\\", isCompany: \\"' . 'true' . '\\", uid: \\"' . $order_billing_company_uid . '\\", registrationId: \\"' . $order_billing_company_reg_id . '\\", name: \\"' . $order_billing_name . '\\", address: \\"' . $order_billing_address . '\\", city: \\"' . $order_billing_city . '\\", country: \\"' . $order_billing_country .  '\\", email: \\"' . $order_billing_email .  '\\", telephone: \\"' . $order_billing_phone . '\\" ) { id } }';
				} else {
					if(!isset($order_billing_company_uid_as_cnp)) { $order_billing_company_uid_as_cnp = ''; }
					$query_string = 'mutation { createClient( state: \\"' . $order_billing_state . '\\", isCompany: \\"' . 'false' . '\\", uid: \\"' . $order_billing_company_uid_as_cnp . '\\", name: \\"' . $order_billing_name . '\\", address: \\"' . $order_billing_address . '\\", city: \\"' . $order_billing_city . '\\", country: \\"' . $order_billing_country .  '\\", email: \\"' . $order_billing_email .  '\\", telephone: \\"' . $order_billing_phone . '\\" ) { id } }';
				}
			}	

			$create_client_response = OBS_Admin_Query::query(OBS_Admin_Authentication::global_api_key(), $query_string);
			if(!is_wp_error($create_client_response) && isset($create_client_response['body'])) {
				$create_client_json = json_decode($create_client_response['body'], true);
			} 

			if(!empty($create_client_json['error'])) {
				try {
					file_put_contents(FAC_WOO_OBS_ERRORS_LOG, $query_string . PHP_EOL, FILE_APPEND);
					file_put_contents(FAC_WOO_OBS_ERRORS_LOG, $create_client_json['error'] . PHP_EOL, FILE_APPEND);
				} catch (Exception $e) { 
					_e("Error on writing factureaza.ro logs.");
				}
			}

			if(!empty($create_client_json['errors'])) {
				try {
					file_put_contents(FAC_WOO_OBS_ERRORS_LOG, $query_string . PHP_EOL, FILE_APPEND);
					file_put_contents(FAC_WOO_OBS_ERRORS_LOG, $create_client_json['errors'][0]['message'] . PHP_EOL, FILE_APPEND);
				} catch (Exception $e) { 
					_e("Error on writing factureaza.ro logs.");
				} 
			}
			
			if(empty($create_client_json['error']) && empty($create_client_json['errors'][0]['message'])) {
				$client_id = $create_client_json['data']['createClient']['id'];
			} else {
				$client_id = NULL;
				_e("Invalid client to add on online billing service for order: " . $order_id);
				_e("<br>");
				_e($create_client_json['errors'][0]['message']);
				_e("<br>");
			}
		} else {
			$client_id = $client_present_id;
			OBS_Client::update_outdated_client_fields(
				$client_present_id,
				$order_billing_company_uid ?? null,
				$order_billing_company_reg_id ?? null,
				$order_billing_company_uid_as_cnp ?? null,
				$order_billing_name ?? null,
				$order_billing_email ?? null,
				$order_billing_phone ?? null,
				$order_billing_state ?? null, 
				$order_billing_country ?? null, 
				$order_billing_address ?? null, 
				$order_billing_city ?? null);
		}

		return $client_id;
		_e("<br>");
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
			$clients = array();
			while(self::obs_array_count($params) > 1) {
				array_pop($params);
				$clients = self::generic_fallback_client_exists_for($params, $order_billing_name, $order_billing_company_name);
				if(self::obs_array_count($clients) >= 1) {
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

		$clients_response = OBS_Admin_Query::query(OBS_Admin_Authentication::global_api_key(), $query_string);
		if(!is_wp_error($clients_response) && isset($clients_response['body'])) {
			$clients_json = json_decode($clients_response['body'], true);	
			if(!empty($clients_json['data'])) {
				$clients = $clients_json['data']['clients'];
			} else {
				$clients = []; 
			} 
		}

		// if there is no client and query params are uid and the default one (name)
		// try to identify client by the uid and compare similarites between names
		if(empty($clients) && in_array('uid', array_keys($params), $strict = true) == true && self::obs_array_count($params) == 1) {
			$clients = self::get_client_by_eq_uid_and_similar_name($params['uid'], $order_billing_name, $order_billing_company_name);
		} else if(empty($clients)) {
			$clients = [];
		}
		return $clients;
	}
	
	public static function get_client_by_eq_uid_and_similar_name($order_billing_uid, $order_billing_name, $order_billing_company_name) {
		$query_string = '{ clients(uid: \\"' . $order_billing_uid . '\\", limit: 1) { id name } }';
		$clients_response = OBS_Admin_Query::query(OBS_Admin_Authentication::global_api_key(), $query_string);
		if(!is_wp_error($clients_response) && isset($clients_response['body']) && preg_match('/\d{6,}$/', $order_billing_uid)) {
		    $clients = self::get_similar_client($clients_response, $order_billing_uid, $order_billing_name, $order_billing_company_name);
		}

		// if client was not found and 
		// if uid starts with two letters (ex. RO) remove those two letters and 
		// try again to find the client, if it is present like this we should use that client.
		if(!isset($clients) || empty($clients)) {
			if(preg_match('/^[a-z,A-Z]{2,}/', $order_billing_uid) && preg_match('/\d{6,}$/', $order_billing_uid)) {
				$query_string = '{ clients(uid: \\"' . preg_replace('/^[a-z,A-Z]{2,}/', '', $order_billing_uid) . '\\", limit: 1) { id name } }';
				$clients_response = OBS_Admin_Query::query(OBS_Admin_Authentication::global_api_key(), $query_string);

                if(!is_wp_error($clients_response) && isset($clients_response['body'])) {
                    $clients = self::get_similar_client($clients_response, $order_billing_uid, $order_billing_name, $order_billing_company_name);
                }
			}
		}

		if(isset($clients)) {
			return $clients;
		}
	}

	public static function get_similar_client($clients_response, $order_billing_uid, $order_billing_name, $order_billing_company_name) {
        $clients_json = json_decode($clients_response['body'], true);
        if(!empty($clients_json['data'])) {
            $clients = $clients_json['data']['clients'];
        } else {
            $clients = [];
        }

        if(isset($clients) && count($clients) >= 1) {
            $similar_client_names = [];
            $eligible_clients = [];
            foreach($clients as $client) {
                $similar_client_name = self::compare_client_names($client['name'], $order_billing_name, $order_billing_company_name);
                if(isset($similar_client_name) && $similar_client_name == true) {
                    array_push($eligible_clients, $client);
                }
            }
			
			// if there is only one eligible client with the same uid and similar names, we use it
			// in all other cases we create a new one.
            if(isset($eligible_clients) && count($eligible_clients) == 1) {
                return $eligible_clients;
            }
        }
	}
	
	public static function compare_client_names($existent_client_name, $new_client_name, $new_client_company_name) {
		$existent_client_name = self::preprocess_client_name($existent_client_name);
		$new_client_name = self::preprocess_client_name($new_client_name);
		$new_client_company_name = self::preprocess_client_name($new_client_company_name);

		similar_text($existent_client_name, $new_client_name, $percentage_similar_client_names);
		similar_text($existent_client_name, $new_client_company_name, $percentage_similar_client_company_names);
		return ($percentage_similar_client_names > FAC_WOO_OBS_SIMILAR_CLIENT_PERCENTAGE_ACCEPTED || $percentage_similar_client_company_names > FAC_WOO_OBS_SIMILAR_CLIENT_PERCENTAGE_ACCEPTED);
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

	public static function preprocess_special_characteres($raw_string) {
		return esc_html(rtrim(str_replace('\\\\', '\\', addslashes($raw_string)), '\\'));
	}

	public static function obs_array_count($element) {
		if(function_exists('is_countable')) {
			if(is_countable($element) && count($element)) {
				return count($element);
			}
		} else {
			return count($element);
		}
	}
}

?>
