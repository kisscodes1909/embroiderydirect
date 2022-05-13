<?php
if(!defined('WPINC')){	die; }

if(!class_exists('THWEPO_License_Manager')):

class THWEPO_License_Manager {
	protected static $_instance = null;

	public $file             = '';
	public $data          = array();


	public function __construct($file, $data){
		$plugin_header = $this->read_plugin_header($file);
		$this->version = $plugin_header['Version'];

		$this->name        = plugin_basename($file);
		$this->api_url      = trailingslashit($data['api_url']);
		$this->product_id   = $data['product_id'];
		$this->product_name = $data['product_name'];
		$this->slug        = sanitize_title($this->product_name);

		$this->wp_override = isset( $data['wp_override'] ) ? (bool) $data['wp_override'] : false;
		$this->beta        = isset( $data['beta'] ) ? (bool) $data['beta'] : false;

		$this->license_page_url = isset($data['license_page_url']) ? $data['license_page_url'] : '';

		$this->sw_identifier = $this->prepare_software_identifier($this->product_name);
		$this->license_data_key = $this->sw_identifier . '_license_data';

		if(is_multisite()){
			$this->domain = str_ireplace(array( 'http://', 'https://' ), '', network_site_url()); // blog domain name
		}else{
			$this->domain = str_ireplace(array( 'http://', 'https://' ), '', home_url()); // blog domain name
		}

		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_update' ), 999);
		add_filter( 'plugins_api', array( $this, 'plugins_api_filter' ), 10, 3 );
		add_action('admin_init', array( $this, 'prepare_license_form_shortcode'));
		add_action('init', array($this ,'license_form_listener'));
		add_action('admin_notices', array($this ,'display_admin_notices'));
		add_action('admin_init', array( $this, 'log_developer_data'));
	}

	public static function instance($file, $data) {
		if(is_null( self::$_instance )) {
			self::$_instance = new self($file, $data);
		}
		return self::$_instance;
	}

	private function write_log ( $log ){
		if (defined('WP_DEBUG') && true === WP_DEBUG) {
			if ( is_array( $log ) || is_object( $log ) ) {
				error_log( print_r( $log, true ) );
			} else {
				error_log( $log );
			}
		}
	}

	private function prepare_software_identifier($product_name){
		$product_name = preg_replace("/\s+/", "_", $product_name);
		$sw_identifier = str_replace("-", "_", $product_name);
		$sw_identifier = 'th_'. sanitize_key($sw_identifier);
		return $sw_identifier;
	}

	private function read_plugin_header($file){
		$plugin_data = get_file_data($file, [
			'Version' => 'Version',
			'Plugin Name' => 'Plugin Name',
		], 'plugin');

		return $plugin_data;
	}
	
	function log_developer_data(){
		if (defined('WP_DEBUG') && true === WP_DEBUG &&  apply_filters( 'thedd_license_client_log_developer_data', false )) {
			$this->write_log('----- Developer tips start -----');
			$this->write_log('Software identifier: ' . $this->sw_identifier);
			$this->write_log('License form shortcode: [' . $this->sw_identifier . '_license_form]');
			$this->write_log('Option table key for license data: ' . $this->license_data_key);
			$this->write_log('----- Developer tips end -----');
		}
	}

	function update_license_data($data){
		if(empty($data)){
			if(is_multisite()){
				delete_site_option($this->license_data_key);
			}else{
				delete_option($this->license_data_key);
			}
		}else{
			$existing_data = $this->get_license_data();
			$updated_data = array_merge($existing_data, $data);
			if(is_multisite()){
				update_site_option($this->license_data_key, $updated_data, 'no');
			}else{
				update_option($this->license_data_key, $updated_data, 'no');
			}
		}
	}

	private function get_license_data(){
		$license_data = false;
		if(is_multisite()){
			$license_data = get_site_option($this->license_data_key);
		}else{
			$license_data = get_option($this->license_data_key);
		}
		return is_array($license_data) && !empty($license_data) ? $license_data : array();
	}

