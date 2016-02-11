<?php 

// Load WP_List_Table if not loaded
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class WACP_Recover_Orders_Table extends WP_List_Table {

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
	 * Total number of recovred orders
	 *
	 * @var int
	 * @since 2.5.2
	 */
	public $total_count;
	
	
	/**
	 * Total number of recovred orders
	 *
	 * @var int
	 * @since 2.5.2
	 */
	public $total_abandoned_cart_count;
	
	/**
	 * Total amount of abadoned orders
	 *
	 * @var int
	 * @since 2.5.2
	 */
	public $total_order_amount;
	
	/**
	 * Total number recovred orders
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
				'ajax'      => false             			// Does this table support ajax?
		) );
		$this->wcap_get_recovered_orders_count_lite();
			
        $this->base_url = admin_url( 'admin.php?page=woocommerce_ac_page&action=stats' );
	}
	
public function wcap_recovered_orders_prepare_items_lite() {

		$columns  = $this->get_columns();
		$hidden   = array(); // No hidden columns
		$sortable = $this->recovered_orders_get_sortable_columns();
		$data     = $this->wacp_recovered_orders_data_lite();
		
		$total_items                = $this->total_count;
		$total_abandoned_cart_count = $this->total_abandoned_cart_count;
		$total_order_amount         = $this->total_order_amount;
		$total_recover_amount       = $this->total_recover_amount;
		$recovered_item             = $this->recovered_item;
		$this->items                = $data;
		
		$this->_column_headers = array( $columns, $hidden, $sortable);
		
		$this->set_pagination_args( array(
				'total_items' => $total_items,                  	// WE have to calculate the total number of items
				'per_page'    => $this->per_page,                     	// WE have to determine how many items to show on a page
				'total_pages' => ceil( $total_items / $this->per_page )   // WE have to calculate the total number of pages
		      )
		);
	}
	
	public function get_columns() {
	    
	    $columns = array(
 		        
                'user_name'       => __( 'User Name', 'woocommerce-ac' ),
		        'user_email_id'   => __( 'User Email Address', 'woocommerce-ac' ),
				'created_on'      => __( 'Cart Abandoned Date', 'woocommerce-ac' ),				
	            'recovered_date'  => __( 'Cart Recovered Date' , 'woocommerce-ac'),
	            'order_total'     => __( 'Order Total', 'woocommerce-ac' )
				
		);
		
	   return apply_filters( 'wcap_recovered_orders_columns', $columns );
	}
	
	public function recovered_orders_get_sortable_columns() {
		$columns = array(
				'created_on'      => array( 'created_on', false ),
				'recovered_date'  => array( 'recovered_date',false)
		);
		return apply_filters( 'wcap_templates_sortable_columns', $columns );
	}
	
	/**
	 * Render the user name Column
	 *
	 * @access public
	 * @since 2.5.2
	 * @param array $abadoned_row_info Contains all the data of the template row 
	 * @return string Data shown in the Email column
	 * 
	 * This function used for individual delete of row, It is for hover effect delete.
	 */
	public function column_user_name( $recovered_orders_row_info ) {
	
	    $row_actions = array();
	    $value = '';
	    $recovered_id = 0;
	    
	    if( isset($recovered_orders_row_info->user_name ) ){
	    $recovered_id = $recovered_orders_row_info->recovered_id ;
	    $row_actions['view_details']   = "<a target=_blank href = post.php?post=$recovered_id&action=edit>". __( 'View Details', 'woocommerce-ac' )."</a>";
	    $user_name = $recovered_orders_row_info->user_name;
        $value = $user_name . $this->row_actions( $row_actions );
	    }
	
	    return apply_filters( 'wcap_recovered_orders_single_column', $value, $recovered_id, 'email' );
	}
    
	/***
	 * This function used to get the abadoned orders count
	 */
    public function wcap_get_recovered_orders_count_lite() {
	
	   global $wpdb;
	   
	   if ( isset( $_POST['start_date'] ) ) $start_date_range = $_POST['start_date'];
	   else $start_date_range = "";
	   
	   if ( isset( $_POST['end_date'] ) ) $end_date_range = $_POST['end_date'];
	   else $end_date_range = "";
	   
	   $start_date = strtotime( $start_date_range." 00:01:01" );
	   $end_date   = strtotime( $end_date_range." 23:59:59" );
	   
	   $query_ac         = "SELECT * FROM " . $wpdb->prefix . "ac_abandoned_cart_history_lite WHERE abandoned_cart_time >= %d AND abandoned_cart_time <= %d AND recovered_cart > 0 ORDER BY recovered_cart desc";
	   $ac_results       = $wpdb->get_results( $wpdb->prepare( $query_ac, $start_date, $end_date ) );
		    
	   $templates_count   = count($ac_results);
	   $this->total_count = $templates_count;
    }
	
	public function wacp_recovered_orders_data_lite() { 
    		global $wpdb;
    		
    		$wcap_class = new woocommerce_abandon_cart ();
    		
    		if ( isset( $_POST['duration_select'] ) ) $duration_range = $_POST['duration_select'];
    		else $duration_range = "";
    		
    		if ( $duration_range == "" ) {
    		
    		    if ( isset( $_GET['duration_select'] ) ) $duration_range = $_GET['duration_select'];
    		}
    		
    		if ( $duration_range == "" ) $duration_range = "last_seven";
    		else $duration_range = "";
            
    		if ( isset( $_POST['start_date'] ) ) $start_date_range = $_POST['start_date'];
    		else $start_date_range = "";
    		
    		if ( $start_date_range == "" ) {
    		    
    		   
    		    $start_date_range = $wcap_class->start_end_dates[$duration_range]['start_date'];
    		    
    		}
    		
            if ( isset( $_POST['end_date'] ) ) $end_date_range = $_POST['end_date'];
    		else $end_date_range = "";
    		
    		if ( $end_date_range == "" ) {
    		    $end_date_range = $wcap_class->start_end_dates[$duration_range]['end_date'];
    		}
    		
    		$start_date = strtotime( $start_date_range." 00:01:01" );
    		$end_date   = strtotime( $end_date_range." 23:59:59" );
    		
    		$query_ac         = "SELECT * FROM " . $wpdb->prefix . "ac_abandoned_cart_history_lite WHERE abandoned_cart_time >= %d AND abandoned_cart_time <= %d AND recovered_cart > 0 ORDER BY recovered_cart desc";
    		$ac_results       = $wpdb->get_results( $wpdb->prepare( $query_ac, $start_date, $end_date ) );
    		
    		$query_ac_carts   = "SELECT * FROM " . $wpdb->prefix . "ac_abandoned_cart_history_lite WHERE abandoned_cart_time >= %d AND abandoned_cart_time <= %d ";
    		$ac_carts_results = $wpdb->get_results( $wpdb->prepare( $query_ac_carts, $start_date, $end_date ) );
    		
    		$recovered_item   = $recovered_total = $count_carts = $total_value = $order_total = 0;
    		
    		$return_recovered_orders = array();
    		$per_page       = $this->per_page;
    		$i = 1;
    		
    		
    		foreach ( $ac_carts_results as $key => $value ) {
    		  
    		        $count_carts += 1;
    		        $cart_detail = json_decode( $value->abandoned_cart_info );
    		        $product_details = array();
    		        if( isset( $cart_detail->cart ) ){
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
    		$total_value = round( $total_value, 2 );
    		
    		
    		$this->total_order_amount         = $total_value ;
    		$this->total_abandoned_cart_count = $count_carts ;
    		
    		$recovered_order_total = 0;
    		
    		$this->total_recover_amount = round( $recovered_order_total, 2 )  ;
    		
    		
    		$table_data  = "";
    		
    		foreach ( $ac_results as $key => $value ) {
    		    
    		
    		    if( $value->recovered_cart != 0 ) {
    		        
    		        $return_recovered_orders[$i] = new stdClass();
    		        
    		        $recovered_id       = $value->recovered_cart;
    		        $rec_order          = get_post_meta( $recovered_id );
    		        $woo_order          = new WC_Order( $recovered_id );
    		        $recovered_date     = strtotime( $woo_order->order_date );
    		        $recovered_date_new = date( 'd M, Y h:i A', $recovered_date );
    		        $recovered_item    += 1;
    		
    		        if ( isset($rec_order) && $rec_order != false ) {
    		            $recovered_total += $rec_order['_order_total'][0];
    		        }
    		        $abandoned_date               = date( 'd M, Y h:i A', $value->abandoned_cart_time );
    		        $abandoned_order_id           = $value->id;
    		        $billing_first_name           = $billing_last_name = $billing_email = '';
    		        $recovered_order_total        = 0;
    		
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
    		        
    		        $return_recovered_orders[ $i ]->user_name              = $billing_first_name . " " . $billing_last_name ;
    		        $return_recovered_orders[ $i ]->user_email_id          = $billing_email;
    		        $return_recovered_orders[ $i ]->created_on             = $abandoned_date;
    		        $return_recovered_orders[ $i ]->recovered_date         = $recovered_date_new;
    		        $return_recovered_orders[ $i ]->recovered_id           = $recovered_id;
    		        $return_recovered_orders[ $i ]->recover_order_date     = $recovered_date;
    		        $return_recovered_orders[ $i ]->abandoned_date         = $value->abandoned_cart_time;
    		        $return_recovered_orders[ $i ]->order_total            = get_woocommerce_currency_symbol() . $recovered_order_total;
    		        
    		        
    		        $this->recovered_item = $recovered_item;
    		        $this->total_recover_amount = round( ( $recovered_order_total + $this->total_recover_amount ) , 2 )  ;
    		        
    		        $i++;
    		    }
    		    
    		    
    		}
    		
    	// sort for order date
		 if (isset($_GET['orderby']) && $_GET['orderby'] == 'created_on') {
    		if (isset($_GET['order']) && $_GET['order'] == 'asc') {
				usort( $return_recovered_orders, array( __CLASS__ ,"wcap_class_recovered_created_on_asc") ); 
			}else {
				usort( $return_recovered_orders, array( __CLASS__ ,"wcap_class_recovered_created_on_dsc") );
			}
		}
		
		// sort for customer name
		else if ( isset( $_GET['orderby']) && $_GET['orderby'] == 'recovered_date' ) {
		if ( isset( $_GET['order'] ) && $_GET['order'] == 'asc' ) {
				usort( $return_recovered_orders, array( __CLASS__ ,"wcap_class_recovered_date_asc" ) );
			}else {
				usort( $return_recovered_orders, array( __CLASS__ ,"wcap_class_recovered_date_dsc" ) );
			}
		}
		
		
		return apply_filters( 'wcap_recovered_orders_table_data', $return_recovered_orders );
	}
	
	function wcap_class_recovered_created_on_asc($value1,$value2) {
	    return $value1->abandoned_date - $value2->abandoned_date;
	}
	
	function wcap_class_recovered_created_on_dsc ($value1,$value2) {
	    return $value2->abandoned_date - $value1->abandoned_date;
	}
	
	function wcap_class_recovered_date_asc($value1,$value2) {
	    return $value1->recover_order_date - $value2->recover_order_date;
	}
	
	function wcap_class_recovered_date_dsc ($value1,$value2) {
	    return $value2->recover_order_date - $value1->recover_order_date;
	}
	
	
	public function column_default( $wcap_abadoned_orders, $column_name ) {
	    $value = '';
	    switch ( $column_name ) {
	        
	        case 'user_email_id' :
			    if(isset($wcap_abadoned_orders->user_email_id)){
			        
			        $user_email_id = "<a href= mailto:$wcap_abadoned_orders->user_email_id>". $wcap_abadoned_orders->user_email_id."</a>" ;
				    $value = $user_email_id;
			    }
				break;
			
			case 'created_on' :
			    if(isset($wcap_abadoned_orders->created_on)){
			       $value = $wcap_abadoned_orders->created_on;
			    }
				break;
			
			case 'recovered_date' :
			    if(isset($wcap_abadoned_orders->recovered_date)){
	 			   $value = $wcap_abadoned_orders->recovered_date;
			    }
				break;
			
			case 'order_total' :
			    if(isset($wcap_abadoned_orders->order_total)){
			     $value = $wcap_abadoned_orders->order_total;
			    }
			    break;
		    default:
			    
				$value = isset( $wcap_abadoned_orders->$column_name ) ? $wcap_abadoned_orders->$column_name : '';
				break;
	    }
		
		return apply_filters( 'wcap_recovered_orders_column_default', $value, $wcap_abadoned_orders, $column_name );
	}
}
?>