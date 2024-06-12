<?php
/**
 * Abandoned Cart Lite for WooCommerce
 *
 * It will capture the guest users data.
 *
 * @author  Tyche Softwares
 * @package abandoned-cart-lite
 */

if ( ! class_exists( 'Wcal_Guest_Ac' ) ) {

	/**
	 * It will add the js, ajax for capturing the guest cart.
	 * It will add an action for populating the guest data when user comes from the abandoned cart reminder emails.
	 *
	 * @since 2.2
	 */
	class Wcal_Guest_Ac {

		/**
		 * Default Constructor function for guest tracking.
		 */
		public function __construct() {
			add_action( 'woocommerce_after_checkout_billing_form', 'user_side_js' );
			add_action( 'wfacp_footer_before_print_scripts', 'user_side_js' ); // Compatibility with Aero Checkout.
			add_action( 'init', 'load_ac_ajax' );
			add_action( 'wp_ajax_nopriv_wcal_gdpr_refused', array( 'wcal_common', 'wcal_gdpr_refused' ) );
			add_filter( 'woocommerce_checkout_fields', 'guest_checkout_fields' );
			add_action( 'wp_footer', 'wcal_js_checkout_blocks', 10, 1 ); // Checkout blocks.
			add_action( 'woocommerce_blocks_loaded', 'wcal_load_guest_blocks_scripts' );
		}
	}

	/**
	 * It will add the ajax for capturing the guest record.
	 *
	 * @hook init
	 * @since 2.2
	 */
	function load_ac_ajax() {
		if ( ! is_user_logged_in() ) {
			add_action( 'wp_ajax_nopriv_save_data', 'save_data' );
		}
	}

	/**
	 * It will add the js for capturing the guest cart.
	 *
	 * @hook woocommerce_after_checkout_billing_form
	 * @since 2.2
	 */
	function user_side_js() {

		if ( ! is_user_logged_in() ) {
			wp_nonce_field( 'save_data', 'wcal_guest_capture_nonce' );

			wp_enqueue_script(
				'wcal_guest_capture',
				plugins_url( '../assets/js/wcal_guest_capture.min.js', __FILE__ ),
				'',
				WCAL_PLUGIN_VERSION,
				true
			);
			$enable_gdpr = get_option( 'wcal_enable_gdpr_consent', '' );
			$guest_msg   = get_option( 'wcal_guest_cart_capture_msg' );

			$session_gdpr = wcal_common::wcal_get_cart_session( 'wcal_cart_tracking_refused' );
			$show_gdpr    = isset( $session_gdpr ) && 'yes' == $session_gdpr ? false : true; // phpcs:ignore

			$vars = array();
			if ( 'on' === $enable_gdpr ) {
				$display_msg = isset( $guest_msg ) && '' !== $guest_msg ? $guest_msg : __( 'Saving your email and cart details helps us keep you up to date with this order.', 'woocommerce-abandoned-cart' );
				$display_msg = apply_filters( 'wcal_gdpr_email_consent_guest_users', $display_msg );

				$no_thanks = get_option( 'wcal_gdpr_allow_opt_out', '' );
				$no_thanks = apply_filters( 'wcal_gdpr_opt_out_text', $no_thanks );

				$opt_out_confirmation_msg = get_option( 'wcal_gdpr_opt_out_message', '' );
				$opt_out_confirmation_msg = apply_filters( 'wcal_gdpr_opt_out_confirmation_text', $opt_out_confirmation_msg );

				$vars = array(
					'_show_gdpr_message'        => $show_gdpr,
					'_gdpr_message'             => htmlspecialchars( $display_msg, ENT_QUOTES ),
					'_gdpr_nothanks_msg'        => htmlspecialchars( $no_thanks, ENT_QUOTES ),
					'_gdpr_after_no_thanks_msg' => htmlspecialchars( $opt_out_confirmation_msg, ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
					'enable_ca_tracking'        => true,
					'ajax_nonce'                => wp_create_nonce( 'wcal_gdpr_nonce' ),
				);
			}

			$vars['ajax_url'] = admin_url( 'admin-ajax.php' );

			wp_localize_script(
				'wcal_guest_capture',
				'wcal_guest_capture_params',
				$vars
			);
		}
	}

	/**
	 * Guest tracking for WC Checkout Blocks.
	 */
	function wcal_js_checkout_blocks() {

		$enable_gdpr = get_option( 'wcal_enable_gdpr_consent', '' );

		$session_gdpr  = wcal_common::wcal_get_cart_session( 'wcal_cart_tracking_refused' );
		$show_gdpr     = isset( $session_gdpr ) && 'yes' == $session_gdpr ? false : true; // phpcs:ignore
		$block_enabled = has_block( 'woocommerce/checkout', wc_get_page_id( 'checkout' ) );

		if ( ! is_user_logged_in() && is_checkout() && $show_gdpr && $block_enabled ) {

			$script_path       = '/build/wcal-blocks-guest-capture.js';
			$script_asset_path = WCAL_PLUGIN_URL . '/build/wcal-blocks-guest-capture.asset.php';
			$script_asset      = file_exists( $script_asset_path )
				? require $script_asset_path
				: array(
					'dependencies' => array(),
					'version'      => '1.0',
				);
			$script_url        = WCAL_PLUGIN_URL . '/' . $script_path;

			wp_register_script(
				'wcal-guest-user-blocks',
				$script_url,
				$script_asset['dependencies'],
				$script_asset['version'],
				true
			);

			$vars = array();

			$vars['ajax_url']        = admin_url( 'admin-ajax.php' );
			$vars['wcal_save_nonce'] = wp_create_nonce( 'save_data' );
			$vars['wcal_gdpr_nonce'] = wp_create_nonce( 'wcal_gdpr_nonce' );
			$vars['user_id']         = 0;
			if ( wcal_common::wcal_get_cart_session( 'user_id' ) && wcal_common::wcal_get_cart_session( 'user_id' ) >= 63000000 ) {
				$user_first_name    = wcal_common::wcal_get_cart_session( 'guest_first_name' ) ? wcal_common::wcal_get_cart_session( 'guest_first_name' ) : '';
				$user_last_name     = wcal_common::wcal_get_cart_session( 'guest_last_name' ) ? wcal_common::wcal_get_cart_session( 'guest_last_name' ) : '';
				$user_email         = wcal_common::wcal_get_cart_session( 'guest_email' ) ? wcal_common::wcal_get_cart_session( 'guest_email' ) : '';
				$vars['first_name'] = $user_first_name;
				$vars['last_name']  = $user_last_name;
				$vars['email']      = $user_email;
				$vars['user_id']    = wcal_common::wcal_get_cart_session( 'user_id' );
			}
			wp_localize_script( 'wcal-guest-user-blocks', 'wcal_guest_capture_blocks_params', $vars );
			wp_enqueue_script( 'wcal-guest-user-blocks' );
		}
	}

	/**
	 * It will add the guest users data in the database.
	 *
	 * @hook wp_ajax_nopriv_save_data
	 * @globals mixed $wpdb
	 * @globals mixed $woocommerce
	 * @since 2.2
	 */
	function save_data() {
		if ( ! is_user_logged_in() ) {

			if ( ! isset( $_POST['wcal_guest_capture_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wcal_guest_capture_nonce'] ) ), 'save_data' ) ) {
				die();
			}

			global $wpdb, $woocommerce;
			if ( isset( $_POST['billing_first_name'] ) && '' !== $_POST['billing_first_name'] ) {
				wcal_common::wcal_set_cart_session( 'billing_first_name', sanitize_text_field( wp_unslash( $_POST['billing_first_name'] ) ) );
			}
			if ( isset( $_POST['billing_last_name'] ) && '' !== $_POST['billing_last_name'] ) {
				wcal_common::wcal_set_cart_session( 'billing_last_name', sanitize_text_field( wp_unslash( $_POST['billing_last_name'] ) ) );
			}
			if ( isset( $_POST['billing_company'] ) && '' !== $_POST['billing_company'] ) {
				wcal_common::wcal_set_cart_session( 'billing_company', sanitize_text_field( wp_unslash( $_POST['billing_company'] ) ) );
			}
			if ( isset( $_POST['billing_address_1'] ) && '' !== $_POST['billing_address_1'] ) {
				wcal_common::wcal_set_cart_session( 'billing_address_1', sanitize_text_field( wp_unslash( $_POST['billing_address_1'] ) ) );
			}
			if ( isset( $_POST['billing_address_2'] ) && '' !== $_POST['billing_address_2'] ) {
				wcal_common::wcal_set_cart_session( 'billing_address_2', sanitize_text_field( wp_unslash( $_POST['billing_address_2'] ) ) );
			}
			if ( isset( $_POST['billing_city'] ) && '' !== $_POST['billing_city'] ) {
				wcal_common::wcal_set_cart_session( 'billing_city', sanitize_text_field( wp_unslash( $_POST['billing_city'] ) ) );
			}
			if ( isset( $_POST['billing_state'] ) && '' !== $_POST['billing_state'] ) {
				wcal_common::wcal_set_cart_session( 'billing_state', sanitize_text_field( wp_unslash( $_POST['billing_state'] ) ) );
			}
			if ( isset( $_POST['billing_postcode'] ) && '' !== $_POST['billing_postcode'] ) {
				wcal_common::wcal_set_cart_session( 'billing_postcode', sanitize_text_field( wp_unslash( $_POST['billing_postcode'] ) ) );
			}
			if ( isset( $_POST['billing_country'] ) && '' !== $_POST['billing_country'] ) {
				wcal_common::wcal_set_cart_session( 'billing_country', sanitize_text_field( wp_unslash( $_POST['billing_country'] ) ) );
			}
			if ( isset( $_POST['billing_email'] ) && '' !== $_POST['billing_email'] ) {
				wcal_common::wcal_set_cart_session( 'billing_email', sanitize_text_field( wp_unslash( $_POST['billing_email'] ) ) );
			}
			if ( isset( $_POST['billing_phone'] ) && '' !== $_POST['billing_phone'] ) {
				wcal_common::wcal_set_cart_session( 'billing_phone', sanitize_text_field( wp_unslash( $_POST['billing_phone'] ) ) );
			}
			if ( isset( $_POST['order_notes'] ) && '' !== $_POST['order_notes'] ) {
				wcal_common::wcal_set_cart_session( 'order_notes', sanitize_text_field( wp_unslash( $_POST['order_notes'] ) ) );
			}
			if ( isset( $_POST['ship_to_billing'] ) && '' !== $_POST['ship_to_billing'] ) {
				wcal_common::wcal_set_cart_session( 'ship_to_billing', sanitize_text_field( wp_unslash( $_POST['ship_to_billing'] ) ) );
			}
			if ( isset( $_POST['shipping_first_name'] ) && '' !== $_POST['shipping_first_name'] ) {
				wcal_common::wcal_set_cart_session( 'shipping_first_name', sanitize_text_field( wp_unslash( $_POST['shipping_first_name'] ) ) );
			}
			if ( isset( $_POST['shipping_last_name'] ) && '' !== $_POST['shipping_last_name'] ) {
				wcal_common::wcal_set_cart_session( 'shipping_last_name', sanitize_text_field( wp_unslash( $_POST['shipping_last_name'] ) ) );
			}
			if ( isset( $_POST['shipping_company'] ) && '' !== $_POST['shipping_company'] ) {
				wcal_common::wcal_set_cart_session( 'shipping_company', sanitize_text_field( wp_unslash( $_POST['shipping_company'] ) ) );
			}
			if ( isset( $_POST['shipping_address_1'] ) && '' !== $_POST['shipping_address_1'] ) {
				wcal_common::wcal_set_cart_session( 'shipping_address_1', sanitize_text_field( wp_unslash( $_POST['shipping_address_1'] ) ) );
			}
			if ( isset( $_POST['shipping_address_2'] ) && '' !== $_POST['shipping_address_2'] ) {
				wcal_common::wcal_set_cart_session( 'shipping_address_2', sanitize_text_field( wp_unslash( $_POST['shipping_address_2'] ) ) );
			}
			if ( isset( $_POST['shipping_city'] ) && '' !== $_POST['shipping_city'] ) {
				wcal_common::wcal_set_cart_session( 'shipping_city', sanitize_text_field( wp_unslash( $_POST['shipping_city'] ) ) );
			}
			if ( isset( $_POST['shipping_state'] ) && '' !== $_POST['shipping_state'] ) {
				wcal_common::wcal_set_cart_session( 'shipping_state', sanitize_text_field( wp_unslash( $_POST['shipping_state'] ) ) );
			}
			if ( isset( $_POST['shipping_postcode'] ) && '' !== $_POST['shipping_postcode'] ) {
				wcal_common::wcal_set_cart_session( 'shipping_postcode', sanitize_text_field( wp_unslash( $_POST['shipping_postcode'] ) ) );
			}
			if ( isset( $_POST['shipping_country'] ) && '' !== $_POST['shipping_country'] ) {
				wcal_common::wcal_set_cart_session( 'shipping_country', sanitize_text_field( wp_unslash( $_POST['shipping_country'] ) ) );
			}
			if ( ! wcal_common::wcal_get_cart_session( 'email_sent_id' ) ) {
				$guest_session_key = wcal_get_guest_session_key();
				// If a record is present in the guest cart history table for the same email id, then delete the previous records.
				$results_guest = $wpdb->get_row( // phpcs:ignore
					$wpdb->prepare(
						'SELECT id, user_id, abandoned_cart_info FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` WHERE session_id = %s',
						$guest_session_key
					)
				);
			} else {
				$abandoned_cart_id = wcal_common::wcal_get_cart_session( 'abandoned_cart_id_lite' );
				$results_guest = $wpdb->get_row( // phpcs:ignore
					$wpdb->prepare(
						'SELECT id, user_id, abandoned_cart_info, session_id FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` WHERE id = %d',
						(int) $abandoned_cart_id
					)
				);
				$guest_session_key = $results_guest->session_id ? $results_guest->session_id : '';
			}

			$user_id           = 0;
			$abandoned_cart_id = 0;
			if ( $results_guest ) {
				if ( $results_guest->user_id > 0 ) {
					$user_id = $results_guest->user_id;
				}
				$abandoned_cart_id = $results_guest->id;
			}
			// Insert record in guest table.
			$billing_first_name = wcal_common::wcal_get_cart_session( 'billing_first_name' );

			$billing_last_name         = wcal_common::wcal_get_cart_session( 'billing_last_name' );
			$billing_email             = wcal_common::wcal_get_cart_session( 'billing_email' );
			$billing_email_restriction = apply_filters( 'wcal_abandoned_cart_user_email', false, $billing_email );

			$shipping_zipcode = '';
			$billing_zipcode  = '';
			if ( ! $billing_email_restriction ) {
				if ( '' != wcal_common::wcal_get_cart_session( 'shipping_postcode' ) ) { // phpcs:ignore
					$shipping_zipcode = wcal_common::wcal_get_cart_session( 'shipping_postcode' );
				} elseif ( '' != wcal_common::wcal_get_cart_session( 'billing_postcode' ) ) { // phpcs:ignore
					$billing_zipcode  = wcal_common::wcal_get_cart_session( 'billing_postcode' );
					$shipping_zipcode = $billing_zipcode;
				}
				$shipping_charges = $woocommerce->cart->shipping_total;
				if ( 0 === $user_id ) {
					$wpdb->query( // phpcs:ignore
						$wpdb->prepare(
							'INSERT INTO `' . $wpdb->prefix . 'ac_guest_abandoned_cart_history_lite`( billing_first_name, billing_last_name, email_id, billing_zipcode, shipping_zipcode, shipping_charges ) VALUES ( %s, %s, %s, %s, %s, %s )',
							$billing_first_name,
							$billing_last_name,
							$billing_email,
							$billing_zipcode,
							$shipping_zipcode,
							$shipping_charges
						)
					);
					// Insert record in abandoned cart table for the guest user.
					$user_id = $wpdb->insert_id;
				} else {
					$wpdb->update( // phpcs:ignore
						$wpdb->prefix . 'ac_guest_abandoned_cart_history_lite',
						array(
							'billing_first_name' => $billing_first_name,
							'billing_last_name'  => $billing_last_name,
							'email_id'           => $billing_email,
							'billing_zipcode'    => $billing_zipcode,
							'shipping_zipcode'   => $shipping_zipcode,
							'shipping_charges'   => $shipping_charges,
						),
						array(
							'id' => $user_id,
						)
					);
				}

				wcal_common::wcal_set_cart_session( 'user_id', $user_id );
				$current_time      = current_time( 'timestamp' ); // phpcs:ignore
				$cut_off_time      = get_option( 'ac_cart_abandoned_time' );
				$cart_cut_off_time = $cut_off_time * 60;
				$compare_time      = $current_time - $cart_cut_off_time;

				$cart = array();

				if ( function_exists( 'WC' ) ) {
					$cart['cart'] = WC()->session->cart;
				} else {
					$cart['cart'] = $woocommerce->session->cart;
				}
				$cart_info = wp_json_encode( $cart );
				if ( 0 === $abandoned_cart_id ) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery
					$wpdb->query(
						$wpdb->prepare(
							'INSERT INTO `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite`( user_id, abandoned_cart_info, abandoned_cart_time, cart_ignored, recovered_cart, user_type, session_id ) VALUES ( %s, %s, %s, %s, %s, %s, %s )',
							$user_id,
							$cart_info,
							$current_time,
							0,
							0,
							'GUEST',
							$guest_session_key
						)
					);

					$abandoned_cart_id = $wpdb->insert_id;
					wcal_common::wcal_set_cart_session( 'abandoned_cart_id_lite', $abandoned_cart_id );
					wcal_common::wcal_add_checkout_link( $abandoned_cart_id );
					wcal_common::wcal_run_webhook_after_cutoff( $abandoned_cart_id );
					wcal_update_guest_persistent_cart( $user_id, $cart_info );
				} else {
					$wpdb->query( // phpcS:ignore
						$wpdb->prepare(
							'UPDATE `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` SET user_id = %s, abandoned_cart_info = %s, abandoned_cart_time = %s WHERE session_id = %s AND cart_ignored = %s',
							$user_id,
							$cart_info,
							$current_time,
							$guest_session_key,
							0
						)
					);
					$get_abandoned_record = $wpdb->get_results( // phpcS:ignore
						$wpdb->prepare(
							'SELECT * FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` WHERE user_id = %d AND cart_ignored = %s AND session_id = %s',
							$user_id,
							0,
							$guest_session_key
						)
					);

					if ( count( $get_abandoned_record ) > 0 ) {
						$abandoned_cart_id = $get_abandoned_record[0]->id;
						wcal_common::wcal_set_cart_session( 'abandoned_cart_id_lite', $abandoned_cart_id );
						wcal_common::wcal_add_checkout_link( $abandoned_cart_id );
						wcal_common::wcal_run_webhook_after_cutoff( $abandoned_cart_id );
					}
					wcal_update_guest_persistent_cart( $user_id, $cart_info );
				}
			}
		}
	}

	/**
	 * Update guest cart similar to WC.
	 *
	 * @param int    $user_id - Guest User ID.
	 * @param string $cart_info - Abandoned Cart Info.
	 */
	function wcal_update_guest_persistent_cart( $user_id, $cart_info ) {
		global $wpdb;

		if ( $user_id >= 63000000 && '' !== $cart_info ) {

			if ( is_multisite() ) {
				// get main site's table prefix.
				$main_prefix = $wpdb->get_blog_prefix( 1 );

				$get_cart = $wpdb->get_results( $wpdb->prepare( 'SELECT umeta_id FROM `' . $main_prefix . "usermeta` WHERE user_id = %d AND meta_key = '_woocommerce_persistent_cart' ORDER BY umeta_id DESC LIMIT 1", $user_id ) ); // phpcs:ignore
				if ( isset( $get_cart ) && is_array( $get_cart ) && 1 === count( $get_cart ) ) {
					$wpdb->update( // phpcs:ignore
						$main_prefix . 'usermeta',
						array(
							'meta_value' => $cart_info, // phpcs:ignore
						),
						array(
							'user_id'  => $user_id,
							'meta_key' => '_woocommerce_persistent_cart', // phpcs:ignore
						)
					);
				} else {
					$wpdb->query( // phpcs:ignore
						$wpdb->prepare(
							'INSERT INTO `' . $main_prefix . 'usermeta`( user_id, meta_key, meta_value ) VALUES ( %s, %s, %s )', // phpcs:ignore
							$user_id,
							'_woocommerce_persistent_cart',
							$cart_info
						)
					);
				}
			} else {
				$get_cart = $wpdb->get_results( $wpdb->prepare( "SELECT umeta_id FROM `" . $wpdb->prefix . "usermeta` WHERE user_id = %d AND meta_key = '_woocommerce_persistent_cart' ORDER BY umeta_id DESC LIMIT 1", $user_id ) ); // phpcs:ignore
				if ( isset( $get_cart ) && is_array( $get_cart ) && 1 === count( $get_cart ) ) {
					$wpdb->update( // phpcs:ignore
						$wpdb->prefix . 'usermeta',
						array(
							'meta_value' => $cart_info, // phpcs:ignore
						),
						array(
							'user_id'  => $user_id,
							'meta_key' => '_woocommerce_persistent_cart', // phpcs:ignore
						)
					);
				} else {
					$wpdb->query( // phpcs:ignore
						$wpdb->prepare(
							'INSERT INTO `' . $wpdb->prefix . 'usermeta`( user_id, meta_key, meta_value ) VALUES ( %s, %s, %s )',
							$user_id,
							'_woocommerce_persistent_cart',
							$cart_info
						)
					);
				}
			}
		}

	}

	/**
	 * It will populate the data on the chekout field if user comes from the abandoned cart reminder emails.
	 *
	 * @hook woocommerce_checkout_fields
	 * @param array $fields All fields of checkout page.
	 * @return array $fields
	 * @since 2.2
	 */
	function guest_checkout_fields( $fields ) {
		if ( '' != wcal_common::wcal_get_cart_session( 'guest_first_name' ) ) { // phpcs:ignore
			$_POST['billing_first_name'] = wcal_common::wcal_get_cart_session( 'guest_first_name' );
		}
		if ( '' != wcal_common::wcal_get_cart_session( 'guest_last_name' ) ) { // phpcs:ignore
			$_POST['billing_last_name'] = wcal_common::wcal_get_cart_session( 'guest_last_name' );
		}
		if ( '' != wcal_common::wcal_get_cart_session( 'guest_email' ) ) { // phpcs:ignore
			$_POST['billing_email'] = wcal_common::wcal_get_cart_session( 'guest_email' );
		}
		if ( '' != wcal_common::wcal_get_cart_session( 'guest_phone' ) ) { // phpcs:ignore
			$_POST['billing_phone'] = wcal_common::wcal_get_cart_session( 'guest_phone' );
		}
		return $fields;
	}

	/**
	 * Load Blocks JS files.
	 */
	function wcal_load_guest_blocks_scripts() {

		// GDPR Notice below the email field.
		if ( 'on' === get_option( 'wcal_enable_gdpr_consent' ) ) {

			require_once WCAL_PLUGIN_PATH . '/includes/blocks/class-wcal-gdpr-emails-blocks-integration.php';
			add_action(
				'woocommerce_blocks_checkout_block_registration',
				function ( $integration_registry ) {
					$integration_registry->register( new Wcal_GDPR_Emails_Blocks_Integration() );
				}
			);
		}
	}

	/**
	 * Return WC Session key.
	 */
	function wcal_get_guest_session_key() {
		$wcal_get_cookie = WC()->session->get_session_cookie();
		if ( $wcal_get_cookie ) {
			$wcal_session_id = $wcal_get_cookie[0];
		} else {
			$wcal_session_id = 0;
		}
		return $wcal_session_id;
	}
}
$woocommerce_guest_ac = new Wcal_Guest_Ac();
