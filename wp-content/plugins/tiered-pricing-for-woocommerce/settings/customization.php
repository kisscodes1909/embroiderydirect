<?php
/*
* Customization Setting Page.
*/

if ( isset( $_GET['sub_section'] ) && 'wtp_customization_summary_block' === sanitize_text_field( $_GET['sub_section'] ) ) {
	$sub_section = 'two';
} else {
	$sub_section = 'one';
}
?>
<ul class="wtp-inside-subtabs subsubsub unpreventSubmitBtn" style="display:none">
	<li><a href="<?php echo esc_url( admin_url('admin.php?page=wc-settings&tab=tier_pricing&section=wtp_customization&sub_section=wtp_customization_display_type') ); ?>" <?php echo ( 'one' === $sub_section ) ? 'class="current"' : ''; ?> ><?php echo esc_html__( 'Display Type Settings', 'wtp' ); ?></a> | </li>
	<li><a href="<?php echo esc_url( admin_url('admin.php?page=wc-settings&tab=tier_pricing&section=wtp_customization&sub_section=wtp_customization_summary_block') ); ?>" <?php echo ( 'two' === $sub_section ) ? 'class="current"' : ''; ?> ><?php echo esc_html__( 'Summary Block', 'wtp' ); ?></a></li>
</ul>
<br class="clear">
<?php

if ( 'one' === $sub_section ) { // This will show Display Type Setting
	
	$settings = array(

		array(
			'name' => __( 'Display Type Settings', 'wtp' ),
			'type' => 'title'
		),
		array(
			'name'    => __( 'Display Type', 'wtp' ),
			'type'    => 'select',
			'id'      => 'wtp_display_type',
			'class'   => 'wtp-form-control-field',
			'desc'    => 'Change display type of tiered pricing',
			'desc_tip' => true,
			'options' => array(
				'block' => __( 'Block', 'wtp' ),
				'tooltip' => __( 'Tooltip', 'wtp' ),
			),
		),
		array(
			'type' => 'sectionend',			
		),
		array(
			'name' => '',
			'type' => 'title'
		),
		array(
			'name'    => __( 'Tooltip Icon Color', 'wtp' ),
			'type'    => 'color',
			'id'      => 'wtp_tooltip_icon_color',
			'class'   => 'wtp-form-control-field type-tooltip ',
			'desc'    => 'Change tootltip icon color',
			'desc_tip' => true,
		),
		array(
			'name'    => __( 'Tooltip Icon Size (px)', 'wtp' ),
			'type'    => 'number',
			'id'      => 'wtp_tooltip_icon_size',
			'class'   => 'wtp-form-control-field type-tooltip ',
			'default' => '20',
			'desc'    => 'Change tootltip icon size',
			'desc_tip' => true,
		),
		array(
			'name'    => __( 'Tooltip Border', 'wtp' ),
			'type'    => 'radio',
			'id'      => 'wtp_tooltip_border',
			'class'   => 'type-tooltip ',
			'options' => array(
				'enabled' => __( 'Enabled', 'wtp' ),
				'disabled' => __( 'Disabled', 'wtp' ),
			),
			'default' => 'disabled',
			'desc'    => 'Add borders on tooltip block',
			'desc_tip' => true,
		),
		array(
			'name'    => __( 'Active Price BG Color', 'wtp' ),
			'type'    => 'color',
			'id'      => 'wtp_active_price_bg_color',
			'class'   => 'wtp-form-control-field type-tooltip ',
			'default' => '#dddddd',
			'desc'    => 'Change background color for active tier range',
			'desc_tip' => true,
		),
		array(
			'name'    => __( 'Quantity Column Text', 'wtp' ),
			'type'    => 'text',
			'id'      => 'wtp_qty_col_text',
			'class'   => 'wtp-form-control-field type-tooltip ',
			'default' => __('Quantity', 'wtp'),
			'desc'    => 'Change tootltip icon color',
			'desc_tip' => true,
		),
		array(
			'name'    => __( 'Price Column Text', 'wtp' ),
			'type'    => 'text',
			'id'      => 'wtp_price_col_text',
			'class'   => 'wtp-form-control-field type-tooltip ',
			'default' => __('Price', 'wtp'),
		),
		array(
			'name'    => __( 'Show Discount Column', 'wtp' ),
			'type'    => 'radio',
			'id'      => 'wtp_show_discount_col',
			'class'   => 'type-tooltip ',
			'options' => array(
				'enabled' => __( 'Enabled', 'wtp' ),
				'disabled' => __( 'Disabled', 'wtp' ),
			),
			'default' => 'disabled'
		),
		array(
			'name'    => __( 'Discount Column Text', 'wtp' ),
			'type'    => 'text',
			'id'      => 'wtp_discount_col_text',
			'class'   => 'type-tooltip type-discount ',
			'default' => __('Discount', 'wtp'),
		),
		array(
			'type' => 'sectionend'		
		),
		array(
			'name' => '',
			'type' => 'title'
		),
		array(
			'name'    => __( 'Table Title', 'wtp' ),
			'type'    => 'text',
			'id'      => 'wtp_table_title',
			'class'   => 'wtp-form-control-field type-table ',
			'default' => __('Tiered Pricing', 'wtp'),
		),
		array(
			'name'    => __( 'Table Position', 'wtp' ),
			'type'    => 'select',
			'id'      => 'wtp_table_position',
			'class' => 'wtp-form-control-field type-table ',
			'options' => array(
				'woocommerce_before_add_to_cart_button' => __( 'Above Add to Cart button', 'wtp' ),
				'woocommerce_after_add_to_cart_button' => __( 'Below Add to Cart button', 'wtp' ),
				'woocommerce_before_single_product_summary' => __( 'Above Product Title', 'wtp' ),
				'woocommerce_before_single_product' => __( 'Before Product Summary', 'wtp' ),
				'woocommerce_after_single_product_summary' => __( 'After Product Summary', 'wtp' ),
			),
			'default' => 'woocommerce_before_add_to_cart_button'
		),
		array(
			'type' => 'sectionend'		
		),
	);
} else { // This will show Summary Block

	$settings = array(

		array(
			'name' => __( 'Summary Block', 'wtp' ),
			'type' => 'title'
		),
		array(
			'name'    => __( 'Display Type', 'wtp' ),
			'type'    => 'select',
			'id'      => 'wtp_summary_display_type',
			'class'   => 'wtp-form-control-field',
			'desc'    => 'Change display type of summary',
			'desc_tip' => true,
			'options' => array(
				'none' => __( 'None', 'wtp' ),
				'table' => __( 'Table', 'wtp' ),
				'inline' => __( 'Inline', 'wtp' ),
			)
		),
		array(
			'type' => 'sectionend'		
		),
		array(
			'name' => '',
			'type' => 'title'
		),
		array(
			'name'    => __( '"Total" Label', 'wtp' ),
			'type'    => 'text',
			'id'      => 'wtp_summary_inline_total_label',
			'class'   => 'wtp-form-control-field type-inline ',
			'default' => __('Subtotal:', 'wtp'),
		),
		array(
			'name'    => __( '"Each" Label', 'wtp' ),
			'type'    => 'text',
			'id'      => 'wtp_summary_inline_each_label',
			'class'   => 'wtp-form-control-field type-inline ',
			'default' => __('Unit Price:', 'wtp'),
		),
		array(
			'type' => 'sectionend'		
		),

	);
}
return $settings;
