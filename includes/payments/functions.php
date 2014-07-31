<?php
/**
 * Payment Functions
 *
 * @package     PDD
 * @subpackage  Payments
 * @copyright   Copyright (c) 2014, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Get Payments
 *
 * Retrieve payments from the database.
 *
 * Since 1.2, this function takes an array of arguments, instead of individual
 * parameters. All of the original parameters remain, but can be passed in any
 * order via the array.
 *
 * $offset = 0, $number = 20, $mode = 'live', $orderby = 'ID', $order = 'DESC',
 * $user = null, $status = 'any', $meta_key = null
 *
 * As of PDD 1.8 this simply wraps PDD_Payments_Query
 *
 * @since 1.0
 * @param array $args Arguments passed to get payments
 * @return object $payments Payments retrieved from the database
 */
function pdd_get_payments( $args = array() ) {

	// Fallback to post objects to ensure backwards compatibility
	if( ! isset( $args['output'] ) ) {
		$args['output'] = 'posts';
	}

	$args     = apply_filters( 'pdd_get_payments_args', $args );
	$payments = new PDD_Payments_Query( $args );
	return $payments->get_payments();
}

/**
 * Retrieve payment by a given field
 *
 * @since       2.0
 * @param       string $field The field to retrieve the payment with
 * @param       mixed $value The value for $field
 * @return      mixed
 */
function pdd_get_payment_by( $field = '', $value = '' ) {

	if( empty( $field ) || empty( $value ) ) {
		return false;
	}

	switch( strtolower( $field ) ) {

		case 'id':
			$payment = get_post( $value );

			if( get_post_type( $payment ) != 'pdd_payment' ) {
				return false;
			}

			break;

		case 'key':
			$payment = pdd_get_payments( array(
				'meta_key'       => '_pdd_payment_purchase_key',
				'meta_value'     => $value,
				'posts_per_page' => 1
			) );

			if( $payment ) {
				$payment = $payment[0];
			}

			break;

		default:
			return false;
	}

	if( $payment ) {
		return $payment;
	}

	return false;
}

/**
 * Insert Payment
 *
 * @since 1.0
 * @param array $payment_data
 * @return int|bool Payment ID if payment is inserted, false otherwise
 */
function pdd_insert_payment( $payment_data = array() ) {
	if ( empty( $payment_data ) )
		return false;

	// Make sure the payment is inserted with the correct timezone
	date_default_timezone_set( pdd_get_timezone_id() );

	// Construct the payment title
	if ( isset( $payment_data['user_info']['first_name'] ) || isset( $payment_data['user_info']['last_name'] ) ) {
		$payment_title = $payment_data['user_info']['first_name'] . ' ' . $payment_data['user_info']['last_name'];
	} else {
		$payment_title = $payment_data['user_email'];
	}

	// Retrieve the ID of the discount used, if any
	if ( $payment_data['user_info']['discount'] != 'none' ) {
		$discount = pdd_get_discount_by( 'code', $payment_data['user_info']['discount'] );
	}

	// Find the next payment number, if enabled 
	if( pdd_get_option( 'enable_sequential' ) ) {
		$number = pdd_get_next_payment_number();
	}

	$args = apply_filters( 'pdd_insert_payment_args', array(
		'post_title'    => $payment_title,
		'post_status'   => isset( $payment_data['status'] ) ? $payment_data['status'] : 'pending',
		'post_type'     => 'pdd_payment',
		'post_parent'   => isset( $payment_data['parent'] ) ? $payment_data['parent'] : null,
		'post_date'     => isset( $payment_data['post_date'] ) ? $payment_data['post_date'] : null,
		'post_date_gmt' => isset( $payment_data['post_date'] ) ? $payment_data['post_date'] : null
	), $payment_data );

	// Create a blank payment
	$payment = wp_insert_post( $args );

	if ( $payment ) {

		$taxes    = wp_list_pluck( $payment_data['cart_details'], 'tax' );
		$cart_tax = array_sum( $taxes );

		$payment_meta = array(
			'currency'     => $payment_data['currency'],
			'campaigns'    => $payment_data['campaigns'],
			'user_info'    => $payment_data['user_info'],
			'cart_details' => $payment_data['cart_details'],
			'tax'          => $cart_tax,
		);

		$mode    = pdd_is_test_mode() ? 'test' : 'live';
		$gateway = ! empty( $payment_data['gateway'] ) ? $payment_data['gateway'] : '';
		$gateway = empty( $gateway ) && isset( $_POST['pdd-gateway'] ) ? $_POST['pdd-gateway'] : $gateway;

		if( ! $payment_data['price'] ) {
			// Ensures the _pdd_payment_total meta key is created for purchases with an amount of 0
			$payment_data['price'] = '0.00';
		}

		// Record the payment details
		update_post_meta( $payment, '_pdd_payment_meta',         apply_filters( 'pdd_payment_meta', $payment_meta, $payment_data ) );
		update_post_meta( $payment, '_pdd_payment_user_id',      $payment_data['user_info']['id'] );
		update_post_meta( $payment, '_pdd_payment_user_email',   $payment_data['user_email'] );
		update_post_meta( $payment, '_pdd_payment_user_ip',      pdd_get_ip() );
		update_post_meta( $payment, '_pdd_payment_purchase_key', $payment_data['purchase_key'] );
		update_post_meta( $payment, '_pdd_payment_total',        $payment_data['price'] );
		update_post_meta( $payment, '_pdd_payment_mode',         $mode );
		update_post_meta( $payment, '_pdd_payment_gateway',      $gateway );
		if ( ! empty( $discount ) ) {
			update_post_meta( $payment, '_pdd_payment_discount_id',  $discount->ID );
		}

		if( pdd_get_option( 'enable_sequential' ) ) {
			update_post_meta( $payment, '_pdd_payment_number', $number );
		}

		// Clear the user's purchased cache
		delete_transient( 'pdd_user_' . $payment_data['user_info']['id'] . '_purchases' );

		do_action( 'pdd_insert_payment', $payment, $payment_data );

		return $payment; // Return the ID
	}
	// Return false if no payment was inserted
	return false;
}