	/**
	 * Returns if the SSL of the store should be verified.
	 *
	 * @since  1.6.13
	 * @return bool
	 */
	private function verify_ssl() {
		return (bool) apply_filters( 'edd_sl_api_request_verify_ssl', true, $this );
	}

	/**
	 * Convert some objects to arrays when injecting data into the update API
	 *
	 * Some data like sections, banners, and icons are expected to be an associative array, however due to the JSON
	 * decoding, they are objects. This method allows us to pass in the object and return an associative array.
	 *
	 * @since 3.6.5
	 *
	 * @param stdClass $data
	 *
	 * @return array
	 */
	private function convert_object_to_array( $data ) {
		$new_data = array();
		foreach ( $data as $key => $value ) {
			$new_data[ $key ] = is_object( $value ) ? $this->convert_object_to_array( $value ) : $value;
		}

		return $new_data;
	}

	function valid_date_format($date, $format = 'Y-m-d H:i:s')
	{
	    $d = DateTime::createFromFormat($format, $date);
	    // The Y ( 4 digits year ) returns TRUE for any integer with any number of digits so changing the comparison from == to === fixes the issue.
	    return $d && $d->format($format) === $date;
	}

	/**
	 * Check for Updates at the defined API endpoint and modify the update array.
	 *
	 * This function dives into the update API just when WordPress creates its update array,
	 * then adds a custom API call and injects the custom plugin data retrieved from the API.
	 * It is reassembled from parts of the native WordPress plugin update code.
	 * See wp-includes/update.php line 121 for the original wp_update_plugins() function.
	 *
	 * @uses api_request()
	 *
	 * @param array   $_transient_data Update array build by WordPress.
	 * @return array Modified update array with custom plugin data.
	 */
	public function check_update( $_transient_data ) {
		if(empty($_transient_data->checked)){
			return $_transient_data;
		}
		
		global $pagenow;

		if ( ! is_object( $_transient_data ) ) {
			$_transient_data = new stdClass;
		}
		
		if ( 'plugins.php' == $pagenow && is_multisite() ) {
			return $_transient_data;
		}

		if ( ! empty( $_transient_data->response ) && ! empty( $_transient_data->response[ $this->name ] ) && false === $this->wp_override ) {
			return $_transient_data;
		}

		$verify_ssl = $this->verify_ssl();

		$license_data = $this->get_license_data();
		$license_key = isset($license_data['license_key']) ? $license_data['license_key'] : '';

		$data = array(
			'edd_action'=> 'get_version',
			'url' => $this->domain,
			'license' => $license_key,
			'item_id' => $this->product_id,
		);

		$request = wp_remote_post($this->api_url, array( 'timeout' => 15, 'sslverify' => $verify_ssl, 'body' => $data ));

		if(is_wp_error($request) || wp_remote_retrieve_response_code($request) != 200) {
			$this->write_log('--- Start of error ---');
			$this->write_log($request);
			$this->write_log('--- End of error ---');
			return $_transient_data;
		} else {
			$response = wp_remote_retrieve_body($request);
			$response = json_decode( $response );
		}

		/* Unset unnessosory properties from EDD response */
		if($response && is_object( $response )){
			unset($response->sections);
			unset($response->banners);
			unset($response->icons);
		}

		/*commented for future release, EDD response have unnessosory properties & we already unset those properties */

		// if ( $response && isset( $response->sections ) ) {
		// 	$response->sections = maybe_unserialize( $response->sections );
		// }
		//
		// if ( $response && isset( $response->banners ) ) {
		// 	$response->banners = maybe_unserialize( $response->banners );
		// }
		//
		// if ( $response && isset( $response->icons ) ) {
		// 	$response->icons = maybe_unserialize( $response->icons );
		// }
		//
		// if ( ! empty( $response->sections ) ) {
		// 	foreach( $response->sections as $key => $section ) {
		// 		$response->$key = (array) $section;
		// 	}
		// }
		if($response && is_object( $response )){
			$response->plugin = isset($response->plugin) && $response->plugin ? $response->plugin : $this->name;
			
			$response_license_data = array(
				'status' => isset($response->license_status) ? $response->license_status : false,
				'expiry' => isset($response->expires) ? $response->expires : "lifetime",
			);
			
			//remove empty array element
			$response_license_data = array_filter( $response_license_data, 'strlen' );
			
			if($response_license_data && isset($response->license_status) && (($response->license_status == 'invalid') || ($response->license_status == 'disabled')) ){
				$this->update_license_data(array());
			}elseif($response_license_data && isset($response->license_status)){
				$this->update_license_data($response_license_data);
			}else{
				$this->update_license_data(array());
			}
		}

		if ( false !== $response && is_object( $response ) && isset( $response->new_version ) ) {
			if ( version_compare( $this->version, $response->new_version, '<' ) ) {
				$_transient_data->response[ $this->name ] = $response;
			} else {
				// Populating the no_update information is required to support auto-updates in WordPress 5.5.
				$_transient_data->no_update[ $this->name ] = $response;
			}
		}
		$_transient_data->last_checked           = time();
		$_transient_data->checked[ $this->name ] = $this->version;

		return $_transient_data;
	}


