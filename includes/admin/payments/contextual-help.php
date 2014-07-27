<?php
/**
 * Contextual Help
 *
 * @package     PDD
 * @subpackage  Admin/Payments
 * @copyright   Copyright (c) 2014, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.4
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Payments contextual help.
 *
 * @access      private
 * @since       1.4
 * @return      void
 */
function pdd_payments_contextual_help() {
	$screen = get_current_screen();

	if ( $screen->id != 'download_page_pdd-payment-history' )
		return;

	$screen->set_help_sidebar(
		'<p><strong>' . sprintf( __( 'For more information:', 'pdd' ) . '</strong></p>' .
		'<p>' . sprintf( __( 'Visit the <a href="%s">documentation</a> on the Easy Digital Downloads website.', 'pdd' ), esc_url( 'https://easydigitaldownloads.com/documentation/' ) ) ) . '</p>' .
		'<p>' . sprintf(
					__( '<a href="%s">Post an issue</a> on <a href="%s">GitHub</a>. View <a href="%s">extensions</a> or <a href="%s">themes</a>.', 'pdd' ),
					esc_url( 'https://github.com/easydigitaldownloads/Easy-Digital-Downloads/issues' ),
					esc_url( 'https://github.com/easydigitaldownloads/Easy-Digital-Downloads' ),
					esc_url( 'https://easydigitaldownloads.com/extensions/' ),
					esc_url( 'https://easydigitaldownloads.com/themes/' )
				) . '</p>'
	);

	$screen->add_help_tab( array(
		'id'	    => 'pdd-payments-overview',
		'title'	    => __( 'Overview', 'pdd' ),
		'content'	=>
			'<p>' . __( "This screen provides access to all of your store's transactions.", 'pdd' ) . '</p>' . 
			'<p>' . __( 'Payments can be searched by email address, user name, or filtered by status (completed, pending, etc.)', 'pdd' ) . '</p>' .
			'<p>' . __( 'You also have the option to bulk delete payment should you wish.', 'pdd' ) . '</p>'
	) );

	$screen->add_help_tab( array(
		'id'	    => 'pdd-payments-search',
		'title'	    => __( 'Search Payments', 'pdd' ),
		'content'	=>
			'<p>' . __( 'The payment history can be searched in several different ways:', 'pdd' ) . '</p>' .
			'<ul>
				<li>' . __( 'You can enter the customer\'s email address', 'pdd' ) . '</li>
				<li>' . __( 'You can enter the customer\'s name or ID prefexed by \'user:\'', 'pdd' ) . '</li>
				<li>' . __( 'You can enter the 32-character purchase key', 'pdd' ) . '</li>
				<li>' . __( 'You can enter the purchase ID', 'pdd' ) . '</li>
				<li>' . __( 'You can enter a transaction ID prefixed by \'txn:\'', 'pdd' ) . '</li>
				<li>' . sprintf( __( 'You can enter the %s ID prefixed by \'#\'', 'pdd' ), pdd_get_label_singular() ) . '</li>
			</ul>'
	) );

	$screen->add_help_tab( array(
		'id'	    => 'pdd-payments-details',
		'title'	    => __( 'Payment Details', 'pdd' ),
		'content'	=>
			'<p>' . __( 'Each payment can be further inspected by clicking the corresponding <em>View Order Details</em> link. This will provide more information including:', 'pdd' ) . '</p>' . 

			'<ul>
				<li><strong>Purchased File</strong> - ' . __( 'The file associated with the purchase.', 'pdd' ) . '</li>
				<li><strong>Purchase Date</strong> - ' . __( 'The exact date and time the payment was completed.', 'pdd' ) . '</li>
				<li><strong>Discount Used</strong> - ' . __( 'If a coupon or discount was used during the checkout process.', 'pdd' ) . '</li>
				<li><strong>Name</strong> - ' . __( "The buyer's name.", 'pdd' ) . '</li>
				<li><strong>Email</strong> - ' . __( "The buyer's email address.", 'pdd' ) . '</li>
				<li><strong>Payment Notes</strong> - ' . __( 'Any customer-specific notes related to the payment.', 'pdd' ) . '</li>
				<li><strong>Payment Method</strong> - ' . __( 'The name of the payment gateway used to complete the payment.', 'pdd' ) . '</li>
				<li><strong>Purchase Key</strong> - ' . __( 'A unique key used to identify the payment.', 'pdd' ) . '</li>
			</ul>'
	) );

	do_action( 'pdd_payments_contextual_help', $screen );
}
add_action( 'load-download_page_pdd-payment-history', 'pdd_payments_contextual_help' );
