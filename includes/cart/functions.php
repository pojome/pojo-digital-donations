<?php
/**
 * Cart Functions
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
 * Get the contents of the cart
 *
 * @since 1.0
 * @return mixed array if cart isn't empty | false otherwise
 */
function pdd_get_cart_contents() {
	$cart = PDD()->session->get( 'pdd_cart' );
	$cart = ! empty( $cart ) ? array_values( $cart ) : false;
	return apply_filters( 'pdd_cart_contents', $cart );
}

/**
 * Retrieve the Cart Content Details
 *
 * Includes prices, tax, etc of all items.
 *
 * @since 1.0
 * @return array $details Cart content details
 */
function pdd_get_cart_content_details() {

	$cart_items = pdd_get_cart_contents();

	if ( empty( $cart_items ) ) {
		return false;
	}

	$details  = array();

	foreach( $cart_items as $key => $item ) {

		$item_price = pdd_get_cart_item_price( $item['id'], $item['options'] );
		$discount   = apply_filters( 'pdd_get_cart_content_details_item_discount_amount', pdd_get_cart_item_discount_amount( $item ), $item );
		$tax        = pdd_get_cart_item_tax( $item );
		$quantity   = pdd_get_cart_item_quantity( $item['id'], $item['options'] );
		$fees       = pdd_get_cart_fees( 'fee', $item['id'] );

		$subtotal   = $item_price * $quantity;
		$tax        = $tax * $quantity;
		$total      = round( ( $subtotal - $discount + $tax ), 2 );

		$details[ $key ]  = array(
			'name'        => get_the_title( $item['id'] ),
			'id'          => $item['id'],
			'item_number' => $item,
			'item_price'  => round( $item_price, 2 ),
			'quantity'    => $quantity,
			'discount'    => round( $discount, 2 ),
			'subtotal'    => round( $subtotal, 2 ),
			'tax'         => round( $tax, 2 ),
			'price'       => $total,
			'fees'        => $fees
		);

	}

	return $details;
}

/**
 * Get Cart Quantity
 *
 * @since 1.0
 * @return int Quantity of items in the cart
 */
function pdd_get_cart_quantity() {
	return ( $cart = pdd_get_cart_contents() ) ? count( $cart ) : 0;
}

/**
 * Add To Cart
 *
 * Adds a download ID to the shopping cart.
 *
 * @since 1.0
 *
 * @param int $download_id Download IDs to be added to the cart
 * @param array $options Array of options, such as variable price
 *
 * @return string Cart key of the new item
 */
function pdd_add_to_cart( $download_id, $options = array() ) {

	$cart = apply_filters( 'pdd_pre_add_to_cart_contents', pdd_get_cart_contents() );

	$download = get_post( $download_id );

	if( 'download' != $download->post_type )
		return; // Not a download product

	if ( ! current_user_can( 'edit_post', $download->ID ) && ( $download->post_status == 'draft' || $download->post_status == 'pending' ) )
		return; // Do not allow draft/pending to be purchased if can't edit. Fixes #1056

	do_action( 'pdd_pre_add_to_cart', $download_id, $options );

	if ( pdd_has_variable_prices( $download_id )  && ! isset( $options['price_id'] ) ) {
		// Forces to the first price ID if none is specified and download has variable prices
		$options['price_id'] = '0';
	}

	$item     = array();
	$to_add   = array();
	$new_item = array();

	if( isset( $options['quantity'] ) ) {
		$quantity = absint( preg_replace( '/[^0-9\.]/', '', $options['quantity'] ) );
		unset( $options['quantity'] );
	} else {
		$quantity = 1;
	}

	if ( isset( $options['price_id'] ) && is_array( $options['price_id'] ) ) {

		// Process multiple price options at once
		foreach ( $options['price_id'] as $price ) {

			$item = array(
				'id'           => $download_id,
				'options'      => array(
					'price_id' => preg_replace( '/[^0-9\.]/', '', $price )
				),
				'quantity'     => $quantity
			);

		}

	} else {

		// Sanitize price IDs
		foreach( $options as $key => $option ) {

			if( 'price_id' == $key ) {
				$options[ $key ] = preg_replace( '/[^0-9\.-]/', '', $option );
			}

		}

		// Add a single item
		$item = array(
			'id'       => $download_id,
			'options'  => $options,
			'quantity' => $quantity
		);
	}

	$to_add = apply_filters( 'pdd_add_to_cart_item', $item );

	if ( ! is_array( $to_add ) )
		return;

	if ( ! isset( $to_add['id'] ) || empty( $to_add['id'] ) )
		return;

	$new_item[] = $to_add;

	if ( is_array( $cart ) ) {
		$cart = array_merge( $cart, $new_item );
	} else {
		$cart = $new_item;
	}

	PDD()->session->set( 'pdd_cart', $cart );

	do_action( 'pdd_post_add_to_cart', $download_id, $options );

	// Clear all the checkout errors, if any
	pdd_clear_errors();

	return count( $cart ) - 1;
}

