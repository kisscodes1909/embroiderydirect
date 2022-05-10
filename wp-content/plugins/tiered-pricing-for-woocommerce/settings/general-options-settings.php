<?php

$all_roles = get_option( 'current_roles' );

foreach ( $all_roles as $key => $val ) {
	$data[ $val['role'] ] = $val['name'];
}

$settings = array(

	'first_section_title'       => array(
		'name' => '',
		'type' => 'title',
		'desc' => '',
		'id'   => 'wc_settings_tab_demo_section_title',
	),
	'enable_tier_pricing' => array(
		'name' => __( 'Enable Tier Pricing', 'wtp' ),
		'type' => 'checkbox',
		'desc' => 'Enable',
		'id'   => 'wc_settings_enable_tier_pricing',
	),
	'user_roles'          => array(
		'type' => 'user_roles_management',
		'id'   => 'wc_settings_table_general',
	),
	'section_end'         => array(
		'type' => 'sectionend',
		'id'   => 'wc_settings_section_end',
	),

);

return $settings;
