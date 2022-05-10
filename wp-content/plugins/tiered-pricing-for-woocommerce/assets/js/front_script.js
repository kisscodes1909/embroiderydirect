jQuery(document).ready(function(){

    var container = jQuery('#wtp-discount-list-container');

    var tooltipContainer = jQuery('#wtp-tooltip');

    if ( container.find('ul li:not(.wtp-table-head)').length > 0 ) {
        tooltipContainer.css({display: 'inline-block'})
    }

    let qty = 1;    

    //debugger;
    jQuery( '.wtp-simple-product-list-container' ).each( function() {
        
        if ( jQuery(this).find('ul li').length <= 0 ) {
            jQuery(this).remove();
        }

        if ( jQuery(this).find('ul li').length > 0 ) {
            jQuery(this).removeClass('wtp-simple-product-list-container');
        }
    });

    // if ( jQuery( '.wtp-simple-product-list-container' ).find('ul li').length > 0 ) {
    //     jQuery('#wtp-discount-list-container').removeClass('wtp-simple-product-list-container');
    // }

    jQuery(".reset_variations").on('click', function () {
        jQuery(container).addClass('wtp-variable-product-list-container');
    });

    jQuery( ".single_variation_wrap" ).on( "show_variation", function ( event, variation ) {
        //alert( variation.variation_id );
        jQuery(container).addClass('wtp-variable-product-list-container');
        jQuery('.single_add_to_cart_button').attr('disabled', 'disabled');
        console.log( variation );
        jQuery.ajax({
            url: wtp_front_script.ajaxurl,
            type: "POST",
            dataType: "json",
            type: 'POST',
            data: {
                action: 'wtp_show_discount_list_product_variation',
                variation_id: variation.variation_id,
                price: variation.display_price,
                nonce: wtp_front_script.nonce
            },
            beforeSend: function() {
                jQuery('#wtp-tooltip').hide();
            },
            success: function (response) {                
                //remove previous response before adding new one
                jQuery(container).find('ul').html('');                

                if ( response != 0 && response.html != '' ) {
                    jQuery(container).removeClass('wtp-variable-product-list-container');
                    jQuery(container).find('ul').html(response.html);                    
                    var qty = jQuery( 'div.quantity .qty' ).val();
                    //whenever you change the quantity
                    check_qty_level( qty );
                    jQuery('#wtp-tooltip').show();
                }

                jQuery('.single_add_to_cart_button').removeAttr('disabled');
            }     
        });
    });

    jQuery( document ).on( 'keyup copy paste change', 'div.quantity .qty', function() {
        //debugger;
        qty = jQuery(this).val();
        check_qty_level( qty );
    });

    jQuery(document).on( 'click', '.reset_variations', function() {
        check_qty_level();
    });

    qty = jQuery( 'div.quantity .qty' ).val();
    check_qty_level( qty );
    
    function check_qty_level( qty = 0 ) {
        const instance = tippy(document.getElementById('wtp-tier-data'));                    
        instance.destroy();
        if ( qty >= 0 ) {            
            jQuery( '#wtp-discount-list-container ul li' ).removeClass('wtp-selected');
            jQuery( '#wtp-discount-list-container ul li' ).each(function() {
                var min_qty = jQuery(this).data('min');
                var max_qty = jQuery(this).data('max');
                if ( max_qty == -1 ) {
                    if ( qty >= min_qty ) {
                        jQuery(this).addClass('wtp-selected');
                        return false;
                    }
                } else {
                    if ( qty >= min_qty && qty <= max_qty ) {
                        jQuery(this).addClass('wtp-selected');
                        return false;
                    }
                }               
            });


            if ( wtp_front_script.summary != 'none' ) {
                var price = jQuery('#wtp-discount-list-container ul li.wtp-selected').data('price');
                var data = {
                    action: 'wtp_calculate_summary',
                    price: price,
                    qty: qty
                };

                jQuery.post( wtp_front_script.ajaxurl, data, function ( success ) {
                    var obj = JSON.parse( success );
                    if ( obj != '' ) {
                        jQuery('.wtp-summary #wtp-unit').html( obj.price );
                        jQuery('.wtp-summary #wtp-subtotal').html( obj.subtotal );
                        jQuery('.wtp-summary').removeClass('wtp-hide');
                    } else {
                        jQuery('.wtp-summary #wtp-unit').html( '' );
                        jQuery('.wtp-summary #wtp-subtotal').html( '' );
                        jQuery('.wtp-summary').addClass('wtp-hide');
                    }              

                });
            }
            
            //whenever you change the quantity
            tippy('#wtp-tooltip', {
                content: jQuery('#wtp-tier-data').html(),
                allowHTML: true,
            });
        }
    }

});