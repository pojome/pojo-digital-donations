<?php
/**
 * Post Type Functions
 *
 * @package     PDD
 * @subpackage  Functions
 * @copyright   Copyright (c) 2014, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Registers and sets up the Downloads custom post type
 *
 * @since 1.0
 * @return void
 */
function pdd_setup_pdd_post_types() {

	$archives = defined( 'PDD_DISABLE_ARCHIVE' ) && PDD_DISABLE_ARCHIVE ? false : true;
	$slug     = defined( 'PDD_SLUG' ) ? PDD_SLUG : 'campaign';
	$rewrite  = defined( 'PDD_DISABLE_REWRITE' ) && PDD_DISABLE_REWRITE ? false : array('slug' => $slug, 'with_front' => false);

	$download_labels =  apply_filters( 'pdd_camp_labels', array(
		'name' 				=> '%2$s',
		'singular_name' 	=> '%1$s',
		'add_new' 			=> __( 'Add New', 'pdd' ),
		'add_new_item' 		=> __( 'Add New %1$s', 'pdd' ),
		'edit_item' 		=> __( 'Edit %1$s', 'pdd' ),
		'new_item' 			=> __( 'New %1$s', 'pdd' ),
		'all_items' 		=> __( 'All %2$s', 'pdd' ),
		'view_item' 		=> __( 'View %1$s', 'pdd' ),
		'search_items' 		=> __( 'Search %2$s', 'pdd' ),
		'not_found' 		=> __( 'No %2$s found', 'pdd' ),
		'not_found_in_trash'=> __( 'No %2$s found in Trash', 'pdd' ),
		'parent_item_colon' => '',
		'menu_name' 		=> __( 'Donations', 'pdd' )
	) );

	foreach ( $download_labels as $key => $value ) {
	   $download_labels[ $key ] = sprintf( $value, pdd_get_label_singular(), pdd_get_label_plural() );
	}

	$download_args = array(
		'labels' 			=> $download_labels,
		'public' 			=> true,
		'publicly_queryable'=> true,
		'show_ui' 			=> true,
		'show_in_menu' 		=> true,
		'query_var' 		=> true,
		'rewrite' 			=> $rewrite,
		'capability_type' 	=> 'product',
		'map_meta_cap'      => true,
		'has_archive' 		=> $archives,
		'hierarchical' 		=> false,
		'supports' 			=> apply_filters( 'pdd_camp_supports', array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'author' ) ),
	);
	register_post_type( 'pdd_camp', apply_filters( 'pdd_camp_post_type_args', $download_args ) );


	/** Payment Post Type */
	$payment_labels = array(
		'name' 				=> _x( 'Payments', 'post type general name', 'pdd' ),
		'singular_name' 	=> _x( 'Payment', 'post type singular name', 'pdd' ),
		'add_new' 			=> __( 'Add New', 'pdd' ),
		'add_new_item' 		=> __( 'Add New Payment', 'pdd' ),
		'edit_item' 		=> __( 'Edit Payment', 'pdd' ),
		'new_item' 			=> __( 'New Payment', 'pdd' ),
		'all_items' 		=> __( 'All Payments', 'pdd' ),
		'view_item' 		=> __( 'View Payment', 'pdd' ),
		'search_items' 		=> __( 'Search Payments', 'pdd' ),
		'not_found' 		=> __( 'No Payments found', 'pdd' ),
		'not_found_in_trash'=> __( 'No Payments found in Trash', 'pdd' ),
		'parent_item_colon' => '',
		'menu_name' 		=> __( 'Payment History', 'pdd' )
	);

	$payment_args = array(
		'labels' 			=> apply_filters( 'pdd_payment_labels', $payment_labels ),
		'public' 			=> false,
		'query_var' 		=> false,
		'rewrite' 			=> false,
		'capability_type' 	=> 'shop_payment',
		'map_meta_cap'      => true,
		'supports' 			=> array( 'title' ),
		'can_export'		=> true
	);
	register_post_type( 'pdd_payment', $payment_args );
}
add_action( 'init', 'pdd_setup_pdd_post_types', 1 );

/**
 * Get Default Labels
 *
 * @since 1.0.8.3
 * @return array $defaults Default labels
 */
function pdd_get_default_labels() {
	$defaults = array(
	   'singular' => __( 'Campaign', 'pdd' ),
	   'plural' => __( 'Campaigns', 'pdd')
	);
	return apply_filters( 'pdd_default_donations_name', $defaults );
}