/**
 * Removes a Download from the Cart
 *
 * @since 1.0
 * @param int $cart_key the cart key to remove. This key is the numerical index of the item contained within the cart array.
 * @return array Updated cart items
 */
function pdd_remove_from_cart( $cart_key ) {
	$cart = pdd_get_cart_contents();

	do_action( 'pdd_pre_remove_from_cart', $cart_key );

	if ( ! is_array( $cart ) ) {
		return true; // Empty cart
	} else {
		$item_id = isset( $cart[ $cart_key ][ 'id' ] ) ? $cart[ $cart_key ][ 'id' ] : null;
		unset( $cart[ $cart_key ] );
	}

	PDD()->session->set( 'pdd_cart', $cart );

	do_action( 'pdd_post_remove_from_cart', $cart_key, $item_id );

	// Clear all the checkout errors, if any
	pdd_clear_errors();

	return $cart; // The updated cart items
}

/**
 * Checks to see if an item is already in the cart and returns a boolean
 *
 * @since 1.0
 *
 * @param int   $download_id ID of the download to remove
 * @param array $options
 * @return bool Item in the cart or not?
 */
function pdd_item_in_cart( $download_id = 0, $options = array() ) {
	$cart_items = pdd_get_cart_contents();

	$ret = false;

	if ( is_array( $cart_items ) ) {
		foreach ( $cart_items as $item ) {
			if ( $item['id'] == $download_id ) {
				if ( isset( $options['price_id'] ) && isset( $item['options']['price_id'] ) ) {
					if ( $options['price_id'] == $item['options']['price_id'] ) {
						$ret = true;
						break;
					}
				} else {
					$ret = true;
					break;
				}
			}
		}
	}

	return (bool) apply_filters( 'pdd_item_in_cart', $ret, $download_id, $options );
}

/**
 * Get the Item Position in Cart
 *
 * @since 1.0.7.2
 *
 * @param int   $download_id ID of the download to get position of
 * @param array $options array of price options
 * @return bool|int|string false if empty cart |  position of the item in the cart
 */
function pdd_get_item_position_in_cart( $download_id = 0, $options = array() ) {
	$cart_items = pdd_get_cart_contents();
	if ( ! is_array( $cart_items ) ) {
		return false; // Empty cart
	} else {
		foreach ( $cart_items as $position => $item ) {
			if ( $item['id'] == $download_id ) {
				if ( isset( $options['price_id'] ) && isset( $item['options']['price_id'] ) ) {
					if ( (int) $options['price_id'] == (int) $item['options']['price_id'] ) {
						return $position;
					}
				} else {
					return $position;
				}
			}
		}
	}
	return false; // Not found
}


/**
 * Check if quantities are enabled
 *
 * @since 1.7
 * @return bool
 */
function pdd_item_quantities_enabled() {
	global $pdd_options;
	$ret = isset( $pdd_options['item_quantities'] );
	return apply_filters( 'pdd_item_quantities_enabled', $ret );
}

/**
 * Set Cart Item Quantity
 *
 * @since 1.7
 *
 * @param int   $download_id Download (cart item) ID number
 * @param int   $quantity
 * @param array $options Download options, such as price ID
 * @return mixed New Cart array
 */
function pdd_set_cart_item_quantity( $download_id = 0, $quantity = 1, $options = array() ) {
	$cart = pdd_get_cart_contents();
	$key  = pdd_get_item_position_in_cart( $download_id, $options );

	if( $quantity < 1 )
		$quantity = 1;

	$cart[ $key ]['quantity'] = $quantity;
	PDD()->session->set( 'pdd_cart', $cart );
	return $cart;

}


/**
 * Get Cart Item Quantity
 *
 * @since 1.0
 * @param int $download_id Download (cart item) ID number
 * @param array $options Download options, such as price ID
 * @return int $quantity Cart item quantity
 */
function pdd_get_cart_item_quantity( $download_id = 0, $options = array() ) {
	$cart     = pdd_get_cart_contents();
	$key      = pdd_get_item_position_in_cart( $download_id, $options );
	$quantity = isset( $cart[ $key ]['quantity'] ) && pdd_item_quantities_enabled() ? $cart[ $key ]['quantity'] : 1;
	if( $quantity < 1 )
		$quantity = 1;
	return apply_filters( 'pdd_get_cart_item_quantity', $quantity, $download_id, $options );
}

/**
 * Get Cart Item Price
 *
 * @since 1.0
 *
 * @param int   $item_id Download (cart item) ID number
 * @param array $options Optional parameters, used for defining variable prices
 * @return string Fully formatted price
 */