	/**
	 * Updates information on the "View version x.x details" page with custom data.
	 *
	 * @uses api_request()
	 *
	 * @param mixed   $_data
	 * @param string  $_action
	 * @param object  $_args
	 * @return object $_data
	 */
	public function plugins_api_filter( $_data, $_action = '', $_args = null ) {
		if ( $_action != 'plugin_information' ) {
			return $_data;
		}

		if ( ! isset( $_args->slug ) || ( $_args->slug != $this->slug ) ) {
			return $_data;
		}

		$verify_ssl = $this->verify_ssl();

		$license_data = $this->get_license_data();
		$license_key = isset($license_data['license_key']) ? $license_data['license_key'] : '';

		$data = array(
			'edd_action'=> 'get_version',
			'url' => $this->domain,
			'license' => $license_key,
			'item_id' => $this->product_id,
		);

		$request = wp_remote_post($this->api_url, array( 'timeout' => 15, 'sslverify' => $verify_ssl, 'body' => $data ));

		if(is_wp_error($request) || wp_remote_retrieve_response_code($request) != 200) {
			$this->write_log('--- Start of error ' . $action . '');
			$this->write_log($request);
			$this->write_log('--- End of error ---');
			return $_data;
		} else {
			$data_json = wp_remote_retrieve_body($request);
			$_data = json_decode( $data_json );
		}

		if ( $_data && isset( $_data->sections ) ) {
			$_data->sections = maybe_unserialize( $_data->sections );
		} else {
			$_data = false;
		}

		if ( $_data && isset( $_data->banners ) ) {
			$_data->banners = maybe_unserialize( $_data->banners );
		}

		if ( $_data && isset( $_data->icons ) ) {
			$_data->icons = maybe_unserialize( $_data->icons );
		}

		if ( ! empty( $_data->sections ) ) {
			foreach( $_data->sections as $key => $section ) {
				$_data->$key = (array) $section;
			}
		}

		// Convert sections into an associative array, since we're getting an object, but Core expects an array.
		if ( isset( $_data->sections ) && ! is_array( $_data->sections ) ) {
			$_data->sections = $this->convert_object_to_array( $_data->sections );
		}

		// Convert banners into an associative array, since we're getting an object, but Core expects an array.
		if ( isset( $_data->banners ) && ! is_array( $_data->banners ) ) {
			$_data->banners = $this->convert_object_to_array( $_data->banners );
		}

		// Convert icons into an associative array, since we're getting an object, but Core expects an array.
		if ( isset( $_data->icons ) && ! is_array( $_data->icons ) ) {
			$_data->icons = $this->convert_object_to_array( $_data->icons );
		}

		// Convert contributors into an associative array, since we're getting an object, but Core expects an array.
		if ( isset( $_data->contributors ) && ! is_array( $_data->contributors ) ) {
			$_data->contributors = $this->convert_object_to_array( $_data->contributors );
		}

		if( ! isset( $_data->plugin ) ) {
			$_data->plugin = $this->name;
		}

		return $_data;
	}


