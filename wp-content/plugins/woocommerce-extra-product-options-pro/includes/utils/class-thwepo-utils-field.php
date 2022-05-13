<?php
/**
 * The custom fields specific functionality for the plugin.
 *
 * @link       https://themehigh.com
 * @since      2.3.0
 *
 * @package    woocommerce-extra-product-options-pro
 * @subpackage woocommerce-extra-product-options-pro/includes/utils
 */
if(!defined('WPINC')){	die; }

if(!class_exists('THWEPO_Utils_Field')):

class THWEPO_Utils_Field {
	static $OPTION_FIELD_TYPES = array("select", "multiselect", "radio", "checkboxgroup", "colorpalette", "imagegroup");

	public static function is_valid_field($field){
		if(isset($field) && $field instanceof WEPO_Product_Field){
			return true;
		} 
		return false;
	}
	
	public static function is_enabled($field){
		if(self::is_valid_field($field) && $field->get_property('enabled')){
			return true;
		}
		return false;
	}
	
	public static function is_custom_field($field){
		//return $field->custom_field;
		return true;
	}
	
	public static function is_price_field($field){
		return $field->get_property('price_field');
	}

	public static function is_option_field($type){
		if($type){
			return in_array($type, self::$OPTION_FIELD_TYPES);
		}
		return false;
	}
	public static function is_multi_option_field($type){
		if($type){
			return in_array($type, array("multiselect", "checkboxgroup", 'colorpalette', 'imagegroup'));
		}
		return false;
	}
	
	public static function is_show_field($field, $product, $categories, $tags=false){
		$rules_set_list = $field->get_property('conditional_rules');
		$valid = THWEPO_Utils_Condition::is_satisfied($rules_set_list, $product, $categories, $tags);
		
		$show = true;
		if($field->get_property('rules_action') === 'hide'){
			$show = $valid ? false : true;
		}else{
			$show = $valid ? true : false;
		}
		$show = apply_filters('thwepo_show_field', $show, $field->name);
		return $show;
	}
	
	public static function prepare_field_from_posted_data($posted, $props){
		$type = isset($posted['i_type']) ? wc_clean(wp_unslash($posted['i_type'])) : '';
		$type = empty($type) ? wc_clean(wp_unslash($posted['i_original_type'])) : $type;
			
		$field = self::create_field($type); 
		
		foreach( $props as $pname => $property ){
			$iname  = 'i_'.$pname;
			
			$pvalue = '';
			if($property['type'] === 'checkbox'){
				$pvalue = isset($posted[$iname]) ? 'yes' : 0;
				
			}else if(isset($posted[$iname])){
				if($pname === 'title' || $pname === 'subtitle' || ($pname === 'value' && $type === 'html') ){
					$pvalue = wp_unslash(wp_filter_post_kses($posted[$iname]));

				}else if($pname === 'value' && $type === 'textarea'){
					$pvalue = wc_sanitize_textarea(wp_unslash($posted[$iname]));

				}else{
					$pvalue = is_array($posted[$iname]) ? implode(',', wc_clean(wp_unslash($posted[$iname]))): wc_clean(wp_unslash($posted[$iname]));
				}
			}
			
			$field->set_property($pname, $pvalue);
		}
		
		if($type === 'select' || $type === 'multiselect' || $type === 'radio' || $type === 'checkboxgroup' || $type === 'colorpalette' || $type === 'imagegroup'){
			$options_json = isset($posted['i_options']) ? trim(stripslashes($posted['i_options'])) : '';
			$options_arr = self::prepare_options_array($options_json);

			$options_extra = apply_filters('thwepo_field_options', array(), $field->get_property('name'));
			if(is_array($options_extra) && !empty($options_extra)){
				$options_arr = array_merge($options_arr, $options_extra);
				// $options_json = self::prepare_options_json($options_arr);
			}

			if(is_array($options_arr) && !empty($options_arr)){
				$options_arr = wc_clean($options_arr);
				$options_json = self::prepare_options_json($options_arr);
			}

			$field->set_property('options_json', $options_json);
			$field->set_property('options', $options_arr);
		}
		
		$ftype = $field->get_property('type');
		if(!$ftype){
			$field->set_property('type', $type);
		}
		
		$field->set_property('name_old', isset($posted['i_name_old']) ? wc_clean(wp_unslash($posted['i_name_old'])) : '');
		
		$field->set_property('rules_action', isset($posted['i_rules_action']) ? trim(stripslashes($posted['i_rules_action'])) : '');
		$field->set_property('conditional_rules_json', isset($posted['i_rules']) ? trim(stripslashes($posted['i_rules'])) : '');
		$field->set_property('conditional_rules', THWEPO_Utils_Condition::prepare_conditional_rules($posted, false));
		
		$field->set_property('rules_action_ajax', isset($posted['i_rules_action_ajax']) ? trim(stripslashes($posted['i_rules_action_ajax'])) : '');
		$field->set_property('conditional_rules_ajax_json', isset($posted['i_rules_ajax']) ? trim(stripslashes($posted['i_rules_ajax'])) : '');
		$field->set_property('conditional_rules_ajax', THWEPO_Utils_Condition::prepare_conditional_rules($posted, true));
		
		self::prepare_properties($field);
		return $field;
	}
	
	public static function prepare_properties($field){
		$name = urldecode( sanitize_title(wc_clean($field->get_property('name'))));
		$type = $field->get_property('type');
		
		$field->set_property('name', $name);
		$field->set_property('id', $name);
				
		if($type === 'radio' || $type === 'select' || $type === 'multiselect' || $type === 'checkboxgroup' || $type === 'colorpalette' || $type === 'imagegroup'){
			foreach($field->get_property('options') as $option_key => $option){
				if(isset($option['price']) && is_numeric($option['price']) && $option['price'] != 0){
					$field->set_property('price_field', 1);
					break;
				}
			}
		}else{
			if((is_numeric($field->price) && $field->price != 0) || $field->price_type === 'custom' || $field->price_type === 'custom-formula'){
				$field->set_property('price_field', 1);
			}
		}
		
		if($type === 'label' || $type === 'heading' || $type === 'html'){
			$field->set_property('price_field', 0);
			$field->set_property('price', 0);
			$field->set_property('price_type', '');
			$field->set_property('price_unit', 0);
			$field->set_property('price_prefix', '');
			$field->set_property('price_suffix', '');
			$field->set_property('taxable', '');
			$field->set_property('tax_class', '');
			$field->set_property('required', 0);
		}
		
		//$field->set_property('property_set', self::get_property_set($field));
		
		//WPML Support
		self::add_wpml_support($field);
		
		return $field;
	}
	
	public static function prepare_options_array($options_json){
		$options_json = rawurldecode($options_json);
		$options_arr = json_decode($options_json, true);
		$options = array();
		
		if($options_arr){
			foreach($options_arr as $option){
				$option['key'] = empty($option['key']) ? $option['text'] : $option['key'];
				$options[$option['key']] = $option;
			}
		}
		return $options;
	}

	public static function prepare_options_json($options){
		$options_json = json_encode($options);
		$options_json = rawurlencode($options_json);
		return $options_json;
	}
	
	public static function create_field($type, $name = false, $field_args = false){
		$field = false;
		
		if(isset($type)){
			if($type === 'inputtext'){
				return new WEPO_Product_Field_InputText();
			}if($type === 'hidden'){
				return new WEPO_Product_Field_Hidden();
			}else if($type === 'password'){
				return new WEPO_Product_Field_Password();
			}else if($type === 'number'){
				return new WEPO_Product_Field_Number();
			}else if($type === 'tel'){
				return new WEPO_Product_Field_Tel();
			}else if($type === 'email'){
				return new WEPO_Product_Field_email();
			}else if($type === 'url'){
				return new WEPO_Product_Field_Url();
			}else if($type === 'range'){
				return new WEPO_Product_Field_Range();
			}else if($type === 'textarea'){
				return new WEPO_Product_Field_Textarea();
			}else if($type === 'select'){
				return new WEPO_Product_Field_Select();
			}else if($type === 'multiselect'){
				return new WEPO_Product_Field_Multiselect();
			}else if($type === 'radio'){
				return new WEPO_Product_Field_Radio();
			}else if($type === 'checkbox'){
				return new WEPO_Product_Field_Checkbox();
			}else if($type === 'checkboxgroup'){
				return new WEPO_Product_Field_CheckboxGroup();
			}else if($type === 'datepicker'){
				return new WEPO_Product_Field_DatePicker();
			}else if($type === 'daterangepicker'){
				return new WEPO_Product_Field_DateRangePicker();
			}else if($type === 'timepicker'){
				return new WEPO_Product_Field_TimePicker();
			}else if($type === 'file'){
				return new WEPO_Product_Field_File();
			}else if($type === 'heading'){
				return new WEPO_Product_Field_Heading();
			}else if($type === 'html'){
				return new WEPO_Product_Field_HTML();
			}else if($type === 'label'){
				return new WEPO_Product_Field_Label();
			}else if($type === 'colorpicker'){
				return new WEPO_Product_Field_ColorPicker();
			}else if($type === 'colorpalette'){
				return new WEPO_Product_Field_ColorPalette();
			}else if($type === 'imagegroup'){
				return new WEPO_Product_Field_ImageGroup();
			}else if($type === 'switch'){
				return new WEPO_Product_Field_Switch();
			}else if($type === 'separator'){
				return new WEPO_Product_Field_Separator();
			}
		}else{
			$field = new WEPO_Product_Field_InputText();
		}
		return $field;
	}
	
