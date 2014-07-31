<?php
/**
 * Exports Functions
 *
 * These are functions are used for exporting data from Pojo Digital Donations.
 *
 * @package     PDD
 * @subpackage  Admin/Export
 * @copyright   Copyright (c) 2014, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

require_once PDD_PLUGIN_DIR . 'includes/admin/reporting/class-export.php';

/**
 * Exports earnings for a specified time period
 * PDD_Earnings_Export class.
 *
 * @since 2.0
 * @return void
 */
function pdd_export_earnings() {
	require_once PDD_PLUGIN_DIR . 'includes/admin/reporting/class-export-earnings.php';

	$earnings_export = new PDD_Earnings_Export();

	$earnings_export->export();
}
add_action( 'pdd_earnings_export', 'pdd_export_earnings' );

/**
 * Exports all the payments stored in Payment History to a CSV file using the
 * PDD_Export class.
 *
 * @since 1.4.4
 * @return void
 */
function pdd_export_payment_history() {
	require_once PDD_PLUGIN_DIR . 'includes/admin/reporting/class-export-payments.php';

	$payments_export = new PDD_Payments_Export();

	$payments_export->export();
}
add_action( 'pdd_payment_export', 'pdd_export_payment_history' );

/**
 * Export all the customers to a CSV file.
 *
 * Note: The WordPress Database API is being used directly for performance
 * reasons (workaround of calling all posts and fetch data respectively)
 *
 * @since 1.4.4
 * @return void
 */
function pdd_export_all_customers() {
	require_once PDD_PLUGIN_DIR . 'includes/admin/reporting/class-export-customers.php';

	$customer_export = new PDD_Customers_Export();

	$customer_export->export();
}
add_action( 'pdd_email_export', 'pdd_export_all_customers' );

/**
 * Exports all the downloads to a CSV file using the PDD_Export class.
 *
 * @since 1.4.4
 * @return void
 */
function pdd_export_all_downloads_history() {
	require_once PDD_PLUGIN_DIR . 'includes/admin/reporting/class-export-download-history.php';

	$file_download_export = new PDD_Download_History_Export();

	$file_download_export->export();
}
add_action( 'pdd_camps_history_export', 'pdd_export_all_downloads_history' );