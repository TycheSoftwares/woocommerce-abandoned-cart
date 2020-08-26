<?php
/**
 * Abandoned Cart Lite for WooCommerce
 *
 * Admin notices handler class.
 *
 * @author  Tyche Softwares
 * @package abandoned-cart-lite
 * @since 5.6 file name changed from wcal_actions.php to class-wcal-delete-handler
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * It will display the admin notices for the pro version.
 *
 * @author  Tyche Softwares
 */
class Wcal_Admin_Notice {

	/**
	 * Show a DB Update Notice when upgrading from 4.7 to latest version
	 *
	 * @since 4.8
	 *
	 * @hook admin_notices
	 */
	public static function wcal_show_db_update_notice() {

		if ( isset( $_GET['page'] ) && 'woocommerce_ac_page' === $_GET['page'] ) {

			if ( ! get_option( 'wcal_scheduler_update_dismiss', FALSE ) ) {
				$post_link = '<a href="https://www.tychesoftwares.com/moving-to-the-action-scheduler-library/?utm_source=AcLiteNotice&utm_medium=link&utm_campaign=AbandonCartLite" target="_blank">here</a>';
				?>
					<div id='wcal_cron_notice' class='is-dismissible notice notice-info wcal-cron-notice'>
						<p><?php _e( "The <b>Abandoned Cart Lite for WooCommerce</b> now uses the Action Scheduler library to send reminders. For further details, please visit $post_link.", 'woocommerce-abandoned-cart' ); ?></p>
					</div>
				<?php	
			}
		}
		

		if ( isset( $_GET['ac_update'] ) && 'email_templates' === $_GET['ac_update'] ) {
			return;
		}

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$results = $wpdb->get_results(
			'SHOW FULL COLUMNS FROM `' . $wpdb->prefix . 'ac_email_templates_lite` WHERE Field = "subject" OR Field = "body"'
		);

		foreach ( $results as $key => $value ) {
			if ( 'utf8mb4_unicode_ci' !== $value->Collation ) {
				/* translators: %s Link to Email Templates page */
				printf( __( '<div id="wcal_update" class="updated woocommerce-message" style="padding:15px;"><span>We need to update your email template database for some improvements. Please take a backup of your databases for your piece of mind</span><span class="submit"><a href="%s" class="button-primary" style="float:right;">Update</a></span></div>', 'woocommerce-abandoned-cart' ), 'admin.php?page=woocommerce_ac_page&action=listcart&ac_update=email_templates' );
				break;
			}
		}
	}
}
