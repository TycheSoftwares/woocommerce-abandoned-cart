<?php 
/*
Plugin Name: WooCommerce Abandon Cart Lite Plugin
Plugin URI: http://www.tychesoftwares.com/store/premium-plugins/woocommerce-abandoned-cart-pro
Description: This plugin captures abandoned carts by logged-in users & emails them about it. <strong><a href="http://www.tychesoftwares.com/store/premium-plugins/woocommerce-abandoned-cart-pro">Click here to get the PRO Version.</a></strong>
Version: 1.4
Author: Ashok Rane
Author URI: http://www.tychesoftwares.com/
*/


// Deletion Settings
register_uninstall_hook( __FILE__, 'woocommerce_ac_delete');

// Add a new interval of 5 minutes
add_filter( 'cron_schedules', 'woocommerce_ac_add_cron_schedule' );

function woocommerce_ac_add_cron_schedule( $schedules ) {
	
    $schedules['5_minutes'] = array(
                'interval' => 300 , // 5 minutes in seconds
                'display'  => __( 'Once Every Five Minutes' ),
    );
    return $schedules;
}

// Schedule an action if it's not already scheduled
if ( ! wp_next_scheduled( 'woocommerce_ac_send_email_action' ) ) {
    wp_schedule_event( time(), '5_minutes', 'woocommerce_ac_send_email_action' );
}

// Hook into that action that'll fire every 5 minutes
add_action( 'woocommerce_ac_send_email_action', 'woocommerce_ac_send_email_cron' );
function woocommerce_ac_send_email_cron() {
    require_once( ABSPATH.'wp-content/plugins/woocommerce-abandoned-cart/cron/send_email.php' );
}

function woocommerce_ac_delete(){
	
	global $wpdb;
	$table_name_ac_abandoned_cart_history = $wpdb->base_prefix . "ac_abandoned_cart_history_lite";
	$sql_ac_abandoned_cart_history = "DROP TABLE " . $table_name_ac_abandoned_cart_history ;
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	$wpdb->get_results($sql_ac_abandoned_cart_history);

	$table_name_ac_email_templates = $wpdb->base_prefix . "ac_email_templates_lite";
	$sql_ac_email_templates = "DROP TABLE " . $table_name_ac_email_templates ;
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	$wpdb->get_results($sql_ac_email_templates);

	$table_name_ac_sent_history = $wpdb->base_prefix . "ac_sent_history_lite";
	$sql_ac_sent_history = "DROP TABLE " . $table_name_ac_sent_history ;
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	$wpdb->get_results($sql_ac_sent_history);

}
//include_once("lang.php");

