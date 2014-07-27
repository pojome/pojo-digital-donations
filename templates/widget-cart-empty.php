<li class="cart_item empty"><?php echo pdd_empty_cart_message(); ?></li>
<li class="cart_item pdd_subtotal" style="display:none;"><?php echo __( 'Subtotal:', 'pdd' ). " <span class='subtotal'>" . pdd_currency_filter( pdd_get_cart_subtotal() ); ?></span></li>
<li class="cart_item pdd_checkout" style="display:none;"><a href="<?php echo pdd_get_checkout_uri(); ?>"><?php _e( 'Checkout', 'pdd' ); ?></a></li>
