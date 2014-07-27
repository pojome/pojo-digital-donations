<?php
/**
 * Front-end Actions
 *
 * @package     PDD
 * @subpackage  Functions
 * @copyright   Copyright (c) 2014, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0.8.1
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Hooks PDD actions, when present in the $_GET superglobal. Every pdd_action
 * present in $_GET is called using WordPress's do_action function. These
 * functions are called on init.
 *
 * @since 1.0
 * @return void
*/
function pdd_get_actions() {
	if ( isset( $_GET['pdd_action'] ) ) {
		do_action( 'pdd_' . $_GET['pdd_action'], $_GET );
	}
}
add_action( 'init', 'pdd_get_actions' );

/**
 * Hooks PDD actions, when present in the $_POST superglobal. Every pdd_action
 * present in $_POST is called using WordPress's do_action function. These
 * functions are called on init.
 *
 * @since 1.0
 * @return void
*/
function pdd_post_actions() {
	if ( isset( $_POST['pdd_action'] ) ) {
		do_action( 'pdd_' . $_POST['pdd_action'], $_POST );
	}
}
add_action( 'init', 'pdd_post_actions' );