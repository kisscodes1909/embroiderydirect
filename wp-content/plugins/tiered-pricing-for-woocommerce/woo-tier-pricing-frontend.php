<?php


$tier_is_enable = get_option( 'wc_settings_enable_tier_pricing' );
if ( 'yes' != $tier_is_enable ) { // no need to process any thing from tier pricing.
	return;
}

// Adding scripts files to frontend.
add_action( 'wp_enqueue_scripts', 'wtp_front_scripts' );
function wtp_front_scripts() {
	global $post;
	if ( isset( $post ) && 'product' === $post->post_type ) {
		// frontend style enqueuing for product page only.

		?>
		<style>
			:root {
				--wtp-border-color: <?php echo esc_attr( get_option( 'wtp_tooltip_icon_color', '#000' ) ); ?>;
				--wtp-active-color: <?php echo esc_attr( get_option( 'wtp_active_price_bg_color', '#ddd' ) ); ?>;
			}

			<?php
			if ( 'enabled' === get_option( 'wtp_tooltip_border', 'disabled' ) ) {
				?>
				.tippy-box {
					border:  1px solid var(--wtp-border-color)!important;
					border-radius:  4px;
				}
				<?php
			}
			?>
		</style>
		<?php

		wp_enqueue_style( 'wtp-front-style', WTP_ROOT_URL . 'assets/css/front_style.css', array(), '1.0.0&t=' . gmdate( 'dmYhis' ) );

		//wp_enqueue_script( 'wtp-tippy-popper', 'https://unpkg.com/@popperjs/core@2', array( 'jquery' ), WTP_VERSION, true );
		//wp_enqueue_script( 'wtp-tippy', 'https://unpkg.com/tippy.js@6', array( 'jquery', 'wtp-tippy-popper' ), WTP_VERSION, true );

		$params = array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'wtp_front' ),
			'summary' => get_option( 'wtp_summary_display_type', 'none' )
		);
		//wp_register_script( 'wtp-front-script', WTP_ROOT_URL . 'assets/js/front_script.js', array( 'jquery', 'wtp-tippy' ), '1.0.0&t=' . gmdate( 'dmYhis' ), true );
		wp_register_script( 'wtp-PaSize', WTP_ROOT_URL . 'assets/js/frontPaSize.js', array( 'jquery' ), '1.0.0&t=' . gmdate( 'dmYhis' ), true );

		//wp_localize_script( 'wtp-front-script', 'wtp_front_script', $params );

		wp_localize_script( 'wtp-PaSize', 'wtp_PaSize_script', $params );


		//wp_enqueue_script( 'wtp-front-script' );
		wp_enqueue_script( 'wtp-PaSize' );


	}
}

