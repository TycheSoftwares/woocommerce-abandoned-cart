<?php

class Wcal_ts_pro_notices {

	/**
	 * Plugin's Name
	 *
	 * @access public
	 * @since 3.5
	 */
	public static $plugin_name = '';

	/**
	 * Plugin's unique prefix
	 *
	 * @access public
	 * @since 3.5
	 */

	public static $plugin_prefix = '';

	/**
	 * Pro plugin's unique prefix
	 *
	 * @access public
	 * @since 3.5
	 */

	public static $pro_plugin_prefix = '';

	/**
	 * @var array Collection of all messages.
	 * @access public
	 */
	public static $ts_pro_notices = array();

	/**
	 * @var string file name
	 * @access public
	 */
	public static $ts_file_name = '';

	/**
	 * @var string Pro version file name
	 * @access public
	 */
	public static $ts_pro_file_name = '';

	/**
	 * Default Constructor
	 *
	 * @since 3.5
	 */
	public function __construct( $ts_plugin_name = '', $ts_plugin_prefix = '', $ts_pro_plugin_prefix = '', $ts_notices = array(), $ts_file = '', $ts_pro_file = '' ) {
		self::$plugin_name       = $ts_plugin_name;
		self::$plugin_prefix     = $ts_plugin_prefix;
		self::$pro_plugin_prefix = $ts_pro_plugin_prefix;
		self::$ts_pro_notices    = $ts_notices;
		self::$ts_file_name      = $ts_file;
		self::$ts_pro_file_name  = $ts_pro_file;

		// Initialize settings
		register_activation_hook( __FILE__, array( __CLASS__, 'ts_activate_time' ) );

		// Add pro notices
		add_action( 'admin_notices', array( __CLASS__, 'ts_notices_of_pro' ) );
		add_action( 'admin_init', array( __CLASS__, 'ts_ignore_pro_notices' ) );

		add_action( self::$plugin_prefix . '_activate', array( __CLASS__, 'ts_activate_time' ) );
	}

	/**
	 * It will add the activation time on activation and plugin prefix activate hook.
	 */
	public static function ts_activate_time() {

		if ( ! get_option( self::$plugin_prefix . '_activate_time' ) ) {
			add_option( self::$plugin_prefix . '_activate_time', current_time( 'timestamp' ) );
		}
	}

