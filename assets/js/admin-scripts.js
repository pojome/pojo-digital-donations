jQuery(document).ready(function ($) {

	/**
	 * Download Configuration Metabox
	 */
	var PDD_Download_Configuration = {
		init : function() {
			this.add();
			this.move();
			this.remove();
			this.type();
			this.prices();
			this.files();
			this.updatePrices();
		},
		clone_repeatable : function(row) {

			clone = row.clone();

			/** manually update any select box values */
			clone.find( 'select' ).each(function() {
				$( this ).val( row.find( 'select[name="' + $( this ).attr( 'name' ) + '"]' ).val() );
			});

			var count  = row.parent().find( 'tr' ).length - 1;

			clone.removeClass( 'pdd_add_blank' );

			clone.find( 'td input, td select' ).val( '' );
			clone.find( 'input, select' ).each(function() {
				var name 	= $( this ).attr( 'name' );

				name = name.replace( /\[(\d+)\]/, '[' + parseInt( count ) + ']');

				$( this ).attr( 'name', name ).attr( 'id', name );
			});

			return clone;
		},

		add : function() {
			$( 'body' ).on( 'click', '.submit .pdd_add_repeatable', function(e) {
				e.preventDefault();
				var button = $( this ),
				row = button.parent().parent().prev( 'tr' ),
				clone = PDD_Download_Configuration.clone_repeatable(row);
				clone.insertAfter( row );
			});
		},

		move : function() {
			/*
			* Disabled until we can work out a way to solve the issues raised here: https://github.com/easydigitaldownloads/Easy-Digital-Downloads/issues/1066
			if( ! $('.pdd_repeatable_table').length )
				return;

			$(".pdd_repeatable_table tbody").sortable({
				handle: '.pdd_draghandle', items: '.pdd_repeatable_row', opacity: 0.6, cursor: 'move', axis: 'y', update: function() {
					var count  = 0;
					$(this).find( 'tr' ).each(function() {
						$(this).find( 'input, select' ).each(function() {
							var name   = $( this ).attr( 'name' );
							name       = name.replace( /\[(\d+)\]/, '[' + count + ']');
							$( this ).attr( 'name', name ).attr( 'id', name );
						});
						count++;
					});
				}
			});
			*/
		},

		remove : function() {
			$( 'body' ).on( 'click', '.pdd_remove_repeatable', function(e) {
				e.preventDefault();

				var row   = $(this).parent().parent( 'tr' ),
					count = row.parent().find( 'tr' ).length - 1,
					type  = $(this).data('type'),
					repeatable = 'tr.pdd_repeatable_' + type + 's';

				/** remove from price condition */
			    $( '.pdd_repeatable_condition_field option[value=' + row.index() + ']' ).remove();

				if( count > 1 ) {
					$( 'input, select', row ).val( '' );
					row.fadeOut( 'fast' ).remove();
				} else {
					switch( type ) {
						case 'price' :
							alert( pdd_vars.one_price_min );
							break;
						case 'file' :
							alert( pdd_vars.one_file_min );
							break;
						default:
							alert( pdd_vars.one_field_min );
							break;
					}
				}

				/* re-index after deleting */
			    $(repeatable).each( function( rowIndex ) {
			        $(this).find( 'input, select' ).each(function() {
			        	var name = $( this ).attr( 'name' );
			        	name = name.replace( /\[(\d+)\]/, '[' + rowIndex+ ']');
			        	$( this ).attr( 'name', name ).attr( 'id', name );
			    	});
			    });
			});
		},

		type : function() {

			$( 'body' ).on( 'change', '#_pdd_product_type', function(e) {

				if ( 'bundle' === $( this ).val() ) {
					$( '#pdd_products' ).show();
					$( '#pdd_camp_files' ).hide();
					$( '#pdd_camp_limit_wrap' ).hide();
				} else {
					$( '#pdd_products' ).hide();
					$( '#pdd_camp_files' ).show();
					$( '#pdd_camp_limit_wrap' ).show();
				}

			});

		},

		prices : function() {
			$( 'body' ).on( 'change', '#pdd_variable_pricing', function( e ) {
				$( '.pdd_pricing_fields, .pdd_repeatable_table .pricing' ).toggle();
			} )
			
				.on( 'change', '#pdd_custom_amount', function( e ) {
					$( '#pdd_custom_price_fields' ).toggle();
				} );
		},

		files : function() {
			if( typeof wp === "undefined" || '1' !== pdd_vars.new_media_ui ){
				//Old Thickbox uploader
				if ( $( '.pdd_upload_file_button' ).length > 0 ) {
					window.formfield = '';

					$('body').on('click', '.pdd_upload_file_button', function(e) {
						e.preventDefault();
						window.formfield = $(this).parent().prev();
						window.tbframe_interval = setInterval(function() {
							jQuery('#TB_iframeContent').contents().find('.savesend .button').val(pdd_vars.use_this_file).end().find('#insert-gallery, .wp-post-thumbnail').hide();
						}, 2000);
						if (pdd_vars.post_id != null ) {
							var post_id = 'post_id=' + pdd_vars.post_id + '&';
						}
						tb_show(pdd_vars.add_new_download, 'media-upload.php?' + post_id +'TB_iframe=true');
					});

					window.pdd_send_to_editor = window.send_to_editor;
					window.send_to_editor = function (html) {
						if (window.formfield) {
							imgurl = $('a', '<div>' + html + '</div>').attr('href');
							window.formfield.val(imgurl);
							window.clearInterval(window.tbframe_interval);
							tb_remove();
						} else {
							window.pdd_send_to_editor(html);
						}
						window.send_to_editor = window.pdd_send_to_editor;
						window.formfield = '';
						window.imagefield = false;
					};
				}
			} else {
				// WP 3.5+ uploader
				var file_frame;
				window.formfield = '';

				$('body').on('click', '.pdd_upload_file_button', function(e) {

					e.preventDefault();

					var button = $(this);

					window.formfield = $(this).closest('.pdd_repeatable_upload_wrapper');

					// If the media frame already exists, reopen it.
					if ( file_frame ) {
						//file_frame.uploader.uploader.param( 'post_id', set_to_post_id );
						file_frame.open();
						return;
					}

					// Create the media frame.
					file_frame = wp.media.frames.file_frame = wp.media( {
						frame: 'post',
						state: 'insert',
						title: button.data( 'uploader_title' ),
						button: {
							text: button.data( 'uploader_button_text' )
						},
						multiple: $( this ).data( 'multiple' ) == '0' ? false : true  // Set to true to allow multiple files to be selected
					} );

					file_frame.on( 'menu:render:default', function( view ) {
						// Store our views in an object.
						var views = {};

						// Unset default menu items
						view.unset( 'library-separator' );
						view.unset( 'gallery' );
						view.unset( 'featured-image' );
						view.unset( 'embed' );

						// Initialize the views in our view object.
						view.set( views );
					} );

					// When an image is selected, run a callback.
					file_frame.on( 'insert', function() {

						var selection = file_frame.state().get('selection');
						selection.each( function( attachment, index ) {
							attachment = attachment.toJSON();
							if ( 0 === index ) {
								// place first attachment in field
								window.formfield.find( '.pdd_repeatable_attachment_id_field' ).val( attachment.id );
								window.formfield.find( '.pdd_repeatable_upload_field' ).val( attachment.url );
								window.formfield.find( '.pdd_repeatable_name_field' ).val( attachment.title );
							} else {
								// Create a new row for all additional attachments
								var row = window.formfield,
									clone = PDD_Download_Configuration.clone_repeatable( row );

								clone.find( '.pdd_repeatable_attachment_id_field' ).val( attachment.id );
								clone.find( '.pdd_repeatable_upload_field' ).val( attachment.url );
								if ( attachment.title.length > 0 ) {
									clone.find( '.pdd_repeatable_name_field' ).val( attachment.title );
								} else {
									clone.find( '.pdd_repeatable_name_field' ).val( attachment.filename );
								}
								clone.insertAfter( row );
							}
						});
					});

					// Finally, open the modal
					file_frame.open();
				});


				// WP 3.5+ uploader
				var file_frame;
				window.formfield = '';
			}

		},

		updatePrices: function() {
			$( '#pdd_price_fields' ).on( 'keyup', '.pdd_variable_prices_name', function() {

				var key = $( this ).parents( 'tr' ).index(),
					name = $( this ).val(),
					field_option = $( '.pdd_repeatable_condition_field option[value=' + key + ']' );

				if ( field_option.length > 0 ) {
					field_option.text( name );
				} else {
					$( '.pdd_repeatable_condition_field' ).append(
						$( '<option></option>' )
							.attr( 'value', key )
							.text( name )
					);
				}
			} );
		}

	};

	PDD_Download_Configuration.init();

	//$('#edit-slug-box').remove();

	// Date picker
	if ( $( '.pdd_datepicker' ).length > 0 ) {
		var dateFormat = 'mm/dd/yy';
		$( '.pdd_datepicker' ).datepicker( {
			dateFormat: dateFormat
		} );
	}

	/**
	 * Edit payment screen JS
	 */
	var PDD_Edit_Payment = {

		init : function() {
			this.edit_address();
			this.remove_download();
			this.add_download();
			this.recalculate_total();
			this.variable_prices_check();
			this.add_note();
			this.remove_note();
			this.resend_receipt();
			this.copy_download_link();
		},


		edit_address : function() {

			// Update base state field based on selected base country
			$('select[name="pdd-payment-address[0][country]"]').change(function() {
				var $this = $(this);
				data = {
					action: 'pdd_get_shop_states',
					country: $this.val(),
					field_name: 'pdd-payment-address[0][state]'
				};
				$.post(ajaxurl, data, function (response) {
					if( 'nostates' == response ) {
						$('#pdd-order-address-state-wrap select, #pdd-order-address-state-wrap input').replaceWith( '<input type="text" name="pdd-payment-address[0][state]" value="" class="pdd-edit-toggles medium-text"/>' );
					} else {
						$('#pdd-order-address-state-wrap select, #pdd-order-address-state-wrap input').replaceWith( response );
					}
				});

				return false;
			});

		},

		remove_download : function() {

			// Remove a download from a purchase
			$('#pdd-purchased-files').on('click', '.pdd-order-remove-download', function() {
				if( confirm( pdd_vars.delete_payment_download ) ) {
					$(this).parent().parent().parent().remove();
					// Flag the Downloads section as changed
					$('#pdd-payment-downloads-changed').val(1);
					$('.pdd-order-payment-recalc-totals').show();
				}
				return false;
			});

		},


		add_download : function() {

			// Add a New Download from the Add Downloads to Purchase Box
			$('#pdd-purchased-files').on('click', '#pdd-order-add-download', function(e) {

				e.preventDefault();

				var download_id    = $('#pdd_order_download_select').val();
				var download_title = $('.chosen-single span').text();
				var amount         = $('#pdd-order-download-amount').val();
				var price_id       = $('.pdd_price_options_select option:selected').val();
				var price_name     = $('.pdd_price_options_select option:selected').text();
				var quantity       = $('#pdd-order-download-quantity').val();

				if( download_id < 1 ) {
					return false;
				}

				if( ! amount ) {
					amount = '0.00';
				}

				var formatted_amount = amount + pdd_vars.currency_sign;
				if ( 'before' === pdd_vars.currency_pos ) {
					formatted_amount = pdd_vars.currency_sign + amount;
				}

				if( price_name ) {
					download_title = download_title + ' - ' + price_name;
				}

				var count = $('#pdd-purchased-files div.row').length;
				var clone = $('#pdd-purchased-files div.row:last').clone();

				clone.find( '.download span' ).html( '<a href="post.php?post=' + download_id + '&action=edit"></a>' );
				clone.find( '.download span a' ).text( download_title );
				clone.find( '.price' ).text( formatted_amount );
				clone.find( '.quantity span' ).text( quantity );
				clone.find( 'input.pdd-payment-details-download-id' ).val( download_id );
				clone.find( 'input.pdd-payment-details-download-price-id' ).val( price_id );
				clone.find( 'input.pdd-payment-details-download-amount' ).val( amount );
				clone.find( 'input.pdd-payment-details-download-quantity' ).val( quantity );

				// Replace the name / id attributes
				clone.find( 'input' ).each(function() {
					var name = $( this ).attr( 'name' );

					name = name.replace( /\[(\d+)\]/, '[' + parseInt( count ) + ']');

					$( this ).attr( 'name', name ).attr( 'id', name );
				});

				// Flag the Downloads section as changed
				$('#pdd-payment-downloads-changed').val(1);

				$(clone).insertAfter( '#pdd-purchased-files div.row:last' );
				$('.pdd-order-payment-recalc-totals').show();

			});
		},

		recalculate_total : function() {

			// Remove a download from a purchase
			$('#pdd-order-recalc-total').on('click', function(e) {
				e.preventDefault();
				var total = 0;
				if( $('#pdd-purchased-files .row .pdd-payment-details-download-amount').length ) {
					$('#pdd-purchased-files .row .pdd-payment-details-download-amount').each(function() {
						var quantity = $(this).next().val();
						if( quantity ) {
							total += ( parseFloat( $(this).val() ) * parseInt( quantity ) );
						} else {
							total += parseFloat( $(this).val() );
						}
					});
				}
				if( $('.pdd-payment-fees').length ) {
					$('.pdd-payment-fees span.fee-amount').each(function() {
						total += parseFloat( $(this).data('fee') );
					});
				}
				$('input[name=pdd-payment-total]').val( total );
			});

		},

		variable_prices_check : function() {

			// On Download Select, Check if Variable Prices Exist
			$('#pdd-purchased-files').on('change', 'select#pdd_order_download_select', function() {

				var $this = $(this), download_id = $this.val();

				if(parseInt(download_id) > 0) {
					var postData = {
						action : 'pdd_check_for_download_price_variations',
						download_id: download_id
					};

					$.ajax({
						type: "POST",
						data: postData,
						url: ajaxurl,
						success: function (response) {
							$('.pdd_price_options_select').remove();
							$(response).insertAfter( $this.next() );
						}
					}).fail(function (data) {
						if ( window.console && window.console.log ) {
							console.log( data );
						}
					});

				}
			});

		},

		add_note : function() {

			$('#pdd-add-payment-note').on('click', function(e) {
				e.preventDefault();
				var postData = {
					action : 'pdd_insert_payment_note',
					payment_id : $(this).data('payment-id'),
					note : $('#pdd-payment-note').val()
				};

				if( postData.note ) {

					$.ajax({
						type: "POST",
						data: postData,
						url: ajaxurl,
						success: function (response) {
							$('#pdd-payment-notes-inner').append( response );
							$('.pdd-no-payment-notes').hide();
							$('#pdd-payment-note').val('');
						}
					}).fail(function (data) {
						if ( window.console && window.console.log ) {
							console.log( data );
						}
					});

				} else {
					var border_color = $('#pdd-payment-note').css('border-color');
					$('#pdd-payment-note').css('border-color', 'red');
					setTimeout( function() {
						$('#pdd-payment-note').css('border-color', border_color );
					}, 500 );
				}

			});

		},

		remove_note : function() {

			$('body').on('click', '.pdd-delete-payment-note', function(e) {

				e.preventDefault();

				if( confirm( pdd_vars.delete_payment_note) ) {

					var postData = {
						action : 'pdd_delete_payment_note',
						payment_id : $(this).data('payment-id'),
						note_id : $(this).data('note-id')
					};

					$.ajax({
						type: "POST",
						data: postData,
						url: ajaxurl,
						success: function (response) {
							$('#pdd-payment-note-' + postData.note_id ).remove();
							if( ! $('.pdd-payment-note').length ) {
								$('.pdd-no-payment-notes').show();
							}
							return false;
						}
					}).fail(function (data) {
						if ( window.console && window.console.log ) {
							console.log( data );
						}
					});
					return true;
				}

			});

		},

		resend_receipt : function() {
			$( 'body' ).on( 'click', '#pdd-resend-receipt', function( e ) {
				return confirm( pdd_vars.resend_receipt );
			} );
		},

		copy_download_link : function() {
			$( 'body' ).on( 'click', '.pdd-copy-download-link', function( e ) {
				e.preventDefault();
				var $this    = $(this);
				var postData = {
					action      : 'pdd_get_file_download_link',
					payment_id  : $('input[name="pdd_payment_id"]').val(),
					download_id : $this.data('download-id'),
					price_id    : $this.data('price-id')
				};

				$.ajax({
					type: "POST",
					data: postData,
					url: ajaxurl,
					success: function (link) {
						$( "#pdd-download-link" ).dialog({
							width: 400
						}).html( '<textarea rows="10" cols="40" id="pdd-download-link-textarea">' + link + '</textarea>' );
						$( "#pdd-download-link-textarea" ).focus().select();
						return false;
					}
				}).fail(function (data) {
					if ( window.console && window.console.log ) {
						console.log( data );
					}
				});

			} );
		}

	};
	PDD_Edit_Payment.init();


	/**
	 * Discount add / edit screen JS
	 */
	var PDD_Discount = {

		init : function() {
			this.type_select();
			this.product_requirements();
		},

		type_select : function() {

			$('#pdd-edit-discount #pdd-type, #pdd-add-discount #pdd-type').change(function() {

				$('.pdd-amount-description').toggle();

			});

		},

		product_requirements : function() {

			$('#products').change(function() {

				if( $(this).val() ) {

					$('#pdd-discount-product-conditions').show();

				} else {

					$('#pdd-discount-product-conditions').hide();
					
				}

			});

		},

	};
	PDD_Discount.init();


	/**
	 * Reports / Exports screen JS
	 */
	var PDD_Reports = {

		init : function() {
			this.date_options();
			this.customers_export();
		},

		date_options : function() {

			// Show hide extended date options
			$( '#pdd-graphs-date-options' ).change( function() {
				var $this = $(this);
				if ( 'other' === $this.val() ) {
					$( '#pdd-date-range-options' ).show();
				} else {
					$( '#pdd-date-range-options' ).hide();
				}
			});

		},

		customers_export : function() {

			// Show / hide Download option when exporting customers

			$( '#pdd_customer_export_download' ).change( function() {

				var $this = $(this), download_id = $('option:selected', $this).val();

				if ( '0' === $this.val() ) {
					$( '#pdd_customer_export_option' ).show();
				} else {
					$( '#pdd_customer_export_option' ).hide();
				}

				// On Download Select, Check if Variable Prices Exist
				if ( parseInt( download_id ) != 0 ) {
					var data = {
						action : 'pdd_check_for_download_price_variations',
						download_id: download_id
					};
					$.post(ajaxurl, data, function(response) {
						$('.pdd_price_options_select').remove();
						$this.after( response );
					});
				} else {
					$('.pdd_price_options_select').remove();
				}
			});

		}

	};
	PDD_Reports.init();


	/**
	 * Settings screen JS
	 */
	var PDD_Settings = {

		init : function() {
			this.general();
			this.taxes();
			this.emails();
			this.misc();
		},

		general : function() {

			if( $('.pdd-color-picker').length ) {
				$('.pdd-color-picker').wpColorPicker();
			}

			// Settings Upload field JS
			if ( typeof wp === "undefined" || '1' !== pdd_vars.new_media_ui ) {
				//Old Thickbox uploader
				if ( $( '.pdd_settings_upload_button' ).length > 0 ) {
					window.formfield = '';

					$('body').on('click', '.pdd_settings_upload_button', function(e) {
						e.preventDefault();
						window.formfield = $(this).parent().prev();
						window.tbframe_interval = setInterval(function() {
							jQuery('#TB_iframeContent').contents().find('.savesend .button').val(pdd_vars.use_this_file).end().find('#insert-gallery, .wp-post-thumbnail').hide();
						}, 2000);
						tb_show( pdd_vars.add_new_download, 'media-upload.php?TB_iframe=true' );
					});

					window.pdd_send_to_editor = window.send_to_editor;
					window.send_to_editor = function (html) {
						if (window.formfield) {
							imgurl = $('a', '<div>' + html + '</div>').attr('href');
							window.formfield.val(imgurl);
							window.clearInterval(window.tbframe_interval);
							tb_remove();
						} else {
							window.pdd_send_to_editor(html);
						}
						window.send_to_editor = window.pdd_send_to_editor;
						window.formfield = '';
						window.imagefield = false;
					};
				}
			} else {
				// WP 3.5+ uploader
				var file_frame;
				window.formfield = '';

				$('body').on('click', '.pdd_settings_upload_button', function(e) {

					e.preventDefault();

					var button = $(this);

					window.formfield = $(this).parent().prev();

					// If the media frame already exists, reopen it.
					if ( file_frame ) {
						//file_frame.uploader.uploader.param( 'post_id', set_to_post_id );
						file_frame.open();
						return;
					}

					// Create the media frame.
					file_frame = wp.media.frames.file_frame = wp.media({
						frame: 'post',
						state: 'insert',
						title: button.data( 'uploader_title' ),
						button: {
							text: button.data( 'uploader_button_text' )
						},
						multiple: false
					});

					file_frame.on( 'menu:render:default', function( view ) {
						// Store our views in an object.
						var views = {};

						// Unset default menu items
						view.unset( 'library-separator' );
						view.unset( 'gallery' );
						view.unset( 'featured-image' );
						view.unset( 'embed' );

						// Initialize the views in our view object.
						view.set( views );
					} );

					// When an image is selected, run a callback.
					file_frame.on( 'insert', function() {

						var selection = file_frame.state().get('selection');
						selection.each( function( attachment, index ) {
							attachment = attachment.toJSON();
							window.formfield.val(attachment.url);
						});
					});

					// Finally, open the modal
					file_frame.open();
				});


				// WP 3.5+ uploader
				var file_frame;
				window.formfield = '';
			}

		},

		taxes : function() {

			if( $('select.pdd-no-states').length ) {
				$('select.pdd-no-states').closest('tr').hide();
			}

			// Update base state field based on selected base country
			$('select[name="pdd_settings[base_country]"]').change(function() {
				var $this = $(this), $tr = $this.closest('tr');
				data = {
					action: 'pdd_get_shop_states',
					country: $(this).val(),
					field_name: 'pdd_settings[base_state]'
				};
				$.post(ajaxurl, data, function (response) {
					if( 'nostates' == response ) {
						$tr.next().hide();
					} else {
						$tr.next().show();
						$tr.next().find('select').replaceWith( response );
					}
				});

				return false;
			});

			// Update tax rate state field based on selected rate country
			$('body').on('change', '#pdd_tax_rates select.pdd-tax-country', function() {
				var $this = $(this);
				data = {
					action: 'pdd_get_shop_states',
					country: $(this).val(),
					field_name: $this.attr('name').replace('country', 'state')
				};
				$.post(ajaxurl, data, function (response) {
					if( 'nostates' == response ) {
						var text_field = '<input type="text" name="' + data.field_name + '" value=""/>';
						$this.parent().next().find('select').replaceWith( text_field );
					} else {
						$this.parent().next().find('input,select').show();
						$this.parent().next().find('input,select').replaceWith( response );
					}
				});

				return false;
			});

			// Insert new tax rate row
			$('#pdd_add_tax_rate').on('click', function() {
				var row = $('#pdd_tax_rates tr:last');
				var clone = row.clone();
				var count = row.parent().find( 'tr' ).length;
				clone.find( 'td input' ).val( '' );
				clone.find( 'input, select' ).each(function() {
					var name = $( this ).attr( 'name' );
					name = name.replace( /\[(\d+)\]/, '[' + parseInt( count ) + ']');
					$( this ).attr( 'name', name ).attr( 'id', name );
				});
				clone.insertAfter( row );
				return false;
			});

			// Remove tax row
			$('body').on('click', '#pdd_tax_rates .pdd_remove_tax_rate', function() {
				if( confirm( pdd_vars.delete_tax_rate ) )
					$(this).closest('tr').remove();
				return false;
			});

		},

		emails : function() {

			// Show the email template previews
			if( $('#email-preview-wrap').length ) {
				var emailPreview = $('#email-preview');
				$('#open-email-preview').colorbox({
					inline: true,
					href: emailPreview,
					width: '80%',
					height: 'auto'
				});
			}

		},

		misc : function() {

			// Hide Symlink option if Download Method is set to Direct
			if( $('select[name="pdd_settings[download_method]"]:selected').val() != 'direct' ) {
				$('select[name="pdd_settings[download_method]"]').parent().parent().next().hide();
				$('select[name="pdd_settings[download_method]"]').parent().parent().next().find('input').attr('checked', false);
			}
			// Toggle download method option
			$('select[name="pdd_settings[download_method]"]').on('change', function() {
				var symlink = $(this).parent().parent().next();
				if( $(this).val() == 'direct' ) {
					symlink.hide();
				} else {
					symlink.show();
					symlink.find('input').attr('checked', false);
				}
			});

		}

	}
	PDD_Settings.init();


	$('.download_page_pdd-payment-history .row-actions .delete a').on('click', function() {
		if( confirm( pdd_vars.delete_payment ) ) {
			return true;
		}
		return false;
	});


	$('#the-list').on('click', '.editinline', function() {
		inlineEditPost.revert();

		var post_id = $(this).closest('tr').attr('id');

		post_id = post_id.replace("post-", "");

		var $pdd_inline_data = $('#post-' + post_id);

		var regprice = $pdd_inline_data.find('.column-price .downloadprice-' + post_id).val();

		// If variable priced product disable editing, otherwise allow price changes
		if ( regprice != $('#post-' + post_id + '.column-price .downloadprice-' + post_id).val() ) {
			$('.regprice', '#pdd-download-data').val(regprice).attr('disabled', false);
		} else {
			$('.regprice', '#pdd-download-data').val( pdd_vars.quick_edit_warning ).attr('disabled', 'disabled');
		}
	});


    // Bulk edit save
    $( 'body' ).on( 'click', '#bulk_edit', function() {

		// define the bulk edit row
		var $bulk_row = $( '#bulk-edit' );

		// get the selected post ids that are being edited
		var $post_ids = new Array();
		$bulk_row.find( '#bulk-titles' ).children().each( function() {
			$post_ids.push( $( this ).attr( 'id' ).replace( /^(ttle)/i, '' ) );
		});

		// get the stock and price values to save for all the product ID's
		var $price = $( '#pdd-download-data input[name="_pdd_regprice"]' ).val();

		var data = {
			action: 		'pdd_save_bulk_edit',
			pdd_bulk_nonce:	$post_ids,
			post_ids:		$post_ids,
			price:			$price
		};

		// save the data
		$.post( ajaxurl, data );

	});

    // Setup Chosen menus
    $('.pdd-select-chosen').chosen({
    	inherit_select_classes: true,
    	placeholder_text_single: pdd_vars.one_option,
    	placeholder_text_multiple: pdd_vars.one_or_more_option,
    });

	// Variables for setting up the typing timer
	var typingTimer;               // Timer identifier
	var doneTypingInterval = 342;  // Time in ms, Slow - 521ms, Moderate - 342ms, Fast - 300ms

    // Replace options with search results
	$('.pdd-select.chosen-container .chosen-search input, .pdd-select.chosen-container .search-field input').keyup(function(e) {

		var val = $(this).val(), container = $(this).closest( '.pdd-select-chosen' );
		var menu_id = container.attr('id').replace( '_chosen', '' );
		var lastKey = e.which;

		// Don't fire if short or is a modifier key (shift, ctrl, apple command key, or arrow keys)
		if(
			val.length <= 3 ||
			(
				e.which == 16 ||
				e.which == 13 ||
				e.which == 91 ||
				e.which == 17 ||
				e.which == 37 ||
				e.which == 38 ||
				e.which == 39 ||
				e.which == 40
			)
		) {
			return;
		}
		
		clearTimeout(typingTimer);
		typingTimer = setTimeout(
			function(){
				$.ajax({
					type: 'GET',
					url: ajaxurl,
					data: {
						action: 'pdd_camp_search',
						s: val
					},
					dataType: "json",
					beforeSend: function(){
						$('ul.chosen-results').empty();
					},
					success: function( data ) {

						// Remove all options but those that are selected
					 	$('#' + menu_id + ' option:not(:selected)').remove();
						$.each( data, function( key, item ) {
						 	// Add any option that doesn't already exist
							if( ! $('#' + menu_id + ' option[value="' + item.id + '"]').length ) {
								$('#' + menu_id).prepend( '<option value="' + item.id + '">' + item.name + '</option>' );
							}
						});
						 // Update the options
						$('.pdd-select-chosen').trigger('chosen:updated');
						$('#' + menu_id).next().find('input').val(val);
					}
				}).fail(function (response) {
					if ( window.console && window.console.log ) {
						console.log( data );
					}
				}).done(function (response) {

		        });
			},
			doneTypingInterval
		);
	});

	// This fixes the Chosen box being 0px wide when the thickbox is opened
	$( '#post' ).on( 'click', '.pdd-thickbox', function() {
		$( '.pdd-select-chosen', '#choose-download' ).css( 'width', '100%' );
	});

	/**
	 * Tools screen JS
	 */
	var PDD_Tools = {

		init : function() {}
	};
	PDD_Tools.init();

	// Ajax user search
	$('.pdd-ajax-user-search').keyup(function() {
		var user_search = $(this).val();
		$('.pdd-ajax').show();
		data = {
			action: 'pdd_search_users',
			user_name: user_search
		};
		
		document.body.style.cursor = 'wait';

		$.ajax({
			type: "POST",
			data: data,
			dataType: "json",
			url: ajaxurl,
			success: function (search_response) {

				$('.pdd-ajax').hide();
				$('.pdd_user_search_results').html('');
				$(search_response.results).appendTo('.pdd_user_search_results');
				document.body.style.cursor = 'default';
			}
		});
	});
	$('body').on('click.pddSelectUser', '.pdd_user_search_results a', function(e) {
		e.preventDefault();
		var login = $(this).data('login');
		$('.pdd-ajax-user-search').val(login);
		$('.pdd_user_search_results').html('');
	});

});
