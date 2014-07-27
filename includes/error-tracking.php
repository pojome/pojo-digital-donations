<?php
/**
 * Error Tracking
 *
 * @package     PDD
 * @subpackage  Functions/Errors
 * @copyright   Copyright (c) 2014, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Print Errors
 *
 * Prints all stored errors. For use during checkout.
 * If errors exist, they are returned.
 *
 * @since 1.0
 * @uses pdd_get_errors()
 * @uses pdd_clear_errors()
 * @return void
 */
function pdd_print_errors() {
	$errors = pdd_get_errors();
	if ( $errors ) {
		$classes = apply_filters( 'pdd_error_class', array(
			'pdd_errors'
		) );
		echo '<div class="' . implode( ' ', $classes ) . '">';
		    // Loop error codes and display errors
		   foreach ( $errors as $error_id => $error ) {
		        echo '<p class="pdd_error" id="pdd_error_' . $error_id . '"><strong>' . __( 'Error', 'pdd' ) . '</strong>: ' . $error . '</p>';
		   }
		echo '</div>';
		pdd_clear_errors();
	}
}
add_action( 'pdd_purchase_form_before_submit', 'pdd_print_errors' );
add_action( 'pdd_ajax_checkout_errors', 'pdd_print_errors' );

/**
 * Get Errors
 *
 * Retrieves all error messages stored during the checkout process.
 * If errors exist, they are returned.
 *
 * @since 1.0
 * @uses PDD_Session::get()
 * @return mixed array if errors are present, false if none found
 */
function pdd_get_errors() {
	return PDD()->session->get( 'pdd_errors' );
}

/**
 * Set Error
 *
 * Stores an error in a session var.
 *
 * @since 1.0
 * @uses PDD_Session::get()
 * @param int $error_id ID of the error being set
 * @param string $error_message Message to store with the error
 * @return void
 */
function pdd_set_error( $error_id, $error_message ) {
	$errors = pdd_get_errors();
	if ( ! $errors ) {
		$errors = array();
	}
	$errors[ $error_id ] = $error_message;
	PDD()->session->set( 'pdd_errors', $errors );
}

/**
 * Clears all stored errors.
 *
 * @since 1.0
 * @uses PDD_Session::set()
 * @return void
 */
function pdd_clear_errors() {
	PDD()->session->set( 'pdd_errors', null );
}

/**
 * Removes (unsets) a stored error
 *
 * @since 1.3.4
 * @uses PDD_Session::set()
 * @param int $error_id ID of the error being set
 * @return string
 */
function pdd_unset_error( $error_id ) {
	$errors = pdd_get_errors();
	if ( $errors ) {
		unset( $errors[ $error_id ] );
		PDD()->session->set( 'pdd_errors', $errors );
	}
}

/**
 * Register die handler for pdd_die()
 *
 * @author Sunny Ratilal
 * @since 1.6
 * @return void
 */
function _pdd_die_handler() {
	if ( defined( 'PDD_UNIT_TESTS' ) )
		return '_pdd_die_handler';
	else
		die();
}

/**
 * Wrapper function for wp_die(). This function adds filters for wp_die() which
 * kills execution of the script using wp_die(). This allows us to then to work
 * with functions using pdd_die() in the unit tests.
 *
 * @author Sunny Ratilal
 * @since 1.6
 * @return void
 */
function pdd_die() {
	add_filter( 'wp_die_ajax_handler', '_pdd_die_handler', 10, 3 );
	add_filter( 'wp_die_handler', '_pdd_die_handler', 10, 3 );
	wp_die('');
}