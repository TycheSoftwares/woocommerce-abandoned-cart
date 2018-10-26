<?php
if( !class_exists( 'WP_Async_Request' ) ) {
    include_once( WP_PLUGIN_DIR . '/woocommerce/includes/libraries/wp-async-request.php' );
    include_once( WP_PLUGIN_DIR . '/woocommerce/includes/libraries/wp-background-process.php' );
}
class WCAL_Async_Request extends WP_Async_Request {

	

	/**
	 * @var string
	 */
	protected $action = 'wcal_single_request';

	/**
	 * Handle
	 *
	 * Override this method to perform any actions required
	 * during the async request.
	 */
	protected function handle() {

	    $reminder_method = $_POST[ 'method' ];
	    
	    if( isset( $reminder_method ) ) {

	        switch( $reminder_method ) {
	            case 'emails':
	                $wcal_cron = new woocommerce_abandon_cart_cron();
	                $wcal_cron->wcal_send_email_notification();
                    break;
	        }
	    }
	}

}
