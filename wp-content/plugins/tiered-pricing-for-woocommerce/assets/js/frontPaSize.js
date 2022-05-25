jQuery( function( $ ) {

    class PASizeUI {
        size = [];
        ElId = '';
        variationData = [];

        /*
        * UIID Element ID
        */
        constructor(ElId) {
            this.ElId = ElId;

            this.paColourElement = $('#pa_colour');
            this.variationsFormElement = $('.variations_form');

            this.readData();
            //this.renderUI();

            this.displayUI();

            // Event: PA colour change
            this.paColourElement.on('change', () => { this.displayUI() });
        }

        /**
         * Init Data
         */

        readData() {
            this.variationData = this.variationsFormElement.find('[data-product_variations]').data('product_variations');
        }

        uiHtmlTemplate(variations) {

            if(variations.length < 1) return '';
            
            let html = '<div><label class="label">Sizes & Quantities</label></div>';            
        
            variations.map((variation) => {
                html += this.variationElement(variation);
            })

            return html;
        }

        variationElement(variation) {
            const {attribute_pa_size} = variation.attributes;
            const {variation_id} = variation;
            
            return `
            <div class='pa-size-ui-item'>
                <div class='pa-size-ui-item-label'>${attribute_pa_size}</div>
                <div class='quantity buttons_added'>
                    <input type="button" value="-" class="minus button is-form">
                    <input value="0" min=0 step=1 class='pa-size-ui-item-qty input-text qty text' type="number" name=qtyNumber[${variation_id}]>
                    <input type="button" value="+" class="plus button is-form">
                </div>
            </div>`;
        }

        getColour() {
            let colour = this.paColourElement.val();
            
            if(!colour) {
                colour = this.paColourElement.find('option:nth-child(2').val();
            }

            return colour;
        }

        getVariation(colour) {
            const variationData = this.variationData.filter((variation) => {
                return variation.attributes.attribute_pa_colour === colour
            });

            return variationData;
        }

        displayUI() {
            const colour = this.getColour();
            const variationData = this.getVariation(colour);
            document.getElementById(this.ElId).innerHTML = this.uiHtmlTemplate(variationData)
        }

        
    }

    
    new PASizeUI('paSizeUI');

    // $("#AddToCart").on('click', (e) => {
    //     e.preventDefault();

    //     $form = $('.variations_form');
    //     const productID = $form.data('product_id');
    //     // {$form.serialize(), data: 'wtp_add_to_cart'}

    //     $.ajax({
    //         type:		'POST',
    //         url:		wtp_PaSize_script.ajaxurl,
    //         data:		$form.serialize() + `&action=wtp_add_to_cart&product_id=${productID}`,
    //         dataType:   'json',
    //         success:	function( response ) {
    //             if ( ! response ) {
    //                 return;
    //             }

    //             if ( response.error && response.product_url ) {
    //                 window.location = response.product_url;
    //                 return;
    //             }

    //             // Redirect to cart option
    //             window.location = wc_add_to_cart_params.cart_url;
    //         },
    //         error:	function( jqXHR, textStatus, errorThrown ) {
           
    //         }
    //     });
    // });


});