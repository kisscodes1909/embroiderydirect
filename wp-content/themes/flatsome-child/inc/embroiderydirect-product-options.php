<?php
/**
 * Product-option and logo-fee customisations for Embroidery Direct.
 *
 * Dependencies:
 * - WooCommerce
 * - WooCommerce Extra Product Options Pro (ThemeHigh)
 *
 * The constants below are the store-specific values that were previously
 * hardcoded inside vendor plugin files. Change them here if field names,
 * labels or prices are changed in the Product Options admin screen.
 */

defined( 'ABSPATH' ) || exit;

const ED_FREE_LOGO_SETUP_SUBTOTAL = 200;
const ED_LOGO_POSITION_COST       = 5;
const ED_LOGO_SETUP_FEE_ID        = 'choose-logo-setup';
const ED_LOGO_POSITION_LABEL      = 'Choose Logo Position';
const ED_LOGO_POSITION_FEE_LABEL  = 'Logo Position Fee';

/**
 * Reuse an uploaded logo while one multi-size submission creates several
 * cart items. The plugin exposes a dynamic filter based on the field name.
 *
 * These filter names correspond to Product Options fields `upload_logo` and
 * `second_upload_logo`. Update them if those field names are renamed.
 */
add_filter( 'thwepo_file_upload_logo_value', 'ed_reuse_product_option_upload', 100, 3 );
add_filter( 'thwepo_file_second_upload_logo_value', 'ed_reuse_product_option_upload', 100, 3 );

function ed_reuse_product_option_upload( $posted_value, $field, $extra_options ) {
	global $thwepo_files;

	if ( isset( $thwepo_files[ $field->name ] ) ) {
		return $thwepo_files[ $field->name ];
	}

	return $posted_value;
}

/**
 * Add the position fee selected through Product Options.
 *
 * Formula: selected positions x $5 x cart quantity. A product may contain
 * multiple logo-position fields (for example, a first and second logo).
 */
add_action( 'woocommerce_cart_calculate_fees', 'ed_add_logo_position_fee', 20 );

function ed_add_logo_position_fee( $cart ) {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return;
	}

	$total_fee = 0;

	foreach ( $cart->get_cart() as $cart_item ) {
		$options = isset( $cart_item['thwepo_options'] ) && is_array( $cart_item['thwepo_options'] )
			? $cart_item['thwepo_options']
			: array();

		foreach ( $options as $option ) {
			if ( ED_LOGO_POSITION_LABEL !== ( $option['label'] ?? '' ) ) {
				continue;
			}

			$positions  = (array) ( $option['value'] ?? array() );
			$total_fee += count( $positions ) * ED_LOGO_POSITION_COST * (int) $cart_item['quantity'];
		}
	}

	if ( $total_fee > 0 ) {
		$cart->add_fee( ED_LOGO_POSITION_FEE_LABEL, $total_fee );
	}
}

/**
 * Waive the Product Options logo setup fee when merchandise subtotal is over
 * $200. Priority 100 lets the Product Options plugin add its fee first.
 */
add_action( 'woocommerce_cart_calculate_fees', 'ed_waive_logo_setup_fee', 100 );

function ed_waive_logo_setup_fee( $cart ) {
	if ( $cart->get_subtotal() <= ED_FREE_LOGO_SETUP_SUBTOTAL ) {
		return;
	}

	foreach ( $cart->fees_api()->get_fees() as $fee_id => $fee ) {
		if ( ED_LOGO_SETUP_FEE_ID === $fee_id || ED_LOGO_SETUP_FEE_ID === $fee->id ) {
			$fees = $cart->fees_api()->get_fees();
			unset( $fees[ $fee_id ] );
			$cart->fees_api()->set_fees( $fees );
			break;
		}
	}
}
