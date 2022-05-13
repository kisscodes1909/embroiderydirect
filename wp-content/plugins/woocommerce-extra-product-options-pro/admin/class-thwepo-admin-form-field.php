<?php
/**
 * The admin field forms functionalities.
 *
 * @link       https://themehigh.com
 * @since      2.3.0
 *
 * @package    woocommerce-extra-product-options-pro
 * @subpackage woocommerce-extra-product-options-pro/admin
 */
if(!defined('WPINC')){	die; }

if(!class_exists('THWEPO_Admin_Form_Field')):

class THWEPO_Admin_Form_Field extends THWEPO_Admin_Form{
	private $field_props = array();

	public function __construct() {
		$this->init_constants();
	}

	private function init_constants(){
		$this->field_props = $this->get_field_form_props();
		//$this->field_props_display = $this->get_field_form_props_display();
	}

	private function get_field_types(){
		return array(
			'inputtext'     	=> __('Text', 'woocommerce-extra-product-options-pro'),
			'hidden'            => __('Hidden', 'woocommerce-extra-product-options-pro'),
			'password'          => __('Password', 'woocommerce-extra-product-options-pro'),
			'number'            => __('Number', 'woocommerce-extra-product-options-pro'),
			'tel'               => __('Telephone', 'woocommerce-extra-product-options-pro'),
			'email'             => __('Email', 'woocommerce-extra-product-options-pro'),
			'range'             => __('Slider/Range', 'woocommerce-extra-product-options-pro'),
			'url'               => __('URL', 'woocommerce-extra-product-options-pro'),
			'textarea'          => __('Textarea', 'woocommerce-extra-product-options-pro'),
			'select'            => __('Select', 'woocommerce-extra-product-options-pro'),
			'multiselect'       => __('Multiselect', 'woocommerce-extra-product-options-pro'),
			'radio'             => __('Radio Button', 'woocommerce-extra-product-options-pro'),
			'checkbox'          => __('Checkbox', 'woocommerce-extra-product-options-pro'),
			'checkboxgroup'     => __('Checkbox Group', 'woocommerce-extra-product-options-pro'),
			'colorpalette'      => __('Color Palette', 'woocommerce-extra-product-options-pro'),
			'imagegroup'        => __('Image Group', 'woocommerce-extra-product-options-pro'),
			'datepicker'    	=> __('Date Picker', 'woocommerce-extra-product-options-pro'),
			'daterangepicker'   => __('Date & Time Range Picker', 'woocommerce-extra-product-options-pro'),
			'timepicker'        => __('Time Picker', 'woocommerce-extra-product-options-pro'),
			'colorpicker'       => __('Colorpicker', 'woocommerce-extra-product-options-pro'),
			'switch'            => __('Switch', 'woocommerce-extra-product-options-pro'),
			'file'              => __('File Upload', 'woocommerce-extra-product-options-pro'),
			'heading'           => __('Heading', 'woocommerce-extra-product-options-pro'),
			'label'             => __('Paragraph', 'woocommerce-extra-product-options-pro'),
			'html'              => __('HTML', 'woocommerce-extra-product-options-pro'),
			'separator'         => __('Separator', 'woocommerce-extra-product-options-pro'),
		);

		/*return array('inputtext' => 'Text', 'hidden' => 'Hidden', 'password' => 'Password', 'textarea' => 'Textarea', 'select' => 'Select', 'multiselect' => 'Multiselect', 
			'radio' => 'Radio', 'checkbox' => 'Checkbox', 'checkboxgroup' => 'Checkbox Group', 'datepicker' => 'Date Picker', 'timepicker' => 'Time Picker', 
			'heading' => 'Heading', 'label' => 'Label');*/
	}

