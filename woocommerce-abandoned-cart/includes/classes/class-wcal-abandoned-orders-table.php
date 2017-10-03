<?php 
// Load WP_List_Table if not loaded
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class WCAL_Abandoned_Orders_Table extends WP_List_Table {

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
	
	public function wcal_abandoned_order_prepare_items() {
		$columns               = $this->get_columns();
		$hidden                = array(); // No hidden columns
		$sortable              = $this->get_sortable_columns();
		$this->total_count     = 0;
		$data                  = $this->wcal_abandoned_cart_data();
		$this->_column_headers = array( $columns, $hidden, $sortable );
		$total_items           = $this->total_count;
		
		if( count( $data ) > 0 ) {
		  $this->items = $data;
		} else {
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
 		        'cb'          => '<input type="checkbox" />',
                'id'          => __( 'Id', 'woocommerce-ac' ),
                'email'       => __( 'Email Address', 'woocommerce-ac' ),
				'customer'    => __( 'Customer', 'woocommerce-ac' ),
				'order_total' => __( 'Order Total', 'woocommerce-ac' ),		        
				'date'        => __( 'Abandoned Date', 'woocommerce-ac' ),
				'status'      => __( 'Status of Cart', 'woocommerce-ac' )
		);
		return apply_filters( 'wcal_abandoned_orders_columns', $columns );
	}
	
	/*** 
	 * It is used to add the check box for the items
	 */
	function column_cb( $item ){	   
	    $abandoned_order_id = '';
	    if( isset( $item->id ) && "" != $item->id ) {
	       $abandoned_order_id = $item->id; 
	    }
	    return sprintf(
	        '<input type="checkbox" name="%1$s[]" value="%2$s" />',
	        'abandoned_order_id',
	        $abandoned_order_id
	    );
	}
	
	public function get_sortable_columns() {
		$columns = array(
				'date' 			=> array( 'date', false ),
				'status'		=> array( 'status',false),
		);
		return apply_filters( 'wcal_abandoned_orders_sortable_columns', $columns );
	}
	
	/**
	 * Render the Email Column
	 *
	 * @access public
	 * @since 2.4.8
	 * @param array $abandoned_row_info Contains all the data of the abandoned order tabs row 
	 * @return string Data shown in the Email column
	 * 
	 * This function used for individual delete of row, It is for hover effect delete.
	 */
	public function column_email( $abandoned_row_info ) {	
	    $row_actions = array();
	    $value = '';
	    $abandoned_order_id = 0;
	    
	    if( isset( $abandoned_row_info->email ) ) {	    
	    $abandoned_order_id    = $abandoned_row_info->id ; 
	    $row_actions['edit']   = '<a href="' . wp_nonce_url( add_query_arg( array( 'action' => 'orderdetails', 'id' => $abandoned_row_info->id ), $this->base_url ), 'abandoned_order_nonce') . '">' . __( 'View order', 'woocommerce-ac' ) . '</a>';
	    $row_actions['delete'] = '<a href="' . wp_nonce_url( add_query_arg( array( 'action' => 'wcal_delete', 'abandoned_order_id' => $abandoned_row_info->id ), $this->base_url ), 'abandoned_order_nonce') . '">' . __( 'Delete', 'woocommerce-ac' ) . '</a>';	
	    $email                 = $abandoned_row_info->email;
	    $value                 = $email . $this->row_actions( $row_actions );	    
	    }	
	    return apply_filters( 'wcal_abandoned_orders_single_column', $value, $abandoned_order_id, 'email' );
	}
    
	public function wcal_abandoned_cart_data() { 
		global $wpdb;    	
		$return_abandoned_orders = array();
		$per_page                = $this->per_page;
		$results                 = array();    	 
		$blank_cart_info         = '{"cart":[]}';
		$blank_cart_info_guest   = '[]';

		$get_section_of_page   = WCAL_Abandoned_Orders_Table::wcal_get_current_section ();
		
		$results 			   = array();
		
		switch ( $get_section_of_page ) {
		    case 'wcal_all_abandoned':
		        # code...
		        if( is_multisite() ) {
		            // get main site's table prefix
		            $main_prefix = $wpdb->get_blog_prefix(1);
		            $query = "SELECT wpac . * , wpu.user_login, wpu.user_email FROM `".$wpdb->prefix."ac_abandoned_cart_history_lite` AS wpac LEFT JOIN ".$main_prefix."users AS wpu ON wpac.user_id = wpu.id
		            WHERE wpac.recovered_cart='0' AND wpac.abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND wpac.abandoned_cart_info NOT LIKE '$blank_cart_info_guest' ORDER BY wpac.abandoned_cart_time DESC";
		            $results = $wpdb->get_results($query);
		        } else {
		            // non-multisite - regular table name
		            $query = "SELECT wpac . * , wpu.user_login, wpu.user_email FROM `".$wpdb->prefix."ac_abandoned_cart_history_lite` AS wpac LEFT JOIN ".$wpdb->prefix."users AS wpu ON wpac.user_id = wpu.id
		            WHERE wpac.recovered_cart='0' AND wpac.abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND wpac.abandoned_cart_info NOT LIKE '$blank_cart_info_guest' ORDER BY wpac.abandoned_cart_time DESC ";
		
		            $results = $wpdb->get_results($query);
		        }
		        break;
		         
		    case 'wcal_all_registered':
		        # code...
		        if( is_multisite() ) {
		            // get main site's table prefix
		            $main_prefix = $wpdb->get_blog_prefix(1);
		            $query = "SELECT wpac . * , wpu.user_login, wpu.user_email FROM `".$wpdb->prefix."ac_abandoned_cart_history_lite` AS wpac LEFT JOIN ".$main_prefix."users AS wpu ON wpac.user_id = wpu.id
		            WHERE wpac.recovered_cart='0' AND wpac.abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND wpac.abandoned_cart_info NOT LIKE '$blank_cart_info_guest' ORDER BY wpac.abandoned_cart_time DESC ";
		            $results = $wpdb->get_results($query);
		        } else {
		            // non-multisite - regular table name
		            $query = "SELECT wpac . * , wpu.user_login, wpu.user_email FROM `".$wpdb->prefix."ac_abandoned_cart_history_lite` AS wpac LEFT JOIN ".$wpdb->prefix."users AS wpu ON wpac.user_id = wpu.id
		            WHERE wpac.recovered_cart='0' AND wpac.abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND wpac.user_type = 'REGISTERED' ORDER BY wpac.abandoned_cart_time DESC ";
		
		
		            $results = $wpdb->get_results($query);
		        }
		        break;
		
		    case 'wcal_all_guest':
		        # code...
		        if( is_multisite() ) {
		            // get main site's table prefix
		            $main_prefix = $wpdb->get_blog_prefix(1);
		            $query = "SELECT wpac . * , wpu.user_login, wpu.user_email FROM `".$wpdb->prefix."ac_abandoned_cart_history_lite` AS wpac LEFT JOIN ".$main_prefix."users AS wpu ON wpac.user_id = wpu.id
		            WHERE wpac.recovered_cart='0' AND wpac.abandoned_cart_info NOT LIKE '$blank_cart_info_guest' wpac.user_id >= 63000000  ORDER BY wpac.abandoned_cart_time DESC ";
		            $results = $wpdb->get_results($query);
		        } else {
		            // non-multisite - regular table name
		            $query = "SELECT wpac . * , wpu.user_login, wpu.user_email FROM `".$wpdb->prefix."ac_abandoned_cart_history_lite` AS wpac LEFT JOIN ".$wpdb->prefix."users AS wpu ON wpac.user_id = wpu.id
		            WHERE wpac.recovered_cart='0' AND wpac.abandoned_cart_info NOT LIKE '$blank_cart_info_guest' AND wpac.user_id >= 63000000 ORDER BY wpac.abandoned_cart_time DESC ";
		            $results = $wpdb->get_results($query);
		        }
		        break;
		
		    case 'wcal_all_visitor':
		     			# code...
		        if( is_multisite() ) {
		            // get main site's table prefix
		            $main_prefix = $wpdb->get_blog_prefix(1);
		            $query = "SELECT wpac . * , wpu.user_login, wpu.user_email FROM `".$wpdb->prefix."ac_abandoned_cart_history_lite` AS wpac LEFT JOIN ".$main_prefix."users AS wpu ON wpac.user_id = wpu.id
		            WHERE wpac.recovered_cart='0' AND wpac.abandoned_cart_info NOT LIKE '$blank_cart_info_guest' wpac.user_id >= 63000000  ORDER BY wpac.abandoned_cart_time DESC ";
		            $results = $wpdb->get_results($query);
		        } else {
		            // non-multisite - regular table name
		            $query = "SELECT wpac . * , wpu.user_login, wpu.user_email FROM `".$wpdb->prefix."ac_abandoned_cart_history_lite` AS wpac LEFT JOIN ".$wpdb->prefix."users AS wpu ON wpac.user_id = wpu.id
		            WHERE wpac.recovered_cart='0' AND wpac.abandoned_cart_info NOT LIKE '$blank_cart_info_guest' AND wpac.user_id = 0 ORDER BY wpac.abandoned_cart_time DESC ";
		            $results = $wpdb->get_results($query);
		        }
		        break;
		
		
		    default:
		        # code...
		        break;
		}
		$i = 0;
	   		
		foreach( $results as $key => $value ) {        		
		    if( $value->user_type == "GUEST" ) {
		        $query_guest   = "SELECT * from `" . $wpdb->prefix . "ac_guest_abandoned_cart_history_lite` WHERE id = %d";
		        $results_guest = $wpdb->get_results( $wpdb->prepare( $query_guest, $value->user_id ) );
		    }
		    $abandoned_order_id = $value->id;
		    $user_id            = $value->user_id;
		    $user_login         = $value->user_login;
	
		    if ( $value->user_type == "GUEST" ) {
		
		        if ( isset( $results_guest[0]->email_id ) ) {
		            $user_email = $results_guest[0]->email_id;
		        } elseif ( $value->user_id == "0" ) {
		            $user_email = '';
		        } else {
		            $user_email = '';
	            }
		
		        if ( isset( $results_guest[0]->billing_first_name ) ) {
		            $user_first_name = $results_guest[0]->billing_first_name;
		        } else if( $value->user_id == "0" ) { 
		            $user_first_name = "Visitor";
		        } else {
		            $user_first_name = "";
		        }
		
		        if ( isset( $results_guest[0]->billing_last_name ) ) {
		            $user_last_name = $results_guest[0]->billing_last_name;
		        } else if( $value->user_id == "0" ) {
		            $user_last_name = "";
		        } else {
		            $user_last_name = "";
		        }    		        
		    } else {    		        
		        $user_email_billing = get_user_meta( $value->user_id, 'billing_email', true );
		        if( $user_email_billing != '' ) {
		            $user_email = $user_email_billing;
		        } else {
		            $user_data = get_userdata( $value->user_id );
		            $user_email = $user_data->user_email;
		        }        		   
		        $user_first_name = "";
		        $user_first_name_temp = get_user_meta( $user_id, 'billing_first_name', true );
		        if( isset( $user_first_name_temp ) && "" == $user_first_name_temp ) {
		            $user_data  = get_userdata( $user_id );
		            $user_first_name = $user_data->first_name;
		        } else {
		            $user_first_name = $user_first_name_temp;
		        }
		        
		        $user_last_name = "";
		        $user_last_name_temp = get_user_meta( $user_id, 'billing_last_name', true );
		        if( isset( $user_last_name_temp ) && "" == $user_last_name_temp ) {
		            $user_data  = get_userdata( $user_id );
		            $user_last_name = $user_data->last_name;
		        } else {
		            $user_last_name = $user_last_name_temp;
		        }
		    }
		
		    $cart_info        = json_decode( $value->abandoned_cart_info );
		    $order_date       = "";
		    $cart_update_time = $value->abandoned_cart_time;
			
		    if ( $cart_update_time != "" && $cart_update_time != 0 ) {
		    	$date_format = date_i18n( get_option( 'date_format' ), $cart_update_time );
            	$time_format = date_i18n( get_option( 'time_format' ), $cart_update_time );
		        $order_date  = $date_format . ' ' . $time_format;
		    }
		
		    $ac_cutoff_time = get_option( 'ac_lite_cart_abandoned_time' );
		    $cut_off_time   = intval( $ac_cutoff_time ) * 60;
		    $current_time   = current_time( 'timestamp' );
		    $compare_time   = $current_time - $cart_update_time;
		    $cart_details   = array();
		    if( isset( $cart_info->cart ) ){
		        $cart_details = $cart_info->cart;
		    }
		    $line_total = 0;
		   
		    if( count( $cart_details ) > 0 ) {    		
		        foreach( $cart_details as $k => $v ) {    		     
		            if( $v->line_subtotal_tax != 0 && $v->line_subtotal_tax > 0 ) {
		                $line_total = $line_total + $v->line_total + $v->line_subtotal_tax;
		            } else {
		                $line_total = $line_total + $v->line_total;
		            }
		        }
		    }
		    //$number_decimal = wc_get_price_decimals();
		    $line_total     = wc_price( $line_total );
		    $quantity_total = 0;
		
		    if ( count( $cart_details ) > 0) {    		         
		        foreach( $cart_details as $k => $v ) {
		            $quantity_total = $quantity_total + $v->quantity;
		        }
		    }
		
		    if ( 1 == $quantity_total ) {
		        $item_disp = __("item", "woocommerce-ac");
		    } else {
		        $item_disp = __("items", "woocommerce-ac");
		    }
		    
		    if( $value->cart_ignored == 0 && $value->recovered_cart == 0 ) {
		        $ac_status = __( "Abandoned", "woocommerce-ac" );
		    } elseif( $value->cart_ignored == 1 && $value->recovered_cart == 0 ) {
		        $ac_status = __( "Abandoned but new","woocommerce-ac" )."</br>". __( "cart created after this", "woocommerce-ac" );
		    } else {
		        $ac_status = "";
		    }
		    
		    if( $compare_time > $cut_off_time && $ac_status != "" ) {                   
		        $return_abandoned_orders[$i] = new stdClass();    		        
                if( $quantity_total > 0 ) {                        
                    $abandoned_order_id                         =  $abandoned_order_id;
                    $customer_information                       = $user_first_name . " ".$user_last_name;
                    $return_abandoned_orders[ $i ]->id          = $abandoned_order_id;
                    $return_abandoned_orders[ $i ]->email       = $user_email;
                    $return_abandoned_orders[ $i ]->customer    = $customer_information;
                    $return_abandoned_orders[ $i ]->order_total = $line_total;
                    $return_abandoned_orders[ $i ]->date        = $order_date;
                    $return_abandoned_orders[ $i ]->status      = $ac_status;                        
               }else {
                   $abandoned_order_id                    = $abandoned_order_id;
                   $return_abandoned_orders[ $i ]->id     = $abandoned_order_id;
                   $return_abandoned_orders[ $i ]->date   = $order_date;
                   $return_abandoned_orders[ $i ]->status = $ac_status;
                }
               // To get the abandoned orders count
               $this->total_count = count( $return_abandoned_orders );
               $i++;
          }        
        }	                   
        // sort for order date
    	 if( isset( $_GET['orderby'] ) && $_GET['orderby'] == 'date' ) {
    		if( isset( $_GET['order'] ) && $_GET['order'] == 'asc' ) {
    			usort( $return_abandoned_orders, array( __CLASS__ , "wcal_class_order_date_asc") ); 
    		}
    		else {
    			usort( $return_abandoned_orders, array( __CLASS__ , "wcal_class_order_date_dsc") );
    		}
    	}
    	// sort for customer name
    	else if( isset( $_GET['orderby'] ) && $_GET['orderby'] == 'status' ) {
    		if( isset( $_GET['order'] ) && $_GET['order'] == 'asc' ) {
    				usort( $return_abandoned_orders, array( __CLASS__ , "wcal_class_status_asc" ) );
    		} else {
    			usort( $return_abandoned_orders, array( __CLASS__ , "wcal_class_status_dsc" ) );
    		}
    	}
	
    	if( isset( $_GET['paged'] ) && $_GET['paged'] > 1 ) {
    		$page_number = $_GET['paged'] - 1;
    		$k = $per_page * $page_number;
    	} else {
    		$k = 0;
    	}
    	$return_abandoned_orders_display = array();
    	for( $j = $k; $j < ( $k+$per_page ); $j++ ) {
    		if( isset( $return_abandoned_orders[$j] ) ) {
    			$return_abandoned_orders_display[$j] = $return_abandoned_orders[$j];
    		} else {
    			break;
    		}
    	}
	return apply_filters( 'wcal_abandoned_orders_table_data', $return_abandoned_orders_display );
	}
	
	function wcal_class_order_date_asc( $value1,$value2 ) {	    
	    $date_two           = $date_one = '';
	    $value_one          = $value1->date;
	    $value_two          = $value2->date;	    
	    $date_formatted_one = date_create_from_format( 'd M, Y h:i A', $value_one );
	    if( isset( $date_formatted_one ) && $date_formatted_one != '' ) {
	        $date_one = date_format( $date_formatted_one, 'Y-m-d h:i A' );
	    }
	    
	    $date_formatted_two = date_create_from_format( 'd M, Y h:i A', $value_two );
	    if( isset( $date_formatted_two ) && $date_formatted_two != '' ) {
	        $date_two = date_format( $date_formatted_two, 'Y-m-d h:i A' );
	    }
	    return strtotime( $date_one ) - strtotime( $date_two );
	}
	
	function wcal_class_order_date_dsc( $value1,$value2 ) {	   
	    $date_two           = $date_one = '';
	    $value_one          = $value1->date;
	    $value_two          = $value2->date;	     
	    $date_formatted_one = date_create_from_format( 'd M, Y h:i A', $value_one );
	    if( isset( $date_formatted_one ) && $date_formatted_one != '' ) {
	        $date_one = date_format( $date_formatted_one, 'Y-m-d h:i A' );
	    }
	     
	    $date_formatted_two = date_create_from_format( 'd M, Y h:i A', $value_two );
	    if( isset( $date_formatted_two ) && $date_formatted_two != '' ) {
	        $date_two = date_format( $date_formatted_two, 'Y-m-d h:i A' );
	    }	    
	    return strtotime($date_two) - strtotime($date_one);
	}
	
	function wcal_class_status_asc( $value1,$value2 ) {
	    return strcasecmp( $value1->status,$value2->status );
	}
	
	function wcal_class_status_dsc( $value1,$value2 ) {
	    return strcasecmp( $value2->status,$value1->status );
	}
	
	public function column_default( $wcal_abandoned_orders, $column_name ) {
	    $value = '';
	    switch( $column_name ) {
			case 'id' :
			    if( isset($wcal_abandoned_orders->id ) ) {
			     $value = '<strong><a href="admin.php?page=woocommerce_ac_page&action=orderdetails&id='.$wcal_abandoned_orders->id.' ">'.$wcal_abandoned_orders->id.'</a> </strong>';
			    }
				break;
			case 'customer' :
			    if( isset( $wcal_abandoned_orders->customer ) ) {
				    $value = $wcal_abandoned_orders->customer;
			    }
				break;			
			case 'order_total' :
			    if( isset( $wcal_abandoned_orders->order_total ) ) {
			       $value = $wcal_abandoned_orders->order_total;
			    }
				break;			
			case 'date' :
			    if( isset( $wcal_abandoned_orders->date ) ) {
	 			   $value = $wcal_abandoned_orders->date;
			    }
				break;			
			case 'status' :
			    if( isset( $wcal_abandoned_orders->status ) ) {
			     $value = $wcal_abandoned_orders->status;
			    }
			    break;		    
			default:			    
				$value = isset( $wcal_abandoned_orders->$column_name ) ? $wcal_abandoned_orders->$column_name : '';
				break;
	    }		
		return apply_filters( 'wcal_abandoned_orders_column_default', $value, $wcal_abandoned_orders, $column_name );
	}
	
	public function get_bulk_actions() {
	    return array(
	        'wcal_delete' => __( 'Delete', 'woocommerce-ac' )
	    );
	}
	
	public function wcal_get_current_section () {
	    $section = 'wcal_all_abandoned';
	    if ( isset( $_GET[ 'wcal_section' ] ) ) {
	        $section = $_GET[ 'wcal_section' ];
	    }
	    return $section	;
	}
}
?>