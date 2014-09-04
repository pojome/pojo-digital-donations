<?php
/**
 * This template is used to display the purchase summary with [pdd_receipt]
 */
global $pdd_receipt_args, $pdd_options;

$payment   = get_post( $pdd_receipt_args['id'] );
$meta      = pdd_get_payment_meta( $payment->ID );
$cart      = pdd_get_payment_meta_cart_details( $payment->ID, true );
$user      = pdd_get_payment_meta_user_info( $payment->ID );
$email     = pdd_get_payment_user_email( $payment->ID );
$status    = pdd_get_payment_status( $payment, true );
?>

	<table id="pdd_purchase_receipt_products">
		<thead>
		<th><?php echo pdd_get_label_singular(); ?></th>
		<?php if ( pdd_use_skus() ) { ?>
			<th><?php _e( 'SKU', 'pdd' ); ?></th>
		<?php } ?>
		<?php if ( pdd_item_quantities_enabled() ) : ?>
			<th><?php _e( 'Quantity', 'pdd' ); ?></th>
		<?php endif; ?>
		<th><?php _e( 'Amount', 'pdd' ); ?></th>
		</thead>

		<tbody>
		<?php if( $cart ) : ?>
			<?php foreach ( $cart as $key => $item ) : ?>
				<?php if( empty( $item['in_bundle'] ) ) : ?>
					<tr>
						<td>

							<?php
							$price_id       = pdd_get_cart_item_price_id( $item );
							$download_files = pdd_get_download_files( $item['id'], $price_id );
							?>

							<div class="pdd_purchase_receipt_product_name">
								<?php echo esc_html( $item['name'] ); ?>
								<?php if( ! is_null( $price_id ) ) : ?>
									<span class="pdd_purchase_receipt_price_name">&nbsp;&ndash;&nbsp;<?php echo pdd_get_price_option_name( $item['id'], $price_id ); ?></span>
								<?php endif; ?>
							</div>

							<?php if ( $pdd_receipt_args['notes'] ) : ?>
								<div class="pdd_purchase_receipt_product_notes"><?php echo pdd_get_product_notes( $item['id'] ); ?></div>
							<?php endif; ?>

						</td>
						<?php if ( pdd_use_skus() ) : ?>
							<td><?php echo pdd_get_download_sku( $item['id'] ); ?></td>
						<?php endif; ?>
						<?php if ( pdd_item_quantities_enabled() ) { ?>
							<td><?php echo $item['quantity']; ?></td>
						<?php } ?>
						<td>
							<?php if( empty( $item['in_bundle'] ) ) : // Only show price when product is not part of a bundle ?>
								<?php echo pdd_currency_filter( pdd_format_amount( $item[ 'price' ] ) ); ?>
							<?php endif; ?>
						</td>
					</tr>
				<?php endif; ?>
			<?php endforeach; ?>
		<?php endif; ?>
		<?php if ( ( $fees = pdd_get_payment_fees( $payment->ID, 'item' ) ) ) : ?>
			<?php foreach( $fees as $fee ) : ?>
				<tr>
					<td class="pdd_fee_label"><?php echo esc_html( $fee['label'] ); ?></td>
					<?php if ( pdd_item_quantities_enabled() ) : ?>
						<td></td>
					<?php endif; ?>
					<td class="pdd_fee_amount"><?php echo pdd_currency_filter( pdd_format_amount( $fee['amount'] ) ); ?></td>
				</tr>
			<?php endforeach; ?>
		<?php endif; ?>
		</tbody>
		<?php if ( $pdd_receipt_args[ 'price' ] ) : ?>
		<tfoot>
			<?php do_action( 'pdd_payment_receipt_products_footer_before', $payment, $pdd_receipt_args ); ?>
			<tr>
				<td><strong><?php _e( 'Total Amount', 'pdd' ); ?>:</strong></td>
				<td><?php echo pdd_payment_amount( $payment->ID ); ?></td>
			</tr>
			<?php do_action( 'pdd_payment_receipt_products_footer_after', $payment, $pdd_receipt_args ); ?>
		</tfoot>
		<?php endif; ?>

	</table>


	<table id="pdd_purchase_receipt">
		<thead>
		<?php do_action( 'pdd_payment_receipt_before', $payment, $pdd_receipt_args ); ?>

		<?php if ( $pdd_receipt_args['payment_id'] ) : ?>
			<tr>
				<th><strong><?php _e( 'Payment', 'pdd' ); ?>:</strong></th>
				<th>#<?php echo pdd_get_payment_number( $payment->ID ); ?></th>
			</tr>
		<?php endif; ?>
		</thead>

		<tbody>

		<tr>
			<td class="pdd_receipt_payment_status"><strong><?php _e( 'Payment Status', 'pdd' ); ?>:</strong></td>
			<td class="pdd_receipt_payment_status <?php echo strtolower( $status ); ?>"><?php echo $status; ?></td>
		</tr>

		<?php if ( $pdd_receipt_args['payment_key'] ) : ?>
			<tr>
				<td><strong><?php _e( 'Payment Key', 'pdd' ); ?>:</strong></td>
				<td><?php echo get_post_meta( $payment->ID, '_pdd_payment_purchase_key', true ); ?></td>
			</tr>
		<?php endif; ?>

		<?php if ( $pdd_receipt_args['payment_method'] ) : ?>
			<tr>
				<td><strong><?php _e( 'Payment Method', 'pdd' ); ?>:</strong></td>
				<td><?php echo pdd_get_gateway_checkout_label( pdd_get_payment_gateway( $payment->ID ) ); ?></td>
			</tr>
		<?php endif; ?>
		<?php if ( $pdd_receipt_args['date'] ) : ?>
			<tr>
				<td><strong><?php _e( 'Date', 'pdd' ); ?>:</strong></td>
				<td><?php echo date_i18n( get_option( 'date_format' ), strtotime( $meta['date'] ) ); ?></td>
			</tr>
		<?php endif; ?>

		<?php do_action( 'pdd_payment_receipt_after', $payment, $pdd_receipt_args ); ?>
		</tbody>
	</table>

<?php do_action( 'pdd_payment_receipt_after_table', $payment, $pdd_receipt_args ); ?>