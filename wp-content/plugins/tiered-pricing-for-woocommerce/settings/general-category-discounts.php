<?php

$args = array (
	'taxonomy'     => 'product_cat',
	'orderby'      => 'name',
	'show_count'   => 0,
	'pad_counts'   => 0,
	'hierarchical' => 1,
	'title_li'     => '',
	'hide_empty'   => 0
);

$settings = array();
$all_categories = get_categories( $args );
if ( !empty( $all_categories ) && count( $all_categories ) > 0 ) {
	foreach ( $all_categories as $key => $val ) {
		$categories[ $val->slug ] = $val->name . ' (' . $val->slug . ')';
	}

	$settings = array(
		'section_title'      => array(
			'name' => __( 'Category based Discounts', 'wtp' ),
			'type' => 'title',
			'desc' => '',
			'id'   => 'wc_settings_tab_demo_section_title',
		),
		'product_categories'     => array(
			'name'    => __( 'Product Categories*', 'wtp' ),
			'type'    => 'select',
			'id'      => 'wc_settings_category_user_category',
			'options' => $categories,
			'class' => 'wtp-form-control-field',
		),
		'start_qty'          => array(
			'name'    => __( 'Minimum qty*', 'wtp' ),
			'type'    => 'number',
			'id'      => 'wc_settings_category_min',
			'class' => 'wtp-form-control-field',
			'desc' => '<div class="wtp-field-response"></div>',
			'custom_attributes' => array(
				'min' => 1				
			),
		),
		'end_qty'            => array(
			'name'    => __( 'Maximum qty', 'wtp' ),
			'type'    => 'number',
			'id'      => 'wc_settings_category_max',
			'class' => 'wtp-form-control-field',
			'desc' => '<div class="wtp-field-response"></div>',
			'custom_attributes' => array(
				'min' => 1				
			),
		),
		'discount_type'   => array(
			'name' => __( 'Discount type*', 'wtp' ),
			'type' => 'select',
			'id'   => 'wc_settings_category_discount_type',
			'options' => array(
				'fixed'        => 'Fixed',
				'percentage' => 'Percentage',
			),
			'class' => 'wtp-form-control-field',
		),
		'discount_value'   => array(
			'name' => __( 'Discount value*', 'wtp' ),
			'type' => 'number',
			'id'   => 'wc_settings_category_discount_value',
			'class' => 'wtp-form-control-field',
			'desc' => '<div class="wtp-field-response"></div>',
			'custom_attributes' => array(
				'min' => 1				
			),
		),
		'button_add_general' => array(
			'name' => __( 'Description', 'wtp' ),
			'type' => 'add_category_map_button',
		),
		'table_category'     => array(
			'type' => 'table_category',
			'id'   => 'wc_settings_table_category',
		),
		'section_end'        => array(
			'type' => 'sectionend',
			'id'   => 'wc_settings_section_end',
		),

	);
}

return $settings;
