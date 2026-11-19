<?php
/**
 * REST endpoints for the Settings screen.
 *
 * Mirrors the routes, envelope and request handling of the Pro plugin's
 * WCAP_REST_Settings_Controller, using the option keys stored by this plugin.
 * Values are stored exactly as a save through options.php stores them.
 *
 * @package Abandoned-Cart-Lite-for-WooCommerce/Admin/API
 * @since 6.10.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WCAL_REST_Settings_Controller class.
 */
class WCAL_REST_Settings_Controller {

	/**
	 * REST namespace.
	 *
	 * @var string
	 */
	const REST_NAMESPACE = 'wcal/v1';

	/**
	 * Option schema for every settings panel.
	 *
	 * Each option can define:
	 *   'type'              => 'string'|'boolean'|'countries'|'email'
	 *   'label'             => field name used in validation errors.
	 *   'default'           => value used when the option doesn't exist.
	 *   'sanitize_callback' => same callback the option is registered with in register_setting().
	 *   'empty_as_default'  => read a stored empty value as the default.
	 *   'non_empty_is_on'   => read any non-empty stored value as enabled.
	 *
	 * @return array
	 */
	public static function panels() {
		return array(
			'email'          => array(
				'wcal_enable_cart_emails'         => array( 'type' => 'boolean' ),
				'ac_lite_email_admin_on_recovery' => array( 'type' => 'boolean' ),
				'wcap_email_admin_on_abandonment' => array( 'type' => 'boolean' ),
			),
			'cart'           => array(
				'ac_lite_cart_abandoned_time'             => array(
					'type'              => 'string',
					'sanitize_callback' => array( 'woocommerce_abandon_cart_lite', 'ac_lite_cart_time_validation' ),
				),
				'ac_lite_track_guest_cart_from_cart_page' => array( 'type' => 'boolean' ),
			),
			'plugin'         => array(
				'ac_lite_delete_abandoned_order_days' => array(
					'type'              => 'string',
					'default'           => '365',
					'empty_as_default'  => true,
					'sanitize_callback' => array( 'woocommerce_abandon_cart_lite', 'wcal_delete_days_validation' ),
				),
			),
			'gdpr'           => array(
				'wcal_enable_gdpr_consent'     => array(
					'type'            => 'boolean',
					'non_empty_is_on' => true,
				),
				// The logged-in message is printed with wp_kses_post(), so the same HTML is allowed.
				'wcal_guest_cart_capture_msg'  => array(
					'type'              => 'string',
					'sanitize_callback' => 'wp_kses_post',
				),
				'wcal_logged_cart_capture_msg' => array(
					'type'              => 'string',
					'sanitize_callback' => 'wp_kses_post',
				),
				'wcal_gdpr_allow_opt_out'      => array(
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'wcal_gdpr_opt_out_message'    => array(
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
			'coupon'         => array(
				'wcal_delete_coupon_data' => array( 'type' => 'boolean' ),
			),
			'exclusions'     => array(
				// One entry per line or comma separated, so the line breaks are kept.
				'wcap_restrict_ip_address'     => array(
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_textarea_field',
				),
				'wcap_restrict_email_address'  => array(
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_textarea_field',
				),
				'wcap_restrict_domain_address' => array(
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_textarea_field',
				),
				'wcap_restrict_countries'      => array( 'type' => 'countries' ),
			),
			'recovery-email' => array(
				// Name and addresses go into the mail headers: no line breaks or tags.
				'wcal_from_name'        => array(
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'wcal_from_email'       => array(
					'type'  => 'email',
					'label' => __( '"From" Address', 'woocommerce-abandoned-cart' ),
				),
				'wcal_reply_email'      => array(
					'type'  => 'email',
					'label' => __( 'Send Reply Emails to', 'woocommerce-abandoned-cart' ),
				),
				'wcal_add_utm_to_links' => array(
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'wcal_auto_login_users' => array( 'type' => 'boolean' ),
			),
		);
	}

	/**
	 * Register the GET and POST routes for all the panels.
	 *
	 * @return void
	 */
	public static function register_routes() {

		foreach ( array_keys( self::panels() ) as $panel ) {
			register_rest_route(
				self::REST_NAMESPACE,
				'/settings/general/' . $panel,
				array(
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => function () use ( $panel ) {
							return self::envelope( self::read_options( $panel ) );
						},
						'permission_callback' => array( __CLASS__, 'get_read_permission' ),
						'args'                => array(),
					),
					array(
						'methods'             => WP_REST_Server::CREATABLE,
						'callback'            => function ( $request ) use ( $panel ) {
							return self::update_item( $panel, $request );
						},
						'permission_callback' => array( __CLASS__, 'get_save_permission' ),
						'args'                => array(),
					),
				)
			);
		}

		register_rest_route(
			self::REST_NAMESPACE,
			'/settings/general/all',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( __CLASS__, 'get_all' ),
					'permission_callback' => array( __CLASS__, 'get_read_permission' ),
					'args'                => array(),
				),
			)
		);
	}

	/**
	 * The menu is available to shop managers, so they can view the settings.
	 *
	 * @return bool|WP_Error
	 */
	public static function get_read_permission() {
		return current_user_can( 'manage_woocommerce' ) ? true : self::forbidden();
	}

	/**
	 * Same capability that options.php requires to save these settings.
	 *
	 * @return bool|WP_Error
	 */
	public static function get_save_permission() {
		return current_user_can( 'manage_options' ) ? true : self::forbidden();
	}

	/**
	 * Permission error.
	 *
	 * @return WP_Error
	 */
	protected static function forbidden() {
		return new WP_Error(
			'wcal_rest_forbidden',
			__( 'You do not have permission to access this resource.', 'woocommerce-abandoned-cart' ),
			array( 'status' => 403 )
		);
	}

	/**
	 * GET /settings/general/all - read all the panels in one request.
	 *
	 * @return WP_REST_Response
	 */
	public static function get_all() {
		$data = array();
		foreach ( array_keys( self::panels() ) as $panel ) {
			$data[ $panel ] = self::read_options( $panel );
		}
		return self::envelope( $data );
	}

	/**
	 * POST /settings/general/{panel} - update the options of a panel.
	 *
	 * Every value is validated before anything is written. If a validator reports
	 * an error, nothing in the panel is saved: the error is returned to the client
	 * along with the values still stored, so the form goes back to them.
	 *
	 * @param string          $panel   Panel name.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	protected static function update_item( $panel, $request ) {

		$body = $request->get_json_params();

		if ( empty( $body ) || ! is_array( $body ) ) {
			return self::invalid( 'body', __( 'Request body must be a non-empty JSON object.', 'woocommerce-abandoned-cart' ) );
		}

		$schema = self::panels()[ $panel ];

		// Settings errors API isn't loaded in REST requests.
		require_once ABSPATH . 'wp-admin/includes/template.php';

		// Validate and sanitize everything first, so that a bad value never reaches
		// the database and a bad request doesn't save part of the panel.
		$values = array();
		$errors = array();

		foreach ( $body as $key => $value ) {
			if ( ! array_key_exists( $key, $schema ) ) {
				continue;
			}

			$field         = $schema[ $key ];
			$accepts_array = 'countries' === $field['type'] && is_array( $value );
			if ( ! is_scalar( $value ) && ! $accepts_array ) {
				/* translators: %s: Option name */
				return self::invalid( $key, sprintf( __( 'Invalid value for %s.', 'woocommerce-abandoned-cart' ), $key ) );
			}

			if ( 'boolean' === $field['type'] ) {
				$values[ $key ] = self::normalize_bool_for_db( $value );
			} elseif ( 'countries' === $field['type'] ) {
				$countries      = is_array( $value ) ? $value : explode( ',', (string) $value );
				$values[ $key ] = woocommerce_abandon_cart_lite::sanitize_restrict_countries( $countries );
			} elseif ( 'email' === $field['type'] ) {
				$email = sanitize_email( (string) $value );
				if ( is_email( $email ) ) {
					$values[ $key ] = $email;
				} else {
					/* translators: %s: Field name */
					$errors[ $key ] = sprintf( __( 'Please enter a valid email address for %s.', 'woocommerce-abandoned-cart' ), isset( $field['label'] ) ? $field['label'] : $key );
				}
			} elseif ( isset( $field['sanitize_callback'] ) ) {
				$values[ $key ] = call_user_func( $field['sanitize_callback'], (string) $value );
				foreach ( get_settings_errors( $key ) as $error ) {
					$errors[ $key ] = $error['message'];
				}
			} else {
				$values[ $key ] = (string) $value;
			}
		}

