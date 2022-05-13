<?php
/**
 * WOOCS Currency Switcher compatibility handler page.
 *
 * @link       https://themehigh.com
 * @since      3.1.9
 *
 * @package    woocommerce-extra-product-options-pro
 * @subpackage woocommerce-extra-product-options-pro/includes/compatibility
 * @author Themehigh
 */

if(!defined('ABSPATH')){ exit; }

if(!class_exists('WEPO_Woocs_Currency_Switcher_Handler')) {
	class WEPO_Woocs_Currency_Switcher_Handler {
		/**
		 * Stores the singleton instance of this class.
		 *
		 * @var WEPO_Woocs_Currency_Switcher_Handler
		 */
		protected static $_instance;

		/**
		 * Returns the singleton instance of this class.
		 *
		 * @return WEPO_Woocs_Currency_Switcher_Handler
		 */
		public static function instance(): WEPO_Woocs_Currency_Switcher_Handler {
			return static::$_instance ?? static::$_instance = new static();
		}

		/**
		 * Lists the option price types that require a currency conversion.
		 * Percentage prices don't have to be converted.
		 *
		 * @var array
		 */
		protected function get_price_type_to_convert(){
			$price_types = array('normal', 'custom', 'dynamic', 'dynamic-excl-base-price', 'char-count', 'custom-formula');
			return apply_filters('thwepo_price_types_to_convert', $price_types, $this);
		}

		protected function should_convert_field_price($price_type){
			$covertable_price_type = $this->get_price_type_to_convert();
			return isset($price_type) && in_array($price_type, $covertable_price_type);
		}

		public function __construct() {

			add_action('woocommerce_before_add_to_cart_button', [$this, 'thwepo_render_woocs_multiplier']);
			
			// Convert the display price of each field
			add_filter('thwepo_extra_cost_unit_price', [$this, 'thwepo_extra_cost_unit_price'], 10, 4);
			add_filter('thwepo_extra_cost_option_price', [$this, 'thwepo_extra_cost_option_price'], 10, 4);
			
			// Check the ajax filter is triggering on Ajax.
			$action = isset($_POST['action']) && $_POST['action'] ? $_POST['action'] : '';
			if(wp_doing_ajax() && $action === 'thwepo_calculate_extra_cost'){

				// Convert the field price during the calculation.
				add_filter('thwepo_product_field_extra_cost', [$this, 'thwepo_convert_ajax_field_price'], 10, 5);

				// Convert the product price
				add_filter('thwepo_product_price', [$this, 'thwepo_convert_ajax_product_price'], 10, 3);
			}else{
				add_filter('thwepo_product_field_extra_cost', [$this, 'thwepo_convert_cart_field_price'], 10, 4);
			}	
			
			// Hooks for showing price in cart item price
			add_filter('thwepo_cart_page_item_price', array($this, 'thwepo_cart_page_item_price_display'), 10, 3);
		}

		public function thwepo_render_woocs_multiplier(){
			$multiplier = $this->convert_amount(1);
			echo '<input type="hidden" id="thwepo_woocs_multiplier" name="thwepo_woocs_multiplier" value="'.$multiplier.'"/>';
		}

		/**
		 * Set returns the price of a product in the active currency.
		 *
		 * @param double $price
		 * @param WC_Product $product
		 * @param bool $is_default
		 * @return double
		 */
		public function thwepo_convert_ajax_product_price($price, $product, $is_default) {
			return $product->get_price();
		}

		/**
		 * Set the thwepo-orginal_price to product price in the switched currency.
		 *
		 * @param array $cart_item
		 * @param array $values
		 * @param string $key
		 * @return array
		 */
		public function woocommerce_get_cart_item_from_session($cart_item, $values, $key) {

			// Replace the original product price with the one in the active currency
			if(isset($cart_item['thwepo-original_price'])) {
				$cart_item['thwepo-original_price'] = $cart_item['data']->get_price();
			}

			return $cart_item;
		}

		/**
		 * Converts an amount from one currency to another.
		 *
		 * @param float price The source price.
		 * @param string to_currency The target currency. If empty, the active currency will be taken.
		 * @param string from_currency The source currency. If empty, WooCommerce base currency will be taken.
		 * @return float The price converted from source to destination currency.
		 */
		protected function convert_amount($price, $to_currency = null, $from_currency = null) {
			// Skip the conversion of non-numeric prices, or empty (zero) prices
			if(!is_numeric($price) || empty($price)) {
				return $price;
			}

			global $WOOCS;
			$price = $WOOCS->raw_woocommerce_price($price);
			
			return $price;
		}

		/**
		 * Converts an option price from shop's base currency to the active one.
		 *
		 * @param double $price
		 * @param string $name
		 * @param array $price_info
		 * @param array $product_info
		 * @return double
		 */
		public function thwepo_convert_ajax_field_price($price, $name, $price_info, $product_info, $price_type){

			if($this->should_convert_field_price($price_type)) {
				$price = $this->convert_amount($price);
			}

			return $price;
		}

		/**
		 * Converts an option price from shop's base currency to the active one.
		 *
		 * @param double $price
		 * @param string $name
		 * @param array $price_info
		 * @param array $product_info
		 * @return double
		 */
		public function thwepo_convert_cart_field_price($price, $name, $price_info, $product_info){

			$is_flat_fee = isset($price_info['is_flat_fee']) ? $price_info['is_flat_fee'] : '';

			if($is_flat_fee){
				$price = $this->convert_amount($price);
			}

			return $price;
		}

		/**
		 * Converts an option price from shop's base currency to the active one.
		 *
		 * @param double $price
		 * @param string $name
		 * @param array $price_info
		 * @param array $product_info
		 * @return double
		 */
		public function thwepo_extra_cost_unit_price($price, $name, $product_price, $price_type){

			if($this->should_convert_field_price($price_type)) {
				$price = $this->convert_amount($price);
			}

			return $price;
		}

		/**
		 * Converts an option price from shop's base currency to the active one.
		 *
		 * @param double $price
		 * @param string $name
		 * @param array $price_info
		 * @param array $product_info
		 * @return double
		 */
		public function thwepo_extra_cost_option_price($price, $price_type, $option, $name){
			$price_type = isset($option['price_type']) && !empty($option['price_type']) ? $option['price_type'] : 'normal';
			if($price && $price_type === 'normal') {
				$price = $this->convert_amount($price);
			}

			return $price;
		}

		/**
		 * Function for convert cart page item price display
		 * 
		 * @param float $price item price
		 *
		 * @return float
		 */
		public function thwepo_cart_page_item_price_display($price, $args, $product_info){			
			$price_type = isset($args['price_type']) ? $args['price_type'] : '';
			$field_type = isset($args['field_type']) ? $args['field_type'] : 'text';
			if(!$price_type && THWEPO_Utils_Field::is_option_field($field_type)){
				$price_type = 'normal';
			}

			if($this->should_convert_field_price($price_type) || $price_type === 'percentage') {
				$price = $this->convert_amount($price);
			}

			return $price;
			
		}
	}
}