<?php
/**
 * Cart Actions
 *
 * @package     PDD
 * @subpackage  Cart
 * @copyright   Copyright (c) 2014, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Register Endpoints for the Cart
 *
 * These endpoints are used for adding/removing items from the cart
 *
 * @since 1.3.4
 * @return void
 */
function pdd_add_rewrite_endpoints( $rewrite_rules ) {
	add_rewrite_endpoint( 'pdd-add', EP_ALL );
	add_rewrite_endpoint( 'pdd-remove', EP_ALL );
}
add_action( 'init', 'pdd_add_rewrite_endpoints' );

/**
 * Process Cart Endpoints
 *
 * Listens for add/remove requests sent from the cart
 *
 * @since 1.3.4
 * @global $wp_query Used to access the current query that is being requested
 * @return void
*/
function pdd_process_cart_endpoints() {
	global $wp_query;

	// Adds an item to the cart with a /pdd-add/# URL
	if ( isset( $wp_query->query_vars['pdd-add'] ) ) {
		$download_id = absint( $wp_query->query_vars['pdd-add'] );
		$cart        = pdd_add_to_cart( $download_id, array() );

		wp_redirect( pdd_get_checkout_uri() ); pdd_die();
	}

	// Removes an item from the cart with a /pdd-remove/# URL
	if ( isset( $wp_query->query_vars['pdd-remove'] ) ) {
		$cart_key = absint( $wp_query->query_vars['pdd-remove'] );
		$cart     = pdd_remove_from_cart( $cart_key );

		wp_redirect( pdd_get_checkout_uri() ); pdd_die();
	}
}
add_action( 'template_redirect', 'pdd_process_cart_endpoints', 100 );

/**
 * Process the Add to Cart request
 *
 * @since 1.0
 *
 * @param $data
 */
function pdd_process_add_to_cart( $data ) {
	$download_id = absint( $data['download_id'] );
	$options     = isset( $data['pdd_options'] ) ? $data['pdd_options'] : array();
	$cart        = pdd_add_to_cart( $download_id, $options );

	if ( pdd_straight_to_checkout() && ! pdd_is_checkout() ) {
		wp_redirect( pdd_get_checkout_uri(), 303 );
		pdd_die();
	} else {
		wp_redirect( remove_query_arg( array( 'pdd_action', 'download_id', 'pdd_options' ) ) ); pdd_die();
	}
}
add_action( 'pdd_add_to_cart', 'pdd_process_add_to_cart' );

/**
 * Process the Remove from Cart request
 *
 * @since 1.0
 *
 * @param $data
 */
function pdd_process_remove_from_cart( $data ) {
	$cart_key = absint( $_GET['cart_item'] );
	pdd_remove_from_cart( $cart_key );
	wp_redirect( remove_query_arg( array( 'pdd_action', 'cart_item' ) ) ); pdd_die();
}
add_action( 'pdd_remove', 'pdd_process_remove_from_cart' );

/**
 * Process the Remove fee from Cart request
 *
 * @since 2.0
 *
 * @param $data
 */
function pdd_process_remove_fee_from_cart( $data ) {
	$fee = sanitize_text_field( $data['fee'] );
	PDD()->fees->remove_fee( $fee );
	wp_redirect( remove_query_arg( array( 'pdd_action', 'fee' ) ) ); pdd_die();
}
add_action( 'pdd_remove_fee', 'pdd_process_remove_fee_from_cart' );

/**
 * Process the Collection Purchase request
 *
 * @since 1.0
 *
 * @param $data
 */
function pdd_process_collection_purchase( $data ) {
	$taxonomy   = urldecode( $data['taxonomy'] );
	$terms      = urldecode( $data['terms'] );
	$cart_items = pdd_add_collection_to_cart( $taxonomy, $terms );
	wp_redirect( add_query_arg( 'added', '1', remove_query_arg( array( 'pdd_action', 'taxonomy', 'terms' ) ) ) );
	pdd_die();
}
add_action( 'pdd_purchase_collection', 'pdd_process_collection_purchase' );


/**
 * Process cart updates, primarily for quantities
 *
 * @since 1.7
 */
function pdd_process_cart_update( $data ) {

	foreach( $data['pdd-cart-downloads'] as $key => $cart_download_id ) {
		$options  = maybe_unserialize( stripslashes( $data['pdd-cart-download-' . $key . '-options'] ) );
		$quantity = absint( $data['pdd-cart-download-' . $key . '-quantity'] );
		pdd_set_cart_item_quantity( $cart_download_id, $quantity, $options );
	}

}
add_action( 'pdd_update_cart', 'pdd_process_cart_update' );

/**
 * Process cart save
 *
 * @since 1.8
 * @return void
 */
function pdd_process_cart_save( $data ) {

	$cart = pdd_save_cart();
	if( ! $cart ) {
		wp_redirect( pdd_get_checkout_uri() ); exit;
	}

}
add_action( 'pdd_save_cart', 'pdd_process_cart_save' );

/**
 * Process cart save
 *
 * @since 1.8
 * @return void
 */
function pdd_process_cart_restore( $data ) {

	$cart = pdd_restore_cart();
	if( ! is_wp_error( $cart ) ) {
		wp_redirect( pdd_get_checkout_uri() ); exit;
	}

}
add_action( 'pdd_restore_cart', 'pdd_process_cart_restore' );
