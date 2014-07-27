<?php
/**
 * Gateway Actions
 *
 * @package     PDD
 * @subpackage  Gateways
 * @copyright   Copyright (c) 2014, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.7
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Processes gateway select on checkout. Only for users without ajax / javascript
 *
 * @since 1.7
 *
 * @param $data
 */
function pdd_process_gateway_select( $data ) {
	if( isset( $_POST['gateway_submit'] ) ) {
		wp_redirect( add_query_arg( 'payment-mode', $_POST['payment-mode'] ) ); exit;
	}
}
add_action( 'pdd_gateway_select', 'pdd_process_gateway_select' );

/**
 * Loads a payment gateway via AJAX
 *
 * @since 1.3.4
 * @return void
 */
function pdd_load_ajax_gateway() {
	if ( isset( $_POST['pdd_payment_mode'] ) ) {
		do_action( 'pdd_purchase_form' );
		exit();
	}
}
add_action( 'wp_ajax_pdd_load_gateway', 'pdd_load_ajax_gateway' );
add_action( 'wp_ajax_nopriv_pdd_load_gateway', 'pdd_load_ajax_gateway' );

/**
 * Sets an error on checkout if no gateways are enabled
 *
 * @since 1.3.4
 * @return void
 */
function pdd_no_gateway_error() {
	$gateways = pdd_get_enabled_payment_gateways();

	if ( empty( $gateways ) )
		pdd_set_error( 'no_gateways', __( 'You must enable a payment gateway to use Pojo Digital Donations', 'pdd' ) );
	else
		pdd_unset_error( 'no_gateways' );
}
add_action( 'init', 'pdd_no_gateway_error' );