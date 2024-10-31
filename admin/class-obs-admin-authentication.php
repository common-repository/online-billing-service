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

define('FAC_WOO_OBS_SALT', 'UHRCJKZONZFLQCUAHDCNKRKYEJZIFTUA');

class OBS_Admin_Authentication {
	public static function crypt_api_key( $api_key, $action = 'e' ) {
		global $wpdb;
	    $obs = "REWSAKSDNSFFQYUFHALNOMKNEPZIOPLW";
	    $results = $wpdb->get_results( "SELECT * FROM " . $obs . " ORDER BY id ASC LIMIT 1", OBJECT );
	    $secret_key = $results[0]->sk;
	    $secret_iv = $results[0]->s4;
	    $output = false;
	    $encrypt_method = "AES-256-CBC";
	    $key = hash( 'sha256', $secret_key );
	    $iv = substr( hash( 'sha256', $secret_iv ), 0, 16 );

	    if( $action == 'e' ) {
	        $output = openssl_encrypt( $api_key . FAC_WOO_OBS_SALT, $encrypt_method, $key, 0, $iv );

			if(empty($output)) {
                $output = self::customOBSEncrypt($api_key . FAC_WOO_OBS_SALT, $key);
				global $wpdb;
				$obs = $wpdb->prefix . "wc_obs_api_key";
				$results = $wpdb->get_results( "SELECT * FROM " . $obs . " ORDER BY id DESC LIMIT 1", OBJECT );

				$wpdb->insert(
					$obs,
					array(
						'api_key' => $results[0]->api_key,
						'encryption_type' => 'obs_custom_enc'
					)
				);
                $encrypt_type = 'obs_custom_enc' ?? null;
            }
	    } else if( $action == 'd' ){
			global $wpdb;
			$obs = $wpdb->prefix . "wc_obs_api_key";
			$results = $wpdb->get_results( "SELECT * FROM " . $obs . " ORDER BY id DESC LIMIT 1", OBJECT );

			if(!empty($results) && !empty($results[0])) {
                $encrypt_type = $results[0]->encryption_type ?? null;
            }

			if(!empty($encrypt_type) && $encrypt_type == 'obs_custom_enc') {
                $output = self::customOBSDecrypt($results[0]->api_key, $key);
                $output = str_replace(FAC_WOO_OBS_SALT, '', $output);
            } else {
                $output = openssl_decrypt( $api_key, $encrypt_method, $key, 0, $iv );
                $output = str_replace(FAC_WOO_OBS_SALT, '', $output);
            }
	    }

		if(empty($encrypt_type)) { $encrypt_type = null; }
	    return [$output, $encrypt_type];
	}

	public static function set_api_key() {
		if (!woocommerce_presnece()) {
            woocommerce_absent_error();
        }

		if(current_user_can('administrator')) {
			require_once plugin_dir_path( __FILE__ ) . '/partials/obs-admin-authentication-form.php';

			global $wpdb;
			$obs = $wpdb->prefix . "wc_obs_api_key";

			if(isset($_POST['api_key'])) {
				$obs_api_key = sanitize_text_field($_POST['api_key']);
				$crypt_api_key_and_type = self::crypt_api_key($obs_api_key, 'e');
				$results = $wpdb->get_results( "SELECT * FROM " . $obs . " WHERE api_key ='" . $crypt_api_key_and_type[0] . "'", OBJECT );
			    self::api_key_is_empty(self::crypt_api_key($crypt_api_key_and_type[0], 'd'));

				$wpdb->insert(
					$obs,
					array(
						'api_key' => $crypt_api_key_and_type[0],
						'encryption_type' => $crypt_api_key_and_type[1]
					)
				);

				foreach ($results as $result) {
					if($result->api_key == $obs_api_key) {
						print_r(esc_html($result->api_key));
						_e("<br>");
					}
				}

			}
			self::api_key_is_empty(self::global_api_key());
		}

		require_once plugin_dir_path( __FILE__ ) . 'class-obs-admin-query.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-obs-admin-orders.php';

		if(self::global_api_key()) {
			OBS_Admin_Query::api_workflow_handler();
		}
	}

	public static function api_key_is_empty($api_key) {
		if(empty($api_key)) {
			_e("<h3 class=\"obs\"> Please add an api key! </h3>");
			return;
		}
	}

	public static function global_api_key() {
		$api_key = NULL;
		global $wpdb;
		$obs = $wpdb->prefix . "wc_obs_api_key";

		$results = $wpdb->get_results( "SELECT * FROM " . $obs . " ORDER BY id DESC LIMIT 1", OBJECT );

		if(!empty($results[0])) {
			if(!empty($results[0]->api_key)) {
				$api_key = self::crypt_api_key($results[0]->api_key, 'd')[0];
			}
		}
		return $api_key;
	}

	function customOBSEncrypt($string, $key=FAC_WOO_OBS_SALT) {
        $result = '';
        for($i=0, $k= strlen($string); $i<$k; $i++) {
            $char = substr($string, $i, 1);
            $keychar = substr($key, ($i % strlen($key))-1, 1);
            $char = chr(ord($char)+ord($keychar));
            $result .= $char;
        }
        return base64_encode($result);
    }

    function customOBSDecrypt($string, $key=FAC_WOO_OBS_SALT) {
        $result = '';
        $string = base64_decode($string);
        for($i=0,$k=strlen($string); $i< $k ; $i++) {
            $char = substr($string, $i, 1);
            $keychar = substr($key, ($i % strlen($key))-1, 1);
            $char = chr(ord($char)-ord($keychar));
            $result .= $char;
        }
        return $result;
    }
}

?>
