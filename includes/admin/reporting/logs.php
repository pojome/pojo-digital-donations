<?php
/**
 * Logs UI
 *
 * @package     PDD
 * @subpackage  Admin/Reports
 * @copyright   Copyright (c) 2014, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.4
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Sales Log View
 *
 * @since 1.4
 * @uses PDD_Sales_Log_Table::prepare_items()
 * @uses PDD_Sales_Log_Table::display()
 * @return void
 */
function pdd_logs_view_sales() {
	include( dirname( __FILE__ ) . '/class-sales-logs-list-table.php' );

	$logs_table = new PDD_Sales_Log_Table();
	$logs_table->prepare_items();
	$logs_table->display();

}
add_action( 'pdd_logs_view_sales', 'pdd_logs_view_sales' );

/**
 * File Download Logs
 *
 * @since 1.4
 * @uses PDD_File_Downloads_Log_Table::prepare_items()
 * @uses PDD_File_Downloads_Log_Table::search_box()
 * @uses PDD_File_Downloads_Log_Table::display()
 * @return void
 */
function pdd_logs_view_file_downloads() {
	include( dirname( __FILE__ ) . '/class-file-downloads-logs-list-table.php' );

	$logs_table = new PDD_File_Downloads_Log_Table();
	$logs_table->prepare_items();
	?>
	<div class="wrap">
		<?php do_action( 'pdd_logs_file_downloads_top' ); ?>
		<form id="pdd-logs-filter" method="get" action="<?php echo admin_url( 'edit.php?post_type=pdd_camp&page=pdd-reports&tab=logs' ); ?>">
			<?php
			$logs_table->search_box( __( 'Search', 'pdd' ), 'pdd-payments' );
			$logs_table->display();
			?>
			<input type="hidden" name="post_type" value="download" />
			<input type="hidden" name="page" value="pdd-reports" />
			<input type="hidden" name="tab" value="logs" />
		</form>
		<?php do_action( 'pdd_logs_file_downloads_bottom' ); ?>
	</div>
<?php
}
add_action( 'pdd_logs_view_file_downloads', 'pdd_logs_view_file_downloads' );

/**
 * Gateway Error Logs
 *
 * @since 1.4
 * @uses PDD_File_Downloads_Log_Table::prepare_items()
 * @uses PDD_File_Downloads_Log_Table::display()
 * @return void
 */
function pdd_logs_view_gateway_errors() {
	include( dirname( __FILE__ ) . '/class-gateway-error-logs-list-table.php' );

	$logs_table = new PDD_Gateway_Error_Log_Table();
	$logs_table->prepare_items();
	$logs_table->display();
}
add_action( 'pdd_logs_view_gateway_errors', 'pdd_logs_view_gateway_errors' );

/**
 * API Request Logs
 *
 * @since 1.5
 * @uses PDD_API_Request_Log_Table::prepare_items()
 * @uses PDD_API_Request_Log_Table::search_box()
 * @uses PDD_API_Request_Log_Table::display()
 * @return void
 */

function pdd_logs_view_api_requests() {
	include( dirname( __FILE__ ) . '/class-api-requests-logs-list-table.php' );

	$logs_table = new PDD_API_Request_Log_Table();
	$logs_table->prepare_items();
	?>
	<div class="wrap">
		<?php do_action( 'pdd_logs_api_requests_top' ); ?>
		<form id="pdd-logs-filter" method="get" action="<?php echo admin_url( 'edit.php?post_type=pdd_camp&page=pdd-reports&tab=logs' ); ?>">
			<?php
			$logs_table->search_box( __( 'Search', 'pdd' ), 'pdd-api-requests' );
			$logs_table->display();
			?>
			<input type="hidden" name="post_type" value="download" />
			<input type="hidden" name="page" value="pdd-reports" />
			<input type="hidden" name="tab" value="logs" />
		</form>
		<?php do_action( 'pdd_logs_api_requests_bottom' ); ?>
	</div>
<?php
}
add_action( 'pdd_logs_view_api_requests', 'pdd_logs_view_api_requests' );


/**
 * Default Log Views
 *
 * @since 1.4
 * @return array $views Log Views
 */
function pdd_log_default_views() {
	$views = array(
		'file_downloads'  => __( 'File Downloads', 'pdd' ),
		'sales' 		  => __( 'Sales', 'pdd' ),
		'gateway_errors'  => __( 'Payment Errors', 'pdd' ),
		'api_requests'    => __( 'API Requests', 'pdd' )
	);

	$views = apply_filters( 'pdd_log_views', $views );

	return $views;
}

/**
 * Renders the Reports page views drop down
 *
 * @since 1.3
 * @return void
*/
function pdd_log_views() {
	$views        = pdd_log_default_views();
	$current_view = isset( $_GET['view'] ) && array_key_exists( $_GET['view'], pdd_log_default_views() ) ? sanitize_text_field( $_GET['view'] ) : 'file_downloads';
	?>
	<form id="pdd-logs-filter" method="get" action="edit.php">
		<select id="pdd-logs-view" name="view">
			<option value="-1"><?php _e( 'Log Type', 'pdd' ); ?></option>
			<?php foreach ( $views as $view_id => $label ): ?>
				<option value="<?php echo esc_attr( $view_id ); ?>" <?php selected( $view_id, $current_view ); ?>><?php echo $label; ?></option>
			<?php endforeach; ?>
		</select>

		<?php do_action( 'pdd_log_view_actions' ); ?>

		<input type="hidden" name="post_type" value="download"/>
		<input type="hidden" name="page" value="pdd-reports"/>
		<input type="hidden" name="tab" value="logs"/>

		<?php submit_button( __( 'Apply', 'pdd' ), 'secondary', 'submit', false ); ?>
	</form>
	<?php
}