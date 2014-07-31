<?php
/**
 * Upgrade Functions
 *
 * @package     PDD
 * @subpackage  Admin/Upgrades
 * @copyright   Copyright (c) 2014, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.3.1
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Display Upgrade Notices
 *
 * @since 1.3.1
 * @return void
*/
function pdd_show_upgrade_notices() {
	if ( isset( $_GET['page'] ) && $_GET['page'] == 'pdd-upgrades' )
		return; // Don't show notices on the upgrades page

	$pdd_version = get_option( 'pdd_version' );

	if ( ! $pdd_version ) {
		// 1.3 is the first version to use this option so we must add it
		$pdd_version = '1.3';
	}

	$pdd_version = preg_replace( '/[^0-9.].*/', '', $pdd_version );

	if ( ! get_option( 'pdd_payment_totals_upgraded' ) && ! get_option( 'pdd_version' ) ) {
		if ( wp_count_posts( 'pdd_payment' )->publish < 1 )
			return; // No payment exist yet

		// The payment history needs updated for version 1.2
		$url = add_query_arg( 'pdd-action', 'upgrade_payments' );
		$upgrade_notice = sprintf( __( 'The Payment History needs to be updated. %s', 'pdd' ), '<a href="' . wp_nonce_url( $url, 'pdd_upgrade_payments_nonce' ) . '">' . __( 'Click to Upgrade', 'pdd' ) . '</a>' );
		add_settings_error( 'pdd-notices', 'pdd-payments-upgrade', $upgrade_notice, 'error' );
	}

	if ( version_compare( $pdd_version, '1.3.2', '<' ) && ! get_option( 'pdd_logs_upgraded' ) ) {
		printf(
			'<div class="updated"><p>' . esc_html__( 'The Purchase and File Download History in Pojo Digital Donations needs to be upgraded, click %shere%s to start the upgrade.', 'pdd' ) . '</p></div>',
			'<a href="' . esc_url( admin_url( 'options.php?page=pdd-upgrades' ) ) . '">',
			'</a>'
		);
	}

	if ( version_compare( $pdd_version, '1.3.4', '<' ) || version_compare( $pdd_version, '1.4', '<' ) ) {
		printf(
			'<div class="updated"><p>' . esc_html__( 'Pojo Digital Donations needs to upgrade the plugin pages, click %shere%s to start the upgrade.', 'pdd' ) . '</p></div>',
			'<a href="' . esc_url( admin_url( 'options.php?page=pdd-upgrades' ) ) . '">',
			'</a>'
		);
	}

	if ( version_compare( $pdd_version, '1.5', '<' ) ) {
		printf(
			'<div class="updated"><p>' . esc_html__( 'Pojo Digital Donations needs to upgrade the database, click %shere%s to start the upgrade.', 'pdd' ) . '</p></div>',
			'<a href="' . esc_url( admin_url( 'options.php?page=pdd-upgrades' ) ) . '">',
			'</a>'
		);
	}

	if ( version_compare( $pdd_version, '2.0', '<' ) ) {
		printf(
			'<div class="updated"><p>' . esc_html__( 'Pojo Digital Donations needs to upgrade the database, click %shere%s to start the upgrade.', 'pdd' ) . '</p></div>',
			'<a href="' . esc_url( admin_url( 'options.php?page=pdd-upgrades' ) ) . '">',
			'</a>'
		);
	}

	if ( PDD()->session->get( 'upgrade_sequential' ) && pdd_get_payments() ) {
		printf(
			'<div class="updated"><p>' . __( 'Pojo Digital Donations needs to upgrade past order numbers to make them sequential, click <a href="%s">here</a> to start the upgrade.', 'pdd' ) . '</p></div>',
			admin_url( 'index.php?page=pdd-upgrades&pdd-upgrade=upgrade_sequential_payment_numbers' )
		);
	}

}
add_action( 'admin_notices', 'pdd_show_upgrade_notices' );

/**
 * Triggers all upgrade functions
 *
 * This function is usually triggered via AJAX
 *
 * @since 1.3.1
 * @return void
*/
function pdd_trigger_upgrades() {
	$pdd_version = get_option( 'pdd_version' );

	if ( ! $pdd_version ) {
		// 1.3 is the first version to use this option so we must add it
		$pdd_version = '1.3';
		add_option( 'pdd_version', $pdd_version );
	}

	if ( version_compare( PDD_VERSION, $pdd_version, '>' ) ) {
		pdd_v131_upgrades();
	}

	if ( version_compare( $pdd_version, '1.3.4', '<' ) ) {
		pdd_v134_upgrades();
	}

	if ( version_compare( $pdd_version, '1.4', '<' ) ) {
		pdd_v14_upgrades();
	}

	if ( version_compare( $pdd_version, '1.5', '<' ) ) {
		pdd_v15_upgrades();
	}

	if ( version_compare( $pdd_version, '2.0', '<' ) ) {
		pdd_v20_upgrades();
	}

	update_option( 'pdd_version', PDD_VERSION );

	if ( DOING_AJAX )
		die( 'complete' ); // Let AJAX know that the upgrade is complete
}
add_action( 'wp_ajax_pdd_trigger_upgrades', 'pdd_trigger_upgrades' );

