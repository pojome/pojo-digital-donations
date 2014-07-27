<?php
/**
 * Admin Actions
 *
 * @package     PDD
 * @subpackage  Admin/Actions
 * @copyright   Copyright (c) 2014, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Processes all PDD actions sent via POST and GET by looking for the 'pdd-action'
 * request and running do_action() to call the function
 *
 * @since 1.0
 * @return void
 */
function pdd_process_actions() {
	if ( isset( $_POST['pdd-action'] ) ) {
		do_action( 'pdd_' . $_POST['pdd-action'], $_POST );
	}

	if ( isset( $_GET['pdd-action'] ) ) {
		do_action( 'pdd_' . $_GET['pdd-action'], $_GET );
	}
}
add_action( 'admin_init', 'pdd_process_actions' );