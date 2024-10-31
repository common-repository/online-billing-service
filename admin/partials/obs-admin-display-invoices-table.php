<p><b>Invoices:</b></p>
<div class="obs-invoice-tab">
	<table class="wp-list-table widefat fixed striped table-view-list posts">
		<thead>
			<tr>
				<th class="obs-tab manage-column column-order_number column-primary">
					Order id
				</th>
				<th class="obs-tab manage-column column-order_number column-primary">
					Date
				</th>
				<th class="obs-tab manage-column column-order_number column-primary">
					Status
				</th>
				<th class="obs-tab manage-column column-order_number column-primary">
					Total
				</th>
				<th class="obs-tab manage-column column-order_number">
					Invoice link
				</th>
			</tr>
		</thead>	

		<tbody id="the-list">
			<?php foreach($_SESSION['obs_invoices']['url'] as $key => $url) { ?>
				<?php 
					$order = wc_get_order( $_SESSION['obs_invoices']['oid'][$key] );
					try {
                        if(is_a( $order, 'WC_Order' )) {
                            $order_data = $order->get_data();
                            $order->get_view_order_url();
                        } else {
                            continue;
                        }
                    } catch (Exception $e) {
                        _e("Undefined order_data or order");
                        continue;
                    }
				?>
				<tr>
					<td class="obs-tab order_number column-order_number has-row-actions column-primary">
						<a class="order-preview" href="<?php _e(esc_url($order->get_edit_order_url())); ?>">
							#<?php _e(esc_html($_SESSION['obs_invoices']['oid'][$key])); ?>
						</a>
					</td>
					<td class="obs-tab order_number column-order_number">
						<?php _e($order->get_date_created()->date('Y-m-d H:i')); ?>
					</td>
					<td class="obs-tab order_number column-order_number">
						<span> <?php _e($order->get_status()); ?> </span>
					</td>
					<td class="obs-tab order_number column-order_number">
					<?php _e($order->get_total() . ' ' . $order->get_currency()); ?>
					</td>
					<td class="obs-tab order_number column-order_number has-row-actions">
						<a class="order-preview" href="<?php _e(esc_html($url)); ?>"> <?php _e(esc_html($url)); ?> </a>
					</td>
				</tr>

			<?php  } ?>
		</tbody>		
	</table>

	<?php 
		$no_of_all_invoices = $_SESSION['obs_invoices']['no_of_all_invoices'] ?? 0;
		$num_of_pages = intval($no_of_all_invoices / 10) + 1;
		$pagenum = isset( $_GET['pagenum'] ) ? absint( $_GET['pagenum'] ) : 1;

		$page_links = paginate_links( array(
            'base' => add_query_arg( 'pagenum', '%#%' ),
            'format' => '',
            'prev_text' => __( '&laquo;', 'text-domain' ),
            'next_text' => __( '&raquo;', 'text-domain' ),
            'total' => $num_of_pages,
            'current' => $pagenum
        ) );

        if ( $page_links ) {
            echo '<div class="tablenav" style="width: 99%;"><div class="tablenav-pages" style="margin: 1em 0">' . $page_links . '</div></div>';
        }
	?>
</div>	

