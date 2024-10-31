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

include_once 'class-obs-admin-authentication.php';
// include_once 'models/obs-account.php';
// include_once 'models/obs-client.php';
// include_once 'models/obs-invoice.php';
// include_once 'models/obs-invoice-series.php';
// include_once 'models/obs-user.php';

$models = glob(plugin_dir_path( __FILE__ ) . 'models/*.php');
foreach($models as $model) {
	if($model != __FILE__) {
		include_once $model;
	}
}

define('FAC_WOO_OBS_URL', 'https://online-billing-service.com');
define('FAC_WOO_OBS_SHARED_DOCUMENT_TLD', 'view');
define('FAC_WOO_OBS_ERRORS_LOG', plugin_dir_path( __FILE__ ) . 'fac-woo-obs-queries.log');
define('FAC_WOO_OBS_SIMILAR_CLIENT_PERCENTAGE_ACCEPTED', 83.5);

class OBS_Admin_Query {
	public static function query( $api_key, $query_string ) {
		$url = FAC_WOO_OBS_URL . '/graphql';
		$body = '{"query": "' . $query_string .  '"}';
		$args = array(
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( $api_key . ':x' ),
				'Content-Type' => 'application/json'
			),
			'timeout' => 45,
			'body' => $body
		);

		if (session_id() || session_status() === PHP_SESSION_ACTIVE) {
				session_write_close();
		}

		// try to catch all errors on query and display them right here
		$response = self::execute_request($url, $args);
		self::parse_query_errors($response);
		return $response;
	}

	public static function execute_request($url, $args) {
		try {
			$response = wp_remote_post( $url, $args );
		} catch (Exception $e) {
			_e('<h3 class="obs-custom-error">');
			_e('Caught exception: ',  $e->getMessage(), "\n");
			_e("<br>");
			_e("key: " . OBS_Admin_Authentication::global_api_key());
			_e("<br>");
			_e("query: " . $query_string);
			_e('</h3>');
			return;
		}

		if(!empty($response)) {
			return $response;
		}
	}

	public static function parse_query_errors($response) {
		_e('<h3 class="obs-custom-error">');
		if(is_wp_error($response)) {
			_e('$response is a WP_ERROR');
			_e("<br>");
			return;
		}

		if(empty($response)) {
			_e('$response is empty');
			_e("<br>");
			return;
		}

		if(!empty($response)) {
			$response_body = json_decode($response['body'], true);
			if(!empty($response_body)) {
				if(!empty($response_body['error'])) {
					_e($response_body['error']['message']);
					_e("<br>");
					return;
				}

				if(!empty($response_body['errors'])) {
					_e($response_body['errors'][0]['message']);
					_e("<br>");
					return;
				}

				if(!empty($response_body['message'])) {
					_e($response_body['message']);
					_e("<br>");
					return;
				}
			}
		}
		_e('</h3>');
		return $response;
	}

	public static function api_workflow_handler($invoice_type = null) {
		if ( !self::obs_wc_stripe_integration_workflow() ) {
			OBS_Account::account_informations();

            // if we have send_document action set we will try to send the invoice to the present order_id
			if(self::is_send_document_session_set()) {
				if(isset($_SESSION['order_id'])) {
                    global $wpdb;
                    $obs = $wpdb->prefix . "wc_obs_invoicing";
                    $results = $wpdb->get_results( "SELECT * FROM " . $obs . " WHERE order_id ='" . $_SESSION['order_id'] . "'", OBJECT );
                    if(!empty($results[0])) {
                        OBS_Invoice::send_email_with_invoice(end($results)->invoice_id, $_SESSION['order_id']);
                    }
                }
			} else {
			    OBS_Invoice::generate_one_invoice($invoice_type);
                OBS_Invoice::generate_batch_invoices($invoice_type);

			}

			// self::generate_all_invoices();
			OBS_Invoice::display_invoices();
		}
	}

	public static function obs_wc_stripe_integration_workflow() {
		global $wpdb;
		$obs = $wpdb->prefix . "wc_obs_settings";
		$results = $wpdb->get_results( "SELECT * FROM " . $obs . " ORDER BY id DESC LIMIT 1", OBJECT );

		if(!empty($results[0])) {
			$stripe_integration_workflow = !empty($results[0]->stripe_integration_workflow) ? true : false;
		} else {
			$stripe_integration_workflow = false;
		}

		return $stripe_integration_workflow;
	}

	public static function is_send_document_session_set() {
	    // From PHP 8 we need to explicitly pass $strict = true to in_array function beacuse of this changes:
	    // Prior to PHP 8.0.0, a string needle will match an array value of 0 in non-strict mode, and vice versa.
        return isset($_SESSION['send_document']) && !empty($_SESSION['send_document']) && in_array($_SESSION['send_document'], [true, 'true'], $strict = true);
	}
}

?>