	public static function add_wpml_support($field){
		THWEPO_i18n::wpml_register_string('Field Title - '.$field->name, $field->title );
		THWEPO_i18n::wpml_register_string('Field Subtitle - '.$field->name, $field->subtitle );
		THWEPO_i18n::wpml_register_string('Field Placeholder - '.$field->name, $field->placeholder );
		
		$options = $field->get_property('options');
		foreach($options as $option){
			THWEPO_i18n::wpml_register_string('Field Option - '.$field->name.' - '.$option['key'], $option['text'] );
		}
	}
	
	/***********************************************
	 *********** DISPLAY FIELD - START **********
	 ***********************************************/
	public static function get_display_label($field){
		$label = !empty($field->title) ? $field->title : $field->placeholder;
		$label = !empty($label) ? $label : $field->name;
		$label = THWEPO_i18n::__t($label);
		return $label;
	}


	
	private static function get_title_html($field, $skip_id = true, $show_price = false){
		$title_html = '';
		if($field->get_property('title')){
			$title_class = THWEPO_Utils::convert_cssclass_string($field->get_property('title_class'));
			$title_type  = $field->get_property('title_type') ? $field->get_property('title_type') : 'label';
			$title_style = $field->get_property('title_color') ? 'style="color:'.$field->get_property('title_color').';"' : '';
			
			$title_html .= '<'.$title_type.' class="label-tag '.$title_class.'" '.$title_style.'>';

			$title_html .= wp_kses_post(THWEPO_i18n::__t($field->get_property('title')));
			
			if($show_price){
				$title_html .= self::get_display_price($field);
			}
			$title_html .= self::get_required_html($field);
			if( $field->get_property('tooltip') ){
				$title_html .= self::get_tooltop_html( $field );
			}
			$title_html .= '</'.$title_type.'>';
		}
		
		$subtitle_html = '';
		if($field->get_property('subtitle')){
			$subtitle_class = THWEPO_Utils::convert_cssclass_string($field->get_property('subtitle_class'));
			$subtitle_type  = $field->get_property('subtitle_type') ? $field->get_property('subtitle_type') : 'span';
			$subtitle_style = $field->get_property('subtitle_color') ? 'color:'.$field->get_property('subtitle_color').';' : '';
			$subtitle_style = 'style="font-size:80%; '.$subtitle_style.'"';
			
			$subtitle_html .= '<'.$subtitle_type.' class="sublabel-tag '.$subtitle_class.'" '.$subtitle_style.'>';
			$subtitle_html .= wp_kses_post(THWEPO_i18n::__t($field->get_property('subtitle')));

			$subtitle_html .= '</'.$subtitle_type.'>';
		}
		
		$html  = $title_html;
		//$html .= self::get_required_html($field);
		if(!empty($subtitle_html)){
			$html .= '<br/>'.$subtitle_html;
		}
		return $html;
	}

	private static function get_tooltop_html( $field ){
		$html = '';
		$tooltip = $field->get_property('tooltip');

		if($tooltip){
			$font_size = $field->get_property('tooltip_size');

			$font_color = $field->get_property('tooltip_color');
			//$font_color = $font_color ? $font_color : '#ffffff';

			$bg_color = $field->get_property('tooltip_bg_color');
			//$bg_color = $bg_color ? $bg_color : '#333333';

			//$style  = 'color:'.$font_color.';';
			//$style .= '--tooltip-bg:'.$bg_color.';';
			$style = '';
			if($font_size){
				$style .= 'font-size:'.$font_size.';';
			}
			if($font_color){
				$style .= 'color:'.$font_color.';';
			}
			if($bg_color){
				$style .= '--tooltip-bg:'.$bg_color.';';
			}
			
			$html .= '<a href="javascript:void(0)" title="'. esc_attr($tooltip) .'" class="thwepo_tooltip" style="'.$style.'">';
			$html .= '<img src="'.THWEPO_ASSETS_URL_ADMIN.'/css/help.png" title="" >';
			$html .= '</a>';
		}

		return $html;
	}

	private static function is_show_price_label_options($field){
		$name = $field->get_property('name');
		$type = $field->get_property('type');

		$show_price = $field->get_property('show_price_label');
		$show_price = $show_price === "yes" || $show_price === true ? true : false;

		$show_price = apply_filters('thwepo_display_field_option_price', $show_price, $name, $type);
		return $show_price;
	}

	private static function is_show_price_label($field, $show_price = true){
		$name = $field->get_property('name');
		$type = $field->get_property('type');

		if($show_price){
			$show_price = $field->get_property('show_price_label');
			$show_price = $show_price === "yes" || $show_price === true ? true : false;
		}

		$show_price = apply_filters('thwepo_display_field_price', $show_price, $name, $type);
		return $show_price;
	}
	
	private static function prepare_field_value($field){
		$type  = $field->get_property('type');
		$name  = $field->get_property('name');
		$value = apply_filters('thwepo_product_extra_option_value_'.$name, $field->get_property('value'));

		$allow_get_method = THWEPO_Utils::get_settings('allow_get_method');
		$posted = $allow_get_method ? $_REQUEST : $_POST;

		if($type === 'textarea'){
			$value = isset($posted[$name]) && $posted[$name] ? wc_sanitize_textarea(wp_unslash($posted[$name])) : $value;
		}else{
			$value = isset($posted[$name]) && $posted[$name] ? wc_clean(wp_unslash($posted[$name])) : $value;
			$value = is_string($value) && $value ? esc_attr($value) : $value;
		}

		return $value;
	}

	private static function prepare_checkbox_default_value($field){
		$name  = $field->get_property('name');
		$value = apply_filters('thwepo_product_extra_option_value_'.$name, $field->get_property('value'));
		$value = $value ? $value : 1;
		return $value;
	}
	
	private static function prepare_field_props($field, $is_multiple = false, $skip_value = false, $skip_placeholder = false){
		$name  = $field->get_property('name');
		$type  = $field->get_property('type');
		$value = self::prepare_field_value($field);
		$value = is_array($value) ? implode(',', $value) : $value;
		$placeholder = $field->get_property('placeholder') ? THWEPO_i18n::esc_attr__t($field->get_property('placeholder')) : false;
		$is_price_field = self::is_price_field($field);
		$is_multiple = $field->get_property('multiple_file') === 'yes' ? true : $is_multiple;
		$name_suffix = $is_multiple ? '[]' : '';
		
		$price_data  = self::get_price_data($field);
		$input_class = $is_price_field ? 'thwepo-price-field' : '';
		$maxlength   = $field->get_property('maxlength') && is_numeric($field->get_property('maxlength')) ? absint($field->get_property('maxlength')) : false;
		$input_mask = $field->get_property('input_mask');

		$i_class = $field->get_property('input_class');
		if($i_class){
			$input_class .= ' '.THWEPO_Utils::convert_cssclass_string($i_class);
		}
		
		if($is_price_field && ($type === 'select' || $type === 'multiselect')){
			$input_class .= ' thwepo-price-option-field';
		}
		
		$disable_select2 = THWEPO_Utils::get_settings('disable_select2_for_select_fields') ? true : false; 
		
		if($type === 'multiselect' && !$disable_select2){
			$input_class .= ' thwepo-enhanced-multi-select';
			
		}else if($type === 'select' && !$disable_select2){
			$input_class .= ' thwepo-enhanced-select';
			
		}else if($type === 'checkbox' || $type === 'switch'){
			$input_class .= ' input-checkbox';
			$default_value = self::prepare_checkbox_default_value($field);
			$value = $value ? $value : $default_value;
			
		}else if($type === 'datepicker'){
			$input_class .= ' thwepo-date-picker';
			
		}else if($type === 'daterangepicker'){
			$input_class .= ' thwepo-daterange-picker';
			
		}else if($type === 'timepicker'){
			$input_class .= ' thwepo-time-picker input-text';

		}else if($type === 'colorpicker'){
			$input_class .= ' thwepo-color-picker input-text';

		}else if($type === 'file'){
			$input_class .= ' thwepo-file-upload';

		}else if($type === 'range'){
			$input_class .= ' thwepo-range-input';
		}
		
		$required_html = '';
		if($field->get_property('required')){
			$required_html .= apply_filters('thwepo_enable_html5_required_validation', true, $name) ? ' required' : '';
			$input_class .= ' validate-required';
		}
		
		$field_props  = 'id="'.$name.'" name="'.$name.$name_suffix.'"';
		if($input_mask){
			$input_class .= ' thwepo-mask-input';
			$field_props .= 'data-mask-pattern="'. esc_attr($input_mask) .'"';
		}

		$field_props .= !$skip_value ? ' value="'.esc_attr($value).'"' : '';
		$field_props .= $placeholder && !$skip_placeholder ? ' placeholder="'.$placeholder.'"' : '';
		$field_props .= ' class="thwepo-input-field '.trim($input_class).'"';
		$field_props .= $price_data;

		if($type === 'multiselect'){
			$field_props .= $maxlength ? ' data-maxselections="'.absint($maxlength).'"' : '';
		}else{
			$number_type = array('number', 'range');
			if(!in_array($type, $number_type)){
				$field_props .= $maxlength ? ' maxlength="'.absint($maxlength).'"' : '';
			}
		}

		if($type === 'file' && $is_multiple ){
			$field_props .= 'multiple';
		
		}else if($type === 'number' || $type === 'range'){
			$field_props .= is_numeric($field->get_property('minlength')) ? ' min="'. floatval($field->get_property('minlength')) .'"' : '';
			$field_props .= is_numeric($field->get_property('maxlength')) ? ' max="'. floatval($field->get_property('maxlength')) .'"' : '';
			$field_props .= is_numeric($field->get_property('step')) ? 'step="'. abs(floatval($field->get_property('step'))).'"' : '';
		
		}else if($type === 'textarea' || $type === 'inputtext'){
			$minlength = $field->get_property('minlength') && is_numeric($field->get_property('minlength')) ? $field->get_property('minlength') : '';
			$field_props .= $minlength ? ' minlength="'.absint($minlength).'"' : '';
		}

		$field_props .= $required_html;
		
		return $field_props;
	}
	