function pdd_cart_item_price( $item_id = 0, $options = array() ) {
	global $pdd_options;

	$tax_on_prices = pdd_prices_show_tax_on_checkout();

	$price = pdd_get_cart_item_price( $item_id, $options, $tax_on_prices );
	$price = pdd_currency_filter( pdd_format_amount( $price ) );
	$label = '';
	if( pdd_display_tax_rate() ) {
		$label = '&nbsp;&ndash;&nbsp;';
		if( pdd_prices_show_tax_on_checkout() ) {
			$label .= sprintf( __( 'includes %s tax', 'pdd' ), pdd_get_formatted_tax_rate() );
		} else {
			$label .= sprintf( __( 'excludes %s tax', 'pdd' ), pdd_get_formatted_tax_rate() );
		}

	}

	return esc_html( $price . $label );
}

/**
 * Get Cart Item Price
 *
 * Gets the price of the cart item. Always exclusive of taxes
 *
 * Do not use this for getting the final price (with taxes and discounts) of an item.
 * Use pdd_get_cart_item_final_price()
 *
 * @since 1.0
 * @param int   $item_id Download ID number
 * @param array $options Optional parameters, used for defining variable prices
 * @param bool $include_tax Whether the price should include taxes
 * @return float|bool Price for this item
 */
function pdd_get_cart_item_price( $download_id = 0, $options = array(), $include_taxes = false ) {
	global $pdd_options;

	$price = 0;

	if ( pdd_has_variable_prices( $download_id ) && ! empty( $options ) ) {
		$prices = pdd_get_variable_prices( $download_id );
		if ( $prices ) {
			$price = isset( $prices[ $options['price_id'] ] ) ? $prices[ $options['price_id'] ]['amount'] : 0;
		}
	}

	if( ! $price ) {
		// Get the standard Download price if not using variable prices
		$price = pdd_get_download_price( $download_id );
	}

	if( ! pdd_download_is_tax_exclusive( $download_id ) ) {

		if( pdd_prices_include_tax() && ! $include_taxes ) {
			// If price is entered with tax, we have to deduct the taxed amount from the price to determine the actual price
			$price -= pdd_calculate_tax( $price );
		} elseif( ! pdd_prices_include_tax() && $include_taxes ) {
			$price += pdd_calculate_tax( $price );
		}

	}

	return apply_filters( 'pdd_cart_item_price', $price, $download_id, $options, $include_taxes );
}

/**
 * Get cart item's final price
 *
 * Gets the amount after taxes and discounts
 *
 * @since 1.9
 * @param int    $item_key Cart item key
 * @return float Final price for the item
 */
function pdd_get_cart_item_final_price( $item_key = 0 ) {
	$items = pdd_get_cart_content_details();
	$final = $items[ $item_key ]['price'];
	return apply_filters( 'pdd_cart_item_final_price', $final, $item_key );
}

/**
 * Get cart item tax
 *
 * @since 1.9
 * @param array $item Cart item array
 * @return float Tax amount
 */
function pdd_get_cart_item_tax( $item = array() ) {

	$tax   = 0;
	$price = false;

	if( ! pdd_download_is_tax_exclusive( $item['id'] ) ) {

		if ( pdd_has_variable_prices( $item['id'] ) && ! empty( $item['options'] ) ) {
			$prices = pdd_get_variable_prices( $item['id'] );
			if ( $prices ) {
				$price = isset( $prices[ $item['options']['price_id'] ] ) ? $prices[ $item['options']['price_id'] ]['amount'] : false;
			}
		}

		if( ! $price ) {
			// Get the standard Download price if not using variable prices
			$price = pdd_get_download_price( $item['id'] );
		}

		if( pdd_taxes_after_discounts() ) {
			$price -= apply_filters( 'pdd_get_cart_item_tax_item_discount_amount', pdd_get_cart_item_discount_amount( $item ), $item );
		}

		$country = ! empty( $_POST['billing_country'] ) ? $_POST['billing_country'] : false;
		$state   = ! empty( $_POST['card_state'] )      ? $_POST['card_state']      : false;

		$tax = pdd_calculate_tax( $price, $country, $state );

	}

	return apply_filters( 'pdd_get_cart_item_tax', $tax, $item['id'], $item );
}

/**
 * Get Price Name
 *
 * Gets the name of the specified price option,
 * for variable pricing only.
 *
 * @since 1.0
 *
 * @param       $download_id Download ID number
 * @param array $options Optional parameters, used for defining variable prices
 * @return mixed|void Name of the price option
 */
function pdd_get_price_name( $download_id = 0, $options = array() ) {
	$return = false;
	if( pdd_has_variable_prices( $download_id ) && ! empty( $options ) ) {
		$prices = pdd_get_variable_prices( $download_id );
		$name   = false;
		if( $prices ) {
			if( isset( $prices[ $options['price_id'] ] ) )
				$name = $prices[ $options['price_id'] ]['name'];
		}
		$return = $name;
	}
	return apply_filters( 'pdd_get_price_name', $return, $download_id, $options );
}

/**
 * Get cart item price id
 *
 * @since 1.0
 *
 * @param array $item Cart item array
 * @return int Price id
 */
