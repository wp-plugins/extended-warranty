<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Assurinty_Global_Product_Exporter
 */

class Assurinty_Global_Product_Exporter {

    var $retailer_name = '';

    /**
     * constructor
     */
    function __construct(){

    }

    /**
     * Create CSV file with products in format required by ASURINTYglobal
     *
     * @return bool
     */
    public function create_file(){

	    $delimiter = '	';
        $enclosure  = '"';
	    $upload_dir = wp_upload_dir();
	    $target_dir = trailingslashit( $upload_dir['basedir'] ) . 'assurintyglobal/products/';
	    if( ! file_exists( $target_dir ) )
		    wp_mkdir_p( $target_dir );

        $filename   = str_replace(' ', '_', strtolower(trim( $this->retailer_name ))) . "_product_" . date('Ymd') . '.csv';
        $csvpath    = $target_dir . $filename;
        $outfile    = fopen( $csvpath, 'w' );

        if ($outfile) {

            /**
             * Loop through posts and output them.
             * Fields: SKU, Product Description, Product Price, Product Category
             */
            $args = array(
                'post_type' 		=> 'product',
                'posts_per_page'	=> -1,
            );

            $loop = new WP_Query( $args );
            if ( $loop->have_posts() ) {
                while ( $loop->have_posts() ) : $loop->the_post();
                    $product = new WC_Product( $loop->post->ID );
                    $product_cats = get_the_terms( $loop->post->ID, 'product_cat');
                    $_categories = array();
                    if ($product_cats){
                         foreach ($product_cats as $cat){
                            $_categories[] = $cat->name;
                        }
                    }
                    $data = array( $product->get_sku(),
	                                $product->id,
                                    get_the_content(),
                                    $product->get_regular_price(),
                                    implode("|",$_categories),
                    );
                    fputcsv( $outfile, $data, $delimiter, $enclosure );

                endwhile;
            } else {
                $GLOBALS['ag_wc_logger']->add( 'Assurnity Global', 'No products found' );
            }
            wp_reset_postdata();

            fclose( $outfile );

        } else {
            $GLOBALS['ag_wc_logger']->add( 'Assurnity Global', 'ERROR opening outfile' );
            return false;
        }
        return $csvpath;
    }

    /**
     *  Send file to ASURINTYglobal mail
     */
    public function send_file( $file ) {

	    $url = 'https://www.assurintyglobal.com/woocommerce/woocommerce_orders.php';

	    $file_name = realpath( $file );
	    $GLOBALS['ag_wc_logger']->add( 'Assurnity Global', 'Sending file: ' . $file_name );

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
