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

if ( is_multisite() ) { // Multisite.

	$blog_list = get_sites();
	foreach ( $blog_list as $blog_list_key => $blog_list_value ) {
		$blog_id_number = $blog_list_value->blog_id;
		if ( $blog_id_number > 1 ) {

			$sub_site_prefix = $wpdb->prefix . $blog_id_number . '_';

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			$wpdb->get_results( 'DROP TABLE ' . $sub_site_prefix . 'ac_abandoned_cart_history_lite' ); // phpcs:ignore

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			$wpdb->get_results( 'DROP TABLE ' . $sub_site_prefix . 'ac_email_templates_lite' ); //phpcs:ignore

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			$wpdb->get_results( 'DROP TABLE ' . $sub_site_prefix . 'ac_sent_history_lite' ); //phpcs:ignore

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			$wpdb->get_results( 'DROP TABLE ' . $sub_site_prefix . 'ac_guest_abandoned_cart_history_lite' ); //phpcs:ignore

			$wpdb->query( "DELETE FROM `$sub_site_prefix" . "options` WHERE option_name LIKE 'wcal_template_%'" ); // phpcs:ignore

			delete_blog_option( $blog_id_number, 'woocommerce_ac_email_body' );
			delete_blog_option( $blog_id_number, 'ac_lite_cart_abandoned_time' );
			delete_blog_option( $blog_id_number, 'ac_lite_email_admin_on_recovery' );
			delete_blog_option( $blog_id_number, 'ac_lite_settings_status' );
			delete_blog_option( $blog_id_number, 'woocommerce_ac_default_templates_installed' );
			delete_blog_option( $blog_id_number, 'wcal_security_key' );
			delete_blog_option( $blog_id_number, 'ac_lite_track_guest_cart_from_cart_page' );
			delete_blog_option( $blog_id_number, 'wcal_from_name' );
			delete_blog_option( $blog_id_number, 'wcal_from_email' );
			delete_blog_option( $blog_id_number, 'wcal_reply_email' );

			delete_blog_option( $blog_id_number, 'ac_security_key' );
			delete_blog_option( $blog_id_number, 'wcal_activate_time' );
			delete_blog_option( $blog_id_number, 'ac_lite_alter_table_queries' );
			delete_blog_option( $blog_id_number, 'ac_lite_delete_alter_table_queries' );
			delete_blog_option( $blog_id_number, 'wcal_allow_tracking' );
			delete_blog_option( $blog_id_number, 'wcal_ts_tracker_last_send' );

			delete_blog_option( $blog_id_number, 'wcal_welcome_page_shown_time' );
			delete_blog_option( $blog_id_number, 'wcal_welcome_page_shown' );

			delete_blog_option( $blog_id_number, 'wcal_guest_cart_capture_msg' );
			delete_blog_option( $blog_id_number, 'wcal_logged_cart_capture_msg' );

			delete_blog_option( $blog_id_number, 'ac_lite_delete_abandoned_order_days' );
			delete_blog_option( $blog_id_number, 'wcal_new_default_templates' );

			delete_blog_option( $blog_id_number, 'ac_lite_delete_redundant_queries' );
			delete_blog_option( $blog_id_number, 'wcal_enable_cart_emails' );
			delete_blog_option( $blog_id_number, 'wcal_scheduler_update_dismiss' );
			delete_blog_option( $blog_id_number, 'wcal_add_email_status_col' );
			delete_blog_option( $blog_id_number, 'wcal_db_version' );
			delete_blog_option( $blog_id_number, 'wcal_previous_version' );
			delete_blog_option( $blog_id_number, 'wcal_gdpr_consent_migrated' );
			delete_blog_option( $blog_id_number, 'wcal_enable_gdpr_consent' );
			delete_blog_option( $blog_id_number, 'wcal_email_type_setup' );
			delete_blog_option( $blog_id_number, 'wcal_add_utm_to_links' );
			delete_blog_option( $blog_id_number, 'wcal_delete_coupon_data' );
			delete_blog_option( $blog_id_number, 'wcal_gdpr_allow_opt_out' );
			delete_blog_option( $blog_id_number, 'wcal_gdpr_opt_out_message' );
			delete_blog_option( $blog_id_number, 'wcal_guest_user_id_altered' );
			delete_blog_option( $blog_id_number, 'wcal_guest_users_manual_reset_needed' );
			delete_blog_option( $blog_id_number, 'wcal_auto_login_users' );
		} else {

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			$wpdb->get_results( 'DROP TABLE ' . $wpdb->prefix . 'ac_abandoned_cart_history_lite' ); //phpcs:ignore

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			$wpdb->get_results( 'DROP TABLE ' . $wpdb->prefix . 'ac_email_templates_lite' ); //phpcs:ignore

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			$wpdb->get_results( 'DROP TABLE ' . $wpdb->prefix . 'ac_sent_history_lite' ); //phpcs:ignore

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			$wpdb->get_results( 'DROP TABLE ' . $wpdb->prefix . 'ac_guest_abandoned_cart_history_lite' ); //phpcs:ignore

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			$wpdb->get_results( "DELETE FROM `$wpdb->prefix" . "usermeta` WHERE meta_key = '_woocommerce_persistent_cart'" ); //phpcs:ignore

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			$wpdb->get_results( "DELETE FROM `$wpdb->prefix" . "usermeta` WHERE meta_key = '_woocommerce_ac_modified_cart'" ); //phpcs:ignore

			$wpdb->query( "DELETE FROM `$wpdb->prefix" . "options` WHERE option_name LIKE 'wcal_template_%'" ); // phpcs:ignore
		}
	}
} else { // Single site.

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$wpdb->get_results( 'DROP TABLE ' . $wpdb->prefix . 'ac_abandoned_cart_history_lite' ); //phpcs:ignore

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$wpdb->get_results( 'DROP TABLE ' . $wpdb->prefix . 'ac_email_templates_lite' ); //phpcs:ignore

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$wpdb->get_results( 'DROP TABLE ' . $wpdb->prefix . 'ac_sent_history_lite' ); //phpcs:ignore

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$wpdb->get_results( 'DROP TABLE ' . $wpdb->prefix . 'ac_guest_abandoned_cart_history_lite' ); //phpcs:ignore

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$wpdb->get_results( "DELETE FROM `$wpdb->prefix" . "usermeta` WHERE meta_key = '_woocommerce_persistent_cart'" ); //phpcs:ignore

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$wpdb->get_results( "DELETE FROM `$wpdb->prefix" . "usermeta` WHERE meta_key = '_woocommerce_ac_modified_cart'" ); //phpcs:ignore

	$wpdb->query( "DELETE FROM `$wpdb->prefix" . "options` WHERE option_name LIKE 'wcal_template_%'" ); // phpcs:ignore
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
delete_option( 'wcal_enable_cart_emails' );
delete_option( 'wcal_scheduler_update_dismiss' );
delete_option( 'wcal_add_email_status_col' );
delete_option( 'wcal_db_version' );
delete_option( 'wcal_previous_version' );

delete_option( 'wcal_gdpr_consent_migrated' );
delete_option( 'wcal_enable_gdpr_consent' );
delete_option( 'wcal_email_type_setup' );
delete_option( 'wcal_add_utm_to_links' );
delete_option( 'wcal_delete_coupon_data' );
delete_option( 'wcal_gdpr_allow_opt_out' );
delete_option( 'wcal_gdpr_opt_out_message' );
delete_option( 'wcal_guest_user_id_altered' );
delete_option( 'wcal_guest_users_manual_reset_needed' );
delete_option( 'wcal_auto_login_users' );