/**
 * Updates a payment status.
 *
 * @since 1.0
 * @param int $payment_id Payment ID
 * @param string $new_status New Payment Status (default: publish)
 * @return void
 */
function pdd_update_payment_status( $payment_id, $new_status = 'publish' ) {

	if ( $new_status == 'completed' || $new_status == 'complete' ) {
		$new_status = 'publish';
	}

	if( empty( $payment_id ) ) {
		return;
	}

	$payment = get_post( $payment_id );

	if ( is_wp_error( $payment ) || ! is_object( $payment ) ) {
		return;
	}

	$old_status = $payment->post_status;

	if ( $old_status === $new_status ) {
		return; // Don't permit status changes that aren't changes
	}

	$do_change = apply_filters( 'pdd_should_update_payment_status', true, $payment_id, $new_status, $old_status );

	if( $do_change ) {

		do_action( 'pdd_before_payment_status_change', $payment_id, $new_status, $old_status );

		$update_fields = array( 'ID' => $payment_id, 'post_status' => $new_status, 'edit_date' => current_time( 'mysql' ) );

		wp_update_post( apply_filters( 'pdd_update_payment_status_fields', $update_fields ) );

		do_action( 'pdd_update_payment_status', $payment_id, $new_status, $old_status );

	}
}

/**
 * Deletes a Purchase
 *
 * @since 1.0
 * @global $pdd_logs
 * @uses PDD_Logging::delete_logs()
 * @param int $payment_id Payment ID (default: 0)
 * @return void
 */
function pdd_delete_purchase( $payment_id = 0 ) {
	global $pdd_logs;

	$downloads = pdd_get_payment_meta_downloads( $payment_id );

	if ( is_array( $downloads ) ) {
		// Update sale counts and earnings for all purchased products
		foreach ( $downloads as $download ) {
			pdd_undo_purchase( $download['id'], $payment_id );
		}
	}

	$amount = pdd_get_payment_amount( $payment_id );
	$status = get_post( $payment_id )->post_status;

	if( $status == 'revoked' || $status == 'publish' ) {
		// Only decrease earnings if they haven't already been decreased (or were never increased for this payment)
		pdd_decrease_total_earnings( $amount );
		// Clear the This Month earnings (this_monththis_month is NOT a typo)
		delete_transient( md5( 'pdd_earnings_this_monththis_month' ) );
	}

	do_action( 'pdd_payment_delete', $payment_id );

	// Remove the payment
	wp_delete_post( $payment_id, true );

	// Remove related sale log entries
	$pdd_logs->delete_logs(
		null,
		'sale',
		array(
			array(
				'key'   => '_pdd_log_payment_id',
				'value' => $payment_id
			)
		)
	);

	do_action( 'pdd_payment_deleted', $payment_id );
}

/**
 * Undos a purchase, including the decrease of sale and earning stats. Used for
 * when refunding or deleting a purchase
 *
 * @since 1.0.8.1
 * @param int $download_id Download (Post) ID
 * @param int $payment_id Payment ID
 * @return void
 */
function pdd_undo_purchase( $download_id, $payment_id ) {
	if ( pdd_is_test_mode() )
        return;

	$payment = get_post( $payment_id );
	$cart_details = pdd_get_payment_meta_cart_details( $payment_id );

	if ( is_array( $cart_details ) ) {
		foreach ( $cart_details as $item ) {
			// Decrease earnings/sales and fire action once per quantity number
			for( $i = 0; $i < $item['quantity']; $i++ ) {
				$user_info = pdd_get_payment_meta_user_info( $payment_id );

				// get the item's price
 				$amount = isset( $item['price'] ) ? $item['price'] : false;

 				// variable priced downloads
				if ( pdd_has_variable_prices( $download_id ) ) {
					$price_id 	= isset( $item['item_number']['options']['price_id'] ) ? $item['item_number']['options']['price_id'] : null;
					$amount 	= ! isset( $item['price'] ) && 0 !== $item['price'] ? pdd_get_price_option_amount( $download_id, $price_id ) : $item['price'];
				}

 				if ( ! $amount ) {
 					// This function is only used on payments with near 1.0 cart data structure
 					$amount = pdd_get_download_final_price( $download_id, $user_info, $amount );
 				}

				// decrease earnings
				pdd_decrease_earnings( $download_id, $amount );

				// decrease purchase count
				pdd_decrease_purchase_count( $download_id );
			}
		}
	}
}


/**
 * Count Payments
 *
 * Returns the total number of payments recorded.
 *
 * @since 1.0
 * @param array $args
 * @return array $count Number of payments sorted by payment status
 */
