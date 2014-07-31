<?php
/**
 * Manual Gateway
 *
 * @package     PDD
 * @subpackage  Gateways
 * @copyright   Copyright (c) 2014, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

/**
 * Manual Gateway does not need a CC form, so remove it.
 *
 * @since 1.0
 * @return void
 */
add_action( 'pdd_manual_cc_form', '__return_false' );

/**
 * Processes the purchase data and uses the Manual Payment gateway to record
 * the transaction in the Purchase History
 *
 * @since 1.0
 * @global $pdd_options Array of all the PDD Options
 * @param array $purchase_data Purchase Data
 * @return void
*/
function pdd_manual_payment( $purchase_data ) {
	global $pdd_options;

	/*
	* Purchase data comes in like this
	*
	$purchase_data = array(
		'campaigns' => array of download IDs,
		'price' => total price of cart contents,
		'purchase_key' =>  // Random key
		'user_email' => $user_email,
		'date' => date('Y-m-d H:i:s'),
		'user_id' => $user_id,
		'post_data' => $_POST,
		'user_info' => array of user's information and used discount code
		'cart_details' => array of cart details,
	);
	*/

	$payment_data = array(
		'price' 		=> $purchase_data['price'],
		'date' 			=> $purchase_data['date'],
		'user_email' 	=> $purchase_data['user_email'],
		'purchase_key' 	=> $purchase_data['purchase_key'],
		'currency' 		=> pdd_get_currency(),
		'campaigns' 	=> $purchase_data['campaigns'],
		'user_info' 	=> $purchase_data['user_info'],
		'cart_details' 	=> $purchase_data['cart_details'],
		'status' 		=> 'pending'
	);

	// Record the pending payment
	$payment = pdd_insert_payment( $payment_data );

	if ( $payment ) {
		pdd_update_payment_status( $payment, 'publish' );
		// Empty the shopping cart
		pdd_empty_cart();
		pdd_send_to_success_page();
	} else {
		pdd_record_gateway_error( __( 'Payment Error', 'pdd' ), sprintf( __( 'Payment creation failed while processing a manual (free or test) purchase. Payment data: %s', 'pdd' ), json_encode( $payment_data ) ), $payment );
		// If errors are present, send the user back to the purchase page so they can be corrected
		pdd_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['pdd-gateway'] );
	}
}
add_action( 'pdd_gateway_manual', 'pdd_manual_payment' );