	public function prepare_license_form_shortcode(){
		add_shortcode( $this->sw_identifier . '_license_form', array($this, 'license_form_shortcode_callback') );
	}

	public function license_form_shortcode_callback($atts){
		$params = shortcode_atts( array(
			'wrapper_class' => 'th-license-form',
		), $atts );
		ob_start();
		$this->output_license_page($params);
		return ob_get_clean();
	}

	public function output_license_page($params){
		$wrapper_class = $params['wrapper_class'];
		$license_data = $this->get_license_data();
		$status = isset($license_data['status']) ? $license_data['status'] : false;
		$license_key = isset($license_data['license_key']) ? $license_data['license_key'] : false;

		$box_style = 'margin-top: 20px; padding: 20px 30px 10px 30px; background-color: #fff; box-shadow: 1px 1px 5px 1px rgba(0,0,0,.1); min-height: 240px;';
		$box_left  = $box_style;
		$box_right = $box_style;

		if($license_key){
			$box_left  .= 'width: 35%; float:left; margin-right: 20px;';
			$box_right .= 'width: 35%; float:left;';
		}else{
			$box_left  .= 'width: 70%;';
		}
		?>
		<div style="<?php echo $box_left; ?>" class="<?php echo $wrapper_class; ?>">
			<?php
			$this->output_license_form($license_data);
			?>
		</div>
		<?php

		if($license_key){
			?>
			<div style="<?php echo $box_right; ?>">
				<?php
				$this->output_license_info($license_data);
				?>
			</div>
			<div style="clear: both;"></div>
			<?php
		}
	}

	private function output_license_form($license_data){
		$license_key = isset($license_data['license_key']) ? $license_data['license_key'] : '';
		$status = isset($license_data['status']) ? $license_data['status'] : false;

		$input_style = 'width: 100%; padding: 10px;';
		$license_field_attr  = 'name="license_key"';
		$license_field_attr .= ' placeholder="License key ( e.g. LDXXRJZQ341X9TH9GFMADYDAA15PE8 )"';
		$form_title_note = '';
		$form_footer_note = '';

		if($license_key){
			$license_field_attr .= ' value="'.$license_key.'"';
			$license_field_attr .= ' readonly';
			$btn_label  = 'Deactivate';
			$btn_action = 'deactivate';
			$form_footer_note = 'Deactivate License Key so that it can be used on another domain.';

			//$this->display_expiry_notices($license_data);
		}else{
			$license_field_attr .= ' value=""';
			$btn_label  = 'Activate';
			$btn_action = 'activate';

			$license_form_title_note = 'Enter your License Key and hit activate button.';
			$license_form_title_note = apply_filters('thedd_license_form_title_note', $license_form_title_note, $this->sw_identifier);

			if($license_form_title_note){
				$form_title_note = '<p>'.$license_form_title_note.'</p>';
			}
		}
		$btn_action .= '-'.$this->sw_identifier;

		//$this->print_validation_notices();
		?>
		<h1>Software License Key</h1>
		<?php echo $form_title_note; ?>
		<form method='post' action='' >
			<p>
				<input type="text" <?php echo $license_field_attr ?> style="<?php echo $input_style; ?>">
				<?php echo wp_nonce_field('handle_license_form', 'nonce_license_form'); ?>
			</p>
			<p>
				<button type="submit" name="action" value="<?php echo $btn_action; ?>" class="button-primary"><?php echo $btn_label; ?></button>
			</p>
		</form>
		<?php
		echo $form_footer_note;
	}

