<?php
$settings = array();

$users = get_users();
if ( ! empty( $users ) && count( $users ) > 0 ) {
	foreach ( $users as $key => $val ) {
		if ( isset($val->data) ) {
			$data[ $val->data->ID ] = $val->data->user_nicename . ' (' . $val->data->ID . ')';
		}
	}

	$settings = array(

		'section_title'      => array(
			'name' => __( 'User Based Discounts', 'wtp' ),
			'type' => 'title',
			'desc' => '',
			'id'   => 'wc_settings_tab_demo_section_title',
		),
		'user_discount'     => array(
			'name'    => __( 'User*', 'wtp' ),
			'type'    => 'select',
			'id'      => 'wc_settings_user_discount',
			'options' => $data,
			'class' => 'wtp-form-control-field',
			'default' => '',
		),
		'start_qty'          => array(
			'name'    => __( 'Minimum qty*', 'wtp' ),
			'type'    => 'number',
			'id'      => 'wc_settings_user_start_qty',
			'class' => 'wtp-form-control-field',
			'desc' => '<div class="wtp-field-response"></div>',
			'custom_attributes' => array(
				'min' => 1				
			),
			'default' => '',
		),
		'end_qty'            => array(
			'name'    => __( 'Maximum qty', 'wtp' ),
			'type'    => 'number',
			'id'      => 'wc_settings_user_end_qty',
			'class' => 'wtp-form-control-field',
			'desc' => '<div class="wtp-field-response"></div>',
			'custom_attributes' => array(
				'min' => 1				
			),
			'default' => '',
		),
		'discount_type'   => array(
			'name' => __( 'Discount type*', 'wtp' ),
			'type' => 'select',
			'id'   => 'wc_settings_user_discount_type',
			'options' => array(
				'fixed'        => 'Fixed',
				'percentage' => 'Percentage',
			),
			'class' => 'wtp-form-control-field',
			'default' => '',
		),
		'discount_value'   => array(
			'name' => __( 'Discount value*', 'wtp' ),
			'type' => 'number',
			'id'   => 'wc_settings_user_discount_value',
			'class' => 'wtp-form-control-field',
			'desc' => '<div class="wtp-field-response"></div>',
			'custom_attributes' => array(
				'min' => 1				
			),
			'default' => '',
		),
		'button_add_general' => array(
			'name' => __( 'Description', 'wtp' ),
			'type' => 'add_user_map_button',
		),
		'table_user'     => array(
			'type' => 'table_user',
			'id'   => 'wc_settings_table_general_user',
		),
		'section_end'        => array(
			'type' => 'sectionend',
			'id'   => 'wc_settings_section_end',
		),
	);
}

return $settings;
