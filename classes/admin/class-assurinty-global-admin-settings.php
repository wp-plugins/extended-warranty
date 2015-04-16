<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Assurinty_Global_Admin_Settings class.
 */
class Assurinty_Global_Admin_Settings {

	var $settings;
	var $title;

	/**
	 * __construct function.
	 *
	 * @access public
	 */
	public function __construct() {
		$this->settings_group = 'assurinty_global_settings';
		$this->title		  = __('ASSURINTYglobal', 'assurinty-global');
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// send form data
		add_action( 'admin_init', array( $this, 'assurinty_global_send_signup' ) );
		add_action( 'admin_notices', array( $this, 'reviewing_application' ) );

		// Check for warranty products import
		add_action( 'admin_init', array( $this, 'assurinty_global_add_warranty_products_action' ) );

	}

	/**
	 * register_settings function.
	 *
	 * @access public
	 * @return void
	 */
	public function register_settings() {

		$this->init_settings();

		foreach ( $this->settings as $section ) {
			foreach ( $section[1] as $option ) {
				if ( isset( $option['std'] ) )
					add_option( $option['name'], $option['std'] );

				if ( isset($option['type']) && ( 'directions' != $option['type'] )
											&& ( 'heading' != $option['type'] )
											&& ( 'subheading' != $option['type']) ){

					register_setting( $this->settings_group, $option['name'], array( $this, 'verify_data') );
				}
			}
		}

	}

	/**
	 *	Verify the settings data being saved
	 */
	public function verify_data( $data ) {
		return $data;
	}

