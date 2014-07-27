<?php
$cart_items    = pdd_get_cart_contents();
$cart_quantity = pdd_get_cart_quantity();
$display       = $cart_quantity > 0 ? '' : 'style="display:none;"';
?>
<p class="pdd-cart-number-of-items"<?php echo $display; ?>><?php _e( 'Number of items in cart', 'pdd' ); ?>: <span class="pdd-cart-quantity"><?php echo $cart_quantity; ?></span></p>
<ul class="pdd-cart">
<?php if( $cart_items ) : ?>

	<?php foreach( $cart_items as $key => $item ) : ?>

		<?php echo pdd_get_cart_item_template( $key, $item, false ); ?>

	<?php endforeach; ?>

	<?php pdd_get_template_part( 'widget', 'cart-checkout' ); ?>

<?php else : ?>

	<?php pdd_get_template_part( 'widget', 'cart-empty' ); ?>

<?php endif; ?>
</ul>