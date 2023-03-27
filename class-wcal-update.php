<?php
/**
 * Abandoned Cart Lite for WooCommerce Uninstall
 *
 * Uninstalling Abandoned Cart Lite for WooCommerce deletes tables, and options.
 *
 * @author      Tyche Softwares
 * @package     Abandoned-Cart-Lite-for-WooCommerce/DB Updates
 * @version     5.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Wcal_Update' ) ) {

	/**
	 * DB changes when updating the plugin.
	 */
	class Wcal_Update {

		/**
		 * Add a scheduled event for updating the DB for each version update.
		 *
		 * @since 5.8.2
		 */
		public static function wcal_schedule_update_action() {
			// Apply a fix for update action not being run.
			self::wcal_fix_db_version_issue();
			// IMP: The default value for get option should be updated in each release to match the current version to ensure update code is not run for first time installs.
			if ( get_option( 'wcal_db_version', WCAL_PLUGIN_VERSION ) !== WCAL_PLUGIN_VERSION && function_exists( 'as_enqueue_async_action' ) && false === as_next_scheduled_action( 'wcal_update_db' ) ) {
				as_enqueue_async_action( 'wcal_update_db' );
			}
		}

		/**
		 * Add the DB version option record.
		 *
		 * @since 5.11.1
		 */
		public static function wcal_fix_db_version_issue() {
			if ( is_multisite() ) {

				$blog_list = get_sites();
				foreach ( $blog_list as $blog_list_key => $blog_list_value ) {
					if ( $blog_list_value->blog_id > 1 ) { // child sites.
						$blog_id = $blog_list_value->blog_id;
						add_blog_option( $blog_id, 'wcal_db_version', '5.10.0' ); // This version number should not be changed. Issue #786.
					}
				}
			} else { // single site.
				add_option( 'wcal_db_version', '5.10.0' ); // This version number should not be changed. Issue #786.
			}
		}

		/**
		 * It will be executed when the plugin is upgraded.
		 *
		 * @hook admin_init
		 * @globals mixed $wpdb
		 * @since 1.0
		 */
		public static function wcal_update_db_check() {

			// check whether its a multi site install or a single site install.
			if ( is_multisite() ) {

				// check if tables exist for the child sites, if not, create.
				if ( 'yes' !== get_blog_option( 1, 'wcal_update_multisite' ) ) {
					// run the activate function.
					woocommerce_abandon_cart_lite::wcal_activate();
					update_blog_option( 1, 'wcal_update_multisite', 'yes' );
				}
				$blog_list = get_sites();
				foreach ( $blog_list as $blog_list_key => $blog_list_value ) {
					if ( $blog_list_value->blog_id > 1 ) { // child sites.
						$blog_id = $blog_list_value->blog_id;
						self::wcal_process_db_update( $blog_id );
					} else { // parent site.
						self::wcal_process_db_update();
					}
				}
			} else { // single site.
				self::wcal_process_db_update();
			}

		}

		/**
		 * Changes in the DB.
		 *
		 * @param int $blog_id - Blog ID (needed for multisites).
		 */
		public static function wcal_process_db_update( $blog_id = 0 ) {

			global $woocommerce, $wpdb;

			$db_prefix = ( 0 === $blog_id ) ? $wpdb->prefix : $wpdb->prefix . $blog_id . '_';

			if ( 0 === $blog_id ) { // single site.
				$wcal_guest_user_id_altered = get_option( 'wcal_guest_user_id_altered' );
				$wcal_manual_reset_needed   = get_option( 'wcal_guest_users_manual_reset_needed' );
				if ( ! get_option( 'wcal_new_default_templates' ) ) {
					$default_template = new Wcal_Default_Template_Settings();
					$default_template->wcal_create_default_templates( $db_prefix, $blog_id );
				}

				update_option( 'wcal_db_version', WCAL_PLUGIN_VERSION );
			} else { // multi site - child sites.
				$wcal_guest_user_id_altered = get_blog_option( $blog_id, 'wcal_guest_user_id_altered' );
				$wcal_manual_reset_needed   = get_blog_option( $blog_id, 'wcal_guest_users_manual_reset_needed' );
				if ( ! get_blog_option( $blog_id, 'wcal_new_default_templates' ) ) {
					$default_template = new Wcal_Default_Template_Settings();
					$default_template->wcal_create_default_templates( $db_prefix, $blog_id );
				}

				update_blog_option( $blog_id, 'wcal_db_version', WCAL_PLUGIN_VERSION );
			}

			// 5.14.0 - Alter Guest Table Auto Increment value if needed.
			if ( 'yes' !== $wcal_guest_user_id_altered ) {
				self::wcal_reset_guest_user_id( $db_prefix, $blog_id, 1 );
			} elseif ( 'yes' === $wcal_manual_reset_needed ) { // Fix #860 - This should be removed in 5.15.0.
				self::wcal_reset_guest_user_id( $db_prefix, $blog_id, 1 );
			}

			self::wcal_alter_tables( $db_prefix, $blog_id );
			self::wcal_individual_settings( $blog_id );
			self::wcal_cleanup( $db_prefix, $blog_id );

		}

		/**
		 * Modify table structures.
		 *
		 * @param string $db_prefix - DB Prefix.
		 * @param int    $blog_id - Blog ID (needed for multisites).
		 */
		public static function wcal_alter_tables( $db_prefix, $blog_id ) {

			global $wpdb;

			if ( 0 === $blog_id ) {
				$tables_altered = get_option( 'ac_lite_alter_table_queries' );
			} else {
				$tables_altered = get_blog_option( $blog_id, 'ac_lite_alter_table_queries' );
			}
			if ( 'yes' !== $tables_altered ) {

				if ( ! $wpdb->get_var( 'SHOW COLUMNS FROM ' . $db_prefix . 'ac_abandoned_cart_history_lite LIKE "user_type"' ) ) { //phpcs:ignore
					$wpdb->query( 'ALTER TABLE ' . $db_prefix . 'ac_abandoned_cart_history_lite ADD `user_type` text AFTER  `recovered_cart`' ); //phpcs:ignore
				}

				if ( ! $wpdb->get_var( 'SHOW COLUMNS FROM ' . $db_prefix . 'ac_email_templates_lite LIKE "is_wc_template"' ) ) { //phpcs:ignore 
					$wpdb->query( 'ALTER TABLE ' . $db_prefix . "ac_email_templates_lite ADD COLUMN `is_wc_template` enum('0','1') COLLATE utf8_unicode_ci NOT NULL AFTER `template_name`, ADD COLUMN `default_template` int(11) NOT NULL AFTER `is_wc_template`" ); //phpcs:ignore
				}

				if ( ! $wpdb->get_var( 'SHOW COLUMNS FROM ' . $db_prefix . "ac_email_templates_lite LIKE 'wc_email_header'" ) ) { //phpcs:ignore
					$wpdb->query( 'ALTER TABLE ' . $db_prefix . 'ac_email_templates_lite ADD COLUMN `wc_email_header` varchar(50) NOT NULL AFTER `default_template`' ); //phpcs:ignore
				}

				if ( $wpdb->get_var( "SHOW TABLES LIKE '{$db_prefix}ac_abandoned_cart_history_lite';" ) ) { //phpcs:ignore
					if ( ! $wpdb->get_var( "SHOW COLUMNS FROM `{$db_prefix}ac_abandoned_cart_history_lite` LIKE 'unsubscribe_link';" ) ) { //phpcs:ignore
						$wpdb->query( "ALTER TABLE {$db_prefix}ac_abandoned_cart_history_lite ADD `unsubscribe_link` enum('0','1') COLLATE utf8_unicode_ci NOT NULL AFTER `user_type`;" ); //phpcs:ignore
					}
				}

				if ( $wpdb->get_var( "SHOW TABLES LIKE '{$db_prefix}ac_abandoned_cart_history_lite';" ) ) { //phpcs:ignore
					if ( ! $wpdb->get_var( "SHOW COLUMNS FROM `{$db_prefix}ac_abandoned_cart_history_lite` LIKE 'session_id';" ) ) { //phpcs:ignore
						$wpdb->query( "ALTER TABLE {$db_prefix}ac_abandoned_cart_history_lite ADD `session_id` varchar(50) COLLATE utf8_unicode_ci NOT NULL AFTER `unsubscribe_link`;" ); //phpcs:ignore
					}
				}
				// Create new column for email type in templates.
				if ( $wpdb->get_var( "SHOW TABLES LIKE '{$db_prefix}ac_email_templates_lite';" ) ) { //phpcs:ignore
					if ( ! $wpdb->get_var( "SHOW COLUMNS FROM `{$db_prefix}ac_email_templates_lite` LIKE 'email_type';" ) ) { //phpcs:ignore
						$wpdb->query( "ALTER TABLE {$db_prefix}ac_email_templates_lite ADD `email_type` VARCHAR(50) NOT NULL AFTER `id`;" ); //phpcs:ignore
					}
				}
				if ( 'yes' !== get_option( 'wcal_email_type_setup', '' ) ) {
					update_option( 'wcal_email_type_setup', 'yes' );
					$wpdb->query( 'UPDATE `' . $db_prefix . 'ac_email_templates_lite` SET email_type="abandoned_cart_email"' ); // phpcs:ignore
				}

				/**
				 * We have moved email templates fields in the setings section. SO to remove that fields column fro the db we need it.
				 * For existing user we need to fill this setting with the first template.
				 *
				 * @since 4.7
				 */
				if ( $wpdb->get_var( "SHOW TABLES LIKE '{$db_prefix}ac_email_templates_lite';" ) ) { //phpcs:ignore
					if ( $wpdb->get_var( "SHOW COLUMNS FROM `{$db_prefix}ac_email_templates_lite` LIKE 'from_email';" ) ) { //phpcs:ignore
						$get_email_template_result  = $wpdb->get_results( "SELECT `from_email` FROM {$db_prefix}ac_email_templates_lite WHERE `is_active` = '1' ORDER BY `id` ASC LIMIT 1" ); //phpcs:ignore
						$wcal_from_email           = '';
						if ( isset( $get_email_template_result ) && count( $get_email_template_result ) > 0 ) {
							$wcal_from_email = $get_email_template_result[0]->from_email;
							/* Store data in setings api*/
							if ( 0 === $blog_id ) {
								update_option( 'wcal_from_email', $wcal_from_email );
							} else {
								update_blog_option( $blog_id, 'wcal_from_email', $wcal_from_email );
							}

							/* Delete table from the Db*/
							$wpdb->query( "ALTER TABLE {$db_prefix}ac_email_templates_lite DROP COLUMN `from_email`;" ); //phpcs:ignore
						}
					}

					if ( $wpdb->get_var( "SHOW COLUMNS FROM `{$db_prefix}ac_email_templates_lite` LIKE 'from_name';" ) ) { //phpcs:ignore
						$get_email_template_from_name_result = $wpdb->get_results( "SELECT `from_name` FROM {$db_prefix}ac_email_templates_lite WHERE `is_active` = '1' ORDER BY `id` ASC LIMIT 1" ); //phpcs:ignore
						$wcal_from_name                      = '';
						if ( isset( $get_email_template_from_name_result ) && count( $get_email_template_from_name_result ) > 0 ) {
							$wcal_from_name = $get_email_template_from_name_result[0]->from_name;
							/* Store data in setings api*/
							if ( 0 === $blog_id ) {
								add_option( 'wcal_from_name', $wcal_from_name );
							} else {
								add_blog_option( $blog_id, 'wcal_from_name', $wcal_from_name );
							}
							/* Delete table from the Db*/
							$wpdb->query( "ALTER TABLE {$db_prefix}ac_email_templates_lite DROP COLUMN `from_name`;" ); //phpcs:ignore
						}
					}

					if ( $wpdb->get_var( "SHOW COLUMNS FROM `{$db_prefix}ac_email_templates_lite` LIKE 'reply_email';" ) ) { //phpcs:ignore
						$get_email_template_reply_email_result = $wpdb->get_results( "SELECT `reply_email` FROM {$db_prefix}ac_email_templates_lite WHERE `is_active` = '1' ORDER BY `id` ASC LIMIT 1" ); //phpcs:ignore
						$wcal_reply_email                      = '';
						if ( isset( $get_email_template_reply_email_result ) && count( $get_email_template_reply_email_result ) > 0 ) {
							$wcal_reply_email = $get_email_template_reply_email_result[0]->reply_email;
							/* Store data in setings api*/
							if ( 0 === $blog_id ) {
								update_option( 'wcal_reply_email', $wcal_reply_email );
							} else {
								update_blog_option( $blog_id, 'wcal_reply_email', $wcal_reply_email );
							}

							/* Delete table from the Db*/
							$wpdb->query( "ALTER TABLE {$db_prefix}ac_email_templates_lite DROP COLUMN `reply_email`;" ); //phpcs:ignore
						}
					}
				}

				if ( 0 === $blog_id ) {
					if ( ! get_option( 'wcal_security_key' ) ) {
						update_option( 'wcal_security_key', 'qJB0rGtIn5UB1xG03efyCp' );
					}

					update_option( 'ac_lite_alter_table_queries', 'yes' );
				} else {
					if ( ! get_blog_option( $blog_id, 'wcal_security_key' ) ) {
						update_blog_option( $blog_id, 'wcal_security_key', 'qJB0rGtIn5UB1xG03efyCp' );
					}

					update_blog_option( $blog_id, 'ac_lite_alter_table_queries', 'yes' );
				}
			}

			// 5.8.2 - Rename manual_email to email_reminder_status.
			if ( 'yes' !== get_option( 'wcal_add_email_status_col', '' ) ) {
				add_option( 'wcal_add_email_status_col', 'yes' );
				self::wcal_update_email_status( $db_prefix );
			}

			// 5.10.0 adding Minutes to the Mail frequency

			$results = $wpdb->get_results( 'SHOW COLUMNS FROM ' . $db_prefix . "ac_email_templates_lite LIKE 'day_or_hour'" );   //phpcs:ignore

			if ( isset( $results, $results[0]->Type ) && ( $results[0]->Type !== "ENUM('Days','Hours','Minutes')" ) ) { //phpcs:ignore
				$wpdb->query( 'ALTER TABLE ' . $db_prefix . "ac_email_templates_lite CHANGE `day_or_hour` `day_or_hour` ENUM('Days','Hours','Minutes') CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL" );   //phpcs:ignore
			}
			if ( ! $wpdb->get_var( 'SHOW COLUMNS FROM ' . $db_prefix . "ac_email_templates_lite LIKE 'coupon_code'" ) ) { //phpcs:ignore
				$wpdb->query( 'ALTER TABLE ' . $db_prefix . 'ac_email_templates_lite  ADD COLUMN `coupon_code` varchar(50) NOT NULL AFTER `wc_email_header`' ); //phpcs:ignore
			}
			if ( ! $wpdb->get_var( 'SHOW COLUMNS FROM ' . $db_prefix . "ac_email_templates_lite LIKE 'generate_unique_coupon_code'" ) ) { //phpcs:ignore
				$wpdb->query( 'ALTER TABLE ' . $db_prefix . 'ac_email_templates_lite ADD COLUMN `generate_unique_coupon_code` ENUM("0","1") CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL AFTER `coupon_code`' ); //phpcs:ignore
			}
			if ( ! $wpdb->get_var( 'SHOW COLUMNS FROM ' . $db_prefix . "ac_email_templates_lite LIKE 'discount'" ) ) { //phpcs:ignore
				$wpdb->query( 'ALTER TABLE ' . $db_prefix . 'ac_email_templates_lite  ADD COLUMN `discount` varchar(50) NOT NULL AFTER `generate_unique_coupon_code`' ); //phpcs:ignore
			}
			if ( ! $wpdb->get_var( 'SHOW COLUMNS FROM ' . $db_prefix . "ac_email_templates_lite LIKE 'discount_type'" ) ) { //phpcs:ignore
				$wpdb->query( 'ALTER TABLE ' . $db_prefix . 'ac_email_templates_lite ADD COLUMN `discount_type` varchar(50) NOT NULL AFTER `discount`' ); //phpcs:ignore
			}
			if ( ! $wpdb->get_var( 'SHOW COLUMNS FROM ' . $db_prefix . "ac_email_templates_lite LIKE 'discount_shipping'" ) ) { //phpcs:ignore
				$wpdb->query( 'ALTER TABLE ' . $db_prefix . 'ac_email_templates_lite ADD COLUMN `discount_shipping` varchar(50) NOT NULL AFTER `discount_type`' ); //phpcs:ignore
			}
			if ( ! $wpdb->get_var( 'SHOW COLUMNS FROM ' . $db_prefix . "ac_email_templates_lite LIKE 'discount_expiry'" ) ) { //phpcs:ignore
				$wpdb->query( 'ALTER TABLE ' . $db_prefix . 'ac_email_templates_lite ADD COLUMN `discount_expiry` varchar(50) NOT NULL AFTER `discount_shipping`' ); //phpcs:ignore
			}
			if ( ! $wpdb->get_var( 'SHOW COLUMNS FROM ' . $db_prefix . "ac_email_templates_lite LIKE 'individual_use'" ) ) { //phpcs:ignore
				$wpdb->query( 'ALTER TABLE ' . $db_prefix . 'ac_email_templates_lite ADD COLUMN `individual_use` ENUM("0","1") CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL AFTER `discount_expiry`' ); //phpcs:ignore
			}
			// 5.12.0
			if ( ! $wpdb->get_var( 'SHOW COLUMNS FROM `' . $db_prefix . "ac_abandoned_cart_history_lite` LIKE 'checkout_link'" ) ) { // phpcs:ignore
				$wpdb->query( 'ALTER TABLE ' . $db_prefix . 'ac_abandoned_cart_history_lite ADD `checkout_link` varchar(500) NOT NULL AFTER `email_reminder_status`' ); // phpcs:ignore
			}
		}
		/**
		 * Add a new column email_reminder_status in the cart history lite table.
		 *
		 * @param string $db_prefix - DB prefix.
		 * @since 5.8.2
		 */
		public static function wcal_update_email_status( $db_prefix ) {

			global $wpdb;

			if ( $wpdb->get_var( "SHOW TABLES LIKE '{$db_prefix}ac_abandoned_cart_history_lite';" ) ) { //phpcs:ignore
				if ( ! $wpdb->get_var( "SHOW COLUMNS FROM `{$db_prefix}ac_abandoned_cart_history_lite` LIKE 'email_reminder_status';" ) ) { //phpcs:ignore
					$wpdb->query( "ALTER TABLE {$db_prefix}ac_abandoned_cart_history_lite ADD `email_reminder_status` VARCHAR(50) COLLATE utf8_unicode_ci NOT NULL AFTER `session_id`;" ); //phpcs:ignore
				}
			}

			// Mark the old carts for whom email sequences have completed as 'complete'.
			$get_last_template        = wcal_common::wcal_get_last_email_template();
			$template_freq            = is_array( $get_last_template ) ? intval( array_pop( $get_last_template ) ) : 0;
			$cron_duration            = 15 * 60;
			$leave_carts_abandoned_in = current_time( 'timestamp' ) - ( $template_freq + $cron_duration ); // phpcs:ignore

			$wpdb->query( // phpcs:ignore
				$wpdb->prepare(
					'UPDATE ' . $db_prefix . "ac_abandoned_cart_history_lite SET email_reminder_status = 'complete' WHERE abandoned_cart_time < %s AND email_reminder_status = ''", // phpcs:ignore
					$leave_carts_abandoned_in
				)
			);

		}
		/**
		 * Move settings from serialized to individual.
		 *
		 * @param int $blog_id - Blog ID (needed for multistes).
		 */
		public static function wcal_individual_settings( $blog_id = 0 ) {

			if ( 0 === $blog_id ) {
				$woocommerce_ac_settings = get_option( 'woocommerce_ac_settings' );
				$ac_settings             = get_option( 'ac_lite_settings_status' );
			} else {
				$woocommerce_ac_settings = get_blog_option( $blog_id, 'woocommerce_ac_settings' );
				$ac_settings             = get_blog_option( $blog_id, 'ac_lite_settings_status' );
			}

			if ( isset( $ac_settings ) && 'INDIVIDUAL' !== $ac_settings ) {
				$cart_time                    = isset( $woocommerce_ac_settings[0]->cart_time ) ? $wcal_settings[0]->cart_time : '10';
				$delete_order_days            = isset( $woocommerce_ac_settings[0]->delete_order_days ) ? $woocommerce_ac_settings[0]->delete_order_days : '';
				$admin_email                  = isset( $woocommerce_ac_settings[0]->email_admin ) ? $woocommerce_ac_settings[0]->email_admin : '';
				$disable_guest_from_cart_page = isset( $woocommerce_ac_settings[0]->disable_guest_cart_from_cart_page ) ? $woocommerce_ac_settings[0]->disable_guest_cart_from_cart_page : '';

				if ( 0 === $blog_id ) {
					add_option( 'ac_lite_cart_abandoned_time', $cart_time );
					add_option( 'ac_lite_delete_abandoned_order_days', $delete_order_days );
					add_option( 'ac_lite_email_admin_on_recovery', $admin_email );
					add_option( 'ac_lite_track_guest_cart_from_cart_page', $disable_guest_from_cart_page );

					update_option( 'ac_lite_settings_status', 'INDIVIDUAL' );
					// Delete the main settings record.
					delete_option( 'woocommerce_ac_settings' );

				} else {

					add_blog_option( $blog_id, 'ac_lite_cart_abandoned_time', $cart_time );
					add_blog_option( $blog_id, 'ac_lite_delete_abandoned_order_days', $delete_order_days );
					add_blog_option( $blog_id, 'ac_lite_email_admin_on_recovery', $admin_email );
					add_blog_option( $blog_id, 'ac_lite_track_guest_cart_from_cart_page', $disable_guest_from_cart_page );

					update_blog_option( $blog_id, 'ac_lite_settings_status', 'INDIVIDUAL' );
					// Delete the main settings record.
					delete_blog_option( $blog_id, 'woocommerce_ac_settings' );

				}
			}

			// 5.12.0 - GDPR Consent.
			if ( '' === get_option( 'wcal_gdpr_consent_migrated', '' ) ) {
				update_option( 'wcal_gdpr_consent_migrated', 'yes' );
				if ( '' !== get_option( 'wcal_guest_cart_capture_msg', '' ) || '' !== get_option( 'wcal_logged_cart_capture_msg', '' ) ) {
					update_option( 'wcal_enable_gdpr_consent', 'on' );
				}
			}
		}

		/**
		 * Cleanup the redundant data.
		 *
		 * @param string $db_prefix - DB prefix to be used.
		 * @param int    $blog_id - Blog ID (needed for multisites).
		 */
		public static function wcal_cleanup( $db_prefix, $blog_id ) {

			global $wpdb;

			if ( 0 === $blog_id ) {
				if ( 'yes' !== get_option( 'ac_lite_delete_redundant_queries', '' ) ) {
					$wpdb->delete( $db_prefix . 'ac_abandoned_cart_history_lite', array( 'abandoned_cart_info' => '{"cart":[]}' ) ); //phpcs:ignore
					update_option( 'ac_lite_delete_redundant_queries', 'yes' );
				}
			} else {

				if ( 'yes' !== get_blog_option( $blog_id, 'ac_lite_delete_redundant_queries', '' ) ) {
					$wpdb->delete( $db_prefix . 'ac_abandoned_cart_history_lite', array( 'abandoned_cart_info' => '{"cart":[]}' ) ); //phpcs:ignore
					update_blog_option( $blog_id, 'ac_lite_delete_redundant_queries', 'yes' );
				}
			}
		}

		/**
		 * Check if the A_I value is set correctly for guest users table.
		 *
		 * @param string $db_prefix - DB Prefix.
		 * @param int    $blog_id - Blog ID (needed for multisites).
		 * @param int    $count - Number of times we tried to set the A_I to a correct value.
		 *
		 * @since 5.14.0
		 */
		public static function wcal_reset_guest_user_id( $db_prefix, $blog_id, $count ) {
			global $wpdb;
			if ( $wpdb->get_var( "SHOW TABLES LIKE '" . $db_prefix . "ac_guest_abandoned_cart_history_lite'" ) ) { // phpcs:ignore
				$last_id = $wpdb->get_var( 'SELECT max(id) FROM `' . $db_prefix . 'ac_guest_abandoned_cart_history_lite`;' ); // phpcs:ignore
				if ( null !== $last_id && $last_id <= 63000000 ) {
					$wpdb->query( 'ALTER TABLE ' . $db_prefix . 'ac_guest_abandoned_cart_history_lite AUTO_INCREMENT = 63000000;' ); // phpcs:ignore
				} elseif ( $last_id > 63000000 ) {
					self::wcal_update_option( $blog_id, 'wcal_guest_users_manual_reset_needed', 'no' );
					return;
				}
				$user_id_status = self::wcal_confirm_guest_user_id( $db_prefix, $blog_id, $count );
				if ( ! $user_id_status ) {
					self::wcal_update_option( $blog_id, 'wcal_guest_users_manual_reset_needed', 'yes' );
				} else {
					self::wcal_update_option( $blog_id, 'wcal_guest_users_manual_reset_needed', 'no' );
					return;
				}
			}
		}

		/**
		 * Update the option record.
		 *
		 * @param int    $blog_id - Blog ID.
		 * @param string $option_name - Option Name.
		 * @param string $option_value - Option Value.
		 */
		public static function wcal_update_option( $blog_id, $option_name, $option_value ) {
			if ( 0 === $blog_id ) {
				update_option( $option_name, $option_value );
			} else {
				update_blog_option( $blog_id, $option_name, $option_value );
			}
		}

		/**
		 * Check if the A_I value is set correctly for guest users table.
		 *
		 * @param string $db_prefix - DB Prefix.
		 * @param int    $blog_id - Blog ID (needed for multisites).
		 * @param int    $count - Number of times we tried to set the A_I to a correct value.
		 * @return bool  Status - success|failure.
		 *
		 * @since 5.14.0
		 */
		public static function wcal_confirm_guest_user_id( $db_prefix, $blog_id, $count ) {
			global $wpdb;

			// Insert a Test Record.
			$wpdb->insert( // phpcs:ignore
				$db_prefix . 'ac_guest_abandoned_cart_history_lite',
				array(
					'email_id' => 'test@tychesoftwares.com',
				)
			);
			// Fetch the record to confirm the User ID.
			$last_id = $wpdb->get_var( 'SELECT max(id) FROM `' . $db_prefix . 'ac_guest_abandoned_cart_history_lite`;' ); // phpcs:ignore
			if ( null !== $last_id && $last_id >= 63000000 ) { // If correct, delete the test record.
				$wpdb->delete( // phpcs:ignore
					$db_prefix . 'ac_guest_abandoned_cart_history_lite',
					array(
						'id'       => $last_id,
						'email_id' => 'test@tychesoftwares.com',
					)
				);
				self::wcal_update_option( $blog_id, 'wcal_guest_user_id_altered', 'yes' );
				return true;
			} else {
				if ( 1 === $count ) {
					$count += 1; // phpcs:ignore
					self::wcal_reset_guest_user_id( $db_prefix, $blog_id, $count );
				} else {
					return false;
				}
			}
			return false;

		}

		/**
		 * Add a notice in WP Admin
		 *
		 * @since 5.14.0
		 */
		public static function wcal_add_notice() {
			?>
			<div class="notice notice-error">
				<p>
					<?php _e( 'There was an error creating the guest cart table for Abandoned Cart Lite for WooCommerce. Please contact the plugin author via <a href="mailto:support@tychesoftwares.freshdesk.com">email</a>', 'woocommerce-abandoned-cart' ); // phpcs:ignore?>
				</p>
			</div>
			<?php
		}
	}
}