	private static function prepare_field_options($field){
		$name  = $field->get_property('name');
		$value = self::prepare_field_value($field);
		$is_price_field = self::is_price_field($field);
		$show_price = self::is_show_price_label_options($field);
		//$show_price = $field->get_property('show_price_label') === "yes" ? true : false;
		//$show_price = apply_filters('thwepo_display_field_option_price', $show_price, $name, $field->get_property('type'));
		$skip_i18n = apply_filters('thwepo_skip_translation_for_numeric_field_options', true, $name);
		$field_options = apply_filters('thwepo_input_field_options', $field->get_property('options'), $name);
		
		$value = is_array($value) ? $value : explode(',', $value);

		$options_html = '';
		foreach($field_options as $option_key => $option){		
			$selected = '';
			if(is_array($value)){
				$selected = in_array($option_key, $value) ? 'selected' : '';
			}else{
				$selected = ($option_key == $value) ? 'selected' : '';
			}
			
			$price_data  = self::get_price_data_option($field, $option);
			$option_text = $option['text'];
			
			$skip_translation = $skip_i18n && is_numeric($option_text) ? true : false;
			if(!$skip_translation){
				$option_text = THWEPO_i18n::esc_html__t($option_text);
			}
			
			if($show_price){
				$price_html = self::get_price_html_option($is_price_field, $option, $field);
				if(!empty($option_key) && !empty($option_text)){
					$option_text .= !empty($price_html) ? $price_html : '';
				}
			}
					
			$options_html .= '<option value="'.esc_attr($option_key).'" '.$selected.' '.$price_data.'>'.$option_text.'</option>';
		}
		return $options_html;
	}
	
	private static function get_required_html($field){
		$html = '';
		if($field->get_property('required')){
			$title_required = __('required', 'woocommerce-extra-product-options-pro');
			$html = apply_filters( 'thwepo_required_html', ' <abbr class="required" title="'.$title_required.'">*</abbr>', $field->get_property('name') );
		}
		return $html;
	}
	
	private static function get_ajax_conditions_data($field){
		$data_str = false;
		if($field->get_property('conditional_rules_ajax_json')){
			$rules_action = $field->get_property('rules_action_ajax') ? $field->get_property('rules_action_ajax') : 'show';
			$rules = urldecode($field->get_property('conditional_rules_ajax_json'));
			// $rules = esc_js($rules);
			$rules = esc_attr($rules);
			
			$data_str = 'id="'.$field->name.'_field" data-rules="'. $rules .'" data-rules-action="'. esc_attr($rules_action) .'"';
		}
		return $data_str;
	}
	
	private static function get_price_data($field){
		$price_data_html = '';
		if(self::is_price_field($field)){
			$price_type = $field->get_property('price_type');
			$price_type = empty($price_type) ? 'normal' : $price_type;
			//$price_type = empty($field->get_property('price_type')) ? 'normal' : $field->get_property('price_type');
			$price = is_numeric($field->get_property('price')) ? $field->get_property('price') : '';
			
			if($price !== ''){
				$price_data_html  = ' data-price-type="'.$price_type.'"';
				$price_data_html .= ' data-price="'.$price.'"';
				
				if($price_type === 'dynamic' || $price_type === 'dynamic-excl-base-price' || $price_type === 'char-count'){
					$price_min_unit = is_numeric($field->get_property('price_min_unit')) ? $field->get_property('price_min_unit') : 0;
					$price_unit 	= is_numeric($field->get_property('price_unit')) ? $field->get_property('price_unit') : 0;
					
					$price_data_html .= ' data-price-unit="'.$price_unit.'"';
					$price_data_html .= ' data-price-min-unit="'.$price_min_unit.'"';
				}
			}else if($price_type == 'custom'){
				$price_data_html  = ' data-price-type="'.$price_type.'"';
			}else if($price_type === 'custom-formula'){
				$custom_formula = $field->get_property('price');
				$price_data_html  = ' data-price-type="'.$price_type.'"';
				$price_data_html .= ' data-price="'. esc_attr($custom_formula) .'"';
			}
		}

		// To be added in future
		// $is_flat_fee = $field->get_property('price_flat_fee');
		// if($is_flat_fee){
		// 	$price_data_html .= ' data-flat-fee="'. esc_attr($is_flat_fee) .'"';
		// }
		
		return $price_data_html;
	}
	
	private static function get_price_data_option($field, $option){
		$price_data_html = '';
		if(self::is_price_field($field)){
			$price_type = isset($option['price_type']) && !empty($option['price_type']) ? $option['price_type'] : 'normal';
			$price = isset($option['price']) && is_numeric($option['price']) ? $option['price'] : '';
			
			if($price !== ''){
				$price_data_html .= ' data-price-type="'.esc_attr($price_type).'"';
				$price_data_html .= ' data-price="'.$price.'"';
			}
		}
		return $price_data_html;
	}
	
	private static function get_price_html_option($is_price_field, $option, $field){
		$price_html = '';
		if($is_price_field){

			$name = '';
    		if($field){
    			$name = $field->get_property('name');
    		}

			$price_type = $option['price_type'];
			$price = $option['price'];
			$price = apply_filters('thwepo_extra_cost_option_price', $price, $price_type, $option, $name);
			
			if(is_numeric($price) && $price != 0){
				$price_html = self::get_price_html($is_price_field, $price_type, $price, $field);
				if($price_html){
					$price_prefix = apply_filters('thwepo_extra_cost_display_prefix', ' (', $name, $price, $price_type);
					$price_suffix = apply_filters('thwepo_extra_cost_display_suffix', ')', $name, $price, $price_type);
					
					$price_html = $price_prefix.$price_html.$price_suffix;
					//$html = $price > 0 ? ' (+'.$html.')' : ' ('.$html.')';
				}
			}
		}
		
		return $price_html;
	}
	
	private static function get_price_html($is_price_field, $price_type, $price, $field = false){
		$html = '';
		if($price_type != 'custom' && $is_price_field){
			if($price_type === 'percentage'){
				$html = $price.apply_filters('thwepo_extra_price_percentage_symbol', '%', $field);
			}else if($price_type === 'dynamic' || $price_type === 'dynamic-excl-base-price' || $price_type === 'char-count'){
				if($field){
					$name = $field->get_property('name');
					$price_html = THWEPO_Price::display_price($price, $field);
					$price_unit = esc_html($field->get_property('price_unit'));

					$unit_keyword = ($price_type === 'char-count') ? 'character' : 'unit';
					$price_unit_label = apply_filters('thwepo_extra_cost_unit_label_'.$name, '/'.$price_unit.' '.$unit_keyword, $price_unit, $name, $price_type);
					$html = $price_html.$price_unit_label;
				}
			}else if($price_type === 'custom-formula'){
				// Need to improve this section in future.
				$price = is_numeric($price) ?  $price : 0;
				$html = THWEPO_Price::display_price($price, $field);
			}else{
				$html = THWEPO_Price::display_price($price, $field);
			}
		}
		return apply_filters('thwepo_field_price_html', $html, $price_type, $price, $field);
	}
	
	private static function get_display_price($field){
		$price_html = '';
		$is_price_field = self::is_price_field($field);
		
		if($is_price_field){
			$price_type = $field->get_property('price_type');
			$price = $field->get_property('price');
			$name = $field->get_property('name');
			
			global $product;
			if($product){
				$product_price = $product->get_price('');

				if('dynamic' === $price_type || 'dynamic-excl-base-price' === $price_type || 'char-count' === $price_type){
					$price = apply_filters('thwepo_extra_cost_dynamic_unit_price', $price, $name, $product_price, $price_type);
				}

				$price = apply_filters('thwepo_extra_cost_unit_price_'.$name, $price, $product_price, $price_type);
				$price = apply_filters('thwepo_extra_cost_unit_price', $price, $name, $product_price, $price_type);
			}
			
			$price_html = self::get_price_html($is_price_field, $price_type, $price, $field);
			if($price_html){
				$price_prefix = apply_filters('thwepo_extra_cost_display_prefix', ' (', $name, $price, $price_type);
				$price_suffix = apply_filters('thwepo_extra_cost_display_suffix', ')', $name, $price, $price_type);
				
				$price_html = $price_prefix.$price_html.$price_suffix;
				//$price_html = '<span class="thwepo-field-price">'.$price_prefix.$price_html.$price_suffix . '</span>';
				//$html = $price > 0 ? ' (+'.$html.')' : ' ('.$html.')';
			}
		}
		return $price_html;
	}
	
