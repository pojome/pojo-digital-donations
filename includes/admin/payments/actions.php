<?php
/**
 * Admin Payment Actions
 *
 * @package     PDD
 * @subpackage  Admin/Payments
 * @copyright   Copyright (c) 2014, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.9
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Process the payment details edit
 *
 * @access      private
 * @since       1.9
 * @return      void
*/
function pdd_update_payment_details( $data ) {

	if( ! current_user_can( 'edit_shop_payment', $data['pdd_payment_id' ] ) ) {
		wp_die( __( 'You do not have permission to edit this payment record', 'pdd' ), __( 'Error', 'pdd' ) );
	}

	check_admin_referer( 'pdd_update_payment_details_nonce' );

	// Retrieve the payment ID
	$payment_id = absint( $data['pdd_payment_id'] );

	// Retrieve existing payment meta
	$meta       = pdd_get_payment_meta( $payment_id );
	$user_info  = pdd_get_payment_meta_user_info( $payment_id );

	$status     = $data['pdd-payment-status'];
	$unlimited  = isset( $data['pdd-unlimited-downloads'] ) ? '1' : '';
	$user_id    = intval( $data['pdd-payment-user-id'] );
	$date       = sanitize_text_field( $data['pdd-payment-date'] );
	$hour       = sanitize_text_field( $data['pdd-payment-time-hour'] );
	$minute     = sanitize_text_field( $data['pdd-payment-time-min'] );
	$email      = sanitize_text_field( $data['pdd-payment-user-email'] );
	$names      = sanitize_text_field( $data['pdd-payment-user-name'] );
	$address    = array_map( 'trim', $data['pdd-payment-address'][0] );

	$total      = pdd_sanitize_amount( $_POST['pdd-payment-total'] );
	$tax        = isset( $_POST['pdd-payment-tax'] ) ? pdd_sanitize_amount( $_POST['pdd-payment-tax'] ) : 0;

	// Setup date from input values
	$date       = date( 'Y-m-d', strtotime( $date ) ) . ' ' . $hour . ':' . $minute . ':00';

	// Setup first and last name from input values
	$names      = explode( ' ', $names );
	$first_name = ! empty( $names[0] ) ? $names[0] : '';
	if( ! empty( $names[1] ) ) {
		unset( $names[0] );
		$last_name = implode( ' ', $names );
	}

	// Setup purchased Downloads and price options
	$updated_downloads = isset( $_POST['pdd-payment-details-downloads'] ) ? $_POST['pdd-payment-details-downloads'] : false;
	if( $updated_downloads && ! empty( $_POST['pdd-payment-downloads-changed'] ) ) {
		$downloads    = array();
		$cart_details = array();
		$i = 0;
		foreach( $updated_downloads as $download ) {
			$item             = array();
			$item['id']       = absint( $download['id'] );
			$item['quantity'] = absint( $download['quantity'] );
			$price_id         = (int) $download['price_id'];

			if( $price_id !== false && pdd_has_variable_prices( $item['id'] ) ) {
				$item['options'] = array(
					'price_id'   => $price_id
				);
			}
			$downloads[] = $item;

			$cart_item   = array();
			$cart_item['item_number'] = $item;

			$cart_details[$i] = array(
				'name'        => get_the_title( $download['id'] ),
				'id'          => $download['id'],
				'item_number' => $item,
				'price'       => $download['amount'],
				'item_price'  => round( $download['amount'] / $download['quantity'], 2 ),
				'quantity'    => $download['quantity'],
				'discount'    => 0,
				'tax'         => 0,
			);
			$i++;
		}

		$meta['downloads']    = $downloads;
		$meta['cart_details'] = $cart_details;
	}

	// Set new meta values
	$user_info['id']         = $user_id;
	$user_info['email']      = $email;
	$user_info['first_name'] = $first_name;
	$user_info['last_name']  = $last_name;
	$user_info['address']    = $address;
	$meta['user_info']       = $user_info;
	$meta['tax']             = $tax;

	// Check for payment notes
	if ( ! empty( $data['pdd-payment-note'] ) ) {

		$note  = wp_kses( $data['pdd-payment-note'], array() );
		pdd_insert_payment_note( $payment_id, $note );

	}

	do_action( 'pdd_update_edited_purchase', $payment_id );

	// Update main payment record
	wp_update_post( array(
		'ID'        => $payment_id,
		'post_date' => $date
	) );

	// Set new status
	pdd_update_payment_status( $payment_id, $status );

	update_post_meta( $payment_id, '_pdd_payment_user_id',             $user_id );
	update_post_meta( $payment_id, '_pdd_payment_user_email',          $email   );
	update_post_meta( $payment_id, '_pdd_payment_meta',                $meta    );
	update_post_meta( $payment_id, '_pdd_payment_total',               $total   );
	update_post_meta( $payment_id, '_pdd_payment_downloads',           $total   );
	update_post_meta( $payment_id, '_pdd_payment_unlimited_downloads', $unlimited );

	do_action( 'pdd_updated_edited_purchase', $payment_id );

	wp_safe_redirect( admin_url( 'edit.php?post_type=download&page=pdd-payment-history&view=view-order-details&pdd-message=payment-updated&id=' . $payment_id ) );
	exit;
}
add_action( 'pdd_update_payment_details', 'pdd_update_payment_details' );

