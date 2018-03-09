<?php 

// Load WP_List_Table if not loaded
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}
/**
 * Abandoned Cart Lite for WooCommerce
 *
 * It will handle the common action for the plugin.
 *
 * @author  Tyche Softwares
 * @package Abandoned-Cart-Lite-for-WooCommerce/Admin/List-Class
 * @since 2.5.2
 */

class WCAL_Templates_Table extends WP_List_Table {

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
	 * Total number of email templates
	 *
	 * @var int
	 * @since 2.5.3
	 */
	public $total_count;
	
    /**
	 * It will add the bulk action function and other variable needed for the class.
	 *
	 * @see WP_List_Table::__construct()
	 * @since 2.5.2
	 */
	public function __construct() {
		global $status, $page;
		// Set parent defaults
		parent::__construct( array(
		        'singular' => __( 'template_id', 'woocommerce-abandoned-cart' ), //singular name of the listed records
		        'plural'   => __( 'template_ids', 'woocommerce-abandoned-cart' ), //plural name of the listed records
				'ajax'     => false             			// Does this table support ajax?
		) );
		$this->process_bulk_action();
        $this->base_url = admin_url( 'admin.php?page=woocommerce_ac_page&action=emailtemplates' );
	}
	
	/**
	 * It will prepare the list of the templates, like columns, pagination, sortable column, all data.
	 * @since 2.5.2
	 */
	public function wcal_templates_prepare_items() {
		$columns  = $this->get_columns();
		$hidden   = array(); // No hidden columns
		$sortable = $this->templates_get_sortable_columns();
		$data     = $this->wcal_templates_data();
		
		$this->_column_headers = array( $columns, $hidden, $sortable );
		$total_items           = $this->total_count;
		$this->items           = $data;
		
		$this->set_pagination_args( array(
				'total_items' => $total_items,                  	// WE have to calculate the total number of items
				'per_page'    => $this->per_page,                     	// WE have to determine how many items to show on a page
				'total_pages' => ceil( $total_items / $this->per_page )   // WE have to calculate the total number of pages
		      )
		);
	}
	
	/**
	 * It will add the columns templates list.
	 * @return array $columns All columns name.
	 * @since 2.5.2
	 */
	public function get_columns() {	    
	    $columns = array(
 		        'cb'            => '<input type="checkbox" />',
                'sr'            => __( 'Sr', 'woocommerce-abandoned-cart' ),
		        'template_name' => __( 'Name Of Template', 'woocommerce-abandoned-cart' ),
				'sent_time'     => __( 'Sent After Set Time', 'woocommerce-abandoned-cart' ),
				'activate'  	=> __( 'Active ?', 'woocommerce-abandoned-cart' )			
		);		
	   return apply_filters( 'wcal_templates_columns', $columns );
	}	
	/**
	 * It is used to add the check box for the items.
	 * @param string $item  
	 * @return string 
	 * @since 2.5.2
	 */
	function column_cb( $item ) {	    
	    $template_id = '';
	    if( isset( $item->id ) && "" != $item->id ) {
	       $template_id = $item->id; 
	    }
	    return sprintf(
	        '<input type="checkbox" name="%1$s[]" value="%2$s" />',
	        'template_id',
	        $template_id
	    );
	}
	
	/**
	 * We can mention on which column we need the sorting. Here we have template name, email sent time
	 * @return array $columns Name of the column
	 * @since 2.5.2
	 */
	public function templates_get_sortable_columns() {
		$columns = array(
				'template_name' => array( 'template_name', false ),
				'sent_time'		=> array( 'sent_time',false),
		);
		return apply_filters( 'wcal_templates_sortable_columns', $columns );
	}
	
