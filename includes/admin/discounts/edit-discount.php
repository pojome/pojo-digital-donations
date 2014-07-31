<?php
/**
 * Edit Discount Page
 *
 * @package     PDD
 * @subpackage  Admin/Discounts
 * @copyright   Copyright (c) 2014, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! isset( $_GET['discount'] ) || ! is_numeric( $_GET['discount'] ) ) {
	wp_die( __( 'Something went wrong.', 'pdd' ), __( 'Error', 'pdd' ) );
}

$discount_id       = absint( $_GET['discount'] );
$discount          = pdd_get_discount( $discount_id );
$product_reqs      = pdd_get_discount_product_reqs( $discount_id );
$excluded_products = pdd_get_discount_excluded_products( $discount_id );
$condition         = pdd_get_discount_product_condition( $discount_id );
$single_use        = pdd_discount_is_single_use( $discount_id );
$flat_display      = pdd_get_discount_type( $discount_id ) == 'percentage' ? '' : ' style="display:none;"';
$percent_display   = pdd_get_discount_type( $discount_id ) == 'percentage' ? ' style="display:none;"' : '';
$condition_display = empty( $product_reqs ) ? ' style="display:none;"' : '';
?>
<h2><?php _e( 'Edit Discount', 'pdd' ); ?> - <a href="<?php echo admin_url( 'edit.php?post_type=pdd_camp&page=pdd-discounts' ); ?>" class="button-secondary"><?php _e( 'Go Back', 'pdd' ); ?></a></h2>
<form id="pdd-edit-discount" action="" method="post">
	<?php do_action( 'pdd_edit_discount_form_top', $discount_id, $discount ); ?>
	<table class="form-table">
		<tbody>
			<tr>
				<th scope="row" valign="top">
					<label for="pdd-name"><?php _e( 'Name', 'pdd' ); ?></label>
				</th>
				<td>
					<input name="name" id="pdd-name" type="text" value="<?php echo esc_attr( stripslashes( $discount->post_title ) ); ?>" style="width: 300px;"/>
					<p class="description"><?php _e( 'The name of this discount', 'pdd' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top">
					<label for="pdd-code"><?php _e( 'Code', 'pdd' ); ?></label>
				</th>
				<td>
					<input type="text" id="pdd-code" name="code" value="<?php echo esc_attr( pdd_get_discount_code( $discount_id ) ); ?>" style="width: 300px;"/>
					<p class="description"><?php _e( 'Enter a code for this discount, such as 10PERCENT', 'pdd' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top">
					<label for="pdd-type"><?php _e( 'Type', 'pdd' ); ?></label>
				</th>
				<td>
					<select name="type" id="pdd-type">
						<option value="percent" <?php selected( pdd_get_discount_type( $discount_id ), 'percent' ); ?>><?php _e( 'Percentage', 'pdd' ); ?></option>
						<option value="flat"<?php selected( pdd_get_discount_type( $discount_id ), 'flat' ); ?>><?php _e( 'Flat amount', 'pdd' ); ?></option>
					</select>
					<p class="description"><?php _e( 'The kind of discount to apply for this discount.', 'pdd' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top">
					<label for="pdd-amount"><?php _e( 'Amount', 'pdd' ); ?></label>
				</th>
				<td>
					<input type="text" id="pdd-amount" name="amount" value="<?php echo esc_attr( pdd_get_discount_amount( $discount_id ) ); ?>" style="width: 40px;"/>
					<p class="description pdd-amount-description"<?php echo $flat_display; ?>><?php printf( __( 'Enter the discount amount in %s', 'pdd' ), pdd_get_currency() ); ?></p>
					<p class="description pdd-amount-description"<?php echo $percent_display; ?>><?php _e( 'Enter the discount percentage. 10 = 10%', 'pdd' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top">
					<label for="pdd-products"><?php printf( __( '%s Requirements', 'pdd' ), pdd_get_label_singular() ); ?></label>
				</th>
				<td>
					<p>
						<?php echo PDD()->html->product_dropdown( array(
							'name'        => 'products[]',
							'id'          => 'products',
							'selected'    => $product_reqs,
							'multiple'    => true,
							'chosen'      => true 
						) ); ?><br/>
					</p>
					<div id="pdd-discount-product-conditions"<?php echo $condition_display; ?>>
						<p>
							<select id="pdd-product-condition" name="product_condition">
								<option value="all"<?php selected( 'all', $condition ); ?>><?php printf( __( 'Cart must contain all selected %s', 'pdd' ), pdd_get_label_plural() ); ?></option>
								<option value="any"<?php selected( 'any', $condition ); ?>><?php printf( __( 'Cart needs one or more of the selected %s', 'pdd' ), pdd_get_label_plural() ); ?></option>
							</select>
						</p>
						<p>
							<label>
								<input type="radio" class="tog" name="not_global" value="0"<?php checked( false, pdd_is_discount_not_global( $discount_id ) ); ?>/>
								<?php _e( 'Apply discount to entire purchase.', 'pdd' ); ?>
							</label><br/>
							<label>
								<input type="radio" class="tog" name="not_global" value="1"<?php checked( true, pdd_is_discount_not_global( $discount_id ) ); ?>/>
								<?php printf( __( 'Apply discount only to selected %s.', 'pdd' ), pdd_get_label_plural() ); ?>
							</label>
						</p>
					</div>
					<p class="description"><?php printf( __( 'Select %s relevant to this discount.', 'pdd' ), pdd_get_label_plural() ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top">
					<label for="pdd-excluded-products"><?php printf( __( 'Excluded %s', 'pdd' ), pdd_get_label_plural() ); ?></label>
				</th>
				<td>
					<?php echo PDD()->html->product_dropdown( array(
						'name'     => 'excluded-products[]',
						'id'       => 'excluded-products',
						'selected' => $excluded_products,
						'multiple' => true,
						'chosen'   => true 
					) ); ?><br/>
					<p class="description"><?php printf( __( '%s that this discount code cannot be applied to.', 'pdd' ), pdd_get_label_plural() ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top">
					<label for="pdd-start"><?php _e( 'Start date', 'pdd' ); ?></label>
				</th>
				<td>
					<input name="start" id="pdd-start" type="text" value="<?php echo esc_attr( pdd_get_discount_start_date( $discount_id ) ); ?>" style="width: 300px;" class="pdd_datepicker"/>
					<p class="description"><?php _e( 'Enter the start date for this discount code in the format of mm/dd/yyyy. For no start date, leave blank. If entered, the discount can only be used after or on this date.', 'pdd' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top">
					<label for="pdd-expiration"><?php _e( 'Expiration date', 'pdd' ); ?></label>
				</th>
				<td>
					<input name="expiration" id="pdd-expiration" type="text" value="<?php echo esc_attr( pdd_get_discount_expiration( $discount_id ) ); ?>" style="width: 300px;" class="pdd_datepicker"/>
					<p class="description"><?php _e( 'Enter the expiration date for this discount code in the format of mm/dd/yyyy. For no expiration, leave blank', 'pdd' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top">
					<label for="pdd-max-uses"><?php _e( 'Max Uses', 'pdd' ); ?></label>
				</th>
				<td>
					<input type="text" id="pdd-max-uses" name="max" value="<?php echo esc_attr( pdd_get_discount_max_uses( $discount_id ) ); ?>" style="width: 40px;"/>
					<p class="description"><?php _e( 'The maximum number of times this discount can be used. Leave blank for unlimited.', 'pdd' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top">
					<label for="pdd-min-cart-amount"><?php _e( 'Minimum Amount', 'pdd' ); ?></label>
				</th>
				<td>
					<input type="text" id="pdd-min-cart-amount" name="min_price" value="<?php echo esc_attr( pdd_get_discount_min_price( $discount_id ) ); ?>" style="width: 40px;"/>
					<p class="description"><?php _e( 'The minimum amount that must be purchased before this discount can be used. Leave blank for no minimum.', 'pdd' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top">
					<label for="pdd-status"><?php _e( 'Status', 'pdd' ); ?></label>
				</th>
				<td>
					<select name="status" id="pdd-status">
						<option value="active" <?php selected( $discount->post_status, 'active' ); ?>><?php _e( 'Active', 'pdd' ); ?></option>
						<option value="inactive"<?php selected( $discount->post_status, 'inactive' ); ?>><?php _e( 'Inactive', 'pdd' ); ?></option>
					</select>
					<p class="description"><?php _e( 'The status of this discount code.', 'pdd' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top">
					<label for="pdd-use-once"><?php _e( 'Use Once Per Customer', 'pdd' ); ?></label>
				</th>
				<td>
					<input type="checkbox" id="pdd-use-once" name="use_once" value="1"<?php checked( true, $single_use ); ?>/>
					<span class="description"><?php _e( 'Limit this discount to a single-use per customer?', 'pdd' ); ?></span>
				</td>
			</tr>
		</tbody>
	</table>
	<?php do_action( 'pdd_edit_discount_form_bottom', $discount_id, $discount ); ?>
	<p class="submit">
		<input type="hidden" name="pdd-action" value="edit_discount"/>
		<input type="hidden" name="discount-id" value="<?php echo absint( $_GET['discount'] ); ?>"/>
		<input type="hidden" name="pdd-redirect" value="<?php echo esc_url( admin_url( 'edit.php?post_type=pdd_camp&page=pdd-discounts' ) ); ?>"/>
		<input type="hidden" name="pdd-discount-nonce" value="<?php echo wp_create_nonce( 'pdd_discount_nonce' ); ?>"/>
		<input type="submit" value="<?php _e( 'Update Discount Code', 'pdd' ); ?>" class="button-primary"/>
	</p>
</form>