	private static function get_char_counter_html($field){
		$cssclass  = $field->get_property('cssclass');
		$class_arr = !empty($cssclass) ? explode(',', $cssclass) : false;
		
		$html = '';
		if($class_arr && (in_array("thwepo-char-count", $class_arr) || in_array("thwepo-char-left", $class_arr)) ) {
			$html = '<span id="'.$field->get_property('name').'-char-count" class="thpl-char-count" style="float: right;"></span><div class="clear"></div>';
		}
		return $html;
	}
	
	public static function prepare_field_html($field, $section){
		$field_html = '';
		$field_type = $field->get_property('type');
		
		if($field_type === 'inputtext'){
			$field_html = self::get_html_inputtext($field, $section);
			
		}if($field_type === 'hidden'){
			$field_html = self::get_html_hidden($field, $section);
			
		}else if($field_type === 'password'){
			$field_html = self::get_html_password($field, $section);
			
		}else if($field_type === 'number'){
			$field_html = self::get_html_number($field, $section);
			
		}else if($field_type === 'tel'){
			$field_html = self::get_html_tel($field, $section);
			
		}else if($field_type === 'email'){
			$field_html = self::get_html_email($field, $section);
			
		}else if($field_type === 'url'){
			$field_html = self::get_html_url($field, $section);
			
		}else if($field_type === 'range'){
			$field_html = self::get_html_range($field, $section);
			
		}else if($field_type === 'textarea'){
			$field_html = self::get_html_textarea($field, $section);
			
		}else if($field_type === 'select'){
			$field_html = self::get_html_select($field, $section);
			
		}else if($field_type === 'multiselect'){
			$field_html = self::get_html_multiselect($field, $section);
			
		}else if($field_type === 'radio'){
			$field_html = self::get_html_radio($field, $section);
			
		}else if($field_type === 'checkbox'){
			$field_html = self::get_html_checkbox($field, $section);
			
		}else if($field_type === 'checkboxgroup'){
			$field_html = self::get_html_checkboxgroup($field, $section);
			
		}else if($field_type === 'datepicker'){
			$field_html = self::get_html_datepicker($field, $section);
			
		}else if($field_type === 'daterangepicker'){
			$field_html = self::get_html_daterangepicker($field, $section);
			
		}else if($field_type === 'timepicker'){
			$field_html = self::get_html_timepicker($field, $section);
			
		}else if($field_type === 'file'){
			$field_html = self::get_html_file($field, $section);
			
		}else if($field_type === 'heading' || $field_type === 'label'){
			$field_html = self::get_html_label($field, $section);

		}else if($field_type === 'html'){
			$field_html = self::get_html_html($field, $section);

		}else if($field_type === 'colorpicker'){
			$field_html = self::get_html_colorpicker($field, $section);

		}else if($field_type === 'colorpalette'){
			$field_html = self::get_html_colorpalette($field, $section);

		}else if($field_type === 'imagegroup'){
			$field_html = self::get_html_imagegroup($field, $section);

		}else if($field_type === 'switch'){
			$field_html = self::get_html_switch($field, $section);

		}else if($field_type === 'separator'){
			$field_html = self::get_html_separator($field, $section);
		}
		
		return $field_html;
	}
	
	private static function prepare_field_html_input($field, $section, $input_html, $show_price = true){
		$html = '';
		if($input_html){
			$field_type = $field->get_property('type');
			$field_name = $field->get_property('name');
			$show_price = self::is_show_price_label($field, $show_price);

			$settings = THWEPO_Utils::get_advanced_settings();
			$is_div_model = THWEPO_Utils::get_setting_value($settings, 'use_div_model');
			
			/*if($show_price){
				$show_price = $field->get_property('show_price_label') === "yes" ? true : false;
			}
			$show_price = apply_filters('thwepo_display_field_price', $show_price, $field->get_property('name'), $field_type);*/
			
			$title_position = $field->get_property('title_position') && $field->get_property('title_position') === 'left' ? 'leftside' : esc_attr($field->get_property('title_position'));
			$wrapper_class  = THWEPO_Utils::convert_cssclass_string($field->get_property('cssclass'));
			$field_wrapper_class = 'thwepo_' . esc_attr($field_type).' '.esc_attr($field_name);
			
			$conditions_data = self::get_ajax_conditions_data($field);
			if($conditions_data){
				$wrapper_class .= empty($wrapper_class) ? 'thwepo-conditional-field' : ' thwepo-conditional-field';
			}
			
			$title_cell_with = $section->get_property('title_cell_with');
			$field_cell_with = $section->get_property('field_cell_with');
			
			$title_cell_css = $title_cell_with ? 'width:'. esc_attr($title_cell_with) .';' : '';
			$field_cell_css = $field_cell_with ? 'width:'. esc_attr($field_cell_with) .';' : '';
			
			$title_cell_css = $title_cell_css ? 'style="'.$title_cell_css.'"' : '';
			$field_cell_css = $field_cell_css ? 'style="'.$field_cell_css.'"' : '';
			
			$title_html  = self::get_title_html($field);
			//$title_html .= self::get_required_html($field);
			
			$input_html .= $show_price ? ' '.self::get_display_price($field) : '';
			
			if($field_type === 'hidden'){
				$html .= '<label class="'. $wrapper_class .'" '.$conditions_data.'>';
				$html .= $input_html;
				$html .= '</label>';
			}else{

				if($is_div_model === 'yes'){
					$html .= '<div class="thwepo-field '. $wrapper_class .' '. $field_wrapper_class .' thwepo-title-'. $title_position .'" '.$conditions_data.' data-type="'. $field_type .'">';
					if($field_type === 'checkbox'){
						$html .= '<div class="value">'. $input_html .'</div>';
					}else{
						$html .= '<div class="label '.$title_position.'" '.$title_cell_css.'>'. $title_html .'</div>';
						$html .= '<div class="value '.$title_position.'" '.$field_cell_css.'>'. $input_html .'</div>';
					}
					$html .= '</div>';
				}else{
					$html .= '<tr class="'. $wrapper_class .' '. $field_wrapper_class .'" '.$conditions_data.'data-type="'. $field_type .'">';
					if($field_type === 'checkbox'){
						$html .= '<td class="value" colspan="2">'. $input_html .'</td>';
					}else{
						$html .= '<td class="label '.$title_position.'" '.$title_cell_css.'>'. $title_html .'</td>';
						$html .= '<td class="value '.$title_position.'" '.$field_cell_css.'>'. $input_html .'</td>';
					}
					$html .= '</tr>';
				}
			}
		}	
		return $html;
	}
	
	private static function get_html_inputtext($field, $section){
		$name = $field->get_property('name');
		$field_props = self::prepare_field_props($field);
		
		$input_html  = '<input type="text" '.$field_props.' />';
		$input_html .= self::get_char_counter_html($field);
		
		$html = self::prepare_field_html_input($field, $section, $input_html);
		return $html;
	}
	
	private static function get_html_hidden($field, $section){
		$name = $field->get_property('name');
		$field_props = self::prepare_field_props($field);
		$input_html  = '<input type="hidden" '.$field_props.' />';
		
		$html = self::prepare_field_html_input($field, $section, $input_html, false);
		return $html;
	}
	
	private static function get_html_password($field, $section){
		$name = $field->get_property('name');
		$field_props = self::prepare_field_props($field);

		$view_password = $field->get_property('view_password');		
		if($view_password && apply_filters('thwepo_display_password_view_option', true)){
			$input_html = '<div class="thwepo-password-field">';
			$input_html .= '<input type="password" '.$field_props.' />';
			$input_html .= '<span class="dashicons dashicons-visibility" onclick="thwepoViewPassword(this)"></span>';
			$input_html .= '</div>';
		}else{
			$input_html  = '<input type="password" '.$field_props.' />';
		}
		
		$input_html .= self::get_char_counter_html($field);
		
		$html = self::prepare_field_html_input($field, $section, $input_html);
		return $html;
	}

	private static function get_html_number($field, $section){
		$name = $field->get_property('name');
		$field_props = self::prepare_field_props($field);
		
		$input_html = '<input type="number" '.$field_props.' />';
		
		$html = self::prepare_field_html_input($field, $section, $input_html);
		return $html;
	}

	private static function get_html_tel($field, $section){
		$name = $field->get_property('name');
		$field_props = self::prepare_field_props($field);
		
		$input_html  = '<input type="tel" '.$field_props.' />';
		
		$html = self::prepare_field_html_input($field, $section, $input_html);
		return $html;
	}

	private static function get_html_email($field, $section){
		$name = $field->get_property('name');
		$field_props = self::prepare_field_props($field);
		
		$input_html  = '<input type="email" '.$field_props.' />';
		
		$html = self::prepare_field_html_input($field, $section, $input_html);
		return $html;
	}

