<?php
/**
 * Uninstall Easy Digital Downloads
 *
 * @package     PDD
 * @subpackage  Uninstall
 * @copyright   Copyright (c) 2014, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.4.3
 */

// Exit if accessed directly
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;

// Load PDD file
include_once( 'easy-digital-downloads.php' );

global $wpdb, $pdd_options, $wp_roles;

if( pdd_get_option( 'uninstall_on_delete' ) ) {

	/** Delete All the Custom Post Types */
	$pdd_taxonomies = array( 'download_category', 'download_tag', 'pdd_log_type', );
	$pdd_post_types = array( 'download', 'pdd_payment', 'pdd_discount', 'pdd_log' );
	foreach ( $pdd_post_types as $post_type ) {
	
		$pdd_taxonomies = array_merge( $pdd_taxonomies, get_object_taxonomies( $post_type ) );
		$items = get_posts( array( 'post_type' => $post_type, 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids' ) );

		if ( $items ) {
			foreach ( $items as $item ) {
				wp_delete_post( $item, true);
			}
		}
	}

	/** Delete All the Terms & Taxonomies */
	foreach ( array_unique( array_filter( $pdd_taxonomies ) ) as $taxonomy ) {
		
		$terms = $wpdb->get_results( $wpdb->prepare( "SELECT t.*, tt.* FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id WHERE tt.taxonomy IN ('%s') ORDER BY t.name ASC", $taxonomy ) );
		
		// Delete Terms
		if ( $terms ) {
			foreach ( $terms as $term ) {
				$wpdb->delete( $wpdb->term_taxonomy, array( 'term_taxonomy_id' => $term->term_taxonomy_id ) );
				$wpdb->delete( $wpdb->terms, array( 'term_id' => $term->term_id ) );
			}
		}
		
		// Delete Taxonomies
		$wpdb->delete( $wpdb->term_taxonomy, array( 'taxonomy' => $taxonomy ), array( '%s' ) );
	}

	/** Delete the Plugin Pages */
	$pdd_created_pages = array( 'purchase_page', 'success_page', 'failure_page', 'purchase_history_page' );
	foreach ( $pdd_created_pages as $p ) {
		if ( isset( $pdd_options[ $p ] ) ) {
			wp_delete_post( $pdd_options[ $p ], true );
		}
	}

	/** Delete all the Plugin Options */
	delete_option( 'pdd_settings' );

	/** Delete Capabilities */
	PDD()->roles->remove_caps();

	/** Delete the Roles */
	$pdd_roles = array( 'shop_manager', 'shop_accountant', 'shop_worker', 'shop_vendor' );
	foreach ( $pdd_roles as $role ) {
		remove_role( $role );
	}

	/** Cleanup Cron Events */
	wp_clear_scheduled_hook( 'pdd_daily_scheduled_events' );
	wp_clear_scheduled_hook( 'pdd_daily_cron' );
	wp_clear_scheduled_hook( 'pdd_weekly_cron' );
}
