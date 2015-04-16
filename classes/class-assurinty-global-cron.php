<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Assurinty_Global_Cron class.
 */
class Assurinty_Global_Cron {


	public $retailer_name = '';
	public $orders_file;

	public function __construct() {

	}

	/**
	 * Create orders file.
	 */
	public function create_orders_file() {

		$delimiter = '	';
		$enclosure = '"';

		$upload_dir = wp_upload_dir();
		$target_dir = trailingslashit( $upload_dir['basedir'] ) . 'assurintyglobal/orders/';
		if( ! file_exists( $target_dir ) )
			wp_mkdir_p( $target_dir );

		$filename = str_replace(' ', '_', strtolower(trim( $this->retailer_name ))) . '_orders_' . date('Ymd') . '.csv';
		$csvpath =	$target_dir . $filename;

		$this->orders_file = $csvpath;

		$outfile = fopen( $csvpath , 'a+' );

		if ( $outfile ) {

			/**
			 * Loop through posts and output them.
			 * Fields: SKU, Product Description, Product Price, Product Category
			 */
			$orders = get_posts( array(
				'post_type' 		=> 'shop_order',
				'posts_per_page'	=> -1,
				'post_status'		=> array_keys( wc_get_order_statuses() ),
				'meta_query'		=> array(
					array(
						'key'		=> '_send_assurnity_global',
						'compare'	=> 'NOT EXISTS',
					)
				)
			) );

			// Stop if $orders is invalid
			if ( ! is_array( $orders ) ) :
				$GLOBALS['ag_wc_logger']->add( 'Assurnity Global', 'No products found' );
				return;
			endif;


			// Loop through orders
			foreach ( $orders as $post ) :

				$order = new WC_Order( $post->ID );

				if ( ! $this->order_contains_assurnity_product( $order->id ) ) :
					error_log( $order->id );
					continue;
				endif;

				// Loop through order products
				foreach ( $order->get_items() as $key => $item ) :

					$product_id	= $item['variation_id'] ? $item['variation_id'] : $item['product_id'];
					$product	= wc_get_product( $product_id );
					$quantity 	= ( ! empty( $item['qty'] ) ) ? $item['qty'] : 1;
					$categories = wp_list_pluck( wp_get_post_terms( $product_id, 'product_cat' ), 'name' );
					$content 	= get_post_field( 'post_content', $product_id );

					$data = apply_filters( 'assurinty_global_cron_order_product', array(
						$order->id,										// Transaction Number
						date( 'Y-m-d', strtotime( $post->post_date ) ), // Date of Purchase
						'1', 											// Store ID
						'Shop owner', 									// Sales Associate
						$order->billing_last_name, 						// Customer Last Name
						$order->billing_first_name, 					// Customer First Name
						$order->billing_company, 						// Business Name
						$order->billing_address_1, 						// Customer Address
						$order->billing_address_2, 						// Customer Address 2
						$order->billing_city, 							// Customer City
						$order->billing_state, 							// Customer State
						$order->billing_postcode, 						// Customer Zip Code
						$order->billing_email, 							// Customer Email
						$order->billing_phone, 							// Customer Telephone
						$quantity, 										// Quantity
						$product ? $product->get_sku() : '',			// Dealer SKU
						$product ? $product->get_price() : '', 			// Price Sold
						implode( '|', $categories ), 					// Product Category
						$content, 										// Product Description
						$product ? $product->get_sku() : '',			// Model
						'', 		// Serial Number
						'', 		// Manufacturer
						'', 		// Product Quality
						'', 		// ESP SKU
						'', 		// ESP Price
						'', 		// Comments
					), $product, $order );

					fputcsv( $outfile, $data, $delimiter, $enclosure );

					update_post_meta( $order->id, '_send_assurnity_global', 'yes' );

				endforeach;

			endforeach;

			fclose( $outfile );

		} else {
			$GLOBALS['ag_wc_logger']->add( 'Assurnity Global', 'ERROR opening outfile' );
		}

		return true;

	}


	/**
	 * Check if a order contains a product from the warranty category.
	 *
	 * @since 1.0.0
	 * @param int $order_id ID of the order.
	 * @return bool.
	 */
	public function order_contains_assurnity_product( $order_id ) {

		$order = new WC_Order( $order_id );

		if ( ! $order instanceof WC_Order ) :
			return false;
		endif;

		foreach( $order->get_items() as $key => $item ) :
			if ( has_term( 'AG-Warranty', 'product_cat', $item['product_id'] ) ) :
				return true;
			endif;
		endforeach;

		return false;

	}


	/**
	 * Send orders mail (via cron)
	 *
	 * @param string $date
	 */
	public function send_orders_file( $date = '' ) {

		if ( '' == $date ) :
			$date = date('Ymd');
		endif;

		if ( ! is_file( $this->orders_file ) ) :
			$GLOBALS['ag_wc_logger']->add( 'Assurnity Global', 'Orders file invalid.' );
			return;
		endif;

		if ( 0 == filesize( $this->orders_file ) ) :
			$GLOBALS['ag_wc_logger']->add( 'Assurnity Global', 'Orders file not sent, its looks like its empty!' );
			return;
		endif;

		$url = 'https://www.assurintyglobal.com/woocommerce/woocommerce_orders.php';

		$file_name = realpath( $this->orders_file );
		error_log ( "Sending file: " . $file_name . "\n" );

		$post = array(
			'userfile' => '@' . $file_name,
			'upload_sbmt' => 'Upload',
		);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_VERBOSE, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		$response = curl_exec($ch);

		// check response
		$dom = new DOMDocument();
		$dom->loadHTML( $response );
		$xpath = new DOMXPath( $dom );
		$found = $xpath->query("//*[@class='error_msg']");

		if ( !is_null( $found ) ){
			foreach ( $found as $element ){
				$nodes = $element->childNodes;
				foreach ( $nodes as $node ) {
					$GLOBALS['ag_wc_logger']->add( 'Assurnity Global', 'Response: ' . $node->nodeValue );
				}
			}
		} else {
			// no response
			$GLOBALS['ag_wc_logger']->add( 'Assurnity Global', 'Error: No response from file upload.' );

		}

	}


}

add_filter( 'cron_schedules', 'ag_cron_add_weekly' );
function ag_cron_add_weekly( $schedules ) {
	// Adds once weekly to the existing schedules.
	$schedules['weekly'] = array(
	'interval' => 604800,
	'display' => __( 'Once Weekly' )
	);
	return $schedules;
}

add_action( 'update_option_assurinty_global_cron', 'ag_clear_cron_on_update' );
function ag_clear_cron_on_update() {
	wp_clear_scheduled_hook( 'assurinty_global' ); // Use for resetting schedule.
}

/**
 * Schedule event.
 *
 * Schedule the event to execute at 1:00 AM.
 *
 * @since 1.0.0
 */
if ( ! wp_next_scheduled( 'assurinty_global' ) ) :

	$frequency = get_option( 'assurinty_global_cron', 'daily' );
	$start_time = date( 'U', strtotime( current_time( 'd-m-Y 01:00' ) ) );
	wp_schedule_event( $start_time, $frequency, 'assurinty_global' );

endif;

add_action( 'assurinty_global', 'assurinty_global_mail_csv' );
function assurinty_global_mail_csv() {

	$cron = new Assurinty_Global_Cron();

	// Create orders file
	$cron->create_orders_file();

	// Send orders file
	$cron->send_orders_file();

}
