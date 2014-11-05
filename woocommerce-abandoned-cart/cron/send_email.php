<?php 

require_once('../../../../wp-load.php');

//if (is_woocommerce_active()) 
{

	/**
	 * woocommerce_abandon_cart_cron class
	 **/
	if (!class_exists('woocommerce_abandon_cart_cron')) {
	
		class woocommerce_abandon_cart_cron {
			
			var $cart_settings_cron;
			var $cart_abandon_cut_off_time_cron;
			
			public function __construct() {
				
				$this->cart_settings_cron = json_decode(get_option('woocommerce_ac_settings'));
				
				$this->cart_abandon_cut_off_time_cron = ($this->cart_settings_cron[0]->cart_time) * 60;
				
			}
			
			/*-----------------------------------------------------------------------------------*/
			/* Class Functions */
			/*-----------------------------------------------------------------------------------*/
			
			/**
			 * Function to send emails
			 */
			function woocommerce_ac_send_email() {
				
				//$cart_settings = json_decode(get_option('woocommerce_ac_settings'));
				
				//$cart_abandon_cut_off_time_cron = ($cart_settings[0]->cart_time) * 60;
				
				{
				
				global $wpdb, $woocommerce;
			
				//Grab the cart abandoned cut-off time from database.
				$cart_settings = json_decode(get_option('woocommerce_ac_settings'));
			
				$cart_abandon_cut_off_time = ($cart_settings[0]->cart_time) * 60;
			
				//Fetch all active templates present in the system
				$query = "SELECT wpet . *
				FROM `".$wpdb->prefix."ac_email_templates` AS wpet
				WHERE wpet.is_active = '1'
				ORDER BY `day_or_hour` DESC, `frequency` ASC ";
				$results = $wpdb->get_results( $query );
			
				$hour_seconds = 3600; // 60 * 60
				$day_seconds = 86400; // 24 * 60 * 60
				foreach ($results as $key => $value)
				{
					if ($value->day_or_hour == 'Days')
					{
						$time_to_send_template_after = $value->frequency * $day_seconds;
					}
					elseif ($value->day_or_hour == 'Hours')
					{
						$time_to_send_template_after = $value->frequency * $hour_seconds;
					}
			
					$carts = $this->get_carts($time_to_send_template_after, $cart_abandon_cut_off_time);
			
					$email_frequency = $value->frequency;
					$email_body_template = $value->body;
			
					$email_subject = $value->subject;
					$user_email_from = get_option('admin_email');
					$headers[] = "From: ".$value->from_name." <".$user_email_from.">";
					$headers[] = "Content-Type: text/html";
					
					$template_id = $value->id;
					
					foreach ($carts as $key => $value )
					{
						$cart_info_db_field = json_decode($value->abandoned_cart_info);
						if (count($cart_info_db_field->cart) > 0 )
						{
							$cart_update_time = $value->abandoned_cart_time;
			
							$new_user = $this->check_sent_history($value->user_id, $cart_update_time, $template_id, $value->id );
							if ( $new_user == true)
							{
								$cart_info_db = $value->abandoned_cart_info;
			
								$email_body = $email_body_template;
								
								$email_body = str_replace("{{customer.firstname}}", get_user_meta($value->user_id, 'first_name', true), $email_body);
								$email_body = str_replace("{{customer.lastname}}", get_user_meta($value->user_id, 'last_name', true), $email_body);
								$email_body = str_replace("{{customer.fullname}}", get_user_meta($value->user_id, 'first_name', true)." ".get_user_meta($value->user_id, 'last_name', true), $email_body);
								
								$query_sent = "INSERT INTO `".$wpdb->prefix."ac_sent_history` (template_id, abandoned_order_id, sent_time, sent_email_id)
								VALUES ('".$template_id."', '".$value->id."', '".current_time('mysql')."', '".$value->user_email."' )";
			
								mysql_query($query_sent);
			
								$query_id = "SELECT * FROM `".$wpdb->prefix."ac_sent_history` WHERE template_id='".$template_id."' AND abandoned_order_id='".$value->id."'
								ORDER BY id DESC
								LIMIT 1 ";
			
								$results_sent = $wpdb->get_results( $query_id );
			
								$email_sent_id = $results_sent[0]->id;
								
								$user_email = $value->user_email;
			
								//echo $email_body."<hr>";
								
								wp_mail( $user_email, $email_subject, $email_body, $headers );
			
							}
			
						}
					}
			
				}
				}
			}
			
			/**
			 * get all carts which have the creation time earlier than the one that is passed
			 *
			 */
			function get_carts($template_to_send_after_time, $cart_abandon_cut_off_time) {
				
				global $wpdb;
			
				$cart_time = current_time('timestamp') - $template_to_send_after_time - $cart_abandon_cut_off_time;
			
				$query = "SELECT wpac . * , wpu.user_login, wpu.user_email
				FROM `".$wpdb->prefix."ac_abandoned_cart_history` AS wpac
				LEFT JOIN ".$wpdb->prefix."users AS wpu ON wpac.user_id = wpu.id
				WHERE cart_ignored = '0'
				AND abandoned_cart_time < $cart_time
				ORDER BY `id` ASC ";
			
				$results = $wpdb->get_results( $query );
			
				return $results;
			
				exit;
			}
			
			function check_sent_history($user_id, $cart_update_time, $template_id, $id) {
				
				global $wpdb;
				$query = "SELECT wpcs . * , wpac . abandoned_cart_time , wpac . user_id
				FROM `".$wpdb->prefix."ac_sent_history` AS wpcs
				LEFT JOIN ".$wpdb->prefix."ac_abandoned_cart_history AS wpac ON wpcs.abandoned_order_id =  wpac.id
				WHERE
				template_id='".$template_id."'
				AND
				wpcs.abandoned_order_id = '".$id."'
				ORDER BY 'id' DESC
				LIMIT 1 ";
			
				$results = $wpdb->get_results( $query );
			
				if (count($results) == 0)
				{
					return true;
				}
				elseif ($results[0]->abandoned_cart_time < $cart_update_time)
				{
					return true;
				}
				else
				{
					return false;
				}
			
			}
			
			
		}
		
	}
	
	$woocommerce_abandon_cart_cron = new woocommerce_abandon_cart_cron();

	$woocommerce_abandon_cart_cron->woocommerce_ac_send_email();
	
}

?>