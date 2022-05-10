<?php

class WTPInitialSetup extends WC_Settings_Page {

	public function __construct() {

		$this->id = 'tier_pricing';

		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_tab' ), 50 );
		add_action( 'woocommerce_sections_' . $this->id, array( $this, 'output_sections' ) );
		add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output' ) );
		add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );

		add_action( 'woocommerce_admin_field_table_general', array( $this, 'add_admin_field_table_general' ) );
		add_action( 'woocommerce_admin_field_table_role', array( $this, 'add_admin_field_table_role' ) );
		add_action( 'woocommerce_admin_field_table_user', array( $this, 'add_admin_field_table_user' ) );
		add_action( 'woocommerce_admin_field_table_category', array( $this, 'add_admin_field_table_category' ) );
		add_action( 'woocommerce_admin_field_user_roles_management', array( $this, 'add_admin_field_user_roles_management' ) );
		add_action( 'woocommerce_admin_field_add_general_map_button', array( $this, 'add_general_map_button' ) );
		add_action( 'woocommerce_admin_field_add_role_map_button', array( $this, 'add_role_map_button' ) );
		add_action( 'woocommerce_admin_field_add_user_map_button', array( $this, 'add_user_map_button' ) );
		add_action( 'woocommerce_admin_field_add_category_map_button', array( $this, 'add_category_map_button' ) );
		add_action( 'admin_init', array( $this, 'get_user_roles' ) );
	}

	public function add_settings_tab( $settings_tabs ) {
		$settings_tabs[ $this->id ] = __( 'Tier Pricing', 'wtp' );
		return $settings_tabs;
	}

	public function get_sections() {
		$sections = array(
			'wtp_general_discount_options'   => __( 'General Settings', 'wtp' ),
			'wtp_general_category_discounts' => __( 'Category Based Discount', 'wtp' ),
			'wtp_general_user_discounts'     => __( 'User Based Discount', 'wtp' ),
			'wtp_general_role_discounts'     => __( 'Role Based Discount', 'wtp' ),
			'wtp_customization'              => __( 'Customization', 'wtp' ),
			'wtp_price_display_setting'      => __( 'Price Display Settings', 'wtp' ),
			'wtp_import_and_export'          => __( 'Import & Export', 'wtp' ),
		);
		return apply_filters( 'woocommerce_get_sections_' . $this->id, $sections );
	}


	public function get_settings( $section = 'wtp_general_discount_options' ) {

		if ( isset( $_GET['section'] ) && ! empty( $_GET['section'] ) ) {
			$section = sanitize_text_field( $_GET['section'] );
		}

		switch ( $section ) {

			case 'wtp_general_discount_options':
				$settings = include WTP_ROOT_PATH . 'settings/general-options-settings.php';
				break;
			case 'wtp_general_role_discounts':
				$settings = include WTP_ROOT_PATH . 'settings/general-role-discounts.php';
				break;
			case 'wtp_general_category_discounts':
				$settings = include WTP_ROOT_PATH . 'settings/general-category-discounts.php';
				break;
			case 'wtp_general_user_discounts':
				$settings = include WTP_ROOT_PATH . 'settings/general-user-discounts.php';
				break;
			case 'wtp_customization':
				$settings = include WTP_ROOT_PATH . 'settings/customization.php';
				break;
			case 'wtp_price_display_setting':
				$settings = include WTP_ROOT_PATH . 'settings/price-display-settings.php';
				break;
			case 'wtp_import_and_export':
				$settings = include WTP_ROOT_PATH . 'settings/import-and-export.php';
				break;
			default:
				$settings = include WTP_ROOT_PATH . 'settings/general-options-settings.php';
				break;
		}

		return apply_filters( 'wc_settings_tab_' . $this->id, $settings, $section );

	}

	public function add_admin_field_table_role( $value ) {
		?>
		<table class="wc-shipping-classes widefat">
			<thead>
				<tr>
					<th class=""><?php echo esc_html__( 'User Role', 'wtp' ); ?></th>
					<th class=""><?php echo esc_html__( 'Minimum Qty', 'wtp' ); ?></th>
					<th class=""><?php echo esc_html__( 'Maximum Qty', 'wtp' ); ?></th>					
					<th class=""><?php echo esc_html__( 'Discount Value', 'wtp' ); ?></th>
					<th class=""><?php echo esc_html__( 'Discount Type', 'wtp' ); ?></th>
					<th class=""><?php echo esc_html__( 'Action', 'wtp' ); ?></th>
				</tr>
			</thead>
			<tbody class="wc-shipping-class-rows" id="wtp-user-role-discounts">
				<?php
				$general_role_rules = get_option( 'general_role_rules', false );
				if ( $general_role_rules ) {
					foreach ( $general_role_rules as $key => $value ) {

						if ( '' === $value['max_qty'] ) {
							$max_qty = '&#8734;';
						} else {
							$max_qty = $value['max_qty'];
						}

						echo '<tr>';
						echo wp_kses_post( '<td class="wc-shipping-classes-blank-state col-1" data-value="' . $value['role'] . '" colspan="">' . $value['role_text'] . '</td>' );
						echo wp_kses_post( '<td class="wc-shipping-classes-blank-state col-2" colspan="">' . $value['min_qty'] . '</td>' );
						echo wp_kses_post( '<td class="wc-shipping-classes-blank-state col-3" colspan="">' . $max_qty . '</td>' );
						echo wp_kses_post( '<td class="wc-shipping-classes-blank-state col-4" colspan="">' . $value['discount_value'] . '</td>' );
						echo wp_kses_post( '<td class="wc-shipping-classes-blank-state col-5" colspan="">' . $value['discount_type'] . '</td>' );
						echo '<td class="wc-shipping-classes-blank-state" colspan="">';

						if ( isset( $value['disabled'] ) && 'true' == $value['disabled'] ) {
							echo '<a class="wtp_actions" data-id=' . esc_attr( $key ) . ' id="general_role_rule_enable"> ' . esc_html__( 'Enable', 'wtp' ) . ' </a>';
						}

						if ( ! isset( $value['disabled'] ) ) {
							echo '<a class="wtp_actions" id="general_role_rule_disable" data-id=' . esc_attr( $key ) . '> ' . esc_html__( 'Disable', 'wtp' ) . '</a>';
						}

						echo ' | <a class="wtp_actions general_role_rule_delete" data-id=' . esc_attr( $key ) . ' > ' . esc_html__( 'Delete', 'wtp' ) . '</a>  | <a class="wtp_actions general_rule_edit" data-type="role" data-id=' . esc_attr( $key ) . ' > ' . esc_html__( 'Edit', 'wtp' ) . '</a></td>';
						echo '</tr>';
					}
				}
				?>
			</tbody>
		</table>
		<?php
	}

	public function add_admin_field_table_user() {
		?>
		<table class="wc-shipping-classes widefat">
			<thead>
				<tr>
					<th class=""><?php echo esc_html__( 'Username', 'wtp' ); ?></th>
					<th class=""><?php echo esc_html__( 'Minimum Qty', 'wtp' ); ?></th>
					<th class=""><?php echo esc_html__( 'Maximum Qty', 'wtp' ); ?></th>					
					<th class=""><?php echo esc_html__( 'Discount Value', 'wtp' ); ?></th>
					<th class=""><?php echo esc_html__( 'Discount Type', 'wtp' ); ?></th>
					<th class=""><?php echo esc_html__( 'Action', 'wtp' ); ?></th>
				</tr>
			</thead>
			<tbody id="wtp-user-type-discounts" class="wc-shipping-class-rows">
				<?php
				$general_user_rules = get_option( 'general_user_rules', false );

				if ( $general_user_rules ) {
					foreach ( $general_user_rules as $key => $value ) {

						if ( '' === $value['max_qty'] ) {
							$max_qty = '&#8734;';
						} else {
							$max_qty = $value['max_qty'];
						}

						echo '<tr>';
						echo wp_kses_post( '<td class="wc-shipping-classes-blank-state col-1" data-value="' . $value['user'] . '" colspan="">' . $value['user_text'] . '</td>' );
						echo wp_kses_post( '<td class="wc-shipping-classes-blank-state col-2" colspan="">' . $value['min_qty'] . '</td>' );
						echo wp_kses_post( '<td class="wc-shipping-classes-blank-state col-3" colspan="">' . $max_qty . '</td>' );
						echo wp_kses_post( '<td class="wc-shipping-classes-blank-state col-4" colspan="">' . $value['discount_value'] . '</td>' );
						echo wp_kses_post( '<td class="wc-shipping-classes-blank-state col-5" colspan="">' . $value['discount_type'] . '</td>' );
						echo '<td class="wc-shipping-classes-blank-state" colspan="">';

						if ( isset( $value['disabled'] ) && 'true' == $value['disabled'] ) {
							echo '<a class="wtp_actions" data-id=' . esc_attr( $key ) . ' id="general_user_rule_enable"> ' . esc_html__( 'Enable', 'wtp' ) . ' </a>';
						}

						if ( ! isset( $value['disabled'] ) ) {
							echo '<a class="wtp_actions" data-id=' . esc_attr( $key ) . ' id="general_user_rule_disable"> ' . esc_html__( 'Disable', 'wtp' ) . '</a>';
						}

						echo ' | <a class="wtp_actions general_user_rule_delete" data-id=' . esc_attr( $key ) . '> ' . esc_html__( 'Delete', 'wtp' ) . '</a>  | <a class="wtp_actions general_rule_edit" data-type="user" data-id=' . esc_attr( $key ) . ' > ' . esc_html__( 'Edit', 'wtp' ) . '</a></td>';
						echo '</tr>';
					}
				}
				?>
			</tbody>
		</table>
		<?php
	}

	public function add_admin_field_table_category( $value ) {

		?>
		<table class="wc-shipping-classes widefat">
			<thead>
				<tr>
					<th class=""><?php echo esc_html__( 'Category', 'wtp' ); ?></th>
					<th class=""><?php echo esc_html__( 'Minimum Qty', 'wtp' ); ?></th>
					<th class=""><?php echo esc_html__( 'Maximum Qty', 'wtp' ); ?></th>					
					<th class=""><?php echo esc_html__( 'Discount Value', 'wtp' ); ?></th>
					<th class=""><?php echo esc_html__( 'Discount Type', 'wtp' ); ?></th>
					<th class=""><?php echo esc_html__( 'Action', 'wtp' ); ?></th>
				</tr>
			</thead>
			<tbody class="wc-shipping-class-rows" id="wtp-category-role-discounts">
				<?php
				$general_category_rules = get_option( 'general_category_rules', false );

				if ( $general_category_rules ) {
					foreach ( $general_category_rules as $key => $value ) {

						if ( '' === $value['max_qty'] ) {
							$max_qty = '&#8734;';
						} else {
							$max_qty = $value['max_qty'];
						}

						echo '<tr>';
						echo wp_kses_post( '<td class="wc-shipping-classes-blank-state col-1" data-value="' . $value['category'] . '" colspan="">' . $value['category_text'] . '</td>' );
						echo wp_kses_post( '<td class="wc-shipping-classes-blank-state col-2" colspan="">' . $value['min_qty'] . '</td>' );
						echo wp_kses_post( '<td class="wc-shipping-classes-blank-state col-3" colspan="">' . $max_qty . '</td>' );
						echo wp_kses_post( '<td class="wc-shipping-classes-blank-state col-4" colspan="">' . $value['discount_value'] . '</td>' );
						echo wp_kses_post( '<td class="wc-shipping-classes-blank-state col-5" colspan="">' . $value['discount_type'] . '</td>' );
						echo '<td class="wc-shipping-classes-blank-state" colspan="">';

						if ( isset( $value['disabled'] ) && 'true' == $value['disabled'] ) {
							echo '<a class="wtp_actions" data-id=' . esc_attr( $key ) . ' id="general_category_rule_enable">' . esc_html__( 'Enable', 'wtp' ) . '</a>';
						}

						if ( ! isset( $value['disabled'] ) ) {
							echo '<a class="wtp_actions" data-id=' . esc_attr( $key ) . ' id="general_category_rule_disable"> ' . esc_html__( 'Disable', 'wtp' ) . '</a>';
						}

						echo ' | <a class="wtp_actions general_category_rule_delete" data-id=' . esc_attr( $key ) . ' >' . esc_html__( 'Delete', 'wtp' ) . '</a> | <a class="wtp_actions general_rule_edit" data-type="category" data-id=' . esc_attr( $key ) . ' >' . esc_html__( 'Edit', 'wtp' ) . '</a></td>';
						echo '</tr>';
					}
				}
				?>
			</tbody>
		</table>
		<?php
	}

	public function add_category_map_button( $value ) {
		echo '<tr>
        <td> </td>
        <td><button id="add_category_based_mapping" class="wtp-button">' . esc_html__( 'Add Rule', 'wtp' ) . '</button> </td>
        </tr>';
	}

	public function add_role_map_button( $value ) {
		echo '<tr>
        <td> </td>
        <td><button id="add_role_based_mapping" class="wtp-button">' . esc_html__( 'Add Rule', 'wtp' ) . '</button> </td>
        </tr>';
	}

	public function add_user_map_button( $value ) {
		echo wp_kses_post(
			'<tr>
		        <td> </td>
		        <td><button id="add_user_based_mapping" class="wtp-button">' . esc_html__( 'Add Rule', 'wtp' ) . '</button> </td>
		        </tr>'
		);
	}

	public function add_admin_field_user_roles_management( $value ) {
		echo '<tr class="wtp-form-control" style="display:table-row">
	        <th scope="row" class="titledesc rms-leftSide">
	        	<label class="wtp-label" for="wc_settings_general_wholesale_role">' . esc_html__( 'Add New Role*', 'wtp' ) . '</label>
	        </th>
	        <td class="rms-rightSide">
	        	<div class="wtp-tighed">
	        		<input id="new_user_role" placeholder="Enter user role" type="text"/>
	        		<button id="add_user_role">Add Role</button>
	        	</div>
	        	<div class="wtp-field-response"></div>
	        </td>
        </tr>';
		echo '<table class="wc-shipping-classes widefat">
        <thead>
            <tr>
                <th class="">' . esc_html__( 'User Role', 'wtp' ) . '</th>
                <th class="">' . esc_html__( 'Action', 'wtp' ) . '</th>
            </tr>
        </thead>
        <tfoot>
          
        </tfoot>
        <tbody class="wc-shipping-class-rows" id="wtp_roles_display">';
		$new_user_roles = get_option( 'wtp_new_user_roles', false );
		if ( ! empty( $new_user_roles ) && count( $new_user_roles ) > 0 ) {
			foreach ( $new_user_roles as $key => $value ) {

				echo '<tr>';
				echo wp_kses_post( '<td class="wc-shipping-classes-blank-state" colspan="">' . $value['user_role'] . '</td>' );
				echo '<td class="wc-shipping-classes-blank-state" colspan=""> <a class="wtp_actions user_role_delete" data-id=' . esc_attr( $key ) . '>Delete</a> </td>';
				echo '</tr>';

			}
		}
		echo '</tbody>
        </table>';

	}

	public function get_user_roles() {

		$editable_roles = get_editable_roles();
		foreach ( $editable_roles as $role => $details ) {
			$sub['role'] = esc_attr( $role );
			$sub['name'] = translate_user_role( $details['name'] );
			$roles[]     = $sub;
		}
		update_option( 'current_roles', $roles );
	}

}

return new WTPInitialSetup();
