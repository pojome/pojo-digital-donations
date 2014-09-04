<?php
/**
 * Cart Template
 *
 * @package     PDD
 * @subpackage  Cart
 * @copyright   Copyright (c) 2014, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Builds the Cart by providing hooks and calling all the hooks for the Cart
 *
 * @since 1.0
 * @return void
 */
function pdd_checkout_cart() {

	// Check if the Update cart button should be shown
	if( pdd_item_quantities_enabled() ) {
		add_action( 'pdd_cart_footer_buttons', 'pdd_update_cart_button' );
	}

	// Check if the Save Cart button should be shown
	if( ! pdd_is_cart_saving_disabled() ) {
		add_action( 'pdd_cart_footer_buttons', 'pdd_save_cart_button' );
	}

	do_action( 'pdd_before_checkout_cart' );
	echo '<form id="pdd_checkout_cart_form" method="post">';
		echo '<div id="pdd_checkout_cart_wrap">';
			pdd_get_template_part( 'checkout_cart' );
		echo '</div>';
	echo '</form>';
	do_action( 'pdd_after_checkout_cart' );
}

/**
 * Renders the Shopping Cart
 *
 * @since 1.0
 *
 * @param bool $echo
 * @return string Fully formatted cart
 */
function pdd_shopping_cart( $echo = false ) {
	global $pdd_options;

	ob_start();

	do_action( 'pdd_before_cart' );

	pdd_get_template_part( 'widget', 'cart' );

	do_action( 'pdd_after_cart' );

	if ( $echo )
		echo ob_get_clean();
	else
		return ob_get_clean();
}

/**
 * Get Cart Item Template
 *
 * @since 1.0
 * @param int $cart_key Cart key
 * @param array $item Cart item
 * @param bool $ajax AJAX?
 * @return string Cart item
*/
function pdd_get_cart_item_template( $cart_key, $item, $ajax = false ) {
	global $post;

	$id = is_array( $item ) ? $item['id'] : $item;

	$remove_url = pdd_remove_item_url( $cart_key, $post, $ajax );
	$title      = get_the_title( $id );
	$options    = !empty( $item['options'] ) ? $item['options'] : array();
	$price      = pdd_get_cart_item_price( $id, $options );

	if ( ! empty( $options ) ) {
		$title .= ( pdd_has_variable_prices( $item['id'] ) ) ? ' <span class="pdd-cart-item-separator">-</span> ' . pdd_get_price_name( $id, $item['options'] ) : pdd_get_price_name( $id, $item['options'] );
	}

	ob_start();

	pdd_get_template_part( 'widget', 'cart-item' );

	$item = ob_get_clean();

	$item = str_replace( '{item_title}', $title, $item );
	$item = str_replace( '{item_amount}', pdd_currency_filter( pdd_format_amount( $price ) ), $item );
	$item = str_replace( '{cart_item_id}', absint( $cart_key ), $item );
	$item = str_replace( '{item_id}', absint( $id ), $item );
	$item = str_replace( '{remove_url}', $remove_url, $item );
  	$subtotal = '';
  	if ( $ajax ){
   	 $subtotal = pdd_currency_filter( pdd_format_amount( pdd_get_cart_subtotal() ) ) ;
  	}
 	$item = str_replace( '{subtotal}', $subtotal, $item );

	return apply_filters( 'pdd_cart_item', $item, $id );
}

/**
 * Returns the Empty Cart Message
 *
 * @since 1.0
 * @return string Cart is empty message
 */
function pdd_empty_cart_message() {
	return apply_filters( 'pdd_empty_cart_message', '<span class="pdd_empty_cart">' . __( 'Your cart is empty.', 'pdd' ) . '</span>' );
}

/**
 * Echoes the Empty Cart Message
 *
 * @since 1.0
 * @return void
 */
function pdd_empty_checkout_cart() {
	echo pdd_empty_cart_message();
}
add_action( 'pdd_cart_empty', 'pdd_empty_checkout_cart' );

