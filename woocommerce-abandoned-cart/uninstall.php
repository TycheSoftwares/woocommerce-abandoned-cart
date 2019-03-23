<?php
/**
 * Abandoned Cart Lite for WooCommerce Uninstall
 *
 * Uninstalling Abandoned Cart Lite for WooCommerce deletes tables, and options.
 *
 * @author      Tyche Softwares
 * @package     Abandoned-Cart-Lite-for-WooCommerce/Uninstaller
 * @version     5.3.0
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

wp_clear_scheduled_hook( 'woocommerce_ac_send_email_action' );

if ( ! is_multisite() ) {
	$table_name_ac_abandoned_cart_history = $wpdb->prefix . "ac_abandoned_cart_history_lite";
	$sql_ac_abandoned_cart_history = "DROP TABLE " . $table_name_ac_abandoned_cart_history ;
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	$wpdb->get_results( $sql_ac_abandoned_cart_history );

	$table_name_ac_email_templates = $wpdb->prefix . "ac_email_templates_lite";
	$sql_ac_email_templates = "DROP TABLE " . $table_name_ac_email_templates ;
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	$wpdb->get_results( $sql_ac_email_templates );

	$table_name_ac_sent_history = $wpdb->prefix . "ac_sent_history_lite";
	$sql_ac_sent_history = "DROP TABLE " . $table_name_ac_sent_history ;
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	$wpdb->get_results( $sql_ac_sent_history );

	$table_name_ac_guest_abandoned_cart_history = $wpdb->prefix . "ac_guest_abandoned_cart_history_lite";
	$sql_ac_abandoned_cart_history = "DROP TABLE " . $table_name_ac_guest_abandoned_cart_history ;
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	$wpdb->get_results( $sql_ac_abandoned_cart_history );

	$sql_table_user_meta_cart = "DELETE FROM `" . $wpdb->prefix . "usermeta` WHERE meta_key = '_woocommerce_persistent_cart'";
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	$wpdb->get_results( $sql_table_user_meta_cart );

	$sql_table_user_meta_cart_modified = "DELETE FROM `" . $wpdb->prefix . "usermeta` WHERE meta_key = '_woocommerce_ac_modified_cart'";
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	$wpdb->get_results( $sql_table_user_meta_cart_modified );   
} else {    
	$query   = "SELECT blog_id FROM `".$wpdb->prefix."blogs`";
	$results = $wpdb->get_results( $query );

	foreach( $results as $key => $value ) {      
		$table_name_ac_abandoned_cart_history = $wpdb->prefix .$value->blog_id."_"."ac_abandoned_cart_history_lite";
		$sql_ac_abandoned_cart_history = "DROP TABLE " . $table_name_ac_abandoned_cart_history ;
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		$wpdb->get_results( $sql_ac_abandoned_cart_history );

		$table_name_ac_email_templates = $wpdb->prefix .$value->blog_id."_"."ac_email_templates_lite";
		$sql_ac_email_templates = "DROP TABLE " . $table_name_ac_email_templates ;
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		$wpdb->get_results( $sql_ac_email_templates );

		$table_name_ac_sent_history = $wpdb->prefix .$value->blog_id."_"."ac_sent_history_lite";
		$sql_ac_sent_history = "DROP TABLE " . $table_name_ac_sent_history ;
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		$wpdb->get_results( $sql_ac_sent_history );

		$table_name_ac_guest_abandoned_cart_history = $wpdb->prefix . "ac_guest_abandoned_cart_history_lite";
		$sql_ac_abandoned_cart_history = "DROP TABLE " . $table_name_ac_guest_abandoned_cart_history ;
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		$wpdb->get_results( $sql_ac_abandoned_cart_history );

		$sql_table_user_meta_cart = "DELETE FROM `" . $wpdb->prefix.$value->blog_id."_"."usermeta` WHERE meta_key = '_woocommerce_persistent_cart'";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		$wpdb->get_results( $sql_table_user_meta_cart );

		$sql_table_user_meta_cart_modified = "DELETE FROM `" . $wpdb->prefix.$value->blog_id."_"."usermeta` WHERE meta_key = '_woocommerce_ac_modified_cart'";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		$wpdb->get_results( $sql_table_user_meta_cart_modified );        
	}
}
delete_option( 'woocommerce_ac_email_body' );
delete_option( 'ac_lite_cart_abandoned_time' );
delete_option( 'ac_lite_email_admin_on_recovery' );
delete_option( 'ac_lite_settings_status' );
delete_option( 'woocommerce_ac_default_templates_installed' );  
delete_option( 'wcal_security_key' );
delete_option( 'ac_lite_track_guest_cart_from_cart_page' );
delete_option( 'wcal_from_name' );
delete_option( 'wcal_from_email' );
delete_option( 'wcal_reply_email' );

delete_option( 'ac_security_key' );
delete_option( 'wcal_activate_time' );
delete_option( 'ac_lite_alter_table_queries' );
delete_option( 'ac_lite_delete_alter_table_queries' );
delete_option( 'wcal_allow_tracking' );
delete_option( 'wcal_ts_tracker_last_send' );

delete_option( 'wcal_welcome_page_shown_time' );
delete_option( 'wcal_welcome_page_shown' );

delete_option( 'wcal_guest_cart_capture_msg' );
delete_option( 'wcal_logged_cart_capture_msg' );

delete_option( 'ac_lite_delete_abandoned_order_days' );
delete_option( 'wcal_new_default_templates' );

delete_option( 'ac_lite_delete_redundant_queries' );