/**
 * Get Singular Label
 *
 * @since 1.0.8.3
 *
 * @param bool $lowercase
 * @return string $defaults['singular'] Singular label
 */
function pdd_get_label_singular( $lowercase = false ) {
	$defaults = pdd_get_default_labels();
	return ($lowercase) ? strtolower( $defaults['singular'] ) : $defaults['singular'];
}

/**
 * Get Plural Label
 *
 * @since 1.0.8.3
 * @return string $defaults['plural'] Plural label
 */
function pdd_get_label_plural( $lowercase = false ) {
	$defaults = pdd_get_default_labels();
	return ( $lowercase ) ? strtolower( $defaults['plural'] ) : $defaults['plural'];
}

/**
 * Change default "Enter title here" input
 *
 * @since 1.4.0.2
 * @param string $title Default title placeholder text
 * @return string $title New placeholder text
 */
function pdd_change_default_title( $title ) {
     // If a frontend plugin uses this filter (check extensions before changing this function)
     if ( !is_admin() ) {
     	$label = pdd_get_label_singular();
        $title = sprintf( __( 'Enter %s title here', 'pdd' ), $label );
     	return $title;
     }
     
     $screen = get_current_screen();

     if  ( 'pdd_camp' == $screen->post_type ) {
     	$label = pdd_get_label_singular();
        $title = sprintf( __( 'Enter %s title here', 'pdd' ), $label );
     }

     return $title;
}
add_filter( 'enter_title_here', 'pdd_change_default_title' );

/**
 * Registers the custom taxonomies for the downloads custom post type
 *
 * @since 1.0
 * @return void
*/
function pdd_setup_download_taxonomies() {

	$slug     = defined( 'PDD_SLUG' ) ? PDD_SLUG : 'campaigns';

	/** Categories */
	$category_labels = array(
		'name' 				=> sprintf( _x( '%s Categories', 'taxonomy general name', 'pdd' ), pdd_get_label_singular() ),
		'singular_name' 	=> _x( 'Category', 'taxonomy singular name', 'pdd' ),
		'search_items' 		=> __( 'Search Categories', 'pdd'  ),
		'all_items' 		=> __( 'All Categories', 'pdd'  ),
		'parent_item' 		=> __( 'Parent Category', 'pdd'  ),
		'parent_item_colon' => __( 'Parent Category:', 'pdd'  ),
		'edit_item' 		=> __( 'Edit Category', 'pdd'  ),
		'update_item' 		=> __( 'Update Category', 'pdd'  ),
		'add_new_item' 		=> __( 'Add New Category', 'pdd'  ),
		'new_item_name' 	=> __( 'New Category Name', 'pdd'  ),
		'menu_name' 		=> __( 'Categories', 'pdd'  ),
		'choose_from_most_used' => sprintf( __( 'Choose from most used %s categories', 'pdd'  ), pdd_get_label_singular() ),
	);

	$category_args = apply_filters( 'pdd_camp_category_args', array(
			'hierarchical' 	=> true,
			'labels' 		=> apply_filters('pdd_camp_category_labels', $category_labels),
			'show_ui' 		=> true,
			'query_var' 	=> 'camp_category',
			'rewrite' 		=> array('slug' => $slug . '/category', 'with_front' => false, 'hierarchical' => true ),
			'capabilities'  => array( 'manage_terms' => 'manage_product_terms','edit_terms' => 'edit_product_terms','assign_terms' => 'assign_product_terms','delete_terms' => 'delete_product_terms' )
		)
	);
	register_taxonomy( 'camp_category', array('pdd_camp'), $category_args );
	register_taxonomy_for_object_type( 'camp_category', 'pdd_camp' );

	/** Tags */
	$tag_labels = array(
		'name' 				=> sprintf( _x( '%s Tags', 'taxonomy general name', 'pdd' ), pdd_get_label_singular() ),
		'singular_name' 	=> _x( 'Tag', 'taxonomy singular name', 'pdd' ),
		'search_items' 		=> __( 'Search Tags', 'pdd'  ),
		'all_items' 		=> __( 'All Tags', 'pdd'  ),
		'parent_item' 		=> __( 'Parent Tag', 'pdd'  ),
		'parent_item_colon' => __( 'Parent Tag:', 'pdd'  ),
		'edit_item' 		=> __( 'Edit Tag', 'pdd'  ),
		'update_item' 		=> __( 'Update Tag', 'pdd'  ),
		'add_new_item' 		=> __( 'Add New Tag', 'pdd'  ),
		'new_item_name' 	=> __( 'New Tag Name', 'pdd'  ),
		'menu_name' 		=> __( 'Tags', 'pdd'  ),
		'choose_from_most_used' => sprintf( __( 'Choose from most used %s tags', 'pdd'  ), pdd_get_label_singular() ),
	);

	$tag_args = apply_filters( 'pdd_camp_tag_args', array(
			'hierarchical' 	=> false,
			'labels' 		=> apply_filters( 'pdd_camp_tag_labels', $tag_labels ),
			'show_ui' 		=> true,
			'query_var' 	=> 'camp_tag',
			'rewrite' 		=> array( 'slug' => $slug . '/tag', 'with_front' => false, 'hierarchical' => true  ),
			'capabilities'  => array( 'manage_terms' => 'manage_product_terms','edit_terms' => 'edit_product_terms','assign_terms' => 'assign_product_terms','delete_terms' => 'delete_product_terms' )

		)
	);
	register_taxonomy( 'camp_tag', array( 'pdd_camp' ), $tag_args );
	register_taxonomy_for_object_type( 'camp_tag', 'pdd_camp' );
}
add_action( 'init', 'pdd_setup_download_taxonomies', 0 );

