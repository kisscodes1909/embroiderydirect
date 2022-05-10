<?php

$all_roles = get_option( 'current_roles' );

foreach ( $all_roles as $key => $val ) {
	$data[ $val['role'] ] = $val['name'] . ' (' . $val['role'] . ')';
}

$settings = array(

	'section_title'      => array(
		'name' => __( 'Role Based Discounts', 'wtp' ),
		'type' => 'title',
		'desc' => '',
		'id'   => 'wc_settings_tab_demo_section_title',
	),
	'wholesale_role'     => array(
		'name'    => __( 'User role*', 'wtp' ),
		'type'    => 'select',
		'id'      => 'wc_settings_role_user_role',
		'class' => 'wtp-form-control-field',
		'options' => $data,		
	),

	'start_qty'          => array(
		'name'    => __( 'Minimum qty*', 'wtp' ),
		'type'    => 'number',
		'id'      => 'wc_settings_role_min',
		'class' => 'wtp-form-control-field',
		'desc' => '<div class="wtp-field-response"></div>',
		'desc'    => '<div class="wtp-field-response"></div>',
		'custom_attributes' => array(
			'min' => 1				
		),
	),

	'end_qty'            => array(
		'name'    => __( 'Maximum qty', 'wtp' ),
		'type'    => 'number',
		'id'      => 'wc_settings_role_max',
		'class' => 'wtp-form-control-field',
		'desc' => '<div class="wtp-field-response"></div>',
		'custom_attributes' => array(
			'min' => 1				
		),
	),
	'discount_type'   => array(
		'name' => __( 'Discount type*', 'wtp' ),
		'type' => 'select',
		'id'   => 'wc_settings_role_discount_type',
		'class' => 'wtp-form-control-field',
		'options' => array(
			'fixed'        => 'Fixed',
			'percentage' => 'Percentage',
		),
	),
	'discount_value'   => array(
		'name' => __( 'Discount value*', 'wtp' ),
		'type' => 'number',
		'id'   => 'wc_settings_role_discount_value',
		'class' => 'wtp-form-control-field',
		'desc' => '<div class="wtp-field-response"></div>',
		'custom_attributes' => array(
			'min' => 1				
		),
	),
	'button_add_general' => array(
		'name' => __( 'Description', 'wtp' ),
		'type' => 'add_role_map_button',
	),
	'table_quantity'     => array(
		'type' => 'table_role',
		'id'   => 'wc_settings_table_general',
	),
	'section_end'        => array(
		'type' => 'sectionend',
		'id'   => 'wc_settings_section_end',
	),

);

return $settings;
