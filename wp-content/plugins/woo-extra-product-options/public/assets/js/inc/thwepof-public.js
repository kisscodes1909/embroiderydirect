var thwepof_public = (function($, window, document) {
	'use strict';

	function initialize_thwepof(){
		var extra_options_wrapper = $('.thwepo-extra-options');
		//if(extra_options_wrapper){
			setup_date_picker(extra_options_wrapper, 'thwepof-date-picker', thwepof_public_var);
		//}
	}

	function setup_date_picker(form, class_selector, data){
		//form.find('.'+class_selector).each(function(){
		$('.'+class_selector).each(function(){
			var readonly = $(this).data("readonly");
			readonly = readonly === 'yes' ? true : false;
			var yearRange = $(this).data("year-range");
			yearRange = '' == yearRange ? '-100:+10' : yearRange;
			
			$(this).datepicker({
				showButtonPanel: true,
				changeMonth: true,
				changeYear: true,
				yearRange: yearRange,
			});
			$(this).prop('readonly', readonly);
		});
	}

	function check_oceanwp_quickview_opened() {
      var qv_modal = $('#owp-qv-wrap');
      if(qv_modal.hasClass('is-visible')){
         initialize_thwepof();
      }else {
          setTimeout(function(){
          	check_oceanwp_quickview_opened();
          }, 1000);
      }
    }

    function apply_input_masking(elm){
    	var data = $(elm).data('mask-pattern');
    	var alias_items = ['datetime','numeric','cssunit','url','IP','email','mac','vin'];

    	if($.inArray(data, alias_items) !== -1){
    		$(elm).inputmask({
	    		"alias": data,
	    	});
    	}else{
    		$(elm).inputmask({
	    		"mask": data,
	    	});
    	}
    }

    function thwepofviewpassword(elm){
    	var icon = $(elm);
    	var parent_elm = icon.closest('.thwepof-password-field');
    	var input = parent_elm.find('input');

    	if(icon.hasClass('dashicons-visibility')){
    		input.attr("type", "text");
    		icon.addClass('dashicons-hidden').removeClass('dashicons-visibility');
    	}else if(icon.hasClass('dashicons-hidden')){
    		input.attr("type", "password");
    		icon.addClass('dashicons-visibility').removeClass('dashicons-hidden');
    	}
    }

    function display_range_value(elm){
			var range_input = $(elm);
			var range_val = range_input.val();
			var width = range_input.width();
			var min_attr =  range_input.attr('min');
			var max_attr =  range_input.attr('max');

			const min = min_attr ? min_attr : 0;
			const max = max_attr ? max_attr : 100;
			const position = Number(((range_val - min) * 100) / (max - min));

			var display_div = range_input.siblings('.thwepof-range-val');
			display_div.html(range_val);
			var display_div_width = display_div.innerWidth();

			var left_position;
			var slider_position = width * position/100;

			if((width - slider_position) < display_div_width/2){
				left_position = 'calc('+ 100 +'% - '+ display_div_width +'px)';
			}else if(slider_position < display_div_width/2){
				left_position = '0px';
			}else{
				left_position = 'calc('+ position +'% - '+ display_div_width/2 +'px)';
			}

			display_div.css('left', left_position);
		}
	
	/***----- INIT -----***/
	initialize_thwepof();
	
	if(thwepof_public_var.is_quick_view == 'flatsome'){
		$(document).on('mfpOpen', function() {
			initialize_thwepof();

			$.magnificPopup.instance._onFocusIn = function(e) {
			    if( $(e.target).hasClass('ui-datepicker-month') ) {
			        return true;
			    }
			    if( $(e.target).hasClass('ui-datepicker-year') ) {
			        return true;
			    }
			    $.magnificPopup.proto._onFocusIn.call(this,e);
			};
		});
	}else if(thwepof_public_var.is_quick_view == 'yith'){
		$(document).on("qv_loader_stop", function() {
			initialize_thwepof();
		});
	}else if(thwepof_public_var.is_quick_view == 'astra'){
		$(document).on("ast_quick_view_loader_stop", function() {
			initialize_thwepof();
		});
	}else if(thwepof_public_var.is_quick_view == 'oceanwp'){
		$(document).on('click', '.owp-quick-view', function(e) {
			check_oceanwp_quickview_opened();
		});
	}

    var mask_fields = $(".thwepof-mask-input");
    mask_fields.each(function(){
    	apply_input_masking(this);
    });

    var wepo_range_input = $('input[type="range"].thwepof-input-field');
    wepo_range_input.each(function(){
    	display_range_value(this);
    });

    wepo_range_input.on('change', function(){
    	display_range_value(this);
    });

	return {
		initialize_thwepof : initialize_thwepof,
		thwepofviewpassword : thwepofviewpassword,
	};

}(window.jQuery, window, document));

function thwepofViewPassword(elm){
	thwepof_public.thwepofviewpassword(elm);
}

function thwepof_init(){
	thwepof_public.initialize_thwepof();
}