function pdd_get_cart_item_price_id( $item = array() ) {
	if( isset( $item['item_number'] ) ) {
		$price_id = isset( $item['item_number']['options']['price_id'] ) ? $item['item_number']['options']['price_id'] : null;
	} else {
		$price_id = isset( $item['options']['price_id'] ) ? $item['options']['price_id'] : null;
	}
	return $price_id;
}

/**
 * Get cart item price name
 *
 * @since 1.8
 * @param int $item Cart item array
 * @return string Price name
 */
function pdd_get_cart_item_price_name( $item = array() ) {
	$price_id = (int) pdd_get_cart_item_price_id( $item );
	$prices   = pdd_get_variable_prices( $item['id'] );
	$name     = ! empty( $prices ) ? $prices[ $price_id ]['name'] : '';
	return apply_filters( 'pdd_get_cart_item_price_name', $name, $item['id'], $price_id, $item );
}

/**
 * Cart Subtotal
 *
 * Shows the subtotal for the shopping cart (no taxes)
 *
 * @since 1.4
 * @global $pdd_options Array of all the PDD Options
 * @return float Total amount before taxes fully formatted
 */
function pdd_cart_subtotal() {
	global $pdd_options;

	$price = esc_html( pdd_currency_filter( pdd_format_amount( pdd_get_cart_subtotal() ) ) );

	// Todo - Show tax labels here (if needed)

	return $price;
}

/**
 * Get Cart Subtotal
 *
 * Gets the total price amount in the cart before taxes and before any discounts
 * uses pdd_get_cart_contents().
 *
 * @since 1.3.3
 * @global $pdd_options Array of all the PDD Options
 * @return float Total amount before taxes
 */
function pdd_get_cart_subtotal() {
	global $pdd_options;

	$subtotal = 0.00;
	$items    = pdd_get_cart_content_details();

	if( $items ) {

		$prices   = wp_list_pluck( $items, 'subtotal' );

		if( is_array( $prices ) ) {
			$subtotal = array_sum( $prices );
		} else {
			$subtotal = 0.00;
		}

		if( $subtotal < 0 ) {
			$subtotal = 0.00;
		}

	}

	return apply_filters( 'pdd_get_cart_subtotal', $subtotal );
}

/**
 * Get Total Cart Amount
 *
 * Returns amount after taxes and discounts
 *
 * @since 1.4.1
 * @global $pdd_options Array of all the PDD Options
 * @param bool $discounts Array of discounts to apply (needed during AJAX calls)
 * @return float Cart amount
 */
function pdd_get_cart_total( $discounts = false ) {
	global $pdd_options;

	$subtotal = pdd_get_cart_subtotal();
	$fees     = pdd_get_cart_fee_total();
	$cart_tax = pdd_get_cart_tax();
	$discount = pdd_get_cart_discounted_amount();
	$total    = $subtotal + $fees + $cart_tax - $discount;

	if( $total < 0 )
		$total = 0.00;

	return (float) apply_filters( 'pdd_get_cart_total', $total );
}


/**
 * Get Total Cart Amount
 *
 * Gets the fully formatted total price amount in the cart.
 * uses pdd_get_cart_amount().
 *
 * @global $pdd_options Array of all the PDD Options
 * @since 1.3.3
 *
 * @param bool $echo
 * @return mixed|string|void
 */
function pdd_cart_total( $echo = true ) {
	global $pdd_options;

	$total = apply_filters( 'pdd_cart_total', pdd_currency_filter( pdd_format_amount( pdd_get_cart_total() ) ) );

	// Todo - Show tax labels here (if needed)

	if ( ! $echo ) {
		return $total;
	}

	echo $total;
}

/**
 * Check if cart has fees applied
 *
 * Just a simple wrapper function for PDD_Fees::has_fees()
 *
 * @since 1.5
 * @param string $type
 * @uses PDD()->fees->has_fees()
 * @return bool Whether the cart has fees applied or not
 */
function pdd_cart_has_fees( $type = 'all' ) {
	return PDD()->fees->has_fees( $type );
}

/**
 * Get Cart Fees
 *
 * Just a simple wrapper function for PDD_Fees::get_fees()
 *
 * @since 1.5
 * @param string $type
 * @uses PDD()->fees->get_fees()
 * @return array All the cart fees that have been applied
 */
function pdd_get_cart_fees( $type = 'all' ) {
	return PDD()->fees->get_fees( $type );
}

/**
 * Get Cart Fee Total
 *
 * Just a simple wrapper function for PDD_Fees::total()
 *
 * @since 1.5
 * @uses PDD()->fees->total()
 * @return float Total Cart Fees
 */
function pdd_get_cart_fee_total() {
	return PDD()->fees->total();
}

/**
 * Get cart tax on Fees
 *
 * @since 2.0
 * @uses PDD()->fees->get_fees()
 * @return float Total Cart tax on Fees
 */
