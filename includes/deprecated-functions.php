<?php
/**
 * Deprecated Functions
 *
 * All functions that have been deprecated.
 *
 * @package     PDD
 * @subpackage  Deprecated
 * @copyright   Copyright (c) 2014, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Get Download Sales Log
 *
 * Returns an array of sales and sale info for a download.
 *
 * @since       1.0
 * @deprecated  1.3.4
 *
 * @param int $download_id ID number of the download to retrieve a log for
 * @param bool $paginate Whether to paginate the results or not
 * @param int $number Number of results to return
 * @param int $offset Number of items to skip
 *
 * @return mixed array|bool
*/
function pdd_get_download_sales_log( $download_id, $paginate = false, $number = 10, $offset = 0 ) {
	$backtrace = debug_backtrace();

	_pdd_deprecated_function( __FUNCTION__, '1.3.4', null, $backtrace );

	$sales_log = get_post_meta( $download_id, '_pdd_sales_log', true );

	if ( $sales_log ) {
		$sales_log = array_reverse( $sales_log );
		$log = array();
		$log['number'] = count( $sales_log );
		$log['sales'] = $sales_log;

		if ( $paginate ) {
			$log['sales'] = array_slice( $sales_log, $offset, $number );
		}

		return $log;
	}

	return false;
}

/**
 * Get File Download Log
 *
 * Returns an array of file download dates and user info.
 *
 * @deprecated 1.3.4
 * @since 1.0
 *
 * @param int $download_id the ID number of the download to retrieve a log for
 * @param bool $paginate whether to paginate the results or not
 * @param int $number the number of results to return
 * @param int $offset the number of items to skip
 *
 * @return mixed array|bool
*/
function pdd_get_file_download_log( $download_id, $paginate = false, $number = 10, $offset = 0 ) {
	$backtrace = debug_backtrace();

	_pdd_deprecated_function( __FUNCTION__, '1.3.4', null, $backtrace );

	$download_log = get_post_meta( $download_id, '_pdd_file_download_log', true );

	if ( $download_log ) {
		$download_log = array_reverse( $download_log );
		$log = array();
		$log['number'] = count( $download_log );
		$log['downloads'] = $download_log;

		if ( $paginate ) {
			$log['downloads'] = array_slice( $download_log, $offset, $number );
		}

		return $log;
	}

	return false;
}

/**
 * Get Downloads Of Purchase
 *
 * Retrieves an array of all files purchased.
 *
 * @since 1.0
 * @deprecated 1.4
 *
 * @param int  $payment_id ID number of the purchase
 * @param null $payment_meta
 * @return bool|mixed
 */
function pdd_get_downloads_of_purchase( $payment_id, $payment_meta = null ) {
	$backtrace = debug_backtrace();

	_pdd_deprecated_function( __FUNCTION__, '1.4', 'pdd_get_payment_meta_downloads', $backtrace );

	if ( is_null( $payment_meta ) ) {
		$payment_meta = pdd_get_payment_meta( $payment_id );
	}

	$downloads = maybe_unserialize( $payment_meta['downloads'] );

	if ( $downloads )
		return $downloads;

	return false;
}

/**
 * Get Menu Access Level
 *
 * Returns the access level required to access the downloads menu. Currently not
 * changeable, but here for a future update.
 *
 * @since 1.0
 * @deprecated 1.4.4
 * @return string
*/
function pdd_get_menu_access_level() {
	$backtrace = debug_backtrace();

	_pdd_deprecated_function( __FUNCTION__, '1.4.4', 'current_user_can(\'manage_shop_settings\')', $backtrace );

	return apply_filters( 'pdd_menu_access_level', 'manage_options' );
}



/**
 * Check if only local taxes are enabled meaning users must opt in by using the
 * option set from the PDD Settings.
 *
 * @since 1.3.3
 * @deprecated 1.6
 * @global $pdd_options
 * @return bool $local_only
 */
function pdd_local_taxes_only() {

	$backtrace = debug_backtrace();

	_pdd_deprecated_function( __FUNCTION__, '1.6', 'no alternatives', $backtrace );

	global $pdd_options;

	$local_only = isset( $pdd_options['tax_condition'] ) && $pdd_options['tax_condition'] == 'local';

	return apply_filters( 'pdd_local_taxes_only', $local_only );
}