	/**
	 * It will add the hover link on the template name. 
	 * This function used for individual delete, edit of row.
	 * @since 2.5.2
	 * @param array $template_row_info Contains all the data of the template row 
	 * @return string $value All hover links, here we have edit and delete
	 * 
	 */
	public function column_template_name( $template_row_info ) {	
	    $row_actions = array();
	    $value = '';
	    $template_id = 0;
	    if( isset( $template_row_info->template_name ) ) {	    
    	    $template_id = $template_row_info->id ; 
    	    
    	    $row_actions['edit']   = '<a href="' . wp_nonce_url( add_query_arg( array( 'action' => 'emailtemplates', 'mode'=>'edittemplate', 'id' => $template_row_info->id ), $this->base_url ), 'abandoned_order_nonce') . '">' . __( 'Edit', 'woocommerce-abandoned-cart' ) . '</a>';
    	    $row_actions['delete'] = '<a href="' . wp_nonce_url( add_query_arg( array( 'action' => 'wcal_delete_template', 'template_id' => $template_row_info->id ), $this->base_url ), 'abandoned_order_nonce') . '">' . __( 'Delete', 'woocommerce-abandoned-cart' ) . '</a>';
    	    
    	    $email = $template_row_info->template_name;
            $value = $email . $this->row_actions( $row_actions );	    
	    }	
	return apply_filters( 'wcal_template_single_column', $value, $template_id, 'email' );
	}
    
    /**
     * It will generate the templates list data.
     * @globals mixed $wpdb
     * @return array $return_templates_data_display Key and value of all the columns
     * @since 2.5.2
     */
	public function wcal_templates_data() { 
		global $wpdb;    		
		$return_templates_data = array();
		$per_page              = $this->per_page;
		$results               = array();    	 
        $query                 = "SELECT wpet . * FROM `" . $wpdb->prefix . "ac_email_templates_lite` AS wpet ORDER BY day_or_hour desc , frequency asc";
        $results               = $wpdb->get_results( $query );		
		$i = 0; 
		   		
		foreach ( $results as $key => $value ) {    		   
		    $return_templates_data[$i] = new stdClass();    		        		        		  
		    $id                        = $value->id;    		    
		    $query_no_emails           = "SELECT * FROM " . $wpdb->prefix . "ac_sent_history_lite WHERE template_id= %d";
		    $subject                   = $value->subject;
		    $body                      = $value->body;
		    $is_active                 = $value->is_active;
		
		    if ( $is_active == '1' ) {
		        $active = "Deactivate";
		    } else {
		        $active = "Activate";
		    }
		    $frequency                                  = $value->frequency;
		    $day_or_hour                                = $value->day_or_hour;    		    
		    $return_templates_data[ $i ]->sr            = $i+1;
		    $return_templates_data[ $i ]->id            = $id;
		    $return_templates_data[ $i ]->template_name = $value->template_name;
		    $return_templates_data[ $i ]->sent_time     = __( $frequency . " " . $day_or_hour . "After Abandonment", 'woocommerce-abandoned-cart' );
		    $return_templates_data[ $i ]->activate      = $active;
		    $return_templates_data[ $i ]->is_active     = $is_active;
		    $i++;  		        		    
        }
        $templates_count   = count( $return_templates_data );
        $this->total_count = $templates_count;
    	// sort for order date
		 if( isset( $_GET['orderby'] ) && $_GET['orderby'] == 'template_name' ) {
    		if( isset($_GET['order'] ) && $_GET['order'] == 'asc' ) {
				usort( $return_templates_data, array( __CLASS__ , "wcal_class_template_name_asc" ) ); 
			} else {
				usort( $return_templates_data, array( __CLASS__ , "wcal_class_template_name_dsc") );
			}
		}
		// sort for customer name
		else if ( isset( $_GET['orderby']) && $_GET['orderby'] == 'sent_time' ) {
    		if ( isset( $_GET['order'] ) && $_GET['order'] == 'asc' ) {
    				usort( $return_templates_data, array( __CLASS__ , "wcal_class_sent_time_asc" ) );
			} else {
				usort( $return_templates_data, array( __CLASS__ , "wcal_class_sent_time_dsc" ) );
			}
		}
		// Pagination per page
		if( isset( $_GET['paged'] ) && $_GET['paged'] > 1 ) {
		    $page_number = $_GET['paged'] - 1;
		    $k = $per_page * $page_number;
		} else {
		    $k = 0;
		}
		$return_templates_data_display = array();
		for( $j = $k; $j < ( $k+$per_page ); $j++ ) {
		    if( isset( $return_templates_data[$j] ) ) {
		        $return_templates_data_display[$j] = $return_templates_data[$j];
		    } else {
		        break;
		    }
		}
		
	return apply_filters( 'wcal_templates_table_data', $return_templates_data_display );
	}
	
