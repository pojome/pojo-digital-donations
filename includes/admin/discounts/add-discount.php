<?php
/**
 * Add Discount Page
 *
 * @package     PDD
 * @subpackage  Admin/Discounts
 * @copyright   Copyright (c) 2014, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;
$downloads = get_posts( array( 'post_type' => 'download', 'nopaging' => true ) );
?>
<h2><?php _e( 'Add New Discount', 'pdd' ); ?> - <a href="<?php echo admin_url( 'edit.php?post_type=download&page=pdd-discounts' ); ?>" class="button-secondary"><?php _e( 'Go Back', 'pdd' ); ?></a></h2>
<form id="pdd-add-discount" action="" method="POST">
	<?php do_action( 'pdd_add_discount_form_top' ); ?>
	<table class="form-table">
		<tbody>
			<tr>
				<th scope="row" valign="top">
					<label for="pdd-name"><?php _e( 'Name', 'pdd' ); ?></label>
				</th>
				<td>
					<input name="name" id="pdd-name" type="text" value="" style="width: 300px;"/>
					<p class="description"><?php _e( 'The name of this discount', 'pdd' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top">
					<label for="pdd-code"><?php _e( 'Code', 'pdd' ); ?></label>
				</th>
				<td>
					<input type="text" id="pdd-code" name="code" value="" style="width: 300px;"/>
					<p class="description"><?php _e( 'Enter a code for this discount, such as 10PERCENT', 'pdd' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top">
					<label for="pdd-type"><?php _e( 'Type', 'pdd' ); ?></label>
				</th>
				<td>
					<select name="type" id="pdd-type">
						<option value="percent"><?php _e( 'Percentage', 'pdd' ); ?></option>
						<option value="flat"><?php _e( 'Flat amount', 'pdd' ); ?></option>
					</select>
					<p class="description"><?php _e( 'The kind of discount to apply for this discount.', 'pdd' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top">
					<label for="pdd-amount"><?php _e( 'Amount', 'pdd' ); ?></label>
				</th>
				<td>
					<input type="text" id="pdd-amount" name="amount" value="" style="width: 40px;"/>
					<p class="description pdd-amount-description" style="display:none;"><?php printf( __( 'Enter the discount amount in %s', 'pdd' ), pdd_get_currency() ); ?></p>
					<p class="description pdd-amount-description"><?php _e( 'Enter the discount percentage. 10 = 10%', 'pdd' ); ?></p>
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
							'multiple'    => true,
							'chosen'      => true 
						) ); ?><br/>
					</p>
					<div id="pdd-discount-product-conditions" style="display:none;">
						<p>
							<select id="pdd-product-condition" name="product_condition">
								<option value="all"><?php printf( __( 'Cart must contain all selected %s', 'pdd' ), pdd_get_label_plural() ); ?></option>
								<option value="any"><?php printf( __( 'Cart needs one or more of the selected %s', 'pdd' ), pdd_get_label_plural() ); ?></option>
							</select>
						</p>
						<p>
							<label>
								<input type="radio" class="tog" name="not_global" value="0" checked="checked"/>
								<?php _e( 'Apply discount to entire purchase.', 'pdd' ); ?>
							</label><br/>
							<label>
								<input type="radio" class="tog" name="not_global" value="1"/>
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
						'selected' => array(),
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
					<input name="start" id="pdd-start" type="text" value="" style="width: 300px;" class="pdd_datepicker"/>
					<p class="description"><?php _e( 'Enter the start date for this discount code in the format of mm/dd/yyyy. For no start date, leave blank. If entered, the discount can only be used after or on this date.', 'pdd' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top">
					<label for="pdd-expiration"><?php _e( 'Expiration date', 'pdd' ); ?></label>
				</th>
				<td>
					<input name="expiration" id="pdd-expiration" type="text" style="width: 300px;" class="pdd_datepicker"/>
					<p class="description"><?php _e( 'Enter the expiration date for this discount code in the format of mm/dd/yyyy. For no expiration, leave blank', 'pdd' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top">
					<label for="pdd-min-cart-amount"><?php _e( 'Minimum Amount', 'pdd' ); ?></label>
				</th>
				<td>
					<input type="text" id="pdd-min-cart-amount" name="min_price" value="" style="width: 40px;"/>
					<p class="description"><?php _e( 'The minimum amount that must be purchased before this discount can be used. Leave blank for no minimum.', 'pdd' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top">
					<label for="pdd-max-uses"><?php _e( 'Max Uses', 'pdd' ); ?></label>
				</th>
				<td>
					<input type="text" id="pdd-max-uses" name="max" value="" style="width: 40px;"/>
					<p class="description"><?php _e( 'The maximum number of times this discount can be used. Leave blank for unlimited.', 'pdd' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top">
					<label for="pdd-use-once"><?php _e( 'Use Once Per Customer', 'pdd' ); ?></label>
				</th>
				<td>
					<input type="checkbox" id="pdd-use-once" name="use_once" value="1"/>
					<span class="description"><?php _e( 'Limit this discount to a single-use per customer?', 'pdd' ); ?></span>
				</td>
			</tr>
		</tbody>
	</table>
	<?php do_action( 'pdd_add_discount_form_bottom' ); ?>
	<p class="submit">
		<input type="hidden" name="pdd-action" value="add_discount"/>
		<input type="hidden" name="pdd-redirect" value="<?php echo esc_url( admin_url( 'edit.php?post_type=download&page=pdd-discounts' ) ); ?>"/>
		<input type="hidden" name="pdd-discount-nonce" value="<?php echo wp_create_nonce( 'pdd_discount_nonce' ); ?>"/>
		<input type="submit" value="<?php _e( 'Add Discount Code', 'pdd' ); ?>" class="button-primary"/>
	</p>
</form>
