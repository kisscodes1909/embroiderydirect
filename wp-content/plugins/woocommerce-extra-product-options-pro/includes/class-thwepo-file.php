<?php
/**
 * 
 *
 * @link       https://themehigh.com
 * @since      3.0.0
 *
 * @package    woocommerce-extra-product-options-pro
 * @subpackage woocommerce-extra-product-options-pro/includes
 */
if(!defined('WPINC')){	die; }

if(!class_exists('THWEPO_File')):

class THWEPO_File {
	protected static $_instance = null;

	public function __construct() {
		
	}

	public static function instance() {
		if(is_null(self::$_instance)){
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function define_hooks(){
		/*
    	add_action('wp_ajax_thwepo_file_upload', array($this, 'ajax_file_upload'));
		add_action('wp_ajax_nopriv_thwepo_file_upload', array($this, 'ajax_file_upload'));

		add_action('wp_ajax_thwepo_remove_uploaded', array($this, 'ajax_remove_uploaded'));
		add_action('wp_ajax_nopriv_thwepo_remove_uploaded', array($this, 'ajax_remove_uploaded'));
		*/
	}

	public function validate_file_count( $names, $field ){
		$file_count = count( $names );
		$min_count = $field->get_property('minfile');
		$max_count = $field->get_property('maxfile');
		$passed = true;
		$title = $field->get_property('title');
		if( empty( $min_count ) && empty( $max_count ) ){
			return true;
		}

		if( ( $min_count === $max_count ) && ($min_count !== $file_count ) ){
			/* translators: %d: file count */
			THWEPO_Utils::add_error('<strong>'.$title.': </strong> '. sprintf(__('%d files need to be uploaded.', 'woocommerce-extra-product-options-pro'), $min_count ) );
			$passed = false;

		}else if( $file_count < $min_count ){
			/* translators: %d: minimum file count */
			THWEPO_Utils::add_error('<strong>'.$title.': </strong> '. sprintf(__('Minimum of %d files need to be uploaded.', 'woocommerce-extra-product-options-pro'), $min_count ) );
			$passed = false;

		}else if( $max_count && $file_count > $max_count ){
			/* translators: %d: maximum file count */
			THWEPO_Utils::add_error('<strong>'.$title.': </strong> '. sprintf(__('Maximum of %d files need to be uploaded.', 'woocommerce-extra-product-options-pro'), $max_count ) );
			$passed = false;
		}
		return $passed;
	}

	public function validate_file($passed, $field, $file){
		if($field->get_property('required') && !$file) {
			/* translators: %s: file upload field name */
			THWEPO_Utils::add_error( sprintf(__('Please select a file for %s.', 'woocommerce-extra-product-options-pro'), $field->get_property('title')) );
			$passed = false;
		}
		$title = THWEPO_Utils_Field::get_display_label($field);
		
		if($file){
			$file_type = THWEPO_Utils::get_posted_file_type($file);
			$file_size = isset($file['size']) ? $file['size'] : false;
			
			if($file_type && $file_size){
				$name = $field->get_property('name');
				$maxsize = apply_filters('thwepo_file_upload_maxsize', $field->get_property('maxsize'), $name);
				$maxsize_bytes = is_numeric($maxsize) ? $maxsize*1048576 : false;
				$accept = apply_filters('thwepo_file_upload_accepted_file_types', $field->get_property('accept'), $name);
				$accept = $accept && !is_array($accept) ? array_map('trim', explode(",", $accept)) : $accept;

				$wp_filetype     = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'] );
				$ext             = empty( $wp_filetype['ext'] ) ? '' : $wp_filetype['ext'];
				$type            = empty( $wp_filetype['type'] ) ? '' : $wp_filetype['type'];

				if(!$type || !$ext){
					THWEPO_Utils::add_error('<strong>'.$title.'</strong> '. sprintf(__(': Sorry, this file type is not permitted for security reasons.', 'woocommerce-extra-product-options-pro')));
					$passed = false;
				}else{

					if(is_array($accept) && !empty($accept) && !in_array($file_type, $accept)){
						$count =  count($accept);

						if($count > 1){
							/* translators: %s: file types */
							THWEPO_Utils::add_error('<strong>'.$title.'</strong> '. sprintf(__(': %1$s - Invalid file type, allowed types are %2$s.', 'woocommerce-extra-product-options-pro'), $file_type, implode(',', $accept)));
							$passed = false;
						}else{
							/* translators: %d: file types */
							THWEPO_Utils::add_error('<strong>'.$title.'</strong> '. sprintf(__(': %1$s - Invalid file type, allowed types is %2$s.', 'woocommerce-extra-product-options-pro'), $file_type, implode(',', $accept)));
							$passed = false;
						}

					}else if($maxsize_bytes && is_numeric($maxsize_bytes) && $file_size >= $maxsize_bytes){
						/* translators: %s: file size */
						THWEPO_Utils::add_error('<strong>'.$title.'</strong> '. sprintf(__(': File too large. File must be less than %s megabytes.', 'woocommerce-extra-product-options-pro'), $maxsize));
						$passed = false;
					}
				}
				
			}else if($field->get_property('required')) {
				THWEPO_Utils::add_error('<strong>'.$title.'</strong> '. sprintf(__(': Please choose a file to upload.', 'woocommerce-extra-product-options-pro')) );
				$passed = false;
			}
		}else if($field->get_property('required')) {
			THWEPO_Utils::add_error('<strong>'.$title.'</strong> '. sprintf(__(': Please choose a file to upload.', 'woocommerce-extra-product-options-pro')) );
			$passed = false;
		}
		
		return $passed;
	}

	public function prepare_file_upload( $file, $name, $field ){
		$posted_value = false;

		if(!$field->get_property('required') && !THWEPO_Utils::is_valid_file($file)){
			return false;
		}

		$uploaded = $this->upload_file($file, $name, $field);

		if($uploaded && !isset($uploaded['error'])){
			$upload_info = array();
			$upload_info['name'] = $file['name'];
			$upload_info['url'] = $uploaded['url'];
			
			$posted_value = $upload_info;
			//$posted_value = $uploaded['url'] . '/' . $file['name']; 
		}else{
			$title = THWEPO_i18n::__t($field->get_property('title'));

			if(!is_array($uploaded['error'])){
				THWEPO_Utils::add_error('<strong>'.$title.'</strong>: '. $uploaded['error']);
			}else{
				THWEPO_Utils::add_error('<strong>'.$title.'</strong>: '. __('Upload failed, Please check the uploaded file.', 'woocommerce-extra-product-options-pro'));
			}

			return false;
		}
		return $posted_value;
	}

	public function upload_file($file, $name, $field){
		$upload = false;
		
		if(is_array($file)){
			if(!function_exists('wp_handle_upload')){
				require_once(ABSPATH. 'wp-admin/includes/file.php');
				require_once(ABSPATH. 'wp-admin/includes/media.php');
			}
			
			add_filter('upload_dir', array('THWEPO_Utils', 'upload_dir'));
			//add_filter('upload_mimes', array('THWEPO_Utils', 'upload_mimes'));
			$upload = wp_handle_upload($file, array('test_form' => false));
			remove_filter('upload_dir', array('THWEPO_Utils', 'upload_dir'));
			//remove_filter('upload_mimes', array('THWEPO_Utils', 'upload_mimes'));
			
			/*if($upload && !isset($upload['error'])){
				echo "File is valid, and was successfully uploaded.\n";
			} else {
				echo $upload['error'];
			}*/
		}
		return $upload;
	}
}

endif;