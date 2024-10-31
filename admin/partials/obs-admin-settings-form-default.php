<?php require_once plugin_dir_path( __FILE__ ) . '../class-obs-admin-errors-handler.php'; ?>

<?php if(empty($custom_series_prefix) && empty($custom_series_suffix)) { ?>
    <div id="message" class="updated woocommerce-message obs-default-form">
        <p>
        Please specify the invoice series prefix and suffix, that you would like to use for the generated invoices.
        <br>
        They must match one of the invoice series defined in your <a href="https://online-billing-service.com/invoice_series">online-billing-service.com</a> account  for the current year.    
        </p>
    </div>
<?php } ?>
    
<table class="form-table obs-default-form">
    <tbody>
        <?php
            if(OBS_Admin_Errors_Handler::handle_errors([$stripe_integration_workflow]) == 'invoice_series_settings_error') {
                _e('<h3 class="obs-custom-error"> You should enter a value at least in one of the fields: "Invoice Series Prefix" or "Invoice Series Suffix"! <h3>');
                $stop_refresh = true;
            }
        ?>

        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="custom_series_prefix"><b> <?php _e('Invoice Series Prefix'); ?> </b></label>
            </th>
            <td class="forminp forminp-text">
                <input id="custom_series_prefix" name="custom_series_prefix" type="text" placeholder="for ex: MYWEBSHOP" value="<?php _e(esc_html($custom_series_prefix)); ?>" style="width: 200px;" /> 							
            </td>
        </tr>

        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="custom_series_suffix"><b> <?php _e('Invoice Series Suffix'); ?> </b></label>
            </th>
            <td class="forminp forminp-text">
                <input id="custom_series_suffix" name="custom_series_suffix" type="text" placeholder="for ex: ORDER" value="<?php _e(esc_html($custom_series_suffix)); ?>" style="width: 200px;" /> 							
            </td>
        </tr>

        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="company_label_name"><b> <?php _e('Company Buyer Name Settings'); ?> </b></label>
            </th>
            <td class="forminp forminp-text">
                <select name="company_label_name">
                    <option value="name_first" <?php if(esc_html($company_label_name) == 'name_first') { _e("selected"); } ?>>Company Name ( First Name, Last Name)</option>
                    <option value="company_first" <?php if(esc_html($company_label_name) == 'company_first') { _e("selected"); } ?>>First Name Last Name (Company Name)</option>
                    <option value="only_company" <?php if(esc_html($company_label_name) == 'only_company') { _e("selected"); } ?>>Company Name</option>
                </select>
                <p class="description"> Select how would you like to display the buyer's name. </p>                       
            </td>
        </tr>

        <tr valign="top">
            <th scope="row" class="titledesc">
                <b> <?php _e('Multiple currencies'); ?></b>
            </th>
            <td class="forminp forminp-text">
                <fieldset>
                    <input id="multiple_currencies" name="multiple_currencies" type="checkbox" value="1" <?php _e($multiple_currencies ? 'checked' : '') ?>/> <label for="multiple_currencies"> Activate multiple currencies </label>
                    <p class="description"> If you do tick this checkbox the invoices will display both currencies (online billing service and woocommerce default currencies).</p>
                </fieldset>    							
            </td>
        </tr>

        <tr valign="top">
            <th scope="row" class="titledesc">
                <b> <?php _e('Proforma Invoices'); ?></b>
            </th>
            <td class="forminp forminp-text">
                <fieldset>
                    <input id="proforma_invoices" name="proforma_invoices" type="checkbox" value="1" <?php _e($proforma_invoices ? 'checked' : '') ?>/> <label for="proforma_invoices"> Activate proforma invoices </label>
                    <p class="description"> If you do tick this checkbox you will generate proforma invoices instead of invoices. This will work only if Automatically create invoices is checked. </p>
                </fieldset>    							
            </td>
        </tr>

        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="custom_redirect"><b> <?php _e('Invoice Redirect Link'); ?> </b></label>
            </th>
            <td class="forminp forminp-text">
                <input id="custom_redirect" name="custom_redirect" type="text" placeholder="Custom Link" value="<?php _e(esc_html($custom_redirect)); ?>" style="width: 200px;" /> 
                <p class="description"> Redirect to this link after generating the invoice.</p>							
            </td>
        </tr>

        <tr valign="top">
            <th scope="row" class="titledesc">
                <b> <?php _e('Automatically create invoices'); ?></b>
            </th>
            <td class="forminp forminp-text">
                <fieldset>
                    <input id="auto_invoicing" name="auto_invoicing" type="checkbox" value="1" <?php _e($auto_invoicing ? 'checked' : '') ?>/> <label for="auto_invoicing"> Activate automatic invoicing </label>
                    <p class="description"> If you do tick this checkbox the invoice will be automatically generated after a new order is marked as status completed. </p>							
                </fieldset>
            </td>
        </tr>

        <tr valign="top">
            <th scope="row" class="titledesc">
                <b> <?php _e('Document state'); ?></b>
            </th>
            <td class="forminp forminp-text">
                <fieldset>
                    <input id="document_state" name="document_state" type="checkbox" value="1" <?php _e($document_state ? 'checked' : '') ?>/> <label for="document_state"> Generate invoices as draft </label>
                    <p class="description"> If you do tick this checkbox the invoice will be generated as a draft. </p>							
                </fieldset>
            </td>
        </tr>

        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="document_position_unit"><b> <?php _e('Document position unit'); ?> </b></label>
            </th>
            <td class="forminp forminp-text">
                <input id="document_position_unit" name="document_position_unit" type="text" placeholder="for ex: Pcs." value="<?php _e(esc_html($document_position_unit)); ?>" style="width: 200px;" /> 							
            </td>
        </tr>

        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="shipping_tax_name"><b> <?php _e('Shipping tax name'); ?> </b></label>
            </th>
            <td class="forminp forminp-text">
                <input id="shipping_tax_name" name="shipping_tax_name" type="text" placeholder="for ex: Shipping tax." value="<?php _e(esc_html($shipping_tax_name)); ?>" style="width: 200px;" /> 							
            </td>
        </tr>

        <tr valign="top">
            <th scope="row" class="titledesc">
                <b> <?php _e('Automatically send invoices or proformas by email after generation'); ?> </b>
            </th>
            <td class="forminp forminp-text">
                <fieldset>            
                    <input id="auto_email" name="auto_email" type="checkbox" value="1" <?php _e($auto_email ? 'checked' : '') ?>/> <label for="auto_email"> Activate automatic email sending </label>
                    <p class="description"> Tick this checkbox if you would like the invoice to be automatically sent to the client after the document generation. </p>						
                </fieldset>        
            </td>
        </tr>

        <tr valign="top">
            <th scope="row" class="titledesc">
                <b> <?php _e('Generate invoice without client address'); ?> </b>
            </th>
            <td class="forminp forminp-text">
                <fieldset>            
                    <input id="invoicing_without_client_address" name="invoicing_without_client_address" type="checkbox" value="1" <?php _e($invoicing_without_client_address ? 'checked' : '') ?>/> <label for="invoicing_without_client_address"> Activate invoicing without client address </label>
                    <p class="description"> Tick this checkbox if you would like to generate invoice without fields: city, country, address. </p>						
                </fieldset>        
            </td>
        </tr>

        <tr valign="top">
            <th scope="row" class="titledesc">
                <b> <?php _e('Do not apply vat on transport tax.'); ?> </b>
            </th>
            <td class="forminp forminp-text">
                <fieldset>            
                    <input id="vat_on_transport" name="vat_on_transport" type="checkbox" value="1" <?php _e($vat_on_transport ? 'checked' : '') ?>/> <label for="vat_on_transport"> Activate invoicing without vat on transport </label>
                    <p class="description"> Tick this checkbox if you would like to generate invoice without apply vat tax on shipping. </p>						
                </fieldset>        
            </td>
        </tr>

        <tr valign="top">
            <th scope="row" class="titledesc">
                <b> <?php _e('Transport tax includes VAT'); ?> </b>
            </th>
            <td class="forminp forminp-text">
                <fieldset>            
                    <input id="tax_rate_shipping_included_vat" name="tax_rate_shipping_included_vat" type="checkbox" value="1" <?php _e($tax_rate_shipping_included_vat ? 'checked' : '') ?>/> <label for="tax_rate_shipping_included_vat"> Activate invoicing with transport tax including VAT </label>                            
                    <p class="description"> Tick this checkbox if shipping tax includes VAT. Note that shipping VAT and total shown in the WooCommerce Order will be different than the corresponding invoice position. </p>						
                </fieldset>        
            </td>
        </tr>

        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="custom_shipping_vat"><b> <?php _e('Custom shipping VAT'); ?> </b></label>
            </th>
            <td class="forminp forminp-text">
                <input id="custom_shipping_vat" name="custom_shipping_vat" type="text" placeholder="for ex: 19" value="<?php _e(esc_html($custom_shipping_vat)); ?>" style="width: 200px;" /> 							
                <p class="description"> This value must be a percentage used to calculate the transport price. This will overwrite other transport taxes on generated invoices. </p>	
            </td>
        </tr>

        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="default_country"><b> <?php _e('Default Country'); ?> </b></label>
            </th>
            <td class="forminp forminp-text">
                <input id="default_country" name="default_country" type="text" placeholder="for ex: DE, GB or RO" value="<?php _e(esc_html($default_country)); ?>" style="width: 200px;" /> 							
                <p class="description"> 
                    You can find list of all country ISO codes <a href="https://en.wikipedia.org/wiki/List_of_ISO_3166_country_codes"> here. </a>
                    <br>
                    This is a fallback country that will be used if the customer doesn't insert any country.
                </p>
            </td>
        </tr>

    </tbody>
</table>
