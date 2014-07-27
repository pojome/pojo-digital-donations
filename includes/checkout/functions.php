<?php
/**
 * Checkout Functions
 *
 * @package     PDD
 * @subpackage  Checkout
 * @copyright   Copyright (c) 2014, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Determines if we're currently on the Checkout page
 *
 * @since 1.1.2
 * @return bool True if on the Checkout page, false otherwise
 */
function pdd_is_checkout() {
	global $pdd_options;
	$is_checkout = isset( $pdd_options['purchase_page'] ) ? is_page( $pdd_options['purchase_page'] ) : false;
	return apply_filters( 'pdd_is_checkout', $is_checkout );
}

/**
 * Determines if a user can checkout or not
 *
 * @since 1.3.3
 * @global $pdd_options Array of all the PDD Options
 * @return bool Can user checkout?
 */
function pdd_can_checkout() {
	global $pdd_options;

	$can_checkout = true; // Always true for now

	return (bool) apply_filters( 'pdd_can_checkout', $can_checkout );
}

/**
 * Retrieve the Success page URI
 *
 * @access      public
 * @since       1.6
 * @return      string
*/
function pdd_get_success_page_uri() {
	global $pdd_options;

	$page_id = isset( $pdd_options['success_page'] ) ? absint( $pdd_options['success_page'] ) : 0;

	return apply_filters( 'pdd_get_success_page_uri', get_permalink( $pdd_options['success_page'] ) );
}

/**
 * Determines if we're currently on the Success page.
 *
 * @since 1.9.9
 * @return bool True if on the Success page, false otherwise.
 */
function pdd_is_success_page() {
	global $pdd_options;
	$is_success_page = isset( $pdd_options['success_page'] ) ? is_page( $pdd_options['success_page'] ) : false;
	return apply_filters( 'pdd_is_success_page', $is_success_page );
}

/**
 * Send To Success Page
 *
 * Sends the user to the succes page.
 *
 * @param string $query_string
 * @access      public
 * @since       1.0
 * @return      void
*/
function pdd_send_to_success_page( $query_string = null ) {
	global $pdd_options;

	$redirect = pdd_get_success_page_uri();

	if ( $query_string )
		$redirect .= $query_string;

	wp_redirect( apply_filters('pdd_success_page_redirect', $redirect, $_POST['pdd-gateway'], $query_string) );
	pdd_die();
}

/**
 * Get the URL of the Checkout page
 *
 * @since 1.0.8
 * @global $pdd_options Array of all the PDD Options
 * @param array $args Extra query args to add to the URI
 * @return mixed Full URL to the checkout page, if present | null if it doesn't exist
 */
function pdd_get_checkout_uri( $args = array() ) {
	global $pdd_options;

	$uri = isset( $pdd_options['purchase_page'] ) ? get_permalink( $pdd_options['purchase_page'] ) : NULL;

	if ( ! empty( $args ) ) {
		// Check for backward compatibility
		if ( is_string( $args ) )
			$args = str_replace( '?', '', $args );

		$args = wp_parse_args( $args );

		$uri = add_query_arg( $args, $uri );
	}

	$scheme = defined( 'FORCE_SSL_ADMIN' ) && FORCE_SSL_ADMIN ? 'https' : 'admin';

	$ajax_url = admin_url( 'admin-ajax.php', $scheme );

	if ( ( ! preg_match( '/^https/', $uri ) && preg_match( '/^https/', $ajax_url ) ) || pdd_is_ssl_enforced() ) {
		$uri = preg_replace( '/^http:/', 'https:', $uri );
	}

	if ( isset( $pdd_options['no_cache_checkout'] ) && pdd_is_caching_plugin_active() )
		$uri = add_query_arg( 'nocache', 'true', $uri );

	return apply_filters( 'pdd_get_checkout_uri', $uri );
}

/**
 * Send back to checkout.
 *
 * Used to redirect a user back to the purchase
 * page if there are errors present.
 *
 * @param array $args
 * @access public
 * @since  1.0
 * @return Void
 */
function pdd_send_back_to_checkout( $args = array() ) {
	$redirect = pdd_get_checkout_uri();

	if ( ! empty( $args ) ) {
		// Check for backward compatibility
		if ( is_string( $args ) )
			$args = str_replace( '?', '', $args );

		$args = wp_parse_args( $args );

		$redirect = add_query_arg( $args, $redirect );
	}

	wp_redirect( apply_filters( 'pdd_send_back_to_checkout', $redirect, $args ) );
	pdd_die();
}

/**
 * Get Success Page URL
 *
 * Gets the success page URL.
 *
 * @param string $query_string
 * @access      public
 * @since       1.0
 * @return      string
*/
function pdd_get_success_page_url( $query_string = null ) {
	global $pdd_options;

	$success_page = get_permalink($pdd_options['success_page']);
	if ( $query_string )
		$success_page .= $query_string;

	return apply_filters( 'pdd_success_page_url', $success_page );
}