function pdd_get_cart_fee_tax() {

	$tax  = 0;
	$fees = pdd_get_cart_fees();

	if( $fees ) {

		foreach ( $fees as $fee_id => $fee ) {

			if( ! empty( $fee['no_tax' ] ) ) {
				continue;
			}

			$tax += pdd_calculate_tax( $fee['amount'] );

		}
	}

	return apply_filters( 'pdd_get_cart_fee_tax', $tax );
}

/**
 * Get Purchase Summary
 *
 * Retrieves the purchase summary.
 *
 * @since       1.0
 *
 * @param      $purchase_data
 * @param bool $email
 * @return string
 */
function pdd_get_purchase_summary( $purchase_data, $email = true ) {
	$summary = '';

	if ( $email ) {
		$summary .= $purchase_data['user_email'] . ' - ';
	}

	foreach ( $purchase_data['downloads'] as $download ) {
		$summary .= get_the_title( $download['id'] ) . ', ';
	}

	$summary = substr( $summary, 0, -2 );

	return $summary;
}

/**
 * Gets the total tax amount for the cart contents
 *
 * @since 1.2.3
 *
 * @return mixed|void Total tax amount
 */
function pdd_get_cart_tax() {

	$cart_tax = 0;
	$items    = pdd_get_cart_content_details();

	if( $items ) {

		$taxes    = wp_list_pluck( $items, 'tax' );

		if( is_array( $taxes ) ) {
			$cart_tax = array_sum( $taxes );
		}

	}

	$cart_tax += pdd_get_cart_fee_tax();

	return apply_filters( 'pdd_get_cart_tax', $cart_tax );
}

/**
 * Gets the total tax amount for the cart contents in a fully formatted way
 *
 * @since 1.2.3
 * @param bool $echo Whether to echo the tax amount or not (default: false)
 * @return string Total tax amount (if $echo is set to true)
 */
function pdd_cart_tax( $echo = false ) {
	$cart_tax = 0;

	if ( pdd_is_cart_taxed() ) {
		$cart_tax = pdd_get_cart_tax();
		$cart_tax = pdd_currency_filter( pdd_format_amount( $cart_tax ) );
	}

	$tax = apply_filters( 'pdd_cart_tax', $cart_tax );

	if ( ! $echo ) {
		return $tax;
	}

	echo $tax;
}

/**
 * Add Collection to Cart
 *
 * Adds all downloads within a taxonomy term to the cart.
 *
 * @since 1.0.6
 * @param string $taxonomy Name of the taxonomy
 * @param mixed $terms Slug or ID of the term from which to add ites | An array of terms
 * @return array Array of IDs for each item added to the cart
 */
function pdd_add_collection_to_cart( $taxonomy, $terms ) {
	if ( ! is_string( $taxonomy ) ) return false;

	if( is_numeric( $terms ) ) {
		$terms = get_term( $terms, $taxonomy );
		$terms = $terms->slug;
	}

	$cart_item_ids = array();

	$args = array(
		'post_type' => 'download',
		'posts_per_page' => -1,
		$taxonomy => $terms
	);

	$items = get_posts( $args );
	if ( $items ) {
		foreach ( $items as $item ) {
			pdd_add_to_cart( $item->ID );
			$cart_item_ids[] = $item->ID;
		}
	}
	return $cart_item_ids;
}

/**
 * Returns the URL to remove an item from the cart
 *
 * @since 1.0
 * @global $post
 * @param int $cart_key Cart item key
 * @param object $post Download (post) object
 * @param bool $ajax AJAX?
 * @return string $remove_url URL to remove the cart item
 */
function pdd_remove_item_url( $cart_key, $post, $ajax = false ) {
	
	global $wp_query;

	if ( defined('DOING_AJAX') ){
		$current_page = pdd_get_checkout_uri();
	} else if( is_page() ) {
		$current_page = add_query_arg( 'page_id', $wp_query->queried_object_id, home_url( 'index.php' ) );
	} else if( is_singular() ) {
		$current_page = add_query_arg( 'p', $wp_query->queried_object_id, home_url( 'index.php' ) );
	} else {
		$current_page = pdd_get_current_page_url();
	}
	$remove_url = add_query_arg( array( 'cart_item' => $cart_key, 'pdd_action' => 'remove' ), $current_page );

	return apply_filters( 'pdd_remove_item_url', $remove_url );
}

/**
 * Returns the URL to remove an item from the cart
 *
 * @since 1.0
 * @global $post
 * @param string $fee_id Fee ID
 * @return string $remove_url URL to remove the cart item
 */
function pdd_remove_cart_fee_url( $fee_id = '') {
	global $post;

	if ( defined('DOING_AJAX') ){
		$current_page = pdd_get_checkout_uri();
	} else if( is_page() ) {
		$current_page = add_query_arg( 'page_id', $post->ID, home_url( 'index.php' ) );
	} else if( is_singular() ) {
		$current_page = add_query_arg( 'p', $post->ID, home_url( 'index.php' ) );
	} else {
		$current_page = pdd_get_current_page_url();
	}
	$remove_url = add_query_arg( array( 'fee' => $fee_id, 'pdd_action' => 'remove_fee' ), $current_page );

	return apply_filters( 'pdd_remove_fee_url', $remove_url );
}

