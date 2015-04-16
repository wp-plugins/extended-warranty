<?php
/**
 * Assurinty_Global_Frontend class
 */

class Assurinty_Global_Frontend {

    /**
     * Constructor.
     */
    public function __construct(){

		add_action('woocommerce_before_cart', array( $this, 'add_javascript'));

    }

	/**
	 *  Add Javascript to the cart page.
	 */
	public function add_javascript() {

		$retailer_name 	= apply_filters( 'assurtnity_global_js_retailer_name', get_option( 'assurinty_global_form_company_name', '' ) );
		$retailer_name	= strtolower( str_replace( ' ', '_', $retailer_name ) );
		$js_url 		= apply_filters( 'assurnity_global_js_url', sprintf( 'http://www.assurintyglobal.com/warranty_selector/%1$s/mw_ws_%1$s.js', $retailer_name ) );


		if ( ! empty( $retailer_name ) ):
			echo "<script src='$js_url'></script>";
		endif;

	}

}