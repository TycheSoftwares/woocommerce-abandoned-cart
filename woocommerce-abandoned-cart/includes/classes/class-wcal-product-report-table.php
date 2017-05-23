<?php 

if( session_id() === '' ){
    //session has not started
    session_start();
}
// Load WP_List_Table if not loaded
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class WCAL_Product_Report_Table extends WP_List_Table {

	/**
	 * Number of results to show per page
	 *
	 * @var string
	 * @since 2.5.3
	 */
	public $per_page = 30;

	/**
	 * URL of this page
	 *
	 * @var string
	 * @since 2.5.3
	 */
	public $base_url;

	/**
	 * Total number of recovred orders
	 *
	 * @var int
	 * @since 2.5.3
	 */
	public $total_count;
	
	
	/**
	 * Total number of recovred orders
	 *
	 * @var int
	 * @since 2.5.3
	 */
	public $open_emails;
	
	/**
	 * Total amount of abandoned orders
	 *
	 * @var int
	 * @since 2.5.3
	 */
	public $link_click_count;
	
	/**
	 * Total number recovred orders
	 *
	 * @var int
	 * @since 2.5.3
	 */
	public $start_date_db;
	
	/**
	 * Total number recovred orders total
	 *
	 * @var int
	 * @since 2.5.3
	 */
	public $end_date_db;
	
	public $duration;
	
    /**
	 * Get things started
	 *
	 * @see WP_List_Table::__construct()
	 */
	public function __construct() {
		global $status, $page;
		// Set parent defaults
		parent::__construct( array(
		        'singular' => __( 'product_id', 'woocommerce-ac' ), //singular name of the listed records
		        'plural'   => __( 'product_ids', 'woocommerce-ac' ), //plural name of the listed records
				'ajax'      => false             			// Does this table support ajax?
		) );		
		$this->base_url = admin_url( 'admin.php?page=woocommerce_ac_page&action=stats' );
	}
	
	public function wcal_product_report_prepare_items() {
		$columns               = $this->get_columns();
		$hidden                = array(); // No hidden columns		
		$data                  = $this->wcal_product_report_data ();		
 		$total_items           = $this->total_count;
        $this->items           = $data;
		$this->_column_headers = array( $columns, $hidden);
		$this->set_pagination_args( array(
				 'total_items' => $total_items,                  	// WE have to calculate the total number of items
				 'per_page'    => $this->per_page,                     	// WE have to determine how many items to show on a page
				 'total_pages' => ceil( $total_items / $this->per_page )   // WE have to calculate the total number of pages
		       )
		);		
	}
	
	public function get_columns() {	    
	    $columns = array( 		        
	            'product_name'     => __( 'Product Name', 'woocommerce-ac' ),
                'abandoned_number' => __( 'Number of Times Abandoned', 'woocommerce-ac' ),
		        'recover_number'   => __( 'Number of Times Recovered', 'woocommerce-ac' )				
	    );		
	   return apply_filters( 'wcal_product_report_columns', $columns );
	}
	
    /**
	 * Render the user name Column
	 *
	 * @access public
	 * @since 2.5.3
	 * @param array $abandoned_row_info Contains all the data of the template row 
	 * @return string Data shown in the Email column
	 * 
	 * This function used for individual delete of row, It is for hover effect delete.
	 */
	public function wcal_product_report_data () { 
		global $wpdb;    		
		$wcal_class            = new woocommerce_abandon_cart_lite ();
		$per_page              = $this->per_page;
		$i                     = 0;    		
		$order                 = "desc";
		$query                 = "SELECT abandoned_cart_time, abandoned_cart_info, recovered_cart FROM `" . $wpdb->prefix . "ac_abandoned_cart_history_lite` ORDER BY recovered_cart DESC";
		$recover_query         = $wpdb->get_results( $query );
		$rec_carts_array       = array ( );
		$recover_product_array = array( );
		$return_product_report = array();
		
		foreach( $recover_query as $recovered_cart_key => $recovered_cart_value ) {
		    $recovered_cart_info = json_decode( $recovered_cart_value->abandoned_cart_info );
		    $recovered_cart_dat  = json_decode( $recovered_cart_value->recovered_cart);		    
		    $cart_update_time    = $recovered_cart_value->abandoned_cart_time;
		    $quantity_total      = 0;
		    $cart_details        = array();
		    if( isset( $recovered_cart_info->cart ) ){
		        $cart_details = $recovered_cart_info->cart;
		    }
		    if ( count( $cart_details ) > 0) {    		        
		        foreach ( $cart_details as $k => $v ) {    		
		            $quantity_total = $quantity_total + $v->quantity;
		        }
		    }
		    			  
		    $ac_cutoff_time = get_option( 'ac_lite_cart_abandoned_time' );
		    $cut_off_time   = $ac_cutoff_time * 60 ;
		    $current_time   = current_time( 'timestamp' );
		    $compare_time   = $current_time - $cart_update_time;
		    if ( is_array( $recovered_cart_info ) || is_object( $recovered_cart_info ) ) {
		        foreach ( $recovered_cart_info as $rec_cart_key => $rec_cart_value ) {
		            foreach ( $rec_cart_value as $rec_product_id_key => $rec_product_id_value ) {
		                $product_id	= $rec_product_id_value->product_id;
		                if ( $compare_time > $cut_off_time ) {
		                    $rec_carts_array [] = $product_id;
		                }
		                if($recovered_cart_dat != 0) {
		                    $recover_product_array[] = $product_id;    		                     
		                }
		            }
		        }
		    }
		}
		
		$count              = array_count_values( $rec_carts_array );
		$count1             = $count;
		$count_new          = $wcal_class->bubble_sort_function ( $count1 ,$order );
		$recover_cart       = "0";
		$count_css          = 0;
		$chunck_array       = array_chunk( $count_new,10, true );  // keep True for retaing the Array Index number which is product ids in our case.    		
		$chunck_array_value = array();

		foreach ( $chunck_array as $chunck_array_key => $chunck_array_value ) {    		    
		    foreach ( $chunck_array_value as $k => $v ) {    		    
    		    $return_product_report[$i] = new stdClass();    		    
    		    $prod_name                 = get_post( $k );
    		    if ( NULL != $prod_name || '' != $prod_name ) {
        		    $product_name          = $prod_name->post_title;
        		    $abandoned_count       = $v;
        		    $recover               = array_count_values( $recover_product_array );
        		    foreach ( $recover as $ke => $ve ) {
        		        if( array_key_exists ( $ke, $count ) ) {    		             
        		            if ( $ke == $k ) {
        		                $recover_cart = $ve;
        		            }
        		        }
        		        if( ! array_key_exists ( $k, $recover ) ) {
        		            $recover_cart = "0";
        		        }
        		    }
        		    
        		    $return_product_report[ $i ]->product_name     = $product_name ;
        		    $return_product_report[ $i ]->abandoned_number = $abandoned_count;
        		    $return_product_report[ $i ]->recover_number   = $recover_cart;
        		    $return_product_report[ $i ]->product_id       = $k;
        		    $i++;  
    		    }  		    
		    }
		}			
		$this->total_count = count ( $return_product_report ) > 0 ? count ( $return_product_report )  : 0 ;     
		   
		// Pagination per page
		if( isset( $_GET['paged'] ) && $_GET['paged'] > 1 ) {
		    $page_number = $_GET['paged'] - 1;
		    $k = $per_page * $page_number;
		} else {
		    $k = 0;
		}
		$return_product_report_display = array();
		for( $j = $k; $j < ( $k+$per_page ); $j++ ) {
		    if( isset( $return_product_report[$j] ) ) {
		        $return_product_report_display[$j] = $return_product_report[$j];
		    } else {
		        break;
		    }
		}		
	return apply_filters( 'wcal_product_report_table_data', $return_product_report_display );
	}
	
	public function column_default( $wcal_sent_emails, $column_name ) {
	    $value = '';
	    switch ( $column_name ) {
	        
	        case 'product_name' :
			    if( isset( $wcal_sent_emails->product_name ) ) {			         
			        $value = "<a href= post.php?post=$wcal_sent_emails->product_id&action=edit title = product name > $wcal_sent_emails->product_name </a>";
			    }
				break;
			
			case 'abandoned_number' :
			    if( isset( $wcal_sent_emails->abandoned_number ) ) {
			       $value = $wcal_sent_emails->abandoned_number;
			    }
				break;
			
			case 'recover_number' :
			    if( isset( $wcal_sent_emails->recover_number ) ) {
			       $value = $wcal_sent_emails->recover_number;
			    }
				break;
			default:
			    
				$value = isset( $wcal_sent_emails->$column_name ) ? $wcal_sent_emails->$column_name : '';
				break;
	    }
		
		return apply_filters( 'wcal_product_report_column_default', $value, $wcal_sent_emails, $column_name );
	}
}
?>