function pdd_count_payments( $args = array() ) {

	global $wpdb;

	$defaults = array(
		'user'       => null,
		's'          => null,
		'start-date' => null,
		'end-date'   => null,
	);

	$args = wp_parse_args( $args, $defaults );

	$join = '';
	$where = "WHERE p.post_type = 'pdd_payment'";

	// Count payments for a specific user
	if( ! empty( $args['user'] ) ) {

		if( is_email( $args['user'] ) )
			$field = 'email';
		elseif( is_numeric( $args['user'] ) )
			$field = 'id';
		else
			$field = '';

		$join = "LEFT JOIN $wpdb->postmeta m ON (p.ID = m.post_id)";

		if ( ! empty( $field ) ) {
			$where .= "
				AND m.meta_key = '_pdd_payment_user_{$field}'
				AND m.meta_value = '{$args['user']}'";
		}

	// Count payments for a search
	} elseif( ! empty( $args['s'] ) ) {

		if ( is_email( $args['s'] ) || strlen( $args['s'] ) == 32 ) {

			if( is_email( $args['s'] ) )
				$field = '_pdd_payment_user_email';
			else
				$field = '_pdd_payment_purchase_key';


			$join = "LEFT JOIN $wpdb->postmeta m ON (p.ID = m.post_id)";
			$where .= "
				AND m.meta_key = '{$field}'
				AND m.meta_value = '{$args['s']}'";

		} elseif ( is_numeric( $args['s'] ) ) {

			$join = "LEFT JOIN $wpdb->postmeta m ON (p.ID = m.post_id)";
			$where .= "
				AND m.meta_key = '_pdd_payment_user_id'
				AND m.meta_value = '{$args['s']}'";

		} else {
			$where .= "AND ((p.post_title LIKE '%{$args['s']}%') OR (p.post_content LIKE '%{$args['s']}%'))";
		}

	}

	// Limit payments count by date
	if ( ! empty( $args['start-date'] ) ) {
		$date = new DateTime( $args['start-date'] );
		$where .= "
			AND p.post_date >= '" . $date->format( 'Y-m-d' ) . "'";
	}

	if ( ! empty ( $args['end-date'] ) ) {
		$date = new DateTime( $args['end-date'] );
		$where .= "
			AND p.post_date <= '" . $date->format( 'Y-m-d' ) . "'";
	}

	$where = apply_filters( 'pdd_count_payments_where', $where );

	$cache_key = md5( implode( '|', $args ) . $where );

	$query = "SELECT p.post_status,count( * ) AS num_posts
		FROM $wpdb->posts p
		$join
		$where
		GROUP BY p.post_status
	";

	$count = wp_cache_get( $cache_key, 'counts');
	if ( false !== $count )
		return $count;

	$count = $wpdb->get_results( $query, ARRAY_A );

	$stats = array();
	foreach ( get_post_stati() as $state )
		$stats[$state] = 0;

	foreach ( (array) $count as $row )
		$stats[$row['post_status']] = $row['num_posts'];

	$stats = (object) $stats;
	wp_cache_set( $cache_key, $stats, 'counts' );

	return $stats;
}


/**
 * Check For Existing Payment
 *
 * @since 1.0
 * @param int $payment_id Payment ID
 * @return bool true if payment exists, false otherwise
 */
function pdd_check_for_existing_payment( $payment_id ) {
	$payment = get_post( $payment_id );

	if ( $payment && $payment->post_status == 'publish' ) {
		return true; // Payment exists
	}

	return false; // This payment doesn't exist
}

/**
 * Get Payment Status
 *
 * @since 1.0
 *
 * @param WP_Post $payment
 * @param bool   $return_label Whether to return the payment status or not
 *
 * @return bool|mixed if payment status exists, false otherwise
 */
function pdd_get_payment_status( $payment, $return_label = false ) {
	if ( ! is_object( $payment ) || !isset( $payment->post_status ) )
		return false;

	$statuses = pdd_get_payment_statuses();
	if ( ! is_array( $statuses ) || empty( $statuses ) )
		return false;

	if ( array_key_exists( $payment->post_status, $statuses ) ) {
		if ( true === $return_label ) {
			return $statuses[ $payment->post_status ];
		} else {
			return array_search( $payment->post_status, $statuses );
		}
	}

	return false;
}

/**
 * Retrieves all available statuses for payments.
 *
 * @since 1.0.8.1
 * @return array $payment_status All the available payment statuses
 */
function pdd_get_payment_statuses() {
	$payment_statuses = array(
		'pending'   => __( 'Pending', 'pdd' ),
		'publish'   => __( 'Complete', 'pdd' ),
		'refunded'  => __( 'Refunded', 'pdd' ),
		'failed'    => __( 'Failed', 'pdd' ),
		'abandoned' => __( 'Abandoned', 'pdd' ),
		'revoked'   => __( 'Revoked', 'pdd' )
	);

	return apply_filters( 'pdd_payment_statuses', $payment_statuses );
}

/**
 * Get Earnings By Date
 *
 * @since 1.0
 * @param int $day Day number
 * @param int $month_num Month number
 * @param int $year Year
 * @param int $hour Hour
 * @return int $earnings Earnings
 */