	private function output_license_info($license_data){
		?>
		<h1><?php _e('License Details', 'text-domain'); ?></h1>
		<?php
			$l_status = isset( $license_data['status'] ) ? $license_data['status'] : '';
			$expiry = isset( $license_data['expiry'] ) ? $license_data['expiry'] : '';
			$wp_date_format = get_option('date_format');
			if($wp_date_format && $expiry && $this->valid_date_format($expiry)){
				$expiry = date_format(date_create($expiry), $wp_date_format);
			}elseif($expiry == 'lifetime'){
				$expiry = 'Lifetime';
			}

			if( ($l_status === 'valid') || ($l_status === 'active') ){
				$l_status_string = '<label style="color: green;">'.ucwords($l_status).'<label>';
			}else{
				$l_status_string = '<label style="color: red;">'.ucwords($l_status).'<label>';
			}
			
			//$expiry = $expiry === 'never' ? ucwords($expiry) : $expiry;
			$cell_style = 'padding: 10px 0; border-bottom: 1px solid #eee;';
			?>
			<table width="100%" style="font-size: 15px;">
				<tbody>
					<tr style="border-bottom: 1px solid ">
						<td style="<?php echo $cell_style ?>" width="40%"><strong><?php _e('License status', 'text-domain'); ?></strong></td>
						<td style="<?php echo $cell_style ?>"><strong><?php echo $l_status_string; ?></strong></td>
					</tr>
					<?php if($expiry){ ?>
						<tr>
							<td style="<?php echo $cell_style ?>"><strong><?php _e('Expiry', 'text-domain'); ?></strong></td>
							<td style="<?php echo $cell_style ?>"><?php echo $expiry; ?></td>
						</tr>
					<?php } ?>
				</tbody>
			</table>
			<?php
	}


	public function license_form_listener() {
		if(isset($_POST['nonce_license_form']) && $_POST['nonce_license_form']){
			if(!wp_verify_nonce($_POST['nonce_license_form'], 'handle_license_form')){
				die('You are not authorized to perform this action.');

			} else {
				$action = isset($_POST['action']) ? $_POST['action'] : '';

				if($action === 'activate-'.$this->sw_identifier){
					$license_key = isset($_POST['license_key']) ? $_POST['license_key'] : '';
					if($license_key){
						$this->trigger_license_request('activate', $_POST);
					}else{
						//$this->handle_notices('E003');
					}
				}elseif($action === 'deactivate-'.$this->sw_identifier){
					$this->trigger_license_request('deactivate', $_POST);
				}

			}
		}
	}

