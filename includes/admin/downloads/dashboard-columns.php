<?php
/**
 * Dashboard Columns
 *
 * @package     PDD
 * @subpackage  Admin/Downloads
 * @copyright   Copyright (c) 2014, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Camp Columns
 *
 * Defines the custom columns and their order
 *
 * @since 1.0
 * 
*@param array $camp_columns Array of download columns
 * 
*@return array $camp_columns Updated array of download columns for Camps
 *  Post Type List Table
 */
function pdd_camp_columns( $camp_columns ) {
	$camp_columns = array(
		'cb' => '<input type="checkbox"/>',
		'title' => __( 'Name', 'pdd' ),
		'camp_category' => __( 'Categories', 'pdd' ),
		'camp_tag' => __( 'Tags', 'pdd' ),
		'price' => __( 'Price', 'pdd' ),
		'sales' => __( 'Sales', 'pdd' ),
		'earnings' => __( 'Earnings', 'pdd' ),
		'shortcode' => __( 'Purchase Short Code', 'pdd' ),
		'date' => __( 'Date', 'pdd' ),
	);

	return apply_filters( 'pdd_camp_columns', $camp_columns );
}
add_filter( 'manage_edit-pdd_camp_columns', 'pdd_camp_columns' );

/**
 * Render Camp Columns
 *
 * @since 1.0
 * @param string $column_name Column name
 * @param int $post_id Camp (Post) ID
 * @return void
 */
function pdd_render_download_columns( $column_name, $post_id ) {
	if ( get_post_type( $post_id ) == 'pdd_camp' ) {
		global $pdd_options;

		$style 			= isset( $pdd_options['button_style'] ) ? $pdd_options['button_style'] : 'button';
		$color 			= isset( $pdd_options['checkout_color'] ) ? $pdd_options['checkout_color'] : 'blue';
		$color			= ( $color == 'inherit' ) ? '' : $color;
		$purchase_text 	= ! empty( $pdd_options['add_to_cart_text'] ) ? $pdd_options['add_to_cart_text'] : __( 'Purchase', 'pdd' );

		switch ( $column_name ) {
			case 'camp_category':
				echo get_the_term_list( $post_id, 'camp_category', '', ', ', '');
				break;
			case 'camp_tag':
				echo get_the_term_list( $post_id, 'camp_tag', '', ', ', '');
				break;
			case 'price':
				if ( pdd_has_variable_prices( $post_id ) ) {
					echo pdd_price_range( $post_id );
				} else {
					echo pdd_price( $post_id, false );
					echo '<input type="hidden" class="downloadprice-' . $post_id . '" value="' . pdd_get_download_price( $post_id ) . '" />';
				}
				break;
			case 'sales':
				if ( current_user_can( 'view_product_stats', $post_id ) ) {
					echo '<a href="' . esc_url( admin_url( 'edit.php?post_type=pdd_camp&page=pdd-reports&tab=logs&download=' . $post_id ) ) . '">';
						echo pdd_get_download_sales_stats( $post_id );
					echo '</a>';
				} else {
					echo '-';
				}
				break;
			case 'earnings':
				if ( current_user_can( 'view_product_stats', $post_id ) ) {
					echo '<a href="' . esc_url( admin_url( 'edit.php?post_type=pdd_camp&page=pdd-reports&view=downloads&download-id=' . $post_id ) ) . '">';
						echo pdd_currency_filter( pdd_format_amount( pdd_get_download_earnings_stats( $post_id ) ) );
					echo '</a>';
				} else {
					echo '-';
				}
				break;
			case 'shortcode':
				echo '[donate_link id="' . absint( $post_id ) . '" text="' . esc_html( $purchase_text ) . '" style="' . $style . '" color="' . esc_attr( $color ) . '"]';
				break;
		}
	}
}
add_action( 'manage_posts_custom_column', 'pdd_render_download_columns', 10, 2 );

/**
 * Registers the sortable columns in the list table
 *
 * @since 1.0
 * @param array $columns Array of the columns
 * @return array $columns Array of sortable columns
 */
function pdd_sortable_download_columns( $columns ) {
	$columns['price']    = 'price';
	$columns['sales']    = 'sales';
	$columns['earnings'] = 'earnings';

	return $columns;
}
add_filter( 'manage_edit-download_sortable_columns', 'pdd_sortable_download_columns' );

/**
 * Sorts Columns in the Camps List Table
 *
 * @since 1.0
 * @param array $vars Array of all the sort variables
 * @return array $vars Array of all the sort variables
 */
function pdd_sort_downloads( $vars ) {
	// Check if we're viewing the "download" post type
	if ( isset( $vars['post_type'] ) && 'pdd_camp' == $vars['post_type'] ) {
		// Check if 'orderby' is set to "sales"
		if ( isset( $vars['orderby'] ) && 'sales' == $vars['orderby'] ) {
			$vars = array_merge(
				$vars,
				array(
					'meta_key' => '_pdd_camp_sales',
					'orderby'  => 'meta_value_num'
				)
			);
		}

		// Check if "orderby" is set to "earnings"
		if ( isset( $vars['orderby'] ) && 'earnings' == $vars['orderby'] ) {
			$vars = array_merge(
				$vars,
				array(
					'meta_key' => '_pdd_camp_earnings',
					'orderby'  => 'meta_value_num'
				)
			);
		}

		// Check if "orderby" is set to "earnings"
		if ( isset( $vars['orderby'] ) && 'price' == $vars['orderby'] ) {
			$vars = array_merge(
				$vars,
				array(
					'meta_key' => 'pdd_price',
					'orderby'  => 'meta_value_num'
				)
			);
		}
	}

	return $vars;
}