/**
 * Registers Custom Post Statuses which are used by the Payments and Discount
 * Codes
 *
 * @since 1.0.9.1
 * @return void
 */
function pdd_register_post_type_statuses() {
	// Payment Statuses
	register_post_status( 'refunded', array(
		'label'                     => _x( 'Refunded', 'Refunded payment status', 'pdd' ),
		'public'                    => true,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'Refunded <span class="count">(%s)</span>', 'Refunded <span class="count">(%s)</span>', 'pdd' )
	) );
	register_post_status( 'failed', array(
		'label'                     => _x( 'Failed', 'Failed payment status', 'pdd' ),
		'public'                    => true,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'Failed <span class="count">(%s)</span>', 'Failed <span class="count">(%s)</span>', 'pdd' )
	)  );
	register_post_status( 'revoked', array(
		'label'                     => _x( 'Revoked', 'Revoked payment status', 'pdd' ),
		'public'                    => true,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'Revoked <span class="count">(%s)</span>', 'Revoked <span class="count">(%s)</span>', 'pdd' )
	)  );
	register_post_status( 'abandoned', array(
		'label'                     => _x( 'Abandoned', 'Abandoned payment status', 'pdd' ),
		'public'                    => true,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'Abandoned <span class="count">(%s)</span>', 'Abandoned <span class="count">(%s)</span>', 'pdd' )
	)  );

	// Discount Code Statuses
	register_post_status( 'active', array(
		'label'                     => _x( 'Active', 'Active discount code status', 'pdd' ),
		'public'                    => true,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'Active <span class="count">(%s)</span>', 'Active <span class="count">(%s)</span>', 'pdd' )
	)  );
	register_post_status( 'inactive', array(
		'label'                     => _x( 'Inactive', 'Inactive discount code status', 'pdd' ),
		'public'                    => true,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'Inactive <span class="count">(%s)</span>', 'Inactive <span class="count">(%s)</span>', 'pdd' )
	)  );
}
add_action( 'init', 'pdd_register_post_type_statuses' );

/**
 * Updated Messages
 *
 * Returns an array of with all updated messages.
 *
 * @since 1.0
 * @param array $messages Post updated message
 * @return array $messages New post updated messages
 */
function pdd_updated_messages( $messages ) {
	global $post, $post_ID;

	$url1 = '<a href="' . get_permalink( $post_ID ) . '">';
	$url2 = pdd_get_label_singular();
	$url3 = '</a>';

	$messages['pdd_camp'] = array(
		1 => sprintf( __( '%2$s updated. %1$sView %2$s%3$s.', 'pdd' ), $url1, $url2, $url3 ),
		4 => sprintf( __( '%2$s updated. %1$sView %2$s%3$s.', 'pdd' ), $url1, $url2, $url3 ),
		6 => sprintf( __( '%2$s published. %1$sView %2$s%3$s.', 'pdd' ), $url1, $url2, $url3 ),
		7 => sprintf( __( '%2$s saved. %1$sView %2$s%3$s.', 'pdd' ), $url1, $url2, $url3 ),
		8 => sprintf( __( '%2$s submitted. %1$sView %2$s%3$s.', 'pdd' ), $url1, $url2, $url3 )
	);

	return $messages;
}
add_filter( 'post_updated_messages', 'pdd_updated_messages' );
