<?php global $post; ?>
<table id="pdd_checkout_cart" <?php if ( ! pdd_is_ajax_disabled() ) { echo 'class="ajaxed"'; } ?>>
	<thead>
		<tr class="pdd_cart_header_row">
			<?php do_action( 'pdd_checkout_table_header_first' ); ?>
			<th class="pdd_cart_item_name"><?php _e( 'Item Name', 'pdd' ); ?></th>
			<th class="pdd_cart_item_price"><?php _e( 'Item Price', 'pdd' ); ?></th>
			<th class="pdd_cart_actions"><?php _e( 'Actions', 'pdd' ); ?></th>
			<?php do_action( 'pdd_checkout_table_header_last' ); ?>
		</tr>
	</thead>
	<tbody>
		<?php $cart_items = pdd_get_cart_contents(); ?>
		<?php do_action( 'pdd_cart_items_before' ); ?>
		<?php if ( $cart_items ) : ?>
			<?php foreach ( $cart_items as $key => $item ) : ?>
				<tr class="pdd_cart_item" id="pdd_cart_item_<?php echo esc_attr( $key ) . '_' . esc_attr( $item['id'] ); ?>" data-download-id="<?php echo esc_attr( $item['id'] ); ?>">
					<?php do_action( 'pdd_checkout_table_body_first', $item ); ?>
					<td class="pdd_cart_item_name">
						<?php
							if ( current_theme_supports( 'post-thumbnails' ) && has_post_thumbnail( $item['id'] ) ) {
								echo '<div class="pdd_cart_item_image">';
									echo get_the_post_thumbnail( $item['id'], apply_filters( 'pdd_checkout_image_size', array( 25,25 ) ) );
								echo '</div>';
							}
							$item_title = get_the_title( $item['id'] );
							if ( ! empty( $item['options'] ) && pdd_has_variable_prices( $item['id'] ) ) {
								$item_title .= ' - ' . pdd_get_cart_item_price_name( $item );
							}
							echo '<span class="pdd_checkout_cart_item_title">' . esc_html( $item_title ) . '</span>';
						?>
					</td>
					<td class="pdd_cart_item_price"><?php echo pdd_cart_item_price( $item['id'], $item['options'] ); ?></td>
					<td class="pdd_cart_actions">
						<?php if( pdd_item_quantities_enabled() ) : ?>
							<input type="number" min="1" step="1" name="pdd-cart-download-<?php echo $key; ?>-quantity" class="pdd-input pdd-item-quantity" value="<?php echo pdd_get_cart_item_quantity( $item['id'], $item['options'] ); ?>"/>
							<input type="hidden" name="pdd-cart-downloads[]" value="<?php echo $item['id']; ?>"/>
							<input type="hidden" name="pdd-cart-download-<?php echo $key; ?>-options" value="<?php echo esc_attr( serialize( $item['options'] ) ); ?>"/>
						<?php endif; ?>
						<a class="pdd_cart_remove_item_btn" href="<?php echo esc_url( pdd_remove_item_url( $key, $post ) ); ?>"><?php _e( 'Remove', 'pdd' ); ?></a>
					</td>
					<?php do_action( 'pdd_checkout_table_body_last', $item ); ?>
				</tr>
			<?php endforeach; ?>
		<?php endif; ?>
		<?php do_action( 'pdd_cart_items_middle' ); ?>
		<?php // TODO: What do here? ?>
		<?php do_action( 'pdd_cart_items_after' ); ?>
	</tbody>
	<tfoot>

		<?php if ( has_action( 'pdd_cart_footer_buttons' ) ) : ?>
			<tr class="pdd_cart_footer_row">
				<th colspan="<?php echo pdd_checkout_cart_columns(); ?>">
					<?php do_action( 'pdd_cart_footer_buttons' ); ?>
				</th>
			</tr>
		<?php endif; ?>
		
		<?php do_action( 'pdd_checkout_table_before_footer' ); ?>
		
		<tr class="pdd_cart_footer_row">
			<?php do_action( 'pdd_checkout_table_footer_first' ); ?>
			<th colspan="<?php echo pdd_checkout_cart_columns(); ?>" class="pdd_cart_total"><?php _e( 'Total', 'pdd' ); ?>: <span class="pdd_cart_amount" data-subtotal="<?php echo pdd_get_cart_total(); ?>" data-total="<?php echo pdd_get_cart_total(); ?>"><?php pdd_cart_total(); ?></span></th>
			<?php do_action( 'pdd_checkout_table_footer_last' ); ?>
		</tr>

		<?php do_action( 'pdd_checkout_table_after_footer' ); ?>
	</tfoot>
</table>
