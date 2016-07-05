<?php 

// Load WP_List_Table if not loaded
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class WACP_Abandoned_Orders_Table extends WP_List_Table {

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
	 * Total number of bookings
	 *
	 * @var int
	 * @since 2.5.2
	 */
	public $total_count;

    /**
	 * Get things started
	 *
	 * @see WP_List_Table::__construct()
	 */
	public function __construct() {

		global $status, $page;

		// Set parent defaults
		parent::__construct( array(
		        'singular' => __( 'abandoned_order_id', 'woocommerce-ac' ), //singular name of the listed records
		        'plural'   => __( 'abandoned_order_ids', 'woocommerce-ac' ), //plural name of the listed records
				'ajax'      => false             			// Does this table support ajax?
		) );
		$this->process_bulk_action();
        $this->base_url = admin_url( 'admin.php?page=woocommerce_ac_page' );
	}
	
	public function wcap_abadoned_order_prepare_items() {

		$columns  = $this->get_columns();
		$hidden   = array(); // No hidden columns
		$sortable = $this->get_sortable_columns();
		$this->total_count = 0;
		$data     = $this->wacp_abandoned_cart_lite_data();
		$this->_column_headers = array( $columns, $hidden, $sortable);
		$total_items           = $this->total_count;
		
		if ( count($data) > 0 ){
		  $this->items = $data;
		}else{
		    $this->items = array();
		}
		$this->set_pagination_args( array(
				'total_items' => $total_items,                  	// WE have to calculate the total number of items
				'per_page'    => $this->per_page,                     	// WE have to determine how many items to show on a page
				'total_pages' => ceil( $total_items / $this->per_page )   // WE have to calculate the total number of pages
		      )
		);
	}
	
	public function get_columns() {
	    
	  
	    $columns = array();
		$columns = array(
 		        'cb'                => '<input type="checkbox" />',
                'id'                => __( 'Id', 'woocommerce-ac' ),
		        'email'             => __( 'Email Address', 'woocommerce-ac' ),
				'customer'     		=> __( 'Customer', 'woocommerce-ac' ),
				'order_total'  		=> __( 'Order Total', 'woocommerce-ac' ),		        
				'date'              => __( 'Abandoned Date', 'woocommerce-ac' ),
				'status'            => __( 'Status of Cart', 'woocommerce-ac' )
		);
		
		return apply_filters( 'wcap_abandoned_orders_columns', $columns );
	}
	
	/*** 
	 * It is used to add the check box for the items
	 */
	function column_cb( $item ){
	    
	    $abadoned_order_id = '';
	    if( isset($item->id) && "" != $item->id ){
	       $abadoned_order_id = $item->id; 
	    }
	    return sprintf(
	        '<input type="checkbox" name="%1$s[]" value="%2$s" />',
	        'abandoned_order_id',
	        $abadoned_order_id
	    );
	}
	
	public function get_sortable_columns() {
		$columns = array(
				'date' 			=> array( 'date', false ),
				'status'		=> array( 'status',false),
		);
		return apply_filters( 'wcap_abandoned_orders_sortable_columns', $columns );
	}
	
	/**
	 * Render the Email Column
	 *
	 * @access public
	 * @since 2.4.8
	 * @param array $abadoned_row_info Contains all the data of the abandoned order tabs row 
	 * @return string Data shown in the Email column
	 * 
	 * This function used for individual delete of row, It is for hover effect delete.
	 */
	public function column_email( $abadoned_row_info ) {
	
	    $row_actions = array();
	    $value = '';
	    $abadoned_order_id = 0;
	    if( isset($abadoned_row_info->email) ){
	    
	    $abadoned_order_id = $abadoned_row_info->id ; 
	    $row_actions['edit']   = '<a href="' . wp_nonce_url( add_query_arg( array( 'action' => 'orderdetails', 'id' => $abadoned_row_info->id ), $this->base_url ), 'abandoned_order_nonce') . '">' . __( 'View order', 'woocommerce-ac' ) . '</a>';
	    $row_actions['delete'] = '<a href="' . wp_nonce_url( add_query_arg( array( 'action' => 'wcap_delete', 'abandoned_order_id' => $abadoned_row_info->id ), $this->base_url ), 'abandoned_order_nonce') . '">' . __( 'Delete', 'woocommerce-ac' ) . '</a>';
	
	    $email = $abadoned_row_info->email;

	    $value = $email . $this->row_actions( $row_actions );
	    
	    }
	
	    return apply_filters( 'wcap_abandoned_orders_single_column', $value, $abadoned_order_id, 'email' );
	}
    
	public function wacp_abandoned_cart_lite_data() { 
    		global $wpdb;
    		
    		$return_abadoned_orders = array();
    		$per_page = $this->per_page;
    		$results  = array();
    	 
    		$blank_cart_info       =  '{"cart":[]}';
    		$blank_cart_info_guest =  '[]';
    		
    		// non-multisite - regular table name
    		 $query = "SELECT wpac . * , wpu.user_login, wpu.user_email
					  FROM `".$wpdb->prefix."ac_abandoned_cart_history_lite` AS wpac
					  LEFT JOIN ".$wpdb->base_prefix."users AS wpu ON wpac.user_id = wpu.id
					  WHERE recovered_cart = '0'
					  AND wpac.abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND wpac.abandoned_cart_info NOT LIKE '$blank_cart_info_guest' ORDER BY wpac.abandoned_cart_time DESC";        

            $results = $wpdb->get_results($query);
			
    		$i = 0;
		   		
    		foreach ( $results as $key => $value ) {    
    		
    		    if ( $value->user_type == "GUEST" ) {
    		        $query_guest   = "SELECT * from `" . $wpdb->prefix . "ac_guest_abandoned_cart_history_lite` WHERE id = %d";
    		        $results_guest = $wpdb->get_results( $wpdb->prepare( $query_guest, $value->user_id ) );
    		    }
    		    $abandoned_order_id = $value->id;
    		    $user_id            = $value->user_id;
    		    $user_login         = $value->user_login;
    	
    		    if ( $value->user_type == "GUEST" ) {
    		
    		        if ( isset( $results_guest[0]->email_id ) ) $user_email = $results_guest[0]->email_id;
    		
    		        if ( isset( $results_guest[0]->billing_first_name ) ) $user_first_name = $results_guest[0]->billing_first_name;
    		        else $user_first_name = "";
    		
    		        if ( isset( $results_guest[0]->billing_last_name ) ) $user_last_name = $results_guest[0]->billing_last_name;
    		        else $user_last_name = "";
    		        
    		    } else {
    		        
    		        $user_email_billing = get_user_meta( $value->user_id, 'billing_email', true );
    		        if( $user_email_billing != '' ){
    		            $user_email = $user_email_billing;
    		        }else{
    		            $user_data = get_userdata( $value->user_id );
    		            $user_email = $user_data->user_email;
    		        }
        		   
    		        $user_first_name = '';
    		        $user_first_name_temp = get_user_meta($value->user_id, 'billing_first_name', true );
    		        if ( isset( $user_first_name_temp ) &&  '' != $user_first_name_temp) {
    		            $user_first_name = $user_first_name_temp;
    		        }else {
    		            $user_first_name = get_user_meta($value->user_id, 'first_name', true );
    		        }
    		
    		        $user_last_name = '';
    		        $user_last_name_temp = get_user_meta($value->user_id, 'billing_last_name', true);
    		        if ( isset( $user_last_name_temp ) && '' !=  $user_last_name_temp) {
    		            $user_last_name = $user_last_name_temp;
    		        }else {
    		            $user_last_name = get_user_meta($value->user_id, 'last_name', true);;
    		        }
    		    }
    		
    		    $cart_info = json_decode( $value->abandoned_cart_info );
    		    $order_date = "";
    		    $cart_update_time = $value->abandoned_cart_time;
    		
    		    if ( $cart_update_time != "" && $cart_update_time != 0 ) {
    		        $order_date = date( 'd M, Y h:i A', $cart_update_time );
    		    }
    		
    		    $ac_cutoff_time = get_option( 'ac_lite_cart_abandoned_time' );
    		    $cut_off_time   = $ac_cutoff_time * 60;
    		    $current_time   = current_time( 'timestamp' );
    		    $compare_time   = $current_time - $cart_update_time;
    		    $cart_details   = array();
    		    if( isset( $cart_info->cart ) ){
    		        $cart_details = $cart_info->cart;
    		    }
    		    $line_total = 0;
    		   
    		    if ( count( $cart_details ) > 0 ) {
    		
    		        foreach ( $cart_details as $k => $v ) {
    		     
    		            if( $v->line_subtotal_tax != 0 && $v->line_subtotal_tax > 0 ) {
    		                $line_total = $line_total + $v->line_total + $v->line_subtotal_tax;
    		            } else {
    		                $line_total = $line_total + $v->line_total;
    		            }
    		        }
    		    }
    		    $line_total = round( $line_total, 2 );
    		    $quantity_total = 0;
    		
    		    if ( count( $cart_details ) > 0) {
    		         
    		        foreach ( $cart_details as $k => $v ) {
    		            $quantity_total = $quantity_total + $v->quantity;
    		        }
    		    }
    		
    		    if ( 1 == $quantity_total ) {
    		        $item_disp = __("item", "woocommerce-ac");
    		    } else {
    		        $item_disp = __("items", "woocommerce-ac");
    		    }
    		    
    		    if( $value->cart_ignored == 0 && $value->recovered_cart == 0 ) {
    		        $ac_status = __("Abandoned", "woocommerce-ac");
    		    } elseif( $value->cart_ignored == 1 && $value->recovered_cart == 0 ) {
    		        $ac_status = __("Abandoned but new","woocommerce-ac")."</br>". __("cart created after this", "woocommerce-ac");
    		    } else {
    		        $ac_status = "";
    		    }
    		    
    		    if ( $compare_time > $cut_off_time && $ac_status != "" ) {
                   
    		        $return_abadoned_orders[$i] = new stdClass();
    		        
                    if( $quantity_total > 0 ) {
                        
                        $abandoned_order_id                        =  $abandoned_order_id;
                        $customer_information                      = $user_first_name . " ".$user_last_name;
                        $return_abadoned_orders[ $i ]->id          = $abandoned_order_id;
                        $return_abadoned_orders[ $i ]->email       = $user_email;
                        $return_abadoned_orders[ $i ]->customer    = $customer_information;
                        $return_abadoned_orders[ $i ]->order_total = get_woocommerce_currency_symbol() . "" . $line_total;
                        $return_abadoned_orders[ $i ]->date        = $order_date;
                        $return_abadoned_orders[ $i ]->status      = $ac_status;
                        
                   }
                   // To get the abadoned orders count
                   $this->total_count = count ($return_abadoned_orders);
                   $i++;
              }
            
        }
		                    
        // sort for order date
		 if (isset($_GET['orderby']) && $_GET['orderby'] == 'date') {
    		if (isset($_GET['order']) && $_GET['order'] == 'asc') {
    				usort( $return_abadoned_orders, array( __CLASS__ ,"wcap_class_order_date_asc") ); 
    			}
    			else {
    				usort( $return_abadoned_orders, array( __CLASS__ ,"wcap_class_order_date_dsc") );
    			}
		}
		
		// sort for customer name
		else if ( isset( $_GET['orderby']) && $_GET['orderby'] == 'status' ) {
		if ( isset( $_GET['order'] ) && $_GET['order'] == 'asc' ) {
				usort( $return_abadoned_orders, array( __CLASS__ ,"wcap_class_status_asc" ) );
			}
			else {
				usort( $return_abadoned_orders, array( __CLASS__ ,"wcap_class_status_dsc" ) );
			}
		}
		
		if (isset($_GET['paged']) && $_GET['paged'] > 1) {
			$page_number = $_GET['paged'] - 1;
			$k = $per_page * $page_number;
		}
		else {
			$k = 0;
		}
		$return_abadoned_orders_display = array();
		for ($j = $k;$j < ($k+$per_page);$j++) {
			if (isset($return_abadoned_orders[$j])) {
				$return_abadoned_orders_display[$j] = $return_abadoned_orders[$j];
			}
			else {
				break;
			}
		}
		return apply_filters( 'wcap_abandoned_orders_table_data', $return_abadoned_orders_display );
	}
	function wcap_class_order_date_asc($value1,$value2) {
	    
	    $date_two =  $date_one = '';
	    $value_one = $value1->date;
	    $value_two = $value2->date;
	    
	    $date_formatted_one  =   date_create_from_format( 'd M, Y h:i A', $value_one );
	    if ( isset( $date_formatted_one ) && $date_formatted_one != '' ) {
	        $date_one = date_format( $date_formatted_one, 'Y-m-d' );
	    }
	    
	    $date_formatted_two  =   date_create_from_format( 'd M, Y h:i A', $value_two );
	    if ( isset( $date_formatted_two ) && $date_formatted_two != '' ) {
	        $date_two = date_format( $date_formatted_two, 'Y-m-d' );
	    }
	    return strtotime($date_one) - strtotime($date_two);
	}
	function wcap_class_order_date_dsc($value1,$value2) {
	    
	    $date_two =  $date_one = '';
	    $value_one = $value1->date;
	    $value_two = $value2->date;
	     
	    $date_formatted_one = date_create_from_format( 'd M, Y h:i A', $value_one );
	    if ( isset( $date_formatted_one ) && $date_formatted_one != '' ) {
	        $date_one = date_format( $date_formatted_one, 'Y-m-d' );
	    }
	     
	    $date_formatted_two = date_create_from_format( 'd M, Y h:i A', $value_two );
	    if ( isset( $date_formatted_two ) && $date_formatted_two != '' ) {
	        $date_two = date_format( $date_formatted_two, 'Y-m-d' );
	    }
	    
	    return strtotime($date_two) - strtotime($date_one);
	}
	
	function wcap_class_status_asc($value1,$value2) {
	    return strcasecmp($value1->status,$value2->status );
	}
	
	function wcap_class_status_dsc ($value1,$value2) {
	    return strcasecmp($value2->status,$value1->status );
	}
	
	public function column_default( $wcap_abadoned_orders, $column_name ) {
	    $value = '';
	    switch ( $column_name ) {
			case 'id' :
			    if(isset($wcap_abadoned_orders->id)){
			     $value = '<strong><a href="admin.php?page=woocommerce_ac_page&action=orderdetails&id='.$wcap_abadoned_orders->id.' ">'.$wcap_abadoned_orders->id.'</a> </strong>';
			    }
				break;
			case 'customer' :
			    if(isset($wcap_abadoned_orders->customer)){
				    $value = $wcap_abadoned_orders->customer;
			    }
				break;
			
			case 'order_total' :
			    if(isset($wcap_abadoned_orders->order_total)){
			       $value = $wcap_abadoned_orders->order_total;
			    }
				break;
			
			case 'date' :
			    if(isset($wcap_abadoned_orders->date)){
	 			   $value = $wcap_abadoned_orders->date;
			    }
				break;
			
			case 'status' :
			    if(isset($wcap_abadoned_orders->status)){
			     $value = $wcap_abadoned_orders->status;
			    }
			    break;
		    
			default:
			    
				$value = isset( $booking->$column_name ) ? $booking->$column_name : '';
				break;
	    }
		
		return apply_filters( 'wcap_abandoned_orders_column_default', $value, $wcap_abadoned_orders, $column_name );
	}
	
	public function get_bulk_actions() {
	    return array(
	        'wcap_delete' => __( 'Delete', 'woocommerce-ac' )
	    );
	}
}
?>