/**
 * Show Added To Cart Messages
 *
 * @since 1.0
 * @param int $download_id Download (Post) ID
 * @return void
 */
function pdd_show_added_to_cart_messages( $download_id ) {
	if ( isset( $_POST['pdd_action'] ) && $_POST['pdd_action'] == 'add_to_cart' ) {
		if ( $download_id != absint( $_POST['download_id'] ) )
			$download_id = absint( $_POST['download_id'] );

		$alert = '<div class="pdd_added_to_cart_alert">'
		. sprintf( __('You have successfully added %s to your shopping cart.', 'pdd'), get_the_title( $download_id ) )
		. ' <a href="' . pdd_get_checkout_uri() . '" class="pdd_alert_checkout_link">' . __('Checkout.', 'pdd') . '</a>'
		. '</div>';

		echo apply_filters( 'pdd_show_added_to_cart_messages', $alert );
	}
}
add_action('pdd_after_download_content', 'pdd_show_added_to_cart_messages');

/**
 * Empties the Cart
 *
 * @since 1.0
 * @uses PDD()->session->set()
 * @return void
 */
function pdd_empty_cart() {
	// Remove cart contents
	PDD()->session->set( 'pdd_cart', NULL );

	// Remove all cart fees
	PDD()->session->set( 'pdd_cart_fees', NULL );

	// Remove any active discounts
	pdd_unset_all_cart_discounts();

	do_action( 'pdd_empty_cart' );
}

/**
 * Store Purchase Data in Sessions
 *
 * Used for storing info about purchase
 *
 * @since 1.1.5
 *
 * @param $purchase_data
 *
 * @uses PDD()->session->set()
 */
function pdd_set_purchase_session( $purchase_data ) {
	PDD()->session->set( 'pdd_purchase', $purchase_data );
}

/**
 * Retrieve Purchase Data from Session
 *
 * Used for retrieving info about purchase
 * after completing a purchase
 *
 * @since 1.1.5
 * @uses PDD()->session->get()
 * @return mixed array | false
 */
function pdd_get_purchase_session() {
	return PDD()->session->get( 'pdd_purchase' );
}

/**
 * Checks if cart saving has been disabled
 *
 * @since 1.8
 * @global $pdd_options
 * @return bool Whether or not cart saving has been disabled
 */
function pdd_is_cart_saving_disabled() {
	global $pdd_options;

	return apply_filters( 'pdd_cart_saving_disabled', ! isset( $pdd_options['enable_cart_saving'] ) );
}

/**
 * Checks if a cart has been saved
 *
 * @since 1.8
 * @return bool
 */
function pdd_is_cart_saved() {

	if( pdd_is_cart_saving_disabled() )
		return false;

	if ( is_user_logged_in() ) {

		$saved_cart = get_user_meta( get_current_user_id(), 'pdd_saved_cart', true );

		// Check that a cart exists
		if( ! $saved_cart )
			return false;

		// Check that the saved cart is not the same as the current cart
		if ( $saved_cart === PDD()->session->get( 'pdd_cart' ) )
			return false;

		return true;

	} else {

		// Check that a saved cart exists
		if ( ! isset( $_COOKIE['pdd_saved_cart'] ) )
			return false;

		// Check that the saved cart is not the same as the current cart
		if ( maybe_unserialize( stripslashes( $_COOKIE['pdd_saved_cart'] ) ) === PDD()->session->get( 'pdd_cart' ) )
			return false;

		return true;

	}

	return false;
}

/**
 * Process the Cart Save
 *
 * @since 1.8
 * @return bool
 */
function pdd_save_cart() {
	global $pdd_options;

	if ( pdd_is_cart_saving_disabled() )
		return false;

	$user_id  = get_current_user_id();
	$cart     = PDD()->session->get( 'pdd_cart' );
	$token    = pdd_generate_cart_token();
	$messages = PDD()->session->get( 'pdd_cart_messages' );

	if ( is_user_logged_in() ) {

		update_user_meta( $user_id, 'pdd_saved_cart', $cart, false );
		update_user_meta( $user_id, 'pdd_cart_token', $token, false );

	} else {

		$cart = serialize( $cart );

		setcookie( 'pdd_saved_cart', $cart, time()+3600*24*7, COOKIEPATH, COOKIE_DOMAIN );
		setcookie( 'pdd_cart_token', $token, time()+3600*24*7, COOKIEPATH, COOKIE_DOMAIN );

	}

	$messages = PDD()->session->get( 'pdd_cart_messages' );

	if ( ! $messages )
		$messages = array();

	$messages['pdd_cart_save_successful'] = sprintf(
		'<strong>%1$s</strong>: %2$s',
		__( 'Success', 'pdd' ),
		__( 'Cart saved successfully. You can restore your cart using this URL:', 'pdd' ) . ' ' . '<a href="' .  pdd_get_checkout_uri() . '?pdd_action=restore_cart&pdd_cart_token=' . $token . '">' .  pdd_get_checkout_uri() . '?pdd_action=restore_cart&pdd_cart_token=' . $token . '</a>'
	);

	PDD()->session->set( 'pdd_cart_messages', $messages );

	if( $cart ) {
		return true;
	}

	return false;
}