if ( 'enabled' === get_option( 'wtp_hide_price', 'disabled' ) && ! is_user_logged_in() ) {
	add_filter( 'woocommerce_get_price_html', 'wtp_hide_price_addcart_not_logged_in', 9999, 2 );
	add_filter( 'woocommerce_subscription_price_string', 'woocommerce_subscription_price_string_removed', 999, 2 );
	add_filter( 'woocommerce_subscriptions_product_price_string', 'woocommerce_subscription_price_string_removed', 999, 2 );	
	function woocommerce_subscription_price_string_removed( $price, $product ) {		
		return '';
	}

	function wtp_hide_price_addcart_not_logged_in( $price, $product ) {

		if ( ! is_user_logged_in() ) {
			$price = sprintf( '<p class="wtp-non-logged">%1$s</p>', get_option( 'wtp_hide_price_text', '' ) );
			remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );
			remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
		}

		return $price;
	}
} else {

	// if customization setting is for tooltip
	if ( 'tooltip' === get_option( 'wtp_display_type', 'block' ) ) {

		add_filter( 'woocommerce_get_price_html', 'add_tooltip_for_tier_price', 999, 2 );
		function add_tooltip_for_tier_price( $price, $instance ) {
			global $woocommerce_loop;

			if ( ! empty( $woocommerce_loop ) && is_product() && 'variable' != $instance->get_type() && ! 'related' == $woocommerce_loop['name'] ) {
				$color   = get_option( 'wtp_tooltip_icon_color', '#000' );
				$size    = get_option( 'wtp_tooltip_icon_size', '20' );
				$tooltip = '<span id="wtp-tooltip" style="display:none"><svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" style="fill:' . $color . '"><path d="M12 0c-6.627 0-12 5.373-12 12s5.373 12 12 12 12-5.373 12-12-5.373-12-12-12zm-2.033 16.01c.564-1.789 1.632-3.932 1.821-4.474.273-.787-.211-1.136-1.74.209l-.34-.64c1.744-1.897 5.335-2.326 4.113.613-.763 1.835-1.309 3.074-1.621 4.03-.455 1.393.694.828 1.819-.211.153.25.203.331.356.619-2.498 2.378-5.271 2.588-4.408-.146zm4.742-8.169c-.532.453-1.32.443-1.761-.022-.441-.465-.367-1.208.164-1.661.532-.453 1.32-.442 1.761.022.439.466.367 1.209-.164 1.661z"/></svg></span>';
				return $price . $tooltip;
			}

			return $price;
		}
	}

	// showing product summary if enable	
	if ( 'none' != get_option( 'wtp_summary_display_type', 'none' ) ) {
		add_action( 'woocommerce_after_add_to_cart_button', 'wtp_product_summary_preview', 9999 );
		function wtp_product_summary_preview() {
			if ( 'table' === get_option( 'wtp_summary_display_type' ) ) { // table summary
				?>
				<div class="wtp-summary wtp-hide">
					<h4><?php echo esc_html__( 'Summary', 'wtp' ); ?></h4>
					<table class="has-background">
						<tbody>
							<tr>
								<th><?php echo esc_html( get_option( 'wtp_summary_inline_each_label', 'Unit Price:' ) ); ?></th>
								<td id="wtp-unit"></td>
							</tr>
							<tr>
								<th><?php echo esc_html( get_option( 'wtp_summary_inline_total_label', 'Subtotal: ' ) ); ?></th>
								<td id="wtp-subtotal"></td>
							</tr>
						</tbody>
					</table>
				</div>
				<?php
			} else { // inline summary
				?>
				<div class="wtp-summary wtp-hide">
					<h4><?php echo esc_html__( 'Summary', 'wtp' ); ?></h4>
					<ul>
						<li>
							<strong><?php echo esc_html( get_option( 'wtp_summary_inline_each_label', 'Unit Price:' ) ); ?></strong>
							<span id="wtp-unit"></span>
						</li>
						<li>
							<strong><?php echo esc_html( get_option( 'wtp_summary_inline_total_label', 'Subtotal: ' ) ); ?></strong>
							<span id="wtp-subtotal"></span>
						</li>
					</ul>
				</div>
				<?php
			}
		}
	}

	if ( 'enabled' === get_option( 'wtp_tier_range_price_show', 'disabled' ) && ! is_admin() ) {		
		add_filter( 'woocommerce_get_price_html', 'wtp_tier_price_range', 99, 2 );
		function wtp_tier_price_range( $price, $product ) {
			if ( 'variation' == $product->get_type() || 'variable' == $product->get_type() || 'variable-subscription' == $product->get_type() || 'grouped' == $product->get_type() ) {
				return $price;
			}

			$post_id    = $product->get_id();
			$product    = wc_get_product( $post_id );
			$categories = get_the_terms( $post_id, 'product_cat' );			
			if ( is_array( $categories ) ) {
				$first_cat  = $categories[0]->slug;
			} else {
				$first_cat = '';
			}

			$user = wp_get_current_user();

			if ( ! empty( $user ) && $user->ID > 0 ) {
				$current_user_id   = $user->ID;
				$current_user_role = implode( ',', $user->roles );
			} else {
				$current_user_id   = 0;
				$current_user_role = '';
			}

			$general_category_rules = get_option( 'general_category_rules', false );
			$general_user_rules     = get_option( 'general_user_rules', false );
			$general_role_rules     = get_option( 'general_role_rules', false );

			if ( ! ( 'grouped' == $product->get_type() ) && ! ( 'variable' == $product->get_type() ) && ! ( 'variation' == $product->get_type() ) && ! ( 'variable-subscription' == $product->get_type() ) ) {

				$price                    = $product->get_price();
				$wtp_product_tier_setting = get_post_meta( $post_id, 'wtp_product_tier_setting', true );

				if ( ! empty( $wtp_product_tier_setting ) && in_array( 'false', $wtp_product_tier_setting['disabled'] ) ) { // Product based tier pricing.
					foreach ( $wtp_product_tier_setting['min_qty'] as $key => $value ) {
						if ( isset( $wtp_product_tier_setting['disabled'][ $key ] ) && 'false' === $wtp_product_tier_setting['disabled'][ $key ] ) {

							$discount_type  = ! empty( $wtp_product_tier_setting['discount_type'][ $key ] ) ? $wtp_product_tier_setting['discount_type'][ $key ] : 'percentage';
							$discount_value = ! empty( $wtp_product_tier_setting['discount_value'][ $key ] ) ? $wtp_product_tier_setting['discount_value'][ $key ] : 0;

							if ( 'percentage' == $discount_type ) {
								$discount_value = ( $price - ( $discount_value / 100 ) * $price );
							} else { // fixed
								$discount_value = ( $price - $discount_value );
							}

							$discount_value = ( $discount_value >= 0 ) ? $discount_value : 0;
							if ( 'incl' == get_option( 'woocommerce_tax_display_shop' ) ) {
								$discount_value = wc_get_price_including_tax( $product, array( 'price' => $discount_value ) );
							}

							$wtp_product_tier_setting['price'][] = $discount_value;
						}
					}

					if ( isset( $wtp_product_tier_setting['price'] ) ) {
						$min_price = min( $wtp_product_tier_setting['price'] );
						if ( 'low_high' == get_option( 'wtp_display_tier_price_range', 'low_high' ) ) {
							return wc_price( $min_price ) . ' - ' . wc_price( $price );
						} else {
							return wc_price( $price ) . ' - ' . wc_price( $min_price );
						}
					} else {
						return wc_price($price);
					}
				}

				if ( ! empty( $general_category_rules ) ) { // Category based tier pricing.

					$flag = false;
					foreach ( $general_category_rules as $key => $value ) {
						if ( $first_cat == $value['category'] && ! isset( $value['disabled'] ) ) {
							$flag = true;
							break;
						}
					}

					if ( $flag ) {
						foreach ( $general_category_rules as $key => $value ) {
							if ( ! isset( $value['disabled'] ) && $first_cat == $value['category'] ) {
								$discount_type  = ! empty( $value['discount_type'] ) ? $value['discount_type'] : 'percentage';
								$discount_value = ! empty( $value['discount_value'] ) ? $value['discount_value'] : 0;

								if ( 'percentage' == $discount_type ) {
									$discount_value = ( $price - ( $discount_value / 100 ) * $price );
								} else { // fixed
									$discount_value = ( $price - $discount_value );
								}

								$discount_value = ( $discount_value >= 0 ) ? $discount_value : 0;
								if ( 'incl' == get_option( 'woocommerce_tax_display_shop' ) ) {
									$discount_value = wc_get_price_including_tax( $product, array( 'price' => $discount_value ) );
								}

								$general_category_rules['price'][] = $discount_value;

							}
						}

						if ( isset( $general_category_rules['price'] ) ) {
							$min_price = min( $general_category_rules['price'] );
							if ( 'low_high' == get_option( 'wtp_display_tier_price_range', 'low_high' ) ) {
								return wc_price( $min_price ) . ' - ' . wc_price( $price );
							} else {
								return wc_price( $price ) . ' - ' . wc_price( $min_price );
							}
						} else {
							return wc_price($price);
						}
					}
				}

				if ( ! empty( $general_user_rules ) ) { // User based tier pricing.

					$flag = false;
					foreach ( $general_user_rules as $key => $value ) {
						if ( $current_user_id == $value['user']  && ! isset( $value['disabled'] ) ) {
							$flag = true;
							break;
						}
					}

					if ( $flag ) {
						foreach ( $general_user_rules as $key => $value ) {
							if ( ! isset( $value['disabled'] ) && $current_user_id == $value['user'] ) {
								$discount_type  = ! empty( $value['discount_type'] ) ? $value['discount_type'] : 'percentage';
								$discount_value = ! empty( $value['discount_value'] ) ? $value['discount_value'] : 0;

								if ( 'percentage' == $discount_type ) {
									$discount_value = ( $price - ( $discount_value / 100 ) * $price );
								} else { // fixed
									$discount_value = ( $price - $discount_value );
								}

								$discount_value = ( $discount_value >= 0 ) ? $discount_value : 0;
								if ( 'incl' == get_option( 'woocommerce_tax_display_shop' ) ) {
									$discount_value = wc_get_price_including_tax( $product, array( 'price' => $discount_value ) );
								}

								$general_user_rules['price'][] = $discount_value;
							}
						}

						if ( isset( $general_user_rules['price'] ) ) {
							$min_price = min( $general_user_rules['price'] );
							if ( 'low_high' == get_option( 'wtp_display_tier_price_range', 'low_high' ) ) {
								return wc_price( $min_price ) . ' - ' . wc_price( $price );
							} else {
								return wc_price( $price ) . ' - ' . wc_price( $min_price );
							}
						} else {
							return wc_price($price);
						}
					}
				}

				if ( ! empty( $general_role_rules ) ) { // Role based tier pricing
					$flag = false;
					foreach ( $general_role_rules as $key => $value ) {
						if ( $current_user_role == $value['role']  && ! isset( $value['disabled'] ) ) {
							$flag = true;
							break;
						}
					}

					if ( $flag ) {
						foreach ( $general_role_rules as $key => $value ) {
							if ( ! isset( $value['disabled'] ) && $current_user_role == $value['role'] ) {
								$discount_type  = ! empty( $value['discount_type'] ) ? $value['discount_type'] : 'percentage';
								$discount_value = ! empty( $value['discount_value'] ) ? $value['discount_value'] : 0;

								if ( 'percentage' == $discount_type ) {
									$discount_value = ( $price - ( $discount_value / 100 ) * $price );
								} else { // fixed
									$discount_value = ( $price - $discount_value );
								}

								$discount_value = ( $discount_value >= 0 ) ? $discount_value : 0;
								if ( 'incl' == get_option( 'woocommerce_tax_display_shop' ) ) {
									$discount_value = wc_get_price_including_tax( $product, array( 'price' => $discount_value ) );
								}

								$general_role_rules['price'][] = $discount_value;
							}
						}

						if ( isset( $general_role_rules['price'] ) ) {
							$min_price = min( $general_role_rules['price'] );
							if ( 'low_high' == get_option( 'wtp_display_tier_price_range', 'low_high' ) ) {
								return wc_price( $min_price ) . ' - ' . wc_price( $price );
							} else {
								return wc_price( $price ) . ' - ' . wc_price( $min_price );
							}
						} else {
							return wc_price($price);
						}
					}
				}
			}

			return wc_price($price);
		}
	}

	//add_action( get_option( 'wtp_table_position', 'woocommerce_before_add_to_cart_button' ), 'wtp_product_discount_list_preview', 99 );
	// Showing discount list & discount calculated depends on setting.
	function wtp_product_discount_list_preview() {
		global $post;
		$post_id    = $post->ID;
		$product    = wc_get_product( $post_id );
		$categories = get_the_terms( $post_id, 'product_cat' );			
		if ( is_array( $categories ) ) {
			$first_cat  = $categories[0]->slug;
		} else {
			$first_cat = '';
		}
		$user       = wp_get_current_user();
		// print_r( $user );
		if ( ! empty( $user ) && $user->ID > 0 ) {
			$current_user_id   = $user->ID;
			$current_user_role = implode( ',', $user->roles );
		} else {
			$current_user_id   = 0;
			$current_user_role = '';
		}

		$general_category_rules = get_option( 'general_category_rules', false );
		$general_user_rules     = get_option( 'general_user_rules', false );
		$general_role_rules     = get_option( 'general_role_rules', false );

		if ( 'tooltip' === get_option( 'wtp_display_type', 'block' ) ) {
			$hide_class = 'wtp-hide';
		} else {
			$hide_class = '';
		}

		if ( ! ( 'grouped' == $product->get_type() ) && ( 'variable' == $product->get_type() ) && ! ( 'variation' == $product->get_type() ) && ! ( 'variable-subscription' == $product->get_type() ) ) {

			// echo '<pre>$product$product';
			// print_r( $product );
			// echo '</pre>';

			// $price = wc_get_price_including_tax( $product, array('price' => $product->get_price() ) );
			$price                    = $product->get_price();
			$wtp_product_tier_setting = get_post_meta( $post_id, 'wtp_product_tier_setting', true );


			// echo '<pre>$wtp_product_tier_setting$wtp_product_tier_setting';
			// print_r( $wtp_product_tier_setting );
			// echo '</pre>';
			if ( ! empty( $wtp_product_tier_setting ) && in_array( 'false', $wtp_product_tier_setting['disabled'] ) ) { // Product based tier pricing.
				?>
				<div id="wtp-tier-data" class="<?php echo esc_attr( $hide_class ); ?>">
					<div id="wtp-discount-list-container" class="wtp-simple-product-list-container wtp-product-based-tier" >
						<h4><?php echo esc_html( get_option( 'wtp_table_title', 'Discount Price List' ) ); ?></h4>
						<?php
						if ( 'tooltip' === get_option( 'wtp_display_type', 'block' ) ) {
							$wtp_table = 'wtp-table';
						} else {
							$wtp_table = '';
						}
						?>
						<ul class="<?php echo esc_attr( $wtp_table );  ?>">
							<?php
							if ( 'tooltip' === get_option( 'wtp_display_type', 'block' ) ) {
								?>
								<li class="wtp-table-head">
									<span><?php echo esc_html( get_option( 'wtp_qty_col_text', 'Quantity' ) ); ?></span>
									<span><?php echo esc_html( get_option( 'wtp_price_col_text', 'Price' ) ); ?></span>
									<?php
									if ( 'enabled' === get_option( 'wtp_show_discount_col', 'disabled' ) ) {
										?>
										<span><?php echo esc_html( get_option( 'wtp_discount_col_text', 'Discount' ) ); ?></span>
										<?php
									}
									?>
								</li>
								<?php
							}

							$is_available_enable = false;
							foreach ( $wtp_product_tier_setting['min_qty'] as $key => $value ) {
								if ( isset( $wtp_product_tier_setting['disabled'][ $key ] ) && 'false' === $wtp_product_tier_setting['disabled'][ $key ] ) {

									// if any one tier is enable. make this true.
									$is_available_enable = true;

									$min_qty        = ( ! empty( $value ) && $value > 0 ) ? $value : 1;
									$max_qty        = ( ! empty( $wtp_product_tier_setting['max_qty'][ $key ] ) && $wtp_product_tier_setting['max_qty'][ $key ] >= $min_qty ) ? $wtp_product_tier_setting['max_qty'][ $key ] : '&#8734;';
									$data_max_qty   = ( ! empty( $wtp_product_tier_setting['max_qty'][ $key ] ) && $wtp_product_tier_setting['max_qty'][ $key ] >= $min_qty ) ? $wtp_product_tier_setting['max_qty'][ $key ] : -1;
									$discount_type  = ! empty( $wtp_product_tier_setting['discount_type'][ $key ] ) ? $wtp_product_tier_setting['discount_type'][ $key ] : 'percentage';
									$discount_value = ! empty( $wtp_product_tier_setting['discount_value'][ $key ] ) ? $wtp_product_tier_setting['discount_value'][ $key ] : 0;

									if ( 'percentage' == $discount_type ) {
										$discount_value = ( $price - ( $discount_value / 100 ) * $price );
									} else { // fixed
										$discount_value = ( $price - $discount_value );
									}

									$discount_value = ( $discount_value >= 0 ) ? $discount_value : 0;
									if ( 'incl' == get_option( 'woocommerce_tax_display_shop' ) ) {
										$discount_value = wc_get_price_including_tax( $product, array( 'price' => $discount_value ) );
									}
									?>
									<li data-min="<?php echo esc_attr( $min_qty ); ?>" data-max="<?php echo esc_attr( $data_max_qty ); ?>" data-price="<?php echo esc_attr( $discount_value ); ?>">
										<span class="ma-quantity-range"><?php echo esc_html__( apply_filters( 'wtp_change_tier_label', 'Pcs' ), 'wtp' ) . ' ' . esc_attr( $min_qty ) . ' - ' . esc_attr( $max_qty ); ?></span>
										<span class="pre-inquiry-price"><?php echo wp_kses_post( wc_price( $discount_value ) ); ?></span>
										<?php
										if ( 'tooltip' === get_option( 'wtp_display_type', 'block' ) && 'enabled' === get_option( 'wtp_show_discount_col', 'disabled' ) ) {
											$show_discount_percentage = 100 - ( ( $discount_value / $price ) * 100 );
											?>
											<span><?php echo sprintf( '%1$s%2$s', esc_attr( number_format( $show_discount_percentage, 2 ) ), esc_html__( '% OFF', 'wtp' ) ); ?></span>
											<?php
										}
										?>
									</li>
									<?php
								}
							}
							?>
						</ul>					
					</div>
				</div>
				<?php
				if ( $is_available_enable ) {
					return false;
				}
			}			

			if ( ! empty( $general_category_rules ) ) { // Category based tier pricing.

				$flag = false;
				foreach ( $general_category_rules as $key => $value ) {
					if ( $first_cat == $value['category'] && ! isset( $value['disabled'] ) ) {
						$flag = true;
						break;
					}
				}

				if ( $flag ) {
					?>
					<div id="wtp-tier-data" class="<?php echo esc_attr( $hide_class ); ?>">
						<div id="wtp-discount-list-container" class="wtp-simple-product-list-container wtp-category-based-tier" >
							<h4><?php echo esc_html( get_option( 'wtp_table_title', 'Discount Price List' ) ); ?></h4>
							<?php
							if ( 'tooltip' === get_option( 'wtp_display_type', 'block' ) ) {
								$wtp_table = 'wtp-table';
							} else {
								$wtp_table = '';
							}
							?>
							<ul class="<?php echo esc_attr( $wtp_table ); ?>">
								<?php
								if ( 'tooltip' === get_option( 'wtp_display_type', 'block' ) ) {
									?>
									<li class="wtp-table-head">
										<span><?php echo esc_html( get_option( 'wtp_qty_col_text', 'Quantity' ) ); ?></span>
										<span><?php echo esc_html( get_option( 'wtp_price_col_text', 'Price' ) ); ?></span>
										<?php
										if ( 'enabled' === get_option( 'wtp_show_discount_col', 'disabled' ) ) {
											?>
											<span><?php echo esc_html( get_option( 'wtp_discount_col_text', 'Discount' ) ); ?></span>
											<?php
										}
										?>
									</li>
									<?php
								}

								$is_available_enable = false;
								foreach ( $general_category_rules as $key => $value ) {
									if ( ! isset( $value['disabled'] ) && $first_cat == $value['category'] ) {
										$is_available_enable = true;
										$min_qty             = ( ! empty( $value['min_qty'] ) && $value['min_qty'] > 0 ) ? $value['min_qty'] : 1;
										$max_qty             = ( ! empty( $value['max_qty'] ) && $value['max_qty'] >= $min_qty ) ? $value['max_qty'] : '&#8734;';
										$data_max_qty        = ( ! empty( $value['max_qty'] ) && $value['max_qty'] >= $min_qty ) ? $value['max_qty'] : -1;
										$discount_type       = ! empty( $value['discount_type'] ) ? $value['discount_type'] : 'percentage';
										$discount_value      = ! empty( $value['discount_value'] ) ? $value['discount_value'] : 0;

										if ( 'percentage' == $discount_type ) {
											$discount_value = ( $price - ( $discount_value / 100 ) * $price );
										} else { // fixed
											$discount_value = ( $price - $discount_value );
										}
										$show_discount_percentage = 100 - ( ( $discount_value / $price ) * 100 );

										$discount_value = ( $discount_value >= 0 ) ? $discount_value : 0;
										if ( 'incl' == get_option( 'woocommerce_tax_display_shop' ) ) {
											$discount_value = wc_get_price_including_tax( $product, array( 'price' => $discount_value ) );
										}										?>
										<li data-min="<?php echo esc_attr( $min_qty ); ?>" data-max="<?php echo esc_attr( $data_max_qty ); ?>" data-price="<?php echo esc_attr( $discount_value ); ?>">
											<!-- <span class="ma-quantity-range"><?php echo esc_attr( $min_qty ) . ' - ' . esc_attr( $max_qty ) . ' ' . esc_html__( apply_filters( 'wtp_change_tier_label', 'Items' ), 'wtp' ); ?></span> -->
											<span class="ma-quantity-range"><?php echo esc_attr( $min_qty ) . '+';?></span>
											<span class="pre-inquiry-price"><?php echo wp_kses_post( wc_price( $discount_value ) ); ?></span>
											<span class="save-value wtp-save-off-label"><?php echo "Save ".number_format( $show_discount_percentage ) . "%" ?></span>
										</li>
										<?php
									}
								}
								?>
							</ul>
						</div>
					</div>
					<?php

					if ( $is_available_enable ) {
						return false;
					}
				}
			}			

			if ( ! empty( $general_user_rules ) ) { // User based tier pricing.

				$flag = false;
				foreach ( $general_user_rules as $key => $value ) {
					if ( $current_user_id == $value['user'] && ! isset( $value['disabled'] ) ) {
						$flag = true;
						break;
					}
				}

				if ( $flag ) {
					?>
					<div id="wtp-tier-data" class="<?php echo esc_attr( $hide_class ); ?>">
						<div id="wtp-discount-list-container" class="wtp-simple-product-list-container wtp-user-based-tier" >
							<h4><?php echo esc_html( get_option( 'wtp_table_title', 'Discount Price List' ) ); ?></h4>
							<?php
							if ( 'tooltip' === get_option( 'wtp_display_type', 'block' ) ) {
								$wtp_table = 'wtp-table';
							} else {
								$wtp_table = '';
							}
							?>
							<ul class="<?php echo esc_attr( $wtp_table ); ?>">
								<?php
								if ( 'tooltip' === get_option( 'wtp_display_type', 'block' ) ) {
									?>
									<li class="wtp-table-head">
										<span><?php echo esc_html( get_option( 'wtp_qty_col_text', 'Quantity' ) ); ?></span>
										<span><?php echo esc_html( get_option( 'wtp_price_col_text', 'Price' ) ); ?></span>
										<?php
										if ( 'enabled' === get_option( 'wtp_show_discount_col', 'disabled' ) ) {
											?>
											<span><?php echo esc_html( get_option( 'wtp_discount_col_text', 'Discount' ) ); ?></span>
											<?php
										}
										?>
									</li>
									<?php
								}

								$is_available_enable = false;
								foreach ( $general_user_rules as $key => $value ) {
									if ( ! isset( $value['disabled'] ) && $current_user_id == $value['user'] ) {

										$is_available_enable = true;
										$min_qty             = ( ! empty( $value['min_qty'] ) && $value['min_qty'] > 0 ) ? $value['min_qty'] : 1;
										$max_qty             = ( ! empty( $value['max_qty'] ) && $value['max_qty'] >= $min_qty ) ? $value['max_qty'] : '&#8734;';
										$data_max_qty        = ( ! empty( $value['max_qty'] ) && $value['max_qty'] >= $min_qty ) ? $value['max_qty'] : -1;
										$discount_type       = ! empty( $value['discount_type'] ) ? $value['discount_type'] : 'percentage';
										$discount_value      = ! empty( $value['discount_value'] ) ? $value['discount_value'] : 0;

										if ( 'percentage' == $discount_type ) {
											$discount_value = ( $price - ( $discount_value / 100 ) * $price );
										} else { // fixed
											$discount_value = ( $price - $discount_value );
										}

										$discount_value = ( $discount_value >= 0 ) ? $discount_value : 0;
										if ( 'incl' == get_option( 'woocommerce_tax_display_shop' ) ) {
											$discount_value = wc_get_price_including_tax( $product, array( 'price' => $discount_value ) );
										}
										?>
										<li data-min="<?php echo esc_attr( $min_qty ); ?>" data-max="<?php echo esc_attr( $data_max_qty ); ?>" data-price="<?php echo esc_attr( $discount_value ); ?>">
											<span class="ma-quantity-range"><?php echo esc_html__( apply_filters( 'wtp_change_tier_label', 'Pcs' ), 'wtp' ) . ' ' . esc_attr( $min_qty ) . ' - ' . esc_attr( $max_qty ); ?></span>
											<span class="pre-inquiry-price"><?php echo wp_kses_post( wc_price( $discount_value ) ); ?></span>
											<?php
											if ( 'tooltip' === get_option( 'wtp_display_type', 'block' ) && 'enabled' === get_option( 'wtp_show_discount_col', 'disabled' ) ) {
												$show_discount_percentage = 100 - ( ( $discount_value / $price ) * 100 );
												?>
												<span><?php echo sprintf( '%1$s%2$s', esc_attr( number_format( $show_discount_percentage, 2 ) ), esc_html__( '% OFF', 'wtp' ) ); ?></span>
												<?php
											}
											?>
										</li>
										<?php
									}
								}
								?>
							</ul>
						</div>
					</div>
					<?php

					if ( $is_available_enable ) {
						return false;
					}
				}
			}

			if ( ! empty( $general_role_rules ) ) { // Role based tier pricing
				$flag = false;
				foreach ( $general_role_rules as $key => $value ) {
					if ( $current_user_role == $value['role']  && ! isset( $value['disabled'] ) ) {
						$flag = true;
						break;
					}
				}

				if ( $flag ) {
					?>
					<div id="wtp-tier-data" class="<?php echo esc_attr( $hide_class ); ?>">
						<div id="wtp-discount-list-container" class="wtp-simple-product-list-container wtp-role-based-tier" >
							<h4><?php echo esc_html( get_option( 'wtp_table_title', 'Discount Price List' ) ); ?></h4>
							<?php
							if ( 'tooltip' === get_option( 'wtp_display_type', 'block' ) ) {
								$wtp_table = 'wtp-table';
							} else {
								$wtp_table = '';
							}
							?>
							<ul class="<?php echo esc_attr( $wtp_table ); ?>">
								<?php
								if ( 'tooltip' === get_option( 'wtp_display_type', 'block' ) ) {
									?>
									<li class="wtp-table-head">
										<span><?php echo esc_html( get_option( 'wtp_qty_col_text', 'Quantity' ) ); ?></span>
										<span><?php echo esc_html( get_option( 'wtp_price_col_text', 'Price' ) ); ?></span>
										<?php
										if ( 'enabled' === get_option( 'wtp_show_discount_col', 'disabled' ) ) {
											?>
											<span><?php echo esc_html( get_option( 'wtp_discount_col_text', 'Discount' ) ); ?></span>
											<?php
										}
										?>
									</li>
									<?php
								}

								$is_available_enable = false;
								foreach ( $general_role_rules as $key => $value ) {
									if ( ! isset( $value['disabled'] ) && $current_user_role == $value['role'] ) {
										$is_available_enable = true;
										$min_qty             = ( ! empty( $value['min_qty'] ) && $value['min_qty'] > 0 ) ? $value['min_qty'] : 1;
										$max_qty             = ( ! empty( $value['max_qty'] ) && $value['max_qty'] >= $min_qty ) ? $value['max_qty'] : '&#8734;';
										$data_max_qty        = ( ! empty( $value['max_qty'] ) && $value['max_qty'] >= $min_qty ) ? $value['max_qty'] : -1;
										$discount_type       = ! empty( $value['discount_type'] ) ? $value['discount_type'] : 'percentage';
										$discount_value      = ! empty( $value['discount_value'] ) ? $value['discount_value'] : 0;

										if ( 'percentage' == $discount_type ) {
											$discount_value = ( $price - ( $discount_value / 100 ) * $price );
										} else { // fixed
											$discount_value = ( $price - $discount_value );
										}

										$discount_value = ( $discount_value >= 0 ) ? $discount_value : 0;
										if ( 'incl' == get_option( 'woocommerce_tax_display_shop' ) ) {
											$discount_value = wc_get_price_including_tax( $product, array( 'price' => $discount_value ) );
										}
										?>
										<li data-min="<?php echo esc_attr( $min_qty ); ?>" data-max="<?php echo esc_attr( $data_max_qty ); ?>" data-price="<?php echo esc_attr( $discount_value ); ?>">
											<span class="ma-quantity-range"><?php echo esc_html__( apply_filters( 'wtp_change_tier_label', 'Pcs' ), 'wtp' ) . ' ' . esc_attr( $min_qty ) . ' - ' . esc_attr( $max_qty ); ?></span>
											<span class="pre-inquiry-price"><?php echo wp_kses_post( wc_price( $discount_value ) ); ?></span>
											<?php
											if ( 'tooltip' === get_option( 'wtp_display_type', 'block' ) && 'enabled' === get_option( 'wtp_show_discount_col', 'disabled' ) ) {
												$show_discount_percentage = 100 - ( ( $discount_value / $price ) * 100 );
												?>
												<span><?php echo sprintf( '%1$s%2$s', esc_attr( number_format( $show_discount_percentage, 2 ) ), esc_html__( '% OFF', 'wtp' ) ); ?></span>
												<?php
											}
											?>
										</li>
										<?php
									}
								}
								?>
							</ul>
						</div>
					</div>
					<?php

					if ( $is_available_enable ) {
						return false;
					}
				}
			}
		} else {
			?>
			<div id="wtp-tier-data" class="<?php echo esc_attr( $hide_class ); ?>">
				<div id="wtp-discount-list-container" class="wtp-variable-product-list-container" >
					<h4><?php echo esc_html( get_option( 'wtp_table_title', 'Discount Price List' ) ); ?></h4>
					<?php
					if ( 'tooltip' === get_option( 'wtp_display_type', 'block' ) ) {
						$wtp_table = 'wtp-table';
					} else {
						$wtp_table = '';
					}
					?>
					<ul class="<?php echo esc_attr( $wtp_table ); ?>"></ul>
				</div>
			</div>
			<?php
		}
	}
}