//if (is_woocommerce_active())
{
	/**
	 * Localisation
	 **/
	load_plugin_textdomain('woocommerce-ac', false, dirname( plugin_basename( __FILE__ ) ) . '/');

	/**
	 * woocommerce_abandon_cart class
	 **/
	if (!class_exists('woocommerce_abandon_cart')) {
	
		class woocommerce_abandon_cart {
			
			var $one_hour;
			var $three_hours;
			var $six_hours;
			var $twelve_hours;
			var $one_day;
			var $one_week;
			
			var $duration_range_select = array();
			var $start_end_dates = array();
			
			public function __construct() {
				
				$this->one_hour = 60 * 60;
				$this->three_hours = 3 * $this->one_hour;
				$this->six_hours = 6 * $this->one_hour;
				$this->twelve_hours = 12 * $this->one_hour;
				$this->one_day = 24 * $this->one_hour;
				$this->one_week = 7 * $this->one_day;
				
				$this->duration_range_select = array('yesterday' => 'Yesterday',
						'today' => 'Today',
						'last_seven' => 'Last 7 days',
						'last_fifteen' => 'Last 15 days',
						'last_thirty' => 'Last 30 days',
						'last_ninety' => 'Last 90 days',
						'last_year_days' => 'Last 365');
				
				$this->start_end_dates = array('yesterday' => array( 'start_date' => date("d M Y", (current_time('timestamp') - 24*60*60)),
						'end_date' => date("d M Y", (current_time('timestamp') - 7*24*60*60))),
						'today' => array( 'start_date' => date("d M Y", (current_time('timestamp'))),
								'end_date' => date("d M Y", (current_time('timestamp')))),
						'last_seven' => array( 'start_date' => date("d M Y", (current_time('timestamp') - 7*24*60*60)),
								'end_date' => date("d M Y", (current_time('timestamp')))),
						'last_fifteen' => array( 'start_date' => date("d M Y", (current_time('timestamp') - 15*24*60*60)),
								'end_date' => date("d M Y", (current_time('timestamp')))),
						'last_thirty' => array( 'start_date' => date("d M Y", (current_time('timestamp') - 30*24*60*60)),
								'end_date' => date("d M Y", (current_time('timestamp')))),
						'last_ninety' => array( 'start_date' => date("d M Y", (current_time('timestamp') - 90*24*60*60)),
								'end_date' => date("d M Y", (current_time('timestamp')))),
						'last_year_days' => array( 'start_date' => date("d M Y", (current_time('timestamp') - 365*24*60*60)),
								'end_date' => date("d M Y", (current_time('timestamp')))));
				
				
				// Initialize settings
				register_activation_hook( __FILE__, array(&$this, 'woocommerce_ac_activate'));
				
				// WordPress Administration Menu 
				add_action('admin_menu', array(&$this, 'woocommerce_ac_admin_menu'));
				
				// Actions to be done on cart update
				add_action('woocommerce_cart_updated', array(&$this, 'woocommerce_ac_store_cart_timestamp'));
				
				// delete added temp fields after order is placed 
				add_filter('woocommerce_order_details_after_order_table', array(&$this, 'action_after_delivery_session'));
				
				add_action( 'admin_init', array(&$this, 'action_admin_init' ));
				add_action( 'admin_init', array(&$this, 'ac_lite_update_db_check' ));
				
				add_action( 'admin_enqueue_scripts', array(&$this, 'my_enqueue_scripts_js' ));
				add_action( 'admin_enqueue_scripts', array(&$this, 'my_enqueue_scripts_css' ));
				
				if( is_admin() )
				{
					if (isset($_GET['page']) && $_GET['page'] == "woocommerce_ac_page")
					{
						add_action('admin_head', array(&$this, 'tinyMCE_ac'));
					}
					
					// Load "admin-only" scripts here
					add_action('admin_head', array(&$this, 'my_action_javascript'));
					add_action('wp_ajax_remove_cart_data', array(&$this, 'remove_cart_data'));
					
					add_action('admin_head', array(&$this, 'my_action_send_preview'));
					add_action('wp_ajax_preview_email_sent', array(&$this, 'preview_email_sent'));
					
				}
				
			}
			
			/*-----------------------------------------------------------------------------------*/
			/* Class Functions */
			/*-----------------------------------------------------------------------------------*/
			
			
			function woocommerce_ac_activate() {
			
				global $wpdb;
				 
				$table_name = $wpdb->base_prefix . "ac_email_templates_lite";
			
				$sql = "CREATE TABLE IF NOT EXISTS $table_name (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`subject` text COLLATE utf8_unicode_ci NOT NULL,
				`body` mediumtext COLLATE utf8_unicode_ci NOT NULL,
				`is_active` enum('0','1') COLLATE utf8_unicode_ci NOT NULL,
				`frequency` int(11) NOT NULL,
				`day_or_hour` enum('Days','Hours') COLLATE utf8_unicode_ci NOT NULL,
				`template_name` text COLLATE utf8_unicode_ci NOT NULL,
				`from_name` text COLLATE utf8_unicode_ci NOT NULL,
  				PRIMARY KEY (`id`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ";
			
				require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
				dbDelta($sql);
			
				$sent_table_name = $wpdb->base_prefix . "ac_sent_history_lite";
			
				$sql_query = "CREATE TABLE IF NOT EXISTS $sent_table_name (
				`id` int(11) NOT NULL auto_increment,
				`template_id` varchar(40) collate utf8_unicode_ci NOT NULL,
				`abandoned_order_id` int(11) NOT NULL,
				`sent_time` datetime NOT NULL,
				`sent_email_id` text COLLATE utf8_unicode_ci NOT NULL,
				PRIMARY KEY  (`id`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ";
				 
				require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
				dbDelta($sql_query);
						 
				$ac_history_table_name = $wpdb->base_prefix . "ac_abandoned_cart_history_lite";
				 
				$history_query = "CREATE TABLE IF NOT EXISTS $ac_history_table_name (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`user_id` int(11) NOT NULL,
				`abandoned_cart_info` text COLLATE utf8_unicode_ci NOT NULL,
				`abandoned_cart_time` int(11) NOT NULL,
				`cart_ignored` enum('0','1') COLLATE utf8_unicode_ci NOT NULL,
				`recovered_cart` int(11) NOT NULL,
				PRIMARY KEY (`id`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
						 
				require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
				dbDelta($history_query);
			}
			
			function ac_lite_update_db_check() {
			    global $wpdb;
			    if( get_option('ac_lite_alter_table_queries') != 'yes') {     
			        if( $wpdb->get_var("SHOW TABLES LIKE '" . $wpdb->prefix . "ac_email_templates'") === $wpdb->prefix . 'ac_email_templates') {
			             $old_table_name = $wpdb->base_prefix . "ac_email_templates";
			             $table_name = $wpdb->base_prefix . "ac_email_templates_lite";
			        
			             $alter_ac_email_table_query = "ALTER TABLE $old_table_name
			             RENAME TO $table_name";
			             $wpdb->get_results ( $alter_ac_email_table_query );
			        
			        }
			        
			        if($wpdb->get_var("SHOW TABLES LIKE '" . $wpdb->prefix . "ac_sent_history'") === $wpdb->prefix . 'ac_sent_history') { 
			             $old_sent_table_name = $wpdb->base_prefix . "ac_sent_history";
			             $sent_table_name = $wpdb->base_prefix . "ac_sent_history_lite";
			             $alter_ac_sent_history_table_query = "ALTER TABLE $old_sent_table_name
			             RENAME TO $sent_table_name";
			             $wpdb->get_results ( $alter_ac_sent_history_table_query );
			        }
			        
			        if( $wpdb->get_var("SHOW TABLES LIKE '" . $wpdb->prefix . "ac_abandoned_cart_history'") === $wpdb->prefix . 'ac_abandoned_cart_history') {
			             $old_ac_history_table_name = $wpdb->base_prefix . "ac_abandoned_cart_history";
			             $ac_history_table_name = $wpdb->base_prefix . "ac_abandoned_cart_history_lite";
			        
			             $alter_ac_abandoned_cart_history_table_query = "ALTER TABLE $old_ac_history_table_name
			             RENAME TO $ac_history_table_name";
			             $wpdb->get_results ( $alter_ac_abandoned_cart_history_table_query );
			        }
			         
			        update_option('ac_lite_alter_table_queries','yes');
			    }
			}
			
			function woocommerce_ac_admin_menu(){
			
				$page = add_submenu_page('woocommerce', __( 'Abandoned Carts', 'woocommerce-ac' ), __( 'Abandoned Carts', 'woocommerce-ac' ), 'manage_woocommerce', 'woocommerce_ac_page', array(&$this, 'woocommerce_ac_page' ));
			
			}
			
			function woocommerce_ac_store_cart_timestamp() {
				
				if ( is_user_logged_in() )
				{
				global $wpdb;
				$user_id = get_current_user_id();
				$current_time = current_time('timestamp');
				$cut_off_time = json_decode(get_option('woocommerce_ac_settings'));
				$cart_cut_off_time = $cut_off_time[0]->cart_time * 60;
				$compare_time = $current_time - $cart_cut_off_time;
				$query = "SELECT * FROM `".$wpdb->base_prefix."ac_abandoned_cart_history_lite`
				WHERE user_id = '".$user_id."'
				AND cart_ignored = '0'
				AND recovered_cart = '0'";
				$results = $wpdb->get_results( $query );
				if ( count($results) == 0 )
				{
					$cart_info = json_encode(get_user_meta($user_id, '_woocommerce_persistent_cart', true));
					$insert_query = "INSERT INTO `".$wpdb->base_prefix."ac_abandoned_cart_history_lite`
					(user_id, abandoned_cart_info, abandoned_cart_time, cart_ignored)
					VALUES ('".$user_id."', '".$cart_info."', '".$current_time."', '0')";
					//mysql_query($insert_query);
                                        $wpdb->query($insert_query);
				}
				elseif ( $compare_time > $results[0]->abandoned_cart_time )
				{
					$updated_cart_info = json_encode(get_user_meta($user_id, '_woocommerce_persistent_cart', true));
					if (! $this->compare_carts( $user_id, $results[0]->abandoned_cart_info) )
					{
						$query_ignored = "UPDATE `".$wpdb->base_prefix."ac_abandoned_cart_history_lite`
						SET cart_ignored = '1'
						WHERE user_id='".$user_id."'";
						//mysql_query($query_ignored);
                                                $wpdb->query($query_ignored);
						$query_update = "INSERT INTO `".$wpdb->base_prefix."ac_abandoned_cart_history_lite`
						(user_id, abandoned_cart_info, abandoned_cart_time, cart_ignored)
						VALUES ('".$user_id."', '".$updated_cart_info."', '".$current_time."', '0')";
						//mysql_query($query_update);
                                                $wpdb->query($query_update);
						update_user_meta($user_id, '_woocommerce_ac_modified_cart', md5("yes"));
					}
					else
					{
						update_user_meta($user_id, '_woocommerce_ac_modified_cart', md5("no"));
					}
				}
				else
				{
					$updated_cart_info = json_encode(get_user_meta($user_id, '_woocommerce_persistent_cart', true));
					$query_update = "UPDATE `".$wpdb->base_prefix."ac_abandoned_cart_history_lite`
					SET abandoned_cart_info = '".$updated_cart_info."',
					abandoned_cart_time = '".$current_time."'
					WHERE user_id='".$user_id."' AND cart_ignored='0' ";
					//mysql_query($query_update);
                                        $wpdb->query($query_update);
				}
				}
			}
			
			function compare_carts($user_id, $last_abandoned_cart)
			{
				$current_woo_cart = get_user_meta($user_id, '_woocommerce_persistent_cart', true);
				$abandoned_cart_arr = json_decode($last_abandoned_cart,true);
			
				$temp_variable = "";
				if ( count($current_woo_cart['cart']) >= count($abandoned_cart_arr['cart']) )
				{
					//do nothing
				}
				else
				{
					$temp_variable = $current_woo_cart;
					$current_woo_cart = $abandoned_cart_arr;
					$abandoned_cart_arr = $temp_variable;
				}
				foreach ($current_woo_cart as $key => $value)
				{
					foreach ($value as $item_key => $item_value)
					{
						$current_cart_product_id = $item_value['product_id'];
						$current_cart_variation_id = $item_value['variation_id'];
						$current_cart_quantity = $item_value['quantity'];
			
						if (isset($abandoned_cart_arr[$key][$item_key]['product_id'])) $abandoned_cart_product_id = $abandoned_cart_arr[$key][$item_key]['product_id'];
                                                else $abandoned_cart_product_id = "";
						if (isset($abandoned_cart_arr[$key][$item_key]['variation_id'])) $abandoned_cart_variation_id = $abandoned_cart_arr[$key][$item_key]['variation_id'];
                                                else $abandoned_cart_variation_id = "";
						if (isset($abandoned_cart_arr[$key][$item_key]['quantity'])) $abandoned_cart_quantity = $abandoned_cart_arr[$key][$item_key]['quantity'];
                                                else $abandoned_cart_quantity = "";
			
						if (($current_cart_product_id != $abandoned_cart_product_id) ||
								($current_cart_variation_id != $abandoned_cart_variation_id) ||
								($current_cart_quantity != $abandoned_cart_quantity) )
						{
							return false;
						}
					}
				}
				return true;
			}
			
			function action_after_delivery_session( $order ) {
				
				global $wpdb;
				$user_id = get_current_user_id();
				delete_user_meta($user_id, '_woocommerce_ac_persistent_cart_time');
				delete_user_meta($user_id, '_woocommerce_ac_persistent_cart_temp_time');
			
				// get all latest abandoned carts that were modified
				$query = "SELECT * FROM `".$wpdb->base_prefix."ac_abandoned_cart_history_lite`
				WHERE user_id = '".$user_id."'
				AND cart_ignored = '0'
				AND recovered_cart = '0'
				ORDER BY id DESC
				LIMIT 1";
				$results = $wpdb->get_results( $query );
			
				if ( get_user_meta($user_id, '_woocommerce_ac_modified_cart', true) == md5("yes") || 
						get_user_meta($user_id, '_woocommerce_ac_modified_cart', true) == md5("no") )
				{
					
					$order_id = $order->id;
					$query_order = "UPDATE `".$wpdb->base_prefix."ac_abandoned_cart_history_lite`
					SET recovered_cart= '".$order_id."',
					cart_ignored = '1'
					WHERE id='".$results[0]->id."' ";
					//mysql_query($query_order);
                                        $wpdb->query($query_order);
					delete_user_meta($user_id, '_woocommerce_ac_modified_cart');
				}
				else
				{
					$delete_query = "DELETE FROM `".$wpdb->base_prefix."ac_abandoned_cart_history_lite`
					WHERE
					id='".$results[0]->id."' ";
					//mysql_query( $delete_query );
                                        $wpdb->query( $delete_query );
				}
			
				
			}
			
			function action_admin_init() {
				// only hook up these filters if we're in the admin panel, and the current user has permission
				// to edit posts and pages
				//echo "hii";
				if ( current_user_can( 'edit_posts' ) && current_user_can( 'edit_pages' ) ) {
					add_filter( 'mce_buttons', array(&$this, 'filter_mce_button' ));
					add_filter( 'mce_external_plugins', array(&$this, 'filter_mce_plugin' ));
				}
			}
			
			function filter_mce_button( $buttons ) {
				// add a separation before our button, here our button's id is &quot;mygallery_button&quot;
				array_unshift( $buttons, 'abandoncart_email_variables', '|' );
				return $buttons;
			}
			
			function filter_mce_plugin( $plugins ) {
				// this plugin file will work the magic of our button
				$plugins['abandoncart'] = plugin_dir_url( __FILE__ ) . 'js/abandoncart_plugin_button.js';
				return $plugins;
			}
			
			function display_tabs() {
			
				if (isset($_GET['action'])) $action = $_GET['action'];
                                else $action = "";
			
				$active_listcart = "";
				$active_emailtemplates = "";
				$active_settings = "";
				$active_stats = "";
			
				if (($action == 'listcart' || $action == 'orderdetails') || $action == '')
				{
					$active_listcart = "nav-tab-active";
				}
			
				if ($action == 'emailtemplates')
				{
					$active_emailtemplates = "nav-tab-active";
				}
			
				if ($action == 'emailsettings')
				{
					$active_settings = "nav-tab-active";
				}
			
				if ($action == 'stats')
				{
					$active_stats = "nav-tab-active";
				}
			
				?>
				
				<div style="background-image: url('<?php echo plugins_url(); ?>/woocommerce-abandoned-cart/images/ac_tab_icon.png') !important;" class="icon32"><br></div>
				<!--<span class="mce_abandoncart_email_variables"><br></span>-->
				
				<h2 class="nav-tab-wrapper woo-nav-tab-wrapper">
				<a href="admin.php?page=woocommerce_ac_page&action=listcart" class="nav-tab <?php echo $active_listcart; ?>"> <?php _e( 'Abandoned Orders', 'woocommerce-ac' );?> </a>
				<a href="admin.php?page=woocommerce_ac_page&action=emailtemplates" class="nav-tab <?php echo $active_emailtemplates; ?>"> <?php _e( 'Email Templates', 'woocommerce-ac' );?> </a>
				<a href="admin.php?page=woocommerce_ac_page&action=emailsettings" class="nav-tab <?php echo $active_settings; ?>"> <?php _e( 'Settings', 'woocommerce-ac' );?> </a>
				<a href="admin.php?page=woocommerce_ac_page&action=stats" class="nav-tab <?php echo $active_stats; ?>"> <?php _e( 'Recovered Orders', 'woocommerce-ac' );?> </a>
				</h2>
				
				<?php
			}
			
			function my_enqueue_scripts_js( $hook ) {
				
				if ( $hook != 'woocommerce_page_woocommerce_ac_page' )
				{
					return;
				}
				else
				{
				
					wp_enqueue_script( 'jquery' );
                                        wp_enqueue_script(
							'jquery-ui-min',
							'//ajax.googleapis.com/ajax/libs/jqueryui/1.9.2/jquery-ui.min.js',
							'',
							'',
							false
					);
					wp_enqueue_script( 'jquery-ui-datepicker' );
					
					//wp_enqueue_script('suggest');
					wp_enqueue_script(
							'jquery-tip',
							plugins_url('/js/jquery.tipTip.minified.js', __FILE__),
							'',
							'',
							false
					);
					wp_register_script( 'woocommerce_admin', plugins_url() . '/woocommerce/assets/js/admin/woocommerce_admin.js', array('jquery', 'jquery-ui-widget', 'jquery-ui-core'));
					wp_enqueue_script( 'woocommerce_admin' );
					
					////////////////////////////////////////////////////////////////
					
					?>
					<script type="text/javascript" >
					function delete_email_template( id )
					{
						var y=confirm('Are you sure you want to delete this Email Template');
						if(y==true)
						{
							location.href='admin.php?page=woocommerce_ac_page&action=emailtemplates&mode=removetemplate&id='+id;
					    }
					}
				    </script>
				    <!-- /////////////////////////////////////////////////////////////// -->
				    
				    <?php
				    wp_enqueue_script('tinyMCE_ac', plugins_url() . '/woocommerce-abandoned-cart/js/tinymce/jscripts/tiny_mce/tiny_mce.js');
				    wp_enqueue_script('ac_email_variables', plugins_url() . '/woocommerce-abandoned-cart/js/abandoncart_plugin_button.js');
				    ?>
				    
				    <?php
				}
			
			}
			
			function tinyMCE_ac(){
			
				?>
				<script language="javascript" type="text/javascript">
				tinyMCE.init({
					theme : "advanced",
					mode: "exact",
					elements : "woocommerce_ac_email_body",
					theme_advanced_toolbar_location : "top",
					theme_advanced_buttons1 : "abandoncart_email_variables,separator,code,separator,preview,separator,bold,italic,underline,strikethrough,separator,"
					+ "justifyleft,justifycenter,justifyright,justifyfull,formatselect,"
					+ "bullist,numlist,outdent,indent,separator,"
					+ "cut,copy,paste,separator,sub,sup,charmap",
					theme_advanced_buttons2 : "formatselect,fontselect,fontsizeselect,styleselect,forecolor,backcolor,forecolorpicker,backcolorpicker,separator,link,unlink,anchor,image,separator,"
					+"undo,redo,cleanup"
					+"image", 
					height:"500px",
					width:"1000px",
					apply_source_formatting : true,
					cleanup: true,
					plugins : "advhr,emotions,fullpage,fullscreen,iespell,media,paste,nonbreaking,pagebreak,preview,print,spellchecker,visualchars,searchreplace,insertdatetime,table,directionality,layer,style,xhtmlxtras,abandoncart",
			        theme_advanced_buttons4 : "advhr,emotions,fullpage,fullscreen,iespell,media,nonbreaking,pagebreak,print,spellchecker,visualchars,searchreplace,insertdatetime,directionality,layer,style,xhtmlxtras,insertlayer,moveforward,movebackward,absolute,cite,ins,del,abbr,acronym,attribs,help,hr,removeformat",
			        theme_advanced_buttons3 : "tablecontrols,search,replace,pastetext,pasteword,selectall,styleprops,ltr,rtl,visualaid,newdocument,blockquote",
			        extended_valid_elements : "hr[class|width|size|noshade]",
			        fullpage_fontsizes : '13px,14px,15px,18pt,xx-large',
			        fullpage_default_xml_pi : false,
			        fullpage_default_langcode : 'en',
			        fullpage_default_title : "My document title",
			        table_styles : "Header 1=header1;Header 2=header2;Header 3=header3",
			        table_cell_styles : "Header 1=header1;Header 2=header2;Header 3=header3;Table Cell=tableCel1",
			        table_row_styles : "Header 1=header1;Header 2=header2;Header 3=header3;Table Row=tableRow1",
			        table_cell_limit : 100,
			        table_row_limit : 5,
			        table_col_limit : 5,
                                convert_urls : false
				});
					
				</script>
				<?php
			}
			
			function my_enqueue_scripts_css( $hook ) {
				
				if ( $hook != 'woocommerce_page_woocommerce_ac_page' )
				{
					return;
				}
				else
				{
					wp_enqueue_style( 'jquery-ui', "http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css" , '', '', false);
					
					wp_enqueue_style( 'woocommerce_admin_styles', plugins_url() . '/woocommerce/assets/css/admin.css' );
					wp_enqueue_style( 'jquery-ui-style', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css' );
					
					?>
						
					<style>
					span.mce_abandoncart_email_variables 
					{
					    background-image: url("<?php echo plugins_url(); ?>/woocommerce-abandoned-cart/images/ac_editor_icon.png") !important;
					    background-position: center center !important;
					    background-repeat: no-repeat !important;
					}
					</style>
				
				<?php 
				}
			}
				
			/**
			 * Abandon Cart Settings Page
			 */
			function woocommerce_ac_page()
			{
				if ( is_user_logged_in() )
				{
				global $wpdb;
					
				// Check the user capabilities
				if ( !current_user_can( 'manage_woocommerce' ) )
				{
					wp_die( __( 'You do not have sufficient permissions to access this page.', 'woocommerce-ac' ) );
				}
			
				?>
			
					<div class="wrap">
						<div class="icon32" style="background-image: url('<?php echo plugins_url(); ?>/woocommerce-abandoned-cart/images/abandoned_cart_small.png') !important;">
							<br />
						</div>
							<h2><?php _e( 'WooCommerce - Abandon Cart Lite', 'woocommerce-ac' ); ?></h2>
					<?php 
					
                                        if (isset($_GET['action'])) $action = $_GET['action'];
					else $action = "";
					
					if (isset($_GET['mode'])) $mode = $_GET['mode'];
					else $mode = "";
                                        
					$this->display_tabs();
					
					if ($action == 'emailsettings')
					{
						// Save the field values
						if ( isset( $_POST['ac_settings_frm'] ) && $_POST['ac_settings_frm'] == 'save' )
						{
							$ac_settings = new stdClass();
							$ac_settings->cart_time = $_POST['cart_abandonment_time'];
							$woo_ac_settings[] = $ac_settings;
							$woocommerce_ac_settings = json_encode($woo_ac_settings);
							
							update_option('woocommerce_ac_settings',$woocommerce_ac_settings);
						}
						?>
			
							<?php if ( isset( $_POST['ac_settings_frm'] ) && $_POST['ac_settings_frm'] == 'save' ) { ?>
							<div id="message" class="updated fade"><p><strong><?php _e( 'Your settings have been saved.', 'woocommerce-ac' ); ?></strong></p></div>
							<?php } ?>
							
							<?php
								//$enable_email_sett = array();
								$enable_email_sett = json_decode(get_option('woocommerce_ac_settings'));
								?>
							<div id="content">
							  <form method="post" action="" id="ac_settings">
								  <input type="hidden" name="ac_settings_frm" value="save">
								  <div id="poststuff">
										<div class="postbox">
											<h3 class="hndle"><?php _e( 'Settings', 'woocommerce-ac' ); ?></h3>
											<div>
											  <table class="form-table">
			
				    							<tr>
				    								<th>
				    									<label for="woocommerce_ac_email_frequency"><b><?php _e( 'Cart abandoned cut-off time', 'woocommerce-ac' ); ?></b></label>
				    								</th>
				    								<td>
														<?php
														$cart_time = "";
														if ( $enable_email_sett[0]->cart_time != '' || $enable_email_sett[0]->cart_time != 'null')
														{
															$cart_time = $enable_email_sett[0]->cart_time;
														}
				    									print'<input type="text" name="cart_abandonment_time" id="cart_abandonment_time" size="5" value="'.$cart_time.'"> minutes
				    									';?>
				    									<img class="help_tip" width="16" height="16" data-tip='<?php _e( 'Consider cart abandoned after X minutes of item being added to cart & order not placed', 'woocommerce') ?>' src="<?php echo plugins_url(); ?>/woocommerce/assets/images/help.png" /></p>
				    									<!-- <span class="description"><?php
				    										_e( 'Consider cart abandoned after X minutes of item being added to cart & order not placed', 'woocommerce-ac' );
				    									?></span> -->
				    								</td>
				    							</tr>
				    							
												</table>
											</div>
										</div>
									</div>
							  <p class="submit">
								<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e( 'Save Changes', 'woocommerce-ac' ); ?>" />
							  </p>
						    </form>
						  </div>
						<?php 
					}
					elseif ($action == 'listcart' || $action == '')
					{
						?>
						
			<p> <?php _e('The list below shows all Abandoned Carts which have remained in cart for a time higher than the "Cart abandoned cut-off time" setting.', 'woocommerce-ac');?> </p>
			
			<?php
			//echo plugins_url();
			include_once(  "pagination.class.php");
			 
			/* Find the number of rows returned from a query; Note: Do NOT use a LIMIT clause in this query */
			$wpdb->get_results("SELECT wpac . * , wpu.user_login, wpu.user_email 
					  FROM `".$wpdb->base_prefix."ac_abandoned_cart_history_lite` AS wpac 
					  LEFT JOIN ".$wpdb->base_prefix."users AS wpu ON wpac.user_id = wpu.id
					  WHERE recovered_cart='0'  ");
                        
                        $count = $wpdb->num_rows;

			if($count > 0) {
				$p = new pagination;
				$p->items($count);
				$p->limit(10); // Limit entries per page
				$p->target("admin.php?page=woocommerce_ac_page&action=listcart");
				//$p->currentPage($_GET[$p->paging]); // Gets and validates the current page
                                if (isset($p->paging))
                                {
                                        if (isset($_GET[$p->paging])) $p->currentPage($_GET[$p->paging]); // Gets and validates the current page
                                }
				$p->calculate(); // Calculates what to show
				$p->parameterName('paging');
				$p->adjacents(1); //No. of page away from the current page
				$p->showCounter(true);
				 
				if(!isset($_GET['paging'])) {
					$p->page = 1;
				} else {
					$p->page = $_GET['paging'];
				}
				 
				//Query for limit paging
				$limit = "LIMIT " . ($p->page - 1) * $p->limit  . ", " . $p->limit;
				 
			}
                        else $limit = "";
			
			?>
			  
			<div class="tablenav">
			    <div class='tablenav-pages'>
			    	<?php if ($count > 0) echo $p->show();  // Echo out the list of paging. ?>
			    </div>
			</div>
			
			<?php 
			
			$order = "";
                        if(isset($_GET['order'])){
			$order = $_GET['order'];
                        }
			if ( $order == "" )
			{
				$order = "desc";
				$order_next = "asc";
			}
			elseif ( $order == "asc" )
			{
				$order_next = "desc";
			}
			elseif ( $order == "desc" )
			{
				$order_next = "asc";
			}
			
			$order_by = "";
                        if(isset($_GET['orderby'])){
			$order_by = $_GET['orderby'];
                        }
			if ( $order_by == "" )
			{
				$order_by = "abandoned_cart_time";
			}
			/* Now we use the LIMIT clause to grab a range of rows */
			$query = "SELECT wpac . * , wpu.user_login, wpu.user_email 
					  FROM `".$wpdb->base_prefix."ac_abandoned_cart_history_lite` AS wpac 
					  LEFT JOIN ".$wpdb->base_prefix."users AS wpu ON wpac.user_id = wpu.id
					  WHERE recovered_cart='0' 
					  ORDER BY `$order_by` $order 
					  $limit";
					  //echo $query;
					  $results = $wpdb->get_results( $query );
			
			/* echo "<pre>";
			print_r($results);
			echo "</pre>"; */
			//exit;
			 
			/* From here you can do whatever you want with the data from the $result link. */
			
			$ac_cutoff_time = json_decode(get_option('woocommerce_ac_settings'));
			
			?> 
			
			
			            <table class='wp-list-table widefat fixed posts' cellspacing='0' id='cart_data'>
						<tr>
							<th> <?php _e( 'Customer', 'woocommerce-ac' ); ?> </th>
							<th> <?php _e( 'Order Total', 'woocommerce-ac' ); ?> </th>
							<th scope="col" id="date_ac" class="manage-column column-date_ac sorted <?php echo $order;?>" style="">
								<a href="admin.php?page=woocommerce_ac_page&action=listcart&orderby=abandoned_cart_time&order=<?php echo $order_next;?>">
									<span> <?php _e( 'Date', 'woocommerce-ac' ); ?> </span>
									<span class="sorting-indicator"></span>
								</a>
							</th>
							<th scope="col" id="status_ac" class="manage-column column-status_ac sorted <?php echo $order;?>" style="">
								<a href="admin.php?page=woocommerce_ac_page&action=listcart&orderby=cart_ignored&order=<?php echo $order_next;?>">
									<span> <?php _e( 'Status', 'woocommerce-ac' ); ?> </span>
									<span class="sorting-indicator"></span>
								</a>
							</th>
							<th> <?php _e( 'Actions', 'woocommerce-ac' ); ?> </th>
						</tr>
			
				<?php 
						foreach ($results as $key => $value)
						{
							$abandoned_order_id = $value->id;
							$user_id = $value->user_id;
							$user_login = $value->user_login;
							$user_email = $value->user_email;
							$user_first_name = get_user_meta($value->user_id, 'first_name');
							$user_last_name = get_user_meta($value->user_id, 'last_name');
							
							$cart_info = json_decode($value->abandoned_cart_info);
							
							$order_date = "";
							$cart_update_time = $value->abandoned_cart_time;
							if ($cart_update_time != "" && $cart_update_time != 0)
							{
								$order_date = date('d M, Y h:i A', $cart_update_time);
							}
							
							$ac_cutoff_time = json_decode(get_option('woocommerce_ac_settings'));
							$cut_off_time = $ac_cutoff_time[0]->cart_time * 60;
							$current_time = current_time('timestamp');
							
							$compare_time = $current_time - $cart_update_time;
							
							$cart_details = $cart_info->cart;
							
							$line_total = 0;
							foreach ($cart_details as $k => $v)
							{
								$line_total = $line_total + $v->line_total;
							}
							
							if( $value->cart_ignored == 0 && $value->recovered_cart == 0 )
							{
								$ac_status = "Abandoned";
							}
							elseif( $value->cart_ignored == 1 && $value->recovered_cart == 0 )
							{
								$ac_status = "Abandoned but new </br>cart created after this";
							}
							else
							{
								$ac_status = "";
							}
							
							?>
							
							<?php 
							if ($compare_time > $cut_off_time && $ac_status != "" )
							{
								{
							?>
							<tr id="row_<?php echo $abandoned_order_id; ?>">
								<td><strong><?php echo "Abandoned Order #".$abandoned_order_id;?></strong><?php echo "</br>Name: ".$user_first_name[0]." ".$user_last_name[0]."<br><a href='mailto:$user_email'>".$user_email."</a>"; ?></td>
								<td><?php echo get_woocommerce_currency_symbol()." ".$line_total; ?></td>
								<td><?php echo $order_date; ?></td>
								<td><?php echo $ac_status; ?>
								<td id="<?php echo $abandoned_order_id; ?>">
								<?php echo "<a href='#' id='$abandoned_order_id-$user_id' class='remove_cart'> <img src='".plugins_url()."/woocommerce-abandoned-cart/images/delete.png' alt='Remove Cart Data' title='Remove Cart Data'></a>"; ?>
								&nbsp;
								
							</tr>
							
							<?php
								} 
							}
						}
						echo "</table>";
					}
					elseif ($action == 'emailtemplates' && ($mode != 'edittemplate' && $mode != 'addnewtemplate' ) )
					{
							?>
													
							<p> <?php _e('Add email templates at different intervals to maximize the possibility of recovering your abandoned carts.', 'woocommerce-ac');?> </p>
							
							<?php
							// Save the field values
							if ( isset( $_POST['ac_settings_frm'] ) && $_POST['ac_settings_frm'] == 'save' )
							{
								
								//$active_post = (empty($_POST['is_active'])) ? '0' : '1';
								$active_post = 1;
								if ( $active_post == 1 )
								{
									$check_query = "SELECT * FROM `".$wpdb->base_prefix."ac_email_templates_lite` 
													WHERE is_active='1' AND frequency='".$_POST['email_frequency']."' AND day_or_hour='".$_POST['day_or_hour']."' ";
									$check_results = $wpdb->get_results($check_query);
									if (count($check_results) == 0 )
									{
										$query = "INSERT INTO `".$wpdb->base_prefix."ac_email_templates_lite` 
										(subject, body, is_active, frequency, day_or_hour, template_name, from_name)
										VALUES ('".$_POST['woocommerce_ac_email_subject']."', 
												'".$_POST['woocommerce_ac_email_body']."', 
												'".$active_post."', 
												'".$_POST['email_frequency']."', 
												'".$_POST['day_or_hour']."', 
												'".$_POST['woocommerce_ac_template_name']."',
												'".$_POST['woocommerce_ac_from_name']."' )";
										//echo $query;
										//mysql_query($query);
                                                                                $wpdb->query($query);
									}
									else 
									{
										$query_update = "UPDATE `".$wpdb->base_prefix."ac_email_templates_lite`
										SET
										is_active='0'
										WHERE frequency='".$_POST['email_frequency']."' AND day_or_hour='".$_POST['day_or_hour']."' ";
										//echo $query_update;
										//mysql_query($query_update);
                                                                                $wpdb->query($query_update);
										
										$query_insert_new = "INSERT INTO `".$wpdb->base_prefix."ac_email_templates_lite` 
										( subject, body, is_active, frequency, day_or_hour, template_name, from_name)
										VALUES ('".$_POST['woocommerce_ac_email_subject']."', 
												'".$_POST['woocommerce_ac_email_body']."', 
												'".$active_post."', 
												'".$_POST['email_frequency']."', 
												'".$_POST['day_or_hour']."', 
												'".$_POST['woocommerce_ac_template_name']."',
												'".$_POST['woocommerce_ac_from_name']."' )";
										//echo $query;
										//mysql_query($query_insert_new);
                                                                                $wpdb->query($query_insert_new);
									}
								}
							}
							
							if ( isset( $_POST['ac_settings_frm'] ) && $_POST['ac_settings_frm'] == 'update' )
							{
								//$active = (empty($_POST['is_active'])) ? '0' : '1';
								$active = 1;
								if ( $active == 1 )
								{
									$check_query = "SELECT * FROM `".$wpdb->base_prefix."ac_email_templates_lite`
									WHERE is_active='1' AND frequency='".$_POST['email_frequency']."' AND day_or_hour='".$_POST['day_or_hour']."' ";
									$check_results = $wpdb->get_results($check_query);
									if (count($check_results) == 0 )
									{
										$query_update = "UPDATE `".$wpdb->base_prefix."ac_email_templates_lite`
										SET
										subject='".$_POST['woocommerce_ac_email_subject']."',
										body='".$_POST['woocommerce_ac_email_body']."',
										is_active='".$active."', frequency='".$_POST['email_frequency']."',
										day_or_hour='".$_POST['day_or_hour']."',
										template_name='".$_POST['woocommerce_ac_template_name']."',
										from_name='".$_POST['woocommerce_ac_from_name']."'
										WHERE id='".$_POST['id']."' ";
										//mysql_query($query_update);
                                                                                $wpdb->query($query_update);
									}
									else 
									{
										$query_update_new = "UPDATE `".$wpdb->base_prefix."ac_email_templates_lite`
										SET
										is_active='0'
										WHERE frequency='".$_POST['email_frequency']."' AND day_or_hour='".$_POST['day_or_hour']."' ";
										//mysql_query($query_update_new);
                                                                                $wpdb->query($query_update_new);
										
										$query_update_latest = "UPDATE `".$wpdb->base_prefix."ac_email_templates_lite`
										SET
										subject='".$_POST['woocommerce_ac_email_subject']."',
										body='".$_POST['woocommerce_ac_email_body']."',
										is_active='".$active."', frequency='".$_POST['email_frequency']."',
										day_or_hour='".$_POST['day_or_hour']."',
										template_name='".$_POST['woocommerce_ac_template_name']."',
										from_name='".$_POST['woocommerce_ac_from_name']."'
										WHERE id='".$_POST['id']."' ";
										//mysql_query($query_update_latest);
                                                                                $wpdb->query($query_update_latest);
									}
								}
							}
							
							if ( $action == 'emailtemplates' && $mode == 'removetemplate' )
							{
								$id_remove = $_GET['id'];
								$query_remove = "DELETE FROM `".$wpdb->base_prefix."ac_email_templates_lite` WHERE id='".$id_remove."' ";
								//mysql_mysql_query($query_remove);
                                                                $wpdb->query($query_remove);
							}
							
							if ( isset( $_POST['ac_settings_frm'] ) && $_POST['ac_settings_frm'] == 'save' ) { ?>
							<div id="message" class="updated fade"><p><strong><?php _e( 'The Email Template has been successfully added.', 'woocommerce-ac' ); ?></strong></p></div>
							<?php } 
							if ( isset( $_POST['ac_settings_frm'] ) && $_POST['ac_settings_frm'] == 'update' ) { ?>
							<div id="message" class="updated fade"><p><strong><?php _e( 'The Email Template has been successfully updated.', 'woocommerce-ac' ); ?></strong></p></div>
							<?php }?>
							
							<div class="tablenav">
							<p style="float:left;">
							<input type="button" value="+ Add New Template" id="add_new_template" onclick="location.href='admin.php?page=woocommerce_ac_page&action=emailtemplates&mode=addnewtemplate';" style="font-weight: bold; color: green; font-size: 18px; cursor: pointer;">
							<!--<a href="admin.php?page=woocommerce_ac_page&action=emailtemplates&mode=addnewtemplate">Add New Template</a>-->
							</p>
							
				<?php
				include_once(  "pagination.class.php"); 
				 
				/* Find the number of rows returned from a query; Note: Do NOT use a LIMIT clause in this query */
				$wpdb->get_results("SELECT wpet . *   
										FROM `".$wpdb->base_prefix."ac_email_templates_lite` AS wpet  
										"); 
                                
                                $count = $wpdb->num_rows;

				if($count > 0) {
					$p = new pagination;
					$p->items($count);
					$p->limit(10); // Limit entries per page
					$p->target("admin.php?page=woocommerce_ac_page&action=emailtemplates");
					if (isset($p->paging))
					{
						if (isset($_GET[$p->paging])) $p->currentPage($_GET[$p->paging]); // Gets and validates the current page
					}
					$p->calculate(); // Calculates what to show
					$p->parameterName('paging');
					$p->adjacents(1); //No. of page away from the current page
					$p->showCounter(true);
						
					if(!isset($_GET['paging'])) {
						$p->page = 1;
					} else {
						$p->page = $_GET['paging'];
					}
						
					//Query for limit paging
					$limit = "LIMIT " . ($p->page - 1) * $p->limit  . ", " . $p->limit;
						
				} 
                                else $limit = "";
					
				?>
							  
				    <div class='tablenav-pages'>
				    	<?php if ($count>0) echo $p->show();  // Echo out the list of paging. ?>
				    </div>
				</div>
				
				<?php 

				$order = "";
				if (isset($_GET['order'])) $order = $_GET['order'];
				if ( $order == "" )
				{
					$order = "asc";
					$order_next = "desc";
				}
				elseif ( $order == "asc" )
				{
					$order_next = "desc";
				}
				elseif ( $order == "desc" )
				{
					$order_next = "asc";
				}
					
				$order_by = "";
				if (isset($_GET['orderby'])) $order_by = $_GET['orderby'];
				if ( $order_by == "" )
				{
					$order_by = "frequency";
				}
				
				$query = "SELECT wpet . *   
						  FROM `".$wpdb->base_prefix."ac_email_templates_lite` AS wpet 
						  ORDER BY $order_by $order 
						  $limit";
				$results = $wpdb->get_results( $query );
				 
				/* From here you can do whatever you want with the data from the $result link. */
				?> 
		
			            <table class='wp-list-table widefat fixed posts' cellspacing='0' id='email_templates'>
						<tr>
							<th> <?php _e( 'Sr', 'woocommerce-ac' ); ?> </th>
							<th scope="col" id="temp_name" class="manage-column column-temp_name sorted <?php echo $order;?>" style="">
								<a href="admin.php?page=woocommerce_ac_page&action=emailtemplates&orderby=template_name&order=<?php echo $order_next;?>">
									<span> <?php _e( 'Template Name', 'woocommerce-ac' ); ?> </span>
									<span class="sorting-indicator"></span>
								</a>
							</th>
							<th scope="col" id="sent" class="manage-column column-sent sorted <?php echo $order;?>" style="">
								<a href="admin.php?page=woocommerce_ac_page&action=emailtemplates&orderby=frequency&order=<?php echo $order_next;?>">
									<span> <?php _e( 'Sent', 'woocommerce-ac' ); ?> </span>
									<span class="sorting-indicator"></span>
								</a>
							</th>
							<th> <?php _e( 'Active ?', 'woocommerce-ac' ); ?> </th>
							<th> <?php _e( 'Actions', 'woocommerce-ac' ); ?> </th>
						</tr>
							
							<?php 
							if (isset($_GET['pageno'])) $add_var = ($_GET['pageno'] - 1) * $limit; 
                                                        else $add_var = "";
							$i = 1 + $add_var;
						foreach ($results as $key => $value)
						{
								$id = $value->id;
								
								$is_active = $value->is_active;
								if ( $is_active == '1' )
								{
									$active = "Yes";
								}
								else
								{
									$active = "No";
								}
								$frequency = $value->frequency;
								$day_or_hour = $value->day_or_hour;
								?>
			
								<tr id="row_<?php echo $id; ?>">
								<td><?php echo $i; ?></td>
								<td><?php echo $value->template_name; ?></td>
								<td><?php echo $frequency." ".$day_or_hour." After Abandonment";?></td>
								<td><?php echo $active; ?></td>
								
								<td>
									<a href="admin.php?page=woocommerce_ac_page&action=emailtemplates&mode=edittemplate&id=<?php echo $id; ?>"> <img src="<?php echo plugins_url(); ?>/woocommerce-abandoned-cart/images/edit.png" alt="Edit" title="Edit" width="20" height="20"> </a>&nbsp;
									<a href="#" onclick="delete_email_template( <?php echo $id; ?> )" > <img src="<?php echo plugins_url(); ?>/woocommerce-abandoned-cart/images/delete.png" alt="Delete" title="Delete" width="20" height="20"> </a>&nbsp;
								</td>
			
								
							</tr>
			
							<?php 
							$i++;
						}
						echo "</table>";
						//echo "</p>";
			
					}
					elseif ($action == 'stats' || $action == '')
					{
						
						?>
						<p>
						<script language='javascript'>
						jQuery(document).ready(function()
						{
						jQuery('#duration_select').change(function()
						{
						var group_name = jQuery('#duration_select').val();
						var today = new Date();
						var start_date = "";
						var end_date = "";
						if ( group_name == "yesterday")
						{
							start_date = new Date(today.getFullYear(), today.getMonth(), today.getDate() - 1);
							end_date = new Date(today.getFullYear(), today.getMonth(), today.getDate() - 1);
						}
						else if ( group_name == "today")
						{
							start_date = new Date(today.getFullYear(), today.getMonth(), today.getDate());
							end_date = new Date(today.getFullYear(), today.getMonth(), today.getDate());
						}
						else if ( group_name == "last_seven")
						{
							start_date = new Date(today.getFullYear(), today.getMonth(), today.getDate() - 7);
							end_date = new Date(today.getFullYear(), today.getMonth(), today.getDate());
						}
						else if ( group_name == "last_fifteen")
						{
							start_date = new Date(today.getFullYear(), today.getMonth(), today.getDate() - 15);
							end_date = new Date(today.getFullYear(), today.getMonth(), today.getDate());
						}
						else if ( group_name == "last_thirty")
						{
							start_date = new Date(today.getFullYear(), today.getMonth(), today.getDate() - 30);
							end_date = new Date(today.getFullYear(), today.getMonth(), today.getDate());
						}
						else if ( group_name == "last_ninety")
						{
							start_date = new Date(today.getFullYear(), today.getMonth(), today.getDate() - 90);
							end_date = new Date(today.getFullYear(), today.getMonth(), today.getDate());
						}
						else if ( group_name == "last_year_days")
						{
							start_date = new Date(today.getFullYear(), today.getMonth(), today.getDate() - 365);
							end_date = new Date(today.getFullYear(), today.getMonth(), today.getDate());
						}

						var monthNames = [ "Jan", "Feb", "Mar", "Apr", "May", "Jun",
						                   "Jul", "Aug", "Sep", "Oct", "Nov", "Dec" ];
						               
						var start_date_value = start_date.getDate() + " " + monthNames[start_date.getMonth()] + " " + start_date.getFullYear();
						var end_date_value = end_date.getDate() + " " + monthNames[end_date.getMonth()] + " " + end_date.getFullYear();

						jQuery('#start_date').val(start_date_value);
						jQuery('#end_date').val(end_date_value);
						//location.href= 'admin.php?page=woocommerce_ac_page&action=stats&durationselect='+group_name;
						});
						});
						</script>
						<?php
						
						if (isset($_POST['duration_select'])) $duration_range = $_POST['duration_select'];
                                                else $duration_range = "";
						if ($duration_range == "")
						{
							if (isset($_GET['duration_select'])) $duration_range = $_GET['duration_select'];
						}
						if ($duration_range == "") $duration_range = "last_seven";
						//global $this->duration_range_select,$this->start_end_dates;
						
						?>
						<p> The Report below shows how many Abandoned Carts we were able to recover for you by sending automatic emails to encourage shoppers.</p>
						<div id="recovered_stats" class="postbox" style="display:block">
						
							<div class="inside">
							<form method="post" action="admin.php?page=woocommerce_ac_page&action=stats" id="ac_stats">
							<select id="duration_select" name="duration_select" >
							<?php
							foreach ( $this->duration_range_select as $key => $value )
							{
								$sel = "";
								if ($key == $duration_range)
								{
									$sel = " selected ";
								} 
								echo"<option value='$key' $sel> $value </option>";
							}
							
							$date_sett = $this->start_end_dates[$duration_range];
							
							?>
							</select>
							
							<script type="text/javascript">
							jQuery(document).ready(function()
							{
							var formats = ["d.m.y", "d M yy","MM d, yy"];
							jQuery("#start_date").datepicker({dateFormat: formats[1]});
							});
			
							jQuery(document).ready(function()
							{
							var formats = ["d.m.y", "d M yy","MM d, yy"];
							jQuery("#end_date").datepicker({dateFormat: formats[1]});
							});
							</script>
							
							
							<?php 
			
							if (isset($_POST['start_date'])) $start_date_range = $_POST['start_date'];
                                                        else $start_date_range = "";
							if ($start_date_range == "")
							{
								$start_date_range = $date_sett['start_date'];
							}
							if (isset($_POST['end_date'])) $end_date_range = $_POST['end_date'];
                                                        else $end_date_range = "";
							if ($end_date_range == "")
							{
								$end_date_range = $date_sett['end_date'];
							}
							
							?>
							
							<label class="start_label" for="start_day"> <?php _e( 'Start Date:', 'woocommerce-ac' ); ?> </label>
							<input type="text" id="start_date" name="start_date" readonly="readonly" value="<?php echo $start_date_range; ?>"/>
							 
							<label class="end_label" for="end_day"> <?php _e( 'End Date:', 'woocommerce-ac' ); ?> </label>
							<input type="text" id="end_date" name="end_date" readonly="readonly" value="<?php echo $end_date_range; ?>"/>
							
							<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e( 'Go', 'woocommerce-ac' ); ?>"  />
							</form>
							</div>
						</div>
						<?php 
						
						global $wpdb;
						$start_date = strtotime($start_date_range." 00:01:01");
						$end_date = strtotime($end_date_range." 23:59:59");
						
						include_once(  "pagination.class.php");
						
						/* Find the number of rows returned from a query; Note: Do NOT use a LIMIT clause in this query */
//						$wpdb->get_results($wpdb->prepare("SELECT * FROM " . $wpdb->base_prefix . "ac_abandoned_cart_history_lite
//								 WHERE abandoned_cart_time >= " . $start_date . "
//								 AND abandoned_cart_time <= " . $end_date . "
//								 AND recovered_cart > '0' 
//								 "));
                                                $wpdb->get_results($wpdb->prepare("SELECT * FROM " . $wpdb->base_prefix . "ac_abandoned_cart_history_lite
								 WHERE abandoned_cart_time >= %d
								 AND abandoned_cart_time <= %d
								 AND recovered_cart > '0' 
								 ",$start_date,$end_date));
                                                $count = $wpdb->num_rows;
						
						if($count > 0) {
							$p = new pagination;
							$p->items($count);
							$p->limit(10); // Limit entries per page
							$p->target("admin.php?page=woocommerce_ac_page&action=stats&duration_select=$duration_range");
							//$p->currentPage($_GET[$p->paging]); // Gets and validates the current page
                                                        if (isset($p->paging))
                                                        {
                                                                if (isset($_GET[$p->paging])) $p->currentPage($_GET[$p->paging]); // Gets and validates the current page
                                                        }
							$p->calculate(); // Calculates what to show
							$p->parameterName('paging');
							$p->adjacents(1); //No. of page away from the current page
							$p->showCounter(true);
						
							if(!isset($_GET['paging'])) {
								$p->page = 1;
							} else {
								$p->page = $_GET['paging'];
							}
						
							//Query for limit paging
							$limit = "LIMIT " . ($p->page - 1) * $p->limit  . ", " . $p->limit;
						
						}
						else
							$limit = "";	
						?>
															  
						<div class="tablenav">
						    <div class='tablenav-pages'>
						    	<?php if ($count>0) echo $p->show();  // Echo out the list of paging. ?>
						    </div>
						</div>
						
						<?php 
						
						$order = "";
						if (isset($_GET['order'])) $order = $_GET['order'];
						if ( $order == "" )
						{
							$order = "desc";
							$order_next = "asc";
						}
						elseif ( $order == "asc" )
						{
							$order_next = "desc";
						}
						elseif ( $order == "desc" )
						{
							$order_next = "asc";
						}
						
						$order_by = "";
						if (isset($_GET['orderby'])) $order_by = $_GET['orderby'];
						if ( $order_by == "" )
						{
							$order_by = "recovered_cart";
						}
						
						$query_ac = "SELECT * FROM " . $wpdb->base_prefix . "ac_abandoned_cart_history_lite  
									 WHERE abandoned_cart_time >= " . $start_date . "
									 AND abandoned_cart_time <= " . $end_date . "
									 AND recovered_cart > 0 
									 ORDER BY $order_by $order $limit";
						$ac_results = $wpdb->get_results( $query_ac );
						
						$query_ac_carts = "SELECT * FROM " . $wpdb->base_prefix . "ac_abandoned_cart_history_lite
										   WHERE abandoned_cart_time >= " . $start_date . "
									 	   AND abandoned_cart_time <= " . $end_date;
						$ac_carts_results = $wpdb->get_results( $query_ac_carts );
						
						$recovered_item = $recovered_total = $count_carts = $total_value = $order_total = 0;
						foreach ( $ac_carts_results as $key => $value)
						{
							// 
							//if( $value->recovered_cart == 0 )
							{
								$count_carts += 1;
									
								$cart_detail = json_decode($value->abandoned_cart_info);
								$product_details = $cart_detail->cart;
								
								$line_total = 0;
								foreach ($product_details as $k => $v)
								{
									$line_total = $line_total + $v->line_total;
								}
								
								$total_value += $line_total;
							}
						}
						$table_data = "";
						foreach ( $ac_results as $key => $value)
						{	
							if( $value->recovered_cart != 0 )
							{
								$recovered_id = $value->recovered_cart;
								$rec_order = get_post_meta( $recovered_id );
								$woo_order = new WC_Order($recovered_id);
								$recovered_date = strtotime($woo_order->order_date);
								$recovered_date_new = date('d M, Y h:i A', $recovered_date);
								$recovered_item += 1;
								
							/*	$order_items = unserialize($rec_order['_order_items'][0]);
								foreach ( $order_items as $order_key => $order_value)
								{
									$order_total += $order_items[$order_key]['line_total'];
								}*/
								$recovered_total += $rec_order['_order_total'][0];
								$abandoned_date = date('d M, Y h:i A', $value->abandoned_cart_time);
								
								$abandoned_order_id = $value->id;
                                                                
                                                                $billing_first_name = $billing_last_name = $billing_email = ''; 
								$recovered_order_total = 0;
								if (isset($rec_order['_billing_first_name'][0])) {
									$billing_first_name = $rec_order['_billing_first_name'][0];
								}
								if (isset($rec_order['_billing_last_name'][0])) {
									$billing_last_name = $rec_order['_billing_last_name'][0];
								}
								if (isset($rec_order['_billing_email'][0])) {
									$billing_email = $rec_order['_billing_email'][0];
								}
								if (isset($rec_order['_order_total'][0])) {
									$recovered_order_total = $rec_order['_order_total'][0];
								}
								
								$table_data .="<tr>
											  <td>Name: ".$billing_first_name." ".$billing_last_name."</br><a href='mailto:'".$billing_email."'>".$billing_email."</td>
											  <td>".$abandoned_date."</td>
											  <td>".$recovered_date_new."</td>
											  <td>".get_woocommerce_currency_symbol()." ".$recovered_order_total."</td>
											  <td> <a href=\"post.php?post=". $recovered_id."&action=edit\">View Details</td>";
							}
						}
						
						?>
						<div id="recovered_stats" class="postbox" style="display:block">
						<div class="inside" >
						<p style="font-size: 15px"> During the selected range <strong><?php echo $count_carts; ?> </strong> carts totaling <strong><?php echo get_woocommerce_currency_symbol()." ".$total_value; ?></strong> were abandoned. We were able to recover <strong><?php echo $recovered_item; ?></strong> of them, which led to an extra <strong><?php echo get_woocommerce_currency_symbol()." ".$recovered_total; ?></strong> in sales</p>
						</div>
						</div>
						
						<table class='wp-list-table widefat fixed posts' cellspacing='0' id='cart_data'>
												<tr>
												<th> <?php _e( 'Customer', 'woocommerce-ac' ); ?> </th>
												<th scope="col" id="created_date" class="manage-column column-created_date sorted <?php echo $order;?>" style="">
													<a href="admin.php?page=woocommerce_ac_page&action=stats&orderby=abandoned_cart_time&order=<?php echo $order_next;?>&durationselect=<?php echo $duration_range;?>">
														<span> <?php _e( 'Created On', 'woocommerce-ac' ); ?> </span>
														<span class="sorting-indicator"></span>
													</a>
												</th>
												<th scope="col" id="rec_order" class="manage-column column-rec_order sorted <?php echo $order;?>" style="">
													<a href="admin.php?page=woocommerce_ac_page&action=stats&orderby=recovered_cart&order=<?php echo $order_next;?>&durationselect=<?php echo $duration_range;?>">
														<span> <?php _e( 'Recovered Date', 'woocommerce-ac' ); ?> </span>
														<span class="sorting-indicator"></span>
													</a>
												</th>
												<th> <?php _e( 'Order Total', 'woocommerce-ac' ); ?> </th>
												<th></th>
												</tr>
						<?php
						echo $table_data;
						print('</table>');
					}
							
				if (isset($_GET['action'])) $action = $_GET['action'];
				if (isset($_GET['mode'])) $mode = $_GET['mode'];
				
				if ( $action == 'emailtemplates' && ($mode == 'addnewtemplate' || $mode == 'edittemplate' ))
				{
					if($mode=='edittemplate')
					{
					$edit_id=$_GET['id'];
					$query="SELECT wpet . *  FROM `".$wpdb->base_prefix."ac_email_templates_lite` AS wpet WHERE id='".$edit_id."'";
					$results = $wpdb->get_results( $query );
					}
					
					$active_post = (empty($_POST['is_active'])) ? '0' : '1';
						
						?>
			
							<div id="content">
							  <form method="post" action="admin.php?page=woocommerce_ac_page&action=emailtemplates" id="ac_settings">
							  
							  <input type="hidden" name="mode" value="<?php echo $mode;?>" />
							  <input type="hidden" name="id" value="<?php echo $_GET['id'];?>" />
							  
							  <?php
								$button_mode = "save";
								$display_message = "Add Email Template";
								if ( $mode == 'edittemplate' )
								{
									$button_mode = "update";
									$display_message = "Edit Email Template";
								}
								  print'<input type="hidden" name="ac_settings_frm" value="'.$button_mode.'">';?>
								  <div id="poststuff">
										<div class="postbox">
											<h3 class="hndle"><?php _e( $display_message, 'woocommerce-ac' ); ?></h3>
											<div>
											  <table class="form-table" id="addedit_template">
												
												<tr>
													<th>
														<label for="woocommerce_ac_template_name"><b><?php _e( 'Template Name:', 'woocommerce-ac ');?></b></label>
													</th>
													<td>
													<?php
													$template_name = "";
													if( $mode == 'edittemplate' )
													{
														$template_name = $results[0]->template_name;
													}
													
													print'<input type="text" name="woocommerce_ac_template_name" id="woocommerce_ac_template_name" class="regular-text" value="'.$template_name.'">';?>
													<img class="help_tip" width="16" height="16" data-tip='<?php _e('Enter a template name for reference', 'woocommerce') ?>' src="<?php echo plugins_url(); ?>/woocommerce/assets/images/help.png" /></p>
												</tr>
												
											    <tr>
											       <th>
				    									<label for="woocommerce_ac_from_name"><b><?php _e( 'Send From This Name:', 'woocommerce-ac' ); ?></b></label>
				    								</th>
				    								<td>
													<?php
													$from_name="Admin";
													if ( $mode == 'edittemplate')
													{
														$from_name=$results[0]->from_name;
													}
													
													print'<input type="text" name="woocommerce_ac_from_name" id="woocommerce_ac_from_name" class="regular-text" value="'.$from_name.'">';?>
													<img class="help_tip" width="16" height="16" data-tip='<?php _e('Enter the name that should appear in the email sent', 'woocommerce') ?>' src="<?php echo plugins_url(); ?>/woocommerce/assets/images/help.png" /></p>
													<?php //echo stripslashes(get_option( 'woocommerce_ac_email_body' )); ?></textarea>
												</tr>
												
												<tr>
											       <th>
				    									<label for="woocommerce_ac_email_subject"><b><?php _e( 'Subject:', 'woocommerce-ac' ); ?></b></label>
				    								</th>
				    								<td>
													<?php
													$subject_edit="";
													if ( $mode == 'edittemplate')
													{
														$subject_edit=$results[0]->subject;
													}
													
													print'<input type="text" name="woocommerce_ac_email_subject" id="woocommerce_ac_email_subject" class="regular-text" value="'.$subject_edit.'">';?>
													<img class="help_tip" width="16" height="16" data-tip='<?php _e('Enter the subject that should appear in the email sent', 'woocommerce') ?>' src="<?php echo plugins_url(); ?>/woocommerce/assets/images/help.png" /></p>
													<?php //echo stripslashes(get_option( 'woocommerce_ac_email_body' )); ?></textarea>
												</tr>
			
				    							<tr>
				    								<th>
				    									<label for="woocommerce_ac_email_body"><b><?php _e( 'Email Body:', 'woocommerce-ac' ); ?></b></label>
				    								</th>
				    								<td>
			
													<?php
													$initial_data = "";//stripslashes(get_option( 'woocommerce_ac_email_body' ));
													if ( $mode == 'edittemplate')
													{
														$initial_data = $results[0]->body;
													}
													
													/* $settings = array(
													'quicktags' => array('buttons' => 'em,strong,link',),
													'text_area_name'=>'woocommerce_ac_email_body',//name you want for the textarea
													'quicktags' => true,
													'class' => 'tinymce',
													'tinymce' => true
													);
													//echo "<textarea id='editortest'> </textarea>";
													$id = 'woocommerce_ac_email_body';//has to be lower case
													wp_editor($initial_data,$id,$settings); */
													
													echo "<textarea id='woocommerce_ac_email_body' name='woocommerce_ac_email_body' rows='15'' cols='80'>".$initial_data."</textarea>";
													?>
				    								
				    									<!--<textarea name="woocommerce_ac_email_body" cols="45" rows="3" class="regular-text"><?php echo stripslashes(get_option( 'woocommerce_ac_email_body' )); ?></textarea><br />-->
				    									<span class="description"><?php
				    										echo __( 'Message to be sent in the reminder email.', 'woocommerce-ac' );
				    									?></span>
				    								</td>
				    							</tr>
			
				    							<tr>
				    								<th>
				    									<label for="woocommerce_ac_email_frequency"><b><?php _e( 'Send this email:', 'woocommerce-ac' ); ?></b></label>
				    								</th>
				    								<td>
				    								
				    									<select name="email_frequency" id="email_frequency">
				    									
				    									<?php
															$frequency_edit="";
															if(	$mode == 'edittemplate')
															{
																$frequency_edit=$results[0]->frequency;
															}
															
				    										for ($i=1;$i<4;$i++)
				    										{
																printf( "<option %s value='%s'>%s</option>\n",
																	selected( $i, $frequency_edit, false ),
																	esc_attr( $i ),
																	$i
																);
				    										}
				    									
				    									?>
				    										
				    									</select>
			
														<select name="day_or_hour" id="day_or_hour">
			
														<?php
														$days_or_hours_edit = "";
														if ( $mode == 'edittemplate')
														{
															$days_or_hours_edit=$results[0]->day_or_hour;
														}
														
														$days_or_hours=array(
																		   'Days' => 'Day(s)',
																		   'Hours' => 'Hour(s)');
														foreach($days_or_hours as $k => $v)
														{
															printf( "<option %s value='%s'>%s</option>\n",
																selected( $k, $days_or_hours_edit, false ),
																esc_attr( $k ),
																$v
															);
				    									}
														?>
			
														</select>
			
				    									
				    									<span class="description"><?php
				    									echo __( 'after cart is abandoned.', 'woocommerce-ac' );
				    									?></span>
				    								</td>
				    							</tr>
				    							
				    							<tr>
				    							<th>
				    								<label for="woocommerce_ac_email_preview"><b><?php _e( 'Send a test email to:', 'woocommerce-ac' ); ?></b></label>
				    							</th>
				    							<td> 
				    							
				    							<input type="text" id="send_test_email" name="send_test_email" class="regular-text" >
				    							<input type="button" value="Send a test email" id="preview_email" onclick="javascript:void(0);">
				    							<img class="help_tip" width="16" height="16" data-tip='<?php _e('Enter the email id to which the test email needs to be sent.', 'woocommerce') ?>' src="<?php echo plugins_url(); ?>/woocommerce/assets/images/help.png" />
				    							<div id="preview_email_sent_msg" style="display:none;"></div>
				    							</p>
				    							
				    							</td>
				    							</tr>

				    							
												</table>
											</div>
										</div>
									</div>
							  <p class="submit">
								<?php
									$button_value = "Save Changes";
									if ( $mode == 'edittemplate' )
									{
										$button_value = "Update Changes";
									}?>
								<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e( $button_value, 'woocommerce-ac' ); ?>"  />
							  </p>
						    </form>
						  </div>
						<?php 
						}
						
						
						
				}
				
			}
				
				
				////////////////////////////////////////////////////////////////
				
				function my_action_javascript()
				{
					?>
						<script type="text/javascript" >
						jQuery(document).ready(function($)
						{
							$("table#cart_data a.remove_cart").click(function()
							{
								//alert('hello there');
								var y=confirm('Are you sure you want to delete this Abandoned Order');
								if(y==true)
								{
									var passed_id = this.id;
									var arr = passed_id.split('-');
									var abandoned_order_id = arr[0];
									var user_id = arr[1];
									var data = {
										abandoned_order_id: abandoned_order_id,
										user_id: user_id,
										action: 'remove_cart_data'
										};
							
								// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
								$.post(ajaxurl, data, function(response)
								{
									//alert('Got this from the server: ' + response);
									$("#row_" + abandoned_order_id).hide();
								});
								}
							});
						});
						</script>
						<?php
						
					}
				
					function remove_cart_data() {
						
						global $wpdb; // this is how you get access to the database
					
						$abandoned_order_id = $_POST['abandoned_order_id'];
						$user_id = $_POST['user_id'];
						$action = $_POST['action'];
						
						$query = "DELETE FROM `".$wpdb->base_prefix."ac_abandoned_cart_history_lite` 
									WHERE 
									id = '$abandoned_order_id' ";
						//echo $query;
						$results = $wpdb->get_results( $query );
						
						die();
					}
					
					//////////////////////////////////////////////////////////////
					
					function my_action_send_preview()
					{
						?>
							<script type="text/javascript" >
							
							jQuery(document).ready(function($)
							{
								$("table#addedit_template input#preview_email").click(function()
								{
									//alert('hello there');
									var from_name_preview = $('#woocommerce_ac_from_name').val();
									var subject_email_preview = $('#woocommerce_ac_email_subject').val();
									var body_email_preview = tinyMCE.activeEditor.getContent();
									var send_email_id = $('#send_test_email').val();
									
									//alert(tinyMCE.activeEditor.getContent());
									var data = {
										from_name_preview: from_name_preview,
										subject_email_preview: subject_email_preview,
										body_email_preview: body_email_preview,
										send_email_id: send_email_id,
										action: 'preview_email_sent'
									};
									//var data = $('#ac_settings').serialize();
									
									// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
									$.post(ajaxurl, data, function(response)
									{
										$("#preview_email_sent_msg").html("<img src='<?php echo plugins_url(); ?>/woocommerce-abandoned-cart/images/check.jpg'>&nbsp;Email has been sent successfully.");
										$("#preview_email_sent_msg").fadeIn();
										setTimeout(function(){$("#preview_email_sent_msg").fadeOut();},3000);
										//alert('Got this from the server: ' + response);
									});
								});
							});
							</script>
							<?php
					}
					
					function preview_email_sent() {
						
						$from_email_name = $_POST['from_name_preview'];
						$subject_email_preview = $_POST['subject_email_preview'];
						$body_email_preview = $_POST['body_email_preview'];
						$to_email_preview = $_POST['send_email_id'];
						
						$user_email_from = get_option('admin_email');
						$headers[] = "From: ".$from_email_name." <".$user_email_from.">"."\r\n";
						$headers[] = "Content-Type: text/html"."\r\n";
                                                
//                                                $headers = "From: ".$from_email_name." <".$from_email_preview.">"."\r\n";
//                                                $headers .= "Content-Type: text/html"."\r\n";
                                               // $headers .= "Reply-To: <".$reply_name_preview.">"."\r\n";
						
						wp_mail( $to_email_preview, $subject_email_preview, stripslashes($body_email_preview), $headers );
				
						echo "email sent";
						
						die();
					}
					
					/////////////////////////////////////////////////////////////////////////////////
					
					
					
					
		}
			
		}
		
		$woocommerce_abandon_cart = new woocommerce_abandon_cart();
		
}

?>