<?php
/**
 * Plugin Name: Tiered Pricing For WooCommerce
 * Plugin URI: https://wpexperts.io/
 * Description: Tiered Pricing for WooCommerce is a powerful strategy that allows you to create pricing tiers on each store level. Each pricing tier will enable you to create rules that help you define the quantity range (minimum and maximum) along with the type of discount it offers (fixed or percentage).
 * Version: 1.0.1
 * Author: wpexpertsio
 * Author URI: https://wpexperts.io/
 * Developer: wpexpertsio
 * Developer URI: https://wpexperts.io/
 * Text Domain: tiered-pricing-for-woocommerce
 * 
 * Woo: 8354233:fbeec76c92b0c05ff10d952d84c99e2d
 * WC requires at least: 3.0
 * WC tested up to: 3.6.4
 * 
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// $debug_tags = array();
// add_action( 'all', function ( $tag ) {
// global $debug_tags;
// if ( in_array( $tag, $debug_tags ) ) {
// return;
// }
// echo "<pre>" . $tag . "</pre>";
// $debug_tags[] = $tag;
// } );

function wtp_woocommerce_active_check() {

	$active_plugins = get_option( 'active_plugins', array() );

	return in_array( 'woocommerce/woocommerce.php', $active_plugins, true ) || array_key_exists( 'woocommerce/woocommerce.php', $active_plugins );
}

if ( wtp_woocommerce_active_check() ) {


	define( 'WTP_ROOT_URL', plugin_dir_url( __FILE__ ) );
	define( 'WTP_ROOT_PATH', plugin_dir_path( __FILE__ ) );
	define( 'WTP_VERSION', '1.1' );

	add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wtp_add_action_links' );


	add_action( 'plugins_loaded', 'wtp_setup' );
	add_action( 'admin_enqueue_scripts', 'wtp_enqueue_scripts' );

	add_action( 'wp_ajax_wtp_general_role_rule', 'wtp_general_role_rule' );
	add_action( 'wp_ajax_wtp_general_role_rule_delete', 'wtp_general_role_rule_delete' );
	add_action( 'wp_ajax_wtp_general_role_rule_disable', 'wtp_general_role_rule_disable' );
	add_action( 'wp_ajax_wtp_general_role_rule_enable', 'wtp_general_role_rule_enable' );

	add_action( 'wp_ajax_wtp_general_category_rule', 'wtp_general_category_rule' );
	add_action( 'wp_ajax_wtp_general_category_rule_delete', 'wtp_general_category_rule_delete' );
	add_action( 'wp_ajax_wtp_general_category_rule_disable', 'wtp_general_category_rule_disable' );
	add_action( 'wp_ajax_wtp_general_category_rule_enable', 'wtp_general_category_rule_enable' );

	add_action( 'wp_ajax_wtp_user_discount_rule', 'wtp_user_discount_rule' );
	add_action( 'wp_ajax_wtp_general_user_rule_delete', 'wtp_general_user_rule_delete' );
	add_action( 'wp_ajax_wtp_general_user_rule_disable', 'wtp_general_user_rule_disable' );
	add_action( 'wp_ajax_wtp_general_user_rule_enable', 'wtp_general_user_rule_enable' );


	add_action( 'wp_ajax_wtp_add_user_role', 'wtp_add_user_role' );
	add_action( 'wp_ajax_wtp_user_role_delete', 'wtp_user_role_delete' );

	add_action( 'wp_ajax_wtp_general_rule_edit', 'wtp_general_rule_edit' );

	add_action( 'wp_ajax_wtp_import_data', 'wtp_import_data' );
	add_action( 'wp_ajax_wtp_start_importing_to_db', 'wtp_start_importing_to_db' );

	add_action( 'wp_ajax_wtp_export_data', 'wtp_export_data' );

	add_action( 'wp_ajax_wtp_calculate_summary', 'wtp_calculate_summary' );
	add_action( 'wp_ajax_nopriv_wtp_calculate_summary', 'wtp_calculate_summary' );

	function wtp_add_action_links( $actions ) {
		
		$mylinks = array(
			'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=tier_pricing&section=wtp_general_discount_options' ) . '">' . esc_html__( 'Settings', 'wtp' ) . '</a>'
		);

		$actions = array_merge( $actions, $mylinks );
		return $actions;
	}

	function wtp_calculate_summary() {

		if ( isset( $_POST['_wtp_nonce'] ) && wp_verify_nonce( sanitize_text_field( $_POST['_wtp_nonce'] ), '_wtp_nonce' ) ) {
			exit( 'Nonce verification failed!' );
		}

		$result = array();

		if ( isset( $_POST['price'] ) && isset( $_POST['qty'] ) ) {

			$unit_price         = sanitize_text_field( $_POST['price'] );
			$qty                = sanitize_text_field( $_POST['qty'] );
			$subtotal           = $qty * $unit_price;
			$result['price']    = wtp_price( $unit_price );
			$result['subtotal'] = wtp_price( $subtotal );
		}

		echo json_encode( $result );
		wp_die();

	}

	function wtp_start_importing_to_db() {		
		if ( isset( $_POST['_wtp_nonce'] ) || wp_verify_nonce( sanitize_text_field( $_POST['_wtp_nonce'] ), 'wtp_import_csv' ) ) {
			exit( 'Nonce verification failed!' );
		}

		header( 'Content-type: text/html; charset=utf-8' );
		header( 'Cache-Control: no-cache, must-revalidate' );
		header( 'Pragma: no-cache' );
		
		if ( isset( $_COOKIE['import_type'] ) && isset( $_COOKIE['csv_file_name'] ) ) {

			$type     = sanitize_text_field( $_COOKIE['import_type'] );
			$filename = sanitize_text_field( $_COOKIE['csv_file_name'] );
			$filepath = WTP_ROOT_PATH . 'imports/';

			if ( file_exists( $filepath ) ) {

				$file_data = fopen( $filepath . $filename, 'r' );

				$title_row = fgetcsv( $file_data );

				$tier_data = array();

				if ( 'product' === $type ) {

					while ( $row = fgetcsv( $file_data ) ) {

						$id_or_slug     = isset( $row[0] ) ? $row[0] : 0;
						$min_qty        = isset( $row[1] ) ? trim( $row[1] ) : '';
						$max_qty        = isset( $row[2] ) ? trim( $row[2] ) : '&#8734;';
						$discount_type  = isset( $row[3] ) ? trim( strtolower( $row[3] ) ) : '';
						$discount_value = isset( $row[4] ) ? trim( $row[4] ) : '';
						$status         = isset( $row[5] ) ? trim( strtolower( $row[5] ) ) : 'enable';

						if ( ! empty( $id_or_slug ) ) {
							$tier_data[ $id_or_slug ]['min_qty'][]        = $min_qty;
							$tier_data[ $id_or_slug ]['max_qty'][]        = $max_qty;
							$tier_data[ $id_or_slug ]['discount_type'][]  = $discount_type;
							$tier_data[ $id_or_slug ]['discount_value'][] = $discount_value;
							if ( 'enable' == $status ) {
								$tier_data[ $id_or_slug ]['disabled'][] = 'false';
							} else {
								$tier_data[ $id_or_slug ]['disabled'][] = 'true';
							}
						}

						if ( ob_get_level() > 0 ) {
							ob_end_flush();
						}
					}					

					if ( is_array( $tier_data ) && count( $tier_data ) > 0 ) {

						foreach ( $tier_data as $product_id => $data ) {

							$product_data = wc_get_product( $product_id );							

							if ( $product_data ) {

								if ( ! ( 'variation' === $product_data->get_type() ) ) {

									if ( ! empty( $data ) ) {
										update_post_meta( $product_id, 'wtp_product_tier_setting', $data );
										echo 'success';
									} else {
										echo '';
									}

								} else {

									if ( ! empty( $data ) ) {
										update_post_meta( $product_id, 'wtp_product_tier_setting_' . $product_id, $data );
										echo 'success';
									} else {
										echo '';
									}

								}								
							}
						}
					}
				}

				if ( 'category' === $type ) {

					$i = 0;
					while ( $row = fgetcsv( $file_data ) ) {

						$id_or_slug     = isset( $row[0] ) ? $row[0] : 0;
						$min_qty        = isset( $row[1] ) ? trim( $row[1] ) : '';
						$max_qty        = isset( $row[2] ) ? trim( $row[2] ) : '&#8734;';
						$discount_type  = isset( $row[3] ) ? trim( strtolower( $row[3] ) ) : '';
						$discount_value = isset( $row[4] ) ? trim( $row[4] ) : '';
						$status         = isset( $row[5] ) ? trim( strtolower( $row[5] ) ) : 'enable';

						$cat = get_term_by( 'slug', $id_or_slug, 'product_cat' );
						if ( $cat ) {
							$tier_data[ $i ]['category']       = $id_or_slug;
							$tier_data[ $i ]['category_text']  = $cat->name;
							$tier_data[ $i ]['min_qty']        = $min_qty;
							$tier_data[ $i ]['max_qty']        = $max_qty;
							$tier_data[ $i ]['discount_type']  = $discount_type;
							$tier_data[ $i ]['discount_value'] = $discount_value;
							if ( 'disable' == $status ) {
								$tier_data[ $i ]['disabled'] = 'true';
							}

							$i++;
						}

						if ( ob_get_level() > 0 ) {
							ob_end_flush();
						}
					}

					if ( ! empty( $tier_data ) ) {
						update_option( 'general_category_rules', $tier_data );
						echo 'success';
					} else {
						echo 'failed';
					}
				}

				if ( 'user' === $type ) {
					$i = 0;
					while ( $row = fgetcsv( $file_data ) ) {

						$id_or_slug     = isset( $row[0] ) ? $row[0] : 0;
						$min_qty        = isset( $row[1] ) ? trim( $row[1] ) : '';
						$max_qty        = isset( $row[2] ) ? trim( $row[2] ) : '&#8734;';
						$discount_type  = isset( $row[3] ) ? trim( strtolower( $row[3] ) ) : '';
						$discount_value = isset( $row[4] ) ? trim( $row[4] ) : '';
						$status         = isset( $row[5] ) ? trim( strtolower( $row[5] ) ) : 'enable';

						$user = get_user_by( 'id', $id_or_slug );
						if ( $user ) {
							$tier_data[ $i ]['user']           = $id_or_slug;
							$tier_data[ $i ]['user_text']      = $user->user_nicename;
							$tier_data[ $i ]['min_qty']        = $min_qty;
							$tier_data[ $i ]['max_qty']        = $max_qty;
							$tier_data[ $i ]['discount_type']  = $discount_type;
							$tier_data[ $i ]['discount_value'] = $discount_value;
							if ( 'disable' == $status ) {
								$tier_data[ $i ]['disabled'] = 'true';
							}

							$i++;
						}

						if ( ob_get_level() > 0 ) {
							ob_end_flush();
						}
					}

					if ( ! empty( $tier_data ) ) {
						update_option( 'general_user_rules', $tier_data );
						echo 'success';
					} else {
						echo 'failed';
					}
				}

				if ( 'role' === $type ) {

					$i = 0;
					while ( $row = fgetcsv( $file_data ) ) {

						$id_or_slug     = isset( $row[0] ) ? $row[0] : 0;
						$min_qty        = isset( $row[1] ) ? trim( $row[1] ) : '';
						$max_qty        = isset( $row[2] ) ? trim( $row[2] ) : '&#8734;';
						$discount_type  = isset( $row[3] ) ? trim( strtolower( $row[3] ) ) : '';
						$discount_value = isset( $row[4] ) ? trim( $row[4] ) : '';
						$status         = isset( $row[5] ) ? trim( strtolower( $row[5] ) ) : 'enable';

						$editable_roles = get_editable_roles();

						if ( isset( $editable_roles[ $id_or_slug ] ) ) {
							$tier_data[ $i ]['role']           = $id_or_slug;
							$tier_data[ $i ]['role_text']      = $editable_roles[ $id_or_slug ]['name'];
							$tier_data[ $i ]['min_qty']        = $min_qty;
							$tier_data[ $i ]['max_qty']        = $max_qty;
							$tier_data[ $i ]['discount_type']  = $discount_type;
							$tier_data[ $i ]['discount_value'] = $discount_value;
							if ( 'disable' == $status ) {
								$tier_data[ $i ]['disabled'] = 'true';
							}

							$i++;
						}

						if ( ob_get_level() > 0 ) {
							ob_end_flush();
						}
					}

					if ( ! empty( $tier_data ) ) {
						update_option( 'general_role_rules', $tier_data );
						echo 'success';
					} else {
						echo 'failed';
					}
				}

				setcookie( 'csv_file_name', '', time() - 3600, '/' );
				setcookie( 'import_type', '', time() - 3600, '/' );

				if ( file_exists( $filepath . $filename ) ) {
					unlink( $filepath . $filename );
				}
			}
		}

		wp_die();
	}

	function wtp_import_data() {

		if ( ! isset( $_POST['_wtp_nonce'] ) && ! wp_verify_nonce( sanitize_text_field( $_POST['_wtp_nonce'] ), 'wtp_import_csv' ) ) {
			exit( 'Nonce verification failed!' );
		}		

		if ( isset( $_POST['wtp_type'] ) && 'wtp_import_csv' === sanitize_text_field( $_POST['wtp_type'] ) ) {

			$error = '';			

			if ( isset( $_POST['select_import_type'] ) && ! empty( sanitize_text_field( $_POST['select_import_type'] ) ) ) {
				if ( isset( $_FILES['wtp_import_csv']['name'] ) && '' != sanitize_text_field( $_FILES['wtp_import_csv']['name'] ) ) {
					$allowed_extension = array( 'csv' );
					$file_array        = explode( '.', sanitize_text_field( $_FILES['wtp_import_csv']['name'] ) );
					$extension         = end( $file_array );

					$maxAllowedSize = 5 * 1024 * 1024;

					if ( isset( $_FILES['wtp_import_csv']['size'] ) &&  $maxAllowedSize >= sanitize_text_field( $_FILES['wtp_import_csv']['size'] ) ) {

						if ( in_array( $extension, $allowed_extension ) ) {
							$new_file_name = rand() . '.' . $extension;
							setcookie( 'csv_file_name', $new_file_name, 0, '/' );
							setcookie( 'import_type', sanitize_text_field( $_POST['select_import_type'] ), 0, '/' );

							$filepath = WTP_ROOT_PATH . 'imports/';

							if ( ! file_exists( $filepath ) ) {
								mkdir( $filepath, 0777, true );
							}

							$filename_with_path = $filepath . $new_file_name;
							if ( isset( $_FILES['wtp_import_csv']['tmp_name'] ) ) {
								move_uploaded_file( sanitize_text_field( $_FILES['wtp_import_csv']['tmp_name'] ), $filename_with_path );
							}
							$file_content = file( $filename_with_path, FILE_SKIP_EMPTY_LINES );
						} else {
							$error = esc_html__( 'Only CSV file format is allowed', 'wtp' );
						}
					} else {
						$error = esc_html__( 'File size exceeded. Please upload file less than or equal to 5MB.', 'wtp' );
					}
				} else {
					$error = esc_html__( 'Please Select File', 'wtp' );
				}
			} else {
				$error = esc_html__( 'Please Select tier type to be imported.', 'wtp' );
			}

			if ( '' != $error ) {
				$output = array(
					'error' => $error,
				);
			} else {
				$output = array(
					'success'    => true,
				);
			}

			echo json_encode( $output );
			wp_die();
		}
	}

	function wtp_export_data() {		
		if ( ! isset( $_POST['_wtp_nonce'] ) && ! wp_verify_nonce( sanitize_text_field( $_POST['_wtp_nonce'] ), 'wtp_export_csv' ) ) {
			exit( 'Nonce verification failed!' );
		}

		if ( isset( $_POST['wtp_type'] ) && 'wtp_export_csv' === sanitize_text_field( $_POST['wtp_type'] ) ) {
			$error   = '';
			$success = '';

			if ( isset( $_POST['select_import_type'] ) && ! empty( sanitize_text_field( $_POST['select_import_type'] ) ) ) {

				if ( 'product' == sanitize_text_field( $_POST['select_import_type'] ) ) {

					$user_id = get_current_user_id();
					$check = get_user_meta( $user_id, 'wtp_export_run', true );

					if ( empty( $check ) ) {
						//running export for the first time
						$all_product_args = array(
							'post_type'   => array( 'product', 'product_variation' ),
							'numberposts' => -1,
							'fields'      => 'ids',
							'post_status' => 'publish',
						);
					} else {
						$all_product_args = array(
							'post_type'   => array( 'product', 'product_variation' ),
							'numberposts' => -1,
							'fields'      => 'ids',
							'post_status' => 'publish',
							'meta_query'  => array(
								array(
									'key'     => 'wtp_product_tier_set',
									'value'   => true,
									'compare' => '=',
								)
							)
						);
					}					

					$products = get_posts( $all_product_args );

					$data = array();

					if ( is_array( $products ) && count( $products ) > 0 ) {

						foreach ( $products as $ID ) {
							$product = wc_get_product( $ID );
							if ( 'variation' == $product->get_type() ) {
								$temp_data = get_post_meta( $ID, 'wtp_product_tier_setting_' . $ID, true );
								if ( isset( $temp_data['min_qty'] ) ) {
									foreach ( $temp_data['min_qty'] as $k => $v ) {
										array_push(
											$data,
											array(
												'ID'       => $ID,
												'min_qty'  => $temp_data['min_qty'][ $k ],
												'max_qty'  => $temp_data['max_qty'][ $k ],
												'discount_type' => $temp_data['discount_type'][ $k ],
												'discount_value' => $temp_data['discount_value'][ $k ],
												'disabled' => $temp_data['disabled'][ $k ],
											)
										);
									}

									if ( empty( $check ) ) {
										update_post_meta( $ID, 'wtp_product_tier_set', true );
									}
								}
							}

							if ( 'simple' == $product->get_type() ) {
								$temp_data = get_post_meta( $ID, 'wtp_product_tier_setting', true );
								if ( isset( $temp_data['min_qty'] ) ) {
									foreach ( $temp_data['min_qty'] as $k => $v ) {
										array_push(
											$data,
											array(
												'ID'       => $ID,
												'min_qty'  => $temp_data['min_qty'][ $k ],
												'max_qty'  => $temp_data['max_qty'][ $k ],
												'discount_type' => $temp_data['discount_type'][ $k ],
												'discount_value' => $temp_data['discount_value'][ $k ],
												'disabled' => $temp_data['disabled'][ $k ],
											)
										);
									}

									if ( empty( $check ) ) {
										update_post_meta( $ID, 'wtp_product_tier_set', true );
									}
								}
							}
						}
					}

					if ( ! empty( $data ) ) {

						//update that the export is ran atleast one time.
						update_user_meta( $user_id, 'wtp_export_run', true );

						// open the file "demosaved.csv" for writing
						$file_name = str_replace( ' ', '_', apply_filters( 'wtp_change_csv_name', 'wtp_' . sanitize_text_field( $_POST['select_import_type'] ) . '_tier_pricing.csv' ) );

						// tell the browser it's going to be a csv file
						header( 'Content-Type: text/csv; charset=UTF-8' );

						// tell the browser we want to save it instead of displaying it
						header( 'Content-Disposition: attachment; filename="' . $file_name . '";' );

						$filepath = WTP_ROOT_PATH . 'exports/';

						if ( ! file_exists( $filepath ) ) {
							mkdir( $filepath, 0777, true );
						} else {
							if ( file_exists( $filepath . $file_name ) ) {
								unlink( $filepath . $file_name );
							}
						}

						$output = fopen( $filepath . $file_name, 'a' );

						$header_column = array(
							esc_html__( 'Product ID/Variation ID', 'wtp' ),
							esc_html__( 'Minimum Quantity', 'wtp' ),
							esc_html__( 'Maximum Quantity', 'wtp' ),
							esc_html__( 'Discount Type', 'wtp' ),
							esc_html__( 'Discount Value', 'wtp' ),
							esc_html__( 'Status', 'wtp' ),
						);

						// save the column headers
						fputcsv( $output, $header_column );

						foreach ( $data as $k => $data ) {

							if ( isset( $data['disabled'] ) && 'true' == $data['disabled'] ) {
								$data['disabled'] = 'disable';
							} else {
								$data['disabled'] = 'enable';
							}

							fputcsv( $output, $data );
						}

						// reset the file pointer to the start of the file
						fseek( $output, 0 );

						fclose( $output );

						$url     = WTP_ROOT_URL . 'exports/' . $file_name;
						/* translators: %1$s and %2$s contains anchor tags*/
						$success = sprintf( esc_html__( 'Successfully exported. Click here to dowload the exported %1$sCSV file.%2$s', 'wtp' ), '<a href="' . esc_url( $url ) . '" target="_blank">', '</a>' );
					} else {
						$error = esc_html__( 'No data found for export.', 'wtp' );
					}
				}

				if ( 'category' == sanitize_text_field( $_POST['select_import_type'] ) ) {
					$data = get_option( 'general_category_rules', false );
					if ( false !== $data ) {

						// open the file "demosaved.csv" for writing
						$file_name = str_replace( ' ', '_', apply_filters( 'wtp_change_csv_name', 'wtp_' . sanitize_text_field( $_POST['select_import_type'] ) . '_tier_pricing.csv' ) );

						// tell the browser it's going to be a csv file
						header( 'Content-Type: text/csv; charset=UTF-8' );

						// tell the browser we want to save it instead of displaying it
						header( 'Content-Disposition: attachment; filename="' . $file_name . '";' );

						$filepath = WTP_ROOT_PATH . 'exports/';

						if ( ! file_exists( $filepath ) ) {
							mkdir( $filepath, 0777, true );
						} else {
							if ( file_exists( $filepath . $file_name ) ) {
								unlink( $filepath . $file_name );
							}
						}

						$output = fopen( $filepath . $file_name, 'a' );

						$header_column = array(
							esc_html__( 'Category (slug)', 'wtp' ), //0
							esc_html__( 'Minimum Quantity', 'wtp' ), //1
							esc_html__( 'Maximum Quantity', 'wtp' ), //2
							esc_html__( 'Discount Type', 'wtp' ), //3
							esc_html__( 'Discount Value', 'wtp' ), //4
							esc_html__( 'Status', 'wtp' ), //5
						);

						// save the column headers
						fputcsv( $output, $header_column );

						$temp_data = array();

						foreach ( $data as $k => $data ) {

							unset( $data['category_text'] );

							$temp_data['category'] = $data['category'];
							$temp_data['min_qty'] = $data['min_qty'];
							$temp_data['max_qty'] = $data['max_qty'];
							$temp_data['discount_type'] = $data['discount_type'];
							$temp_data['discount_value'] = $data['discount_value'];
							$temp_data['disabled'] = isset( $data['disabled'] ) && 'true' == $data['disabled'] ? 'disable' : 'enable';							

							fputcsv( $output, $temp_data );
						}

						// reset the file pointer to the start of the file
						fseek( $output, 0 );

						fclose( $output );

						$url     = WTP_ROOT_URL . 'exports/' . $file_name;
						/* translators: %1$s and %2$s contains anchor tags*/
						$success = sprintf( esc_html__( 'Successfully exported. Click here to dowload the exported %1$sCSV file.%2$s', 'wtp' ), '<a href="' . esc_url( $url ) . '" target="_blank">', '</a>' );
					} else {
						$error = esc_html__( 'No data found for export.', 'wtp' );
					}
				}

				if ( 'user' == sanitize_text_field( $_POST['select_import_type'] ) ) {
					$data = get_option( 'general_user_rules', false );
					if ( false !== $data ) {

						// open the file "demosaved.csv" for writing
						$file_name = str_replace( ' ', '_', apply_filters( 'wtp_change_csv_name', 'wtp_' . sanitize_text_field( $_POST['select_import_type'] ) . '_tier_pricing.csv' ) );

						// tell the browser it's going to be a csv file
						header( 'Content-Type: text/csv; charset=UTF-8' );

						// tell the browser we want to save it instead of displaying it
						header( 'Content-Disposition: attachment; filename="' . $file_name . '";' );

						$filepath = WTP_ROOT_PATH . 'exports/';

						if ( ! file_exists( $filepath ) ) {
							mkdir( $filepath, 0777, true );
						} else {
							if ( file_exists( $filepath . $file_name ) ) {
								unlink( $filepath . $file_name );
							}
						}

						$output = fopen( $filepath . $file_name, 'a' );

						$header_column = array(
							esc_html__( 'User ID', 'wtp' ),
							esc_html__( 'Minimum Quantity', 'wtp' ),
							esc_html__( 'Maximum Quantity', 'wtp' ),
							esc_html__( 'Discount Type', 'wtp' ),
							esc_html__( 'Discount Value', 'wtp' ),
							esc_html__( 'Status', 'wtp' ),
						);

						// save the column headers
						fputcsv( $output, $header_column );

						$temp_data = array();

						foreach ( $data as $k => $data ) {

							unset( $data['user_text'] );

							$temp_data['user'] = $data['user'];
							$temp_data['min_qty'] = $data['min_qty'];
							$temp_data['max_qty'] = $data['max_qty'];
							$temp_data['discount_type'] = $data['discount_type'];
							$temp_data['discount_value'] = $data['discount_value'];
							$temp_data['disabled'] = isset( $data['disabled'] ) && 'true' == $data['disabled'] ? 'disable' : 'enable';

							fputcsv( $output, $temp_data );
						}

						// reset the file pointer to the start of the file
						fseek( $output, 0 );

						fclose( $output );

						$url     = WTP_ROOT_URL . 'exports/' . $file_name;
						/* translators: %1$s and %2$s contains anchor tags*/
						$success = sprintf( esc_html__( 'Successfully exported. Click here to dowload the exported %1$sCSV file.%2$s', 'wtp' ), '<a href="' . esc_url( $url ) . '" target="_blank">', '</a>' );
					} else {
						$error = esc_html__( 'No data found for export.', 'wtp' );
					}
				}

				if ( 'role' == sanitize_text_field( $_POST['select_import_type'] ) ) {
					$data = get_option( 'general_role_rules', false );
					if ( false !== $data ) {

						// open the file "demosaved.csv" for writing
						$file_name = str_replace( ' ', '_', apply_filters( 'wtp_change_csv_name', 'wtp_' . sanitize_text_field( $_POST['select_import_type'] ) . '_tier_pricing.csv' ) );

						// tell the browser it's going to be a csv file
						header( 'Content-Type: text/csv; charset=UTF-8' );

						// tell the browser we want to save it instead of displaying it
						header( 'Content-Disposition: attachment; filename="' . $file_name . '";' );

						$filepath = WTP_ROOT_PATH . 'exports/';

						if ( ! file_exists( $filepath ) ) {
							mkdir( $filepath, 0777, true );
						} else {
							if ( file_exists( $filepath . $file_name ) ) {
								unlink( $filepath . $file_name );
							}
						}

						$output = fopen( $filepath . $file_name, 'a' );

						$header_column = array(
							esc_html__( 'Role (slug)', 'wtp' ),
							esc_html__( 'Minimum Quantity', 'wtp' ),
							esc_html__( 'Maximum Quantity', 'wtp' ),
							esc_html__( 'Discount Type', 'wtp' ),
							esc_html__( 'Discount Value', 'wtp' ),
							esc_html__( 'Status', 'wtp' ),
						);

						// save the column headers
						fputcsv( $output, $header_column );

						$temp_data = array();

						foreach ( $data as $k => $data ) {

							unset( $data['role_text'] );

							$temp_data['role'] = $data['role'];
							$temp_data['min_qty'] = $data['min_qty'];
							$temp_data['max_qty'] = $data['max_qty'];
							$temp_data['discount_type'] = $data['discount_type'];
							$temp_data['discount_value'] = $data['discount_value'];
							$temp_data['disabled'] = isset( $data['disabled'] ) && 'true' == $data['disabled'] ? 'disable' : 'enable';

							fputcsv( $output, $temp_data );
						}

						// reset the file pointer to the start of the file
						fseek( $output, 0 );

						fclose( $output );

						$url     = WTP_ROOT_URL . 'exports/' . $file_name;
						/* translators: %1$s and %2$s contains anchor tags*/
						$success = sprintf( esc_html__( 'Successfully exported. Click here to dowload the exported %1$sCSV file.%2$s', 'wtp' ), '<a href="' . esc_url( $url ) . '" target="_blank">', '</a>' );
					} else {
						$error = esc_html__( 'No data found for export.', 'wtp' );
					}
				}
			} else {
				$error = esc_html__( 'Please select tier type to be exported.', 'wtp' );
			}

			if ( '' != $error ) {
				$output = array(
					'error' => $error,
				);
			} else {
				$output = array(
					'success' => $success,
				);
			}

			echo json_encode( $output );
		}

		wp_die();

	}

	function wtp_general_rule_edit() {

		if ( isset( $_POST['tier_pricing_nonce'] ) && wp_verify_nonce( sanitize_text_field( $_POST['tier_pricing_nonce'] ), 'tier_pricing_nonce' ) ) {
			exit( 'Un Authorized!' );
		}

		$post = $_POST;

		$general_rule_id   = $post['rule_id'];
		$general_rule_type = $post['rule_type'];

		if ( 'category' == $general_rule_type ) {
			$general_category_rules = get_option( 'general_category_rules', false );
			if ( isset( $general_category_rules[ $general_rule_id ] ) ) {
				$general_category_rules[ $general_rule_id ]['category']       = $post['general_role_user_role'];
				$general_category_rules[ $general_rule_id ]['category_text']  = $post['general_role_user_role_text'];
				$general_category_rules[ $general_rule_id ]['min_qty']        = $post['general_role_min_quantity'];
				$general_category_rules[ $general_rule_id ]['max_qty']        = $post['general_role_max_quantity'];
				$general_category_rules[ $general_rule_id ]['discount_value'] = $post['general_role_discount_value'];
				$general_category_rules[ $general_rule_id ]['discount_type']  = $post['general_role_discount_type'];

				update_option( 'general_category_rules', $general_category_rules );
			}
		}

		if ( 'user' == $general_rule_type ) {
			$general_user_rules = get_option( 'general_user_rules', false );
			if ( isset( $general_user_rules[ $general_rule_id ] ) ) {
				$general_user_rules[ $general_rule_id ]['user']           = $post['general_role_user_role'];
				$general_user_rules[ $general_rule_id ]['user_text']      = $post['general_role_user_role_text'];
				$general_user_rules[ $general_rule_id ]['min_qty']        = $post['general_role_min_quantity'];
				$general_user_rules[ $general_rule_id ]['max_qty']        = $post['general_role_max_quantity'];
				$general_user_rules[ $general_rule_id ]['discount_value'] = $post['general_role_discount_value'];
				$general_user_rules[ $general_rule_id ]['discount_type']  = $post['general_role_discount_type'];

				update_option( 'general_user_rules', $general_user_rules );
			}
		}

		if ( 'role' == $general_rule_type ) {
			$general_role_rules = get_option( 'general_role_rules', false );
			if ( isset( $general_role_rules[ $general_rule_id ] ) ) {
				$general_role_rules[ $general_rule_id ]['role']           = $post['general_role_user_role'];
				$general_role_rules[ $general_rule_id ]['role_text']      = $post['general_role_user_role_text'];
				$general_role_rules[ $general_rule_id ]['min_qty']        = $post['general_role_min_quantity'];
				$general_role_rules[ $general_rule_id ]['max_qty']        = $post['general_role_max_quantity'];
				$general_role_rules[ $general_rule_id ]['discount_value'] = $post['general_role_discount_value'];
				$general_role_rules[ $general_rule_id ]['discount_type']  = $post['general_role_discount_type'];

				update_option( 'general_role_rules', $general_role_rules );
			}
		}

		wp_die( 'done' );
	}

	add_action( 'init', 'wtp_load_frontned_files' );
	function wtp_load_frontned_files() {
		include_once WTP_ROOT_PATH . 'woo-tier-pricing-frontend.php';
	}

	add_action( 'wp_ajax_wtp_toggle', 'wtp_toggle' );
	function wtp_toggle() {

		if ( isset( $_POST['tier_pricing_nonce'] ) && wp_verify_nonce( sanitize_text_field( $_POST['tier_pricing_nonce'] ), 'tier_pricing_nonce' ) ) {
			exit( 'Un Authorized!' );
		}

		$post = $_POST;

		if ( isset( $post['general_tier_pricing'] ) && 'true' === $post['general_tier_pricing'] ) {
			update_option( 'wc_settings_enable_tier_pricing', 'yes' );
		} else {
			update_option( 'wc_settings_enable_tier_pricing', 'no' );
		}

		wp_die( 'done' );
	}


	add_filter( 'woocommerce_product_data_tabs', 'wk_custom_product_tab', 10, 1 );
	function wk_custom_product_tab( $default_tabs ) {
		$default_tabs['woo_tier_pricing'] = array(
			'label'    => __( 'Tier Pricing', 'domain' ),
			'target'   => 'wtp_tier_pricing',
			'priority' => 60,
			'class'    => array(
				'show_if_simple',
			),
		);
		return $default_tabs;
	}



	/** Adding tier product settings to product tabs*/
	add_action( 'woocommerce_product_data_panels', 'woo_tier_pricing' );

	function woo_tier_pricing() {
		require_once WTP_ROOT_PATH . '/product/admin/product_data_tab_content.php';
	}

	add_action( 'save_post', 'wtp_product_data_tab_content_save' );

	function wtp_product_data_tab_content_save( $post_id ) {

		global $post;

		if ( isset( $_POST['woocommerce_meta_nonce'] ) && ! wp_verify_nonce( sanitize_key( $_POST['woocommerce_meta_nonce'] ), 'woocommerce_save_data' ) ) {
			wp_die( 'Not Authorized!' );
		}

		if ( isset( $_POST['post_type'] ) && 'product' == sanitize_text_field( $_POST['post_type'] ) ) {
			$product_data = wc_get_product( $post );
			if ( ! ( $product_data->is_type( 'variable' ) || $product_data->is_type( 'variable-subscription' ) ) ) {
				if ( isset( $_POST['wtp_product_tier_setting'] ) ) {
					$data = $_POST;
					update_post_meta( $post_id, 'wtp_product_tier_setting', $data['wtp_product_tier_setting'] );
					update_post_meta( $post_id, 'wtp_product_tier_set', true );
				} else {
					update_post_meta( $post_id, 'wtp_product_tier_setting', '' );
					update_post_meta( $post_id, 'wtp_product_tier_set', false );
				}
			}
		}
	}


	// variable product
	add_action( 'woocommerce_variation_options', 'wtp_product_variation_data_tab_content', 10, 3 );
	function wtp_product_variation_data_tab_content( $loop, $variation_data, $variation ) {
		require WTP_ROOT_PATH . '/product/admin/product_data_variation_tab_content.php';
	}

	// save variation
	add_action( 'woocommerce_save_product_variation', 'wtp_product_variation_data_tab_content_save', 10, 2 );
	function wtp_product_variation_data_tab_content_save( $post_id ) {
		global $post;

		if ( isset( $_POST['woocommerce_meta_nonce'] ) && ! wp_verify_nonce( sanitize_key( $_POST['woocommerce_meta_nonce'] ), 'woocommerce_save_data' ) ) {
			wp_die( 'Not Authorized!' );
		}

		$data = $_POST;

		if ( isset( $data['product-type'] ) && 'variable' == $data['product-type'] ) {
			if ( isset( $data['variable_post_id'] ) ) {
				$variable_post_id = $data['variable_post_id'];
				// echo '<pre>';
				// print_r( $variable_post_id );
				// echo '</pre>';

				foreach ( $variable_post_id as $key => $variation_id ) {
					if ( isset( $data[ 'wtp_product_tier_setting_' . $variation_id ] ) ) {

						// echo '<pre>';
						// print_r( $data['wtp_product_tier_setting_' . $variation_id] );
						// echo '</pre>';

						update_post_meta( $variation_id, 'wtp_product_tier_setting_' . $variation_id, $data[ 'wtp_product_tier_setting_' . $variation_id ] );
						update_post_meta( $variation_id, 'wtp_product_tier_set', true );
					} else {
						update_post_meta( $variation_id, 'wtp_product_tier_setting_' . $variation_id, '' );
						update_post_meta( $variation_id, 'wtp_product_tier_set', false );
					}
				}
			}
		}
	}

	function wtp_general_role_rule() {

		$general_role_rules = get_option( 'general_role_rules', false );

		if ( isset( $_POST['tier_pricing_nonce'] ) && wp_verify_nonce( sanitize_text_field( $_POST['tier_pricing_nonce'] ), 'tier_pricing_nonce' ) ) {
			exit( 'Un Authorized!' );
		}

		$post = $_POST;

		if ( isset( $post['rule_id'] ) && '' != $post['rule_id'] ) {

			$general_rule_id = $post['rule_id'];

			if ( isset( $general_role_rules[ $general_rule_id ] ) ) {
				$general_role_rules[ $general_rule_id ]['role']           = $post['general_role_user_role'];
				$general_role_rules[ $general_rule_id ]['role_text']      = $post['general_role_user_role_text'];
				$general_role_rules[ $general_rule_id ]['min_qty']        = $post['general_role_min_quantity'];
				$general_role_rules[ $general_rule_id ]['max_qty']        = $post['general_role_max_quantity'];
				$general_role_rules[ $general_rule_id ]['discount_value'] = $post['general_role_discount_value'];
				$general_role_rules[ $general_rule_id ]['discount_type']  = $post['general_role_discount_type'];

				update_option( 'general_role_rules', $general_role_rules );
			} else {
				$general_role_rule = array(
					'role'           => $post['general_role_user_role'],
					'role_text'      => $post['general_role_user_role_text'],
					'min_qty'        => $post['general_role_min_quantity'],
					'max_qty'        => $post['general_role_max_quantity'],
					'discount_value' => $post['general_role_discount_value'],
					'discount_type'  => $post['general_role_discount_type'],
				);

				array_push( $general_role_rules, $general_role_rule );
				update_option( 'general_role_rules', $general_role_rules );
			}

			$result = get_option( 'general_role_rules', false );
			echo json_encode( $result );
			wp_die();
		}

		if ( false === $general_role_rules ) {

			$general_role_rule[0] = array(

				'role'           => $post['general_role_user_role'],
				'role_text'      => $post['general_role_user_role_text'],
				'min_qty'        => $post['general_role_min_quantity'],
				'max_qty'        => $post['general_role_max_quantity'],
				'discount_value' => $post['general_role_discount_value'],
				'discount_type'  => $post['general_role_discount_type'],

			);

			update_option( 'general_role_rules', $general_role_rule );
		}

		if ( false !== $general_role_rules ) {

			$general_role_rule = array(
				'role'           => $post['general_role_user_role'],
				'role_text'      => $post['general_role_user_role_text'],
				'min_qty'        => $post['general_role_min_quantity'],
				'max_qty'        => $post['general_role_max_quantity'],
				'discount_value' => $post['general_role_discount_value'],
				'discount_type'  => $post['general_role_discount_type'],

			);

			array_push( $general_role_rules, $general_role_rule );
			update_option( 'general_role_rules', $general_role_rules );
		}

		$result = get_option( 'general_role_rules', false );

		echo json_encode( $result );
		wp_die();

	}

	function wtp_general_role_rule_delete() {

		if ( isset( $_POST['tier_pricing_nonce'] ) && wp_verify_nonce( sanitize_text_field( $_POST['tier_pricing_nonce'] ), 'tier_pricing_nonce' ) ) {
			exit( 'Un Authorized!' );
		}

		$post = $_POST;

		$general_role_rule_id = $post['general_role_rule_id'];
		$general_role_rules   = get_option( 'general_role_rules', false );

		if ( isset( $general_role_rules[ $general_role_rule_id ] ) ) {
			unset( $general_role_rules[ $general_role_rule_id ] );
		}

		update_option( 'general_role_rules', $general_role_rules );

		wp_die( 'done' );
	}

	function wtp_general_role_rule_disable() {

		if ( isset( $_POST['tier_pricing_nonce'] ) && wp_verify_nonce( sanitize_text_field( $_POST['tier_pricing_nonce'] ), 'tier_pricing_nonce' ) ) {
			exit( 'Un Authorized!' );
		}

		$post = $_POST;

		$general_role_rule_id = $post['general_role_rule_id'];

		$general_role_rules = get_option( 'general_role_rules', false );

		if ( isset( $general_role_rules[ $general_role_rule_id ] ) ) {
			$general_role_rules[ $general_role_rule_id ]['disabled'] = 'true';
		}

		update_option( 'general_role_rules', $general_role_rules );

		wp_die( 'done' );
	}

	function wtp_general_role_rule_enable() {

		if ( isset( $_POST['tier_pricing_nonce'] ) && wp_verify_nonce( sanitize_text_field( $_POST['tier_pricing_nonce'] ), 'tier_pricing_nonce' ) ) {
			exit( 'Un Authorized!' );
		}

		$post = $_POST;

		$general_role_rule_id = $post['general_qty_rule_id'];
		$general_role_rules   = get_option( 'general_role_rules', false );
		// echo '<pre>init';
		// print_r($general_role_rules);
		// echo '</pre>';
		if ( isset( $general_role_rules[ $general_role_rule_id ]['disabled'] ) ) {
			// echo '<pre>before';
			// print_r($general_role_rules);
			// echo '</pre>';
			unset( $general_role_rules[ $general_role_rule_id ]['disabled'] );
			// echo '<pre>after';
			// print_r($general_role_rules);
			// echo '</pre>';
		}
		update_option( 'general_role_rules', $general_role_rules );
		wp_die( 'done' );
	}


	function wtp_general_category_rule() {

		$general_category_rules = get_option( 'general_category_rules', false );

		if ( isset( $_POST['tier_pricing_nonce'] ) && wp_verify_nonce( sanitize_text_field( $_POST['tier_pricing_nonce'] ), 'tier_pricing_nonce' ) ) {
			exit( 'Un Authorized!' );
		}

		$post = $_POST;

		if ( isset( $post['rule_id'] ) && '' != $post['rule_id'] ) {

			$general_rule_id = $post['rule_id'];

			if ( isset( $general_category_rules[ $general_rule_id ] ) ) {
				$general_category_rules[ $general_rule_id ]['category']       = $post['general_category_user_category'];
				$general_category_rules[ $general_rule_id ]['category_text']  = $post['general_category_user_category_text'];
				$general_category_rules[ $general_rule_id ]['min_qty']        = $post['general_category_min_quantity'];
				$general_category_rules[ $general_rule_id ]['max_qty']        = $post['general_category_max_quantity'];
				$general_category_rules[ $general_rule_id ]['discount_value'] = $post['general_category_discount_value'];
				$general_category_rules[ $general_rule_id ]['discount_type']  = $post['general_category_discount_type'];

				update_option( 'general_category_rules', $general_category_rules );
			} else {
				$general_category_rule = array(

					'category'       => $post['general_category_user_category'],
					'category_text'  => $post['general_category_user_category_text'],
					'min_qty'        => $post['general_category_min_quantity'],
					'max_qty'        => $post['general_category_max_quantity'],
					'discount_value' => $post['general_category_discount_value'],
					'discount_type'  => $post['general_category_discount_type'],

				);

				array_push( $general_category_rules, $general_category_rule );
				update_option( 'general_category_rules', $general_category_rules );
			}

			$result = get_option( 'general_category_rules', false );
			echo json_encode( $result );
			wp_die();
		}

		if ( false === $general_category_rules ) {

			$general_category_rule[0] = array(

				'category'       => $post['general_category_user_category'],
				'category_text'  => $post['general_category_user_category_text'],
				'min_qty'        => $post['general_category_min_quantity'],
				'max_qty'        => $post['general_category_max_quantity'],
				'discount_value' => $post['general_category_discount_value'],
				'discount_type'  => $post['general_category_discount_type'],

			);

			update_option( 'general_category_rules', $general_category_rule );
		}

		if ( false !== $general_category_rules ) {

			$general_category_rule = array(

				'category'       => $post['general_category_user_category'],
				'category_text'  => $post['general_category_user_category_text'],
				'min_qty'        => $post['general_category_min_quantity'],
				'max_qty'        => $post['general_category_max_quantity'],
				'discount_value' => $post['general_category_discount_value'],
				'discount_type'  => $post['general_category_discount_type'],

			);

			array_push( $general_category_rules, $general_category_rule );
			update_option( 'general_category_rules', $general_category_rules );
		}

		$result = get_option( 'general_category_rules', false );

		echo json_encode( $result );
		wp_die();

	}

	function wtp_general_category_rule_delete() {

		if ( isset( $_POST['tier_pricing_nonce'] ) && wp_verify_nonce( sanitize_text_field( $_POST['tier_pricing_nonce'] ), 'tier_pricing_nonce' ) ) {
			exit( 'Un Authorized!' );
		}

		$post = $_POST;

		$general_category_rule_id = $post['general_category_rule_id'];
		$general_category_rules   = get_option( 'general_category_rules', false );

		if ( isset( $general_category_rules[ $general_category_rule_id ] ) ) {
			unset( $general_category_rules[ $general_category_rule_id ] );
		}

		update_option( 'general_category_rules', $general_category_rules );
		wp_die( 'done' );
	}

	function wtp_general_category_rule_disable() {

		if ( isset( $_POST['tier_pricing_nonce'] ) && wp_verify_nonce( sanitize_text_field( $_POST['tier_pricing_nonce'] ), 'tier_pricing_nonce' ) ) {
			exit( 'Un Authorized!' );
		}

		$post = $_POST;

		$general_category_rule_id = $post['general_category_rule_id'];
		$general_category_rules   = get_option( 'general_category_rules', false );

		if ( isset( $general_category_rules[ $general_category_rule_id ] ) ) {
			$general_category_rules[ $general_category_rule_id ]['disabled'] = 'true';
		}

		update_option( 'general_category_rules', $general_category_rules );
		wp_die( 'done' );
	}

	function wtp_general_category_rule_enable() {

		if ( isset( $_POST['tier_pricing_nonce'] ) && wp_verify_nonce( sanitize_text_field( $_POST['tier_pricing_nonce'] ), 'tier_pricing_nonce' ) ) {
			exit( 'Un Authorized!' );
		}

		$post = $_POST;

		$general_category_rule_id = $post['general_category_rule_id'];
		$general_category_rules   = get_option( 'general_category_rules', false );

		if ( $general_category_rules[ $general_category_rule_id ]['disabled'] ) {
			unset( $general_category_rules[ $general_category_rule_id ]['disabled'] );
		}

		update_option( 'general_category_rules', $general_category_rules );
		wp_die( 'done' );
	}


	function wtp_user_discount_rule() {

		$general_user_rules = get_option( 'general_user_rules', false );

		if ( isset( $_POST['tier_pricing_nonce'] ) && wp_verify_nonce( sanitize_text_field( $_POST['tier_pricing_nonce'] ), 'tier_pricing_nonce' ) ) {
			exit( 'Un Authorized!' );
		}

		$post = $_POST;

		if ( isset( $post['rule_id'] ) && '' != $post['rule_id'] ) {

			$general_rule_id = $post['rule_id'];

			if ( isset( $general_user_rules[ $general_rule_id ] ) ) {
				$general_user_rules[ $general_rule_id ]['user']           = $post['general_user_discount'];
				$general_user_rules[ $general_rule_id ]['user_text']      = $post['general_user_discount_text'];
				$general_user_rules[ $general_rule_id ]['min_qty']        = $post['general_user_min_quantity'];
				$general_user_rules[ $general_rule_id ]['max_qty']        = $post['general_user_max_quantity'];
				$general_user_rules[ $general_rule_id ]['discount_value'] = $post['general_user_discount_value'];
				$general_user_rules[ $general_rule_id ]['discount_type']  = $post['general_user_discount_type'];

				update_option( 'general_user_rules', $general_user_rules );
			} else {
				$general_user_rule = array(
					'user'           => $post['general_user_discount'],
					'user_text'      => $post['general_user_discount_text'],
					'min_qty'        => $post['general_user_min_quantity'],
					'max_qty'        => $post['general_user_max_quantity'],
					'discount_value' => $post['general_user_discount_value'],
					'discount_type'  => $post['general_user_discount_type'],
				);

				array_push( $general_user_rules, $general_user_rule );
				update_option( 'general_user_rules', $general_user_rules );
			}

			$result = get_option( 'general_user_rules', false );
			echo json_encode( $result );
			wp_die();
		}

		if ( false === $general_user_rules ) {

			$general_user_rule[0] = array(

				'user'           => $post['general_user_discount'],
				'user_text'      => $post['general_user_discount_text'],
				'min_qty'        => $post['general_user_min_quantity'],
				'max_qty'        => $post['general_user_max_quantity'],
				'discount_value' => $post['general_user_discount_value'],
				'discount_type'  => $post['general_user_discount_type'],

			);

			update_option( 'general_user_rules', $general_user_rule );
		}

		if ( false !== $general_user_rules ) {

			$general_user_rule = array(

				'user'           => $post['general_user_discount'],
				'user_text'      => $post['general_user_discount_text'],
				'min_qty'        => $post['general_user_min_quantity'],
				'max_qty'        => $post['general_user_max_quantity'],
				'discount_value' => $post['general_user_discount_value'],
				'discount_type'  => $post['general_user_discount_type'],

			);

			array_push( $general_user_rules, $general_user_rule );
			update_option( 'general_user_rules', $general_user_rules );
		}

		$result = get_option( 'general_user_rules', false );
		echo json_encode( $result );
		wp_die();

	}

	function wtp_general_user_rule_delete() {

		if ( isset( $_POST['tier_pricing_nonce'] ) && wp_verify_nonce( sanitize_text_field( $_POST['tier_pricing_nonce'] ), 'tier_pricing_nonce' ) ) {
			exit( 'Un Authorized!' );
		}

		$post = $_POST;

		$general_user_rule_id = $post['general_user_rule_id'];
		$general_user_rules   = get_option( 'general_user_rules', false );

		if ( isset( $general_user_rules[ $general_user_rule_id ] ) ) {
			unset( $general_user_rules[ $general_user_rule_id ] );
		}

		update_option( 'general_user_rules', $general_user_rules );

		wp_die( 'done' );
	}

	function wtp_general_user_rule_disable() {

		if ( isset( $_POST['tier_pricing_nonce'] ) && wp_verify_nonce( sanitize_text_field( $_POST['tier_pricing_nonce'] ), 'tier_pricing_nonce' ) ) {
			exit( 'Un Authorized!' );
		}

		$post = $_POST;

		$general_user_rule_id = $post['general_user_rule_id'];
		$general_user_rules   = get_option( 'general_user_rules', false );

		if ( isset( $general_user_rules[ $general_user_rule_id ] ) ) {
			$general_user_rules[ $general_user_rule_id ]['disabled'] = 'true';
		}

		update_option( 'general_user_rules', $general_user_rules );

		wp_die( 'done' );

	}

	function wtp_general_user_rule_enable() {

		if ( isset( $_POST['tier_pricing_nonce'] ) && wp_verify_nonce( sanitize_text_field( $_POST['tier_pricing_nonce'] ), 'tier_pricing_nonce' ) ) {
			exit( 'Un Authorized!' );
		}

		$post = $_POST;

		$general_user_rule_id = $post['general_user_rule_id'];
		$general_user_rules   = get_option( 'general_user_rules', false );

		if ( isset( $general_user_rules[ $general_user_rule_id ]['disabled'] ) ) {
			unset( $general_user_rules[ $general_user_rule_id ]['disabled'] );
		}

		update_option( 'general_user_rules', $general_user_rules );
		wp_die( 'done' );
	}


	function wtp_setup() {

		load_plugin_textdomain( 'wtp', false, basename( dirname( __FILE__ ) ) . '/languages/' );

		function my_plugin_add_settings( $settings ) {
			$settings[] = include WTP_ROOT_PATH . 'wtp-initial-setup.php';
			return $settings;
		}
		add_filter( 'woocommerce_get_settings_pages', 'my_plugin_add_settings' );

	}

	function wtp_enqueue_scripts( $hook ) {

		global $post;
		if ( ( isset( $_GET['tab'] ) && 'tier_pricing' === sanitize_text_field( $_GET['tab'] ) ) || ( isset( $post ) && 'product' === $post->post_type ) ) {
			wp_enqueue_style( 'wtp-admin-style', WTP_ROOT_URL . 'assets/css/admin_style.css', array(), '1.0.0&t=' . gmdate( 'h:i:s' ) );

			$params = array(
				'ajaxurl'             => admin_url( 'admin-ajax.php' ),
				'enable_txt'          => esc_html__( 'Enable', 'wtp' ),
				'disable_txt'         => esc_html__( 'Disable', 'wtp' ),
				'delete_txt'          => esc_html__( 'Delete', 'wtp' ),
				'edit_txt'            => esc_html__( 'Edit', 'wtp' ),
				'minimum_txt'         => esc_html__( 'Minimum Quantity', 'wtp' ),
				'maximum_txt'         => esc_html__( 'Maximum Quantity', 'wtp' ),
				'dis_type_txt'        => esc_html__( 'Discount Type', 'wtp' ),
				'dis_val_txt'         => esc_html__( 'Discount Value', 'wtp' ),
				'visibility_txt'      => esc_html__( 'Status', 'wtp' ),
				'percentage_txt'      => esc_html__( 'Percentage', 'wtp' ),
				'fixed_txt'           => esc_html__( 'Fixed', 'wtp' ),
				'desc_txt'            => esc_html__( 'Add price discount range here.', 'wtp' ),
				'required_txt'        => esc_html__( '* Required field', 'wtp' ),
				'image_url'           => WTP_ROOT_URL,
				'sample_csv_product'  => WTP_ROOT_URL . 'settings/sample/sample-product.csv',
				'sample_csv_category' => WTP_ROOT_URL . 'settings/sample/sample-category.csv',
				'sample_csv_user'     => WTP_ROOT_URL . 'settings/sample/sample-user.csv',
				'sample_csv_role'     => WTP_ROOT_URL . 'settings/sample/sample-role.csv',
			);

			wp_register_script( 'wtp-admin-script', WTP_ROOT_URL . 'assets/js/settings.js', array( 'jquery' ), '1.0.0&t=' . gmdate( 'h:i:s' ), true );
			wp_localize_script( 'wtp-admin-script', 'wtp_admin_script', $params );
			wp_enqueue_script( 'wtp-admin-script' );
		}

	}


	function wtp_add_user_role() {

		$new_user_roles = get_option( 'wtp_new_user_roles', false );

		if ( isset( $_POST['tier_pricing_nonce'] ) && wp_verify_nonce( sanitize_text_field( $_POST['tier_pricing_nonce'] ), 'tier_pricing_nonce' ) ) {
			exit( 'Un Authorized!' );
		}

		$post = $_POST;

		if ( false === $new_user_roles ) {
			$new_user_role[0] = array(
				'user_role' => $post['new_user_role'],
			);

			$status = wtp_create_role( $post['new_user_role'] );
			if ( $status ) {
				update_option( 'wtp_new_user_roles', $new_user_role );
			}
		}

		if ( false !== $new_user_roles ) {
			$new_user_role = array(
				'user_role' => $post['new_user_role'],
			);

			$status = wtp_create_role( $post['new_user_role'] );
			if ( $status ) {
				array_push( $new_user_roles, $new_user_role );
				update_option( 'wtp_new_user_roles', $new_user_roles );
			}
		}

		$new_user_roles = get_option( 'wtp_new_user_roles', false );
		// $id = isset( $new_user_roles[] )
		echo json_encode( $new_user_roles );

		wp_die();

	}

	function wtp_create_role( $role, $divider = '-' ) {

		$display_name = $role;
		// replace non letter or digits by divider
		$role = preg_replace( '~[^\pL\d]+~u', $divider, $role );

		// transliterate
		$role = iconv( 'utf-8', 'us-ascii//TRANSLIT', $role );

		// remove unwanted characters
		$role = preg_replace( '~[^-\w]+~', '', $role );

		// trim
		$role = trim( $role, $divider );

		// remove duplicate divider
		$role = preg_replace( '~-+~', $divider, $role );

		// lowercase
		$role = strtolower( $role );

		if ( empty( $role ) ) {
			return 'n-a';
		}

		return add_role( $role, $display_name );
	}

	function wtp_delete_role( $role, $divider = '-' ) {
		// replace non letter or digits by divider
		$role = preg_replace( '~[^\pL\d]+~u', $divider, $role );

		// transliterate
		$role = iconv( 'utf-8', 'us-ascii//TRANSLIT', $role );

		// remove unwanted characters
		$role = preg_replace( '~[^-\w]+~', '', $role );

		// trim
		$role = trim( $role, $divider );

		// remove duplicate divider
		$role = preg_replace( '~-+~', $divider, $role );

		// lowercase
		$role = strtolower( $role );

		if ( empty( $role ) ) {
			return 'n-a';
		}

		// die( $role );
		return remove_role( $role );
	}

	function wtp_user_role_delete() {

		if ( isset( $_POST['tier_pricing_nonce'] ) && wp_verify_nonce( sanitize_text_field( $_POST['tier_pricing_nonce'] ), 'tier_pricing_nonce' ) ) {
			exit( 'Un Authorized!' );
		}

		$post = $_POST;

		$user_role_id   = $post['user_role_id'];
		$new_user_roles = get_option( 'wtp_new_user_roles', false );
		// $new_user_roles = array_reverse( $new_user_roles );
		$role = isset( $new_user_roles[ $user_role_id ] ) ? $new_user_roles[ $user_role_id ] : '';
		if ( '' != $role['user_role'] ) {
			wtp_delete_role( $role['user_role'] );
		}
		unset( $new_user_roles[ $user_role_id ] );
		update_option( 'wtp_new_user_roles', $new_user_roles );
		wp_die( 'done' );
	}

	function wtp_price( $price, $args = array() ) {
		extract(
			apply_filters(
				'wc_price_args',
				wp_parse_args(
					$args,
					array(
						'ex_tax_label'       => false,
						'currency'           => '',
						'decimal_separator'  => wc_get_price_decimal_separator(),
						'thousand_separator' => wc_get_price_thousand_separator(),
						'decimals'           => wc_get_price_decimals(),
						'price_format'       => get_woocommerce_price_format(),
					)
				)
			)
		);

		$negative = $price < 0;
		$price    = apply_filters( 'raw_woocommerce_price', floatval( $negative ? $price * -1 : $price ) );
		$price    = apply_filters( 'formatted_woocommerce_price', number_format( $price, $decimals, $decimal_separator, $thousand_separator ), $price, $decimals, $decimal_separator, $thousand_separator );

		if ( apply_filters( 'woocommerce_price_trim_zeros', false ) && $decimals > 0 ) {
			$price = wc_trim_zeros( $price );
		}

		$formatted_price = ( $negative ? '-' : '' ) . sprintf( $price_format, '<span class="woocommerce-Price-currencySymbol">' . get_woocommerce_currency_symbol( $currency ) . '</span>', $price );
		$return          = '<span class="woocommerce-Price-amount amount">' . $formatted_price . '</span>';

		if ( $ex_tax_label && wc_tax_enabled() ) {
			$return .= ' <small class="woocommerce-Price-taxLabel tax_label">' . WC()->countries->ex_tax_or_vat() . '</small>';
		}

		return apply_filters( 'wc_price', $return, $price, $args );
	}
} else {
	/**
	 * Notice for admin
	 */
	add_action( 'admin_notices', 'wtp_inactive_plugin_notice' );

	/**
	 * Inactive plugin notice.
	 */
	function wtp_inactive_plugin_notice() {
		$class    = 'notice notice-error';
		$headline = __( 'Woocommerce Tier Pricing requires WooCommerce to be install & active.', 'wtp' );
		$message  = __( 'Go to the plugins page to activate WooCommerce', 'wtp' );
		printf( '<div class="%1$s"><h2>%2$s</h2><p>%3$s</p></div>', esc_attr( $class ), esc_html( $headline ), esc_html( $message ) );
	}
}