	/**
	 * output function.
	 *
	 * @access public
	 * @return void
	 */
	public function output() {

		$this->init_settings();
		?>
		<div class="wrap">
			<form method="post" action="options.php">

				<?php settings_fields( $this->settings_group ); ?>

				<h2>
					<?php
					echo  esc_html( $this->title );
					?>
				</h2>
				<br/>

				<h2 class="nav-tab-wrapper">
					<?php
					foreach ( $this->settings as $key => $section ) {
						echo '<a href="#settings-' . sanitize_title( $key ) . '" class="nav-tab">' . esc_html( $section[0] ) . '</a>';
					}
					?>
				</h2><br/>

				<?php
				if ( ! empty( $_GET['settings-updated'] ) ) {
					flush_rewrite_rules();
				}

				foreach ( $this->settings as $tab => $section ) {

					echo '<div id="settings-' . sanitize_title( $tab ) . '" class="settings_panel">';

					echo '<table class="form-table">';

					foreach ( $section[1] as $option ) {

						$placeholder	= ( ! empty( $option['placeholder'] ) ) ? 'placeholder="' . $option['placeholder'] . '"' : '';
						$class			= ! empty( $option['class'] ) ? $option['class'] : '';
						if (isset($option['name']))
							$value			= get_option( $option['name'] );
						else
							$value			= '';
						$option['type'] = ! empty( $option['type'] ) ? $option['type'] : '';
						$attributes		= array();

						if ( ! empty( $option['attributes'] ) && is_array( $option['attributes'] ) )
							foreach ( $option['attributes'] as $attribute_name => $attribute_value )
								$attributes[] = esc_attr( $attribute_name ) . '="' . esc_attr( $attribute_value ) . '"';

						if ( ! empty($option['label']) )
							echo '<tr valign="top" class="' . $class . '"><th scope="row"><label for="setting-' . $option['name'] . '">' . $option['label'] . '</a></th><td>';

						switch ( $option['type'] ) {
							case "heading" :
								echo '</table>';
								?><h3><?php echo $option['heading']; ?></h3><?php
								echo '<table class="form-table">';
								break;
							case "subheading" :
								echo '</table>';
								?><p><?php echo $option['heading']; ?></p><?php
								echo '<table class="form-table">';
								break;
							case "checkbox" :

								?><label><input id="setting-<?php echo $option['name']; ?>" name="<?php echo $option['name']; ?>" type="checkbox" value="1" <?php echo implode( ' ', $attributes ); ?> <?php checked( '1', $value ); ?> /> <?php echo $option['cb_label']; ?></label><br/><?php

								if ( isset($option['desc']) && $option['desc'] )
									echo ' <p class="description">' . $option['desc'] . '</p>';

								break;
							case "textarea" :
								?><textarea id="setting-<?php echo $option['name']; ?>" class="large-text" cols="50" rows="3" name="<?php echo $option['name']; ?>" <?php echo implode( ' ', $attributes ); ?> <?php echo $placeholder; ?>><?php echo esc_textarea( $value ); ?></textarea><?php
								if ( isset($option['desc']) && $option['desc'] )
									echo ' <p class="description">' . $option['desc'] . '</p>';

								break;
							case "select" :

								?><select id="setting-<?php echo $option['name']; ?>" class="regular-text" name="<?php echo $option['name']; ?>" <?php echo implode( ' ', $attributes ); ?>><?php
								foreach( $option['options'] as $key => $name ) :
									echo '<option value="' . esc_attr( $key ) . '" ' . selected( $value, $key, false ) . '>' . esc_html( $name ) . '</option>';
								endforeach;
								?></select><?php

								if ( isset($option['desc']) && $option['desc'] )
									echo ' <p class="description">' . $option['desc'] . '</p>';

								break;
							case "chosen" :
								?><select data-placeholder="<?php _e( 'Choose products&hellip;', 'assurinty-global' ); ?>" style="width: 350px;" multiple="multiple" id="setting-<?php echo $option['name']; ?>" title="<?php echo $option['name']; ?>" class="chosen-select" name="<?php echo $option['name']; ?>[]" <?php echo implode( ' ', $attributes ); ?>><?php
								foreach( $option['options'] as $key => $name ) :
									echo '<option value="' . esc_attr( $key ) . '" ' . selected( in_array( $key, $value ), true ) .	 ' >' . esc_html( $name ) . '</option>';
								endforeach;
								?></select><?php

								if ( isset($option['desc']) && $option['desc'] )
									echo ' <p class="description">' . $option['desc'] . '</p>';

								break;
							case "password" :

								?><input id="setting-<?php echo $option['name']; ?>" class="regular-text" type="password" name="<?php echo $option['name']; ?>" value="<?php esc_attr_e( $value ); ?>" <?php echo implode( ' ', $attributes ); ?> <?php echo $placeholder; ?> /><?php

								if ( isset($option['desc']) && $option['desc'] )
									echo ' <p class="description">' . $option['desc'] . '</p>';

								break;
							case "button" :
								?><button name="<?php echo esc_html( $option['name'] ); ?>" class="button" id="<?php echo esc_html( $option['name'] ); ?>"" ><?php echo esc_html( $option['button'] ); ?></button><?php
								if ( isset($option['desc']) && $option['desc'] )
									echo '<p class="description">' . $option['desc'] . '</p>';

								break;
							case "directions" :
								echo '<p>' . $option['desc'] . '</p>';
								break;
							default :

								?><input id="setting-<?php echo $option['name']; ?>" class="regular-text" type="text" name="<?php echo $option['name']; ?>" value="<?php esc_attr_e( $value ); ?>" <?php echo implode( ' ', $attributes ); ?> <?php echo $placeholder; ?> /><?php

								if ( isset($option['desc']) && $option['desc'] )
									echo ' <p class="description">' . $option['desc'] . '</p>';

								break;
						}
						echo '</td></tr>';
					}

					?><tr>
						 <td>
							  <p class="submit">
								<?php if ( 'assurinty_global_tools' == $tab ) : ?>
									<input type="submit" class="button-primary" name='save' value="<?php _e( 'Save changes', 'assurinty-global' ); ?>" />
								<?php else : ?>
									<input type="submit" class="button-primary" name='send' value="<?php _e( 'Send Application', 'assurinty-global' ); ?>" />
								<?php endif; ?>
							   </p>
						 </td>
					</tr><?php

					echo '</table></div>';
				}
				?>

			</form>
		</div>
		<script type="text/javascript">
			jQuery('.nav-tab-wrapper a').click(function() {
				jQuery('.settings_panel').hide();
				jQuery('.nav-tab-active').removeClass('nav-tab-active');
				jQuery( jQuery(this).attr('href') ).show();
				jQuery(this).addClass('nav-tab-active');
				return false;
			});
			jQuery('.nav-tab-wrapper a:first').click();
		</script><?php

	}

