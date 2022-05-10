<ul class="wtp-inside-subtabs subsubsub unpreventSubmitBtn"><!--This ul is neccessary for showing submit button, watch settings.js file line #3 --></ul>
<?php
$settings = array(

	array(
		'name' => __( 'Price Display Settings', 'wtp' ),
		'type' => 'title'
	),
	array(
		'name'    => __( 'Enable Hide Price & Add to Cart Button for Guest Users', 'wtp' ),
		'type'    => 'radio',
		'id'      => 'wtp_hide_price',		
		'options' => array(
			'enabled' => __( 'Enabled', 'wtp' ),
			'disabled' => __( 'Disabled', 'wtp' ),
		),
		'default' => 'disabled',
		'desc'    => 'Enable to hide price and Add to cart button for guest users.',
		'desc_tip' => true,
	),
	array(
		'name'    => __( 'Hide Price Text', 'wtp' ),
		'type'    => 'text',
		'id'      => 'wtp_hide_price_text',
		'class'   => 'wtp-form-control-field type-hide-price ',		
		'desc'    => 'This text will be shown in place in archive and product pages.',
		'desc_tip' => true,
	),
	array(
		'name'    => __( 'Enable Tier Price Range', 'wtp' ),
		'type'    => 'radio',
		'id'      => 'wtp_tier_range_price_show',		
		'options' => array(
			'enabled' => __( 'Enabled', 'wtp' ),
			'disabled' => __( 'Disabled', 'wtp' ),
		),
		'default' => 'disabled',
		'desc'    => 'Enable to show tier price range instead of simple price on archive page only.',
		'desc_tip' => true,
	),
	array(
		'name'    => __( 'Display Tier Price Range', 'wtp' ),
		'type'    => 'select',
		'id'      => 'wtp_display_tier_price_range',
		'class'   => 'wtp-form-control-field type-tier-price-range',		
		'options' => array(
			'low_high' => __(  'Low to High', 'wtp' ),
			'high_low' => __( 'High to Low', 'wtp' ),
		)
	),
	array(
		'type' => 'sectionend'		
	)
);

return $settings;
