<?php
/**
 * Abandoned Cart Lite for WooCommerce
 *
 * It will add the default template for the plugin.
 *
 * @author  Tyche Softwares
 * @package abandoned-cart-lite
 * @since 2.5
 */

/**
 * Default Template Class.
 */
class Wcal_Default_Template_Settings {

	/**
	 * This function will load default template while activating the plugin.
	 *
	 * @param string $db_prefix - DB prefix.
	 * @param int    $blog_id - Blog ID.
	 * @globals mixed $wpdb
	 * @since 2.5
	 */
	public function wcal_create_default_templates( $db_prefix, $blog_id ) {
		global $wpdb;
		$template_name_array    = 'Initial';
		$template_subject_array = 'Hey {{customer.firstname}}!! You left something in your cart';
		$active_post_array      = 0;
		$email_frequency_array  = 1;
		$day_or_hour_array      = 'Hours';
		ob_start();
		include WCAL_PLUGIN_PATH . '/includes/templates/template_1.php';
		$content            = ob_get_clean();
		$body_content_array = addslashes( $content );
		$is_wc_template     = 1;
		$default_template   = 1;
		$header_text        = addslashes( 'You left Something in Your Cart!' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query(
			$wpdb->prepare(
				'INSERT INTO `' . $db_prefix . 'ac_email_templates_lite`
		   		( subject, body, is_active, frequency, day_or_hour, template_name, is_wc_template, default_template, wc_email_header )
		   		VALUES ( %s, %s, %s, %s, %s, %s, %s, %s, %s )',
				$template_subject_array,
				$body_content_array,
				$active_post_array,
				$email_frequency_array,
				$day_or_hour_array,
				$template_name_array,
				$is_wc_template,
				$default_template,
				$header_text
			)
		);

		if ( 0 === $blog_id ) {
			add_option( 'wcal_new_default_templates', 'yes' );
		} else {
			add_blog_option( $blog_id, 'wcal_new_default_templates', 'yes' );
		}
	}
}
