<?php
/**
 * Tax Functions
 *
 * These are functions used for checking if taxes are enabled, calculating taxes, etc.
 * Functions for retrieving tax amounts and such for individual payments are in
 * includes/payment-functions.php and includes/cart-functions.php
 *
 * @package     PDD
 * @subpackage  Functions/Taxes
 * @copyright   Copyright (c) 2014, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.3.3
*/

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Checks if taxes are enabled by using the option set from the PDD Settings.
 * The value returned can be filtered.
 *
 * @since 1.3.3
 * @global $pdd_options
 * @return bool Whether or not taxes are enabled
 */
function pdd_use_taxes() {
	global $pdd_options;

	return apply_filters( 'pdd_use_taxes', isset( $pdd_options['enable_taxes'] ) );
}

/**
 * Checks if the user has enabled the option to calculate taxes after discounts
 * have been entered
 *
 * @since 1.4.1
 * @global $pdd_options
 * @return bool Whether or not taxes are calculated after discount
 */
function pdd_taxes_after_discounts() {
	global $pdd_options;
	$ret = isset( $pdd_options['taxes_after_discounts'] ) && pdd_use_taxes();
	return apply_filters( 'pdd_taxes_after_discounts', $ret );
}

/**
 * Retrieve tax rates
 *
 * @since 1.6
 * @global $pdd_options
 * @return array Defined tax rates
 */
function pdd_get_tax_rates() {

	$rates = get_option( 'pdd_tax_rates', array() );
	return apply_filters( 'pdd_get_tax_rates', $rates );
}

/**
 * Get taxation rate
 *
 * @since 1.3.3
 * @global $pdd_options
 *
 * @param bool $country
 * @param bool $state
 * @return mixed|void
 */
function pdd_get_tax_rate( $country = false, $state = false ) {
	global $pdd_options;

	$rate = isset( $pdd_options['tax_rate'] ) ? (float) $pdd_options['tax_rate'] : 0;

	$user_address = pdd_get_customer_address();

	if( empty( $country ) ) {
		if( ! empty( $_POST['billing_country'] ) ) {
			$country = $_POST['billing_country'];
		} elseif( is_user_logged_in() && ! empty( $user_address ) ) {
			$country = $user_address['country'];
		}
		$country = ! empty( $country ) ? $country : pdd_get_shop_country();
	}

	if( empty( $state ) ) {
		if( ! empty( $_POST['state'] ) ) {
			$state = $_POST['state'];
		} elseif( is_user_logged_in() && ! empty( $user_address ) ) {
			$state = $user_address['state'];
		}
		$state = ! empty( $state ) ? $state : pdd_get_shop_state();
	}

	if( ! empty( $country ) ) {
		$tax_rates   = pdd_get_tax_rates();

		if( ! empty( $tax_rates ) ) {

			// Locate the tax rate for this country / state, if it exists
			foreach( $tax_rates as $key => $tax_rate ) {

				if( $country != $tax_rate['country'] )
					continue;

				if( ! empty( $tax_rate['global'] ) ) {
					if( ! empty( $tax_rate['rate'] ) ) {
						$rate = number_format( $tax_rate['rate'], 4 );
					}
				} else {

					if( empty( $tax_rate['state'] ) || strtolower( $state ) != strtolower( $tax_rate['state'] ) )
						continue;

					$state_rate = $tax_rate['rate'];
					if( 0 !== $state_rate || ! empty( $state_rate ) ) {
						$rate = number_format( $state_rate, 4 );
					}
				}
			}
		}
	}

	if( $rate > 1 ) {
		// Convert to a number we can use
		$rate = $rate / 100;
	}
	return apply_filters( 'pdd_tax_rate', $rate, $country, $state );
}

/**
 * Retrieve a fully formatted tax rate
 *
 * @since 1.9
 * @param string $country The country to retrieve a rate for
 * @param string $state The state to retrieve a rate for
 * @return string Formatted rate
 */
function pdd_get_formatted_tax_rate( $country = false, $state = false ) {
	$rate = pdd_get_tax_rate( $country, $state );
	$rate = round( $rate * 100, 4 );
	$formatted = $rate .= '%';
	return apply_filters( 'pdd_formatted_tax_rate', $formatted, $rate, $country, $state );
}

/**
 * Calculate the taxed amount
 *
 * @since 1.3.3
 * @param $amount float The original amount to calculate a tax cost
 * @param $country string The country to calculate tax for. Will use default if not passed
 * @param $state string The state to calculate tax for. Will use default if not passed
 * @return float $tax Taxed amount
 */