/**
 * Checks if a customer has opted into local taxes
 *
 * @since 1.4.1
 * @deprecated 1.6
 * @uses PDD_Session::get()
 * @return bool
 */
function pdd_local_tax_opted_in() {

	$backtrace = debug_backtrace();

	_pdd_deprecated_function( __FUNCTION__, '1.6', 'no alternatives', $backtrace );

	$opted_in = PDD()->session->get( 'pdd_local_tax_opt_in' );
	return ! empty( $opted_in );
}

/**
 * Show taxes on individual prices?
 *
 * @since 1.4
 * @deprecated 1.9
 * @global $pdd_options
 * @return bool Whether or not to show taxes on prices
 */
function pdd_taxes_on_prices() {
	global $pdd_options;

	$backtrace = debug_backtrace();

	_pdd_deprecated_function( __FUNCTION__, '1.9', 'no alternatives', $backtrace );

	return apply_filters( 'pdd_taxes_on_prices', isset( $pdd_options['taxes_on_prices'] ) );
}

/**
 * Show Has Purchased Item Message
 *
 * Prints a notice when user has already purchased the item.
 *
 * @since 1.0
 * @deprecated 1.8
 * @global $user_ID
 */
function pdd_show_has_purchased_item_message() {

	$backtrace = debug_backtrace();

	_pdd_deprecated_function( __FUNCTION__, '1.8', 'no alternatives', $backtrace );

	global $user_ID, $post;

	if( !isset( $post->ID ) )
		return;

	if ( pdd_has_user_purchased( $user_ID, $post->ID ) ) {
		$alert = '<p class="pdd_has_purchased">' . __( 'You have already purchased this item, but you may purchase it again.', 'pdd' ) . '</p>';
		echo apply_filters( 'pdd_show_has_purchased_item_message', $alert );
	}
}

/**
 * Flushes the total earning cache when a new payment is created
 *
 * @since 1.2
 * @deprecated 1.8.4
 * @param int $payment Payment ID
 * @param array $payment_data Payment Data
 * @return void
 */
function pdd_clear_earnings_cache( $payment, $payment_data ) {

	$backtrace = debug_backtrace();

	_pdd_deprecated_function( __FUNCTION__, '1.8.4', 'no alternatives', $backtrace );

	delete_transient( 'pdd_total_earnings' );
}
//add_action( 'pdd_insert_payment', 'pdd_clear_earnings_cache', 10, 2 );

/**
 * Get Cart Amount
 *
 * @since 1.0
 * @deprecated 1.9
 * @param bool $add_taxes Whether to apply taxes (if enabled) (default: true)
 * @param bool $local_override Force the local opt-in param - used for when not reading $_POST (default: false)
 * @return float Total amount
*/
function pdd_get_cart_amount( $add_taxes = true, $local_override = false ) {

	$backtrace = debug_backtrace();

	_pdd_deprecated_function( __FUNCTION__, '1.9', 'pdd_get_cart_subtotal() or pdd_get_cart_total()', $backtrace );

	$amount = pdd_get_cart_subtotal( false );
	if ( ! empty( $_POST['pdd-discount'] ) || pdd_get_cart_discounts() !== false ) {
		// Retrieve the discount stored in cookies
		$discounts = pdd_get_cart_discounts();

		// Check for a posted discount
		$posted_discount = isset( $_POST['pdd-discount'] ) ? trim( $_POST['pdd-discount'] ) : '';

		if ( $posted_discount && ! in_array( $posted_discount, $discounts ) ) {
			// This discount hasn't been applied, so apply it
			$amount = pdd_get_discounted_amount( $posted_discount, $amount );
		}

		if( ! empty( $discounts ) ) {
			// Apply the discounted amount from discounts already applied
			$amount -= pdd_get_cart_discounted_amount();
		}
	}

	if ( pdd_use_taxes() && pdd_is_cart_taxed() && $add_taxes ) {
		$tax = pdd_get_cart_tax();
		$amount += $tax;
	}

	if( $amount < 0 )
		$amount = 0.00;

	return apply_filters( 'pdd_get_cart_amount', $amount, $add_taxes, $local_override );
}

