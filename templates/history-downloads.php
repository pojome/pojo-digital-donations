<?php
// Retrieve all purchases for the current user
$purchases = pdd_get_users_purchases( get_current_user_id(), 20, true, 'any' );
if ( $purchases ) :
	do_action( 'pdd_before_download_history' ); ?>
	<table id="pdd_user_history">
		<thead>
			<tr class="pdd_download_history_row">
				<?php do_action( 'pdd_download_history_header_start' ); ?>
				<th class="pdd_download_download_name"><?php _e( 'Download Name', 'pdd' ); ?></th>
				<?php if ( ! pdd_no_redownload() ) : ?>
					<th class="pdd_download_download_files"><?php _e( 'Files', 'pdd' ); ?></th>
				<?php endif; //End if no redownload?>
				<?php do_action( 'pdd_download_history_header_end' ); ?>
			</tr>
		</thead>
		<?php foreach ( $purchases as $payment ) :
			$downloads      = pdd_get_payment_meta_cart_details( $payment->ID, true );
			$purchase_data  = pdd_get_payment_meta( $payment->ID );
			$email          = pdd_get_payment_user_email( $payment->ID );

			if ( $downloads ) :
				foreach ( $downloads as $download ) :

					// Skip over Bundles. Products included with a bundle will be displayed individually
					if ( pdd_is_bundled_product( $download['id'] ) )
						continue; ?>

					<tr class="pdd_download_history_row">
						<?php
						$price_id 		= pdd_get_cart_item_price_id( $download );
						$download_files = pdd_get_download_files( $download['id'], $price_id );
						$name           = get_the_title( $download['id'] );

						// Retrieve and append the price option name
						if ( ! empty( $price_id ) ) {
							$name .= ' - ' . pdd_get_price_option_name( $download['id'], $price_id, $payment->ID );
						}

						do_action( 'pdd_download_history_row_start', $payment->ID, $download['id'] );
						?>
						<td class="pdd_download_download_name"><?php echo esc_html( $name ); ?></td>

						<?php if ( ! pdd_no_redownload() ) : ?>
							<td class="pdd_download_download_files">
								<?php

								if ( pdd_is_payment_complete( $payment->ID ) ) :

									if ( $download_files ) :

										foreach ( $download_files as $filekey => $file ) :

											$download_url = pdd_get_download_file_url( $purchase_data['key'], $email, $filekey, $download['id'], $price_id );
											?>

											<div class="pdd_download_file">
												<a href="<?php echo esc_url( $download_url ); ?>" class="pdd_download_file_link">
													<?php echo esc_html( $file['name'] ); ?>
												</a>
											</div>

											<?php do_action( 'pdd_download_history_files', $filekey, $file, $id, $payment->ID, $purchase_data );
										endforeach;

									else :
										_e( 'No downloadable files found.', 'pdd' );
									endif; // End if payment complete

								else : ?>
									<span class="pdd_download_payment_status">
										<?php printf( __( 'Payment status is %s', 'pdd' ), pdd_get_payment_status( $payment, true) ); ?>
									</span>
									<?php
								endif; // End if $download_files
								?>
							</td>
						<?php endif; // End if ! pdd_no_redownload()

						do_action( 'pdd_download_history_row_end', $payment->ID, $download['id'] );
						?>
					</tr>
					<?php
				endforeach; // End foreach $downloads
			endif; // End if $downloads
		endforeach;
		?>
	</table>
	<div id="pdd_download_history_pagination" class="pdd_pagination navigation">
		<?php
		$big = 999999;
		echo paginate_links( array(
			'base'    => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
			'format'  => '?paged=%#%',
			'current' => max( 1, get_query_var( 'paged' ) ),
			'total'   => ceil( pdd_count_purchases_of_customer() / 20 ) // 20 items per page
		) );
		?>
	</div>
	<?php
	do_action( 'pdd_after_download_history' );
else : ?>
	<p class="pdd-no-downloads"><?php _e( 'You have not purchased any downloads', 'pdd' ); ?></p>
	<?php
endif;
