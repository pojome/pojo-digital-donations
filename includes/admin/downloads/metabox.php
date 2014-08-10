<?php
/**
 * Metabox Functions
 *
 * @package     PDD
 * @subpackage  Admin/Downloads
 * @copyright   Copyright (c) 2014, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/** All Downloads *****************************************************************/

/**
 * Register all the meta boxes for the Download custom post type
 *
 * @since 1.0
 * @return void
 */
function pdd_add_download_meta_box() {
	$post_types = apply_filters( 'pdd_camp_metabox_post_types' , array( 'pdd_camp' ) );

	foreach ( $post_types as $post_type ) {

		/** Product Prices **/
		add_meta_box( 'pdd_product_prices', sprintf( __( '%1$s Prices', 'pdd' ), pdd_get_label_singular(), pdd_get_label_plural() ),  'pdd_render_download_meta_box', $post_type, 'normal', 'high' );

		/** Product Settings **/
		add_meta_box( 'pdd_product_settings', sprintf( __( '%1$s Settings', 'pdd' ), pdd_get_label_singular(), pdd_get_label_plural() ),  'pdd_render_settings_meta_box', $post_type, 'side', 'default' );

		/** Product Notes */
		add_meta_box( 'pdd_product_notes', sprintf( __( '%1$s Notes', 'pdd' ), pdd_get_label_singular(), pdd_get_label_plural() ), 'pdd_render_product_notes_meta_box', $post_type, 'normal', 'high' );

		if ( current_user_can( 'view_product_stats', get_the_ID() ) ) {
			/** Product Stats */
			add_meta_box( 'pdd_product_stats', sprintf( __( '%1$s Stats', 'pdd' ), pdd_get_label_singular(), pdd_get_label_plural() ), 'pdd_render_stats_meta_box', $post_type, 'side', 'high' );
		}
	}
}
add_action( 'add_meta_boxes', 'pdd_add_download_meta_box' );

/**
 * Returns default PDD Download meta fields.
 *
 * @since 1.9.5
 * @return array $fields Array of fields.
 */
function pdd_camp_metabox_fields() {

	$fields = array(
			'_pdd_product_type',
			'pdd_price',
			'_variable_pricing',
			'_pdd_price_options_mode',
			'pdd_variable_prices',
			'_custom_amount',
			'pdd_min_custom_amount',
			'pdd_camp_files',
			'_pdd_purchase_text',
			'_pdd_purchase_style',
			'_pdd_purchase_color',
			'_pdd_bundled_products',
			'_pdd_hide_donate_link',
			'_pdd_camp_tax_exclusive',
			'_pdd_button_behavior',
			'pdd_product_notes'
		);

	if ( pdd_use_skus() ) {
		$fields[] = 'pdd_sku';
	}

	return apply_filters( 'pdd_metabox_fields_save', $fields );
}

/**
 * Save post meta when the save_post action is called
 *
 * @since 1.0
 * @param int $post_id Download (Post) ID
 * @global array $post All the data of the the current post
 * @return void
 */