	public function get_field_form_props(){
		$html_text_tags = $this->get_html_text_tags();
		$field_types = $this->get_field_types();
		
		$validators = array(
			'email'  => __('Email', 'woocommerce-extra-product-options-pro'),
			'number' => __('Number', 'woocommerce-extra-product-options-pro'),
		);
		$custom_validators = THWEPO_Utils::get_settings('custom_validators');
		if(is_array($custom_validators)){
			foreach( $custom_validators as $vname => $validator ) {
				$validators[$vname] = $validator['label'];
			}
		}
		
		$confirm_validators = THWEPO_Utils::get_settings('confirm_validators');
		if(is_array($confirm_validators)){
			foreach( $confirm_validators as $vname => $validator ) {
				$validators[$vname] = $validator['label'];
			}
		}
		
		$price_types = array(
			'normal'      => __('Fixed', 'woocommerce-extra-product-options-pro'),
			'custom'      => __('Custom', 'woocommerce-extra-product-options-pro'),
			'percentage'  => __('Percentage of Product Price', 'woocommerce-extra-product-options-pro'),
			'dynamic'     => __('Dynamic', 'woocommerce-extra-product-options-pro'),
			'dynamic-excl-base-price' => __('Dynamic - Exclude base price', 'woocommerce-extra-product-options-pro'),
			'char-count' => __('Character Count', 'woocommerce-extra-product-options-pro'),
			'custom-formula' => __('Custom Formula', 'woocommerce-extra-product-options-pro'),
		);
		$price_types_non_input = array(
			'normal'      => __('Fixed', 'woocommerce-extra-product-options-pro'),
			'percentage'  => __('Percentage of Product Price', 'woocommerce-extra-product-options-pro'),
		);
		
		$title_positions = array(
			'left'  => __('Left of the field', 'woocommerce-extra-product-options-pro'),
			'above' => __('Above field', 'woocommerce-extra-product-options-pro'),
		);
		
		$time_formats = array(
			'h:i A' => __('12-hour format', 'woocommerce-extra-product-options-pro'),
			'H:i'   => __('24-hour format', 'woocommerce-extra-product-options-pro'),
		);
		
		$week_days = array(
			'sun' => __('Sunday', 'woocommerce-extra-product-options-pro'),
			'mon' => __('Monday', 'woocommerce-extra-product-options-pro'),
			'tue' => __('Tuesday', 'woocommerce-extra-product-options-pro'),
			'wed' => __('Wednesday', 'woocommerce-extra-product-options-pro'),
			'thu' => __('Thursday', 'woocommerce-extra-product-options-pro'),
			'fri' => __('Friday', 'woocommerce-extra-product-options-pro'),
			'sat' => __('Saturday', 'woocommerce-extra-product-options-pro'),
		);
		
		$upload_file_types = array(
			'png'  => __('PNG', 'woocommerce-extra-product-options-pro'),
			'jpg'  => __('JPG', 'woocommerce-extra-product-options-pro'),
			'gif'  => __('GIF', 'woocommerce-extra-product-options-pro'),
			'pdf'  => __('PDF', 'woocommerce-extra-product-options-pro'),
			'docx' => __('DOCX', 'woocommerce-extra-product-options-pro'),
		);

		$colorpicker_styles = array(
			'style1'  => __('Style1', 'woocommerce-extra-product-options-pro'),
			'style2'  => __('Style2', 'woocommerce-extra-product-options-pro'),
		);

		$option_text_position = array(
			'right'   => __('Right of the option', 'woocommerce-extra-product-options-pro'),
			'below'   => __('Below option', 'woocommerce-extra-product-options-pro'),
			'tooltip' => __('Show as Tooltip', 'woocommerce-extra-product-options-pro'),
		);

		$border_types = array(
			'solid'  => __('Solid', 'woocommerce-extra-product-options-pro'),
			'dotted' => __('Dotted', 'woocommerce-extra-product-options-pro'),
			'dashed' => __('Dashed', 'woocommerce-extra-product-options-pro'),			
			'none'   => __('None', 'woocommerce-extra-product-options-pro'),
		);
		
		$hint_name = __("Used to save values in database. Name must begin with a lowercase letter.", 'woocommerce-extra-product-options-pro');
		$hint_title = __("Display name for the input field which will be shown on the product page. A link can be set by using the relevant HTML tags. For example: <a href='URL that you want to link to' target='_blank'>I agree to the terms and conditions</a>. Please use single quotes instead of double quotes", 'woocommerce-extra-product-options-pro');
		$hint_value = __("Default value to be shown when the checkout form is loaded.", 'woocommerce-extra-product-options-pro');
		$hint_placeholder = __("Short hint that describes the expected value/format of the input field.", 'woocommerce-extra-product-options-pro');
		$hint_input_class = __("Define CSS class here to make the input field styled differently.", 'woocommerce-extra-product-options-pro');
		$hint_title_class = __("Define CSS class name here to style Label.", 'woocommerce-extra-product-options-pro');
		
		$hint_accept = __("Specify allowed file types separated by comma (e.g. png,jpg,docx,pdf).", 'woocommerce-extra-product-options-pro');
		
		$hint_default_date = __("Specify a date in the current date format, or number of days from today (e.g. +7) or a string of values and periods ('y' for years, 'm' for months, 'w' for weeks, 'd' for days, e.g. '+1m +7d'), or leave empty for today.", 'woocommerce-extra-product-options-pro');
		$hint_date_format = __("The format for parsed and displayed dates.", 'woocommerce-extra-product-options-pro');
		$hint_min_date = __("The minimum selectable date. Specify a date in yyyy-mm-dd format, or number of days from today (e.g. -7) or a string of values and periods ('y' for years, 'm' for months, 'w' for weeks, 'd' for days, e.g. '-1m -7d'), or leave empty for no minimum limit.", 'woocommerce-extra-product-options-pro');
		$hint_max_date = __("The maximum selectable date. Specify a date in yyyy-mm-dd format, or number of days from today (e.g. +7) or a string of values and periods ('y' for years, 'm' for months, 'w' for weeks, 'd' for days, e.g. '+1m +7d'), or leave empty for no maximum limit.", 'woocommerce-extra-product-options-pro');
		$hint_year_range = __("The range of years displayed in the year drop-down: either relative to today's year ('-nn:+nn' e.g. -5:+3), relative to the currently selected year ('c-nn:c+nn' e.g. c-10:c+10), absolute ('nnnn:nnnn' e.g. 2002:2012), or combinations of these formats ('nnnn:+nn' e.g. 2002:+3). Note that this option only affects what appears in the drop-down, to restrict which dates may be selected use the minDate and/or maxDate options.", 'woocommerce-extra-product-options-pro');
		$hint_number_of_months = __("The number of months to show at once.", 'woocommerce-extra-product-options-pro');
		$hint_disabled_dates = __("Specify dates in yyyy-mm-dd format separated by comma.", 'woocommerce-extra-product-options-pro');

		$hint_date_range_start_date = __("Start date of date range picker in the configured date format", 'woocommerce-extra-product-options-pro');
		$hint_date_range_end_date = __("End date of date range picker in the configured date format", 'woocommerce-extra-product-options-pro');
		$hint_date_range_min_year = __("The minimum selectable year. Eg: 2010", 'woocommerce-extra-product-options-pro');
		$hint_date_range_max_year = __("The Maximum selectable year. Eg: 2025", 'woocommerce-extra-product-options-pro');
		$hint_date_range_time_only = __("Enable Time picker for Time selection", 'woocommerce-extra-product-options-pro');
		
		return array(
			'name' 		  => array('type'=>'text', 'name'=>'name', 'label'=>__('Name', 'woocommerce-extra-product-options-pro'), 'required'=>1),
			'type' 		  => array('type'=>'select', 'name'=>'type', 'label'=>__('Field Type', 'woocommerce-extra-product-options-pro'), 'required'=>1, 'options'=>$field_types, 
								'onchange'=>'thwepoFieldTypeChangeListner(this)'),
			'value' 	  => array('type'=>'text', 'name'=>'value', 'label'=>__('Default Value', 'woocommerce-extra-product-options-pro')),
			'placeholder' => array('type'=>'text', 'name'=>'placeholder', 'label'=>__('Placeholder', 'woocommerce-extra-product-options-pro')),
			'validate' 	  => array('type'=>'multiselect', 'name'=>'validate', 'label'=>__('Validations', 'woocommerce-extra-product-options-pro'), 'placeholder'=>__('Select validations', 'woocommerce-extra-product-options-pro'), 'options'=>$validators),
			'cssclass'    => array('type'=>'text', 'name'=>'cssclass', 'label'=>__('Wrapper Class', 'woocommerce-extra-product-options-pro'), 'placeholder'=>__('Separate classes with comma', 'woocommerce-extra-product-options-pro')),
			'input_class'    => array('type'=>'text', 'name'=>'input_class', 'label'=>__('Input Class', 'woocommerce-extra-product-options-pro'), 'placeholder'=>__('Separate classes with comma', 'woocommerce-extra-product-options-pro')),
			
			'price'        => array('type'=>'text', 'name'=>'price', 'label'=>__('Price', 'woocommerce-extra-product-options-pro'), 'placeholder'=>__('Price', 'woocommerce-extra-product-options-pro')),
			'price_unit'   => array('type'=>'text', 'name'=>'price_unit', 'label'=>__('Unit', 'woocommerce-extra-product-options-pro'), 'placeholder'=>__('Unit', 'woocommerce-extra-product-options-pro')),
			'price_type'   => array('type'=>'select', 'name'=>'price_type', 'label'=>__('Price Type', 'woocommerce-extra-product-options-pro'), 'options'=>$price_types, 'onchange'=>'thwepoPriceTypeChangeListener(this)'),
			'price_min_unit' => array('type'=>'text', 'name'=>'price_min_unit', 'label'=>__('Min. Unit', 'woocommerce-extra-product-options-pro'), 'placeholder'=>__('Min. Unit', 'woocommerce-extra-product-options-pro')),
			//'price_prefix' => array('type'=>'text', 'name'=>'price_prefix', 'label'=>'Price Prefix'),
			//'price_suffix' => array('type'=>'text', 'name'=>'price_suffix', 'label'=>'Price Suffix'),
			'show_price_label' => array('type'=>'checkbox', 'name'=>'show_price_label', 'label'=>__("Display price label along with field input box", 'woocommerce-extra-product-options-pro'), 'value'=>'yes', 'checked'=>1),
			'show_price_in_order' => array('type'=>'checkbox', 'name'=>'show_price_in_order', 'label'=>__("Display price in Cart, Checkout and Order details", 'woocommerce-extra-product-options-pro'), 'value'=>'yes', 'checked'=>1),
			'price_flat_fee' => array('type'=>'checkbox', 'name'=>'price_flat_fee', 'label'=>__("Apply price as Flat fee", 'woocommerce-extra-product-options-pro'), 'value'=>'yes', 'checked'=>0, 'onchange' => 'thwepoFlatFeeToggleListener(this)'),
			//'show_price_table' => array('type'=>'checkbox', 'name'=>'show_price_table', 'label'=>"Display calculated price in price table", 'value'=>'yes', 'checked'=>0),
			
			'minlength'   => array('type'=>'number', 'name'=>'minlength', 'label'=>__('Min. Length', 'woocommerce-extra-product-options-pro'), 'hint_text'=>__('The minimum number of characters allowed', 'woocommerce-extra-product-options-pro'), 'min'=>0),
			'maxlength'   => array('type'=>'number', 'name'=>'maxlength', 'label'=>__('Max. Length', 'woocommerce-extra-product-options-pro'), 'hint_text'=>__('The maximum number of characters allowed', 'woocommerce-extra-product-options-pro'), 'min'=>0),

			'step'   => array('type'=>'number', 'name'=>'step', 'label'=>__('Step. Value', 'woocommerce-extra-product-options-pro'), 'hint_text'=>__('Specifies the legal number intervals', 'woocommerce-extra-product-options-pro')),
			'checked'  => array('type'=>'checkbox', 'name'=>'checked', 'label'=>__('Checked by default', 'woocommerce-extra-product-options-pro'), 'value'=>'yes', 'checked'=>0),

			'required' => array('type'=>'checkbox', 'name'=>'required', 'label'=>__('This field is Required', 'woocommerce-extra-product-options-pro'), 'value'=>'yes', 'checked'=>0, 'status'=>1),
			'enabled'  => array('type'=>'checkbox', 'name'=>'enabled', 'label'=>__('This field is Enabled', 'woocommerce-extra-product-options-pro'), 'value'=>'yes', 'checked'=>1, 'status'=>1),
			'readonly'  => array('type'=>'checkbox', 'name'=>'readonly', 'label'=>__('Readonly', 'woocommerce-extra-product-options-pro'), 'value'=>'yes', 'checked'=>0, 'status'=>1),

			'hide_in_cart' => array('type'=>'checkbox', 'name'=>'hide_in_cart', 'label'=>__("Don't display in cart", 'woocommerce-extra-product-options-pro'), 'value'=>'yes', 'checked'=>0),
			'hide_in_checkout' => array('type'=>'checkbox', 'name'=>'hide_in_checkout', 'label'=>__("Don't display in checkout", 'woocommerce-extra-product-options-pro'), 'value'=>'yes', 'checked'=>0),
			'hide_in_order' => array('type'=>'checkbox', 'name'=>'hide_in_order', 'label'=>__("Don't display in order for customers", 'woocommerce-extra-product-options-pro'), 'value'=>'yes', 'checked'=>0),
			'hide_in_order_admin' => array('type'=>'checkbox', 'name'=>'hide_in_order_admin', 'label'=>__("Don't display in order for Admin users", 'woocommerce-extra-product-options-pro'), 'value'=>'yes', 'checked'=>0),
			
			'title'          => array('type'=>'text', 'name'=>'title', 'label'=>__('Title', 'woocommerce-extra-product-options-pro')),
			'title_position' => array('type'=>'select', 'name'=>'title_position', 'label'=>__('Title Position', 'woocommerce-extra-product-options-pro'), 'options'=>$title_positions, 'value'=>'left'),
			'title_type'     => array('type'=>'select', 'name'=>'title_type', 'label'=>__('Title Type', 'woocommerce-extra-product-options-pro'), 'value'=>'label', 'options'=>$html_text_tags),
			'title_color'    => array('type'=>'colorpicker', 'name'=>'title_color', 'label'=>__('Title Color', 'woocommerce-extra-product-options-pro')),
			'title_class'    => array('type'=>'text', 'name'=>'title_class', 'label'=>__('Title Class', 'woocommerce-extra-product-options-pro'), 'placeholder'=>__('Separate classes with comma', 'woocommerce-extra-product-options-pro')),
			
			'subtitle'       => array('type'=>'text', 'name'=>'subtitle', 'label'=>__('Description', 'woocommerce-extra-product-options-pro')),
			'subtitle_type'  => array('type'=>'select', 'name'=>'subtitle_type', 'label'=>__('Description Type', 'woocommerce-extra-product-options-pro'), 'value'=>'label', 'options'=>$html_text_tags),
			'subtitle_color' => array('type'=>'colorpicker', 'name'=>'subtitle_color', 'label'=>__('Description Color', 'woocommerce-extra-product-options-pro')),
			'subtitle_class' => array('type'=>'text', 'name'=>'subtitle_class', 'label'=>__('Description Class', 'woocommerce-extra-product-options-pro'), 'placeholder'=>__('Separate classes with comma', 'woocommerce-extra-product-options-pro')),
			
			'maxsize' => array('type'=>'text', 'name'=>'maxsize', 'label'=>__('Maxsize(in MB)', 'woocommerce-extra-product-options-pro')),
			'accept'  => array('type'=>'text', 'name'=>'accept', 'label'=>__('Accepted File Types', 'woocommerce-extra-product-options-pro'), 'placeholder'=>__('eg: png,jpg,docx,pdf', 'woocommerce-extra-product-options-pro'), 'hint_text'=>$hint_accept),

			'cols' => array('type'=>'text', 'name'=>'cols', 'label'=>__('Cols', 'woocommerce-extra-product-options-pro')),
			'rows' => array('type'=>'text', 'name'=>'rows', 'label'=>__('Rows', 'woocommerce-extra-product-options-pro')),
						
			'default_date' => array('type'=>'text','name'=>'default_date', 'label'=>__('Default Date', 'woocommerce-extra-product-options-pro'),'placeholder'=>"Leave empty for today's date",'hint_text'=>$hint_default_date),
			'date_format'  => array('type'=>'text', 'name'=>'date_format', 'label'=>__('Date Format', 'woocommerce-extra-product-options-pro'), 'value'=>'dd/mm/yy', 'hint_text'=>$hint_date_format),
			'min_date'     => array('type'=>'text', 'name'=>'min_date', 'label'=>__('Min. Date', 'woocommerce-extra-product-options-pro'), 'placeholder'=>'The minimum selectable date', 'hint_text'=>$hint_min_date),
			'max_date'     => array('type'=>'text', 'name'=>'max_date', 'label'=>__('Max. Date', 'woocommerce-extra-product-options-pro'), 'placeholder'=>'The maximum selectable date', 'hint_text'=>$hint_max_date),
			'year_range'   => array('type'=>'text', 'name'=>'year_range', 'label'=>__('Year Range', 'woocommerce-extra-product-options-pro'), 'value'=>'-100:+1', 'hint_text'=>$hint_year_range),
			'number_of_months' => array('type'=>'text', 'name'=>'number_of_months', 'label'=>__('Number Of Months', 'woocommerce-extra-product-options-pro'), 'value'=>'1', 'hint_text'=>$hint_number_of_months),
			'disabled_days'  => array('type'=>'multiselect', 'name'=>'disabled_days', 'label'=>__('Disabled Days', 'woocommerce-extra-product-options-pro'), 'placeholder'=>'Select days to disable', 'options'=>$week_days),
			'disabled_dates' => array('type'=>'text', 'name'=>'disabled_dates', 'label'=>__('Disabled Dates', 'woocommerce-extra-product-options-pro'), 'placeholder'=>'Separate dates with comma', 
			'hint_text'=>$hint_disabled_dates),

			'start_date'   => array('type'=>'text', 'name'=>'start_date', 'label'=>__('Start Date', 'woocommerce-extra-product-options-pro'), 'value'=>'', 'hint_text'=>$hint_date_range_start_date),
			'end_date'   => array('type'=>'text', 'name'=>'end_date', 'label'=>__('End Date', 'woocommerce-extra-product-options-pro'), 'value'=>'', 'hint_text'=>$hint_date_range_end_date),
			'min_year'   => array('type'=>'text', 'name'=>'min_year', 'label'=>__('Min.Year', 'woocommerce-extra-product-options-pro'), 'value'=>'', 'hint_text'=>$hint_date_range_min_year),
			'max_year'   => array('type'=>'text', 'name'=>'max_year', 'label'=>__('Max.Year', 'woocommerce-extra-product-options-pro'), 'value'=>'', 'hint_text'=>$hint_date_range_max_year),
			'enable_time_picker' => array('type'=>'checkbox', 'name'=>'enable_time_picker', 'label'=>__('Enable Time Picker', 'woocommerce-extra-product-options-pro'), 'value'=>'yes', 'checked'=>0,),
			'show_time_only' => array('type'=>'checkbox', 'name'=>'show_time_only', 'label'=>__('Show Time Picker Only', 'woocommerce-extra-product-options-pro'), 'hint_text'=> $hint_date_range_time_only, 'value'=>'yes', 'checked'=>0,),
			'show_single_datepicker' => array('type'=>'checkbox', 'name'=>'show_single_datepicker', 'label'=>__('Show Single Datepicker', 'woocommerce-extra-product-options-pro'), 'value'=>'yes', 'checked'=>0,),
			
			'min_time'    => array('type'=>'text', 'name'=>'min_time', 'label'=>__('Min. Time', 'woocommerce-extra-product-options-pro'), 'value'=>'12:00am', 'sub_label'=>'ex: 12:30am'),
			'max_time'    => array('type'=>'text', 'name'=>'max_time', 'label'=>__('Max. Time', 'woocommerce-extra-product-options-pro'), 'value'=>'11:30pm', 'sub_label'=>'ex: 11:30pm'),
			'start_time'  => array('type'=>'text', 'name'=>'start_time', 'label'=>__('Start Time', 'woocommerce-extra-product-options-pro'), 'value'=>'', 'sub_label'=>'ex: 2h 30m'),
			'time_step'   => array('type'=>'text', 'name'=>'time_step', 'label'=>__('Time Step', 'woocommerce-extra-product-options-pro'), 'value'=>'30', 'sub_label'=>'In minutes, ex: 30'),
			'time_format' => array('type'=>'select', 'name'=>'time_format', 'label'=>__('Time Format', 'woocommerce-extra-product-options-pro'), 'value'=>'h:i A', 'options'=>$time_formats),
			'linked_date' => array('type'=>'text', 'name'=>'linked_date', 'label'=>__('Linked Date', 'woocommerce-extra-product-options-pro')),

			'multiple_file'  => array('type'=>'checkbox', 'name'=>'multiple_file', 'label'=>__('Multiple file upload', 'woocommerce-extra-product-options-pro'), 'value'=>'yes', 'checked'=>0, 'onchange' => 'thwepoMultipleFileListener(this)'),
			'minfile'   => array('type'=>'text', 'name'=>'minfile', 'label'=>__('Min. Files', 'woocommerce-extra-product-options-pro'), 'hint_text'=>__('The minimum number of files to be uploaded', 'woocommerce-extra-product-options-pro')),
			'maxfile'   => array('type'=>'text', 'name'=>'maxfile', 'label'=>__('Max. Files', 'woocommerce-extra-product-options-pro'), 'hint_text'=>__('The maximum number of files to be uploaded', 'woocommerce-extra-product-options-pro')),

			'tooltip'	=> array('type'=>'text', 'name'=>'tooltip', 'label'=>__('Tooltip Text', 'woocommerce-extra-product-options-pro')),
			'tooltip_size'     => array('type'=>'text', 'name'=>'tooltip_size', 'label'=>__('Font Size', 'woocommerce-extra-product-options-pro'), 'min' => '1'),
			'tooltip_color'    => array('type'=>'colorpicker', 'name'=>'tooltip_color', 'label'=>__('Font Color', 'woocommerce-extra-product-options-pro'), 'value'=>'#fff'),
			'tooltip_bg_color'    => array('type'=>'colorpicker', 'name'=>'tooltip_bg_color', 'label'=>__('Background Color', 'woocommerce-extra-product-options-pro'), 'value'=>'#333'),
			'tooltip_border_color'    => array('type'=>'colorpicker', 'name'=>'tooltip_border_color', 'label'=>__('Border Color', 'woocommerce-extra-product-options-pro')),

			'colorpicker_style' => array('type'=>'select', 'name'=>'colorpicker_style', 'label'=>__('Colorpicker Style', 'woocommerce-extra-product-options-pro'), 'options'=>$colorpicker_styles, 'onchange' => 'thwepoColorpickerStyleChangeListner(this)'),
			'colorpicker_radius'  => array('type'=>'text', 'name'=>'colorpicker_radius', 'label'=>__('Border Radius', 'woocommerce-extra-product-options-pro')),
			'colorpreview_radius' => array('type'=>'text', 'name'=>'colorpreview_radius', 'label'=>__('Border Radius 2', 'woocommerce-extra-product-options-pro'), 'value' => 50),

			'input_mask'   => array('type'=>'text', 'name'=>'input_mask', 'label'=>__('Input Masking Pattern', 'woocommerce-extra-product-options-pro'), 'hint_text'=>__('Helps to ensure input to a predefined format like (999) 999-9999.', 'woocommerce-extra-product-options-pro')),
			'multiselection' => array('type'=>'checkbox', 'name'=>'multiselection', 'label'=>__('Allow multiple Selections', 'woocommerce-extra-product-options-pro'), 'value'=>'yes', 'checked'=>0, 'status'=>1, 'onchange' => 'thwepoMultiselectionListener(this)'),
			'display_vertically' => array('type'=>'checkbox', 'name'=>'display_vertically', 'label'=>__('Align options vertically', 'woocommerce-extra-product-options-pro'), 'value'=>'yes', 'checked'=>0, 'status'=>1),

			'option_text_position' => array('type'=>'select', 'name'=>'option_text_position', 'label'=>__('Option Text Position', 'woocommerce-extra-product-options-pro'), 'options'=>$option_text_position, 'value'=>'below'),
			'image_group_size' => array('type'=>'text', 'name'=>'image_group_size', 'label'=>__('Image Display Size', 'woocommerce-extra-product-options-pro'), 'hint_text'=>''),
			'image_group_radius' => array('type'=>'text', 'name'=>'image_group_radius', 'label'=>__('Image Border Radius', 'woocommerce-extra-product-options-pro'), 'hint_text'=>''),
			'enable_full_image_view' => array('type'=>'checkbox', 'name'=>'enable_full_image_view', 'label'=>__('Enable Full Image View in Lightbox', 'woocommerce-extra-product-options-pro'), 'value'=>'yes', 'checked'=>0, 'status'=>1),

			'view_password'  => array('type'=>'checkbox', 'name'=>'view_password', 'label'=>__('Show view password Icon', 'woocommerce-extra-product-options-pro'), 'value'=>'yes', 'checked'=>0, 'status'=>1),
			'option_size' => array('type'=>'text', 'name'=>'option_size', 'label'=>__('Color Palette Size', 'woocommerce-extra-product-options-pro'), 'hint_text'=>''),

			'separator_type' => array('type'=>'select', 'name'=>'separator_type', 'label'=>__('Separator Type', 'woocommerce-extra-product-options-pro'), 'options'=>$border_types, 'value'=>'dashed'),
			'separator_hight' => array('type'=>'text', 'name'=>'separator_hight', 'label'=>__('Height', 'woocommerce-extra-product-options-pro'), 'value' => '1px'),
		);
	}