/**
 * Get Purchase Receipt Template Tags
 *
 * Displays all available template tags for the purchase receipt.
 *
 * @since 1.6
 * @deprecated 1.9
 * @author Daniel J Griffiths
 * @return string $tags
 */
function pdd_get_purchase_receipt_template_tags() {
	$tags = __('Enter the email that is sent to users after completing a successful purchase. HTML is accepted. Available template tags:', 'pdd') . '<br/>' .
			'{download_list} - ' . __('A list of download links for each download purchased', 'pdd') . '<br/>' .
			'{file_urls} - ' . __('A plain-text list of download URLs for each download purchased', 'pdd') . '<br/>' .
			'{name} - ' . __('The buyer\'s first name', 'pdd') . '<br/>' .
			'{fullname} - ' . __('The buyer\'s full name, first and last', 'pdd') . '<br/>' .
			'{username} - ' . __('The buyer\'s user name on the site, if they registered an account', 'pdd') . '<br/>' .
			'{user_email} - ' . __('The buyer\'s email address', 'pdd') . '<br/>' .
			'{billing_address} - ' . __('The buyer\'s billing address', 'pdd') . '<br/>' .
			'{date} - ' . __('The date of the purchase', 'pdd') . '<br/>' .
			'{subtotal} - ' . __('The price of the purchase before taxes', 'pdd') . '<br/>' .
			'{tax} - ' . __('The taxed amount of the purchase', 'pdd') . '<br/>' .
			'{price} - ' . __('The total price of the purchase', 'pdd') . '<br/>' .
			'{payment_id} - ' . __('The unique ID number for this purchase', 'pdd') . '<br/>' .
			'{receipt_id} - ' . __('The unique ID number for this purchase receipt', 'pdd') . '<br/>' .
			'{payment_method} - ' . __('The method of payment used for this purchase', 'pdd') . '<br/>' .
			'{sitename} - ' . __('Your site name', 'pdd') . '<br/>' .
			'{receipt_link} - ' . __( 'Adds a link so users can view their receipt directly on your website if they are unable to view it in the browser correctly.', 'pdd' );

	return apply_filters( 'pdd_purchase_receipt_template_tags_description', $tags );
}


/**
 * Get Sale Notification Template Tags
 *
 * Displays all available template tags for the sale notification email
 *
 * @since 1.7
 * @deprecated 1.9
 * @author Daniel J Griffiths
 * @return string $tags
 */
function pdd_get_sale_notification_template_tags() {
	$tags = __( 'Enter the email that is sent to sale notification emails after completion of a purchase. HTML is accepted. Available template tags:', 'pdd' ) . '<br/>' .
			'{download_list} - ' . __('A list of download links for each download purchased', 'pdd') . '<br/>' .
			'{file_urls} - ' . __('A plain-text list of download URLs for each download purchased', 'pdd') . '<br/>' .
			'{name} - ' . __('The buyer\'s first name', 'pdd') . '<br/>' .
			'{fullname} - ' . __('The buyer\'s full name, first and last', 'pdd') . '<br/>' .
			'{username} - ' . __('The buyer\'s user name on the site, if they registered an account', 'pdd') . '<br/>' .
			'{user_email} - ' . __('The buyer\'s email address', 'pdd') . '<br/>' .
			'{billing_address} - ' . __('The buyer\'s billing address', 'pdd') . '<br/>' .
			'{date} - ' . __('The date of the purchase', 'pdd') . '<br/>' .
			'{subtotal} - ' . __('The price of the purchase before taxes', 'pdd') . '<br/>' .
			'{tax} - ' . __('The taxed amount of the purchase', 'pdd') . '<br/>' .
			'{price} - ' . __('The total price of the purchase', 'pdd') . '<br/>' .
			'{payment_id} - ' . __('The unique ID number for this purchase', 'pdd') . '<br/>' .
			'{receipt_id} - ' . __('The unique ID number for this purchase receipt', 'pdd') . '<br/>' .
			'{payment_method} - ' . __('The method of payment used for this purchase', 'pdd') . '<br/>' .
			'{sitename} - ' . __('Your site name', 'pdd');

	return apply_filters( 'pdd_sale_notification_template_tags_description', $tags );
}
