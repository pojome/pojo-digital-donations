<?php
/**
 * Login / Register Functions
 *
 * @package     PDD
 * @subpackage  Functions/Login
 * @copyright   Copyright (c) 2014, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Login Form
 *
 * @since 1.0
 * @global $pdd_options
 * @global $post
 * @param string $redirect Redirect page URL
 * @return string Login form
*/
function pdd_login_form( $redirect = '' ) {
	global $pdd_options, $pdd_login_redirect;

	if ( empty( $redirect ) ) {
		$redirect = pdd_get_current_page_url();
	}

	$pdd_login_redirect = $redirect;

	ob_start();

	pdd_get_template_part( 'shortcode', 'login' );

	return apply_filters( 'pdd_login_form', ob_get_clean() );
}

/**
 * Registration Form
 *
 * @since 2.0
 * @global $pdd_options
 * @global $post
 * @param string $redirect Redirect page URL
 * @return string Register form
*/
function pdd_register_form( $redirect = '' ) {
	global $pdd_options, $pdd_register_redirect;

	if ( empty( $redirect ) ) {
		$redirect = pdd_get_current_page_url();
	}

	$pdd_register_redirect = $redirect;

	ob_start();

	if( ! is_user_logged_in() ) {
		pdd_get_template_part( 'shortcode', 'register' );
	}

	return apply_filters( 'pdd_register_form', ob_get_clean() );
}

/**
 * Process Login Form
 *
 * @since 1.0
 * @param array $data Data sent from the login form
 * @return void
*/
function pdd_process_login_form( $data ) {
	if ( wp_verify_nonce( $data['pdd_login_nonce'], 'pdd-login-nonce' ) ) {
		$user_data = get_user_by( 'login', $data['pdd_user_login'] );
		if ( ! $user_data ) {
			$user_data = get_user_by( 'email', $data['pdd_user_login'] );
		}
		if ( $user_data ) {
			$user_ID = $user_data->ID;
			$user_email = $user_data->user_email;
			if ( wp_check_password( $data['pdd_user_pass'], $user_data->user_pass, $user_data->ID ) ) {
				pdd_log_user_in( $user_data->ID, $data['pdd_user_login'], $data['pdd_user_pass'] );
			} else {
				pdd_set_error( 'password_incorrect', __( 'The password you entered is incorrect', 'pdd' ) );
			}
		} else {
			pdd_set_error( 'username_incorrect', __( 'The username you entered does not exist', 'pdd' ) );
		}
		// Check for errors and redirect if none present
		$errors = pdd_get_errors();
		if ( ! $errors ) {
			$redirect = apply_filters( 'pdd_login_redirect', $data['pdd_redirect'], $user_ID );
			wp_redirect( $redirect );
			pdd_die();
		}
	}
}
add_action( 'pdd_user_login', 'pdd_process_login_form' );

/**
 * Log User In
 *
 * @since 1.0
 * @param int $user_id User ID
 * @param string $user_login Username
 * @param string $user_pass Password
 * @return void
*/
function pdd_log_user_in( $user_id, $user_login, $user_pass ) {
	if ( $user_id < 1 )
		return;

	wp_set_auth_cookie( $user_id );
	wp_set_current_user( $user_id, $user_login );
	do_action( 'wp_login', $user_login, get_userdata( $user_id ) );
	do_action( 'pdd_log_user_in', $user_id, $user_login, $user_pass );
}


/**
 * Process Register Form
 *
 * @since 2.0
 * @param array $data Data sent from the register form
 * @return void
*/
function pdd_process_register_form( $data ) {

	if( is_user_logged_in() ) {
		return;
	}

	if( empty( $_POST['pdd_register_submit'] ) ) {
		return;
	}

	do_action( 'pdd_pre_process_register_form' );

	if( empty( $data['pdd_user_login'] ) ) {
		pdd_set_error( 'empty_username', __( 'Invalid username', 'pdd' ) );
	}

	if( username_exists( $data['pdd_user_login'] ) ) {
		pdd_set_error( 'username_unavailable', __( 'Username already taken', 'pdd' ) );
	}

	if( ! validate_username( $data['pdd_user_login'] ) ) {
		pdd_set_error( 'username_invalid', __( 'Invalid username', 'pdd' ) );
	}

	if( email_exists( $data['pdd_user_email'] ) ) {
		pdd_set_error( 'email_unavailable', __( 'Email address already taken', 'pdd' ) );
	}

	if( empty( $data['pdd_user_email'] ) || ! is_email( $data['pdd_user_email'] ) ) {
		pdd_set_error( 'email_invalid', __( 'Invalid email', 'pdd' ) );
	}

	if( ! empty( $data['pdd_payment_email'] ) && $data['pdd_payment_email'] != $data['pdd_user_email'] && ! is_email( $data['pdd_payment_email'] ) ) {
		pdd_set_error( 'payment_email_invalid', __( 'Invalid payment email', 'pdd' ) );
	}

	if( empty( $_POST['pdd_user_pass'] ) ) {
		pdd_set_error( 'empty_password', __( 'Please enter a password', 'pdd' ) );
	}

	if( ( ! empty( $_POST['pdd_user_pass'] ) && empty( $_POST['pdd_user_pass2'] ) ) || ( $_POST['pdd_user_pass'] !== $_POST['pdd_user_pass2'] ) ) {
		pdd_set_error( 'password_mismatch', __( 'Passwords do not match', 'pdd' ) );
	}

	do_action( 'pdd_process_register_form' );

	// Check for errors and redirect if none present
	$errors = pdd_get_errors();

	if (  empty( $errors ) ) {

		$redirect = apply_filters( 'pdd_register_redirect', $data['pdd_redirect'] );

		pdd_register_and_login_new_user( array(
			'user_login'      => $data['pdd_user_login'],
			'user_pass'       => $data['pdd_user_pass'],
			'user_email'      => $data['pdd_user_email'],
			'user_registered' => date( 'Y-m-d H:i:s' ),
			'role'            => get_option( 'default_role' )
		) );

		wp_redirect( $redirect );
		pdd_die();
	}
}
add_action( 'pdd_user_register', 'pdd_process_register_form' );