		if ( empty( $errors ) ) {
			// Added only in is_admin() requests by the main class.
			add_filter( 'pre_update_option_wcal_auto_login_users', array( 'woocommerce_abandon_cart_lite', 'wcal_update_admin_notice_value' ), 10, 2 );

			foreach ( $values as $key => $value ) {
				update_option( $key, $value );
			}

			if ( array_key_exists( 'wcal_delete_coupon_data', $values ) ) {
				woocommerce_abandon_cart_lite::wcal_sync_coupon_schedule();
			}
		}

		if ( ! empty( $errors ) ) {
			return new WP_Error(
				'wcal_invalid_' . sanitize_key( key( $errors ) ),
				implode( ' ', $errors ),
				array(
					'status' => 400,
					'saved'  => self::read_options( $panel ),
				)
			);
		}

		return self::envelope( self::read_options( $panel ) );
	}

	/**
	 * Read the options of a panel and cast them for the client.
	 *
	 * @param string $panel Panel name.
	 * @return array
	 */
	protected static function read_options( $panel ) {

		$data = array();

		foreach ( self::panels()[ $panel ] as $key => $field ) {
			$default = isset( $field['default'] ) ? $field['default'] : '';
			$raw     = get_option( $key, $default );

			if ( 'boolean' === $field['type'] ) {
				$data[ $key ] = empty( $field['non_empty_is_on'] ) ? 'on' === $raw : '' !== $raw;
				continue;
			}

			$value = is_scalar( $raw ) ? (string) $raw : '';
			if ( '' === $value && ! empty( $field['empty_as_default'] ) ) {
				$value = $default;
			}
			$data[ $key ] = $value;
		}

		return $data;
	}

	/**
	 * Normalise a truthy/falsy request value to 'on' or '' for DB storage.
	 *
	 * @param mixed $value Incoming value.
	 * @return string 'on' or ''.
	 */
	protected static function normalize_bool_for_db( $value ) {

		if ( is_bool( $value ) ) {
			return $value ? 'on' : '';
		}

		if ( is_int( $value ) || is_float( $value ) ) {
			return $value ? 'on' : '';
		}

		$str = strtolower( trim( (string) $value ) );

		return in_array( $str, array( 'on', 'yes', '1', 'true' ), true ) ? 'on' : '';
	}

	/**
	 * Response envelope.
	 *
	 * @param mixed $data Response data.
	 * @return WP_REST_Response
	 */
	protected static function envelope( $data ) {
		return rest_ensure_response( array( 'data' => $data ) );
	}

	/**
	 * Invalid request error.
	 *
	 * @param string $param   Parameter name.
	 * @param string $message Error message.
	 * @return WP_Error
	 */
	protected static function invalid( $param, $message ) {
		return new WP_Error( 'wcal_invalid_' . sanitize_key( $param ), $message, array( 'status' => 400 ) );
	}
}