function pdd_get_earnings_by_date( $day = null, $month_num, $year = null, $hour = null ) {

	// This is getting deprecated soon. Use PDD_Payment_Stats with the get_earnings() method instead

	global $wpdb;

	$args = array(
		'post_type'      => 'pdd_payment',
		'nopaging'       => true,
		'year'           => $year,
		'monthnum'       => $month_num,
		'post_status'    => array( 'publish', 'revoked' ),
		'fields'         => 'ids',
		'update_post_term_cache' => false
	);
	if ( ! empty( $day ) )
		$args['day'] = $day;

	if ( ! empty( $hour ) )
		$args['hour'] = $hour;

	$args     = apply_filters( 'pdd_get_earnings_by_date_args', $args );
	$key      = md5( serialize( $args ) );
	$earnings = get_transient( $key );

	if( false === $earnings ) {
		$sales = get_posts( $args );
		$earnings = 0;
		if ( $sales ) {
			$sales = implode( ',', $sales );
			$earnings += $wpdb->get_var( "SELECT SUM(meta_value) FROM $wpdb->postmeta WHERE meta_key = '_pdd_payment_total' AND post_id IN({$sales})" );

		}
		// Cache the results for one hour
		set_transient( $key, $earnings, 60*60 );
	}

	return round( $earnings, 2 );
}

/**
 * Get Sales By Date
 *
 * @since 1.1.4.0
 * @author Sunny Ratilal
 * @param int $day Day number
 * @param int $month_num Month number
 * @param int $year Year
 * @param int $hour Hour
 * @return int $count Sales
 */
function pdd_get_sales_by_date( $day = null, $month_num = null, $year = null, $hour = null ) {

	// This is getting deprecated soon. Use PDD_Payment_Stats with the get_sales() method instead

	$args = array(
		'post_type'      => 'pdd_payment',
		'nopaging'       => true,
		'year'           => $year,
		'fields'         => 'ids',
		'post_status'    => array( 'publish', 'revoked' ),
		'update_post_meta_cache' => false,
		'update_post_term_cache' => false
	);

	if ( ! empty( $month_num ) )
		$args['monthnum'] = $month_num;

	if ( ! empty( $day ) )
		$args['day'] = $day;

	if ( ! empty( $hour ) )
		$args['hour'] = $hour;
	
	$args = apply_filters( 'pdd_get_sales_by_date_args', $args  );
	
	$key   = md5( serialize( $args ) );
	$count = get_transient( $key, 'pdd' );

	if( false === $count ) {
		$sales = new WP_Query( $args );
		$count = (int) $sales->post_count;
		// Cache the results for one hour
		set_transient( $key, $count, 60*60 );
	}

	return $count;
}

/**
 * Checks whether a payment has been marked as complete.
 *
 * @since 1.0.8
 * @param int $payment_id Payment ID to check against
 * @return bool true if complete, false otherwise
 */
function pdd_is_payment_complete( $payment_id ) {
	$payment = get_post( $payment_id );
	$ret = false;
	if ( $payment && $payment->post_status == 'publish' ) {
		$ret = true;
	}
	return apply_filters( 'pdd_is_payment_complete', $ret, $payment_id, $payment->post_status );
}

/**
 * Get Total Sales
 *
 * @since 1.2.2
 * @return int $count Total sales
 */
function pdd_get_total_sales() {

	$payments = pdd_count_payments();
	return $payments->revoked + $payments->publish;
}

/**
 * Get Total Earnings
 *
 * @since 1.2
 * @return float $total Total earnings
 */
function pdd_get_total_earnings() {

	$total = get_option( 'pdd_earnings_total', 0 );

	// If no total stored in DB, use old method of calculating total earnings
	if( ! $total ) {

		global $wpdb;

		$total = get_transient( 'pdd_earnings_total' );

		if( false === $total ) {

			$total = (float) 0;

			$args = apply_filters( 'pdd_get_total_earnings_args', array(
				'offset' => 0,
				'number' => -1,
				'status' => array( 'publish', 'revoked' ),
				'fields' => 'ids'
			) );


			$payments = pdd_get_payments( $args );
			if ( $payments ) {

				/*
				 * If performing a purchase, we need to skip the very last payment in the database, since it calls
				 * pdd_increase_total_earnings() on completion, which results in duplicated earnings for the very
				 * first purchase
				 */

				if( did_action( 'pdd_update_payment_status' ) ) {
					array_pop( $payments );
				}

				if( ! empty( $payments ) ) {
					$payments = implode( ',', $payments );
					$total += $wpdb->get_var( "SELECT SUM(meta_value) FROM $wpdb->postmeta WHERE meta_key = '_pdd_payment_total' AND post_id IN({$payments})" );
				}

			}

			// Cache results for 1 day. This cache is cleared automatically when a payment is made
			set_transient( 'pdd_earnings_total', $total, 86400 );

			// Store the total for the first time
			update_option( 'pdd_earnings_total', $total );
		}
	}

	if( $total < 0 ) {
		$total = 0; // Don't ever show negative earnings
	}

	return apply_filters( 'pdd_total_earnings', round( $total, 2 ) );
}

/**
 * Increase the Total Earnings
 *
 * @since 1.8.4
 * @param $amount int The amount you would like to increase the total earnings by.
 * @return float $total Total earnings
 */
function pdd_increase_total_earnings( $amount = 0 ) {
	$total = pdd_get_total_earnings();
	$total += $amount;
	update_option( 'pdd_earnings_total', $total );
	return $total;
}

/**
 * Decrease the Total Earnings
 *
 * @since 1.8.4
 * @param $amount int The amount you would like to decrease the total earnings by.
 * @return float $total Total earnings
 */
function pdd_decrease_total_earnings( $amount = 0 ) {
	$total = pdd_get_total_earnings();
	$total -= $amount;
	if( $total < 0 ) {
		$total = 0;
	}
	update_option( 'pdd_earnings_total', $total );
	return $total;
}