/**
 * Get the URL of the Transaction Failed page
 *
 * @since 1.3.4
 * @global $pdd_options Array of all the PDD Options
 *
 * @param bool $extras Extras to append to the URL
 * @return mixed|void Full URL to the Transaction Failed page, if present, home page if it doesn't exist
 */
function pdd_get_failed_transaction_uri( $extras = false ) {
	global $pdd_options;

	$uri = ! empty( $pdd_options['failure_page'] ) ? trailingslashit( get_permalink( $pdd_options['failure_page'] ) ) : home_url();
	if ( $extras )
		$uri .= $extras;

	return apply_filters( 'pdd_get_failed_transaction_uri', $uri );
}

/**
 * Mark payments as Failed when returning to the Failed Transaction page
 *
 * @access      public
 * @since       1.9.9
 * @return      void
*/
function pdd_listen_for_failed_payments() {
	
	$failed_page = pdd_get_option( 'failure_page', 0 );

	if( ! empty( $failed_page ) && is_page( $failed_page ) && ! empty( $_GET['payment-id'] ) ) {

		$payment_id = absint( $_GET['payment-id'] );
		pdd_update_payment_status( $payment_id, 'failed' );

	}

}
add_action( 'template_redirect', 'pdd_listen_for_failed_payments' );

/**
 * Check if a field is required
 *
 * @param string $field
 * @access      public
 * @since       1.7
 * @return      bool
*/
function pdd_field_is_required( $field = '' ) {
	$required_fields = pdd_purchase_form_required_fields();
	return array_key_exists( $field, $required_fields );
}

/**
 * Retrieve an array of banned_emails
 *
 * @since       2.0
 * @return      array
 */
function pdd_get_banned_emails() {
	$emails = array_map( 'trim', pdd_get_option( 'banned_emails', array() ) );

	return apply_filters( 'pdd_get_banned_emails', $emails );
}

/**
 * Determines if an email is banned
 *
 * @since       2.0
 * @return      bool
 */
function pdd_is_email_banned( $email = '' ) {

	if( empty( $email ) ) {
		return false;
	}

	$ret = in_array( trim( $email ), pdd_get_banned_emails() );

	return apply_filters( 'pdd_is_email_banned', $ret, $email );
}

/** 
 * Determines if secure checkout pages are enforced
 *
 * @since       2.0
 * @return      bool True if enforce SSL is enabled, false otherwise
 */
function pdd_is_ssl_enforced() {
	$ssl_enforced = pdd_get_option( 'enforce_ssl', false );
	return (bool) apply_filters( 'pdd_is_ssl_enforced', $ssl_enforced );
}

/**
 * Handle redirections for SSL enforced checkouts
 *
 * @since 2.0
 * @global $pdd_options Array of all the PDD Options
 * @return void
 */
function pdd_enforced_ssl_redirect_handler() {
	if ( ! pdd_is_ssl_enforced() || ! pdd_is_checkout() || is_admin() || is_ssl() ) {
		return;
	}
 
	if ( isset( $_SERVER["HTTPS"] ) && $_SERVER["HTTPS"] == "on" ) {
		return;
	}

	$uri = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

	wp_safe_redirect( $uri );
	exit;
}
add_action( 'template_redirect', 'pdd_enforced_ssl_redirect_handler' );

/**
 * Handle rewriting asset URLs for SSL enforced checkouts
 *
 * @since 2.0
 * @return void
 */
function pdd_enforced_ssl_asset_handler() {
	if ( ! pdd_is_ssl_enforced() || ! pdd_is_checkout() || is_admin() ) {
		return;
	}

	$filters = array(
		'post_thumbnail_html',
		'wp_get_attachment_url',
		'wp_get_attachment_image_attributes',
		'wp_get_attachment_url',
		'option_stylesheet_url',
		'option_template_url',
		'script_loader_src',
		'style_loader_src',
		'template_directory_uri',
		'stylesheet_directory_uri',
		'site_url'
	);
	
	$filters = apply_filters( 'pdd_enforced_ssl_asset_filters', $filters );

	foreach ( $filters as $filter ) {
		add_filter( $filter, 'pdd_enforced_ssl_asset_filter', 1 );
	}
}
add_action( 'template_redirect', 'pdd_enforced_ssl_asset_handler' );

/**
 * Filter filters and convert http to https
 *
 * @since 2.0
 * @param mixed $content
 * @return mixed
 */
function pdd_enforced_ssl_asset_filter( $content ) {
	if ( is_array( $content ) ) {
		$content = array_map( 'pdd_enforced_ssl_asset_filter', $content );
	} else {
		$content = str_replace( 'http:', 'https:', $content );
	}

	return $content;
}