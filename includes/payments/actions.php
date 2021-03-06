<?php
/**
 * Payment Actions
 *
 * @package     PDD
 * @subpackage  Payments
 * @copyright   Copyright (c) 2014, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Complete a purchase
 *
 * Performs all necessary actions to complete a purchase.
 * Triggered by the pdd_update_payment_status() function.
 *
 * @since 1.0.8.3
 * @param int $payment_id the ID number of the payment
 * @param string $new_status the status of the payment, probably "publish"
 * @param string $old_status the status of the payment prior to being marked as "complete", probably "pending"
 * @return void
*/
function pdd_complete_purchase( $payment_id, $new_status, $old_status ) {
	if ( $old_status == 'publish' || $old_status == 'complete' )
		return; // Make sure that payments are only completed once

	// Make sure the payment completion is only processed when new status is complete
	if ( $new_status != 'publish' && $new_status != 'complete' )
		return;

	$completed_date = pdd_get_payment_completed_date( $payment_id );
	$user_info      = pdd_get_payment_meta_user_info( $payment_id );
	$amount         = pdd_get_payment_amount( $payment_id );
	$cart_details   = pdd_get_payment_meta_cart_details( $payment_id );

	do_action( 'pdd_pre_complete_purchase', $payment_id );

	if ( is_array( $cart_details ) ) {

		// Increase purchase count and earnings
		foreach ( $cart_details as $download ) {

			// "bundle" or "default"
			$download_type = pdd_get_download_type( $download['id'] );
			$price_id      = isset( $download['options']['price_id'] ) ? (int) $download['options']['price_id'] : false;

			$price_id      = isset( $download['options']['price_id'] ) ? (int) $download['options']['price_id'] : false;

			// Increase earnings and fire actions once per quantity number
			for( $i = 0; $i < $download['quantity']; $i++ ) {

				if ( ! pdd_is_test_mode() || apply_filters( 'pdd_log_test_payment_stats', false ) ) {

					pdd_record_sale_in_log( $download['id'], $payment_id, $price_id );
					pdd_increase_purchase_count( $download['id'] );
					pdd_increase_earnings( $download['id'], $download['price'] );

				}

				if( empty( $completed_date ) ) {
					// Ensure this action only runs once ever
					do_action( 'pdd_complete_download_purchase', $download['id'], $payment_id, $download_type, $download );
				}

			}

		}

		// Clear the total earnings cache
		delete_transient( 'pdd_earnings_total' );
		// Clear the This Month earnings (this_monththis_month is NOT a typo)
		delete_transient( md5( 'pdd_earnings_this_monththis_month' ) );
		delete_transient( md5( 'pdd_earnings_todaytoday' ) );
	}

	// Check for discount codes and increment their use counts
	if ( isset( $user_info['discount'] ) && $user_info['discount'] != 'none' ) {

		$discounts = array_map( 'trim', explode( ',', $user_info['discount'] ) );

		if( ! empty( $discounts ) ) {

			foreach( $discounts as $code ) {

				pdd_increase_discount_usage( $code );

			}

		}
	}

	pdd_increase_total_earnings( $amount );

	// Ensure this action only runs once ever
	if( empty( $completed_date ) ) {

		// Save the completed date
		update_post_meta( $payment_id, '_pdd_completed_date', current_time( 'mysql' ) );

		do_action( 'pdd_complete_purchase', $payment_id );
	}

	// Empty the shopping cart
	pdd_empty_cart();
}
add_action( 'pdd_update_payment_status', 'pdd_complete_purchase', 100, 3 );


/**
 * Record payment status change
 *
 * @since 1.4.3
 * @param int $payment_id the ID number of the payment
 * @param string $new_status the status of the payment, probably "publish"
 * @param string $old_status the status of the payment prior to being marked as "complete", probably "pending"
 * @return void
 */
function pdd_record_status_change( $payment_id, $new_status, $old_status ) {

	// Get the list of statuses so that status in the payment note can be translated
	$stati      = pdd_get_payment_statuses();
	$old_status = isset( $stati[ $old_status ] ) ? $stati[ $old_status ] : $old_status;
	$new_status = isset( $stati[ $new_status ] ) ? $stati[ $new_status ] : $new_status;

	$status_change = sprintf( __( 'Status changed from %s to %s', 'pdd' ), $old_status, $new_status );

	pdd_insert_payment_note( $payment_id, $status_change );
}
add_action( 'pdd_update_payment_status', 'pdd_record_status_change', 100, 3 );