	private function trigger_license_request($action, $posted){
		// data to send in our API request
		$request_data = $this->prepare_request_data($action, $posted);	

		//$request = wp_remote_post( $target_url, array('body' => $request_data) );
		$request = wp_remote_post($this->api_url, array( 'timeout' => 15, 'sslverify' => false, 'body' => $request_data ));

		if(is_wp_error($request) || wp_remote_retrieve_response_code($request) != 200){
			//$this->handle_notices('E002');
			$this->write_log('--- Start of error ' . $action . '');
			$this->write_log($request);
			$this->write_log('--- End of error ---');
		} else {
			$response = wp_remote_retrieve_body( $request );
			$response_obj = json_decode($response);

			if ( $response_obj && (false === $response_obj->success) ) {
				if(isset($response_obj->error)){

					switch( $response_obj->error ) {

						case 'expired' :
							$message = __( 'Your license key expired.' );
							break;

						case 'disabled' :
						case 'revoked' :

							$message = __( 'Your license key has been disabled.' );
							break;

						case 'missing' :

							$message = __( 'Invalid license.' );
							break;

						case 'invalid' :
						case 'site_inactive' :

							$message = __( 'Your license is not active for this URL.' );
							break;

						case 'item_name_mismatch' :

							$message = sprintf( __( 'This appears to be an invalid license key for %s.' ), $this->product_name );
							break;

						case 'no_activations_left':

							$message = __( 'Your license key has reached its activation limit.' );
							break;

						default :

							$message = __( 'An error occurred, please try again.' );
							break;
					}
					$this->update_license_data(array());
				}else{
					$message = __( 'An error occurred, please try again.' );
					$this->update_license_data(array());
				}

				add_action('admin_notices', function() use ($message){
					echo '<div class="notice notice-error"><p>'. $message. '</p></div>';
				});

			}else{
				if($action == 'activate'){
					$message = __('License activated successfully.', 'th_edd_license_helper');
					$license_data = $this->prepare_data_from_response($response_obj);
					$license_data['license_key'] = isset($posted['license_key']) ? $posted['license_key'] : '';
					$this->update_license_data($license_data);
				}elseif($action == 'deactivate'){
					$message = __('License deactivated successfully.', 'th_edd_license_helper');
					$this->update_license_data(array());
				}

				add_action('admin_notices', function() use ($message) {
					echo '<div class="notice notice-success"><p>'. $message. '</p></div>';
				});
			}

		}

	}



	public function prepare_request_data($action, $posted){
		// Validate Posted Data & create data array to POST to API
		$license_key = isset($posted['license_key']) ? $posted['license_key'] : '';
		$license_key = trim($license_key);
		if($action == 'activate'){

			$api_params = array(
				'edd_action'  => 'activate_license',
				'license'     => $license_key,
				'item_name'   => urlencode( $this->product_name ), // the name of our product in EDD
				'url'         => $this->domain,
				'environment' => function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production',
			);

		}elseif($action == 'deactivate'){

			$api_params = array(
				'edd_action'  => 'deactivate_license',
				'license'     => $license_key,
				'item_name'   => urlencode( $this->product_name ), // the name of our product in EDD
				'url'         => $this->domain,
				'environment' => function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production',
			);

		}
		return $api_params;
	}

	function prepare_data_from_response($response_obj){
		return array(
			'status' => $response_obj->license,
			'expiry' => $response_obj->expires,
			/*
			'license_limit' => $response_obj->license_limit,
			'site_count' => $response_obj->site_count,
			'activations_left' => $response_obj->activations_left,
			*/
		);
	}

	public function display_admin_notices() {
		if(!apply_filters('themehigh_show_admin_notice', true, $this->sw_identifier )){
			return;
		}

		$dismissed_notice = get_transient( 'th_' . $this->sw_identifier . 'dismiss_notice' );
		if($dismissed_notice){
			return;
		}

		$ldata = $this->get_license_data();

		/*
		$is_dismissible = false;
		if(isset($ldata['status'])){
			$is_dismissible = ($ldata['license_status'] == 'expired') ? true : false;
		}
		**/

		$is_dismissible = apply_filters( 'themehigh_allow_dismissible_admin_notice', false, $this->sw_identifier, $ldata );

		if($ldata){
			/*
			$status = isset($ldata['status']) ? $ldata[$ldata['status']] : '';
			if($status != 'valid'){
				$notice = 'The license of <strong>%s</strong> is not activated. <a href="%s">Click here</a> to activate the license.';
			}
			*/
		}else{
			$notice = 'The license of <strong>%s</strong> is not activated. <a href="%s">Click here</a> to activate the license.';
		}

		if(!empty($notice)){
			if(is_multisite()){
				$enable_notification_sub_site = apply_filters( 'themehigh_enable_notifications_sub_site', true, $this->sw_identifier );

				if(is_main_site()){
					$this->show_admin_notice_content($notice, 'admin_notice', $is_dismissible);
				}else{
					if($enable_notification_sub_site){
						$this->show_admin_notice_content($notice, 'admin_notice', $is_dismissible);
					}
				}
			}else{
				$this->show_admin_notice_content($notice, 'admin_notice', $is_dismissible);
			}
		}
	}

