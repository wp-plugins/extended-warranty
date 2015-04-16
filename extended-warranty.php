<?php
/*
 Plugin Name: ASSURINTYglobal for WooCommerce
 Plugin URI: http://assurintyglobal.com
 Description: Upsell fully insured extended warranties and service plans with ASSURINTYglobal for WP/WooCommerce.
 Author: ASSURINTYglobal
 Author URI: http://assurintyglobal.com
 Version: 1.0.0
 Text Domain: assurinty-global
 Domain Path: /languages/
 */

if ( class_exists( 'Assurinty_Global' ) ) return;

define( 'ASSURNITY_GLOBAL_FILE', __FILE__ );

if ( ! function_exists( 'is_plugin_active_for_network' ) )
	require_once( ABSPATH . '/wp-admin/includes/plugin.php' );

// Check if WooCommerce is active
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) :
	if ( ! is_plugin_active_for_network( 'woocommerce/woocommerce.php' ) ) :
		return;
	endif;
endif;


/**
 * Localisation
 */
load_plugin_textdomain( 'assurinty-global', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );


/**
 * Plugin page links
 */
function assurinty_global_chooser_plugin_links( $links ) {
    $plugin_links = array(
        '<a href="' . admin_url( 'admin.php?page=woocommerce_assurinty_global' ) . '">' . __( 'Settings', 'assurinty-global' ) . '</a>',
        '<a href="http://www.assurintyglobal.com">' . __( 'Support', 'assurinty-global' ) . '</a>',
        '<a href="http://www.assurintyglobal.com">' . __( 'Documentation', 'assurinty-global' ) . '</a>',
    );

    return array_merge( $plugin_links, $links );
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'assurinty_global_chooser_plugin_links' );

/**
 * Load the plugin
 */
function assurinty_global_init() {

	$GLOBALS['ag_wc_logger'] = new WC_extendeLogger();

    if ( is_admin() )
        include_once( 'classes/admin/class-assurinty-global-admin.php' );


	if ( ! is_admin() || defined( 'DOING_AJAX' ) ) {
        include_once( 'classes/class-assurinty-global-frontend.php');
		$GLOBALS['assurinty_global'] = new Assurinty_Global_Frontend();
    }

    include_once( 'classes/class-assurinty-global-cron.php' );
    $GLOBALS['assurinity_global_cron'] = new Assurinty_Global_Cron();

}
add_action('init', 'assurinty_global_init', 100);

/**
 * Add the 'warranty' product type
 */
function activate_assurinty_global() {

    // add the warranty product type
    if ( ! get_term_by( 'slug', sanitize_title( 'warranty' ), 'product_type' ) ) {
        wp_insert_term( 'warranty', 'product_type' );
    }

}
register_activation_hook( __FILE__, 'activate_assurinty_global' );

/**
 * Import warranty products to WooCommerce catalog
 *
 * @param array $categories
 */
function assurinty_global_add_products( $categories ) {

    include_once('classes/admin/class-assurinty-global-product-importer.php');

    $importer = new Assurinty_Global_Product_Importer();
    $importer->categories = $categories;
    $importer->parse_csv();
    $importer->import_warranty_products();

    return;
}

/**
 * Send products to ASSURINTYglobal to setup warranty suggestions
 *
 * @param string $retailer_name
 */
function assurinty_global_send_products( $retailer_name ) {

    // send all products to a csv file
    include_once('classes/admin/class-assurinty-global-product-exporter.php');

    $exporter = new Assurinty_Global_Product_Exporter();
    $exporter->retailer_name = $retailer_name;
    if ( $file = $exporter->create_file() ) {
		$GLOBALS['ag_wc_logger']->add( 'Assurnity Global', 'Products file created & send!' );
        $exporter->send_file( $file );
    } else {
	    $GLOBALS['ag_wc_logger']->add( 'Assurnity Global', 'Products file could not be created.' );
    }

    return;

}
