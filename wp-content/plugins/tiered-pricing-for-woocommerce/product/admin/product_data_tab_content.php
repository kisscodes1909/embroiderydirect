<?php /* silence is golden */ ?>
<div id='wtp_tier_pricing' class='panel woocommerce_options_panel'>
	<div class='wtp_options_group'>
		<?php
		global $post;
		$postID                = $post->ID;
		$wtp_product_tier_setting = get_post_meta( $postID, 'wtp_product_tier_setting', true );

		// echo '<pre>';
		// print_r( $wtp_product_tier_setting );
		// echo '</pre>';

		if ( ! empty( $wtp_product_tier_setting ) ) {
			$i = 0;
			foreach ( $wtp_product_tier_setting['min_qty'] as $key => $value ) {
				$i++;
				$min_qty  = ( ! empty( $value ) && $value > 0 ) ? $value : 1;
				$max_qty    = ( ! empty( $wtp_product_tier_setting['max_qty'][ $key ] ) && $wtp_product_tier_setting['max_qty'][ $key ] >= $min_qty ) ? $wtp_product_tier_setting['max_qty'][ $key ] : '';
				$discount_type  = ! empty( $wtp_product_tier_setting['discount_type'][ $key ] ) ? $wtp_product_tier_setting['discount_type'][ $key ] : 'percentage';
				$discount_value = ! empty( $wtp_product_tier_setting['discount_value'][ $key ] ) ? $wtp_product_tier_setting['discount_value'][ $key ] : 0;
				$disabled  = ! empty( $wtp_product_tier_setting['disabled'][ $key ] ) ? $wtp_product_tier_setting['disabled'][ $key ] : 'yes';
				?>
				<div class="wtp-discount-group wtp-bulk-group bulk_range_group_<?php esc_attr_e( $i ); ?>" data-index="<?php esc_attr_e( $i ); ?>">
					<div class="range_setter_inner">
						<div class="bulk-row-main">
							<div class="bulk-row-start wtp-input-filed-hight bulk-row-inner">
								<div class="bulk-min">
									<input type="number" name="wtp_product_tier_setting[min_qty][]" class="bulk_discount_min wtp_value_selector wtp_next_value" placeholder="min" min="0" step="any" value="<?php esc_attr_e( $min_qty ); ?>"><br/>
									<span class="wdbq_desc_text"><?php echo esc_html__('Minimum Quantity', 'wtp'); ?></span>
								</div>
								<div class="bulk-max">
									<input type="number" name="wtp_product_tier_setting[max_qty][]" class="bulk_discount_max wtp_value_selector wtp_auto_add_value" placeholder="max" min="0" step="any" value="<?php esc_attr_e( $max_qty ); ?>"><br/>
									<span class="wdbq_desc_text"><?php echo esc_html__('Maximum Quantity', 'wtp'); ?></span>
								</div>
								<div class="bulk_gen_disc_type wtp-select-filed-hight">
									<select name="wtp_product_tier_setting[discount_type][]" class="bulk-discount-type bulk_discount_select">                                        
										<option value="percentage" <?php echo ( 'percentage' == $discount_type ) ? 'selected="selected"' : ''; ?> ><?php echo esc_html__('Percentage', 'wtp'); ?></option>
										<option value="fixed" <?php echo ( 'fixed' == $discount_type ) ? 'selected="selected"' : ''; ?> ><?php echo esc_html__('Fixed', 'wtp'); ?></option>                                                                        
									</select><br/>
									<span class="wdbq_desc_text"><?php echo esc_html__('Discount Type', 'wtp'); ?></span>
								</div>
								<div class="bulk_amount">
									<input type="number" name="wtp_product_tier_setting[discount_value][]" class="bulk_discount_value bulk_value_selector wtp_value_selector" placeholder="Discount" min="0" step="any" value="<?php esc_attr_e( $discount_value ); ?>"><br/>
									<span class="wdbq_desc_text"><?php echo esc_html__('Discount Value', 'wtp'); ?></span>
								</div>
								<div class="bulk_gen_disc_type wtp-select-filed-hight">
									<select name="wtp_product_tier_setting[disabled][]" class="bulk-discount-type bulk_discount_select">                                        
										<option value="false" <?php echo ( 'fasle' == $disabled ) ? 'selected="selected"' : ''; ?> ><?php echo esc_html__('Enable', 'wtp'); ?></option>
										<option value="true" <?php echo ( 'true' == $disabled ) ? 'selected="selected"' : ''; ?> ><?php echo esc_html__('Disable', 'wtp'); ?></option>                                                                        
									</select><br/>
									<span class="wdbq_desc_text"><?php echo esc_html__('Status', 'wtp'); ?></span>
								</div>
								<div class="wtp-btn-remove">
									<span class="dashicons dashicons-no-alt wtp_discount_remove" data-rmdiv="bulk_range_group_<?php esc_attr_e( $i ); ?>"></span>
								</div>
							</div>
						</div>
					</div>
				</div>
				<?php
			}
		} else {
			?>
			<p class="wtp-addtext"><?php echo esc_html__('Add price discount range here.', 'wtp'); ?></p>
			<?php
		}
		?>
	</div>
	<div class="add-condition-and-filters wtp-discount-add-row">
		<button type="button" class="button add_discount_elements" data-current-index="0" ><?php echo esc_html__('Add Rule', 'wtp'); ?></button>
	</div>
</div>
