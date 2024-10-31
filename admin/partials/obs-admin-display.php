<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       online-billing-service.com
 * @since      1.4.9
 *
 * @package    OBS
 * @subpackage OBS/admin/partials
 */
?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<?php
	require_once plugin_dir_path( __FILE__ ) . '../class-obs-admin-authentication.php';
	OBS_Admin_Authentication::set_api_key();

?>
