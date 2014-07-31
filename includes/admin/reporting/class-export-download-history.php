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
 * PDD_Download_History_Export Class
 *
 * @since 1.4.4
 */
class PDD_Download_History_Export extends PDD_Export {
	/**
	 * Our export type. Used for export-type specific filters/actions
	 *
	 * @var string
	 * @since 1.4.4
	 */
	public $export_type = 'download_history';


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

		$month = isset( $_POST['month'] ) ? absint( $_POST['month'] ) : date( 'n' );
		$year  = isset( $_POST['year']  ) ? absint( $_POST['year']  ) : date( 'Y' );

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . apply_filters( 'pdd_camp_history_export_filename', 'pdd-export-' . $this->export_type . '-' . $month . '-' . $year ) . '.csv' );
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
		$cols = array(
			'date'     => __( 'Date',   'pdd' ),
			'user'     => __( 'Downloaded by', 'pdd' ),
			'ip'       => __( 'IP Address', 'pdd' ),
			'pdd_camp' => __( 'Product', 'pdd' ),
			'file'     => __( 'File', 'pdd' )
		);
		return $cols;
	}

	/**
	 * Get the Export Data
	 *
	 * @access public
	 * @since 1.4.4
 	 * @global object $pdd_logs PDD Logs Object
	 * @return array $data The data for the CSV file
	 */
	public function get_data() {
		global $pdd_logs;

		$data = array();

		$args = array(
			'nopaging' => true,
			'log_type' => 'file_download',
			'monthnum' => isset( $_POST['month'] ) ? absint( $_POST['month'] ) : date( 'n' ),
			'year'     => isset( $_POST['year'] ) ? absint( $_POST['year'] ) : date( 'Y' )
		);

		$logs = $pdd_logs->get_connected_logs( $args );

		if ( $logs ) {
			foreach ( $logs as $log ) {
				$user_info = get_post_meta( $log->ID, '_pdd_log_user_info', true );
				$files     = pdd_get_download_files( $log->post_parent );
				$file_id   = (int) get_post_meta( $log->ID, '_pdd_log_file_id', true );
				$file_name = isset( $files[ $file_id ]['name'] ) ? $files[ $file_id ]['name'] : null;
				$user      = get_userdata( $user_info['id'] );
				$user      = $user ? $user->user_login : $user_info['email'];

				$data[]    = array(
					'date'     => $log->post_date,
					'user'     => $user,
					'ip'       => get_post_meta( $log->ID, '_pdd_log_ip', true ),
					'pdd_camp' => get_the_title( $log->post_parent ),
					'file'     => $file_name
				);
			}
		}

		$data = apply_filters( 'pdd_export_get_data', $data );
		$data = apply_filters( 'pdd_export_get_data_' . $this->export_type, $data );

		return $data;
	}
}