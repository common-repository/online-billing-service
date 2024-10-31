<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              online-billing-service.com
 * @since             1.4.9
 * @package           OBS
 *
 * @wordpress-plugin
 * Plugin Name:       obs
 * Plugin URI:        https://online-billing-service.com
 * Description:       online billing service plugin integrates WooCommerce with online-billing-service.com invoicing app.
 * Version:           1.4.9
 * Author:            online-billing-service.com
 * Author URI:        online-billing-service.com
 * Requires PHP:      7.2
 * Tested up to:      6.7
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       obs
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.4.9 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'FAC_WOO_OBS_VERSION', '1.4.9' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-obs-activator.php
 */
function fac_woo_obs_activate_obs() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-obs-activator.php';
	OBS_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-obs-deactivator.php
 */
function fac_woo_obs_deactivate_obs() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-obs-deactivator.php';
	OBS_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'fac_woo_obs_activate_obs' );
register_deactivation_hook( __FILE__, 'fac_woo_obs_deactivate_obs' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-obs.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.4.9
 */

function fac_woo_obs_db_secret() {
	global $wpdb;
    $obs = "REWSAKSDNSFFQYUFHALNOMKNEPZIOPLW";
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $obs (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		sk varchar(255) NOT NULL,
		s4 varchar(255) NOT NULL,
		UNIQUE KEY id (id)
	) $charset_collate;";

	$results = $wpdb->get_results( "SELECT * FROM " . $obs . " ORDER BY id ASC LIMIT 1", OBJECT );
	if(empty($results[0])) {
		$sk = substr( str_shuffle(md5(time())), 0, 32 );
		$s4 = substr( str_shuffle(md5(time())), 0, 32 );

		$wpdb->insert(
			$obs,
			array(
				'sk' => $sk,
				's4' => $s4
			)
		);
	}

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
}

function fac_woo_obs_db_api_key() {
	global $wpdb;
    $obs = $wpdb->prefix . "wc_obs_api_key";
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $obs (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		api_key varchar(255) NOT NULL,
		encryption_type varchar(255),
		UNIQUE KEY id (id)
	) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

	// add index on api_key
	$sql = "CREATE INDEX api_key
	ON $obs (api_key) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
}

function fac_woo_obs_db_invoicing() {
	global $wpdb;
    $obs = $wpdb->prefix . "wc_obs_invoicing";
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $obs (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		invoice_id varchar(255) NOT NULL,
		order_id varchar(255) NOT NULL,
		invoice_type varchar(255) NOT NULL,
		UNIQUE KEY id (id)
	) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

	// add index on invoice_id
	$sql = "CREATE INDEX invoice_id
	ON $obs (invoice_id) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

	// add index on order_id
	$sql = "CREATE INDEX order_id
	ON $obs (order_id) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
}

function fac_woo_obs_db_settings() {
	global $wpdb;
    $obs = $wpdb->prefix . "wc_obs_settings";
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $obs (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		default_country varchar(255),
		custom_series_prefix varchar(255),
		custom_shipping_vat varchar(255),
		custom_series_suffix varchar(255),
		multiple_currencies mediumint(9),
		proforma_invoices mediumint(9),
		custom_redirect varchar(255),
		auto_invoicing mediumint(9),
		auto_email mediumint(9),
		invoicing_without_client_address mediumint(9),
		vat_on_transport mediumint(9),
		tax_rate_shipping_included_vat mediumint(9),
		document_state varchar(255),
		document_position_unit varchar(255),
		shipping_tax_name varchar(255),
		company_label_name varchar(255),
		stripe_integration_workflow mediumint(9),
		stripe_shipping_tax_value varchar(255),
		stripe_shipping_tax_value_includes_vat mediumint(9),
		stripe_document_position_unit_name varchar(255),
		stripe_shipping_description varchar(255),
		UNIQUE KEY id (id)
	) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
}

function fac_woo_obs_db_exchange_rates() {
	global $wpdb;
    $obs = $wpdb->prefix . "wc_obs_exchange_rates";
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $obs (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		order_date varchar(255),
		exchange_rate varchar(255),
		currencies varchar(255),
		UNIQUE KEY id (id)
	) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
}

function fac_woo_obs_run_obs() {
	$plugin = new OBS();
	$plugin->run();

	fac_woo_obs_db_secret();
	fac_woo_obs_db_api_key();
	fac_woo_obs_db_invoicing();
	fac_woo_obs_db_settings();
	fac_woo_obs_db_exchange_rates();
}
fac_woo_obs_run_obs();

function woocommerce_absent_error() {
    wp_die(__('<strong>online billing service plugin</strong> needs Woocommerce to be installed. <a href="#" onclick="window.history.back();">Back</a>.', 'online billing service'));
}

function woocommerce_incompatible_error() {
	wp_die(__('<strong>online billing service plugin</strong> needs Woocommerce version higher than 3.0.0 and WordPress version higher than 4.7.0 to be installed. <a href="#" onclick="window.history.back();">Back</a>.', 'online billing service'));
}

function woocommerce_presnece() {
	if ( class_exists( 'WooCommerce' ) ) {
		return true;
	} else {
		return false;
	}
}

function check_obs_compatibility() {
    $min_wp_ver = '4.7.0';
    $min_woo_ver = '3.0.0';

    // check woocommerce version to be higher than 4.7
    if ( version_compare( $GLOBALS['wp_version'], $min_wp_ver, '<' )
    ) {
        return false;
    }
    if ( class_exists( 'WooCommerce' ) ) {
        global $woocommerce;
        if( version_compare( $woocommerce->version, $min_woo_ver, "<" ) ) {
            return false;
        }
    }
    else {
        return false;
    }

    return true;
}

?>
