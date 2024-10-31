<?php

include_once 'obs-initializers.php';

class OBS_Account {
    public static function account_informations() {
		$query_string = '{ account { id name companyAddress1 companyCity companyFax companyUid companyName companyState totalOpen } }';

		$account_informations_response = OBS_Admin_Query::query(OBS_Admin_Authentication::global_api_key(), $query_string);
        
        if(is_wp_error($account_informations_response)) {
			print_r($account_informations_response);
		}
		
        $account_data_present = false;
		if(!empty($account_informations_response)) {
		  $account_informations_json = json_decode($account_informations_response['body'], true);
		  $account_data_present = (!empty($account_informations_json['data']['account'][0]['id']) || !empty($account_informations_json['data']['account'][0]['name']));
		}

		$api_key = OBS_Admin_Authentication::global_api_key();
		$last_six = substr($api_key, -6);
		$display_api_key = str_repeat('*', 20 + rand(0, 5)) . $last_six;

		if($account_data_present) {
			_e("<h1> You are now connected to online-billing-service.com </h1>");
			_e("<p class=\"api-key-text\">Current API_KEY: " . $display_api_key . "</p>");			
			_e("<p class=\"account-text\">Current account: " . "<b>" . $account_informations_json['data']['account'][0]['name'] . '</b> (' . $account_informations_json['data']['account'][0]['companyName'] . ") </p>");
		} else {
			_e("<p>Something went wrong.</p>");

			global $wpdb;
			$obs = $wpdb->prefix . "wc_obs_api_key";
			$results = $wpdb->get_results( "DELETE FROM " . $obs . " ORDER BY id DESC LIMIT 1", OBJECT );

			if(!empty($account_informations_response)) {
				print_r(esc_html($account_informations_response['body']));
			}
		}		
	}
}

?>
