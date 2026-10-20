( function( $ ) {
	'use strict';

	const config = window.embroideryDirectProduct || {};
	const colourSlug = config.colourAttribute || 'pa_colour';
	const sizeSlug = config.sizeAttribute || 'pa_size';

	class MultiSizeUI {
		constructor( elementId ) {
			this.element = document.getElementById( elementId );
			this.formElement = this.element ? $( this.element ).closest( '.variations_form' ) : $();
			// Flatsome can render a hidden lightbox form before the real form.
			// If the nearest form has no embedded variations, use the first form
			// that contains an actual variation JSON payload.
			if ( ! this.formElement.length || ! this.hasVariationPayload( this.formElement ) ) {
				this.formElement = $( '.variations_form' ).filter( ( index, form ) => this.hasVariationPayload( $( form ) ) ).first();
			}
			this.colourElement = this.formElement.find( `#${ colourSlug }` );

			if ( ! this.element || ! this.colourElement.length || ! this.formElement.length ) {
				return;
			}

			this.variationData = this.readVariationData();
			this.addHeader();
			this.display();
			this.colourElement.on( 'change', () => this.display() );
			this.element.addEventListener( 'input', () => this.updateTotal() );
			this.element.addEventListener( 'change', () => this.updateTotal() );
			this.element.addEventListener( 'click', () => window.setTimeout( () => this.updateTotal(), 0 ) );
			this.formElement.on( 'submit.edMultiSizeAjax', event => this.submit( event ) );
		}

		hasVariationPayload( form ) {
			let raw = form.attr( 'data-product_variations' );
			if ( ! raw || raw === 'false' || raw === '[]' ) {
				raw = form.find( '[data-product_variations]' ).first().attr( 'data-product_variations' );
			}
			return raw && raw !== 'false' && raw !== '[]';
		}

		readVariationData() {
			let raw = this.formElement.attr( 'data-product_variations' );
			if ( ! raw || raw === 'false' || raw === '[]' ) {
				raw = this.formElement.find( '[data-product_variations]' ).first().attr( 'data-product_variations' );
			}
			if ( ! raw || raw === 'false' ) {
				return [];
			}

			try {
				return Array.isArray( raw ) ? raw : JSON.parse( raw.replace( /&quot;/g, '"' ).replace( /&#039;/g, "'" ).replace( /&amp;/g, '&' ) );
			} catch ( error ) {
				const fallback = this.formElement.find( '[data-product_variations]' ).data( 'product_variations' );
				return Array.isArray( fallback ) ? fallback : [];
			}
		}

		addHeader() {
			this.element.insertAdjacentHTML(
				'beforebegin',
				`<div class="ed-size-heading">
					<div><span class="ed-step">02</span><strong>${ config.sizesLabel || 'Sizes & Quantities' }</strong></div>
					<span class="ed-size-total" aria-live="polite">0 items</span>
				</div>`
			);
		}

		getColour() {
			return this.colourElement.val() || this.colourElement.find( 'option:nth-child(2)' ).val();
		}

		getVariations( colour ) {
			return this.variationData.filter(
				variation => variation.attributes[ `attribute_${ colourSlug }` ] === colour
			);
		}

		renderItem( variation ) {
			const size = variation.attributes[ `attribute_${ sizeSlug }` ];

			return `
				<div class="pa-size-ui-item">
					<div class="pa-size-ui-item-label"><span>Size</span>${ size }</div>
					<div class="quantity buttons_added">
						<input type="button" value="−" class="minus button is-form" aria-label="Decrease ${ size } quantity">
						<input value="0" min="0" step="1" class="pa-size-ui-item-qty input-text qty text" type="number" name="qtyNumber[${ variation.variation_id }]" aria-label="${ size } quantity">
						<input type="button" value="+" class="plus button is-form" aria-label="Increase ${ size } quantity">
					</div>
				</div>`;
		}

		display() {
			const variations = this.getVariations( this.getColour() );

			this.element.innerHTML = variations.length
				? variations.map( variation => this.renderItem( variation ) ).join( '' )
				: '';

			this.updateTotal();
		}

		updateTotal() {
			const total = this.formElement.find( '.pa-size-ui-item-qty' ).toArray().reduce(
				( sum, input ) => sum + Math.max( 0, Number.parseInt( input.value, 10 ) || 0 ),
				0
			);
			const output = this.formElement.find( '.ed-size-total' );
			this.formElement.find( '.pa-size-ui-item-qty' ).each( function() {
				const quantity = Math.max( 0, Number.parseInt( this.value, 10 ) || 0 );
				$( this ).closest( '.pa-size-ui-item' ).toggleClass( 'has-quantity', quantity > 0 );
			} );

			output.text( `${ total } ${ total === 1 ? 'item' : 'items' }` );
			output.toggleClass( 'has-items', total > 0 );
			const button = this.formElement.find( '.single_add_to_cart_button' ).first();
			button.prop( 'disabled', total < 1 ).attr( 'aria-disabled', total < 1 ? 'true' : 'false' );
			if ( total > 0 ) {
				button.removeClass( 'disabled wc-variation-selection-needed wc-variation-is-unavailable' );
			}
			this.updateTierHighlight( total );
		}

		showStatus( message, type = 'error' ) {
			let status = this.formElement.find( '.ed-cart-status' );
			if ( ! status.length ) {
				status = $( '<div class="ed-cart-status" role="status" aria-live="polite"></div>' );
				this.formElement.find( '.single_add_to_cart_button' ).first().after( status );
			}
			status.removeClass( 'is-error is-success' ).addClass( `is-${ type }` ).text( message );
		}

		submit( event ) {
			event.preventDefault();
			const inputs = this.formElement.find( '.pa-size-ui-item-qty' );
			const total = inputs.toArray().reduce(
				( sum, input ) => sum + Math.max( 0, Number.parseInt( input.value, 10 ) || 0 ),
				0
			);
			if ( total < 1 ) {
				this.showStatus( config.emptyMessage || 'Choose a quantity for at least one size.' );
				return;
			}

			const button = this.formElement.find( '.single_add_to_cart_button' ).first();
			const originalLabel = button.text();
			const data = new FormData( this.formElement.get( 0 ) );
			data.set( 'action', 'ed_multi_size_add_to_cart' );
			data.set( 'nonce', config.ajaxNonce || '' );
			button.prop( 'disabled', true ).addClass( 'is-loading' ).text( config.addingLabel || 'Adding…' );

			$.ajax( {
				url: config.ajaxUrl,
				type: 'POST',
				data,
				processData: false,
				contentType: false,
			} ).done( response => {
				if ( ! response || ! response.fragments ) {
					const responseMessage = response && response.data ? response.data.message : '';
					this.showStatus( responseMessage || config.errorMessage || 'Could not add these items.' );
					return;
				}
				$( document.body ).trigger( 'added_to_cart', [ response.fragments, response.cart_hash, button ] );
				inputs.val( 0 );
				this.updateTotal();
				this.showStatus( response.message || config.addedLabel || 'Added to cart', 'success' );
			} ).fail( xhr => {
				const errorMessage = xhr.responseJSON && xhr.responseJSON.data ? xhr.responseJSON.data.message : '';
				this.showStatus( errorMessage || config.errorMessage || 'Could not add these items.' );
			} ).always( () => {
				button.removeClass( 'is-loading' ).text( originalLabel );
				this.updateTotal();
			} );
		}

		updateTierHighlight( total ) {
			const tiers = $( '#wtp-discount-list-container li[data-min]' );
			let active = null;
			tiers.each( function() {
				const min = Number.parseInt( $( this ).data( 'min' ), 10 ) || 0;
				if ( total >= min && ( ! active || min > active.min ) ) {
					active = { element: this, min };
				}
			} );
			tiers.removeClass( 'is-active' ).attr( 'aria-current', 'false' );
			if ( active ) {
				$( active.element ).addClass( 'is-active' ).attr( 'aria-current', 'true' );
			}
		}
	}

	new MultiSizeUI( 'paSizeUI' );

	/** Add structural step labels without changing plugin templates. */
	$( '.variations_form .variation-ui' ).first().attr( 'data-step', '01  Choose colour' );
	$( '.variations_form .extra-options' ).first().attr( 'data-step', '03  Personalise' );

	/** Mirror checked state on image-option cards for clear visual feedback. */
	$( document ).on( 'change', '.thwepo-image-group-item input', function() {
		$( this ).closest( '.thwepo-image-group-item' ).toggleClass( 'is-selected', this.checked );
	} );
	$( '.thwepo-image-group-item input:checked' ).trigger( 'change' );
}( jQuery ) );
