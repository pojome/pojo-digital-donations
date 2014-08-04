<?php
/**
 * Email Functions
 *
 * @package     PDD
 * @subpackage  Emails
 * @copyright   Copyright (c) 2014, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Email the download link(s) and payment confirmation to the buyer in a
 * customizable Purchase Receipt
 *
 * @since 1.0
 * @param int $payment_id Payment ID
 * @param bool $admin_notice Whether to send the admin email notification or not (default: true)
 * @return void
 */
function pdd_email_purchase_receipt( $payment_id, $admin_notice = true ) {
	global $pdd_options;

	$payment_data = pdd_get_payment_meta( $payment_id );
	$user_id      = pdd_get_payment_user_id( $payment_id );
	$user_info    = maybe_unserialize( $payment_data['user_info'] );
	$email        = pdd_get_payment_user_email( $payment_id );

	if ( isset( $user_id ) && $user_id > 0 ) {
		$user_data = get_userdata($user_id);
		$name = $user_data->display_name;
	} elseif ( isset( $user_info['first_name'] ) && isset( $user_info['last_name'] ) ) {
		$name = $user_info['first_name'] . ' ' . $user_info['last_name'];
	} else {
		$name = $email;
	}

	$message = pdd_get_email_body_header();
	$message .= pdd_get_email_body_content( $payment_id, $payment_data, $admin_notice );
	$message .= pdd_get_email_body_footer();

	$from_name = isset( $pdd_options['from_name'] ) ? $pdd_options['from_name'] : get_bloginfo('name');
	$from_name = apply_filters( 'pdd_purchase_from_name', $from_name, $payment_id, $payment_data );

	$from_email = isset( $pdd_options['from_email'] ) ? $pdd_options['from_email'] : get_option('admin_email');
	$from_email = apply_filters( 'pdd_purchase_from_address', $from_email, $payment_id, $payment_data );

	$subject = apply_filters( 'pdd_purchase_subject', ! empty( $pdd_options['purchase_subject'] )
		? wp_strip_all_tags( $pdd_options['purchase_subject'], true )
		: __( 'Donation Receipt', 'pdd' ), $payment_id );

	$subject = pdd_do_email_tags( $subject, $payment_id );

	$headers = "From: " . stripslashes_deep( html_entity_decode( $from_name, ENT_COMPAT, 'UTF-8' ) ) . " <$from_email>\r\n";
	$headers .= "Reply-To: ". $from_email . "\r\n";
	//$headers .= "MIME-Version: 1.0\r\n";
	$headers .= "Content-Type: text/html; charset=utf-8\r\n";
	$headers = apply_filters( 'pdd_receipt_headers', $headers, $payment_id, $payment_data );

	// Allow add-ons to add file attachments
	$attachments = apply_filters( 'pdd_receipt_attachments', array(), $payment_id, $payment_data );
	if ( apply_filters( 'pdd_email_purchase_receipt', true ) ) {
		wp_mail( $email, $subject, $message, $headers, $attachments );
	}

	if ( $admin_notice && ! pdd_admin_notices_disabled( $payment_id ) ) {
		do_action( 'pdd_admin_sale_notice', $payment_id, $payment_data );
	}
}

/**
 * Email the download link(s) and payment confirmation to the admin accounts for testing.
 *
 * @since 1.5
 * @global $pdd_options Array of all the PDD Options
 * @return void
 */
function pdd_email_test_purchase_receipt() {
	global $pdd_options;

	$default_email_body = __( "Dear", "pdd" ) . " {name},\n\n";
	$default_email_body .= __( "Thank you for your donation. We appreciate it so much.", "pdd" ) . "\n\n";
	$default_email_body .= "{sitename}";

	$email = isset( $pdd_options['purchase_receipt'] ) ? $pdd_options['purchase_receipt'] : $default_email_body;

	$message = pdd_get_email_body_header();
	$message .= apply_filters( 'pdd_purchase_receipt', pdd_email_preview_template_tags( $email ), 0, array() );
	$message .= pdd_get_email_body_footer();

	$from_name = isset( $pdd_options['from_name'] ) ? $pdd_options['from_name'] : get_bloginfo('name');
	$from_email = isset( $pdd_options['from_email'] ) ? $pdd_options['from_email'] : get_option('admin_email');

	$subject = apply_filters( 'pdd_purchase_subject', isset( $pdd_options['purchase_subject'] )
		? trim( $pdd_options['purchase_subject'] )
		: __( 'Donation Receipt', 'pdd' ), 0 );

	$headers = "From: " . stripslashes_deep( html_entity_decode( $from_name, ENT_COMPAT, 'UTF-8' ) ) . " <$from_email>\r\n";
	$headers .= "Reply-To: ". $from_email . "\r\n";
	//$headers .= "MIME-Version: 1.0\r\n";
	$headers .= "Content-Type: text/html; charset=utf-8\r\n";
	$headers = apply_filters( 'pdd_test_purchase_headers', $headers );

	wp_mail( pdd_get_admin_notice_emails(), $subject, $message, $headers );
}

/**
 * Sends the Admin Sale Notification Email
 *
 * @since 1.4.2
 * @param int $payment_id Payment ID (default: 0)
 * @param array $payment_data Payment Meta and Data
 * @return void
 */
