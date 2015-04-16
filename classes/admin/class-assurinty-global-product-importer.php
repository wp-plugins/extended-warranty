<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Assurinty_Global_Product_Importer
 */

class Assurinty_Global_Product_Importer {

    var $categories = array();
    var $warranty_products = array();

    /**
     * Constructor.
     */
    public function __construct(){

    }

    /**
     *  Parse the products.csv file that is shipped with the plugin.
     *
     */
    public function parse_csv(){

        $plugin_dir = plugin_dir_path(__FILE__);
        $plugin_dir = str_replace('classes/admin/', '', $plugin_dir);
        $file = $plugin_dir . 'assets/products.csv';
        $delimiter = ',';
        $enclosure = '"';

        if ( ( $handle = fopen( $file, "r" ) ) !== FALSE ) {

            fgetcsv( $handle, 0, $delimiter, $enclosure ); // remove header
            $parsed_data = array();

            while ( ( $postmeta = fgetcsv( $handle, 0, $delimiter, $enclosure ) ) !== FALSE ) {
                $parsed_data[] = $postmeta;
                unset( $postmeta );
            }
            $this->warranty_products = $parsed_data;
            fclose( $handle );
        }

    }

    /**
     * Import warranty products from the products.csv based on the category selections in the sign up form.
     *
     * @access public
     */
    public function import_warranty_products(){

		set_time_limit ( 0 );
        /**
         * 0 Item
         * 1 Description
         * 2 Department
         * 3 SKU
         * 4 Retailer Cost
         * 5 RETAIL PRICE
         */

        foreach ( $this->warranty_products as $product ) {

			if ( $this->product_sku_exists( $product[3] ) ) {
				$GLOBALS['ag_wc_logger']->add( 'Assurnity Global',  'Product already Exists ' . $product[3] );
				continue;
			}

			$GLOBALS['ag_wc_logger']->add( 'Assurnity Global', 'Product doesn\'t exists yet ' . $product[3] );

			if ( ! $attach_id = $this->image_exists( 'Assurinty Logo' ) ) :

		            // Add logo file to Media
		            $logo_file 		= str_replace('classes/admin/', '', plugin_dir_path(__FILE__)) . 'assets/assurinty_global_logo.jpg';
		            $upload_dir 	= wp_upload_dir();
		            $uploaded_file 	= trailingslashit($upload_dir['path']) . basename($logo_file);

		            copy( $logo_file, $uploaded_file );
		            $file_type = wp_check_filetype( basename( $logo_file ), null );

		            $attachment = array(
		                'guid'           	=> $upload_dir['url'] . '/' . basename( $logo_file),
		                'post_mime_type'    => $file_type['type'],
		                'post_title'        => 'Assurinty Logo',
		                'post_content'      => '',
		                'post_status'       => 'inherit'
		            );
		            $attach_id = wp_insert_attachment( $attachment, $uploaded_file );

		            require_once( ABSPATH . 'wp-admin/includes/image.php' );

		            // Generate the metadata for the attachment, and update the database record.
		            $attach_data = wp_generate_attachment_metadata( $attach_id, $uploaded_file );
		            wp_update_attachment_metadata( $attach_id, $attach_data );

			endif;

            // Insert product if category is selected
            if ( in_array($product[2], array( 'Warranty' ) ) ) {
                $postdata = array(
                    'post_author'    => get_current_user_id(),
                    'post_date'      => '',
                    'post_date_gmt'  => '',
                    'post_content'   => $product[1],
                    'post_title'     => $product[0],
                    'post_name'      => $product[0],
                    'post_status'    => 'publish',
                    'post_type'      => 'product',
                );

                $post_id = wp_insert_post( $postdata, true );

                if ( is_wp_error( $post_id ) ) {
                    unset( $product );
                } else {
                    // worked update post meta
                    update_post_meta( $post_id, '_sku', maybe_unserialize( trim( $product[3] ) ) );
                    update_post_meta( $post_id, '_regular_price', str_replace( ',', '', ( wc_clean( $product[5] ) ) ) );
					update_post_meta( $post_id, '_price', str_replace( ',', '', ( wc_clean( $product[5] ) ) ) );
                    update_post_meta( $post_id, '_thumbnail_id', $attach_id );
                    update_post_meta( $post_id, '_visibility', 'hidden' );
					update_post_meta( $post_id, '_virtual', 'no' );
					update_post_meta( $post_id, '_downloadable', 'no' );
					update_post_meta( $post_id, '_weight', '' );
					update_post_meta( $post_id, '_length', '' );
					update_post_meta( $post_id, '_width', '' );
					update_post_meta( $post_id, '_height', '' );
					update_post_meta( $post_id, '_stock', '' );
					update_post_meta( $post_id, '_stock_status', 'instock' );
					update_post_meta( $post_id, '_sale_price', '' );
					update_post_meta( $post_id, '_sale_price_dates_from', '' );
					update_post_meta( $post_id, '_sale_price_dates_to', '' );
					update_post_meta( $post_id, '_download_limit', '' );
					update_post_meta( $post_id, '_download_expiry', '' );
					update_post_meta( $post_id, '_downloadable_files', '' );

					wp_set_object_terms( $post_id, array( 'AG-Warranty' ), 'product_cat' );

                    unset( $product );
                }
            } else {
                $GLOBALS['ag_wc_logger']->add( 'Assurnity Global', 'skipping product: ' . $product[3] );
            }

        }
    }


    /**
     * Check if a product (sku) already exists in the DB
     *
     * @param $sku
     * @return bool
     */
    public function product_sku_exists( $sku ) {

	    $post_args = array(
		    'posts_per_page' 	=> '1',
		    'post_type'			=> 'product',
		    'meta_key'			=> '_sku',
		    'meta_value'		=> $sku,
	    );
	    $posts = get_posts( $post_args );

	    if ( is_array( $posts ) && empty( $posts ) ) :
	    	return false;
	    endif;

	    return true;

    }

    /**
     * Check if image exists already
     *
     * @param $filename
     *
     * @return bool|int
     */
    public function image_exists( $filename ) {

	    $filename 	= preg_replace( '/\.(.*)/', '', $filename );
		$image 		= get_page_by_title( $filename, OBJECT, 'attachment' );

		if ( isset( $image->ID ) ) :
			return $image->ID;
		else :
			return false;
		endif;

    }

}
