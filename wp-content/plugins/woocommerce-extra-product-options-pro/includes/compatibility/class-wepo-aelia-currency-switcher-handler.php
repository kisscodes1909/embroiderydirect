<?php
/**
 * Aelia Currency Switcher compatibility handler page.
 *
 * @link       https://themehigh.com
 * @since      3.1.7
 *
 * @package    woocommerce-extra-product-options-pro
 * @subpackage woocommerce-extra-product-options-pro/includes/compatibility
 * @author Themehigh
 */

if(!defined('ABSPATH')){ exit; }

if(!class_exists('WEPO_Aelia_Currency_Switcher_Handler')) {
	class WEPO_Aelia_Currency_Switcher_Handler {
		/**
		 * Stores the singleton instance of this class.
		 *
		 * @var WEPO_Aelia_Currency_Switcher_Handler
		 */
		protected static $_instance;

		/**
		 * Returns the singleton instance of this class.
		 *
		 * @return WEPO_Aelia_Currency_Switcher_Handler
		 */
		public static function instance(): WEPO_Aelia_Currency_Switcher_Handler {
			return static::$_instance ?? static::$_instance = new static();
		}

		/**
		 * Lists the option price types that require a currency conversion.
		 * Percentage prices don't have to be converted.
		 *
		 * @var array
		 */
		protected $option_price_types_to_convert = [
			'normal',
			'', // Radio options seem to have an empty value when the price is fixed
		];

		protected function should_convert_option_prices($option): bool {
			return is_array($option) && isset($option['price_type']) && in_array($option['price_type'], $this->option_price_types_to_convert);
		}

		public function __construct() {
			add_filter('woocommerce_get_cart_item_from_session', [$this, 'woocommerce_get_cart_item_from_session'], 1, 3);
			
			// Convert the field price during the calculation.
			add_filter('thwepo_product_field_extra_cost', [$this, 'thwepo_product_field_extra_cost'], 10, 4);

			// Convert the display price of each field
			add_filter('thwepo_extra_cost_unit_price', [$this, 'thwepo_extra_cost_unit_price'], 10, 4);
			add_filter('thwepo_extra_cost_option_price', [$this, 'thwepo_extra_cost_option_price'], 10, 4);

			// Convert the product price
			add_filter('thwepo_product_price', [$this, 'thwepo_product_price'], 10, 3);
		}

		/**
		 * Set returns the price of a product in the active currency.
		 *
		 * @param double $price
		 * @param WC_Product $product
		 * @param bool $is_default
		 * @return double
		 */
		public function thwepo_product_price($price, $product, $is_default) {
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

			// If the source currency is not specified, take the shop's base currency as a default
			if(empty($from_currency)) {
				$from_currency = get_option('woocommerce_currency');
			}

			// If the target currency is not specified, take the active currency as a default.
			// The Currency Switcher sets this currency automatically, based on the context. Other
			// plugins can also override it, based on their own custom criteria, by implementing
			// a filter for the "woocommerce_currency" hook.
			//
			// For example, a subscription plugin may decide that the active currency is the one
			// taken from a previous subscription, because it's processing a renewal, and such
			// renewal should keep the original prices, in the original currency.
			if(empty($to_currency)) {
				$to_currency = get_woocommerce_currency();
			}

			// Call the currency conversion filter. Using a filter allows for loose coupling. If the
			// Aelia Currency Switcher is not installed, the filter call will return the original
			// amount, without any conversion being performed. Your plugin won't even need to know if
			// the multi-currency plugin is installed or active
			return apply_filters('wc_aelia_cs_convert', $price, $from_currency, $to_currency);
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
		public function thwepo_product_field_extra_cost($price, $name, $price_info, $product_info){
			return $this->convert_amount($price);
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
			return $this->convert_amount($price);
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
			return $this->convert_amount($price);
		}
	}
}