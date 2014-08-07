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
<table id="pdd_purchase_receipt">
	<thead>
		<?php do_action( 'pdd_payment_receipt_before', $payment, $pdd_receipt_args ); ?>

		<?php if ( $pdd_receipt_args['payment_id'] ) : ?>
		<tr>
			<th><strong><?php _e( 'Payment', 'pdd' ); ?>:</strong></th>
			<th><?php echo pdd_get_payment_number( $payment->ID ); ?></th>
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

		<?php if ( $pdd_receipt_args[ 'price' ] ) : ?>
			
			<tr>
				<td><strong><?php _e( 'Total Price', 'pdd' ); ?>:</strong></td>
				<td><?php echo pdd_payment_amount( $payment->ID ); ?></td>
			</tr>

		<?php endif; ?>

		<?php do_action( 'pdd_payment_receipt_after', $payment, $pdd_receipt_args ); ?>
	</tbody>
</table>

<?php do_action( 'pdd_payment_receipt_after_table', $payment, $pdd_receipt_args ); ?>

<?php if ( $pdd_receipt_args[ 'products' ] ) : ?>

	<h3><?php echo apply_filters( 'pdd_payment_receipt_products_title', __( 'Products', 'pdd' ) ); ?></h3>

	<table id="pdd_purchase_receipt_products">
		<thead>
			<th><?php _e( 'Name', 'pdd' ); ?></th>
			<?php if ( pdd_use_skus() ) { ?>
				<th><?php _e( 'SKU', 'pdd' ); ?></th>
			<?php } ?>
			<?php if ( pdd_item_quantities_enabled() ) : ?>
				<th><?php _e( 'Quantity', 'pdd' ); ?></th>
			<?php endif; ?>
			<th><?php _e( 'Price', 'pdd' ); ?></th>
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

						<?php
						if( pdd_is_payment_complete( $payment->ID ) && pdd_receipt_show_download_files( $item['id'], $pdd_receipt_args ) ) : ?>
						<ul class="pdd_purchase_receipt_files">
							<?php
							if ( $download_files && is_array( $download_files ) ) :

								foreach ( $download_files as $filekey => $file ) :

									$download_url = pdd_get_download_file_url( $meta['key'], $email, $filekey, $item['id'], $price_id );
									?>
									<li class="pdd_camp_file">
										<a href="<?php echo esc_url( $download_url ); ?>" download="<?php echo pdd_get_file_name( $file ); ?>" class="pdd_camp_file_link"><?php echo pdd_get_file_name( $file ); ?></a>
									</li>
									<?php
									do_action( 'pdd_receipt_files', $filekey, $file, $item['id'], $payment->ID, $meta );
								endforeach;

							elseif( pdd_is_bundled_product( $item['id'] ) ) :

								$bundled_products = pdd_get_bundled_products( $item['id'] );

								foreach( $bundled_products as $bundle_item ) : ?>
									<li class="pdd_bundled_product">
										<span class="pdd_bundled_product_name"><?php echo get_the_title( $bundle_item ); ?></span>
										<ul class="pdd_bundled_product_files">
											<?php
											$download_files = pdd_get_download_files( $bundle_item );

											if( $download_files && is_array( $download_files ) ) :

												foreach ( $download_files as $filekey => $file ) :

													$download_url = pdd_get_download_file_url( $meta['key'], $email, $filekey, $bundle_item ); ?>
													<li class="pdd_camp_file">
														<a href="<?php echo esc_url( $download_url ); ?>" class="pdd_camp_file_link"><?php echo esc_html( $file['name'] ); ?></a>
													</li>
													<?php
													do_action( 'pdd_receipt_bundle_files', $filekey, $file, $item['id'], $bundle_item, $payment->ID, $meta );

												endforeach;
											else :
												echo '<li>' . __( 'No downloadable files found for this bundled item.', 'pdd' ) . '</li>';
											endif;
											?>
										</ul>
									</li>
									<?php
								endforeach;

							else :
								echo '<li>' . __( 'No downloadable files found.', 'pdd' ) . '</li>';
							endif; ?>
						</ul>
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

	</table>
<?php endif; ?>
