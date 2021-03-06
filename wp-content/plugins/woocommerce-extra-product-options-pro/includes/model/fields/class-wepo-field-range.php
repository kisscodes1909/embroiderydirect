<?php
/**
 * Custom product field Number data object.
 *
 * @link       https://themehigh.com
 * @since      2.3.0
 *
 * @package    woocommerce-extra-product-options-pro
 * @subpackage woocommerce-extra-product-options-pro/includes/model/fields
 */
if(!defined('WPINC')){	die; }

if(!class_exists('WEPO_Product_Field_Range')):

class WEPO_Product_Field_Range extends WEPO_Product_Field{
	public $step = '';

	public function __construct() {
		$this->type = 'range';
	}
}

endif;