/**
 * Converts old sale and file download logs to new logging system
 *
 * @since 1.3.1
 * @uses WP_Query
 * @uses PDD_Logging
 * @return void
 */
function pdd_v131_upgrades() {
	if ( get_option( 'pdd_logs_upgraded' ) )
		return;

	if ( version_compare( get_option( 'pdd_version' ), '1.3', '>=' ) )
		return;

	ignore_user_abort( true );

	if ( ! pdd_is_func_disabled( 'set_time_limit' ) && ! ini_get( 'safe_mode' ) )
		set_time_limit( 0 );

	$args = array(
		'post_type' 		=> 'pdd_camp',
		'posts_per_page' 	=> -1,
		'post_status' 		=> 'publish'
	);

	$query = new WP_Query( $args );
	$count = $query->post_count;
	$downloads = $query->get_posts();

	if ( $downloads ) {
		$pdd_log = new PDD_Logging();
		$i = 0;
		foreach ( $downloads as $download ) {
			// Convert sale logs
			$sale_logs = pdd_get_download_sales_log( $download->ID, false );

			if ( $sale_logs ) {
				foreach ( $sale_logs['sales'] as $sale ) {
					$log_data = array(
						'post_parent'	=> $download->ID,
						'post_date'		=> $sale['date'],
						'log_type'		=> 'sale'
					);

					$log_meta = array(
						'payment_id'=> $sale['payment_id']
					);

					$log = $pdd_log->insert_log( $log_data, $log_meta );
				}
			}

			// Convert file download logs
			$file_logs = pdd_get_file_download_log( $download->ID, false );

			if ( $file_logs ) {
				foreach ( $file_logs['campaigns'] as $log ) {
					$log_data = array(
						'post_parent'	=> $download->ID,
						'post_date'		=> $log['date'],
						'log_type'		=> 'file_download'

					);

					$log_meta = array(
						'user_info'	=> $log['user_info'],
						'file_id'	=> $log['file_id'],
						'ip'		=> $log['ip']
					);

					$log = $pdd_log->insert_log( $log_data, $log_meta );
				}
			}
		}
	}
	add_option( 'pdd_logs_upgraded', '1' );
}

/**
 * Upgrade routine for v1.3.4
 *
 * @since 1.3.4
 * @return void
 */
function pdd_v134_upgrades() {
	$general_options = get_option( 'pdd_settings_general' );

	if ( isset( $general_options['failure_page'] ) )
		return; // Settings already updated

	// Failed Purchase Page
	$failed = wp_insert_post(
		array(
			'post_title'     => __( 'Transaction Failed', 'pdd' ),
			'post_content'   => __( 'Your transaction failed, please try again or contact site support.', 'pdd' ),
			'post_status'    => 'publish',
			'post_author'    => 1,
			'post_type'      => 'page',
			'post_parent'    => $general_options['purchase_page'],
			'comment_status' => 'closed'
		)
	);

	$general_options['failure_page'] = $failed;

	update_option( 'pdd_settings_general', $general_options );
}

/**
 * Upgrade routine for v1.4
 *
 * @since 1.4
 * @global $pdd_options Array of all the PDD Options
 * @return void
 */
function pdd_v14_upgrades() {
	global $pdd_options;

	/** Add [pdd_receipt] to success page **/
	$success_page = get_post( $pdd_options['success_page'] );

	// Check for the [pdd_receipt] short code and add it if not present
	if( strpos( $success_page->post_content, '[pdd_receipt' ) === false ) {
		$page_content = $success_page->post_content .= "\n[pdd_receipt]";
		wp_update_post( array( 'ID' => $pdd_options['success_page'], 'post_content' => $page_content ) );
	}

	/** Convert Discounts to new Custom Post Type **/
	$discounts = get_option( 'pdd_discounts' );

	if ( $discounts ) {
		foreach ( $discounts as $discount_key => $discount ) {
			$status = isset( $discount['status'] ) ? $discount['status'] : 'inactive';

			$discount_id = wp_insert_post( array(
				'post_type'   => 'pdd_discount',
				'post_title'  => isset( $discount['name'] ) ? $discount['name'] : '',
				'post_status' => 'active'
			) );

			$meta = array(
				'code'        => isset( $discount['code'] ) ? $discount['code'] : '',
				'uses'        => isset( $discount['uses'] ) ? $discount['uses'] : '',
				'max_uses'    => isset( $discount['max'] ) ? $discount['max'] : '',
				'amount'      => isset( $discount['amount'] ) ? $discount['amount'] : '',
				'start'       => isset( $discount['start'] ) ? $discount['start'] : '',
				'expiration'  => isset( $discount['expiration'] ) ? $discount['expiration'] : '',
				'type'        => isset( $discount['type'] ) ? $discount['type'] : '',
				'min_price'   => isset( $discount['min_price'] ) ? $discount['min_price'] : ''
			);

			foreach ( $meta as $meta_key => $value ) {
				update_post_meta( $discount_id, '_pdd_discount_' . $meta_key, $value );
			}
		}

		// Remove old discounts from database
		delete_option( 'pdd_discounts' );
	}
}


