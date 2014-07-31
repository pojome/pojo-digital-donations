<?php
/**
 * Admin Payment History
 *
 * @package     PDD
 * @subpackage  Admin/Payments
 * @copyright   Copyright (c) 2014, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Payment History Page
 *
 * Renders the payment history page contents.
 *
 * @access      private
 * @since       1.0
 * @return      void
*/
function pdd_payment_history_page() {
	global $pdd_options;

	$pdd_payment = get_post_type_object( 'pdd_payment' );

	if ( isset( $_GET['view'] ) && 'view-order-details' == $_GET['view'] ) {
		require_once PDD_PLUGIN_DIR . 'includes/admin/payments/view-order-details.php';
	} else {
		require_once PDD_PLUGIN_DIR . 'includes/admin/payments/class-payments-table.php';
		$payments_table = new PDD_Payment_History_Table();
		$payments_table->prepare_items();
	?>
	<div class="wrap">
		<h2><?php echo $pdd_payment->labels->menu_name ?></h2>
		<?php do_action( 'pdd_payments_page_top' ); ?>
		<form id="pdd-payments-filter" method="get" action="<?php echo admin_url( 'edit.php?post_type=pdd_camp&page=pdd-payment-history' ); ?>">
			<input type="hidden" name="post_type" value="download" />
			<input type="hidden" name="page" value="pdd-payment-history" />

			<?php $payments_table->views() ?>

			<?php $payments_table->advanced_filters(); ?>
			
			<?php $payments_table->display() ?>
		</form>
		<?php do_action( 'pdd_payments_page_bottom' ); ?>
	</div>
<?php
	}
}

/**
 * Renders the mobile link at the bottom of the payment history page
 *
 * @since 1.8.4
 * @return void
*/
function pdd_payment_history_mobile_link() { 
	?>
	<p class="pdd-mobile-link">
		<a href="https://easydigitaldownloads.com/extension/ios-sales-earnings-tracker/" target="_blank">
			<img src="<?php echo PDD_PLUGIN_URL . 'assets/images/icons/iphone.png'; ?>"/>
			<?php _e( 'Get the PDD Sales / Earnings tracker for iOS', 'pdd' ); ?>
		</a>
	</p>
	<?php 
}
add_action( 'pdd_payments_page_bottom', 'pdd_payment_history_mobile_link' );

/**
 * Payment History admin titles
 *
 * @since 1.6
 *
 * @param $admin_title
 * @param $title
 * @return string
 */
function pdd_view_order_details_title( $admin_title, $title ) {
	if ( 'download_page_pdd-payment-history' != get_current_screen()->base )
		return $admin_title;

	if( ! isset( $_GET['pdd-action'] ) )
		return $admin_title;

	switch( $_GET['pdd-action'] ) :

		case 'view-order-details' :
			$title = __( 'View Order Details', 'pdd' ) . ' - ' . $admin_title;
			break;
		case 'edit-payment' :
			$title = __( 'Edit Payment', 'pdd' ) . ' - ' . $admin_title;
			break;
		default:
			$title = $admin_title;
			break;
	endswitch;

	return $title;
}
add_filter( 'admin_title', 'pdd_view_order_details_title', 10, 2 );

/**
 * Intercept default Edit post links for PDD payments and rewrite them to the View Order Details screen
 *
 * @since 1.8.3
 *
 * @param $url
 * @param $post_id
 * @param $context
 * @return string
 */
function pdd_override_edit_post_for_payment_link( $url, $post_id = 0, $context ) {

	$post = get_post( $post_id );
	if( ! $post )
		return $url;

	if( 'pdd_payment' != $post->post_type )
		return $url;

	$url = admin_url( 'edit.php?post_type=pdd_camp&page=pdd-payment-history&view=view-order-details&id=' . $post_id );

	return $url;
}
add_filter( 'get_edit_post_link', 'pdd_override_edit_post_for_payment_link', 10, 3 );