	/**
	 * It will sort the data alphabetally ascending on the template name.
	 * @param array | object $value1 All data of the list
	 * @param array | object $value2 All data of the list
	 * @return sorted array  
	 * @since 2.5.2
	 */
	function wcal_class_template_name_asc( $value1,$value2 ) {
	    return strcasecmp( $value1->template_name,$value2->template_name );
	}
	
	/**
	 * It will sort the data alphabetally descending on the template name.
	 * @param array | object $value1 All data of the list
	 * @param array | object $value2 All data of the list
	 * @return sorted array  
	 * @since 2.5.2
	 */
	function wcal_class_template_name_dsc( $value1,$value2 ) {
	    return strcasecmp( $value2->template_name,$value1->template_name );
	}
	
	/**
	 * It will sort the data alphanumeric ascending on the template time.
	 * @param array | object $value1 All data of the list
	 * @param array | object $value2 All data of the list
	 * @return sorted array  
	 * @since 2.5.2
	 */
	function wcal_class_sent_time_asc( $value1,$value2 ) {
	    return strnatcasecmp( $value1->sent_time,$value2->sent_time );
	}
	
	/**
	 * It will sort the data alphanumeric descending on the template time.
	 * @param array | object $value1 All data of the list
	 * @param array | object $value2 All data of the list
	 * @return sorted array  
	 * @since 2.5.2
	 */
	function wcal_class_sent_time_dsc( $value1,$value2 ) {
	    return strnatcasecmp( $value2->sent_time,$value1->sent_time );
	}
	
	/**
	 * It will display the data for the templates list
	 * @param array | object $wcal_abandoned_orders All data of the list
	 * @param stirng $column_name Name of the column
	 * @return string $value Data of the column
	 * @since 2.5.2
	 */
	public function column_default( $wcal_abandoned_orders, $column_name ) {
	    $value = '';
	    switch ( $column_name ) {	       
	        case 'sr' :
	            if( isset( $wcal_abandoned_orders->sr ) ) {
	                $value = $wcal_abandoned_orders->sr;
	            }
	            break;	            
			case 'template_name' :
			    if( isset( $wcal_abandoned_orders->template_name ) ) {
				    $value = $wcal_abandoned_orders->template_name;
			    }
				break;			
			case 'sent_time' :
			    if( isset( $wcal_abandoned_orders->sent_time ) ) {
			       $value = $wcal_abandoned_orders->sent_time;
			    }
				break;			
			case 'activate' :
			    if( isset( $wcal_abandoned_orders->activate ) ) {			       
			       $active    = $wcal_abandoned_orders->activate;
			       $id        = $wcal_abandoned_orders->id;
			       $is_active = $wcal_abandoned_orders->is_active;			       
			       $active    = ''; 
			       if ( $is_active == '1' ) {
			           $active = "on";
			       } else {
			           $active = "off";
			       }
			       $active_text   = __( $active, 'woocommerce-abandoned-cart' ); 
			       //$value   = '<a href="#" onclick="wcal_activate_email_template('. $id.', '.$is_active.' )"> '.$active_text.'</a>'; 				
			       $value =  '<button type="button" class="wcal-switch wcal-toggle-template-status" '
					. 'wcal-template-id="'. $id .'" '
					. 'wcal-template-switch="'. ( $active ) . '">'
					. $active_text . '</button>';      
			    }
				break;			
		    default:			    
				$value = isset( $wcal_abandoned_orders->$column_name ) ? $wcal_abandoned_orders->$column_name : '';
				break;
	    }		
	return apply_filters( 'wcal_template_column_default', $value, $wcal_abandoned_orders, $column_name );
	}
	
	/**
	 * It will add the bulk action, here Delete
	 * @return array
	 * @since 2.5.2
	 */
	public function get_bulk_actions() {
	    return array(
	        'wcal_delete_template' => __( 'Delete', 'woocommerce-abandoned-cart' )
	    );
	}
}
?>