	private static function get_html_url($field, $section){
		$name = $field->get_property('name');
		$field_props = self::prepare_field_props($field);
		
		$input_html  = '<input type="url" '.$field_props.' />';
		
		$html = self::prepare_field_html_input($field, $section, $input_html);
		return $html;
	}

	private static function get_html_range($field, $section){
		$name = $field->get_property('name');
		$field_props = self::prepare_field_props($field);
		
		$input_html  = '<div class="thwepo-range-field">';
		$input_html .= '<input type="range" '.$field_props.' />';
		$input_html .= '<output class="thwepo-range-val"></output>';
		$input_html .= '</div>';
		
		$html = self::prepare_field_html_input($field, $section, $input_html);
		return $html;
	}
	
	private static function get_html_textarea($field, $section){
		$name = $field->get_property('name');
		$value = self::prepare_field_value($field);
		$cols = $field->get_property('cols');
		$rows = $field->get_property('rows');
		$field_props = self::prepare_field_props($field, false, true, false);

		$field_props .= $cols ? ' cols="'. esc_attr($cols) .'"' : '';
		$field_props .= $rows ? ' rows="'. esc_attr($rows) .'"' : '';
		
		$input_html  = '<textarea '.$field_props.' >'. esc_textarea($value) .'</textarea>';
		$input_html .= self::get_char_counter_html($field);
		
		$html = self::prepare_field_html_input($field, $section, $input_html);
		return $html;
	}
	
	private static function get_html_select($field, $section){
		$disable_select2 = THWEPO_Utils::get_settings('disable_select2_for_select_fields') ? true : false; 
		
		$name = $field->get_property('name');
		$field_props = self::prepare_field_props($field, false, false, true);
		
		$placeholder = $field->get_property('placeholder') ? THWEPO_i18n::esc_attr__t($field->get_property('placeholder')) : false;
		$options_html = self::prepare_field_options($field);
		
		$field_props .= $placeholder ? ' data-placeholder="'. $placeholder .' "' : ' data-placeholder=""';
		
		$input_html   = '<div class="thwepo-select-field">';
		$input_html  .= '<select '.$field_props.' >';
		if($disable_select2){
			$input_html .= $placeholder ? '<option value="" selected="selected">'.$placeholder.'</option>' : '';
		}
		$input_html .= $options_html;
		$input_html .= '</select>';
		$input_html .= '</div>';
		
		$html = self::prepare_field_html_input($field, $section, $input_html, false);
		return $html;
	}
	
	private static function get_html_multiselect($field, $section){
		$name = $field->get_property('name');
		$field_props = self::prepare_field_props($field, true, false, true);
		
		$placeholder = $field->get_property('placeholder') ? THWEPO_i18n::esc_attr__t($field->get_property('placeholder')) : false;
		$options_html = self::prepare_field_options($field);
		
		$field_props .= $placeholder ? ' data-placeholder="'. $placeholder .' "' : ' data-placeholder=""';
		
		$input_html  = '<div class="thwepo-select-field">';
		$input_html .= '<select multiple="multiple" '.$field_props.'" >';
		$input_html .= $options_html;
		$input_html .= '</select>';
		$input_html .= '</div>';
		
		$html = self::prepare_field_html_input($field, $section, $input_html, false);
		return $html;
	}
	
	private static function get_html_radio($field, $section){
		$name = $field->get_property('name');
		$value = self::prepare_field_value($field);
		$is_price_field = self::is_price_field($field);
		$show_price = self::is_show_price_label_options($field);
		//$show_price = $field->get_property('show_price_label') === "yes" ? true : false;
		//$show_price = apply_filters('thwepo_display_field_option_price',$show_price, $name, $field->get_property('type'));
	
		$cssclass = THWEPO_Utils::convert_cssclass_array($field->get_property('cssclass'));
		$input_class = $is_price_field ? 'thwepo-price-field' : '';
		$title_class = THWEPO_Utils::convert_cssclass_string($field->get_property('title_class'));

		$display_vertically = $field->get_property('display_vertically');
		$outer_class = 'yes' === $display_vertically ? 'thwepo-valign' : 'thwepo-inline';
		
		$options = apply_filters('thwepo_input_field_options', $field->get_property('options'), $name);
	
		$i_class = $field->get_property('input_class');
		if($i_class){
			$input_class .= ' '.THWEPO_Utils::convert_cssclass_string($i_class);
		}

		$required_html = '';
		if($field->get_property('required')){
			$required_html .= apply_filters('thwepo_enable_html5_required_validation', true, $name) ? ' required' : '';
			$input_class .= ' validate-required';
		}
		
		$input_html = '';
		$price_html = '';
		foreach($options as $option_key => $option){
			$checked = checked($value, esc_attr($option_key), false);
			$price_data  = self::get_price_data_option($field, $option);
			$option_text = THWEPO_i18n::esc_html__t($option['text']);
			$option_key  = esc_attr($option_key);
			
			if($show_price){
				$price_html = self::get_price_html_option($is_price_field, $option, $field);
				if(!empty($option_key) && !empty($option_text)){
					$option_text .= !empty($price_html) ? $price_html : '';
				}
			}

			$option_text = apply_filters('thwepo_input_field_option_text', $option_text, $option_key, $options, $price_html, $name);
		
			$field_props  = 'value="'.$option_key.'" class="thwepo-input-field '.trim($input_class).'" '.$checked;
			$field_props .= $price_data;
			$field_props .= $required_html;
			$option_id   = esc_attr($name).'_'.esc_attr($option_key);
			
			$input_html .= '<label for="'. $option_id .'" class="radio '. $title_class .' '. $outer_class .'" style="margin-right: 10px;">';
			$input_html .= '<input type="radio" id="'. $option_id .'" name="'. esc_attr($name) .'" '. $field_props .'/> ';
			$input_html .= $option_text .'</label>';
			$input_html .= (is_array($cssclass) && in_array("valign", $cssclass) && ('yes' !== $display_vertically)) ? '<br/>' : '';
		}
		
		$html = self::prepare_field_html_input($field, $section, $input_html, false);
		return $html;
	}
	
	private static function get_html_checkbox($field, $section){
		$name  = $field->get_property('name');
		$value = $field->get_property('value') ? $field->get_property('value') : 1;
		$value = apply_filters('thwepo_product_extra_option_value_'.$name, $value);
		$title_class = THWEPO_Utils::convert_cssclass_string($field->get_property('title_class'));
		$checked = $field->get_property('checked');
		
		if(isset($_POST[$name])){
			$checked = isset($_POST[$name]) && $_POST[$name] == $value ? 1 : 0;
		}

		$show_price = self::is_show_price_label($field);
		$title_html = self::get_title_html($field, true, $show_price);
		//$title_html .= self::get_required_html($field);

		// if($field->get_property('subtitle') && apply_filters('thwepo_checkbox_field_label_wrap', true, $name)){
		// 	$title_html = '<span class="label-wrapper-checkbox">'.$title_html.'</span>';
		// }
		
		$field_props  = self::prepare_field_props($field, false, false, true);
		$field_props .= $checked ? ' checked="checked"' : '';

		$checkbox_html = '<input type="checkbox" '. $field_props .' /> ';
		
		$input_html  = '<input type="hidden" name="'. esc_attr($name) .'" value="">';
		$input_html .= '<label for="'. esc_attr($name) .'" class="label-tag checkbox '. $title_class .'">';

		// $input_html .= '<input type="checkbox" '. $field_props .' /> ';

		
		//$input_html .= THWEPO_i18n::__t($field->get_property('title'));
		//$input_html .= self::get_required_html($field);

		if(apply_filters('thwepo_checkbox_field_label_wrap', true, $name)){
			$title_html = '<span class="label-wrapper-checkbox">'. $checkbox_html . $title_html .'</span>';
		}else{
			$input_html .= $checkbox_html;
		}

		$input_html .= $title_html;
		$input_html .= '</label>';
		
		$html = self::prepare_field_html_input($field, $section, $input_html, false);
		return $html;
	}
	
