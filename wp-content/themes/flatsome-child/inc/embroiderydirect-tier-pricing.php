<?php
/**
 * Multi-size variation UI and tier-pricing integration.
 *
 * Store attribute slugs:
 * - Colour: pa_colour
 * - Size:   pa_size
 */

defined( 'ABSPATH' ) || exit;

const ED_COLOUR_ATTRIBUTE = 'pa_colour';
const ED_SIZE_ATTRIBUTE   = 'pa_size';

/** Load the multi-size UI only on product pages. */
add_action( 'wp_enqueue_scripts', 'ed_enqueue_multi_size_assets', 30 );

function ed_enqueue_multi_size_assets() {
	if ( ! is_product() ) {
		return;
	}

	$theme = wp_get_theme();

	// The plugin still provides pricing data and cart calculations, but its
	// presentation rules fight the child-theme grid (fixed heights, dotted
	// borders and flex margins). Keep the logic and own the product-page UI.
	wp_dequeue_style( 'wtp-front-style' );
	wp_deregister_style( 'wtp-front-style' );

	wp_enqueue_style(
		'ed-multi-size',
		get_stylesheet_directory_uri() . '/assets/css/embroiderydirect-product.css',
		array(),
		$theme->get( 'Version' )
	);

	wp_enqueue_script(
		'ed-multi-size',
		get_stylesheet_directory_uri() . '/assets/js/embroiderydirect-multi-size.js',
		array( 'jquery' ),
		$theme->get( 'Version' ),
		true
	);

	wp_localize_script(
		'ed-multi-size',
		'embroideryDirectProduct',
		array(
			'colourAttribute' => ED_COLOUR_ATTRIBUTE,
			'sizeAttribute'   => ED_SIZE_ATTRIBUTE,
			'sizesLabel'      => __( 'Sizes & Quantities', 'flatsome-child' ),
		)
	);
}

/** Change the customer-facing Colour label without editing WooCommerce. */
add_filter( 'woocommerce_attribute_label', 'ed_colour_attribute_label', 100, 3 );

function ed_colour_attribute_label( $label, $name, $product ) {
	return ED_COLOUR_ATTRIBUTE === $name || 'Colour' === $label
		? __( 'Choose Colour', 'flatsome-child' )
		: $label;
}

/** Move the short description above the product summary, matching the old UI. */
add_action( 'wp', 'ed_move_product_short_description' );

function ed_move_product_short_description() {
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20 );
	add_action( 'woocommerce_before_single_product_summary', 'woocommerce_template_single_excerpt', 30 );
}

/** Keep the tier table at the position previously hardcoded in the plugin. */
add_action( 'woocommerce_single_product_summary', 'ed_render_tier_pricing_table', 12 );

function ed_render_tier_pricing_table() {
	if ( function_exists( 'wtp_product_discount_list_preview' ) ) {
		wtp_product_discount_list_preview();
	}
}

/**
 * Apply a tier to the combined quantity of all size variations belonging to
 * the same variable product. The original plugin calculates each variation
 * independently, which is not suitable for a mixed-size garment order.
 */
add_action( 'wp_loaded', 'ed_replace_tier_pricing_cart_calculator', 20 );

function ed_replace_tier_pricing_cart_calculator() {
	if ( ! function_exists( 'wtp_calculator_product' ) ) {
		return;
	}

	remove_action( 'woocommerce_before_calculate_totals', 'wtp_product_alter_price_cart', 9999 );
	add_action( 'woocommerce_before_calculate_totals', 'ed_apply_parent_quantity_tier_price', 9999 );
}

function ed_apply_parent_quantity_tier_price( $cart ) {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return;
	}

	// WooCommerce may fire this action more than once during a request. Avoid
	// applying a tier discount repeatedly to an already-discounted price.
	if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 ) {
		return;
	}

	$parent_quantities = ed_get_parent_product_quantities( $cart );

	foreach ( $cart->get_cart() as $cart_item ) {
		$product      = $cart_item['data'];
		$product_id   = (int) $cart_item['product_id'];
		$variation_id = (int) $cart_item['variation_id'];
		$price        = $product->get_price();
		$quantity     = $parent_quantities[ $product_id ] ?? (int) $cart_item['quantity'];
		$new_price    = (string) wtp_calculator_product(
			$product_id,
			$price,
			$quantity,
			$product->get_type(),
			$variation_id
		);

		if ( '' !== $new_price ) {
			$product->set_price( $new_price );
			$product->set_sale_price( $new_price );
			$product->set_regular_price( $price );
		} else {
			$product->set_price( $price );
		}
	}
}

function ed_get_parent_product_quantities( $cart ) {
	$quantities = array();

	foreach ( $cart->get_cart() as $cart_item ) {
		$product_id = (int) $cart_item['product_id'];
		$quantities[ $product_id ] = ( $quantities[ $product_id ] ?? 0 ) + (int) $cart_item['quantity'];
	}

	return $quantities;
}
