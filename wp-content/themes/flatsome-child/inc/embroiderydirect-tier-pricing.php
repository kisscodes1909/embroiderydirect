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
			'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
			'ajaxNonce'       => wp_create_nonce( 'ed_multi_size_add_to_cart' ),
			'addingLabel'     => __( 'Adding…', 'flatsome-child' ),
			'addedLabel'      => __( 'Added to cart', 'flatsome-child' ),
			'emptyMessage'    => __( 'Choose a quantity for at least one size.', 'flatsome-child' ),
			'errorMessage'    => __( 'Could not add these items. Please try again.', 'flatsome-child' ),
		)
	);
}

/** Add all selected size variations and return WooCommerce cart fragments. */
add_action( 'wp_ajax_ed_multi_size_add_to_cart', 'ed_ajax_multi_size_add_to_cart' );
add_action( 'wp_ajax_nopriv_ed_multi_size_add_to_cart', 'ed_ajax_multi_size_add_to_cart' );

function ed_ajax_multi_size_add_to_cart() {
	check_ajax_referer( 'ed_multi_size_add_to_cart', 'nonce' );

	$product_id = isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0;
	$quantities = isset( $_POST['qtyNumber'] ) && is_array( $_POST['qtyNumber'] )
		? wc_clean( wp_unslash( $_POST['qtyNumber'] ) )
		: array();
	$added      = 0;

	if ( ! $product_id || empty( $quantities ) ) {
		wp_send_json_error( array( 'message' => __( 'Choose a quantity for at least one size.', 'flatsome-child' ) ), 400 );
	}

	foreach ( $quantities as $variation_id => $raw_quantity ) {
		$variation_id = absint( $variation_id );
		$quantity     = wc_stock_amount( $raw_quantity );

		if ( $quantity < 1 ) {
			continue;
		}

		$variation = wc_get_product( $variation_id );
		if ( ! $variation || ! $variation->is_type( 'variation' ) || $product_id !== $variation->get_parent_id() ) {
			wp_send_json_error( array( 'message' => __( 'One of the selected sizes is invalid.', 'flatsome-child' ) ), 400 );
		}

		$passed_validation = apply_filters(
			'woocommerce_add_to_cart_validation',
			true,
			$product_id,
			$quantity,
			$variation_id,
			$variation->get_variation_attributes()
		);

		if ( ! $passed_validation ) {
			continue;
		}

		$cart_item_key = WC()->cart->add_to_cart(
			$product_id,
			$quantity,
			$variation_id,
			$variation->get_variation_attributes()
		);

		if ( $cart_item_key ) {
			$added += $quantity;
		}
	}

	if ( $added < 1 ) {
		$message = wc_notice_count( 'error' )
			? wp_strip_all_tags( wc_print_notices( true ) )
			: __( 'Could not add these items to the cart.', 'flatsome-child' );
		wp_send_json_error( array( 'message' => $message ), 400 );
	}

	WC()->cart->calculate_totals();
	ob_start();
	woocommerce_mini_cart();
	$mini_cart = ob_get_clean();
	$fragments = apply_filters(
		'woocommerce_add_to_cart_fragments',
		array(
			'div.widget_shopping_cart_content' => '<div class="widget_shopping_cart_content">' . $mini_cart . '</div>',
		)
	);

	wp_send_json(
		array(
			'fragments' => $fragments,
			'cart_hash' => WC()->cart->get_cart_hash(),
			'count'     => $added,
			'message'   => sprintf(
				/* translators: %d: number of items added. */
				_n( '%d item added to your cart.', '%d items added to your cart.', $added, 'flatsome-child' ),
				$added
			),
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
