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

<?php
    global $wpdb;
    $obs = $wpdb->prefix . "wc_obs_settings";
    $results = $wpdb->get_results( "SELECT * FROM " . $obs . " ORDER BY id DESC LIMIT 1", OBJECT );
    if(!empty($results[0]->custom_redirect)) {
        $custom_redirect = $results[0]->custom_redirect;
        $url = '//' . $custom_redirect;
    }
?>

<script>
    window.location="<?php _e($url); ?>";
</script>

<?php exit; ?>