	private static function get_html_checkboxgroup($field, $section){
		$name = $field->get_property('name');
		$cssclass = THWEPO_Utils::convert_cssclass_array($field->get_property('cssclass'));
		$value = self::prepare_field_value($field);
		$is_price_field = self::is_price_field($field);

		$display_vertically = $field->get_property('display_vertically');
		$outer_class = 'yes' === $display_vertically ? 'thwepo-valign' : 'thwepo-inline';

		$input_class = $is_price_field ? 'thwepo-price-field' : '';
		$title_class = THWEPO_Utils::convert_cssclass_string($field->get_property('title_class'));
		$show_price = self::is_show_price_label_options($field);
		//$show_price = $field->get_property('show_price_label') === "yes" ? true : false;
		//$show_price = apply_filters('thwepo_display_field_option_price', $show_price, $name, $field->get_property('type'));
		$options_per_line = apply_filters('thwepo_checkboxgroup_options_per_line', 1, $name);
		$options = apply_filters('thwepo_input_field_options', $field->get_property('options'), $name);
		
		$i_class = $field->get_property('input_class');
		if($i_class){
			$input_class .= ' '.THWEPO_Utils::convert_cssclass_string($i_class);
		}

		$value = is_array($value) ? $value : explode(',', $value);

		$index = 1;
		$input_html = '';
		$price_html = '';
		foreach($options as $option_key => $option){
			$checked = '';
			if(is_array($value)){
				$checked = in_array($option_key, $value) ? 'checked' : '';
			}else{
				$checked = ($option_key === $value) ? 'checked' : '';
			}
			
			$price_data  = self::get_price_data_option($field, $option);
			$option_text = THWEPO_i18n::esc_html__t($option['text']);
		
			if($show_price){
				$price_html = self::get_price_html_option($is_price_field, $option, $field);
				if(!empty($option_key) && !empty($option_text)){
					$option_text .= !empty($price_html) ? $price_html : '';
				}
			}
			
			$option_text = apply_filters('thwepo_input_field_option_text', $option_text, $option_key, $options, $price_html, $name);
			$option_id   = esc_attr($name).'_'.esc_attr($option_key);
			$label_style = "display:inline; margin-right: 10px;";
			
			$field_props  = 'id="'.$option_id.'" name="'. esc_attr($name) .'[]" value="'.esc_attr($option_key).'"';
			$field_props .= ' class="thwepo-input-field input-checkbox '.trim($input_class).'" '.$checked;
			$field_props .= $price_data;
			
			$input_html .= '<label for="'. $option_id .'" style="'.$label_style.'" class="label-tag checkbox '. $title_class .' '. $outer_class .'">';
			$input_html .= '<input type="checkbox" data-multiple="1" '. $field_props .' /> ';
			$input_html .= $option_text;
			$input_html .= '</label>';
			
			if(is_array($cssclass) && in_array("valign", $cssclass) && ('yes' !== $display_vertically)){
				$breakline = (is_numeric($options_per_line) && $options_per_line > 0 && fmod($index, $options_per_line) == 0) ? true : false;
				$input_html .= $breakline ? '<br/>' : '';
			}
			
			$index++;
		}
		
		$html = self::prepare_field_html_input($field, $section, $input_html, false);
		return $html;
	}
	
	private static function get_html_datepicker($field, $section){ 
		$name = $field->get_property('name');
		$field_props = self::prepare_field_props($field);
		$field_props .= $field->get_property('readonly') ? ' data-readonly="yes"' : ' data-readonly="no"';

		$min_date = $field->get_property('min_date');
		$max_date = $field->get_property('max_date');
		$disabled_days  = $field->get_property('disabled_days');
		$disabled_dates = $field->get_property('disabled_dates');
		
		$min_date = apply_filters('thwepo_min_date_date_picker', $min_date, $name);
		$max_date = apply_filters('thwepo_max_date_date_picker', $max_date, $name);
		$disabled_days  = apply_filters('thwepo_disabled_days_date_picker', $disabled_days, $name);
		$disabled_dates = apply_filters('thwepo_disabled_dates_date_picker', $disabled_dates, $name);
		$firstDay       = apply_filters('thwepo_date_picker_first_day', 0, $name);
		$display_inline = apply_filters('thwepo_date_picker_display_inline', false, $name);

		$disabled_days  = is_array($disabled_days) ? implode(',', $disabled_days) : $disabled_days;
		$disabled_dates = is_array($disabled_dates) ? implode(',', $disabled_dates) : $disabled_dates;
		
		$dp_props  = ' data-date-format="'. esc_attr($field->get_property('date_format')) .'" data-default-date="'. esc_attr($field->get_property('default_date')) .'"';
		$dp_props .= ' data-min-date="'. esc_attr($min_date) .'" data-max-date="'. esc_attr($max_date) .'"';
		$dp_props .= ' data-year-range="'. esc_attr($field->get_property('year_range')) .'" data-number-months="'. esc_attr($field->get_property('number_of_months')) .'"';
		$dp_props .= ' data-disabled-days="'. esc_attr($disabled_days) .'" data-disabled-dates="'. esc_attr($disabled_dates) .'"';
		$dp_props .= ' data-first-day="'. esc_attr($firstDay) .'"';
		
		if($display_inline){
			$field_props = str_replace('thwepo-date-picker', '', $field_props);
			$dp_props .= ' data-alt-field="'. esc_attr($name) .'"';

			$input_html  = '<input type="hidden" '.$field_props.' />';
			$input_html .= '<span '.$dp_props.' class="thwepo-date-picker"></span>';
		}else{
			$field_props .= $dp_props;
			$input_html  = '<input type="text" '.$field_props.' />';
			//$input_html .= self::get_char_counter_html($field);
		}
		
		$html = self::prepare_field_html_input($field, $section, $input_html);
		return $html;
	}

	private static function get_html_daterangepicker($field, $section){ 
		$name = $field->get_property('name');
		$field_props = self::prepare_field_props($field);
		$field_props .= $field->get_property('readonly') ? ' data-readonly="yes"' : ' data-readonly="no"';

		$start_date = $field->get_property('start_date');
		$end_date = $field->get_property('end_date');

		$min_date = $field->get_property('min_date');
		$max_date = $field->get_property('max_date');
		
		$min_date = apply_filters('thwepo_min_date_date_picker', $min_date, $name);
		$max_date = apply_filters('thwepo_max_date_date_picker', $max_date, $name);
		$firstDay = apply_filters('thwepo_date_picker_first_day', 0, $name);

		$date_format = $field->get_property('date_format');
		$default_date = '';

		$min_year = $field->get_property('min_year');
		$max_year = $field->get_property('max_year');
		$enable_time_picker = $field->get_property('enable_time_picker');

		$show_time_only = $field->get_property('show_time_only');
		$show_single_datepicker = $field->get_property('show_single_datepicker');
		
		$dp_props  = ' data-date-format="'. esc_attr($date_format) .'" data-default-date="'. esc_attr($default_date) .'"';
		$dp_props .= ' data-start-date="'. esc_attr($start_date) .'" data-end-date="'. esc_attr($end_date) .'"';
		$dp_props .= ' data-min-date="'. esc_attr($min_date) .'" data-max-date="'. esc_attr($max_date) .'"';
		$dp_props .= ' data-min-year="'. esc_attr($min_year) .'" data-max-year="'. esc_attr($max_year) .'"';
		$dp_props .= ' data-first-day="'. esc_attr($firstDay) .'"';
		$dp_props .= ' data-timepicker="'. esc_attr($enable_time_picker) .'"';
		$dp_props .= ' data-timeonly="'. esc_attr($show_time_only) .'"';
		$dp_props .= ' data-single-calendar="'. esc_attr($show_single_datepicker) .'"';
		$dp_props .= ' data-type="daterangepicker"';
		
		$field_props .= $dp_props;
		$input_html  = '<input type="text" '.$field_props.' />';

		$start_date = isset($_REQUEST[$name .'_start']) ? $_REQUEST[$name .'_start'] : $start_date;
		$end_date = isset($_REQUEST[$name .'_end']) ? $_REQUEST[$name .'_end'] : $end_date;

		$input_html .= '<input type="hidden" id="'. $name .'_start" name="'. $name .'_start" value="'. $start_date .'"/>';
		$input_html .= '<input type="hidden"id="'. $name .'_end" name="'. $name .'_end" value="'. $end_date .'"/>';
		
		$html = self::prepare_field_html_input($field, $section, $input_html);
		return $html;
	}
	
	private static function get_html_timepicker($field, $section){
		$name = $field->get_property('name');
		$field_props = self::prepare_field_props($field);
		
		$field_props .= ' data-min-time="'.esc_attr($field->get_property('min_time')).'" data-max-time="'.esc_attr($field->get_property('max_time')).'"';
		$field_props .= ' data-step="'.esc_attr($field->get_property('time_step')).'" data-format="'.esc_attr($field->get_property('time_format')).'"';
		
		$input_html  = '<input type="text" '.$field_props.' />';
		//$input_html .= self::get_char_counter_html($field);
		
		$html = self::prepare_field_html_input($field, $section, $input_html);
		return $html;
	}
	
	private static function get_html_file($field, $section){
		$name = $field->get_property('name');
		$field_props = self::prepare_field_props($field);
		
		$input_html  = '<input type="file" '.$field_props.' />';
		$input_html .= '<div class="thwepo-preview-file"></div>';



		/*$hinput_class = '';
		$input_class = '';
		$value_json = '';

		$name  = $field->get_property('name');
		$name  = esc_attr($name);
		$value = self::prepare_field_value($field);
		$value = is_array($value) ? implode(',', $value) : $value;
		$custom_file_field_attr = '';

		$price_data   = self::get_price_data($field);
		$hinput_class = $is_price_field ? 'thwepo-price-field' : '';
		
		$input_class = $field->get_property('input_class');
		if(($ckey = array_search('thwcfe-input-field', $input_class)) !== false){
		    unset($input_class[$ckey]);
		}
		if($input_class){
			$input_class .= ' '.THWEPO_Utils::convert_cssclass_string($i_class);
		}

		if($value){
			$value = str_replace('\\','\\\\',$value);
			$value_arr = json_decode($value, true);
			$value = is_array($value_arr) && isset($value_arr['name']) ? $value_arr['name'] : '';

			if($value){
				$custom_file_field_attr = 'style="display:none;"';
			}
		}

		$input_html .= '<input type="hidden" id="'.$name.'" name="'.$name.'" '.$price_data.' value="'.$value_json.'" ';
		$input_html .= 'class="thwcfe-checkout-file-value input-text '.esc_attr(implode(' ', $hinput_class)) .'" />';
		
		$input_html .= '<input type="file" id="'. $name .'_file" name="'. $name .'_file" value="'. esc_attr($value) .'"';
		$input_html .= ' class="thwcfe-checkout-file '.esc_attr(implode(' ', $input_class)) .'" '; 
		$input_html .= $custom_file_field_attr.' />';
		*/



		
		$html = self::prepare_field_html_input($field, $section, $input_html);
		return $html;
	}
	
