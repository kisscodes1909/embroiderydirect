<?php
/**
 * Custom product field Input Text data object.
 *
 * @link       https://themehigh.com
 * @since      3.1.9
 *
 * @package    woocommerce-extra-product-options-pro
 * @subpackage woocommerce-extra-product-options-pro/includes/model/fields
 */
if(!defined('WPINC')){	die; }

if(!class_exists('WEPO_Product_Field_Email')):

class WEPO_Product_Field_Email extends WEPO_Product_Field{
	
	public function __construct() {
		$this->type = 'email';
	}
}

endif;