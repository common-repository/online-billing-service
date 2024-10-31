<?php
    global $wpdb;
    $obs = $wpdb->prefix . "wc_obs_settings";
    $results = $wpdb->get_results( "SELECT * FROM " . $obs . " ORDER BY id DESC LIMIT 1", OBJECT );
    
    if(!empty($results[0])) {
        $default_country = $results[0]->default_country;
        $custom_series_prefix = $results[0]->custom_series_prefix;
        $custom_shipping_vat = $results[0]->custom_shipping_vat;
        $custom_series_suffix = $results[0]->custom_series_suffix;
        $multiple_currencies = $results[0]->multiple_currencies;
        $proforma_invoices = $results[0]->proforma_invoices;
        $custom_redirect = $results[0]->custom_redirect;
        $auto_invoicing = $results[0]->auto_invoicing;
        $auto_email = $results[0]->auto_email;
        $invoicing_without_client_address = $results[0]->invoicing_without_client_address;
        $vat_on_transport = $results[0]->vat_on_transport;
        $tax_rate_shipping_included_vat = $results[0]->tax_rate_shipping_included_vat;
        $document_state = $results[0]->document_state;
        $document_position_unit = $results[0]->document_position_unit;
        $shipping_tax_name = $results[0]->shipping_tax_name;
        $company_label_name = $results[0]->company_label_name;
        $stripe_integration_workflow = $results[0]->stripe_integration_workflow;
        $stripe_shipping_tax_value = $results[0]->stripe_shipping_tax_value;
        $stripe_shipping_tax_value_includes_vat = $results[0]->stripe_shipping_tax_value_includes_vat;
        $stripe_document_position_unit_name = $results[0]->stripe_document_position_unit_name;
        $stripe_shipping_description = $results[0]->stripe_shipping_description;
    } else {
        $default_country = null;
        $custom_series_prefix = null;
        $custom_shipping_vat = null;
        $custom_series_suffix = null;
        $multiple_currencies = null;
        $proforma_invoices = null;
        $custom_redirect = null;
        $auto_invoicing = null;
        $auto_email = null;
        $invoicing_without_client_address = null;
        $vat_on_transport = null;
        $tax_rate_shipping_included_vat = null;
        $document_state = null;
        $document_position_unit = null;
        $shipping_tax_name = null;
        $company_label_name = null;
        $stripe_integration_workflow = null;
        $stripe_shipping_tax_value = null;
        $stripe_shipping_tax_value_includes_vat = null;
        $stripe_document_position_unit_name = null;
        $stripe_shipping_description = null;
    }
?>

<h2>
    <?php _e('online-billing-service.com Settings', 'online-billing-service.com'); ?>
</h2>

<div class="wrap woocommerce">
    <form method="post">
        <table class="form-table">
            <tbody>
                <tr valign="top">
                    <th scope="row" class="titledesc">
                        <b> <?php _e('Stripe Integration'); ?> </b>
                    </th>
                    <td class="forminp forminp-text">
                        <fieldset>
                            <input id="stripe_integration_workflow" name="stripe_integration_workflow" type="checkbox" value="1" <?php _e($stripe_integration_workflow ? 'checked' : '') ?>/> <label for="stripe_integration_workflow"> Activate stripe integration </label>
                            <p class="description">
                                Click this checkbox if you want the invoice to be created after the order is paid via Stripe.
                                <br>
                                By default an invoice can be created for every order, regardless of its payment status.
                            </p>
                        </fieldset>
                    </td>
                </tr>
            </tbody>
        </table>
        <hr>

        <?php
            require_once plugin_dir_path( __FILE__ ) . '/obs-admin-settings-form-default.php';
            require_once plugin_dir_path( __FILE__ ) . '/obs-admin-settings-form-stripe.php';
        ?>

        <input name="Submit" type="submit" class="button button-primary" value="<?php _e('Submit', 'obs'); ?>" />
    </form>
</div>
