<?php
/**
 * Admin / Heartbeat
 *
 * @package     PDD
 * @subpackage  Admin
 * @copyright   Copyright (c) 2012, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.8
*/


/**
 * PDD_Heartbeart Class
 *
 * Hooks into the WP heartbeat API to update various parts of the dashboard as new sales are made
 *
 * Dashboard components that are effect:
 *	- Dashboard Summary Widget
 *
 * @since 1.8
 */
class PDD_Heartbeat {

	/**
	 * Get things started
	 *
	 * @access public
	 * @since 1.8
	 * @return void
	 */
	public static function init() {

		add_filter( 'heartbeat_received', array( 'PDD_Heartbeat', 'heartbeat_received' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( 'PDD_Heartbeat', 'enqueue_scripts' ) );
	}

	/**
	 * Tie into the heartbeat and append our stats
	 *
	 * @access public
	 * @since 1.8
	 * @return array
	 */
	public static function heartbeat_received( $response, $data ) {

		if( ! current_user_can( 'view_shop_reports' ) ) {
			return $response; // Only modify heartbeat if current user can view show reports
		}

		// Make sure we only run our query if the pdd_heartbeat key is present
		if( ( isset( $data['pdd_heartbeat'] ) ) && ( $data['pdd_heartbeat'] == 'dashboard_summary' ) ) {

			// Instantiate the stats class
			$stats = new PDD_Payment_Stats;

			$earnings = pdd_get_total_earnings();

			// Send back the number of complete payments
			$response['pdd-total-payments'] = pdd_format_amount( pdd_get_total_sales(), false );
			$response['pdd-total-earnings'] = html_entity_decode( pdd_currency_filter( pdd_format_amount( $earnings ) ), ENT_COMPAT, 'UTF-8' );
			$response['pdd-payments-month'] = pdd_format_amount( $stats->get_sales( 0, 'this_month', false, array( 'publish', 'revoked' ) ), false );
			$response['pdd-earnings-month'] = html_entity_decode( pdd_currency_filter( pdd_format_amount( $stats->get_earnings( 0, 'this_month' ) ) ), ENT_COMPAT, 'UTF-8' );
			$response['pdd-payments-today'] = pdd_format_amount( $stats->get_sales( 0, 'today', false, array( 'publish', 'revoked' ) ), false );
			$response['pdd-earnings-today'] = html_entity_decode( pdd_currency_filter( pdd_format_amount( $stats->get_earnings( 0, 'today' ) ) ), ENT_COMPAT, 'UTF-8' );

		}

		return $response;

	}

	/**
	 * Load the heartbeat scripts
	 *
	 * @access public
	 * @since 1.8
	 * @return array
	 */
	public static function enqueue_scripts() {

		if( ! current_user_can( 'view_shop_reports' ) ) {
			return; // Only load heartbeat if current user can view show reports
		}

		// Make sure the JS part of the Heartbeat API is loaded.
		wp_enqueue_script( 'heartbeat' );
		add_action( 'admin_print_footer_scripts', array( 'PDD_Heartbeat', 'footer_js' ), 20 );
	}

	/**
	 * Inject our JS into the admin footer
	 *
	 * @access public
	 * @since 1.8
	 * @return array
	 */
	public static function footer_js() {
		global $pagenow;

		// Only proceed if on the dashboard
		if( 'index.php' != $pagenow ) {
			return;
		}

		if( ! current_user_can( 'view_shop_reports' ) ) {
			return; // Only load heartbeat if current user can view show reports
		}

		?>
		<script>
			(function($){

			// Hook into the heartbeat-send
			$(document).on('heartbeat-send', function(e, data) {
				data['pdd_heartbeat'] = 'dashboard_summary';
			});

			// Listen for the custom event "heartbeat-tick" on $(document).
			$(document).on( 'heartbeat-tick', function(e, data) {

				// Only proceed if our PDD data is present
				if ( ! data['pdd-total-payments'] )
					return;
				console.log('tick');
				// Update sale count and bold it to provide a highlight
				$('.pdd_dashboard_widget .table_totals .b.b-earnings').text( data['pdd-total-earnings'] ).css( 'font-weight', 'bold' );
				$('.pdd_dashboard_widget .table_totals .b.b-sales').text( data['pdd-total-payments'] ).css( 'font-weight', 'bold' );
				$('.pdd_dashboard_widget .table_today .b.b-earnings').text( data['pdd-earnings-today'] ).css( 'font-weight', 'bold' );
				$('.pdd_dashboard_widget .table_today .b.b-sales').text( data['pdd-payments-today'] ).css( 'font-weight', 'bold' );
				$('.pdd_dashboard_widget .table_current_month .b-earnings').text( data['pdd-earnings-month'] ).css( 'font-weight', 'bold' );
				$('.pdd_dashboard_widget .table_current_month .b-sales').text( data['pdd-payments-month'] ).css( 'font-weight', 'bold' );

				// Return font-weight to normal after 2 seconds
				setTimeout(function(){
					$('.pdd_dashboard_widget .b.b-sales,.pdd_dashboard_widget .b.b-earnings').css( 'font-weight', 'normal' );
					$('.pdd_dashboard_widget .table_current_month .b.b-earnings,.pdd_dashboard_widget .table_current_month .b.b-sales').css( 'font-weight', 'normal' );
				}, 2000);

			});
			}(jQuery));
		</script>
		<?php
	}
}
add_action( 'plugins_loaded', array( 'PDD_Heartbeat', 'init' ) );