	/**
	 * It will display notices for the pro version of the plugin.
	 */
	public static function ts_notices_of_pro() {

		global $current_screen;
		$current_screen = get_current_screen();
		if ( ( method_exists( $current_screen, 'is_block_editor' ) && $current_screen->is_block_editor() )
			|| ( function_exists( 'is_gutenberg_page' ) && is_gutenberg_page() ) ) {
			return;
		}

		$activate_time       = get_option( self::$plugin_prefix . '_activate_time' );
		$sixty_days          = strtotime( '+60 Days', $activate_time );
		$current_time        = current_time( 'timestamp' );
		$add_query_arguments = '';
		$message             = '';
		$user_id             = get_current_user_id();
		$class               = 'updated notice-info point-notice one';
		$style               = 'position:relative';

		if ( ! is_plugin_active( self::$ts_pro_file_name ) &&
			( false === $activate_time || ( $activate_time > 0 && $current_time >= $sixty_days ) ) ) {

			if ( ! get_user_meta( $user_id, self::$plugin_prefix . '_first_notice_ignore' ) ) {

				$add_query_arguments = add_query_arg( self::$plugin_prefix . '_first_notice_ignore', '0' );
				$cancel_button       = '<a href="' . $add_query_arguments . '" class="dashicons dashicons-dismiss dashicons-dismiss-icon" style="position: absolute; top: 8px; right: 8px; color: #222; opacity: 0.4; text-decoration: none !important;"></a>';

				printf( '<div class="%1$s" style="%2$s"><p>%3$s %4$s</p></div>', $class, $style, self::$ts_pro_notices[1], $cancel_button );//phpcs:disable
			}

			if ( get_user_meta( $user_id, self::$plugin_prefix . '_first_notice_ignore' ) &&
				! get_user_meta( $user_id, self::$plugin_prefix . '_second_notice_ignore' )
				 ) {

				$first_ignore_time = get_user_meta( $user_id, self::$plugin_prefix . '_first_notice_ignore_time' );
				$fifteen_days      = strtotime( '+15 Days', $first_ignore_time[0] );

				// $fifteen_days = strtotime( '+2 Minutes', $first_ignore_time[0] );

				if ( $current_time > $fifteen_days ) {

					$add_query_arguments = add_query_arg( self::$plugin_prefix . '_second_notice_ignore', '0' );
					$cancel_button       = '<a href="' . $add_query_arguments . '" class="dashicons dashicons-dismiss dashicons-dismiss-icon" style="position: absolute; top: 8px; right: 8px; color: #222; opacity: 0.4; text-decoration: none !important;"></a>';

					printf( '<div class="%1$s" style="%2$s"><p>%3$s %4$s</p></div>', $class, $style, self::$ts_pro_notices[2], $cancel_button );
				}
			}

			if ( get_user_meta( $user_id, self::$plugin_prefix . '_first_notice_ignore' ) &&
				get_user_meta( $user_id, self::$plugin_prefix . '_second_notice_ignore' ) &&
				! get_user_meta( $user_id, self::$plugin_prefix . '_third_notice_ignore' )
			   ) {

				$second_ignore_time = get_user_meta( $user_id, self::$plugin_prefix . '_second_notice_ignore_time' );
				$ts_fifteen_days    = strtotime( '+15 Days', $second_ignore_time[0] );

				if ( $current_time > $ts_fifteen_days ) {

					$add_query_arguments = add_query_arg( self::$plugin_prefix . '_third_notice_ignore', '0' );
					$cancel_button       = '<a href="' . $add_query_arguments . '" class="dashicons dashicons-dismiss dashicons-dismiss-icon" style="position: absolute; top: 8px; right: 8px; color: #222; opacity: 0.4; text-decoration: none !important;"></a>';

					printf( '<div class="%1$s" style="%2$s"><p>%3$s %4$s</p></div>', $class, $style, self::$ts_pro_notices[3], $cancel_button );
				}
			}

			if ( get_user_meta( $user_id, self::$plugin_prefix . '_first_notice_ignore' ) &&
				get_user_meta( $user_id, self::$plugin_prefix . '_second_notice_ignore' ) &&
				get_user_meta( $user_id, self::$plugin_prefix . '_third_notice_ignore' ) &&
				! get_user_meta( $user_id, self::$plugin_prefix . '_fourth_notice_ignore' )
			   ) {

				$third_ignore_time = get_user_meta( $user_id, self::$plugin_prefix . '_third_notice_ignore_time' );
				$ts_fifteen_days   = strtotime( '+15 Days', $third_ignore_time[0] );

				if ( $current_time > $ts_fifteen_days ) {

					$add_query_arguments = add_query_arg( self::$plugin_prefix . '_fourth_notice_ignore', '0' );
					$cancel_button       = '<a href="' . $add_query_arguments . '" class="dashicons dashicons-dismiss dashicons-dismiss-icon" style="position: absolute; top: 8px; right: 8px; color: #222; opacity: 0.4; text-decoration: none !important;"></a>';

					printf( '<div class="%1$s" style="%2$s"><p>%3$s %4$s</p></div>', $class, $style, self::$ts_pro_notices[4], $cancel_button );
				}
			}

			if ( get_user_meta( $user_id, self::$plugin_prefix . '_first_notice_ignore' ) &&
				 get_user_meta( $user_id, self::$plugin_prefix . '_second_notice_ignore' ) &&
				 get_user_meta( $user_id, self::$plugin_prefix . '_third_notice_ignore' ) &&
				 get_user_meta( $user_id, self::$plugin_prefix . '_fourth_notice_ignore' ) &&
				! get_user_meta( $user_id, self::$plugin_prefix . '_fifth_notice_ignore' )
			   ) {

				$fourth_ignore_time = get_user_meta( $user_id, self::$plugin_prefix . '_fourth_notice_ignore_time' );
				$ts_fifteen_days    = strtotime( '+15 Days', $fourth_ignore_time[0] );

				if ( $current_time > $ts_fifteen_days ) {

					$add_query_arguments = add_query_arg( self::$plugin_prefix . '_fifth_notice_ignore', '0' );
					$cancel_button       = '<a href="' . $add_query_arguments . '" class="dashicons dashicons-dismiss dashicons-dismiss-icon" style="position: absolute; top: 8px; right: 8px; color: #222; opacity: 0.4; text-decoration: none !important;"></a>';

					printf( '<div class="%1$s" style="%2$s"><p>%3$s %4$s</p></div>', $class, $style, self::$ts_pro_notices[5], $cancel_button );
				}
			}

			/**
			 * Display Other plugin notices.
			 */

			if ( get_user_meta( $user_id, self::$plugin_prefix . '_first_notice_ignore' ) &&
				 get_user_meta( $user_id, self::$plugin_prefix . '_second_notice_ignore' ) &&
				 get_user_meta( $user_id, self::$plugin_prefix . '_third_notice_ignore' ) &&
				 get_user_meta( $user_id, self::$plugin_prefix . '_fourth_notice_ignore' ) &&
				 get_user_meta( $user_id, self::$plugin_prefix . '_fifth_notice_ignore' )
			) {
				$fifth_ignore_time = get_user_meta( $user_id, self::$plugin_prefix . '_fifth_notice_ignore_time' );

				self::ts_display_other_pro_plugin_notices( $current_time, $activate_time, $fifth_ignore_time [0] );
			}
		}

		$seven_days = strtotime( '+7 Days', $activate_time );
		if ( is_plugin_active( self::$ts_pro_file_name ) &&
		( false === $activate_time || ( $activate_time > 0 && $current_time >= $seven_days ) ) ) {

			self::ts_display_other_pro_plugin_notices( $current_time, $activate_time );
		}
	}