// Show discount list for variation product on selection of each variation via ajax
add_action( 'wp_ajax_wtp_show_discount_list_product_variation', 'wtp_show_discount_list_product_variation', 10 );
add_action( 'wp_ajax_nopriv_wtp_show_discount_list_product_variation', 'wtp_show_discount_list_product_variation', 10 );
function wtp_show_discount_list_product_variation() {

	if ( isset( $_POST['nonce'] ) && ! wp_verify_nonce( sanitize_text_field( $_POST['nonce'] ), 'wtp_front' ) ) {
		exit( 'Not Authorized' );
	}

	if ( isset( $_POST['variation_id'] ) && ! empty( $_POST['variation_id'] ) ) {

		$variation_id = sanitize_text_field( $_POST['variation_id'] );
		$variation    = wc_get_product( $variation_id );
		$product_id   = $variation->get_parent_id();
		$categories   = get_the_terms( $product_id, 'product_cat' );
		$first_cat    = $categories[0]->slug;
		$user         = wp_get_current_user();
		if ( ! empty( $user ) && $user->ID > 0 ) {
			$current_user_id   = $user->ID;
			$current_user_role = implode( ',', $user->roles );
		} else {
			$current_user_id   = 0;
			$current_user_role = '';
		}

		$general_category_rules = get_option( 'general_category_rules', false );
		$general_user_rules     = get_option( 'general_user_rules', false );
		$general_role_rules     = get_option( 'general_role_rules', false );

		// $price = ( isset( $_POST['price'] ) && !empty( $_POST['price'] ) ) ? sanitize_text_field( $_POST['price'] ) : 0;
		$price                    = $variation->get_price();
		$wtp_product_tier_setting = get_post_meta( $variation_id, 'wtp_product_tier_setting_' . $variation_id, true );
		$html                     = '';

		if ( ! empty( $wtp_product_tier_setting ) && in_array( 'false', $wtp_product_tier_setting['disabled'] ) ) {

			if ( 'tooltip' === get_option( 'wtp_display_type', 'block' ) ) {
				$html .= '<li class="wtp-table-head">
					<span>' . esc_html( get_option( 'wtp_qty_col_text', 'Quantity' ) ) . '</span>
					<span>' . esc_html( get_option( 'wtp_price_col_text', 'Price' ) ) . '</span>';

				if ( 'enabled' === get_option( 'wtp_show_discount_col', 'disabled' ) ) {
					$html .= '<span>' . esc_html( get_option( 'wtp_discount_col_text', 'Discount' ) ) . '</span>';
				}

				$html .= '</li>';
			}

			$is_available_enable = false;
			foreach ( $wtp_product_tier_setting['min_qty'] as $key => $value ) {
				if ( isset( $wtp_product_tier_setting['disabled'][ $key ] ) && 'false' === $wtp_product_tier_setting['disabled'][ $key ] ) {
					$is_available_enable = true;
					$min_qty             = ( ! empty( $value ) && $value > 0 ) ? $value : 1;
					$max_qty             = ( ! empty( $wtp_product_tier_setting['max_qty'][ $key ] ) && $wtp_product_tier_setting['max_qty'][ $key ] >= $min_qty ) ? $wtp_product_tier_setting['max_qty'][ $key ] : '&#8734;';
					$data_max_qty        = ( ! empty( $wtp_product_tier_setting['max_qty'][ $key ] ) && $wtp_product_tier_setting['max_qty'][ $key ] >= $min_qty ) ? $wtp_product_tier_setting['max_qty'][ $key ] : -1;
					$discount_type       = ! empty( $wtp_product_tier_setting['discount_type'][ $key ] ) ? $wtp_product_tier_setting['discount_type'][ $key ] : 'percentage';
					$discount_value      = ! empty( $wtp_product_tier_setting['discount_value'][ $key ] ) ? $wtp_product_tier_setting['discount_value'][ $key ] : 0;

					if ( 'percentage' == $discount_type ) {
						$discount_value = ( $price - ( $discount_value / 100 ) * $price );
					} else { // fixed
						$discount_value = ( $price - $discount_value );
					}

					$discount_value = ( $discount_value >= 0 ) ? $discount_value : 0;
					if ( 'incl' == get_option( 'woocommerce_tax_display_shop' ) ) {
						$discount_value = wc_get_price_including_tax( $variation, array( 'price' => $discount_value ) );
					}
					$html .= '<li data-min="' . esc_attr( $min_qty ) . '" data-max="' . esc_attr( $data_max_qty ) . '" data-price="' . esc_attr( $discount_value ) . '">';
					$html .= '<span class="ma-quantity-range">' . esc_html__( apply_filters( 'wtp_change_tier_label', 'Pcs' ), 'wtp' ) . ' ' . esc_attr( $min_qty ) . ' - ' . esc_attr( $max_qty ) . '</span>';
					$html .= '<span class="pre-inquiry-price">' . wc_price( $discount_value ) . '</span>';

					if ( 'tooltip' === get_option( 'wtp_display_type', 'block' ) && 'enabled' === get_option( 'wtp_show_discount_col', 'disabled' ) ) {
						$show_discount_percentage = 100 - ( ( $discount_value / $price ) * 100 );
						$html                    .= '<span>' . sprintf( '%1$s%2$s', esc_attr( number_format( $show_discount_percentage, 2 ) ), esc_html__( '% OFF', 'wtp' ) ) . '</span>';
					}

					$html .= '</li>';
				}
			}

			if ( $is_available_enable ) {

				$status['html'] = $html;
				echo json_encode( $status );
				wp_die();
			}
		}

		if ( ! empty( $general_category_rules ) ) { // Category based tier pricing.

			$flag = false;
			foreach ( $general_category_rules as $key => $value ) {
				if ( $first_cat == $value['category']  && ! isset( $value['disabled'] ) ) {
					$flag = true;
					break;
				}
			}

			if ( $flag ) {

				if ( 'tooltip' === get_option( 'wtp_display_type', 'block' ) ) {
					$html .= '<li class="wtp-table-head">
						<span>' . esc_html( get_option( 'wtp_qty_col_text', 'Quantity' ) ) . '</span>
						<span>' . esc_html( get_option( 'wtp_price_col_text', 'Price' ) ) . '</span>';

					if ( 'enabled' === get_option( 'wtp_show_discount_col', 'disabled' ) ) {
						$html .= '<span>' . esc_html( get_option( 'wtp_discount_col_text', 'Discount' ) ) . '</span>';
					}

					$html .= '</li>';
				}

				$is_available_enable = false;
				foreach ( $general_category_rules as $key => $value ) {
					if ( ! isset( $value['disabled'] ) && $first_cat == $value['category'] ) {
						$is_available_enable = true;
						$min_qty             = ( ! empty( $value['min_qty'] ) && $value['min_qty'] > 0 ) ? $value['min_qty'] : 1;
						$max_qty             = ( ! empty( $value['max_qty'] ) && $value['max_qty'] >= $min_qty ) ? $value['max_qty'] : '&#8734;';
						$data_max_qty        = ( ! empty( $value['max_qty'] ) && $value['max_qty'] >= $min_qty ) ? $value['max_qty'] : -1;
						$discount_type       = ! empty( $value['discount_type'] ) ? $value['discount_type'] : 'percentage';
						$discount_value      = ! empty( $value['discount_value'] ) ? $value['discount_value'] : 0;

						if ( 'percentage' == $discount_type ) {
							$discount_value = ( $price - ( $discount_value / 100 ) * $price );
						} else { // fixed
							$discount_value = ( $price - $discount_value );
						}

						$discount_value = ( $discount_value >= 0 ) ? $discount_value : 0;
						if ( 'incl' == get_option( 'woocommerce_tax_display_shop' ) ) {
							$discount_value = wc_get_price_including_tax( $variation, array( 'price' => $discount_value ) );
						}
						$html .= '<li data-min="' . esc_attr( $min_qty ) . '" data-max="' . esc_attr( $data_max_qty ) . '" data-price="' . esc_attr( $discount_value ) . '" >';
						$html .= '<span class="ma-quantity-range">' . esc_html__( apply_filters( 'wtp_change_tier_label', 'Pcs' ), 'wtp' ) . ' ' . esc_attr( $min_qty ) . ' - ' . esc_attr( $max_qty ) . '</span>';
						$html .= '<span class="pre-inquiry-price">' . wc_price( $discount_value ) . '</span>';

						if ( 'tooltip' === get_option( 'wtp_display_type', 'block' ) && 'enabled' === get_option( 'wtp_show_discount_col', 'disabled' ) ) {
							$show_discount_percentage = 100 - ( ( $discount_value / $price ) * 100 );
							$html                    .= '<span>' . sprintf( '%1$s%2$s', esc_attr( number_format( $show_discount_percentage, 2 ) ), esc_html__( '% OFF', 'wtp' ) ) . '</span>';
						}

						$html .= '</li>';
					}
				}

				if ( $is_available_enable ) {
					$status['html'] = $html;
					echo json_encode( $status );
					wp_die();
				}
			}
		}

		if ( ! empty( $general_user_rules ) ) { // User based tier pricing.

			$flag = false;
			foreach ( $general_user_rules as $key => $value ) {
				if ( $current_user_id == $value['user']  && ! isset( $value['disabled'] ) ) {
					$flag = true;
					break;
				}
			}

			if ( $flag ) {

				if ( 'tooltip' === get_option( 'wtp_display_type', 'block' ) ) {
					$html .= '<li class="wtp-table-head">
						<span>' . esc_html( get_option( 'wtp_qty_col_text', 'Quantity' ) ) . '</span>
						<span>' . esc_html( get_option( 'wtp_price_col_text', 'Price' ) ) . '</span>';

					if ( 'enabled' === get_option( 'wtp_show_discount_col', 'disabled' ) ) {
						$html .= '<span>' . esc_html( get_option( 'wtp_discount_col_text', 'Discount' ) ) . '</span>';
					}

					$html .= '</li>';
				}

				$is_available_enable = false;
				foreach ( $general_user_rules as $key => $value ) {
					if ( ! isset( $value['disabled'] ) && $current_user_id == $value['user'] ) {
						$is_available_enable = true;
						$min_qty             = ( ! empty( $value['min_qty'] ) && $value['min_qty'] > 0 ) ? $value['min_qty'] : 1;
						$max_qty             = ( ! empty( $value['max_qty'] ) && $value['max_qty'] >= $min_qty ) ? $value['max_qty'] : '&#8734;';
						$data_max_qty        = ( ! empty( $value['max_qty'] ) && $value['max_qty'] >= $min_qty ) ? $value['max_qty'] : -1;
						$discount_type       = ! empty( $value['discount_type'] ) ? $value['discount_type'] : 'percentage';
						$discount_value      = ! empty( $value['discount_value'] ) ? $value['discount_value'] : 0;

						if ( 'percentage' == $discount_type ) {
							$discount_value = ( $price - ( $discount_value / 100 ) * $price );
						} else { // fixed
							$discount_value = ( $price - $discount_value );
						}

						$discount_value = ( $discount_value >= 0 ) ? $discount_value : 0;
						if ( 'incl' == get_option( 'woocommerce_tax_display_shop' ) ) {
							$discount_value = wc_get_price_including_tax( $variation, array( 'price' => $discount_value ) );
						}
						$html .= '<li data-min="' . esc_attr( $min_qty ) . '" data-max="' . esc_attr( $data_max_qty ) . '" data-price="' . esc_attr( $discount_value ) . '">';
						$html .= '<span class="ma-quantity-range">' . esc_html__( apply_filters( 'wtp_change_tier_label', 'Pcs' ), 'wtp' ) . ' ' . esc_attr( $min_qty ) . ' - ' . esc_attr( $max_qty ) . '</span>';
						$html .= '<span class="pre-inquiry-price">' . wc_price( $discount_value ) . '</span>';

						if ( 'tooltip' === get_option( 'wtp_display_type', 'block' ) && 'enabled' === get_option( 'wtp_show_discount_col', 'disabled' ) ) {
							$show_discount_percentage = 100 - ( ( $discount_value / $price ) * 100 );
							$html                    .= '<span>' . sprintf( '%1$s%2$s', esc_attr( number_format( $show_discount_percentage, 2 ) ), esc_html__( '% OFF', 'wtp' ) ) . '</span>';
						}

						$html .= '</li>';
					}
				}

				if ( $is_available_enable ) {
					$status['html'] = $html;
					echo json_encode( $status );
					wp_die();
				}
			}
		}

		if ( ! empty( $general_role_rules ) ) {
			$flag = false;
			foreach ( $general_role_rules as $key => $value ) {
				if ( $current_user_role == $value['role']  && ! isset( $value['disabled'] ) ) {
					$flag = true;
					break;
				}
			}

			if ( $flag ) {

				if ( 'tooltip' === get_option( 'wtp_display_type', 'block' ) ) {
					$html .= '<li class="wtp-table-head">
						<span>' . esc_html( get_option( 'wtp_qty_col_text', 'Quantity' ) ) . '</span>
						<span>' . esc_html( get_option( 'wtp_price_col_text', 'Price' ) ) . '</span>';

					if ( 'enabled' === get_option( 'wtp_show_discount_col', 'disabled' ) ) {
						$html .= '<span>' . esc_html( get_option( 'wtp_discount_col_text', 'Discount' ) ) . '</span>';
					}

					$html .= '</li>';
				}

				$is_available_enable = false;
				foreach ( $general_role_rules as $key => $value ) {
					$is_available_enable = true;
					if ( ! isset( $value['disabled'] ) && $current_user_role == $value['role'] ) {
						$min_qty        = ( ! empty( $value['min_qty'] ) && $value['min_qty'] > 0 ) ? $value['min_qty'] : 1;
						$max_qty        = ( ! empty( $value['max_qty'] ) && $value['max_qty'] >= $min_qty ) ? $value['max_qty'] : '&#8734;';
						$data_max_qty   = ( ! empty( $value['max_qty'] ) && $value['max_qty'] >= $min_qty ) ? $value['max_qty'] : -1;
						$discount_type  = ! empty( $value['discount_type'] ) ? $value['discount_type'] : 'percentage';
						$discount_value = ! empty( $value['discount_value'] ) ? $value['discount_value'] : 0;

						if ( 'percentage' == $discount_type ) {
							$discount_value = ( $price - ( $discount_value / 100 ) * $price );
						} else { // fixed
							$discount_value = ( $price - $discount_value );
						}

						$discount_value = ( $discount_value >= 0 ) ? $discount_value : 0;
						if ( 'incl' == get_option( 'woocommerce_tax_display_shop' ) ) {
							$discount_value = wc_get_price_including_tax( $variation, array( 'price' => $discount_value ) );
						}
						$html .= '<li data-min="' . esc_attr( $min_qty ) . '" data-max="' . esc_attr( $data_max_qty ) . '" data-price="' . esc_attr( $discount_value ) . '">';
						$html .= '<span class="ma-quantity-range">' . esc_html__( apply_filters( 'wtp_change_tier_label', 'Pcs' ), 'wtp' ) . ' ' . esc_attr( $min_qty ) . ' - ' . esc_attr( $max_qty ) . '</span>';
						$html .= '<span class="pre-inquiry-price">' . wc_price( $discount_value ) . '</span>';

						if ( 'tooltip' === get_option( 'wtp_display_type', 'block' ) && 'enabled' === get_option( 'wtp_show_discount_col', 'disabled' ) ) {
							$show_discount_percentage = 100 - ( ( $discount_value / $price ) * 100 );
							$html                    .= '<span>' . sprintf( '%1$s%2$s', esc_attr( number_format( $show_discount_percentage, 2 ) ), esc_html__( '% OFF', 'wtp' ) ) . '</span>';
						}

						$html .= '</li>';
					}
				}

				if ( $is_available_enable ) {
					$status['html'] = $html;
					echo json_encode( $status );
					wp_die();
				}
			}
		}
	}
}


