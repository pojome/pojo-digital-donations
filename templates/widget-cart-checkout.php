<li class="cart_item pdd_subtotal"><?php echo __( 'Subtotal:', 'pdd' ). " <span class='subtotal'>" . pdd_currency_filter( pdd_format_amount( pdd_get_cart_subtotal() ) ); ?></span></li>
<li class="cart_item pdd_checkout"><a href="<?php echo pdd_get_checkout_uri(); ?>"><?php _e( 'Checkout', 'pdd' ); ?></a></li>
