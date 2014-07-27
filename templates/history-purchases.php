<?php
// Retrieve all purchases for the current user
$purchases = pdd_get_users_purchases( get_current_user_id(), 20, true, 'any' );
if ( $purchases ) : ?>
	<table id="pdd_user_history">
		<thead>
			<tr class="pdd_purchase_row">
				<?php do_action('pdd_purchase_history_header_before'); ?>
				<th class="pdd_purchase_id"><?php _e('ID', 'pdd'); ?></th>
				<th class="pdd_purchase_date"><?php _e('Date', 'pdd'); ?></th>
				<th class="pdd_purchase_amount"><?php _e('Amount', 'pdd'); ?></th>
				<th class="pdd_purchase_details"><?php _e('Details', 'pdd'); ?></th>
				<?php do_action('pdd_purchase_history_header_after'); ?>
			</tr>
		</thead>
		<?php foreach ( $purchases as $post ) : setup_postdata( $post ); ?>
			<?php $purchase_data = pdd_get_payment_meta( $post->ID ); ?>
			<tr class="pdd_purchase_row">
				<?php do_action( 'pdd_purchase_history_row_start', $post->ID, $purchase_data ); ?>
				<td class="pdd_purchase_id">#<?php echo pdd_get_payment_number( $post->ID ); ?></td>
				<td class="pdd_purchase_date"><?php echo date_i18n( get_option('date_format'), strtotime( get_post_field( 'post_date', $post->ID ) ) ); ?></td>
				<td class="pdd_purchase_amount">
					<span class="pdd_purchase_amount"><?php echo pdd_currency_filter( pdd_format_amount( pdd_get_payment_amount( $post->ID ) ) ); ?></span>
				</td>
				<td class="pdd_purchase_details">
					<?php if( $post->post_status != 'publish' ) : ?>
					<span class="pdd_purchase_status <?php echo $post->post_status; ?>"><?php echo pdd_get_payment_status( $post, true ); ?></span>
					<a href="<?php echo add_query_arg( 'payment_key', pdd_get_payment_key( $post->ID ), pdd_get_success_page_uri() ); ?>">&raquo;</a>
					<?php else: ?>
					<a href="<?php echo add_query_arg( 'payment_key', pdd_get_payment_key( $post->ID ), pdd_get_success_page_uri() ); ?>"><?php _e( 'View Details and Downloads', 'pdd' ); ?></a>
					<?php endif; ?>
				</td>
				<?php do_action( 'pdd_purchase_history_row_end', $post->ID, $purchase_data ); ?>
			</tr>
		<?php endforeach; ?>
	</table>
	<div id="pdd_purchase_history_pagination" class="pdd_pagination navigation">
		<?php
		$big = 999999;
		echo paginate_links( array(
			'base'    => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
			'format'  => '?paged=%#%',
			'current' => max( 1, get_query_var( 'paged' ) ),
			'total'   => ceil( pdd_count_purchases_of_customer() / 20 ) // 20 items per page
		) );
		?>
	</div>
	<?php wp_reset_postdata(); ?>
<?php else : ?>
	<p class="pdd-no-purchases"><?php _e('You have not made any purchases', 'pdd'); ?></p>
<?php endif;