	/**
	 * It will display the all other pro plugin notices
	 */
	public static function ts_display_other_pro_plugin_notices( $current_time, $activate_time, $ts_consider_time = '' ) {
		$user_id = get_current_user_id();
		$class   = 'updated notice-info point-notice';
		$style   = 'position:relative';

		if ( ! get_user_meta( $user_id, self::$plugin_prefix . '_sixth_notice_ignore' )
			) {

			if ( '' != $ts_consider_time ) {
				/**
				 * This is fifth ignore notice time plus 7 days
				 */
				$ts_consider_time = strtotime( '+7 Days', $ts_consider_time );
			}
			if ( $current_time > $ts_consider_time ) {
				$add_query_arguments = add_query_arg( self::$plugin_prefix . '_sixth_notice_ignore', '0' );
				$cancel_button       = '<a href="' . $add_query_arguments . '" class="dashicons dashicons-dismiss dashicons-dismiss-icon" style="position: absolute; top: 8px; right: 8px; color: #222; opacity: 0.4; text-decoration: none !important;"></a>';

				printf( '<div class="%1$s" style="%2$s"><p>%3$s %4$s</p></div>', $class, $style, self::$ts_pro_notices[6], $cancel_button );
			}
		}

		if ( get_user_meta( $user_id, self::$plugin_prefix . '_sixth_notice_ignore' ) &&
			! get_user_meta( $user_id, self::$plugin_prefix . '_seventh_notice_ignore' )
		) {

			$sixth_ignore_time = get_user_meta( $user_id, self::$plugin_prefix . '_sixth_notice_ignore_time' );
			$ts_seven_days     = strtotime( '+7 Days', $sixth_ignore_time[0] );
			if ( $current_time > $ts_seven_days ) {

				$add_query_arguments = add_query_arg( self::$plugin_prefix . '_seventh_notice_ignore', '0' );
				$cancel_button       = '<a href="' . $add_query_arguments . '" class="dashicons dashicons-dismiss dashicons-dismiss-icon" style="position: absolute; top: 8px; right: 8px; color: #222; opacity: 0.4; text-decoration: none !important;"></a>';

				printf( '<div class="%1$s" style="%2$s"><p>%3$s %4$s</p></div>', $class, $style, self::$ts_pro_notices[7], $cancel_button );
			}
		}

		if ( get_user_meta( $user_id, self::$plugin_prefix . '_sixth_notice_ignore' ) &&
				get_user_meta( $user_id, self::$plugin_prefix . '_seventh_notice_ignore' ) &&
			! get_user_meta( $user_id, self::$plugin_prefix . '_eigth_notice_ignore' )
		) {

			$seventh_ignore_time = get_user_meta( $user_id, self::$plugin_prefix . '_seventh_notice_ignore_time' );
			$ts_seven_days       = strtotime( '+7 Days', $seventh_ignore_time[0] );
			if ( $current_time > $ts_seven_days ) {

				$add_query_arguments = add_query_arg( self::$plugin_prefix . '_eigth_notice_ignore', '0' );
				$cancel_button       = '<a href="' . $add_query_arguments . '" class="dashicons dashicons-dismiss dashicons-dismiss-icon" style="position: absolute; top: 8px; right: 8px; color: #222; opacity: 0.4; text-decoration: none !important;"></a>';

				printf( '<div class="%1$s" style="%2$s"><p>%3$s %4$s</p></div>', $class, $style, self::$ts_pro_notices[8], $cancel_button );
			}
		}

		if ( get_user_meta( $user_id, self::$plugin_prefix . '_sixth_notice_ignore' ) &&
				get_user_meta( $user_id, self::$plugin_prefix . '_seventh_notice_ignore' ) &&
				get_user_meta( $user_id, self::$plugin_prefix . '_eigth_notice_ignore' ) &&
			! get_user_meta( $user_id, self::$plugin_prefix . '_ninth_notice_ignore' )
		) {

			$eigth_ignore_time = get_user_meta( $user_id, self::$plugin_prefix . '_eigth_notice_ignore_time' );
			$ts_seven_days     = strtotime( '+7 Days', $eigth_ignore_time[0] );
			if ( $current_time > $ts_seven_days ) {

				$add_query_arguments = add_query_arg( self::$plugin_prefix . '_ninth_notice_ignore', '0' );
				$cancel_button       = '<a href="' . $add_query_arguments . '" class="dashicons dashicons-dismiss dashicons-dismiss-icon" style="position: absolute; top: 8px; right: 8px; color: #222; opacity: 0.4; text-decoration: none !important;"></a>';

				printf( '<div class="%1$s" style="%2$s"><p>%3$s %4$s</p></div>', $class, $style, self::$ts_pro_notices[9], $cancel_button );
			}
		}
	}

