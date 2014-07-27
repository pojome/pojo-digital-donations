<?php
/**
 * Customers Export Class
 *
 * This class handles customer export
 *
 * @package     PDD
 * @subpackage  Admin/Reports
 * @copyright   Copyright (c) 2014, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.4.4
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * PDD_Customers_Export Class
 *
 * @since 1.4.4
 */
class PDD_Customers_Export extends PDD_Export {
	/**
	 * Our export type. Used for export-type specific filters/actions
	 *
	 * @var string
	 * @since 1.4.4
	 */
	public $export_type = 'customers';

	/**
	 * Set the export headers
	 *
	 * @access public
	 * @since 1.4.4
	 * @return void
	 */
	public function headers() {
		ignore_user_abort( true );

		if ( ! pdd_is_func_disabled( 'set_time_limit' ) && ! ini_get( 'safe_mode' ) )
			set_time_limit( 0 );

		$extra = '';

		if ( ! empty( $_POST['pdd_export_download'] ) ) {
			$extra = sanitize_title( get_the_title( absint( $_POST['pdd_export_download'] ) ) ) . '-';
		}

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . apply_filters( 'pdd_customers_export_filename', 'pdd-export-' . $extra . $this->export_type . '-' . date( 'm-d-Y' ) ) . '.csv' );
		header( "Expires: 0" );
	}

	/**
	 * Set the CSV columns
	 *
	 * @access public
	 * @since 1.4.4
	 * @return array $cols All the columns
	 */
	public function csv_cols() {
		if ( ! empty( $_POST['pdd_export_download'] ) ) {
			$cols = array(
				'first_name' => __( 'First Name',   'pdd' ),
				'last_name'  => __( 'Last Name',   'pdd' ),
				'email'      => __( 'Email', 'pdd' ),
				'date'       => __( 'Date Purchased', 'pdd' )
			);
		} else {

			$cols = array();

			if( 'emails' != $_POST['pdd_export_option'] ) {
				$cols['name'] = __( 'Name',   'pdd' );
			}

			$cols['email'] = __( 'Email',   'pdd' );

			if( 'full' == $_POST['pdd_export_option'] ) {
				$cols['purchases'] = __( 'Total Purchases',   'pdd' );
				$cols['amount']    = __( 'Total Purchased', 'pdd' ) . ' (' . html_entity_decode( pdd_currency_filter( '' ) ) . ')';
			}

		}

		return $cols;
	}

	/**
	 * Get the Export Data
	 *
	 * @access public
	 * @since 1.4.4
	 * @global object $wpdb Used to query the database using the WordPress
	 *   Database API
	 * @global object $pdd_logs PDD Logs Object
	 * @return array $data The data for the CSV file
	 */
	public function get_data() {
		global $wpdb;

		$data = array();

		if ( ! empty( $_POST['pdd_export_download'] ) ) {

			// Export customers of a specific product
			global $pdd_logs;

			$args = array(
				'post_parent' => absint( $_POST['pdd_export_download'] ),
				'log_type'    => 'sale',
				'nopaging'    => true
			);

			if( isset( $_POST['pdd_price_option'] ) ) {
				$args['meta_query'] = array(
					array(
						'key'   => '_pdd_log_price_id',
						'value' => (int) $_POST['pdd_price_option']
					)
				);
			}

			$logs = $pdd_logs->get_connected_logs( $args );

			if ( $logs ) {
				foreach ( $logs as $log ) {
					$payment_id = get_post_meta( $log->ID, '_pdd_log_payment_id', true );
					$user_info  = pdd_get_payment_meta_user_info( $payment_id );
					$data[] = array(
						'first_name' => $user_info['first_name'],
						'last_name'  => $user_info['last_name'],
						'email'      => $user_info['email'],
						'date'       => $log->post_date
					);
				}
			}
		} else {
			// Export all customers
			$emails = $wpdb->get_col( "SELECT DISTINCT meta_value FROM $wpdb->postmeta WHERE meta_key = '_pdd_payment_user_email' " );

			$i = 0;

			foreach ( $emails as $email ) {

				if( 'emails' != $_POST['pdd_export_option'] ) {
					$wp_user = get_user_by( 'email', $email );
					$data[$i]['name'] = $wp_user ? $wp_user->display_name : __( 'Guest', 'pdd' );
				}

				$data[$i]['email'] = $email;

				if( 'full' == $_POST['pdd_export_option'] ) {
					$stats = pdd_get_purchase_stats_by_user( $email );
					$data[$i]['purchases'] = $stats['purchases'];
					$data[$i]['amount']    = pdd_format_amount( $stats['total_spent'] );
				}
				$i++;
			}
		}

		$data = apply_filters( 'pdd_export_get_data', $data );
		$data = apply_filters( 'pdd_export_get_data_' . $this->export_type, $data );

		return $data;
	}
}