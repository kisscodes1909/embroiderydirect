<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://themehigh.com
 * @since      2.3.0
 *
 * @package    woocommerce-extra-product-options-pro
 * @subpackage woocommerce-extra-product-options-pro/admin
 */
if(!defined('WPINC')){	die; }

if(!class_exists('THWEPO_Admin')):
 
class THWEPO_Admin {
	private $plugin_name;
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    2.3.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;

		//$this->init_product_settings();
	}
	
	public function enqueue_styles_and_scripts($hook) {
		if(strpos($hook, 'product_page_th_extra_product_options_pro') === false) {
			return;
		}
		$debug_mode = apply_filters('thwepo_debug_mode', false);
		$suffix = $debug_mode ? '' : '.min';
		
		$this->enqueue_styles($suffix);
		$this->enqueue_scripts($suffix);
	}
	
	private function enqueue_styles($suffix) {
		wp_enqueue_style('jquery-ui-style', '//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css?ver=1.11.4');
		wp_enqueue_style('woocommerce_admin_styles', THWEPO_WOO_ASSETS_URL.'css/admin.css');
		wp_enqueue_style('wp-color-picker');
		wp_enqueue_style('thwepo-admin-style', THWEPO_ASSETS_URL_ADMIN . 'css/thwepo-admin'. $suffix .'.css', $this->version);
		//wp_enqueue_style('thwepo-colorpicker-style', THWEPO_ASSETS_URL_ADMIN . 'colorpicker/spectrum.css');
	}

	private function enqueue_scripts($suffix) {
		$deps = array('jquery', 'jquery-ui-dialog', 'jquery-ui-sortable', 'jquery-tiptip', 'wc-enhanced-select', 'selectWoo', 'wp-color-picker',);
		//$deps = array('jquery', 'jquery-ui-dialog', 'jquery-ui-sortable', 'jquery-tiptip', 'woocommerce_admin', 'wc-enhanced-select', 'selectWoo', 'wp-color-picker');
		
		/*wp_enqueue_script('thwepo-admin-base', THWEPO_ASSETS_URL_ADMIN . 'js/inc/thwepo-admin-base.js', $deps, $this->version, false);
		wp_enqueue_script('thwepo-admin-conditions', THWEPO_ASSETS_URL_ADMIN . 'js/inc/thwepo-admin-conditions.js', array('thwepo-admin-base'), $this->version, false);
		wp_enqueue_script('thwepo-admin-script', THWEPO_ASSETS_URL_ADMIN . 'js/inc/thwepo-admin.js', array('thwepo-admin-base', 'thwepo-admin-conditions'), $this->version, false);
		wp_enqueue_script('thwepo-admin-conditions', THWEPO_ASSETS_URL_ADMIN . 'js/inc/thwepo-admin-advanced.js', array('thwepo-admin-base'), $this->version, false);
		*/
		wp_enqueue_media();
		wp_enqueue_script( 'thwepo-admin-script', THWEPO_ASSETS_URL_ADMIN . 'js/thwepo-admin'. $suffix .'.js', $deps, $this->version, false );

		$wepo_var = array(
            'admin_url' => admin_url(),
            'ajax_url'  => admin_url( 'admin-ajax.php' ),
            'asset_url' => THWEPO_ASSETS_URL_ADMIN,
            'thumbnail_size' => apply_filters('thwepo_selected_image_size', 'thumbnail'),
            'load_product_nonce'	=> wp_create_nonce('wepo-load-products'),

            'edit_field'  => __('Edit Field', 'woocommerce-extra-product-options-pro'),
			'new_field'   => __('New Field', 'woocommerce-extra-product-options-pro'),
			'new_section' => __('New Section', 'woocommerce-extra-product-options-pro'),
			'edit_section'=> __('Edit Section', 'woocommerce-extra-product-options-pro'),

            'tag'         => __('Tag', 'woocommerce-extra-product-options-pro'),
			'product'     => __('Product', 'woocommerce-extra-product-options-pro'),
			'category'    => __('Category', 'woocommerce-extra-product-options-pro'),
			'user_role'   => __('User role', 'woocommerce-extra-product-options-pro'),

			'equal'       => __('Equals to/ In', 'woocommerce-extra-product-options-pro'),
			'notequal'    => __('Not Equals to/ Not in', 'woocommerce-extra-product-options-pro'),

			'empty'       => __('Is empty', 'woocommerce-extra-product-options-pro'),
			'not_empty'   => __('Is not empty', 'woocommerce-extra-product-options-pro'),
			'value_eq'    => __('Value equals to', 'woocommerce-extra-product-options-pro'),
			'value_ne'    => __('Value not equals to', 'woocommerce-extra-product-options-pro'),
			'value_in'    => __('Value in', 'woocommerce-extra-product-options-pro'),
			'value_cn'    => __('Contains', 'woocommerce-extra-product-options-pro'),
			'value_nc'    => __('Not contains', 'woocommerce-extra-product-options-pro'),
			'value_gt'    => __('Value greater than', 'woocommerce-extra-product-options-pro'),
			'value_le'    => __('Value less than', 'woocommerce-extra-product-options-pro'),
			'value_sw'    => __('Value starts with', 'woocommerce-extra-product-options-pro'),
			'value_nsw'   => __('Value not starts with', 'woocommerce-extra-product-options-pro'),
			'date_eq'     => __('Date equals to', 'woocommerce-extra-product-options-pro'),
			'date_ne'     => __('Date not equals to', 'woocommerce-extra-product-options-pro'),
			'date_gt'     => __('Date after', 'woocommerce-extra-product-options-pro'),
			'date_lt'     => __('Date before', 'woocommerce-extra-product-options-pro'),
			'day_eq'      => __('Day equals to', 'woocommerce-extra-product-options-pro'),
			'day_ne'      => __('Day not equals to', 'woocommerce-extra-product-options-pro'),
			'checked'     => __('Is checked', 'woocommerce-extra-product-options-pro'),
			'not_checked' => __('Is not checked', 'woocommerce-extra-product-options-pro'),
			'regex'       => __('Match expression', 'woocommerce-extra-product-options-pro'),

			'normal'      => __('Fixed', 'woocommerce-extra-product-options-pro'),
			'custom'      => __('Custom', 'woocommerce-extra-product-options-pro'),
			'percentage'  => __('Percentage of Product Price', 'woocommerce-extra-product-options-pro'),
			'dynamic'     => __('Dynamic', 'woocommerce-extra-product-options-pro'),
			'dynamic_excl_base_price' => __('Dynamic - Exclude base price', 'woocommerce-extra-product-options-pro'),
			'char_count'  => __('Character Count', 'woocommerce-extra-product-options-pro'),
			'custom_formula' => __('Custom Formula', 'woocommerce-extra-product-options-pro'),
        );
		wp_localize_script('thwepo-admin-script', 'wepo_var', $wepo_var);
	}
	
	public function admin_menu() {
		$capability = THWEPO_Utils::wepo_capability();
		$this->screen_id = add_submenu_page('edit.php?post_type=product', THWEPO_i18n::__t('WooCommerce Extra Product Option'), 
		THWEPO_i18n::__t('Extra Product Option'), $capability, 'th_extra_product_options_pro', array($this, 'output_settings'));
 	
		//add_action('admin_print_scripts-'. $this->screen_id, array($this, 'enqueue_admin_scripts'));
	}
	
	public function add_screen_id($ids){
		$ids[] = 'woocommerce_page_th_extra_product_options_pro';
		$ids[] = strtolower( THWEPO_i18n::__t('WooCommerce') ) .'_page_th_extra_product_options_pro';

		return $ids;
	}

	/*public function init_product_settings(){
		$prod_settings = THWEPO_Admin_Settings_Product::instance();	
		$prod_settings->render_page();
	}*/
	
	public function plugin_action_links($links) {
		$settings_link = '<a href="'.admin_url('edit.php?post_type=product&page=th_extra_product_options_pro').'">'. __('Settings') .'</a>';
		array_unshift($links, $settings_link);
		return $links;
	}
	
	public function plugin_row_meta( $links, $file ) {
		if(THWEPO_BASE_NAME == $file) {
			$doc_link = esc_url('https://www.themehigh.com/help-guides/woocommerce-extra-product-options/');
			$support_link = esc_url('https://help.themehigh.com/hc/en-us');
				
			$row_meta = array(
				'docs' => '<a href="'.$doc_link.'" target="_blank" aria-label="'.THWEPO_i18n::esc_attr__t('View plugin documentation').'">'.THWEPO_i18n::esc_html__t('Docs').'</a>',
				'support' => '<a href="'.$support_link.'" target="_blank" aria-label="'. THWEPO_i18n::esc_attr__t('Visit premium customer support' ) .'">'. THWEPO_i18n::esc_html__t('Premium support') .'</a>',
			);

			return array_merge( $links, $row_meta );
		}
		return (array) $links;
	}
	
	public function output_settings(){
		$tab = isset( $_GET['tab'] ) ? esc_attr( $_GET['tab'] ) : 'general_settings';
		
		echo '<div class="thwepo-wrap">';
		if($tab === 'advanced_settings'){			
			$advanced_settings = THWEPO_Admin_Settings_Advanced::instance();	
			$advanced_settings->render_page();			
		}else if($tab === 'license_settings'){			
			$license_settings = THWEPO_Admin_Settings_License::instance();	
			$license_settings->render_page();	
		}else{
			$general_settings = THWEPO_Admin_Settings_General::instance();	
			$general_settings->render_page();
		}
		echo '</div">';
	}
}

endif;