function pdd_ajax_store_payment_note() {

	$payment_id = absint( $_POST['payment_id'] );
	$note       = wp_kses( $_POST['note'], array() );

	if( empty( $payment_id ) )
		die( '-1' );

	if( empty( $note ) )
		die( '-1' );

	$note_id = pdd_insert_payment_note( $payment_id, $note );
	die( pdd_get_payment_note_html( $note_id ) );
}
add_action( 'wp_ajax_pdd_insert_payment_note', 'pdd_ajax_store_payment_note' );

/**
 * Triggers a payment note deletion without ajax
 *
 * @since 1.6
 * @param array $data Arguments passed
 * @return void
*/
function pdd_trigger_payment_note_deletion( $data ) {

	if( ! wp_verify_nonce( $data['_wpnonce'], 'pdd_delete_payment_note_' . $data['note_id'] ) )
		return;

	if( ! current_user_can( 'edit_shop_payment', $data['payment_id' ] ) ) {
		wp_die( __( 'You do not have permission to edit this payment record', 'pdd' ), __( 'Error', 'pdd' ) );
	}

	$edit_order_url = admin_url( 'edit.php?post_type=download&page=pdd-payment-history&view=view-order-details&pdd-message=payment-note-deleted&id=' . absint( $data['payment_id'] ) );

	pdd_delete_payment_note( $data['note_id'], $data['payment_id'] );

	wp_redirect( $edit_order_url );
}
add_action( 'pdd_delete_payment_note', 'pdd_trigger_payment_note_deletion' );

/**
 * Delete a payment note deletion with ajax
 *
 * @since 1.6
 * @param array $data Arguments passed
 * @return void
*/
function pdd_ajax_delete_payment_note() {

	if( ! current_user_can( 'edit_shop_payment', $_POST['payment_id' ] ) ) {
		wp_die( __( 'You do not have permission to edit this payment record', 'pdd' ), __( 'Error', 'pdd' ) );
	}

	if( pdd_delete_payment_note( $_POST['note_id'], $_POST['payment_id'] ) ) {
		die( '1' );
	} else {
		die( '-1' );
	}

}
add_action( 'wp_ajax_pdd_delete_payment_note', 'pdd_ajax_delete_payment_note' );

/**
 * Retrieves a new download link for a purchased file
 *
 * @since 2.0
 * @return string
*/
function pdd_ajax_generate_file_download_link() {

	if( ! current_user_can( 'view_shop_reports' ) ) {
		die( '-1' );
	}

	$payment_id  = absint( $_POST['payment_id'] );
	$download_id = absint( $_POST['download_id'] );
	$price_id    = absint( $_POST['price_id'] );

	if( empty( $payment_id ) )
		die( '-2' );

	if( empty( $download_id ) )
		die( '-3' );

	$payment_key = pdd_get_payment_key( $payment_id );
	$email       = pdd_get_payment_user_email( $payment_id );

	$limit = pdd_get_file_download_limit( $download_id );
	if ( ! empty( $limit ) ) {
		// Increase the file download limit when generating new links
		pdd_set_file_download_limit_override( $download_id, $payment_id );
	}

	$files = pdd_get_download_files( $download_id, $price_id );
	if( ! $files ) {
		die( '-4' );
	}

	$file_urls = '';

	foreach( $files as $file_key => $file ) {

		$file_urls .= pdd_get_download_file_url( $payment_key, $email, $file_key, $download_id, $price_id );
		$file_urls .= "\n\n";

	}

	die( $file_urls );

}
add_action( 'wp_ajax_pdd_get_file_download_link', 'pdd_ajax_generate_file_download_link' );
