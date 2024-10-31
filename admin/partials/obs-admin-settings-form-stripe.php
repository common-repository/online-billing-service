<table class="form-table obs-stripe-form">
    <tbody>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="stripe_shipping_tax_value"><b> <?php _e('Shipping tax value'); ?> </b></label>
            </th>
            <td class="forminp forminp-text">
                <input id="stripe_shipping_tax_value" name="stripe_shipping_tax_value" type="number" placeholder="for ex: 20" value="<?php _e(esc_html($stripe_shipping_tax_value)); ?>" style="width: 200px;" /> 							
            </td>
        </tr>

        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="stripe_document_position_unit_name"><b> <?php _e('Document position unit name'); ?> </b></label>
            </th>
            <td class="forminp forminp-text">
                <input id="stripe_document_position_unit_name" name="stripe_document_position_unit_name" type="number" placeholder="for ex: Pcs." value="<?php _e(esc_html($stripe_document_position_unit_name)); ?>" style="width: 200px;" /> 							
                <p class="description"> The default used value for this field is "Pcs.". </p>
            </td>
        </tr>

        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="stripe_shipping_description"><b> <?php _e('Shipping description '); ?> </b></label>
            </th>
            <td class="forminp forminp-text">
                <input id="stripe_shipping_description" name="stripe_shipping_description" type="number" placeholder="for ex: Shipping" value="<?php _e(esc_html($stripe_shipping_description)); ?>" style="width: 200px;" /> 							
            </td>
        </tr>

        <tr valign="top">
            <th scope="row" class="titledesc">
                <b> <?php _e('Shipping tax includes vat'); ?> </b>
            </th>
            <td class="forminp forminp-text">
                <fieldset>            
                    <input id="stripe_shipping_tax_value_includes_vat" name="stripe_shipping_tax_value_includes_vat" type="checkbox" value="1" <?php _e($stripe_shipping_tax_value_includes_vat ? 'checked' : ''); ?>/> <label for="stripe_shipping_tax_value_includes_vat"> Shipping tax includes VAT </label>
                    <p class="description"> Tick this checkbox if shipping tax value includes vat. </p>						
                </fieldset>        
            </td>
        </tr>
    </tbody>
</table>
