<?php
/**
 * View Order Details
 *
 * @package     PDD
 * @subpackage  Admin/Payments
 * @copyright   Copyright (c) 2014, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.6
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * View Order Details Page
 *
 * @since 1.6
 * @return void
*/
if ( ! isset( $_GET['id'] ) || ! is_numeric( $_GET['id'] ) ) {
	wp_die( __( 'Payment ID not supplied. Please try again', 'pdd' ), __( 'Error', 'pdd' ) );
}

// Setup the variables
$payment_id   = absint( $_GET['id'] );
$number       = pdd_get_payment_number( $payment_id );
$item         = get_post( $payment_id );

// Sanity check... fail if purchase ID is invalid
if ( !is_object( $item ) || $item->post_type != 'pdd_payment' ) {
    wp_die( __( 'The specified ID does not belong to a payment. Please try again', 'pdd' ), __( 'Error', 'pdd' ) );
}

$payment_meta = pdd_get_payment_meta( $payment_id );
$cart_items   = pdd_get_payment_meta_cart_details( $payment_id );
$user_id      = pdd_get_payment_user_id( $payment_id );
$payment_date = strtotime( $item->post_date );
$unlimited    = pdd_payment_has_unlimited_downloads( $payment_id );
$user_info    = pdd_get_payment_meta_user_info( $payment_id );
$address      = ! empty( $user_info['address'] ) ? $user_info['address'] : array( 'line1' => '', 'line2' => '', 'city' => '', 'country' => '', 'state' => '', 'zip' => '' );
?>
<div class="wrap pdd-wrap">
	<h2><?php printf( __( 'Payment %s', 'pdd' ), $number ); ?></h2>
	<?php do_action( 'pdd_view_order_details_before', $payment_id ); ?>
	<form id="pdd-edit-order-form" method="post">
		<?php do_action( 'pdd_view_order_details_form_top', $payment_id ); ?>
		<div id="poststuff">
			<div id="pdd-dashboard-widgets-wrap">
				<div id="post-body" class="metabox-holder columns-2">
					<div id="postbox-container-1" class="postbox-container">
						<div id="side-sortables" class="meta-box-sortables ui-sortable">
							<?php do_action( 'pdd_view_order_details_sidebar_before', $payment_id ); ?>
							
							<div id="pdd-order-totals" class="postbox">
								<h3 class="hndle">
									<span><?php _e( 'Payment Totals', 'pdd' ); ?></span>
								</h3>
								<div class="inside">
									<div class="pdd-order-totals-box pdd-admin-box">
										<?php do_action( 'pdd_view_order_details_totals_before', $payment_id ); ?>
										<div class="pdd-order-payment pdd-admin-box-inside">
											<p>
												<span class="label"><?php _e( 'Total Price', 'pdd' ); ?>:</span>&nbsp;
												<input name="pdd-payment-total" type="number" step="0.01" class="small-text right" value="<?php echo esc_attr( pdd_get_payment_amount( $payment_id ) ); ?>"/>
											</p>
										</div>
										<div class="pdd-order-payment-recalc-totals pdd-admin-box-inside" style="display:none">
											<p>
												<span class="label"><?php _e( 'Recalculate Totals', 'pdd' ); ?>:</span>&nbsp;
												<a href="#" id="pdd-order-recalc-total" class="button button-secondary right"><?php _e( 'Recalculate', 'pdd' ); ?></a>
											</p>
										</div>
										<?php do_action( 'pdd_view_order_details_totals_after', $payment_id ); ?>
									</div><!-- /.pdd-order-totals-box -->
								</div><!-- /.inside -->
							</div><!-- /#pdd-order-totals -->
	
							<div id="pdd-order-update" class="postbox pdd-order-data">
								
								<h3 class="hndle">
									<span><?php _e( 'Update Payment', 'pdd' ); ?></span>
								</h3>
								<div class="inside">
									<div class="pdd-admin-box">
	
										<div class="pdd-admin-box-inside">
	
											<?php
											$gateway = pdd_get_payment_gateway( $payment_id );
											if ( $gateway ) { ?>
											<p>
												<strong><?php _e( 'Gateway:', 'pdd' ); ?></strong>&nbsp;
												<span><?php echo pdd_get_gateway_admin_label( $gateway ); ?></span>
											</p>
											<?php } ?>
	
											<p>
												<strong><?php _e( 'Key:', 'pdd' ); ?></strong>&nbsp;
												<span><?php echo pdd_get_payment_key( $payment_id ); ?></span>
											</p>
	
											<p>
												<strong><?php _e( 'IP:', 'pdd' ); ?></strong>&nbsp;
												<span><?php esc_attr_e( pdd_get_payment_user_ip( $payment_id )); ?></span>
											</p>
	
										</div>
	
										<div class="pdd-admin-box-inside">
											<p>
												<span class="label"><?php _e( 'Status:', 'pdd' ); ?></span>&nbsp;
												<select name="pdd-payment-status" class="medium-text">
													<?php foreach( pdd_get_payment_statuses() as $key => $status ) : ?>
														<option value="<?php esc_attr_e( $key ); ?>"<?php selected( pdd_get_payment_status( $item, true ), $status ); ?>><?php esc_html_e( $status ); ?></option>
													<?php endforeach; ?>
												</select>
											</p>
										</div>
										
										<div class="pdd-admin-box-inside">
											<p>
												<span class="label"><?php _e( 'Date:', 'pdd' ); ?></span>&nbsp;
												<input type="text" name="pdd-payment-date" value="<?php esc_attr_e( date( 'm/d/Y', $payment_date ) ); ?>" class="medium-text pdd_datepicker"/>
											</p>
										</div>
	
										<div class="pdd-admin-box-inside">
											<p>
												<span class="label"><?php _e( 'Time:', 'pdd' ); ?></span>&nbsp;
												<input type="number" step="1" max="24" name="pdd-payment-time-hour" value="<?php esc_attr_e( date_i18n( 'H', $payment_date ) ); ?>" class="small-text pdd-payment-time-hour"/>&nbsp;:&nbsp;
												<input type="number" step="1" max="59" name="pdd-payment-time-min" value="<?php esc_attr_e( date( 'i', $payment_date ) ); ?>" class="small-text pdd-payment-time-min"/>
											</p>
										</div>

										<?php do_action( 'pdd_view_order_details_update_inner', $payment_id ); ?>
	
									</div><!-- /.column-container -->
	
								</div><!-- /.inside -->
	
								<div class="pdd-order-update-box pdd-admin-box">
									<?php do_action( 'pdd_view_order_details_update_before', $payment_id ); ?>
									<div id="major-publishing-actions">
										<div id="publishing-action">
											<input type="submit" class="button button-primary right" value="<?php esc_attr_e( 'Save Payment', 'pdd' ); ?>"/>
											<?php if( pdd_is_payment_complete( $payment_id ) ) : ?>
												<a href="<?php echo add_query_arg( array( 'pdd-action' => 'email_links', 'purchase_id' => $payment_id ) ); ?>" id="pdd-resend-receipt" class="button-secondary right"><?php _e( 'Resend Receipt', 'pdd' ); ?></a>
											<?php endif; ?>
										</div>
										<div class="clear"></div>
									</div>
									<?php do_action( 'pdd_view_order_details_update_after', $payment_id ); ?>
								</div><!-- /.pdd-order-update-box -->
	
							</div><!-- /#pdd-order-data -->
							
							<?php do_action( 'pdd_order_update_after', $payment_id ); ?>
	
							<?php do_action( 'pdd_view_order_details_sidebar_after', $payment_id ); ?>
						</div><!-- /#side-sortables -->
					</div><!-- /#postbox-container-1 -->
	
					<div id="postbox-container-2" class="postbox-container">
						<div id="normal-sortables" class="meta-box-sortables ui-sortable">
	
							<?php do_action( 'pdd_view_order_details_main_before', $payment_id ); ?>
	
							<div id="pdd-customer-details" class="postbox">
								<h3 class="hndle">
									<span><?php _e( 'Customer Details', 'pdd' ); ?></span>
								</h3>
								<div class="inside pdd-clearfix">
	
									<div class="column-container">
										<div class="column">
											<strong><?php _e( 'Name:', 'pdd' ); ?></strong>&nbsp;
											<input type="text" name="pdd-payment-user-name" value="<?php esc_attr_e( $user_info['first_name'] . ' ' . $user_info['last_name'] ); ?>" class="medium-text"/>
										</div>
										<div class="column">
											<strong><?php _e( 'Email:', 'pdd' ); ?></strong>&nbsp;
											<input type="email" name="pdd-payment-user-email" value="<?php esc_attr_e( pdd_get_payment_user_email( $payment_id ) ); ?>" class="medium-text"/>
										</div>
										<div class="column">
											<strong><?php _e( 'User ID:', 'pdd' ); ?></strong>&nbsp;
											<input type="number" step="1" min="-1" name="pdd-payment-user-id" value="<?php esc_attr_e( $user_id ); ?>" class="small-text"/>
										</div>
									</div>
	
									<?php 
									// The pdd_payment_personal_details_list hook is left here for backwards compatibility
									do_action( 'pdd_payment_personal_details_list', $payment_meta, $user_info );
									do_action( 'pdd_payment_view_details', $payment_id );
									?>
	
								</div><!-- /.inside -->
							</div><!-- /#pdd-customer-details -->
	
							<?php do_action( 'pdd_view_order_details_billing_before', $payment_id ); ?>
	
							<div id="pdd-billing-details" class="postbox">
								<h3 class="hndle">
									<span><?php _e( 'Billing Address', 'pdd' ); ?></span>
								</h3>
								<div class="inside pdd-clearfix">
	
									<div id="pdd-order-address">
	
										<div class="order-data-address">
											<div class="data column-container">
												<div class="column">
													<p>
														<strong class="order-data-address-line"><?php _e( 'Street Address Line 1:', 'pdd' ); ?></strong><br/>
														<input type="text" name="pdd-payment-address[0][line1]" value="<?php esc_attr_e( $address['line1'] ); ?>" class="medium-text" />
													</p>
													<p>
														<strong class="order-data-address-line"><?php _e( 'Street Address Line 2:', 'pdd' ); ?></strong><br/>
														<input type="text" name="pdd-payment-address[0][line2]" value="<?php esc_attr_e( $address['line2'] ); ?>" class="medium-text" />
													</p>
														
												</div>
												<div class="column">
													<p>
														<strong class="order-data-address-line"><?php echo _x( 'City:', 'Address City', 'pdd' ); ?></strong><br/>
														<input type="text" name="pdd-payment-address[0][city]" value="<?php esc_attr_e( $address['city'] ); ?>" class="medium-text"/>
														
													</p>
													<p>
														<strong class="order-data-address-line"><?php echo _x( 'Zip / Postal Code:', 'Zip / Postal code of address', 'pdd' ); ?></strong><br/>
														<input type="text" name="pdd-payment-address[0][zip]" value="<?php esc_attr_e( $address['zip'] ); ?>" class="medium-text"/>
														
													</p>
												</div>
												<div class="column">
													<p id="pdd-order-address-country-wrap">
														<strong class="order-data-address-line"><?php echo _x( 'Country:', 'Address country', 'pdd' ); ?></strong><br/>
														<?php
														echo PDD()->html->select( array(
															'options'          => pdd_get_country_list(),
															'name'             => 'pdd-payment-address[0][country]',
															'selected'         => $address['country'],
															'show_option_all'  => false,
															'show_option_none' => false
														) );
														?>
													</p>
													<p id="pdd-order-address-state-wrap">
														<strong class="order-data-address-line"><?php echo _x( 'State / Province:', 'State / province of address', 'pdd' ); ?></strong><br/>
														<?php
														$states = pdd_get_shop_states( $address['country'] );
														if( ! empty( $states ) ) {
															echo PDD()->html->select( array(
																'options'          => $states,
																'name'             => 'pdd-payment-address[0][state]',
																'selected'         => $address['state'],
																'show_option_all'  => false,
																'show_option_none' => false
															) );
														} else { ?>
															<input type="text" name="pdd-payment-address[0][state]" value="<?php esc_attr_e( $address['state'] ); ?>" class="medium-text"/>
															<?php
														} ?>
													</p>
												</div>
											</div>
										</div>
									</div><!-- /#pdd-order-address -->
	
									<?php do_action( 'pdd_payment_billing_details', $payment_id ); ?>
	
								</div><!-- /.inside -->
							</div><!-- /#pdd-billing-details -->
	
							<?php do_action( 'pdd_view_order_details_billing_after', $payment_id ); ?>
	
							<?php $column_count = pdd_item_quantities_enabled() ? 'columns-4' : 'columns-3'; ?>
							<div id="pdd-purchased-files" class="postbox <?php echo $column_count; ?>">
								<h3 class="hndle">
									<span><?php printf( __( 'Purchased %s', 'pdd' ), pdd_get_label_plural() ); ?></span>
								</h3>
								
								<?php if ( $cart_items ) :
									$i = 0;
									foreach ( $cart_items as $key => $cart_item ) : ?>
									<div class="row">
										<ul>
											<?php
											// Item ID is checked if isset due to the near-1.0 cart data
											$item_id  = isset( $cart_item['id']    ) ? $cart_item['id']    : $cart_item;
											$price    = isset( $cart_item['price'] ) ? $cart_item['price'] : false;
											$price_id = isset( $cart_item['item_number']['options']['price_id'] ) ? $cart_item['item_number']['options']['price_id'] : null;
											$quantity = isset( $cart_item['quantity'] ) && $cart_item['quantity'] > 0 ? $cart_item['quantity'] : 1;
	
											if( ! $price ) {
												// This function is only used on payments with near 1.0 cart data structure
												$price = pdd_get_download_final_price( $item_id, $user_info, null );
											}
											?>
	
											<li class="download">
												<span>
													<a href="<?php echo admin_url( 'post.php?post=' . $item_id . '&action=edit' ); ?>">
														<?php echo get_the_title( $item_id );

														if ( isset( $cart_items[ $key ]['item_number'] ) && isset( $cart_items[ $key ]['item_number']['options'] ) ) {
															$price_options = $cart_items[ $key ]['item_number']['options'];

															if ( isset( $price_id ) ) {
																echo ' - ' . pdd_get_price_option_name( $item_id, $price_id, $payment_id );
															}
														}
														?>
													</a>
												</span>
												<input type="hidden" name="pdd-payment-details-downloads[<?php echo $key; ?>][id]" class="pdd-payment-details-download-id" value="<?php echo esc_attr( $item_id ); ?>"/>
												<input type="hidden" name="pdd-payment-details-downloads[<?php echo $key; ?>][price_id]" class="pdd-payment-details-download-price-id" value="<?php echo esc_attr( $price_id ); ?>"/>
												<input type="hidden" name="pdd-payment-details-downloads[<?php echo $key; ?>][amount]" class="pdd-payment-details-download-amount" value="<?php echo esc_attr( $price ); ?>"/>
												<input type="hidden" name="pdd-payment-details-downloads[<?php echo $key; ?>][quantity]" class="pdd-payment-details-download-quantity" value="<?php echo esc_attr( $quantity ); ?>"/>
												
											</li>
	
											<?php if( pdd_item_quantities_enabled() ) : ?>
											<li class="quantity">
												<?php echo __( 'Quantity:', 'pdd' ) . '&nbsp;<span>' . $quantity . '</span>'; ?>
											</li>
											<?php endif; ?>
	
											<li class="price">
												<?php echo pdd_currency_filter( pdd_format_amount( $price ) ); ?>
											</li>
	
											<li class="actions">
												<?php if( pdd_get_download_files( $item_id, $price_id ) ) : ?>
													<a href="" class="pdd-copy-download-link" data-download-id="<?php echo esc_attr( $item_id ); ?>" data-price-id="<?php echo esc_attr( $price_id ); ?>"><?php _e( 'Copy Download Link(s)', 'pdd' ); ?></a> | 
												<?php endif; ?>
												<a href="" class="pdd-order-remove-download pdd-delete" data-key="<?php echo esc_attr( $key ); ?>"><?php _e( 'Remove', 'pdd' ); ?></a>
											</li>
										</ul>
									</div>
									<?php
									$i++;
									endforeach; ?>
									<div class="inside">
										<ul>
											<li class="download">
												<?php echo PDD()->html->product_dropdown( array(
													'name'   => 'pdd-order-download-select',
													'id'     => 'pdd-order-download-select',
													'chosen' => true
												) ); ?>
											</li>
		
											<?php if( pdd_item_quantities_enabled() ) : ?>
											<li class="quantity">
												<span><?php _e( 'Quantity', 'pdd' ); ?>:&nbsp;</span>
												<input type="number" id="pdd-order-download-quantity" class="small-text" min="1" step="1" value="1" />
											</li>
											<?php endif; ?>
		
											<li class="price">
												<?php
												echo PDD()->html->text( array( 'name' => 'pdd-order-download-amount',
													'label' => __( 'Amount: ', 'pdd' ),
													'class' => 'small-text pdd-order-download-price' 
												) );
												?>
											</li>
		
											<li class="actions">
												<a href="" id="pdd-order-add-download" class="button button-secondary"><?php printf( __( 'Add %s to Payment', 'pdd' ), pdd_get_label_singular() ); ?></a>
											</li>
		
										</ul>
									
										<input type="hidden" name="pdd-payment-downloads-changed" id="pdd-payment-downloads-changed" value=""/>
		
									</div><!-- /.inside -->
								<?php else : $key = 0; ?>
								<div class="row">
									<p><?php printf( __( 'No %s included with this purchase', 'pdd' ), pdd_get_label_plural() ); ?></p>
								</div>
								<?php endif; ?>
							</div><!-- /#pdd-purchased-files -->
	
							<?php do_action( 'pdd_view_order_details_files_after', $payment_id ); ?>
	
							<div id="pdd-payment-notes" class="postbox">
								<h3 class="hndle"><span><?php _e( 'Payment Notes', 'pdd' ); ?></span></h3>
								<div class="inside">
									<div id="pdd-payment-notes-inner">
										<?php
										$notes = pdd_get_payment_notes( $payment_id );
										if ( ! empty( $notes ) ) :
											$no_notes_display = ' style="display:none;"';
											foreach ( $notes as $note ) :
												
												echo pdd_get_payment_note_html( $note, $payment_id );
	
											endforeach;
										else :
											$no_notes_display = '';
										endif;
										echo '<p class="pdd-no-payment-notes"' . $no_notes_display . '>'. __( 'No payment notes', 'pdd' ) . '</p>';
										?>
									</div>
									<textarea name="pdd-payment-note" id="pdd-payment-note" class="large-text"></textarea>
									
									<p>
										<button id="pdd-add-payment-note" class="button button-secondary right" data-payment-id="<?php echo absint( $payment_id ); ?>"><?php _e( 'Add Note', 'pdd' ); ?></button>
									</p>
									
									<div class="clear"></div>
								</div><!-- /.inside -->
							</div><!-- /#pdd-payment-notes -->
	
							<?php do_action( 'pdd_view_order_details_main_after', $payment_id ); ?>
						</div><!-- /#normal-sortables -->
					</div><!-- #postbox-container-2 -->
				</div><!-- /#post-body -->
			</div><!-- #pdd-dashboard-widgets-wrap -->
		</div><!-- /#post-stuff -->
		<?php do_action( 'pdd_view_order_details_form_bottom', $payment_id ); ?>
		<?php wp_nonce_field( 'pdd_update_payment_details_nonce' ); ?>
		<input type="hidden" name="pdd_payment_id" value="<?php echo esc_attr( $payment_id ); ?>"/>
		<input type="hidden" name="pdd_action" value="update_payment_details"/>
	</form>
	<?php do_action( 'pdd_view_order_details_after', $payment_id ); ?>
</div><!-- /.wrap -->

<div id="pdd-download-link" title="<?php _e( 'Copy Download Link(s)', 'pdd' ); ?>"></div>