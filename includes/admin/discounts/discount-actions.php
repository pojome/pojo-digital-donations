<?php
/**
 * Discount Actions
 *
 * @package     PDD
 * @subpackage  Admin/Discounts
 * @copyright   Copyright (c) 2014, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0.8.1
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Sets up and stores a new discount code
 *
 * @since 1.0
 * @param array $data Discount code data
 * @uses pdd_store_discount()
 * @return void
 */
function pdd_add_discount( $data ) {
	if ( isset( $data['pdd-discount-nonce'] ) && wp_verify_nonce( $data['pdd-discount-nonce'], 'pdd_discount_nonce' ) ) {
		// Setup the discount code details
		$posted = array();

		foreach ( $data as $key => $value ) {
			if ( $key != 'pdd-discount-nonce' && $key != 'pdd-action' && $key != 'pdd-redirect' ) {
				if ( is_string( $value ) || is_int( $value ) )
					$posted[ $key ] = strip_tags( addslashes( $value ) );
				elseif ( is_array( $value ) )
					$posted[ $key ] = array_map( 'absint', $value );
			}
		}

		// Set the discount code's default status to active
		$posted['status'] = 'active';
		if ( pdd_store_discount( $posted ) ) {
			wp_redirect( add_query_arg( 'pdd-message', 'discount_added', $data['pdd-redirect'] ) ); pdd_die();
		} else {
			wp_redirect( add_query_arg( 'pdd-message', 'discount_add_failed', $data['pdd-redirect'] ) ); pdd_die();
		}		
	}
}
add_action( 'pdd_add_discount', 'pdd_add_discount' );

/**
 * Saves an edited discount
 *
 * @since 1.0
 * @param array $data Discount code data
 * @return void
 */
function pdd_edit_discount( $data ) {
	if ( isset( $data['pdd-discount-nonce'] ) && wp_verify_nonce( $data['pdd-discount-nonce'], 'pdd_discount_nonce' ) ) {
		// Setup the discount code details
		$discount = array();

		foreach ( $data as $key => $value ) {
			if ( $key != 'pdd-discount-nonce' && $key != 'pdd-action' && $key != 'discount-id' && $key != 'pdd-redirect' ) {
				if ( is_string( $value ) || is_int( $value ) )
					$discount[ $key ] = strip_tags( addslashes( $value ) );
				elseif ( is_array( $value ) )
					$discount[ $key ] = array_map( 'absint', $value );
			}
		}

		$old_discount = pdd_get_discount_by( 'code', $data['code'] );
		$discount['uses'] = pdd_get_discount_uses( $old_discount->ID );

		if ( pdd_store_discount( $discount, $data['discount-id'] ) ) {
			wp_redirect( add_query_arg( 'pdd-message', 'discount_updated', $data['pdd-redirect'] ) ); pdd_die();
		} else {
			wp_redirect( add_query_arg( 'pdd-message', 'discount_update_failed', $data['pdd-redirect'] ) ); pdd_die();
		}
	}
}
add_action( 'pdd_edit_discount', 'pdd_edit_discount' );

/**
 * Listens for when a discount delete button is clicked and deletes the
 * discount code
 *
 * @since 1.0
 * @param array $data Discount code data
 * @uses pdd_remove_discount()
 * @return void
 */
function pdd_delete_discount( $data ) {
	if ( ! isset( $data['_wpnonce'] ) || ! wp_verify_nonce( $data['_wpnonce'], 'pdd_discount_nonce' ) )
		wp_die( __( 'Trying to cheat or something?', 'pdd' ), __( 'Error', 'pdd' ) );

	$discount_id = $data['discount'];
	pdd_remove_discount( $discount_id );
}
add_action( 'pdd_delete_discount', 'pdd_delete_discount' );

/**
 * Activates Discount Code
 *
 * Sets a discount code's status to active
 *
 * @since 1.0
 * @param array $data Discount code data
 * @uses pdd_update_discount_status()
 * @return void
 */
function pdd_activate_discount( $data ) {
	$id = absint( $data['discount'] );
	pdd_update_discount_status( $id, 'active' );
}
add_action( 'pdd_activate_discount', 'pdd_activate_discount' );

/**
 * Deactivate Discount
 *
 * Sets a discount code's status to deactivate
 *
 * @since 1.0
 * @param array $data Discount code data
 * @uses pdd_update_discount_status()
 * @return void
*/
function pdd_deactivate_discount( $data) {
	$id = absint( $data['discount'] );
	pdd_update_discount_status( $id, 'inactive' );
}
add_action( 'pdd_deactivate_discount', 'pdd_deactivate_discount' );