/**
 * Get Payment Meta for a specific Payment
 *
 * @since 1.2
 * @param int $payment_id Payment ID
 * @return array $meta Payment Meta
 */
function pdd_get_payment_meta( $payment_id ) {
	$meta = get_post_meta( $payment_id, '_pdd_payment_meta', true );

	// Payment meta was simplified in PDD v1.5, so these are here for backwards compatibility
	if ( ! isset( $meta['key'] ) )
		$meta['key'] = pdd_get_payment_key( $payment_id );

	if ( ! isset( $meta['email'] ) )
		$meta['email'] = pdd_get_payment_user_email( $payment_id );

	if ( ! isset( $meta['date'] ) )
		$meta['date'] = get_post_field( 'post_date', $payment_id );

	return apply_filters( 'pdd_get_payment_meta', $meta, $payment_id );
}

/**
 * Get the user_info Key from Payment Meta
 *
 * @since 1.2
 * @param int $payment_id Payment ID
 * @return array $user_info User Info Meta Values
 */
function pdd_get_payment_meta_user_info( $payment_id ) {
	$payment_meta = pdd_get_payment_meta( $payment_id );
	$user_info    = isset( $payment_meta['user_info'] ) ? maybe_unserialize( $payment_meta['user_info'] ) : false;

	return apply_filters( 'pdd_payment_meta_user_info', $user_info );
}

/**
 * Get the downloads Key from Payment Meta
 *
 * @since 1.2
 * @param int $payment_id Payment ID
 * @return array $downloads Downloads Meta Values
 */
function pdd_get_payment_meta_downloads( $payment_id ) {
	$payment_meta = pdd_get_payment_meta( $payment_id );
	$downloads    = isset( $payment_meta['campaigns'] ) ? maybe_unserialize( $payment_meta['campaigns'] ) : array();

	return apply_filters( 'pdd_payment_meta_downloads', $downloads );
}

/**
 * Get the cart_details Key from Payment Meta
 *
 * @since 1.2
 * @param int $payment_id Payment ID
 * @param bool $include_bundle_files Whether to retrieve product IDs associated with a bundled product and return them in the array
 * @return array $cart_details Cart Details Meta Values
 */
function pdd_get_payment_meta_cart_details( $payment_id, $include_bundle_files = false ) {
	$payment_meta = pdd_get_payment_meta( $payment_id );
	$cart_details = ! empty( $payment_meta['cart_details'] ) ? maybe_unserialize( $payment_meta['cart_details'] ) : array();

	if( ! empty( $cart_details ) ) {

		foreach( $cart_details as $key => $cart_item ) {

			// Ensure subtotal is set, for pre-1.9 orders
			if( ! isset( $cart_item['subtotal'] ) ) {
				$cart_details[$key]['subtotal'] = $cart_item['price'];
			}

			if( $include_bundle_files ) {

				if( 'bundle' != pdd_get_download_type( $cart_item['id'] ) )
					continue;

				$products = pdd_get_bundled_products( $cart_item['id'] );
				if( empty( $products ) )
					continue;

				foreach( $products as $product_id ) {
					$cart_details[]   = array(
						'id'          => $product_id,
						'name'        => get_the_title( $product_id ),
						'item_number' => array(
							'id'      => $product_id,
							'options' => array(),
						),
						'price'       => 0,
						'subtotal'    => 0,
						'quantity'    => 1,
						'tax'         => 0,
						'in_bundle'   => 1,
						'parent'		=> array(
								'id' 			=> $cart_item['id'],
								'options' 		=> isset( $cart_item['item_number']['options'] ) ? $cart_item['item_number']['options'] : array()
							)
					);
				}
			}
		}

	}

	return apply_filters( 'pdd_payment_meta_cart_details', $cart_details );
}

/**
 * Get the user email associated with a payment
 *
 * @since 1.2
 * @param int $payment_id Payment ID
 * @return string $email User Email
 */
function pdd_get_payment_user_email( $payment_id ) {
	$email = get_post_meta( $payment_id, '_pdd_payment_user_email', true );

	return apply_filters( 'pdd_payment_user_email', $email );
}

/**
 * Get the user ID associated with a payment
 *
 * @since 1.5.1
 * @param int $payment_id Payment ID
 * @return string $user_id User ID
 */
function pdd_get_payment_user_id( $payment_id ) {
	$user_id = get_post_meta( $payment_id, '_pdd_payment_user_id', true );

	return apply_filters( 'pdd_payment_user_id', $user_id );
}

/**
 * Get the status of the unlimited downloads flag
 *
 * @since 2.0
 * @param int $payment_id Payment ID
 * @return bool $unlimited
 */
function pdd_payment_has_unlimited_downloads( $payment_id ) {
	$unlimited = (bool) get_post_meta( $payment_id, '_pdd_payment_unlimited_downloads', true );

	return apply_filters( 'pdd_payment_unlimited_downloads', $unlimited );
}

/**
 * Get the IP address used to make a purchase
 *
 * @since 1.9
 * @param int $payment_id Payment ID
 * @return string $ip User IP
 */
function pdd_get_payment_user_ip( $payment_id ) {
	$ip = get_post_meta( $payment_id, '_pdd_payment_user_ip', true );
	return apply_filters( 'pdd_payment_user_ip', $ip );
}

/**
 * Get the date a payment was completed
 *
 * @since 2.0
 * @param int $payment_id Payment ID
 * @return string $date The date the payment was completed
 */