/**
 * Process the Cart Restoration
 *
 * @since 1.8
 * @return mixed || false Returns false if cart saving is disabled
 */
function pdd_restore_cart() {

	if ( pdd_is_cart_saving_disabled() )
		return false;

	$user_id    = get_current_user_id();
	$saved_cart = get_user_meta( $user_id, 'pdd_saved_cart', true );
	$token      = pdd_get_cart_token();

	if ( is_user_logged_in() && $saved_cart ) {

		$messages = PDD()->session->get( 'pdd_cart_messages' );

		if ( ! $messages )
			$messages = array();

		if ( isset( $_GET['pdd_cart_token'] ) && $_GET['pdd_cart_token'] != $token ) {

			$messages['pdd_cart_restoration_failed'] = sprintf( '<strong>%1$s</strong>: %2$s', __( 'Error', 'pdd' ), __( 'Cart restoration failed. Invalid token.', 'pdd' ) );
			PDD()->session->set( 'pdd_cart_messages', $messages );

			return new WP_Error( 'invalid_cart_token', __( 'The cart cannot be restored. Invalid token.', 'pdd' ) );
		}

		delete_user_meta( $user_id, 'pdd_saved_cart' );
		delete_user_meta( $user_id, 'pdd_cart_token' );

	} elseif ( ! is_user_logged_in() && isset( $_COOKIE['pdd_saved_cart'] ) && $token ) {

		$saved_cart = $_COOKIE['pdd_saved_cart'];

		if ( $_GET['pdd_cart_token'] != $token ) {

			$messages['pdd_cart_restoration_failed'] = sprintf( '<strong>%1$s</strong>: %2$s', __( 'Error', 'pdd' ), __( 'Cart restoration failed. Invalid token.', 'pdd' ) );
			PDD()->session->set( 'pdd_cart_messages', $messages );

			return new WP_Error( 'invalid_cart_token', __( 'The cart cannot be restored. Invalid token.', 'pdd' ) );
		}

		$saved_cart = maybe_unserialize( stripslashes( $saved_cart ) );

		setcookie( 'pdd_saved_cart', '', time()-3600, COOKIEPATH, COOKIE_DOMAIN );
		setcookie( 'pdd_cart_token', '', time()-3600, COOKIEPATH, COOKIE_DOMAIN );

	}

	$messages['pdd_cart_restoration_successful'] = sprintf( '<strong>%1$s</strong>: %2$s', __( 'Success', 'pdd' ), __( 'Cart restored successfully.', 'pdd' ) );
	PDD()->session->set( 'pdd_cart', $saved_cart );
	PDD()->session->set( 'pdd_cart_messages', $messages );

	return true;
}

/**
 * Retrieve a saved cart token. Used in validating saved carts
 *
 * @since 1.8
 * @return int
 */
function pdd_get_cart_token() {

	$user_id = get_current_user_id();

	if( is_user_logged_in() ) {
		$token = get_user_meta( $user_id, 'pdd_cart_token', true );
	} else {
		$token = isset( $_COOKIE['pdd_cart_token'] ) ? $_COOKIE['pdd_cart_token'] : false;
	}
	return apply_filters( 'pdd_get_cart_token', $token, $user_id );
}

/**
 * Delete Saved Carts after one week
 *
 * @since 1.8
 * @global $wpdb
 * @return void
 */
function pdd_delete_saved_carts() {
	global $wpdb;

	$start = date( 'Y-m-d', strtotime( '-7 days' ) );
	$carts = $wpdb->get_results(
		"
		SELECT user_id, meta_key, FROM_UNIXTIME(meta_value, '%Y-%m-%d') AS date
		FROM {$wpdb->usermeta}
		WHERE meta_key = 'pdd_cart_token'
		", ARRAY_A
	);

	if ( $carts ) {
		foreach ( $carts as $cart ) {
			$user_id    = $cart['user_id'];
			$meta_value = $cart['date'];

			if ( strtotime( $meta_value ) < strtotime( '-1 week' ) ) {
				$wpdb->delete(
					$wpdb->usermeta,
					array(
						'user_id'  => $user_id,
						'meta_key' => 'pdd_cart_token'
					)
				);

				$wpdb->delete(
					$wpdb->usermeta,
					array(
						'user_id'  => $user_id,
						'meta_key' => 'pdd_saved_cart'
					)
				);
			}
		}
	}
}
add_action( 'pdd_weekly_scheduled_events', 'pdd_delete_saved_carts' );

