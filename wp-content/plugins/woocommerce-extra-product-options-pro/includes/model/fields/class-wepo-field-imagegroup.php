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

if(!class_exists('WEPO_Product_Field_ImageGroup')):

class WEPO_Product_Field_ImageGroup extends WEPO_Product_Field{
	public $options = array();
	public $option_text_position = '';

	public $image_group_size = '';
	public $image_group_radius = '';
	
	public $multiselection = false;
	public $display_vertically = false;
	public $enable_full_image_view = false;
	
	public function __construct() {
		$this->type = 'imagegroup';
	}
}

endif;