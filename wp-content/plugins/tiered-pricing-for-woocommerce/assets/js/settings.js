jQuery( document ).ready(function() {

	jQuery('ul.wtp-inside-subtabs').show();

	if ( jQuery('#mainform').find( 'ul.wtp-inside-subtabs' ).hasClass('unpreventSubmitBtn') ) {
		jQuery('button.woocommerce-save-button').css('visibility', 'visible');
	} else {
		jQuery('button.woocommerce-save-button').on('click', function(r){
			r.preventDefault();
		});		
	}

	jQuery('#wc_settings_enable_tier_pricing').on('change', function(e){

		jQuery(this).parents('body').prepend(`<div class="wtp-overlay"></div>`);
		var checked = jQuery(this).is(':checked');
		var data = {
			'action': 'wtp_toggle',
			'general_tier_pricing': checked,
		};
		jQuery.post(
			ajaxurl,
			data,
			function(response) {

				if ( response === 'done' ) {
					if ( jQuery('.wtp-overlay').length > 0 ) {
						//setTimeout(function(){});
						jQuery('.wtp-overlay').fadeOut('500').remove();
						jQuery('button.woocommerce-save-button').trigger('click');
					}
				}

			}
		);
	});

	jQuery( '#add_user_based_mapping' ).click(

		function(e) {
			window.onbeforeunload = null;
			e.preventDefault();

			var general_user_discount       = jQuery( '#wc_settings_user_discount' ).val();
			var general_user_discount_text  = jQuery( '#wc_settings_user_discount option:selected' ).html();
			var general_user_min_quantity   = jQuery( '#wc_settings_user_start_qty' ).val();
			var general_user_max_quantity   = jQuery( '#wc_settings_user_end_qty' ).val();
			var general_user_discount_value = jQuery( '#wc_settings_user_discount_value' ).val();
			var general_user_discount_type  = jQuery( '#wc_settings_user_discount_type' ).val();
			var flag_1 = false;
			var flag_2 = false;
			var flag_3 = false;
			var $html = '';
			var switcher = '';
			var msg = `<p class="wtp-alert wtp-alert-danger">${wtp_admin_script.required_txt}</p>`;
			var rule_id = jQuery('#rule_id').val();

			//clear field response messages on click
			jQuery('.wtp-field-response').html('');

			if ( general_user_min_quantity != '' && general_user_min_quantity > 0 ) {
				flag_1 = true;
			} else {
				jQuery( '#wc_settings_user_start_qty' ).siblings('.wtp-field-response').html(msg);
			}

			if ( general_user_discount_value != '' && general_user_discount_value > 0 ) {
				flag_2 = true;
			} else {
				jQuery( '#wc_settings_user_discount_value' ).siblings('.wtp-field-response').html(msg);
			}

			if ( general_user_max_quantity == '' || general_user_max_quantity > 0 ) {
				flag_3 = true;
			} else {
				jQuery( '#wc_settings_user_end_qty' ).siblings('.wtp-field-response').html(msg);
			}

			if ( flag_1 && flag_2 && flag_3 ) {

				jQuery(this).parents('body').prepend(`<div class="wtp-overlay"></div>`);

				var data = {
					'action': 'wtp_user_discount_rule',
					'general_user_discount': general_user_discount,
					'general_user_discount_text': general_user_discount_text,
					'general_user_min_quantity': general_user_min_quantity,
					'general_user_max_quantity': general_user_max_quantity,
					'general_user_discount_value': general_user_discount_value,
					'general_user_discount_type': general_user_discount_type,
					'rule_id': rule_id
				};

				jQuery.post(
					ajaxurl,
					data,
					function(response) {

						if ( typeof response != 'undefined' && response != '' ) {
							response = JSON.parse( response );
							console.log( response );
							//console.log('foreach now');
							jQuery.each( response, function( k, v ) {
								//console.log( 'id is ' + k + ' and value is ' + v );

								if ( typeof v.disabled != 'undefined' && v.disabled == 'true' ) {
									switcher = `<a class="wtp_actions" id="general_user_rule_enable" data-id="${k}"> ${wtp_admin_script.enable_txt}</a>`;										
								} else {
									switcher = `<a class="wtp_actions" id="general_user_rule_disable" data-id="${k}"> ${wtp_admin_script.disable_txt}</a>`;
								}

								if ( v.max_qty == '' ) {
									max_qty = '&#8734;';
								} else {
									max_qty = v.max_qty;
								}

								$html += `<tr>
									<td class="wc-shipping-classes-blank-state col-1" data-value="${v.user}">${v.user_text}</td>
									<td class="wc-shipping-classes-blank-state col-2">${v.min_qty}</td>
									<td class="wc-shipping-classes-blank-state col-3">${max_qty}</td>
									<td class="wc-shipping-classes-blank-state col-4">${v.discount_value}</td>
									<td class="wc-shipping-classes-blank-state col-5">${v.discount_type}</td>
									<td>
										${switcher}
										| <a class="wtp_actions general_user_rule_delete" data-id="${k}">${wtp_admin_script.delete_txt}</a>
										| <a class="wtp_actions general_rule_edit" data-type="user" data-type="rule" data-id="${k}">${wtp_admin_script.edit_txt}</a>
									</td>
								</tr>`;

							});

							jQuery('#wtp-user-type-discounts').find('tr').removeClass('focusout');
							jQuery('#wtp-user-type-discounts').find('td').removeClass('focusin');
							jQuery('table').find('input[type="number"], select').removeClass('edit-field');							
							jQuery('#wtp-user-type-discounts').html( $html );

							jQuery( '#add_user_based_mapping' ).text( 'Add Rule' );
							if ( jQuery('#rule_id').length > 0 ) {
								jQuery('#rule_id').remove();
							}

							jQuery('#mainform')[0].reset();
							jQuery( '#wc_settings_user_discount' ).val('');							
							jQuery( '#wc_settings_user_start_qty' ).val('');
							jQuery( '#wc_settings_user_end_qty' ).val('');
							jQuery( '#wc_settings_user_discount_value' ).val('');
							jQuery( '#wc_settings_user_discount_type' ).val('');
							if ( jQuery('.wtp-overlay').length > 0 ) {
								//setTimeout(function(){});
								jQuery('.wtp-overlay').fadeOut('500').remove();
							}
						}

					}
				);
			}
		}
	);

	jQuery( "body" ).on(
		"click",
		".general_user_rule_delete" ,
		function() {

			var parent_this = this;
			var data        = {
				'action': 'wtp_general_user_rule_delete',
				'general_user_rule_id': jQuery( this ).attr( 'data-id' ),
			};

			jQuery(parent_this).parents('body').prepend(`<div class="wtp-overlay"></div>`);

			jQuery.post(
				ajaxurl,
				data,
				function(response) {

					if ( 'done' == response ) {

						jQuery( parent_this ).parents( 'tr' ).remove();
						if ( jQuery('.wtp-overlay').length > 0 ) {
							//setTimeout(function(){});
							jQuery('.wtp-overlay').fadeOut('500').remove();
						}
					}

				}
			);

		}
	);

	jQuery( "body" ).on(
		"click",
		"#general_user_rule_disable",
		function() {

			var parent_this = this;
			var data        = {
				'action': 'wtp_general_user_rule_disable',
				'general_user_rule_id': jQuery( this ).attr( 'data-id' ),
			};

			jQuery(parent_this).parents('body').prepend(`<div class="wtp-overlay"></div>`);

			jQuery.post(
				ajaxurl,
				data,
				function(response) {

					if ( 'done' == response ) {

						jQuery( parent_this ).text( wtp_admin_script.enable_txt );
						jQuery( parent_this ).attr( 'id', 'general_user_rule_enable' );
						if ( jQuery('.wtp-overlay').length > 0 ) {
							//setTimeout(function(){});
							jQuery('.wtp-overlay').fadeOut('500').remove();
						}
					}

				}
			);

		}
	);

	jQuery( "body" ).on(
		"click",
		"#general_user_rule_enable" ,
		function() {

			var parent_this = this;
			var data        = {
				'action': 'wtp_general_user_rule_enable',
				'general_user_rule_id': jQuery( this ).attr( 'data-id' )
			};

			jQuery(parent_this).parents('body').prepend(`<div class="wtp-overlay"></div>`);

			jQuery.post(
				ajaxurl,
				data,
				function(response) {

					if ( 'done' == response ) {

						jQuery( parent_this ).text( wtp_admin_script.disable_txt );
						jQuery( parent_this ).attr( 'id', 'general_user_rule_disable' );
						if ( jQuery('.wtp-overlay').length > 0 ) {
							//setTimeout(function(){});
							jQuery('.wtp-overlay').fadeOut('500').remove();
						}

					}

				}
			);

		}
	);

	jQuery( '#add_category_based_mapping' ).click(
		function(e) {
			window.onbeforeunload = null;
			e.preventDefault();
			//debugger;
			var general_user_category        	  = jQuery( '#wc_settings_category_user_category' ).val();
			var general_user_category_text        = jQuery( '#wc_settings_category_user_category option:selected' ).html();
			var general_category_min_quantity     = jQuery( '#wc_settings_category_min' ).val();
			var general_category_max_quantity     = jQuery( '#wc_settings_category_max' ).val();
			var general_category_discount_value   = jQuery( '#wc_settings_category_discount_value' ).val();
			var general_category_discount_type    = jQuery( '#wc_settings_category_discount_type' ).val();
			var flag_1 = false;
			var flag_2 = false;
			var flag_3 = false;
			var $html = '';
			var switcher = '';
			var msg = `<p class="wtp-alert wtp-alert-danger">${wtp_admin_script.required_txt}</p>`;
			var rule_id = jQuery('#rule_id').val();

			//clear field response messages on click
			jQuery('.wtp-field-response').html('');

			if ( general_category_min_quantity != '' && general_category_min_quantity > 0 ) {
				flag_1 = true;
			} else {
				jQuery( '#wc_settings_category_min' ).siblings('.wtp-field-response').html(msg);
			}

			if ( general_category_discount_value != '' && general_category_discount_value > 0 ) {
				flag_2 = true;
			} else {
				jQuery( '#wc_settings_category_discount_value' ).siblings('.wtp-field-response').html(msg);
			}

			if ( general_category_max_quantity == '' || general_category_max_quantity > 0 ) {
				flag_3 = true;
			} else {
				jQuery( '#wc_settings_category_max' ).siblings('.wtp-field-response').html(msg);
			}

			if ( flag_1 && flag_2 && flag_3 ) {

				jQuery(this).parents('body').prepend(`<div class="wtp-overlay"></div>`);

				var data = {

					'action': 'wtp_general_category_rule',
					'general_category_user_category': general_user_category,
					'general_category_user_category_text': general_user_category_text,
					'general_category_min_quantity': general_category_min_quantity,
					'general_category_max_quantity': general_category_max_quantity,
					'general_category_discount_value': general_category_discount_value,
					'general_category_discount_type': general_category_discount_type,
					'rule_id': rule_id

				};

				jQuery.post(
					ajaxurl,
					data,
					function(response) {

						if ( typeof response != 'undefined' && response != '' ) {
							response = JSON.parse( response );
							console.log( response );
							//console.log('foreach now');
							jQuery.each( response, function( k, v ) {
								//console.log( 'id is ' + k + ' and value is ' + v );

								if ( typeof v.disabled != 'undefined' && v.disabled == 'true' ) {
									switcher = `<a class="wtp_actions" id="general_category_rule_enable" data-id="${k}"> ${wtp_admin_script.enable_txt}</a>`;										
								} else {
									switcher = `<a class="wtp_actions" id="general_category_rule_disable" data-id="${k}"> ${wtp_admin_script.disable_txt}</a>`;
								}

								if ( v.max_qty == '' ) {
									max_qty = '&#8734;';
								} else {
									max_qty = v.max_qty;
								}

								$html += `<tr>
									<td class="wc-shipping-classes-blank-state col-1" data-value="${v.category}">${v.category_text}</td>
									<td class="wc-shipping-classes-blank-state col-2">${v.min_qty}</td>
									<td class="wc-shipping-classes-blank-state col-3">${max_qty}</td>
									<td class="wc-shipping-classes-blank-state col-4">${v.discount_value}</td>
									<td class="wc-shipping-classes-blank-state col-5">${v.discount_type}</td>
									<td>
										${switcher}
										| <a class="wtp_actions general_category_rule_delete" data-id="${k}">${wtp_admin_script.delete_txt}</a>
										| <a class="wtp_actions general_rule_edit" data-type="category" data-id="${k}">${wtp_admin_script.edit_txt}</a>
									</td>									
								</tr>`;

							});

							jQuery('#wtp-category-role-discounts').find('tr').removeClass('focusout');
							jQuery('#wtp-category-role-discounts').find('td').removeClass('focusin');
							jQuery('table').find('input[type="number"], select').removeClass('edit-field');
							jQuery('#wtp-category-role-discounts').html( $html );

							jQuery( '#add_category_based_mapping' ).text( 'Add Rule' );
							if ( jQuery('#rule_id').length > 0 ) {
								jQuery('#rule_id').remove();
							}

							jQuery('#mainform')[0].reset();
							jQuery( '#wc_settings_category_user_category' ).val('');
							jQuery( '#wc_settings_category_min' ).val('');
							jQuery( '#wc_settings_category_max' ).val('');
							jQuery( '#wc_settings_category_discount_value' ).val('');
							jQuery( '#wc_settings_category_discount_type' ).val('');
							if ( jQuery('.wtp-overlay').length > 0 ) {
								//setTimeout(function(){});
								jQuery('.wtp-overlay').fadeOut('500').remove();
							}
						}

					}
				);
			}

		}
	);

	jQuery( "body" ).on(
		"click",
		".general_category_rule_delete" ,
		function() {

			var parent_this = this;
			var data        = {
				'action': 'wtp_general_category_rule_delete',
				'general_category_rule_id': jQuery( this ).attr( 'data-id' ),
			};

			jQuery(parent_this).parents('body').prepend(`<div class="wtp-overlay"></div>`);

			jQuery.post(
				ajaxurl,
				data,
				function(response) {

					if ( 'done' == response ) {

						jQuery( parent_this ).parents( 'tr' ).remove();
						if ( jQuery('.wtp-overlay').length > 0 ) {
							//setTimeout(function(){});
							jQuery('.wtp-overlay').fadeOut('500').remove();
						}
					}

				}
			);

		}
	);

	jQuery( "body" ).on(
		"click",
		"#general_category_rule_disable",
		function() {

			var parent_this = this;
			var data        = {
				'action': 'wtp_general_category_rule_disable',
				'general_category_rule_id': jQuery( this ).attr( 'data-id' ),
			};

			jQuery(parent_this).parents('body').prepend(`<div class="wtp-overlay"></div>`);

			jQuery.post(
				ajaxurl,
				data,
				function(response) {

					if ( 'done' == response ) {

						jQuery( parent_this ).text( wtp_admin_script.enable_txt );
						jQuery( parent_this ).attr( 'id', 'general_category_rule_enable' );
						if ( jQuery('.wtp-overlay').length > 0 ) {
							//setTimeout(function(){});
							jQuery('.wtp-overlay').fadeOut('500').remove();
						}
					}

				}
			);

		}
	);

	jQuery( "body" ).on(
		"click",
		"#general_category_rule_enable" ,
		function() {

			var parent_this = this;
			var data        = {
				'action': 'wtp_general_category_rule_enable',
				'general_category_rule_id': jQuery( this ).attr( 'data-id' )
			};

			jQuery(parent_this).parents('body').prepend(`<div class="wtp-overlay"></div>`);

			jQuery.post(
				ajaxurl,
				data,
				function(response) {

					if ( 'done' == response ) {

						jQuery( parent_this ).text( wtp_admin_script.disable_txt );
						jQuery( parent_this ).attr( 'id', 'general_category_rule_disable' );
						if ( jQuery('.wtp-overlay').length > 0 ) {
							//setTimeout(function(){});
							jQuery('.wtp-overlay').fadeOut('500').remove();
						}

					}

				}
			);

		}
	);
		
	jQuery( '#add_role_based_mapping' ).click(
		function(e) {
			window.onbeforeunload = null;
			e.preventDefault();

			var general_user_role        	  = jQuery( '#wc_settings_role_user_role' ).val();
			var general_user_role_text        = jQuery( '#wc_settings_role_user_role option:selected' ).html();
			var general_role_min_quantity     = jQuery( '#wc_settings_role_min' ).val();
			var general_role_max_quantity     = jQuery( '#wc_settings_role_max' ).val();
			var general_role_discount_value   = jQuery( '#wc_settings_role_discount_value' ).val();
			var general_role_discount_type    = jQuery( '#wc_settings_role_discount_type' ).val();
			var flag_1 = false;
			var flag_2 = false;
			var flag_3 = false;
			var $html = '';
			var switcher = '';
			var msg = `<p class="wtp-alert wtp-alert-danger">${wtp_admin_script.required_txt}</p>`;
			var rule_id = jQuery('#rule_id').val();
			
			//clear field response messages on click
			jQuery('.wtp-field-response').html('');

			if ( general_role_min_quantity != '' && general_role_min_quantity > 0 ) {
				flag_1 = true;
			} else {
				jQuery( '#wc_settings_role_min' ).siblings('.wtp-field-response').html(msg);
			}

			if ( general_role_discount_value != '' && general_role_discount_value > 0  ) {
				flag_2 = true;

			} else {
				jQuery( '#wc_settings_role_discount_value' ).siblings('.wtp-field-response').html(msg);
			}

			if ( general_role_max_quantity == '' || general_role_max_quantity > 0 ) {
				flag_3 = true;
			} else {
				jQuery( '#wc_settings_role_max' ).siblings('.wtp-field-response').html(msg);
			}
			
			if ( flag_1 && flag_2 && flag_3 ) {

				jQuery(this).parents('body').prepend(`<div class="wtp-overlay"></div>`);

				var data = {

					'action': 'wtp_general_role_rule',
					'general_role_user_role': general_user_role,
					'general_role_user_role_text': general_user_role_text,
					'general_role_min_quantity': general_role_min_quantity,
					'general_role_max_quantity': general_role_max_quantity,
					'general_role_discount_value': general_role_discount_value,
					'general_role_discount_type': general_role_discount_type,
					'rule_id': rule_id

				};

				jQuery.post(
					ajaxurl,
					data,
					function(response) {

						if ( typeof response != 'undefined' && response != '' ) {
							response = JSON.parse( response );
							console.log( response );
							//console.log('foreach now');
							jQuery.each( response, function( k, v ) {
								//console.log( 'id is ' + k + ' and value is ' + v );

								if ( typeof v.disabled != 'undefined' && v.disabled == 'true' ) {
									switcher = `<a class="wtp_actions" id="general_role_rule_enable" data-id="${k}"> ${wtp_admin_script.enable_txt}</a>`;										
								} else {
									switcher = `<a class="wtp_actions" id="general_role_rule_disable" data-id="${k}"> ${wtp_admin_script.disable_txt}</a>`;
								}

								if ( v.max_qty == '' ) {
									max_qty = '&#8734;';
								} else {
									max_qty = v.max_qty;
								}

								$html += `<tr>
									<td class="wc-shipping-classes-blank-state col-1" data-value="${v.role}">${v.role_text}</td>
									<td class="wc-shipping-classes-blank-state col-2">${v.min_qty}</td>
									<td class="wc-shipping-classes-blank-state col-3">${max_qty}</td>
									<td class="wc-shipping-classes-blank-state col-4">${v.discount_value}</td>
									<td class="wc-shipping-classes-blank-state col-5">${v.discount_type}</td>
									<td>
										${switcher}
										| <a class="wtp_actions general_role_rule_delete" data-id="${k}">${wtp_admin_script.delete_txt}</a>
										| <a class="wtp_actions general_rule_edit" data-type="role" data-id="${k}">${wtp_admin_script.edit_txt}</a>
									</td>									
								</tr>`;

							});

							jQuery('#wtp-user-role-discounts').find('tr').removeClass('focusout');
							jQuery('#wtp-user-role-discounts').find('td').removeClass('focusin');
							jQuery('table').find('input[type="number"], select').removeClass('edit-field');
							jQuery('#wtp-user-role-discounts').html( $html );

							jQuery( '#add_role_based_mapping' ).text( 'Add Rule' );
							if ( jQuery('#rule_id').length > 0 ) {
								jQuery('#rule_id').remove();
							}

							jQuery('#mainform')[0].reset();
							jQuery( '#wc_settings_role_user_role' ).val('');
							jQuery( '#wc_settings_role_min' ).val('');
							jQuery( '#wc_settings_role_max' ).val('');
							jQuery( '#wc_settings_role_discount_value' ).val('');
							jQuery( '#wc_settings_role_discount_type' ).val('');
							if ( jQuery('.wtp-overlay').length > 0 ) {
								//setTimeout(function(){});
								jQuery('.wtp-overlay').fadeOut('500').remove();
							}
						}

					}
				);

			}

		}
	);

	jQuery( "body" ).on(
		"click",
		".general_role_rule_delete" ,
		function() {

			var parent_this = this;
			var data        = {
				'action': 'wtp_general_role_rule_delete',
				'general_role_rule_id': jQuery( this ).attr( 'data-id' ),
			};

			jQuery(parent_this).parents('body').prepend(`<div class="wtp-overlay"></div>`);

			jQuery.post(
				ajaxurl,
				data,
				function(response) {

					if ( 'done' == response ) {

						jQuery( parent_this ).parents( 'tr' ).remove();
						if ( jQuery('.wtp-overlay').length > 0 ) {
							//setTimeout(function(){});
							jQuery('.wtp-overlay').fadeOut('500').remove();
						}
					}

				}
			);

		}
	);

	jQuery( "body" ).on(
		"click",
		"#general_role_rule_disable",
		function() {

			var parent_this = this;
			var data        = {
				'action': 'wtp_general_role_rule_disable',
				'general_role_rule_id': jQuery( this ).attr( 'data-id' ),
			};

			jQuery(parent_this).parents('body').prepend(`<div class="wtp-overlay"></div>`);

			jQuery.post(
				ajaxurl,
				data,
				function(response) {

					if ( 'done' == response ) {

						jQuery( parent_this ).text( wtp_admin_script.enable_txt );
						jQuery( parent_this ).attr( 'id', 'general_role_rule_enable' );
						if ( jQuery('.wtp-overlay').length > 0 ) {
							//setTimeout(function(){});
							jQuery('.wtp-overlay').fadeOut('500').remove();
						}
					}

				}
			);

		}
	);

	jQuery( "body" ).on(
		"click",
		"#general_role_rule_enable" ,
		function() {

			var parent_this = this;
			var data        = {
				'action': 'wtp_general_role_rule_enable',
				'general_qty_rule_id': jQuery( this ).attr( 'data-id' )
			};

			jQuery(parent_this).parents('body').prepend(`<div class="wtp-overlay"></div>`);

			jQuery.post(
				ajaxurl,
				data,
				function(response) {

					if ( 'done' == response ) {

						jQuery( parent_this ).text( wtp_admin_script.disable_txt );
						jQuery( parent_this ).attr( 'id', 'general_role_rule_disable' );
						if ( jQuery('.wtp-overlay').length > 0 ) {
							//setTimeout(function(){});
							jQuery('.wtp-overlay').fadeOut('500').remove();
						}

					}

				}
			);

		}
	);


	jQuery( '#add_user_role' ).click(
		function(e) {
			window.onbeforeunload = null;
			e.preventDefault();
			//debugger;
			var new_user_role = jQuery( '#new_user_role' ).val();
			var $html = '';

			if ( typeof new_user_role != 'undefined' && jQuery.trim( new_user_role ) != '' ) {

				jQuery(this).parents('tr').find('.wtp-field-response').html('');
				jQuery(this).parents('body').prepend(`<div class="wtp-overlay"></div>`);
				
				var data = {
					'action': 'wtp_add_user_role',
					'new_user_role': new_user_role,
				};

				jQuery.post(
					ajaxurl,
					data,
					function(response) {

						if ( typeof response != 'undefined' && response != '' ) {
							//console.log( response );
							response = JSON.parse( response );
							//console.log( response );
							//jQuery( '#new_user_role' ).val('');
							jQuery.each( response, function( k, v ) {
								//console.log( 'id is ' + k + ' and value is ' + v.user_role );
								$html += `<tr>
									<td>${v.user_role}</td>
									<td><a class="wtp_actions user_role_delete" data-id="${k}">${wtp_admin_script.delete_txt}</a></td>
								</tr>`;

							});

							jQuery('#wtp_roles_display').html( $html );
							//jQuery('#mainform')[0].reset();
							jQuery('#new_user_role').val('');
							if ( jQuery('.wtp-overlay').length > 0 ) {
								//setTimeout(function(){});
								jQuery('.wtp-overlay').fadeOut('500').remove();
							}
							//jQuery( '.woocommerce-save-button' ).trigger( 'click' );
						}

					}
				);

			} else {
				jQuery(this).parents('tr').find('.wtp-field-response').html(`<p class="wtp-alert wtp-alert-danger">${wtp_admin_script.required_txt}</p>`);
			}

	});

	jQuery( "body" ).on(
		"click",
		".user_role_delete",
		function() {

			var parent_this = this;
			var id = jQuery( this ).attr( 'data-id' );
			if ( typeof id != 'undefined' && id != '' ) {
				
				jQuery(this).parents('body').prepend(`<div class="wtp-overlay"></div>`);
				
				var data        = {
					'action': 'wtp_user_role_delete',
					'user_role_id': id,
				};

				jQuery.post(
					ajaxurl,
					data,
					function(response) {

						if ( 'done' == response ) {
							if ( jQuery('.wtp-overlay').length > 0 ) {
								jQuery('.wtp-overlay').fadeOut('500').remove();
							}
							jQuery( parent_this ).parents( 'tr' ).remove();
						}

					}
				);
			}

		}
	);

	jQuery( document ).on(
		"click",
		".general_rule_edit" ,
		function() {
			//debugger;
			var type = jQuery( this ).data( 'type' );
			var p = jQuery(this).parents('tr');
			var rule_id = jQuery( this ).data( 'id' );

			//focus only edited row.
			jQuery(this).parents('tr').siblings().addClass('focusout');
			jQuery(this).parents('tr').find('td').addClass('focusin');
			//change color of fields when during edit
			jQuery('table').find('input[type="number"], select').addClass('edit-field');

			jQuery('html, body').animate({
		        scrollTop: parseInt( jQuery("#mainform").offset().top )
		    }, 700);

			var a = p.find('td.col-1').data('value');
			var b = p.find('td.col-2').text();
			var c = p.find('td.col-3').text();
			var d = p.find('td.col-4').text();
			var e = p.find('td.col-5').text();

			if ( type == 'category' ) {
				jQuery('#wc_settings_category_user_category').val(a).trigger('change');
				jQuery('#wc_settings_category_min').val(b).trigger('change');
				jQuery('#wc_settings_category_max').val(c).trigger('change');
				jQuery('#wc_settings_category_discount_value').val(d).trigger('change');
				jQuery('#wc_settings_category_discount_type').val(e).trigger('change');

				var btn = jQuery('#add_category_based_mapping');
				jQuery(btn).text('Edit Rule');

			}

			if ( type == 'user' ) {
				jQuery('#wc_settings_user_discount').val(a).trigger('change');
				jQuery('#wc_settings_user_start_qty').val(b).trigger('change');
				jQuery('#wc_settings_user_end_qty').val(c).trigger('change');
				jQuery('#wc_settings_user_discount_value').val(d).trigger('change');
				jQuery('#wc_settings_user_discount_type').val(e).trigger('change');
				
				var btn = jQuery('#add_user_based_mapping');
				jQuery(btn).text('Edit Rule');
			}

			if ( type == 'role' ) {
				jQuery('#wc_settings_role_user_role').val(a).trigger('change');
				jQuery('#wc_settings_role_min').val(b).trigger('change');
				jQuery('#wc_settings_role_max').val(c).trigger('change');				
				jQuery('#wc_settings_role_discount_value').val(d).trigger('change');
				jQuery('#wc_settings_role_discount_type').val(e).trigger('change');

				var btn = jQuery('#add_role_based_mapping');
				jQuery(btn).text('Edit Rule');
			}

			if ( jQuery('#rule_id').length <= 0 ) {
				jQuery('#mainform').prepend(`<input type="hidden" id="rule_id" name="rule_id" value="${rule_id}" />`);
			} else {
				jQuery('#rule_id').val(rule_id).trigger('change');
			}

		}
	);

	/** product metabox jquery started from here **/
	let html = '';
	let index = '';

	jQuery(document).on('click', '.add_discount_elements', function (e) {
        e.preventDefault();
        addElement (jQuery(this), '');
    });

    jQuery(document).on('click', '.wtp_discount_remove', function (e) {
        e.preventDefault();
        //deleteElement(jQuery(this), '');
        cont = jQuery(this).parents('#wtp_tier_pricing');
        var el = jQuery(this).data('rmdiv');
        jQuery('.' + el).find( '.bulk_discount_min' ).val('').trigger('change');
        jQuery('.' + el).remove();
        var index = jQuery(cont).find('.wtp-discount-group').length;

        if ( index == 0 || typeof index == undefined ) {
            jQuery(cont).find('.wtp_options_group').append('<p class="wtp-addtext">'+ wtp_admin_script.desc_txt +'</p>');
        }
    });

    jQuery(document).on('click', '.add_variation_discount_elements', function (e) {
        e.preventDefault();
        addElement (jQuery(this), 'variation_');
    });

    jQuery(document).on('click', '.wtp_variation_discount_remove', function (e) {
        e.preventDefault();
        //deleteElement(jQuery(this), 'variation_');
        variation_id = '_' + jQuery(this).data('variation-id');
        var_id = '_' + jQuery(this).data('variation-id');
        var cont = jQuery(this).parents('#wtp_product_variation_discount_meta');
        var el = jQuery(this).data('rmdiv');
        jQuery('.' + el).find( '.bulk_discount_min' ).val('').trigger('change');
        jQuery('.' + el).remove();
        var index = jQuery(cont).find('.wtp-discount-group').length;

        if ( index == 0 || typeof index == undefined ) {
            jQuery(cont).prepend('<p class="wtp-addtext">'+ wtp_admin_script.desc_txt +'</p>');
        }
    });

	function addElement ($this, type='') {
		//debugger;
        if ( type == 'variation_' ) {
            variation_id = jQuery($this).data('variation-id') + '_';
            var_id = '_' + jQuery($this).data('variation-id');
            cont = jQuery($this).parents('#wtp_product_variation_discount_meta');
        } else {
            variation_id = '';
            var_id = '';
            cont = jQuery($this).parents('#wtp_tier_pricing');
        }

        index = jQuery(cont).find('.wtp-discount-group').length;
        ++index;

        //console.log('before ' + index);

        html = '<div class="wtp-discount-group wtp-bulk-group bulk_range_group_'+ variation_id + index +'" data-index="'+ index +'">'
                    +'<div class="range_setter_inner">'
                        +'<div class="bulk-row-main">'
                            +'<div class="bulk-row-start wtp-input-filed-hight bulk-row-inner">'
                                +'<div class="bulk-min">'
                                    +'<input type="number" name="wtp_product_tier_setting'+ var_id +'[min_qty][]" class="bulk_discount_min wtp_value_selector wtp_next_value" placeholder="min" min="0" step="any" value=""><br/>'
                                    +'<span class="wtp_desc_text">'+ wtp_admin_script.minimum_txt +'</span>'
                                +'</div>'
                                +'<div class="bulk-max">'
                                    +'<input type="number" name="wtp_product_tier_setting'+ var_id +'[max_qty][]" class="bulk_discount_max wtp_value_selector wtp_auto_add_value" placeholder="max" min="0" step="any" value=""><br/>'
                                    +'<span class="wtp_desc_text">'+ wtp_admin_script.maximum_txt +'</span>'
                                +'</div>'
                                +'<div class="bulk_gen_disc_type wtp-select-filed-hight">'
                                    +'<select name="wtp_product_tier_setting'+ var_id +'[discount_type][]" class="bulk-discount-type bulk_discount_select">'
                                        +'<option value="percentage">'+ wtp_admin_script.percentage_txt +'</option>'
                                        +'<option value="fixed">'+ wtp_admin_script.fixed_txt +'</option>                                                                        '
                                    +'</select><br/>'
                                    +'<span class="wtp_desc_text">'+ wtp_admin_script.dis_type_txt +'</span>'
                                +'</div>'
                                +'<div class="bulk_amount">'
                                    +'<input type="number" name="wtp_product_tier_setting'+ var_id +'[discount_value][]" class="bulk_discount_value bulk_value_selector wtp_value_selector" placeholder="Discount" min="0" step="any" value="0"><br/>'
                                    +'<span class="wtp_desc_text">'+ wtp_admin_script.dis_val_txt +'</span>'
                                +'</div>'
                                +'<div class="bulk_gen_disc_type wtp-select-filed-hight">'
                                    +'<select name="wtp_product_tier_setting'+ var_id +'[disabled][]" class="bulk-discount-type bulk_discount_select">'
                                        +'<option value="false">'+ wtp_admin_script.enable_txt +'</option>'
                                        +'<option value="true">'+ wtp_admin_script.disable_txt +'</option>                                                                  '
                                    +'</select><br/>'
                                    +'<span class="wtp_desc_text">'+ wtp_admin_script.visibility_txt +'</span>'
                                +'</div>'
                                +'<div class="wtp-btn-remove">'
                                    +'<span class="dashicons dashicons-no-alt wtp_'+ type +'discount_remove" data-rmdiv="bulk_range_group_'+ variation_id + index +'"></span>'
                                +'</div>'
                            +'</div>'
                        +'</div>'
                    +'</div>'
                +'</div>';

        console.log('before ' + index);

        jQuery($this).attr('data-current-index', index);

        if ( jQuery(cont).find('.wtp-addtext').length > 0 ) {
            jQuery('.wtp-addtext').remove();
        }

        jQuery(cont).find('.wtp_options_group').append(html);

    }

    function deleteElement ($this, type='') {
        if ( type == 'variation_' ) {
            variation_id = '_' + jQuery($this).data('variation-id');
            var_id = '_' + jQuery($this).data('variation-id');
            cont = jQuery($this).parents('#wtp_product_variation_discount_meta');
        } else {
            variation_id = '';
            var_id = '';
            cont = jQuery($this).parents('#wtp_product_discount_meta');
        }
        var el = jQuery($this).data('rmdiv');
        //jQuery('.' + el).remove();
        jQuery('.' + el).find( '.bulk_discount_min' ).val('').trigger('change');
        jQuery('.' + el).remove();
        //debugger;
        var index = jQuery(cont).find('.wtp-discount-group').length;

        if ( index == 0 || typeof index == undefined ) {
            jQuery(cont).html('<p class="wtp-addtext">'+ wtp_admin_script.desc_txt +'</p>');
        }

    }

    jQuery('#wtp_display_type').on( 'change', function() {
    	var This = jQuery(this);
    	var val = This.val();
    	showDisplayTypeSettings( val );
    });    


    showDisplayTypeSettings();


    function showDisplayTypeSettings( val='' ) {

    	jQuery( `.type-tooltip` ).parents('table').css('display', 'none');
		jQuery( `.type-table` ).parents('table').css('display', 'none');

		if ( 'disabled' == jQuery( 'input[name="wtp_show_discount_col"]:checked' ).val() ) {
			jQuery( '#wtp_discount_col_text' ).parents('tr').css( 'display', 'none' );
		}

    	if ( '' == val ) {
    		val = jQuery('#wtp_display_type').val();
    	}

    	if ( 'tooltip' === val ) {
			jQuery( `.type-tooltip` ).parents('table').addClass('wtp-show-table');
			jQuery( `.type-table` ).parents('table').removeClass('wtp-show-table');
		} else {
			jQuery( `.type-table` ).parents('table').addClass('wtp-show-table');
			jQuery( `.type-tooltip` ).parents('table').removeClass('wtp-show-table');
		}
    }

    jQuery( 'input[name="wtp_show_discount_col"]' ).on( 'click', function() {
    	if ( jQuery(this).is(':checked') ) {
    		var val = jQuery(this).val();
    		if ( 'enabled' === val ) {
    			jQuery( '#wtp_discount_col_text' ).parents('tr').css( 'display', 'table-row' );
    		} else {
    			jQuery( '#wtp_discount_col_text' ).parents('tr').css( 'display', 'none' );
    		}
    	}
    });

    jQuery('#wtp_summary_display_type').on( 'change', function() {
    	var This = jQuery(this);
    	var val = This.val();
    	showSummaryDisplayTypeSettings( val );
    });

    showSummaryDisplayTypeSettings();

    function showSummaryDisplayTypeSettings( val='' ) {

    	jQuery( `.type-inline` ).parents('table').css('display', 'none');

    	if ( '' == val ) {
    		val = jQuery('#wtp_summary_display_type').val();
    	}

    	if ( 'inline' === val || 'table' === val ) {
			jQuery( `.type-inline` ).parents('table').addClass('wtp-show-table');			
		} else {			
			jQuery( `.type-inline` ).parents('table').removeClass('wtp-show-table');
		}
    }

    jQuery( 'input[name="wtp_hide_price"]' ).on( 'click', function() {
    	if ( jQuery(this).is(':checked') ) {
    		var val = jQuery(this).val();
    		if ( 'enabled' === val ) {
    			jQuery( '#wtp_hide_price_text' ).parents('tr').css( 'display', 'table-row' );
    		} else {
    			jQuery( '#wtp_hide_price_text' ).parents('tr').css( 'display', 'none' );
    		}
    	}
    });

    if ( 'disabled' == jQuery( 'input[name="wtp_hide_price"]:checked' ).val() ) {
		jQuery( '#wtp_hide_price_text' ).parents('tr').css( 'display', 'none' );
	}

    jQuery( 'input[name="wtp_tier_range_price_show"]' ).on( 'click', function() {
    	if ( jQuery(this).is(':checked') ) {
    		var val = jQuery(this).val();
    		if ( 'enabled' === val ) {
    			jQuery( '#wtp_display_tier_price_range' ).parents('tr').css( 'display', 'table-row' );
    		} else {
    			jQuery( '#wtp_display_tier_price_range' ).parents('tr').css( 'display', 'none' );
    		}
    	}
    });

    if ( 'disabled' == jQuery( 'input[name="wtp_tier_range_price_show"]:checked' ).val() ) {
		jQuery( '#wtp_display_tier_price_range' ).parents('tr').css( 'display', 'none' );
	}

	jQuery('#select_import_type').on('change', function () {
		jQuery( '#wtp_sample_csv' ).hide();

		if ( 'product' == jQuery(this).val() ) {
			jQuery('#wtp_sample_csv').find('a').attr( 'href', wtp_admin_script.sample_csv_product );
			jQuery('#wtp_sample_csv').css('display', 'block');
		}

		if ( 'category' == jQuery(this).val() ) {
			jQuery('#wtp_sample_csv').find('a').attr( 'href', wtp_admin_script.sample_csv_category );
			jQuery('#wtp_sample_csv').css('display', 'block');
		}

		if ( 'user' == jQuery(this).val() ) {
			jQuery('#wtp_sample_csv').find('a').attr( 'href', wtp_admin_script.sample_csv_user );
			jQuery('#wtp_sample_csv').css('display', 'block');
		}

		if ( 'role' == jQuery(this).val() ) {
			jQuery('#wtp_sample_csv').find('a').attr( 'href', wtp_admin_script.sample_csv_role );
			jQuery('#wtp_sample_csv').css('display', 'block');
		}
	});


	jQuery( '#mainform' ).on('submit', function(event) {
		
		if ( jQuery('#wtp_type').val() == 'wtp_import_csv' ) {

			event.preventDefault();
			jQuery('#wtp_message').html('');
			jQuery.ajax({
			    url: wtp_admin_script.ajaxurl,
			    method:"POST",
			    data: new FormData(this),
			    dataType:"json",
			    contentType:false,
			    cache:false,
			    processData:false,
			    beforeSend: function() {
			    	jQuery('#wtp_import_csv').attr('disabled','disabled');
			    	jQuery('#import_runner').val('Importing');
			    	jQuery('#wtp_message').html('<div class="alert alert-warning">Data uploading is in process.</div>');
			    },
			    success:function(data) {
		     		jQuery('#wtp_import_csv').attr('disabled',false);
		     		if ( jQuery('.drop-zone__prompt').length <= 0 ) {
			     		jQuery('.drop-zone').prepend( `<span class="drop-zone__prompt">Drop file here or click to upload</span>` );
			     	}
			     	jQuery('.drop-zone__thumb').remove();
			     	jQuery('#wtp_import_csv').val('');			     	

			    	if(data.success) {

			      		jQuery.ajax({
			      			url: wtp_admin_script.ajaxurl,
			      			method: 'POST',
			      			data: {action: 'wtp_start_importing_to_db'},
			      			success: function ( data ) {

			      				if ( '' != data ) {
			      					setTimeout(function(){
			      						jQuery('#wtp_message').html('<div class="alert alert-success">Data imported successfully.</div>');
			      					}, 500);			      					
			      				} else {
			      					setTimeout(function(){
			      						jQuery('#wtp_message').html('<div class="alert alert-danger">Import failed. No rows affected.</div>');
			      					}, 500);			      					
			      				}

			      				jQuery('#import_runner').val('Import Data');
			      			},
			      		});			      		
			     	}
			     
			     	if(data.error) {
			      		jQuery('#wtp_message').html('<div class="alert alert-danger">'+data.error+'</div>');
			      		jQuery('#import_runner').val('Import Data');
			     	}
			    }
			});
		}

		if ( jQuery('#wtp_type').val() == 'wtp_export_csv' ) {

			event.preventDefault();
			jQuery('#wtp_message').html('');

			jQuery.ajax({
			    url: wtp_admin_script.ajaxurl,
			    method:"POST",
			    data: new FormData(this),
			    dataType:"json",
			    contentType:false,
			    cache:false,
			    processData:false,			    
			    beforeSend: function() {
			    	jQuery('#export_runner').val('Exporting');
			    	jQuery('#wtp_message').html('<div class="alert alert-warning">Data exporting is in process.</div>');
			    },
			    success:function(data) {

			    	if(data.success) {
			      		jQuery('#wtp_message').html('<div class="alert alert-success">'+data.success+'</div>');
			     	}
			     
			     	if(data.error) {
			      		jQuery('#wtp_message').html('<div class="alert alert-danger">'+data.error+'</div>');
			     	}

			     	jQuery('#export_runner').val('Export Data');
			    }
			});
		}

	});

});