/**
 * Generate URL token to restore the cart via a URL
 *
 * @since 1.8
 * @return string UNIX timestamp
 */
function pdd_generate_cart_token() {
	return apply_filters( 'pdd_generate_cart_token', time() );
}


function pdd_custom_amount_add_to_cart_item( $item ) {
	remove_filter( 'pdd_add_to_cart_item', 'pdd_custom_amount_add_to_cart_item' );
	remove_filter( 'pdd_ajax_pre_cart_item_template', 'pdd_custom_amount_add_to_cart_item' );

	if ( ! empty( $_POST['post_data'] ) || isset( $_POST['pdd_custom_amount'] ) ) {
		// From where we got? ajax or normal?
		if ( ! empty( $_POST['post_data'] ) ) {
			$post_data = wp_parse_args( $_POST['post_data'] );
			$custom_amount = isset( $post_data['pdd_custom_amount'] ) ? $post_data['pdd_custom_amount'] : '';
		} else {
			$custom_amount = $_POST['pdd_custom_amount'];
		}
		
		if ( ! empty( $custom_amount ) ) {
			if ( ! pdd_has_variable_prices( $item['id'] ) || ( pdd_has_variable_prices( $item['id'] ) && '99999' === $item['options']['price_id'] ) ) {
				$item['options']['custom_amount'] = pdd_sanitize_amount( $custom_amount );
			}
		}
	}
	
	add_filter( 'pdd_add_to_cart_item', 'pdd_custom_amount_add_to_cart_item' );
	add_filter( 'pdd_ajax_pre_cart_item_template', 'pdd_custom_amount_add_to_cart_item' );
	
	return $item;
}
add_filter( 'pdd_add_to_cart_item', 'pdd_custom_amount_add_to_cart_item' );
add_filter( 'pdd_ajax_pre_cart_item_template', 'pdd_custom_amount_add_to_cart_item' );


function pdd_custom_amount_pre_add_to_cart( $download_id, $options ) {
	remove_filter( 'pdd_pre_add_to_cart', 'pdd_custom_amount_pre_add_to_cart', 10, 2 );

	if ( ! empty( $_POST['post_data'] ) || isset( $_POST['pdd_custom_amount'] ) ) {
		// From where we got? ajax or normal?
		if ( ! empty( $_POST['post_data'] ) ) {
			$post_data = wp_parse_args( $_POST['post_data'] );
			$custom_amount = isset( $post_data['pdd_custom_amount'] ) ? $post_data['pdd_custom_amount'] : '';
		} else {
			$custom_amount = $_POST['pdd_custom_amount'];
		}
		
		if ( ! empty( $custom_amount ) ) {
			if ( ! pdd_has_variable_prices( $download_id ) || ( pdd_has_variable_prices( $download_id ) && '99999' === $options['price_id'] ) ) {
				$options['custom_amount'] = pdd_sanitize_amount( $custom_amount );
			}
		}
	}
	
	add_filter( 'pdd_pre_add_to_cart', 'pdd_custom_amount_pre_add_to_cart', 10, 2 );
	
	return $options;
}
add_filter( 'pdd_pre_add_to_cart', 'pdd_custom_amount_pre_add_to_cart', 10, 2 );

function pdd_custom_amount_cart_item_price( $price, $item_id, $options = array(), $tax ) {
	if ( pdd_has_custom_amount( $item_id ) && isset( $options['custom_amount'] ) ) {
		$min_amount    = get_post_meta( $item_id, 'pdd_custom_amount', true );
		$custom_amount = $options['custom_amount'];
		if ( $min_amount > 0 && ( $custom_amount >= $min_amount ) ) {
			$price = $options['custom_amount'];
		} elseif ( ( empty( $min_amount ) || 0 >= $min_amount ) && is_numeric( $options['custom_amount'] ) ) {
			$price = $options['custom_amount'];
		}
		
		if ( $tax ) {
			if (
				( pdd_prices_include_tax() && ! pdd_is_cart_taxed() && pdd_use_taxes() ) ||
				( pdd_is_cart_taxed() && pdd_prices_show_tax_on_checkout() || ( ! pdd_prices_show_tax_on_checkout() && pdd_prices_include_tax() ) )
			) {
				$price = pdd_calculate_tax( $price );
			}
		}
	}

	return $price;
}
add_filter( 'pdd_cart_item_price', 'pdd_custom_amount_cart_item_price', 10, 4 );

function pdd_custom_amount_get_cart_item_price_name( $name, $download_id, $price_id, $item ) {
	if ( pdd_has_custom_amount( $download_id ) && isset( $item['options']['custom_amount'] ) ) {
		$name = __( 'Custom Amount', 'pdd' );
	}
	
	return $name;
}
add_filter( 'pdd_get_cart_item_price_name', 'pdd_custom_amount_get_cart_item_price_name', 10, 4 );