	/**
	 * Ignore notices & update the time for it.
	 */
	public static function ts_ignore_pro_notices() {
		$user_id = get_current_user_id();
		// If user clicks to ignore the notice, add that to their user meta
		if ( isset( $_GET[ self::$plugin_prefix . '_first_notice_ignore' ] ) && '0' === $_GET[ self::$plugin_prefix . '_first_notice_ignore' ] ) {
			add_user_meta( $user_id, self::$plugin_prefix . '_first_notice_ignore', 'true', true );
			add_user_meta( $user_id, self::$plugin_prefix . '_first_notice_ignore_time', current_time( 'timestamp' ), true );
			wp_safe_redirect( remove_query_arg( self::$plugin_prefix . '_first_notice_ignore' ) );

		}

		if ( isset( $_GET[ self::$plugin_prefix . '_second_notice_ignore' ] ) && '0' === $_GET[ self::$plugin_prefix . '_second_notice_ignore' ] ) {
			add_user_meta( $user_id, self::$plugin_prefix . '_second_notice_ignore', 'true', true );
			add_user_meta( $user_id, self::$plugin_prefix . '_second_notice_ignore_time', current_time( 'timestamp' ), true );
			wp_safe_redirect( remove_query_arg( self::$plugin_prefix . '_second_notice_ignore' ) );
		}

		if ( isset( $_GET[ self::$plugin_prefix . '_third_notice_ignore' ] ) && '0' === $_GET[ self::$plugin_prefix . '_third_notice_ignore' ] ) {
			add_user_meta( $user_id, self::$plugin_prefix . '_third_notice_ignore', 'true', true );
			add_user_meta( $user_id, self::$plugin_prefix . '_third_notice_ignore_time', current_time( 'timestamp' ), true );
			wp_safe_redirect( remove_query_arg( self::$plugin_prefix . '_third_notice_ignore' ) );
		}

		if ( isset( $_GET[ self::$plugin_prefix . '_fourth_notice_ignore' ] ) && '0' === $_GET[ self::$plugin_prefix . '_fourth_notice_ignore' ] ) {
			add_user_meta( $user_id, self::$plugin_prefix . '_fourth_notice_ignore', 'true', true );
			add_user_meta( $user_id, self::$plugin_prefix . '_fourth_notice_ignore_time', current_time( 'timestamp' ), true );
			wp_safe_redirect( remove_query_arg( self::$plugin_prefix . '_fourth_notice_ignore' ) );
		}

		if ( isset( $_GET[ self::$plugin_prefix . '_fifth_notice_ignore' ] ) && '0' === $_GET[ self::$plugin_prefix . '_fifth_notice_ignore' ] ) {
			add_user_meta( $user_id, self::$plugin_prefix . '_fifth_notice_ignore', 'true', true );
			add_user_meta( $user_id, self::$plugin_prefix . '_fifth_notice_ignore_time', current_time( 'timestamp' ), true );
			wp_safe_redirect( remove_query_arg( self::$plugin_prefix . '_fifth_notice_ignore' ) );
		}

		if ( isset( $_GET[ self::$plugin_prefix . '_sixth_notice_ignore' ] ) && '0' === $_GET[ self::$plugin_prefix . '_sixth_notice_ignore' ] ) {
			add_user_meta( $user_id, self::$plugin_prefix . '_sixth_notice_ignore', 'true', true );
			add_user_meta( $user_id, self::$plugin_prefix . '_sixth_notice_ignore_time', current_time( 'timestamp' ), true );
			wp_safe_redirect( remove_query_arg( self::$plugin_prefix . '_sixth_notice_ignore' ) );
		}

		if ( isset( $_GET[ self::$plugin_prefix . '_seventh_notice_ignore' ] ) && '0' === $_GET[ self::$plugin_prefix . '_seventh_notice_ignore' ] ) {
			add_user_meta( $user_id, self::$plugin_prefix . '_seventh_notice_ignore', 'true', true );
			add_user_meta( $user_id, self::$plugin_prefix . '_seventh_notice_ignore_time', current_time( 'timestamp' ), true );
			wp_safe_redirect( remove_query_arg( self::$plugin_prefix . '_seventh_notice_ignore' ) );
		}

		if ( isset( $_GET[ self::$plugin_prefix . '_eigth_notice_ignore' ] ) && '0' === $_GET[ self::$plugin_prefix . '_eigth_notice_ignore' ] ) {
			add_user_meta( $user_id, self::$plugin_prefix . '_eigth_notice_ignore', 'true', true );
			add_user_meta( $user_id, self::$plugin_prefix . '_eigth_notice_ignore_time', current_time( 'timestamp' ), true );
			wp_safe_redirect( remove_query_arg( self::$plugin_prefix . '_eigth_notice_ignore' ) );
		}

		if ( isset( $_GET[ self::$plugin_prefix . '_ninth_notice_ignore' ] ) && '0' === $_GET[ self::$plugin_prefix . '_ninth_notice_ignore' ] ) {
			add_user_meta( $user_id, self::$plugin_prefix . '_ninth_notice_ignore', 'true', true );
			add_user_meta( $user_id, self::$plugin_prefix . '_ninth_notice_ignore_time', current_time( 'timestamp' ), true );
			wp_safe_redirect( remove_query_arg( self::$plugin_prefix . '_ninth_notice_ignore' ) );//phpcs:enable
		}
	}
}
