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

class OBS_Admin_Orders {
	public static function all_orders_id() {
		global $wpdb;
	    $statuses = array_keys(wc_get_order_statuses());
	    $statuses = implode( "','", $statuses );

	    $orders_ids = $wpdb->get_col( "
	        SELECT ID FROM {$wpdb->prefix}posts
	        WHERE post_type LIKE 'shop_order'
	        AND post_status IN ('$statuses')
	    " );

		return $orders_ids;
	}
}

?>
