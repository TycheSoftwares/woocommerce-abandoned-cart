<?php 

// Load WP_List_Table if not loaded
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class wcal_Recover_Orders_Table extends WP_List_Table {

	/**
	 * Number of results to show per page
	 *
	 * @var string
	 * @since 2.5.2
	 */
	public $per_page = 30;

	/**
	 * URL of this page
	 *
	 * @var string
	 * @since 2.5.2
	 */
	public $base_url;

	/**
	 * Total number of recovered orders
	 *
	 * @var int
	 * @since 2.5.2
	 */
	public $total_count;
	
	
	/**
	 * Total number of abandoned orders
	 *
	 * @var int
	 * @since 2.5.2
	 */
	public $total_abandoned_cart_count;
	
	/**
	 * Total amount of abandoned orders
	 *
	 * @var int
	 * @since 2.5.2
	 */
	public $total_order_amount;
	
	/**
	 * Total number recovered orders
	 *
	 * @var int
	 * @since 2.5.2
	 */
	public $recovered_item;
	
	/**
	 * Total number recovred orders total
	 *
	 * @var int
	 * @since 2.5.2
	 */
	public $total_recover_amount;		

    /**
	 * Get things started
	 *
	 * @see WP_List_Table::__construct()
	 */
	public function __construct() {
		global $status, $page;
		// Set parent defaults
		parent::__construct( array(
		        'singular' => __( 'rec_abandoned_id', 'woocommerce-ac' ), //singular name of the listed records
		        'plural'   => __( 'rec_abandoned_ids', 'woocommerce-ac' ), //plural name of the listed records
				'ajax'     => false             			// Does this table support ajax?
		) );			
        $this->base_url = admin_url( 'admin.php?page=woocommerce_ac_page&action=stats' );
	}
	
    public function wcal_recovered_orders_prepare_items() {
		$columns                    = $this->get_columns();
		$hidden                     = array(); // No hidden columns
		$sortable                   = $this->recovered_orders_get_sortable_columns();
		$data                       = $this->wcal_recovered_orders_data();		
		$total_items                = $this->total_count;
		$total_abandoned_cart_count = $this->total_abandoned_cart_count;
		$total_order_amount         = $this->total_order_amount;
		$total_recover_amount       = $this->total_recover_amount;
		$recovered_item             = $this->recovered_item;
		$this->items                = $data;		
		$this->_column_headers      = array( $columns, $hidden, $sortable);		
		$this->set_pagination_args( array(
				'total_items' => $total_items,                  	// WE have to calculate the total number of items
				'per_page'    => $this->per_page,                     	// WE have to determine how many items to show on a page
				'total_pages' => ceil( $total_items / $this->per_page )   // WE have to calculate the total number of pages
		      )
		);
	}
	
	public function get_columns() {	    
	    $columns = array( 		        
                'user_name'       => __( 'Customer Name', 'woocommerce-ac' ),
		        'user_email_id'   => __( 'Email Address', 'woocommerce-ac' ),
				'created_on'      => __( 'Cart Abandoned Date', 'woocommerce-ac' ),				
	            'recovered_date'  => __( 'Cart Recovered Date' , 'woocommerce-ac'),
	            'order_total'     => __( 'Order Total', 'woocommerce-ac' )				
		);		
	   return apply_filters( 'wcal_recovered_orders_columns', $columns );
	}
	
	public function recovered_orders_get_sortable_columns() {
		$columns = array(
				'created_on'      => array( 'created_on', false ),
				'recovered_date'  => array( 'recovered_date',false)
		);
		return apply_filters( 'wcal_templates_sortable_columns', $columns );
	}
	
	/**
	 * Render the user name Column
	 *
	 * @access public
	 * @since 2.5.2
	 * @param array $abandoned_row_info Contains all the data of the template row 
	 * @return string Data shown in the Email column
	 * 
	 * This function used for individual delete of row, It is for hover effect delete.
	 */
	public function column_user_name( $recovered_orders_row_info ) {	
	    $row_actions  = array();
	    $value        = '';
	    $recovered_id = 0;
	    
	    if( isset( $recovered_orders_row_info->user_name ) ) {
    	    $recovered_id                = $recovered_orders_row_info->recovered_id ;
    	    $row_actions['view_details'] = "<a target=_blank href = post.php?post=$recovered_id&action=edit>". __( 'View Details', 'woocommerce-ac' )."</a>";
    	    $user_name                   = $recovered_orders_row_info->user_name;
            $value                       = $user_name . $this->row_actions( $row_actions );
	    }	
	    return apply_filters( 'wcal_recovered_orders_single_column', $value, $recovered_id, 'email' );
	}
    	
	public function wcal_recovered_orders_data() { 
		global $wpdb, $woocommerce;    		
		$wcal_class      = new woocommerce_abandon_cart_lite ();
		$number_decimal  = wc_get_price_decimals();
		
		if ( isset( $_POST['duration_select'] ) ) {
		    $duration_range = $_POST['duration_select'];
		}
		else {
		    $duration_range = "";
		}
		
		if ( $duration_range == "" ) {    		
		    if ( isset( $_GET['duration_select'] ) ) {
		        $duration_range = $_GET['duration_select'];
		    }
		}
		
		if ( $duration_range == "" ) {
		    $duration_range = "last_seven";
		}
		else {
		    $duration_range = "";
		}
        
		if ( isset( $_POST['start_date'] ) ) {
		    $start_date_range = $_POST['start_date'];
		}
		else {
		    $start_date_range = "";
		}
		
		if ( $start_date_range == "" ) {    		        		  
		    $start_date_range = $wcal_class->start_end_dates[$duration_range]['start_date'];    		    
		}
		
        if ( isset( $_POST['end_date'] ) ) $end_date_range = $_POST['end_date'];
		else $end_date_range = "";
		
		if ( $end_date_range == "" ) {
		    $end_date_range = $wcal_class->start_end_dates[$duration_range]['end_date'];
		}
		
		$start_date = strtotime( $start_date_range." 00:01:01" );
		$end_date   = strtotime( $end_date_range." 23:59:59" );
		
		$ac_cutoff_time          = get_option( 'ac_lite_cart_abandoned_time' );
		$cut_off_time            = $ac_cutoff_time * 60;
		$current_time            = current_time( 'timestamp' );
		$compare_time            = $current_time - $cut_off_time;
		
		$query_ac         = "SELECT * FROM " . $wpdb->prefix . "ac_abandoned_cart_history_lite 
		                      WHERE abandoned_cart_time >= %d AND abandoned_cart_time <= %d AND recovered_cart > 0 AND abandoned_cart_time <= '$compare_time' ORDER BY recovered_cart desc";
		$ac_results       = $wpdb->get_results( $wpdb->prepare( $query_ac, $start_date, $end_date ) );
		
		$query_ac_carts   = "SELECT * FROM " . $wpdb->prefix . "ac_abandoned_cart_history_lite 
		                     WHERE abandoned_cart_time >= %d AND abandoned_cart_time <= %d AND abandoned_cart_time <= '$compare_time' ";
		$ac_carts_results = $wpdb->get_results( $wpdb->prepare( $query_ac_carts, $start_date, $end_date ) );
		
		$recovered_item   = $recovered_total = $count_carts = $total_value = $order_total = 0;    		
		$return_recovered_orders = array();
		$per_page         = $this->per_page;
		$i                = 0;		
		foreach ( $ac_carts_results as $key => $value ) {    		  
	        $count_carts += 1;
	        $cart_detail = json_decode( $value->abandoned_cart_info );
	        $product_details = array();
	        if( isset( $cart_detail->cart ) ) {
	            $product_details = $cart_detail->cart;
	        }
	        $line_total = 0;
	
	        if ( isset( $product_details ) && count( $product_details ) > 0 && $product_details != false ) {    		
	            foreach ( $product_details as $k => $v ) {    		
	                if( $v->line_subtotal_tax != 0 && $v->line_subtotal_tax > 0 ) {
	                    $line_total = $line_total + $v->line_total + $v->line_subtotal_tax;
	                } else {
	                    $line_total = $line_total + $v->line_total;
	                }
	            }
	        }
	        $total_value += $line_total; 
		}
		$total_value                      = wc_price( $total_value );    		
		$this->total_order_amount         = $total_value ;
		$this->total_abandoned_cart_count = $count_carts ;    		
		$recovered_order_total            = 0;    		
		$this->total_recover_amount       = round( $recovered_order_total, $number_decimal )  ;    		
		$this->recovered_item             = 0;    		
		$table_data                       = "";
		
		foreach ( $ac_results as $key => $value ) {    		        		
		    if( $value->recovered_cart != 0 ) {    		        
		        $return_recovered_orders[$i] = new stdClass();
		        
		        $recovered_id       = $value->recovered_cart;
		        $rec_order          = get_post_meta( $recovered_id );
		        $woo_order = array();
		        try{
		        	$woo_order          = new WC_Order( $recovered_id );
		    	
					if( version_compare( $woocommerce->version, '3.0.0', ">=" ) ) {
	    	        	$order = get_post( $recovered_id );
						$recovered_date = strtotime ( $order->post_date );
						$recovered_date_format    = date_i18n( get_option( 'date_format' ), $recovered_date );
        				$recovered_time_format 	= date_i18n( get_option( 'time_format' ), $recovered_date ); 
        						
						$recovered_date_new = $recovered_date_format . ' ' . $recovered_time_format;
	    	        }else{
	    	        	$recovered_date     = strtotime( $woo_order->order_date );
	    	        	$recovered_date_format        = date_i18n( get_option( 'date_format' ), $recovered_date );
        				$recovered_time_format 	    = date_i18n( get_option( 'time_format' ), $recovered_date ); 
	    	        	$recovered_date_new = $recovered_date_format . ' ' . $recovered_time_format;
	    	    	}

			        $recovered_item    += 1;
			
			        if ( isset( $rec_order ) && $rec_order != false ) {
			            $recovered_total += $rec_order['_order_total'][0];
			        }
			        $date_format      	   = date_i18n( get_option( 'date_format' ), $value->abandoned_cart_time );
        			$time_format 	  	   = date_i18n( get_option( 'time_format' ), $value->abandoned_cart_time ); 
			        $abandoned_date        = $date_format . ' ' . $time_format;
			        $abandoned_order_id    = $value->id;
			        $billing_first_name    = $billing_last_name = $billing_email = '';
			        $recovered_order_total = 0;
			
			        if ( isset( $rec_order['_billing_first_name'][0] ) ) {
			            $billing_first_name = $rec_order['_billing_first_name'][0];
			        }
			
			        if ( isset( $rec_order['_billing_last_name'][0] ) ) {
			            $billing_last_name = $rec_order['_billing_last_name'][0];
			        }
			
			        if ( isset( $rec_order['_billing_email'][0] ) ) {
			            $billing_email = $rec_order['_billing_email'][0];
			        }
			
			        if ( isset( $rec_order['_order_total'][0] ) ) {
			            $recovered_order_total = $rec_order['_order_total'][0];
			        }
			        
			        $return_recovered_orders[ $i ]->user_name          = $billing_first_name . " " . $billing_last_name ;
			        $return_recovered_orders[ $i ]->user_email_id      = $billing_email;
			        $return_recovered_orders[ $i ]->created_on         = $abandoned_date;
			        $return_recovered_orders[ $i ]->recovered_date     = $recovered_date_new;
			        $return_recovered_orders[ $i ]->recovered_id       = $recovered_id;
			        $return_recovered_orders[ $i ]->recover_order_date = $recovered_date;
			        $return_recovered_orders[ $i ]->abandoned_date     = $value->abandoned_cart_time;
			        $return_recovered_orders[ $i ]->order_total        = wc_price($recovered_order_total);
			            		        
			        $this->recovered_item = $recovered_item;
			        $this->total_recover_amount = round( ( $recovered_order_total + $this->total_recover_amount ) , $number_decimal );    		        
			        $i++;
			    }catch (Exception $e){
			    	
			    } 

		    }   		    
		}
		$templates_count   = count( $return_recovered_orders );
		$this->total_count = $templates_count;
    	// sort for order date
		if( isset( $_GET['orderby'] ) && $_GET['orderby'] == 'created_on' ) {
    	   if( isset( $_GET['order'] ) && $_GET['order'] == 'asc' ) {
				usort( $return_recovered_orders, array( __CLASS__ , "wcal_class_recovered_created_on_asc" ) ); 
			} else {
				usort( $return_recovered_orders, array( __CLASS__ , "wcal_class_recovered_created_on_dsc" ) );
			}
		}
		// sort for customer name
		else if ( isset( $_GET['orderby']) && $_GET['orderby'] == 'recovered_date' ) {
		if( isset( $_GET['order'] ) && $_GET['order'] == 'asc' ) {
				usort( $return_recovered_orders, array( __CLASS__ , "wcal_class_recovered_date_asc" ) );
			}else {
				usort( $return_recovered_orders, array( __CLASS__ , "wcal_class_recovered_date_dsc" ) );
			}
		}
		// Pagination per page
		if( isset( $_GET['paged'] ) && $_GET['paged'] > 1 ) {
		    $page_number = $_GET['paged'] - 1;
		    $k = $per_page * $page_number;
		} else {
		    $k = 0;
		}
		$return_recovered_orders_display = array();
		for( $j = $k; $j < ( $k+$per_page ); $j++ ) {
		    if( isset( $return_recovered_orders[$j] ) ) {
		        $return_recovered_orders_display[$j] = $return_recovered_orders[$j];
		    } else {
		        break;
		    }
		}		
	return apply_filters( 'wcal_recovered_orders_table_data', $return_recovered_orders_display );
	}
	
	function wcal_class_recovered_created_on_asc( $value1,$value2 ) {
	    return $value1->abandoned_date - $value2->abandoned_date;
	}
	
	function wcal_class_recovered_created_on_dsc( $value1,$value2 ) {
	    return $value2->abandoned_date - $value1->abandoned_date;
	}
	
	function wcal_class_recovered_date_asc( $value1,$value2 ) {

		global $woocommerce;
		if( version_compare( $woocommerce->version, '3.0.0', ">=" ) ) {
			$value1						= $value1->recover_order_date;
			$value2						= $value2->recover_order_date;
			
			$value1->recover_order_date = $value1->getTimestamp();
			$value2->recover_order_date = $value2->getTimestamp();
		}
	    return $value1->recover_order_date - $value2->recover_order_date;
	}
	
	function wcal_class_recovered_date_dsc( $value1,$value2 ) {
		global $woocommerce;
		if( version_compare( $woocommerce->version, '3.0.0', ">=" ) ) {
			$value1						= $value1->recover_order_date;
			$value2						= $value2->recover_order_date;
			
			$value1->recover_order_date = $value1->getTimestamp();
			$value2->recover_order_date = $value2->getTimestamp();
		}
	    return $value2->recover_order_date - $value1->recover_order_date;
	}
	
	public function column_default( $wcal_abandoned_orders, $column_name ) {
	    $value = '';
	    switch ( $column_name ) {      
	        case 'user_email_id' :
			    if( isset( $wcal_abandoned_orders->user_email_id ) ) {
			        
			        $user_email_id = "<a href= mailto:$wcal_abandoned_orders->user_email_id>". $wcal_abandoned_orders->user_email_id."</a>" ;
				    $value = $user_email_id;
			    }
				break;
			case 'created_on' :
			    if( isset( $wcal_abandoned_orders->created_on ) ) {
			       $value = $wcal_abandoned_orders->created_on;
			    }
				break;
			case 'recovered_date' :
			    if( isset( $wcal_abandoned_orders->recovered_date ) ) {
	 			   $value = $wcal_abandoned_orders->recovered_date;
			    }
				break;
			case 'order_total' :
			    if( isset( $wcal_abandoned_orders->order_total ) ) {
			     $value = $wcal_abandoned_orders->order_total;
			    }
			    break;
		    default:    
				$value = isset( $wcal_abandoned_orders->$column_name ) ? $wcal_abandoned_orders->$column_name : '';
				break;
	    }
		return apply_filters( 'wcal_recovered_orders_column_default', $value, $wcal_abandoned_orders, $column_name );
	}
}
?>