/**
 * Download Load
 *
 * Sorts the downloads.
 *
 * @since 1.0
 * @return void
 */
function pdd_camp_load() {
	add_filter( 'request', 'pdd_sort_downloads' );
}
add_action( 'load-edit.php', 'pdd_camp_load', 9999 );

/**
 * Add Download Filters
 *
 * Adds taxonomy drop down filters for downloads.
 *
 * @since 1.0
 * @return void
 */
function pdd_add_download_filters() {
	global $typenow;

	// Checks if the current post type is 'pdd_camp'
	if ( $typenow == 'pdd_camp') {
		$terms = get_terms( 'camp_category' );
		if ( count( $terms ) > 0 ) {
			echo "<select name='camp_category' id='camp_category' class='postform'>";
				echo "<option value=''>" . __( 'Show all categories', 'pdd' ) . "</option>";
				foreach ( $terms as $term ) {
					$selected = isset( $_GET['camp_category'] ) && $_GET['camp_category'] == $term->slug ? ' selected="selected"' : '';
					echo '<option value="' . esc_attr( $term->slug ) . '"' . $selected . '>' . esc_html( $term->name ) .' (' . $term->count .')</option>';
				}
			echo "</select>";
		}

		$terms = get_terms( 'camp_tag' );
		if ( count( $terms ) > 0) {
			echo "<select name='camp_tag' id='camp_tag' class='postform'>";
				echo "<option value=''>" . __( 'Show all tags', 'pdd' ) . "</option>";
				foreach ( $terms as $term ) {
					$selected = isset( $_GET['camp_tag']) && $_GET['camp_tag'] == $term->slug ? ' selected="selected"' : '';
					echo '<option value="' . esc_attr( $term->slug ) . '"' . $selected . '>' . esc_html( $term->name ) .' (' . $term->count .')</option>';
				}
			echo "</select>";
		}
	}

}
add_action( 'restrict_manage_posts', 'pdd_add_download_filters', 100 );

/**
 * Adds price field to Quick Edit options
 *
 * @since 1.1.3.4
 * @param string $column_name Name of the column
 * @param string $post_type Current Post Type (i.e. download)
 * @return void
 */
function pdd_price_field_quick_edit( $column_name, $post_type ) {
	if ( $column_name != 'price' || $post_type != 'pdd_camp' ) return;
	?>
	<fieldset class="inline-edit-col-left">
		<div id="pdd-download-data" class="inline-edit-col">
			<h4><?php echo sprintf( __( '%s Configuration', 'pdd' ), pdd_get_label_singular() ); ?></h4>
			<label>
				<span class="title"><?php _e( 'Price', 'pdd' ); ?></span>
				<span class="input-text-wrap">
					<input type="text" name="_pdd_regprice" class="text regprice" />
				</span>
			</label>
			<br class="clear" />
		</div>
	</fieldset>
	<?php
}
add_action( 'quick_edit_custom_box', 'pdd_price_field_quick_edit', 10, 2 );
add_action( 'bulk_edit_custom_box', 'pdd_price_field_quick_edit', 10, 2 );

/**
 * Updates price when saving post
 *
 * @since 1.1.3.4
 * @param int $post_id Download (Post) ID
 * @return void
 */
function pdd_price_save_quick_edit( $post_id ) {
	if ( ! isset( $_POST['post_type']) || 'pdd_camp' !== $_POST['post_type'] ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return $post_id;
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return $post_id;

	if ( isset( $_REQUEST['_pdd_regprice'] ) ) {
		update_post_meta( $post_id, 'pdd_price', strip_tags( stripslashes( $_REQUEST['_pdd_regprice'] ) ) );
	}
}
add_action( 'save_post', 'pdd_price_save_quick_edit' );

/**
 * Process bulk edit actions via AJAX
 *
 * @since 1.4.4
 * @return void
 */
function pdd_save_bulk_edit() {
	$post_ids = ( isset( $_POST[ 'post_ids' ] ) && ! empty( $_POST[ 'post_ids' ] ) ) ? $_POST[ 'post_ids' ] : array();

	if ( ! empty( $post_ids ) && is_array( $post_ids ) ) {
		$price = isset( $_POST['price'] ) ? strip_tags( stripslashes( $_POST['price'] ) ) : 0;
		foreach ( $post_ids as $post_id ) {
			if ( ! empty( $price ) ) {
				update_post_meta( $post_id, 'pdd_price', pdd_sanitize_amount( $price ) );
			}
		}
	}

	die();
}
add_action( 'wp_ajax_pdd_save_bulk_edit', 'pdd_save_bulk_edit' );