// Calculate price during adding to cart.
add_action( 'woocommerce_before_calculate_totals', 'wtp_product_alter_price_cart', 9999 );
function wtp_product_alter_price_cart( $cart ) {

	if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
		return;
	}

	if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 ) {
		return;
	}

	$parentProductQuantities = getParentProductQuantities($cart->get_cart_item_quantities());

	// LOOP THROUGH CART ITEMS & APPLY DISCOUNT.
	foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
		$product      = $cart_item['data'];
		$product_id   = $cart_item['product_id'];
		$variation_id = $cart_item['variation_id'];
		$price        = $product->get_price();
		$type         = $product->get_type();
		//$quantity     = $cart_item['quantity'];

		// Apply total variation product quantity 
		$quantity     = $parentProductQuantities[$product_id];

		$new_price    = (string) wtp_calculator_product( $product_id, $price, $quantity, $type, $variation_id );

		//$new_price = 5;

		if ( '' != $new_price ) {
			$cart_item['data']->set_price( $new_price );
			$cart_item['data']->set_sale_price( $new_price );
			$cart_item['data']->set_regular_price( $price );
		} else {
			$cart_item['data']->set_price( $price );
		}
	}



}

function getParentProductQuantities($cartItemQuantities) {
	$productQuantities = [];

	foreach($cartItemQuantities as $product_id => $quantity) {
		$product  = wc_get_product( $product_id );
		
		// Calculate parent quantity product
		if ( $product && 'variation' === $product->get_type() ) {
			$productQuantities[$product->get_parent_id()] = $productQuantities[$product->get_parent_id()] + $quantity;
		} else {
			$productQuantities[$product_id] = $quantity;
		}

	}
	return $productQuantities;
}