/**
 * Upgrade routine for v1.5
 *
 * @since 1.5
 * @return void
 */
function pdd_v15_upgrades() {
	// Update options for missing tax settings
	$tax_options = get_option( 'pdd_settings_taxes' );

	// Set include tax on checkout to off
	$tax_options['checkout_include_tax'] = 'no';

	// Check if prices are displayed with taxes
	if( isset( $tax_options['taxes_on_prices'] ) ) {
		$tax_options['prices_include_tax'] = 'yes';
	} else {
		$tax_options['prices_include_tax'] = 'no';
	}

	update_option( 'pdd_settings_taxes', $tax_options );

	// Flush the rewrite rules for the new /pdd-api/ end point
	flush_rewrite_rules();
}

/**
 * Upgrades for PDD v2.0
 *
 * @since 2.0
 * @return void
 */
function pdd_v20_upgrades() {

	global $pdd_options, $wpdb;

	ignore_user_abort( true );

	if ( ! pdd_is_func_disabled( 'set_time_limit' ) && ! ini_get( 'safe_mode' ) ) {
		set_time_limit( 0 );
	}

	// Upgrade for the anti-behavior fix - #2188
	if( ! empty( $pdd_options['disable_ajax_cart'] ) ) {
		unset( $pdd_options['enable_ajax_cart'] );
	} else {
		$pdd_options['enable_ajax_cart'] = '1';
	}

	// Upgrade for the anti-behavior fix - #2188
	if( ! empty( $pdd_options['disable_cart_saving'] ) ) {
		unset( $pdd_options['enable_cart_saving'] );
	} else {
		$pdd_options['enable_cart_saving'] = '1';
	}

	// Properly set the register / login form options based on whether they were enabled previously - #2076
	if( ! empty( $pdd_options['show_register_form'] ) ) {
		$pdd_options['show_register_form'] = 'both';
	} else {
		$pdd_options['show_register_form'] = 'none';
	}

	// Remove all old, improperly expired sessions. See https://github.com/easydigitaldownloads/Easy-Digital-Downloads/issues/2031
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_wp_session_expires_%' AND option_value+0 < 2789308218" );

	update_option( 'pdd_settings', $pdd_options );

}

/**
 * Upgrades for PDD v2.0 and sequential payment numbers
 *
 * @since 2.0
 * @return void
 */
function pdd_v20_upgrade_sequential_payment_numbers() {

	ignore_user_abort( true );

	if ( ! pdd_is_func_disabled( 'set_time_limit' ) && ! ini_get( 'safe_mode' ) ) {
		set_time_limit( 0 );
	}

	$step   = isset( $_GET['step'] )  ? absint( $_GET['step'] )  : 1;
	$total  = isset( $_GET['total'] ) ? absint( $_GET['total'] ) : false;

	if( empty( $total ) || $total <= 1 ) {
		$payments = pdd_count_payments();
		foreach( $payments as $status ) {
			$total += $status;
		}
	}

	$args   = array(
		'number' => 100,
		'page'   => $step,
		'status' => 'any',
		'order'  => 'ASC'
	);

	$payments = new PDD_Payments_Query( $args );
	$payments = $payments->get_payments();

	if( $payments ) {

		$prefix  = pdd_get_option( 'sequential_prefix' );
		$postfix = pdd_get_option( 'sequential_postfix' );
		$number  = ! empty( $_GET['custom'] ) ? absint( $_GET['custom'] ) : intval( pdd_get_option( 'sequential_start', 1 ) );

		foreach( $payments as $payment ) {
			
			// Re-add the prefix and postfix
			$payment_number = $prefix . $number . $postfix;

			update_post_meta( $payment->ID, '_pdd_payment_number', $payment_number );

			// Increment the payment number
			$number++;
				
		}

		// Payments found so upgrade them
		$step++;
		$redirect = add_query_arg( array(
			'page'        => 'pdd-upgrades',
			'pdd-upgrade' => 'upgrade_sequential_payment_numbers',
			'step'        => $step,
			'custom'      => $number,
			'total'       => $total
		), admin_url( 'index.php' ) );
		wp_redirect( $redirect ); exit;

	} else {


		// No more payments found, finish up
		PDD()->session->set( 'upgrade_sequential', null );
		wp_redirect( admin_url() ); exit;
	}

}
add_action( 'pdd_upgrade_sequential_payment_numbers', 'pdd_v20_upgrade_sequential_payment_numbers' );