	/**
	 * init_settings function.
	 *
	 * @access protected
	 * @return void
	 */
	protected function init_settings() {

		$this->settings = apply_filters( 'assurinty_global_settings',
			array(
				'assurinty_global_settings' => array(
					__('Sign Up Form', 'assurinty-global'),
					array(
						array(
							'type'		  => 'heading',
							'heading'	  => __( 'ASSURINTYglobal Account Information', 'assurinty-global' ),
						),
						array(
							'type'		  => 'directions',
							'desc'		  => __('Fill out this form to sign up for ASSURINTYglobal!','assurinty-global'),
						),
						array(
							'name'		  => 'assurinty_global_form_webaddress',
							'label'		  => __( 'Website Address<span style="color:red;">*</span>', 'assurinty-global' ),
							'type'		  => 'text'
						),
						array(
							'name'		  => 'assurinty_global_form_email',
							'label'		  => __( 'Email Address<span style="color:red;">*</span>', 'assurinty-global' ),
							'desc'		  => __( 'This will be your user name', 'assurinty-global' ),
							'type'		  => 'text'
						),
						array(
							'name'		  => 'assurinty_global_form_password',
							'label'		  => __( 'Password<span style="color:red;">*</span>', 'assurinty-global' ),
							'type'		  => 'password',
						),
						array(
							'name'		  => 'assurinty_global_form_password_confirm',
							'label'		  => __( 'Confirm Password<span style="color:red;">*</span>', 'assurinty-global' ),
							'type'		  => 'password',
						),
						array(
							'type'		  => 'heading',
							'heading'		=> __( 'Website Information', 'assurinty-global' ),
						),

						array(
							'name'		  => 'assurinty_global_form_first_name',
							'label'		  => __( 'First Name<span style="color:red;">*</span>', 'assurinty-global' ),
							'type'		  => 'text'
						),
						array(
							'name'		  => 'assurinty_global_form_last_name',
							'label'		  => __( 'Last Name<span style="color:red;">*</span>', 'assurinty-global' ),
							'type'		  => 'text'
						),
						array(
							'name'		  => 'assurinty_global_form_company_name',
							'label'		  => __( 'Company Name<span style="color:red;">*</span>', 'assurinty-global' ),
							'type'		  => 'text'
						),
						array(
							'name'		  => 'assurinty_global_form_dba',
							'label'		  => __( 'DBA<span style="color:red;">*</span>', 'assurinty-global' ),
							'type'		  => 'text'
						),
						array(
							'name'		  => 'assurinty_global_form_company_address',
							'label'		  => __( 'Company Address<span style="color:red;">*</span>', 'assurinty-global' ),
							'type'		  => 'text'
						),
						array(
							'name'		  => 'assurinty_global_form_city',
							'label'		  => __( 'City<span style="color:red">*</span>', 'assurinty-global'),
							'type'		  => 'text'
						),
						array(
							'name'		  => 'assurinty_global_form_state',
							'label'		  => __( 'State<span style="color:red;">*</span>', 'assurinty-global' ),
							'type'		  => 'text'
						),
						array(
							'name'		  => 'assurinty_global_form_zip',
							'label'		  => __( 'Zip Code<span style="color:red;">*</span>', 'assurinty-global' ),
							'type'		  => 'text'
						),
						array(
							'name'		  => 'assurinty_global_form_country',
							'label'		  => __( 'Country<span style="color:red;">*</span>', 'assurinty-global' ),
							'type'		  => 'text'
						),
						array(
							'name'		  => 'assurinty_global_form_telephone',
							'label'		  => __( 'Company Telephone<span style="color:red;">*</span>', 'assurinty-global' ),
							'type'		  => 'text'
						),
						array(
							'name'		  => 'assurinty_global_form_year_founded',
							'label'		  => __( 'Year Founded<span style="color:red;">*</span>', 'assurinty-global' ),
							'type'		  => 'text'
						),
						array(
							'type'			=> 'heading',
							'heading'		=> __( 'Technical Contact', 'assurinty-global' ),
						),
						array(
							'name'		  => 'assurinty_global_form_tech_name',
							'label'		  => __( 'Name<span style="color:red;">*</span>', 'assurinty-global' ),
							'type'		  => 'text'
						),
						array(
							'name'		  => 'assurinty_global_form_tech_telephone',
							'label'		  => __( 'Telephone<span style="color:red;">*</span>', 'assurinty-global' ),
							'type'		  => 'text'
						),
						array(
							'name'		  => 'assurinty_global_form_tech_email',
							'label'		  => __( 'Email Address<span style="color:red;">*</span>', 'assurinty-global' ),
							'type'		  => 'text'
						),
						array(
							'type'			=> 'heading',
							'heading'		=> __( 'Product Information', 'assurinty-global' ),
						),
						array(
							'type'			=> 'subheading',
							'heading'		=> __( '<strong>Primary categories listed for sale on your website</strong>', 'assurinty-global' ),
						),
						array(
							'type'		  => 'checkbox',
							'name'		  => 'assurinty_global_form_check_0',
							'label'		  => '',
							'cb_label'		 => __( 'Eyewear', 'assurinty-global' ),
							'required'	  => false,
						),
						array(
							'type'		  => 'checkbox',
							'name'		  => 'assurinty_global_form_check_1',
							'label'		  => '',
							'cb_label'		 => __( 'Electronics', 'assurinty-global' ),
							'required'	  => false,
						),
						array(
							'type'		  => 'checkbox',
							'name'		  => 'assurinty_global_form_check_2',
							'label'		  => '',
							'cb_label'		 => __( 'Appliances', 'assurinty-global' ),
							'required'	  => false,
						),
						array(
							'type'		  => 'checkbox',
							'name'		  => 'assurinty_global_form_check_3',
							'label'		  => '',
							'cb_label'		 => __( 'Jewelry', 'assurinty-global' ),
							'required'	  => false,
						),
						array(
							'type'		  => 'checkbox',
							'name'		  => 'assurinty_global_form_check_4',
							'label'		  => '',
							'cb_label'		 => __( 'Watches', 'assurinty-global' ),
							'required'	  => false,
						),
						array(
							'type'		  => 'checkbox',
							'name'		  => 'assurinty_global_form_check_5',
							'label'		  => '',
							'cb_label'		 => __( 'Carpets and flooring', 'assurinty-global' ),
							'required'	  => false,
						),
						array(
							'type'		  => 'checkbox',
							'name'		  => 'assurinty_global_form_check_6',
							'label'		  => '',
							'cb_label'		 => __( 'Furniture', 'assurinty-global' ),
							'required'	  => false,
						),
						array(
							'type'		  => 'checkbox',
							'name'		  => 'assurinty_global_form_check_7',
							'label'		  => '',
							'cb_label'		 => __( 'Toys', 'assurinty-global' ),
							'required'	  => false,
						),
						array(
							'type'		  => 'checkbox',
							'name'		  => 'assurinty_global_form_check_8',
							'label'		  => '',
							'cb_label'		 => __( 'Apparel', 'assurinty-global' ),
							'required'	  => false,
						),
						array(
							'type'		  => 'checkbox',
							'name'		  => 'assurinty_global_form_check_9',
							'label'		  => '',
							'cb_label'		 => __( 'Tools', 'assurinty-global' ),
							'required'	  => false,
						),
						array(
							'type'		  => 'checkbox',
							'name'		  => 'assurinty_global_form_check_10',
							'label'		  => '',
							'cb_label'		 => __( 'Hardware', 'assurinty-global' ),
							'required'	  => false,
						),
						array(
							'type'		  => 'checkbox',
							'name'		  => 'assurinty_global_form_check_11',
							'label'		  => '',
							'cb_label'		 => __( 'Automotive', 'assurinty-global' ),
							'required'	  => false,
						),
						array(
							'type'		  => 'checkbox',
							'name'		  => 'assurinty_global_form_check_12',
							'label'		  => '',
							'cb_label'		 => __( 'Food', 'assurinty-global' ),
							'required'	  => false,
						),
						array(
							'type'			=> 'subheading',
							'heading'		=> __( '<strong>Secondary categories listed for sale on your website</strong>', 'assurinty-global' ),
						),
						array(
							'type'		  => 'checkbox',
							'name'		  => 'assurinty_global_form_check_secondary_0',
							'label'		  => '',
							'cb_label'		 => __( 'Eyewear', 'assurinty-global' ),
							'required'	  => false,
						),
						array(
							'type'		  => 'checkbox',
							'name'		  => 'assurinty_global_form_check_secondary_1',
							'label'		  => '',
							'cb_label'		 => __( 'Electronics', 'assurinty-global' ),
							'required'	  => false,
						),
						array(
							'type'		  => 'checkbox',
							'name'		  => 'assurinty_global_form_check_secondary_2',
							'label'		  => '',
							'cb_label'		 => __( 'Appliances', 'assurinty-global' ),
							'required'	  => false,
						),
						array(
							'type'		  => 'checkbox',
							'name'		  => 'assurinty_global_form_check_secondary_3',
							'label'		  => '',
							'cb_label'		 => __( 'Jewelry', 'assurinty-global' ),
							'required'	  => false,
						),
						array(
							'type'		  => 'checkbox',
							'name'		  => 'assurinty_global_form_check_secondary_4',
							'label'		  => '',
							'cb_label'		 => __( 'Watches', 'assurinty-global' ),
							'required'	  => false,
						),
						array(
							'type'		  => 'checkbox',
							'name'		  => 'assurinty_global_form_check_secondary_5',
							'label'		  => '',
							'cb_label'	  => __( 'Carpets and flooring', 'assurinty-global' ),
							'required'	  => false,
						),
						array(
							'type'		  => 'checkbox',
							'name'		  => 'assurinty_global_form_check_secondary_6',
							'label'		  => '',
							'cb_label'		 => __( 'Furniture', 'assurinty-global' ),
							'required'	  => false,
						),
						array(
							'type'		  => 'checkbox',
							'name'		  => 'assurinty_global_form_check_secondary_7',
							'label'		  => '',
							'cb_label'		 => __( 'Toys', 'assurinty-global' ),
							'required'	  => false,
						),
						array(
							'type'		  => 'checkbox',
							'name'		  => 'assurinty_global_form_check_secondary_8',
							'label'		  => '',
							'cb_label'		 => __( 'Apparel', 'assurinty-global' ),
							'required'	  => false,
						),
						array(
							'type'		  => 'checkbox',
							'name'		  => 'assurinty_global_form_check_secondary_9',
							'label'		  => '',
							'cb_label'	  => __( 'Tools', 'assurinty-global' ),
							'required'	  => false,
						),
						array(
							'type'		  => 'checkbox',
							'name'		  => 'assurinty_global_form_check_secondary_10',
							'label'		  => '',
							'cb_label'		 => __( 'Hardware', 'assurinty-global' ),
							'required'	  => false,
						),
						array(
							'type'		  => 'checkbox',
							'name'		  => 'assurinty_global_form_check_secondary_11',
							'label'		  => '',
							'cb_label'		 => __( 'Automotive', 'assurinty-global' ),
							'required'	  => false,
						),
						array(
							'type'			 => 'checkbox',
							'name'		  => 'assurinty_global_form_check_secondary_12',
							'label'		  => '',
							'cb_label'		 => __( 'Food', 'assurinty-global' ),
							'required'	  => false,
						),
						array(
							'type'		  => 'heading',
							'heading'		=> __( 'Billing Information', 'assurinty-global' ),
						),
						array(
							'name'		  => 'assurinty_global_form_accounts_payable',
							'label'		  => __( 'Accounts payable contact name<span style="color:red;">*</span>', 'assurinty-global' ),
							'type'		  => 'text'
						),
						array(
							'name'		  => 'assurinty_global_form_accounts_payable_email',
							'label'		  => __( 'Email address<span style="color:red;">*</span>', 'assurinty-global' ),
							'type'		  => 'text'
						),
						array(
							'name'		  => 'assurinty_global_form_accounts_payable_telephone',
							'label'		  => __( 'Telephone<span style="color:red;">*</span>', 'assurinty-global' ),
							'type'		  => 'text'
						),
					)
				),
				'assurinty_global_tools' => array(
					__('Tools', 'assurinty-global'),
					array(
						array(
							'name'	=> 'assurinty_global_add_warranty_products',
							'button' => 'Add Warranty Products',
							'type'	=> 'button',
							'desc'	=> __('This will add warranty products to the catalog based on the categories you chose on the signup form.','assurinty-global'),
						),
						array(
							'name'	=> 'assurinty_global_resend_application',
							'type'	=> 'button',
							'button' => 'Resend application',
							'desc'	=> __('Use this button to get the signup form back to modify/resend your application'),
						),
					)
				),
			)
		);

		// Remove sign up form when already signed up
		if ( 'yes' == get_option( 'assurinty_global_signed_up' ) ) :
			unset( $this->settings['assurinty_global_settings'] );
		endif;

	}

