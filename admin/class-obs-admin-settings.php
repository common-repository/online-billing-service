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


class OBS_Admin_Settings {
	public static function get_settings() {
		if (!woocommerce_presnece()) {
            woocommerce_absent_error();
        }

        require_once plugin_dir_path( __FILE__ ) . '/partials/obs-admin-settings-form.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-obs-admin-errors-handler.php';
		global $wpdb;
		$obs = $wpdb->prefix . "wc_obs_settings";

		if(!empty($_POST)) {
			if(empty($_POST['multiple_currencies'])) {
				$_POST['multiple_currencies'] = 0;
			}

			if(empty($_POST['proforma_invoices'])) {
				$_POST['proforma_invoices'] = 0;
			}

			if(empty($_POST['auto_invoicing'])) {
				$_POST['auto_invoicing'] = 0;
			}

			if(empty($_POST['auto_email'])) {
				$_POST['auto_email'] = 0;
			}

			if(empty($_POST['invoicing_without_client_address'])) {
				$_POST['invoicing_without_client_address'] = 0;
			}

			if(empty($_POST['vat_on_transport'])) {
				$_POST['vat_on_transport'] = 0;
			}

			if(empty($_POST['tax_rate_shipping_included_vat'])) {
				$_POST['tax_rate_shipping_included_vat'] = 0;
			}

			if(empty($_POST['document_state'])) {
				$_POST['document_state'] = 0;
			}

			if(empty($_POST['stripe_integration_workflow'])) {
				$_POST['stripe_integration_workflow'] = 0;
			}

			if(empty($_POST['stripe_shipping_tax_value_includes_vat'])) {
				$_POST['stripe_shipping_tax_value_includes_vat'] = 0;
			}

			$stripe_integration_workflow = $_POST['stripe_integration_workflow'] ?? 0;

			$stripe_shipping_tax_value = !empty($_POST['stripe_shipping_tax_value']) ? sanitize_text_field($_POST['stripe_shipping_tax_value']) : '';
			$stripe_document_position_unit_name = !empty($_POST['stripe_document_position_unit_name']) ? sanitize_text_field($_POST['stripe_document_position_unit_name']) : '';
			$stripe_shipping_description = !empty($_POST['stripe_shipping_description']) ? sanitize_text_field($_POST['stripe_shipping_description']) : '';
			$custom_series_prefix = !empty($_POST['custom_series_prefix']) ? sanitize_text_field($_POST['custom_series_prefix']) : '';
			$default_country = !empty($_POST['default_country']) ? sanitize_text_field($_POST['default_country']) : '';
			$custom_shipping_vat = !empty($_POST['custom_shipping_vat']) ? sanitize_text_field($_POST['custom_shipping_vat']) : '';
			$custom_series_suffix = !empty($_POST['custom_series_suffix']) ? sanitize_text_field($_POST['custom_series_suffix']) : '';
			$custom_redirect = !empty($_POST['custom_redirect']) ? sanitize_text_field($_POST['custom_redirect']) : '';
			$document_position_unit = !empty($_POST['document_position_unit']) ? sanitize_text_field($_POST['document_position_unit']) : '';
			$shipping_tax_name = !empty($_POST['shipping_tax_name']) ? sanitize_text_field($_POST['shipping_tax_name']) : '';
			$company_label_name = !empty($_POST['company_label_name']) ? sanitize_text_field($_POST['company_label_name']) : '';

			if(empty($stripe_integration_workflow)) {
				$stripe_integration_workflow = 0;
			}

			global $wpdb;
			$obs = $wpdb->prefix . "wc_obs_settings";
			$results = $wpdb->get_results( "SELECT * FROM " . $obs . " ORDER BY id DESC LIMIT 1", OBJECT );

			if(!empty($results) && $stripe_integration_workflow != $results[0]->stripe_integration_workflow) {
				$wpdb->update(
					$obs,
					array(
						'stripe_integration_workflow' => $stripe_integration_workflow
					),
					array(
						'id' => $results[0]->id
					)
				);
				header("Refresh:0");
				return;
			}

			if(!(OBS_Admin_Errors_Handler::handle_errors([$stripe_integration_workflow]) == 'invoice_series_settings_error')) {
				$parameters = array(
					'custom_series_prefix' => $custom_series_prefix,
					'default_country' => $default_country,
					'custom_shipping_vat' => $custom_shipping_vat,
					'custom_series_suffix' => $custom_series_suffix,
					'multiple_currencies' => sanitize_text_field($_POST['multiple_currencies']),
					'proforma_invoices' => sanitize_text_field($_POST['proforma_invoices']),
					'custom_redirect' => $custom_redirect,
					'auto_invoicing' => sanitize_text_field($_POST['auto_invoicing']),
					'auto_email' => sanitize_text_field($_POST['auto_email']),
					'invoicing_without_client_address' => sanitize_text_field($_POST['invoicing_without_client_address']),
					'vat_on_transport' => sanitize_text_field($_POST['vat_on_transport']),
					'tax_rate_shipping_included_vat' => sanitize_text_field($_POST['tax_rate_shipping_included_vat']),
					'document_state' => sanitize_text_field($_POST['document_state']),
					'document_position_unit' => $document_position_unit,
					'shipping_tax_name' => $shipping_tax_name,
					'company_label_name' => $company_label_name,
					'stripe_integration_workflow' => $stripe_integration_workflow,
					'stripe_shipping_tax_value' => $stripe_shipping_tax_value,
					'stripe_shipping_tax_value_includes_vat' => sanitize_text_field($_POST['stripe_shipping_tax_value_includes_vat']),
					'stripe_document_position_unit_name' => $stripe_document_position_unit_name,
					'stripe_shipping_description' => $stripe_shipping_description
				);

				if(!empty($results) && !empty($results[0]) && !empty($results[0]->id)) {
					$trimmed_parameters = array_diff(array_map('trim', $parameters), array('', NULL, ' '));
                    $trimmed_parameters['custom_series_prefix'] = $parameters['custom_series_prefix'];
                    $trimmed_parameters['custom_series_suffix'] = $parameters['custom_series_suffix'];
					$trimmed_parameters['custom_redirect'] = $parameters['custom_redirect'];
					$trimmed_parameters['document_position_unit'] = $parameters['document_position_unit'];
					$trimmed_parameters['shipping_tax_name'] = $parameters['shipping_tax_name'];
					$trimmed_parameters['custom_shipping_vat'] = $parameters['custom_shipping_vat'];
					$trimmed_parameters['default_country'] = $parameters['default_country'];

					$wpdb->update(
						$obs,
						$trimmed_parameters,
						array(
							'id' => $results[0]->id
						)
					);
				} else {
					$wpdb->insert(
						$obs,
						$parameters
					);
				}
			} else {
				global $wpdb;
				$obs = $wpdb->prefix . "wc_obs_settings";
				$results = $wpdb->get_results( "SELECT * FROM " . $obs . " ORDER BY id DESC LIMIT 1", OBJECT );

				$wpdb->update(
					$obs,
					array(
						'stripe_integration_workflow' => $stripe_integration_workflow
					),
					array(
						'id' => $results[0]->id
					)
				);
			}

			if(empty($stop_refresh) || !$stop_refresh) {
				header("Refresh:0");
				return;
			}
		}
	}
}

?>
