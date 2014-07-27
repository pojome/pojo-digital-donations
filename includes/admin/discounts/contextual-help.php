<?php
/**
 * Contextual Help
 *
 * @package     PDD
 * @subpackage  Admin/Discounts
 * @copyright   Copyright (c) 2014, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.2.3
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Adds the Contextual Help for the Discount Codes Page
 *
 * @since 1.3
 * @return void
 */
function pdd_discounts_contextual_help() {
	$screen = get_current_screen();

	$screen->set_help_sidebar(
		'<p><strong>' . sprintf( __( 'For more information:', 'pdd' ) . '</strong></p>' .
		'<p>' . sprintf( __( 'Visit the <a href="%s">documentation</a> on the Pojo Digital Donations website.', 'pdd' ), esc_url( 'https://easydigitaldownloads.com/documentation/' ) ) ) . '</p>' .
		'<p>' . sprintf(
					__( '<a href="%s">Post an issue</a> on <a href="%s">GitHub</a>. View <a href="%s">extensions</a> or <a href="%s">themes</a>.', 'pdd' ),
					esc_url( 'https://github.com/easydigitaldownloads/Easy-Digital-Downloads/issues' ),
					esc_url( 'https://github.com/easydigitaldownloads/Easy-Digital-Downloads' ),
					esc_url( 'https://easydigitaldownloads.com/extensions/' ),
					esc_url( 'https://easydigitaldownloads.com/themes/' )
				) . '</p>'
	);

	$screen->add_help_tab( array(
		'id'	    => 'pdd-discount-general',
		'title'	    => __( 'General', 'pdd' ),
		'content'	=>
			'<p>' . __( 'Discount codes allow you to offer buyers special discounts by having them enter predefined codes during checkout.', 'pdd' ) . '</p>' .
			'<p>' . __( 'Discount codes that are set to "inactive" cannot be redeemed.', 'pdd' ) . '</p>' .
			'<p>' . __( 'Discount codes are setup to only be used only one time by each customer. If a customer attempts to use a code a second time, they will be given an error.', 'pdd' ) . '</p>'
	) );

	$screen->add_help_tab( array(
		'id'	    => 'pdd-discount-add',
		'title'	    => __( 'Adding Discounts', 'pdd' ),
		'content'	=>
			'<p>' . __( 'You can create any number of discount codes easily from this page.', 'pdd' ) . '</p>' .
			'<p>' . __( 'Discount codes have several options:', 'pdd' ) . '</p>' .
			'<ul>'.
				'<li>' . __( '<strong>Name</strong> - this is the name given to the discount. Used primarily for administrative purposes.', 'pdd' ) . '</li>' .
				'<li>' . __( '<strong>Code</strong> - this is the unique code that customers will enter during checkout to redeem the code.', 'pdd' ) . '</li>' .
				'<li>' . __( '<strong>Type</strong> - this is the type of discount this code awards.', 'pdd' ) . '</li>' .
				'<li>' . __( '<strong>Amount</strong> - this discount amount provided by this code. For percentage based discounts, enter a number such as 70 for 70%. Do not enter a percent sign.', 'pdd' ) . '</li>' .
				'<li>' . __( '<strong>Requirements</strong> - This allows you to select the product(s) that are required to be purchased in order for a discount to be applied.', 'pdd' ) . '</li>' .
				'<li>' . __( '- <strong>Condition</strong> - This lets you set whether all selected products must be in the cart, or just a minimum of one.', 'pdd' ) . '</li>' .
				'<li>' . __( '- <strong>Apply discount only to selected Downloads?</strong> - If this box is checked, only the prices of the required products will be discounted. If left unchecked, the discount will apply to all products in the cart.', 'pdd' ) . '</li>' .
				'<li>' . __( '<strong>Start Date</strong> - this is the date that this code becomes available. If a customer attempts to redeem the code prior to this date, they will be given an error. This is optional.', 'pdd' ) . '</li>' .
				'<li>' . __( '<strong>Expiration Date</strong> - this is the end date for the discount. After this date, the code will no longer be able to be used. This is optional.', 'pdd' ) . '</li>' .
				'<li>' . __( '<strong>Minimum Amount</strong> - this is the minimum purchase amount required to use this code. If a customer has less than this amount in their cart, they will be given an error. This is optional.', 'pdd' ) . '</li>' .
				'<li>' . __( '<strong>Max Uses</strong> - this is the maximum number of times this discount can be redeemed. Once this number is reached, no more customers will be allowed to use it.', 'pdd' ) . '</li>' .
			'</ul>'
	) );

	do_action( 'pdd_discounts_contextual_help', $screen );
}
add_action( 'load-download_page_pdd-discounts', 'pdd_discounts_contextual_help' );