jQuery(document).ready(function($) {
    var $body = $('body'),
        $pdd_cart_amount = $('.pdd_cart_amount');

    // Update state/province field on checkout page
    $body.on('change', '#pdd_cc_address input.card_state, #pdd_cc_address select', function() {
        var $this = $(this);
        if( 'card_state' != $this.attr('id') ) {

            // If the country field has changed, we need to update the state/province field
            var postData = {
                action: 'pdd_get_shop_states',
                country: $this.val(),
                field_name: 'card_state'
            };

            $.ajax({
                type: "POST",
                data: postData,
                url: pdd_global_vars.ajaxurl,
                success: function (response) {
                    if( 'nostates' == response ) {
                        var text_field = '<input type="text" name="card_state" class="cart-state pdd-input required" value=""/>';
                        $this.parent().next().find('input,select').replaceWith( text_field );
                    } else {
                        $this.parent().next().find('input,select').replaceWith( response );
                    }
                    $('body').trigger('pdd_cart_billing_address_updated', [ response ]);
                }
            }).fail(function (data) {
                if ( window.console && window.console.log ) {
                    console.log( data );
                }
            }).done(function (data) {
                recalculate_taxes();
            });
        } else {
            recalculate_taxes();
        }

        return false;
    });

    function recalculate_taxes( state ) {
        if( '1' != pdd_global_vars.taxes_enabled )
            return; // Taxes not enabled

        var $pdd_cc_address = $('#pdd_cc_address');

        if( ! state ) {
            state = $pdd_cc_address.find('#card_state').val();
        }

        var postData = {
            action: 'pdd_recalculate_taxes',
            billing_country: $pdd_cc_address.find('#billing_country').val(),
            state: state
        };

        $.ajax({
            type: "POST",
            data: postData,
            dataType: "json",
            url: pdd_global_vars.ajaxurl,
            success: function (tax_response) {
                $('#pdd_checkout_cart').replaceWith(tax_response.html);
                $('.pdd_cart_amount').html(tax_response.total);
                var tax_data = new Object();
                tax_data.postdata = postData;
                tax_data.response = tax_response;
                $('body').trigger('pdd_taxes_recalculated', [ tax_data ]);
            }
        }).fail(function (data) {
            if ( window.console && window.console.log ) {
              console.log( data );
            }
        });
    }

    /* Credit card verification */

    $body.on('keyup', '.pdd-do-validate .card-number', function() {
        pdd_validate_card( $(this) );
    });

    function pdd_validate_card( field ) {
        var card_field = field;
        card_field.validateCreditCard(function(result) {
            var $card_type = $('.card-type');

            if(result.card_type == null) {
                $card_type.removeClass().addClass('off card-type');
                card_field.removeClass('valid');
                card_field.addClass('error');
            } else {
                $card_type.removeClass('off');
                $card_type.addClass( result.card_type.name );
                if (result.length_valid && result.luhn_valid) {
                    card_field.addClass('valid');
                    card_field.removeClass('error');
                } else {
                    card_field.removeClass('valid');
                    card_field.addClass('error');
                }
            }
        });
    }

    // Make sure a gateway is selected
    $body.on('submit', '#pdd_payment_mode', function() {
        var gateway = $('#pdd-gateway option:selected').val();
        if( gateway == 0 ) {
            alert( pdd_global_vars.no_gateway );
            return false;
        }
    });

    // Add a class to the currently selected gateway on click
    $body.on('click', '#pdd_payment_mode_select input', function() {
        $('#pdd_payment_mode_select label.pdd-gateway-option-selected').removeClass( 'pdd-gateway-option-selected' );
        $('#pdd_payment_mode_select input:checked').parent().addClass( 'pdd-gateway-option-selected' );
    });

    /* Discounts */
    var before_discount = $pdd_cart_amount.text(),
        $checkout_form_wrap = $('#pdd_checkout_form_wrap');

    // Validate and apply a discount
    $checkout_form_wrap.on('click', '.pdd-apply-discount', function (event) {

    	event.preventDefault();

        var $this = $(this),
            discount_code = $('#pdd-discount').val(),
            pdd_discount_loader = $('#pdd-discount-loader');

        if (discount_code == '' || discount_code == pdd_global_vars.enter_discount ) {
            return false;
        }

        var postData = {
            action: 'pdd_apply_discount',
            code: discount_code
        };

        $('#pdd-discount-error-wrap').html('').hide();
        pdd_discount_loader.show();

        $.ajax({
            type: "POST",
            data: postData,
            dataType: "json",
            url: pdd_global_vars.ajaxurl,
            success: function (discount_response) {
                if( discount_response ) {
                    if (discount_response.msg == 'valid') {
                        $('.pdd_cart_discount').html(discount_response.html);
                        $('.pdd_cart_discount_row').show();
                        $('.pdd_cart_amount').each(function() {
                            $(this).text(discount_response.total);
                        });
                        $('#pdd-discount', $checkout_form_wrap ).val('');

                        recalculate_taxes();

                    	if( '0.00' == discount_response.total_plain ) {

                    		$('#pdd_cc_fields,#pdd_cc_address').slideUp();
                    		$('input[name="pdd-gateway"]').val( 'manual' );

                    	} else {

                    		$('#pdd_cc_fields,#pdd_cc_address').slideDown();

                    	}

						$('body').trigger('pdd_discount_applied', [ discount_response ]);

                    } else {
                        $('#pdd-discount-error-wrap').html( '<span class="pdd_error">' + discount_response.msg + '</span>' );
                        $('#pdd-discount-error-wrap').show();
                        $('body').trigger('pdd_discount_invalid', [ discount_response ]);
                    }
                } else {
                    if ( window.console && window.console.log ) {
                        console.log( discount_response );
                    }
                    $('body').trigger('pdd_discount_failed', [ discount_response ]);
                }
                pdd_discount_loader.hide();
            }
        }).fail(function (data) {
            if ( window.console && window.console.log ) {
                console.log( data );
            }
        });

        return false;
    });

    // Prevent the checkout form from submitting when hitting Enter in the discount field
    $checkout_form_wrap.on('keypress', '#pdd-discount', function (event) {
        if (event.keyCode == '13') {
            return false;
        }
    });

    // Apply the discount when hitting Enter in the discount field instead
    $checkout_form_wrap.on('keyup', '#pdd-discount', function (event) {
        if (event.keyCode == '13') {
            $checkout_form_wrap.find('.pdd-apply-discount').trigger('click');
        }
    });

    // Remove a discount
    $body.on('click', '.pdd_discount_remove', function (event) {

        var $this = $(this), postData = {
            action: 'pdd_remove_discount',
            code: $this.data('code')
        };

        $.ajax({
            type: "POST",
            data: postData,
            dataType: "json",
            url: pdd_global_vars.ajaxurl,
            success: function (discount_response) {

                $('.pdd_cart_amount').each(function() {
                	if( pdd_global_vars.currency_sign + '0.00' == $(this).text() || '0.00' + pdd_global_vars.currency_sign == $(this).text() ) {
                		// We're removing a 100% discount code so we need to force the payment gateway to reload
                		window.location.reload();
                	}
                    $(this).text(discount_response.total);
                });

                $('.pdd_cart_discount').html(discount_response.html);

                if( ! discount_response.discounts ) {
                   $('.pdd_cart_discount_row').hide();
                }


                recalculate_taxes();

                $('#pdd_cc_fields,#pdd_cc_address').slideDown();

				$('body').trigger('pdd_discount_removed', [ discount_response ]);
            }
        }).fail(function (data) {
            if ( window.console && window.console.log ) {
                console.log( data );
            }
        });

        return false;
    });

    // When discount link is clicked, hide the link, then show the discount input and set focus.
    $body.on('click', '.pdd_discount_link', function(e) {
        e.preventDefault();
        $('.pdd_discount_link').parent().hide();
        $('#pdd-discount-code-wrap').show().find('#pdd-discount').focus();
    });

    // Hide / show discount fields for browsers without javascript enabled
    $body.find('#pdd-discount-code-wrap').hide();
    $body.find('#pdd_show_discount').show();

    // Update the checkout when item quantities are updated
    $('#pdd_checkout_cart').on('change', '.pdd-item-quantity', function (event) {

        var $this = $(this),
            quantity = $this.val(),
            download_id = $this.closest('tr.pdd_cart_item').data('download-id');

        var postData = {
            action: 'pdd_update_quantity',
            quantity: quantity,
            download_id: download_id
        };

        //pdd_discount_loader.show();

        $.ajax({
            type: "POST",
            data: postData,
            dataType: "json",
            url: pdd_global_vars.ajaxurl,
            success: function (response) {
                 $('.pdd_cart_amount').each(function() {
                    $(this).text(response.total);
                    $('body').trigger('pdd_quantity_updated', [ response ]);
                });
            }
        }).fail(function (data) {
            if ( window.console && window.console.log ) {
                console.log( data );
            }
        });

        return false;
    });

});
