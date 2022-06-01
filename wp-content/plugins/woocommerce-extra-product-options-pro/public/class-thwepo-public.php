<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://themehigh.com
 * @since      2.3.0
 *
 * @package    woocommerce-extra-product-options-pro
 * @subpackage woocommerce-extra-product-options-pro/public
 */
if(!defined('WPINC')){	die; }

if(!class_exists('THWEPO_Public')):
 
class THWEPO_Public {
	private $plugin_name;
	private $version;
	private $price;
	private $file;
	private $sections_extra;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;

		$this->price = new THWEPO_Price(); //THWEPO_Price::instance();
		$this->file = new THWEPO_File(); //THWEPO_File::instance();
		
		add_action('after_setup_theme', array($this, 'define_public_hooks'));
	}

	public function define_public_hooks(){
		$this->hooks_override_add_to_cart_link();
		$this->hooks_render_product_fields();
		$this->hooks_process_product_fields();
		$this->hooks_display_item_meta();
		
		$this->price->define_hooks();
		$this->file->define_hooks(); 
	}

	public function enqueue_styles_and_scripts() {
		global $wp_scripts;
		$is_quick_view = THWEPO_Utils::is_quick_view_plugin_active();
		
		if(is_product() || ( $is_quick_view && (is_shop() || is_product_category()) ) || apply_filters('thwepo_enqueue_public_scripts', false)){
			$debug_mode = apply_filters('thwepo_debug_mode', false);
			$suffix = $debug_mode ? '' : '.min';
			$jquery_version = isset($wp_scripts->registered['jquery-ui-core']->ver) ? $wp_scripts->registered['jquery-ui-core']->ver : '1.9.2';
			
			$this->enqueue_styles($suffix, $jquery_version);
			$this->enqueue_scripts($suffix, $jquery_version, $is_quick_view);
		}
	}
	
	private function enqueue_styles($suffix, $jquery_version) {
		//wp_register_style('select2', THWEPO_WOO_ASSETS_URL.'/css/select2.css');
		
		wp_enqueue_style('select2');

		if(apply_filters('thwepo_display_password_view_option', true)){
			wp_enqueue_style( 'dashicons' );
		}

		wp_enqueue_style('jquery-ui-style', THWEPO_ASSETS_URL_PUBLIC.'css/jquery-ui/jquery-ui.css');
		wp_enqueue_style('thwepo-timepicker-style', THWEPO_ASSETS_URL_PUBLIC.'js/timepicker/jquery.timepicker.css');
		wp_enqueue_style('thwepo-daterange-style', THWEPO_ASSETS_URL_PUBLIC.'js/date-range-picker/daterangepicker.css');
		wp_enqueue_style('thwepo-public-style', THWEPO_ASSETS_URL_PUBLIC . 'css/thwepo-public'. $suffix .'.css', $this->version);
		wp_enqueue_style('wp-color-picker');

		$settings 		= THWEPO_Utils::get_advanced_settings();
		$display_styles = THWEPO_Utils::get_setting_value($settings, 'display_styles');
		$active_color 	= THWEPO_Utils::get_setting_value($settings, 'active_color');
		$active_color 	= $active_color ? $active_color : '#000';

		$plugin_style = '';
		if($display_styles && 'default_style' !== $display_styles){
			$plugin_style .= "
				.thwepo-section-title.active *{
					color: $active_color !important;
				}
			";
		}

		wp_add_inline_style( 'thwepo-public-style', $plugin_style );
	}

	private function enqueue_scripts($suffix, $jquery_version, $is_quick_view) {
		$in_footer = apply_filters( 'thwepo_enqueue_script_in_footer', true );
		$deps = array();
		
		wp_register_script('thwepo-timepicker-script', THWEPO_ASSETS_URL_PUBLIC.'js/timepicker/jquery.timepicker.min.js', array('jquery'), '1.0.1');

		wp_register_script('thwepo-input-mask', THWEPO_ASSETS_URL_PUBLIC.'js/inputmask-js/jquery.inputmask.min.js', array('jquery'), '5.0.6');

		wp_register_script('thwepo-moment', THWEPO_ASSETS_URL_PUBLIC.'js/date-range-picker/moment.min.js', array('jquery'), '2.29.1');
		wp_register_script('thwepo-daterange', THWEPO_ASSETS_URL_PUBLIC.'js/date-range-picker/daterangepicker.min.js', array('jquery', 'thwepo-moment'), '3.1.0');

		if(apply_filters('thwepo_include_jquery_ui_i18n', true)){
			//wp_register_script('jquery-ui-i18n', '//ajax.googleapis.com/ajax/libs/jqueryui/'.$jquery_version.'/i18n/jquery-ui-i18n.min.js', array('jquery','jquery-ui-datepicker'), $in_footer);
			wp_register_script('jquery-ui-i18n', THWEPO_ASSETS_URL_PUBLIC.'js/jquery-ui-i18n.min.js', array('jquery','jquery-ui-datepicker'), $in_footer);
			
			$deps[] = 'jquery-ui-i18n';
		}else{
			$deps[] = 'jquery';
			$deps[] = 'jquery-ui-datepicker';
		}
		
		if(THWEPO_Utils::get_settings('disable_select2_for_select_fields') != 'yes'){
			$deps[] = 'selectWoo';
			
			$select2_languages = apply_filters( 'thwepo_select2_i18n_languages', false);
			if(is_array($select2_languages)){
				foreach($select2_languages as $lang){
					$handle = 'select2_i18n_'.$lang;
					wp_register_script($handle, '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/i18n/'.$lang.'.js', array('jquery','selectWoo'));
					$deps[] = $handle;
				}
			}
		}

		if(THWEPO_Utils::check_specific_field_type_in_settings('daterangepicker') && apply_filters('thwepo_enqueue_date_range_js', true)){
			$current_language = THWEPO_Utils::get_locale_code();

			wp_enqueue_script('thwepo-moment');
			wp_enqueue_script('thwepo-daterange');

			$current_language = THWEPO_Utils::get_locale_code();
			if($current_language !== 'en'){
				$daterange_locale = 'thwepo_daterange_i18_'.$current_language;
				wp_register_script($daterange_locale, '//cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/locale/'.$current_language.'.js', array('jquery','thwepo-moment', 'thwepo-daterange'));
				wp_enqueue_script($daterange_locale);
			}
		}

		wp_enqueue_script('iris', admin_url( 'js/iris.min.js' ), array( 'jquery-ui-draggable', 'jquery-ui-slider', 'jquery-touch-punch' ), false, 1);

		wp_register_script('thwepo-public-script', THWEPO_ASSETS_URL_PUBLIC . 'js/thwepo-public'. $suffix .'.js', $deps, $this->version, true );
		
		wp_enqueue_script('thwepo-timepicker-script');

		if(apply_filters('thwepo_enable_input_mask_enqueue', true)){
			wp_enqueue_script('thwepo-input-mask');
		}

		wp_enqueue_script('thwepo-public-script');

		$display_style = THWEPO_Utils::get_settings('display_styles');
		
		$wepo_var = array(
			'lang' => array(
				'am' => __('am', 'woocommerce-extra-product-options-pro'), 
				'pm' => __('pm', 'woocommerce-extra-product-options-pro'),  
				'AM' => __('AM', 'woocommerce-extra-product-options-pro'), 
				'PM' => __('PM', 'woocommerce-extra-product-options-pro'),
				'decimal' => __('.', 'woocommerce-extra-product-options-pro'), 
				'mins' => __('mins', 'woocommerce-extra-product-options-pro'), 
				'hr'   => __('hr', 'woocommerce-extra-product-options-pro'), 
				'hrs'  => __('hrs', 'woocommerce-extra-product-options-pro'),
			),

			'language' 	  => THWEPO_Utils::get_locale_code(),
			'date_format' => THWEPO_Utils::get_jquery_date_format(wc_date_format()),
			'readonly_date_field' => apply_filters('thwepo_date_picker_field_readonly', true),
			'ajax_url'    => admin_url( 'admin-ajax.php' ),
			'price_ph_simple'	=> apply_filters('thwepo_product_price_placeholder', ''),
			'price_ph_variable'	=> apply_filters('thwepo_variable_product_price_placeholder', ''),
			'is_quick_view' => $is_quick_view,
			'change_event_disabled_fields' => apply_filters('thwepo_change_event_disabled_fields', ''),
			'thwepo_select2_auto_width' => apply_filters('thwepo_select2_auto_width', false),
			'price_symbol'	=> get_woocommerce_currency_symbol(),
			'wp_max_file_upload_size' => wp_max_upload_size(),
			'thwepo_extra_cost_nonce' => wp_create_nonce('thwepo-extra-cost'),
			'file_upload_error' => __('Maximum upload size exceeded.', 'woocommerce-extra-product-options-pro'),

			'range_picker_time_format' =>  apply_filters('thwepo_range_picker_time_format', true),
			'range_picker_time_increment' =>  apply_filters('thwepo_range_picker_time_increment', '1'),
			'show_dropdown_year' =>  apply_filters('thwepo_show_dropdown_year', true),
			'start_of_week' => get_option('start_of_week'),

			'price_data' => array(
				'currency'           => get_woocommerce_currency(),
				'currency_symbol'    => get_woocommerce_currency_symbol(),
				'decimal_separator'  => wc_get_price_decimal_separator(),
				'thousand_separator' => wc_get_price_thousand_separator(),
				'decimals'           => wc_get_price_decimals(),
				'price_format'       => get_woocommerce_price_format(),
			),

			'display_style' => $display_style,
			'disable_scrole_top_animation'  => apply_filters('thwepo_disable_scrole_top_animation', false),
			'scroll_top_offset'  => apply_filters('thwepo_scroll_top_offset', 90),
		);
		wp_localize_script('thwepo-public-script', 'thwepo_public_var', $wepo_var);
	}
	

	/**********************************************
	***** ADD TO CART LINK OVERRIDES - START ******
	***********************************************/
	private function hooks_override_add_to_cart_link(){
		$hp_atc_link = apply_filters('thwepo_loop_add_to_cart_link_hook_priority', 20);

		add_filter('woocommerce_loop_add_to_cart_args', array($this, 'woo_loop_add_to_cart_args'), $hp_atc_link, 2);
		add_filter('woocommerce_product_add_to_cart_url', array($this, 'woo_product_add_to_cart_url'), $hp_atc_link, 2);
		add_filter('woocommerce_product_add_to_cart_text', array($this, 'woo_product_add_to_cart_text'), $hp_atc_link, 2);

		if(THWEPO_Utils::woo_version_check('3.3')){
			add_filter('woocommerce_loop_add_to_cart_link', array($this, 'woo_loop_add_to_cart_link'), $hp_atc_link, 3);
		}else{
			add_filter('woocommerce_loop_add_to_cart_link', array($this, 'woo_loop_add_to_cart_link'), $hp_atc_link, 2);
		}
	}

	public function woo_loop_add_to_cart_args($args, $product){
		if($this->is_modify_product_add_to_cart_link($product)){
			if(THWEPO_Utils::woo_version_check('3.3')){
				if(isset($args['class'])){
					$args['class'] = str_replace("ajax_add_to_cart", "", $args['class']);
				}
			}
		}
		return $args;
	}

	public function woo_product_add_to_cart_url($url, $product){
		if($this->is_modify_product_add_to_cart_link($product)){
			$url = $product->get_permalink();
		}
		return $url;
	}

	public function woo_product_add_to_cart_text($text, $product){
		$modify = $this->is_modify_product_add_to_cart_text($product);
		$product_type = THWEPO_Utils::get_product_type($product);

		if($modify){
			if(THWEPO_Utils::has_extra_options($product)){
				$text = $this->add_to_cart_text_addon($text, $product, $product_type);
			}else{
				$text = $this->add_to_cart_text_default($text, $product, $product_type);
			}
		}

		$text = apply_filters('thwepo_loop_add_to_cart_text', $text);
		return $text;
	}

	public function woo_loop_add_to_cart_link($link, $product, $args=false){
		if($this->is_modify_product_add_to_cart_link($product)){
			$class = '';
			if($args && isset($args['class'])){
				$args['class'] = str_replace("ajax_add_to_cart", "", $args['class']);
				$class = $args['class'];
				$class = $class ? $class : 'button';
			}

			if(THWEPO_Utils::is_active_theme('flatsome')){
				$product_type = THWEPO_Utils::get_product_type($product);

				$flatsome_classes = array(
					'add_to_cart_button', 
					'product_type_'.$product_type, 
					'button',
					'primary',
					'mb-0',
					'is-'.get_theme_mod( 'add_to_cart_style', 'outline' ),
					'is-small'
				);

				$class  = str_replace($flatsome_classes, "", $class);
				$class .= ' '.implode(" ", $flatsome_classes);

				$args['class'] = $class;
			}

			if(THWEPO_Utils::woo_version_check('3.3')){
				$link = sprintf( '<a href="%s" data-quantity="%s" class="%s" %s>%s</a>',
					esc_url( $product->add_to_cart_url() ),
					esc_attr( isset( $args['quantity'] ) ? $args['quantity'] : 1 ),
					esc_attr( isset( $args['class'] ) ? $args['class'] : 'button' ),
					isset( $args['attributes'] ) ? wc_implode_html_attributes( $args['attributes'] ) : '',
					esc_html( $product->add_to_cart_text() )
				);
			}else{
				$product_id = false;
				$product_sku = false;
	    		if(THWEPO_Utils::woo_version_check()){
	    			$product_id = $product->get_id();
	    			$product_sku = $product->get_sku();
	    		}else{
	    			$product_id = $product->id;
	    			$product_sku = $product->sku;
	    		}

				$link = sprintf( '<a rel="nofollow" href="%s" data-quantity="%s" data-product_id="%s" data-product_sku="%s" class="%s">%s</a>',
					esc_url( $product->add_to_cart_url() ),
					esc_attr( isset( $quantity ) ? $quantity : 1 ),
					esc_attr( $product_id ),
					esc_attr( $product_sku ),
					esc_attr( isset( $class ) ? $class : 'button' ),
					esc_html( $product->add_to_cart_text() )
				);
			}
		}
		return $link;
	}

	private function add_to_cart_text_addon($text, $product, $product_type){
		$new_text = '';

		if($product_type === 'simple' || $product_type === 'bundle'){
			//if($product->is_in_stock()){
				$new_text = THWEPO_Utils::get_settings('add_to_cart_text_addon_simple');
			//}
		}else if($product_type === 'variable'){
			//if($product->is_purchasable()){
				$new_text = THWEPO_Utils::get_settings('add_to_cart_text_addon_variable');
			//}
		}

		return !empty($new_text) ? esc_html(THWEPO_i18n::__t($new_text)) : __( 'Select options', 'woocommerce' );
	}

	private function add_to_cart_text_default($text, $product, $product_type){
		$new_text = '';

		if($product_type === 'simple' || $product_type === 'bundle'){
			if($product->is_in_stock()){
				$new_text = THWEPO_Utils::get_settings('add_to_cart_text_simple');
			}
		}else if($product_type === 'variable'){
			if($product->is_purchasable()){
				$new_text = THWEPO_Utils::get_settings('add_to_cart_text_variable');
			}
		}

		return !empty($new_text) ? esc_html(THWEPO_i18n::__t($new_text)) : $text;
	}

	private function is_modify_product_add_to_cart_text($product){
		$disable_override = THWEPO_Utils::get_settings('disable_loop_add_to_cart_text_override');
		$modify = $disable_override === 'yes' ? false : true;
		
		return apply_filters('thwepo_modify_loop_add_to_cart_text', $modify);
	}

	private function is_modify_product_add_to_cart_link($product){
		$disable_override = THWEPO_Utils::get_settings('disable_loop_add_to_cart_link_override');
		$modify = $disable_override === 'yes' ? false : true;
		$modify = apply_filters('thwepo_modify_loop_add_to_cart_link', $modify);
		$product_type = THWEPO_Utils::get_product_type($product);

		if($modify && THWEPO_Utils::has_extra_options($product) && $product->is_in_stock() && ($product_type === 'simple' || $product_type === 'bundle')){
			return true;
		}
		return false;
	}
	/**********************************************
	***** ADD TO CART LINK OVERRIDES - END ********
	***********************************************/
	

	/**********************************************
	***** RENDER PRODUCT FIELDS - START ***********
	***********************************************/
	private function hooks_render_product_fields(){
		$hp_display = apply_filters('thwepo_display_hooks_priority', 10);

		$hn_before_single_product = apply_filters('hook_name_before_single_product', 'woocommerce_before_single_product');
		$hns_before_single_product = apply_filters('hook_names_before_single_product', array());

		$hn_before_atc_button = apply_filters('hook_name_before_add_to_cart_button', 'woocommerce_before_add_to_cart_button');
		$hn_after_atc_button = apply_filters('hook_name_after_add_to_cart_button', 'woocommerce_after_add_to_cart_button');

		add_action( $hn_before_single_product, array($this, 'prepare_section_hook_map') ); //Deprecated
		if(is_array($hns_before_single_product)){
			foreach($hns_before_single_product as $hook_name){
				add_action($hook_name, array($this, 'prepare_section_hook_map'));
			}
		}

		if(THWEPO_Utils::is_yith_quick_view_enabled()){
			add_action('yith_wcqv_product_summary', array($this, 'prepare_section_hook_map'), 1);
		}
		if(THWEPO_Utils::is_flatsome_quick_view_enabled()){
			add_action('woocommerce_single_product_lightbox_summary',array($this, 'prepare_section_hook_map'), 1);
		}
		if(THWEPO_Utils::is_astra_quick_view_enabled()){
			add_action('astra_woo_quick_view_product_summary',array($this, 'prepare_section_hook_map'), 1);
		}
		if(THWEPO_Utils::is_oceanwp_quickview_enabled()){
			$ocean_wp_hook = apply_filters('thwepo_ocean_wp_prepare_hook', 'ocean_before_single_product_title');
			add_action($ocean_wp_hook, array($this, 'prepare_section_hook_map'), 1);
		}
		
		add_action( $hn_before_atc_button, array($this, 'action_before_add_to_cart_button'), $hp_display);	
		add_action( $hn_after_atc_button, array($this, 'action_after_add_to_cart_button'), $hp_display);
		add_action( 'woocommerce_single_variation', array($this, 'action_before_variation_data'), 5);

		if(apply_filters('thwepo_enable_additional_positions', false)){
			add_action( 'woocommerce_before_variations_form', array($this, 'action_before_variations_form'), $hp_display);
		}
		
		//add_action( 'woocommerce_before_add_to_cart_quantity', array($this, 'action_before_add_to_cart_quantity'), $hp_display);
		//add_action( 'woocommerce_after_add_to_cart_quantity', array($this, 'action_after_add_to_cart_quantity'), $hp_display);
		//add_action( 'woocommerce_before_variations_form', array($this, 'action_before_variations_form'), $hp_display);
		//add_action( 'woocommerce_after_variations_form', array($this, 'action_after_variations_form'), $hp_display);
		//add_action( 'woocommerce_before_single_variation', array($this, 'action_before_single_variation'), $hp_display);
		//add_action( 'woocommerce_after_single_variation', array($this, 'action_after_single_variation'), $hp_display);
		//add_action( 'woocommerce_single_variation', array($this, 'action_single_variation_90'), 90);

		add_action( $hn_before_atc_button, array($this, 'render_price_table'), $hp_display+10);

		// add_action('woocommerce_grouped_product_list_after', array($this, 'render_grouped_product_ids'), 3, 10);

		// Avada builder compatibility
		if(THWEPO_Utils::get_current_theme() === 'Avada'){
			add_filter('fusion_woo_component_content', array($this, 'add_missing_hook_in_avada_builder') ,10, 2);
		}
	}

	/**
	 * Prepare section hook map to display section and fields in product, quickview pages.
	 */
	public function prepare_section_hook_map(){ 
		global $product;
		
		$product_id = THWEPO_Utils::get_product_id($product);
		$categories = THWEPO_Utils::get_product_categories($product_id);
		$tags 		= THWEPO_Utils::get_product_tags($product_id);
		
		$sections = THWEPO_Utils::get_custom_sections();
		$section_hook_map = array();
		
		if($sections && is_array($sections) && !empty($sections)){
			foreach($sections as $section_name => $section){
				$section = THWEPO_Utils_Section::prepare_section_and_fields($section, $product_id, $categories, $tags);
				
				if($section){
					$hook_name = $section->get_property('position');

					if(array_key_exists($hook_name, $section_hook_map) && is_array($section_hook_map[$hook_name])) {
						$section_hook_map[$hook_name][$section_name] = $section;
					}else{
						$section_hook_map[$hook_name] = array();
						$section_hook_map[$hook_name][$section_name] = $section;
					}
				}
			}
		}
		
		$this->sections_extra = $section_hook_map;
	}
	
	public function action_before_add_to_cart_button(){
		$this->render_disabled_field_names_hidden_field();
		$this->render_sections('woo_before_add_to_cart_button');
	}
	public function action_after_add_to_cart_button(){
		$this->render_sections('woo_after_add_to_cart_button');
	}
	public function action_before_variations_form(){
		$this->render_sections('woo_before_variations_form');
	}
	public function action_after_variations_form(){
		$this->render_sections('woo_after_variations_form');
	}
	public function action_before_add_to_cart_quantity(){
		$this->render_sections('woo_before_add_to_cart_quantity');
	}
	public function action_after_add_to_cart_quantity(){
		$this->render_sections('woo_after_add_to_cart_quantity');
	}
	public function action_before_single_variation(){
		$this->render_sections('woo_before_single_variation');
	}
	public function action_after_single_variation(){
		$this->render_sections('woo_after_single_variation');
	}
	public function action_before_variation_data(){
		$this->render_sections('woo_single_variation_5');
	}
	public function action_single_variation_90(){
		$this->render_sections('woo_single_variation_90');
	}

	public function render_disabled_field_names_hidden_field(){
		global $product;
		$prod_field_names = THWEPO_Utils_Section::get_product_fields($product, true);
		$prod_field_names = is_array($prod_field_names) ? implode(",", $prod_field_names) : '';
		$product_price = $product->get_price();
		
		$tax_calc = array(
			'qty'   => 1,
			'price' => 10,
		);

		$taxed_price = wc_get_price_to_display($product, $tax_calc);
		$tax_percentage = $taxed_price/10;
		
		echo '<input type="hidden" id="thwepo_product_fields" name="thwepo_product_fields" value="'.$prod_field_names.'"/>';
		echo '<input type="hidden" id="thwepo_disabled_fields" name="thwepo_disabled_fields" value=""/>';
		echo '<input type="hidden" id="thwepo_disabled_sections" name="thwepo_disabled_sections" value=""/>';
		echo '<input type="hidden" id="thwepo_unvalidated_fields" name="thwepo_unvalidated_fields" value=""/>';
		echo '<input type="hidden" id="thwepo_product_price" name="thwepo_product_price" data-taxmultiplier="'. $tax_percentage .'"  value="'.$product_price.'"/>';
	}
	
	private function render_sections($hook_name){
		if($this->sections_extra){
			global $product;
			$product_type = THWEPO_Utils::get_product_type($product);
			
			$sections = THWEPO_Utils::get_sections_by_hook($this->sections_extra, $hook_name);
			if($sections){

				$this->render_display_style_tabs($sections);

				foreach($sections as $section_name => $section){
					$section_html = THWEPO_Utils_Section::prepare_section_html($section, $product_type);
					echo $section_html;
				}
			}
		}
	}

	private function render_display_style_tabs($sections){
		$settings = THWEPO_Utils::get_advanced_settings();
		$display_styles = THWEPO_Utils::get_setting_value($settings, 'display_styles');
	
		if($display_styles && 'default_style' !== $display_styles){

			$tab_title_html = '';
			foreach($sections as $section_name => $section){
				$title_html 	 = '<span class="thwepo-section-name">';
				$title_html 	.= THWEPO_i18n::__t(wp_kses_post($section->get_property('title')));
				$title_html 	.= '</span>';
				$section_slug 	 = $section->get_property('name');

				$conditions_data = THWEPO_Utils_Section::get_ajax_conditions_data($section);
				$cssclass = $conditions_data ? 'thwepo-conditional-section' : '';
				$title_style = $section->get_property('title_color') ? 'style="color:'.$section->get_property('title_color').';"' : '';

				$has_non_hidden_fields = THWEPO_Utils_Section::has_non_hidden_fields($section);
				if($has_non_hidden_fields){
					$tab_title_html .= '<div class="thwepo-section-title section-title '. $cssclass .'" data-section="'. $section_slug .'"'. $title_style .'>'.$title_html.'</div>';
				}
			}

			if($tab_title_html){
				$tab_list_html 	= '<div class="thwepo-section-list thwepo_list_'. $display_styles .'">';
				$tab_list_html .= $tab_title_html;
				$tab_list_html .= '</div>';

				echo $tab_list_html;
			}
		}
	}

	public function render_price_table(){
		$this->price->render_price_table();
	}

	/**
	 * Render grouped product ids in the product page.
	 */
	public function render_grouped_product_ids($grouped_product_columns, $quantites_required, $product){
		$child_products = '';

		if($product){
			$child_products= $product->get_children();
			if($child_products && is_array($child_products)){
				$child_product_ids = implode(',', $child_products);
			}else{
				$child_product_ids = $child_products;
			}

			if($child_product_ids){
				echo '<tr><td style="width:0px;height:0px; padding:0px;" colspan="3"><input type="hidden" name="grouped_child_ids" value="'. $child_product_ids .'"></td><tr>';
			}
		}
	}

	public function add_missing_hook_in_avada_builder($content, $shortcode_handle){
	    if($content && 'fusion_tb_woo_cart' === $shortcode_handle && apply_filters('thwepo_add_wc_hook_in_avada_builder', true)){
	        $content = '';
	        ob_start();
	        do_action( 'woocommerce_before_single_product' );
	        woocommerce_template_single_add_to_cart();
	        $content .= ob_get_clean();
	    }

	    return $content;
	}

	/**********************************************
	***** RENDER PRODUCT FIELDS - START ***********
	***********************************************/


	/**********************************************
	***** PROCESS PRODUCT FIELDS - START **********
	***********************************************/
	private function hooks_process_product_fields(){
		$hp_validation = apply_filters('thwepo_add_to_cart_validation_hook_priority', 99);
		$hp_add_item_data = apply_filters('thwepo_add_cart_item_data_hook_priority', 10);
		$hp_new_order = apply_filters('thwepo_new_order_item_hook_priority', 10);

		add_filter('woocommerce_add_to_cart_validation', array($this, 'add_to_cart_validation'), $hp_validation, 6);
		add_filter('woocommerce_add_cart_item_data', array($this, 'add_cart_item_data'), $hp_add_item_data, 3);

		if(THWEPO_Utils::woo_version_check()){
			add_action( 'woocommerce_new_order_item', array($this, 'woo_new_order_item'), $hp_new_order, 3);
		}else{
			//Older version WooCommerce support
			add_action( 'woocommerce_add_order_item_meta', array($this, 'woo_add_order_item_meta'), 1, 3 ); 
		}

		add_filter('woocommerce_order_again_cart_item_data', array($this, 'filter_order_again_cart_item_data'), 10, 3);
	}

	public function add_to_cart_validation($passed, $product_id, $quantity, $variation_id=false, $variations=false, $cart_item_data=false){ 
		$extra_options = $this->prepare_product_options(false);
		$ignore_unposted = apply_filters( 'thwepo_ignore_unposted_fields', false );
		
		if($extra_options){
			//$upload_fields = array();
			
			foreach($extra_options as $field_name => $field){
				$type = $field->get_property('type');
				$is_posted = isset($_POST[$field_name]) || isset($_REQUEST[$field_name]) ? true : false;
				$posted_value = $this->get_posted_value($field_name, $type);
				
				if(($type === 'radio' || $type === 'multiselect' || $type === 'checkboxgroup' || $type === 'colorpalette' || $type === 'imagegroup') && (!$is_posted || !$posted_value) && !$ignore_unposted){
					$passed = $this->validate_field($passed, $field, $posted_value);
					
				}else if($type === 'file'){
					//$upload_fields[$field_name] = $field;
					$file = isset($_FILES[$field_name]) ? $_FILES[$field_name] : false;
					// Change starts here - no if else before. Was only the statement inside else
					if( $field->get_property('multiple_file') === 'yes' ){
						if( isset( $file['name'] ) && is_array($file['name']) && array_filter( $file['name'] ) ){
							if( $this->file->validate_file_count( $file['name'], $field ) ){
								foreach ($file['name'] as $index => $fname) {
									$nfile = wp_list_pluck($file, $index);
									$passed = $this->file->validate_file($passed, $field, $nfile);
								}
							}else{
								$passed = false;
							}
						}
					}else{
						$passed = $this->file->validate_file($passed, $field, $file);
					}
					// Change ends here
				}else if($is_posted){
					$passed = $this->validate_field($passed, $field, $posted_value);
				}
			}
			
			/*if($passed){
				foreach($upload_fields as $name => $field){
					$uploaded = $this->file->upload_file($_FILES, $name, $field);
					if(isset($uploaded['error'])){
						$this->wepo_add_error('<strong>'.$title.'</strong> '. $upload['error']);
						$passed = false;
					}
				}
			}*/
		}
		return $passed;
	}

	private function validate_field($passed, $field, $posted_value){
		$name  = $field->get_property('name');
		$type  = $field->get_property('type');
		$value = $this->get_posted_value($name, $type);
		$is_required = $field->get_property('required');
		
		if(is_array($value)){
			foreach($value as $key => $val){
				if(THWEPO_Utils::is_blank($val)){
					unset($value[$key]);
				}
			}
		}
		
		if($is_required && empty($value)) {
			if(THWEPO_Utils_Field::is_multi_option_field($type)){
				/* translators: %s: field name */
				$this->wepo_add_error( sprintf(__('Please choose a value for %s.', 'woocommerce-extra-product-options-pro'), $field->get_property('title')) );
			}else{
				/* translators: %s: field name */
				$this->wepo_add_error( sprintf(__('Please enter a value for %s.', 'woocommerce-extra-product-options-pro'), $field->get_property('title')) );
			}

			$passed = false;
		}else{
			$title = THWEPO_i18n::__t(wc_clean($field->get_property('title')));
			$validators = $field->get_property('validate');
			$validators = !empty($validators) ? explode("|", $validators) : false;

			if($validators && !empty($value)){
				foreach($validators as $validator){
					switch($validator) {
						case 'number' :
							if(!is_numeric($value)){
								/* translators: %s: number field */
								$this->wepo_add_error('<strong>'.$title.'</strong> '. sprintf(__('(%s) is not a valid number.', 'woocommerce-extra-product-options-pro'), $value));
								$passed = false;
							}
							break;

						case 'email' :
							if(!is_email($value)){
								/* translators: %s: email field */
								$this->wepo_add_error('<strong>'.$title.'</strong> '. sprintf(__('(%s) is not a valid email address.', 'woocommerce-extra-product-options-pro'), $value));
								$passed = false;
							}
							break;
						default:
							$custom_validators = THWEPO_Utils::get_settings('custom_validators');
							$custom_validator  = is_array($custom_validators) && isset($custom_validators[$validator]) ? $custom_validators[$validator] : false;
							
							if(is_array($custom_validator)){
								$pattern = $custom_validator['pattern'];
								$preg_result = @preg_match($pattern, $value);

								if($preg_result === 0) {
									$this->wepo_add_error(sprintf(THWEPO_i18n::__t($custom_validator['message']), $title));
									$passed = false;
								}else if($preg_result === false){
									$preg_error_msg = error_get_last()["message"];
									$this->wepo_add_error(sprintf(THWEPO_i18n::__t($preg_error_msg)));
									$passed = false;
								}
							}else{
								$con_validators = THWEPO_Utils::get_settings('confirm_validators');
								$cnf_validator = is_array($con_validators) && isset($con_validators[$validator]) ? $con_validators[$validator] : false;
								if(is_array($cnf_validator)){
									$cfield = $cnf_validator['pattern'];
									$cvalue = $this->get_posted_value($cfield);
									
									if($value && $cvalue && $value != $cvalue) {
										$this->wepo_add_error(sprintf(THWEPO_i18n::__t($cnf_validator['message']), $title));
										$passed = false;
									}
								}
							}
							break;
					}
				}
			}
		}

		if($type === 'checkboxgroup' || $type === 'multiselect' || $type === 'colorpalette' || $type === 'imagegroup'){
			$passed = $this->validate_min_max_selection($passed, $field, $value);
		}else{
			if(!is_array($value) && $type !== 'number' && $type !== 'range' ){
				$passed = $this->validate_input_field_min_maxlength($passed, $field, $value);
			}
		}

		if($is_required && ($type === 'inputtext')){
			$passed = $this->validate_masked_input_field($passed, $field, $value);
		}

		return $passed;
	}

	public function validate_input_field_min_maxlength($passed, $field, $value){

		if(!$value){
			return $passed;
		}

		$field_label = $field->get_property('title');
		$maxlength = absint($field->get_property('maxlength'));
		$minlegth = absint($field->get_property('minlength'));
		$word_count = strlen($value);

		if($maxlength && $word_count > $maxlength){
			/* translators: %d: maximum character allowed */
			$this->wepo_add_error('<strong>'.wc_clean($field_label).':</strong> '. sprintf(__('The entered value exceeds the maximum length allowed for the field. The maximum number of characters allowed is %d.', 'woocommerce-extra-product-options-pro'), $maxlength));
			$passed = false;
		}else if($minlegth && $word_count < $minlegth){
			/* translators: %d: minimum character required */
			$this->wepo_add_error('<strong>'.wc_clean($field_label).':</strong> '. sprintf(__('The entered value less than the minimum length required for the field. The minimum number of character required is %d.', 'woocommerce-extra-product-options-pro'), $minlegth));
			$passed = false;
		}

		return $passed;
	}

	public function validate_min_max_selection($passed, $field, $value){
		$field_label = $field->get_property('title');
		$total_item_checked = (is_array($value) && count($value) > 0) ? count($value) : 0;

		if($total_item_checked > 0){
			$min_check = absint($field->get_property('minlength'));
			$max_check = absint($field->get_property('maxlength'));

			if($min_check && ($total_item_checked < $min_check)){
				/* translators: %d: minimum selections */
				$this->wepo_add_error('<strong>'.wc_clean($field_label).':</strong> '. sprintf(__('Make at least %d selections.', 'woocommerce-extra-product-options-pro'), $min_check));
				$passed = false;
			}else if($max_check && $total_item_checked > $max_check){
				/* translators: %d: maximum selection */
				$this->wepo_add_error('<strong>'.wc_clean($field_label).':</strong> '. sprintf(__('Number of selections exceeds maximum limit (%d).', 'woocommerce-extra-product-options-pro'), $max_check));
				$passed = false;
			}
		}

		return $passed;
	}

	public function validate_masked_input_field($passed, $field, $value){
		$name  = $field->get_property('name');
		$unvalidated_fields = $this->get_posted_value('thwepo_unvalidated_fields');
		$unvalidated_fields  = $unvalidated_fields ? explode(",", $unvalidated_fields) : array();

		if(is_array($unvalidated_fields)){
			$unvalidated_fields = array_unique($unvalidated_fields);
			foreach ($unvalidated_fields as $field_name) {

				if($field_name === $name){
					$this->wepo_add_error('<strong>'.wc_clean($field->get_property('title')).':</strong> '.THWEPO_i18n::__t('Please fill in the displayed format'));
					$passed = false;
				}
			}
		}

		return $passed;
	}

	public function add_cart_item_data($cart_item_data, $product_id = 0, $variation_id = 0){
		$skip = (isset($cart_item_data['bundled_by']) && apply_filters('thwepo_skip_extra_options_for_bundled_items', true)) ? true : false;

		$skip = apply_filters('thwepo_skip_extra_options_for_cart_item', $skip, $cart_item_data, $product_id, $variation_id);
		
		if(!$skip){
			$extra_cart_item_data = $this->prepare_extra_cart_item_data();

			if($extra_cart_item_data){
				if(apply_filters('thwepo_set_unique_key_for_cart_item', false, $cart_item_data, $product_id, $variation_id)){
					$cart_item_data['unique_key'] = md5( microtime().rand() );
				}
				$cart_item_data['thwepo_options'] = $extra_cart_item_data;
			}
		}
		return $cart_item_data;
	}

	public function woo_new_order_item($item_id, $item, $order_id){
		$legacy_values = is_object($item) && isset($item->legacy_values) ? $item->legacy_values : false;
		if($legacy_values){
			$extra_options = isset($legacy_values['thwepo_options']) ? $legacy_values['thwepo_options'] : false;
			$product_price = isset($legacy_values['thwepo-original_price']) ? $legacy_values['thwepo-original_price'] : false;
			
			$this->add_order_item_meta($item_id, $item, $extra_options, $product_price);
		}
	}
	
	public function woo_add_order_item_meta( $item_id, $values, $cart_item_key ) {
		if($values && is_array($values)){
			$extra_options = isset($values['thwepo_options']) ? $values['thwepo_options'] : false;
			$product_price = isset($values['thwepo-original_price']) ? $values['thwepo-original_price'] : false;
			
			$this->add_order_item_meta($item_id, $values, $extra_options, $product_price);
		}
	}

	public function add_order_item_meta($item_id, $item, $extra_options, $product_price) {
		if($extra_options){
			$product_info = array();
			$product_info['id'] = $item['product_id'];
			$product_info['price'] = $product_price;
			$product_info['qty'] = $item['quantity'];

			foreach($extra_options as $name => $data){
				$ftype = isset($data['field_type']) ? $data['field_type'] : false;
				$value = isset($data['value']) ? $data['value'] : '';
				
				if($ftype === 'file'){
					$value = json_encode($value);//THWEPO_Utils::get_file_display_name($value);
				}else{
					$value = is_array($value) ? implode(",", $value) : $value;
				}
				
				//$display_value = $value;
				$value = apply_filters('thwepo_add_order_item_meta_value', $value, $name, $value);

				if($ftype != 'file'){
					$value = trim(stripslashes($value));
				}

				$price_html = $this->price->get_display_price_item_meta($data, $product_info, true);
				if($price_html){
					$price_html = apply_filters('thwepo_add_order_item_meta_price_html', $price_html, $name, $data);
					$price_meta_key_prefix = $this->get_order_item_price_meta_key_prefix();

					wc_add_order_item_meta( $item_id, $price_meta_key_prefix.$name, trim(stripslashes($price_html)) );
				}
				
				/*if($this->is_show_option_price_in_order($name, $data)){
					$display_value .= $price_html;
				}*/

				wc_add_order_item_meta($item_id, $name, $value);
			}
		}
	}

	private function prepare_product_options($names_only = true){
		$final_fields = array();
		$allow_get_method = THWEPO_Utils::get_settings('allow_get_method');
		$posted = $allow_get_method ? $_REQUEST : $_POST;

		$product_fields  = isset($posted['thwepo_product_fields']) ? wc_clean($posted['thwepo_product_fields']) : '';
		$disabled_fields = isset($posted['thwepo_disabled_fields']) ? wc_clean($posted['thwepo_disabled_fields']) : '';
		$disabled_sections = isset($posted['thwepo_disabled_sections']) ? wc_clean($posted['thwepo_disabled_sections']) : '';

		$prod_fields = $product_fields ? explode(",", $product_fields) : array();
		$dis_sections  = $disabled_sections ? explode(",", $disabled_sections) : array();
		$dis_fields  = $disabled_fields ? explode(",", $disabled_fields) : array();
		
		if(is_array($dis_sections)){
			$sections = THWEPO_Utils::get_custom_sections();
			if($sections && is_array($sections)){
				foreach($dis_sections as $sname) {
					$section = isset($sections[$sname]) ? $sections[$sname] : false;
					if(THWEPO_Utils_Section::is_valid_section($section)){
						$sfields = THWEPO_Utils_Section::get_fields($section);
						foreach($sfields as $name => $field) {
							if(THWEPO_Utils_Field::is_enabled($field) && ($key = array_search($name, $prod_fields)) !== false){
								unset($prod_fields[$key]);
							}
							/*if(isset($prod_fields[$name])){
								unset($prod_fields[$name]);
							}*/
						}
					}
				}
			}
		}
		
		$result = array_diff($prod_fields, $dis_fields);
		if($names_only){
			$final_fields = $result;
		}else{
			$extra_options = THWEPO_Utils::get_custom_fields_full(true);
			foreach($result as $name) {
				if(isset($extra_options[$name])){
					$final_fields[$name] = $extra_options[$name];
				}
			}
		}
		
		return $final_fields;
	}

	private function prepare_extra_cart_item_data(){
		$extra_data = array();
		$extra_options = $this->prepare_product_options(false);

		if($extra_options){
			foreach($extra_options as $name => $field){
				$type = $field->get_property('type');
				$posted_value = false;
				
				if($type === 'file'){

					$posted_value = apply_filters("thwepo_file_{$field->name}_value", '', $field, $extra_options);

					if(isset($_FILES[$name]) && empty($posted_value) ){
						$file = $_FILES[$name];

						if( $field->get_property('multiple_file') === 'yes' ){
							$posted_files = [];
							if( isset( $file['name'] ) && is_array( $file['name'] ) ){
								foreach ($file['name'] as $index => $fname) {
									$nfile = wp_list_pluck($file, $index);
									$posted_value = $this->file->prepare_file_upload( $nfile, $name, $field );
									if( !$posted_value){
										continue;
									}
									array_push( $posted_files, $posted_value);
								}
								$posted_value = $posted_files;
							}
						}else{
							$posted_value = $this->file->prepare_file_upload( $file, $name, $field );
							if( !$posted_value ){
								continue;
							}
						}
					}

					// Save upload to global variable and reusable
					global $thwepo_files;
					$thwepo_files[$field->name] = $posted_value;
				}else{
					$posted_value = $this->get_posted_value($name, $field->get_property('type'));
				}
				
				if($posted_value) {
					$price_type = $field->get_property('price_type');
					$price_unit = $field->get_property('price_unit');
					$quantity   = false;
					
					if($price_type && ($price_type === 'dynamic' || $price_type === 'dynamic-excl-base-price' || $price_type === 'char-count')){
						if($price_unit && !is_numeric($price_unit)){
							$qty_field = isset($extra_options['price_unit']) ? $extra_options['price_unit'] : false;
							$quantity = $qty_field && $this->get_posted_value($qty_field->get_property('name'), $qty_field->get_property('type'));
							$price_unit = 1;
						}
					}else{
						$price_unit = 0;
					}

					$custom_formula_fields = array();
					if($price_type && ($price_type === 'custom-formula')){
						$custom_formula = $field->get_property('price');
						$custom_formula_fields = $this->get_fields_in_custom_formula($custom_formula, $extra_options);
					}
					
					$data_arr = array();
					$data_arr['field_type']  	  		= $field->get_property('type');
					$data_arr['name']  			  		= $name;
					$data_arr['label'] 		 	  		= THWEPO_Utils_Field::get_display_label($field);
					$data_arr['value'] 		 	  		= $posted_value;
					$data_arr['price']       	  		= $field->get_property('price');
					$data_arr['price_type']  	  		= $price_type;
					$data_arr['price_unit']  	  		= $price_unit;
					$data_arr['price_min_unit']   		= $field->get_property('price_min_unit');
					$data_arr['quantity'] 		  		= $quantity;
					$data_arr['price_field'] 	  		= $field->get_property('price_field');
					$data_arr['options']          		= $field->get_property('options');
					$data_arr['hide_in_cart']     		= $field->get_property('hide_in_cart');
					$data_arr['hide_in_checkout'] 		= $field->get_property('hide_in_checkout');
					$data_arr['show_price_in_order'] 	= $field->get_property('show_price_in_order');
					$data_arr['price_flat_fee'] 		= $field->get_property('price_flat_fee');
					$data_arr['custom_formula'] 		= $custom_formula_fields;
					
					$extra_data[$name] = $data_arr;
				}
			}
		}

		$extra_data = apply_filters('thwepo_extra_cart_item_data', $extra_data);

		return $extra_data;
	}

	public function get_fields_in_custom_formula($custom_formula=false, $extra_options=false){
		if(!$custom_formula || empty($extra_options)){
			return;
		}

		$regExp = '/{([^}]+)}/';
		preg_match_all($regExp, $custom_formula, $placeholders);

		$custom_formula_fields = array();
		foreach($placeholders[1] as $key => $placeholder){
			preg_match('/thwepo_(.*)_price/', $placeholder, $field_name);
			if(!empty($field_name) && isset($field_name[1])){
				$fname = $field_name[1];
				if(is_array($extra_options) && array_key_exists($fname, $extra_options)){
					$field = $extra_options[$fname];
					$type    = $field->get_property('type');
					$field_price = $field->get_property('price');
					$price_type = $field->get_property('price_type');

					if($type === 'file'){

						if($field->get_property('multiple_file') === 'yes'){
							$field_value = isset($_FILES[$fname]['name'][0]) && !empty($_FILES[$fname]['name'][0]) ? 'yes' : '';
						}else{
							$field_value = isset($_FILES[$fname]['name']) && !empty($_FILES[$fname]['name']) ? 'yes' : '';
						}

					}else{
						$field_value = $this->get_posted_value($fname, $type);
					}

					$product_price = isset($_REQUEST['thwepo_product_price']) ? $_REQUEST['thwepo_product_price'] : 0;

					if($this->is_price_field_type_option($type)){
						$price_type = 'normal';
						$price_props = $this->prepare_option_field_price_props($field, $field_value);

						$oprice = isset($price_props['price']) ? explode(',', $price_props['price']) : '';
						$oprice_type_arr = isset($price_props['price_type']) ? explode(',', $price_props['price_type']) : '';

						$total_price = 0;
						if($oprice){

							foreach($oprice as $index => $price){
								$oprice_type = isset($oprice_type_arr[$index]) ? $oprice_type_arr[$index] : 'normal';

								if($oprice_type === 'normal'){
									$price = $price ? $price : 0;
								}else if($oprice_type === 'percentage'){
									$price = $product_price*($price/100);
								}else{
									$price = 0;
								}

								$total_price = $total_price + $price;
							}

							$field_price = $total_price;
						}
					}

					if($price_type === 'normal'){
						$field_price = $field_price ? $field_price : 0;
					}else if($price_type === 'percentage'){
						$field_price = $product_price*($field_price/100);
					}else{
						$field_price = 0;
					}

					if(!$field_value){
						$field_price = 0;
					}

					$custom_formula_fields['price_field'][$fname] = $field_price;
				}
			}

			preg_match('/thwepo_(.*)_value/', $placeholder, $field_name);
			if(!empty($field_name) && isset($field_name[1])){
				$fname = $field_name[1];
				if(is_array($extra_options) && array_key_exists($fname, $extra_options)){
					$field = $extra_options[$fname];
					$type = $field->get_property('type');

					if($type === 'file'){
						if($field->get_property('multiple_file') === 'yes'){
							$field_value = isset($_FILES[$fname]['name'][0]) && !empty($_FILES[$fname]['name'][0]) ? 'yes' : '';
						}else{
							$field_value = isset($_FILES[$fname]['name']) && !empty($_FILES[$fname]['name']) ? 'yes' : '';
						}
					}else{
						$field_value = $this->get_posted_value($fname, $type);
					}

					$custom_formula_fields['value_field'][$fname] = $field_value;
				}
			}
		}

		return $custom_formula_fields;
	}

	private function is_price_field_type_option($type){
		if($type && ($type === 'select' || $type === 'multiselect' || $type === 'radio' || $type === 'checkboxgroup' || $type === 'colorpalette' || $type === 'imagegroup')){
			return true;
		}
		return false;
	}

	private function prepare_option_field_price_props($field, $field_value){
		$price_props = array();
		$price = '';
		$price_type = '';

		$type    = $field->get_property('type');
		$name    = $field->get_property('name');
		$value   = isset($field_value) ? $field_value : false;
		$options = $field->get_property('options');

		if(!is_array($options) || empty($options)){
			return $price_props;
		}

		$is_multiselect = false;
		if(($type === 'colorpalette' || $type === 'imagegroup') && is_array($value)){
			$is_multiselect = true;
		}

		if($type === 'select' || $type === 'radio' || (!$is_multiselect && ($type === 'colorpalette' || $type === 'imagegroup'))){
			$selected_option = isset($options[$value]) ? $options[$value] : false;

			if(is_array($selected_option)){
				$price      = isset($selected_option['price']) ? $selected_option['price'] : false;
				$price_type = isset($selected_option['price_type']) ? $selected_option['price_type'] : false;
				$price_type = $price_type ? $price_type : 'normal';
			}
		}else if($type === 'multiselect' || $type === 'checkboxgroup' || $is_multiselect){
			if(is_array($value)){
				foreach($value as $ovalue){
					$selected_option = isset($options[$ovalue]) ? $options[$ovalue] : false;

					if(is_array($selected_option)){
						$oprice      = isset($selected_option['price']) ? $selected_option['price'] : false;
						$oprice_type = isset($selected_option['price_type']) ? $selected_option['price_type'] : false;

						if(is_numeric($oprice)){
							$oprice_type = $oprice_type ? $oprice_type : 'normal';

							if(!empty($price)){
								$price .= ',';
							}

							if(!empty($price_type)){
								$price_type .= ',';
							}

							$price      .= $oprice;
							$price_type .= $oprice_type;
						}
					}
				}
			}
		}

		if(!empty($price) && !empty($price_type)){
			$price_props['price']      = $price;
			$price_props['price_type'] = $price_type;
		}

		return $price_props;
	}

	/**********************************************
	***** PROCESS PRODUCT FIELDS - END ************
	***********************************************/


	/**********************************************
	***** DISPLAY PRODUCT ITEM META - START *******
	***********************************************/
	private function hooks_display_item_meta(){
		add_filter( 'woocommerce_get_item_data', array($this, 'filter_get_item_data'), 10, 2 );
		add_filter( 'woocommerce_order_item_get_formatted_meta_data', array($this, 'filter_order_item_get_formatted_meta_data'), 10, 2);

		if(THWEPO_Utils::woo_version_check('3.6.0')){
			add_filter( 'woocommerce_email_styles', array($this, 'add_extra_image_group_css_in_email'), 9999, 2 );
		}else{
			add_filter( 'woocommerce_email_styles', array($this, 'add_extra_image_group_css_in_email'), 9999);
		}
		
		add_action( 'wp_head', array($this, 'add_additonal_css_in_cart_and_checkout'));
	}

	// Filter item data to allow 3rd parties to add more to the array.
	public function filter_get_item_data($item_data, $cart_item = null){
		if(apply_filters('thwepo_display_custom_cart_item_meta', true)){
			$item_data = is_array($item_data) ? $item_data : array();		
			$extra_options = $cart_item && isset($cart_item['thwepo_options']) ? $cart_item['thwepo_options'] : false;
			$product_qty = $cart_item && isset($cart_item['quantity']) ? $cart_item['quantity'] : 1;
			$product_price = $cart_item && isset($cart_item['thwepo-original_price']) ? $cart_item['thwepo-original_price'] : false;
			$display_option_text = apply_filters('thwepo_order_item_meta_display_option_text', true);

			if($extra_options){
				$product_info = array();
				$product_info['id'] = $cart_item['product_id'];
				$product_info['price'] = $product_price;
				$product_info['qty'] = $product_qty;
				
				foreach($extra_options as $name => $data){
					//if(isset($data['value']) && isset($data['label'])) {
					if($this->is_show_option_in_cart($name, $data)){
						$ftype = isset($data['field_type']) ? $data['field_type'] : false;
						$value = isset($data['value']) ? $data['value'] : '';
						
						if($ftype === 'file'){
							$value = THWEPO_Utils::get_file_display_name($value, apply_filters('thwepo_item_display_filename_as_link', false, $name));
							//$value = THWEPO_Utils::get_filename_from_path($value);
						}else if($ftype === 'colorpicker'){
							$value = $this->get_formated_color_display($value);

						}else if($ftype === 'colorpalette'){
							$options = isset($data['options']) ? $data['options'] : '';
							$value = $this->get_color_palette_display($value, $options);

						}else if($ftype === 'imagegroup'){
							$options = isset($data['options']) ? $data['options'] : '';
							$value = $this->get_image_group_display($value, $options);
							
						}else{
							$value = is_array($value) ? implode(",", $value) : $value;
							$value = $display_option_text ? THWEPO_Utils::get_option_display_value($name, $value, $data) : $value;
						}
						
						if($this->is_show_option_price_in_cart($name, $data)){
							$value .= $this->price->get_display_price_item_meta($data, $product_info);
						}
						
						$item_data[] = array("name" => THWEPO_i18n::__t($data['label']), "value" => trim(stripslashes($value)));
					}
				}
			}
		}
		return $item_data;
	}

	public function filter_order_item_get_formatted_meta_data($formatted_meta, $order_item){
		if(!empty($formatted_meta)){
			//$name_title_map = THWEPO_Utils::get_options_name_title_map();
			$custom_fields = THWEPO_Utils::get_custom_fields_full();
			$display_option_text = apply_filters('thwepo_order_item_meta_display_option_text', true);
			$price_meta_key_prefix = $this->get_order_item_price_meta_key_prefix();
			
			//if($name_title_map){
			if($custom_fields){
				foreach($formatted_meta as $key => $meta){
					//if(array_key_exists($meta->key, $name_title_map)) {
					if(array_key_exists($meta->key, $custom_fields)) {
						$field = $custom_fields[$meta->key];

						if($this->is_show_option_in_order($field)){
							$type = $field->get_property('type');
							$display_key = THWEPO_Utils_Field::get_display_label($field);
							$value = $meta->value;
							$display_value = '';
							$price_meta_key = $price_meta_key_prefix.$meta->key;
							
							if($type === 'file'){
								// $value = THWEPO_Utils::get_file_display_name_order($value, apply_filters('thwepo_order_display_filename_as_link', true, $meta->key));
								// $display_value = $value;

								$display_value = THWEPO_Utils::get_file_display_name_order($value, apply_filters('thwepo_order_display_filename_as_link', true, $meta->key));

							}else if($type === 'colorpicker'){
								$display_value = $value;
								$display_value = $this->get_formated_color_display($display_value);

							}else if($type === 'colorpalette'){
								$options = $field->get_property('options');
								$multiselection = $field->get_property('multiselection');
								$display_value = $value;

								if($multiselection){
									$display_value = explode(',', $display_value);
								}
								
								$display_value = $this->get_color_palette_display($display_value, $options);

							}else if($type === 'imagegroup'){
								$options = $field->get_property('options');
								$multiselection = $field->get_property('multiselection');
								$display_value = $value;

								if($multiselection){
									$display_value = explode(',', $display_value);
								}
								
								$display_value = $this->get_image_group_display($display_value, $options);

							}else{
								$display_value = $display_option_text ? THWEPO_Utils::get_option_display_value($meta->key, $value, null) : $value;
								//$display_value = $display_option_text ? THWEPO_Utils::get_option_display_value($meta->key, $meta->value, null) : $meta->value;
							}

							if($this->is_show_option_price_in_order($field)){
								$price_html = $order_item->get_meta($price_meta_key);
								if($price_html){
									$display_value .= ' '.$price_html;
								}
							}

							$display_key = apply_filters('thwepo_order_item_display_meta_key', $display_key, $meta, $order_item);
							$display_value = apply_filters('thwepo_order_item_display_meta_value', $display_value, $meta, $order_item);

							$formatted_meta[$key] = (object) array(
								'key'           => $meta->key,
								'value'         => $value,
								//'display_key'   => apply_filters( 'woocommerce_order_item_display_meta_key', $name_title_map[$meta->key] ),
								'display_key'   => THWEPO_i18n::__t($display_key),
								'display_value' => wpautop( make_clickable($display_value) ),
							);
						}else{
							unset($formatted_meta[$key]);
						}
					}else{
						if(THWEPO_Utils::startsWith($meta->key, $price_meta_key_prefix)){
							unset($formatted_meta[$key]);
						}
					}
				}
			}
		}
		return $formatted_meta;
	}

	private function get_image_group_display($option_value, $options){
		$selected_image = '<div class="thwepo-item-val thwepo-item-image-group">';

		if($option_value && is_array($option_value)){
			foreach ($option_value as $key => $option_key) {
				$selected_image .= $this->get_image_item_display($option_key, $options);
			}

		}else{
			$selected_image .= $this->get_image_item_display($option_value, $options);
		}

		$selected_image .= '</div>';
		return $selected_image;
	}

	private function get_image_item_display($option_value, $options){
		$selected_image = '';

		if(array_key_exists($option_value, $options)){
			$option_image_data = isset($options[$option_value]['image']) ? $options[$option_value]['image'] : '';
			$json_decoded = json_decode($option_image_data, true);

			$option_image = '';
			if($json_decoded !== NULL){
				$option_image = isset($json_decoded['thumbnail']) ? $json_decoded['thumbnail'] :'';
			}

			$option_text = isset($options[$option_value]['text']) ? $options[$option_value]['text'] : '' ;
			$option_key = isset($options[$option_value]['key']) ? $options[$option_value]['key'] : '';
			$option_display = $option_text ? $option_text : $option_key;

			$option_display = THWEPO_i18n::__t($option_display);
			$selected_image .= $this->get_formated_image_display($option_image, $option_display);
		}

		return $selected_image;
	}

	private function get_formated_image_display($display_value, $option_text=false){
		if($option_text){
			return '<div class="thwepo-image-item" style="margin-bottom: 5px;"><img style="width:32px; margin-right:5px; vertical-align: middle;" src="'. $display_value .'" alt="item-image"><span>'. $option_text .'</span></div>';
		}else{
			return '<div class="thwepo-image-item" style="margin-bottom: 5px;"><img style="width:32px; margin-right:5px; vertical-align: middle;" src="'. $display_value .'" alt="item-image"><span>'. $display_value .'</span></div>';
		}
	}

	private function get_color_palette_display($option_value, $options){
		$selected_color = '';

		if($option_value && is_array($option_value)){
			foreach ($option_value as $key => $option_key) {
				$selected_color .= $this->get_color_item_display($option_key, $options);				
			}

		}else{
			$selected_color .= $this->get_color_item_display($option_value, $options);
		}

		return $selected_color;
	}

	private function get_color_item_display($option_value, $options){
		$selected_color = '';

		if(array_key_exists($option_value, $options)){
			$option_color = isset($options[$option_value]['color']) ? $options[$option_value]['color'] : '' ;
			$option_text = isset($options[$option_value]['text']) ? $options[$option_value]['text'] : '' ;
			$option_key = isset($options[$option_value]['key']) ? $options[$option_value]['key'] : '';
			$option_display = $option_text ? $option_text : $option_key;

			$option_display = THWEPO_i18n::__t($option_display);
			$selected_color .= $this->get_formated_color_display($option_color, $option_display);
		}

		return $selected_color;
	}

	private function get_formated_color_display($display_value, $option_text=false){
		if($option_text){
			return '<span style="line-height: 0px;padding: 0px; font-size: 22px; color:' . $display_value .';">&#9632;</span>' . $option_text;
		}else{
			return '<span style="line-height: 0px;padding: 0px; font-size: 22px; color:' . $display_value .';">&#9632;</span>' . $display_value;
		}
	}

	public function add_extra_image_group_css_in_email($css, $email=false){
		$custom_css  = '.thwepo-item-val.thwepo-item-image-group {display:block;clear:both;}';
		$custom_css .= '.thwepo-image-item {display: inline-block;margin-right: 10px; margin-bottom: 5px;}';

		$custom_css = apply_filters('thwepo_email_additional_css', $custom_css, $email);
		$css = $css . $custom_css;

		return $css;
	}

	public function add_additonal_css_in_cart_and_checkout(){
		if(is_cart() || is_checkout()){
			$additonal_css = '<style type="text/css">';
			$additonal_css .= '.thwepo-item-val.thwepo-item-image-group .thwepo-image-item {display: inline-block;margin-right: 10px;}';
			$additonal_css .= '</style>';

			echo $additonal_css;
		}
	}

	private function is_show_option_in_cart($name, $data){
		$show = true;

		if(isset($data['value']) && isset($data['label'])){
			if(is_checkout()){
				$hide_in_checkout = isset($data['hide_in_checkout']) ? $data['hide_in_checkout'] : false;

				$show = $hide_in_checkout ? false : true;
				$show = apply_filters('thwepo_display_custom_checkout_item_meta', $show, $name);

			}else if(is_cart()){
				$hide_in_cart = isset($data['hide_in_cart']) ? $data['hide_in_cart'] : false;

				$show = $hide_in_cart ? false : true;
				$show = apply_filters('thwepo_display_custom_cart_item_meta', $show, $name);

			}else{ //To handle mini cart view. This is same as cart page behaviour.
				$hide_in_cart = isset($data['hide_in_cart']) ? $data['hide_in_cart'] : false;
				$show = $hide_in_cart ? false : true;
				$show = apply_filters('thwepo_display_custom_cart_item_meta', $show, $name);
			}
		}else{
			$show = false;
		}

		return $show;
	}

	private function is_show_option_in_order($field){
		$show = true;

		if($field){
			$capability = apply_filters('thwepo_required_capability', 'manage_woocommerce');

			if(current_user_can($capability)){
				$show = $field->get_property('hide_in_order_admin') ? false : $show;
				$show = apply_filters('thwepo_display_custom_order_item_meta_admin', $show, $field->get_property('name'));
			}else{
				$show = $field->get_property('hide_in_order') ? false : $show;
				$show = apply_filters('thwepo_display_custom_order_item_meta', $show, $field->get_property('name'));
			}
		}else{
			$show = false;
		}

		return $show;
	}

	private function is_show_option_price_in_cart($name, $data){
		$show = true;

		if(is_checkout()){
			$show = isset($data['show_price_in_order']) ? filter_var($data['show_price_in_order'], FILTER_VALIDATE_BOOLEAN) : true;
			$show = apply_filters('thwepo_show_price_for_item_meta', $show, $name); //Deprecated
			$show = apply_filters('thwepo_show_option_price_in_checkout', $show, $name);

		}else if(is_cart()){
			$show = isset($data['show_price_in_order']) ? filter_var($data['show_price_in_order'], FILTER_VALIDATE_BOOLEAN) : true;
			$show = apply_filters('thwepo_show_price_for_item_meta', $show, $name); //Deprecated
			$show = apply_filters('thwepo_show_option_price_in_cart', $show, $name);

		}else{ //To handle mini cart view. This is same as cart page behaviour.
			$show = isset($data['show_price_in_order']) ? filter_var($data['show_price_in_order'], FILTER_VALIDATE_BOOLEAN) : true;
			$show = apply_filters('thwepo_show_price_for_item_meta', $show, $name); //Deprecated
			$show = apply_filters('thwepo_show_option_price_in_cart', $show, $name);
		}

		return $show;
	}

	private function is_show_option_price_in_order($field){
		$show = true;

		if($field){
			$name = $field->get_property('name');
			$capability = apply_filters('thwepo_required_capability', 'manage_woocommerce');

			if(current_user_can($capability)){
				$show = $field->get_property('show_price_in_order') ? true : false;

				$show = apply_filters('thwepo_show_price_for_order_formatted_meta', $show, $name); //Deperecated
				$show = apply_filters('thwepo_show_option_price_in_order_admin', $show, $name);
			}else{
				$show = $field->get_property('show_price_in_order') ? true : false;

				$show = apply_filters('thwepo_show_price_for_order_formatted_meta', $show, $name); //Deperecated
				$show = apply_filters('thwepo_show_option_price_in_order', $show, $name);
			}
		}else{
			$show = false;
		}

		return $show;
	}
	/**********************************************
	***** DISPLAY PRODUCT ITEM META - START *******
	***********************************************/


   /***************************************************
	************** ORDER AGAIN DATA - START ***********
	***************************************************/
	public function filter_order_again_cart_item_data($cart_item_data, $item, $order){
		$extra_cart_item_data = $this->prepare_order_again_extra_cart_item_data($item, $order);
			
		if($extra_cart_item_data){
			$cart_item_data['thwepo_options'] = $extra_cart_item_data;
		}
		return $cart_item_data;
	}

	private function prepare_order_again_extra_cart_item_data($item, $order){
		$extra_data = array();

		if($item){
			$meta_data = $item->get_meta_data();
			if(is_array($meta_data)){
				$extra_options = THWEPO_Utils::get_custom_fields_full();

				foreach($meta_data as $key => $meta){
					if(array_key_exists($meta->key, $extra_options)) {
						$field = $extra_options[$meta->key];

						if($meta->value){
							$price_type = $field->get_property('price_type');
							$price_unit = $field->get_property('price_unit');
							$quantity   = false;

							$type = $field->get_property('type');
							$value = $meta->value;
							
							if($type === 'file'){
                            	$value = json_decode($value, true);
							}else if(THWEPO_Utils_Field::is_multi_option_field($type)){
								$value = is_string($value) ? explode(",", $value) : $value;
							}
							
							if($price_type && ($price_type === 'dynamic' || $price_type === 'dynamic-excl-base-price' || $price_type === 'char-count')){
								if($price_unit && !is_numeric($price_unit)){
									$qty_field = isset($extra_options['price_unit']) ? $extra_options['price_unit'] : false;
									$quantity = $qty_field && $this->get_posted_value($qty_field->get_property('name'), $qty_field->get_property('type'));
									$price_unit = 1;
								}
							}else{
								$price_unit = 0;
							}

							$custom_formula_fields = array();
							if($price_type && ($price_type === 'custom-formula')){
								$custom_formula = $field->get_property('price');
								$custom_formula_fields = $this->get_custom_formula_field_from_metadata($custom_formula, $extra_options, $item);
							}

							$data_arr = array();
							$data_arr['field_type']  	  = $type;
							$data_arr['name']  			  = $meta->key;
							$data_arr['label'] 			  = THWEPO_Utils_Field::get_display_label($field);
							$data_arr['value'] 	          = $value;
							$data_arr['price']       	  = $field->get_property('price');
							$data_arr['price_type']  	  = $price_type;
							$data_arr['price_unit']  	  = $price_unit;
							$data_arr['price_min_unit']   = $field->get_property('price_min_unit');
							$data_arr['quantity'] 		  = $quantity;
							$data_arr['price_field'] 	  = $field->get_property('price_field');
							$data_arr['options']          = $field->get_property('options');
							$data_arr['hide_in_cart']     = $field->get_property('hide_in_cart');
							$data_arr['hide_in_checkout'] = $field->get_property('hide_in_checkout');
							$data_arr['price_flat_fee']   = $field->get_property('price_flat_fee');
							$data_arr['custom_formula']   = $custom_formula_fields;
							
							$extra_data[$meta->key] = $data_arr;
						}
					}
				}
			}
		}

		return $extra_data;
	}

	public function get_custom_formula_field_from_metadata($custom_formula=false, $extra_options=false, $item=false){
		if(!$custom_formula || empty($item)){
			return;
		}

		$regExp = '/{([^}]+)}/';
		preg_match_all($regExp, $custom_formula, $placeholders);

		$meta_data = $item->get_meta_data();

		$custom_formula_fields = array();
		$value_fields = array();
		foreach($placeholders[1] as $key => $placeholder){
			preg_match('/thwepo_(.*)_price/', $placeholder, $field_name);
			if(!empty($field_name) && isset($field_name[1])){
				$fname = $field_name[1];
				if(is_array($extra_options) && array_key_exists($fname, $extra_options)){

					$field = $extra_options[$fname];
					$type    = $field->get_property('type');
					$field_price = $field->get_property('price');
					$price_type = $field->get_property('price_type');
					$field_value = $item->get_meta($fname);

					$product = $item->get_product();
					$product_price = $product->get_price('');

					if($this->is_price_field_type_option($type)){
						$price_type = 'normal';

						if($type === 'file'){
                        	$field_value  = $field_value ? 'yes' : '';                        	
						}else if(THWEPO_Utils_Field::is_multi_option_field($type)){
							$field_value = is_string($field_value) ? explode(",", $field_value) : $field_value;
						}

						$price_props = $this->prepare_option_field_price_props($field, $field_value);

						$oprice = isset($price_props['price']) ? explode(',', $price_props['price']) : '';
						$oprice_type_arr = isset($price_props['price_type']) ? explode(',', $price_props['price_type']) : '';

						$total_price = 0;
						if($oprice){

							foreach($oprice as $index => $price){
								$oprice_type = isset($oprice_type_arr[$index]) ? $oprice_type_arr[$index] : 'normal';

								if($oprice_type === 'normal'){
									$price = $price ? $price : 0;
								}else if($oprice_type === 'percentage'){
									$price = $product_price*($price/100);
								}else{
									$price = 0;
								}

								$total_price = $total_price + $price;
							}

							$field_price = $total_price;
						}
					}

					if($price_type === 'normal'){
						$field_price = $field_price ? $field_price : 0;
					}else if($price_type === 'percentage'){
						$field_price = $product_price * ($field_price/100);
					}else{
						$field_price = 0;
					}

					if(!$field_value){
						$field_price = 0;
					}

					$custom_formula_fields['price_field'][$fname] = $field_price;
				}
			}

			preg_match('/thwepo_(.*)_value/', $placeholder, $field_name);
			if(!empty($field_name) && isset($field_name[1])){
				$fname = $field_name[1];
				$field_value = $item->get_meta($fname);

				if(is_array($extra_options) && array_key_exists($fname, $extra_options)){
					$field = $extra_options[$fname];
					$type    = $field->get_property('type');

					if($type === 'file'){
	                	$field_value  = $field_value ? 'yes' : '';
					}else if(THWEPO_Utils_Field::is_multi_option_field($type)){
						$field_value = is_string($field_value) ? explode(",", $field_value) : $field_value;
					}

					$custom_formula_fields['value_field'][$fname] = $field_value;
				}
			}
		}

		return $custom_formula_fields;
	}

   /***************************************************
	************** ORDER AGAIN DATA - END ***********
	***************************************************/


	public function get_order_item_price_meta_key_prefix(){
		return apply_filters('thwepo_add_order_item_price_meta_key_prefix', '_thwepoprice_');
	}

	public function get_posted_value($name, $type = false){
		$is_posted = isset($_POST[$name]) || isset($_REQUEST[$name]) ? true : false;
		$value = false;
		
		if($is_posted){
			$value = isset($_POST[$name]) && $_POST[$name] ? $_POST[$name] : false;
			$value = empty($value) && isset($_REQUEST[$name]) ? $_REQUEST[$name] : $value;

			if($type === 'textarea'){
				$value = sanitize_textarea_field(wp_unslash($value));

			}else{
				$value = wc_clean(wp_unslash($value));
			}
		}

		$value = apply_filters('thwepo_add_to_cart_posted_value', $value, $name, $type);
		return $value;
	}
	
	public function wepo_add_error($msg){
		if(THWEPO_Utils::woo_version_check('2.3.0')){
			wc_add_notice($msg, 'error');
		} else {
			WC()->add_error($msg);
		}
	}

	/*private function remove_disabled_fields($extra_options){
		$disabled_fields = isset( $_POST['thwepo_disabled_fields'] ) ? wc_clean( $_POST['thwepo_disabled_fields'] ) : '';
		
		if(is_array($extra_options) && $disabled_fields){
			$dis_fields = explode(",", $disabled_fields);
			
			if(is_array($dis_fields) && !empty($dis_fields)){
				foreach($extra_options as $fname => $field) {
					if(in_array($fname, $dis_fields)){
						unset($extra_options[$fname]);
					}
				}
			}
		}
		return $extra_options;
	}*/
}

endif;