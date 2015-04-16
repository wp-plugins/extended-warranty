<?php
/**
 * Assurinty_Global_Admin class
 */

class Assurinty_Global_Admin {

    /**
     * construct()
     */
    public function __construct(){

        include_once( 'class-assurinty-global-admin-settings.php');

        $this->settings_page = new Assurinty_Global_Admin_Settings();

        add_action('admin_menu',array( $this, 'menu') );

        // Add warranty to the product select box
        add_filter( 'product_type_selector', __CLASS__ . '::add_warranty_to_select' );

	    add_action( 'admin_notices', array( $this, 'application_notice' ) );

    }

    /**
     * menu()
     *
     * @access public
     * @return void
     */
    function menu() {
        $show_in_menu = current_user_can('manage_woocommerce') ? 'woocommerce' : false;
        add_submenu_page($show_in_menu, __('Warranty', 'assurinty-global'),  __('Warranty', 'assurinty-global')
            , 'manage_woocommerce', 'woocommerce_assurinty_global', array( $this->settings_page,'output'));
    }

    /**
     * Add the 'warranty' product type to the WooCommerce product type select box.
     *
     * @param array Array of Product types & their labels, excluding the Warranty product type.
     * @return array Array of Product types & their labels, including the Warranty product type.
     * @since 1.0
     */
    public static function add_warranty_to_select( $product_types ){

        $product_types['warranty-product'] = __( 'Warranty', 'assurinty-global' );
        return $product_types;
    }

	/**
	 * Application
	 *
	 *
	 */
	public function application_notice() {

		$show_notice = true;
		if ( isset($_GET['page']) ) {
			$show_notice = ( 'woocommerce_assurinty_global' == $_GET['page'] ) ? false : true;
		}

		if ( 'yes' == get_option( 'assurinty_global_signed_up' ) ) {
			$show_notice = false;
		}

		if ( $show_notice ) {
				?>
				<div id="message" class="updated woocommerce-message wc-connect">
				<p><?php _e( '<strong>Thanks for using ASSURANTYglobal for WooCommerce! Next step is to setup your accont is to fill out the application.</strong>', 'assurinty-global' ); ?></p>

				<p class="submit">
					<a href="<?php echo admin_url( 'admin.php?page=woocommerce_assurinty_global' ); ?>"
					   class="button-primary"><?php _e( 'Go to Application', 'assurinty-global' ); ?></a>
				</p>
				</div><?php
		}
	}

}

new Assurinty_Global_Admin;