function pdd_get_payment_completed_date( $payment_id = 0 ) {

	$payment = get_post( $payment_id );

	if( 'pending' == $payment->post_status || 'preapproved' == $payment->post_status ) {
		return false; // This payment was never completed
	}

	$date = ( $date = get_post_meta( $payment_id, '_pdd_completed_date', true ) ) ? $date : $payment->modified_date;

	return apply_filters( 'pdd_payment_completed_date', $date, $payment_id );
}

/**
 * Get the gateway associated with a payment
 *
 * @since 1.2
 * @param int $payment_id Payment ID
 * @return string $gateway Gateway
 */
function pdd_get_payment_gateway( $payment_id ) {
	$gateway = get_post_meta( $payment_id, '_pdd_payment_gateway', true );

	return apply_filters( 'pdd_payment_gateway', $gateway );
}

/**
 * Get the purchase key for a purchase
 *
 * @since 1.2
 * @param int $payment_id Payment ID
 * @return string $key Purchase key
 */
function pdd_get_payment_key( $payment_id = 0 ) {
	$key = get_post_meta( $payment_id, '_pdd_payment_purchase_key', true );
	return apply_filters( 'pdd_payment_key', $key, $payment_id );
}

/**
 * Get the payment order number
 *
 * This will return the payment ID if sequential order numbers are not enabled or the order number does not exist
 *
 * @since 2.0
 * @param int $payment_id Payment ID
 * @return string $number Payment order number
 */
function pdd_get_payment_number( $payment_id = 0 ) {

	$number = $payment_id;

	if( pdd_get_option( 'enable_sequential' ) ) {

		$number = get_post_meta( $payment_id, '_pdd_payment_number', true );

		if( ! $number ) {
		
			$number = $payment_id;
	
		}
	
	}
	
	return apply_filters( 'pdd_payment_number', $number, $payment_id );
}

/**
 * Gets the next available order number
 *
 * This is used when inserting a new payment
 *
 * @since 2.0
 * @return string $number The next available payment number
 */
function pdd_get_next_payment_number() {

	if( ! pdd_get_option( 'enable_sequential' ) ) {
		return false;
	}

	$prefix  = pdd_get_option( 'sequential_prefix' );
	$postfix = pdd_get_option( 'sequential_postfix' );
	$start   = pdd_get_option( 'sequential_start', 1 );

	$payments     = new PDD_Payments_Query( array( 'number' => 1, 'order' => 'DESC', 'orderby' => 'ID', 'output' => 'posts', 'fields' => 'ids' ) );

	$last_payment = $payments->get_payments(); 

	if( $last_payment ) {
		
		$number = pdd_get_payment_number( $last_payment[0] );

		if( empty( $number ) ) {

			$number = $prefix . $start . $postfix;

		} else {

			// Remove prefix and postfix
			$number = str_replace( $prefix, '', $number );
			$number = str_replace( $postfix, '', $number );

			// Ensure it's a whole number
			$number = intval( $number );

			// Increment the payment number
			$number++;

			// Re-add the prefix and postfix
			$number = $prefix . $number . $postfix;

		}

	} else {

		$number = $prefix . $start . $postfix;

	}

	return apply_filters( 'pdd_get_next_payment_number', $number );
}

/**
 * Get the fully formatted payment amount. The payment amount is retrieved using
 * pdd_get_payment_amount() and is then sent through pdd_currency_filter() and
 * pdd_format_amount() to format the amount correctly.
 *
 * @since 1.4
 * @param int $payment_id Payment ID
 * @return string $amount Fully formatted payment amount
 */
function pdd_payment_amount( $payment_id = 0 ) {
	$amount = pdd_get_payment_amount( $payment_id );
	return pdd_currency_filter( pdd_format_amount( $amount ) );
}
/**
 * Get the amount associated with a payment
 *
 * @access public
 * @since 1.2
 * @param int $payment_id Payment ID
 */
function pdd_get_payment_amount( $payment_id ) {

	$amount = get_post_meta( $payment_id, '_pdd_payment_total', true );

	if ( empty( $amount ) && '0.00' != $amount ) {
		$meta   = get_post_meta( $payment_id, '_pdd_payment_meta', true );
		$meta   = maybe_unserialize( $meta );

		if ( isset( $meta['amount'] ) ) {
			$amount = $meta['amount'];
		}
	}

	return apply_filters( 'pdd_payment_amount', floatval( $amount ), $payment_id );
}

/**
 * Retrieves subtotal for payment (this is the amount before taxes) and then
 * returns a full formatted amount. This function essentially calls
 * pdd_get_payment_subtotal()
 *
 * @since 1.3.3
 *
 * @param int $payment_id Payment ID
 *
 * @see pdd_get_payment_subtotal()
 *
 * @return array Fully formatted payment subtotal
 */
function pdd_payment_subtotal( $payment_id = 0 ) {
	$subtotal = pdd_get_payment_subtotal( $payment_id );

	return pdd_currency_filter( pdd_format_amount( $subtotal ) );
}

/**
 * Retrieves subtotal for payment (this is the amount before taxes) and then
 * returns a non formatted amount.
 *
 * @since 1.3.3
 * @global $pdd_options Array of all the PDD Options
 * @param int $payment_id Payment ID
 * @return float $subtotal Subtotal for payment (non formatted)
 */
