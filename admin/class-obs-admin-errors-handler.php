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

class OBS_Admin_Errors_Handler {
    public static function handle_errors($fields) {
        if(self::invoice_series_settings_error($fields)) {
            return 'invoice_series_settings_error';
        }
    }

    public static function invoice_series_settings_error($fields) {
        if(!empty($_POST)) {
            $post_custom_series_prefix = !empty($_POST['custom_series_prefix']) ? sanitize_text_field($_POST['custom_series_prefix']) : '';
            $post_custom_series_suffix = !empty($_POST['custom_series_suffix']) ? sanitize_text_field($_POST['custom_series_suffix']) : '';
            $post_stripe_integration_workflow = $_POST['stripe_integration_workflow'] ?? 0;

            foreach ($fields as $field) {
                if(empty($fields)) {
                    $field = 0;
                } else {
                    if($field) {
                        $field = 1;
                    } else {
                        $field = 0;
                    }
                }
            }

            if(empty($fields)) {
                $field = 0;
            }

            $post_stripe_integration_workflow = $post_stripe_integration_workflow ?? $field;
            if((empty($post_custom_series_prefix) && empty($post_custom_series_suffix)) && !$post_stripe_integration_workflow) {
                return true;
            }
        }
    }
}

?>