// totalQty(WC()->cart->get_cart_item_quantities());



// Show discount price on cart line product price
add_filter( 'woocommerce_cart_item_price', 'wtp_change_cart_table_price_display', 999, 3 );
function wtp_change_cart_table_price_display( $price, $cart_item, $cart_item_key ) {
	$slashed_price = $cart_item['data']->get_regular_price();
	$is_on_sale    = $cart_item['data']->is_on_sale();
	if ( $is_on_sale ) {

		if ( 'incl' == get_option( 'woocommerce_tax_display_cart' ) ) {
			$slashed_price = wc_get_price_including_tax( $cart_item['data'], array( 'price' => $slashed_price ) );
		} else {
			$slashed_price = $slashed_price;
		}

		$price = sprintf( '<del>%s</del> <ins>%s</ins>', wc_price( $slashed_price ), $price );
	}

	return $price;
}

// Show discount price on cart line subtotal
add_filter( 'woocommerce_cart_item_subtotal', 'wtp_change_cart_table_line_subtotal_price_display', 999, 3 );
function wtp_change_cart_table_line_subtotal_price_display( $subtotal, $cart_item, $cart_item_key ) {

	$is_on_sale = $cart_item['data']->is_on_sale();
	if ( $is_on_sale ) {

		if ( 'incl' == get_option( 'woocommerce_tax_display_cart' ) ) {
			$slashed_price = wc_get_price_including_tax( $cart_item['data'], array( 'price' => $cart_item['data']->get_regular_price() * $cart_item['quantity'] ) );
		} else {
			$slashed_price = $cart_item['data']->get_regular_price() * $cart_item['quantity'];
		}

		$subtotal = sprintf( '<del>%s</del> <ins>%s</ins>', wc_price( $slashed_price ), $subtotal );
	}
	return $subtotal;
}


