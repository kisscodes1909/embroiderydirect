<?php
/**
 * Custom product field Checkbox Group data object.
 *
 * @link       https://themehigh.com
 * @since      3.1.0
 *
 * @package    woocommerce-extra-product-options-pro
 * @subpackage woocommerce-extra-product-options-pro/includes/model/fields
 */
if(!defined('WPINC')){	die; }

if(!class_exists('WEPO_Product_Field_ColorPalette')):

class WEPO_Product_Field_ColorPalette extends WEPO_Product_Field{
	public $options = array();
	public $multiselection = false;
	public $display_vertically = false;
	
	public $option_size = '';
	public $colorpicker_radius = '';
	public $option_text_position = '';
	
	public function __construct() {
		$this->type = 'colorpalette';
	}
}

endif;