	private static function get_html_label($field, $section){
		$title_html = '';
		if($field->get_property('title')){
			$title_id 	 = 'id="'.$field->get_property('name').'_title"';
			$title_class = THWEPO_Utils::convert_cssclass_string($field->get_property('title_class'));
			$title_type  = $field->get_property('title_type') ? $field->get_property('title_type') : 'label';
			$title_style = $field->get_property('title_color') ? 'style="color:'.$field->get_property('title_color').';"' : '';
			
			$title_html .= '<'.$title_type.' '.$title_id.' class="'.$title_class.'" '.$title_style.'>';
			$title_html .= wp_kses_post(THWEPO_i18n::__t($field->get_property('title')));
			$title_html .= '</'.$title_type.'>';
		}
		
		$subtitle_html = '';
		if($field->get_property('subtitle')){
			$subtitle_id 	= 'id="'.$field->get_property('name').'_subtitle"';
			$subtitle_class = THWEPO_Utils::convert_cssclass_string($field->get_property('subtitle_class'));
			$subtitle_type  = $field->get_property('subtitle_type') ? $field->get_property('subtitle_type') : 'span';
			$subtitle_style = $field->get_property('subtitle_color') ? 'color:'.$field->get_property('subtitle_color').';' : '';
			$subtitle_style = 'style="font-size:80%; '.$subtitle_style.'"';
			
			$subtitle_html .= '<'.$subtitle_type.' '.$subtitle_id.' class="'.$subtitle_class.'" '.$subtitle_style.'>';
			$subtitle_html .= wp_kses_post(THWEPO_i18n::__t($field->get_property('subtitle')));
			$subtitle_html .= '</'.$subtitle_type.'>';
		}
		
		$html = $title_html;
		if(!empty($subtitle_html)){
			$html .= '<br/>'.$subtitle_html;
		}
		$html = !empty($html) ? $html : '&nbsp;';
		
		$wrapper_class = THWEPO_Utils::convert_cssclass_string($field->get_property('cssclass'));
		$conditions_data = self::get_ajax_conditions_data($field);
		if($conditions_data){
			$wrapper_class .= empty($wrapper_class) ? 'thwepo-conditional-field' : ' thwepo-conditional-field';
		}

		$settings = THWEPO_Utils::get_advanced_settings();
		$is_div_model = THWEPO_Utils::get_setting_value($settings, 'use_div_model');

		if($is_div_model === 'yes'){
			$field_html  = '<div class="thwepo-field '. $wrapper_class .'" '.$conditions_data.'>';
			$field_html .= '<div class="text-cell">'. $html .'</div>';
			$field_html .= '</div>';

		}else{
			$field_html  = '<tr class="'. $wrapper_class .'" '.$conditions_data.'>';
			$field_html .= '<td class="text-cell" colspan="2">'. $html .'</td>';
			$field_html .= '</tr>';
		}
						
		return $field_html;
	}

	private static function get_html_html($field, $section){
		$field_html = '';
		$content = $field->get_property('value');
		
		if($content){
			$wrapper_class = THWEPO_Utils::convert_cssclass_string($field->get_property('cssclass'));
			$conditions_data = self::get_ajax_conditions_data($field);
			if($conditions_data){
				$wrapper_class .= empty($wrapper_class) ? 'thwepo-conditional-field' : ' thwepo-conditional-field';
			}

			$settings = THWEPO_Utils::get_advanced_settings();
			$is_div_model = THWEPO_Utils::get_setting_value($settings, 'use_div_model');

			if($is_div_model === 'yes'){
				$field_html  = '<div class="thwepo-field '. $wrapper_class .'" '.$conditions_data.'>';
				$field_html .= '<div class="text-cell">'. apply_filters( 'the_content', $content) .'</div>';
				$field_html .= '</div>';

			}else{
				$field_html  = '<tr class="'. $wrapper_class .'" '.$conditions_data.'>';
				$field_html .= '<td class="text-cell" colspan="2">'. apply_filters( 'the_content', $content) .'</td>';
				$field_html .= '</tr>';
			}
		}
				
		return $field_html;
	}

	private static function get_html_colorpicker($field, $section){
		$name = $field->get_property('name');
		$style = $field->get_property('colorpicker_style');
		$field_props = self::prepare_field_props($field);

		$class  = 'thwepo-colorpicker-preview';
		$class .= ' '.$name.'_preview';

		/*if($style == 'style1'){

		}else{

		}
		$class = $style == 'style2' ? 'thplwepo-round ' : '';*/

		$input_html  = '<span class="'.$class.'" style=""></span>';
		$input_html .= '<input type="text" '.$field_props.' autocomplete="off"/>';
		$input_html .= self::get_char_counter_html($field);
		
		$html = self::prepare_field_html_input($field, $section, $input_html);
		return $html;
	}

	private static function get_html_colorpalette($field, $section){
		// $options_per_line = apply_filters('thwepo_checkboxgroup_options_per_line', 1, $name);
		
		$name = $field->get_property('name');
		$is_multiselection = $field->get_property('multiselection');
		$colorpicker_radius = $field->get_property('colorpicker_radius');
		$display_vertically = $field->get_property('display_vertically');
		$option_size = $field->get_property('option_size');
		$option_text_position = $field->get_property('option_text_position');

		$value = self::prepare_field_value($field);

		$is_price_field = self::is_price_field($field);
		$show_price = self::is_show_price_label_options($field);
		
		$cssclass = THWEPO_Utils::convert_cssclass_array($field->get_property('cssclass'));
		$input_class = $is_price_field ? 'thwepo-price-field' : '';
		$title_class = THWEPO_Utils::convert_cssclass_string($field->get_property('title_class'));

		if($option_text_position === 'tooltip'){
			$option_class = 'thwepo-option-tooltip';
		}else if($option_text_position === 'below'){
			$option_class = 'thwepo-option-below';
		}else{
			$option_class = 'thwepo-option-right';
		}
		$title_class .= ' '. $option_class;
		
		$options = apply_filters('thwepo_input_field_options', $field->get_property('options'), $name);

		$i_class = $field->get_property('input_class');
		if($i_class){
			$input_class .= ' '.THWEPO_Utils::convert_cssclass_string($i_class);
		}

		$required_html = '';
		if($field->get_property('required') && !$is_multiselection){
			$required_html .= apply_filters('thwepo_enable_html5_required_validation', true, $name) ? ' required' : '';
			$input_class .= ' validate-required';
		}

		$border_radius_css = '';
		if($colorpicker_radius){
			$border_radius_css .= is_numeric($colorpicker_radius) ? 'border-radius:'. $colorpicker_radius . 'px;' : 'border-radius:' . esc_attr($colorpicker_radius) . ';';
		}

		$option_size_css = '';
		$label_css = '';
		if($option_size){
			$option_size = esc_attr($option_size);
			$option_size_css .= is_numeric($option_size) ? 'width:'. $option_size . 'px; height:'. $option_size . 'px' : 'width:' . $option_size . '; height:'. $option_size .';';

			// if($option_text_position === 'right'){
			// 	$label_css = is_numeric($option_size) ? 'height:'. $option_size . 'px;' : 'height:' . $option_size . ';';
			// }
		}

		// if($option_text_position === 'right' && !$label_css){
		// 	$label_css = 'height:30px;';
		// }

		$additional_css = $border_radius_css . $option_size_css;
		$outer_class = $display_vertically === 'yes' ? 'thwepo-valign' : 'thwepo-inline';

		$value = is_array($value) ? $value : explode(',', $value);
		
		$input_html = '';
		$price_html = '';
		foreach($options as $option_key => $option){

			$checked = '';
			if(is_array($value)){
				$checked = in_array($option_key, $value) ? 'checked' : '';
			}else{
				$checked = ($option_key === $value) ? 'checked' : '';
			}

			$price_data  = self::get_price_data_option($field, $option);
			$option_text = THWEPO_i18n::esc_html__t($option['text']);
			$option_key  = esc_attr($option_key);
			$option_color = isset($option['color']) ? esc_attr($option['color']) : '';

			if($show_price){
				$price_html = self::get_price_html_option($is_price_field, $option, $field);
				if(!empty($option_key) && !empty($option_text)){
					$option_text .= !empty($price_html) ? $price_html : '';
				}
			}

			$option_text = apply_filters('thwepo_input_field_option_text', $option_text, $option_key, $options, $price_html, $name);
			
			$field_props  = 'value="'.$option_key.'" class="thwepo-input-field '.trim($input_class).'" '.$checked;
			$field_props .= $price_data;
			$field_props .= $required_html;
			$option_id   = esc_attr($name).'_'.esc_attr($option_key);

			$input_html .= '<div class="thwepo-color-palette-item '. $outer_class .'">';

			if($is_multiselection){
				$input_html .= '<input type="checkbox" id="'. $option_id .'" name="'. esc_attr($name) .'[]" data-multiple="1" '. $field_props .' /> ';				
			}else{
				$input_html .= '<input type="radio" id="'. $option_id .'" name="'. esc_attr($name) .'" '. $field_props .'/> ';
			}

			$input_html .= '<label for="'. $option_id .'" class="thwepo-color-palette-lable '. $title_class .'" style="'. $label_css .'">';
			$input_html .= '<span class="thwepo-color-box" style="background:'. esc_attr($option_color) .';'. $additional_css .'" title="'. $option_key .'"></span>';
			$input_html .= '<span class="thwepo-color-label">' . $option_text . '</span>';
			$input_html .= '</label>';
			$input_html .= '</div>';
		}

		$html = self::prepare_field_html_input($field, $section, $input_html, false);
		return $html;
	}