	private function show_admin_notice_content($notice, $type='admin_notice', $is_dismissible = false){
		if($is_dismissible){
			$dismiss_url = add_query_arg( array(
					$this->sw_identifier.'_dismiss_admin_notice' => true,
				) );
		}

		$notice = sprintf($notice, $this->product_name, $this->license_page_url);
		?>
		<div class="error notice <?php
				echo $this->sw_identifier . '_admin_notice ';
				if ( $is_dismissible ) {
					echo 'is-dismissible" data-dismiss-url="' . esc_url( $dismiss_url );
				} ?>" >
			<p><?php _e($notice, 'themehigh-license'); ?></p>
		</div>
		<?php
	}

	public function handle_notice_dismiss(){
		$dismiss_notice = filter_input( INPUT_GET, $this->sw_identifier.'_dismiss_admin_notice', FILTER_SANITIZE_STRING );
		if($dismiss_notice){
			$expiration = apply_filters( 'thlm_dismissible_notice_expiration', 1 * MONTH_IN_SECONDS, $this->sw_identifier );
			$expiration = absint( $expiration );
			set_transient( 'th_' . $this->sw_identifier . 'dismiss_notice', true, $expiration );
		}
	}

	public function custom_script_on_admin_footer(){
		?>
		<script>
		(function( $ ) {
		    'use strict';
			var wrapper = '.<?php echo $this->sw_identifier . '_admin_notice';?>';
		    $( function() {
		        $( wrapper ).on( 'click', '.notice-dismiss', function( event, el ) {
		            var $notice = $(this).parent('.notice.is-dismissible');
		            var dismiss_url = $notice.attr('data-dismiss-url');
		            if ( dismiss_url ) {
						window.location.replace(dismiss_url);
		            }
		        });
		    } );
		})( jQuery );
		</script>
		<?php
	}

	public static function thwepo_lm_to_edd_license_migration() {
		$edd_license_key = 'th_extra_product_options_for_woocommerce_license_data';
		$edd_license_data = get_option($edd_license_key, array());
		if(empty($edd_license_data)){
			$lm_software_title = THWEPO_SOFTWARE_TITLE;
			$lm_prefix = str_ireplace(array( ' ', '_', '&', '?', '-' ), '_', strtolower($lm_software_title));
			$lm_license_key = $lm_prefix . '_thlmdata';
			$lm_license_data = self::thwepo_get_thlm_saved_license_data($lm_license_key);
			if($lm_license_data){
				$status = isset($lm_license_data['status']) ? $lm_license_data['status'] : '';
				if($status = 'active'){
					$new_data = array(
						'license_key' => isset($lm_license_data['license_key']) ? $lm_license_data['license_key'] : '',
						'expiry' => isset($lm_license_data['expiry_date']) ? $lm_license_data['expiry_date'] : '',
						'status' => 'valid',
					);
					$result = self::thwepo_update_edd_license_data($edd_license_key, $new_data);
					if($result){
						self::thwepo_delete_lm_license_data($lm_license_key);
					}
				}
			}
		}
		
	}
	
	private static function thwepo_get_thlm_saved_license_data($key){
		$license_data = '';
		if(is_multisite()){
			$license_data = get_site_option($key);
		}else{
			$license_data = get_option($key);
		}
		return $license_data;
	}

	private static function thwepo_update_edd_license_data($edd_license_key, $data){
		$result = false;
		if(is_multisite()){
			$result = update_site_option($edd_license_key, $data, 'no');
		}else{
			$result = update_option($edd_license_key, $data, 'no');
		}
		return $result;
	}

	private static function thwepo_delete_lm_license_data($key){
		if(is_multisite()){
			delete_site_option($key);
		}else{
			delete_option($key);
		}
	}
}

endif;
