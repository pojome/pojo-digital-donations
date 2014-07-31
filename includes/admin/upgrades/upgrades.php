<?php
/**
 * Upgrade Screen
 *
 * @package     PDD
 * @subpackage  Admin/Upgrades
 * @copyright   Copyright (c) 2014, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.3.1
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Render Upgrades Screen
 *
 * @since 1.3.1
 * @return void
*/
function pdd_upgrades_screen() {
	$action = isset( $_GET['pdd-upgrade'] ) ? sanitize_text_field( $_GET['pdd-upgrade'] ) : '';
	$step   = isset( $_GET['step'] )        ? absint( $_GET['step'] )                     : 1;
	$total  = isset( $_GET['total'] )       ? absint( $_GET['total'] )                    : false;
	$custom = isset( $_GET['custom'] )      ? absint( $_GET['custom'] )                   : 0;
	$steps  = round( ( $total / 100 ), 0 );
	?>
	<div class="wrap">
		<h2><?php _e( 'Pojo Digital Donations - Upgrades', 'pdd' ); ?></h2>
	
		<?php if( ! empty( $action ) ) : ?>

			<div id="pdd-upgrade-status">
				<p><?php _e( 'The upgrade process has started, please be patient. This could take several minutes. You will be automatically redirected when the upgrade is finished.', 'pdd' ); ?></p>
				<?php if( ! empty( $total ) ) : ?>
					<p><strong><?php printf( __( 'Step %d of approximately %d running', 'pdd' ), $step, $steps ); ?>
				<?php endif; ?>
			</div>
			<script type="text/javascript">
				document.location.href = "index.php?pdd_action=<?php echo $action; ?>&step=<?php echo $step; ?>&total=<?php echo $total; ?>&custom=<?php echo $custom; ?>";
			</script>

		<?php else : ?>

			<div id="pdd-upgrade-status">
				<p>
					<?php _e( 'The upgrade process has started, please be patient. This could take several minutes. You will be automatically redirected when the upgrade is finished.', 'pdd' ); ?>
					<img src="<?php echo PDD_PLUGIN_URL . '/assets/images/loading.gif'; ?>" id="pdd-upgrade-loader"/>
				</p>
			</div>
			<script type="text/javascript">
				jQuery( document ).ready( function() {
					// Trigger upgrades on page load
					var data = { action: 'pdd_trigger_upgrades' };
					jQuery.post( ajaxurl, data, function (response) {
						if( response == 'complete' ) {
							jQuery('#pdd-upgrade-loader').hide();
							document.location.href = 'index.php?page=pdd-about'; // Redirect to the welcome page
						}
					});
				});
			</script>

		<?php endif; ?>

	</div>
	<?php
}