	private static function get_html_imagegroup($field, $section){
		// $options_per_line = apply_filters('thwepo_checkboxgroup_options_per_line', 1, $name);
		
		$name = $field->get_property('name');
		$is_multiselection = $field->get_property('multiselection');
		$image_group_radius = $field->get_property('image_group_radius');
		$display_vertically = $field->get_property('display_vertically');
		$image_group_size = $field->get_property('image_group_size');
		$enable_full_image_view = $field->get_property('enable_full_image_view');
		$option_text_position = $field->get_property('option_text_position');

		$value = self::prepare_field_value($field);

		$is_price_field = self::is_price_field($field);
		$show_price = self::is_show_price_label_options($field);
		
		$cssclass = THWEPO_Utils::convert_cssclass_array($field->get_property('cssclass'));
		$input_class = $is_price_field ? 'thwepo-price-field' : '';
		$title_class = THWEPO_Utils::convert_cssclass_string($field->get_property('title_class'));

		if($option_text_position === 'tooltip'){
			$option_class = 'thwepo-option-tooltip';
		}else if($option_text_position === 'right'){
			$option_class = 'thwepo-option-right';
		}else{
			$option_class = 'thwepo-option-below';
		}
		$title_class .= ' '. $option_class;
		
		$options = apply_filters('thwepo_input_field_options', $field->get_property('options'), $name);

		$i_class = $field->get_property('input_class');
		if($i_class){
			$input_class .= ' '.THWEPO_Utils::convert_cssclass_string($i_class);
		}

		$required_html = '';
		if($field->get_property('required') && !$is_multiselection){
			$required_html .= apply_filters('thwepo_enable_html5_required_validation', true, $name) ? ' required' : '';
			$input_class .= ' validate-required';
		}

		$border_radius_css = '';
		if($image_group_radius){
			$border_radius_css .= is_numeric($image_group_radius) ? 'border-radius:'. $image_group_radius . 'px;' : 'border-radius:' . esc_attr($image_group_radius) . ';';
		}

		$outer_class = $display_vertically === 'yes' ? 'thwepo-valign' : 'thwepo-inline';

		$image_size_css = '';
		if($image_group_size){
			$image_size_css .= is_numeric($image_group_size) ? 'width:'. $image_group_size . 'px;' : 'width:' . esc_attr($image_group_size) . ';';
		}else{
			$image_size_css = 'width: 100px;';
		}

		$value = is_array($value) ? $value : explode(',', $value);
		
		$input_html = '';
		$price_html = '';
		foreach($options as $option_key => $option){

			$checked = '';
			if(is_array($value)){
				$checked = in_array($option_key, $value) ? 'checked' : '';
			}else{
				$checked = ($option_key === $value) ? 'checked' : '';
			}

			$price_data  = self::get_price_data_option($field, $option);
			$option_text = THWEPO_i18n::esc_html__t($option['text']);
			$option_key  = esc_attr($option_key);

			$option_image_data = isset($option['image']) ? $option['image'] : '';
			$json_decoded = json_decode($option_image_data, true);

			$thumbnail = '';
			$full_image = '';
			if($json_decoded !== NULL){
				$thumbnail = isset($json_decoded['thumbnail']) ? $json_decoded['thumbnail'] :'';
				$full_image = isset($json_decoded['full_image']) ? $json_decoded['full_image'] :'';
			}

			if(!(($option_text || $option_key) && $thumbnail)){
				continue;
			}

			if($show_price){
				$price_html = self::get_price_html_option($is_price_field, $option, $field);
				if(!empty($option_key) && !empty($option_text)){
					$option_text .= !empty($price_html) ? $price_html : '';
				}
			}

			$option_text = apply_filters('thwepo_input_field_option_text', $option_text, $option_key, $options, $price_html, $name);
			
			$field_props  = 'value="'.$option_key.'" class="thwepo-input-field '.trim($input_class).'" '.$checked;
			$field_props .= $price_data;
			$field_props .= $required_html;
			$option_id   = esc_attr($name).'_'.esc_attr($option_key);

			$input_html .= '<div class="thwepo-image-group-item '. $outer_class .'">';

			if($is_multiselection){
				$input_html .= '<input type="checkbox" id="'. $option_id .'" name="'. esc_attr($name) .'[]" data-multiple="1" '. $field_props .' /> ';				
			}else{
				$input_html .= '<input type="radio" id="'. $option_id .'" name="'. esc_attr($name) .'" '. $field_props .'/> ';
			}

			$input_html .= '<label for="'. $option_id .'" class="thwepo-image-group-lable '. $title_class .'" style="margin-right: 10px;">';
			
			if($thumbnail){
				$input_html .= '<div class="thwepo-img-wrap">';
				$input_html .= '<img class="thwepo-image-box" src="'. esc_url($thumbnail) .'" style="'. $image_size_css . $border_radius_css .'" title="'. $option_key .'">';

				if($enable_full_image_view === 'yes'){
					$input_html .= '<span class="thwepo-popup-image" data-src="'. esc_url($full_image) .'"></span>';
				}

				$input_html .= '</div>';
			}

			$input_html .= '<span class="thwepo-image-label">'. $option_text . '</span>';
			$input_html .= '</label></div>';
		}

		$html = self::prepare_field_html_input($field, $section, $input_html, false);
		return $html;
	}

	private static function get_html_switch($field, $section){
		$name  = $field->get_property('name');
		$value = $field->get_property('value') ? $field->get_property('value') : 1;
		$value = apply_filters('thwepo_product_extra_option_value_'.$name, $value);
		$title_class = THWEPO_Utils::convert_cssclass_string($field->get_property('title_class'));
		$checked = $field->get_property('checked');
		
		if(isset($_POST[$name])){
			$checked = isset($_POST[$name]) && $_POST[$name] == $value ? 1 : 0;
		}

		$show_price = self::is_show_price_label($field);
		$price_value = '';
		if($show_price){
			$price_value = self::get_display_price($field);
		}
		
		$field_props  = self::prepare_field_props($field, false, false, true);
		$field_props .= $checked ? ' checked="checked"' : '';

		$input_html  = '<div class="thwepo-switch-input">';
		$input_html .= '<input type="hidden" name="'. esc_attr($name) .'" value="">';
		$input_html .= '<input type="checkbox" '. $field_props .' /> ';
		$input_html .= '<label for="'. esc_attr($name) .'" class="thwepo-switch-label"></label>';
		$input_html .= '<div class="thwepo-price">'. $price_value .'</div>';
		$input_html .= '</div>';
		
		$html = self::prepare_field_html_input($field, $section, $input_html, false);
		return $html;
	}

	private static function get_html_separator($field, $section){
		$field_html = '';
		$name = $field->get_property('name');
		$field_id = $name ? 'id="'. $name .'"' : '';

		$field_type = $field->get_property('type');
		$separator_type  = $field->get_property('separator_type');
		$separator_hight = $field->get_property('separator_hight');
		$wrapper_class   = THWEPO_Utils::convert_cssclass_string($field->get_property('cssclass'));

		$conditions_data = self::get_ajax_conditions_data($field);
		if($conditions_data){
			$wrapper_class .= empty($wrapper_class) ? 'thwepo-conditional-field' : ' thwepo-conditional-field';
		}

		$css  = '';
		$css .= $separator_type ? 'border-style:' . $separator_type .';' : '';
		$css .= $separator_hight ? 'border-top-width:' . $separator_hight .'; border-bottom-width: 0px;' : '';

		if($css){
			$css = 'style="'. $css .'"';
		}

		$settings = THWEPO_Utils::get_advanced_settings();
		$is_div_model = THWEPO_Utils::get_setting_value($settings, 'use_div_model');

		if($is_div_model === 'yes'){
			$field_html  = '<div class="thwepo-field thwepo_' . esc_attr($field_type).' '. $wrapper_class .'" '. $conditions_data .'>';
			$field_html .= '<div ' . $field_id. ' class="thwepo-seperator"'. $css .'></div>';
			$field_html .= '</div>';

		}else{
			$field_html  = '<tr class="'. $wrapper_class .' thwepo_' . esc_attr($field_type).'" '. $conditions_data .'>';
			$field_html .= '<td ' . $field_id. ' class="thwepo-seperator-td" colspan="2"><div class="thwepo-seperator"'. $css .'></div></td>';
			$field_html .= '</tr>';
		}
		
		return $field_html;
	}
}

endif;