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

class F4_Admin_Orders {
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