	public function assurinty_global_add_warranty_products_action() {

		if ( ! isset( $_POST['assurinty_global_add_warranty_products'] ) || ! isset( $_POST['option_page'] ) || 'assurinty_global_settings' != $_POST['option_page'] ) :
			return false;
		endif;

		assurinty_global_add_products( array() );

	}

	/**
	 * send the sign up form via email and send the CSV
	 */
	public function assurinty_global_send_signup() {

		if ( isset( $_POST['assurinty_global_resend_application'] ) ) :
			update_option( 'assurinty_global_signed_up', 'no' );
		endif;


		// Show error notice if they exist
		if ( 'yes' == get_option( 'assurnity_validation_error' ) ) :
			add_action( 'admin_notices', array( $this, 'sign_up_validate_notice' ) );
			update_option( 'assurnity_validation_error', '' );
		endif;

		// if the form was submitted
		if ( isset( $_POST['option_page'] ) && ( $_POST['option_page'] == 'assurinty_global_settings') && isset( $_POST['send'] ) ) {

			// Stop if fields are not validated
			if ( ! $this->validate_fields() ) {
				return;
			}

			// email form
			$message = "Form Susmission from site: " . get_site_url() . '<br/>';
			$message .= "Website Address: " . $_POST['assurinty_global_form_webaddress'] . '<br/>';
			$message .= "Email Address: " . $_POST['assurinty_global_form_email'] . '<br/>';
			$message .= "Password: " . $_POST['assurinty_global_form_password'] . '<br/><br/>';

			$message .= "WEBSITE INFORMATION" . '<br/>';
			$message .= "First Name: " . $_POST['assurinty_global_form_first_name'] . '<br/>';
			$message .= "Last Name: " . $_POST['assurinty_global_form_last_name'] . '<br/>';
			$message .= "Company Name: " . $_POST['assurinty_global_form_company_name'] . '<br/>';
			$message .= "DBA: " . $_POST['assurinty_global_form_dba'] . '<br/>';
			$message .= "Company Address: " . $_POST['assurinty_global_form_company_address'] . '<br/>';
			$message .= "City: " . $_POST['assurinty_global_form_city'] . '<br/>';
			$message .= "State: " . $_POST['assurinty_global_form_state'] . '<br/>';
			$message .= "Zip: " . $_POST['assurinty_global_form_zip'] . '<br/>';
			$message .= "Country: " . $_POST['assurinty_global_form_country'] . '<br/>';
			$message .= "Telephone: " . $_POST['assurinty_global_form_telephone'] . '<br/>';
			$message .= "Year Founded: " . $_POST['assurinty_global_form_year_founded'] . '<br/><br/>';

			$message .= "TECHNICAL CONTACT" . '<br/>';
			$message .= "Name: " . $_POST['assurinty_global_form_tech_name'] . '<br/>';
			$message .= "Telephone: " . $_POST['assurinty_global_form_tech_telephone'] . '<br/>';
			$message .= "Email Address: " . $_POST['assurinty_global_form_tech_email'] . '<br/><br/>';

			$message .= "WEBSITE INFORMATION" . '<br/>';
			$categories = array();
			for ($i=0; $i<13; $i++) {
				switch ($i) {
					case 0:
						if (isset($_POST['assurinty_global_form_check_0']))
							array_push($categories, 'Eyewear');
						break;
					 case 1:
						 if (isset($_POST['assurinty_global_form_check_1']))
							 array_push($categories, 'Electronics');
						 break;
					 case 2:
						 if (isset($_POST['assurinty_global_form_check_2']))
							 array_push($categories, 'Appliances');
						 break;
					 case 3:
						 if (isset($_POST['assurinty_global_form_check_3']))
						 array_push($categories, 'Jewelry');
						 break;
					 case 4:
						 if (isset($_POST['assurinty_global_form_check_4']))
						 array_push($categories, 'Watches');
						 break;
					 case 5:
						 if (isset($_POST['assurinty_global_form_check_5']))
						 array_push($categories, 'Carpets and flooring');
						 break;
					 case 6:
						 if (isset($_POST['assurinty_global_form_check_6']))
						 array_push($categories, 'Furniture');
						 break;
					 case 7:
						 if (isset($_POST['assurinty_global_form_check_7']))
						 array_push($categories, 'Toys');
						 break;
					 case 8:
						 if (isset($_POST['assurinty_global_form_check_8']))
						 array_push($categories, 'Apparel');
						 break;
					 case 9:
						 if (isset($_POST['assurinty_global_form_check_9']))
						 array_push($categories, 'Tools');
						 break;
					 case 10:
						 if (isset($_POST['assurinty_global_form_check_10']))
						 array_push($categories, 'Hardware');
						 break;
					 case 11:
						 if (isset($_POST['assurinty_global_form_check_11']))
						 array_push($categories, 'Automotive');
						 break;
					 case 12:
						 if (isset($_POST['assurinty_global_form_check_12']))
						 array_push($categories, 'Food');
						 break;
				}
			}
			$message .= "Primary Categories: " . implode(", ", $categories) . '<br/>';

			$secondary_categories = array();
			for ($i=0; $i<13; $i++) {
				switch ($i) {
					case 0:
						if (isset($_POST['assurinty_global_form_check_secondary_0']))
							array_push($secondary_categories, 'Eyewear');
						break;
					 case 1:
						 if (isset($_POST['assurinty_global_form_check_secondary_1']))
							 array_push($secondary_categories, 'Electronics');
						 break;
					 case 2:
						 if (isset($_POST['assurinty_global_form_check_secondary_2']))
							 array_push($secondary_categories, 'Appliances');
						 break;
					 case 3:
						 if (isset($_POST['assurinty_global_form_check_secondary_3']))
							 array_push($secondary_categories, 'Jewelry');
						 break;
					 case 4:
						 if (isset($_POST['assurinty_global_form_check_secondary_4']))
							 array_push($secondary_categories, 'Watches');
						 break;
					 case 5:
						 if (isset($_POST['assurinty_global_form_check_secondary_5']))
							 array_push($secondary_categories, 'Carpets and flooring');
						 break;
					 case 6:
						 if (isset($_POST['assurinty_global_form_check_secondary_6']))
							 array_push($secondary_categories, 'Furniture');
						 break;
					 case 7:
						 if (isset($_POST['assurinty_global_form_check_secondary_7']))
							 array_push($secondary_categories, 'Toys');
						 break;
					 case 8:
						 if (isset($_POST['assurinty_global_form_check_secondary_8']))
							 array_push($secondary_categories, 'Apparel');
						 break;
					 case 9:
						 if (isset($_POST['assurinty_global_form_check_secondary_9']))
							 array_push($secondary_categories, 'Tools');
						 break;
					 case 10:
						 if (isset($_POST['assurinty_global_form_check_secondary_10']))
							 array_push($secondary_categories, 'Hardware');
						 break;
					 case 11:
						 if (isset($_POST['assurinty_global_form_check_secondary_11']))
							 array_push($secondary_categories, 'Automotive');
						 break;
					 case 12:
						 if (isset($_POST['assurinty_global_form_check_secondary_12']))
							 array_push($secondary_categories, 'Food');
						 break;
				}
			}
			$message .= "Secondary Categories: " . implode(", ",$secondary_categories) . '<br/><br/>';

			$message .= "BILLING INFORMATION" . '<br/>';
			$message .= "Accounts Payable Contact Name: " . $_POST['assurinty_global_form_accounts_payable'] . '<br/>';
			$message .= "Email Address: " . $_POST['assurinty_global_form_accounts_payable_email'] . '<br/>';
			$message .= "Telephone: " . $_POST['assurinty_global_form_accounts_payable_telephone'] . '<br/>';

			$headers[] = 'From: ' . $_POST['assurinty_global_form_company_name'] . ' <' . $_POST['assurinty_global_form_email'] . '>';
			$headers[] = 'MIME-Version: 1.0';
			$headers[] = 'Content-Type: text/html';
			$headers[] = 'charset=utf-8';

			//wp_mail( 'woocommercemerchant@assurintyglobal.com', 'ASSURNITYglobal New Account Signup', $message, $headers );

			// Save value that the shop owner has signed up successfully
			update_option( 'assurinty_global_signed_up', 'yes' );
			update_option( 'assurinty_global_show_notice', 'no' );

			 /**
			  * Add warranty products to the site
			  */
			 $warranty_categories = array_unique(array_merge( $categories, $secondary_categories ));
			 assurinty_global_add_products( $warranty_categories );

			 /**
			  * Export store products to csv
			  */
			 assurinty_global_send_products( $_POST['assurinty_global_form_company_name'] );

		}

	}