function pdd_calculate_tax( $amount = 0, $country = false, $state = false ) {
	global $pdd_options;

	$rate = pdd_get_tax_rate( $country, $state );
	$tax  = 0.00;

	if ( pdd_use_taxes() ) {

		if ( pdd_prices_include_tax() ) {
			$pre_tax = ( $amount / ( 1 + $rate ) );
			$tax     = $amount - $pre_tax;
		} else {
			$tax = $amount * $rate;
		}

	}

	return apply_filters( 'pdd_taxed_amount', $tax, $rate, $country, $state );
}

/**
 * Stores the tax info in the payment meta
 *
 * @since 1.3.3
 * @param $year int The year to retrieve taxes for, i.e. 2012
 * @uses pdd_get_sales_tax_for_year()
 * @return void
*/
function pdd_sales_tax_for_year( $year = null ) {
	echo pdd_currency_filter( pdd_format_amount( pdd_get_sales_tax_for_year( $year ) ) );
}

/**
 * Gets the sales tax for the current year
 *
 * @since 1.3.3
 * @param $year int The year to retrieve taxes for, i.e. 2012
 * @uses pdd_get_payment_tax()
 * @return float $tax Sales tax
 */
function pdd_get_sales_tax_for_year( $year = null ) {
	
	// Start at zero
	$tax = 0;

	if ( ! empty( $year ) ) {


		$args = array(
			'post_type' 		=> 'pdd_payment',
			'post_status'       => array( 'publish', 'revoked' ),
			'posts_per_page' 	=> -1,
			'year' 				=> $year,
			'fields'			=> 'ids'
		);

		$payments = get_posts( $args );

		if( $payments ) {

			foreach( $payments as $payment ) {
				$tax += pdd_get_payment_tax( $payment );
			}

		}

	}

	return apply_filters( 'pdd_get_sales_tax_for_year', $tax, $year );
}

/**
 * Is the cart taxed?
 *
 * This used to include a check for local tax opt-in, but that was ripped out in v1.6, so this is just a wrapper now
 *
 * @since 1.5
 * @return bool
 */
function pdd_is_cart_taxed() {
	return pdd_use_taxes();
}

/**
 * Check if the individual product prices include tax
 *
 * @since 1.5
 * @global $pdd_options
 * @return bool $include_tax
*/
function pdd_prices_include_tax() {
	global $pdd_options;

	$ret = isset( $pdd_options['prices_include_tax'] ) && $pdd_options['prices_include_tax'] == 'yes' && pdd_use_taxes();

	return apply_filters( 'pdd_prices_include_tax', $ret );
}

/**
 * Checks whether the user has enabled display of taxes on the checkout
 *
 * @since 1.5
 * @global $pdd_options
 * @return bool $include_tax
 */
function pdd_prices_show_tax_on_checkout() {
	global $pdd_options;
	$ret = isset( $pdd_options['checkout_include_tax'] ) && $pdd_options['checkout_include_tax'] == 'yes' && pdd_use_taxes();
	return apply_filters( 'pdd_taxes_on_prices_on_checkout', $ret );
}

/**
 * Check to see if we should show included taxes
 *
 * Some countries (notably in the EU) require included taxes to be displayed.
 *
 * @since 1.7
 * @author Daniel J Griffiths
 * @return bool
 */
function pdd_display_tax_rate() {
	global $pdd_options;

	$ret = pdd_use_taxes() && isset( $pdd_options['display_tax_rate'] );

	return apply_filters( 'pdd_display_tax_rate', $ret );
}

/**
 * Should we show address fields
 *
 * @since 1.y
 * @return bool
 */
function pdd_cart_needs_billing_address_fields() {
	if ( ! pdd_use_billing_address() )
		return false;
	return ! did_action( 'pdd_after_cc_fields', 'pdd_default_cc_address_fields' );
}

function pdd_use_billing_address() {
	return (bool) pdd_get_option( 'enable_billing_address' );
}

/**
 * Is this Download excluded from tax?
 *
 * @since 1.9
 * @return bool
 */
function pdd_camp_is_tax_exclusive( $download_id = 0 ) {
	$ret = (bool) get_post_meta( $download_id, '_pdd_camp_tax_exclusive', true );
	return apply_filters( 'pdd_camp_is_tax_exclusive', $ret, $download_id );
}