/**
 * Reduces earnings and sales stats when a purchase is refunded
 *
 * @since 1.8.2
 * @param $data Arguments passed
 * @return void
 */
function pdd_undo_purchase_on_refund( $payment_id, $new_status, $old_status ) {

	if( 'publish' != $old_status && 'revoked' != $old_status )
		return;

	if( 'refunded' != $new_status )
		return;

	$downloads = pdd_get_payment_meta_cart_details( $payment_id );
	if( $downloads ) {
		foreach( $downloads as $download ) {
			pdd_undo_purchase( $download['id'], $payment_id );
		}
	}

	// Decrease store earnings
	$amount = pdd_get_payment_amount( $payment_id );
	pdd_decrease_total_earnings( $amount );

	// Clear the This Month earnings (this_monththis_month is NOT a typo)
	delete_transient( md5( 'pdd_earnings_this_monththis_month' ) );
}
add_action( 'pdd_update_payment_status', 'pdd_undo_purchase_on_refund', 100, 3 );


/**
 * Trigger a Purchase Deletion
 *
 * @since 1.3.4
 * @param $data Arguments passed
 * @return void
 */
function pdd_trigger_purchase_delete( $data ) {
	if ( wp_verify_nonce( $data['_wpnonce'], 'pdd_payment_nonce' ) ) {
		$payment_id = absint( $data['purchase_id'] );
		pdd_delete_purchase( $payment_id );
		wp_redirect( admin_url( '/edit.php?post_type=pdd_camp&page=pdd-payment-history&pdd-message=payment_deleted' ) );
		pdd_die();
	}
}
add_action( 'pdd_delete_payment', 'pdd_trigger_purchase_delete' );

/**
 * Flushes the current user's purchase history transient when a payment status
 * is updated
 *
 * @since 1.2.2
 *
 * @param $payment_id
 * @param $new_status the status of the payment, probably "publish"
 * @param $old_status the status of the payment prior to being marked as "complete", probably "pending"
 */
function pdd_clear_user_history_cache( $payment_id, $new_status, $old_status ) {
	$user_info = pdd_get_payment_meta_user_info( $payment_id );

	delete_transient( 'pdd_user_' . $user_info['id'] . '_purchases' );
}
add_action( 'pdd_update_payment_status', 'pdd_clear_user_history_cache', 10, 3 );

/**
 * Updates all old payments, prior to 1.2, with new
 * meta for the total purchase amount
 *
 * This is so that payments can be queried by their totals
 *
 * @since 1.2
 * @param array $data Arguments passed
 * @return void
*/
function pdd_update_old_payments_with_totals( $data ) {
	if ( ! wp_verify_nonce( $data['_wpnonce'], 'pdd_upgrade_payments_nonce' ) )
		return;

	if ( get_option( 'pdd_payment_totals_upgraded' ) )
		return;

	$payments = pdd_get_payments( array(
		'offset' => 0,
		'number' => -1,
		'mode'   => 'all'
	) );

	if ( $payments ) {
		foreach ( $payments as $payment ) {
			$meta = pdd_get_payment_meta( $payment->ID );
			update_post_meta( $payment->ID, '_pdd_payment_total', $meta['amount'] );
		}
	}

	add_option( 'pdd_payment_totals_upgraded', 1 );
}
add_action( 'pdd_upgrade_payments', 'pdd_update_old_payments_with_totals' );

/**
 * Updates week-old+ 'pending' orders to 'abandoned'
 *
 * @since 1.6
 * @return void
*/
function pdd_mark_abandoned_orders() {
	$args = array(
		'status' => 'pending',
		'number' => -1,
		'fields' => 'ids'
	);

	add_filter( 'posts_where', 'pdd_filter_where_older_than_week' );

	$payments = pdd_get_payments( $args );

	remove_filter( 'posts_where', 'pdd_filter_where_older_than_week' );

	if( $payments ) {
		foreach( $payments as $payment ) {
			pdd_update_payment_status( $payment, 'abandoned' );
		}
	}
}
add_action( 'pdd_weekly_scheduled_events', 'pdd_mark_abandoned_orders' );