function pdd_camp_meta_box_save( $post_id, $post ) {

	if ( ! isset( $_POST['pdd_camp_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['pdd_camp_meta_box_nonce'], basename( __FILE__ ) ) ) {
		return;
	}

	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ( defined( 'DOING_AJAX') && DOING_AJAX ) || isset( $_REQUEST['bulk_edit'] ) ) {
		return;
	}

	if ( isset( $post->post_type ) && 'revision' == $post->post_type ) {
		return;
	}

	if ( ! current_user_can( 'edit_product', $post_id ) ) {
		return;
	}

	// The default fields that get saved
	$fields = pdd_camp_metabox_fields();

	foreach ( $fields as $field ) {
		// Accept blank or "0"
		if ( ! empty( $_POST[ $field ] ) ) {
			$new = apply_filters( 'pdd_metabox_save_' . $field, $_POST[ $field ] );
			update_post_meta( $post_id, $field, $new );
		} else {
			delete_post_meta( $post_id, $field );
		}
	}

	if ( pdd_has_variable_prices( $post_id ) ) {
		$lowest = pdd_get_lowest_price_option( $post_id );
		update_post_meta( $post_id, 'pdd_price', $lowest );
	}

	do_action( 'pdd_save_download', $post_id, $post );
}

add_action( 'save_post', 'pdd_camp_meta_box_save', 10, 2 );

/**
 * Sanitize the price before it is saved
 *
 * This is mostly for ensuring commas aren't saved in the price
 *
 * @since 1.3.2
 * @param string $price Price before sanitization
 * @return float $price Sanitized price
 */
function pdd_sanitize_price_save( $price ) {
	return pdd_sanitize_amount( $price );
}
add_filter( 'pdd_metabox_save_pdd_price', 'pdd_sanitize_price_save' );

/**
 * Sanitize the variable prices
 *
 * Ensures prices are correctly mapped to an array starting with an index of 0
 *
 * @since 1.4.2
 * @param array $prices Variable prices
 * @return array $prices Array of the remapped variable prices
 */
function pdd_sanitize_variable_prices_save( $prices ) {

	global $post;

	foreach( $prices as $id => $price ) {

		if( empty( $price['amount'] ) ) {

			$price['amount'] = 0;

		}

		$prices[ $id ]['amount'] = pdd_sanitize_amount( $price['amount'] );

	}

	// Make sure all prices are rekeyed starting at 0
	return array_values( $prices );
}
add_filter( 'pdd_metabox_save_pdd_variable_prices', 'pdd_sanitize_variable_prices_save' );

/**
 * Sanitize bundled products on save
 *
 * Ensures a user doesn't try and include a product's ID in the products bundled with that product
 *
 * @since       1.6
 *
 * @param array $products
 * @return array
 */
function pdd_sanitize_bundled_products_save( $products = array() ) {

	global $post;

	$self = array_search( $post->ID, $products );

	if( $self !== false )
		unset( $products[ $self ] );

	return array_values( array_unique( $products ) );
}
add_filter( 'pdd_metabox_save__pdd_bundled_products', 'pdd_sanitize_bundled_products_save' );


/**
 * Sanitize the file downloads
 *
 * Ensures files are correctly mapped to an array starting with an index of 0
 *
 * @since 1.5.1
 * @param array $files Array of all the file downloads
 * @return array $files Array of the remapped file downloads
 */
function pdd_sanitize_files_save( $files ) {

	// Clean up filenames to ensure whitespaces are stripped
	foreach( $files as $id => $file ) {

		if( ! empty( $files[ $id ][ 'file' ] ) ) {
			$files[ $id ][ 'file' ] = trim( $file[ 'file' ] );
		}

		if( ! empty( $files[ $id ][ 'name' ] ) ) {
			$files[ $id ][ 'name' ] = trim( $file[ 'name' ] );
		}
	}

	// Make sure all files are rekeyed starting at 0
	return array_values( $files );
}
add_filter( 'pdd_metabox_save_pdd_camp_files', 'pdd_sanitize_files_save' );

/**
 * Don't save blank rows.
 *
 * When saving, check the price and file table for blank rows.
 * If the name of the price or file is empty, that row should not
 * be saved.
 *
 * @since 1.2.2
 * @param array $new Array of all the meta values
 * @return array $new New meta value with empty keys removed
 */
function pdd_metabox_save_check_blank_rows( $new ) {
	foreach ( $new as $key => $value ) {
		if ( empty( $value['name'] ) && empty( $value['amount'] ) && empty( $value['file'] ) )
			unset( $new[ $key ] );
	}

	return $new;
}
add_filter( 'pdd_metabox_save_pdd_variable_prices', 'pdd_metabox_save_check_blank_rows' );
add_filter( 'pdd_metabox_save_pdd_camp_files', 'pdd_metabox_save_check_blank_rows' );


/** Download Configuration *****************************************************************/

/**
 * Download Metabox
 *
 * Extensions (as well as the core plugin) can add items to the main download
 * configuration metabox via the `pdd_meta_box_fields` action.
 *
 * @since 1.0
 * @return void
 */
function pdd_render_download_meta_box() {
	global $post, $pdd_options;

	/*
	 * Output the price fields
	 * @since 1.9
	 */
	do_action( 'pdd_meta_box_price_fields', $post->ID );

	/*
	 * Output the price fields
	 *
	 * Left for backwards compatibility
	 *
	 */
	do_action( 'pdd_meta_box_fields', $post->ID );

	wp_nonce_field( basename( __FILE__ ), 'pdd_camp_meta_box_nonce' );
}

/**
 * Download Files Metabox
 *
 * @since 1.9
 * @return void
 */
function pdd_render_files_meta_box() {
	global $post, $pdd_options;

	/*
	 * Output the files fields
	 * @since 1.9
	 */
	//do_action( 'pdd_meta_box_files_fields', $post->ID );
}

/**
 * Download Settings Metabox
 *
 * @since 1.9
 * @return void
 */
function pdd_render_settings_meta_box() {
	global $post, $pdd_options;

	/*
	 * Output the files fields
	 * @since 1.9
	 */
	do_action( 'pdd_meta_box_settings_fields', $post->ID );
}

/**
 * Price Section
 *
 * If variable pricing is not enabled, simply output a single input box.
 *
 * If variable pricing is enabled, outputs a table of all current prices.
 * Extensions can add column heads to the table via the `pdd_camp_file_table_head`
 * hook, and actual columns via `pdd_camp_file_table_row`
 *
 * @since 1.0
 *
 * @see pdd_render_price_row()
 *
 * @param $post_id
 */
function pdd_render_price_field( $post_id ) {
	global $pdd_options;

	$price              = pdd_get_download_price( $post_id );
	$variable_pricing   = pdd_has_variable_prices( $post_id );
	$custom_amount      = pdd_has_custom_amount( $post_id );
	$prices             = pdd_get_variable_prices( $post_id );
	$single_option_mode = pdd_single_price_option_mode( $post_id );

	$price_display         = $variable_pricing ? ' style="display:none;"' : '';
	$variable_display      = $variable_pricing ? '' : ' style="display:none;"';
	$custom_amount_display = $custom_amount ? '' : ' style="display:none;"';
?>
	<p>
		<strong><?php echo apply_filters( 'pdd_price_options_heading', __( 'Pricing Options:', 'pdd' ) ); ?></strong>
	</p>

	<p>
		<label for="pdd_variable_pricing">
			<input type="checkbox" name="_variable_pricing" id="pdd_variable_pricing" value="1" <?php checked( 1, $variable_pricing ); ?> />
			<?php echo apply_filters( 'pdd_variable_pricing_toggle_text', __( 'Enable variable pricing', 'pdd' ) ); ?>
		</label>
	</p>

	<div id="pdd_regular_price_field" class="pdd_pricing_fields" <?php echo $price_display; ?>>
		<?php
			$price_args = array(
				'name'  => 'pdd_price',
				'value' => isset( $price ) ? esc_attr( pdd_format_amount( $price ) ) : '',
				'class' => 'pdd-price-field'
			);
		?>

		<?php if ( ! isset( $pdd_options['currency_position'] ) || $pdd_options['currency_position'] == 'before' ) : ?>
			<?php echo pdd_currency_filter( '' ); ?>
			<?php echo PDD()->html->text( $price_args ); ?>
		<?php else : ?>
			<?php echo PDD()->html->text( $price_args ); ?>
			<?php echo pdd_currency_filter( '' ); ?>
		<?php endif; ?>

		<?php do_action( 'pdd_price_field', $post_id ); ?>
	</div>
	
	<?php do_action( 'pdd_after_price_field', $post_id ); ?>

	<p>
		<strong><?php echo apply_filters( 'pdd_custom_amount_heading', __( 'Custom Amount:', 'pdd' ) ); ?></strong>
	</p>

	<p>
		<label for="pdd_custom_amount">
			<input type="checkbox" name="_custom_amount" id="pdd_custom_amount" value="1" <?php checked( 1, $custom_amount ); ?> />
			<?php echo apply_filters( 'pdd_custom_amount_toggle_text', __( 'Enable custom amount', 'pdd' ) ); ?>
		</label>
	</p>

	<div id="pdd_custom_price_fields" class="pdd_custom_amount_fields" <?php echo $custom_amount_display; ?>>
		<?php echo _x( 'Min:', 'custom-amount', 'pdd' ); ?>
		<?php
		$price_args = array(
			'name'  => 'pdd_min_custom_amount',
			'value' => get_post_meta( $post_id, 'pdd_min_custom_amount', true ),
			'placeholder' => '9.99',
			'class' => 'pdd-price-field'
		);
		?>
		<?php if( ! isset( $pdd_options['currency_position'] ) || $pdd_options['currency_position'] == 'before' ) : ?>
			<span><?php echo pdd_currency_filter( '' ); ?></span>
			<?php echo PDD()->html->text( $price_args ); ?>
		<?php else : ?>
			<?php echo PDD()->html->text( $price_args ); ?>
			<?php echo pdd_currency_filter( '' ); ?>
		<?php endif; ?>
		<?php echo _x( 'Enter empty or 0 for no min amount', 'custom-amount', 'pdd' ); ?>
	</div>

	<div id="pdd_variable_price_fields" class="pdd_pricing_fields" <?php echo $variable_display; ?>>
		<input type="hidden" id="pdd_variable_prices" class="pdd_variable_prices_name_field" value=""/>
		<p>
			<?php echo PDD()->html->checkbox( array( 'name' => '_pdd_price_options_mode', 'current' => $single_option_mode ) ); ?>
			<label for="_pdd_price_options_mode"><?php echo apply_filters( 'pdd_multi_option_purchase_text', __( 'Enable multi-option purchase mode. Allows multiple price options to be added to your cart at once', 'pdd' ) ); ?></label>
		</p>
		<div id="pdd_price_fields" class="pdd_meta_table_wrap">
			<table class="widefat pdd_repeatable_table" width="100%" cellpadding="0" cellspacing="0">
				<thead>
					<tr>
						<!--drag handle column. Disabled until we can work out a way to solve the issues raised here: https://github.com/easydigitaldownloads/Easy-Digital-Downloads/issues/1066
						<th style="width: 20px"></th>
						-->
						<th><?php _e( 'Option Name', 'pdd' ); ?></th>
						<th style="width: 100px"><?php _e( 'Price', 'pdd' ); ?></th>
						<?php do_action( 'pdd_camp_price_table_head', $post_id ); ?>
						<th style="width: 2%"></th>
					</tr>
				</thead>
				<tbody>
					<?php
						if ( ! empty( $prices ) ) :
							foreach ( $prices as $key => $value ) :
								$name   = isset( $value['name'] ) ? $value['name'] : '';
								$amount = isset( $value['amount'] ) ? $value['amount'] : '';

								$args = apply_filters( 'pdd_price_row_args', compact( 'name', 'amount' ), $value );
					?>
						<tr class="pdd_variable_prices_wrapper pdd_repeatable_row">
							<?php do_action( 'pdd_render_price_row', $key, $args, $post_id ); ?>
						</tr>
					<?php
							endforeach;
						else :
					?>
						<tr class="pdd_variable_prices_wrapper pdd_repeatable_row">
							<?php do_action( 'pdd_render_price_row', 0, array(), $post_id ); ?>
						</tr>
					<?php endif; ?>

					<tr>
						<td class="submit" colspan="4" style="float: none; clear:both; background:#fff;">
							<a class="button-secondary pdd_add_repeatable" style="margin: 6px 0;"><?php _e( 'Add New Price', 'pdd' ); ?></a>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div><!--end #pdd_variable_price_fields-->
<?php
}
add_action( 'pdd_meta_box_price_fields', 'pdd_render_price_field', 10 );

/**
 * Individual Price Row
 *
 * Used to output a table row for each price associated with a download.
 * Can be called directly, or attached to an action.
 *
 * @since 1.2.2
 *
 * @param       $key
 * @param array $args
 * @param       $post_id
 */
function pdd_render_price_row( $key, $args = array(), $post_id ) {
	global $pdd_options;

	$defaults = array(
		'name'   => null,
		'amount' => null
	);

	$args = wp_parse_args( $args, $defaults );

?>
	<!--
	Disabled until we can work out a way to solve the issues raised here: https://github.com/easydigitaldownloads/Easy-Digital-Downloads/issues/1066
	<td>
		<span class="pdd_draghandle"></span>
	</td>
	-->

	<td>
		<?php echo PDD()->html->text( array(
			'name'  => 'pdd_variable_prices[' . $key . '][name]',
			'value' => esc_attr( $args['name'] ),
			'placeholder' => __( 'Option Name', 'pdd' ),
			'class' => 'pdd_variable_prices_name large-text'
		) ); ?>
	</td>

	<td>
		<?php
			$price_args = array(
				'name'  => 'pdd_variable_prices[' . $key . '][amount]',
				'value' => $args['amount'],
				'placeholder' => '9.99',
				'class' => 'pdd-price-field'
			);
		?>

		<?php if( ! isset( $pdd_options['currency_position'] ) || $pdd_options['currency_position'] == 'before' ) : ?>
			<span><?php echo pdd_currency_filter( '' ); ?></span>
			<?php echo PDD()->html->text( $price_args ); ?>
		<?php else : ?>
			<?php echo PDD()->html->text( $price_args ); ?>
			<?php echo pdd_currency_filter( '' ); ?>
		<?php endif; ?>
	</td>

	<?php do_action( 'pdd_camp_price_table_row', $post_id, $key, $args ); ?>

	<td>
		<a href="#" class="pdd_remove_repeatable" data-type="price" style="background: url(<?php echo admin_url('/images/xit.gif'); ?>) no-repeat;">&times;</a>
	</td>
<?php
}
add_action( 'pdd_render_price_row', 'pdd_render_price_row', 10, 3 );

/**
 * Product type options
 *
 * @access      private
 * @since       1.6
 * @return      void
 */
function pdd_render_product_type_field( $post_id = 0 ) {

	$types = pdd_get_download_types();
	$type  = pdd_get_download_type( $post_id );
?>
	<p>
		<strong><?php echo apply_filters( 'pdd_product_type_options_heading', __( 'Product Type Options:', 'pdd' ) ); ?></strong>
	</p>
	<p>
		<?php echo PDD()->html->select( array(
			'options'          => $types,
			'name'             => '_pdd_product_type',
			'id'               => '_pdd_product_type',
			'selected'         => $type,
			'show_option_all'  => false,
			'show_option_none' => false
		) ); ?>
		<label for="pdd_product_type"><?php _e( 'Select a product type', 'pdd' ); ?></label>
	</p>
<?php
}
add_action( 'pdd_meta_box_files_fields', 'pdd_render_product_type_field', 10 );

/**
 * Renders product field
 * @since 1.6
 *
 * @param $post_id
 */
function pdd_render_products_field( $post_id ) {
	$type     = pdd_get_download_type( $post_id );
	$display  = $type == 'bundle' ? '' : ' style="display:none;"';
	$products = pdd_get_bundled_products( $post_id );
?>
	<div id="pdd_products"<?php echo $display; ?>>
		<div id="pdd_file_fields" class="pdd_meta_table_wrap">
			<table class="widefat" width="100%" cellpadding="0" cellspacing="0">
				<thead>
					<tr>
						<th><?php printf( __( 'Bundled %s:', 'pdd' ), pdd_get_label_plural() ); ?></th>
						<?php do_action( 'pdd_camp_products_table_head', $post_id ); ?>
					</tr>
				</thead>
				<tbody>
					<tr class="pdd_repeatable_product_wrapper">
						<td>
							<?php
							echo PDD()->html->product_dropdown( array(
								'name'     => '_pdd_bundled_products[]',
								'id'       => 'pdd_bundled_products',
								'selected' => $products,
								'multiple' => true,
								'chosen'   => true
							) );
							?>
						</td>
						<?php do_action( 'pdd_camp_products_table_row', $post_id ); ?>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
<?php
}
add_action( 'pdd_meta_box_files_fields', 'pdd_render_products_field', 10 );

/**
 * File Downloads section.
 *
 * Outputs a table of all current files. Extensions can add column heads to the table
 * via the `pdd_camp_file_table_head` hook, and actual columns via
 * `pdd_camp_file_table_row`
 *
 * @since 1.0
 * @see pdd_render_file_row()
 * @param int $post_id Download (Post) ID
 * @return void
 */
function pdd_render_files_field( $post_id = 0 ) {
	$type             = pdd_get_download_type( $post_id );
	$files            = pdd_get_download_files( $post_id );
	$variable_pricing = pdd_has_variable_prices( $post_id );
	$display          = $type == 'bundle' ? ' style="display:none;"' : '';
	$variable_display = $variable_pricing ? '' : 'display:none;';
?>
	<div id="pdd_camp_files"<?php echo $display; ?>>
		<p>
			<strong><?php _e( 'File Downloads:', 'pdd' ); ?></strong>
		</p>

		<input type="hidden" id="pdd_camp_files" class="pdd_repeatable_upload_name_field" value=""/>

		<div id="pdd_file_fields" class="pdd_meta_table_wrap">
			<table class="widefat pdd_repeatable_table" width="100%" cellpadding="0" cellspacing="0">
				<thead>
					<tr>
						<!--drag handle column. Disabled until we can work out a way to solve the issues raised here: https://github.com/easydigitaldownloads/Easy-Digital-Downloads/issues/1066
						<th style="width: 20px"></th>
						-->
						<th style="width: 20%"><?php _e( 'File Name', 'pdd' ); ?></th>
						<th><?php _e( 'File URL', 'pdd' ); ?></th>
						<th class="pricing" style="width: 20%; <?php echo $variable_display; ?>"><?php _e( 'Price Assignment', 'pdd' ); ?></th>
						<?php do_action( 'pdd_camp_file_table_head', $post_id ); ?>
						<th style="width: 2%"></th>
					</tr>
				</thead>
				<tbody>
				<?php
					if ( ! empty( $files ) && is_array( $files ) ) :
						foreach ( $files as $key => $value ) :
							$name          = isset( $value['name'] )          ? $value['name']          : '';
							$file          = isset( $value['file'] )          ? $value['file']          : '';
							$condition     = isset( $value['condition'] )     ? $value['condition']     : false;
							$attachment_id = isset( $value['attachment_id'] ) ? absint( $value['attachment_id'] ) : false;

							$args = apply_filters( 'pdd_file_row_args', compact( 'name', 'file', 'condition', 'attachment_id' ), $value );
				?>
						<tr class="pdd_repeatable_upload_wrapper pdd_repeatable_row">
							<?php do_action( 'pdd_render_file_row', $key, $args, $post_id ); ?>
						</tr>
				<?php
						endforeach;
					else :
				?>
					<tr class="pdd_repeatable_upload_wrapper pdd_repeatable_row">
						<?php do_action( 'pdd_render_file_row', 0, array(), $post_id ); ?>
					</tr>
				<?php endif; ?>
					<tr>
						<td class="submit" colspan="4" style="float: none; clear:both; background: #fff;">
							<a class="button-secondary pdd_add_repeatable" style="margin: 6px 0 10px;"><?php _e( 'Add New File', 'pdd' ); ?></a>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
<?php
}
add_action( 'pdd_meta_box_files_fields', 'pdd_render_files_field', 20 );


/**
 * Individual file row.
 *
 * Used to output a table row for each file associated with a download.
 * Can be called directly, or attached to an action.
 *
 * @since 1.2.2
 * @param string $key Array key
 * @param array $args Array of all the arguments passed to the function
 * @param int $post_id Download (Post) ID
 * @return void
 */
function pdd_render_file_row( $key = '', $args = array(), $post_id ) {
	$defaults = array(
		'name'          => null,
		'file'          => null,
		'condition'     => null,
		'attachment_id' => null
	);

	$args = wp_parse_args( $args, $defaults );

	$prices = pdd_get_variable_prices( $post_id );

	$variable_pricing = pdd_has_variable_prices( $post_id );
	$variable_display = $variable_pricing ? '' : ' style="display:none;"';
?>

	<!--
	Disabled until we can work out a way to solve the issues raised here: https://github.com/easydigitaldownloads/Easy-Digital-Downloads/issues/1066
	<td>
		<span class="pdd_draghandle"></span>
	</td>
	-->
	<td>
		<input type="hidden" name="pdd_camp_files[<?php echo absint( $key ); ?>][attachment_id]" class="pdd_repeatable_attachment_id_field" value="<?php echo esc_attr( absint( $args['attachment_id'] ) ); ?>"/>
		<?php echo PDD()->html->text( array(
			'name'        => 'pdd_camp_files[' . $key . '][name]',
			'value'       => $args['name'],
			'placeholder' => __( 'File Name', 'pdd' ),
			'class'       => 'pdd_repeatable_name_field large-text'
		) ); ?>
	</td>

	<td>
		<div class="pdd_repeatable_upload_field_container">
			<?php echo PDD()->html->text( array(
				'name'        => 'pdd_camp_files[' . $key . '][file]',
				'value'       => $args['file'],
				'placeholder' => __( 'Upload or enter the file URL', 'pdd' ),
				'class'       => 'pdd_repeatable_upload_field pdd_upload_field large-text'
			) ); ?>

			<span class="pdd_upload_file">
				<a href="#" data-uploader_title="" data-uploader_button_text="<?php _e( 'Insert', 'pdd' ); ?>" class="pdd_upload_file_button" onclick="return false;"><?php _e( 'Upload a File', 'pdd' ); ?></a>
			</span>
		</div>
	</td>

	<td class="pricing"<?php echo $variable_display; ?>>
		<?php
			$options = array();

			if ( $prices ) {
				foreach ( $prices as $price_key => $price ) {
					$options[ $price_key ] = $prices[ $price_key ][ 'name' ];
				}
			}

			echo PDD()->html->select( array(
				'name'             => 'pdd_camp_files[' . $key . '][condition]',
				'class'            => 'pdd_repeatable_condition_field',
				'options'          => $options,
				'selected'         => $args['condition'],
				'show_option_none' => false
			) );
		?>
	</td>

	<?php do_action( 'pdd_camp_file_table_row', $post_id, $key, $args ); ?>

	<td>
		<a href="#" class="pdd_remove_repeatable" data-type="file" style="background: url(<?php echo admin_url('/images/xit.gif'); ?>) no-repeat;">&times;</a>
	</td>
<?php
}
add_action( 'pdd_render_file_row', 'pdd_render_file_row', 10, 3 );


/**
 * File Download Limit Row
 *
 * The file download limit is the maximum number of times each file
 * can be downloaded by the buyer
 *
 * @since 1.3.1
 * @param int $post_id Download (Post) ID
 * @return void
 */
function pdd_render_download_limit_row( $post_id ) {
    global $pdd_options;

    if( ! current_user_can( 'manage_shop_settings' ) )
        return;

	$pdd_camp_limit = pdd_get_file_download_limit( $post_id );
	$display = 'bundle' == pdd_get_download_type( $post_id ) ? ' style="display: none;"' : '';
?>
	<div id="pdd_camp_limit_wrap"<?php echo $display; ?>>
		<p><strong><?php _e( 'File Download Limit:', 'pdd' ); ?></strong></p>
		<label for="pdd_camp_limit">
			<?php echo PDD()->html->text( array(
				'name'  => '_pdd_camp_limit',
				'value' => $pdd_camp_limit,
				'class' => 'small-text'
			) ); ?>
			<?php _e( 'Leave blank for global setting or 0 for unlimited', 'pdd' ); ?>
		</label>
	</div>
<?php
}
//add_action( 'pdd_meta_box_settings_fields', 'pdd_render_download_limit_row', 20 );

/**
 * Product tax settings
 *
 * Outputs the option to mark whether a product is exclusive of tax
 *
 * @since 1.9
 * @param int $post_id Download (Post) ID
 * @return void
 */
function pdd_render_dowwn_tax_options( $post_id = 0 ) {
    global $pdd_options;

    if( ! current_user_can( 'manage_shop_settings' ) || ! pdd_use_taxes() )
        return;

	$exclusive = pdd_camp_is_tax_exclusive( $post_id );
?>
	<p><strong><?php _e( 'Ignore Tax:', 'pdd' ); ?></strong></p>
	<label for="_pdd_camp_tax_exclusive">
		<?php echo PDD()->html->checkbox( array(
			'name'    => '_pdd_camp_tax_exclusive',
			'current' => $exclusive
		) ); ?>
		<?php _e( 'Mark this product as exclusive of tax', 'pdd' ); ?>
	</label>
<?php
}
//add_action( 'pdd_meta_box_settings_fields', 'pdd_render_dowwn_tax_options', 30 );

/**
 * Render Accounting Options
 *
 * @since 1.6
 * @param int $post_id Download (Post) ID
 * @return void
 */
function pdd_render_accounting_options( $post_id ) {
	global $pdd_options;

	if( ! pdd_use_skus() ) {
		return;
	}

		$pdd_sku = get_post_meta( $post_id, 'pdd_sku', true );
?>
		<p><strong><?php _e( 'Accounting Options:', 'pdd' ); ?></strong></p>
		<p>
			<label for="pdd_sku">
				<?php echo PDD()->html->text( array(
					'name'  => 'pdd_sku',
					'value' => $pdd_sku,
					'class' => 'small-text'
				) ); ?>
				<?php echo sprintf( __( 'Enter an SKU for this %s.', 'pdd' ), strtolower( pdd_get_label_singular() ) ); ?>
			</label>
		</p>
<?php
}
add_action( 'pdd_meta_box_settings_fields', 'pdd_render_accounting_options', 25 );


/**
 * Render Disable Button
 *
 * @since 1.0
 * @param int $post_id Download (Post) ID
 * @return void
 */
function pdd_render_disable_button( $post_id ) {
	$hide_button = get_post_meta( $post_id, '_pdd_hide_donate_link', true ) ? 1 : 0;
	$behavior    = get_post_meta( $post_id, '_pdd_button_behavior', true );
?>
	<p><strong><?php _e( 'Button Options:', 'pdd' ); ?></strong></p>
	<p>
		<label for="_pdd_hide_donate_link">
			<?php echo PDD()->html->checkbox( array(
				'name'    => '_pdd_hide_donate_link',
				'current' => $hide_button
			) ); ?>
			<?php _e( 'Disable the automatic output of the purchase button', 'pdd' ); ?>
		</label>
	</p>
	<?php if( pdd_shop_supports_buy_now() ) : ?>
	<p>
		<label for="_pdd_button_behavior">
			<?php echo PDD()->html->select( array(
				'name'    => '_pdd_button_behavior',
				'options' => array(
					'add_to_cart' => __( 'Add to Cart', 'pdd' ),
					'direct'      => __( 'Buy Now', 'pdd' )
				),
				'show_option_all'  => null,
				'show_option_none' => null,
				'selected' => $behavior
			) ); ?>
			<?php _e( 'Purchase button behavior', 'pdd' ); ?>
		</label>
	</p>
<?php
	endif;
}
add_action( 'pdd_meta_box_settings_fields', 'pdd_render_disable_button', 30 );


/** Product Notes *****************************************************************/

/**
 * Product Notes Meta Box
 *
 * Renders the Product Notes meta box
 *
 * @since 1.2.1
 * @global array $post Contains all the download data
 * @global array $pdd_options Contains all the options set for PDD
 * @return void
 */
function pdd_render_product_notes_meta_box() {
	global $post, $pdd_options;

	do_action( 'pdd_product_notes_meta_box_fields', $post->ID );
}

/**
 * Render Product Notes Field
 *
 * @since 1.2.1
 * @param int $post_id Download (Post) ID
 * @return void
 */
function pdd_render_product_notes_field( $post_id ) {
	global $pdd_options;

	$product_notes = pdd_get_product_notes( $post_id );
?>
	<textarea rows="1" cols="40" class="large-texarea" name="pdd_product_notes" id="pdd_product_notes_field"><?php echo esc_textarea( $product_notes ); ?></textarea>
	<p><?php _e( 'Special notes or instructions for this product. These notes will be added to the purchase receipt.', 'pdd' ); ?></p>
<?php
}
add_action( 'pdd_product_notes_meta_box_fields', 'pdd_render_product_notes_field' );


/** Stats *****************************************************************/

/**
 * Render Stats Meta Box
 *
 * @since 1.0
 * @global array $post Contains all the download data
 * @return void
 */
function pdd_render_stats_meta_box() {
	global $post;

	if( ! current_user_can( 'view_product_stats', $post->ID ) ) {
		return;
	}

	$earnings = pdd_get_download_earnings_stats( $post->ID );
	$sales    = pdd_get_download_sales_stats( $post->ID );
?>

	<p>
		<strong class="label"><?php _e( 'Sales:', 'pdd' ); ?></strong>
		<span><?php echo $sales; ?> &mdash; <a href="<?php echo admin_url( '/edit.php?page=pdd-reports&view=sales&post_type=download&tab=logs&download=' . $post->ID ); ?>"><?php _e( 'View Sales Log', 'pdd' ); ?></a></span>
	</p>

	<p>
		<strong class="label"><?php _e( 'Earnings:', 'pdd' ); ?></strong>
		<span><?php echo pdd_currency_filter( pdd_format_amount( $earnings ) ); ?></span>
	</p>

	<hr />
	<p>
		<span><a href="<?php echo admin_url( 'edit.php?post_type=pdd_camp&page=pdd-reports&view=downloads&download-id=' . $post->ID ); ?>"><?php _e( 'View Detailed Earnings Report', 'pdd' ); ?></a></span>
	</p>
<?php
	do_action('pdd_stats_meta_box');
}
