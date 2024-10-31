<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       online-billing-service.com
 * @since      1.4.9
 *
 * @package    OBS
 * @subpackage OBS/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.4.9
 * @package    OBS
 * @subpackage OBS/includes
 * @author     OBS <office@online-billing-service.com>
 */
class OBS_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.4.9
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain(
			'obs',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);
	}
}

?>