	/**
	 * Validate all sign-up fields
	 */
	public function validate_fields() {

		$settings = $this->settings;

			if ( $_POST['assurinty_global_form_password'] !==
				$_POST['assurinty_global_form_password_confirm'] ) :
					update_option( 'assurnity_validation_error', 'yes' );
					return false;
			endif;

			foreach ( $settings['assurinty_global_settings'][1] as $option ) :

				// All fields are required by default
				if ( ( ! isset( $option['required'] ) || true == $option['required'] ) && isset( $option['name'] ) && empty( $_POST[ $option['name'] ] ) ) :
					update_option( 'assurnity_validation_error', 'yes' );
					return false;
				endif;

			endforeach;


		return true;

	}

	/**
	 * Sign up notice.
	 */
	public function sign_up_validate_notice() {

		 ?><div class="error">
			 <p><?php _e( 'There were some validation error(s) on the sign-up form. Please verify all fields and try again', 'assurinty-global' ); ?></p>
		 </div><?php

	}

	/**
	 * Reviewing application notice
	 */
	public function reviewing_application() {

		if ( isset( $_GET['page'] ) && ( $_GET['page'] == 'woocommerce_assurinty_global' ) && ( 'yes' == get_option( 'assurinty_global_signed_up' ) ) ) {
			?>
			<div class="updated">
			<p><?php _e( 'ASSURITNYglobal is reviewing your site and inventory to make sure that the products are warrantyable. They will contact you within several days to activate the extension.', 'assurinty-global' ); ?></p>
			</div><?php
		}
	}

}