	public function output_field_forms(){
		$this->output_field_form_pp();
		$this->output_form_fragments();
	}

	private function output_field_form_pp(){
		?>
        <div id="thwepo_field_form_pp" class="thpladmin-modal-mask">
          <?php $this->output_popup_form_fields(); ?>
        </div>
        <?php
	}

	/*****************************************/
	/********** POPUP FORM WIZARD ************/
	/*****************************************/
	private function output_popup_form_fields(){
		?>
		<div class="thpladmin-modal">
			<div class="modal-container">
				<span class="modal-close" onclick="thwepoCloseModal(this)">×</span>
				<div class="modal-content">
					<div class="modal-body">
						<div class="form-wizard wizard">
							<aside>
								<side-title class="wizard-title"><?php _e('Save Field', 'woocommerce-extra-product-options-pro'); ?></side-title>
								<ul class="pp_nav_links">
									<li class="text-primary active first pp-nav-link-basic" data-index="0">
										<i class="dashicons dashicons-admin-generic text-primary"></i><?php _e('Basic Info', 'woocommerce-extra-product-options-pro'); ?>
										<i class="i i-chevron-right dashicons dashicons-arrow-right-alt2"></i>
									</li>
									<li class="text-primary pp-nav-link-styles" data-index="1">
										<i class="dashicons dashicons-art text-primary"></i><?php _e('Display Styles', 'woocommerce-extra-product-options-pro'); ?>
										<i class="i i-chevron-right dashicons dashicons-arrow-right-alt2"></i>
									</li>
									<li class="text-primary pp-nav-link-tooltip" data-index="2">
										<i class="dashicons dashicons-admin-comments text-primary"></i><?php _e('Tooltip Details', 'woocommerce-extra-product-options-pro'); ?>
										<i class="i i-chevron-right dashicons dashicons-arrow-right-alt2"></i>
									</li>
									<li class="text-primary pp-nav-link-price" data-index="3">
										<i class="dashicons dashicons-cart text-primary"></i><?php _e('Price Details', 'woocommerce-extra-product-options-pro'); ?>
										<i class="i i-chevron-right dashicons dashicons-arrow-right-alt2"></i>
									</li>
									<li class="text-primary last pp-nav-link-rules" data-index="4">
										<i class="dashicons dashicons-filter text-primary"></i><?php _e('Display Rules', 'woocommerce-extra-product-options-pro'); ?>
										<i class="i i-chevron-right dashicons dashicons-arrow-right-alt2"></i>
									</li>
									<!--<li class="text-primary" data-index="5">
										<i class="dashicons dashicons-controls-repeat text-primary"></i>Repeat Rules
										<i class="i i-chevron-right dashicons dashicons-arrow-right-alt2"></i>
									</li>-->
								</ul>
							</aside>
							<main class="form-container main-full">
								<form method="post" id="thwepo_field_form" action="">
									<input type="hidden" name="f_action" value="" />
									<input type="hidden" name="i_name_old" value="" />
									<!--<input type="hidden" name="i_rowid" value="" />-->
					                <input type="hidden" name="i_original_type" value="" />

									<input type="hidden" name="i_options" value="" />
									<input type="hidden" name="i_rules" value="" />
									<input type="hidden" name="i_rules_ajax" value="" />

									<div class="data-panel data_panel_0">
										<?php $this->render_form_tab_general_info(); ?>
									</div>
									<div class="data-panel data_panel_1">
										<?php $this->render_form_tab_display_details(); ?>
									</div>
									<div class="data-panel data_panel_2">
										<?php $this->render_form_tab_tooltip_info(); ?>
									</div>
									<div class="data-panel data_panel_3">
										<?php $this->render_form_tab_price_info(); ?>
									</div>
									<div class="data-panel data_panel_4">
										<?php $this->render_form_tab_display_rules(); ?>
									</div>
									<!--<div class="data-panel data_panel_5">
										<?php //$this->render_form_tab_repeat_rules(); ?>
									</div>-->
									<?php wp_nonce_field( 'save_pro_field_property', 'save_pro_field_nonce' ); ?>
								</form>
							</main>
							<footer>
								<span class="Loader"></span>
								<div class="btn-toolbar">
									<button class="save-btn pull-right btn btn-primary" onclick="thwepoSaveField(this)">
										<span><?php _e('Save & Close', 'woocommerce-extra-product-options-pro'); ?></span>
									</button>
									<button class="next-btn pull-right btn btn-primary-alt" onclick="thwepoWizardNext(this)">
										<span><?php _e('Next', 'woocommerce-extra-product-options-pro'); ?></span><i class="i i-plus"></i>
									</button>
									<button class="prev-btn pull-right btn btn-primary-alt" onclick="thwepoWizardPrevious(this)">
										<span><?php _e('Back', 'woocommerce-extra-product-options-pro'); ?></span><i class="i i-plus"></i>
									</button>
								</div>
							</footer>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/*----- TAB - General Info -----*/
	private function render_form_tab_general_info(){
		$this->render_form_tab_main_title(__('Basic Details', 'woocommerce-extra-product-options-pro'));

		?>
		<div style="display: inherit;" class="data-panel-content">
			<?php
			$this->render_form_fragment_general();
			//$this->render_form_field_inputtext();
			?>
			<table class="thwepo_field_form_tab_general_placeholder thwepo_pp_table thwepo-general-info"></table>
		</div>
		<?php
	}

	/*----- TAB - Display Details -----*/
	private function render_form_tab_display_details(){
		$this->render_form_tab_main_title(__('Display Settings', 'woocommerce-extra-product-options-pro'));

		?>
		<div style="display: inherit;" class="data-panel-content mt-10">
			<table class="thwepo_pp_table compact thwepo-display-info">
				<?php
				$this->render_form_elm_row($this->field_props['cssclass']);
				$this->render_form_elm_row($this->field_props['input_class']);
				$this->render_form_elm_row($this->field_props['title_class']);
				$this->render_form_elm_row($this->field_props['subtitle_class']);

				$this->render_form_elm_row($this->field_props['title_position']);
				$this->render_form_elm_row($this->field_props['title_type']);
				$this->render_form_elm_row($this->field_props['title_color']);
				$this->render_form_elm_row($this->field_props['subtitle_type']);
				$this->render_form_elm_row($this->field_props['subtitle_color']);

				//$this->render_form_elm_row($this->field_props['colorpicker_style']);
				//$this->render_form_elm_row($this->field_props['colorpicker_radius']);
				//$this->render_form_elm_row($this->field_props['colorpreview_radius']);

				$this->render_form_elm_row_cb($this->field_props['hide_in_cart']);
				$this->render_form_elm_row_cb($this->field_props['hide_in_checkout']);
				$this->render_form_elm_row_cb($this->field_props['hide_in_order']);
				$this->render_form_elm_row_cb($this->field_props['hide_in_order_admin']);
				?>
			</table>
		</div>
		<?php
	}

	private function render_display_vertically_fields(){

		?>
		<table id="thwepo_display_field_vertically" class="thwepo_field_form_table" width="100%" style="display:none;">
			<?php 
			$this->render_form_elm_row($this->field_props['display_vertically']);
			?>
		</table>
		<?php 
	}

	private function render_color_palette_display_fields(){

		?>
		<table id="thwepo_display_field_colorpalette" class="thwepo_field_form_table" width="100%" style="display:none;">
			<?php 
			$this->render_form_elm_row($this->field_props['option_text_position']);
			$this->render_form_elm_row($this->field_props['colorpicker_radius']);
			$this->render_form_elm_row($this->field_props['option_size']);
			$this->render_form_elm_row($this->field_props['display_vertically']);
			?>
		</table>
		<?php 
	}

	private function render_image_group_display_fields(){

		?>
		<table id="thwepo_display_field_imagegroup" class="thwepo_field_form_table" width="100%" style="display:none;">
			<?php 
			$this->render_form_elm_row($this->field_props['option_text_position']);
			$this->render_form_elm_row($this->field_props['image_group_radius']);
			$this->render_form_elm_row($this->field_props['image_group_size']);
			$this->render_form_elm_row($this->field_props['display_vertically']);
			$this->render_form_elm_row($this->field_props['enable_full_image_view']);
			?>
		</table>
		<?php 
	}

	/*----- TAB - Tooltip Info -----*/
	private function render_form_tab_tooltip_info(){
		$this->render_form_tab_main_title(__('Tooltip Details', 'woocommerce-extra-product-options-pro'));

		?>
		<div style="display: inherit;" class="data-panel-content">
			<table class="thwepo_pp_table thwepo-tooltip-info">
				<?php
				$this->render_form_elm_row($this->field_props['tooltip']);
				$this->render_form_elm_row($this->field_props['tooltip_size']);
				$this->render_form_elm_row_cp($this->field_props['tooltip_color']);
				$this->render_form_elm_row_cp($this->field_props['tooltip_bg_color']);
				//$this->render_form_elm_row_cp($this->field_props['tooltip_border_color']);
				?>
			</table>
		</div>
		<?php
	}

	/*----- TAB - Price Info -----*/
	private function render_form_tab_price_info(){
		$this->render_form_tab_main_title(__('Price Details', 'woocommerce-extra-product-options-pro'));

		$price_type_props = $this->field_props['price_type'];
		$options = isset($price_type_props['options']) ? $price_type_props['options'] : array();
		
		/*if($type === 'datepicker' || $type === 'timepicker' || $type === 'checkbox' || $type === 'file'){
			unset($options['custom']);
			unset($options['dynamic']);
			unset($options['dynamic-excl-base-price']);
		}*/
		
		$price_type_props['options'] = $options;

		?>
		<div style="display: inherit;" class="data-panel-content">
			<table class="thwepo_pp_table thwepo-price-info">
				<tr class="form_field_price_type">
					<?php $this->render_form_field_element($price_type_props, $this->cell_props); ?>
		        </tr>
		        <tr class="form_field_price">
		            <td class="label"><?php THWEPO_i18n::_et('Price'); ?></td>
		            <?php $this->render_form_fragment_tooltip(false); ?>
		            <td class="field">
		            	<input type="text" name="i_price" placeholder="Price" style="width:260px;" class="thpladmin-price-field"/>
		                <label class="thpladmin-dynamic-price-field" style="display:none">per</label>
		                <input type="text" name="i_price_unit" placeholder="Unit" style="width:80px; display:none" class="thpladmin-dynamic-price-field"/>
		                <label class="thpladmin-dynamic-price-field thpladmin-price-unit-label" style="display:none">unit</label>
		            </td>
				</tr> 
				<tr style="display:none" class="thpladmin-dynamic-price-field">        
		            <?php          
		        	$this->render_form_field_element($this->field_props['price_min_unit'], $this->cell_props);
					?> 
				</tr>
				<?php
				$this->render_form_elm_row_cb($this->field_props['show_price_label']);
				$this->render_form_elm_row_cb($this->field_props['show_price_in_order']);
				$this->render_form_elm_row_cb($this->field_props['price_flat_fee']);
				//$this->render_form_elm_row_cb($this->field_props['show_price_table']);
				?>
			</table>
		</div>
		<?php
	}

	/*----- TAB - Display Rules -----*/
	private function render_form_tab_display_rules(){
		$this->render_form_tab_main_title(__('Display Rules', 'woocommerce-extra-product-options-pro'));

		?>
		<div style="display: inherit;" class="data-panel-content">
			<table class="thwepo_pp_table thwepo-display-rules">
				<?php
				$this->render_form_fragment_rules(); 
				$this->render_form_fragment_rules_ajax();
				?>
			</table>
		</div>
		<?php
	}

	/*----- TAB - Repeat Rules -----*/
	/*private function render_form_tab_repeat_rules(){
		$this->render_form_tab_main_title('Repeat Rules');

		?>
		<div style="display: inherit;" class="data-panel-content">
			<?php
			?>
		</div>
		<?php
	}*/

	/*-------------------------------*/
	/*------ Form Field Groups ------*/
	/*-------------------------------*/
	private function render_form_fragment_general($input_field = true){
		$field_types = $this->get_field_types();
		
		$field_name_label = $input_field ? THWEPO_i18n::__t('Name') : THWEPO_i18n::__t('ID');
		?>
		<div class="err_msgs"></div>
        <table class="thwepo_pp_table">
        	<?php
			$this->render_form_elm_row($this->field_props['type']);
			$this->render_form_elm_row($this->field_props['name']);
			?>
        </table>  
        <?php
	}

	private function output_form_fragments(){
		$this->render_form_field_inputtext();
		$this->render_form_field_hidden();
		$this->render_form_field_password();
		$this->render_form_field_number();
		$this->render_form_field_tel();	
		$this->render_form_field_email();
		$this->render_form_field_url();
		$this->render_form_field_range();
		$this->render_form_field_textarea();
		$this->render_form_field_select();
		$this->render_form_field_multiselect();		
		$this->render_form_field_radio();
		$this->render_form_field_checkbox();
		$this->render_form_field_checkboxgroup();
		$this->render_form_field_datepicker();
		$this->render_form_field_daterangepicker();
		$this->render_form_field_timepicker();
		$this->render_form_field_file();		
		$this->render_form_field_heading();
		$this->render_form_field_html();
		$this->render_form_field_label();
		$this->render_form_field_default();
		$this->render_form_field_colorpicker();
		$this->render_form_field_colorpalette();
		$this->render_form_field_imagegroup();
		$this->render_form_field_switch();
		$this->render_form_field_separator();

		$this->render_color_palette_display_fields();
		$this->render_image_group_display_fields();
		$this->render_display_vertically_fields();
		
		$this->render_field_form_fragment_product_list();
		$this->render_field_form_fragment_category_list();
		$this->render_field_form_fragment_tag_list();
		$this->render_field_form_fragment_user_role_list();
		$this->render_field_form_fragment_fields_wrapper();
	}

	private function render_form_field_inputtext(){
		?>
        <table id="thwepo_field_form_id_inputtext" class="thwepo_pp_table" style="display:none;">
        	<?php
			$this->render_form_elm_row($this->field_props['title']);
			$this->render_form_elm_row($this->field_props['subtitle']);
			$this->render_form_elm_row($this->field_props['value']);
			$this->render_form_elm_row($this->field_props['placeholder']);
			$this->render_form_elm_row($this->field_props['minlength']);
			$this->render_form_elm_row($this->field_props['maxlength']);
			$this->render_form_elm_row($this->field_props['validate']);
			$this->render_form_elm_row($this->field_props['input_mask']);

			$this->render_form_elm_row_cb($this->field_props['required']);
			$this->render_form_elm_row_cb($this->field_props['enabled']);
			?>
        </table>
        <?php   
	}

	private function render_form_field_hidden(){
		?>
        <table id="thwepo_field_form_id_hidden" class="thwepo_field_form_table" width="100%" style="display:none;">
			<?php
			$this->render_form_elm_row($this->field_props['title']);
			$this->render_form_elm_row($this->field_props['value']);

			$this->render_form_elm_row_cb($this->field_props['required']);
			$this->render_form_elm_row_cb($this->field_props['enabled']);
			?>  
        </table>
        <?php   
	}
	
	private function render_form_field_password(){
		?>
        <table id="thwepo_field_form_id_password" class="thwepo_field_form_table" width="100%" style="display:none;">
            <?php
			$this->render_form_elm_row($this->field_props['title']);
			$this->render_form_elm_row($this->field_props['subtitle']);
			$this->render_form_elm_row($this->field_props['placeholder']);
			$this->render_form_elm_row($this->field_props['maxlength']);
			$this->render_form_elm_row($this->field_props['validate']);

			$this->render_form_elm_row_cb($this->field_props['view_password']);
			$this->render_form_elm_row_cb($this->field_props['required']);
			$this->render_form_elm_row_cb($this->field_props['enabled']);
			?>  
        </table>
        <?php   
	}

	private function render_form_field_number(){
		$min_attribute = $this->field_props['minlength'];
        $min_attribute['label'] = __('Min. Value', 'woocommerce-extra-product-options-pro');
		$min_attribute['hint_text'] = __('The minimum value allowed', 'woocommerce-extra-product-options-pro');

        $max_attribute = $this->field_props['maxlength'];
        $max_attribute['label'] = __('Max. Value', 'woocommerce-extra-product-options-pro');
		$max_attribute['hint_text'] = __('The maximum value allowed', 'woocommerce-extra-product-options-pro');

		?>
        <table id="thwepo_field_form_id_number" class="thwepo_field_form_table" width="100%" style="display:none;">
            <?php
			$this->render_form_elm_row($this->field_props['title']);
			$this->render_form_elm_row($this->field_props['subtitle']);
			$this->render_form_elm_row($this->field_props['value']);
			$this->render_form_elm_row($this->field_props['placeholder']);
			$this->render_form_elm_row($min_attribute);
			$this->render_form_elm_row($max_attribute);
			$this->render_form_elm_row($this->field_props['step']);

			$this->render_form_elm_row_cb($this->field_props['required']);
			$this->render_form_elm_row_cb($this->field_props['enabled']);
			?>     
        </table>
        <?php   
	}

	private function render_form_field_tel(){
		?>
        <table id="thwepo_field_form_id_tel" class="thwepo_field_form_table" width="100%" style="display:none;">
            <?php
			$this->render_form_elm_row($this->field_props['title']);
			$this->render_form_elm_row($this->field_props['subtitle']);
			$this->render_form_elm_row($this->field_props['value']);
			$this->render_form_elm_row($this->field_props['placeholder']);
			$this->render_form_elm_row($this->field_props['maxlength']);
			$this->render_form_elm_row($this->field_props['validate']);

			$this->render_form_elm_row_cb($this->field_props['required']);
			$this->render_form_elm_row_cb($this->field_props['enabled']);
			?>    
        </table>
        <?php   
	}

	private function render_form_field_email(){
		?>
        <table id="thwepo_field_form_id_email" class="thwepo_field_form_table" width="100%" style="display:none;">
            <?php
			$this->render_form_elm_row($this->field_props['title']);
			$this->render_form_elm_row($this->field_props['subtitle']);
			$this->render_form_elm_row($this->field_props['value']);
			$this->render_form_elm_row($this->field_props['placeholder']);
			// $this->render_form_elm_row($this->field_props['maxlength']);
			$this->render_form_elm_row($this->field_props['validate']);

			$this->render_form_elm_row_cb($this->field_props['required']);
			$this->render_form_elm_row_cb($this->field_props['enabled']);
			?>    
        </table>
        <?php   
	}

	private function render_form_field_url(){
		?>
        <table id="thwepo_field_form_id_url" class="thwepo_field_form_table" width="100%" style="display:none;">
            <?php
			$this->render_form_elm_row($this->field_props['title']);
			$this->render_form_elm_row($this->field_props['subtitle']);
			$this->render_form_elm_row($this->field_props['value']);
			$this->render_form_elm_row($this->field_props['placeholder']);

			$this->render_form_elm_row_cb($this->field_props['required']);
			$this->render_form_elm_row_cb($this->field_props['enabled']);
			?>    
        </table>
        <?php   
	}

	private function render_form_field_range(){
		$min_attribute = $this->field_props['minlength'];
        $min_attribute['label'] = __('Min. Value', 'woocommerce-extra-product-options-pro');
		$min_attribute['hint_text'] = __('The minimum value of slider', 'woocommerce-extra-product-options-pro');

        $max_attribute = $this->field_props['maxlength'];
        $max_attribute['label'] = __('Max. Value', 'woocommerce-extra-product-options-pro');
		$max_attribute['hint_text'] = __('The maximum value of slider', 'woocommerce-extra-product-options-pro');

		?>
        <table id="thwepo_field_form_id_range" class="thwepo_field_form_table" width="100%" style="display:none;">
            <?php
			$this->render_form_elm_row($this->field_props['title']);
			$this->render_form_elm_row($this->field_props['subtitle']);
			$this->render_form_elm_row($this->field_props['value']);
			$this->render_form_elm_row($min_attribute);
			$this->render_form_elm_row($max_attribute);
			$this->render_form_elm_row($this->field_props['step']);

			$this->render_form_elm_row_cb($this->field_props['required']);
			$this->render_form_elm_row_cb($this->field_props['enabled']);
			?>     
        </table>
        <?php   
	}
	
	private function render_form_field_textarea(){
		?>
        <table id="thwepo_field_form_id_textarea" class="thwepo_field_form_table" width="100%" style="display:none;">
            <?php
			$this->render_form_elm_row($this->field_props['title']);
			$this->render_form_elm_row($this->field_props['subtitle']);
			$this->render_form_elm_row($this->field_props['value']);
			$this->render_form_elm_row($this->field_props['placeholder']);
			$this->render_form_elm_row($this->field_props['minlength']);
			$this->render_form_elm_row($this->field_props['maxlength']);
			$this->render_form_elm_row($this->field_props['cols']);
			$this->render_form_elm_row($this->field_props['rows']);

			$this->render_form_elm_row_cb($this->field_props['required']);
			$this->render_form_elm_row_cb($this->field_props['enabled']);
			?>     
        </table>
        <?php   
	}
	
	private function render_form_field_select(){
		?>
        <table id="thwepo_field_form_id_select" class="thwepo_field_form_table" width="100%" style="display:none;">
            <?php
			$this->render_form_elm_row($this->field_props['title']);
			$this->render_form_elm_row($this->field_props['subtitle']);
			$this->render_form_elm_row($this->field_props['value']);
			$this->render_form_elm_row($this->field_props['placeholder']);

			$this->render_form_elm_row_cb($this->field_props['required']);
			$this->render_form_elm_row_cb($this->field_props['enabled']);

			$this->render_form_fragment_h_spacing();
			$this->render_form_fragment_options('select');
			?>
        </table>
        <?php   
	}
	
	private function render_form_field_multiselect(){
		$field_props_minlength = $this->field_props['minlength'];
        $field_props_minlength['label'] = __('Min. Selections', 'woocommerce-extra-product-options-pro');
		$field_props_minlength['hint_text'] = __('The minimum number of options required to select', 'woocommerce-extra-product-options-pro');

		$field_props_maxlength = $this->field_props['maxlength'];
		$field_props_maxlength['label'] = __('Max. Selections', 'woocommerce-extra-product-options-pro');
		$field_props_maxlength['hint_text'] = __('The maximum number of options that can be selected', 'woocommerce-extra-product-options-pro');
		?>
        <table id="thwepo_field_form_id_multiselect" class="thwepo_field_form_table" width="100%" style="display:none;">
            <?php
			$this->render_form_elm_row($this->field_props['title']);
			$this->render_form_elm_row($this->field_props['subtitle']);
			$this->render_form_elm_row($this->field_props['value']);
			$this->render_form_elm_row($this->field_props['placeholder']);

			$this->render_form_elm_row($field_props_minlength);
			$this->render_form_elm_row($field_props_maxlength);

			$this->render_form_elm_row_cb($this->field_props['required']);
			$this->render_form_elm_row_cb($this->field_props['enabled']);

			$this->render_form_fragment_h_spacing();
			$this->render_form_fragment_options('multiselect');
			?> 
        </table>
        <?php   
	}
	
	private function render_form_field_radio(){
		?>
        <table id="thwepo_field_form_id_radio" class="thwepo_field_form_table" width="100%" style="display:none;">
            <?php
			$this->render_form_elm_row($this->field_props['title']);
			$this->render_form_elm_row($this->field_props['subtitle']);
			$this->render_form_elm_row($this->field_props['value']);

			$this->render_form_elm_row_cb($this->field_props['required']);
			$this->render_form_elm_row_cb($this->field_props['enabled']);

			$this->render_form_fragment_h_spacing();
			$this->render_form_fragment_options('radio');
			?>
        </table>
        <?php   
	}
	
	private function render_form_field_checkbox(){
		$value_props = $this->field_props['value'];
		$value_props['label'] = __('Value', 'woocommerce-extra-product-options-pro');

		?>
        <table id="thwepo_field_form_id_checkbox" class="thwepo_field_form_table" width="100%" style="display:none;">
            <?php
			$this->render_form_elm_row($this->field_props['title']);
			$this->render_form_elm_row($this->field_props['subtitle']);
			$this->render_form_elm_row($value_props);

			$this->render_form_elm_row_cb($this->field_props['checked']);
			$this->render_form_elm_row_cb($this->field_props['required']);
			$this->render_form_elm_row_cb($this->field_props['enabled']);

			//$this->render_form_fragment_h_spacing();
			// $this->render_form_fragment_options();
			?>  
        </table>
        <?php   
	}
	
	private function render_form_field_checkboxgroup(){
		$min_checked = $this->field_props['minlength'];
        $min_checked['label'] = __('Min. Selection', 'woocommerce-extra-product-options-pro');
		$min_checked['hint_text'] = __('The minimum checked item', 'woocommerce-extra-product-options-pro');

        $max_checked = $this->field_props['maxlength'];
        $max_checked['label'] = __('Max. Selection', 'woocommerce-extra-product-options-pro');
		$max_checked['hint_text'] = __('The maximum checked item', 'woocommerce-extra-product-options-pro');

		?>
        <table id="thwepo_field_form_id_checkboxgroup" class="thwepo_field_form_table" width="100%" style="display:none;">
            <?php
			$this->render_form_elm_row($this->field_props['title']);
			$this->render_form_elm_row($this->field_props['subtitle']);
			$this->render_form_elm_row($this->field_props['value']);

			$this->render_form_elm_row($min_checked);
			$this->render_form_elm_row($max_checked);

			$this->render_form_elm_row_cb($this->field_props['required']);
			$this->render_form_elm_row_cb($this->field_props['enabled']);

			$this->render_form_fragment_h_spacing();
			$this->render_form_fragment_options('checkboxgroup');
			?>
        </table>
        <?php   
	}
	
	private function render_form_field_datepicker(){
		?>
        <table id="thwepo_field_form_id_datepicker" class="thwepo_field_form_table" width="100%" style="display:none;">
            <?php
			$this->render_form_elm_row($this->field_props['title']);
			$this->render_form_elm_row($this->field_props['subtitle']);
			$this->render_form_elm_row($this->field_props['placeholder']);

			$this->render_form_elm_row($this->field_props['date_format']);
			$this->render_form_elm_row($this->field_props['default_date']);
			$this->render_form_elm_row($this->field_props['min_date']);
			$this->render_form_elm_row($this->field_props['max_date']);
			$this->render_form_elm_row($this->field_props['year_range']);
			$this->render_form_elm_row($this->field_props['number_of_months']);
			$this->render_form_elm_row($this->field_props['disabled_days']);
			$this->render_form_elm_row($this->field_props['disabled_dates']);

			$this->render_form_elm_row_cb($this->field_props['required']);
			$this->render_form_elm_row_cb($this->field_props['enabled']);
			$this->render_form_elm_row_cb($this->field_props['readonly']);
			?> 
        </table>
        <?php   
	}

	private function render_form_field_daterangepicker(){
		$date_format = $this->field_props['date_format'];
		$date_format['value'] = 'DD/MM/YYYY';
		$date_format['hint_text'] = __("The format for parsed and displayed dates. Eg: DD/MM/YYYY. Use 'hh:mm A' to get the time picker value. eg: 'DD/MM/YYYY hh:mm A'. If you enable the 'Show Time picker only' option, then you will need to add hh:mm A as a format.", 'woocommerce-extra-product-options-pro');

		$min_date = $this->field_props['min_date'];
		$min_date['hint_text'] = __("The minimum selectable date. Specify a date in configured format.", 'woocommerce-extra-product-options-pro');

		$max_date = $this->field_props['max_date'];
		$max_date['hint_text'] = __("The maximum selectable date. Specify a date in configured format.", 'woocommerce-extra-product-options-pro');
		?>
        <table id="thwepo_field_form_id_daterangepicker" class="thwepo_field_form_table" width="100%" style="display:none;">
            <?php
			$this->render_form_elm_row($this->field_props['title']);
			$this->render_form_elm_row($this->field_props['subtitle']);
			$this->render_form_elm_row($this->field_props['placeholder']);

			$this->render_form_elm_row($date_format);
			$this->render_form_elm_row($this->field_props['start_date']);
			$this->render_form_elm_row($this->field_props['end_date']);
			$this->render_form_elm_row($min_date);
			$this->render_form_elm_row($max_date);
			$this->render_form_elm_row($this->field_props['min_year']);
			$this->render_form_elm_row($this->field_props['max_year']);

			// $this->render_form_elm_row($this->field_props['number_of_months']);
			// $this->render_form_elm_row($this->field_props['disabled_days']);
			// $this->render_form_elm_row($this->field_props['disabled_dates']);

			$this->render_form_elm_row_cb($this->field_props['enable_time_picker']);
			$this->render_form_elm_row_cb($this->field_props['show_time_only']);
			$this->render_form_elm_row_cb($this->field_props['show_single_datepicker']);

			$this->render_form_elm_row_cb($this->field_props['required']);
			$this->render_form_elm_row_cb($this->field_props['enabled']);
			$this->render_form_elm_row_cb($this->field_props['readonly']);
			?> 
        </table>
        <?php   
	}
	
	private function render_form_field_timepicker(){
		?>
        <table id="thwepo_field_form_id_timepicker" class="thwepo_field_form_table" width="100%" style="display:none;">
            <?php
			$this->render_form_elm_row($this->field_props['title']);
			$this->render_form_elm_row($this->field_props['subtitle']);
			$this->render_form_elm_row($this->field_props['value']);
			$this->render_form_elm_row($this->field_props['placeholder']);

			$this->render_form_elm_row($this->field_props['min_time']);
			$this->render_form_elm_row($this->field_props['max_time']);
			$this->render_form_elm_row($this->field_props['time_step']);
			$this->render_form_elm_row($this->field_props['time_format']);

			$this->render_form_elm_row_cb($this->field_props['required']);
			$this->render_form_elm_row_cb($this->field_props['enabled']);
			?>
        </table>
        <?php   
	}

	private function render_form_field_colorpicker(){
		$colorpicker_style = isset( $this->field_props['colorpicker_style']['value'] ) && $this->field_props['colorpicker_style']['value'] == 'style2' ? "table-row" : "none";

		$color = $this->field_props['value'];
    	$color['type'] = 'colorpicker';
		?>
        <table id="thwepo_field_form_id_colorpicker" class="thwepo_field_form_table" width="100%" style="display:none;">
			<?php
			$this->render_form_elm_row($this->field_props['title']);
			$this->render_form_elm_row($this->field_props['subtitle']);
			$this->render_form_elm_row($color);
			//$this->render_form_elm_row($this->field_props['tooltip']);

			$this->render_form_elm_row_cb($this->field_props['required']);
			$this->render_form_elm_row_cb($this->field_props['enabled']);
			?>
        </table>
        <?php   
	}

	private function render_form_field_colorpalette(){
		$field_props_minlength = $this->field_props['minlength'];
        $field_props_minlength['label'] = 'Min. Selections';
		$field_props_minlength['hint_text'] = 'The minimum number of pallets required to select';

		$field_props_maxlength = $this->field_props['maxlength'];
		$field_props_maxlength['label'] = 'Max. Selections';
		$field_props_maxlength['hint_text'] = 'The maximum number of pallets that can be selected';

		?>
        <table id="thwepo_field_form_id_colorpalette" class="thwepo_field_form_table" width="100%" style="display:none;">
			<?php
			$this->render_form_elm_row($this->field_props['title']);
			$this->render_form_elm_row($this->field_props['subtitle']);
			$this->render_form_elm_row($this->field_props['value']);

			$this->render_form_elm_row($field_props_minlength);
			$this->render_form_elm_row($field_props_maxlength);

			$this->render_form_elm_row_cb($this->field_props['required']);
			$this->render_form_elm_row_cb($this->field_props['enabled']);
			$this->render_form_elm_row_cb($this->field_props['multiselection']);

			$this->render_form_fragment_h_spacing();
			$this->render_form_fragment_options('colorpalette');
			?>
        </table>
        <?php   
	}

	private function render_form_field_imagegroup(){
		$field_props_minlength = $this->field_props['minlength'];
        $field_props_minlength['label'] = __('Min. Selections', 'woocommerce-extra-product-options-pro');
		$field_props_minlength['hint_text'] = __('The minimum number of Image required to select', 'woocommerce-extra-product-options-pro');

		$field_props_maxlength = $this->field_props['maxlength'];
		$field_props_maxlength['label'] = __('Max. Selections', 'woocommerce-extra-product-options-pro');
		$field_props_maxlength['hint_text'] = __('The maximum number of Image that can be selected', 'woocommerce-extra-product-options-pro');

		?>
        <table id="thwepo_field_form_id_imagegroup" class="thwepo_field_form_table" width="100%" style="display:none;">
			<?php
			$this->render_form_elm_row($this->field_props['title']);
			$this->render_form_elm_row($this->field_props['subtitle']);
			$this->render_form_elm_row($this->field_props['value']);

			$this->render_form_elm_row($field_props_minlength);
			$this->render_form_elm_row($field_props_maxlength);

			$this->render_form_elm_row_cb($this->field_props['required']);
			$this->render_form_elm_row_cb($this->field_props['enabled']);
			$this->render_form_elm_row_cb($this->field_props['multiselection']);

			$this->render_form_fragment_h_spacing();
			$this->render_form_fragment_options('imagegroup');
			?>
        </table>
        <?php   
	}
	
	private function render_form_field_file(){
		?>
        <table id="thwepo_field_form_id_file" class="thwepo_field_form_table" width="100%" style="display:none;">
			<?php
			$this->render_form_elm_row($this->field_props['title']);
			$this->render_form_elm_row($this->field_props['subtitle']);
			$this->render_form_elm_row($this->field_props['maxsize']);
			$this->render_form_elm_row($this->field_props['accept']);
			$this->render_form_elm_row($this->field_props['minfile']);
			$this->render_form_elm_row($this->field_props['maxfile']);

			$this->render_form_elm_row_cb($this->field_props['multiple_file']);
			$this->render_form_elm_row_cb($this->field_props['required']);
			$this->render_form_elm_row_cb($this->field_props['enabled']);
			?>
        </table>
        <?php   
	}
	
	private function render_form_field_heading(){
		$title_props = $this->field_props['title'];
		$title_props['required'] = true;

		?>
        <table id="thwepo_field_form_id_heading" class="thwepo_field_form_table" width="100%" style="display:none;">
			<?php
			$this->render_form_elm_row($title_props);
			$this->render_form_elm_row($this->field_props['title_type']);
			$this->render_form_elm_row_cp($this->field_props['title_color']);
			$this->render_form_elm_row($this->field_props['title_class']);
			$this->render_form_elm_row($this->field_props['subtitle']);
			$this->render_form_elm_row($this->field_props['subtitle_type']);
			$this->render_form_elm_row_cp($this->field_props['subtitle_color']);
			$this->render_form_elm_row($this->field_props['subtitle_class']);

			$this->render_form_elm_row_cb($this->field_props['enabled']);
			?>
        </table>
        <?php   
	}

	private function render_form_field_html(){
		$content_props = $this->field_props['value'];
		$content_props['type']     = 'textarea';
		$content_props['label']    = __('Content', 'woocommerce-extra-product-options-pro');
		$content_props['required'] = true;
		?>
        <table id="thwepo_field_form_id_html" class="thwepo_field_form_table" width="100%" style="display:none;">
			<?php
			$this->render_form_elm_row_ta($content_props);
			$this->render_form_elm_row($this->field_props['cssclass']);

			$this->render_form_elm_row_cb($this->field_props['enabled']);
			?>     
        </table>
        <?php   
	}
	
	private function render_form_field_label(){
		$title_props = $this->field_props['title'];
		$title_props['type']     = 'textarea';
		$title_props['label']    = __('Content', 'woocommerce-extra-product-options-pro');
		$title_props['required'] = true;

		$title_type_props = $this->field_props['title_type'];
		$title_type_props['label'] = __('Tag Type', 'woocommerce-extra-product-options-pro');

		$title_color_props = $this->field_props['title_color'];
		$title_color_props['label'] = __('Content Color', 'woocommerce-extra-product-options-pro');

		$title_class_props = $this->field_props['title_class'];
		$title_class_props['label'] = __('Wrapper Class', 'woocommerce-extra-product-options-pro');
		?>
        <table id="thwepo_field_form_id_label" class="thwepo_field_form_table" width="100%" style="display:none;">
			<?php
			$this->render_form_elm_row_ta($title_props);
			$this->render_form_elm_row($title_type_props);
			$this->render_form_elm_row_cp($title_color_props);
			$this->render_form_elm_row($title_class_props);

			$this->render_form_elm_row_cb($this->field_props['enabled']);
			?>     
        </table>
        <?php   
	}
	
	private function render_form_field_default(){
		?>
        <table id="thwepo_field_form_id_default" class="thwepo_field_form_table" width="100%" style="display:none;">
            <?php
			$this->render_form_elm_row($this->field_props['title']);
			$this->render_form_elm_row($this->field_props['subtitle']);
			$this->render_form_elm_row($this->field_props['value']);
			$this->render_form_elm_row($this->field_props['placeholder']);
			$this->render_form_elm_row($this->field_props['maxlength']);
			$this->render_form_elm_row($this->field_props['validate']);

			$this->render_form_elm_row_cb($this->field_props['required']);
			$this->render_form_elm_row_cb($this->field_props['enabled']);
			?>    
        </table>
        <?php   
	}

	private function render_form_field_switch(){
		$value_props = $this->field_props['value'];
		$value_props['label'] = __('Value', 'woocommerce-extra-product-options-pro');

		?>
        <table id="thwepo_field_form_id_switch" class="thwepo_field_form_table" width="100%" style="display:none;">
            <?php
			$this->render_form_elm_row($this->field_props['title']);
			$this->render_form_elm_row($this->field_props['subtitle']);
			$this->render_form_elm_row($value_props);

			$this->render_form_elm_row_cb($this->field_props['checked']);
			$this->render_form_elm_row_cb($this->field_props['required']);
			$this->render_form_elm_row_cb($this->field_props['enabled']);

			?>  
        </table>
        <?php   
	}

	private function render_form_field_separator(){
		?>
        <table id="thwepo_field_form_id_separator" class="thwepo_pp_table" style="display:none;">
        	<?php
        	$this->render_form_elm_row($this->field_props['cssclass']);
        	$this->render_form_elm_row($this->field_props['separator_hight']);
        	$this->render_form_elm_row($this->field_props['separator_type']);
			$this->render_form_elm_row_cb($this->field_props['enabled']);
			?>
        </table>
        <?php   
	}

	private function render_form_fragment_options($type = false){
		?>
		<tr>
			<td class="sub-title"><?php THWEPO_i18n::_et('Options'); ?></td>
			<?php $this->render_form_fragment_tooltip(); ?>
			<td></td>
		</tr>
		<tr>
			<td colspan="3" class="p-0">
				<table border="0" cellpadding="0" cellspacing="0" class="thwepo-option-list thpladmin-options-table"><tbody>
					<tr class="thwepo-option thwepo-option-<?php echo $type; ?>">
						<?php 
						if($type === 'imagegroup'){
							?>
							<td class="thwepoadmin-option-image" width="100px">
								<div class="thwadmin-option-item">
									<input type="hidden" class="thwepo-image-input" name="i_options_image[]" value="">
									<img  class="thwepo-upload-preview" src="<?php echo THWEPO_ASSETS_URL_ADMIN . '/images/placeholder.svg'; ?>"  alt="option-image" />
									<button type="button" class="thwepo-upload-button" onclick="thwepoUploadImage(this,event)"> 
                   	<img class="thwepo-upload-icon" src="<?php echo THWEPO_ASSETS_URL_ADMIN .'/images/upload.svg' ?>" alt="upload">
                  </button>
                </div>
							</td>
							<?php
						}
						?>
						<td class="key"><input type="text" name="i_options_key[]" placeholder="Option Value"></td>
						<td class="value"><input type="text" name="i_options_text[]" placeholder="Option Text"></td>
						
						<?php 
						if($type === 'colorpalette'){
							?>
							<td class="option-color"><input type="color" name="i_options_color[]" placeholder="Option Color"></td>
							<?php 
						}
						?>

						<td class="price"><input type="text" name="i_options_price[]" placeholder="Price"></td>
						<td class="price-type">    
							<select name="i_options_price_type[]">
								<option selected="selected" value=""><?php _e('Fixed', 'woocommerce-extra-product-options-pro'); ?></option>
								<option value="percentage"><?php _e('Percentage', 'woocommerce-extra-product-options-pro'); ?></option>
							</select>
						</td>
						<td class="action-cell">
							<a href="javascript:void(0)" onclick="thwepoAddNewOptionRow(this)" class="btn btn-tiny btn-primary" title="Add new option">+</a><a href="javascript:void(0)" onclick="thwepoRemoveOptionRow(this)" class="btn btn-tiny btn-danger" title="Remove option">x</a><span class="btn btn-tiny sort ui-sortable-handle"></span>
						</td>
					</tr>
				</tbody></table>            	
			</td>
		</tr>
        <?php
	}
}

endif;