// Repeatable function.
function wtp_calculator_product( $product_id, $price, $quantity, $type, $variation_id ) {

	$categories = get_the_terms( $product_id, 'product_cat' );
	$first_cat  = $categories[0]->slug;
	$user       = wp_get_current_user();
	if ( ! empty( $user ) && $user->ID > 0 ) {
		$current_user_id   = $user->ID;
		$current_user_role = implode( ',', $user->roles );
	} else {
		$current_user_id   = 0;
		$current_user_role = '';
	}

	$general_category_rules = get_option( 'general_category_rules', false );
	$general_user_rules     = get_option( 'general_user_rules', false );
	$general_role_rules     = get_option( 'general_role_rules', false );

	if ( ! ( 'variable' == $type ) && ! ( 'variation' == $type ) && ! ( 'variable-subscription' == $type ) ) {
		$wtp_product_tier_setting = get_post_meta( $product_id, 'wtp_product_tier_setting', true );
	} else {
		$wtp_product_tier_setting = get_post_meta( $variation_id, 'wtp_product_tier_setting_' . $variation_id, true );
	}

	if ( ! empty( $wtp_product_tier_setting ) ) {
		$is_available_enable = false;
		foreach ( $wtp_product_tier_setting['min_qty'] as $key => $value ) {
			if ( isset( $wtp_product_tier_setting['disabled'][ $key ] ) && 'false' === $wtp_product_tier_setting['disabled'][ $key ] ) {
				$is_available_enable = true;
				$min_qty             = ( ! empty( $value ) && $value > 0 ) ? $value : 1;
				$max_qty             = ( ! empty( $wtp_product_tier_setting['max_qty'][ $key ] ) && $wtp_product_tier_setting['max_qty'][ $key ] >= $min_qty ) ? $wtp_product_tier_setting['max_qty'][ $key ] : $min_qty;
				$check_max_qty       = ( ! empty( $wtp_product_tier_setting['max_qty'][ $key ] ) && $wtp_product_tier_setting['max_qty'][ $key ] >= $min_qty ) ? $wtp_product_tier_setting['max_qty'][ $key ] : $min_qty;
				$data_max_qty        = ( ! empty( $value['max_qty'] ) && $value['max_qty'] >= $min_qty ) ? $value['max_qty'] : -1;
				$discount_type       = ! empty( $wtp_product_tier_setting['discount_type'][ $key ] ) ? $wtp_product_tier_setting['discount_type'][ $key ] : 'percentage';
				$discount_value      = ! empty( $wtp_product_tier_setting['discount_value'][ $key ] ) ? $wtp_product_tier_setting['discount_value'][ $key ] : 0;

				if ( 'percentage' == $discount_type ) {
					$discount_value = ( $price - ( $discount_value / 100 ) * $price );
				} else { // fixed
					$discount_value = ( $price - $discount_value );
				}

				$discount_value = ( $discount_value >= 0 ) ? $discount_value : 0;
				// echo 'min_qty ' . $min_qty . '<br>';
				// echo 'check_max_qty ' . $check_max_qty . '<br>';
				// echo 'quantity ' . $quantity . '<br>';

				if ( $check_max_qty == $min_qty ) {
					if ( $quantity >= $min_qty ) {
						return $discount_value;
					}
				} else {
					if ( $quantity >= $min_qty && $quantity <= $check_max_qty ) {
						return $discount_value;
					}
				}
			}
		}

		if ( $is_available_enable ) {
			return $price;
		}
	}

	if ( ! empty( $general_category_rules ) ) {

		$flag = false;
		foreach ( $general_category_rules as $key => $value ) {
			if ( $first_cat == $value['category'] ) {
				$flag = true;
				break;
			}
		}

		if ( $flag ) {
			// echo 'I am inside';
			$is_available_enable = false;
			foreach ( $general_category_rules as $key => $value ) {
				if ( ! isset( $value['disabled'] ) && $first_cat == $value['category'] ) {
					$is_available_enable = true;
					// echo 'I am inside too';
					$min_qty        = ( ! empty( $value['min_qty'] ) && $value['min_qty'] > 0 ) ? $value['min_qty'] : 1;
					$max_qty        = ( ! empty( $value['max_qty'] ) && $value['max_qty'] >= $min_qty ) ? $value['max_qty'] : '&#8734;';
					$check_max_qty  = ( ! empty( $value['max_qty'] ) && $value['max_qty'] >= $min_qty ) ? $value['max_qty'] : $min_qty;
					$data_max_qty   = ( ! empty( $value['max_qty'] ) && $value['max_qty'] >= $min_qty ) ? $value['max_qty'] : -1;
					$discount_type  = ! empty( $value['discount_type'] ) ? $value['discount_type'] : 'percentage';
					$discount_value = ! empty( $value['discount_value'] ) ? $value['discount_value'] : 0;

					// echo 'min_qty is ' . $min_qty . '<br>';
					// echo 'max_qty is ' . $max_qty . '<br>';
					// echo 'data_max_qty is ' . $data_max_qty . '<br>';
					// echo 'discount_type is ' . $discount_type . '<br>';
					// echo 'discount_value is ' . $discount_value . '<br>';

					if ( 'percentage' == $discount_type ) {
						$discount_value = ( $price - ( $discount_value / 100 ) * $price );
					} else { // fixed
						$discount_value = ( $price - $discount_value );
					}

					$discount_value = ( $discount_value >= 0 ) ? $discount_value : 0;

					if ( $check_max_qty == $min_qty ) {
						if ( $quantity >= $min_qty ) {
							return $discount_value;
						}
					} else {
						if ( $quantity >= $min_qty && $quantity <= $check_max_qty ) {
							return $discount_value;
						}
					}
				}
			}

			if ( $is_available_enable ) {
				return $price;
			}
		}
	}

	if ( ! empty( $general_user_rules ) ) {

		$flag = false;
		foreach ( $general_user_rules as $key => $value ) {
			if ( $current_user_id == $value['user'] ) {
				$flag = true;
				break;
			}
		}

		if ( $flag ) {
			$is_available_enable = false;
			foreach ( $general_user_rules as $key => $value ) {
				if ( ! isset( $value['disabled'] ) && $current_user_id == $value['user'] ) {
					$is_available_enable = true;
					$min_qty             = ( ! empty( $value['min_qty'] ) && $value['min_qty'] > 0 ) ? $value['min_qty'] : 1;
					$max_qty             = ( ! empty( $value['max_qty'] ) && $value['max_qty'] >= $min_qty ) ? $value['max_qty'] : '&#8734;';
					$check_max_qty       = ( ! empty( $value['max_qty'] ) && $value['max_qty'] >= $min_qty ) ? $value['max_qty'] : $min_qty;
					$data_max_qty        = ( ! empty( $value['max_qty'] ) && $value['max_qty'] >= $min_qty ) ? $value['max_qty'] : -1;
					$discount_type       = ! empty( $value['discount_type'] ) ? $value['discount_type'] : 'percentage';
					$discount_value      = ! empty( $value['discount_value'] ) ? $value['discount_value'] : 0;

					if ( 'percentage' == $discount_type ) {
						$discount_value = ( $price - ( $discount_value / 100 ) * $price );
					} else { // fixed
						$discount_value = ( $price - $discount_value );
					}

					$discount_value = ( $discount_value >= 0 ) ? $discount_value : 0;

					if ( $check_max_qty == $min_qty ) {
						if ( $quantity >= $min_qty ) {
							return $discount_value;
						}
					} else {
						if ( $quantity >= $min_qty && $quantity <= $check_max_qty ) {
							return $discount_value;
						}
					}
				}
			}

			if ( $is_available_enable ) {
				return $price;
			}
		}
	}

	if ( ! empty( $general_role_rules ) ) {

		$flag = false;
		foreach ( $general_role_rules as $key => $value ) {
			if ( $current_user_role == $value['role'] ) {
				$flag = true;
				break;
			}
		}

		if ( $flag ) {
			$is_available_enable = false;
			foreach ( $general_role_rules as $key => $value ) {
				if ( ! isset( $value['disabled'] ) && $current_user_role == $value['role'] ) {
					$is_available_enable = true;
					$min_qty             = ( ! empty( $value['min_qty'] ) && $value['min_qty'] > 0 ) ? $value['min_qty'] : 1;
					$max_qty             = ( ! empty( $value['max_qty'] ) && $value['max_qty'] >= $min_qty ) ? $value['max_qty'] : '&#8734;';
					$check_max_qty       = ( ! empty( $value['max_qty'] ) && $value['max_qty'] >= $min_qty ) ? $value['max_qty'] : $min_qty;
					$data_max_qty        = ( ! empty( $value['max_qty'] ) && $value['max_qty'] >= $min_qty ) ? $value['max_qty'] : -1;
					$discount_type       = ! empty( $value['discount_type'] ) ? $value['discount_type'] : 'percentage';
					$discount_value      = ! empty( $value['discount_value'] ) ? $value['discount_value'] : 0;

					if ( 'percentage' == $discount_type ) {
						$discount_value = ( $price - ( $discount_value / 100 ) * $price );
					} else { // fixed
						$discount_value = ( $price - $discount_value );
					}

					$discount_value = ( $discount_value >= 0 ) ? $discount_value : 0;

					if ( $check_max_qty == $min_qty ) {
						if ( $quantity >= $min_qty ) {
							return $discount_value;
						}
					} else {
						if ( $quantity >= $min_qty && $quantity <= $check_max_qty ) {
							return $discount_value;
						}
					}
				}
			}

			if ( $is_available_enable ) {
				return $price;
			}
		}
	}
}
// Add discount the price
add_action('woocommerce_single_product_summary', 'wtp_product_discount_list_preview', 12);

// Edit your color label
add_action('woocommerce_attribute_label', function($label, $name, $product){
	if( $label !== 'Colour' ) return $label;
	return 'Choose Colour';
}, 100, 3);


remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20);

add_action('woocommerce_before_single_product_summary', 'woocommerce_template_single_excerpt', 30);

// Prevent multiple upload
add_filter('thwepo_file_upload_logo_value', function($field, $extra_options){
	global $thwepo_files;
	
	list('upload_logo' => $upload_logo) = $thwepo_files;

	if($upload_logo) {
		return $upload_logo;
	}

	return '';
}, 100, 2);

add_action('woocommerce_after_calculate_totals', function($cart) {
	if($cart->get_subtotal() > 200) {
		$cart->fees_api()->remove_all_fees();
	}
});
