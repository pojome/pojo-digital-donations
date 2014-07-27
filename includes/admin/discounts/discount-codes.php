<?php
/**
 * Discount Codes
 *
 * @package     PDD
 * @subpackage  Admin/Discounts
 * @copyright   Copyright (c) 2014, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Renders the Discount Pages Admin Page
 *
 * @since 1.4
 * @author Sunny Ratilal
 * @return void
*/
function pdd_discounts_page() {
	global $pdd_options;

	if ( isset( $_GET['pdd-action'] ) && $_GET['pdd-action'] == 'edit_discount' ) {
		require_once PDD_PLUGIN_DIR . 'includes/admin/discounts/edit-discount.php';
	} elseif ( isset( $_GET['pdd-action'] ) && $_GET['pdd-action'] == 'add_discount' ) {
		require_once PDD_PLUGIN_DIR . 'includes/admin/discounts/add-discount.php';
	} else {
		require_once PDD_PLUGIN_DIR . 'includes/admin/discounts/class-discount-codes-table.php';
		$discount_codes_table = new PDD_Discount_Codes_Table();
		$discount_codes_table->prepare_items();
	?>
	<div class="wrap">
		<h2><?php _e( 'Discount Codes', 'pdd' ); ?><a href="<?php echo add_query_arg( array( 'pdd-action' => 'add_discount' ) ); ?>" class="add-new-h2"><?php _e( 'Add New', 'pdd' ); ?></a></h2>
		<?php do_action( 'pdd_discounts_page_top' ); ?>
		<form id="pdd-discounts-filter" method="get" action="<?php echo admin_url( 'edit.php?post_type=download&page=pdd-discounts' ); ?>">
			<?php $discount_codes_table->search_box( __( 'Search', 'pdd' ), 'pdd-discounts' ); ?>

			<input type="hidden" name="post_type" value="download" />
			<input type="hidden" name="page" value="pdd-discounts" />

			<?php $discount_codes_table->views() ?>
			<?php $discount_codes_table->display() ?>
		</form>
		<?php do_action( 'pdd_discounts_page_bottom' ); ?>
	</div>
<?php
	}
}