function pdd_get_payment_subtotal( $payment_id = 0) {
	global $pdd_options;

	$subtotal = pdd_get_payment_amount( $payment_id );

	$tax = pdd_use_taxes() ? pdd_get_payment_tax( $payment_id ) : 0;
	$subtotal -= $tax;

	return apply_filters( 'pdd_get_payment_subtotal', $subtotal, $payment_id );
}

/**
 * Retrieves taxed amount for payment and then returns a full formatted amount
 * This function essentially calls pdd_get_payment_tax()
 *
 * @since 1.3.3
 * @see pdd_get_payment_tax()
 * @param int $payment_id Payment ID
 * @param bool $payment_meta Payment Meta provided? (default: false)
 * @return string $subtotal Fully formatted payment subtotal
 */
function pdd_payment_tax( $payment_id = 0, $payment_meta = false ) {
	$tax = pdd_get_payment_tax( $payment_id, $payment_meta );

	return pdd_currency_filter( pdd_format_amount( $tax ) );
}

/**
 * Retrieves taxed amount for payment and then returns a non formatted amount
 *
 * @since 1.3.3
 * @param int $payment_id Payment ID
 * @param bool $payment_meta Get payment meta?
 * @return float $subtotal Subtotal for payment (non formatted)
 */
function pdd_get_payment_tax( $payment_id = 0, $payment_meta = false ) {
	if ( ! $payment_meta )
		$payment_meta = pdd_get_payment_meta( $payment_id );

	$tax = isset( $payment_meta['tax'] ) ? $payment_meta['tax'] : 0;

	return apply_filters( 'pdd_get_payment_tax', $tax, $payment_id );
}

/**
 * Retrieves arbitrary fees for the payment
 *
 * @since 1.5
 * @param int $payment_id Payment ID
 * @param string $type Fee type
 * @return mixed array if payment fees found, false otherwise
 */
function pdd_get_payment_fees( $payment_id = 0, $type = 'all' ) {

	$payment_meta = pdd_get_payment_meta( $payment_id );

	$fees = array();
	$payment_fees = isset( $payment_meta['fees'] ) ? $payment_meta['fees'] : false;

	if ( ! empty( $payment_fees ) ) {

		foreach ( $payment_fees as $fee_id => $fee ) {

			if( 'all' != $type && ! empty( $fee['type'] ) && $type != $fee['type'] ) {

				unset( $payment_fees[ $fee_id ] );

			} else {

				$fees[] = array(
					'id'     => $fee_id,
					'amount' => $fee['amount'],
					'label'  => $fee['label']
				);

			}
		}
	}

	return apply_filters( 'pdd_get_payment_fees', $fees, $payment_id );
}

/**
 * Retrieve the purchase ID based on the purchase key
 *
 * @since 1.3.2
 * @global object $wpdb Used to query the database using the WordPress
 *   Database API
 * @param string $key the purchase key to search for
 * @return int $purchase Purchase ID
 */
function pdd_get_purchase_id_by_key( $key ) {
	global $wpdb;

	$purchase = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_pdd_payment_purchase_key' AND meta_value = %s LIMIT 1", $key ) );

	if ( $purchase != NULL )
		return $purchase;

	return 0;
}

/**
 * Retrieve all notes attached to a purchase
 *
 * @since 1.4
 * @param int $payment_id The payment ID to retrieve notes for
 * @param string $search Search for notes that contain a search term
 * @return array $notes Payment Notes
 */
function pdd_get_payment_notes( $payment_id = 0, $search = '' ) {

	if ( empty( $payment_id ) && empty( $search ) ) {
		return false;
	}

	remove_filter( 'comments_clauses', 'pdd_hide_payment_notes', 10, 2 );

	$notes = get_comments( array( 'post_id' => $payment_id, 'order' => 'ASC', 'search' => $search ) );

	add_filter( 'comments_clauses', 'pdd_hide_payment_notes', 10, 2 );

	return $notes;
}


/**
 * Add a note to a payment
 *
 * @since 1.4
 * @param int $payment_id The payment ID to store a note for
 * @param string $note The note to store
 * @return int The new note ID
 */
function pdd_insert_payment_note( $payment_id = 0, $note = '' ) {
	if ( empty( $payment_id ) )
		return false;

	do_action( 'pdd_pre_insert_payment_note', $payment_id, $note );

	$note_id = wp_insert_comment( wp_filter_comment( array(
		'comment_post_ID'      => $payment_id,
		'comment_content'      => $note,
		'user_id'              => is_admin() ? get_current_user_id() : 0,
		'comment_date'         => current_time( 'mysql' ),
		'comment_date_gmt'     => current_time( 'mysql', 1 ),
		'comment_approved'     => 1,
		'comment_parent'       => 0,
		'comment_author'       => '',
		'comment_author_IP'    => '',
		'comment_author_url'   => '',
		'comment_author_email' => '',
		'comment_type'         => 'pdd_payment_note'

	) ) );

	do_action( 'pdd_insert_payment_note', $note_id, $payment_id, $note );

	return $note_id;
}

/**
 * Deletes a payment note
 *
 * @since 1.6
 * @param int $comment_id The comment ID to delete
 * @param int $payment_id The payment ID the note is connected to
 * @return bool True on success, false otherwise
 */
function pdd_delete_payment_note( $comment_id = 0, $payment_id = 0 ) {
	if( empty( $comment_id ) )
		return false;

	do_action( 'pdd_pre_delete_payment_note', $comment_id, $payment_id );
	$ret = wp_delete_comment( $comment_id, true );
	do_action( 'pdd_post_delete_payment_note', $comment_id, $payment_id );

	return $ret;
}

