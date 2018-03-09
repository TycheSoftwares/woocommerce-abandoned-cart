<?php
/**
 * Abandoned Cart Lite for WooCommerce
 *
 * It will manage the tracking of the plugin data.
 *
 * @author  Tyche Softwares
 * @package Abandoned-Cart-Lite-for-WooCommerce/Admin/Tracking-Data
 */
 
include_once( 'classes/class-wcal-ts-tracker.php' );

/**
 * It will have all the data for tracking the data.
 * @since 3.9
 */
class Wcal_TS_Tracking {
	/**
	 * It will add all the necessary action for tracking the data.
	 * @since 3.9
	 */
	public function __construct() {
		//Tracking Data
		add_action( 'admin_notices',               array( &$this, 'wcal_track_usage_data' ), 10 );
		add_action( 'admin_footer',                array( &$this, 'wcal_admin_notices_scripts' ) );
		add_action( 'wp_ajax_wcal_admin_notices',  array( &$this, 'wcal_admin_notices' ) );
	}

	/**
	 * It will add the js for dismissible notice.
	 * @since 3.9
	 */
	public static function wcal_admin_notices_scripts() {
		wp_enqueue_script( 'wcal_admin_dismissal_notice', plugins_url() . '/woocommerce-abandoned-cart/assets/js/wcal_ts_dismiss_notice.js' );
    }

    /**
     * It will the admin notice.
     * @since 3.9
     */
    public static function wcal_admin_notices() {
    	Class_Wcal_Ts_Tracker::wcal_ts_send_tracking_data( false );
        update_option( 'wcal_allow_tracking', 'dismissed' );
		die();
    }

	/**
	 * It will check the selected admin action it will be either allow or not allow.
	 * @since 3.9
	 */
	private function wcal_ts_tracking_actions() {

		if ( isset( $_GET[ 'wcal_tracker_optin' ] ) && isset( $_GET[ 'wcal_tracker_nonce' ] ) && wp_verify_nonce( $_GET[ 'wcal_tracker_nonce' ], 'wcal_tracker_optin' ) ) {
			update_option( 'wcal_allow_tracking', 'yes' );
			Class_Wcal_Ts_Tracker::wcal_ts_send_tracking_data( true );
			header( 'Location: ' . $_SERVER[ 'HTTP_REFERER' ] );
		} elseif ( isset( $_GET[ 'wcal_tracker_optout' ] ) && isset( $_GET[ 'wcal_tracker_nonce' ] ) && wp_verify_nonce( $_GET[ 'wcal_tracker_nonce' ], 'wcal_tracker_optout' ) ) {
			update_option( 'wcal_allow_tracking', 'no' );
			Class_Wcal_Ts_Tracker::wcal_ts_send_tracking_data( false );
			header( 'Location: ' . $_SERVER[ 'HTTP_REFERER' ] );
		}
	}

	/**
	 * It will add the notice on the admin side.
	 * @since 3.9
	 */
	function wcal_track_usage_data() {
		$wcal_admin_url = get_admin_url();
		echo '<input type="hidden" id="admin_url" value="' . $wcal_admin_url . '"/>';
		$this->wcal_ts_tracking_actions();
		if ( 'unknown' === get_option( 'wcal_allow_tracking', 'unknown' ) ) : ?>
			<div class="wcal-message wcal-tracker notice notice-info is-dismissible" style="position: relative;">
				<div style="position: absolute;"><img class="site-logo" src="<?php echo plugins_url(); ?>/woocommerce-abandoned-cart/assets/images/site-logo-new.jpg"></div>
				<p style="margin: 10px 0 10px 130px; font-size: medium;">
					<?php print( __( 'Want to help make Abandoned Cart even more awesome? Allow Abandoned Cart to collect non-sensitive diagnostic data and usage information and get 20% off on your next purchase. <a href="https://www.tychesoftwares.com/abandoned-cart-lite-usage-tracking/" target="_blank">Find out more</a>. <br><br>', 'woocommerce-abandoned-cart' ) ); ?></p>
				<p class="submit">
					<a class="button-primary button button-large" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wcal_tracker_optin', 'true' ), 'wcal_tracker_optin', 'wcal_tracker_nonce' ) ); ?>"><?php esc_html_e( 'Allow', 'woocommerce-abandoned-cart' ); ?></a>
					<a class="button-secondary button button-large skip"  href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wcal_tracker_optout', 'true' ), 'wcal_tracker_optout', 'wcal_tracker_nonce' ) ); ?>"><?php esc_html_e( 'No thanks', 'woocommerce-abandoned-cart' ); ?></a>
				</p>
			</div>
		<?php endif;
	}
}
$TS_tracking = new Wcal_TS_Tracking();