/** Drag & Drop function starts here*/
document.querySelectorAll(".drop-zone__input").forEach((inputElement) => {  
  const dropZoneElement = inputElement.closest(".drop-zone");

  dropZoneElement.addEventListener("click", (e) => {
    inputElement.click();
  });

  inputElement.addEventListener("change", (e) => {  	
    if (inputElement.files.length) {
      updateThumbnail(dropZoneElement, inputElement.files[0]);
    }
  });

  dropZoneElement.addEventListener("dragover", (e) => {
    e.preventDefault();
    dropZoneElement.classList.add("drop-zone--over");
  });

  ["dragleave", "dragend"].forEach((type) => {
    dropZoneElement.addEventListener(type, (e) => {
      dropZoneElement.classList.remove("drop-zone--over");
    });
  });

  dropZoneElement.addEventListener("drop", (e) => {
    e.preventDefault();

    if (e.dataTransfer.files.length) {
      inputElement.files = e.dataTransfer.files;
      updateThumbnail(dropZoneElement, e.dataTransfer.files[0]);
    }

    dropZoneElement.classList.remove("drop-zone--over");
  });
});

/**
 * Updates the thumbnail on a drop zone element.
 *
 * @param {HTMLElement} dropZoneElement
 * @param {File} file
 */
function updateThumbnail(dropZoneElement, file) {	
  let thumbnailElement = dropZoneElement.querySelector(".drop-zone__thumb");

  // First time - remove the prompt
  if (dropZoneElement.querySelector(".drop-zone__prompt")) {
    dropZoneElement.querySelector(".drop-zone__prompt").remove();
  }

  // First time - there is no thumbnail element, so lets create it
  if (!thumbnailElement) {
    thumbnailElement = document.createElement("div");
    thumbnailElement.classList.add("drop-zone__thumb");
    dropZoneElement.appendChild(thumbnailElement);
  }

  thumbnailElement.dataset.label = file.name;

  // Show thumbnail for image files
  if (file.type.startsWith("image/")) {
    const reader = new FileReader();

    reader.readAsDataURL(file);
    reader.onload = () => {
      thumbnailElement.style.backgroundImage = `url('${reader.result}')`;
    };
  } else {
    thumbnailElement.style.background = `url('${wtp_admin_script.image_url}assets/images/file-csv-icon.png') no-repeat center / contain`;
  }
}

/** Drag & Drop function ends here*/