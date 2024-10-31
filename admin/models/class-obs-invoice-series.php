<?php

include_once 'obs-initializers.php'; 

class OBS_Invoice_Series {
    public static function get_invoice_series($invoice_type = null) {
        global $wpdb;
        $obs = $wpdb->prefix . "wc_obs_settings";
        $results = $wpdb->get_results( "SELECT * FROM " . $obs . " ORDER BY id DESC LIMIT 1", OBJECT );
        if(empty($results[0])) { return; }

        $custom_series_prefix = $results[0]->custom_series_prefix ?? '';
        $custom_series_suffix = $results[0]->custom_series_suffix ?? '';

        if(empty($invoice_type)) {
            global $wpdb;
            $obs = $wpdb->prefix . "wc_obs_settings";
            $results = $wpdb->get_results( "SELECT * FROM " . $obs . " ORDER BY id DESC LIMIT 1", OBJECT );
            $proforma_invoices = $results[0]->proforma_invoices;
            $invoice_or_proforma = $proforma_invoices ? 'proformaInvoiceSeries' : 'invoiceSeries';
        } else {
            $invoice_or_proforma = $invoice_type . 'Series';
        }
    
        $query_string = '{ ' . $invoice_or_proforma . ' { id prefix suffix year counterCurrent } }';
        $invoice_series_response = OBS_Admin_Query::query(OBS_Admin_Authentication::global_api_key(), $query_string);
        
        if(!is_wp_error($invoice_series_response) && !empty($invoice_series_response)) {
            $invoice_series_json = json_decode($invoice_series_response['body'], true);
            $invoice_series = $invoice_series_json['data'][$invoice_or_proforma];
        }
        
        $current_year = date('Y');
        if(!empty($invoice_series)) {
            foreach ($invoice_series as $is) {

                if ($is['prefix'] === $custom_series_prefix && $is['suffix'] === $custom_series_suffix && $is['year'] === $current_year) {
                    return $is['id'] . ',' . $is['counterCurrent'];
                } // else { _e("ERROR"); }
            }
        }
    }
}

?>
