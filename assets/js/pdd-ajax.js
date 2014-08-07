var pdd_scripts;
jQuery( document ).ready( function( $ ) {

	// Hide unneeded elements. These are things that are required in case JS breaks or isn't present
	$( '.pdd-no-js' ).hide();
	$( 'a.pdd-add-to-cart' ).addClass( 'pdd-has-js' );

	// Send Remove from Cart requests
	$( 'body' ).on( 'click.pddRemoveFromCart', '.pdd-remove-from-cart', function( event ) {
		var $this = $( this ),
			item = $this.data( 'cart-item' ),
			action = $this.data( 'action' ),
			id = $this.data( 'download-id' ),
			data = {
				action: action,
				cart_item: item
			};

		$.ajax( {
			type: "POST",
			data: data,
			dataType: "json",
			url: pdd_scripts.ajaxurl,
			success: function( response ) {
				if ( response.removed ) {
					if ( parseInt( pdd_scripts.position_in_cart, 10 ) === parseInt( item, 10 ) ) {
						window.location = window.location;
						return false;
					}

					// Remove the selected cart item
					$( '.pdd-cart' ).find( "[data-cart-item='" + item + "']" ).parent().remove();

					// Check to see if the purchase form for this download is present on this page
					if ( $( '#pdd_purchase_' + id ).length ) {
						$( '#pdd_purchase_' + id + ' .pdd_go_to_checkout' ).hide();
						$( '#pdd_purchase_' + id + ' a.pdd-add-to-cart' ).show().removeAttr( 'data-pdd-loading' );
					}

					$( 'span.pdd-cart-quantity' ).each( function() {
						var quantity = parseInt( $( this ).text(), 10 ) - 1;
						if ( quantity < 1 ) {
							quantity = 0;
						}
						$( this ).text( quantity );
						$( 'body' ).trigger( 'pdd_quantity_updated', [ quantity ] );
					} );

					$( '.cart_item.pdd_subtotal span' ).html( response.subtotal );

					if ( !$( '.pdd-cart-item' ).length ) {
						$( '.cart_item.pdd_subtotal,.pdd-cart-number-of-items,.cart_item.pdd_checkout' ).hide();
						$( '.pdd-cart' ).append( '<li class="cart_item empty">' + pdd_scripts.empty_cart_message + '</li>' );
					}

					$( 'body' ).trigger( 'pdd_cart_item_removed', [ response ] );
				}
			}
		} ).fail( function( response ) {
			if ( window.console && window.console.log ) {
				console.log( response );
			}
		} ).done( function( response ) {

		} );

		return false;
	} )

		// Send Add to Cart request
		.on( 'click.pddAddToCart', '.pdd-add-to-cart', function( e ) {

			e.preventDefault();

			var $this = $( this ), form = $this.closest( 'form' );

			if ( 'straight_to_gateway' == form.find( '.pdd_action_input' ).val() ) {
				form.submit();
				return true; // Submit the form
			}

			var $spinner = $this.find( '.pdd-loading' );
			var container = $this.closest( 'div' );

			var spinnerWidth = $spinner.width(),
				spinnerHeight = $spinner.height();

			// Show the spinner
			$this.attr( 'data-pdd-loading', '' );

			$spinner.css( {
				'margin-left': spinnerWidth / -2,
				'margin-top': spinnerHeight / -2
			} );

			var form = $this.parents( 'form' ).last();
			var download = $this.data( 'download-id' );
			var variable_price = $this.data( 'variable-price' );
			var price_mode = $this.data( 'price-mode' );
			var item_price_ids = [];

			if ( variable_price == 'yes' ) {

				if ( !$( '.pdd_price_option_' + download + ':checked', form ).length ) {
					// hide the spinner
					$this.removeAttr( 'data-pdd-loading' );
					alert( pdd_scripts.select_option );
					return;
				}

				$( '.pdd_price_option_' + download + ':checked', form ).each( function( index ) {
					item_price_ids[ index ] = $( this ).val();
				} );

			} else {
				item_price_ids[0] = download;
			}

			var action = $this.data( 'action' );
			var data = {
				action: action,
				download_id: download,
				price_ids: item_price_ids,
				post_data: $( form ).serialize()
			};

			$.ajax( {
				type: "POST",
				data: data,
				dataType: "json",
				url: pdd_scripts.ajaxurl,
				success: function( response ) {

					if ( pdd_scripts.redirect_to_checkout == '1' ) {

						window.location = pdd_scripts.checkout_page;

					} else {

						// Add the new item to the cart widget
						if ( $( '.cart_item.empty' ).length ) {
							$( response.cart_item ).insertBefore( '.cart_item.pdd_subtotal' );
							$( '.cart_item.pdd_checkout,.cart_item.pdd_subtotal' ).show();
							$( '.cart_item.empty' ).remove();
						} else {
							$( response.cart_item ).insertBefore( '.cart_item.pdd_subtotal' );
						}

						$( '.cart_item.pdd_subtotal span' ).html( response.subtotal );

						// Update the cart quantity
						$( 'span.pdd-cart-quantity' ).each( function() {
							var quantity = parseInt( $( this ).text(), 10 ) + 1;
							$( this ).text( quantity );
							$( 'body' ).trigger( 'pdd_quantity_updated', [ quantity ] );
						} );

						// Show the "number of items in cart" message
						if ( $( '.pdd-cart-number-of-items' ).css( 'display' ) == 'none' ) {
							$( '.pdd-cart-number-of-items' ).show( 'slow' );
						}

						if ( variable_price == 'no' || price_mode != 'multi' ) {
							// Switch purchase to checkout if a single price item or variable priced with radio buttons
							$( 'a.pdd-add-to-cart', container ).toggle();
							$( '.pdd_go_to_checkout', container ).css( 'display', 'inline-block' );
						}

						if ( price_mode == 'multi' ) {
							// remove spinner for multi
							$this.removeAttr( 'data-pdd-loading' );
						}

						// Update all buttons for same download
						if ( $( '.pdd_camp_purchase_form' ).length ) {
							var parent_form = $( '.pdd_camp_purchase_form *[data-download-id="' + download + '"]' ).parents( 'form' );
							$( 'a.pdd-add-to-cart', parent_form ).hide();
							$( '.pdd_go_to_checkout', parent_form ).show().removeAttr( 'data-pdd-loading' );
						}

						if ( response != 'incart' ) {
							// Show the added message
							$( '.pdd-cart-added-alert', container ).fadeIn();
							setTimeout( function() {
								$( '.pdd-cart-added-alert', container ).fadeOut();
							}, 3000 );
						}

						$( 'body' ).trigger( 'pdd_cart_item_added', [ response ] );

					}
				}
			} ).fail( function( response ) {
				if ( window.console && window.console.log ) {
					console.log( response );
				}
			} ).done( function( response ) {

			} );

			return false;
		} );

	$( 'div.pdd_price_options :input' ).on( 'change', function() {
		var $this_form = $( this ).closest( 'form.pdd_camp_purchase_form' ),
			$custom_amount_radio = $( 'input.pdd_custom_amount_radio', $this_form );

		if ( $custom_amount_radio.prop( 'checked' ) ) {
			$( 'div.pdd-custom-amount-wrapper', $this_form ).fadeIn();
		} else {
			$( 'div.pdd-custom-amount-wrapper', $this_form ).fadeOut( 'fast' );
		}
	} )
		.trigger( 'change' );

	$( '.pdd-add-to-cart' ).on( 'click', function( e ) {
		$( 'div.pdd_errors.pdd_custom_amount_error' ).remove();
		
		var $this_form = $( this ).closest( 'form.pdd_camp_purchase_form' ),
			$custom_amount_radio = $( 'input.pdd_custom_amount_radio', $this_form ),
			$custom_amount = $( 'input.pdd_custom_amount', $this_form );

		if ( 'yes' === $( this ).data( 'variable-price' ) && ! $custom_amount_radio.prop( 'checked' ) ) {
			return true;
		}

		var min_amount = parseInt( $custom_amount.data( 'min_amount' ), 10 );

		if ( isNaN( min_amount ) ) {
			return true; // Custom price isn't enabled
		}

		if ( $custom_amount.val() >= min_amount ) {
			return true;
		}
		
		$( $this_form ).append( '<div class="pdd_errors pdd_custom_amount_error"><p class="pdd_error">Please enter a custom price higher than the minimum amount.</p></div>' );
		
		return false;
	} );

	// Show the login form on the checkout page
	$( '#pdd_checkout_form_wrap' ).on( 'click', '.pdd_checkout_register_login', function() {
		var $this = $( this ),
			data = {
				action: $this.data( 'action' )
			};
		// Show the ajax loader
		$( '.pdd-cart-ajax' ).show();

		$.post( pdd_scripts.ajaxurl, data, function( checkout_response ) {
			$( '#pdd_checkout_login_register' ).html( pdd_scripts.loading );
			$( '#pdd_checkout_login_register' ).html( checkout_response );
			// Hide the ajax loader
			$( '.pdd-cart-ajax' ).hide();
		} );
		return false;
	} );

	// Process the login form via ajax
	$( document ).on( 'click', '#pdd_purchase_form #pdd_login_fields input[type=submit]', function( e ) {

		e.preventDefault();

		var complete_purchase_val = $( this ).val();

		$( this ).val( pdd_global_vars.purchase_loading );

		$( this ).after( '<span class="pdd-cart-ajax"><i class="pdd-icon-spinner pdd-icon-spin"></i></span>' );

		var data = {
			action: 'pdd_process_checkout_login',
			pdd_ajax: 1,
			pdd_user_login: $( '#pdd_login_fields #pdd_user_login' ).val(),
			pdd_user_pass: $( '#pdd_login_fields #pdd_user_pass' ).val()
		};

		$.post( pdd_global_vars.ajaxurl, data, function( data ) {

			if ( $.trim( data ) == 'success' ) {
				$( '.pdd_errors' ).remove();
				window.location = pdd_scripts.checkout_page;
			} else {
				$( '#pdd_login_fields input[type=submit]' ).val( complete_purchase_val );
				$( '.pdd-cart-ajax' ).remove();
				$( '.pdd_errors' ).remove();
				$( '#pdd-user-login-submit' ).before( data );
			}
		} );

	} );

	// Load the fields for the selected payment method
	$( 'select#pdd-gateway, input.pdd-gateway' ).change( function( e ) {

		var payment_mode = $( '#pdd-gateway option:selected, input.pdd-gateway:checked' ).val();

		if ( payment_mode == '0' )
			return false;

		pdd_load_gateway( payment_mode );

		return false;
	} );

	// Auto load first payment gateway
	if ( pdd_scripts.is_checkout == '1' && $( 'select#pdd-gateway, input.pdd-gateway' ).length ) {
		setTimeout( function() {
			pdd_load_gateway( pdd_scripts.default_gateway );
		}, 200 );
	}

	$( document ).on( 'click', '#pdd_purchase_form #pdd_purchase_submit input[type=submit]', function( e ) {

		e.preventDefault();

		var complete_purchase_val = $( this ).val();

		$( this ).val( pdd_global_vars.purchase_loading );

		$( this ).after( '<span class="pdd-cart-ajax"><i class="pdd-icon-spinner pdd-icon-spin"></i></span>' );

		$.post( pdd_global_vars.ajaxurl, $( '#pdd_purchase_form' ).serialize() + '&action=pdd_process_checkout&pdd_ajax=true', function( data ) {
			if ( $.trim( data ) == 'success' ) {
				$( '.pdd_errors' ).remove();
				$( '#pdd_purchase_form' ).submit();
			} else {
				$( '#pdd_purchase_form #pdd-purchase-button' ).val( complete_purchase_val );
				$( '.pdd-cart-ajax' ).remove();
				$( '.pdd_errors' ).remove();
				$( '#pdd_purchase_submit' ).before( data );
			}
		} );

	} );

} );

function pdd_load_gateway( payment_mode ) {

	// Show the ajax loader
	jQuery( '.pdd-cart-ajax' ).show();
	jQuery( '#pdd_purchase_form_wrap' ).html( '<img src="' + pdd_scripts.ajax_loader + '"/>' );

	jQuery.post( pdd_scripts.ajaxurl + '?payment-mode=' + payment_mode, {
			action: 'pdd_load_gateway',
			pdd_payment_mode: payment_mode
		},
		function( response ) {
			jQuery( '#pdd_purchase_form_wrap' ).html( response );
			jQuery( '.pdd-no-js' ).hide();
			jQuery( '#pdd_checkout_wrap' ).trigger( 'pdd_purchase_form_updated' );
		}
	);

}
