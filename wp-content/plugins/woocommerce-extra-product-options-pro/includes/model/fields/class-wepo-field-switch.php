<?php
/**
 * Custom product field Checkbox data object.
 *
 * @link       https://themehigh.com
 * @since      3.2.0
 *
 * @package    woocommerce-extra-product-options-pro
 * @subpackage woocommerce-extra-product-options-pro/includes/model/fields
 */
if(!defined('WPINC')){	die; }

if(!class_exists('WEPO_Product_Field_Switch')):

class WEPO_Product_Field_Switch extends WEPO_Product_Field{
	public $checked = false;
	
	public function __construct() {
		$this->type = 'switch';
	}
}

endif;