/*
 * Calculate the number of columns in the cart table dynamically.
 *
 * @since 1.8
 * @return int The number of columns
 */
function pdd_checkout_cart_columns() {
	$head_first = did_action( 'pdd_checkout_table_header_first' );
	$head_last  = did_action( 'pdd_checkout_table_header_last' );
	$default    = 3;
	
	return apply_filters( 'pdd_checkout_cart_columns', $head_first + $head_last + $default );
}

/**
 * Display the "Save Cart" button on the checkout
 *
 * @since 1.8
 * @global $pdd_options Array of all the PDD Options
 * @return void
 */
function pdd_save_cart_button() {
	global $pdd_options;

	if ( pdd_is_cart_saving_disabled() )
		return;

	$color = isset( $pdd_options[ 'checkout_color' ] ) ? $pdd_options[ 'checkout_color' ] : 'blue';
	$color = ( $color == 'inherit' ) ? '' : $color;

	if ( pdd_is_cart_saved() ) : ?>
		<a class="pdd-cart-saving-button pdd-submit button<?php echo ' ' . $color; ?>" id="pdd-restore-cart-button" href="<?php echo add_query_arg( array( 'pdd_action' => 'restore_cart', 'pdd_cart_token' => pdd_get_cart_token() ) ) ?>"><?php _e( 'Restore Previous Cart', 'pdd' ); ?></a>
	<?php endif; ?>
	<a class="pdd-cart-saving-button pdd-submit button<?php echo ' ' . $color; ?>" id="pdd-save-cart-button" href="<?php echo add_query_arg( 'pdd_action', 'save_cart' ) ?>"><?php _e( 'Save Cart', 'pdd' ); ?></a>
	<?php
}

/**
 * Displays the restore cart link on the empty cart page, if a cart is saved
 *
 * @since 1.8
 * @return void
 */
function pdd_empty_cart_restore_cart_link() {

	if( pdd_is_cart_saving_disabled() )
		return;

	if( pdd_is_cart_saved() ) {
		echo ' <a class="pdd-cart-saving-link" id="pdd-restore-cart-link" href="' . add_query_arg( array( 'pdd_action' => 'restore_cart', 'pdd_cart_token' => pdd_get_cart_token() ) ) . '">' . __( 'Restore Previous Cart.', 'pdd' ) . '</a>';
	}
}
add_action( 'pdd_cart_empty', 'pdd_empty_cart_restore_cart_link' );

/**
 * Display the "Save Cart" button on the checkout
 *
 * @since 1.8
 * @global $pdd_options Array of all the PDD Options
 * @return void
 */
function pdd_update_cart_button() {
	global $pdd_options;

	if ( ! pdd_item_quantities_enabled() )
		return;

	$color = isset( $pdd_options[ 'checkout_color' ] ) ? $pdd_options[ 'checkout_color' ] : 'blue';
	$color = ( $color == 'inherit' ) ? '' : $color;
?>
	<input type="submit" name="pdd_update_cart_submit" class="pdd-submit pdd-no-js button<?php echo ' ' . $color; ?>" value="<?php _e( 'Update Cart', 'pdd' ); ?>"/>
	<input type="hidden" name="pdd_action" value="update_cart"/>
<?php

}

/**
 * Display the messages that are related to cart saving
 *
 * @since 1.8
 * @return void
 */
function pdd_display_cart_messages() {
	$messages = PDD()->session->get( 'pdd_cart_messages' );

	if ( $messages ) {
		$classes = apply_filters( 'pdd_error_class', array(
			'pdd_errors'
		) );
		echo '<div class="' . implode( ' ', $classes ) . '">';
		    // Loop message codes and display messages
		   foreach ( $messages as $message_id => $message ){
		        echo '<p class="pdd_error" id="pdd_msg_' . $message_id . '">' . $message . '</p>';
		   }
		echo '</div>';

		// Remove all of the cart saving messages
		PDD()->session->set( 'pdd_cart_messages', null );
	}
}
add_action( 'pdd_before_checkout_cart', 'pdd_display_cart_messages' );