function pdd_admin_email_notice( $payment_id = 0, $payment_data = array() ) {
	global $pdd_options;

	/* Send an email notification to the admin */
	$admin_email = pdd_get_admin_notice_emails();
	$user_id     = pdd_get_payment_user_id( $payment_id );
	$user_info   = maybe_unserialize( $payment_data['user_info'] );

	if ( isset( $user_id ) && $user_id > 0 ) {
		$user_data = get_userdata($user_id);
		$name = $user_data->display_name;
	} elseif ( isset( $user_info['first_name'] ) && isset( $user_info['last_name'] ) ) {
		$name = $user_info['first_name'] . ' ' . $user_info['last_name'];
	} else {
		$name = $user_info['email'];
	}

	$admin_message = pdd_get_email_body_header();
	$admin_message .= pdd_get_sale_notification_body_content( $payment_id, $payment_data );
	$admin_message .= pdd_get_email_body_footer();

	if( ! empty( $pdd_options['sale_notification_subject'] ) ) {
		$admin_subject = wp_strip_all_tags( $pdd_options['sale_notification_subject'], true );
	} else {
		$admin_subject = sprintf( __( 'New donation submitted - Order #%1$s', 'pdd' ), $payment_id );
	}

	$admin_subject = pdd_do_email_tags( $admin_subject, $payment_id );
	$admin_subject = apply_filters( 'pdd_admin_sale_notification_subject', $admin_subject, $payment_id, $payment_data );

	$from_name  = isset( $pdd_options['from_name'] )  ? $pdd_options['from_name']  : get_bloginfo('name');
	$from_email = isset( $pdd_options['from_email'] ) ? $pdd_options['from_email'] : get_option('admin_email');

	$admin_headers = "From: " . stripslashes_deep( html_entity_decode( $from_name, ENT_COMPAT, 'UTF-8' ) ) . " <$from_email>\r\n";
	$admin_headers .= "Reply-To: ". $from_email . "\r\n";
	//$admin_headers .= "MIME-Version: 1.0\r\n";
	$admin_headers .= "Content-Type: text/html; charset=utf-8\r\n";
	$admin_headers .= apply_filters( 'pdd_admin_sale_notification_headers', $admin_headers, $payment_id, $payment_data );

	$admin_attachments = apply_filters( 'pdd_admin_sale_notification_attachments', array(), $payment_id, $payment_data );

	wp_mail( $admin_email, $admin_subject, $admin_message, $admin_headers, $admin_attachments );
}
add_action( 'pdd_admin_sale_notice', 'pdd_admin_email_notice', 10, 2 );

/**
 * Retrieves the emails for which admin notifications are sent to (these can be
 * changed in the PDD Settings)
 *
 * @since 1.0
 * @global $pdd_options Array of all the PDD Options
 * @return mixed
 */
function pdd_get_admin_notice_emails() {
	global $pdd_options;

	$emails = isset( $pdd_options['admin_notice_emails'] ) && strlen( trim( $pdd_options['admin_notice_emails'] ) ) > 0 ? $pdd_options['admin_notice_emails'] : get_bloginfo( 'admin_email' );
	$emails = array_map( 'trim', explode( "\n", $emails ) );

	return apply_filters( 'pdd_admin_notice_emails', $emails );
}

/**
 * Checks whether admin sale notices are disabled
 *
 * @since 1.5.2
 *
 * @param int $payment_id
 * @return mixed
 */
function pdd_admin_notices_disabled( $payment_id = 0 ) {
	global $pdd_options;
	$retval = isset( $pdd_options['disable_admin_notices'] );
	return apply_filters( 'pdd_admin_notices_disabled', $retval, $payment_id );
}

/**
 * Get sale notification email text
 *
 * Returns the stored email text if available, the standard email text if not
 *
 * @since 1.7
 * @author Daniel J Griffiths
 * @return string $message
 */
function pdd_get_default_sale_notification_email() {
	global $pdd_options;

	$default_email_body = __( 'Hello', 'pdd' ) . "\n\n" . sprintf( __( 'A %s payment has been made', 'pdd' ), pdd_get_label_plural() ) . ".\n\n";
	$default_email_body .= sprintf( __( '%s sold:', 'pdd' ), pdd_get_label_plural() ) . "\n\n";
	$default_email_body .= __( 'Donated by: ', 'pdd' ) . ' {name}' . "\n";
	$default_email_body .= __( 'Amount: ', 'pdd' ) . ' {price}' . "\n";
	$default_email_body .= __( 'Payment Method: ', 'pdd' ) . ' {payment_method}' . "\n\n";
	$default_email_body .= __( 'Thank you', 'pdd' );

	$message = ( isset( $pdd_options['sale_notification'] ) && !empty( $pdd_options['sale_notification'] ) ) ? $pdd_options['sale_notification'] : $default_email_body;

	return $message;
}

/**
 * Get various correctly formatted names used in emails
 *
 * @since 1.9
 * @param $user_info
 *
 * @return array $email_names
 */
function pdd_get_email_names( $user_info ) {
	$email_names = array();
	$user_info 	= maybe_unserialize( $user_info );

	$email_names[ 'fullname' ] = '';
	if ( isset( $user_info['id'] ) && $user_info['id'] > 0 && isset( $user_info['first_name'] ) ) {
		$user_data = get_userdata( $user_info['id'] );
		$email_names[ 'name' ]      = $user_info['first_name'];
		$email_names[ 'fullname' ]  = $user_info['first_name'] . ' ' . $user_info['last_name'];
		$email_names[ 'username' ]  = $user_data->user_login;
	} elseif ( isset( $user_info['first_name'] ) ) {
		$email_names[ 'name' ]     = $user_info['first_name'];
		$email_names[ 'fullname' ] = $user_info['first_name'] . ' ' . $user_info['last_name'];
		$email_names[ 'username' ] = $user_info['first_name'];
	} else {
		$email_names[ 'name' ]     = $user_info['email'];
		$email_names[ 'username' ] = $user_info['email'];
	}

	return $email_names;
}