/**
 * Gets the payment note HTML
 *
 * @since 1.9
 * @param object/int $note The comment object or ID
 * @param int $payment_id The payment ID the note is connected to
 * @return string
 */
function pdd_get_payment_note_html( $note, $payment_id = 0 ) {

	if( is_numeric( $note ) ) {
		$note = get_comment( $note );
	}

	if ( ! empty( $note->user_id ) ) {
		$user = get_userdata( $note->user_id );
		$user = $user->display_name;
	} else {
		$user = __( 'PDD Bot', 'pdd' );
	}

	$date_format = get_option( 'date_format' ) . ', ' . get_option( 'time_format' );

	$delete_note_url = wp_nonce_url( add_query_arg( array(
		'pdd-action' => 'delete_payment_note',
		'note_id'    => $note->comment_ID,
		'payment_id' => $payment_id
	) ), 'pdd_delete_payment_note_' . $note->comment_ID );

	$note_html = '<div class="pdd-payment-note" id="pdd-payment-note-' . $note->comment_ID . '">';
		$note_html .='<p>';
			$note_html .= '<strong>' . $user . '</strong>&nbsp;&ndash;&nbsp;' . date_i18n( $date_format, strtotime( $note->comment_date ) ) . '<br/>';
			$note_html .= $note->comment_content;
			$note_html .= '&nbsp;&ndash;&nbsp;<a href="' . esc_url( $delete_note_url ) . '" class="pdd-delete-payment-note" data-note-id="' . absint( $note->comment_ID ) . '" data-payment-id="' . absint( $payment_id ) . '" title="' . __( 'Delete this payment note', 'pdd' ) . '">' . __( 'Delete', 'pdd' ) . '</a>';
		$note_html .= '</p>';
	$note_html .= '</div>';

	return $note_html;

}

/**
 * Exclude notes (comments) on pdd_payment post type from showing in Recent
 * Comments widgets
 *
 * @since 1.4.1
 * @param array $clauses Comment clauses for comment query
 * @param obj $wp_comment_query WordPress Comment Query Object
 * @return array $clauses Updated comment clauses
 */
function pdd_hide_payment_notes( $clauses, $wp_comment_query ) {
    global $wpdb;

	$clauses['where'] .= ' AND comment_type != "pdd_payment_note"';
    return $clauses;
}
add_filter( 'comments_clauses', 'pdd_hide_payment_notes', 10, 2 );


/**
 * Exclude notes (comments) on pdd_payment post type from showing in comment feeds
 *
 * @since 1.5.1
 * @param array $where
 * @param obj $wp_comment_query WordPress Comment Query Object
 * @return array $where
 */
function pdd_hide_payment_notes_from_feeds( $where, $wp_comment_query ) {
    global $wpdb;

	$where .= $wpdb->prepare( " AND comment_type != %s", 'pdd_payment_note' );
	return $where;
}
add_filter( 'comment_feed_where', 'pdd_hide_payment_notes_from_feeds', 10, 2 );


/**
 * Remove PDD Comments from the wp_count_comments function
 *
 * @access public
 * @since 1.5.2
 * @param array $stats (empty from core filter)
 * @param int $post_id Post ID
 * @return array Array of comment counts
*/
function pdd_remove_payment_notes_in_comment_counts( $stats, $post_id ) {
	global $wpdb, $pagenow;

	if( 'index.php' != $pagenow ) {
		return $stats;
	}

	$post_id = (int) $post_id;

	if ( apply_filters( 'pdd_count_payment_notes_in_comments', false ) )
		return $stats;

	$stats = wp_cache_get( "comments-{$post_id}", 'counts' );

	if ( false !== $stats )
		return $stats;

	$where = 'WHERE comment_type != "pdd_payment_note"';

	if ( $post_id > 0 )
		$where .= $wpdb->prepare( " AND comment_post_ID = %d", $post_id );

	$count = $wpdb->get_results( "SELECT comment_approved, COUNT( * ) AS num_comments FROM {$wpdb->comments} {$where} GROUP BY comment_approved", ARRAY_A );

	$total = 0;
	$approved = array( '0' => 'moderated', '1' => 'approved', 'spam' => 'spam', 'trash' => 'trash', 'post-trashed' => 'post-trashed' );
	foreach ( (array) $count as $row ) {
		// Don't count post-trashed toward totals
		if ( 'post-trashed' != $row['comment_approved'] && 'trash' != $row['comment_approved'] )
			$total += $row['num_comments'];
		if ( isset( $approved[$row['comment_approved']] ) )
			$stats[$approved[$row['comment_approved']]] = $row['num_comments'];
	}

	$stats['total_comments'] = $total;
	foreach ( $approved as $key ) {
		if ( empty($stats[$key]) )
			$stats[$key] = 0;
	}

	$stats = (object) $stats;
	wp_cache_set( "comments-{$post_id}", $stats, 'counts' );

	return $stats;
}
add_filter( 'wp_count_comments', 'pdd_remove_payment_notes_in_comment_counts', 10, 2 );


/**
 * Filter where older than one week
 *
 * @access public
 * @since 1.6
 * @param string $where Where clause
 * @return string $where Modified where clause
*/
function pdd_filter_where_older_than_week( $where = '' ) {
	// Payments older than one week
	$start = date( 'Y-m-d', strtotime( '-7 days' ) );
	$where .= " AND post_date <= '{$start}'";
	return $where;
}
